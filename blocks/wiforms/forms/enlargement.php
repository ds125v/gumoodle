<?php
// WI Forms Block
//
// Copyright E-Learn Design Limited 2009
// http://www.e-learndesign.co.uk
//

class enlargement_form extends moodleform {

    function definition() {
        global $CFG,$COURSE;

        $mform =& $this->_form;

        $att = 'size=40';

        // hidden stuff
        $mform->addElement('hidden','id',$COURSE->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden','form','enlargement');
        $mform->setType('form',PARAM_ALPHA);

        // form elements
        $mform->addElement('static','enlargement','','<h3>Notice of Enlargement of WIs</h3>');
        $mform->addElement('text','federation','Federation',$att);
        $mform->addElement('text','continuing',"'Continuing' WI",$att);
        $mform->addElement('text','terminating',"'Terminating' WI",$att);
        $mform->addElement('text','name','Name of Enlarged WI',$att);
        $mform->addElement('date_selector','dateenlargement','Date of Enlargement');
        $mform->addElement('text','wia','WIA',$att);
        $this->add_action_buttons();
    }

    function format_html( $data ) {
        $html = "<h3>Notice of Enlargement of WIs</h3>\n";
        $html .= '<table cellspacing="0" cellpadding="5" >';
        $html .= "<tr><th>Federation:</th><td>{$data->federation}</td></tr>\n";
        $html .= "<tr><th>'Continuing' WI:</th><td>{$data->continuing}</td></tr>\n";
        $html .= "<tr><th>'Terminating' WI:</th><td>{$data->terminating}</td></tr>\n";
        $html .= "<tr><th>Name of Enlarged WI:</th><td>{$data->name}</td></tr>\n";
        $html .= "<tr><th>Date of Enlargement:</th><td>" . userdate($data->dateenlargement, '%A, %e %B %G') . "</td></tr>\n";
        $html .= "<tr><th>WIA:</th><td>{$data->wia}</td></tr>\n";
        $html .= "</table>\n";

        return $html;
    }

}
