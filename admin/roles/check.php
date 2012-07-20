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
 * Shows the result of has_capability for every capability for a user in a context.
 *
 * @package    core
 * @subpackage role
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/roles/lib.php');

$contextid = required_param('contextid',PARAM_INT);

list($context, $course, $cm) = get_context_info_array($contextid);

$url = new moodle_url('/admin/roles/check.php', array('contextid' => $contextid));

if ($course) {
    $isfrontpage = ($course->id == SITEID);
} else {
    $isfrontpage = false;
    if ($context->contextlevel == CONTEXT_USER) {
        $course = $DB->get_record('course', array('id'=>optional_param('courseid', SITEID, PARAM_INT)), '*', MUST_EXIST);
        $user = $DB->get_record('user', array('id'=>$context->instanceid), '*', MUST_EXIST);
        $url->param('courseid', $course->id);
        $url->param('userid', $user->id);
    } else {
        $course = $SITE;
    }
}

// security first
require_login($course, false, $cm);
if (!has_any_capability(array('moodle/role:assign', 'moodle/role:safeoverride', 'moodle/role:override', 'moodle/role:manage'), $context)) {
    print_error('nopermissions', 'error', '', get_string('checkpermissions', 'role'));
}
$PAGE->set_url($url);
$PAGE->set_context($context);

$courseid = $course->id;
$contextname = print_context_name($context);

// Get the user_selector we will need.
// Teachers within a course just get to see the same list of people they can
// assign roles to. Admins (people with moodle/role:manage) can run this report for any user.
$options = array('context' => $context, 'roleid' => 0);
if (has_capability('moodle/role:manage', $context)) {
    $userselector = new potential_assignees_course_and_above('reportuser', $options);
} else {
    $userselector = roles_get_potential_user_selector($context, 'reportuser', $options);
}
$userselector->set_multiselect(false);
$userselector->set_rows(10);

// Work out an appropriate page title.
$title = get_string('checkpermissionsin', 'role', $contextname);

$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);

switch ($context->contextlevel) {
    case CONTEXT_SYSTEM:
        admin_externalpage_setup('checkpermissions', '', array('contextid' => $contextid));
        break;
    case CONTEXT_USER:
        $fullname = fullname($user, has_capability('moodle/site:viewfullnames', $context));
        $PAGE->set_heading($fullname);
        $showroles = 1;
        break;
    case CONTEXT_COURSECAT:
        $PAGE->set_heading("$SITE->fullname: ".get_string("categories"));
        break;
    case CONTEXT_COURSE:
        if ($isfrontpage) {
            admin_externalpage_setup('frontpageroles', '', array('contextid' => $contextid), $CFG->wwwroot . '/' . $CFG->admin . '/roles/check.php');
        } else {
            $PAGE->set_heading($course->fullname);
        }
        break;
    case CONTEXT_MODULE:
        $PAGE->set_heading(print_context_name($context, false));
        $PAGE->set_cacheable(false);
        break;
    case CONTEXT_BLOCK:
        $PAGE->set_heading($PAGE->course->fullname);
        break;
}

// Get the list of the reported-on user's role assignments - must be after
// the page setup code above, or the language might be wrong.
$reportuser = $userselector->get_selected_user();
if (!is_null($reportuser)) {
    $roleassignments = get_user_roles_with_special($context, $reportuser->id);
    $rolenames = role_get_names($context);
}

echo $OUTPUT->header();

// Print heading.
echo $OUTPUT->heading($title);

// If a user has been chosen, show all the permissions for this user.
if (!is_null($reportuser)) {
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

    if (!empty($roleassignments)) {
        echo $OUTPUT->heading(get_string('rolesforuser', 'role', fullname($reportuser)), 3);
        echo html_writer::start_tag('ul');

        $systemcontext = context_system::instance();
        foreach ($roleassignments as $ra) {
            $racontext = context::instance_by_id($ra->contextid);
            $link = html_writer::link($racontext->get_url(), $racontext->get_context_name());

            $rolename = $rolenames[$ra->roleid]->localname;
            if (has_capability('moodle/role:manage', $systemcontext)) {
                $rolename = html_writer::link(new moodle_url('/admin/roles/define.php',
                        array('action' => 'view', 'roleid' => $ra->roleid)), $rolename);
            }

            echo html_writer::tag('li', get_string('roleincontext', 'role',
                    array('role' => $rolename, 'context' => $link)));
        }
        echo html_writer::end_tag('ul');
    }

    echo $OUTPUT->heading(get_string('permissionsforuser', 'role', fullname($reportuser)), 3);
    $table = new check_capability_table($context, $reportuser, $contextname);
    $table->display();
    echo $OUTPUT->box_end();

    $selectheading = get_string('selectanotheruser', 'role');
} else {
    $selectheading = get_string('selectauser', 'role');
}

// Show UI for choosing a user to report on.
echo $OUTPUT->box_start('generalbox boxwidthnormal boxaligncenter', 'chooseuser');
echo '<form method="get" action="' . $CFG->wwwroot . '/' . $CFG->admin . '/roles/check.php" >';

// Hidden fields.
echo '<input type="hidden" name="contextid" value="' . $context->id . '" />';
if (!empty($user->id)) {
    echo '<input type="hidden" name="userid" value="' . $user->id . '" />';
}
if ($isfrontpage) {
    echo '<input type="hidden" name="courseid" value="' . $courseid . '" />';
}

// User selector.
echo $OUTPUT->heading('<label for="reportuser">' . $selectheading . '</label>', 3);
$userselector->display();

// Submit button and the end of the form.
echo '<p id="chooseusersubmit"><input type="submit" value="' . get_string('showthisuserspermissions', 'role') . '" /></p>';
echo '</form>';
echo $OUTPUT->box_end();

// Appropriate back link.
if ($context->contextlevel > CONTEXT_USER) {
    echo html_writer::start_tag('div', array('class'=>'backlink'));
    echo html_writer::tag('a', get_string('backto', '', $contextname), array('href'=>get_context_url($context)));
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();

