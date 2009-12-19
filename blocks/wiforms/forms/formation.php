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

class formation_form extends moodleform {

    function definition() {
        global $CFG,$COURSE;

        $mform =& $this->_form;

        $att = 'size=40';

        // hidden stuff
        $mform->addElement('hidden','id',$COURSE->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden','form','formation');
        $mform->setType('form',PARAM_ALPHA);

        // form elements
        $mform->addElement('static','formation','','<h3>Notice of Formation of an Institute</h3>');
        $mform->addElement('text','federation','Federation',$att);
        $mform->addElement('text','nameofwi',"Name of WI",$att);
        $mform->addElement('date_selector','dateoffirstmeeting','Date of first meeting');
        $mform->addElement('date_selector','officialformationdate','Official formation date');
        $mform->addElement('text','place',"Workplace/University/Urban/Other",$att);
        $mform->addElement('text','secretary','WIA/Federation Secretary',$att);
        $mform->addElement('text','address1','Address (line 1)',$att);
        $mform->addElement('text','address2','Address (line 2)',$att);
        $this->add_action_buttons();
    }

    function format_html( $data ) {
        $html = "<h3>Notice of Formation of an Institute</h3>\n";
        $html .= '<table cellspacing="0" cellpadding="5" >';
        $html .= "<tr><th>Federation:</th><td>{$data->federation}</td></tr>\n";
        $html .= "<tr><th>Name of WI:</th><td>{$data->nameofwi}</td></tr>\n";
        $html .= "<tr><th>Date of first meeting:</th><td>" . userdate($data->dateoffirstmeeting, '%A, %e %B %G') . "</td></tr>\n";
        $html .= "<tr><th>Official formation date:</th><td>" .  userdate($data->officialformationdate, '%A, %e %B %G') . "</td></tr>\n";
        $html .= "<tr><th>Workplace/University/Urban/Other:</th><td>{$data->place}</td></tr>\n";
        $html .= "<tr><th>Secretary:</th><td>{$data->secretary}</td></tr>\n";
        $html .= "<tr><th>Address (line 1):</th><td>{$data->address1}</td></tr>\n";
        $html .= "<tr><th>Address (line 2):</th><td>{$data->address2}</td></tr>\n";
        $html .= "</table>\n";

        return $html;
    }

}
