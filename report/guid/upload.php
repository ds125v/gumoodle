<?php

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('lib.php');

// stuff
$ldaphost = 'dv-srv1.gla.ac.uk'; // data vault
$dn = 'o=Gla'; // base dn for search

// Start the page.
admin_externalpage_setup('reportguid', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('heading', 'report_guid'));

// check we have ldap
if (!function_exists( 'ldap_connect' )) {
    error( 'ldap drivers are not loaded' );
}

// form 
$mform = new guidreportupload_form();
if ($mform->is_cancelled()) {
    redirect( 'index.php' );
    die;
} else if ($data = $mform->get_data()) {

    // get the data from the file
    $filedata = $mform->get_file_content('csvfile');
    $iid = csv_import_reader::get_new_iid('uploadguid');
    $cir = new csv_import_reader( $iid, 'uploadguid');
    $count = $cir->load_csv_content( $filedata, 'utf8', 'comma' );
    
    // check for errors
    if ($cir->get_error()) {
        $link = new moodle_url( '/report/guid/upload.php' );
        notice( 'Error reading CSV file - ' . $cir->get_error(), $link );
        print_footer();
        die;
    }

    // notify line count or error
    if ($count>0) {
        echo "<p><strong>Number of lines in CSV file = $count</strong></p>";
    }
    else {
        echo $OUTPUT->notification( get_string('emptycsv', 'report_guid') );
    }

    // count created
    $createdcount = 0;
    $errorcount = 0;
    $existscount = 0;

    // iterate over lines in csv
    $cir->init();
    while ($line = $cir->next()) {
        // get the guid from first column
        foreach ($line as $key => $item) {
            $item = trim( $item,'" ' );
            if ($key==0) {
                $guid = $item;
            }
            else {

                // don't care about rest of line
                continue;
            }
        }

        // if no guid then carry on
        if (empty($guid)) {
            continue;
        }

        // notify...
        echo "<p><span class=\"label\">'$guid'</span> ";

        // try to find or make an account
        if (!$user = $DB->get_record( 'user', array('username'=>strtolower($guid)) )) {
        
            // need to find them in ldap
            $result = guid_ldapsearch( $ldaphost, $dn, "uid=$guid" );
            if (empty($result)) {
                echo "<span class=\"label label-important\">Error - Unable to find user in LDAP></span> ";
                $errorcount++;
                continue;
            }

            // sanity check
            if (count($result)>1) {
                echo "<span class=\"label label-important\">Error - Unexpected multiple results</span>";
                $errorcount++;
                continue;
            }

            // create account
            $result = array_shift( $result );
            $user = createUserFromLdap( $result );
            $link = new moodle_url( '/user/view.php', array('id'=>$user->id) );
            echo "<span class=\"label label-success\">account created for <a href=\"$link\">" . fullname($user) . "</a></span>";
            $createdcount++;
        }
        else {
            $link = new moodle_url( '/user/view.php', array('id'=>$user->id) );
            echo "<span class=\"label label-warning\">account not created, already exists for <a href=\"$link\">" . fullname($user) . "</a></span>";
            $existscount++;
        }

        echo "</p>";
    }
    echo "<ul class=\"label\">";
    echo "<li><strong>$createdcount new accounts created</strong></li>";
    echo "<li><strong>$existscount accounts already existed</strong></li>";
    echo "<li><strong>$errorcount lines caused an error</strong></li>";
    echo "</ul>";
} else {
    $mform->display();
}

echo $OUTPUT->footer();

?>
