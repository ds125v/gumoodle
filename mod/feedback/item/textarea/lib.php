<?php  // $Id: lib.php,v 1.6.2.6 2008/06/20 20:54:37 agrabs Exp $
defined('FEEDBACK_INCLUDE_TEST') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/feedback_item_class.php');

class feedback_item_textarea extends feedback_item_base {
    var $type = "textarea";
    function init() {
    
    }
    
    function &show_edit($item) {
        global $CFG;

        require_once('textarea_form.php');
        
        $item_form = new feedback_textarea_form();

        $item->presentation = empty($item->presentation) ? '' : $item->presentation;
        $item->name = empty($item->name) ? '' : htmlspecialchars(stripslashes_safe($item->name));
              
        $item->required = isset($item->required) ? $item->required : 0;
        if($item->required) {
            $item_form->requiredcheck->setValue(true);
        }

        $item_form->itemname->setValue($item->name);

        $widthAndHeight = explode('|',$item->presentation);
        $itemwidth = isset($widthAndHeight[0]) ? $widthAndHeight[0] : 30;
        $itemheight = isset($widthAndHeight[1]) ? $widthAndHeight[1] : 5;
        $item_form->selectwith->setValue($itemwidth);
        $item_form->selectheight->setValue($itemheight);
        
        return $item_form;
    }

    //liefert eine Struktur ->name, ->data = array(mit Antworten)
    function get_analysed($item, $groupid, $courseid = false) {
        $aVal = null;
        $aVal->data = array();
        $aVal->name = $item->name;
        //$values = get_records('feedback_value', 'item', $item->id);
        $values = feedback_get_group_values($item, $groupid, $courseid);
        if($values) {
            $data = array();
            foreach($values as $value) {
                $data[] = str_replace("\n", '<br />', $value->value);
            }
            $aVal->data = $data;
        }
        return $aVal;
    }

    function get_printval($item, $value) {
        
        if(!isset($value->value)) return '';

        return $value->value;
    }

    function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
        $values = feedback_get_group_values($item, $groupid, $courseid);
        if($values) {
            //echo '<table>';2
            // $itemnr++;
            echo '<tr><th colspan="2" align="left">'. $itemnr . '&nbsp;' . stripslashes_safe($item->name) .'</th></tr>';
            foreach($values as $value) {
                echo '<tr><td valign="top" align="left">-&nbsp;&nbsp;</td><td align="left" valign="top">' . str_replace("\n", '<br />', $value->value) . '</td></tr>';
            }
            //echo '</table>';
        }
        // return $itemnr;
    }

    function excelprint_item(&$worksheet, $rowOffset, $item, $groupid, $courseid = false) {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid);

        $worksheet->setFormat("<l><f><ro2><vo><c:green>");
        $worksheet->write_string($rowOffset, 0, stripslashes_safe($item->name));
        $data = $analysed_item->data;
        if(is_array($data)) {
            $worksheet->setFormat("<l><ro2><vo>");
            if(isset($data[0])) {
                $worksheet->write_string($rowOffset, 1, $data[0]);
            }
            $rowOffset++;
            for($i = 1; $i < sizeof($data); $i++) {
                $worksheet->setFormat("<l><vo>");
                $worksheet->write_string($rowOffset, 1, $data[$i]);
                $rowOffset++;
            }
        }
        $rowOffset++;
        return $rowOffset;
    }

    function print_item($item, $value = false, $readonly = false, $edit = false, $highlightrequire = false){
        $align = get_string('thisdirection') == 'ltr' ? 'left' : 'right';
        
        $presentation = explode ("|", $item->presentation);
        if($highlightrequire AND $item->required AND strval($value) == '') {
            $highlight = 'bgcolor="#FFAAAA" class="missingrequire"';
        }else {
            $highlight = '';
        }
        $requiredmark =  ($item->required == 1)?'<span class="feedback_required_mark">*</span>':'';
    ?>
        <td <?php echo $highlight;?> valign="top" align="<?php echo $align;?>"><?php echo format_text(stripslashes_safe($item->name) . $requiredmark, true, false, false);?></td>
        <td valign="top" align="<?php echo $align;?>">
    <?php
        if($readonly){
            // print_simple_box_start($align);
            print_box_start('generalbox boxalign'.$align);
            echo $value?str_replace("\n",'<br />',$value):'&nbsp;';
            // print_simple_box_end();
            print_box_end();
        }else {
    ?>
            <textarea name="<?php echo $item->typ . '_' . $item->id;?>"
                        cols="<?php echo $presentation[0];?>"
                        rows="<?php echo $presentation[1];?>"><?php echo $value?htmlspecialchars($value):'';?></textarea>
    <?php
        }
    ?>
        </td>
    <?php
    }

    function check_value($value, $item) {
        //if the item is not required, so the check is true if no value is given
        if((!isset($value) OR $value == '') AND $item->required != 1) return true;
        if($value == "")return false;
        return true;
    }

    function create_value($data) {
        $data = addslashes(clean_text($data));
        return $data;
    }

    function get_presentation($data) {
        return $data->itemwidth . '|'. $data->itemheight;
    }

    function get_hasvalue() {
        return 1;
    }
}
?>
