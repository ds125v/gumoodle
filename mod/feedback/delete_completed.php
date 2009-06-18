<?php // $Id: delete_completed.php,v 1.4.2.2 2008/05/15 10:33:06 agrabs Exp $
/**
* prints the form to confirm the deleting of a completed
*
* @version $Id: delete_completed.php,v 1.4.2.2 2008/05/15 10:33:06 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");
    require_once('delete_completed_form.php');

    $id = required_param('id', PARAM_INT);
    $completedid = optional_param('completedid', 0, PARAM_INT);

    if($completedid == 0){
        error(get_string('no_complete_to_delete', 'feedback'), 'show_entries.php?id='.$id.'&do_show=showentries');
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
    
    if(!$capabilities->deletesubmissions){
        error(get_string('error'));
    }
    
    $mform = new mod_feedback_delete_completed_form();
    $newformdata = array('id'=>$id,
                        'completedid'=>$completedid,
                        'confirmdelete'=>'1',
                        'do_show'=>'edit');
    $mform->set_data($newformdata);
    $formdata = $mform->get_data();
    
    if ($mform->is_cancelled()) {
        redirect('show_entries.php?id='.$id.'&do_show=showentries');
    }
    
    if(isset($formdata->confirmdelete) AND $formdata->confirmdelete == 1){
        if($completed = get_record('feedback_completed', 'id', $completedid)) {
            feedback_delete_completed($completedid);
            add_to_log($course->id, 'feedback', 'delete', 'view.php?id='.$cm->id, $feedback->id,$cm->id);
            redirect('show_entries.php?id='.$id.'&do_show=showentries');
        }
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

    /// Print the main part of the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    print_heading(format_text($feedback->name));
    // print_simple_box_start("center", "60%", "#FFAAAA", 20, "noticebox");
    print_box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');
    print_heading(get_string('confirmdeleteentry', 'feedback'));
    $mform->display();
    // print_simple_box_end();
    print_box_end();
        

    print_footer($course);

?>
