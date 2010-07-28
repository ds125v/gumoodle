<?php

require_once('../../../config.php');
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
admin_externalpage_setup('reportguid');
admin_externalpage_print_header();
print_heading(get_string('heading', 'report_guid'));

// form for getting user details
print_box_start();
echo "<form action=\"{$CFG->wwwroot}/admin/report/guid/index.php?action=search\" method=\"post\" >\n";
echo "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />\n";
echo "<table>\n";
echo "<tr><td colspan=\"2\" align=\"center\">".get_string('instructions','report_guid')."</td></tr>\n";
echo "<tr><td align=\"right\">".get_string('firstname','report_guid').
    "</td><td><input type=\"text\" name=\"firstname\" value=\"$firstname\"/></td></tr>\n";
echo "<tr><td align=\"right\">".get_string('lastname','report_guid').
    "</td><td><input type=\"text\" name=\"lastname\" value=\"$lastname\" /></td></tr>\n";
echo "<tr><td align=\"right\">".get_string('email','report_guid').
    "</td><td><input type=\"text\" name=\"email\" value=\"$email\" /></td></tr>\n";
echo "<tr><td align=\"right\">".get_string('guidform','report_guid').
    "</td><td><input type=\"text\" name=\"guid\" value=\"$guid\" /></td></tr>\n";
echo "<tr><td colspan=\"2\" align=\"center\"<input type=\"submit\" /></td></tr>\n";
echo "</table>\n";
echo "</form>\n";
print_box_end();

// check for user create
if (($action == 'create') and confirm_sesskey()) {
    if (!empty($USER->report_guid_ldap)) {
        $result = $USER->report_guid_ldap;
        if ($guid==$result['uid']) {
            $user = create_user_record( $guid,'not cached','guid' ); 
            $user->firstname = $result['givenname'];
            $user->lastname = $result['sn'];
            empty( $result['mail'] ) ? $user->email = '' : $user->email = $result['mail'];
            update_record( 'user',$user );
        }
    }
}

// check for Search
if ((($action == 'search') or ($action == 'create')) and confirm_sesskey()) {
    if (!$filter = build_filter( $firstname, $lastname, $guid, $email )) {
        admin_externalpage_print_footer();
        die;
    }
    $result = guid_ldapsearch( $ldaphost, $dn, $filter );
    if ($result === false) {
        echo "<p><b>LDAP Search failed. Try with debugging on</b></p>\n";
        admin_externalpage_print_footer();
        die;
    }
    print_results( $result ); 
}

admin_externalpage_print_footer();

?>
