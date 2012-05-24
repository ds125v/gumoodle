<?php

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once('lib.php');

// stuff
$ldaphost = 'dv-srv1.gla.ac.uk'; // data vault
$dn = 'o=Gla'; // base dn for search

// get paramters
$firstname = optional_param( 'firstname','',PARAM_TEXT );
$lastname = optional_param( 'lastname','',PARAM_TEXT );
$email = optional_param( 'email','',PARAM_CLEAN );
$guid = optional_param( 'guid','',PARAM_ALPHANUM );
$action = optional_param( 'action','',PARAM_ALPHA );


// Start the page.
admin_externalpage_setup('reportguid', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('heading', 'report_guid'));

// check we have ldap
if (!function_exists( 'ldap_connect' )) {
    error( 'ldap drivers are not loaded' );
}

// check for user create
if (($action == 'create') and confirm_sesskey()) {
    if (!empty($USER->report_guid_ldap)) {
        $result = $USER->report_guid_ldap;
        if ($guid==$result['uid']) {
            $user = createUserFromLdap( $result );    
            notice( "User has been created ({$user->firstname} {$user->lastname})" );
        }
    }
}

// form 
$mform = new guidreport_form(null,null,'get');
$mform->display();
if ($mform->is_cancelled()) {
    redirect( "index.php" );
} else if ($data = $mform->get_data()) {
    if (!$filter = build_filter( $data->firstname, $data->lastname, $data->guid, $data->email )) {
        notice( "Error building filter. Please refine your search and try again." );
        echo $OUTPUT->footer();
        die;
    }
    $result = guid_ldapsearch( $ldaphost, $dn, $filter );
    if (is_string( $result )) {
        notice( "Error returned by search (possibily too many results). Please refine your search and try again. Error was '$result'" );
        die;
    }
    if ($result === false) {
        echo "<p><b>LDAP Search failed. Try with debugging on</b></p>\n";
        echo $OUTPUT->footer();
        die;
    }
    // build url for paging
    $url = new moodle_url( $CFG->wwwroot.'/admin/report/guid/index.php',
        array(
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'email' => $data->email,
            'guid' => $data->guid,
            'submitbutton' => $data->submitbutton,
            '_qf__guidreport_form' => 1,
        ));
    print_results( $result, $url ); 
}

// link to upload script
echo "<div class=\"generalbox\">";
echo "<a href=\"{$CFG->wwwroot}/report/guid/upload.php\">".get_string('uploadguid','report_guid')."</a>";
echo "</div>\n";

echo $OUTPUT->footer();

?>
