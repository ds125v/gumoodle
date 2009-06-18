<?php // $Id: delete_item.php,v 1.3.2.2 2008/05/15 10:33:07 agrabs Exp $
/**
* deletes an item of the feedback
*
* @version $Id: delete_item.php,v 1.3.2.2 2008/05/15 10:33:07 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/
    
    require_once("../../config.php");
    require_once("lib.php");
    require_once('delete_item_form.php');

    $id = required_param('id', PARAM_INT);
    $deleteitem = required_param('deleteitem', PARAM_INT);

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
    
    if(!$capabilities->edititems){
        error(get_string('error'));
    }
                
    $mform = new mod_feedback_delete_item_form();
    $newformdata = array('id'=>$id,
                        'deleteitem'=>$deleteitem,
                        'confirmdelete'=>'1');
    $mform->set_data($newformdata);
    $formdata = $mform->get_data();
    
    if ($mform->is_cancelled()) {
        redirect('edit.php?id='.$id);
    }
    
    if(isset($formdata->confirmdelete) AND $formdata->confirmdelete == 1){
        feedback_delete_item($formdata->deleteitem);
        redirect('edit.php?id=' . $id);
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
    print_heading(get_string('confirmdeleteitem', 'feedback'));
    print_string('relateditemsdeleted','feedback');
    $mform->display();
    // print_simple_box_end();
    print_box_end();
        
    print_footer($course);

?>
