<?php

class block_ws extends block_base {

    function init() {
        $this->title = get_string('title', 'block_ws');
        $this->version = 2010072700;
    }

    function applicable_formats() {
        return array('site' => true);
    }

    function specialization() {
        global $CFG;
    }

    function get_content() {
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        // it doesn't actually have any content
        $this->content = new stdClass;
        $this->content->text = $data;
        $this->content->footer ='';
        return $this->content;
    }
}
?>
