<?php // $Id: index.php,v 0.2 2009/02/21 matbury Exp $
/**
 * This page lists all the instances of flv in a particular course
 *
 * @author Matt Bury - matbury@gmail.com - http://matbury.com/
 * @version $Id: index.php,v 0.2 2009/02/21 matbury Exp $
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

/// Replace flv with the name of your module

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "flv", "view all", "index.php?id=$course->id", "");


/// Get all required stringsflv

    $strflvs = get_string("modulenameplural", "flv");
    $strflv  = get_string("modulename", "flv");


/// Print the header

    $navlinks = array();
    $navlinks[] = array('name' => $strflvs, 'link' => '', 'type' => 'activity');
    //$navigation = build_navigation($navlinks);
	
	//print_header_simple("$strflvs", "", $navigation, "", "", true, "", navmenu($course));
    print_header_simple("$strflvs", "", 'flv', "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $flvs = get_all_instances_in_course("flv", $course)) {
        notice("There are no flvs", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", "left", "left", "left");
    } else {
        $table->head  = array ($strname);
        $table->align = array ("left", "left", "left");
    }

    foreach ($flvs as $flv) {
        if (!$flv->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$flv->coursemodule\">$flv->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$flv->coursemodule\">$flv->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($flv->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
