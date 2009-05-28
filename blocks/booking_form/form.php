<?php
// Part of block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, 2008.
    require_once('../../config.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->libdir.'/ajax/ajaxlib.php');
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once $CFG->libdir.'/formslib.php';

    $eventid = required_param('eventid', PARAM_INT);
    $cid = required_param('id', PARAM_INT);
    require_login($cid);

    class block_booking_form_form extends moodleform {
	    function block_booking_form_form($workshops=false, $feeoptions=false, $options=false){
	    	$this->workshops = $workshops;
	    	$this->feeoptions = $feeoptions;
	    	$this->options = $options;
	        $this->moodleform();
	    }

        function definition (){
            global $CFG, $USER, $COURSE;
            global $userfields;

            $mform =& $this->_form;

            $mform->addElement('header', 'yourdetails', get_string('blockname', 'block_booking_form'));
            $mform->addElement('hidden', 'eventid');
            $mform->addElement('hidden', 'notme', '0');
            $mform->addElement('hidden', 'id', $COURSE->id);
            $mform->addElement('text', 'title', get_string('title', 'block_booking_form'), 'size="5"');
            $mform->addElement('text', 'forename', get_string('forename', 'block_booking_form'), 'size="20"');
            $mform->addElement('text', 'surname', get_string('surname', 'block_booking_form'), 'size="20"');
            $mform->addElement('text', 'organization', get_string('organization', 'block_booking_form'), 'size="45"');
            $mform->addElement('text', 'department', get_string('department', 'block_booking_form'), 'size="45"');
            $mform->addElement('textarea', 'address', get_string('address', 'block_booking_form'), 'cols="40" rows="5"');
            $mform->addElement('text', 'telephone', get_string('phone', 'block_booking_form'), 'size="35"');
            $mform->addElement('text', 'email', get_string('email', 'block_booking_form'), 'size="45"');
            if($this->feeoptions != false) {
            	$mform->addElement('header', 'feeoptions', get_string('feeoptions', 'block_booking_form'));
                foreach($this->feeoptions as $fo) {
                	$mform->addElement('radio', 'block_booking_form_feeoptid', $fo->title, '(' . $fo->value .')', $fo->id);
                }
            }
            if($this->workshops != false) {
            	$mform->addElement('header', 'workshops', get_string('reservesessions', 'block_booking_form'));
                foreach($this->workshops as $w) {
                	if($w->availableplaces > 0) {
	            		$mform->addElement('checkbox', 'workshop_'.$w->id, $w->title);
                    }
                    else {
	            		$mform->addElement('checkbox', 'workshop_'.$w->id, $w->title. ' '. get_string('fullybooked', 'block_booking_form'), array('disabled'=>'1'));
                    }
            	}
            }
            $mform->addElement('header', 'specialrequirements', get_string('specialrequirements', 'block_booking_form'));
            $mform->addElement('text', 'dietary_req', get_string('dietary', 'block_booking_form'), 'size="40" length="40"');
            $mform->addElement('text', 'hearing_req', get_string('hearing', 'block_booking_form'), 'size="40" length="40"');
            $mform->addElement('text', 'mobility_req', get_string('mobility', 'block_booking_form'), 'size="40" length="40"');
            $mform->addElement('text', 'other_req', get_string('other', 'block_booking_form'), 'size="40" length="40"');
            if($this->options != false) {
            	$mform->addElement('header', 'permissionsandoptions', get_string('permissionsandoptions', 'block_booking_form'));
                foreach($this->options as $o) {
                	$mform->addElement('checkbox', 'option'.$o->id, '', $o->title);
                }
            }

            //$mform->addRule('forename', get_string('err_required','form'), 'lettersonly', null, 'client');
            $mform->addRule('forename', get_string('err_required','form'), 'required', null, 'client');
            //$mform->addRule('surname', get_string('err_required','form'), 'lettersonly', null, 'client');
            $mform->addRule('surname', get_string('err_required','form'), 'required', null, 'client');
            $mform->addRule('email', get_string('err_email','form'), 'email', null, 'client');
            $mform->addRule('email', get_string('err_required','form'), 'required', null, 'client');
            //$mform->addRule('address', get_string('err_required','form'), 'required', null, 'client');
            $mform->addRule('telephone', get_string('err_required','form'), 'required', null, 'client');
            if($this->feeoptions != false) {
	            $mform->addRule('block_booking_form_feeoptid', get_string('feechoicerequired', 'block_booking_form'), 'required', null, 'client');
            }
            if($this->options != false) {
                foreach($this->options as $o) {
                	if(!$o->optional) {
                		$mform->addRule('option'.$o->id, $o->warning, 'required', null, 'client');
                	}
                }
            }

            //echo "<pre>"; print_r($mform->getRegisteredRules()); echo "</pre>";

            $this->add_action_buttons(false, get_string('submitbooking', 'block_booking_form'));
        }
    }

    $id = optional_param('id', 0, PARAM_INT);
    if (! ($course = get_record('course', 'id', $id)) ) {
        //error('Invalid course id');
        $course = $COURSE;
    }
    else $COURSE = $course;

    if (!$context = get_context_instance(CONTEXT_COURSE, $course->id)) {
        print_error('nocontext');
    }

	$notme = optional_param('notme', 0, PARAM_INT);
    if(!has_capability('moodle/course:manageactivities', $context)) {
    	$notme = 0; // only teachers/admins can book for someone else
    }
    if(isguest()) { // But guest have to be someone else...
    	$notme = 1;
    }


    require_login($course->id);

    $navlinks = array();
    // Course name, if appropriate.
    $eventinfo = get_record('block_booking_form', 'id', $eventid);
    $bookingcount = count_records('block_booking_form_bookings', 'block_booking_formid', $eventid);

	$wrkshops = get_records('block_booking_form_workshops', 'block_booking_formid', $eventid);
	if($wrkshops != false) {
        foreach($wrkshops as $w) {
        	if($w->maxbookings != 0) {
            	$curbookings = count_records('block_booking_form_wsres', 'block_booking_form_workshopsid', $w->id);
                $w->availableplaces = $w->maxbookings - $curbookings;
            }
            else {
                $w->availableplaces = 1; // actually unlimited but any +ve int will do.
            }
    	}
    }
	$feeopts = get_records('block_booking_form_feeopt', 'block_booking_formid', $eventid);
	$options = get_records('block_booking_form_choice', 'block_booking_formid', $eventid);

    if(!$notme) {
    	$prevbkinfo = get_record('block_booking_form_bookings', 'userid', $USER->id, 'block_booking_formid', $eventid);
    }
    else
    	$prevbkinfo = false;

    $newbookingmade = false;

    $mform = new block_booking_form_form($wrkshops, $feeopts, $options);
    if ($formdata = $mform->get_data()) {
    //Process
    	$bookinginfo = $formdata;
        $bookinginfo->timemodified = time();
        $bookinginfo->block_booking_formid = $eventid;
        if(!$notme) {
	        $bookinginfo->userid = $USER->id;
        }
        else {
	        $bookinginfo->userid = 0;
        }
	    //echo "<pre>"; print_r($bookinginfo); echo "</pre>";
        if($prevbkinfo===false) {
	        $bookingid = insert_record('block_booking_form_bookings', $bookinginfo);
    		$prevbkinfo = get_record('block_booking_form_bookings', 'id', $bookingid, 'block_booking_formid', $eventid);
            //# now need to add workshops and options
			if($wrkshops != false) {
		        foreach($wrkshops as $w) {
                	$wsfield = 'workshop_'.$w->id;
		            if(isset($formdata->$wsfield)) {
	                    $wsres = new Object();
	                    $wsres->timemodified = time();
	                    $wsres->block_booking_form_workshopsid = $w->id;
	                    $wsres->block_booking_form_bookingsid = $bookingid;
                    	insert_record('block_booking_form_wsres', $wsres);
                    }
                }
            }
			if($options != false) {
		        foreach($options as $o) {
                	$wsfield = 'option'.$o->id;
		            if(isset($formdata->$wsfield)) {
	                    $ores = new Object();
	                    $ores->timemodified = time();
	                    $ores->block_booking_form_choiceid = $o->id;
	                    $ores->block_booking_form_bookingsid = $bookingid;
                    	insert_record('block_booking_form_choicemade', $ores);
                    }
                }
            }
            // Send confirmation e-mail
            if(!$notme)
            {
            	$emailtitle = get_string('bookingconfirmation', 'block_booking_form'). ' : ' .$eventinfo->eventtitle;
                $messagetext = get_string('dear', 'block_booking_form').' '.$bookinginfo->forename.",\n\n";
                $messagetext = get_string('placereserved', 'block_booking_form');
                $emailsent = email_to_user($USER, get_admin(), $emailtitle, $messagetext);
            }

            $newbookingmade = true;
        }
    }
    elseif(!$notme) {
    	$prefill = array();
        $prefill['forename'] = $USER->firstname;
        $prefill['surname'] = $USER->lastname;
        $prefill['email'] = $USER->email;
        $prefill['telephone'] = $USER->phone1;
        $prefill['organization'] = $USER->institution;
        $prefill['department'] = $USER->department;
        $prefill['address'] = $USER->address;
        $prefill['eventid'] = $eventid;
    	$mform->set_data($prefill);
    }
    else {
    	$prefill = array();
        $prefill['notme'] = $eventid;
        $prefill['eventid'] = $eventid;
    	$mform->set_data($prefill);
    }

    if(($eventinfo->maxbookings > 0)&&($eventinfo->maxbookings <= $bookingcount)) {
    	$eventfull = true;
    } else {
    	$eventfull = false;
    }
    $navlinks[] = array(
	        'name' => $eventinfo->eventtitle,
	        'link' => '',
	        'type' => 'title');

    $navigation = build_navigation($navlinks);
    print_header_simple('', '', $navigation, 'title', '', true,
    					'', navmenu($course));
    echo '<h2>'.$eventinfo->eventtitle.'</h2>';
    if($newbookingmade) {
        if(strlen($eventinfo->confirmation)){
	    	echo '<p>'.$eventinfo->confirmation.'</p>';
        }
    }
    else {
	    echo $eventinfo->description;
    }

    if((!empty($prevbkinfo))&&(optional_param('clear', 0, PARAM_INT)==1)) {
    	$booking = get_record('block_booking_form_bookings', 'userid', $USER->id, 'block_booking_formid', $eventid);
    	if(delete_records('block_booking_form_bookings', 'userid', $USER->id, 'block_booking_formid', $eventid)) {
        	delete_records('block_booking_form_wsres', 'block_booking_form_bookingsid', $booking->id);
        	delete_records('block_booking_form_choicemade', 'block_booking_form_bookingsid', $booking->id);
    		$prevbkinfo = false;
        }
    }

    if($newbookingmade) {
    	echo '<p>'.get_string('thankyou', 'block_booking_form').'</p>';
        echo '<a href="'.$CFG->wwwroot . '/course/view.php?id='.$COURSE->id.'">'.get_string('returnto', 'block_booking_form', format_string($COURSE->fullname,true)).'</a>';
        if(($notme==1)&&(!isguest())) {
        	echo '<p><a href="'.$CFG->wwwroot . '/blocks/booking_form/form.php?id=' . $course->id.'&eventid='.$eventid.'&notme=1">'.get_string('addanotherbooking','block_booking_form').'</a></p>';
        }
    }
    elseif((($prevbkinfo===false)||($notme==1))&&(!$eventfull)) {
		$mform->display();
    }
    elseif(!empty($prevbkinfo)) {
    	echo '<p>'.get_string('alreadybooked', 'block_booking_form').'</p>';
        echo "<p><a href='?clear=1&id=$id&eventid=$eventid'>".get_string('cancelmybooking','block_booking_form')."</a></p>";
        echo '<a href="'.$CFG->wwwroot . '/course/view.php?id='.$COURSE->id.'">'.get_string('returnto', 'block_booking_form', format_string($COURSE->fullname,true)).'</a>';
    }
    else {
    	echo '<p>'.get_string('maxplacesbooked', 'block_booking_form').'</p>';
    }
    //echo "<pre>"; print_r($COURSE); echo "</pre>";
    print_footer(NULL, $course);

?>
