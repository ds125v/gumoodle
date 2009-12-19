<?php
// WI Forms Block
//
// Copyright E-Learn Design Limited 2009
// http://www.e-learndesign.co.uk
//

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
