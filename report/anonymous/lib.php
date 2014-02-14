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
 * This file contains functions used by the participation report
 *
 * @package    report
 * @subpackage anonymous
 * @copyright  2013 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_anonymous_extend_navigation_course($navigation, $course, $context) {
    global $CFG, $OUTPUT;

    // Must have rights to view this course and to see one of the 'assignment' types.
    $capmods = has_capability('mod/turnitintool:grade', $context) || has_capability('mod/assign:grade', $context);
    if (has_capability('report/anonymous:view', $context) && $capmods) {
        $url = new moodle_url('/report/anonymous/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_anonymous'), $url,
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function report_anonymous_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = array(
        '*'                          => get_string('page-x', 'pagetype'),
        'report-*'                   => get_string('page-report-x', 'pagetype'),
        'report-anonymous-*'     => get_string('page-report-anonymous-x',  'report_anonymous'),
        'report-anonymous-index' => get_string('page-report-anonymous-index',  'report_anonymous'),
    );
    return $array;
}
