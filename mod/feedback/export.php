<?php // $Id: export.php,v 1.2.2.1 2008/05/15 10:33:07 agrabs Exp $
/**
* prints the form to export the items as xml-file
*
* @version $Id: export.php,v 1.2.2.1 2008/05/15 10:33:07 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");

    // get parameters
    $id = required_param('id', PARAM_INT); 
    $action = optional_param('action', false, PARAM_ALPHA);

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
    
    if ($action == 'exportfile') {
        if(!$exportdata = feedback_get_xml_data($feedback->id)) {
            error('no data');
        }
        @feedback_send_xml_data($exportdata, 'feedback_'.$feedback->id.'.xml');
        exit;
    }

    redirect('view.php?id='.$id);
    exit;
  
    function feedback_get_xml_data($feedbackid) {
        $space = '     ';
        //get all items of the feedback
        if(!$items = get_records('feedback_item', 'feedback', $feedbackid, 'position')) {
            return false;
        }
        
        //writing the header of the xml file including the charset of the currrent used language
        $data = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
        $data .= '<FEEDBACK VERSION="200701" COMMENT="XML-Importfile for mod/feedback">'."\n";
        $data .= $space.'<ITEMS>'."\n";
        
        //writing all the items
        foreach($items as $item) {
            //start of item
            $data .= $space.$space.'<ITEM TYPE="'.$item->typ.'" REQUIRED="'.$item->required.'">'."\n";
            
            //start of itemtext
            $data .= $space.$space.$space.'<ITEMTEXT>'."\n";
            //start of CDATA
            $data .= $space.$space.$space.$space.'<![CDATA[';
            $data .= $item->name;
            //end of CDATA
            $data .= ']]>'."\n";
            //end of itemtext
            $data .= $space.$space.$space.'</ITEMTEXT>'."\n";
            
            //start of presentation
            $data .= $space.$space.$space.'<PRESENTATION>'."\n";
            //start of CDATA
            $data .= $space.$space.$space.$space.'<![CDATA[';
            $data .= $item->presentation;
            //end of CDATA
            $data .= ']]>'."\n";
            //end of presentation
            $data .= $space.$space.$space.'</PRESENTATION>'."\n";
            
            //end of item
            $data .= $space.$space.'</ITEM>'."\n";
        }
        
        //writing the footer of the xml file
        $data .= $space.'</ITEMS>'."\n";
        $data .= '</FEEDBACK>'."\n";
        
        return $data;
    }
    
    function feedback_send_xml_data($data, $filename) {
        $charset = get_string('thischarset');
        @header('Content-Type: application/xml; charset=UTF-8');
        @header('Content-Disposition: attachment; filename='.$filename);
        print($data);
    }
?>
