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
        if (!enrol_is_enabled('gudatabase')) {
            return true;
        }
        if (!$this->get_config('dbtype') or !$this->get_config('dbhost') or !$this->get_config('remoteenroltable') or !$this->get_config('remotecoursefield') or !$this->get_config('remoteuserfield')) {
            return true;
        }
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

        return $enrolments;
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

        // find the default role 
        $defaultrole      = $this->get_config('defaultrole');

        // get the external data for these codes
        $enrolments = $this->external_enrolments( $codes );

        // iterate over the enrolments and deal
        foreach ($enrolments as $enrolment) {
            $username = $enrolment->UserName; 
            
            // can we find this user
            if (!$user = $DB->get_record( 'user', array('username'=>$username)) {

            }
        }

echo "<pre>"; print_r( $enrolments ); die;
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
            $this->add_instance($course);
        }
        else {

            // force instance if it doesn't have one
            $instances = enrol_get_instances( $course->id, TRUE );
            $found = FALSE;
            foreach ($instances as $instance) {
                if ($instance->enrol == $this->get_name()) {
                    $found = TRUE;
                    $instanceid = $instance->id;
                }
            }
            if (!$found) {
                $instanceid = $this->add_instance($course);
            }
        }

        // get the instance of the enrolment plugin
        $instance = $DB->get_record('enrol', array('id'=>$instanceid));

        // add the users to the course
        $this->enrol_course_users( $course, $instance ); 

        echo "<pre>"; print_r( $course ); die;
    }

}

