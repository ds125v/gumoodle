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
 * GUID Enrolment sync
 *
 * @package    gusync
 * @copyright  2012 Howard miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// how many seconds can this run for on every
// cron invocation
define( 'LOCAL_GUSYNC_MAXTIME', 10 );

/**
 * gusync cron script
 */
function local_gusync_cron() {
    global $CFG;
    global $DB;

    // get the start time, we'll limit
    // how long this runs for
    $starttime = time();

    // get plugin config
    $config = get_config( 'local_gusync' );
  
    // are we set up?
    if (empty($config->dbhost)) {
        mtrace( 'gusync: not configured' );
        return false;
    }

    // attempt to connect to external db
    if (!$extdb=local_gusync_dbinit($config)) {
        mtrace( 'gusync: unable to connect to external database' );
        return false;
    }

    // get the last course index we processed
    if (empty($config->startcourseindex)) {
        $startcourseindex = 0;
    }
    else {
        $startcourseindex = $config->startcourseindex;
    }
    mtrace( "gusync: starting at course index $startcourseindex" );

    // get the basics of all visible courses
    // don't load the whole course records!!
    $courses = $DB->get_records( 'course', array('visible'=>1), '', 'id' );

    // convert courses to simple array
    $courses = array_values( $courses );
    $highestindex = count($courses)-1;
    mtrace( "gusync: highest course index is $highestindex" );

    // process from current index to (potentially) the end
    for ($i=$startcourseindex; $i<=$highestindex; $i++) {
        $course = $courses[$i];
        local_gusync_processcourse( $extdb, $course->id );
        $lastcourseprocessed = $i;
        
        // if we've used too much time then bail out
        $elapsedtime = time() - $starttime;
        if ($elapsedtime > LOCAL_GUSYNC_MAXTIME) {
            break;
        }
    }

    // set new value of index
    if ($lastcourseprocessed >= $highestindex) {
        set_config( 'startcourseindex', 0, 'local_gusync' );
    }
    else {
        set_config( 'startcourseindex', $lastcourseprocessed+1, 'local_gusync' );
    }

    // create very poor average course process
    $oldaverage = empty($config->average) ? 0 : $config->average;
    $newaverage = ($oldaverage + $lastcourseprocessed - $startcourseindex)/2;
    set_config( 'average', $newaverage, 'local_gusync' );  
    mtrace( 'gusync: completed, processed courses = ' . ($lastcourseprocessed - $startcourseindex) );

    // done with engines
    $extdb->Close();
    return true;
}

/**
 * process the enrolments in a given course
 * @param object $extdb
 * @param int $id course id
 */
function local_gusync_processcourse( $extdb, $id ) {
    global $CFG;
    global $DB;
    global $SITE;

    // site name
    $sitename = $SITE->shortname;

    // get complete course
    $course = $DB->get_record( 'course', array('id'=>$id));

    // try to find existing record
    $coursesql = "select * from moodlecourses where ";
    $coursesql .= "courseid = $id and site='$sitename' ";
    $extcourse = local_gusync_query( $extdb, $coursesql, TRUE );

    // update/insert
    if (empty($extcourse))  {
        $sql = "insert into moodlecourses ";
        $sql .= "set site='$sitename', ";
        $sql .= "courseid=$id, ";
        $sql .= "shortname='" . addslashes($course->shortname) . "', ";
        $sql .= "name='" . addslashes($course->fullname) . "', ";
        $sql .= "startdate={$course->startdate} ";
    }
    else {
        $sql = "update moodlecourses ";
        $sql .= "set site='$sitename', ";
        $sql .= "courseid=$id, ";
        $sql .= "shortname='" . addslashes($course->shortname) . "', ";
        $sql .= "name='" . addslashes($course->fullname) . "', ";
        $sql .= "startdate={$course->startdate} ";
        $sql .= "where id={$extcourse->id}";
    }
    local_gusync_query( $extdb, $sql );

    // reload course object with final data
    $extcourse = local_gusync_query( $extdb, $coursesql, TRUE );

    // get list of enrolments for this course
    $users = local_gusync_getusers( $course );

    // if no users, nothing to do
    if (empty($users)) {
        return false;
    }

    // loop through users, adding updating enrol table
    foreach ($users as $user) {
        $guid = $user->username;

        // get lastaccess for this user
        if ($lastaccess = $DB->get_record('user_lastaccess', array('userid'=>$user->id, 'courseid'=>$id))) {
            $timeaccess = $lastaccess->timeaccess;
        }
        else {
            $timeaccess = 0;
        }
    
        // try to find existing record
        $enrolsql = "select * from moodleenrolments ";
        $enrolsql .= "where guid='$guid' and moodlecoursesid={$extcourse->id} ";
        $extenrol = local_gusync_query( $extdb, $enrolsql, TRUE );

        // update/insert
        if (empty($extenrol)) {
            $sql = "insert into moodleenrolments ";
            $sql .= "set guid='$guid', ";
            $sql .= "moodlecoursesid={$extcourse->id}, ";
            $sql .= "timestart = {$user->timemodified}, ";
            $sql .= "timelastaccess = $timeaccess ";    
        }
        else {
            $sql = "update moodleenrolments ";
            $sql .= "set guid='$guid', ";
            $sql .= "moodlecoursesid={$extcourse->id}, ";
            $sql .= "timestart = {$user->timemodified}, ";
            $sql .= "timelastaccess = $timeaccess ";    
            $sql .= "where id={$extenrol->id} ";
        }
        local_gusync_query( $extdb, $sql );
    }
}

/**
 * get the users enrolled in the selected course
 * @param object $course
 * @return array user objects
 */
function local_gusync_getusers( $course ) {
    global $DB;

    // get active enrolments on this course
    $instances = enrol_get_instances( $course->id, true );

    // nothing to do?
    if (empty($instances)) {
        return false;
    }

    // get the (guid) users in these instances
    $users = array();
    foreach ($instances as $instance) {
        $sql = "select distinct u.id as id, username, ue.timemodified as timemodified, auth ";
        $sql .= "from {user} as u join {user_enrolments} as ue on (ue.userid = u.id) ";
        $sql .= "where enrolid = ? ";
        $sql .= "and auth = ? ";
        $enrolments = $DB->get_records_sql( $sql, array($instance->id, 'guid') );

        // any?
        if (empty($enrolments)) {
            continue;
        }

        // add users indexing by username (we don't care how enrolled)
        foreach ($enrolments as $guid=>$enrolment) {
            $users[$guid] = $enrolment;
        }
    }

    return $users;
}

/**
 * Tries to make connection to the external database.
 *
 * @return null|ADONewConnection
 */
function local_gusync_dbinit($config) {
    global $CFG;

    require_once($CFG->libdir.'/adodb/adodb.inc.php');

    // Connect to the external database (forcing new connection)
    $extdb = ADONewConnection('mysqli');

    // the dbtype my contain the new connection URL, so make sure we are not connected yet
    if (!$extdb->IsConnected()) {
        $result = $extdb->Connect(
            $config->dbhost, 
            $config->dbuser, 
            $config->dbpass, 
            $config->dbname, 
            TRUE
        );
        if (!$result) {
            return null;
        }
    }

    $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
    return $extdb;
}

/**
 * Execute sql and return rows from external database
 * @param object $extdb 
 * @param string $sql
 * @param boolean $singlerecord
 * @return mixes rows or false
 */
function local_gusync_query( $extdb, $sql, $singlerecord=FALSE ) {

    // attempt to execute the sql
    if (!$rs = $extdb->Execute($sql)) {
        mtrace( 'gusync: failed to execute ' . $sql . " (Error is '" . $extdb->ErrorMsg() . "')" );
        return false;
    }

    // return data
    $results = array();
    if (!$rs->EOF) {
        while ($fields = $rs->FetchRow()) {
            $results[] = (object)$fields;
        }
    }

    // check if results obtained
    if (empty($results)) {
        return FALSE;
    }

    // return only the first record if required
    if ($singlerecord) {
        return $results[0];
    }

    return $results;
}

