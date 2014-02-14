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

// Parameters.
$id = required_param('id', PARAM_INT); // Course id.
$codeid = optional_param('codeid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$url = new moodle_url('/report/guenrol/index.php', array('id' => $id));

// Page setup.
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourse');
}

// Security.
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('report/guenrol:view', $context);
$output = $PAGE->get_renderer('report_guenrol');;

// Log.
add_to_log($course->id, "course", "report guenrol", "report/guenrol/index.php?id=$course->id", $course->id);

$PAGE->set_title($course->shortname .': '. get_string('pluginname', 'report_guenrol'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading( get_string('title', 'report_guenrol') . ' : ' . $course->fullname );

// Re-sync the codes
if ($action=='sync') {
    $gudatabase = enrol_get_plugin('gudatabase');
    $gudatabase->course_updated(false, $course, null);
}

// Get the codes for this course.
$codes = $DB->get_records( 'enrol_gudatabase_codes', array('courseid' => $id));

// convert to simple array
$simplecodes = array();
foreach ($codes as $code) {
   $simplecodes[] = $code->code; 
}

// If action is 'removed'...
if (($action=='removed') || ($action=='unenrol')) {

    // Get the gudatabase enrolments in this course.
    $sql = 'SELECT u.id, u.username, u.firstname, u.lastname, u.deleted, e.id AS instance from {user} u ';
    $sql .= 'JOIN {user_enrolments} ue ON (ue.userid=u.id) ';
    $sql .= 'JOIN {enrol} e ON (ue.enrolid=e.id) ';
    $sql .= 'WHERE e.courseid=? AND e.enrol=? ';
    $sql .= 'ORDER BY lastname, firstname ';
    $users = $DB->get_records_sql($sql, array($id, 'gudatabase'));

    // Get all the users who are supposed to be in this course
    // and change to ids (for comparison).
    $gudatabase = enrol_get_plugin('gudatabase');
    $codeusers = $gudatabase->external_enrolments($simplecodes);
    $usernames = array();
    foreach ($codeusers as $codeuser) {
        $usernames[$codeuser->UserName] = $codeuser;
    }

    // compare
    $removed = array();
    foreach ($users as $user) {
        if (empty($usernames[$user->username])) {
            $removed[$user->id] = $user;
        }
    }

    // Display or unenrol
    if ($action=='removed') {
        $output->list_removed_users($id, $removed);
    } else {
        foreach ($removed as $remove) {
            $instance = $DB->get_record('enrol', array('id' => $remove->instance));
            $gudatabase->unenrol_user($instance, $remove->id);
        }
        $output->removed($id);
    }
} else if (empty($codeid)) {
    $output->menu($id, $codes);
} else {

    // Get enrolment info.
    if ($codeid > -1) {
        $selectedcode = $codes[ $codeid ];
    } else {
        $selectedcode = null;
    }

    // Get users.
    if ($codeid > -1) {
        $codename = $codes[$codeid]->code;
        $coursename = $codes[$codeid]->coursename;
        $subjectname = $codes[$codeid]->subjectname;
        $users = $DB->get_records('enrol_gudatabase_users', array('courseid' => $id, 'code' => $codename));
    } else {
        $users = array();
        foreach ($codes as $code) {
            $codeusers = $DB->get_records('enrol_gudatabase_users', array('courseid' => $id, 'code' => $code->code));
            $users = array_merge($users, $codeusers);
        }
        $codename = '';
        $coursename = '';
        $subjectname = '';
    }

    // Convert to unique userid table based on code.
    $uniqueusers = array();
    foreach ($users as $user) {
        if (empty($uniqueusers[ $user->userid ])) {
            $moodleuser = $DB->get_record('user', array('id' => $user->userid));
            $uniqueusers[ $user->userid ] = $user;
            $uniqueusers[ $user->userid ]->firstname = $moodleuser->firstname;
            $uniqueusers[ $user->userid ]->lastname = $moodleuser->lastname;
            $uniqueusers[ $user->userid ]->fullname = fullname( $moodleuser );
            $uniqueusers[ $user->userid ]->deleted = $moodleuser->deleted;
            $uniqueusers[ $user->userid ]->username = $moodleuser->username;
        } else {
            $uniqueusers[ $user->userid]->code .= ", {$user->code}";
        }
    }

    // Sort.
    usort( $uniqueusers, 'report_guenrol_sort' );

    // Some information.
    $output->code_info($codes, $codeid, $codename, $coursename, $subjectname);

    // List users.
    $output->list_users($uniqueusers);
}

echo $OUTPUT->footer();

// Callback function for sort.
function report_guenrol_sort( $a, $b ) {
    $afirstname = strtolower( $a->firstname );
    $alastname = strtolower( $a->lastname );
    $bfirstname = strtolower( $b->firstname );
    $blastname = strtolower( $b->lastname );

    if ($alastname == $blastname) {
        if ($afirstname == $bfirstname) {
            return 0;
        } else {
            return ($afirstname < $bfirstname) ? -1 : 1;
        }
    } else {
        return ($alastname < $blastname) ? -1 : 1;
    }
}
