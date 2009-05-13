<?php // $Id: import.php,v 1.6.2.2 2008/06/13 21:43:02 agrabs Exp $
/**
* prints the form to import items from xml-file
*
* @version $Id: import.php,v 1.6.2.2 2008/06/13 21:43:02 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");
    
    // get parameters
    $id = required_param('id', PARAM_INT);
    $choosefile = optional_param('choosefile', false, PARAM_PATH);
    $action = optional_param('action', false, PARAM_ALPHA);

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
        error('this action is not allowed');
    }
    
    unset($filename);
    if ($action == 'choosefile' AND confirm_sesskey() ) { 

        // file checks out ok
        $fileisgood = false;

        // work out if this is an uploaded file 
        // or one from the filesarea.
        if ($choosefile) {
            $filename = "{$CFG->dataroot}/{$course->id}/{$choosefile}";
        }
    }
    
    // process if we are happy file is ok
    if (isset($filename)) { 
        if(!is_file($filename) OR !is_readable($filename)) {
            error('file not found or not readable');
        }
        if(!$xmldata = feedback_load_xml_data($filename)) {
            error('failed to loading xml', 'edit.php?id='.$id);
        }
        
        $importerror = feedback_import_loaded_data($xmldata, $feedback->id);
        if($importerror->stat == true) {
            redirect('edit.php?id='.$id.'&do_show=templates', get_string('import_successfully', 'feedback'), 3);
            exit;
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
    print_heading(get_string('import_questions','feedback'));
    
    if(isset($importerror->msg) AND is_array($importerror->msg)) {
        // print_simple_box_start('center', '', '#FFAAAA', 5, 'notifyproblem');
        print_box_start('generalbox errorboxcontent boxaligncenter');
        foreach($importerror->msg as $msg) {
            echo $msg.'<br />';
        }
        // print_simple_box_end();
        print_box_end();
    }
    
    ?>

     <form name="form" method="post" action="import.php">
          <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />
          <input type="hidden" name="action" value="choosefile" />
          <input type="hidden" name="id" value="<?php p($id);?>" />
          <input type="hidden" name="do_show" value="templates" />
          <?php print_box_start('generalbox boxaligncenter boxwidthwide'); ?>
          <input type="radio" name="deleteolditems" value="1" checked="checked" /> <?php echo get_string('delete_old_items', 'feedback').' ('.get_string('oldvalueswillbedeleted','feedback').')';?><br />
          <input type="radio" name="deleteolditems" value="0" /> <?php echo get_string('append_new_items', 'feedback').' ('.get_string('oldvaluespreserved','feedback').')';?><br />
          <table cellpadding="5">
                <tr>
                     <td align="right"><?php print_string('file', 'feedback'); ?>:</td>
                     <td><input type="text" name="choosefile" size="50" /></td>
                </tr>

                <tr>
                     <td>&nbsp;</td>
                     <td><?php  button_to_popup_window ("/files/index.php?id={$course->id}&amp;choose=form.choosefile", 
                          "coursefiles", get_string('choosefile', 'feedback'), 500, 750, get_string('choosefile', 'feedback')); ?>
                          <input type="submit" name="save" value="<?php print_string('importfromthisfile', 'feedback'); ?>" />
                    </td>
                </tr>
          </table>
          <?php 
          print_box_end(); ?>
     </form>

     <?php

    echo '<div align="center">';
    print_single_button('edit.php', array('id'=>$id, 'do_show'=>'templates'), get_string('cancel'));
    echo '</div>';
    print_footer($course);
  
    function feedback_load_xml_data($filename) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/xmlize.php');
    
        $datei =  file_get_contents($filename);
        
        if(!$datei = feedback_check_xml_utf8($datei)) return false;
        
        $data = xmlize($datei, 1, 'UTF-8');
        
        if(intval($data['FEEDBACK']['@']['VERSION']) != 200701) {
            return false;
        }
        $data = $data['FEEDBACK']['#']['ITEMS'][0]['#']['ITEM'];
        return $data;
    }
    
    function feedback_import_loaded_data(&$data, $feedbackid){
        global $CFG;
        
        $deleteolditems = optional_param('deleteolditems', 0, PARAM_INT);
        
        $error = new object();
        $error->stat = true;
        $error->msg = array();
        
        if(!is_array($data)) {
            $error->msg[] = get_string('data_is_not_an_array', 'feedback');
            $error->stat = false;
            return $error;
        }
        
        if($deleteolditems) {
            feedback_delete_all_items($feedbackid);
            $position = 0;
        } else {
            //items will be add to the end of the existing items
            $position = count_records('feedback_item', 'feedback', $feedbackid);
        }
        
        foreach($data as $item) {
            $position++;
            //check the typ
            $typ = $item['@']['TYPE'];
            
            //check oldtypes first
            switch($typ) {
                case 'radio':
                    $typ = 'multichoice';
                    $oldtyp = 'radio';
                    break;
                case 'dropdown':
                    $typ = 'multichoice';
                    $oldtyp = 'dropdown';
                    break;
                case 'check':
                    $typ = 'multichoice';
                    $oldtyp = 'check';
                    break;
                case 'radiorated':
                    $typ = 'multichoicerated';
                    $oldtyp = 'radiorated';
                    break;
                case 'dropdownrated':
                    $typ = 'multichoicerated';
                    $oldtyp = 'dropdownrated';
                    break;
                default:
                    $oldtyp = $typ;
            }

            $itemclass = 'feedback_item_'.$typ;
            if($typ != 'pagebreak' AND !class_exists($itemclass)) {
                $error->stat = false;
                $error->msg[] = 'type ('.$typ.') not found';
                continue;
            }
            $itemobj = new $itemclass();
            
            $newitem = new object();
            $newitem->feedback = $feedbackid;
            $newitem->template = 0;
            $newitem->typ = $typ;
            $newitem->name = trim($item['#']['ITEMTEXT'][0]['#']);
            $newitem->presentation = trim($item['#']['PRESENTATION'][0]['#']);
            //check old types of radio, check, and so on
            switch($oldtyp) {
                case 'radio':
                    $newitem->presentation = 'r>>>>>'.$newitem->presentation;
                    break;
                case 'dropdown':
                    $newitem->presentation = 'd>>>>>'.$newitem->presentation;
                    break;
                case 'check':
                    $newitem->presentation = 'c>>>>>'.$newitem->presentation;
                    break;
                case 'radiorated':
                    $newitem->presentation = 'r>>>>>'.$newitem->presentation;
                    break;
                case 'dropdownrated':
                    $newitem->presentation = 'd>>>>>'.$newitem->presentation;
                    break;
            }
            
            if($typ != 'pagebreak') {
                $newitem->hasvalue = $itemobj->get_hasvalue();
            }else {
                $newitem->hasvalue = 0;
            }
            $newitem->required = intval($item['@']['REQUIRED']);
            $newitem->position = $position;
            if(!insert_record('feedback_item', $newitem)) {
                $error->stat = false;
                $error->msg[] = 'item ('.$newitem->name.') not imported';
            }
        }
        return $error;
    }

    function feedback_check_xml_utf8($text) {
        //find the encoding
        $searchpattern = '/^\<\?xml.+(encoding=\"([a-z0-9-]*)\").+\?\>/is';
        
        if(!preg_match($searchpattern, $text, $match)) return false; //no xml-file
        
        //$match[0] = \<\? xml ... \?\> (without \)
        //$match[1] = encoding="...."
        //$match[2] = ISO-8859-1 or so on
        if(isset($match[0]) AND !isset($match[1])){ //no encoding given. we assume utf-8
            return $text;
        }
        
        if(isset($match[0]) AND isset($match[1]) AND isset($match[2])) { //encoding is given in $match[2]
            $enc = $match[2];
            $textlib = textlib_get_instance();
            return $textlib->convert($text, $enc);
        }
    }
?>
