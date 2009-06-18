<?php // $Id: index.php,v 1.4.2.3 2008/05/15 10:33:07 agrabs Exp $
/**
* prints the overview of all feedbacks included into the current course
*
* @version $Id: index.php,v 1.4.2.3 2008/05/15 10:33:07 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }
    $capabilities = feedback_load_course_capabilities($course->id);

    require_login($course->id);

    add_to_log($course->id, 'feedback', 'view all', htmlspecialchars('index.php?id='.$course->id), $course->id);


    /// Print the page header
    $strfeedbacks = get_string("modulenameplural", "feedback");
    $strfeedback  = get_string("modulename", "feedback");
    
    $navlinks = array();
    $navlinks[] = array('name' => $strfeedbacks, 'link' => "", 'type' => 'activity');
    
    $navigation = build_navigation($navlinks);
    
    print_header_simple(get_string('modulename', 'feedback').' '.get_string('activities'), "",
                 $navigation, "", "", true, null, navmenu($course));

    /// Get all the appropriate data

    if (! $feedbacks = get_all_instances_in_course("feedback", $course)) {
        notice("There are no feedbacks", htmlspecialchars('../../course/view.php?id='.$course->id));
        die;
    }

    /// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");
    $strresponses = get_string('responses', 'feedback');

    if ($course->format == "weeks") {
        if($capabilities->viewreports) {
            $table->head  = array ($strweek, $strname, $strresponses);
            $table->align = array ("center", "left", 'center');
        }else{
            $table->head  = array ($strweek, $strname);
            $table->align = array ("center", "left");
        }
    } else if ($course->format == "topics") {
        if($capabilities->viewreports) {
            $table->head  = array ($strtopic, $strname, $strresponses);
            $table->align = array ("center", "left", "center");
        }else{
            $table->head  = array ($strtopic, $strname);
            $table->align = array ("center", "left");
        }
    } else {
        if($capabilities->viewreports) {
            $table->head  = array ($strname, $strresponses);
            $table->align = array ("left", "center");
        }else{
            $table->head  = array ($strname);
            $table->align = array ("left");
        }
    }

    
    foreach ($feedbacks as $feedback) {
        //get the responses of each feedback

        if($capabilities->viewreports) {
            $completedFeedbackCount = intval(feedback_get_completeds_group_count($feedback));
        }
        
        if (!$feedback->visible) {
            //Show dimmed if the mod is hidden
            $link = '<a class="dimmed" href="'.htmlspecialchars('view.php?id='.$feedback->coursemodule).'">'.$feedback->name.'</a>';
        } else {
            //Show normal if the mod is visible
            $link = '<a href="'.htmlspecialchars('view.php?id='.$feedback->coursemodule).'">'.$feedback->name.'</a>';
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $tabledata = array ($feedback->section, $link);
        } else {
            $tabledata = array ($link);
        }
        if($capabilities->viewreports) {
            $tabledata[] = $completedFeedbackCount;
        }
        
        $table->data[] = $tabledata;
        
    }

    echo "<br />";

    print_table($table);

    /// Finish the page

    print_footer($course);

?>
