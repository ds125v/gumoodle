<?php
// WI Forms Block
//
// Copyright E-Learn Design Limited 2009
// http://www.e-learndesign.co.uk
//

class block_wiforms extends block_list {

    /** 
     * set the block name and version number
     */
    function init() {
        $this->title = get_string('blockname', 'block_wiforms');
        $this->version = 2009121701; // YYMMDDXX
    }

    /*
     * This has a global config screen
     */
    function has_config() {
        return true;
    }

    /*
     * Get the contents of the block
     */
    function get_content() {
        global $USER, $CFG, $COURSE;

        if ($this->content != NULL) {
            return $this->content;
        } 

        // set up content
        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->items = array();
        $this->content->icons = array();

        // check capability
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        if (!has_capability('block/wiforms:access', $context  )) {
            return $this->content;
        }

        // course id
        $id = $COURSE->id;

        // add links to forms
        $this->content->items[] = "<a href=\"{$CFG->wwwroot}/blocks/wiforms/email.php?id=$id&amp;form=enlargement\">Notice of Enlargement of WIs</a>";
        $this->content->icons[] = "<img src=\"{$CFG->pixpath}/f/text.gif\" height=\"16\" width=\"16\" alt=\"icon\" />";

        $this->content->items[] = "<a href=\"{$CFG->wwwroot}/blocks/wiforms/email.php?id=$id&amp;form=formation\">Notice of Formation of an Institute</a>";
        $this->content->icons[] = "<img src=\"{$CFG->pixpath}/f/text.gif\" height=\"16\" width=\"16\" alt=\"icon\" />";

        $this->content->items[] = "<a href=\"{$CFG->wwwroot}/blocks/wiforms/email.php?id=$id&amp;form=suspension\">Notice of Suspension of a WI</a>";
        $this->content->icons[] = "<img src=\"{$CFG->pixpath}/f/text.gif\" height=\"16\" width=\"16\" alt=\"icon\" />";

        return $this->content;
    }

}
