<?php

require_once( "{$CFG->dirroot}/question/type/multichoice/edit_multichoice_form.php" );

class question_edit_taggedmc_form extends question_edit_multichoice_form {

    function qtype() {
        return 'taggedmc';
    }

    function definition_inner(&$mform) {
        parent::definition_inner($mform);
       
        $mform->addElement('header', 'mctagshdr', get_string('mctags','qtype_taggedmc') ); 
        $mform->addElement('htmleditor', 'mctags', get_string('mctags', 'qtype_taggedmc'),
                                array('course' => $this->coursefilesid));
    }

}
