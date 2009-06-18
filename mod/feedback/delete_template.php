<?php // $Id: delete_template.php,v 1.5.2.1 2008/05/15 10:33:07 agrabs Exp $
/**
* deletes a template
*
* @version $Id: delete_template.php,v 1.5.2.1 2008/05/15 10:33:07 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");
    require_once('delete_template_form.php');
    
    // $SESSION->feedback->current_tab = 'templates';
    $current_tab = 'templates';

    $id = required_param('id', PARAM_INT);
    $canceldelete = optional_param('canceldelete', false, PARAM_INT);
    $shoulddelete = optional_param('shoulddelete', false, PARAM_INT);
    $deletetempl = optional_param('deletetempl', false, PARAM_INT);
    // $formdata = data_submitted('nomatch');
    
    if(($formdata = data_submitted('nomatch')) AND !confirm_sesskey()) {
        error('no sesskey defined');
    }
    
    if($canceldelete == 1){
        redirect(htmlspecialchars('edit.php?id='.$id.'&do_show=templates'));
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
    
    if(!$capabilities->deletetemplate){
        error(get_string('error'));
    }
    
    $mform = new mod_feedback_delete_template_form();
    $newformdata = array('id'=>$id,
                        'deletetempl'=>$deletetempl,
                        'confirmdelete'=>'1');
    
    $mform->set_data($newformdata);
    $formdata = $mform->get_data();
    
    if ($mform->is_cancelled()) {
        redirect(htmlspecialchars('delete_template.php?id='.$id));
    }
    
    if(isset($formdata->confirmdelete) AND $formdata->confirmdelete == 1){
        feedback_delete_template($formdata->deletetempl);
        redirect(htmlspecialchars('delete_template.php?id=' . $id));
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

    /// print the tabs
    include('tabs.php');
    
    /// Print the main part of the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    print_heading(get_string('delete_template','feedback'));
    if($shoulddelete == 1) {
    
        // print_simple_box_start("center", "60%", "#FFAAAA", 20, "noticebox");
        print_box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');
        print_heading(get_string('confirmdeletetemplate', 'feedback'));
        $mform->display();
        // print_simple_box_end();
        print_box_end();
    }else {
        $templates = feedback_get_template_list($course, true);
        echo '<div align="center">';
        if(!is_array($templates)) {
            // print_simple_box(get_string('no_templates_available_yet', 'feedback'), "center");
            print_box(get_string('no_templates_available_yet', 'feedback'), 'generalbox boxaligncenter');
        }else {
            echo '<table width="30%">';
            echo '<tr><th>'.get_string('templates', 'feedback').'</th><th>&nbsp;</th></tr>';
            foreach($templates as $template) {
                echo '<tr><td align="center">'.$template->name.'</td>';
                echo '<td align="center">';
                echo '<form action="'.$ME.'" method="post">';
                echo '<input title="'.get_string('delete_template','feedback').'" type="image" src="'.$CFG->pixpath .'/t/delete.gif" hspace="1" height="11" width="11" border="0" />';
                echo '<input type="hidden" name="deletetempl" value="'.$template->id.'" />';
                echo '<input type="hidden" name="shoulddelete" value="1" />';
                echo '<input type="hidden" name="id" value="'.$id.'" />';
                echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
                echo '</form>';
                echo '</td></tr>';
            }
            echo '</table>';
        }
?>
        <form name="frm" action="<?php echo $ME;?>" method="post">
            <input type="hidden" name="sesskey" value="<?php echo $USER->sesskey;?>" />
            <input type="hidden" name="id" value="<?php echo $id;?>" />
            <input type="hidden" name="canceldelete" value="0" />
            <button type="button" onclick="this.form.canceldelete.value=1;this.form.submit();"><?php print_string('cancel');?></button>
        </form>
        </div>
<?php
    }

    print_footer($course);

?>
