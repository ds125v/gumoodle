<?php //$Id: signout_form.php,v 1.1.2.1 2009/04/07 11:10:13 anic Exp $

require_once($CFG->dirroot.'/lib/formslib.php');

class signout_form extends moodleform {

    // Define the form
    function definition () {
        global $USER, $CFG, $COURSE;

        $mform  =& $this->_form;
        $groupselect = $this->_customdata;

        $mform->addElement('hidden','id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden','signout');
        $mform->setType('signout', PARAM_INT);

        $this->add_action_buttons(true, get_string('signout', 'groupselect'));
    }

}

?>
