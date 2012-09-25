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

// how many seconds can this run for on every
// cron invocation
define( 'ENROL_GUDATABASE_MAXTIME', 120 );

// we inherit from vanilla database plugin
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
     * @return bool - true means user with 'enrol/xxx:unenrol' may unenrol this user, false means nobody may touch this user enrolment
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
     * @return bool - true means user with 'enrol/xxx:unenrol' may unenrol others freely, false means nobody may touch user_enrolments
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
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class'=>'unenrollink', 'rel'=>$ue->id));
        }
        return $actions;
    }

    /** 
     * synchronise enrollments for particular course
     * @param object $course
     */
    public function sync_course_enrolments($course) {
        global $CFG, $DB;

        // check that plugin is vaguely configured
        if (!$this->get_config('dbtype') or !$this->get_config('dbhost') or !$this->get_config('remoteenroltable') or !$this->get_config('remotecoursefield') or !$this->get_config('remoteuserfield')) {
            return false;
        }
    }

    /**
     * split the course code into an array accounting
     * for multiple delimeters etc.
     * @param string $code (list of) course codes
     * @return array array of course codes
     */
    private function split_code( $code ) {

        // split on comma or space
        $codes = preg_split("/[\s,]+/", $code, null, PREG_SPLIT_NO_EMPTY );

        return $codes;
    }

    /**
     * get enrollment data from external table
     * @param array $codes list of course codes
     * @param string $userid user id
     * @return array 
     */
    protected function external_enrolments( $codes=null, $userid=null ) {
        global $CFG, $DB;

        // connect to external db 
        if (!$extdb = $this->db_init()) {
            mtrace('Error while communicating with external enrolment database');
            return 1;
        }

        // get connection details
        $table            = $this->get_config('remoteenroltable');
        $coursefield      = strtolower($this->get_config('remotecoursefield'));
        $userfield        = strtolower($this->get_config('remoteuserfield'));

        // work out appropriate sql
        $sql = "select * from $table where ";

        // if $codes is supplied
        if (!empty( $codes )) {
            $quotedcodes = array();
            foreach ($codes as $code) {
                $quotedcodes[] = "'" . $this->db_addslashes($code) . "'";
            }
            $codestring = implode(',', $quotedcodes);
            $sql .= "$coursefield in ($codestring) ";
        }
        else if (!empty($userid)) {
            $sql .= "$userfield = '" . $this->db_addslashes($userid) . "'";
        }

        // read the data from external db
        $enrolments = array();
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                while ($row = $rs->FetchRow()) {
                    $enrolment = (object)$row;
                    $enrolments[] = $enrolment;
                }
            }
            $rs->Close();
        }
        else {
            mtrace('Error reading from the UofG enrolment table');
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

        // connect to external db 
        if (!$extdb = $this->db_init()) {
            mtrace('Error while communicating with external enrolment database');
            return false;
        }

        // get connection details
        $table            = $this->get_config('codesenroltable');

        // if table not defined then we can't do anything
        if (empty($table)) {
            return false;
        }

        // create the sql
        $sql = "select * from $table where CourseCat='" . $this->db_addslashes($code) . "'";

        // and run the query
        $data = false;
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                $row = $rs->FetchRow();
                $data = (object)$row;
            }
            $rs->Close();
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

        // connect to external db 
        if (!$extdb = $this->db_init()) {
            mtrace('Error while communicating with external enrolment database');
            return false;
        }

        // get connection details
        $table = $this->get_config('remoteenroltable');

        // GUIDs can't be trusted in the external database, So...
        // match on $user->idnumber against (external) matric_no.

        // create the sql. In the event idnumber (matric number)
        // not specified, just need to go with username (GUID)
        $sql = "select * from $table where ";
        if (empty($user->idnumber)) {
            $sql .= " UserName = '" . $this->db_addslashes($user->username) . "'"; 
        }
        else {
            $sql .= " matric_no = '" . $this->db_addslashes($user->idnumber) . "'";
        }

        // and run the query
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

        // if it looks like a student guid then make the matric no
        // which is more reliable
        $guid = strtolower( $guid );
        if (preg_match('/^\d+[a-z]$/', $guid)) {
            $matric = substr( $guid, 0, -1 );
        }
        else {
            $matric = '';
        }

        // make a fake user object
        $user = new stdClass();
        $user->username = $guid;
        $user->idnumber = $matric;

        // get the courses
        if (!$courses = $this->external_userdata( $user )) {
            return FALSE;
        }
      
        // add the courses information
        foreach ($courses as $course) {
            $code = $course->courses;
            if ($coursedata = $this->external_coursedata( $code )) {
                $course->name = $coursedata->Crse_name;
                $course->ou = $coursedata->ou_name;
            }
            else {
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
    function create_user_record($username, $matricid) {
        global $CFG, $DB;

        //just in case check text case
        $username = trim(textlib::strtolower($username));

        // we will be using 'guid' ldap plugin only
        $authplugin = get_auth_plugin('guid');

        // build up new user object
        $newuser = new stdClass();

        // get user info from guid auth plugin
        if ($newinfo = $authplugin->get_userinfo($username, $matricid)) {
            $newinfo = truncate_userinfo($newinfo);
            foreach ($newinfo as $key => $value){
                $newuser->$key = $value;
            }
        }
 
        // from here on in the username will be the uid (if it
        // exists). This is the definitive GUID
        if (!empty($newuser->uid)) {
            $username = $newuser->uid;
            $newuser->username = $username;
        }

        // check for dodgy email
        if (!empty($newuser->email)) {
            if (email_is_not_allowed($newuser->email)) {
                unset($newuser->email);
            }
        }

        // this shouldn't happen, but default city is
        // always Glasgow
        if (!isset($newuser->city)) {
            $newuser->city = 'Glasgow';
        }

        // fix for MDL-8480
        // user CFG lang for user if $newuser->lang is empty
        // or $user->lang is not an installed language
        if (empty($newuser->lang) || !get_string_manager()->translation_exists($newuser->lang)) {
            $newuser->lang = $CFG->lang;
        }

        // basic settings
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

        // fetch full user record for the event, the complete user data contains too much info
        // and we want to be consistent with other places that trigger this event
        events_trigger('user_created', $DB->get_record('user', array('id'=>$user->id)));

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

        // run through codes finding data
        foreach ($codes as $code) {
            $coursedata = $this->external_coursedata( $code );

            // it's possible (and ok) that nothing is found
            if (!empty($coursedata)) {

                // create data record
                $coursecode = new stdClass;
                $coursecode->code = $code;
                $coursecode->courseid = $course->id;
                $coursecode->subject = $coursedata->Crse_cd_Subject;

                // COCK UP: these codes can contain letters at the end
                // but we'll just strip them off for now
                $coursecode->coursenumber = clean_param($coursedata->Crse_cd_nbr, PARAM_INT);
                $coursecode->coursename = $coursedata->Crse_name;
                $coursecode->subjectname = $coursedata->ou_name;
                $coursecode->subjectnumber = $coursedata->ou_cd;
                $coursecode->timeadded = time();
            
                // is there already a record for this combination
                if ($record = $DB->get_record( 'enrol_gudatabase_codes', array('code'=>$code, 'courseid'=>$course->id))) {
                    $coursecode->id = $record->id;
                    $DB->update_record( 'enrol_gudatabase_codes', $coursecode );
                }
                else {
                    $DB->insert_record( 'enrol_gudatabase_codes', $coursecode );
                }
            }
        }

        // now need to check if there are entries for that course
        // that should be deleted
        $entries = $DB->get_records( 'enrol_gudatabase_codes', array( 'courseid'=>$course->id ));
        if (!empty($entries)) {
            foreach ($entries as $entry) {
                if (!in_array($entry->code, $codes)) {
                    $DB->delete_records( 'enrol_gudatabase_codes', array( 'id'=>$entry->id ));
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

        // construct database object
        $courseuser = new stdClass;
        $courseuser->userid = $user->id;
        $courseuser->courseid = $course->id;
        $courseuser->code = $code;
        $courseuser->timeupdated = time();

        // insert or update
        if ($record = $DB->get_record('enrol_gudatabase_users', array('userid'=>$user->id, 'courseid'=>$course->id))) {
            $courseuser->id = $record->id;
            $DB->update_record( 'enrol_gudatabase_users', $courseuser );
        }
        else {
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

        // first need to get a list of possible course codes
        // we will aggregate single code from course shortname
        // and (possible) list from idnumber    
        $shortname = $course->shortname;
        $idnumber = $course->idnumber;
        $codes = $this->split_code( $idnumber );
        $codes[] = clean_param( $shortname, PARAM_ALPHANUM );

        // cache the codes against the course
        $this->save_codes( $course, $codes );

        // find the default role 
        $defaultrole = $this->get_config('defaultrole');

        // get the external data for these codes
        $enrolments = $this->external_enrolments( $codes );

        // iterate over the enrolments and deal
        foreach ($enrolments as $enrolment) {
            $username = $enrolment->UserName; 
            $matric_no = $enrolment->matric_no;
            
            // can we find this user
            // check against idnumber <=> matric_no if possible
            // NOTE: the username in enrol database should be correct but some
            //       are not. The matricno<=>idnumber is definitive however
            if (!$user = $DB->get_record( 'user', array('username'=>$username))) {

                // if we get here, couldn't find with username, so 
                // let's just have another go with idnumber
                if (!$user = $DB->get_record( 'user', array('idnumber'=>$matric_no))) {
                    $user = $this->create_user_record( $username, $matric_no );
                }
            }

            // enroll user into course
            $this->enrol_user( $instance, $user->id, $defaultrole, 0, 0, ENROL_USER_ACTIVE ); 

            // cache enrolment 
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

        // get all instances in this course
        $instances = enrol_get_instances( $course->id, TRUE );

        // search for this one
        $found = FALSE;
        foreach ($instances as $instance) {
            if ($instance->enrol == $this->get_name()) {
                $found = TRUE;
                $instanceid = $instance->id;
            }
        }

        // if we didn't find it then add it
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

        // make sure we have config
        $this->load_config();

        // we want all our new courses to have this plugin
        if ($inserted) {
            $instanceid = $this->add_instance($course);
        }
        else {
            $instanceid = $this->check_instance( $course );
        }

        // get the instance of the enrolment plugin
        $instance = $DB->get_record('enrol', array('id'=>$instanceid));

        // add the users to the course
        $this->enrol_course_users( $course, $instance );

        return TRUE;
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

        // this is just a bodge to kill this for admin users
        $admins = explode( ',', $CFG->siteadmins );
        if (in_array($user->id, $admins)) {
            return true;
        }

        // get the list of courses for current user
        $enrolments = $this->external_userdata( $user );

        // if there aren't any then there's nothing to see here
        if (empty($enrolments)) {
            return true;
        }

        // there could be duplicate courses going this way, so we'll 
        // build an array to filter them out
        $uniquecourses = array();
        
        // go through list of codes and find the courses
        foreach ($enrolments as $enrolment) {
        
            // we need to find the courses in our own table of courses
            // to allow for multiple codes
            $codes = $DB->get_records('enrol_gudatabase_codes', array('code'=>$enrolment->courses));
            if (!empty($codes)) {
                foreach ($codes as $code) {
                    $uniquecourses[ $code->courseid ] = $code;
                }
            }
        }

        // find the default role 
        $defaultrole = $this->get_config('defaultrole');

        // go through the list of course codes and enrol student
        if (!empty($uniquecourses)) {
            foreach ($uniquecourses as $courseid=>$code) {
      
                // get course object
                if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
                    continue;
                }

                // make sure it has this enrolment plugin
                $instanceid = $this->check_instance( $course );

                // get the instance of the enrolment plugin
                $instance = $DB->get_record('enrol', array('id'=>$instanceid));

                // enroll user into course
                $this->enrol_user( $instance, $user->id, $defaultrole, 0, 0, ENROL_USER_ACTIVE ); 

                // cache enrolment 
                $this->cache_user_enrolment( $course, $user, $code->code );
            }
        }

        return true;
    }

    /**
     * cron service to update course enrolments
     */
    function cron() {
        global $CFG;
        global $DB;

        // get the start time, we'll limit
        // how long this runs for
        $starttime = time();

        // get plugin config
        $config = get_config( 'enrol_gudatabase' );

        // are we set up?
        if (empty($config->dbhost)) {
            mtrace( 'enrol_gudatabase: not configured' );
            return false;
        }

        // get the last course index we processed
        if (empty($config->startcourseindex)) {
            $startcourseindex = 0;
        }
        else {
            $startcourseindex = $config->startcourseindex;
        }
        mtrace( "enrol_gudatabase: starting at course index $startcourseindex" );

        // get the basics of all visible courses
        // don't load the whole course records!!
        $courses = $DB->get_records( 'course', array('visible'=>1), '', 'id' );

        // convert courses to simple array
        $courses = array_values( $courses );
        $highestindex = count($courses)-1;
        mtrace( "enrol_gudatabase: highest course index is $highestindex" );

        // process from current index to (potentially) the end
        for ($i=$startcourseindex; $i<=$highestindex; $i++) {
            $course = $DB->get_record('course', array('id'=>$courses[$i]->id));

            // avoid site and front page
            if ($course->id > 1) {
                mtrace( "enrol_gudatbase: updating enrolments for course '{$course->fullname}'" );
                $this->course_updated(FALSE, $course, NULL);
            }
            $lastcourseprocessed = $i;

            // if we've used too much time then bail out
            $elapsedtime = time() - $starttime;
            if ($elapsedtime > ENROL_GUDATABASE_MAXTIME) {
                break;
            }
        }

        // set new value of index
        if ($lastcourseprocessed >= $highestindex) {
            $nextcoursetoprocess = 0;
        }
        else {
            $nextcoursetoprocess = $lastcourseprocessed+1;
        }
        set_config( 'startcourseindex', $nextcoursetoprocess, 'enrol_gudatabase' );
        mtrace( "enrol_gudatabase: next course index to process is $nextcoursetoprocess" );

        // create very poor average course process
        $oldaverage = empty($config->average) ? 0 : $config->average;
        $newaverage = ($oldaverage + $lastcourseprocessed - $startcourseindex)/2;
        set_config( 'average', $newaverage, 'enrol_gudatabase' );
        mtrace( 'enrol_gudatabase: completed, processed courses = ' . ($lastcourseprocessed - $startcourseindex) );
    }
}

