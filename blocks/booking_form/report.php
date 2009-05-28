<?php
// Part of block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, 2008.

    require_once('../../config.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->libdir.'/ajax/ajaxlib.php');
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once $CFG->libdir.'/formslib.php';

    $eventid = required_param('eventid', PARAM_INT);
    $reporttype = optional_param('type', 'dlist', PARAM_ALPHA);


    $id = required_param('id', PARAM_INT);
    if (! ($course = get_record('course', 'id', $id)) ) {
        //error('Invalid course id');
        $course = $COURSE;
    }
    else $COURSE = $course;

    require_login($course->id);

    if (!$context = get_context_instance(CONTEXT_COURSE, $course->id)) {
        print_error('nocontext');
    }
	if(!has_capability('moodle/course:manageactivities', $context)) {
    	exit;
	}

    $navlinks = array();
    // Course name, if appropriate.
    $eventinfo = get_record('block_booking_form', 'id', $eventid);
    $navlinks[] = array(
	        'name' => $eventinfo->eventtitle,
	        'link' => '',
	        'type' => 'title');

    $navigation = build_navigation($navlinks);
//    print_header_simple('', '', $navigation, 'title', '', true, '', navmenu($course));
    echo '<h2>'.$eventinfo->eventtitle.'</h2>';
    echo '<p>'.get_string('returnto','block_booking_form').' <a href="'.$CFG->wwwroot . '/course/view.php?id='. $COURSE->id .'">'.$COURSE->shortname.'</a></p>';

    echo '<p><a href="report.php?eventid='.$eventid.'&id='.$id.'&type=dlist">'.get_string('delegatelist','block_booking_form').'</a> - ';
    echo '<a href="report.php?eventid='.$eventid.'&id='.$id.'&type=signin">'.get_string('signinsheet','block_booking_form').'</a> - ';
    echo '<a href="report.php?eventid='.$eventid.'&id='.$id.'&type=badge">'.get_string('badgeinfo','block_booking_form').'</a> - ';
    echo '<a href="report.php?eventid='.$eventid.'&id='.$id.'&type=wshops">'.get_string('workshopchoices','block_booking_form').'</a> - ';
    echo '<a href="report.php?eventid='.$eventid.'&id='.$id.'&type=invoice">'.get_string('invoicelist','block_booking_form').'</a> - ';
    echo '<a href="report.php?eventid='.$eventid.'&id='.$id.'&type=special">'.get_string('specialrequirements2','block_booking_form').'</a> - ';
    echo '<a href="report.php?eventid='.$eventid.'&id='.$id.'&type=otherevents">'.get_string('otherevents','block_booking_form').'</a></p>';
    //echo '<a href="report.php?eventid='.$eventid.'&id='.$id.'&type=csv">'.get_string('csv','block_booking_form').'</a></p>';

    // Check if a booking is to be canceled, and if so do it
	$action = optional_param('action', '', PARAM_ALPHA);
    $bookingid = optional_param('booking', 0, PARAM_INT);
    if(($action=='cancel')&&($bookingid>0)) {
		$action = optional_param('action', '', PARAM_ALPHA);
    	if(delete_records('block_booking_form_bookings', 'id', $bookingid)) {
        	delete_records('block_booking_form_wsres', 'block_booking_form_bookingsid', $bookingid);
        	delete_records('block_booking_form_choicemade', 'block_booking_form_bookingsid', $bookingid);
            echo '<p>'.get_string('canceledbookingnum','block_booking_form',$bookingid).'</p>';
        }
    }


    $bookings = get_records('block_booking_form_bookings', 'block_booking_formid', $eventid, 'surname, forename');
    if(($bookings !== false)||($reporttype=='otherevents')) {
    	switch($reporttype) {
        	case 'dlist':
            	echo '<p>'.get_string('numberofdelegates', 'block_booking_form').' : '.sizeof($bookings).'</p>';
                printdelegatelist($bookings);
                break;
            case 'signin':
            	printsigninsheet($bookings);
            	break;
            case 'badge':
            	printbadges($bookings);
             	break;
            case 'wshops':
            	printworkshops($eventid);
             	break;
            case 'invoice':
	            printinvoicelist($eventid, $bookings);
             	break;
            case 'csv':
             	break;
            case 'special':
	            printspecialrequirements($eventid, $bookings);
             	break;
            case 'otherevents':
	            listOtherEvents();
             	break;
            default:
            	echo get_string('notimplemented', 'block_booking_form');
                break;
        }
    }
    else {
    	echo get_string('nobookingsmade', 'block_booking_form');
    }
//    echo "<pre>"; print_r($bookings); echo "</pre>";

//print_footer(NULL, $course);

function printdelegatelist($bookings) {
	global $eventid, $id;
   	echo '<table cellspacing="5">';
    foreach($bookings as $b) {
    	echo '<tr><td>'.$b->forename . ' ' . $b->surname . ' </td>';
           echo '<td>';
           if(strlen($b->department)) {
           	   echo $b->department . ', ';
           }
           echo $b->organization . ' </td>';
           echo '<td>' . $b->email . '</td>';
           echo '<td><a href="report.php?eventid='.$eventid.'&id='.$id.'&type=dlist&action=cancel&booking='.$b->id.'">Cancel?</a></td></tr>';
    }
   	echo '</table>';
}

function printsigninsheet($bookings) {
	echo '<table>';
    foreach($bookings as $b) {
    	echo '<tr><td><br/>'.$b->surname . ', ' . $b->forename . '</td><td><br/>___________________________________</td></tr>';
    }
	echo '</table>';
}

function printbadges($bookings) {
    foreach($bookings as $b) {
    	echo '<h2>'.$b->forename . ' ' . $b->surname . ' </h2>';
        if(strlen($b->department)) {
        		echo $b->department . '<br/>';
        }
        echo $b->organization . '<br/><p/><p/>';
    }
}

function printworkshops($eventid) {
    $workshops = get_records('block_booking_form_workshops', 'block_booking_formid', $eventid);
	if($workshops) {
	    foreach($workshops as $w) {
	    	echo '<h2>'.$w->title.'</h2>';
	        $selections = get_records('block_booking_form_wsres', 'block_booking_form_workshopsid', $w->id);
	        $wslist = array();
	        if($selections !== false){
		        foreach($selections as $s) {
		        	$booking = get_record('block_booking_form_bookings', 'id', $s->block_booking_form_bookingsid);
	                if($booking) {
		                $wslist[] = $booking->surname . ', ' . $booking->forename;
	                }
		        }
		        sort($wslist);
		        foreach($wslist as $l) {
		        	echo $l . '<br/>';
		        }
	        }
	    }
	}
}

function printinvoicelist($eventid, $bookings) {
	$feeopts = get_records('block_booking_form_feeopt', 'block_booking_formid', $eventid);
	if($feeopts) {
	    foreach($feeopts as $o) {
	    	echo '<h2>'.$o->title.' ('.$o->value.')</h2>';
	    	foreach($bookings as $b) {
				if($b->block_booking_form_feeoptid == $o->id) {
	             	echo '<pre>'.$b->title.' '.$b->forename.' '.$b->surname.'<br/>';
	                if(strlen(trim($b->department))){
	             		echo $b->department.'<br/>';
	                }
	                if(strlen(trim($b->organization))){
	             		echo $b->organization.'<br/>';
	                }
	             	echo $b->address.'</pre>';
	            }
	        }
	    }
	}
}

function printspecialrequirements($eventid, $bookings) {
	echo '<h2>'.get_string('dietary', 'block_booking_form').'</h2>';
	foreach($bookings as $b) {
    	if($b->dietary_req != '') {
        	echo $b->title .' '.$b->forename.' '.$b->surname.' : '.$b->dietary_req.'<br/>';
        }
	}
	echo '<h2>'.get_string('hearing', 'block_booking_form').'</h2>';
	foreach($bookings as $b) {
    	if($b->hearing_req != '') {
        	echo $b->title .' '.$b->forename.' '.$b->surname.' : '.$b->hearing_req.'<br/>';
        }
	}
	echo '<h2>'.get_string('mobility', 'block_booking_form').'</h2>';
	foreach($bookings as $b) {
    	if($b->mobility_req != '') {
        	echo $b->title .' '.$b->forename.' '.$b->surname.' : '.$b->mobility_req.'<br/>';
        }
	}
	echo '<h2>'.get_string('other', 'block_booking_form').'</h2>';
	foreach($bookings as $b) {
    	if($b->other_req != '') {
        	echo $b->title .' '.$b->forename.' '.$b->surname.' : '.$b->other_req.'<br/>';
        }
	}

}

function listOtherEvents() {
global $COURSE;
	echo '<h2>Other Events (in this course)</h2>';
    $forms = get_records('block_booking_form', 'courseid', $COURSE->id);
    foreach($forms as $f) {
    	echo '<p><b><a href="report.php?id='.$COURSE->id.'&eventid='.$f->id.'">'.$f->eventtitle.'</a></b><br/>';
    	echo $f->description.'</p>';
    }
}


?>
