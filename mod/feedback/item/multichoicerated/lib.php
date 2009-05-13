<?php  // $Id: lib.php,v 1.1.2.3 2008/06/20 20:54:37 agrabs Exp $
defined('FEEDBACK_INCLUDE_TEST') OR die('not allowed');
require_once($CFG->dirroot.'/mod/feedback/item/feedback_item_class.php');

define('FEEDBACK_RADIORATED_ADJUST_SEP', '<<<<<');

define('FEEDBACK_MULTICHOICERATED_MAXCOUNT', 10); //count of possible items
define('FEEDBACK_MULTICHOICERATED_VALUE_SEP', '####');
define('FEEDBACK_MULTICHOICERATED_VALUE_SEP2', '/');
define('FEEDBACK_MULTICHOICERATED_TYPE_SEP', '>>>>>');
define('FEEDBACK_MULTICHOICERATED_LINE_SEP', '|');
define('FEEDBACK_MULTICHOICERATED_ADJUST_SEP', '<<<<<');

class feedback_item_multichoicerated extends feedback_item_base {
    var $type = "multichoicerated";
    function init() {
    
    }
    
    function &show_edit($item) {
        global $CFG;
        
        require_once('multichoicerated_form.php');
        
        $item_form = new feedback_multichoicerated_form();
        
        $item->presentation = empty($item->presentation) ? '' : $item->presentation;
        $item->name = empty($item->name) ? '' : htmlspecialchars(stripslashes_safe($item->name));

        $info = $this->get_info($item);

        $item->required = isset($item->required) ? $item->required : 0;
        if($item->required) {
            $item_form->requiredcheck->setValue(true);
        }
        
        $item_form->itemname->setValue($item->name);
        
        $item_form->selectadjust->setValue($info->horizontal);
        
        $item_form->selecttype->setValue($info->subtype);

        $itemvalues = $this->prepare_presentation_values_print($info->presentation, FEEDBACK_MULTICHOICERATED_VALUE_SEP, FEEDBACK_MULTICHOICERATED_VALUE_SEP2);
        $item_form->values->setValue($itemvalues);
        
        return $item_form;
    }

    //liefert ein eindimensionales Array mit drei Werten(typ, name, XXX)
    //XXX ist ein eindimensionales Array (Mittelwert der Werte der Antworten bei Typ Radio_rated) Jedes Element ist eine Struktur (answertext, avg)
    function get_analysed($item, $groupid = false, $courseid = false) {
        $analysedItem = array();
        $analysedItem[] = $item->typ;
        $analysedItem[] = $item->name;
        
        //die moeglichen Antworten extrahieren
        $info = $this->get_info($item);
        $lines = null;
        $lines = explode (FEEDBACK_MULTICHOICERATED_LINE_SEP, stripslashes_safe($info->presentation));
        if(!is_array($lines)) return null;

        //die Werte holen
        $values = feedback_get_group_values($item, $groupid, $courseid);
        if(!$values) return null;
        //schleife ueber den Werten und ueber die Antwortmoeglichkeiten
        
        $analysedAnswer = array();

        for($i = 1; $i <= sizeof($lines); $i++) {
            $item_values = explode(FEEDBACK_MULTICHOICERATED_VALUE_SEP, $lines[$i-1]);
            $ans = null;
            $ans->answertext = $item_values[1];
            $avg = 0.0;
            $anscount = 0;
            foreach($values as $value) {
                //ist die Antwort gleich dem index der Antworten + 1?
                if ($value->value == $i) {
                    $avg += $item_values[0]; //erst alle Werte aufsummieren
                    $anscount++;
                }
            }
            $ans->answercount = $anscount;
            $ans->avg = doubleval($avg) / doubleval(sizeof($values));
            $ans->value = $item_values[0];
            $ans->quotient = $ans->answercount / sizeof($values);
            $analysedAnswer[] = $ans;
        }
        $analysedItem[] = $analysedAnswer;
        return $analysedItem;
    }

    function get_printval($item, $value) {
        $printval = '';
        
        if(!isset($value->value)) return $printval;
        
        $info = $this->get_info($item);
                
        $presentation = explode (FEEDBACK_MULTICHOICERATED_LINE_SEP, stripslashes_safe($info->presentation));
        $index = 1;
        foreach($presentation as $pres){
            if($value->value == $index){
                $item_label = explode(FEEDBACK_MULTICHOICERATED_VALUE_SEP, $pres);
                $printval = $item_label[1];
                break;
            }
            $index++;
        }
        return $printval;
    }

    function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
        $sep_dec = get_string('separator_decimal', 'feedback');
        if(substr($sep_dec, 0, 2) == '[['){
            $sep_dec = FEEDBACK_DECIMAL;
        }
        
        $sep_thous = get_string('separator_thousand', 'feedback');
        if(substr($sep_thous, 0, 2) == '[['){
            $sep_thous = FEEDBACK_THOUSAND;
        }
            
        $analysedItem = $this->get_analysed($item, $groupid, $courseid);
        if($analysedItem) {
            //echo '<table>';
            // $itemnr++;
            echo '<tr><th colspan="2" align="left">'. $itemnr . '&nbsp;' . stripslashes($analysedItem[1]) .'</th></tr>';
            $analysedVals = $analysedItem[2];
            $pixnr = 0;
            $avg = 0.0;
            foreach($analysedVals as $val) {
                if( function_exists("bcmod")) {
                    $intvalue = bcmod($pixnr, 10);
                }else {
                    $intvalue = 0;
                }
                $pix = "pics/$intvalue.gif";
                $pixnr++;
                $pixwidth = intval($val->quotient * FEEDBACK_MAX_PIX_LENGTH);
                
                $avg += $val->avg;
                $quotient = number_format(($val->quotient * 100), 2, $sep_dec, $sep_thous);
                echo '<tr><td align="left" valign="top">-&nbsp;&nbsp;' . trim($val->answertext) . ' ('.$val->value.'):</td><td align="left" style="width: '.FEEDBACK_MAX_PIX_LENGTH.'"><img alt="'.$intvalue.'" src="'.$pix.'" height="5" width="'.$pixwidth.'" />' . $val->answercount. (($val->quotient > 0)?'&nbsp;('. $quotient . '&nbsp;%)':'') . '</td></tr>';
            }
            $avg = number_format(($avg), 2, $sep_dec, $sep_thous);
            echo '<tr><td align="left" colspan="2"><b>'.get_string('average', 'feedback').': '.$avg.'</b></td></tr>';
            //echo '</table>';
        }
        // return $itemnr;
    }

    function excelprint_item(&$worksheet, $rowOffset, $item, $groupid, $courseid = false) {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid);


        $data = $analysed_item[2];

        $worksheet->setFormat("<l><f><ro2><vo><c:green>");
        //frage schreiben
        $worksheet->write_string($rowOffset, 0, stripslashes($analysed_item[1]));
        if(is_array($data)) {
            $avg = 0.0;
            for($i = 0; $i < sizeof($data); $i++) {
                $aData = $data[$i];
                
                $worksheet->setFormat("<l><f><ro2><vo><c:blue>");
                $worksheet->write_string($rowOffset, $i + 1, trim($aData->answertext).' ('.$aData->value.')');
                
                $worksheet->setFormat("<l><vo>");
                $worksheet->write_number($rowOffset + 1, $i + 1, $aData->answercount);
                //$worksheet->setFormat("<l><f><vo>");
                //$worksheet->write_number($rowOffset + 2, $i + 1, $aData->avg);
                $avg += $aData->avg;
            }
            //mittelwert anzeigen
            $worksheet->setFormat("<l><f><ro2><vo><c:red>");
            $worksheet->write_string($rowOffset, sizeof($data) + 1, get_string('average', 'feedback'));
            
            $worksheet->setFormat("<l><f><vo>");
            $worksheet->write_number($rowOffset + 1, sizeof($data) + 1, $avg);
        }
        $rowOffset +=2 ;
        return $rowOffset;
    }

    function print_item($item, $value = false, $readonly = false, $edit = false, $highlightrequire = false){
        $align = get_string('thisdirection') == 'ltr' ? 'left' : 'right';
        $info = $this->get_info($item);
        
        $lines = explode (FEEDBACK_MULTICHOICERATED_LINE_SEP, stripslashes_safe($info->presentation));
        $requiredmark =  ($item->required == 1)?'<span class="feedback_required_mark">*</span>':'';
        if($highlightrequire AND $item->required AND intval($value) <= 0) {
            $highlight = 'bgcolor="#FFAAAA" class="missingrequire"';
        }else {
            $highlight = '';
        }
    ?>
        <td <?php echo $highlight;?> valign="top" align="<?php echo $align;?>"><?php echo format_text(stripslashes_safe($item->name) . $requiredmark, true, false, false);?></td>
        <td valign="top" align="<?php echo $align;?>">
    <?php
        $index = 1;
        $checked = '';
        if($readonly){
            foreach($lines as $line){
                if($value == $index){
                    $item_value = explode(FEEDBACK_MULTICHOICERATED_VALUE_SEP, $line);
                    // print_simple_box_start('left');
                    print_box_start('generalbox boxalign'.$align);
                    echo text_to_html($item_value[1], true, false, false);
                    // print_simple_box_end();
                    print_box_end();
                    break;
                }
                $index++;
            }
        } else {
            switch($info->subtype) {
                case 'r':
                    $this->print_item_radio($item, $value, $info, $align, $edit, $lines);
                    break;
                case 'd':
                    $this->print_item_dropdown($item, $value, $info, $align, $edit, $lines);
                    break;
            }
        }
    ?>
        </td>
    <?php
    }

    function check_value($value, $item) {
        if((!isset($value) OR $value == '' OR $value == 0) AND $item->required != 1) return true;
        if(intval($value) > 0)return true;
        return false;
    }

    function create_value($data) {
        $data = clean_param($data, PARAM_INT);
        return $data;
    }

    function get_presentation($data) {
        // $present = str_replace("\n", FEEDBACK_MULTICHOICERATED_LINE_SEP, trim($data->itemvalues));
        $present = $this->prepare_presentation_values_save(trim($data->itemvalues), FEEDBACK_MULTICHOICERATED_VALUE_SEP2, FEEDBACK_MULTICHOICERATED_VALUE_SEP);
        // $present = str_replace("\n", FEEDBACK_MULTICHOICERATED_LINE_SEP, trim($data->itemvalues));
        if(!isset($data->subtype)) {
            $subtype = 'r';
        }else {
            $subtype = substr($data->subtype, 0, 1);
        }
        if(isset($data->horizontal) AND $data->horizontal == 1 AND $subtype != 'd') {
            $present .= FEEDBACK_MULTICHOICERATED_ADJUST_SEP.'1';
        }
        return $subtype.FEEDBACK_MULTICHOICERATED_TYPE_SEP.$present;
    }

    function get_hasvalue() {
        return 1;
    }
    
    function get_info($item) {
        $presentation = empty($item->presentation) ? '' : $item->presentation;
        
        $info = new object();
        //check the subtype of the multichoice
        //it can be check(c), radio(r) or dropdown(d)
        $info->subtype = '';
        $info->presentation = '';
        $info->horizontal = false;
        
        @list($info->subtype, $info->presentation) = explode(FEEDBACK_MULTICHOICE_TYPE_SEP, $item->presentation);
        if(!isset($info->subtype)) {
            $info->subtype = 'r';
        }


        if($info->subtype != 'd') {
            @list($info->presentation, $info->horizontal) = explode(FEEDBACK_MULTICHOICE_ADJUST_SEP, $info->presentation);
            if(isset($info->horizontal) AND $info->horizontal == 1) {
                $info->horizontal = true;
            }else {
                $info->horizontal = false;
            }
        }

        return $info;
    }
    
    function print_item_radio($item, $value, $info, $align, $edit, $lines) {
        $index = 1;
        $checked = '';
        ?>
        <table><tr>
        <td valign="top" align="<?php echo $align;?>"><input type="radio"
                name="<?php echo $item->typ . '_' . $item->id ;?>"
                id="<?php echo $item->typ.'_'.$item->id.'_xxx';?>"
                value="" <?php echo $value ? '' : 'checked="checked"';?> />
        </td>
        <td align="<?php echo $align;?>">
            <label for="<?php echo $item->typ.'_'.$item->id.'_xxx';?>"><?php print_string('not_selected', 'feedback');?>&nbsp;</label>
        </td>
        </tr></table>
        <?php
        if($info->horizontal) {
            echo '<table><tr>';
        }
        foreach($lines as $line){
            if($value == $index){
                $checked = 'checked="checked"';
            }else{
                $checked = '';
            }
            $radio_value = explode(FEEDBACK_MULTICHOICERATED_VALUE_SEP, $line);
            $inputname = $item->typ . '_' . $item->id;
            $inputid = $inputname.'_'.$index;
            if($info->horizontal) {
        ?>
                <td valign="top" align="<?php echo $align;?>"><input type="radio"
                        name="<?php echo $inputname;?>"
                        id="<?php echo $inputid;?>"
                        value="<?php echo $index;?>" <?php echo $checked;?> />
                </td><td align="<?php echo $align;?>"><label for="<?php echo $inputid;?>"><?php
                                if($edit) {
                                    echo text_to_html('('.$radio_value[0].') '.$radio_value[1], true, false, false);
                                }else {
                                    echo text_to_html($radio_value[1], true, false, false);
                                }
                            ?>&nbsp;</label>
                </td>
        <?php
            }else {
        ?>
                <table><tr>
                <td valign="top" align="<?php echo $align;?>"><input type="radio"
                        name="<?php echo $inputname;?>"
                        id="<?php echo $inputid;?>"
                        value="<?php echo $index;?>" <?php echo $checked;?> />
                </td><td align="<?php echo $align;?>"><label for="<?php echo $inputid;?>"><?php
                                if($edit) {
                                    echo text_to_html('('.$radio_value[0].') '.$radio_value[1], true, false, false);
                                }else {
                                    echo text_to_html($radio_value[1], true, false, false);
                                }
                            ?>&nbsp;</label>
                </td></tr></table>
        <?php
            }
            $index++;
        }
        if($info->horizontal) {
            echo '</tr></table>';
        }
    }
    
    function print_item_dropdown($item, $value, $info, $align, $edit, $lines) {
        echo '<select name="'. $item->typ . '_' . $item->id . '">';
        echo '<option value="0">&nbsp;</option>';
        $index = 1;
        $checked = '';
        foreach($lines as $line){
            if($value == $index){
                $selected = 'selected="selected"';
            }else{
                $selected = '';
            }
            $dropdown_value = explode(FEEDBACK_MULTICHOICERATED_VALUE_SEP, $line);
            if($edit) {
                echo '<option value="'. $index.'" '. $selected.'>'. clean_text('('.$dropdown_value[0].') '.$dropdown_value[1]).'</option>';
            }else {
                echo '<option value="'. $index.'" '. $selected.'>'. clean_text($dropdown_value[1]).'</option>';
            }
            $index++;
        }
        echo '</select>';
    
    }
    
    function prepare_presentation_values_print($valuestring, $valuesep1, $valuesep2) {
        $lines = explode(FEEDBACK_MULTICHOICERATED_LINE_SEP, $valuestring);
        $newlines = array();
        foreach($lines as $line) {
            $value = '';
            $text = '';
            if(strpos($line, $valuesep1) === false) {
                $value = 0;
                $text = $line;
            }else {
                @list($value, $text) = explode($valuesep1, $line, 2);
            }
            
            $value = intval($value);
            $newlines[] = $value.$valuesep2.$text;
        }
        $newlines = implode("\n", $newlines);
        return $newlines;
    }
    
    function prepare_presentation_values_save($valuestring, $valuesep1, $valuesep2) {
        $lines = explode("\n", $valuestring);
        $newlines = array();
        foreach($lines as $line) {
            $value = '';
            $text = '';
            
            if(strpos($line, $valuesep1) === false) {
                $value = 0;
                $text = $line;
            }else {
                @list($value, $text) = explode($valuesep1, $line, 2);
            }
            
            $value = intval($value);
            $newlines[] = $value.$valuesep2.$text;
        }
        $newlines = implode(FEEDBACK_MULTICHOICERATED_LINE_SEP, $newlines);
        return $newlines;
    }
}
?>
