<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * GUID report
 *
 * @package    report_guid
 * @copyright  2013 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");

function build_filter( $firstname, $lastname, $guid, $email ) {

    // LDAP filter doesn't like escaped characters.
    $lastname = stripslashes( $lastname );
    $firstname = stripslashes( $firstname );

    // If the GUID is supplied then we don't care about anything else.
    if (!empty($guid)) {
        return "uid=$guid";
    }

    // If the email is supplied then we don't care about name.
    if (!empty($email)) {
        return "mail=$email";
    }

    // Otherwise we'll take the name.
    if (empty($firstname) and !empty($lastname)) {
        return "sn=$lastname";
    }
    if (!empty($firstname) and empty($lastname)) {
        return "givenname=$firstname";
    }
    if (!empty($firstname) and !empty($lastname)) {
        return "(&(sn=$lastname)(givenname=$firstname))";
    }

    // Everything must have been empty.
    return false;
}

function guid_ldapsearch( $ldaphost, $ldapdn, $filter ) {

    // Connect to host.
    if (!$dv = ldap_connect( $ldaphost )) {
        debugging( 'Failed to connect to ldap host ' );
        return false;
    }

    // Anonymous bind.
    if (!ldap_bind( $dv )) {
        debugging( 'Failed anonymous bind to ldap host '.ldap_error( $dv ) );
        return false;
    }

    // Search.
    if (!$search = @ldap_search($dv, $ldapdn, $filter)) {
        debugging( 'ldap search failed for filter "'.$filter.'" '.ldap_error( $dv ) );
        return false;
    }

    // Check for errors returned.
    // (particularly partial results as GUID is limited to 100).
    $errorcode = ldap_errno( $dv );
    $errorstring = ldap_error( $dv );

    // If error returned then...
    // Need to check for string.
    if ($errorcode != 0) {
        return $errorstring;
    }

    // Check if we got any results.
    if (ldap_count_entries( $dv, $search) < 1) {
        return array();
    }

    // Get results.
    if (!$results = ldap_get_entries($dv, $search)) {
        debugging( 'Failed to extract ldap results '.ldap_error( $dv ) );
        return false;
    }

    // Unravel results.
    $results = cleanup_entry( $results );

    return $results;
}

function cleanup_entry( $entry ) {
    $retentry = array();
    for ($i = 0; $i < $entry['count']; $i++) {
        if (is_array($entry[$i])) {
            $subtree = $entry[$i];

            // This condition should be superfluous so just take the recursive call
            // adapted to your situation in order to increase perf..
            if ( !empty($subtree['dn']) && !isset($retentry[$subtree['dn']])) {
                $retentry[$subtree['dn']] = cleanup_entry($subtree);
            } else {
                $retentry[] = cleanup_entry($subtree);
            }
        } else {
            $attribute = $entry[$i];
            if ( $entry[$attribute]['count'] == 1 ) {
                $retentry[$attribute] = $entry[$attribute][0];
            } else {
                for ($j = 0; $j < $entry[$attribute]['count']; $j++) {
                    $retentry[$attribute][] = $entry[$attribute][$j];
                }
            }
        }
    }
    return $retentry;
}

function print_results( $results, $url ) {
    // Basic idea is to print as abbreviated list unless there is
    // only one.

    global $CFG, $DB;

    // Check there are some.
    if (empty($results)) {
        echo "<div class=\"generalbox\">".get_string('noresults', 'report_guid')."</div>";
        return;
    }

    // Get/set paging information.
    $pagesize = 20;
    $page = optional_param( 'page', 0, PARAM_INT );
    $firstcol = $page * $pagesize + 1;
    $lastcol = $firstcol + $pagesize - 1;

    // Use fancy table thing.
    require_once( "{$CFG->libdir}/tablelib.php" );

    // Note any external email addresses.
    $externalmail = false;

    // Add dn into data.
    foreach ($results as $dn => $result) {
        $results[$dn]['dn'] = $dn;
    }

    if (count($results) > 1) {
        $table = new flexible_table( 'ldap' );
        $table->pageable( true );
        $table->pagesize( $pagesize, count( $results ) );
        $table->define_baseurl( $url->out(true, array('sesskey' => sesskey())) );
        $table->define_columns( array('CN', 'firstname', 'lastname', 'email', 'more') );
        $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
        $table->define_headers( array(
            get_string( 'username' ),
            get_string( 'firstname' ),
            get_string( 'lastname' ),
            get_string( 'email' ),
            '') );
        $table->setup();
        $colnum = 0;
        foreach ($results as $cn => $result) {
            $colnum++;
            if (($colnum < $firstcol) || ($colnum > $lastcol)) {
                continue;
            }
            $guid = $result['uid'];

            // Possible (but rare) multiple uids.
            if (is_array($guid)) {
                $guid = $result['cn'];
            }

            // Modify guid in url.
            $url->param( 'guid', $guid );

            $mailinfo = get_email( $result );
            $mail = $mailinfo['mail'];
            if (!$mailinfo['primary']) {
                $mail = "<i>$mail</i>";
                $externalmail = true;
            }
            if ($user = $DB->get_record('user', array('username' => strtolower($guid)))) {
                $username = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$user->id}&amp;course=1\">$guid</a>";
            } else {
                $username = $guid;
            }
            if ($username) {
                $table->add_data( array(
                    $username,
                    $result['givenname'],
                    $result['sn'],
                    $mail,
                    '<a href="'.$url->out(true, array('sesskey' => sesskey())).'">'.
                    get_string('more', 'report_guid').'</a>' )
                );
            }
        }
        echo "<div class=\"generalbox\">".get_string('numberofresults', 'report_guid', count($results))."</div>";
        $table->print_html();

        // If external emails - add note.
        if ($externalmail) {
            echo '<div class="generalbox">'.get_string( 'externalmail', 'report_guid' ).'</div>';
        }

    } else {
        print_single( $results );
    }
}

function get_email( $result ) {

    // Try to find an email address to use.
    if (!empty($result['mail'])) {
        return array( 'primary' => true, 'mail' => $result['mail'] );
    }
    if (!empty($result['emailaddress'])) {
        $mail = ltrim( $result['emailaddress'], '3#' );
        return array( 'primary' => false, 'mail' => $mail );
    }
    return array( 'primary' => true, 'mail' => '' );
}

function print_single( $results ) {
    global $OUTPUT;

    // Just print a single result.
    global $CFG, $USER, $DB;

    $result = array_shift( $results );
    $fullname = ucwords(strtolower($result['givenname'].' '.$result['sn']));

    // Do they have an email.
    $mailinfo = get_email( $result );

    // Do they have a moodle account?
    $username = $result['uid'];
    if (is_array($username)) {
        $username = $result['cn'];
    }
    if ($user = $DB->get_record( 'user', array('username' => strtolower($username)) )) {
        $displayname = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$user->id}&amp;course=1\">$fullname</a>";
        $create = '';
    } else {
        $displayname = $fullname;
        $create = "<a href=\"{$CFG->wwwroot}/report/guid/index.php?action=create&amp;guid=$username&amp;sesskey=".sesskey()."\" >";
        if (!empty( $mailinfo['mail'] )) {
            $create .= get_string('create', 'report_guid')."</a>";
        } else {
            $create = '<i>'.get_string('noemail', 'report_guid').'</i>';
        }

        // Save the record in case we want to create the user.
        $USER->report_guid_ldap = $result;
    }
    if (!empty($user)) {
        echo $OUTPUT->user_picture( $user, array('size' => 100) );
    }
    echo "<p><strong>".get_string( 'resultfor', 'report_guid')." $displayname</strong> $create ($username)</p>\n";
    array_prettyprint( $result );

    // Check for entries in enrollments.
    $enrolments = get_all_enrolments( $username );
    if (!empty($enrolments)) {
        print_enrolments( $enrolments, $fullname, $username );
    } else if ($enrolments === false) {
        echo '<p class="alert alert-error">' . get_string('noguenrol', 'report_guid') . '</p>';
    } else {
        echo '<p class="alert">' . get_string('noenrolments', 'report_guid') . '</p>';
    }

    if ($enrolments !== false) {

        // Find mycampus enrolment data.
        $gudatabase_config = get_config('enrol_gudatabase');
        if (empty($gudatabase_config->dbhost)) {
            echo '<p class="alert alert-error">' . get_string('nogudatabase', 'report_guid') . '</p>';
        } else {
            $gudatabase = enrol_get_plugin('gudatabase');
            $courses = $gudatabase->get_user_courses( $username );
            if (!empty($courses)) {
                print_mycampus($courses, $username);
            } else {
                echo '<p class="alert">' . get_string('nomycampus', 'report_guid') . '</p>';
            }
        }
    }
}

/**
 * go and find enrollments across all Moodles
 * from external enrollment tables
 */
function get_all_enrolments( $guid ) {
    global $CFG;

    // Get plugin config for local_gusync.
    $config = get_config( 'local_gusync' );

    // Is that plugin configured?
    if (empty($config->dbhost)) {
        return false;
    }

    // Just use local_gusync's library functions.
    if (file_exists($CFG->dirroot . '/local/gusync/lib.php')) {
        require_once($CFG->dirroot . '/local/gusync/lib.php');
    } else {
        return false;
    }

    // Attempt to connect to external db.
    if (!$extdb = local_gusync_dbinit($config)) {
        return false;
    }

    // SQL to find user enrolments.
    $sql = "select * from moodleenrolments join moodlecourses ";
    $sql .= "on (moodleenrolments.moodlecoursesid = moodlecourses.id) ";
    $sql .= "where guid='" . addslashes( $guid ) . "' ";
    $sql .= "order by site, timelastaccess desc ";
    $enrolments = local_gusync_query( $extdb, $sql );

    $extdb->Close();
    if (count($enrolments) == 0) {
        return array();
    } else {
        return $enrolments;
    }
}

/**
 * print enrolments 
 */
function print_enrolments( $enrolments, $name, $guid ) {
    global $OUTPUT;

    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('enrolments', 'report_guid', $name));

    // Old site to see when site changes.
    $oldsite = '';

    // Run through enrolments.
    foreach ($enrolments as $enrolment) {
        $newsite = $enrolment->site;
        if ($newsite != $oldsite) {
            $sitelink = $enrolment->wwwroot;
            echo "<p>&nbsp;</p>";
            echo "<h3>".get_string('enrolmentsonsite', 'report_guid', "<a href=\"$sitelink\">$newsite</a>")."</h3>";
            $profilelink = $enrolment->wwwroot . '/user/view.php?id=' . $guid;
            $oldsite = $newsite;
        }
        $courselink = $enrolment->wwwroot . '/course/view.php?id=' . $enrolment->courseid;
        if (empty($enrolment->timelastaccess)) {
            $lasttime = get_string('never');
        } else {
            $lasttime = date( 'd/M/y H:i', $enrolment->timelastaccess );
        }
        echo "<a href=\"$courselink\">{$enrolment->name}</a> <i>(accessed $lasttime)</i><br />";
    }

    echo $OUTPUT->box_end();
}

/**
 * print MyCampus data
 */
function print_mycampus($courses, $guid) {
    global $OUTPUT;

    // Normalise.
    $guid = strtolower( $guid );

    // Title.
    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('mycampus', 'report_guid'));

    // Did we pick up any guid mismatches.
    $mismatch = false;

    // Run through the courses.
    foreach ($courses as $course) {
        echo "<p><strong>{$course->courses}</strong> ";
        if ($course->name != '-') {
            echo "'{$course->name}' in '{$course->ou}' ";
        }

        // Check for username discrepancy.
        if ($course->UserName != $guid) {
            echo "as <span class=\"label label-warning\">{$course->UserName}</span> ";
            $mismatch = true;
        }
        echo "</p>";
    }

    // Mismatch?
    if ($mismatch) {
        echo "<p><span class=\"label label-warning\">".get_string('guidnomatch', 'report_guid')."</span></p>";
    }

    echo $OUTPUT->box_end();
}

function array_prettyprint( $rows ) {
    echo "<ul>\n";
    foreach ($rows as $name => $row) {
        if (is_array( $row )) {
            echo "<li><strong>$name:</strong>";
            array_prettyprint( $row );
            echo "</li>\n";
        } else {
            echo "<li><strong>$name</strong> => $row</li>\n";
        }
    }
    echo "</ul>\n";
}

// Create new Moodle user.
function create_user_from_ldap( $result ) {
    global $DB;

    $user = create_user_record( strtolower($result['uid']), 'not cached', 'guid' );
    $user->firstname = $result['givenname'];
    $user->lastname = $result['sn'];
    $user->city = 'Glasgow';
    $user->country = 'GB';
    $mailinfo = get_email( $result );
    $user->email = $mailinfo['mail'];
    if (!empty( $user->email )) {
        $DB->update_record( 'user', $user );

        // If not primary email make this email private.
        if (!$mailinfo['primary']) {
            $DB->set_field( 'user', 'maildisplay', 0, array('id' => $user->id));
        }
    }

    return $user;
}

// Form definition for search.
class guidreport_form extends moodleform {

    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        // Main part.
        $mform->addElement('html', '<div>'.get_string('instructions', 'report_guid' ) );
        $mform->addElement('text', 'firstname', get_string('firstname', 'report_guid' ) );
        $mform->addElement('text', 'lastname', get_string('lastname', 'report_guid' ) );
        $mform->addElement('text', 'email', get_string('email', 'report_guid' ) );
        $mform->addElement('text', 'guid', get_string('guidform', 'report_guid' ) );

        // Action buttons.
        $this->add_action_buttons(true, get_string('search', 'report_guid'));
    }
}

// Form definition for upload form.
class guidreportupload_form extends moodleform {

    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        // File upload.
        $mform->addElement('header', 'guidupload', get_string('uploadheader', 'report_guid' ) );
        $mform->addElement('html', '<div>'.get_string('uploadinstructions', 'report_guid' ).'</div>' );
        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'report_guid' ) );

        // Action buttons.
        $this->add_action_buttons(true, get_string('submitfile', 'report_guid'));
    }

}
