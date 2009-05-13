<?php
require_once($CFG->dirroot.'/mod/feedback/lib.php');
class block_feedback extends block_base {

    function init() {
        $this->title = get_string('feedback', 'block_feedback');
        $this->version = 2007072601;
    }

    function get_content() {
        global $CFG;
        $courseid = intval($this->instance->pageid);
        if($courseid <= 0) $courseid = SITEID;
        if($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance->pageid)) {
            $this->instance->pageid = SITEID;
        }

        $this->content = New stdClass;
        $this->content->text = '';

        if ( $feedbacks = feedback_get_feedbacks_from_sitecourse_map($courseid)) { //arb
        
            foreach ($feedbacks as $feedback) { //arb
                $this->content->text .= 
                    '<a href="'.htmlspecialchars($CFG->wwwroot.'/mod/feedback/view.php?id='.$feedback->cmid.'&courseid='.$courseid).'">
                        '.$feedback->name . '
                    </a><br/>';
            }
    
        }

        $this->content->footer = '';

        return $this->content;

    }
    
    function applicable_formats() {
        return array('site' => true, 'course' => true);
    }

}

?>
