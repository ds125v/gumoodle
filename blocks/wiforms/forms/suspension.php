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

class suspension_form extends moodleform {

    function definition() {
        global $CFG,$COURSE;

        $mform =& $this->_form;

        $att = 'size=40';

        // hidden stuff
        $mform->addElement('hidden','id',$COURSE->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden','form','suspension');
        $mform->setType('form',PARAM_ALPHA);

        // form elements
        $mform->addElement('static','wi','',"<h3>National Federation of Women's Institutes" );
        $mform->addElement('static','suspension','','<h3>Notice of Suspension of a WI</h3>');
        $mform->addElement('text','federation','Federation',$att);
        $mform->addElement('text','nameofwi',"Name of WI",$att);
        $mform->addElement('text','reason',"Reason for Suspension",$att);
        $mform->addElement('date_selector','datesuspension','Date of Suspension');
        $mform->addElement('text','funds','Funds held (if known)',$att);
        $mform->addElement('text','advisor','WI Adviser',$att);
        $this->add_action_buttons(true, 'Send');
    }

    function format_html( $data ) {
        $html = "<h3>National Federation of Women's Institutes</h3>";
        $html .= "<h3>Notice of Suspension of a WI</h3>\n";
        $html .= '<table cellspacing="0" cellpadding="5" >';
        $html .= "<tr><th>Federation:</th><td>{$data->federation}</td></tr>\n";
        $html .= "<tr><th>Name of WI:</th><td>{$data->nameofwi}</td></tr>\n";
        $html .= "<tr><th>Reason for Suspension:</th><td>{$data->reason}</td></tr>\n";
        $html .= "<tr><th>Date of Suspension:</th><td>" . userdate($data->datesuspension, '%A, %e %B %G') . "</td></tr>\n";
        $html .= "<tr><th>Funds held (if known):</th><td>{$data->funds}</td></tr>\n";
        $html .= "<tr><th>WI Adviser:</th><td>{$data->advisor}</td></tr>\n";
        $html .= "</table>\n";

        return $html;
    }

}
