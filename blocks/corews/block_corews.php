<?php

class block_corews extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_corews');
    }

    function get_content () {
        global $USER, $CFG, $SESSION;

        if ($this->content !== NULL) {
            return $this->content;
        }

        // Empty content
        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = '';
 
        // Block is only visible to course editors
        $context = get_context_instance(CONTEXT_COURSE, $this->page->course->id);
        if (!has_capability('moodle/course:update', $context)) {
            return $this->content;
        }

        // check that the block has been configured
        if (empty($this->config->coursecode) or empty($this->config->courseid)) {
            $this->content->text = get_string('notconfigured', 'block_corews');
            return $this->content;
        }

        // just display settings
        $this->content->text = '<ul>';
        $this->content->text .= '<li>' . get_string('coursecode', 'block_corews') . " '{$this->config->coursecode}'</li>";
        $this->content->text .= '<li>' . get_string('courseid', 'block_corews') . " '{$this->config->courseid}'</li>";
        $this->content->text .= '</ul>';

        return $this->content;
    }
}


