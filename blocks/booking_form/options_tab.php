</form>
<?php
// Part of block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, 2008.

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/ajax/ajaxlib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once $CFG->libdir.'/formslib.php';

require_once('options_form.php');

if(isset($this->config->eventid)) {
	$optioncount = count_records('block_booking_form_choice', 'block_booking_formid', $this->config->eventid);
	if($optioncount>0) {
		$options =  get_records('block_booking_form_choice', 'block_booking_formid', $this->config->eventid);
	}

    $canedit = (count_records('block_booking_form_bookings', 'block_booking_formid', $this->config->eventid)==0)?true:false;
	$mform = new options_form("$CFG->wwwroot/blocks/booking_form/action.php", $optioncount, $edit, $canedit);

	$toForm['eventid'] = $this->config->eventid;
	if($optioncount>0){
		$n=1;
	    foreach($options as $o) {
			$toForm['optiontitle'.$n] = $o->title;
	        $toForm['optionid'.$n] = $o->id;
            if($edit == $n) {
            	$toForm['newwarning'] = $o->warning;
                $toForm['isassert'] = !$o->optional;
            }
	        $n++;
	    }
	}

	$mform->set_data($toForm);
	$mform->display();
}
else {
	echo get_string('setupblockfirst', 'block_booking_form');
}

?>
