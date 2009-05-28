<?php

class block_create_course_archive extends block_base {
    function init() {
        $this->title = get_string('createcoursearchive', 'block_create_course_archive');
        $this->version = 2009051301;
    }
    
    function specialization() {
        $this->title = get_string('archivecourse', 'block_create_course_archive');
    }
    
    function get_content() {
        global $COURSE, $CFG;
        if($this->content !== NULL) {
            return $this->content;
        }
        $this->content = new stdClass;
        //$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        //if(has_capability('moodle/course:update', $context)) {
        $link = $CFG->wwwroot.'/blocks/create_course_archive/index.php?id='.$COURSE->id;
        $this->content->text = '<a href="'.$link.'">'.get_string('archivecourse', 'block_create_course_archive').'</a>';
        $this->content->footer = null;
        //} else {
        //	$this->content->text='';
        //    $this->content->footer='';
        //}
        return $this->content;
    }
}
?>

