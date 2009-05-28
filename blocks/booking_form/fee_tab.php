</form>
<?php
// Part of block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, 2008.

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/ajax/ajaxlib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once $CFG->libdir.'/formslib.php';

require_once('fee_form.php');

if(isset($this->config->eventid)) {
	$feecount = count_records('block_booking_form_feeopt', 'block_booking_formid', $this->config->eventid);
	$feeopts = get_records('block_booking_form_feeopt', 'block_booking_formid', $this->config->eventid);

    $bookingcount = count_records('block_booking_form_bookings', 'block_booking_formid', $this->config->eventid);

	$mform = new fee_form("$CFG->wwwroot/blocks/booking_form/action.php", $feecount, !$bookingcount);

	$toForm['eventid'] = $this->config->eventid;
	if($feecount>0){
		$n=1;
	    foreach($feeopts as $o) {
			$toForm['title'.$n] = $o->title;
	        $toForm['feeid'.$n] = $o->id;
		    //$toForm['title'.$n] .= ' ('.$o->value.')';
		    $toForm['value'.$n] = $o->value;
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
