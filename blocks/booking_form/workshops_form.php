<?php
// Part of block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, 2008.

class workshops_form extends moodleform {
    var $editoption;
    var $canedit;
	var $wscount;

    function workshops_form($target, $wscount, $editoption=0, $canedit=true){
    	$this->wscount = $wscount;
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
        $mform->addElement('hidden','action','workshops');
        $mform->addElement('hidden','instanceid',$instanceid);
        $mform->addElement('hidden','sesskey',$USER->sesskey);
        $mform->addElement('hidden','workshopcount',$this->wscount);

        if($this->wscount) {
	        $mform->addElement('header','cur',"Workshops");
	        for($n=1; $n<=$this->wscount; $n++) {
	        	if($n==$this->editoption) {
		        	$mform->addElement('text','wst'.$n,"Workshop title $n", array('size'=>'50'));
		        	$mform->addElement('text','wslim'.$n,"Number of places", array('size'=>'3'));
	       			$mform->addElement('hidden','editopt',$this->editoption);
        			$mform->addElement('submit', 'submitbutton', get_string('update'));
                }
                else {
		        	$mform->addElement('static','wst'.$n,"Workshop title $n");
		        	$mform->addElement('static','wslim'.$n,"Number of places");
                    if(($this->editoption==0)&&($this->canedit==true)) {
                   		$mform->addElement('submit', 'edit'.$n, get_string('edit'));
                    }
                }
	        	$mform->addElement('hidden','wsid'.$n);
	        }
        }
        if($this->editoption == 0) {
	        $mform->addElement('header','new',"Add a workshop");
	       	$mform->addElement('text','newwst',"Workshop title", array('size'=>'50'));
	       	$mform->addElement('text','newwslim',"Number of places", array('size'=>'3'));
	        $this->add_action_buttons(false, get_string('update'));
        }
    }
}


?>
