<?php
	// Niall S F Barr, Sept-Oct 2008
	// contains the code that controls extra features for booking forms

require_once('../../config.php');
require_once $CFG->libdir.'/formslib.php';

	$cid = required_param('id', PARAM_INTEGER);
	$instanceid = required_param('instanceid', PARAM_INTEGER);
    $sesskey = required_param('sesskey', PARAM_TEXT);
    $action = required_param('action', PARAM_TEXT);

	$edit = null;
    $editopt = optional_param('editopt', 0, PARAM_INT);
    $canedit = (count_records('block_booking_form_bookings', 'block_booking_formid', $instanceid)==0)?true:false;

    require_login($cid);

    if($action=='workshops') {
		$displaycount = required_param('workshopcount', PARAM_INT);
    	require_once('workshops_form.php');
		$mform = new workshops_form('', $displaycount, $editopt);
		if ($formdata = $mform->get_data()) {
			if(isset($formdata->newwst)&&(trim($formdata->newwst))!='') {
            	$newws = new Object();
                $newws->block_booking_formid = $formdata->eventid;
                $newws->title = trim($formdata->newwst);
                if($formdata->newwslim!='') {
	                $newws->maxbookings = intval($formdata->newwslim);
                }
                else {
	                $newws->maxbookings = 0;
                }
                insert_record('block_booking_form_workshops', $newws);
            }
            elseif((isset($formdata->editopt))&& $canedit) { // check alowed, i.e. no bookings
            	$formarray = get_object_vars($formdata);
                $workshop = get_record('block_booking_form_workshops', 'id', $formarray['wsid'.$formdata->editopt]);
                $workshop->title = $formarray['wst'.$formdata->editopt];
                $workshop->maxbookings = $formarray['wslim'.$formdata->editopt];
                if(strlen(trim($workshop->title))>0) {
	                update_record('block_booking_form_workshops', $workshop);
                }
                else {
	                delete_records('block_booking_form_workshops', 'id', $workshop->id);
                }
                $edit = null;
            }
            elseif($formdata->workshopcount > 0) { // might be an edit request
            	$formarray = get_object_vars($formdata);
                $n = 0;
                while((!$edit)&&($n < $formdata->workshopcount)) {
                	$n++;
                    if(isset($formarray['edit'.$n])) {
                    	$edit = $n;
                    }
                }

            }
		}
    }

    if($action=='options') {
		$displaycount = required_param('optioncount', PARAM_INT);
    	require_once('options_form.php');
		$mform = new options_form('', $displaycount, $editopt);


		if ($formdata = $mform->get_data()) {
			if(isset($formdata->newtext)&&(trim($formdata->newtext)!='')) {
            	$newoption = new Object();
                $newoption->title = $formdata->newtext;
                $newoption->warning = $formdata->newwarning;
                $newoption->optional = !$formdata->isassert;
                $newoption->block_booking_formid = $formdata->eventid;
                $newoption->timemodified = time();
                insert_record('block_booking_form_choice', $newoption);
            }
            elseif((isset($formdata->editopt))&& $canedit) { // check alowed, i.e. no bookings
            	$formarray = get_object_vars($formdata);
                $option = get_record('block_booking_form_choice', 'id', $formarray['optionid'.$formdata->editopt]);
                $option->title = $formarray['optiontitle'.$formdata->editopt];
                $option->warning = $formdata->newwarning;
                $option->optional = !$formdata->isassert;
                if(strlen(trim($option->title))>0) {
	                update_record('block_booking_form_choice', $option);
                }
                else {
	                delete_records('block_booking_form_choice', 'id', $option->id);
                }
                $edit = null;
            }
            elseif($formdata->optioncount > 0) { // might be an edit request
            	$formarray = get_object_vars($formdata);
                $n = 0;
                while((!$edit)&&($n < $formdata->optioncount)) {
                	$n++;
                    if(isset($formarray['edit'.$n])) {
                    	$edit = $n;
                    }
                }

            }
		}
    }

    if($action=='fee') {
		$displaycount = required_param('feecount', PARAM_INT);
    	require_once('fee_form.php');
		$mform = new fee_form('', $displaycount);
		if ($formdata = $mform->get_data()) {
			if(trim($formdata->title)!='') {
            	$newfeeoption = new Object();
                $newfeeoption->title = $formdata->title;
                $newfeeoption->value = $formdata->value;
                $newfeeoption->block_booking_formid = $formdata->eventid;
                $newfeeoption->timemodified = time();
                insert_record('block_booking_form_feeopt', $newfeeoption);
            }
            if($formdata->feecount > 0) {
            	$formarray = get_object_vars($formdata);
            	for($n=1; $n<=$formdata->feecount; $n++) {
                    $name = 'optid'.$n;
                	$feeoption = get_record('block_booking_form_feeopt', 'id', $formarray['feeid'.$n]);
                    $feeoption->title = trim($formarray['title'.$n]);
                    $feeoption->value = trim($formarray['value'.$n]);
                    if(($feeoption->title == '')&&($feeoption->value == '')) {
                    	delete_records('block_booking_form_feeopt', 'id', $formarray['feeid'.$n]);
                    }
                    else {
                    	update_record('block_booking_form_feeopt', $feeoption);
                    }
                 }
            }
		}
    }

  	$url = "$CFG->wwwroot/course/view.php?id=$cid";
    $url .= "&amp;instanceid=$instanceid&amp;sesskey=$sesskey&amp;blockaction=config&amp;action=$action";
    if($edit) {
    	$url .= '&edit='.$edit;
    }
   	redirect($url);


?>
