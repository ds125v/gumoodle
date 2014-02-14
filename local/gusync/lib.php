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
 * @package    local_gusync
 * @copyright  2012 Howard miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * gusync cron script
 */
function local_gusync_cron() {
    global $CFG;
    global $DB;

    // Get the start time, we'll limit
    // how long this runs for.
    $starttime = time();

    // Get plugin config.
    $config = get_config( 'local_gusync' );

    // Are we set up?
    if (empty($config->dbhost)) {
        mtrace( 'local_gusync: not configured' );
        return false;
    }

    // Testing mode warning.
    if ($config->testing) {
        mtrace( 'local_gusync: running in testing mode' );
    }

    // Attempt to connect to external db.
    if (!$extdb = local_gusync_dbinit($config)) {
        mtrace( 'local_gusync: unable to connect to external database' );
        return false;
    }

    // Get the last course index we processed.
    if (empty($config->startcourseindex)) {
        $startcourseindex = 0;
    } else {
        $startcourseindex = $config->startcourseindex;
    }
    mtrace( "local_gusync: starting at course index $startcourseindex" );

    // Get the basics of all visible courses
    // don't load the whole course records!!
    $courses = $DB->get_records( 'course', array(), '', 'id' );

    // Convert courses to simple array.
    $courses = array_values( $courses );
    $highestindex = count($courses) - 1;
    mtrace( "local_gusync: highest course index is $highestindex" );
    mtrace( "local_gusync: configured time limit is {$config->timelimit} seconds" );

    // Count the courses processed.
    $processcount = 0;

    // Process from current index to (potentially) the end.
    for ($i = $startcourseindex; $i <= $highestindex; $i++) {
        $course = $courses[$i];
        local_gusync_processcourse( $extdb, $course->id, $config->testing );
        $lastcourseprocessed = $i;
        $processcount++;

        // If we've used too much time then bail out.
        $elapsedtime = time() - $starttime;
        if ($elapsedtime > $config->timelimit) {
            break;
        }
    }

    // Set new value of index.
    if ($lastcourseprocessed >= $highestindex) {
        set_config( 'startcourseindex', 0, 'local_gusync' );
    } else {
        set_config( 'startcourseindex', $lastcourseprocessed + 1, 'local_gusync' );
    }

    // Create very poor average course process.
    $oldaverage = empty($config->average) ? 0 : $config->average;
    $newaverage = ($oldaverage + $lastcourseprocessed - $startcourseindex) / 2;
    set_config( 'average', $newaverage, 'local_gusync' );
    mtrace( 'local_gusync: completed, processed courses = ' . $processcount );
    $elapsedtime = time() - $starttime;
    mtrace( "local_gusync: actual elapsed time was $elapsedtime seconds" );

    // Done with engines.
    $extdb->Close();
    return true;
}

/**
 * process the enrolments in a given course
 * @param object $extdb
 * @param int $id course id
 */
function local_gusync_processcourse( $extdb, $id, $testing ) {
    global $CFG;
    global $DB;
    global $SITE;

    // Site name.
    $sitename = $SITE->shortname;

    // Get complete course.
    $course = $DB->get_record( 'course', array('id' => $id));
    mtrace( 'local_gusync: processing course ' . $course->shortname );

    // Add users from visible courses and take them
    // from hidden ones (too).
    $visible = $course->visible;

    // Try to find existing record.
    $coursesql = "select * from moodlecourses where ";
    $coursesql .= "courseid = $id and site='$sitename' ";
    $extcourse = local_gusync_query( $extdb, $coursesql, true );

    // Update/insert.
    if (empty($extcourse)) {
        $sql = "insert into moodlecourses ";
        $sql .= "set site='$sitename', ";
        $sql .= "wwwroot='" . addslashes($CFG->wwwroot) . "', ";
        $sql .= "courseid=$id, ";
        $sql .= "shortname='" . addslashes($course->shortname) . "', ";
        $sql .= "name='" . addslashes($course->fullname) . "', ";
        $sql .= "startdate={$course->startdate} ";
    } else {
        $sql = "update moodlecourses ";
        $sql .= "set site='$sitename', ";
        $sql .= "wwwroot='" . addslashes($CFG->wwwroot) . "', ";
        $sql .= "courseid=$id, ";
        $sql .= "shortname='" . addslashes($course->shortname) . "', ";
        $sql .= "name='" . addslashes($course->fullname) . "', ";
        $sql .= "startdate={$course->startdate} ";
        $sql .= "where id={$extcourse->id}";
    }
    if (!$testing) {
        local_gusync_query( $extdb, $sql );
    }

    // Reload course object with final data.
    $extcourse = local_gusync_query( $extdb, $coursesql, true );

    // If we are 'testing' then it's possible this is still empty
    if ($testing && empty($extcourse)) {
        mtrace('local_gusync: course data does not exist and not written in testing mode (warning)');
        return;
    }

    // If not visible then just throw everybody out regardless.
    if (!$visible) {
        $sql = 'delete from moodleenrolments ';
        $sql .= "where moodlecoursesid={$extcourse->id}";
        if (!$testing) {
            local_gusync_query( $extdb, $sql );
        }
        mtrace( 'local_gusync: all users removed for hidden course ' . $course->shortname );
        return;
    }

    // Get list of enrolments for this course.
    $users = local_gusync_getusers( $course );

    // Create list of GUIDs for later deletion checking.
    $guids = array();

    // Loop through users, adding updating enrol table.
    if (!empty($users)) {
        foreach ($users as $user) {
            $guid = $user->username;
            $guids[ $guid ] = $guid;

            // Get lastaccess for this user.
            if ($lastaccess = $DB->get_record('user_lastaccess', array('userid' => $user->id, 'courseid' => $id))) {
                $timeaccess = $lastaccess->timeaccess;
            } else {
                $timeaccess = 0;
            }

            // Try to find existing record.
            $enrolsql = "select * from moodleenrolments ";
            $enrolsql .= "where guid='$guid' and moodlecoursesid={$extcourse->id} ";
            $extenrol = local_gusync_query( $extdb, $enrolsql, true );

            // Update/insert.
            if (empty($extenrol)) {
                $sql = "insert into moodleenrolments ";
                $sql .= "set guid='$guid', ";
                $sql .= "moodlecoursesid={$extcourse->id}, ";
                $sql .= "timestart = {$user->timemodified}, ";
                $sql .= "timelastaccess = $timeaccess ";
            } else {
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

    // Now we need to find any users that are in the external table
    // but NOT enrolled in the course.

    // Get complete list.
    $sql = "select id,guid from moodleenrolments where moodlecoursesid={$extcourse->id}";
    $enrolments = local_gusync_query( $extdb, $sql );

    // Run through and find any NOT in the $users list.
    $deleteusers = array();
    if (!empty($enrolments)) {
        foreach ($enrolments as $enrolment) {
            $guid = $enrolment->guid;
            if (empty($guids[$guid])) {
                $deleteusers[ $enrolment->id ] = $enrolment->id;
            }
        }
    }

    // Delete sql.
    if (count($deleteusers) > 0) {
        $list = implode(',', $deleteusers);
        $sql = "delete from moodleenrolments where id in ($list) ";
        if (!$testing) {
            local_gusync_query( $extdb, $sql );
        }
    }
}

/**
 * get the users enrolled in the selected course
 * @param object $course
 * @return array user objects
 */
function local_gusync_getusers( $course ) {
    global $DB;

    // Get active enrolments on this course.
    $instances = enrol_get_instances( $course->id, true );

    // Nothing to do?
    if (empty($instances)) {
        return false;
    }

    // Get the (guid) users in these instances.
    $users = array();
    foreach ($instances as $instance) {
        $sql = "select distinct u.id as id, username, ue.timemodified as timemodified, auth ";
        $sql .= "from {user} as u join {user_enrolments} as ue on (ue.userid = u.id) ";
        $sql .= "where enrolid = ? ";
        $sql .= "and auth = ? ";
        $enrolments = $DB->get_records_sql( $sql, array($instance->id, 'guid') );

        // Any?
        if (empty($enrolments)) {
            continue;
        }

        // Add users indexing by username (we don't care how enrolled).
        foreach ($enrolments as $guid => $enrolment) {
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

    // Connect to the external database (forcing new connection).
    $extdb = ADONewConnection('mysqli');

    // The dbtype my contain the new connection URL, so make sure we are not connected yet.
    if (!$extdb->IsConnected()) {
        $result = $extdb->Connect(
            $config->dbhost,
            $config->dbuser,
            $config->dbpass,
            $config->dbname,
            true
        );
        if (!$result) {
            echo "<pre>".$extdb->ErrorMsg()."</pre>";
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
function local_gusync_query( $extdb, $sql, $singlerecord=false ) {

    // Attempt to execute the sql.
    if (!$rs = $extdb->Execute($sql)) {
        mtrace( 'local_gusync: failed to execute ' . $sql . " (Error is '" . $extdb->ErrorMsg() . "')" );
        return false;
    }

    // Return data.
    $results = array();
    if (!$rs->EOF) {
        while ($fields = $rs->FetchRow()) {
            $results[] = (object)$fields;
        }
    }

    // Check if results obtained.
    if (empty($results)) {
        return array();
    }

    // Return only the first record if required.
    if ($singlerecord) {
        return $results[0];
    }

    return $results;
}

// EVENT HANDLER(S).

/**
 * Catch course_deleted event
 * @param object $course course object
 */
function local_gusync_course_deleted($course) {
    global $SITE;

    // Site name.
    $sitename = $SITE->shortname;

    // Get plugin config.
    $config = get_config( 'local_gusync' );

    // Are we set up?
    if (empty($config->dbhost)) {
        mtrace( 'local_gusync: not configured' );
        return false;
    }

    // Attempt to connect to external db.
    if (!$extdb = local_gusync_dbinit($config)) {
        mtrace( 'local_gusync: unable to connect to external database' );
        return false;
    }

    // Try to find existing course record.
    $coursesql = "select * from moodlecourses where ";
    $coursesql .= "courseid = {$course->id} and site='$sitename' ";
    $extcourse = local_gusync_query( $extdb, $coursesql, true );

    if (!empty($extcourse)) {

        // Delete all the users for this course.
        $sql = 'delete from moodleenrolments where moodlecoursesid = ' . $extcourse->id;
        if (!$config->testing) {
            local_gusync_query( $extdb, $sql );
        }

        // Delete the course.
        $sql = 'delete from moodlecourses where id = ' . $extcourse->id;
        if (!$config->testing) {
            local_gusync_query( $extdb, $sql );
        }
    }

    $extdb->Close();
    return true;
}

