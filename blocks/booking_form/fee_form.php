<?php
// Part of block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, 2008.

class fee_form extends moodleform {
	var $feecount;
    var $editable;

    function fee_form($target, $feecount, $editable=true){
    	$this->feecount = $feecount;
        $this->moodleform($target);
        $this->editable = $editable;
    }

    function definition (){
        global $CFG, $USER;
        global $userfields;
		$cid = required_param('id', PARAM_INTEGER);
		$instanceid = required_param('instanceid', PARAM_INTEGER);

        $mform =& $this->_form;
        $mform->addElement('hidden','id',$cid);
        $mform->addElement('hidden','eventid');

        $mform->addElement('hidden','blockaction','config');
        $mform->addElement('hidden','action','fee');
        $mform->addElement('hidden','instanceid',$instanceid);
        $mform->addElement('hidden','sesskey',$USER->sesskey);
        $mform->addElement('hidden','feecount',$this->feecount);

        if($this->feecount>0) {
        	$mform->addElement('header', 'oa', get_string('feeoptions', 'block_booking_form'));
            for($n=1; $n <= $this->feecount; $n++) {
	        	//$mform->addElement('static','title'.$n, get_string('feetitle', 'block_booking_form').$n);
	        	$mform->addElement('hidden','feeid'.$n);
                $foa = array();
		       	$foa[] =& $mform->createElement('text','title'.$n, get_string('text', 'block_booking_form'), array('size'=>'40'));
                if($this->editable) {
			       	$foa[] =& $mform->createElement('text','value'.$n, get_string('value', 'block_booking_form'), array('size'=>'8'));
                }
                else {
			       	$foa[] =& $mform->createElement('static','value'.$n, get_string('value', 'block_booking_form'));
                }
                $mform->addGroup($foa, 'foa'.$n, get_string('feetitle', 'block_booking_form'), ' : ', false);
 	        }
        }
        $mform->addElement('header','new',"Add a fee option");
       	$mform->addElement('text','title',get_string('text', 'block_booking_form'), array('size'=>'40'));
       	$mform->addElement('text','value',get_string('value', 'block_booking_form'), array('size'=>'8'));

        $this->add_action_buttons(false, get_string('update'));
    }
}


?>
