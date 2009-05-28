<?php
// block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, Sept-Oct 2008.

class block_booking_form extends block_base {
    function init() {
        $this->version = 2008082100;
        $this->title = get_string('blockname', 'block_booking_form');
    }

    function specialization() {
        if(isset($this->config->title)) {
            $this->title = $this->config->title;
        }
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $CFG, $USER, $course, $timestart, $context;

        $this->content = new stdClass;
        $this->content->footer = '';

        if(isset($this->config->eventid)) {
	        $this->content->text = '<div><b>'.$this->config->eventtitle.'</b></div>';
	        $this->content->text .= '<div>'.$this->config->description . '</div>';

            $this->content->text .= '<form style="text-align: center;" action="'.$CFG->wwwroot . '/blocks/booking_form/form.php" method="GET">';
            $this->content->text .= '<input type="hidden" name="id" value="'.$course->id.'"/>';
            $this->content->text .= '<input type="hidden" name="eventid" value="'.$this->config->eventid.'"/>';
            $this->content->text .= '<input style="text-align: center;" type="submit" name="submit" value="'.get_string('bookaplace','block_booking_form').'"/>';
            $this->content->text .= '</form>';
	        //$this->content->text .= '<a href="'.$CFG->wwwroot . '/blocks/booking_form/form.php?id=' . $course->id.'&eventid='.$this->config->eventid.'">'.get_string('bookaplace','block_booking_form').'</a>';

            if(has_capability('moodle/course:manageactivities', $context)) {
	        	$this->content->text .= '<ul><li><a href="'.$CFG->wwwroot . '/blocks/booking_form/form.php?id=' . $course->id.'&eventid='.$this->config->eventid.'&notme=1">'.get_string('bookathirdpartyplace','block_booking_form').'</a></li>';
				$this->content->text .= '<li><a href="'.$CFG->wwwroot . '/blocks/booking_form/report.php?id=' . $course->id.'&eventid='.$this->config->eventid.'">'.get_string('viewreports','block_booking_form').'</a></li></ul>';                                                                       }
        }
        else {
            $this->content->text = get_string('configrequired', 'block_booking_form');
        }
        return $this->content;
    }

    function instance_allow_config() {
	    return true;
    }

    function instance_allow_multiple() {
	    return true;
    }

    function applicable_formats() {
    	return array('site-index' => false, 'course-view' => true, 'course-view-social' => true);
	}

    function instance_config_save($data, $pinned=false) {
    	global $course;
        //echo "<pre>"; print_r($data); echo "</pre>";
        $data = stripslashes_recursive($data);
        $data->timemodified=time();
        if($course->id !== null) {
	        $data->courseid = $course->id;
        } else {
	        $data->courseid = $course->id;
        }
        if(empty($data->limit)) {
        	$data->maxbookings=0;
        }
		$data2 = addslashes_recursive($data);
        if(isset($this->config->eventid)) {
            $eventid = $this->config->eventid;
            $data2->id = $this->config->eventid;
            update_record('block_booking_form', $data2);
        }
        else {
        	$eventid = insert_record('block_booking_form', $data2);
        }
        $this->config = $data;
        $this->config->eventid = $eventid;
        $table = 'block_instance';
        if (!empty($pinned)) {
            $table = 'block_pinned';
        }
        return set_field($table, 'configdata', base64_encode(serialize($this->config)), 'id', $this->instance->id);
    }
}

?>
