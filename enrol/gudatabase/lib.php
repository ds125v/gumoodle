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
 * UofG Database enrolment plugin.
 *
 * This plugin synchronises enrolment and roles with external database table.
 *
 * @package    enrol
 * @subpackage gudatabase
 * @copyright  2012 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We inherit from vanilla database plugin.
require_once( $CFG->dirroot . '/enrol/database/lib.php' );

/**
 * UofG Database enrolment plugin implementation.
 * @author  Howard Miller - inherited from code by Petr Skoda
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_gudatabase_plugin extends enrol_database_plugin {

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function instance_deleteable($instance) {
        return true;
    }

    /**
     * Does this plugin assign protected roles are can they be manually removed?
     * @return bool - false means anybody may tweak roles, it does not use itemid and component when assigning roles
     */
    public function roles_protected() {
        return true;
    }


    /**
     * Does this plugin allow manual unenrolment of a specific user?
     * Yes, but only if user suspended...
     *
     * @param stdClass $instance course enrol instance
     * @param stdClass $ue record from user_enrolments table
     *
     * @return bool - true means user with 'enrol/xxx:unenrol'
     * may unenrol this user, false means nobody may touch this user enrolment
     */
    public function allow_unenrol_user(stdClass $instance, stdClass $ue) {
        return true;
    }

    /**
     * Does this plugin allow manual changes in user_enrolments table?
     *
     * All plugins allowing this must implement 'enrol/xxx:manage' capability
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means it is possible to change enrol period and status in user_enrolments table
     */
    public function allow_manage(stdClass $instance) {
        return true;
    }

    /**
     * Does this plugin allow manual unenrolment of all users?
     * All plugins allowing this must implement 'enrol/xxx:unenrol' capability
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means user with 'enrol/xxx:unenrol'
     * may unenrol others freely, false means nobody may touch user_enrolments
     */
    public function allow_unenrol(stdClass $instance) {
        return true;
    }

    /**
     * Gets an array of the user enrolment actions
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol_user($instance, $ue) && has_capability('enrol/gudatabase:unenrol', $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'),
                    $url, array('class' => 'unenrollink', 'rel' => $ue->id));
        }
        return $actions;
    }

    /** 
     * synchronise enrollments for particular course
     * @param object $course
     */
    public function sync_course_enrolments($course) {
        global $CFG, $DB;

        // Check that plugin is vaguely configured.
        if (!$this->get_config('dbtype') or
                !$this->get_config('dbhost') or
                !$this->get_config('remoteenroltable') or
                !$this->get_config('remotecoursefield') or
                !$this->get_config('remoteuserfield')) {
            return false;
        }
    }

    /**
     * split the course code into an array accounting
     * for multiple delimeters etc.
     * @param string $code (list of) course codes
     * @return array array of course codes
     */
    public function split_code( $code ) {

        // Split on comma or space.
        $codes = preg_split("/[\s,]+/", $code, null, PREG_SPLIT_NO_EMPTY );

        return $codes;
    }

    /**
     * get enrollment data from external table
     * @param array $codes list of course codes
     * @param string $userid user id
     * @return array 
     */
    public function external_enrolments( $codes=null, $userid=null ) {
        global $CFG, $DB;

        // Codes and userid can't both be null.
        if (!$codes && !$userid) {
            $this->error( 'A value must be supplied for codes or userid in external_enrolments' );
            return false;
        }

        // Connect to external db.
        if (!$extdb = $this->db_init()) {
            $this->error('Error while communicating with external enrolment database');
            return false;
        }

        // Get connection details.
        $table            = $this->get_config('remoteenroltable');
        $coursefield      = strtolower($this->get_config('remotecoursefield'));
        $userfield        = strtolower($this->get_config('remoteuserfield'));

        // Work out appropriate sql.
        $sql = "select * from $table where ";

        // If $codes is supplied.
        if (!empty( $codes )) {
            $quotedcodes = array();
            foreach ($codes as $code) {
                $quotedcodes[] = "'" . $this->db_addslashes($code) . "'";
            }
            $codestring = implode(',', $quotedcodes);
            $sql .= "$coursefield in ($codestring) ";
        } else if (!empty($userid)) {
            $sql .= "$userfield = '" . $this->db_addslashes($userid) . "'";
        }

        // Read the data from external db.
        $enrolments = array();
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                while ($row = $rs->FetchRow()) {
                    $enrolment = (object)$row;
                    $enrolments[] = $enrolment;
                }
            }
            $rs->Close();
        } else {
            $msg = $extdb->ErrorMsg();
            $this->error('Error executing query in UofG enrolment table "'.$msg.'" - '.$sql);
            return false;
        }

        $extdb->Close();
        return $enrolments;
    }

    /**
     * get course information from
     * external database
     * @param string $code course code
     * @return object course details (false if not found)
     */
    protected function external_coursedata( $code ) {
        global $CFG, $DB;

        // Connect to external db.
        if (!$extdb = $this->db_init()) {
            $this->error('Error while communicating with external enrolment database');
            return false;
        }

        // Get connection details.
        $table = $this->get_config('codesenroltable');

        // If table not defined then we can't do anything.
        if (empty($table)) {
            return false;
        }

        // Create the sql.
        $sql = "select * from $table where CourseCat='" . $this->db_addslashes($code) . "'";

        // And run the query.
        $data = false;
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                $row = $rs->FetchRow();
                $data = (object)$row;
            }
            $rs->Close();
        } else {
            $msg = $extdb->ErrorMsg();
            $this->error('Error executing query in UofG enrolment table "'.$msg.'" - '.$sql);

            return false;
        }

        if (!$data && (strpos($code, '*') !== false)) {
            $enrolments = $this->external_enrolments(array($code));
            if ($enrolments) {
                $data = new stdClass();
                $data->Crse_cd_Subject = '-';
                $data->Crse_cd_nbr = 0;
                $data->Crse_name = '-';
                $data->ou_name = '-';
                $data->ou_cd = 0;
            }
        }

        $extdb->Close();
        return $data;
    }

    /**
     * get course information from
     * external database by user
     * @param string $user user object
     * @return array of objects course details (false if not found)
     */
    protected function external_userdata( $user ) {
        global $CFG, $DB;

        // Connect to external db.
        if (!$extdb = $this->db_init()) {
            $this->error('Error while communicating with external enrolment database');
            return false;
        }

        // Get connection details.
        $table = $this->get_config('remoteenroltable');

        // GUIDs can't be trusted in the external database, So...
        // match on $user->idnumber against (external) matric_no.

        // Create the sql. In the event idnumber (matric number)
        // not specified, just need to go with username (GUID).
        $sql = "select * from $table where ";
        if (empty($user->idnumber)) {
            $sql .= " UserName = '" . $this->db_addslashes($user->username) . "'";
        } else {
            $sql .= " matric_no = '" . $this->db_addslashes($user->idnumber) . "'";
        }

        // And run the query.
        $data = array();
        if ($rs = $extdb->Execute($sql)) {
            while (!$rs->EOF) {
                $row = $rs->FetchRow();
                $data[] = (object)$row;
            }
            $rs->Close();
        }

        $extdb->Close();
        return $data;
    }

    /**
     * utility function to get user's list of (external) courses
     * in form suitable for report
     * @param string $guid user's GUID
     * @return array list of courses
     */
    public function get_user_courses( $guid ) {

        // If it looks like a student guid then make the matric no
        // which is more reliable.
        $guid = strtolower( $guid );
        if (preg_match('/^\d+[a-z]$/', $guid)) {
            $matric = substr( $guid, 0, -1 );
        } else {
            $matric = '';
        }

        // Make a fake user object.
        $user = new stdClass();
        $user->username = $guid;
        $user->idnumber = $matric;

        // Get the courses.
        if (!$courses = $this->external_userdata( $user )) {
            return false;
        }

        // Add the courses information.
        foreach ($courses as $course) {
            $code = $course->courses;
            if ($coursedata = $this->external_coursedata( $code )) {
                $course->name = fix_utf8($coursedata->Crse_name);
                $course->ou = $coursedata->ou_name;
            } else {
                $course->name = '-';
                $course->ou = '-';
            }
        }

        return $courses;
    }

    /**
     * Creates a bare-bones user record
     * Copied (and modified) from moodlelib.php
     *
     * @param string $username New user's username to add to record
     * @param string $matricid New user's matriculation number
     * @return stdClass A complete user object
     */
    public function create_user_record($username, $matricid) {
        global $CFG, $DB;

        // Just in case check text case.
        $username = trim(textlib::strtolower($username));

        // We will be using 'guid' ldap plugin only.
        $authplugin = get_auth_plugin('guid');

        // Build up new user object.
        $newuser = new stdClass();

        // Get user info from guid auth plugin.
        if ($newinfo = $authplugin->get_userinfo($username, $matricid)) {
            $newinfo = truncate_userinfo($newinfo);
            foreach ($newinfo as $key => $value) {
                $newuser->$key = $value;
            }
        }

        // From here on in the username will be the uid (if it
        // exists). This is the definitive GUID.
        if (!empty($newuser->uid)) {
            $username = trim(textlib::strtolower($newuser->uid));
            $newuser->username = $username;
        }

        // Check for dodgy email.
        if (!empty($newuser->email)) {
            if (email_is_not_allowed($newuser->email)) {
                unset($newuser->email);
            }
        }

        // This shouldn't happen, but default city is
        // always Glasgow.
        if (!isset($newuser->city)) {
            $newuser->city = 'Glasgow';
        }

        // Fix for MDL-8480
        // user CFG lang for user if $newuser->lang is empty
        // or $user->lang is not an installed language.
        if (empty($newuser->lang) || !get_string_manager()->translation_exists($newuser->lang)) {
            $newuser->lang = $CFG->lang;
        }

        // Basic settings.
        $newuser->auth = 'guid';
        $newuser->username = $username;
        $newuser->confirmed = 1;
        $newuser->lastip = getremoteaddr();
        $newuser->timecreated = time();
        $newuser->timemodified = $newuser->timecreated;
        $newuser->mnethostid = $CFG->mnet_localhost_id;

        $newuser->id = $DB->insert_record('user', $newuser);
        $user = get_complete_user_data('id', $newuser->id);
        update_internal_user_password($user, '');

        // Fetch full user record for the event, the complete user data contains too much info
        // and we want to be consistent with other places that trigger this event.
        events_trigger('user_created', $DB->get_record('user', array('id' => $user->id)));

        return $user;
    }

    /** 
     * save codes:
     * maintain a table of codes versus course
     * so we can use in cron and reports
     * NB: we will check it exists here too
     * @param object $course
     * @param array $codes list of codes
     */
    public function save_codes( $course, $codes ) {
        global $CFG, $DB;

        // Run through codes finding data.
        foreach ($codes as $code) {
            $coursedata = $this->external_coursedata( $code );

            // It's possible (and ok) that nothing is found.
            if (!empty($coursedata)) {

                // Create data record.
                $coursecode = new stdClass;
                $coursecode->code = $code;
                $coursecode->courseid = $course->id;
                $coursecode->subject = $coursedata->Crse_cd_Subject;

                // COCK UP: these codes can contain letters at the end
                // but we'll just strip them off for now.
                $coursecode->coursenumber = clean_param($coursedata->Crse_cd_nbr, PARAM_INT);
                $coursecode->coursename = fix_utf8($coursedata->Crse_name);
                $coursecode->subjectname = fix_utf8($coursedata->ou_name);
                $coursecode->subjectnumber = $coursedata->ou_cd;

                // Is there already a record for this combination.
                if ($record = $DB->get_record( 'enrol_gudatabase_codes', array('code' => $code, 'courseid' => $course->id))) {
                    $coursecode->id = $record->id;
                    $DB->update_record( 'enrol_gudatabase_codes', $coursecode );
                } else {
                    $coursecode->timeadded = time();
                    $DB->insert_record( 'enrol_gudatabase_codes', $coursecode );
                }
            }
        }

        // Now need to check if there are entries for that course
        // that should be deleted.
        $entries = $DB->get_records( 'enrol_gudatabase_codes', array( 'courseid' => $course->id ));
        if (!empty($entries)) {
            foreach ($entries as $entry) {
                if (!in_array($entry->code, $codes)) {
                    $DB->delete_records( 'enrol_gudatabase_codes', array( 'id' => $entry->id ));
                }
            }
        }
    }

    /**
     * cache user enrolment
     * @param object $course
     * @param object $user
     * @param string $code
     */
    private function cache_user_enrolment( $course, $user, $code) {
        global $DB;

        // Construct database object.
        $courseuser = new stdClass;
        $courseuser->userid = $user->id;
        $courseuser->courseid = $course->id;
        $courseuser->code = $code;
        $courseuser->timeupdated = time();

        // Insert or update?
        if ($record = $DB->get_record('enrol_gudatabase_users', array('userid' => $user->id, 'courseid' => $course->id))) {
            $courseuser->id = $record->id;
            $DB->update_record( 'enrol_gudatabase_users', $courseuser );
        } else {
            $DB->insert_record( 'enrol_gudatabase_users', $courseuser );
        }
    }

    /**
     * Get enrollments for given course
     * and add users
     * @parm object $course 
     * @param object $instance of enrol plugin
     * @return boolean success
     */
    public function enrol_course_users( $course, $instance ) {
        global $CFG, $DB;

        // First need to get a list of possible course codes
        // we will aggregate single code from course shortname
        // and (possible) list from idnumber.
        $shortname = $course->shortname;
        $idnumber = $course->idnumber;
        $codes = $this->split_code( $idnumber );
        $codes[] = clean_param( $shortname, PARAM_ALPHANUM );

        // Cache the codes against the course.
        $this->save_codes( $course, $codes );

        // Find the default role .
        $defaultrole = $this->get_config('defaultrole');

        // Get the external data for these codes.
        $enrolments = $this->external_enrolments( $codes );
        if ($enrolments === false) {
            return false;
        }

        // Iterate over the enrolments and deal.
        foreach ($enrolments as $enrolment) {
            $username = $enrolment->UserName;
            $matricno = $enrolment->matric_no;

            // Can we find this user?
            // Check against idnumber <=> matric_no if possible
            // NOTE: the username in enrol database should be correct but some
            //       are not. The matricno<=>idnumber is definitive however.
            if (!$user = $DB->get_record( 'user', array('username' => $username))) {

                // If we get here, couldn't find with username, so
                // let's just have another go with idnumber.
                if (!$user = $DB->get_record( 'user', array('idnumber' => $matricno))) {
                    $user = $this->create_user_record( $username, $matricno );
                }
            }

            // Enrol user into course.
            $this->enrol_user( $instance, $user->id, $defaultrole, 0, 0, ENROL_USER_ACTIVE );

            // Cache enrolment.
            $this->cache_user_enrolment( $course, $user, $enrolment->courses );
        }

        return true;
    }

    /**
     * check if course has instance of this plugin
     * add if not
     * @param object $course
     * @return int instanceid
     */
    private function check_instance( $course ) {

        // Get all instances in this course.
        $instances = enrol_get_instances( $course->id, true );

        // Search for this one.
        $found = false;
        foreach ($instances as $instance) {
            if ($instance->enrol == $this->get_name()) {
                $found = true;
                $instanceid = $instance->id;
            }
        }

        // If we didn't find it then add it.
        if (!$found) {
            $instanceid = $this->add_instance($course);
        }

        return $instanceid;
    }

    /**
     * Called after updating/inserting course.
     *
     * @param bool $inserted true if course just inserted
     * @param object $course
     * @param object $data form data
     * @return void
     */
    public function course_updated($inserted, $course, $data) {
        global $DB;

        // If course is not visible we don't do anything.
        if (!$course->visible) {
            return true;
        }

        // Make sure we have config.
        $this->load_config();

        // We want all our new courses to have this plugin.
        if ($inserted) {
            $instanceid = $this->add_instance($course);
        } else {
            $instanceid = $this->check_instance( $course );
        }

        // Get the instance of the enrolment plugin.
        $instance = $DB->get_record('enrol', array('id' => $instanceid));

        // Add the users to the course.
        $this->enrol_course_users( $course, $instance );

        return true;
    }

    /**
     * synchronise enrollments when user logs in
     * TODO: this needs to actually do something.
     *
     * @param object $user user record
     * @return void
     */
    public function sync_user_enrolments($user) {
        global $CFG, $DB;

        // This is just a bodge to kill this for admin users.
        $admins = explode( ',', $CFG->siteadmins );
        if (in_array($user->id, $admins)) {
            return true;
        }

        // Get the list of courses for current user.
        $enrolments = $this->external_userdata( $user );

        // If there aren't any then there's nothing to see here.
        if (empty($enrolments)) {
            return true;
        }

        // There could be duplicate courses going this way, so we'll
        // build an array to filter them out.
        $uniquecourses = array();

        // Go through list of codes and find the courses.
        foreach ($enrolments as $enrolment) {

            // We need to find the courses in our own table of courses
            // to allow for multiple codes.
            $codes = $DB->get_records('enrol_gudatabase_codes', array('code' => $enrolment->courses));
            if (!empty($codes)) {
                foreach ($codes as $code) {
                    $uniquecourses[ $code->courseid ] = $code;
                }
            }
        }

        // Find the default role .
        $defaultrole = $this->get_config('defaultrole');

        // Go through the list of course codes and enrol student.
        if (!empty($uniquecourses)) {
            foreach ($uniquecourses as $courseid => $code) {

                // Get course object.
                if (!$course = $DB->get_record('course', array('id' => $courseid))) {
                    continue;
                }

                // Make sure it has this enrolment plugin.
                $instanceid = $this->check_instance( $course );

                // Get the instance of the enrolment plugin.
                $instance = $DB->get_record('enrol', array('id' => $instanceid));

                // Enroll user into course.
                $this->enrol_user( $instance, $user->id, $defaultrole, 0, 0, ENROL_USER_ACTIVE );

                // Cache enrolment.
                $this->cache_user_enrolment( $course, $user, $code->code );
            }
        }

        return true;
    }

    /**
     * cron service to update course enrolments
     */
    public function cron() {
        global $CFG;
        global $DB;

        // Get the start time, we'll limit
        // how long this runs for.
        $starttime = time();

        // Get plugin config.
        $config = get_config( 'enrol_gudatabase' );

        // Are we set up?
        if (empty($config->dbhost)) {
            mtrace( 'enrol_gudatabase: not configured' );
            return false;
        }

        // Get the last course index we processed.
        if (empty($config->startcourseindex)) {
            $startcourseindex = 0;
        } else {
            $startcourseindex = $config->startcourseindex;
        }
        mtrace( "enrol_gudatabase: starting at course index $startcourseindex" );

        // Get the basics of all visible courses
        // don't load the whole course records!!
        $courses = $DB->get_records( 'course', array('visible' => 1), '', 'id' );

        // Convert courses to simple array.
        $courses = array_values( $courses );
        $highestindex = count($courses) - 1;
        mtrace( "enrol_gudatabase: highest course index is $highestindex" );
        mtrace( "enrol_gudatabase: configured time limit is {$config->timelimit} seconds" );

        // Process from current index to (potentially) the end.
        for ($i = $startcourseindex; $i <= $highestindex; $i++) {
            $course = $DB->get_record('course', array('id' => $courses[$i]->id));

            // Avoid site and front page.
            if ($course->id > 1) {
                $updatestart = microtime(true);
                mtrace( "enrol_gudatabase: updating enrolments for course '{$course->shortname}'" );
                $this->course_updated(false, $course, null);
                $updateend = microtime(true);
                $updatetime = number_format($updateend - $updatestart, 4);
                mtrace( "enrol_gudatabase: --- course {$course->shortname} took $updatetime seconds to update");
            }
            $lastcourseprocessed = $i;

            // If we've used too much time then bail out.
            $elapsedtime = time() - $starttime;
            if ($elapsedtime > $config->timelimit) {
                break;
            }
        }

        // Set new value of index.
        if ($lastcourseprocessed >= $highestindex) {
            $nextcoursetoprocess = 0;
        } else {
            $nextcoursetoprocess = $lastcourseprocessed + 1;
        }
        set_config( 'startcourseindex', $nextcoursetoprocess, 'enrol_gudatabase' );
        mtrace( "enrol_gudatabase: next course index to process is $nextcoursetoprocess" );

        // Create very poor average course process.
        $oldaverage = empty($config->average) ? 0 : $config->average;
        $newaverage = ($oldaverage + $lastcourseprocessed - $startcourseindex) / 2;
        set_config( 'average', $newaverage, 'enrol_gudatabase' );
        $elapsedtime = time() - $starttime;
        mtrace( 'enrol_gudatabase: completed, processed courses = ' . ($lastcourseprocessed - $startcourseindex) );
        mtrace( "enrol_gudatabase: actual elapsed time was $elapsedtime seconds" );
    }

    /**
     * Handle error messages appropriately
     * for cli or web based operation
     * @param string $message message to display/log
     */
    private function error($message) {
        if (defined('CLI_SCRIPT')) {
            mtrace($message);
        } else {
            error($message);
        }
    }

    /**
     * Automatic enrol sync executed during restore.
     * TODO: This needs to do something or justify why not
     * @param stdClass $course course record
     */
    public function restore_sync_course($course) {
        $this->course_updated(false, $course, null);
    }
}

