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
        $this->version = 2009121700; // YYMMDDXX
    }

    /*
     * Get the contents of the block
     */
    function get_content() {
        global $USER, $CFG;

        if ($this->content != NULL) {
            return $this->content;
        } 

        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->items = array();
        $this->content->icons = array();

        return $this->content;
    }

}
