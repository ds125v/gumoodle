</form>
<?php
// Part of block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, 2008.

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/ajax/ajaxlib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once $CFG->libdir.'/formslib.php';

require_once('workshops_form.php');

if(isset($this->config->eventid)) {
	$wrkshopcount = count_records('block_booking_form_workshops', 'block_booking_formid', $this->config->eventid);
    if($wrkshopcount > 0){
		$wrkshops = get_records('block_booking_form_workshops', 'block_booking_formid', $this->config->eventid);
    }
    $canedit = (count_records('block_booking_form_bookings', 'block_booking_formid', $this->config->eventid)==0)?true:false;

	$mform = new workshops_form("$CFG->wwwroot/blocks/booking_form/action.php", $wrkshopcount, $edit, $canedit);

	//$toForm['workshopcount'] = $wrkshopcount;
	$toForm['eventid'] = $this->config->eventid;
	if($wrkshopcount > 0) {
		$n=1;
		foreach($wrkshops as $ws) {
		    $toForm['wst'.$n] = $ws->title;
	        if($ws->maxbookings > 0) {
			    $toForm['wslim'.$n] = $ws->maxbookings;
	        }
            elseif($edit != $n) {
            	$toForm['wslim'.$n] = get_string('unlimited','block_booking_form');
            }
            else {
            	$toForm['wslim'.$n] = '';
            }
		    $toForm['wsid'.$n] = $ws->id;
		    $n++;
		}
	}

	$mform->set_data($toForm);
	$mform->display();
}
else {
	echo get_string('setupblockfirst', 'block_booking_form');
}
//echo "<pre>"; print_r($wrkshops); echo "</pre>";
//echo "<pre>"; print_r($this->config); echo "</pre>";

?>
