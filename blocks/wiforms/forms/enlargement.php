<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 2009 onwards  E-Learn Design Limited                    //
// http://www.e-learndesign.co.uk
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

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
        $mform->addElement('static','wi','',"<h3>National Federation of Women's Institutes" );
        $mform->addElement('static','enlargement','','<h3>Notice of Enlargement of WIs</h3>');
        $mform->addElement('text','federation','Federation',$att);
        $mform->addElement('text','continuing',"'Continuing' WI",$att);
        $mform->addElement('text','terminating',"'Terminating' WI",$att);
        $mform->addElement('text','name','Name of Enlarged WI',$att);
        $mform->addElement('date_selector','dateenlargement','Date of Enlargement');
        $mform->addElement('text','wia','WI Adviser (Mrs/Miss)',$att);
        $mform->addElement('static','mcs','','<strong>Please ensure that the MCS is up to date with the transfer of members from the terminating WI to the new enlarged WI</strong>');
        $this->add_action_buttons(true, 'Send');
    }

    function format_html( $data ) {
        $html = "<h3>National Federation of Women's Institutes</h3>";
        $html .= "<h3>Notice of Enlargement of WIs</h3>\n";
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
