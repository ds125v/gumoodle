<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 2009 onwards  E-Learn Design Limited                    //
// http://www.e-learndesign.co.uk
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

class block_wiforms extends block_list {

    /** 
     * set the block name and version number
     */
    function init() {
        $this->title = get_string('blockname', 'block_wiforms');
        $this->version = 2009122300; // YYMMDDXX
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
