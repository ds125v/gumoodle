<?php

require_once( "{$CFG->dirroot}/blocks/guenrol/lib.php" );

class block_guenrol extends block_base {

    var $count_existingusers = 0; // count of users already having profiles

    /**
     * Sets the block name and version number
     *
     * @return void
     **/
    function init() {
        $this->title = get_string('blockname', 'block_guenrol');
        $this->version = 2010070704;  // YYYYMMDDXX
        $this->cron = 1;
    }

    /**
     * Gets the contents of the block (course view)
     *
     * @return object An object with an array of items, an array of icons, and a string for the footer
     **/
    function get_content() {
        global $USER, $CFG, $COURSE;

        if($this->content !== NULL) {
            return $this->content;
        }

        // if no course code is defined there's no point
        $localcoursefield = $CFG->enrol_localcoursefield;
        $coursecode = addslashes( $COURSE->$localcoursefield );
        if (empty($coursecode)) {
            $this->content->text = get_string('nocoursecode','block_guenrol');
            $this->content->footer = '';
            return $this->content;
        }

        // get course context
        $coursecontext = get_context_instance( CONTEXT_COURSE, $COURSE->id );

        // require capability
        if (!has_capability('block/guenrol:access',$coursecontext)) {
            $this->content->text = '';
            $this->content->footer = '';
            return $this->content;
        }

        // get the list of users from the external database
        $userlist = get_db_users($COURSE);

        // check for db error
        if ($userlist===false) {
            $this->content->text = get_string('dberror','block_guenrol');
            $this->content->footer = '';
            return $this->content;
        }

        // get count of external db users 
        // need to do now as the size of $userlist changes
        $dbuserscount = count( $userlist );

        // get the number of users who have profiles already
        $existing_profile_count = get_authenticated_users( $userlist );

        // get the default role for this course 
        $role = get_default_course_role( $COURSE );

        // get the users who are enrolled in the course
        $enrolled_in_course_count = get_enrolled_users( $userlist, $role, $coursecontext );

        // work out how many accounts need to be added
        // exclude accounts in enrol db but not ldap
        $toaddcount = 0;
        foreach ($userlist as $item) {
            if ($item->in_db and empty($item->enrol_method)) {
                if (!(empty($item->profile_exists) and empty($item->in_ldap))) {
                    $toaddcount++;
                }
            }
        }

        // output
        $this->content->text = "<p><center><img src=\"{$CFG->wwwroot}/blocks/guenrol/images/logo.png\" /></center></p>";

        // warning if no users
        if (empty($dbuserscount)) {
            $this->content->text .= get_string('nousers','block_guenrol');
        }
     
        $this->content->text .= "<p><center>".get_string('registryusers','block_guenrol').": <b>$dbuserscount</center></b></p>";
        $this->content->text .= "<p><center>".get_string('moodleusers','block_guenrol').": <b>$existing_profile_count</center></b></p>";
        $this->content->text .= "<p><center>".get_string('enrolledincourse','block_guenrol').": <b>$enrolled_in_course_count</center></b></p>";
        // add button (if required)
        if (!empty($toaddcount)) {
            $this->content->text .= "<p><center><a href=\"{$CFG->wwwroot}/blocks/guenrol/view.php?action=process&amp;id={$COURSE->id}&amp;sesskey=".sesskey()."\">";
            $this->content->text .= get_string('addusers','block_guenrol',$toaddcount)."</a></center></p>";
        }

        // more... button
        $this->content->text .= "<p><center><a href=\"{$CFG->wwwroot}/blocks/guenrol/view.php?id={$COURSE->id}\">More....</a></center></p>";

        $this->content->footer = '';
        return $this->content;
    }

    function cron() {
        global $CFG;

        // in this case we are just going to iterate over all visible
        // courses with courseid defined
        mtrace( 'Performing UofG automated enrollments' );

        // field name for matching course codes in course object
        $localcoursefield = $CFG->localcoursefield;

        // we're going to time how long this takes
        $starttime = microtime();
        $controltime = time();

        // get courses
        $courses = get_records_select('course',"$localcoursefield<>'' and visible=1");

        // iterate over courses doing stuff
        foreach ($courses as $course) {

            // does this course have a timestamp
            if ($guenrol_time = get_record( 'block_guenrol_time','course',$course->id)) {
                 $lasttime = $guenrol_time->timestamp;

                 // only run this once a day or if idnumber changes for each course
                 $nexttime = $lasttime + 84000;
                 if ((time()<$nexttime) and ($course->$localcoursefield == $guenrol_time->idnumber)) {
                     continue;
                 }

                 // write new timestamp
                 $guenrol_time->timestamp = time();
                 update_record( 'block_guenrol_time',$guenrol_time );
            }
            else {
          
                // write new timestamp
                $guenrol_time = null;
                $guenrol_time->course = $course->id;
                $guenrol_time->timestamp = time();
                $guenrol_time->idnumber = $course->$localcoursefield;
                insert_record( 'block_guenrol_time',$guenrol_time );
            }

            $role = get_default_course_role( $course );
            $context = get_context_instance( CONTEXT_COURSE, $course->id );
            cron_process( $course, $context, $role );

            // if we've taken more than n seconds then give up for now
            // we'll get some more on the next cron run 
            $timesofar = time() - $controltime;
            if ($timesofar > 300) {
                mtrace( 'out of time on this run of guenrol' );
                break;
            }
        }

        // how long did it take
        $endtime = microtime();
        $elapsedtime = microtime_diff( $starttime, $endtime );
        mtrace( "Time taken to run guenrol cron = ".$elapsedtime );
    }

}

?>
