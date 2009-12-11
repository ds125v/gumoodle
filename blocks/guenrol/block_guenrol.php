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
        $this->version = 2009083100;  // YYYYMMDDXX
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

        // sanity check - if db enrolment isn't set up then bail
        if (empty($CFG->enrol_dbname)) {
            $this->content->text = get_string( 'dbnotconfigured','block_guenrol' );
            $this->content->footer = '';
            return $this->content;
        }

        // get course context
        $coursecontext = get_context_instance( CONTEXT_COURSE, $COURSE->id );

        // get the list of users from the external database
        $userlist = get_enrolled_users();
        if ($userlist===false) {
            $this->content->text = get_string( 'dbconnecterror','block_guenrol' );
            $this->content->footer = '';
            return $this->content;
        }


        // get the number of users who have profiles already
        $usercheck = get_existing_users( $userlist );

        // get the default role for this course 
        $role = get_default_course_role( $COURSE );

        $this->content->text = get_string('numberusers','block_guenrol',count($userlist) );
        $this->content->text .= get_string('numberexistingusers','block_guenrol',$this->count_existingusers );

        $this->content->footer = '';
        return $this->content;
    }



}

?>
