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
        notice( 'Error reading CSV file - ' . $cir->get_error() );
        print_footer();
        die;
    }

    // notify line count
    echo "<div class=\"generalbox\">Number of lines in CSV file = $count</div>";

    // iterate over lines in csv
    $cir->init();
    while ($line = $cir->next()) {
        // get the id and courses, first is guid
        $courses = array();
        foreach ($line as $key => $item) {
            $item = trim( $item,'" ' );
            if ($key==0) {
                $guid = $item;
            }
            else {
                $courses[] = $item;
            }
        }

        // if no guid then carry on
        if (empty($guid)) {
            continue;
        }

        // notify...
        echo "<hr />User (GUID) <strong>$guid</strong>:<br />";

        // try to find or make an account
        if (!$user = $DB->get_record( 'user', array('username'=>strtolower($guid)) )) {
        
            // need to find them in ldap
            $result = guid_ldapsearch( $ldaphost, $dn, "uid=$guid" );
            if (empty($result)) {
                echo "Unable to find user in LDAP<br />";
                continue;
            }

            // sanity check
            if (count($result)>1) {
                echo "Unexpected multiple results<br />";
                continue;
            }

            // create account
            $result = array_shift( $result );
            $user = createUserFromLdap( $result );
            echo "Account created for {$user->firstname} {$user->lastname}<br />";
        }
        else {
            echo "User already exists in Moodle</br>";
        }

        // enrol user on courses
        if (!empty( $courses )) {
            foreach ($courses as $coursename) {
            
                // check if shortname exists
                if (!$course = $DB->get_record('course', array('shortname'=>$coursename))) {
                    echo "Course '$coursename' was not found<br />";
                    continue; 
                }

                // find default role and assign
                enrol_into_course( $course, $user, 'manual' );
                echo ".....Enrolled into course '{$course->fullname}'<br />";
            }
        }
    }
} else {
    $mform->display();
}

echo $OUTPUT->footer();

?>
