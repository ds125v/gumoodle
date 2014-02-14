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
 * @package    enrol_gudatabase
 * @copyright  2013 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');

$enrolid = required_param('enrolid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$instance = $DB->get_record('enrol', array('id' => $enrolid, 'enrol' => 'gudatabase'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' =>$instance->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login();
if (!is_enrolled($context)) {
    redirect(new moodle_url('/'));
}
require_login($course);

$plugin = enrol_get_plugin('gudatabase');

// Security defined inside following function.
if (!$plugin->get_unenrolself_link($instance)) {
    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
}

$PAGE->set_url('/enrol/gudatabase/unenrolself.php', array('enrolid' => $instance->id));
$PAGE->set_title($plugin->get_instance_name($instance));

// Is user allowed to unenrol?
// They must not be in the MyCampus feed
$shortname = $course->shortname;
$idnumber = $course->idnumber;
$codes = $plugin->split_code( $idnumber );
$codes[] = clean_param( $shortname, PARAM_ALPHANUM );
$usercourses = $plugin->get_user_courses($USER->username);
foreach ($usercourses as $uc) {
    if (in_array($uc->courses, $codes)) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('cannotunenrol', 'enrol_gudatabase'), 'alert alert-error');
        $conurl = new moodle_url('/course/view.php', array('id' => $course->id));
        echo $OUTPUT->continue_button($conurl);
        echo $OUTPUT->footer();
        die;
    }
}

if ($confirm and confirm_sesskey()) {
    $plugin->unenrol_user($instance, $USER->id);
    add_to_log($course->id, 'course', 'unenrol', '../enrol/users.php?id='.$course->id, $course->id);
    redirect(new moodle_url('/index.php'));
}

echo $OUTPUT->header();
$yesurl = new moodle_url($PAGE->url, array('confirm' => 1, 'sesskey' => sesskey()));
$nourl = new moodle_url('/course/view.php', array('id' => $course->id));
$message = get_string('unenrolselfconfirm', 'enrol_gudatabase', format_string($course->fullname));
echo $OUTPUT->confirm($message, $yesurl, $nourl);
echo $OUTPUT->footer();
