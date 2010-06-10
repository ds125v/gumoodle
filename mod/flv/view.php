<?php  // $Id: view.php,v 0.2 2009/02/21 matbury Exp $
/**
 * This page prints a particular instance of flv
 *
 * @author Matt Bury - matbury@gmail.com
 * @version $Id: view.php,v 0.2 2009/02/21 matbury Exp $
 * @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
 * @package flv
 **/
 
/**    Copyright (C) 2009  Matt Bury
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

	require_once("../../config.php");
    require_once("lib.php");
	
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // flv ID
	
    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $flv = get_record("flv", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $flv = get_record("flv", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $flv->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("flv", $flv->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "flv", "view", "view.php?id=$cm->id", "$flv->id");

/// Print the page header
    $strflvs = get_string("modulenameplural", "flv");
    $strflv  = get_string("modulename", "flv");
	
	// Print Javascript head code that embeds SWF file using SWFObject. If SWFObject fails
	// for some reason, the standard <embed> and <object> HTML code should work.
	// The "flv_print_header_js()" function is in mod/flv/lib.php
	$navigation = build_navigation(get_string('flv', 'flv').': '.$flv->name, $id);
    print_header_simple(format_string($flv->name), "", $navigation, "", flv_print_header_js($flv), true, update_module_button($cm->id, $course->id, $strflv), navmenu($course, $cm));
	
	// Everything between the <div id="myAlternativeContent"> tags is 
	// overwritten by SWFObject.
	echo flv_print_body($flv); // mod/flv/lib.php
	
/// Finish the page
    print_footer($course);
	
// End of mod/flv/view.php
?>
