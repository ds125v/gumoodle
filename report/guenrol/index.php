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

// if codeid=0 we will just show the list of possible codes
if (empty($codeid)) {
    $codes = $DB->get_records( 'enrol_gudatabase_codes', array('courseid'=>$id));
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
        echo '</ul>';
    }
}
else {

    // get enrolment info
    if (!$code = $DB->get_record('enrol_gudatabase_codes', array('id'=>$codeid))) {
        error('Unable to read codes table');
        die;
    }

    // get users
    $users = $DB->get_records('enrol_gudatabase_users', array('courseid'=>$id, 'code'=>$code->code));

    // some information
    echo "<p>Enrolments relating to code <strong>{$code->code}</strong>. ";
    echo "Course '{$code->coursename}' in '{$code->subjectname}'</p>";

    // list users
    echo "<ul id=\"guenrol_users\">";
    foreach ($users as $user) {
        $moodleuser = $DB->get_record('user', array('id'=>$user->userid));

        // be sure not to show deleted accounts
        if ($moodleuser->deleted) {
            continue;
        }

        // display user (profile) link and data
        $link = new moodle_url( '/user/profile.php', array('id'=>$moodleuser->id));
        echo "<li>";
        echo "<a href=\"$link\"><strong>{$moodleuser->username}</strong></a> ";
        echo fullname( $moodleuser );
        echo "</li>";
    }
    echo "</ul>";
    echo "<p>Total number of users of this code: " . count($users) . "</p>";
}

echo $OUTPUT->footer();


