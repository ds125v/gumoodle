<?php // $Id: show_entries.php,v 1.6.2.6 2009/06/13 13:07:15 agrabs Exp $
/**
* print the single entries
*
* @version $Id: show_entries.php,v 1.6.2.6 2009/06/13 13:07:15 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");
    
    ////////////////////////////////////////////////////////
    //get the params
    ////////////////////////////////////////////////////////
    $id = required_param('id', PARAM_INT);
    $userid = optional_param('userid', false, PARAM_INT);
    $do_show = required_param('do_show', PARAM_ALPHA);
    // $SESSION->feedback->current_tab = $do_show;
    $current_tab = $do_show;

    ////////////////////////////////////////////////////////
    //get the objects
    ////////////////////////////////////////////////////////
    
    if($userid) {
        $formdata->userid = intval($userid);
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

    require_login($course->id, true, $cm);
    
    if(($formdata = data_submitted('nomatch')) AND !confirm_sesskey()) {
        error('no sesskey defined');
    }
    
    if(!$capabilities->viewreports){
        error(get_string('error'));
    }

    ////////////////////////////////////////////////////////
    //get the responses of given user
    ////////////////////////////////////////////////////////
    if($do_show == 'showoneentry') {
        //get the feedbackitems
        $feedbackitems = get_records('feedback_item', 'feedback', $feedback->id, 'position');
        $feedbackcompleted = get_record_select('feedback_completed','feedback='.$feedback->id.' AND userid='.$formdata->userid.' AND anonymous_response='.FEEDBACK_ANONYMOUS_NO); //arb
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
                 
    include('tabs.php');

    /// Print the main part of the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////
    /// Print the links to get responses and analysis
    ////////////////////////////////////////////////////////
    if($do_show == 'showentries'){
        //print the link to analysis
        if($capabilities->viewreports) {
            //get the effective groupmode of this course and module
            $groupmode = groupmode($course, $cm);
            // $mygroupid = 0;
            $groupselect = groups_print_activity_menu($cm, 'show_entries.php?id=' . $cm->id.'&do_show=showentries', true);
            $mygroupid = groups_get_activity_group($cm);
            
            //get students in conjunction with groupmode
            if($groupmode > 0) {

                if($mygroupid > 0) {
                    $students = feedback_get_complete_users($cm, $mygroupid);
                } else {
                    $students = feedback_get_complete_users($cm);
                }
            }else {
                $students = feedback_get_complete_users($cm);
            }

            $completedFeedbackCount = feedback_get_completeds_group_count($feedback, $mygroupid);
            if($feedback->course == SITEID){
                echo '<div align="center"><a href="'.htmlspecialchars('analysis_course.php?id=' . $id . '&courseid='.$courseid).'">';
                echo get_string('course') .' '. get_string('analysis', 'feedback') . ' ('.get_string('completed_feedbacks', 'feedback').': '.intval($completedFeedbackCount).')</a>';
                helpbutton('viewcompleted', '', 'feedback', true, true);
                echo '</div>';
            }else {
                echo '<div align="center"><a href="'.htmlspecialchars('analysis.php?id=' . $id . '&courseid='.$courseid).'">';
                echo get_string('analysis', 'feedback') . ' ('.get_string('completed_feedbacks', 'feedback').': '.intval($completedFeedbackCount).')</a>';
                echo '</div>';
            }
        }
    
        //####### viewreports-start
        if($capabilities->viewreports) {
            //print the list of students
            // print_simple_box_start('center', '80%');
            // echo '<div align="center">';
            print_box_start('generalbox boxaligncenter boxwidthwide');
            echo isset($groupselect) ? $groupselect : '';
            echo '<div class="clearer"></div>';
            echo '<table><tr><td width="400">';
            if (!$students) {
                if($courseid != SITEID){
                    notify(get_string('noexistingstudents'));
                }
            } else{
                echo print_string('non_anonymous_entries', 'feedback');
                // echo ' ('.count_records_select('feedback_completed', 'feedback = ' . $feedback->id.' AND anonymous_response='.FEEDBACK_ANONYMOUS_NO).')<hr />';
                echo ' ('.count($students).')<hr />';

                foreach ($students as $student){
                    $completedCount = count_records_select('feedback_completed', 'userid = ' . $student->id . ' AND feedback = ' . $feedback->id.' AND anonymous_response='.FEEDBACK_ANONYMOUS_NO);
                    if($completedCount > 0) {
                     // Are we assuming that there is only one response per user? Should westep through a feedbackcompleteds? I added the addition anonymous check to the select so that only non-anonymous submissions are retrieved. 
                        $feedbackcompleted = get_record_select('feedback_completed','feedback='.$feedback->id.' AND userid='.$student->id.' AND anonymous_response='.FEEDBACK_ANONYMOUS_NO);
                    ?>
                        <table width="100%">
                            <tr>
                                <td align="left">
                                    <?php echo print_user_picture($student->id, $course->id, $student->picture, false, true);?>
                                </td>
                                <td align="left">
                                    <?php echo fullname($student);?>
                                </td>
                                <td align="right">
                                <?php
                                    $show_button_link = $ME;
                                    $show_button_options = array('sesskey'=>$USER->sesskey, 'userid'=>$student->id, 'do_show'=>'showoneentry', 'id'=>$id);
                                    $show_button_label = get_string('show_entries', 'feedback');
                                    print_single_button($show_button_link, $show_button_options, $show_button_label, 'post');
                                ?>
                                </td>
                    <?php
                        if($capabilities->deletesubmissions) {
                    ?>
                                <td align="right">
                                <?php
                                    $delete_button_link = 'delete_completed.php';
                                    $delete_button_options = array('sesskey'=>$USER->sesskey, 'completedid'=>$feedbackcompleted->id, 'do_show'=>'showoneentry', 'id'=>$id);
                                    $delete_button_label = get_string('delete_entry', 'feedback');
                                    print_single_button($delete_button_link, $delete_button_options, $delete_button_label, 'post');
                                ?>
                                </td>
                    <?php
                        }
                    ?>
                            </tr>
                        </table>
                    <?php
                    }
                }
            }
    ?>
            <hr />
            <table width="100%">
                <tr>
                    <td align="left" colspan="2">
                        <?php print_string('anonymous_entries', 'feedback');?>&nbsp;(<?php echo count_records_select('feedback_completed', 'feedback = ' . $feedback->id.' AND anonymous_response='.FEEDBACK_ANONYMOUS_YES);?>)
                    </td>
                    <td align="right">
                        <?php
                            $show_anon_button_link = 'show_entries_anonym.php';
                            $show_anon_button_options = array('sesskey'=>$USER->sesskey, 'userid'=>0, 'do_show'=>'showoneentry', 'id'=>$id);
                            $show_anon_button_label = get_string('show_entries', 'feedback');
                            print_single_button($show_anon_button_link, $show_anon_button_options, $show_anon_button_label, 'post');
                        ?>
                    </td>
                </tr>
            </table> 
    <?php
            echo '</td></tr></table>';
            // print_simple_box_end();
            print_box_end();
        }
    
    }
    ////////////////////////////////////////////////////////
    /// Print the responses of the given user
    ////////////////////////////////////////////////////////
    if($do_show == 'showoneentry') {
        print_heading(format_text($feedback->name));
        
        //print the items
        if(is_array($feedbackitems)){
            $usr = get_record('user', 'id', $formdata->userid);
            if($feedbackcompleted) {
                echo '<p align="center">'.UserDate($feedbackcompleted->timemodified).'<br />('.fullname($usr).')</p>';
            } else {
                echo '<p align="center">'.get_string('not_completed_yet','feedback').'</p>';
            }
            // print_simple_box_start("center", '50%');
            print_box_start('generalbox boxaligncenter boxwidthnormal');
            echo '<form>';
            echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
            echo '<table width="100%">';
            $itemnr = 0;
            foreach($feedbackitems as $feedbackitem){
                //get the values
                $value = get_record_select('feedback_value','completed ='.$feedbackcompleted->id.' AND item='.$feedbackitem->id);
                echo '<tr>';
                if($feedbackitem->hasvalue == 1 AND $feedback->autonumbering) {
                    $itemnr++;
                    echo '<td valign="top">' . $itemnr . '.&nbsp;</td>';
                } else {
                    echo '<td>&nbsp;</td>';
                }
                
                if($feedbackitem->typ != 'pagebreak') {
                    if(isset($value->value)) {
                        feedback_print_item($feedbackitem, $value->value, true);
                    }else {
                        feedback_print_item($feedbackitem, false, true);
                    }
                }else {
                    echo '<td><hr /></td>';
                }
                echo '</tr>';
            }
            echo '<tr><td colspan="2" align="center">';
            echo '</td></tr>';
            echo '</table>';
            echo '</form>';
            // print_simple_box_end();
            print_box_end();
        }
        print_continue(htmlspecialchars('show_entries.php?id='.$id.'&do_show=showentries'));
    }
    /// Finish the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    print_footer($course);

?>
