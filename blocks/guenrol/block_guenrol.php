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

        //if($this->content !== NULL) {
        //    return $this->content;
        //}

        // get course context
        $coursecontext = get_context_instance( CONTEXT_COURSE, $COURSE->id );

        // get the list of users from the external database
        $userlist = get_db_users();

        // get the number of users who have profiles already
        $existing_profile_count = get_authenticated_users( $userlist );

        // get the default role for this course 
        $role = get_default_course_role( $COURSE );

        // get the users who are enrolled in the course
        $enrolled_in_course_count = get_enrolled_users( $userlist, $role, $coursecontext );

        // get data from ldap server
        get_ldap_data( $userlist );

echo "<pre>"; var_dump( $userlist ); die;
        $this->content->text = "<p>Course users = ".count($userlist)."</p>";
        $this->content->text .= "<p>Authenticated users = ".$existing_profile_count."</p>";
        $this->content->text .= "<p>Enrolled users = ".$enrolled_in_course_count."</p>";

        $this->content->footer = '';
        return $this->content;
    }



}

?>
