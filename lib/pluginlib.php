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
 * Defines classes used for plugins management
 *
 * This library provides a unified interface to various plugin types in
 * Moodle. It is mainly used by the plugins management admin page and the
 * plugins check page during the upgrade.
 *
 * @package    core
 * @subpackage admin
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');  // curl class needed here

/**
 * Singleton class providing general plugins management functionality
 */
class plugin_manager {

    /** the plugin is shipped with standard Moodle distribution */
    const PLUGIN_SOURCE_STANDARD    = 'std';
    /** the plugin is added extension */
    const PLUGIN_SOURCE_EXTENSION   = 'ext';

    /** the plugin uses neither database nor capabilities, no versions */
    const PLUGIN_STATUS_NODB        = 'nodb';
    /** the plugin is up-to-date */
    const PLUGIN_STATUS_UPTODATE    = 'uptodate';
    /** the plugin is about to be installed */
    const PLUGIN_STATUS_NEW         = 'new';
    /** the plugin is about to be upgraded */
    const PLUGIN_STATUS_UPGRADE     = 'upgrade';
    /** the standard plugin is about to be deleted */
    const PLUGIN_STATUS_DELETE     = 'delete';
    /** the version at the disk is lower than the one already installed */
    const PLUGIN_STATUS_DOWNGRADE   = 'downgrade';
    /** the plugin is installed but missing from disk */
    const PLUGIN_STATUS_MISSING     = 'missing';

    /** @var plugin_manager holds the singleton instance */
    protected static $singletoninstance;
    /** @var array of raw plugins information */
    protected $pluginsinfo = null;
    /** @var array of raw subplugins information */
    protected $subpluginsinfo = null;

    /**
     * Direct initiation not allowed, use the factory method {@link self::instance()}
     */
    protected function __construct() {
    }

    /**
     * Sorry, this is singleton
     */
    protected function __clone() {
    }

    /**
     * Factory method for this class
     *
     * @return plugin_manager the singleton instance
     */
    public static function instance() {
        if (is_null(self::$singletoninstance)) {
            self::$singletoninstance = new self();
        }
        return self::$singletoninstance;
    }

    /**
     * Returns a tree of known plugins and information about them
     *
     * @param bool $disablecache force reload, cache can be used otherwise
     * @return array 2D array. The first keys are plugin type names (e.g. qtype);
     *      the second keys are the plugin local name (e.g. multichoice); and
     *      the values are the corresponding objects extending {@link plugininfo_base}
     */
    public function get_plugins($disablecache=false) {
        global $CFG;

        if ($disablecache or is_null($this->pluginsinfo)) {
            $this->pluginsinfo = array();
            $plugintypes = get_plugin_types();
            $plugintypes = $this->reorder_plugin_types($plugintypes);
            foreach ($plugintypes as $plugintype => $plugintyperootdir) {
                if (in_array($plugintype, array('base', 'general'))) {
                    throw new coding_exception('Illegal usage of reserved word for plugin type');
                }
                if (class_exists('plugininfo_' . $plugintype)) {
                    $plugintypeclass = 'plugininfo_' . $plugintype;
                } else {
                    $plugintypeclass = 'plugininfo_general';
                }
                if (!in_array('plugininfo_base', class_parents($plugintypeclass))) {
                    throw new coding_exception('Class ' . $plugintypeclass . ' must extend plugininfo_base');
                }
                $plugins = call_user_func(array($plugintypeclass, 'get_plugins'), $plugintype, $plugintyperootdir, $plugintypeclass);
                $this->pluginsinfo[$plugintype] = $plugins;
            }

            if (empty($CFG->disableupdatenotifications) and !during_initial_install()) {
                // append the information about available updates provided by {@link available_update_checker()}
                $provider = available_update_checker::instance();
                foreach ($this->pluginsinfo as $plugintype => $plugins) {
                    foreach ($plugins as $plugininfoholder) {
                        $plugininfoholder->check_available_updates($provider);
                    }
                }
            }
        }

        return $this->pluginsinfo;
    }

    /**
     * Returns list of plugins that define their subplugins and the information
     * about them from the db/subplugins.php file.
     *
     * At the moment, only activity modules can define subplugins.
     *
     * @param bool $disablecache force reload, cache can be used otherwise
     * @return array with keys like 'mod_quiz', and values the data from the
     *      corresponding db/subplugins.php file.
     */
    public function get_subplugins($disablecache=false) {

        if ($disablecache or is_null($this->subpluginsinfo)) {
            $this->subpluginsinfo = array();
            $mods = get_plugin_list('mod');
            foreach ($mods as $mod => $moddir) {
                $modsubplugins = array();
                if (file_exists($moddir . '/db/subplugins.php')) {
                    include($moddir . '/db/subplugins.php');
                    foreach ($subplugins as $subplugintype => $subplugintyperootdir) {
                        $subplugin = new stdClass();
                        $subplugin->type = $subplugintype;
                        $subplugin->typerootdir = $subplugintyperootdir;
                        $modsubplugins[$subplugintype] = $subplugin;
                    }
                $this->subpluginsinfo['mod_' . $mod] = $modsubplugins;
                }
            }
        }

        return $this->subpluginsinfo;
    }

    /**
     * Returns the name of the plugin that defines the given subplugin type
     *
     * If the given subplugin type is not actually a subplugin, returns false.
     *
     * @param string $subplugintype the name of subplugin type, eg. workshopform or quiz
     * @return false|string the name of the parent plugin, eg. mod_workshop
     */
    public function get_parent_of_subplugin($subplugintype) {

        $parent = false;
        foreach ($this->get_subplugins() as $pluginname => $subplugintypes) {
            if (isset($subplugintypes[$subplugintype])) {
                $parent = $pluginname;
                break;
            }
        }

        return $parent;
    }

    /**
     * Returns a localized name of a given plugin
     *
     * @param string $plugin name of the plugin, eg mod_workshop or auth_ldap
     * @return string
     */
    public function plugin_name($plugin) {
        list($type, $name) = normalize_component($plugin);
        return $this->pluginsinfo[$type][$name]->displayname;
    }

    /**
     * Returns a localized name of a plugin type in plural form
     *
     * Most plugin types define their names in core_plugin lang file. In case of subplugins,
     * we try to ask the parent plugin for the name. In the worst case, we will return
     * the value of the passed $type parameter.
     *
     * @param string $type the type of the plugin, e.g. mod or workshopform
     * @return string
     */
    public function plugintype_name_plural($type) {

        if (get_string_manager()->string_exists('type_' . $type . '_plural', 'core_plugin')) {
            // for most plugin types, their names are defined in core_plugin lang file
            return get_string('type_' . $type . '_plural', 'core_plugin');

        } else if ($parent = $this->get_parent_of_subplugin($type)) {
            // if this is a subplugin, try to ask the parent plugin for the name
            if (get_string_manager()->string_exists('subplugintype_' . $type . '_plural', $parent)) {
                return $this->plugin_name($parent) . ' / ' . get_string('subplugintype_' . $type . '_plural', $parent);
            } else {
                return $this->plugin_name($parent) . ' / ' . $type;
            }

        } else {
            return $type;
        }
    }

    /**
     * @param string $component frankenstyle component name.
     * @return plugininfo_base|null the corresponding plugin information.
     */
    public function get_plugin_info($component) {
        list($type, $name) = normalize_component($component);
        $plugins = $this->get_plugins();
        if (isset($plugins[$type][$name])) {
            return $plugins[$type][$name];
        } else {
            return null;
        }
    }

    /**
     * Get a list of any other plugins that require this one.
     * @param string $component frankenstyle component name.
     * @return array of frankensyle component names that require this one.
     */
    public function other_plugins_that_require($component) {
        $others = array();
        foreach ($this->get_plugins() as $type => $plugins) {
            foreach ($plugins as $plugin) {
                $required = $plugin->get_other_required_plugins();
                if (isset($required[$component])) {
                    $others[] = $plugin->component;
                }
            }
        }
        return $others;
    }

    /**
     * Check a dependencies list against the list of installed plugins.
     * @param array $dependencies compenent name to required version or ANY_VERSION.
     * @return bool true if all the dependencies are satisfied.
     */
    public function are_dependencies_satisfied($dependencies) {
        foreach ($dependencies as $component => $requiredversion) {
            $otherplugin = $this->get_plugin_info($component);
            if (is_null($otherplugin)) {
                return false;
            }

            if ($requiredversion != ANY_VERSION and $otherplugin->versiondisk < $requiredversion) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks all dependencies for all installed plugins
     *
     * This is used by install and upgrade. The array passed by reference as the second
     * argument is populated with the list of plugins that have failed dependencies (note that
     * a single plugin can appear multiple times in the $failedplugins).
     *
     * @param int $moodleversion the version from version.php.
     * @param array $failedplugins to return the list of plugins with non-satisfied dependencies
     * @return bool true if all the dependencies are satisfied for all plugins.
     */
    public function all_plugins_ok($moodleversion, &$failedplugins = array()) {

        $return = true;
        foreach ($this->get_plugins() as $type => $plugins) {
            foreach ($plugins as $plugin) {

                if (!$plugin->is_core_dependency_satisfied($moodleversion)) {
                    $return = false;
                    $failedplugins[] = $plugin->component;
                }

                if (!$this->are_dependencies_satisfied($plugin->get_other_required_plugins())) {
                    $return = false;
                    $failedplugins[] = $plugin->component;
                }
            }
        }

        return $return;
    }

    /**
     * Checks if there are some plugins with a known available update
     *
     * @return bool true if there is at least one available update
     */
    public function some_plugins_updatable() {
        foreach ($this->get_plugins() as $type => $plugins) {
            foreach ($plugins as $plugin) {
                if ($plugin->available_updates()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Defines a list of all plugins that were originally shipped in the standard Moodle distribution,
     * but are not anymore and are deleted during upgrades.
     *
     * The main purpose of this list is to hide missing plugins during upgrade.
     *
     * @param string $type plugin type
     * @param string $name plugin name
     * @return bool
     */
    public static function is_deleted_standard_plugin($type, $name) {
        static $plugins = array(
            // do not add 1.9-2.2 plugin removals here
        );

        if (!isset($plugins[$type])) {
            return false;
        }
        return in_array($name, $plugins[$type]);
    }

    /**
     * Defines a white list of all plugins shipped in the standard Moodle distribution
     *
     * @param string $type
     * @return false|array array of standard plugins or false if the type is unknown
     */
    public static function standard_plugins_list($type) {
        static $standard_plugins = array(

            'assignment' => array(
                'offline', 'online', 'upload', 'uploadsingle'
            ),

            'assignsubmission' => array(
                'comments', 'file', 'onlinetext'
            ),

            'assignfeedback' => array(
                'comments', 'file'
            ),

            'auth' => array(
                'cas', 'db', 'email', 'fc', 'imap', 'ldap', 'manual', 'mnet',
                'nntp', 'nologin', 'none', 'pam', 'pop3', 'radius',
                'shibboleth', 'webservice'
            ),

            'block' => array(
                'activity_modules', 'admin_bookmarks', 'blog_menu',
                'blog_recent', 'blog_tags', 'calendar_month',
                'calendar_upcoming', 'comments', 'community',
                'completionstatus', 'course_list', 'course_overview',
                'course_summary', 'feedback', 'glossary_random', 'html',
                'login', 'mentees', 'messages', 'mnet_hosts', 'myprofile',
                'navigation', 'news_items', 'online_users', 'participants',
                'private_files', 'quiz_results', 'recent_activity',
                'rss_client', 'search_forums', 'section_links',
                'selfcompletion', 'settings', 'site_main_menu',
                'social_activities', 'tag_flickr', 'tag_youtube', 'tags'
            ),

            'booktool' => array(
                'exportimscp', 'importhtml', 'print'
            ),

            'coursereport' => array(
                //deprecated!
            ),

            'datafield' => array(
                'checkbox', 'date', 'file', 'latlong', 'menu', 'multimenu',
                'number', 'picture', 'radiobutton', 'text', 'textarea', 'url'
            ),

            'datapreset' => array(
                'imagegallery'
            ),

            'editor' => array(
                'textarea', 'tinymce'
            ),

            'enrol' => array(
                'authorize', 'category', 'cohort', 'database', 'flatfile',
                'guest', 'imsenterprise', 'ldap', 'manual', 'meta', 'mnet',
                'paypal', 'self'
            ),

            'filter' => array(
                'activitynames', 'algebra', 'censor', 'emailprotect',
                'emoticon', 'mediaplugin', 'multilang', 'tex', 'tidy',
                'urltolink', 'data', 'glossary'
            ),

            'format' => array(
                'scorm', 'social', 'topics', 'weeks'
            ),

            'gradeexport' => array(
                'ods', 'txt', 'xls', 'xml'
            ),

            'gradeimport' => array(
                'csv', 'xml'
            ),

            'gradereport' => array(
                'grader', 'outcomes', 'overview', 'user'
            ),

            'gradingform' => array(
                'rubric', 'guide'
            ),

            'local' => array(
            ),

            'message' => array(
                'email', 'jabber', 'popup'
            ),

            'mnetservice' => array(
                'enrol'
            ),

            'mod' => array(
                'assign', 'assignment', 'book', 'chat', 'choice', 'data', 'feedback', 'folder',
                'forum', 'glossary', 'imscp', 'label', 'lesson', 'lti', 'page',
                'quiz', 'resource', 'scorm', 'survey', 'url', 'wiki', 'workshop'
            ),

            'plagiarism' => array(
            ),

            'portfolio' => array(
                'boxnet', 'download', 'flickr', 'googledocs', 'mahara', 'picasa'
            ),

            'profilefield' => array(
                'checkbox', 'datetime', 'menu', 'text', 'textarea'
            ),

            'qbehaviour' => array(
                'adaptive', 'adaptivenopenalty', 'deferredcbm',
                'deferredfeedback', 'immediatecbm', 'immediatefeedback',
                'informationitem', 'interactive', 'interactivecountback',
                'manualgraded', 'missing'
            ),

            'qformat' => array(
                'aiken', 'blackboard', 'blackboard_six', 'examview', 'gift',
                'learnwise', 'missingword', 'multianswer', 'webct',
                'xhtml', 'xml'
            ),

            'qtype' => array(
                'calculated', 'calculatedmulti', 'calculatedsimple',
                'description', 'essay', 'match', 'missingtype', 'multianswer',
                'multichoice', 'numerical', 'random', 'randomsamatch',
                'shortanswer', 'truefalse'
            ),

            'quiz' => array(
                'grading', 'overview', 'responses', 'statistics'
            ),

            'quizaccess' => array(
                'delaybetweenattempts', 'ipaddress', 'numattempts', 'openclosedate',
                'password', 'safebrowser', 'securewindow', 'timelimit'
            ),

            'report' => array(
                'backups', 'completion', 'configlog', 'courseoverview',
                'log', 'loglive', 'outline', 'participation', 'progress', 'questioninstances', 'security', 'stats'
            ),

            'repository' => array(
                'alfresco', 'boxnet', 'coursefiles', 'dropbox', 'equella', 'filesystem',
                'flickr', 'flickr_public', 'googledocs', 'local', 'merlot',
                'picasa', 'recent', 's3', 'upload', 'url', 'user', 'webdav',
                'wikimedia', 'youtube'
            ),

            'scormreport' => array(
                'basic',
                'interactions',
                'graphs'
            ),

            'theme' => array(
                'afterburner', 'anomaly', 'arialist', 'base', 'binarius',
                'boxxie', 'brick', 'canvas', 'formal_white', 'formfactor',
                'fusion', 'leatherbound', 'magazine', 'mymobile', 'nimble',
                'nonzero', 'overlay', 'serenity', 'sky_high', 'splash',
                'standard', 'standardold'
            ),

            'tool' => array(
                'assignmentupgrade', 'bloglevelupgrade', 'capability', 'customlang', 'dbtransfer', 'generator',
                'health', 'innodb', 'langimport', 'multilangupgrade', 'phpunit', 'profiling',
                'qeupgradehelper', 'replace', 'spamcleaner', 'timezoneimport', 'unittest',
                'uploaduser', 'unsuproles', 'xmldb'
            ),

            'webservice' => array(
                'amf', 'rest', 'soap', 'xmlrpc'
            ),

            'workshopallocation' => array(
                'manual', 'random', 'scheduled'
            ),

            'workshopeval' => array(
                'best'
            ),

            'workshopform' => array(
                'accumulative', 'comments', 'numerrors', 'rubric'
            )
        );

        if (isset($standard_plugins[$type])) {
            return $standard_plugins[$type];
        } else {
            return false;
        }
    }

    /**
     * Reorders plugin types into a sequence to be displayed
     *
     * For technical reasons, plugin types returned by {@link get_plugin_types()} are
     * in a certain order that does not need to fit the expected order for the display.
     * Particularly, activity modules should be displayed first as they represent the
     * real heart of Moodle. They should be followed by other plugin types that are
     * used to build the courses (as that is what one expects from LMS). After that,
     * other supportive plugin types follow.
     *
     * @param array $types associative array
     * @return array same array with altered order of items
     */
    protected function reorder_plugin_types(array $types) {
        $fix = array(
            'mod'        => $types['mod'],
            'block'      => $types['block'],
            'qtype'      => $types['qtype'],
            'qbehaviour' => $types['qbehaviour'],
            'qformat'    => $types['qformat'],
            'filter'     => $types['filter'],
            'enrol'      => $types['enrol'],
        );
        foreach ($types as $type => $path) {
            if (!isset($fix[$type])) {
                $fix[$type] = $path;
            }
        }
        return $fix;
    }
}


/**
 * General exception thrown by the {@link available_update_checker} class
 */
class available_update_checker_exception extends moodle_exception {

    /**
     * @param string $errorcode exception description identifier
     * @param mixed $debuginfo debugging data to display
     */
    public function __construct($errorcode, $debuginfo=null) {
        parent::__construct($errorcode, 'core_plugin', '', null, print_r($debuginfo, true));
    }
}


/**
 * Singleton class that handles checking for available updates
 */
class available_update_checker {

    /** @var available_update_checker holds the singleton instance */
    protected static $singletoninstance;
    /** @var null|int the timestamp of when the most recent response was fetched */
    protected $recentfetch = null;
    /** @var null|array the recent response from the update notification provider */
    protected $recentresponse = null;
    /** @var null|string the numerical version of the local Moodle code */
    protected $currentversion = null;
    /** @var null|string the release info of the local Moodle code */
    protected $currentrelease = null;
    /** @var null|string branch of the local Moodle code */
    protected $currentbranch = null;
    /** @var array of (string)frankestyle => (string)version list of additional plugins deployed at this site */
    protected $currentplugins = array();

    /**
     * Direct initiation not allowed, use the factory method {@link self::instance()}
     */
    protected function __construct() {
    }

    /**
     * Sorry, this is singleton
     */
    protected function __clone() {
    }

    /**
     * Factory method for this class
     *
     * @return available_update_checker the singleton instance
     */
    public static function instance() {
        if (is_null(self::$singletoninstance)) {
            self::$singletoninstance = new self();
        }
        return self::$singletoninstance;
    }

    /**
     * Returns the timestamp of the last execution of {@link fetch()}
     *
     * @return int|null null if it has never been executed or we don't known
     */
    public function get_last_timefetched() {

        $this->restore_response();

        if (!empty($this->recentfetch)) {
            return $this->recentfetch;

        } else {
            return null;
        }
    }

    /**
     * Fetches the available update status from the remote site
     *
     * @throws available_update_checker_exception
     */
    public function fetch() {
        $response = $this->get_response();
        $this->validate_response($response);
        $this->store_response($response);
    }

    /**
     * Returns the available update information for the given component
     *
     * This method returns null if the most recent response does not contain any information
     * about it. The returned structure is an array of available updates for the given
     * component. Each update info is an object with at least one property called
     * 'version'. Other possible properties are 'release', 'maturity', 'url' and 'downloadurl'.
     *
     * For the 'core' component, the method returns real updates only (those with higher version).
     * For all other components, the list of all known remote updates is returned and the caller
     * (usually the {@link plugin_manager}) is supposed to make the actual comparison of versions.
     *
     * @param string $component frankenstyle
     * @param array $options with supported keys 'minmaturity' and/or 'notifybuilds'
     * @return null|array null or array of available_update_info objects
     */
    public function get_update_info($component, array $options = array()) {

        if (!isset($options['minmaturity'])) {
            $options['minmaturity'] = 0;
        }

        if (!isset($options['notifybuilds'])) {
            $options['notifybuilds'] = false;
        }

        if ($component == 'core') {
            $this->load_current_environment();
        }

        $this->restore_response();

        if (empty($this->recentresponse['updates'][$component])) {
            return null;
        }

        $updates = array();
        foreach ($this->recentresponse['updates'][$component] as $info) {
            $update = new available_update_info($component, $info);
            if (isset($update->maturity) and ($update->maturity < $options['minmaturity'])) {
                continue;
            }
            if ($component == 'core') {
                if ($update->version <= $this->currentversion) {
                    continue;
                }
                if (empty($options['notifybuilds']) and $this->is_same_release($update->release)) {
                    continue;
                }
            }
            $updates[] = $update;
        }

        if (empty($updates)) {
            return null;
        }

        return $updates;
    }

    /**
     * The method being run via cron.php
     */
    public function cron() {
        global $CFG;

        if (!$this->cron_autocheck_enabled()) {
            $this->cron_mtrace('Automatic check for available updates not enabled, skipping.');
            return;
        }

        $now = $this->cron_current_timestamp();

        if ($this->cron_has_fresh_fetch($now)) {
            $this->cron_mtrace('Recently fetched info about available updates is still fresh enough, skipping.');
            return;
        }

        if ($this->cron_has_outdated_fetch($now)) {
            $this->cron_mtrace('Outdated or missing info about available updates, forced fetching ... ', '');
            $this->cron_execute();
            return;
        }

        $offset = $this->cron_execution_offset();
        $start = mktime(1, 0, 0, date('n', $now), date('j', $now), date('Y', $now)); // 01:00 AM today local time
        if ($now > $start + $offset) {
            $this->cron_mtrace('Regular daily check for available updates ... ', '');
            $this->cron_execute();
            return;
        }
    }

    /// end of public API //////////////////////////////////////////////////////

    /**
     * Makes cURL request to get data from the remote site
     *
     * @return string raw request result
     * @throws available_update_checker_exception
     */
    protected function get_response() {
        $curl = new curl(array('proxy' => true));
        $response = $curl->post($this->prepare_request_url(), $this->prepare_request_params());
        $curlinfo = $curl->get_info();
        if ($curlinfo['http_code'] != 200) {
            throw new available_update_checker_exception('err_response_http_code', $curlinfo['http_code']);
        }
        return $response;
    }

    /**
     * Makes sure the response is valid, has correct API format etc.
     *
     * @param string $response raw response as returned by the {@link self::get_response()}
     * @throws available_update_checker_exception
     */
    protected function validate_response($response) {

        $response = $this->decode_response($response);

        if (empty($response)) {
            throw new available_update_checker_exception('err_response_empty');
        }

        if (empty($response['status']) or $response['status'] !== 'OK') {
            throw new available_update_checker_exception('err_response_status', $response['status']);
        }

        if (empty($response['apiver']) or $response['apiver'] !== '1.0') {
            throw new available_update_checker_exception('err_response_format_version', $response['apiver']);
        }

        if (empty($response['forbranch']) or $response['forbranch'] !== moodle_major_version(true)) {
            throw new available_update_checker_exception('err_response_target_version', $response['forbranch']);
        }
    }

    /**
     * Decodes the raw string response from the update notifications provider
     *
     * @param string $response as returned by {@link self::get_response()}
     * @return array decoded response structure
     */
    protected function decode_response($response) {
        return json_decode($response, true);
    }

    /**
     * Stores the valid fetched response for later usage
     *
     * This implementation uses the config_plugins table as the permanent storage.
     *
     * @param string $response raw valid data returned by {@link self::get_response()}
     */
    protected function store_response($response) {

        set_config('recentfetch', time(), 'core_plugin');
        set_config('recentresponse', $response, 'core_plugin');

        $this->restore_response(true);
    }

    /**
     * Loads the most recent raw response record we have fetched
     *
     * After this method is called, $this->recentresponse is set to an array. If the
     * array is empty, then either no data have been fetched yet or the fetched data
     * do not have expected format (and thence they are ignored and a debugging
     * message is displayed).
     *
     * This implementation uses the config_plugins table as the permanent storage.
     *
     * @param bool $forcereload reload even if it was already loaded
     */
    protected function restore_response($forcereload = false) {

        if (!$forcereload and !is_null($this->recentresponse)) {
            // we already have it, nothing to do
            return;
        }

        $config = get_config('core_plugin');

        if (!empty($config->recentresponse) and !empty($config->recentfetch)) {
            try {
                $this->validate_response($config->recentresponse);
                $this->recentfetch = $config->recentfetch;
                $this->recentresponse = $this->decode_response($config->recentresponse);
            } catch (available_update_checker_exception $e) {
                debugging('Invalid info about available updates detected and will be ignored: '.$e->getMessage(), DEBUG_ALL);
                $this->recentresponse = array();
            }

        } else {
            $this->recentresponse = array();
        }
    }

    /**
     * Compares two raw {@link $recentresponse} records and returns the list of changed updates
     *
     * This method is used to populate potential update info to be sent to site admins.
     *
     * @param array $old
     * @param array $new
     * @throws available_update_checker_exception
     * @return array parts of $new['updates'] that have changed
     */
    protected function compare_responses(array $old, array $new) {

        if (empty($new)) {
            return array();
        }

        if (!array_key_exists('updates', $new)) {
            throw new available_update_checker_exception('err_response_format');
        }

        if (empty($old)) {
            return $new['updates'];
        }

        if (!array_key_exists('updates', $old)) {
            throw new available_update_checker_exception('err_response_format');
        }

        $changes = array();

        foreach ($new['updates'] as $newcomponent => $newcomponentupdates) {
            if (empty($old['updates'][$newcomponent])) {
                $changes[$newcomponent] = $newcomponentupdates;
                continue;
            }
            foreach ($newcomponentupdates as $newcomponentupdate) {
                $inold = false;
                foreach ($old['updates'][$newcomponent] as $oldcomponentupdate) {
                    if ($newcomponentupdate['version'] == $oldcomponentupdate['version']) {
                        $inold = true;
                    }
                }
                if (!$inold) {
                    if (!isset($changes[$newcomponent])) {
                        $changes[$newcomponent] = array();
                    }
                    $changes[$newcomponent][] = $newcomponentupdate;
                }
            }
        }

        return $changes;
    }

    /**
     * Returns the URL to send update requests to
     *
     * During the development or testing, you can set $CFG->alternativeupdateproviderurl
     * to a custom URL that will be used. Otherwise the standard URL will be returned.
     *
     * @return string URL
     */
    protected function prepare_request_url() {
        global $CFG;

        if (!empty($CFG->alternativeupdateproviderurl)) {
            return $CFG->alternativeupdateproviderurl;
        } else {
            return 'http://download.moodle.org/api/1.0/updates.php';
        }
    }

    /**
     * Sets the properties currentversion, currentrelease, currentbranch and currentplugins
     *
     * @param bool $forcereload
     */
    protected function load_current_environment($forcereload=false) {
        global $CFG;

        if (!is_null($this->currentversion) and !$forcereload) {
            // nothing to do
            return;
        }

        require($CFG->dirroot.'/version.php');
        $this->currentversion = $version;
        $this->currentrelease = $release;
        $this->currentbranch = moodle_major_version(true);

        $pluginman = plugin_manager::instance();
        foreach ($pluginman->get_plugins() as $type => $plugins) {
            foreach ($plugins as $plugin) {
                if (!$plugin->is_standard()) {
                    $this->currentplugins[$plugin->component] = $plugin->versiondisk;
                }
            }
        }
    }

    /**
     * Returns the list of HTTP params to be sent to the updates provider URL
     *
     * @return array of (string)param => (string)value
     */
    protected function prepare_request_params() {
        global $CFG;

        $this->load_current_environment();
        $this->restore_response();

        $params = array();
        $params['format'] = 'json';

        if (isset($this->recentresponse['ticket'])) {
            $params['ticket'] = $this->recentresponse['ticket'];
        }

        if (isset($this->currentversion)) {
            $params['version'] = $this->currentversion;
        } else {
            throw new coding_exception('Main Moodle version must be already known here');
        }

        if (isset($this->currentbranch)) {
            $params['branch'] = $this->currentbranch;
        } else {
            throw new coding_exception('Moodle release must be already known here');
        }

        $plugins = array();
        foreach ($this->currentplugins as $plugin => $version) {
            $plugins[] = $plugin.'@'.$version;
        }
        if (!empty($plugins)) {
            $params['plugins'] = implode(',', $plugins);
        }

        return $params;
    }

    /**
     * Returns the current timestamp
     *
     * @return int the timestamp
     */
    protected function cron_current_timestamp() {
        return time();
    }

    /**
     * Output cron debugging info
     *
     * @see mtrace()
     * @param string $msg output message
     * @param string $eol end of line
     */
    protected function cron_mtrace($msg, $eol = PHP_EOL) {
        mtrace($msg, $eol);
    }

    /**
     * Decide if the autocheck feature is disabled in the server setting
     *
     * @return bool true if autocheck enabled, false if disabled
     */
    protected function cron_autocheck_enabled() {
        global $CFG;

        if (empty($CFG->updateautocheck)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Decide if the recently fetched data are still fresh enough
     *
     * @param int $now current timestamp
     * @return bool true if no need to re-fetch, false otherwise
     */
    protected function cron_has_fresh_fetch($now) {
        $recent = $this->get_last_timefetched();

        if (empty($recent)) {
            return false;
        }

        if ($now < $recent) {
            $this->cron_mtrace('The most recent fetch is reported to be in the future, this is weird!');
            return true;
        }

        if ($now - $recent > 24 * HOURSECS) {
            return false;
        }

        return true;
    }

    /**
     * Decide if the fetch is outadated or even missing
     *
     * @param int $now current timestamp
     * @return bool false if no need to re-fetch, true otherwise
     */
    protected function cron_has_outdated_fetch($now) {
        $recent = $this->get_last_timefetched();

        if (empty($recent)) {
            return true;
        }

        if ($now < $recent) {
            $this->cron_mtrace('The most recent fetch is reported to be in the future, this is weird!');
            return false;
        }

        if ($now - $recent > 48 * HOURSECS) {
            return true;
        }

        return false;
    }

    /**
     * Returns the cron execution offset for this site
     *
     * The main {@link self::cron()} is supposed to run every night in some random time
     * between 01:00 and 06:00 AM (local time). The exact moment is defined by so called
     * execution offset, that is the amount of time after 01:00 AM. The offset value is
     * initially generated randomly and then used consistently at the site. This way, the
     * regular checks against the download.moodle.org server are spread in time.
     *
     * @return int the offset number of seconds from range 1 sec to 5 hours
     */
    protected function cron_execution_offset() {
        global $CFG;

        if (empty($CFG->updatecronoffset)) {
            set_config('updatecronoffset', rand(1, 5 * HOURSECS));
        }

        return $CFG->updatecronoffset;
    }

    /**
     * Fetch available updates info and eventually send notification to site admins
     */
    protected function cron_execute() {

        try {
            $this->restore_response();
            $previous = $this->recentresponse;
            $this->fetch();
            $this->restore_response(true);
            $current = $this->recentresponse;
            $changes = $this->compare_responses($previous, $current);
            $notifications = $this->cron_notifications($changes);
            $this->cron_notify($notifications);
            $this->cron_mtrace('done');
        } catch (available_update_checker_exception $e) {
            $this->cron_mtrace('FAILED!');
        }
    }

    /**
     * Given the list of changes in available updates, pick those to send to site admins
     *
     * @param array $changes as returned by {@link self::compare_responses()}
     * @return array of available_update_info objects to send to site admins
     */
    protected function cron_notifications(array $changes) {
        global $CFG;

        $notifications = array();
        $pluginman = plugin_manager::instance();
        $plugins = $pluginman->get_plugins(true);

        foreach ($changes as $component => $componentchanges) {
            if (empty($componentchanges)) {
                continue;
            }
            $componentupdates = $this->get_update_info($component,
                array('minmaturity' => $CFG->updateminmaturity, 'notifybuilds' => $CFG->updatenotifybuilds));
            if (empty($componentupdates)) {
                continue;
            }
            // notify only about those $componentchanges that are present in $componentupdates
            // to respect the preferences
            foreach ($componentchanges as $componentchange) {
                foreach ($componentupdates as $componentupdate) {
                    if ($componentupdate->version == $componentchange['version']) {
                        if ($component == 'core') {
                            // in case of 'core' this is enough, we already know that the
                            // $componentupdate is a real update with higher version
                            $notifications[] = $componentupdate;
                        } else {
                            // use the plugin_manager to check if the reported $componentchange
                            // is a real update with higher version. such a real update must be
                            // present in the 'availableupdates' property of one of the component's
                            // available_update_info object
                            list($plugintype, $pluginname) = normalize_component($component);
                            if (!empty($plugins[$plugintype][$pluginname]->availableupdates)) {
                                foreach ($plugins[$plugintype][$pluginname]->availableupdates as $availableupdate) {
                                    if ($availableupdate->version == $componentchange['version']) {
                                        $notifications[] = $componentupdate;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $notifications;
    }

    /**
     * Sends the given notifications to site admins via messaging API
     *
     * @param array $notifications array of available_update_info objects to send
     */
    protected function cron_notify(array $notifications) {
        global $CFG;

        if (empty($notifications)) {
            return;
        }

        $admins = get_admins();

        if (empty($admins)) {
            return;
        }

        $this->cron_mtrace('sending notifications ... ', '');

        $text = get_string('updatenotifications', 'core_admin') . PHP_EOL;
        $html = html_writer::tag('h1', get_string('updatenotifications', 'core_admin')) . PHP_EOL;

        $coreupdates = array();
        $pluginupdates = array();

        foreach ($notifications as $notification) {
            if ($notification->component == 'core') {
                $coreupdates[] = $notification;
            } else {
                $pluginupdates[] = $notification;
            }
        }

        if (!empty($coreupdates)) {
            $text .= PHP_EOL . get_string('updateavailable', 'core_admin') . PHP_EOL;
            $html .= html_writer::tag('h2', get_string('updateavailable', 'core_admin')) . PHP_EOL;
            $html .= html_writer::start_tag('ul') . PHP_EOL;
            foreach ($coreupdates as $coreupdate) {
                $html .= html_writer::start_tag('li');
                if (isset($coreupdate->release)) {
                    $text .= get_string('updateavailable_release', 'core_admin', $coreupdate->release);
                    $html .= html_writer::tag('strong', get_string('updateavailable_release', 'core_admin', $coreupdate->release));
                }
                if (isset($coreupdate->version)) {
                    $text .= ' '.get_string('updateavailable_version', 'core_admin', $coreupdate->version);
                    $html .= ' '.get_string('updateavailable_version', 'core_admin', $coreupdate->version);
                }
                if (isset($coreupdate->maturity)) {
                    $text .= ' ('.get_string('maturity'.$coreupdate->maturity, 'core_admin').')';
                    $html .= ' ('.get_string('maturity'.$coreupdate->maturity, 'core_admin').')';
                }
                $text .= PHP_EOL;
                $html .= html_writer::end_tag('li') . PHP_EOL;
            }
            $text .= PHP_EOL;
            $html .= html_writer::end_tag('ul') . PHP_EOL;

            $a = array('url' => $CFG->wwwroot.'/'.$CFG->admin.'/index.php');
            $text .= get_string('updateavailabledetailslink', 'core_admin', $a) . PHP_EOL;
            $a = array('url' => html_writer::link($CFG->wwwroot.'/'.$CFG->admin.'/index.php', $CFG->wwwroot.'/'.$CFG->admin.'/index.php'));
            $html .= html_writer::tag('p', get_string('updateavailabledetailslink', 'core_admin', $a)) . PHP_EOL;
        }

        if (!empty($pluginupdates)) {
            $text .= PHP_EOL . get_string('updateavailableforplugin', 'core_admin') . PHP_EOL;
            $html .= html_writer::tag('h2', get_string('updateavailableforplugin', 'core_admin')) . PHP_EOL;

            $html .= html_writer::start_tag('ul') . PHP_EOL;
            foreach ($pluginupdates as $pluginupdate) {
                $html .= html_writer::start_tag('li');
                $text .= get_string('pluginname', $pluginupdate->component);
                $html .= html_writer::tag('strong', get_string('pluginname', $pluginupdate->component));

                $text .= ' ('.$pluginupdate->component.')';
                $html .= ' ('.$pluginupdate->component.')';

                $text .= ' '.get_string('updateavailable', 'core_plugin', $pluginupdate->version);
                $html .= ' '.get_string('updateavailable', 'core_plugin', $pluginupdate->version);

                $text .= PHP_EOL;
                $html .= html_writer::end_tag('li') . PHP_EOL;
            }
            $text .= PHP_EOL;
            $html .= html_writer::end_tag('ul') . PHP_EOL;

            $a = array('url' => $CFG->wwwroot.'/'.$CFG->admin.'/plugins.php');
            $text .= get_string('updateavailabledetailslink', 'core_admin', $a) . PHP_EOL;
            $a = array('url' => html_writer::link($CFG->wwwroot.'/'.$CFG->admin.'/plugins.php', $CFG->wwwroot.'/'.$CFG->admin.'/plugins.php'));
            $html .= html_writer::tag('p', get_string('updateavailabledetailslink', 'core_admin', $a)) . PHP_EOL;
        }

        $a = array('siteurl' => $CFG->wwwroot);
        $text .= get_string('updatenotificationfooter', 'core_admin', $a) . PHP_EOL;
        $a = array('siteurl' => html_writer::link($CFG->wwwroot, $CFG->wwwroot));
        $html .= html_writer::tag('footer', html_writer::tag('p', get_string('updatenotificationfooter', 'core_admin', $a),
            array('style' => 'font-size:smaller; color:#333;')));

        $mainadmin = reset($admins);

        foreach ($admins as $admin) {
            $message = new stdClass();
            $message->component         = 'moodle';
            $message->name              = 'availableupdate';
            $message->userfrom          = $mainadmin;
            $message->userto            = $admin;
            $message->subject           = get_string('updatenotifications', 'core_admin');
            $message->fullmessage       = $text;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml   = $html;
            $message->smallmessage      = get_string('updatenotifications', 'core_admin');
            $message->notification      = 1;
            message_send($message);
        }
    }

    /**
     * Compare two release labels and decide if they are the same
     *
     * @param string $remote release info of the available update
     * @param null|string $local release info of the local code, defaults to $release defined in version.php
     * @return boolean true if the releases declare the same minor+major version
     */
    protected function is_same_release($remote, $local=null) {

        if (is_null($local)) {
            $this->load_current_environment();
            $local = $this->currentrelease;
        }

        $pattern = '/^([0-9\.\+]+)([^(]*)/';

        preg_match($pattern, $remote, $remotematches);
        preg_match($pattern, $local, $localmatches);

        $remotematches[1] = str_replace('+', '', $remotematches[1]);
        $localmatches[1] = str_replace('+', '', $localmatches[1]);

        if ($remotematches[1] === $localmatches[1] and rtrim($remotematches[2]) === rtrim($localmatches[2])) {
            return true;
        } else {
            return false;
        }
    }
}


/**
 * Defines the structure of objects returned by {@link available_update_checker::get_update_info()}
 */
class available_update_info {

    /** @var string frankenstyle component name */
    public $component;
    /** @var int the available version of the component */
    public $version;
    /** @var string|null optional release name */
    public $release = null;
    /** @var int|null optional maturity info, eg {@link MATURITY_STABLE} */
    public $maturity = null;
    /** @var string|null optional URL of a page with more info about the update */
    public $url = null;
    /** @var string|null optional URL of a ZIP package that can be downloaded and installed */
    public $download = null;

    /**
     * Creates new instance of the class
     *
     * The $info array must provide at least the 'version' value and optionally all other
     * values to populate the object's properties.
     *
     * @param string $name the frankenstyle component name
     * @param array $info associative array with other properties
     */
    public function __construct($name, array $info) {
        $this->component = $name;
        foreach ($info as $k => $v) {
            if (property_exists('available_update_info', $k) and $k != 'component') {
                $this->$k = $v;
            }
        }
    }
}


/**
 * Factory class producing required subclasses of {@link plugininfo_base}
 */
class plugininfo_default_factory {

    /**
     * Makes a new instance of the plugininfo class
     *
     * @param string $type the plugin type, eg. 'mod'
     * @param string $typerootdir full path to the location of all the plugins of this type
     * @param string $name the plugin name, eg. 'workshop'
     * @param string $namerootdir full path to the location of the plugin
     * @param string $typeclass the name of class that holds the info about the plugin
     * @return plugininfo_base the instance of $typeclass
     */
    public static function make($type, $typerootdir, $name, $namerootdir, $typeclass) {
        $plugin              = new $typeclass();
        $plugin->type        = $type;
        $plugin->typerootdir = $typerootdir;
        $plugin->name        = $name;
        $plugin->rootdir     = $namerootdir;

        $plugin->init_display_name();
        $plugin->load_disk_version();
        $plugin->load_db_version();
        $plugin->load_required_main_version();
        $plugin->init_is_standard();

        return $plugin;
    }
}


/**
 * Base class providing access to the information about a plugin
 *
 * @property-read string component the component name, type_name
 */
abstract class plugininfo_base {

    /** @var string the plugintype name, eg. mod, auth or workshopform */
    public $type;
    /** @var string full path to the location of all the plugins of this type */
    public $typerootdir;
    /** @var string the plugin name, eg. assignment, ldap */
    public $name;
    /** @var string the localized plugin name */
    public $displayname;
    /** @var string the plugin source, one of plugin_manager::PLUGIN_SOURCE_xxx constants */
    public $source;
    /** @var fullpath to the location of this plugin */
    public $rootdir;
    /** @var int|string the version of the plugin's source code */
    public $versiondisk;
    /** @var int|string the version of the installed plugin */
    public $versiondb;
    /** @var int|float|string required version of Moodle core  */
    public $versionrequires;
    /** @var array other plugins that this one depends on, lazy-loaded by {@link get_other_required_plugins()} */
    public $dependencies;
    /** @var int number of instances of the plugin - not supported yet */
    public $instances;
    /** @var int order of the plugin among other plugins of the same type - not supported yet */
    public $sortorder;
    /** @var array|null array of {@link available_update_info} for this plugin */
    public $availableupdates;

    /**
     * Gathers and returns the information about all plugins of the given type
     *
     * @param string $type the name of the plugintype, eg. mod, auth or workshopform
     * @param string $typerootdir full path to the location of the plugin dir
     * @param string $typeclass the name of the actually called class
     * @return array of plugintype classes, indexed by the plugin name
     */
    public static function get_plugins($type, $typerootdir, $typeclass) {

        // get the information about plugins at the disk
        $plugins = get_plugin_list($type);
        $ondisk = array();
        foreach ($plugins as $pluginname => $pluginrootdir) {
            $ondisk[$pluginname] = plugininfo_default_factory::make($type, $typerootdir,
                $pluginname, $pluginrootdir, $typeclass);
        }
        return $ondisk;
    }

    /**
     * Sets {@link $displayname} property to a localized name of the plugin
     */
    public function init_display_name() {
        if (!get_string_manager()->string_exists('pluginname', $this->component)) {
            $this->displayname = '[pluginname,' . $this->component . ']';
        } else {
            $this->displayname = get_string('pluginname', $this->component);
        }
    }

    /**
     * Magic method getter, redirects to read only values.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        switch ($name) {
            case 'component': return $this->type . '_' . $this->name;

            default:
                debugging('Invalid plugin property accessed! '.$name);
                return null;
        }
    }

    /**
     * Return the full path name of a file within the plugin.
     *
     * No check is made to see if the file exists.
     *
     * @param string $relativepath e.g. 'version.php'.
     * @return string e.g. $CFG->dirroot . '/mod/quiz/version.php'.
     */
    public function full_path($relativepath) {
        if (empty($this->rootdir)) {
            return '';
        }
        return $this->rootdir . '/' . $relativepath;
    }

    /**
     * Load the data from version.php.
     *
     * @return stdClass the object called $plugin defined in version.php
     */
    protected function load_version_php() {
        $versionfile = $this->full_path('version.php');

        $plugin = new stdClass();
        if (is_readable($versionfile)) {
            include($versionfile);
        }
        return $plugin;
    }

    /**
     * Sets {@link $versiondisk} property to a numerical value representing the
     * version of the plugin's source code.
     *
     * If the value is null after calling this method, either the plugin
     * does not use versioning (typically does not have any database
     * data) or is missing from disk.
     */
    public function load_disk_version() {
        $plugin = $this->load_version_php();
        if (isset($plugin->version)) {
            $this->versiondisk = $plugin->version;
        }
    }

    /**
     * Sets {@link $versionrequires} property to a numerical value representing
     * the version of Moodle core that this plugin requires.
     */
    public function load_required_main_version() {
        $plugin = $this->load_version_php();
        if (isset($plugin->requires)) {
            $this->versionrequires = $plugin->requires;
        }
    }

    /**
     * Initialise {@link $dependencies} to the list of other plugins (in any)
     * that this one requires to be installed.
     */
    protected function load_other_required_plugins() {
        $plugin = $this->load_version_php();
        if (!empty($plugin->dependencies)) {
            $this->dependencies = $plugin->dependencies;
        } else {
            $this->dependencies = array(); // By default, no dependencies.
        }
    }

    /**
     * Get the list of other plugins that this plugin requires to be installed.
     *
     * @return array with keys the frankenstyle plugin name, and values either
     *      a version string (like '2011101700') or the constant ANY_VERSION.
     */
    public function get_other_required_plugins() {
        if (is_null($this->dependencies)) {
            $this->load_other_required_plugins();
        }
        return $this->dependencies;
    }

    /**
     * Sets {@link $versiondb} property to a numerical value representing the
     * currently installed version of the plugin.
     *
     * If the value is null after calling this method, either the plugin
     * does not use versioning (typically does not have any database
     * data) or has not been installed yet.
     */
    public function load_db_version() {
        if ($ver = self::get_version_from_config_plugins($this->component)) {
            $this->versiondb = $ver;
        }
    }

    /**
     * Sets {@link $source} property to one of plugin_manager::PLUGIN_SOURCE_xxx
     * constants.
     *
     * If the property's value is null after calling this method, then
     * the type of the plugin has not been recognized and you should throw
     * an exception.
     */
    public function init_is_standard() {

        $standard = plugin_manager::standard_plugins_list($this->type);

        if ($standard !== false) {
            $standard = array_flip($standard);
            if (isset($standard[$this->name])) {
                $this->source = plugin_manager::PLUGIN_SOURCE_STANDARD;
            } else if (!is_null($this->versiondb) and is_null($this->versiondisk)
                    and plugin_manager::is_deleted_standard_plugin($this->type, $this->name)) {
                $this->source = plugin_manager::PLUGIN_SOURCE_STANDARD; // to be deleted
            } else {
                $this->source = plugin_manager::PLUGIN_SOURCE_EXTENSION;
            }
        }
    }

    /**
     * Returns true if the plugin is shipped with the official distribution
     * of the current Moodle version, false otherwise.
     *
     * @return bool
     */
    public function is_standard() {
        return $this->source === plugin_manager::PLUGIN_SOURCE_STANDARD;
    }

    /**
     * Returns true if the the given Moodle version is enough to run this plugin
     *
     * @param string|int|double $moodleversion
     * @return bool
     */
    public function is_core_dependency_satisfied($moodleversion) {

        if (empty($this->versionrequires)) {
            return true;

        } else {
            return (double)$this->versionrequires <= (double)$moodleversion;
        }
    }

    /**
     * Returns the status of the plugin
     *
     * @return string one of plugin_manager::PLUGIN_STATUS_xxx constants
     */
    public function get_status() {

        if (is_null($this->versiondb) and is_null($this->versiondisk)) {
            return plugin_manager::PLUGIN_STATUS_NODB;

        } else if (is_null($this->versiondb) and !is_null($this->versiondisk)) {
            return plugin_manager::PLUGIN_STATUS_NEW;

        } else if (!is_null($this->versiondb) and is_null($this->versiondisk)) {
            if (plugin_manager::is_deleted_standard_plugin($this->type, $this->name)) {
                return plugin_manager::PLUGIN_STATUS_DELETE;
            } else {
                return plugin_manager::PLUGIN_STATUS_MISSING;
            }

        } else if ((string)$this->versiondb === (string)$this->versiondisk) {
            return plugin_manager::PLUGIN_STATUS_UPTODATE;

        } else if ($this->versiondb < $this->versiondisk) {
            return plugin_manager::PLUGIN_STATUS_UPGRADE;

        } else if ($this->versiondb > $this->versiondisk) {
            return plugin_manager::PLUGIN_STATUS_DOWNGRADE;

        } else {
            // $version = pi(); and similar funny jokes - hopefully Donald E. Knuth will never contribute to Moodle ;-)
            throw new coding_exception('Unable to determine plugin state, check the plugin versions');
        }
    }

    /**
     * Returns the information about plugin availability
     *
     * True means that the plugin is enabled. False means that the plugin is
     * disabled. Null means that the information is not available, or the
     * plugin does not support configurable availability or the availability
     * can not be changed.
     *
     * @return null|bool
     */
    public function is_enabled() {
        return null;
    }

    /**
     * Populates the property {@link $availableupdates} with the information provided by
     * available update checker
     *
     * @param available_update_checker $provider the class providing the available update info
     */
    public function check_available_updates(available_update_checker $provider) {
        global $CFG;

        if (isset($CFG->updateminmaturity)) {
            $minmaturity = $CFG->updateminmaturity;
        } else {
            // this can happen during the very first upgrade to 2.3
            $minmaturity = MATURITY_STABLE;
        }

        $this->availableupdates = $provider->get_update_info($this->component,
            array('minmaturity' => $minmaturity));
    }

    /**
     * If there are updates for this plugin available, returns them.
     *
     * Returns array of {@link available_update_info} objects, if some update
     * is available. Returns null if there is no update available or if the update
     * availability is unknown.
     *
     * @return array|null
     */
    public function available_updates() {

        if (empty($this->availableupdates) or !is_array($this->availableupdates)) {
            return null;
        }

        $updates = array();

        foreach ($this->availableupdates as $availableupdate) {
            if ($availableupdate->version > $this->versiondisk) {
                $updates[] = $availableupdate;
            }
        }

        if (empty($updates)) {
            return null;
        }

        return $updates;
    }

    /**
     * Returns the URL of the plugin settings screen
     *
     * Null value means that the plugin either does not have the settings screen
     * or its location is not available via this library.
     *
     * @return null|moodle_url
     */
    public function get_settings_url() {
        return null;
    }

    /**
     * Returns the URL of the screen where this plugin can be uninstalled
     *
     * Visiting that URL must be safe, that is a manual confirmation is needed
     * for actual uninstallation of the plugin. Null value means that the
     * plugin either does not support uninstallation, or does not require any
     * database cleanup or the location of the screen is not available via this
     * library.
     *
     * @return null|moodle_url
     */
    public function get_uninstall_url() {
        return null;
    }

    /**
     * Returns relative directory of the plugin with heading '/'
     *
     * @return string
     */
    public function get_dir() {
        global $CFG;

        return substr($this->rootdir, strlen($CFG->dirroot));
    }

    /**
     * Provides access to plugin versions from {config_plugins}
     *
     * @param string $plugin plugin name
     * @param double $disablecache optional, defaults to false
     * @return int|false the stored value or false if not found
     */
    protected function get_version_from_config_plugins($plugin, $disablecache=false) {
        global $DB;
        static $pluginversions = null;

        if (is_null($pluginversions) or $disablecache) {
            try {
                $pluginversions = $DB->get_records_menu('config_plugins', array('name' => 'version'), 'plugin', 'plugin,value');
            } catch (dml_exception $e) {
                // before install
                $pluginversions = array();
            }
        }

        if (!array_key_exists($plugin, $pluginversions)) {
            return false;
        }

        return $pluginversions[$plugin];
    }
}


/**
 * General class for all plugin types that do not have their own class
 */
class plugininfo_general extends plugininfo_base {
}


/**
 * Class for page side blocks
 */
class plugininfo_block extends plugininfo_base {

    public static function get_plugins($type, $typerootdir, $typeclass) {

        // get the information about blocks at the disk
        $blocks = parent::get_plugins($type, $typerootdir, $typeclass);

        // add blocks missing from disk
        $blocksinfo = self::get_blocks_info();
        foreach ($blocksinfo as $blockname => $blockinfo) {
            if (isset($blocks[$blockname])) {
                continue;
            }
            $plugin                 = new $typeclass();
            $plugin->type           = $type;
            $plugin->typerootdir    = $typerootdir;
            $plugin->name           = $blockname;
            $plugin->rootdir        = null;
            $plugin->displayname    = $blockname;
            $plugin->versiondb      = $blockinfo->version;
            $plugin->init_is_standard();

            $blocks[$blockname]   = $plugin;
        }

        return $blocks;
    }

    public function init_display_name() {

        if (get_string_manager()->string_exists('pluginname', 'block_' . $this->name)) {
            $this->displayname = get_string('pluginname', 'block_' . $this->name);

        } else if (($block = block_instance($this->name)) !== false) {
            $this->displayname = $block->get_title();

        } else {
            parent::init_display_name();
        }
    }

    public function load_db_version() {
        global $DB;

        $blocksinfo = self::get_blocks_info();
        if (isset($blocksinfo[$this->name]->version)) {
            $this->versiondb = $blocksinfo[$this->name]->version;
        }
    }

    public function is_enabled() {

        $blocksinfo = self::get_blocks_info();
        if (isset($blocksinfo[$this->name]->visible)) {
            if ($blocksinfo[$this->name]->visible) {
                return true;
            } else {
                return false;
            }
        } else {
            return parent::is_enabled();
        }
    }

    public function get_settings_url() {

        if (($block = block_instance($this->name)) === false) {
            return parent::get_settings_url();

        } else if ($block->has_config()) {
            if (file_exists($this->full_path('settings.php'))) {
                return new moodle_url('/admin/settings.php', array('section' => 'blocksetting' . $this->name));
            } else {
                $blocksinfo = self::get_blocks_info();
                return new moodle_url('/admin/block.php', array('block' => $blocksinfo[$this->name]->id));
            }

        } else {
            return parent::get_settings_url();
        }
    }

    public function get_uninstall_url() {

        $blocksinfo = self::get_blocks_info();
        return new moodle_url('/admin/blocks.php', array('delete' => $blocksinfo[$this->name]->id, 'sesskey' => sesskey()));
    }

    /**
     * Provides access to the records in {block} table
     *
     * @param bool $disablecache do not use internal static cache
     * @return array array of stdClasses
     */
    protected static function get_blocks_info($disablecache=false) {
        global $DB;
        static $blocksinfocache = null;

        if (is_null($blocksinfocache) or $disablecache) {
            try {
                $blocksinfocache = $DB->get_records('block', null, 'name', 'name,id,version,visible');
            } catch (dml_exception $e) {
                // before install
                $blocksinfocache = array();
            }
        }

        return $blocksinfocache;
    }
}


/**
 * Class for text filters
 */
class plugininfo_filter extends plugininfo_base {

    public static function get_plugins($type, $typerootdir, $typeclass) {
        global $CFG, $DB;

        $filters = array();

        // get the list of filters from both /filter and /mod location
        $installed = filter_get_all_installed();

        foreach ($installed as $filterlegacyname => $displayname) {
            $plugin                 = new $typeclass();
            $plugin->type           = $type;
            $plugin->typerootdir    = $typerootdir;
            $plugin->name           = self::normalize_legacy_name($filterlegacyname);
            $plugin->rootdir        = $CFG->dirroot . '/' . $filterlegacyname;
            $plugin->displayname    = $displayname;

            $plugin->load_disk_version();
            $plugin->load_db_version();
            $plugin->load_required_main_version();
            $plugin->init_is_standard();

            $filters[$plugin->name] = $plugin;
        }

        $globalstates = self::get_global_states();

        if ($DB->get_manager()->table_exists('filter_active')) {
            // if we're upgrading from 1.9, the table does not exist yet
            // if it does, make sure that all installed filters are registered
            $needsreload  = false;
            foreach (array_keys($installed) as $filterlegacyname) {
                if (!isset($globalstates[self::normalize_legacy_name($filterlegacyname)])) {
                    filter_set_global_state($filterlegacyname, TEXTFILTER_DISABLED);
                    $needsreload = true;
                }
            }
            if ($needsreload) {
                $globalstates = self::get_global_states(true);
            }
        }

        // make sure that all registered filters are installed, just in case
        foreach ($globalstates as $name => $info) {
            if (!isset($filters[$name])) {
                // oops, there is a record in filter_active but the filter is not installed
                $plugin                 = new $typeclass();
                $plugin->type           = $type;
                $plugin->typerootdir    = $typerootdir;
                $plugin->name           = $name;
                $plugin->rootdir        = $CFG->dirroot . '/' . $info->legacyname;
                $plugin->displayname    = $info->legacyname;

                $plugin->load_db_version();

                if (is_null($plugin->versiondb)) {
                    // this is a hack to stimulate 'Missing from disk' error
                    // because $plugin->versiondisk will be null !== false
                    $plugin->versiondb = false;
                }

                $filters[$plugin->name] = $plugin;
            }
        }

        return $filters;
    }

    public function init_display_name() {
        // do nothing, the name is set in self::get_plugins()
    }

    /**
     * @see load_version_php()
     */
    protected function load_version_php() {
        if (strpos($this->name, 'mod_') === 0) {
            // filters bundled with modules do not have a version.php and so
            // do not provide their own versioning information.
            return new stdClass();
        }
        return parent::load_version_php();
    }

    public function is_enabled() {

        $globalstates = self::get_global_states();

        foreach ($globalstates as $filterlegacyname => $info) {
            $name = self::normalize_legacy_name($filterlegacyname);
            if ($name === $this->name) {
                if ($info->active == TEXTFILTER_DISABLED) {
                    return false;
                } else {
                    // it may be 'On' or 'Off, but available'
                    return null;
                }
            }
        }

        return null;
    }

    public function get_settings_url() {

        $globalstates = self::get_global_states();
        $legacyname = $globalstates[$this->name]->legacyname;
        if (filter_has_global_settings($legacyname)) {
            return new moodle_url('/admin/settings.php', array('section' => 'filtersetting' . str_replace('/', '', $legacyname)));
        } else {
            return null;
        }
    }

    public function get_uninstall_url() {

        if (strpos($this->name, 'mod_') === 0) {
            return null;
        } else {
            $globalstates = self::get_global_states();
            $legacyname = $globalstates[$this->name]->legacyname;
            return new moodle_url('/admin/filters.php', array('sesskey' => sesskey(), 'filterpath' => $legacyname, 'action' => 'delete'));
        }
    }

    /**
     * Convert legacy filter names like 'filter/foo' or 'mod/bar' into frankenstyle
     *
     * @param string $legacyfiltername legacy filter name
     * @return string frankenstyle-like name
     */
    protected static function normalize_legacy_name($legacyfiltername) {

        $name = str_replace('/', '_', $legacyfiltername);
        if (strpos($name, 'filter_') === 0) {
            $name = substr($name, 7);
            if (empty($name)) {
                throw new coding_exception('Unable to determine filter name: ' . $legacyfiltername);
            }
        }

        return $name;
    }

    /**
     * Provides access to the results of {@link filter_get_global_states()}
     * but indexed by the normalized filter name
     *
     * The legacy filter name is available as ->legacyname property.
     *
     * @param bool $disablecache
     * @return array
     */
    protected static function get_global_states($disablecache=false) {
        global $DB;
        static $globalstatescache = null;

        if ($disablecache or is_null($globalstatescache)) {

            if (!$DB->get_manager()->table_exists('filter_active')) {
                // we're upgrading from 1.9 and the table used by {@link filter_get_global_states()}
                // does not exist yet
                $globalstatescache = array();

            } else {
                foreach (filter_get_global_states() as $legacyname => $info) {
                    $name                       = self::normalize_legacy_name($legacyname);
                    $filterinfo                 = new stdClass();
                    $filterinfo->legacyname     = $legacyname;
                    $filterinfo->active         = $info->active;
                    $filterinfo->sortorder      = $info->sortorder;
                    $globalstatescache[$name]   = $filterinfo;
                }
            }
        }

        return $globalstatescache;
    }
}


/**
 * Class for activity modules
 */
class plugininfo_mod extends plugininfo_base {

    public static function get_plugins($type, $typerootdir, $typeclass) {

        // get the information about plugins at the disk
        $modules = parent::get_plugins($type, $typerootdir, $typeclass);

        // add modules missing from disk
        $modulesinfo = self::get_modules_info();
        foreach ($modulesinfo as $modulename => $moduleinfo) {
            if (isset($modules[$modulename])) {
                continue;
            }
            $plugin                 = new $typeclass();
            $plugin->type           = $type;
            $plugin->typerootdir    = $typerootdir;
            $plugin->name           = $modulename;
            $plugin->rootdir        = null;
            $plugin->displayname    = $modulename;
            $plugin->versiondb      = $moduleinfo->version;
            $plugin->init_is_standard();

            $modules[$modulename]   = $plugin;
        }

        return $modules;
    }

    public function init_display_name() {
        if (get_string_manager()->string_exists('pluginname', $this->component)) {
            $this->displayname = get_string('pluginname', $this->component);
        } else {
            $this->displayname = get_string('modulename', $this->component);
        }
    }

    /**
     * Load the data from version.php.
     * @return object the data object defined in version.php.
     */
    protected function load_version_php() {
        $versionfile = $this->full_path('version.php');

        $module = new stdClass();
        if (is_readable($versionfile)) {
            include($versionfile);
        }
        return $module;
    }

    public function load_db_version() {
        global $DB;

        $modulesinfo = self::get_modules_info();
        if (isset($modulesinfo[$this->name]->version)) {
            $this->versiondb = $modulesinfo[$this->name]->version;
        }
    }

    public function is_enabled() {

        $modulesinfo = self::get_modules_info();
        if (isset($modulesinfo[$this->name]->visible)) {
            if ($modulesinfo[$this->name]->visible) {
                return true;
            } else {
                return false;
            }
        } else {
            return parent::is_enabled();
        }
    }

    public function get_settings_url() {

        if (file_exists($this->full_path('settings.php')) or file_exists($this->full_path('settingstree.php'))) {
            return new moodle_url('/admin/settings.php', array('section' => 'modsetting' . $this->name));
        } else {
            return parent::get_settings_url();
        }
    }

    public function get_uninstall_url() {

        if ($this->name !== 'forum') {
            return new moodle_url('/admin/modules.php', array('delete' => $this->name, 'sesskey' => sesskey()));
        } else {
            return null;
        }
    }

    /**
     * Provides access to the records in {modules} table
     *
     * @param bool $disablecache do not use internal static cache
     * @return array array of stdClasses
     */
    protected static function get_modules_info($disablecache=false) {
        global $DB;
        static $modulesinfocache = null;

        if (is_null($modulesinfocache) or $disablecache) {
            try {
                $modulesinfocache = $DB->get_records('modules', null, 'name', 'name,id,version,visible');
            } catch (dml_exception $e) {
                // before install
                $modulesinfocache = array();
            }
        }

        return $modulesinfocache;
    }
}


/**
 * Class for question behaviours.
 */
class plugininfo_qbehaviour extends plugininfo_base {

    public function get_uninstall_url() {
        return new moodle_url('/admin/qbehaviours.php',
                array('delete' => $this->name, 'sesskey' => sesskey()));
    }
}


/**
 * Class for question types
 */
class plugininfo_qtype extends plugininfo_base {

    public function get_uninstall_url() {
        return new moodle_url('/admin/qtypes.php',
                array('delete' => $this->name, 'sesskey' => sesskey()));
    }
}


/**
 * Class for authentication plugins
 */
class plugininfo_auth extends plugininfo_base {

    public function is_enabled() {
        global $CFG;
        /** @var null|array list of enabled authentication plugins */
        static $enabled = null;

        if (in_array($this->name, array('nologin', 'manual'))) {
            // these two are always enabled and can't be disabled
            return null;
        }

        if (is_null($enabled)) {
            $enabled = array_flip(explode(',', $CFG->auth));
        }

        return isset($enabled[$this->name]);
    }

    public function get_settings_url() {
        if (file_exists($this->full_path('settings.php'))) {
            return new moodle_url('/admin/settings.php', array('section' => 'authsetting' . $this->name));
        } else {
            return new moodle_url('/admin/auth_config.php', array('auth' => $this->name));
        }
    }
}


/**
 * Class for enrolment plugins
 */
class plugininfo_enrol extends plugininfo_base {

    public function is_enabled() {
        global $CFG;
        /** @var null|array list of enabled enrolment plugins */
        static $enabled = null;

        // We do not actually need whole enrolment classes here so we do not call
        // {@link enrol_get_plugins()}. Note that this may produce slightly different
        // results, for example if the enrolment plugin does not contain lib.php
        // but it is listed in $CFG->enrol_plugins_enabled

        if (is_null($enabled)) {
            $enabled = array_flip(explode(',', $CFG->enrol_plugins_enabled));
        }

        return isset($enabled[$this->name]);
    }

    public function get_settings_url() {

        if ($this->is_enabled() or file_exists($this->full_path('settings.php'))) {
            return new moodle_url('/admin/settings.php', array('section' => 'enrolsettings' . $this->name));
        } else {
            return parent::get_settings_url();
        }
    }

    public function get_uninstall_url() {
        return new moodle_url('/admin/enrol.php', array('action' => 'uninstall', 'enrol' => $this->name, 'sesskey' => sesskey()));
    }
}


/**
 * Class for messaging processors
 */
class plugininfo_message extends plugininfo_base {

    public function get_settings_url() {
        $processors = get_message_processors();
        if (isset($processors[$this->name])) {
            $processor = $processors[$this->name];
            if ($processor->available && $processor->hassettings) {
                return new moodle_url('settings.php', array('section' => 'messagesetting'.$processor->name));
            }
        }
        return parent::get_settings_url();
    }

    /**
     * @see plugintype_interface::is_enabled()
     */
    public function is_enabled() {
        $processors = get_message_processors();
        if (isset($processors[$this->name])) {
            return $processors[$this->name]->configured && $processors[$this->name]->enabled;
        } else {
            return parent::is_enabled();
        }
    }

    /**
     * @see plugintype_interface::get_uninstall_url()
     */
    public function get_uninstall_url() {
        $processors = get_message_processors();
        if (isset($processors[$this->name])) {
            return new moodle_url('message.php', array('uninstall' => $processors[$this->name]->id, 'sesskey' => sesskey()));
        } else {
            return parent::get_uninstall_url();
        }
    }
}


/**
 * Class for repositories
 */
class plugininfo_repository extends plugininfo_base {

    public function is_enabled() {

        $enabled = self::get_enabled_repositories();

        return isset($enabled[$this->name]);
    }

    public function get_settings_url() {

        if ($this->is_enabled()) {
            return new moodle_url('/admin/repository.php', array('sesskey' => sesskey(), 'action' => 'edit', 'repos' => $this->name));
        } else {
            return parent::get_settings_url();
        }
    }

    /**
     * Provides access to the records in {repository} table
     *
     * @param bool $disablecache do not use internal static cache
     * @return array array of stdClasses
     */
    protected static function get_enabled_repositories($disablecache=false) {
        global $DB;
        static $repositories = null;

        if (is_null($repositories) or $disablecache) {
            $repositories = $DB->get_records('repository', null, 'type', 'type,visible,sortorder');
        }

        return $repositories;
    }
}


/**
 * Class for portfolios
 */
class plugininfo_portfolio extends plugininfo_base {

    public function is_enabled() {

        $enabled = self::get_enabled_portfolios();

        return isset($enabled[$this->name]);
    }

    /**
     * Provides access to the records in {portfolio_instance} table
     *
     * @param bool $disablecache do not use internal static cache
     * @return array array of stdClasses
     */
    protected static function get_enabled_portfolios($disablecache=false) {
        global $DB;
        static $portfolios = null;

        if (is_null($portfolios) or $disablecache) {
            $portfolios = array();
            $instances  = $DB->get_recordset('portfolio_instance', null, 'plugin');
            foreach ($instances as $instance) {
                if (isset($portfolios[$instance->plugin])) {
                    if ($instance->visible) {
                        $portfolios[$instance->plugin]->visible = $instance->visible;
                    }
                } else {
                    $portfolios[$instance->plugin] = $instance;
                }
            }
        }

        return $portfolios;
    }
}


/**
 * Class for themes
 */
class plugininfo_theme extends plugininfo_base {

    public function is_enabled() {
        global $CFG;

        if ((!empty($CFG->theme) and $CFG->theme === $this->name) or
            (!empty($CFG->themelegacy) and $CFG->themelegacy === $this->name)) {
            return true;
        } else {
            return parent::is_enabled();
        }
    }
}


/**
 * Class representing an MNet service
 */
class plugininfo_mnetservice extends plugininfo_base {

    public function is_enabled() {
        global $CFG;

        if (empty($CFG->mnet_dispatcher_mode) || $CFG->mnet_dispatcher_mode !== 'strict') {
            return false;
        } else {
            return parent::is_enabled();
        }
    }
}


/**
 * Class for admin tool plugins
 */
class plugininfo_tool extends plugininfo_base {

    public function get_uninstall_url() {
        return new moodle_url('/admin/tools.php', array('delete' => $this->name, 'sesskey' => sesskey()));
    }
}


/**
 * Class for admin tool plugins
 */
class plugininfo_report extends plugininfo_base {

    public function get_uninstall_url() {
        return new moodle_url('/admin/reports.php', array('delete' => $this->name, 'sesskey' => sesskey()));
    }
}


/**
 * Class for local plugins
 */
class plugininfo_local extends plugininfo_base {

    public function get_uninstall_url() {
        return new moodle_url('/admin/localplugins.php', array('delete' => $this->name, 'sesskey' => sesskey()));
    }

    public function get_settings_url() {
        if (file_exists($this->full_path('settings.php'))) {
            return new moodle_url('/admin/settings.php', array('section' => 'local_' . $this->name));
        } else {
            return parent::get_settings_url();
        }
    }
}
