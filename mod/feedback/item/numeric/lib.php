<?php  // $Id: lib.php,v 1.7.2.5 2008/11/14 16:52:12 agrabs Exp $
defined('FEEDBACK_INCLUDE_TEST') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/feedback_item_class.php');

class feedback_item_numeric extends feedback_item_base {
    var $type = "numeric";
    var $sep_dec, $sep_thous;
    
    function init() {
        $this->sep_dec = get_string('separator_decimal', 'feedback');
        if(substr($this->sep_dec, 0, 2) == '[['){
            $this->sep_dec = FEEDBACK_DECIMAL;
        }
        
        $this->sep_thous = get_string('separator_thousand', 'feedback');
        if(substr($this->sep_thous, 0, 2) == '[['){
            $this->sep_thous = FEEDBACK_THOUSAND;
        }
    }
    
    function &show_edit($item) {
        global $CFG;
        
        require_once('numeric_form.php');
        
        $item_form = new feedback_numeric_form();

        $item->presentation = empty($item->presentation) ? '' : $item->presentation;
        $item->name = empty($item->name) ? '' : htmlspecialchars(stripslashes_safe($item->name));
        
        $item->required = isset($item->required) ? $item->required : 0;
        if($item->required) {
            $item_form->requiredcheck->setValue(true);
        }

        $item_form->itemname->setValue($item->name);
        
        $range_from_to = explode('|',$item->presentation);
        
        $range_from = (isset($range_from_to[0]) AND is_numeric($range_from_to[0])) ? str_replace(FEEDBACK_DECIMAL, $this->sep_dec, floatval($range_from_to[0])) : '-';
        $range_to = (isset($range_from_to[1]) AND is_numeric($range_from_to[1])) ? str_replace(FEEDBACK_DECIMAL, $this->sep_dec, floatval($range_from_to[1])) : '-';
        
        $item_form->selectfrom->setValue($range_from);
        
        $item_form->selectto->setValue($range_to);
        
        return $item_form;
    }

    //liefert eine Struktur ->name, ->data = array(mit Antworten)
    function get_analysed($item, $groupid = false, $courseid = false) {
        $analysed = null;
        $analysed->data = array();
        $analysed->name = $item->name;
        //$values = get_records('feedback_value', 'item', $item->id);
        $values = feedback_get_group_values($item, $groupid, $courseid);
        
        $avg = 0.0;
        $counter = 0;
        if($values) {
            $data = array();
            foreach($values as $value) {
                if(is_numeric($value->value)) {
                    $data[] = $value->value;
                    $avg += $value->value;
                    $counter++;
                }
            }
            $avg = $counter > 0 ? $avg / $counter : 0;
            $analysed->data = $data;
            $analysed->avg = $avg;
        }
        return $analysed;
    }

    function get_printval($item, $value) {
        if(!isset($value->value)) return '';
        
        return $value->value;
    }

    function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
        
        // $values = feedback_get_group_values($item, $groupid, $courseid);
        $values = $this->get_analysed($item, $groupid, $courseid);

        if(isset($values->data) AND is_array($values->data)) {
            //echo '<table>';2
            // $itemnr++;
            echo '<tr><th colspan="2" align="left">'. $itemnr . '&nbsp;' . stripslashes($item->name) .'</th></tr>';
            foreach($values->data as $value) {
                echo '<tr><td colspan="2" valign="top" align="left">-&nbsp;&nbsp;' . number_format($value, 2, $this->sep_dec, $this->sep_thous) . '</td></tr>';
            }
            //echo '</table>';
            if(isset($values->avg)) {
                $avg = number_format($values->avg, 2, $this->sep_dec, $this->sep_thous);
            } else {
                $avg = number_format(0, 2, $this->sep_dec, $this->sep_thous);
            }
            echo '<tr><td align="left" colspan="2"><b>'.get_string('average', 'feedback').': '.$avg.'</b></td></tr>';
        }
        // return $itemnr;
    }

    function excelprint_item(&$worksheet, $rowOffset, $item, $groupid, $courseid = false) {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid);

        $worksheet->setFormat("<l><f><ro2><vo><c:green>");
        $worksheet->write_string($rowOffset, 0, stripslashes($item->name));
        $data = $analysed_item->data;
        if(is_array($data)) {
            // $worksheet->setFormat("<l><ro2><vo>");
            // $worksheet->write_number($rowOffset, 1, $data[0]);
            // $rowOffset++;
            // for($i = 1; $i < sizeof($data); $i++) {
                // $worksheet->setFormat("<l><vo>");
                // $worksheet->write_number($rowOffset, 1, $data[$i]);
                // $rowOffset++;
            // }
        
            //mittelwert anzeigen
            $worksheet->setFormat("<l><f><ro2><vo><c:red>");
            $worksheet->write_string($rowOffset, 1, get_string('average', 'feedback'));
            
            $worksheet->setFormat("<l><f><vo>");
            $worksheet->write_number($rowOffset + 1, 1, $analysed_item->avg);
            $rowOffset++;
        }
        $rowOffset++;
        return $rowOffset;
    }

    function print_item($item, $value = false, $readonly = false, $edit = false, $highlightrequire = false){
        $align = get_string('thisdirection') == 'ltr' ? 'left' : 'right';
        
        //get the range
        $range_from_to = explode('|',$item->presentation);
        //get the min-value
        $range_from = (isset($range_from_to[0]) AND is_numeric($range_from_to[0])) ? floatval($range_from_to[0]) : '-';
        //get the max-value
        $range_to = (isset($range_from_to[1]) AND is_numeric($range_from_to[1])) ? floatval($range_from_to[1]) : '-';
        if($highlightrequire AND (!$this->check_value($value, $item))) {
            $highlight = 'bgcolor="#FFAAAA" class="missingrequire"';
        }else {
            $highlight = '';
        }
        $requiredmark =  ($item->required == 1)?'<span class="feedback_required_mark">*</span>':'';
    ?>
        <td <?php echo $highlight;?> valign="top" align="<?php echo $align;?>">
            <?php 
                echo format_text(stripslashes_safe($item->name) . $requiredmark, true, false, false);
                switch(true) {
                    case ($range_from === '-' AND is_numeric($range_to)):
                        echo ' ('.get_string('maximal', 'feedback').': '.str_replace(FEEDBACK_DECIMAL, $this->sep_dec, $range_to).')';
                        break;
                    case (is_numeric($range_from) AND $range_to === '-'):
                        echo ' ('.get_string('minimal', 'feedback').': '.str_replace(FEEDBACK_DECIMAL, $this->sep_dec, $range_from).')';
                        break;
                    case ($range_from === '-' AND $range_to === '-'):
                        break;
                    default:
                        echo ' ('.str_replace(FEEDBACK_DECIMAL, $this->sep_dec, $range_from).' - '.str_replace(FEEDBACK_DECIMAL, $this->sep_dec, $range_to).')';
                        break;
                }
            ?>
        </td>
        <td valign="top" align="<?php echo $align;?>">
    <?php
        if($readonly){
            // print_simple_box_start($align);
            print_box_start('generalbox boxalign'.$align);
            echo (is_numeric($value)) ? number_format($value, 2, $this->sep_dec, $this->sep_thous) : '&nbsp;';
            // print_simple_box_end();
            print_box_end();
        }else {
    ?>
            <input type="text" name="<?php echo $item->typ.'_'.$item->id; ?>"
                                    size="10"
                                    maxlength="10"
                                    value="<?php echo $value ? $value : ''; ?>" />
    <?php
        }
    ?>
        </td>
    <?php
    }

    function check_value($value, $item) {
        $value = str_replace($this->sep_dec, FEEDBACK_DECIMAL, $value);
        //if the item is not required, so the check is true if no value is given
        if((!isset($value) OR $value == '') AND $item->required != 1) return true;
        if(!is_numeric($value))return false;
        
        $range_from_to = explode('|',$item->presentation);
        $range_from = (isset($range_from_to[0]) AND is_numeric($range_from_to[0])) ? floatval($range_from_to[0]) : '-';
        $range_to = (isset($range_from_to[1]) AND is_numeric($range_from_to[1])) ? floatval($range_from_to[1]) : '-';
        
        switch(true) {
            case ($range_from === '-' AND is_numeric($range_to)):
                if(floatval($value) <= $range_to) return true;
                break;
            case (is_numeric($range_from) AND $range_to === '-'):
                if(floatval($value) >= $range_from) return true;
                break;
            case ($range_from === '-' AND $range_to === '-'):
                return true;
                break;
            default:
                if(floatval($value) >= $range_from AND floatval($value) <= $range_to) return true;
                break;
        }
        
        return false;
    }

    function create_value($data) {
        $data = str_replace($this->sep_dec, FEEDBACK_DECIMAL, $data);
        
        if(is_numeric($data)) {
            $data = floatval($data);
        }else {
            $data = '';
        }
        return $data;
    }

    function get_presentation($data) {
        $num1 = str_replace($this->sep_dec, FEEDBACK_DECIMAL, $data->numericrangefrom);
        if(is_numeric($num1)) {
            $num1 = floatval($num1);
        }else {
            $num1 = '-';
        }
        
        $num2 = str_replace($this->sep_dec, FEEDBACK_DECIMAL, $data->numericrangeto);
        if(is_numeric($num2)) {
            $num2 = floatval($num2);
        }else {
            $num2 = '-';
        }
        
        if($num1 === '-' OR $num2 === '-') {
            return $num1 . '|'. $num2;
        }
        
        if($num1 > $num2) {
            return $num2 . '|'. $num1;
        }else {
            return $num1 . '|'. $num2;        
        }
    }

    function get_hasvalue() {
        return 1;
    }
}
?>
