<?php // $Id: complete.php,v 1.5.2.7 2008/11/16 20:46:25 agrabs Exp $
/**
* prints the form so the user can fill out the feedback
*
* @version $Id: complete.php,v 1.5.2.7 2008/11/16 20:46:25 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);
    $completedid = optional_param('completedid', false, PARAM_INT);
    $preservevalues  = optional_param('preservevalues', 0,  PARAM_INT);
    $courseid = optional_param('courseid', false, PARAM_INT);
    $gopage = optional_param('gopage', -1, PARAM_INT);
    $lastpage = optional_param('lastpage', false, PARAM_INT);
    $startitempos = optional_param('startitempos', 0, PARAM_INT);
    $lastitempos = optional_param('lastitempos', 0, PARAM_INT);
    $anonymous_response = optional_param('anonymous_response', 0, PARAM_INT); //arb

    $highlightrequired = false;

    if(($formdata = data_submitted('nomatch')) AND !confirm_sesskey()) {
        error('no sesskey defined');
    }

    //if the use hit enter into a textfield so the form should not submit
    if(isset($formdata->sesskey) AND !isset($formdata->savevalues) AND !isset($formdata->gonextpage) AND !isset($formdata->gopreviouspage)) {
        $gopage = $formdata->lastpage;
    }

    if(isset($formdata->savevalues)) {
        $savevalues = true;
    }else {
        $savevalues = false;
    }
    
    if($gopage < 0 AND !$savevalues) {
        if(isset($formdata->gonextpage)){
            $gopage = $lastpage + 1;
            $gonextpage = true;
            $gopreviouspage = false;
        }else if(isset($formdata->gopreviouspage)){
            $gopage = $lastpage - 1;
            $gonextpage = false;
            $gopreviouspage = true;
        }else {
            error('parameter (gopage) required');
        }
    }else {
        $gonextpage = $gopreviouspage = false;
    }
    

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
    
    if($feedback->anonymous == FEEDBACK_ANONYMOUS_YES) {
        $capabilities->complete = true;
    }
    
    //check whether the feedback is located and! started from the mainsite
    if($course->id == SITEID AND !$courseid) {
        $courseid = SITEID;
    }
        
    //check whether the feedback is mapped to the given courseid
    if($course->id == SITEID) {
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
    
    if(!$capabilities->complete) {
        error(get_string('error'));
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


    feedback_print_errors();
  
    //check, if the feedback is open (timeopen, timeclose)
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
    
    //additional check for multiple-submit (prevent browsers back-button). the main-check is in view.php
    $feedback_can_submit = true;
    if($feedback->multiple_submit == 0 ) {
        if(feedback_is_already_submitted($feedback->id, $courseid)) {
            $feedback_can_submit = false;
        }
    }
    if($feedback_can_submit) {
        //preserving the items
        if($preservevalues == 1){
            if(!$SESSION->feedback->is_started == true)error('error', $CFG->wwwroot.'/course/view.php?id='.$course->id);
            //checken, ob alle required items einen wert haben
            if(feedback_check_values($_POST, $startitempos, $lastitempos)) {
                    $userid = $USER->id; //arb
                if($completedid = feedback_save_values($_POST, $USER->id, true)){
                    if($userid > 0) {
                        add_to_log($course->id, 'feedback', 'startcomplete', 'view.php?id='.$cm->id, $feedback->id, $cm->id, $userid);
                    }
                    if(!$gonextpage AND !$gopreviouspage) $preservevalues = false;//es kann gespeichert werden
                    
                }else {
                    $savereturn = 'failed';
                    if(isset($lastpage)) {
                        $gopage = $lastpage;
                    }else {
                        error('parameter failed');
                    }
                }
            }else {
                $savereturn = 'missing';
                $highlightrequired = true;
                if(isset($lastpage)) {
                    $gopage = $lastpage;
                }else {
                    error('parameter failed');
                }
            
            }
        }
        
        //saving the items
        if($savevalues AND !$preservevalues){
            //exists there any pagebreak, so there are values in the feedback_valuetmp
            $userid = $USER->id; //arb

            if($feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
                $feedbackcompleted = feedback_get_current_completed($feedback->id, false, $courseid);
            }else{
                $feedbackcompleted = false;
            }
            $feedbackcompletedtmp = get_record('feedback_completedtmp', 'id', $completedid);
            //fake saving for switchrole
            $is_switchrole = feedback_check_is_switchrole();
            if($is_switchrole) {
                $savereturn = 'saved';
                feedback_delete_completedtmp($completedid);
            }else if($new_completed_id = feedback_save_tmp_values($feedbackcompletedtmp, $feedbackcompleted, $userid)) {
                $savereturn = 'saved';
                if($feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
                    add_to_log($course->id, 'feedback', 'submit', 'view.php?id='.$cm->id, $feedback->id, $cm->id, $userid);
                    feedback_send_email($cm, $feedback, $course, $userid);
                }else {
                    feedback_send_email_anonym($cm, $feedback, $course, $userid);
                }
                //tracking the submit
                $multiple_count = null;
                $multiple_count->userid = $USER->id;
                $multiple_count->feedback = $feedback->id;
                $multiple_count->completed = $new_completed_id;
                $multiple_count->count = 1;
                insert_record('feedback_tracking', $multiple_count);
                unset($SESSION->feedback->is_started);
                
            }else {
                $savereturn = 'failed';
            }
           
        }


        if($allbreaks = feedback_get_all_break_positions($feedback->id)){
            if($gopage <= 0) {
                $startposition = 0;
            }else {
                $startposition = $allbreaks[$gopage - 1];
            }
            $ispagebreak = true;
        }else {
            $startposition = 0;
            $newpage = 0;
            $ispagebreak = false;
        }
        
        //get the feedbackitems after the last shown pagebreak
        $feedbackitems = get_records_select('feedback_item', 'feedback = '.$feedback->id.' AND position > '.$startposition, 'position');
        
        //get the first pagebreak
        if($pagebreaks = get_records_select('feedback_item', "feedback = ".$feedback->id." AND typ = 'pagebreak'", 'position')) {
            $pagebreaks = array_values($pagebreaks);
            $firstpagebreak = $pagebreaks[0];
        }else {
            $firstpagebreak = false;
        }
        $maxitemcount = count_records('feedback_item', 'feedback', $feedback->id);
        
        //get the values of completeds before done. Anonymous user can not get these values.
        if((!isset($SESSION->feedback->is_started)) AND (!isset($savereturn)) AND ($feedback->anonymous == FEEDBACK_ANONYMOUS_NO)) {
            if(!$feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $courseid)) {
                if($feedbackcompleted = feedback_get_current_completed($feedback->id, false, $courseid)) {
                    //copy the values to feedback_valuetmp create a completedtmp
                    $feedbackcompletedtmp = feedback_set_tmp_values($feedbackcompleted);
                }
            }
        }else {
            $feedbackcompletedtmp = feedback_get_current_completed($feedback->id, true, $courseid);
        }

        /// Print the main part of the page
        ///////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////
        print_heading(format_text($feedback->name));
        
        if(isset($savereturn) && $savereturn == 'saved') {
            if($feedback->page_after_submit) {
                // print_simple_box_start('center', '75%');
                print_box_start('generalbox boxaligncenter boxwidthwide');
                echo format_text(stripslashes_safe($feedback->page_after_submit));
                // print_simple_box_end();
                print_box_end();
            } else {
                echo '<p align="center"><b><font color="green">'.get_string('entries_saved','feedback').'</font></b></p>';
                if( intval($feedback->publish_stats) == 1) {
                    echo '<p align="center"><a href="analysis.php?id=' . $id . '&courseid='.$courseid.'">';
                    echo get_string('completed_feedbacks', 'feedback').'</a>';
                    echo '</p>';
                }
            }
            if($feedback->site_after_submit) {
                print_continue(feedback_encode_target_url($feedback->site_after_submit));
            }else {
                if($courseid) {
                    print_continue($CFG->wwwroot.'/course/view.php?id='.$courseid);
                }else {
                    if($course->id == SITEID) {
                        print_continue($CFG->wwwroot);
                    } else {
                        print_continue($CFG->wwwroot.'/course/view.php?id='.$course->id);
                    }
                }
            }
        }else {
            if(isset($savereturn) && $savereturn == 'failed') {
                echo '<p align="center"><b><font color="red">'.get_string('saving_failed','feedback').'</font></b></p>';
            }
    
            if(isset($savereturn) && $savereturn == 'missing') {
                echo '<p align="center"><b><font color="red">'.get_string('saving_failed_because_missing_or_false_values','feedback').'</font></b></p>';
            }
    
            //print the items
            if(is_array($feedbackitems)){
                // print_simple_box_start('center', '75%');
                print_box_start('generalbox boxaligncenter boxwidthwide');
                echo '<div align="center"><form name="frm" action="'.$ME.'" method="post" onsubmit=" ">';
                echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
                echo '<table>';
                switch ($feedback->anonymous) {
                    case FEEDBACK_ANONYMOUS_YES:
                        echo '<tr><th colspan="3" align="center"><input type="hidden" name="anonymous" value="1" /><input type="hidden" name="anonymous_response" value="'.FEEDBACK_ANONYMOUS_YES.'" />'.get_string('mode', 'feedback').': '.get_string('anonymous', 'feedback').'</th></tr>'; //arb
                        break;
                    case FEEDBACK_ANONYMOUS_NO:
                        echo '<tr><th colspan="3" align="center"><input type="hidden" name="anonymous" value="0" /><input type="hidden" name="anonymous_response" value="'.FEEDBACK_ANONYMOUS_NO.'" />'.get_string('mode', 'feedback').': '.get_string('non_anonymous', 'feedback').'&nbsp;</td></tr>'; //arb
                        break;
                }
                //check, if there exists required-elements
                $countreq = count_records('feedback_item', 'feedback', $feedback->id, 'required', 1);
                if($countreq > 0) {
                    echo '<tr><td colspan="3"><font color="red">(*)' . get_string('items_are_required', 'feedback') . '</font></td></tr>';
                }
                
                unset($startitem);
                $itemnr = count_records_select('feedback_item', 'feedback = '. $feedback->id . ' AND hasvalue = 1 AND position < '.$startposition);
                foreach($feedbackitems as $feedbackitem){
                    if(!isset($startitem)) {
                        //avoid showing double pagebreaks
                        if($feedbackitem->typ == 'pagebreak') continue;
                        $startitem = $feedbackitem;
                    }
                    $value = '';
                    //get the value
                    $frmvaluename = $feedbackitem->typ . '_'. $feedbackitem->id;
                    if(isset($savereturn)) {
                        $value =  isset($formdata->{$frmvaluename})?$formdata->{$frmvaluename}:NULL;
                    }else {
                        if(isset($feedbackcompletedtmp->id)) {
                            $value = feedback_get_item_value($feedbackcompletedtmp->id, $feedbackitem->id, true);
                        }
                    }
                    echo '<tr>';
                    if($feedbackitem->hasvalue == 1 AND $feedback->autonumbering) {
                        $itemnr++;
                        echo '<td valign="top">' . $itemnr . '.&nbsp;</td>';
                    } else {
                        echo '<td>&nbsp;</td>';
                    }
                    if($feedbackitem->typ != 'pagebreak') {
                        feedback_print_item($feedbackitem, $value, false, false, $highlightrequired);
                    }
                    echo '</tr>';
                    echo '<tr><td>&nbsp;</td></tr>';
                    
                    $lastbreakposition = $feedbackitem->position; //last item-pos (item or pagebreak)
                    if($feedbackitem->typ == 'pagebreak'){
                        break;
                    }else {
                        $lastitem = $feedbackitem;
                    }
                }
                echo '</table>';
                echo '<input type="hidden" name="id" value="'.$id.'" />';
                echo '<input type="hidden" name="feedbackid" value="'.$feedback->id.'" />';
                echo '<input type="hidden" name="lastpage" value="'.$gopage.'" />';
                echo '<input type="hidden" name="completedid" value="'.(isset($feedbackcompletedtmp->id)?$feedbackcompletedtmp->id:'').'" />';
                echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
                echo '<input type="hidden" name="preservevalues" value="1" />';
                if(isset($startitem)) {
                    echo '<input type="hidden" name="startitempos" value="'. $startitem->position . '" />';
                    echo '<input type="hidden" name="lastitempos" value="'. $lastitem->position . '" />';
                }
                
                if( $ispagebreak AND $lastbreakposition > $firstpagebreak->position) {
                    echo '<input name="gopreviouspage" type="submit" value="'.get_string('previous_page','feedback').'" />';
                }
                if($lastbreakposition < $maxitemcount){
                    echo '<input name="gonextpage" type="submit" value="'.get_string('next_page','feedback').'" />';
                }
                if($lastbreakposition >= $maxitemcount) { //last page
                    echo '<input name="savevalues" type="submit" value="'.get_string('save_entries','feedback').'" />';
                }
                
                echo '</form>';
                
                    if($courseid) {
                        echo '<form name="frm" action="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'" method="post" onsubmit=" ">';
                    }else{
                        if($course->id == SITEID) {
                            echo '<form name="frm" action="'.$CFG->wwwroot.'" method="post" onsubmit=" ">';
                        } else {
                            echo '<form name="frm" action="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'" method="post" onsubmit=" ">';
                        }
                    }
                echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
                echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
                echo '<button type="submit">'.get_string('cancel').'</button>';
                echo '</form>';
                echo '</div>';
                $SESSION->feedback->is_started = true;
                // print_simple_box_end();
                print_box_end();
            }
        }
    }else {
        // print_simple_box_start('center');
        print_box_start('generalbox boxaligncenter');
            echo '<h2><font color="red">'.get_string('this_feedback_is_already_submitted', 'feedback').'</font></h2>';
            print_continue($CFG->wwwroot.'/course/view.php?id='.$course->id);
        // print_simple_box_end();
        print_box_end();
    }
    /// Finish the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    print_footer($course);

?>
