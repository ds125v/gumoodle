<?php // $Id: use_templ.php,v 1.4.2.2 2008/05/15 10:33:08 agrabs Exp $
/**
* print the confirm dialog to use template and create new items from template
*
* @version $Id: use_templ.php,v 1.4.2.2 2008/05/15 10:33:08 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");
    require_once('use_templ_form.php');

    $id = required_param('id', PARAM_INT); 
    $templateid = optional_param('templateid', false, PARAM_INT);
    $deleteolditems = optional_param('deleteolditems', 0, PARAM_INT);
   
    if(!$templateid) {
        redirect('edit.php?id='.$id);
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
    
    if(!$capabilities->edititems){
        error(get_string('error'));
    }
    
    $mform = new mod_feedback_use_templ_form();
    $newformdata = array('id'=>$id,
                        'templateid'=>$templateid,
                        'confirmadd'=>'1',
                        'deleteolditems'=>'1',
                        'do_show'=>'edit');
    $mform->set_data($newformdata);
    $formdata = $mform->get_data();
    
    if ($mform->is_cancelled()) {
        redirect('edit.php?id='.$id.'&do_show=templates');
    }
    
    if(isset($formdata->confirmadd) AND $formdata->confirmadd == 1){
        feedback_items_from_template($feedback, $templateid, $deleteolditems);
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
    print_heading(get_string('confirmusetemplate', 'feedback'));
    
    $mform->display();

    // print_simple_box_end();
    print_box_end();

    $templateitems = get_records('feedback_item', 'template', $templateid, 'position');
    if(is_array($templateitems)){
        $templateitems = array_values($templateitems);
    }

    if(is_array($templateitems)){
        $itemnr = 0;
        echo '<p align="center">'.get_string('preview', 'feedback').'</p>';
        // print_simple_box_start('center', '75%');
        print_box_start('generalbox boxaligncenter boxwidthwide');
        echo '<div align="center"><table>';
        foreach($templateitems as $templateitem){
            echo '<tr>';
            if($templateitem->hasvalue == 1 AND $feedback->autonumbering) {
                $itemnr++;
                echo '<td valign="top">' . $itemnr . '.&nbsp;</td>';
            } else {
                echo '<td>&nbsp;</td>';
            }
            if($templateitem->typ != 'pagebreak') {
                feedback_print_item($templateitem);
            }else {
                echo '<td><hr /></td><td>'.get_string('pagebreak', 'feedback').'</td>';
            }
            echo '</tr>';
            echo '<tr><td>&nbsp;</td></tr>';
        }
        echo '</table></div>';
        // print_simple_box_end();
        print_box_end();
    }else{
        // print_simple_box(get_string('no_items_available_at_this_template','feedback'),"center");
        print_box(get_string('no_items_available_at_this_template','feedback'),'generalbox boxaligncenter boxwidthwide');
    }

    print_footer($course);

?>
