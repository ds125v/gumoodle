<?php // $Id: edit.php,v 1.6.2.2 2008/05/15 10:33:07 agrabs Exp $
/**
* prints the form to edit the feedback items such moving, deleting and so on
*
* @version $Id: edit.php,v 1.6.2.2 2008/05/15 10:33:07 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");
    require_once('edit_form.php');

    $id = required_param('id', PARAM_INT);

    if(($formdata = data_submitted('nomatch')) AND !confirm_sesskey()) {
        error('no sesskey defined');
    }
    
    $do_show = optional_param('do_show', 'edit', PARAM_ALPHA);
    $moveupitem = optional_param('moveupitem', false, PARAM_INT);
    $movedownitem = optional_param('movedownitem', false, PARAM_INT);
    $moveitem = optional_param('moveitem', false, PARAM_INT);
    $movehere = optional_param('movehere', false, PARAM_INT);
    $switchitemrequired = optional_param('switchitemrequired', false, PARAM_INT);
    
    // $SESSION->feedback->current_tab = $do_show;
    $current_tab = $do_show;
 
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

    //move up/down items
    if($moveupitem){
        $item = get_record('feedback_item', 'id', $moveupitem);
        feedback_moveup_item($item);
    }
    if($movedownitem){
        $item = get_record('feedback_item', 'id', $movedownitem);
        feedback_movedown_item($item);
    }
    
    //moving of items
    if($movehere && isset($SESSION->feedback->moving->movingitem)){
        $item = get_record('feedback_item', 'id', intval($SESSION->feedback->moving->movingitem));
        feedback_move_item($item, intval($movehere));
    }
    if($moveitem){
        $item = get_record('feedback_item', 'id', $moveitem);
        $SESSION->feedback->moving->shouldmoving = 1;
        $SESSION->feedback->moving->movingitem = $moveitem;
    } else {
        unset($SESSION->feedback->moving);
    }
    
    if($switchitemrequired) {
        $item = get_record('feedback_item', 'id', $switchitemrequired);
        @feedback_switch_item_required($item);
        redirect($ME.'?'.feedback_edit_get_default_query($id, $do_show));
        exit;
    }
    
    //the create_template-form
    $create_template_form = new feedback_edit_create_template_form();
    $create_template_form->set_feedbackdata(array('capabilities' => $capabilities));
    $create_template_form->set_form_elements();
    $create_template_form->set_data(array('id'=>$id, 'do_show'=>'templates'));
    $create_template_formdata = $create_template_form->get_data();
    if(isset($create_template_formdata->savetemplate) && $create_template_formdata->savetemplate == 1) {
        //check the capabilities to create templates
        if(!$capabilities->createprivatetemplate AND !$capabilities->createpublictemplate) {
            error('saving templates is not allowed');
        }
        if(trim($create_template_formdata->templatename) == '')
        {
            $savereturn = 'notsaved_name';
        }else {
            if($capabilities->createpublictemplate) {
                $create_template_formdata->ispublic = isset($create_template_formdata->ispublic) ? 1 : 0;
            }else {
                $create_template_formdata->ispublic = 0;
            }
            if(!feedback_save_as_template($feedback, $create_template_formdata->templatename, $create_template_formdata->ispublic))
            {
                $savereturn = 'failed';
            }else {
                $savereturn = 'saved';
            }
        }
    }

    //get the feedbackitems
    $lastposition = 0;
    $feedbackitems = get_records('feedback_item', 'feedback', $feedback->id, 'position');
    if(is_array($feedbackitems)){
        $feedbackitems = array_values($feedbackitems);
        $lastitem = $feedbackitems[count($feedbackitems)-1];
        $lastposition = $lastitem->position;
    }
    $lastposition++;
    
    
    //the add_item-form
    $add_item_form = new feedback_edit_add_question_form('edit_item.php');
    $add_item_form->set_data(array('id'=>$id, 'position'=>$lastposition));

    //the use_template-form
    $use_template_form = new feedback_edit_use_template_form('use_templ.php');
    $use_template_form->set_feedbackdata(array('course' => $course));
    $use_template_form->set_form_elements();
    $use_template_form->set_data(array('id'=>$id));

    //the create_template-form
    //$create_template_form = new feedback_edit_create_template_form('use_templ.php');

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
	
    $savereturn=isset($savereturn)?$savereturn:'';
	  
    //print the messages
    if($savereturn == 'notsaved_name') {
        echo '<p align="center"><b><font color="red">'.get_string('name_required','feedback').'</font></b></p>';
    }

    if($savereturn == 'saved') {
        echo '<p align="center"><b><font color="green">'.get_string('template_saved','feedback').'</font></b></p>';
    }
    
    if($savereturn == 'failed') {
        echo '<p align="center"><b><font color="red">'.get_string('saving_failed','feedback').'</font></b></p>';
    }

    feedback_print_errors();
    
    ///////////////////////////////////////////////////////////////////////////
    ///print the template-section
    ///////////////////////////////////////////////////////////////////////////
    if($do_show == 'templates') {
        // print_simple_box_start("center", '80%');
        print_box_start('generalbox boxaligncenter boxwidthwide');
        $use_template_form->display();
        
        if($capabilities->createprivatetemplate OR $capabilities->createpublictemplate) {
            $create_template_form->display();
            echo '<p><a href="'.htmlspecialchars('delete_template.php?id='.$id).'">'.get_string('delete_templates', 'feedback').'</a></p>';
        }else {
            echo '&nbsp;';
        }

        if($capabilities->edititems) {
            echo '<p>
                <a href="'.htmlspecialchars('export.php?action=exportfile&id='.$id).'">'.get_string('export_questions', 'feedback').'</a>/
                <a href="'.htmlspecialchars('import.php?id='.$id).'">'.get_string('import_questions', 'feedback').'</a>
            </p>';
        }
        // print_simple_box_end();
        print_box_end();
    }
    ///////////////////////////////////////////////////////////////////////////
    ///print the Item-Edit-section
    ///////////////////////////////////////////////////////////////////////////
    if($do_show == 'edit') {
        
        $add_item_form->display();

        if(is_array($feedbackitems)){
            $itemnr = 0;
            
            $helpbutton = helpbutton('preview', get_string('preview','feedback'), 'feedback',true,false,'',true);
            
            print_heading($helpbutton . get_string('preview', 'feedback'));
            if(isset($SESSION->feedback->moving) AND $SESSION->feedback->moving->shouldmoving == 1) {
                print_heading('<a href="'.htmlspecialchars($ME.'?id='.$id).'">'.get_string('cancel_moving', 'feedback').'</a>');
            }
            // print_simple_box_start('center', '80%');
            print_box_start('generalbox boxaligncenter boxwidthwide');
            
            //check, if there exists required-elements
            $countreq = count_records('feedback_item', 'feedback', $feedback->id, 'required', 1);
            if($countreq > 0) {
                // echo '<font color="red">(*)' . get_string('items_are_required', 'feedback') . '</font>';
                echo '<span class="feedback_required_mark">(*)' . get_string('items_are_required', 'feedback') . '</span>';
            }
            
            echo '<table>';
            if(isset($SESSION->feedback->moving) AND $SESSION->feedback->moving->shouldmoving == 1) {
                $moveposition = 1;
                echo '<tr>'; //only shown if shouldmoving = 1
                    echo '<td>';
                    $buttonlink = $ME.'?'.htmlspecialchars(feedback_edit_get_default_query($id, $do_show).'&movehere='.$moveposition);
                    echo '<a title="'.get_string('move_here','feedback').'" href="'.$buttonlink.'">
                            <img class="movetarget" alt="'.get_string('move_here','feedback').'" src="'.$CFG->pixpath .'/movehere.gif" />
                          </a>';

                        // echo '<form action="'.$ME.'" method="post"><fieldset>';
                        // echo '<input title="'.get_string('move_here','feedback').'" type="image" src="'.$CFG->pixpath .'/movehere.gif" hspace="1" height="16" width="80" border="0" />';
                        // echo '<input type="hidden" name="movehere" value="'.$moveposition.'" />';
                        // feedback_edit_print_default_form_values($id, $do_show);
                        // echo '</fieldset></form>';
                    echo '</td>';
                echo '</tr>';
            }
            //print the inserted items
            $itempos = 0;
            foreach($feedbackitems as $feedbackitem){
                $itempos++;
                if(isset($SESSION->feedback->moving) AND $SESSION->feedback->moving->movingitem == $feedbackitem->id){ //hiding the item to move
                    continue;
                }
                echo '<tr>';
                //items without value only are labels
                if($feedbackitem->hasvalue == 1 AND $feedback->autonumbering) {
                    $itemnr++;
                    echo '<td valign="top">' . $itemnr . '.&nbsp;</td>';
                } else {
                    echo '<td>&nbsp;</td>';
                }
                if($feedbackitem->typ != 'pagebreak') {
                    feedback_print_item($feedbackitem, false, false, true);
                }else {
                    echo '<td class="feedback_pagebreak"><b>'.get_string('pagebreak', 'feedback').'</b></td><td><hr width="100%" size="8px" noshade="noshade" /></td>';
                }
                echo '<td>('.get_string('position', 'feedback').':'.$itempos .')</td>';
                echo '<td>';
                if($feedbackitem->position > 1){
                    $buttonlink = $ME.'?'.htmlspecialchars(feedback_edit_get_default_query($id, $do_show).'&moveupitem='.$feedbackitem->id);
                    echo '<a class="icon up" title="'.get_string('moveup_item','feedback').'" href="'.$buttonlink.'">
                            <img alt="'.get_string('moveup_item','feedback').'" src="'.$CFG->pixpath .'/t/up.gif" />
                          </a>';
                    //print the button to move-up the item
                    // echo '<form action="'.$ME.'" method="post"><fieldset>';
                    // ///////echo '<input title="'.get_string('moveup_item','feedback').'" type="image" src="'.$CFG->pixpath .'/t/up.gif" hspace="1" height="11" width="11" border="0" />';
                    // echo '<input class="feedback_moveup_button" title="'.get_string('moveup_item','feedback').'" type="image" src="'.$CFG->pixpath .'/t/up.gif" />';
                    // echo '<input type="hidden" name="moveupitem" value="'.$feedbackitem->id.'" />';
                    // feedback_edit_print_default_form_values($id, $do_show);
                    // echo '</fieldset></form>';
                }else{
                    echo '&nbsp;';
                }
                echo '</td>';
                echo '<td>';
                if($feedbackitem->position < $lastposition - 1){
                    $buttonlink = $ME.'?'.htmlspecialchars(feedback_edit_get_default_query($id, $do_show).'&movedownitem='.$feedbackitem->id);
                    echo '<a class="icon down" title="'.get_string('movedown_item','feedback').'" href="'.$buttonlink.'">
                            <img alt="'.get_string('movedown_item','feedback').'" src="'.$CFG->pixpath .'/t/down.gif" />
                          </a>';
                    //print the button to move-down the item
                    // echo '<form action="'.$ME.'" method="post"><fieldset>';
                    // echo '<input title="'.get_string('movedown_item','feedback').'" type="image" src="'.$CFG->pixpath .'/t/down.gif" hspace="1" height="11" width="11" border="0" />';
                    // echo '<input class="feedback_movedown_button" title="'.get_string('movedown_item','feedback').'" type="image" src="'.$CFG->pixpath .'/t/down.gif" />';
                    // echo '<input type="hidden" name="movedownitem" value="'.$feedbackitem->id.'" />';
                    // feedback_edit_print_default_form_values($id, $do_show);
                    // echo '</fieldset></form>';
                }else{
                    echo '&nbsp;';
                }
                echo '</td>';
                echo '<td>';
                    $buttonlink = $ME.'?'.htmlspecialchars(feedback_edit_get_default_query($id, $do_show).'&moveitem='.$feedbackitem->id);
                    echo '<a class="editing_move" title="'.get_string('move_item','feedback').'" href="'.$buttonlink.'">
                            <img alt="'.get_string('move_item','feedback').'" src="'.$CFG->pixpath .'/t/move.gif" />
                          </a>';
                    // echo '<form action="'.$ME.'" method="post"><fieldset>';
                    // echo '<input title="'.get_string('move_item','feedback').'" type="image" src="'.$CFG->pixpath .'/t/move.gif" hspace="1" height="11" width="11" border="0" />';
                    // echo '<input class="feedback_move_button" title="'.get_string('move_item','feedback').'" type="image" src="'.$CFG->pixpath .'/t/move.gif" />';
                    // echo '<input type="hidden" name="moveitem" value="'.$feedbackitem->id.'" />';
                    // feedback_edit_print_default_form_values($id, $do_show);
                    // echo '</fieldset></form>';
                echo '</td>';
                echo '<td>';
                //print the button to edit the item
                if($feedbackitem->typ != 'pagebreak') {
                    $buttonlink = 'edit_item.php?'.htmlspecialchars(feedback_edit_get_default_query($id, $do_show).'&itemid='.$feedbackitem->id.'&typ='.$feedbackitem->typ);
                    echo '<a class="editing_update" title="'.get_string('edit_item','feedback').'" href="'.$buttonlink.'">
                            <img alt="'.get_string('edit_item','feedback').'" src="'.$CFG->pixpath .'/t/edit.gif" />
                          </a>';
                    // echo '<form action="edit_item.php" method="post"><fieldset>';
                    // echo '<input title="'.get_string('edit_item','feedback').'" type="image" src="'.$CFG->pixpath .'/t/edit.gif" hspace="1" height="11" width="11" border="0" />';
                    // echo '<input class="feedback_edit_button" title="'.get_string('edit_item','feedback').'" type="image" src="'.$CFG->pixpath .'/t/edit.gif" />';
                    // echo '<input type="hidden" name="itemid" value="'.$feedbackitem->id.'" />';
                    // echo '<input type="hidden" name="typ" value="'.$feedbackitem->typ.'" />';
                    // feedback_edit_print_default_form_values($id, $do_show);
                    // echo '</fieldset></form>';
                }else {
                    echo '&nbsp;';
                }
                echo '</td>';
                echo '<td>';
                
                //print the toggle-button to switch required yes/no
                if($feedbackitem->hasvalue == 1) {
                    // echo '<form action="'.$ME.'" method="post"><fieldset>';
                    if($feedbackitem->required == 1) {
                        // echo '<input title="'.get_string('switch_item_to_not_required','feedback').'" type="image" src="pics/required.gif" hspace="1" height="11" width="11" border="0" />';
                        // echo '<input class="feedback_required_button" title="'.get_string('switch_item_to_not_required','feedback').'" type="image" src="pics/required.gif" />';
                        $buttontitle = get_string('switch_item_to_not_required','feedback');
                        $buttonimg = 'pics/required.gif';
                    } else {
                        // echo '<input title="'.get_string('switch_item_to_required','feedback').'" type="image" src="pics/notrequired.gif" hspace="1" height="11" width="11" border="0" />';
                        // echo '<input class="feedback_required_button" title="'.get_string('switch_item_to_required','feedback').'" type="image" src="pics/notrequired.gif" />';
                        $buttontitle = get_string('switch_item_to_required','feedback');
                        $buttonimg = 'pics/notrequired.gif';
                    }
                    $buttonlink = $ME.'?'.htmlspecialchars(feedback_edit_get_default_query($id, $do_show).'&switchitemrequired='.$feedbackitem->id);
                    echo '<a class="icon feedback_switchrequired" title="'.$buttontitle.'" href="'.$buttonlink.'">
                            <img alt="'.$buttontitle.'" src="'.$buttonimg.'" />
                          </a>';
                    // echo '<input type="hidden" name="switchitemrequired" value="'.$feedbackitem->id.'" />';
                    // feedback_edit_print_default_form_values($id, $do_show);
                    // echo '</fieldset></form>';
                }else {
                    echo '&nbsp;';
                }
                echo '</td>';
                echo '<td>';
                    $buttonlink = 'delete_item.php?'.htmlspecialchars(feedback_edit_get_default_query($id, $do_show).'&deleteitem='.$feedbackitem->id);
                    echo '<a class="icon delete" title="'.get_string('delete_item','feedback').'" href="'.$buttonlink.'">
                            <img alt="'.get_string('delete_item','feedback').'" src="'.$CFG->pixpath .'/t/delete.gif" />
                          </a>';
                //print the button to drop the item
                // echo '<form action="delete_item.php" method="post"><fieldset>';
                // echo '<input class="feedback_delete_button" title="'.get_string('delete_item','feedback').'" type="image" src="'.$CFG->pixpath .'/t/delete.gif" />';
                // echo '<input type="hidden" name="deleteitem" value="'.$feedbackitem->id.'" />';
                // feedback_edit_print_default_form_values($id, $do_show);
                // echo '</fieldset></form>';
                echo '</td>';
                echo '</tr>';
                if(isset($SESSION->feedback->moving) AND $SESSION->feedback->moving->shouldmoving == 1) {
                    $moveposition++;
                    echo '<tr>'; //only shown if shouldmoving = 1
                        echo '<td>';
                            $buttonlink = $ME.'?'.htmlspecialchars(feedback_edit_get_default_query($id, $do_show).'&movehere='.$moveposition);
                            echo '<a title="'.get_string('move_here','feedback').'" href="'.$buttonlink.'">
                                    <img class="movetarget" alt="'.get_string('move_here','feedback').'" src="'.$CFG->pixpath .'/movehere.gif" />
                                  </a>';
                            // echo '<form action="'.$ME.'" method="post"><fieldset>';
                            // echo '<input class="feedback_movehere_button" title="'.get_string('move_here','feedback').'" type="image" src="'.$CFG->pixpath .'/movehere.gif" />';
                            // echo '<input type="hidden" name="movehere" value="'.$moveposition.'" />';
                            // feedback_edit_print_default_form_values($id, $do_show);
                            // echo '</fieldset></form>';
                        echo '</td>';
                    echo '</tr>';
                }else {
                    echo '<tr><td>&nbsp;</td></tr>';
                }
                
            }
            echo '</table>';
            // print_simple_box_end();
            print_box_end();
        }else{
            // print_simple_box(get_string('no_items_available_yet','feedback'),"center");
            print_box(get_string('no_items_available_yet','feedback'),'generalbox boxaligncenter');
        }
    }
    /// Finish the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    print_footer($course);

    function feedback_edit_get_default_query($id, $tab) {
        global $USER;
        
        $query = 'id='.$id;
        $query .= '&do_show='.$tab;
        //$query .= '&sesskey='.$USER->sesskey;
        
        return $query;
    }

    function feedback_edit_print_default_form_values($id, $tab) {
        global $USER;
        
        echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
        echo '<input type="hidden" name="id" value="'.$id.'" />';
        echo '<input type="hidden" name="do_show" value="'.$tab.'" />';
    }
?>
