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
 * Anonymous report
 *
 * @package    report
 * @subpackage anonymous
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

// Parameters.
$id = required_param('id', PARAM_INT);
$mod = optional_param('mod', '', PARAM_ALPHA);
$assignid = optional_param('assign', 0, PARAM_INT);
$partid = optional_param('part', 0, PARAM_INT);
$reveal = optional_param('reveal', 0, PARAM_INT);
$export = optional_param('export', 0, PARAM_INT);

$url = new moodle_url('/report/anonymous/index.php', array('id' => $id));
$fullurl = new moodle_url('/report/anonymous/index.php', array(
    'id' => $id,
    'mod' => $mod,
    'assign' => $assignid,
    'part' => $partid,
    'reveal' => $reveal,
));

// Page setup.
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourse');
}

// Security.
require_login($course);
$output = $PAGE->get_renderer('report_anonymous');
$context = get_context_instance(CONTEXT_COURSE, $course->id);
$captt = has_capability('mod/turnitintool:grade', $context);
$capassign = has_capability('mod/assign:grade', $context);
$capmods = $captt || $capassign;
if (!$capmods || !has_capability('report/anonymous:view', $context)) {
    notice(get_string('nocapability', 'report_anonymous'));
}

// Log.
add_to_log($course->id, "course", "report anonymous", "report/anonymous/index.php?id=$course->id", $course->id);

if (!$export) {
    $PAGE->set_title($course->shortname .': '. get_string('pluginname', 'report_anonymous'));
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
}

// Get assignments with 'blind' marking.
if ($capassign) {
    $assignments = report_anonymous::get_assignments($id);
} else {
    $assignments = array();
}

// Get turnitintool submissions with 'anon' marking.
if ($captt) {
    $tts = report_anonymous::get_tts($id);
} else {
    $tts = array();
}

// Has a link been submitted?
if ($mod) {
    if (!report_anonymous::allowed_to_view($mod, $assignid, $partid, $assignments, $tts)) {
        notice(get_string('notallowed', 'report_anonymous'), $url);
    }

    if ($mod == 'assign') {
        $assignment = $DB->get_record('assign', array('id' => $assignid));
        $allusers = report_anonymous::get_assign_users($context);
        $notsubmittedusers = report_anonymous::get_assign_notsubmitted($assignid, $allusers);
        $notsubmittedusers = report_anonymous::sort_users($notsubmittedusers, $reveal);
        if ($export) {
            $filename = "anonymous_{$assignment->name}.xls";
            report_anonymous::export($notsubmittedusers, $reveal, $filename);
            die;
        }
        $output->actions($context, $fullurl, $reveal);
        $output->report_assign($id, $assignment, $allusers, $notsubmittedusers, $reveal);
        $output->back_button($url);
    } else {
        $part = $DB->get_record('turnitintool_parts', array('id' => $partid));
        $turnitintool = $DB->get_record('turnitintool', array('id' => $part->turnitintoolid));
        $allusers = report_anonymous::get_turnitintool_users($context);
        $notsubmittedusers = report_anonymous::get_turnitintool_notsubmitted($part->turnitintoolid, $partid, $allusers);
        $notsubmittedusers = report_anonymous::sort_users($notsubmittedusers, $reveal);
        if ($export) {
            $filename = "anonymous_{$turnitintool->name}_{$part->partname}.xls";
            report_anonymous::export($notsubmittedusers, $reveal, $filename, $turnitintool->name, $part->partname);
            die;
        }
        $output->actions($context, $fullurl, $reveal);
        $output->report_turnitintool($id, $part, $allusers, $notsubmittedusers, $reveal);
        $output->back_button($url);
    }
} else {

    // List of activities to select.
    $output->list_assign($fullurl, $assignments);
    $output->list_turnitintool($fullurl, $tts);
}

echo $OUTPUT->footer();

