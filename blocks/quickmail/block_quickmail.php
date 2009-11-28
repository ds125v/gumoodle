<?php // $Id: block_quickmail.php,v 2.0 2007/04/05 09:53:43 whchuang Exp $

/**
 * Quickmail - Allows teachers and students to email one another
 *      at a course level. This version of Quickmail disabled the group
 *      mode as it is still not stable in Moodle 1.8
 *
 * @Original author Mark Nielsen, updated by Bibek Bhattarai and Wen Hao Chuang
 * @package quickmailv2
 **/ 

/**
 * This is the Quickmail block class.  Contains the necessary
 * functions for a Moodle block.  Has some extra functions as well
 * to increase its flexibility and useability
 *
 * @package moodleblock
 * @author Mark Nielsen
 * @todo Make a global config so that admins can set the defaults (default for student (yes/no) default for groupmode (select a groupmode or use the courses groupmode)) 
 * NOTE: make sure email.php and emaillog.php use the global config settings
 **/
class block_quickmail extends block_list {
    
    /**
     * Sets the block name and version number
     *
     * @return void
     * @author Mark Nielsen
     **/
    function init() {
        $this->title = get_string('blockname', 'block_quickmail').' (Beta)';
		//$this->title = this->title.' Beta Test Version';
        $this->version = 2007040500;  // YYYYMMDDXX
    }
    
    /**
     * Gets the contents of the block (course view)
     *
     * @return object An object with an array of items, an array of icons, and a string for the footer
     * @author Mark Nielsen
     **/
    function get_content() {
        global $USER, $CFG;

        if($this->content !== NULL) {
            return $this->content;
        }
        
        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->items = array();
        $this->content->icons = array();
        
        if (empty($this->instance)) {
            return $this->content;
        }

    /// load defaults (will only load if config is empty)
        $this->load_defaults();

        $this->load_course();
        
        if (!$this->check_permission()) {
            return $this->content;
        }
                
    /// link to composing an email
    /// here we revised a little bit to add a question mark for HELP button - Wen Hao Chuang
        $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/quickmail/email.php?id='.$this->course->id.'&amp;instanceid='.$this->instance->id.'">'.
                                    get_string('composeemail', 'block_quickmail').'</a>'.'	<a target="popup" title="Quickmail" href="../help.php?module=moodle&amp;file=quickmail.html" onclick="return openpopup(\'/help.php?module=moodle&amp;file=quickmail.html\', \'popup\', \'menubar=0,location=0,scrollbars,resizable,width=500,height=400\', 0);"><img height="17" width="17" alt="quickmail" src="../pix/help.gif" /></a>';

        $this->content->icons[] = '<img src="'.$CFG->pixpath.'/i/email.gif" height="16" width="16" alt="'.get_string('email').'" />';
        
    /// link to history log
     //   $this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/quickmail/emaillog.php?id='.$this->course->id.'&amp;instanceid='.$this->instance->id.'">'.
       //                             get_string('emailhistory', 'block_quickmail').'</a>';

        $this->content->icons[] = '<img src="'.$CFG->pixpath.'/t/log.gif" height="14" width="14" alt="'.get_string('log').'" />';
    
    /// link to config for teachers
        if (isteacher($this->instance->pageid)) {
            $this->content->footer = "<a href=\"$CFG->wwwroot/course/view.php?id={$this->instance->pageid}&amp;instanceid={$this->instance->id}&amp;sesskey=$USER->sesskey&amp;blockaction=config\">".
                            get_string('settings').'...</a>';
        }
        
        return $this->content;
    }
    
    /**
     * Allows the block to be configurable at an instance level.
     *
     * @return boolean
     * @author Mark Nielsen
     **/
    function instance_allow_config() {
        return true;
    }
    
    /**
     * Check to make sure that the current user is allowed to use Quickmail.
     *
     * Permissions:
     *          Teacher/Admin:  Always allow
     *          Student:        If allowed by instance
     *          Guest/Other:    Never allow
     * @return boolean True for access / False for denied
     * @author Mark Nielsen
     **/
    function check_permission() {
        if (isteacher($this->instance->pageid)) {
            return true;
        } else if ($this->config->allowstudents) {
            return isstudent($this->instance->pageid);
        } else {
            return false;
        }
    }


    /**
     * Get the groupmode of Quickmail.  This function pays
     * attention to the course group mode force.
     *
     * @return int The group mode of the block
     * @author Mark Nielsen
     **/
    function groupmode() {
        $this->load_course();
                
        if ($this->course->groupmodeforce) {
            return $this->course->groupmode;
        } else {
            return $this->config->groupmode;
        }
    }

 
    /**
     * Loads default config data when config is empty (that way we know it exists).
     *
     * Defaults:
     *      group mode           = course group mode
     *      allow student access = yes
     * @return void
     * @author Mark Nielsen
     * @todo Make a global config so that admins can set the defaults (default for student (yes/no) default for groupmode (select a groupmode or use the courses groupmode))  NOTE: make sure email.php and emaillog.php use the global config settings
     **/
    function load_defaults() {
        if (empty($this->config)) {
        /// blank config
            $this->load_course();

            $defaults = new stdClass;
            $defaults->groupmode = $this->course->groupmode;
            $defaults->allowstudents = 0;

            $this->instance_config_save($defaults);
        }
    }
    
    /**
     * Loads the course record into $this->course.
     *
     * This function first checks to make sure that
     * the course is not already loaded first.  If not,
     * then grab it from the database
     *
     * @return void
     * @author Mark Nielsen
     **/
    function load_course() {
        if (empty($this->course)) {
            $this->course = get_record('course', 'id', $this->instance->pageid);
        }
    }
}

?>
