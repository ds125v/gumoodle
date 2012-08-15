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
 * Provide interface for topics AJAX course formats
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package course
 */

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}
require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot.'/course/lib.php');

// Initialise ALL the incoming parameters here, up front.
$courseid   = required_param('courseId', PARAM_INT);
$class      = required_param('class', PARAM_ALPHA);
$field      = optional_param('field', '', PARAM_ALPHA);
$instanceid = optional_param('instanceId', 0, PARAM_INT);
$sectionid  = optional_param('sectionId', 0, PARAM_INT);
$beforeid   = optional_param('beforeId', 0, PARAM_INT);
$value      = optional_param('value', 0, PARAM_INT);
$column     = optional_param('column', 0, PARAM_ALPHA);
$id         = optional_param('id', 0, PARAM_INT);
$summary    = optional_param('summary', '', PARAM_RAW);
$sequence   = optional_param('sequence', '', PARAM_SEQUENCE);
$visible    = optional_param('visible', 0, PARAM_INT);
$pageaction = optional_param('action', '', PARAM_ALPHA); // Used to simulate a DELETE command
$title      = optional_param('title', '', PARAM_TEXT);

$PAGE->set_url('/course/rest.php', array('courseId'=>$courseid,'class'=>$class));

//NOTE: when making any changes here please make sure it is using the same access control as course/mod.php !!

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
// Check user is logged in and set contexts if we are dealing with resource
if (in_array($class, array('resource'))) {
    $cm = get_coursemodule_from_id(null, $id, $course->id, false, MUST_EXIST);
    require_login($course, false, $cm);
    $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    require_login($course);
}
$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
require_sesskey();

echo $OUTPUT->header(); // send headers

// OK, now let's process the parameters and do stuff
// MDL-10221 the DELETE method is not allowed on some web servers, so we simulate it with the action URL param
$requestmethod = $_SERVER['REQUEST_METHOD'];
if ($pageaction == 'DELETE') {
    $requestmethod = 'DELETE';
}

switch($requestmethod) {
    case 'POST':

        switch ($class) {
            case 'section':

                if (!$DB->record_exists('course_sections', array('course'=>$course->id, 'section'=>$id))) {
                    throw new moodle_exception('AJAX commands.php: Bad Section ID '.$id);
                }

                switch ($field) {
                    case 'visible':
                        require_capability('moodle/course:sectionvisibility', $coursecontext);
                        $resourcestotoggle = set_section_visible($course->id, $id, $value);
                        echo json_encode(array('resourcestotoggle' => $resourcestotoggle));
                        break;

                    case 'move':
                        require_capability('moodle/course:update', $coursecontext);
                        move_section_to($course, $id, $value);
                        // See if format wants to do something about it
                        $libfile = $CFG->dirroot.'/course/format/'.$course->format.'/lib.php';
                        $functionname = 'callback_'.$course->format.'_ajax_section_move';
                        if (!function_exists($functionname) && file_exists($libfile)) {
                            require_once $libfile;
                        }
                        if (function_exists($functionname)) {
                            echo json_encode($functionname($course));
                        }
                        break;
                }
                rebuild_course_cache($course->id);
                break;

            case 'resource':
                switch ($field) {
                    case 'visible':
                        require_capability('moodle/course:activityvisibility', $modcontext);
                        set_coursemodule_visible($cm->id, $value);
                        break;

                    case 'groupmode':
                        require_capability('moodle/course:manageactivities', $modcontext);
                        set_coursemodule_groupmode($cm->id, $value);
                        break;

                    case 'indent':
                        require_capability('moodle/course:manageactivities', $modcontext);
                        $cm->indent = $value;
                        if ($cm->indent >= 0) {
                            $DB->update_record('course_modules', $cm);
                        }
                        break;

                    case 'move':
                        require_capability('moodle/course:manageactivities', $modcontext);
                        if (!$section = $DB->get_record('course_sections', array('course'=>$course->id, 'section'=>$sectionid))) {
                            throw new moodle_exception('AJAX commands.php: Bad section ID '.$sectionid);
                        }

                        if ($beforeid > 0){
                            $beforemod = get_coursemodule_from_id('', $beforeid, $course->id);
                            $beforemod = $DB->get_record('course_modules', array('id'=>$beforeid));
                        } else {
                            $beforemod = NULL;
                        }

                        moveto_module($cm, $section, $beforemod);
                        break;
                    case 'gettitle':
                        require_capability('moodle/course:manageactivities', $modcontext);
                        $cm = get_coursemodule_from_id('', $id, 0, false, MUST_EXIST);
                        $module = new stdClass();
                        $module->id = $cm->instance;

                        // Don't pass edit strings through multilang filters - we need the entire string
                        echo json_encode(array('instancename' => $cm->name));
                        break;
                    case 'updatetitle':
                        require_capability('moodle/course:manageactivities', $modcontext);
                        $cm = get_coursemodule_from_id('', $id, 0, false, MUST_EXIST);
                        $module = new stdClass();
                        $module->id = $cm->instance;

                        // Escape strings as they would be by mform
                        if (!empty($CFG->formatstringstriptags)) {
                            $module->name = clean_param($title, PARAM_TEXT);
                        } else {
                            $module->name = clean_param($title, PARAM_CLEANHTML);
                        }

                        if (!empty($module->name)) {
                            $DB->update_record($cm->modname, $module);
                        } else {
                            $module->name = $cm->name;
                        }

                        // We need to return strings after they've been through filters for multilang
                        $stringoptions = new stdClass;
                        $stringoptions->context = $coursecontext;
                        echo json_encode(array('instancename' => format_string($module->name, true,  $stringoptions)));
                        break;
                }
                rebuild_course_cache($course->id);
                break;

            case 'course':
                switch($field) {
                    case 'marker':
                        require_capability('moodle/course:setcurrentsection', $coursecontext);
                        course_set_marker($course->id, $value);
                        break;
                }
                break;
        }
        break;

    case 'DELETE':
        switch ($class) {
            case 'resource':
                require_capability('moodle/course:manageactivities', $modcontext);
                $modlib = "$CFG->dirroot/mod/$cm->modname/lib.php";

                if (file_exists($modlib)) {
                    include_once($modlib);
                } else {
                    throw new moodle_exception("Ajax rest.php: This module is missing mod/$cm->modname/lib.php");
                }
                $deleteinstancefunction = $cm->modname."_delete_instance";

                // Run the module's cleanup funtion.
                if (!$deleteinstancefunction($cm->instance)) {
                    throw new moodle_exception("Ajax rest.php: Could not delete the $cm->modname $cm->name (instance)");
                    die;
                }

                // remove all module files in case modules forget to do that
                $fs = get_file_storage();
                $fs->delete_area_files($modcontext->id);

                if (!delete_course_module($cm->id)) {
                    throw new moodle_exception("Ajax rest.php: Could not delete the $cm->modname $cm->name (coursemodule)");
                }
                // Remove the course_modules entry.
                if (!delete_mod_from_section($cm->id, $cm->section)) {
                    throw new moodle_exception("Ajax rest.php: Could not delete the $cm->modname $cm->name from section");
                }

                // Trigger a mod_deleted event with information about this module.
                $eventdata = new stdClass();
                $eventdata->modulename = $cm->modname;
                $eventdata->cmid       = $cm->id;
                $eventdata->courseid   = $course->id;
                $eventdata->userid     = $USER->id;
                events_trigger('mod_deleted', $eventdata);

                rebuild_course_cache($course->id);

                add_to_log($courseid, "course", "delete mod",
                           "view.php?id=$courseid",
                           "$cm->modname $cm->instance", $cm->id);
                break;
        }
        break;
}
