<?php // $Id: edit_item.php,v 1.6.2.5 2008/06/08 21:15:57 agrabs Exp $
/**
* prints the form to edit a dedicated item
*
* @version $Id: edit_item.php,v 1.6.2.5 2008/06/08 21:15:57 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', NULL, PARAM_INT);
    $typ = optional_param('typ', false, PARAM_ALPHA);
    $itemid = optional_param('itemid', false, PARAM_INT);
    
    if(!$typ)redirect(htmlspecialchars('edit.php?id=' . $id));

    // set up some general variables
    $usehtmleditor = can_use_html_editor(); 


    if(($formdata = data_submitted('nomatch')) AND !confirm_sesskey()) {
        error('no sesskey defined');
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

    //if the typ is pagebreak so the item will be saved directly
    if($typ == 'pagebreak') {
        feedback_create_pagebreak($feedback->id);
        redirect(htmlspecialchars('edit.php?id='.$id));
        exit;
    }
    
    //get the existing item or create it
    // $formdata->itemid = isset($formdata->itemid) ? $formdata->itemid : NULL;
    if($itemid and $item = get_record('feedback_item', 'id', $itemid)){
        $typ = $item->typ;
        $position = $item->position;
    }else {
        $position = -1;
        $item = new stdClass();
        if ($position == '')$position = 0;
        if(!$typ)error('missing value "typ"', htmlspecialchars('edit.php?id='.$id));
    }

    ////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////
    if(isset($formdata->cancel)){
        redirect(htmlspecialchars('edit.php?id=' . $id));
    }

    // if(isset($formdata->editcancel) AND $formdata->editcancel == 1){
        // redirect(htmlspecialchars('edit.php?id=' . $id));
    // }
    
    if(isset($formdata->saveitem) AND $formdata->saveitem == 1){
        $newposition = $formdata->position;
        $formdata->position = $newposition + 1;

        if (!$newitemid = feedback_create_item($formdata)) {
            $SESSION->feedback->errors[] = get_string('item_creation_failed', 'feedback');
        }else {
            $newitem = get_record('feedback_item', 'id', $newitemid);
            if (!feedback_move_item($newitem, $newposition)){
                $SESSION->feedback->errors[] = get_string('item_creation_failed', 'feedback');
            }else {
                redirect(htmlspecialchars('edit.php?id='.$id));
            }            
        }
    }
    
    if(isset($formdata->updateitem) AND $formdata->updateitem == 1){
        //update the item and go back
        if (!feedback_update_item($item, $formdata)) {
            $SESSION->feedback->errors[] = get_string('item_update_failed', 'feedback');
        } else {
            if (!feedback_move_item($item, $formdata->position)){
                $SESSION->feedback->errors[] = get_string('item_update_failed', 'feedback');
            }else {
                redirect(htmlspecialchars('edit.php?id='.$id));
            } 
        }
    }
    ////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////

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
    print_heading(format_text($feedback->name));
  
          
    //print errormsg
    if(isset($error)){echo $error;}

    feedback_print_errors();
    
    //new formdefinition
    $itemclass = 'feedback_item_'.$typ;
    $itemobj = new $itemclass();
    $item_form = &$itemobj->show_edit($item);

    $i_form = &$item_form->_form;
    // $i_form->addElement('header', 'general', 'Titel');
    $i_form->addElement('hidden', 'id', $id);
    $i_form->addElement('hidden', 'itemid', isset($item->id)?$item->id:'');
    $i_form->addElement('hidden', 'typ', $typ);
    $i_form->addElement('hidden', 'feedbackid', $feedback->id);
    

    $lastposition = count_records('feedback_item', 'feedback', $feedback->id);    
    if($position == -1){
        $i_formselect_last = $lastposition + 1;
        $i_formselect_value = $lastposition + 1;
    }else {
        $i_formselect_last = $lastposition;
        $i_formselect_value = $item->position;
    }
    
    $numlist = array();
    for($i = 1; $i <= $i_formselect_last; $i++) {
        $numlist[$i] = $i;
    }
    $i_formselect = &$i_form->addElement('select',
                                        'position', 
                                        get_string('position', 'feedback').'&nbsp;', 
                                        $numlist);
    $i_formselect->setValue($i_formselect_value);
    
    $buttonarray = array();
    if(!empty($item->id)){
        $i_form->addElement('hidden', 'updateitem', '1');
        // $i_form->addElement('submit', 'update_item', get_string('update_item', 'feedback'));
        $buttonarray[] = &$i_form->createElement('submit', 'update_item', get_string('update_item', 'feedback'));
    }else{
        $i_form->addElement('hidden', 'saveitem', '1');
        // $i_form->addElement('submit', 'save_item', get_string('save_item', 'feedback'));
        $buttonarray[] = &$i_form->createElement('submit', 'save_item', get_string('save_item', 'feedback'));
    }
    // $i_form->addElement('cancel');
    $buttonarray[] = &$i_form->createElement('cancel');
    $i_form->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    $item_form->display();

/*            
    // print_simple_box_start('center');
    print_box_start('generalbox boxwidthwide boxaligncenter');
        echo '<form action="'.$ME.'" method="post">';
        echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
    
    //this div makes the buttons stand side by side
    echo '<div>';
    $itemclass = 'feedback_item_'.$typ;
    $itemobj = new $itemclass();
    $itemobj->show_edit($item, $usehtmleditor);
    echo '</div>';        
        echo '<input type="hidden" name="id" value="'.$id.'" />';
        echo '<input type="hidden" name="itemid" value="'.(isset($item->id)?$item->id:'').'" />';
        echo '<input type="hidden" name="typ" value="'.$typ.'" />';
        echo '<input type="hidden" name="feedbackid" value="'.$feedback->id.'" />';
    
    //choose the position
    $lastposition = count_records('feedback_item', 'feedback', $feedback->id);
    echo get_string('position', 'feedback').'&nbsp;';
    echo '<select name="position">';
        //Dropdown-Items for choosing the position
        if($position == -1){
            feedback_print_numeric_option_list(1, $lastposition + 1, $lastposition + 1);
        }else {
            feedback_print_numeric_option_list(1, $lastposition, $item->position);
        }
    echo '</select><hr />';
    
    //////////////////////////////////////////////////////////////////////////////////////        
    //////////////////////////////////////////////////////////////////////////////////////        
    if(!empty($item->id)){
        echo '<input type="hidden" id="updateitem" name="updateitem" value="1" />';
        echo '<input type="submit" value ="'.get_string('update_item', 'feedback').'" />';
    }else{
        echo '<input type="hidden" id="saveitem" name="saveitem" value="1" />';
        echo '<input type="submit" value="'.get_string('save_item', 'feedback').'" />';
    }
    echo '<input type="submit" name="cancel" value="'.get_string('cancel').'" />';
    echo '</form>';
    //////////////////////////////////////////////////////////////////////////////////////        
    //////////////////////////////////////////////////////////////////////////////////////
*/
    // print_simple_box_end();
    // print_box_end();
  
    if ($typ!='label') {
        echo '<script language="javascript">';
        echo 'document.getElementById("itemname").focus()';
        echo '</script>';
    } 

    /// Finish the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    print_footer($course);

?>
