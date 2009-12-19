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

require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");

// parameters
$id = required_param('id',PARAM_INT);
$formname = required_param('form',PARAM_ALPHA);

// housekeeping
require_login($id);
$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

// check capability
require_capability('block/wiforms:access', $context  );

// load correct form
$filename = "forms/$formname.php";
if (!file_exists( $filename ) ) { 
    error( "Form $filename not found" );
}
require_once( $filename );

// instantiate the form
$formclass = "{$formname}_form";
$mform = new  $formclass();

// was form cancelled?
if ($mform->is_cancelled()) {
    redirect( "{$CFG->wwwroot}/course/view.php?id=$id", get_string('emailcancelled','block_wiforms'),2 );
}

// was form submitted
if ($formdata = $mform->get_data()) {
    $html = $mform->format_html( $formdata );

    // send as email
    $mailer = get_mailer();
    $mailer->From = $CFG->supportemail;
    $mailer->FromName = $CFG->supportname;
    $mailer->AddAddress( $CFG->block_wiforms_email);
    $mailer->AddReplyTo( $CFG->noreplyaddress );
    $mailer->Subject = $CFG->block_wiforms_subject;
    $mailer->IsHTML(true);
    $mailer->Body = $html;
    if (!$mailer->Send()) {
        error( 'Failed to send email: '.$mail->ErrorInfo );
    }

    // back to main course page
    redirect( "{$CFG->wwwroot}/course/view.php?id=$id", get_string('emailsent','block_wiforms'), 5 );
}

// display the form
print_header_simple();
$mform->display();
print_footer($COURSE);
