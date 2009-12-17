<?php
// WI Forms Block
//
// Copyright E-Learn Design Limited 2009
// http://www.e-learndesign.co.uk
//

require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");

// get form to display2
$id = required_param('id',PARAM_INT);
$formname = required_param('form',PARAM_ALPHA);

// housekeeping
require_login();
$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

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
    redirect( "{$CFG->wwwroot}/course/view.php?id=$id" );
}

// was form submitted
if ($formdata = $mform->get_data()) {
    $html = $mform->format_html( $formdata );
    echo $html;
  echo "<pre>"; print_r( $formdata ); die;
}

// display the form
print_header_simple();
$mform->display();
print_footer($COURSE);
