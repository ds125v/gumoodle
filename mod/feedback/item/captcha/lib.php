<?PHP  // $Id: lib.php,v 1.4.2.3 2008/06/20 20:54:36 agrabs Exp $
defined('FEEDBACK_INCLUDE_TEST') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/feedback_item_class.php');

class feedback_item_captcha extends feedback_item_base {
    var $type = "captcha";
    function init() {
    
    }
    
    function &show_edit($item) {
        global $CFG;
        
        require_once('captcha_form.php');
        
        $item_form = new feedback_captcha_form();

        $item->presentation = empty($item->presentation) ? 3 : $item->presentation;
        $item->name = empty($item->name) ? '' : htmlspecialchars(stripslashes_safe($item->name));
        
        $item->required = isset($item->required) ? $item->required : 1;
        if($item->required) {
            $item_form->requiredcheck->setValue(true);
        }

        $item_form->itemname->setValue($item->name);
        
        $item_form->select->setValue($item->presentation);
        return $item_form;
    }

    //liefert eine Struktur ->name, ->data = array(mit Antworten)
    function get_analysed($item, $groupid = false, $courseid = false) {
        return null;
    }

    function get_printval($item, $value) {
        return '';
    }

    function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
        return $itemnr;
    }

    function excelprint_item(&$worksheet, $rowOffset, $item, $groupid, $courseid = false) {
        return $rowOffset;
    }

    function print_item($item, $value = false, $readonly = false, $edit = false, $highlightrequire = false){
        global $SESSION, $CFG;
        
        $align = get_string('thisdirection') == 'ltr' ? 'left' : 'right';
        
        $presentation = $item->presentation;
        $SESSION->feedback->item->captcha->charcount = $presentation;
        
        $cmid = 0;
        if(!$readonly) {
            $feedbackid = $item->feedback;
            if($feedbackid > 0) {
                $feedback = get_record('feedback', 'id', $feedbackid);
                if($cm = get_coursemodule_from_instance("feedback", $feedback->id, $feedback->course)) {
                    $cmid = $cm->id;
                }
            }
        }
        
        if(isset($SESSION->feedback->item->captcha->checked)) {
            $checked = $SESSION->feedback->item->captcha->checked == true;
            unset($SESSION->feedback->item->captcha->checked);
        }else {
            $checked = false;
        }
        
        //check if an false value even the value is not required
        if(!$readonly AND !$item->required AND $value != '' AND $SESSION->feedback->item->captcha->checkchar != $value) {
            $falsevalue = true;
        }else {
            $falsevalue = false;
        }
        
        if(($highlightrequire AND $item->required AND !$checked) OR $falsevalue) {
            $highlight = 'bgcolor="#FFAAAA" class="missingrequire"';
        }else {
            $highlight = '';
        }
        $requiredmark = ($item->required == 1)?'<span class="feedback_required_mark">*</span>':'';
    ?>
        <td <?php echo $highlight;?> valign="top" align="<?php echo $align;?>">
            <?php echo format_text(stripslashes_safe($item->name) . $requiredmark, true, false, false);?>
            <img alt="<?php echo $this->type;?>" src="<?php echo $CFG->wwwroot.htmlspecialchars('/mod/feedback/item/captcha/print_captcha.php?id='.$cmid);?>" />
        </td>
        <td valign="top" align="<?php echo $align;?>">
    <?php
        if($readonly){
            // print_simple_box_start($align);
            print_box_start('generalbox boxalign'.$align);
            echo $value?$value:'&nbsp;';
            // print_simple_box_end();
            print_box_end();
        }else {
    ?>
            <input type="text" name="<?php echo $item->typ . '_' . $item->id;?>"
                                    size="<?php echo $presentation;?>"
                                    maxlength="<?php echo $presentation;?>"
                                    value="" />
    <?php
        }
    ?>
        </td>
    <?php
    }

    function check_value($value, $item) {
        global $SESSION;
        //if the item is not required, so the check is true if no value is given
        if((!isset($value) OR $value == '') AND $item->required != 1) return true;
        if($SESSION->feedback->item->captcha->checkchar == $value) {
            $SESSION->feedback->item->captcha->checked = true;
            return true;
        }
        return false;
    }

    function create_value($data) {
        $data = addslashes(clean_text($data));
        return $data;
    }

    function get_presentation($data) {
        return $data->count_of_nums;
    }

    function get_hasvalue() {
        return 1;
    }
}
?>
