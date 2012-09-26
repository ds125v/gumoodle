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
 * Participation report
 *
 * @package    report
 * @subpackage guenrol
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/lib/tablelib.php');

// parameters
$id = required_param('id', PARAM_INT); // course id.
$codeid	= optional_param('codeid', 0, PARAM_INT);

$url = new moodle_url('/report/guenrol/index.php', array('id'=>$id));

// page setip
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourse');
}

// security
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('report/guenrol:view', $context);

// log
add_to_log($course->id, "course", "report guenrol", "report/guenrol/index.php?id=$course->id", $course->id);

$PAGE->set_title($course->shortname .': '. get_string('pluginname', 'report_guenrol'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading( get_string('title', 'report_guenrol') . ' : ' . $course->fullname );

// get the codes for this course
$codes = $DB->get_records( 'enrol_gudatabase_codes', array('courseid'=>$id));

// if codeid=0 we will just show the list of possible codes
if (empty($codeid)) {
    if (empty($codes)) {
        echo "<p>There are no automatic enrolment codes defined in this course</p>";
    }
    else {
        echo "<p>" . get_string('listofcodes', 'report_guenrol') . "</p>";
        echo '<ul id="guenrol_codes">';
        foreach ($codes as $code) {
            
            // establish link for detailed display
            $link = new moodle_url('/report/guenrol/index.php', array('id'=>$id, 'codeid'=>$code->id));
            echo "<li><a href=\"$link\">";
            echo "<strong>{$code->code}</strong></a> ";
            echo "\"{$code->coursename}\" ";
            echo "({$code->subjectname}) ";
            echo "</li>";
        }

        // if there is more than 1 show aggregated
        if (count($codes)>1) {
            $link = new moodle_url('/report/guenrol/index.php', array('id'=>$id, 'codeid'=>-1));
            echo "<li><a href=\"$link\">";
            echo "<strong>" . get_string('showall', 'report_guenrol') . "</strong></a> ";
            echo "</li>";
        }
        echo '</ul>';

        // dropdown to get sort order
    }
}
else {

    // get enrolment info
    if ($codeid>-1) {
        $selectedcode = $codes[ $codeid ];
    }
    else {
        $selectedcode = NULL;
    }

    // get users
    if ($codeid>-1) {
        $codename = $codes[$codeid]->code;
        $coursename = $codes[$codeid]->coursename;
        $subjectname = $codes[$codeid]->subjectname;
        $users = $DB->get_records('enrol_gudatabase_users', array('courseid'=>$id, 'code'=>$codename));
    }
    else {
        $users = array();
        foreach ($codes as $code) {
            $codeusers = $DB->get_records('enrol_gudatabase_users', array('courseid'=>$id, 'code'=>$code->code));
            $users = array_merge($users, $codeusers);
        }    
    }

    // convert to unique userid table based on code
    $uniqueusers = array();
    foreach ($users as $user) {
        if (empty($uniqueusers[ $user->userid ])) {
            $moodleuser = $DB->get_record('user', array('id'=>$user->userid));
            $uniqueusers[ $user->userid ] = $user;
	    $uniqueusers[ $user->userid ]->firstname = $moodleuser->firstname;
            $uniqueusers[ $user->userid ]->lastname = $moodleuser->lastname;
            $uniqueusers[ $user->userid ]->fullname = fullname( $moodleuser );
            $uniqueusers[ $user->userid ]->deleted = $moodleuser->deleted;
            $uniqueusers[ $user->userid ]->username = $moodleuser->username;
        }
        else {
            $uniqueusers[ $user->userid]->code .= ", {$user->code}";
        }
    }

    // sort
    usort( $uniqueusers, 'report_guenrol_sort' );

    // some information
    if ($codeid>-1) {
        echo "<p>Enrolments relating to code <strong>$codename</strong>. ";
        echo "Course '$coursename' in '$subjectname'</p>";
    }
    else {
        echo "<p>Users for the following coursecodes:<p>";
        echo "<ul>";
        foreach ($codes as $code) {
            echo "<li><strong>{$code->code}</strong> Course '{$code->coursename}' in '{$code->subjectname}'</li>";
        }
        echo "</ul>";
    }

    // list users
    echo "<ul id=\"guenrol_users\">";
    foreach ($uniqueusers as $user) {

        // be sure not to show deleted accounts
        if ($user->deleted) {
            continue;
        }

        // display user (profile) link and data
        $link = new moodle_url( '/user/profile.php', array('id'=>$user->userid));
        echo "<li>";
        echo "<a href=\"$link\"><strong>{$user->username}</strong></a> ";
        echo $user->fullname;
        echo " <small>({$user->code})</small>";
        echo "</li>";
    }
    echo "</ul>";
    echo "<p>Total number of users of this code: " . count($users) . "</p>";
}

echo $OUTPUT->footer();

function bbb() { }

// callback function for sort
function report_guenrol_sort( $a, $b ) {
    $afirstname = strtolower( $a->firstname );
    $alastname = strtolower( $a->lastname );
    $bfirstname = strtolower( $b->firstname );
    $blastname = strtolower( $b->lastname );

    if ($alastname == $blastname) {
        if ($afirstname == $bfirstname) {
            return 0;
        }
        else {
            return ($afirstname < $bfirstname) ? -1 : 1;
        }
    }
    else {
        return ($alastname < $blastname) ? -1 : 1;
    }
}
