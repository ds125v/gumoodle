<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cron functions.
 *
 * @package    core
 * @subpackage admin
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute cron tasks
 */
function cron_run() {
    global $DB, $CFG, $OUTPUT;

    if (CLI_MAINTENANCE) {
        echo "CLI maintenance mode active, cron execution suspended.\n";
        exit(1);
    }

    if (moodle_needs_upgrading()) {
        echo "Moodle upgrade pending, cron execution suspended.\n";
        exit(1);
    }

    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/gradelib.php');

    if (!empty($CFG->showcronsql)) {
        $DB->set_debug(true);
    }
    if (!empty($CFG->showcrondebugging)) {
        $CFG->debug = DEBUG_DEVELOPER;
        $CFG->debugdisplay = true;
    }

    set_time_limit(0);
    $starttime = microtime();

    // Increase memory limit
    raise_memory_limit(MEMORY_EXTRA);

    // Emulate normal session - we use admin accoutn by default
    cron_setup_user();

    // Start output log
    $timenow  = time();
    mtrace("Server Time: ".date('r',$timenow)."\n\n");


    // Run cleanup core cron jobs, but not every time since they aren't too important.
    // These don't have a timer to reduce load, so we'll use a random number
    // to randomly choose the percentage of times we should run these jobs.
    srand ((double) microtime() * 10000000);
    $random100 = rand(0,100);
    if ($random100 < 20) {     // Approximately 20% of the time.
        mtrace("Running clean-up tasks...");

        // Delete users who haven't confirmed within required period
        if (!empty($CFG->deleteunconfirmed)) {
            $cuttime = $timenow - ($CFG->deleteunconfirmed * 3600);
            $rs = $DB->get_recordset_sql ("SELECT *
                                             FROM {user}
                                            WHERE confirmed = 0 AND firstaccess > 0
                                                  AND firstaccess < ?", array($cuttime));
            foreach ($rs as $user) {
                delete_user($user); // we MUST delete user properly first
                $DB->delete_records('user', array('id'=>$user->id)); // this is a bloody hack, but it might work
                mtrace(" Deleted unconfirmed user for ".fullname($user, true)." ($user->id)");
            }
            $rs->close();
        }


        // Delete users who haven't completed profile within required period
        if (!empty($CFG->deleteincompleteusers)) {
            $cuttime = $timenow - ($CFG->deleteincompleteusers * 3600);
            $rs = $DB->get_recordset_sql ("SELECT *
                                             FROM {user}
                                            WHERE confirmed = 1 AND lastaccess > 0
                                                  AND lastaccess < ? AND deleted = 0
                                                  AND (lastname = '' OR firstname = '' OR email = '')",
                                          array($cuttime));
            foreach ($rs as $user) {
                delete_user($user);
                mtrace(" Deleted not fully setup user $user->username ($user->id)");
            }
            $rs->close();
        }


        // Delete old logs to save space (this might need a timer to slow it down...)
        if (!empty($CFG->loglifetime)) {  // value in days
            $loglifetime = $timenow - ($CFG->loglifetime * 3600 * 24);
            $DB->delete_records_select("log", "time < ?", array($loglifetime));
            mtrace(" Deleted old log records");
        }


        // Delete old backup_controllers and logs.
        $loglifetime = get_config('backup', 'loglifetime');
        if (!empty($loglifetime)) {  // Value in days.
            $loglifetime = $timenow - ($loglifetime * 3600 * 24);
            // Delete child records from backup_logs.
            $DB->execute("DELETE FROM {backup_logs}
                           WHERE EXISTS (
                               SELECT 'x'
                                 FROM {backup_controllers} bc
                                WHERE bc.backupid = {backup_logs}.backupid
                                  AND bc.timecreated < ?)", array($loglifetime));
            // Delete records from backup_controllers.
            $DB->execute("DELETE FROM {backup_controllers}
                          WHERE timecreated < ?", array($loglifetime));
            mtrace(" Deleted old backup records");
        }


        // Delete old cached texts
        if (!empty($CFG->cachetext)) {   // Defined in config.php
            $cachelifetime = time() - $CFG->cachetext - 60;  // Add an extra minute to allow for really heavy sites
            $DB->delete_records_select('cache_text', "timemodified < ?", array($cachelifetime));
            mtrace(" Deleted old cache_text records");
        }


        if (!empty($CFG->usetags)) {
            require_once($CFG->dirroot.'/tag/lib.php');
            tag_cron();
            mtrace(' Executed tag cron');
        }


        // Context maintenance stuff
        context_helper::cleanup_instances();
        mtrace(' Cleaned up context instances');
        context_helper::build_all_paths(false);
        // If you suspect that the context paths are somehow corrupt
        // replace the line below with: context_helper::build_all_paths(true);
        mtrace(' Built context paths');


        // Remove expired cache flags
        gc_cache_flags();
        mtrace(' Cleaned cache flags');


        // Cleanup messaging
        if (!empty($CFG->messagingdeletereadnotificationsdelay)) {
            $notificationdeletetime = time() - $CFG->messagingdeletereadnotificationsdelay;
            $DB->delete_records_select('message_read', 'notification=1 AND timeread<:notificationdeletetime', array('notificationdeletetime'=>$notificationdeletetime));
            mtrace(' Cleaned up read notifications');
        }

        mtrace("...finished clean-up tasks");

    } // End of occasional clean-up tasks


    // Send login failures notification - brute force protection in moodle is weak,
    // we should at least send notices early in each cron execution
    if (notify_login_failures()) {
        mtrace(' Notified login failures');
    }


    // Make sure all context instances are properly created - they may be required in auth, enrol, etc.
    context_helper::create_instances();
    mtrace(' Created missing context instances');


    // Session gc
    session_gc();
    mtrace("Cleaned up stale user sessions");


    // Run the auth cron, if any before enrolments
    // because it might add users that will be needed in enrol plugins
    $auths = get_enabled_auth_plugins();
    mtrace("Running auth crons if required...");
    foreach ($auths as $auth) {
        $authplugin = get_auth_plugin($auth);
        if (method_exists($authplugin, 'cron')) {
            mtrace("Running cron for auth/$auth...");
            $authplugin->cron();
            if (!empty($authplugin->log)) {
                mtrace($authplugin->log);
            }
        }
        unset($authplugin);
    }
    // Generate new password emails for users - ppl expect these generated asap
    if ($DB->count_records('user_preferences', array('name'=>'create_password', 'value'=>'1'))) {
        mtrace('Creating passwords for new users...');
        $newusers = $DB->get_recordset_sql("SELECT u.id as id, u.email, u.firstname,
                                                 u.lastname, u.username, u.lang,
                                                 p.id as prefid
                                            FROM {user} u
                                            JOIN {user_preferences} p ON u.id=p.userid
                                           WHERE p.name='create_password' AND p.value='1' AND u.email !='' AND u.suspended = 0 AND u.auth != 'nologin' AND u.deleted = 0");

        // note: we can not send emails to suspended accounts
        foreach ($newusers as $newuser) {
            if (setnew_password_and_mail($newuser)) {
                unset_user_preference('create_password', $newuser);
                set_user_preference('auth_forcepasswordchange', 1, $newuser);
            } else {
                trigger_error("Could not create and mail new user password!");
            }
        }
        $newusers->close();
    }


    // It is very important to run enrol early
    // because other plugins depend on correct enrolment info.
    mtrace("Running enrol crons if required...");
    $enrols = enrol_get_plugins(true);
    foreach($enrols as $ename=>$enrol) {
        // do this for all plugins, disabled plugins might want to cleanup stuff such as roles
        if (!$enrol->is_cron_required()) {
            continue;
        }
        mtrace("Running cron for enrol_$ename...");
        $enrol->cron();
        $enrol->set_config('lastcron', time());
    }


    // Run all cron jobs for each module
    mtrace("Starting activity modules");
    get_mailer('buffer');
    if ($mods = $DB->get_records_select("modules", "cron > 0 AND ((? - lastcron) > cron) AND visible = 1", array($timenow))) {
        foreach ($mods as $mod) {
            $libfile = "$CFG->dirroot/mod/$mod->name/lib.php";
            if (file_exists($libfile)) {
                include_once($libfile);
                $cron_function = $mod->name."_cron";
                if (function_exists($cron_function)) {
                    mtrace("Processing module function $cron_function ...", '');
                    $pre_dbqueries = null;
                    $pre_dbqueries = $DB->perf_get_queries();
                    $pre_time      = microtime(1);
                    if ($cron_function()) {
                        $DB->set_field("modules", "lastcron", $timenow, array("id"=>$mod->id));
                    }
                    if (isset($pre_dbqueries)) {
                        mtrace("... used " . ($DB->perf_get_queries() - $pre_dbqueries) . " dbqueries");
                        mtrace("... used " . (microtime(1) - $pre_time) . " seconds");
                    }
                    // Reset possible changes by modules to time_limit. MDL-11597
                    @set_time_limit(0);
                    mtrace("done.");
                }
            }
        }
    }
    get_mailer('close');
    mtrace("Finished activity modules");


    mtrace("Starting blocks");
    if ($blocks = $DB->get_records_select("block", "cron > 0 AND ((? - lastcron) > cron) AND visible = 1", array($timenow))) {
        // We will need the base class.
        require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
        foreach ($blocks as $block) {
            $blockfile = $CFG->dirroot.'/blocks/'.$block->name.'/block_'.$block->name.'.php';
            if (file_exists($blockfile)) {
                require_once($blockfile);
                $classname = 'block_'.$block->name;
                $blockobj = new $classname;
                if (method_exists($blockobj,'cron')) {
                    mtrace("Processing cron function for ".$block->name.'....','');
                    if ($blockobj->cron()) {
                        $DB->set_field('block', 'lastcron', $timenow, array('id'=>$block->id));
                    }
                    // Reset possible changes by blocks to time_limit. MDL-11597
                    @set_time_limit(0);
                    mtrace('done.');
                }
            }

        }
    }
    mtrace('Finished blocks');


    mtrace('Starting admin reports');
    cron_execute_plugin_type('report');
    mtrace('Finished admin reports');


    mtrace('Starting main gradebook job...');
    grade_cron();
    mtrace('done.');


    mtrace('Starting processing the event queue...');
    events_cron();
    mtrace('done.');


    if ($CFG->enablecompletion) {
        // Completion cron
        mtrace('Starting the completion cron...');
        require_once($CFG->libdir . '/completion/cron.php');
        completion_cron();
        mtrace('done');
    }


    if ($CFG->enableportfolios) {
        // Portfolio cron
        mtrace('Starting the portfolio cron...');
        require_once($CFG->libdir . '/portfoliolib.php');
        portfolio_cron();
        mtrace('done');
    }


    //now do plagiarism checks
    require_once($CFG->libdir.'/plagiarismlib.php');
    plagiarism_cron();


    mtrace('Starting course reports');
    cron_execute_plugin_type('coursereport');
    mtrace('Finished course reports');


    // run gradebook import/export/report cron
    mtrace('Starting gradebook plugins');
    cron_execute_plugin_type('gradeimport');
    cron_execute_plugin_type('gradeexport');
    cron_execute_plugin_type('gradereport');
    mtrace('Finished gradebook plugins');


    // Run external blog cron if needed
    if ($CFG->useexternalblogs) {
        require_once($CFG->dirroot . '/blog/lib.php');
        mtrace("Fetching external blog entries...", '');
        $sql = "timefetched < ? OR timefetched = 0";
        $externalblogs = $DB->get_records_select('blog_external', $sql, array(time() - $CFG->externalblogcrontime));

        foreach ($externalblogs as $eb) {
            blog_sync_external_entries($eb);
        }
        mtrace('done.');
    }
    // Run blog associations cleanup
    if ($CFG->useblogassociations) {
        require_once($CFG->dirroot . '/blog/lib.php');
        // delete entries whose contextids no longer exists
        mtrace("Deleting blog associations linked to non-existent contexts...", '');
        $DB->delete_records_select('blog_association', 'contextid NOT IN (SELECT id FROM {context})');
        mtrace('done.');
    }


    //Run registration updated cron
    mtrace(get_string('siteupdatesstart', 'hub'));
    require_once($CFG->dirroot . '/' . $CFG->admin . '/registration/lib.php');
    $registrationmanager = new registration_manager();
    $registrationmanager->cron();
    mtrace(get_string('siteupdatesend', 'hub'));

    // If enabled, fetch information about available updates and eventually notify site admins
    if (empty($CFG->disableupdatenotifications)) {
        require_once($CFG->libdir.'/pluginlib.php');
        $updateschecker = available_update_checker::instance();
        $updateschecker->cron();
    }

    //cleanup old session linked tokens
    //deletes the session linked tokens that are over a day old.
    mtrace("Deleting session linked tokens more than one day old...", '');
    $DB->delete_records_select('external_tokens', 'lastaccess < :onedayago AND tokentype = :tokentype',
                    array('onedayago' => time() - DAYSECS, 'tokentype' => EXTERNAL_TOKEN_EMBEDDED));
    mtrace('done.');


    // all other plugins
    cron_execute_plugin_type('message', 'message plugins');
    cron_execute_plugin_type('filter', 'filters');
    cron_execute_plugin_type('editor', 'editors');
    cron_execute_plugin_type('format', 'course formats');
    cron_execute_plugin_type('profilefield', 'profile fields');
    cron_execute_plugin_type('webservice', 'webservices');
    cron_execute_plugin_type('repository', 'repository plugins');
    cron_execute_plugin_type('qbehaviour', 'question behaviours');
    cron_execute_plugin_type('qformat', 'question import/export formats');
    cron_execute_plugin_type('qtype', 'question types');
    cron_execute_plugin_type('plagiarism', 'plagiarism plugins');
    cron_execute_plugin_type('theme', 'themes');
    cron_execute_plugin_type('tool', 'admin tools');


    // and finally run any local cronjobs, if any
    if ($locals = get_plugin_list('local')) {
        mtrace('Processing customized cron scripts ...', '');
        // new cron functions in lib.php first
        cron_execute_plugin_type('local');
        // legacy cron files are executed directly
        foreach ($locals as $local => $localdir) {
            if (file_exists("$localdir/cron.php")) {
                include("$localdir/cron.php");
            }
        }
        mtrace('done.');
    }


    // Run automated backups if required - these may take a long time to execute
    require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot.'/backup/util/helper/backup_cron_helper.class.php');
    backup_cron_automated_helper::run_automated_backup();


    // Run stats as at the end because they are known to take very long time on large sites
    if (!empty($CFG->enablestats) and empty($CFG->disablestatsprocessing)) {
        require_once($CFG->dirroot.'/lib/statslib.php');
        // check we're not before our runtime
        $timetocheck = stats_get_base_daily() + $CFG->statsruntimestarthour*60*60 + $CFG->statsruntimestartminute*60;

        if (time() > $timetocheck) {
            // process configured number of days as max (defaulting to 31)
            $maxdays = empty($CFG->statsruntimedays) ? 31 : abs($CFG->statsruntimedays);
            if (stats_cron_daily($maxdays)) {
                if (stats_cron_weekly()) {
                    if (stats_cron_monthly()) {
                        stats_clean_old();
                    }
                }
            }
            @set_time_limit(0);
        } else {
            mtrace('Next stats run after:'. userdate($timetocheck));
        }
    }


    // cleanup file trash - not very important
    $fs = get_file_storage();
    $fs->cron();

    mtrace("Clean up cached external files");
    // 1 week
    cache_file::cleanup(array(), 60 * 60 * 24 * 7);

    mtrace("Cron script completed correctly");

    $difftime = microtime_diff($starttime, microtime());
    mtrace("Execution took ".$difftime." seconds");
}

/**
 * Executes cron functions for a specific type of plugin.
 *
 * @param string $plugintype Plugin type (e.g. 'report')
 * @param string $description If specified, will display 'Starting (whatever)'
 *   and 'Finished (whatever)' lines, otherwise does not display
 */
function cron_execute_plugin_type($plugintype, $description = null) {
    global $DB;

    // Get list from plugin => function for all plugins
    $plugins = get_plugin_list_with_function($plugintype, 'cron');

    // Modify list for backward compatibility (different files/names)
    $plugins = cron_bc_hack_plugin_functions($plugintype, $plugins);

    // Return if no plugins with cron function to process
    if (!$plugins) {
        return;
    }

    if ($description) {
        mtrace('Starting '.$description);
    }

    foreach ($plugins as $component=>$cronfunction) {
        $dir = get_component_directory($component);

        // Get cron period if specified in version.php, otherwise assume every cron
        $cronperiod = 0;
        if (file_exists("$dir/version.php")) {
            $plugin = new stdClass();
            include("$dir/version.php");
            if (isset($plugin->cron)) {
                $cronperiod = $plugin->cron;
            }
        }

        // Using last cron and cron period, don't run if it already ran recently
        $lastcron = get_config($component, 'lastcron');
        if ($cronperiod && $lastcron) {
            if ($lastcron + $cronperiod > time()) {
                // do not execute cron yet
                continue;
            }
        }

        mtrace('Processing cron function for ' . $component . '...');
        $pre_dbqueries = $DB->perf_get_queries();
        $pre_time = microtime(true);

        $cronfunction();

        mtrace("done. (" . ($DB->perf_get_queries() - $pre_dbqueries) . " dbqueries, " .
                round(microtime(true) - $pre_time, 2) . " seconds)");

        set_config('lastcron', time(), $component);
        @set_time_limit(0);
    }

    if ($description) {
        mtrace('Finished ' . $description);
    }
}

/**
 * Used to add in old-style cron functions within plugins that have not been converted to the
 * new standard API. (The standard API is frankenstyle_name_cron() in lib.php; some types used
 * cron.php and some used a different name.)
 *
 * @param string $plugintype Plugin type e.g. 'report'
 * @param array $plugins Array from plugin name (e.g. 'report_frog') to function name (e.g.
 *   'report_frog_cron') for plugin cron functions that were already found using the new API
 * @return array Revised version of $plugins that adds in any extra plugin functions found by
 *   looking in the older location
 */
function cron_bc_hack_plugin_functions($plugintype, $plugins) {
    global $CFG; // mandatory in case it is referenced by include()d PHP script

    if ($plugintype === 'report') {
        // Admin reports only - not course report because course report was
        // never implemented before, so doesn't need BC
        foreach (get_plugin_list($plugintype) as $pluginname=>$dir) {
            $component = $plugintype . '_' . $pluginname;
            if (isset($plugins[$component])) {
                // We already have detected the function using the new API
                continue;
            }
            if (!file_exists("$dir/cron.php")) {
                // No old style cron file present
                continue;
            }
            include_once("$dir/cron.php");
            $cronfunction = $component . '_cron';
            if (function_exists($cronfunction)) {
                $plugins[$component] = $cronfunction;
            } else {
                debugging("Invalid legacy cron.php detected in $component, " .
                        "please use lib.php instead");
            }
        }
    } else if (strpos($plugintype, 'grade') === 0) {
        // Detect old style cron function names
        // Plugin gradeexport_frog used to use grade_export_frog_cron() instead of
        // new standard API gradeexport_frog_cron(). Also applies to gradeimport, gradereport
        foreach(get_plugin_list($plugintype) as $pluginname=>$dir) {
            $component = $plugintype.'_'.$pluginname;
            if (isset($plugins[$component])) {
                // We already have detected the function using the new API
                continue;
            }
            if (!file_exists("$dir/lib.php")) {
                continue;
            }
            include_once("$dir/lib.php");
            $cronfunction = str_replace('grade', 'grade_', $plugintype) . '_' .
                    $pluginname . '_cron';
            if (function_exists($cronfunction)) {
                $plugins[$component] = $cronfunction;
            }
        }
    }

    return $plugins;
}


/**
 * Notify admin users or admin user of any failed logins (since last notification).
 *
 * Note that this function must be only executed from the cron script
 * It uses the cache_flags system to store temporary records, deleting them
 * by name before finishing
 *
 * @return bool True if executed, false if not
 */
function notify_login_failures() {
    global $CFG, $DB, $OUTPUT;

    if (empty($CFG->notifyloginfailures)) {
        return false;
    }

    $recip = get_users_from_config($CFG->notifyloginfailures, 'moodle/site:config');

    if (empty($CFG->lastnotifyfailure)) {
        $CFG->lastnotifyfailure=0;
    }

    // If it has been less than an hour, or if there are no recipients, don't execute.
    if (((time() - HOURSECS) < $CFG->lastnotifyfailure) || !is_array($recip) || count($recip) <= 0) {
        return false;
    }

    // we need to deal with the threshold stuff first.
    if (empty($CFG->notifyloginthreshold)) {
        $CFG->notifyloginthreshold = 10; // default to something sensible.
    }

    // Get all the IPs with more than notifyloginthreshold failures since lastnotifyfailure
    // and insert them into the cache_flags temp table
    $sql = "SELECT ip, COUNT(*)
              FROM {log}
             WHERE module = 'login' AND action = 'error'
                   AND time > ?
          GROUP BY ip
            HAVING COUNT(*) >= ?";
    $params = array($CFG->lastnotifyfailure, $CFG->notifyloginthreshold);
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $iprec) {
        if (!empty($iprec->ip)) {
            set_cache_flag('login_failure_by_ip', $iprec->ip, '1', 0);
        }
    }
    $rs->close();

    // Get all the INFOs with more than notifyloginthreshold failures since lastnotifyfailure
    // and insert them into the cache_flags temp table
    $sql = "SELECT info, count(*)
              FROM {log}
             WHERE module = 'login' AND action = 'error'
                   AND time > ?
          GROUP BY info
            HAVING count(*) >= ?";
    $params = array($CFG->lastnotifyfailure, $CFG->notifyloginthreshold);
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $inforec) {
        if (!empty($inforec->info)) {
            set_cache_flag('login_failure_by_info', $inforec->info, '1', 0);
        }
    }
    $rs->close();

    // Now, select all the login error logged records belonging to the ips and infos
    // since lastnotifyfailure, that we have stored in the cache_flags table
    $sql = "SELECT * FROM (
        SELECT l.*, u.firstname, u.lastname
              FROM {log} l
              JOIN {cache_flags} cf ON l.ip = cf.name
         LEFT JOIN {user} u         ON l.userid = u.id
             WHERE l.module = 'login' AND l.action = 'error'
                   AND l.time > ?
                   AND cf.flagtype = 'login_failure_by_ip'
        UNION ALL
            SELECT l.*, u.firstname, u.lastname
              FROM {log} l
              JOIN {cache_flags} cf ON l.info = cf.name
         LEFT JOIN {user} u         ON l.userid = u.id
             WHERE l.module = 'login' AND l.action = 'error'
                   AND l.time > ?
                   AND cf.flagtype = 'login_failure_by_info') t
        ORDER BY t.time DESC";
    $params = array($CFG->lastnotifyfailure, $CFG->lastnotifyfailure);

    // Init some variables
    $count = 0;
    $messages = '';
    // Iterate over the logs recordset
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $log) {
        $log->time = userdate($log->time);
        $messages .= get_string('notifyloginfailuresmessage','',$log)."\n";
        $count++;
    }
    $rs->close();

    // If we have something useful to report.
    if ($count > 0) {
        $site = get_site();
        $subject = get_string('notifyloginfailuressubject', '', format_string($site->fullname));
        // Calculate the complete body of notification (start + messages + end)
        $body = get_string('notifyloginfailuresmessagestart', '', $CFG->wwwroot) .
                (($CFG->lastnotifyfailure != 0) ? '('.userdate($CFG->lastnotifyfailure).')' : '')."\n\n" .
                $messages .
                "\n\n".get_string('notifyloginfailuresmessageend','',$CFG->wwwroot)."\n\n";

        // For each destination, send mail
        mtrace('Emailing admins about '. $count .' failed login attempts');
        foreach ($recip as $admin) {
            //emailing the admins directly rather than putting these through the messaging system
            email_to_user($admin, generate_email_supportuser(), $subject, $body);
        }
    }

    // Update lastnotifyfailure with current time
    set_config('lastnotifyfailure', time());

    // Finally, delete all the temp records we have created in cache_flags
    $DB->delete_records_select('cache_flags', "flagtype IN ('login_failure_by_ip', 'login_failure_by_info')");

    return true;
}
