<?php
// Part of block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, 2008.

class options_form extends moodleform {
	var $optionscount;
    var $editoption;
    var $canedit;

    function options_form($target, $optioncount, $editoption=0, $canedit = true){
    	$this->optioncount = $optioncount;
    	$this->editoption = $editoption;
        $this->canedit = $canedit;
        $this->moodleform($target);
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
        $mform->addElement('hidden','action','options');
        $mform->addElement('hidden','instanceid',$instanceid);
        $mform->addElement('hidden','sesskey',$USER->sesskey);
        $mform->addElement('hidden','optioncount',$this->optioncount);

        if($this->optioncount>0) {
        	$mform->addElement('header', 'oa', get_string('optionsandassertions', 'block_booking_form'));
            for($n=1; $n <= $this->optioncount; $n++) {
            	if($n==$this->editoption) {
	       			$mform->addElement('htmleditor','optiontitle'.$n ,get_string('text', 'block_booking_form'));
	       			$mform->addElement('selectyesno','isassert',get_string('required'));
			       	$mform->addElement('text','newwarning',get_string('warning', 'block_booking_form'), array('size'=>'40'));
	       			$mform->addElement('hidden','editopt',$this->editoption);
        			$mform->addElement('submit', 'submitbutton', get_string('update'));
                }
                else {
	        		$mform->addElement('static','optiontitle'.$n, get_string('text', 'block_booking_form').' '.$n);
                    if(($this->editoption==0)&&($this->canedit==true)) {
                   		$mform->addElement('submit', 'edit'.$n, get_string('edit'));
                    }
                }
	        	$mform->addElement('hidden','optionid'.$n);
 	        }
        }
        if($this->editoption == 0) {
	        $mform->addElement('header','new',"Add a option or assertion");
	       	$mform->addElement('htmleditor','newtext',get_string('text', 'block_booking_form'));
	       	$mform->addElement('selectyesno','isassert',get_string('required'));
	       	$mform->addElement('text','newwarning',get_string('warning', 'block_booking_form'), array('size'=>'40'));
        	$this->add_action_buttons(false, get_string('update'));
        }

    }
}


?>
