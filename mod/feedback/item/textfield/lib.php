<?php  // $Id: lib.php,v 1.6.2.5 2008/06/20 20:54:37 agrabs Exp $
defined('FEEDBACK_INCLUDE_TEST') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/feedback_item_class.php');

class feedback_item_textfield extends feedback_item_base {
    var $type = "textfield";
    function init() {
    
    }
    
    function &show_edit($item) {
        global $CFG;
        
        require_once('textfield_form.php');
        
        $item_form = new feedback_textfield_form();
        
        $item->presentation = empty($item->presentation) ? '' : $item->presentation;
        $item->name = empty($item->name) ? '' : htmlspecialchars(stripslashes_safe($item->name));
        
        $item->required = isset($item->required) ? $item->required : 0;
        if($item->required) {
            $item_form->requiredcheck->setValue(true);
        }

        $item_form->itemname->setValue($item->name);

        $sizeAndLength = explode('|',$item->presentation);
        $itemsize = isset($sizeAndLength[0]) ? $sizeAndLength[0] : 30;
        $itemlength = isset($sizeAndLength[1]) ? $sizeAndLength[1] : 5;
        $item_form->selectwith->setValue($itemsize);
        $item_form->selectheight->setValue($itemlength);
        
        return $item_form;
    }

    //liefert eine Struktur ->name, ->data = array(mit Antworten)
    function get_analysed($item, $groupid = false, $courseid = false) {
        $aVal = null;
        $aVal->data = null;
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
                echo '<tr><td colspan="2" valign="top" align="left">-&nbsp;&nbsp;' . str_replace("\n", '<br />', $value->value) . '</td></tr>';
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
            $worksheet->write_string($rowOffset, 1, $data[0]);
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
            echo $value?$value:'&nbsp;';
            // print_simple_box_end();
            print_box_end();
        }else {
    ?>
            <input type="text" name="<?php echo $item->typ . '_' . $item->id;?>"
                                    size="<?php echo $presentation[0];?>"
                                    maxlength="<?php echo $presentation[1];?>"
                                    value="<?php echo $value?htmlspecialchars($value):'';?>" />
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
        return $data->itemsize . '|'. $data->itemmaxlength;
    }

    function get_hasvalue() {
        return 1;
    }
}
?>
