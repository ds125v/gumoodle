<?php
/**
 * @package   turnitintool
 * @copyright 2010 nLearning Ltd
 */
require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/turnitintool/lib.php');

class mod_turnitintool_mod_form extends moodleform_mod {

    function definition() {

        global $CFG, $DB, $COURSE, $USER;
        $mform    =& $this->_form;
        
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('turnitintoolname', 'turnitintool'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $input->length=40;
        $input->field=get_string('turnitintoolname','turnitintool');
        $mform->addRule('name', get_string('maxlength','turnitintool',$input), 'maxlength', 40, 'client');
        $mform->addRule('name', get_string('maxlength','turnitintool',$input), 'maxlength', 40, 'server');
        
        if (is_callable(array($this,'add_intro_editor'))) {
            $this->add_intro_editor(true, get_string('turnitintoolintro', 'turnitintool'));
        } else {
            $mform->addElement('htmleditor', 'intro', get_string('turnitintoolintro', 'turnitintool'));
            $mform->setType('intro', PARAM_RAW);
            $mform->addRule('intro', get_string('required'), 'required', null, 'client');
            $input->length=1000;
            $input->field=get_string('turnitintoolintro','turnitintool');
            $mform->addRule('intro', get_string('maxlength','turnitintool',$input), 'maxlength', 1000, 'client');
            $mform->addRule('intro', get_string('maxlength','turnitintool',$input), 'maxlength', 1000, 'server');
        }
        
        $typeoptions = turnitintool_filetype_array();

        $mform->addElement('select', 'type', get_string('type', 'turnitintool'), $typeoptions);
        $mform->setHelpButton('type', array('types', get_string('type', 'turnitintool'), 'turnitintool'));
        $mform->addRule('type', get_string('required'), 'required', null, 'client');
        
        $options = array();
        for($i = 1; $i <= 5; $i++) {
            $options[$i] = $i;
        }
        
        $mform->addElement('select', 'numparts', get_string('numberofparts', 'turnitintool'), $options);
        $mform->setHelpButton('numparts', array('numberofparts', get_string('numberofparts', 'turnitintool'), 'turnitintool'));
        
        $suboptions = array( 0 => get_string('namedparts','turnitintool'), 1 => get_string('portfolio','turnitintool'));
        
        $mform->addElement('hidden','portfolio',0);
        
        $maxtii=20971520;
        if ($CFG->maxbytes>$maxtii) {
            $maxbytes1=$maxtii;
        } else {
            $maxbytes1=$CFG->maxbytes;
        }
        if ($COURSE->maxbytes>$maxtii) {
            $maxbytes2=$maxtii;
        } else {
            $maxbytes2=$COURSE->maxbytes;
        }
        
        $options=get_max_upload_sizes($maxbytes1, $maxbytes2);
        
        $mform->addElement('select', 'maxfilesize', get_string('maxfilesize', 'turnitintool'), $options);
        $mform->setHelpButton('maxfilesize', array('maxfilesize', get_string('maxfilesize', 'turnitintool'), 'turnitintool'));
        
        unset($options);
        for ($i=0;$i<=100;$i++) {
            $options[$i]=$i;
        }
        $mform->addElement('select', 'grade', get_string('overallgrade', 'turnitintool'), $options);
        $mform->setHelpButton('grade', array('overallgrade', get_string('overallgrade', 'turnitintool'), 'turnitintool'));
        $mform->setDefault('grade', 100);

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        
        $mform->addElement('hidden','defaultdtstart',time());
        $mform->addElement('hidden','defaultdtdue',strtotime('+7 days'));
        $mform->addElement('hidden','defaultdtpost',strtotime('+7 days'));
        
        if (isset($this->_cm->id)) {
            $turnitintool=turnitintool_get_record("turnitintool", "id", $this->_cm->instance);
            $updating=true;
        } else {
            $updating=false;
        }
        
        if ($updating AND $CFG->turnitin_useanon AND isset($turnitintool->anon) AND $turnitintool->anon) {
            $staticout=(isset($turnitintool->anon) AND $turnitintool->anon) ? get_string('yes', 'turnitintool') : get_string('no', 'turnitintool');
            $mform->addElement('static', 'static', get_string('turnitinanon', 'turnitintool'), $staticout);
            $mform->addElement('hidden', 'anon', $turnitintool->anon);
            $mform->setHelpButton('static', array('turnitinanon', get_string('turnitinanon', 'turnitintool'), 'turnitintool'));
        } else if ($CFG->turnitin_useanon) {
            $mform->addElement('select', 'anon', get_string('turnitinanon', 'turnitintool'), $ynoptions);
            $mform->setHelpButton('anon', array('turnitinanon', get_string('turnitinanon', 'turnitintool'), 'turnitintool'));
            $mform->setDefault('anon', 0);
        } else {
            $mform->addElement('hidden', 'anon', 0);
        }
        
        $mform->addElement('select', 'studentreports', get_string('studentreports', 'turnitintool'), $ynoptions);
        $mform->setHelpButton('studentreports', array('studentreports', get_string('studentreports', 'turnitintool'), 'turnitintool'));
        $mform->setDefault('studentreports', 0);
        
        $mform->addElement('header', 'general', get_string('advancedoptions', 'turnitintool'));
        $mform->addElement('select', 'allowlate', get_string('allowlate', 'turnitintool'), $ynoptions);
        $mform->setDefault('allowlate', 0);
        
        
        
        $genoptions = array( 0 => get_string('genimmediately1','turnitintool'), 1 => get_string('genimmediately2','turnitintool'), 2 => get_string('genduedate','turnitintool'));
        $mform->addElement('select', 'reportgenspeed', get_string('reportgenspeed', 'turnitintool'), $genoptions);
        $mform->setDefault('reportgenspeed', 0);
        
		$suboptions = array( 0 => get_string('norepository','turnitintool'), 1 => get_string('standardrepository','turnitintool'), 2 => get_string('institutionalrepository','turnitintool'));
		
        $mform->addElement('select', 'submitpapersto', get_string('submitpapersto', 'turnitintool'), $suboptions);
        $mform->setDefault('submitpapersto', 1);
        
        $mform->addElement('select', 'spapercheck', get_string('spapercheck', 'turnitintool'), $ynoptions);
        $mform->setDefault('spapercheck', 1);
        
        $mform->addElement('select', 'internetcheck', get_string('internetcheck', 'turnitintool'), $ynoptions);
        $mform->setDefault('internetcheck', 1);
        
        $mform->addElement('select', 'journalcheck', get_string('journalcheck', 'turnitintool'), $ynoptions);
        $mform->setDefault('journalcheck', 1);
        
        $mform->addElement('hidden','ownerid',NULL);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();

    }
}

/* ?> */