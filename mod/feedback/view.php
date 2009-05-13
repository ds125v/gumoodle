<?php // $Id: view.php,v 1.6.2.6 2008/11/16 20:46:25 agrabs Exp $
/**
* the first page to view the feedback
*
* @version $Id: view.php,v 1.6.2.6 2008/11/16 20:46:25 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/
    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);
    $courseid = optional_param('courseid', false, PARAM_INT);
    
    // $SESSION->feedback->current_tab = 'view';
    $current_tab = 'view';

    if ($id) {
        if (! $cm = get_coursemodule_from_id('feedback', $id)) {
            error("Course Module ID was incorrect");
        }
     
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
     
        if (! $feedback = get_record("feedback", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
    }

    $capabilities = feedback_load_capabilities($cm->id);

    if($feedback->anonymous == FEEDBACK_ANONYMOUS_YES AND !$capabilities->edititems) {
        $capabilities->complete = true;
    }
    
    //check whether the feedback is located and! started from the mainsite
    if($course->id == SITEID AND !$courseid) {
        $courseid = SITEID;
    }

    //check whether the feedback is mapped to the given courseid
    if($course->id == SITEID AND !$capabilities->edititems) {
        if(get_records('feedback_sitecourse_map', 'feedbackid', $feedback->id)) {
            if(!get_record('feedback_sitecourse_map', 'feedbackid', $feedback->id, 'courseid', $courseid)){
                error("this feedback is not available");
            }
        }
    }
    
    if($feedback->anonymous != FEEDBACK_ANONYMOUS_YES) {
        if($course->id == SITEID) {
            require_login($course->id, true);
        }else {
            require_login($course->id, true, $cm);
        }
    } else {
        if($course->id == SITEID) {
            require_course_login($course, true);
        }else {
            require_course_login($course, true, $cm);
        }
    }
    
    //check whether the given courseid exists
    if($courseid AND $courseid != SITEID) {
        if($course2 = get_record('course', 'id', $courseid)){
            require_course_login($course2); //this overwrites the object $course :-(
            $course = get_record("course", "id", $cm->course); // the workaround
        }else {
            error("courseid is not correct");
        }
    }

    if($feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
        add_to_log($course->id, 'feedback', 'view', 'view.php?id='.$cm->id, $feedback->id,$cm->id);
    }

    /// Print the page header
    $strfeedbacks = get_string("modulenameplural", "feedback");
    $strfeedback  = get_string("modulename", "feedback");
    $buttontext = update_module_button($cm->id, $course->id, $strfeedback);
    
    $navlinks = array();
    $navlinks[] = array('name' => $strfeedbacks, 'link' => "index.php?id=$course->id", 'type' => 'activity');
    $navlinks[] = array('name' => format_string($feedback->name), 'link' => "", 'type' => 'activityinstance');
    
    $navigation = build_navigation($navlinks);
    
    print_header_simple(format_string($feedback->name), "",
                 $navigation, "", "", true, $buttontext, navmenu($course, $cm));

    //ishidden check.
    //feedback in courses
    if ((empty($cm->visible) and !$capabilities->viewhiddenactivities) AND $course->id != SITEID) {
        notice(get_string("activityiscurrentlyhidden"));
    }

    //ishidden check.
    //feedback on mainsite
    if ((empty($cm->visible) and !$capabilities->viewhiddenactivities) AND $courseid == SITEID) {
        notice(get_string("activityiscurrentlyhidden"));
    }

    /// Print the main part of the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    /// print the tabs
    include('tabs.php');

    print_heading(format_text($feedback->name));

    // print_simple_box_start('center', '80%');
    print_box_start('generalbox boxaligncenter boxwidthwide');
    echo format_text($feedback->summary);
    // print_simple_box_end();
    print_box_end();
    
    if($capabilities->edititems) {
        print_heading(get_string("page_after_submit", "feedback"), '', 4);
        // print_simple_box_start('center', '80%');
        print_box_start('generalbox boxaligncenter boxwidthwide');
        echo format_text($feedback->page_after_submit);
        // print_simple_box_end();
        print_box_end();
    }
    
    if( (intval($feedback->publish_stats) == 1) AND !( $capabilities->viewreports) ) {
        if($multiple_count = count_records('feedback_tracking', 'userid', $USER->id, 'feedback', $feedback->id)) {
            echo '<div align="center"><a href="'.htmlspecialchars('analysis.php?id=' . $id . '&courseid='.$courseid).'">';
            echo get_string('completed_feedbacks', 'feedback').'</a>';
            echo '</div>';
        }
    }
    echo '<p>';

    //####### mapcourse-start
    if($capabilities->mapcourse) {
        if($feedback->course == SITEID) {
            // print_simple_box_start('center', '80%');
            print_box_start('generalbox boxaligncenter boxwidthwide');
            echo '<div align="center">';
            echo '<form action="mapcourse.php" method="get">';
            echo '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'" />';
            echo '<input type="hidden" name="id" value="'.$id.'" />';
            echo '<button type="submit">'.get_string('mapcourses', 'feedback').'</button>';
            helpbutton('mapcourse', '', 'feedback', true, true);
            echo '</form>';
            echo '<br />';
            echo '</div>';
            // print_simple_box_end();
            print_box_end();
        }
    }
    //####### mapcourse-end

    //####### completed-start
    if($capabilities->complete AND !$capabilities->edititems) {
        // print_simple_box_start('center', '80%');
        print_box_start('generalbox boxaligncenter boxwidthwide');
        //check, whether the feedback is open (timeopen, timeclose)
        $checktime = time();
        if(($feedback->timeopen > $checktime) OR ($feedback->timeclose < $checktime AND $feedback->timeclose > 0)) {
            // print_simple_box_start('center');
            print_box_start('generalbox boxaligncenter');
                echo '<h2><font color="red">'.get_string('feedback_is_not_open', 'feedback').'</font></h2>';
                print_continue($CFG->wwwroot.'/course/view.php?id='.$course->id);
            // print_simple_box_end();
            print_box_end();
            print_footer($course);
            exit;
        }
        
        //check multiple Submit
        $feedback_can_submit = true;
        if($feedback->multiple_submit == 0 ) {
            if(feedback_is_already_submitted($feedback->id, $courseid)) {
                $feedback_can_submit = false;
            }
        }
        if($feedback_can_submit) {
            //if the user is not known so we cannot save the values temporarly
            if(!isset($USER->username) OR $USER->username == 'guest') {
                $completefile = 'complete_guest.php';
                $guestid = $USER->sesskey;
            }else {
                $completefile = 'complete.php';
                $guestid = false;
            }
            if($feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $courseid, $guestid)) {
                if($startpage = feedback_get_page_to_continue($feedback->id, $courseid, $guestid)) {
                    echo '<a href="'.htmlspecialchars($completefile.'?id='.$id.'&gopage='.$startpage.'&courseid='.$courseid).'">'.get_string('continue_the_form', 'feedback').'</a>';
                }else {
                    echo '<a href="'.htmlspecialchars($completefile.'?id='.$id.'&gopage=0&courseid='.$courseid).'">'.get_string('continue_the_form', 'feedback').'</a>';
                }
            }else {
                echo '<a href="'.htmlspecialchars($completefile.'?id='.$id.'&gopage=0&courseid='.$courseid).'">'.get_string('complete_the_form', 'feedback').'</a>';
            }
        }else {
            echo '<h2><font color="red">'.get_string('this_feedback_is_already_submitted', 'feedback').'</font></h2>';
            if($courseid) {
                print_continue($CFG->wwwroot.'/course/view.php?id='.$courseid);
            }else {
                print_continue($CFG->wwwroot.'/course/view.php?id='.$course->id);
            }
        }
        // print_simple_box_end();
        print_box_end();
    }
    //####### completed-end
    echo "</p>";

    /// Finish the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    print_footer($course);

?>
