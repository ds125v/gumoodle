<?php // $Id: index.php,v 1.1 2010/04/26 16:34:44 arborrow Exp $
/**
 * @package   turnitintool
 * @copyright 2010 nLearning Ltd
 */
    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = turnitintool_get_record("course", "id", $id)) {
        turnitintool_print_error('courseiderror','turnitintool');
    }

    require_login($course->id);

    add_to_log($course->id, "turnitintool", "view all", "index.php?id=$course->id", "");


/// Get all required stringsnewmodule

    $strturnitintools = get_string("modulenameplural", "turnitintool");
    $strturnitintool  = get_string("modulename", "turnitintool");
	

    if (!is_callable('build_navigation')) {
        $navigation = array(
						array('title' => $course->shortname, 'url' => $CFG->wwwroot."/course/view.php?id=$course->id", 'type' => 'course'),
                        array('title' => $strturnitintools, 'url' => '', 'type' => 'activity')
					  );
    } else {
		$navigation = array(
                        array('name' => $strturnitintools, 'url' => '', 'type' => 'activity')
					  );
        $navigation = build_navigation($navigation,"");
    }

    /// Print the header
    
    turnitintool_header(NULL,$course,$_SERVER["REQUEST_URI"],$strturnitintools, $SITE->fullname, $navigation, '', '', true, '', '');

    //print_header_simple($strturnitintools, '', $navigation, "", "", true, "", navmenu($course));
    
    

/// Get all the appropriate data

    if (! $turnitintools = get_all_instances_in_course("turnitintool", $course)) {
        notice("There are no ".$strturnitintools, "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");
	$strdtstart = get_string("dtstart","turnitintool");
	$strsubmissions = get_string("submissions","turnitintool");
	$strnumparts = get_string("numberofparts","turnitintool");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname, $strdtstart, $strnumparts, $strsubmissions);
        $table->align = array ("center", "left", "center", "center", "center");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname, $strdtstart ,$strnumparts , $strsubmissions);
        $table->align = array ("center", "left", "center", "center", "center");
    } else {
        $table->head  = array ($strname, $strdtstart, $strnumparts, $strsubmissions);
        $table->align = array ("left", "center", "center", "center");
    }
    $table->class='';
	$table->width='100%';



    foreach ($turnitintools as $turnitintool) {
        $dimmed='';
        if (!$turnitintool->visible) {
            //Show dimmed if the mod is hidden
            $dimmed=' class="dimmed"';
        }

        $link = '<a'.$dimmed.' href="view.php?id='.$turnitintool->coursemodule.'">'.$turnitintool->name.'</a>';
        $part=turnitintool_get_record_select('turnitintool_parts','turnitintoolid='.$turnitintool->id.' AND deleted=0','MIN(dtstart) dtstart');
        $dtstart = '<span'.$dimmed.'>'.userdate($part->dtstart,get_string('strftimedatetimeshort','langconfig')).'</span>';
        $partcount=turnitintool_count_records_select('turnitintool_parts','turnitintoolid='.$turnitintool->id.' AND deleted=0');
		if (has_capability('mod/turnitintool:grade', get_context_instance(CONTEXT_MODULE, $turnitintool->coursemodule))) {
	        $submissioncount='<a'.$dimmed.' href="view.php?id='.$turnitintool->coursemodule.'&do=allsubmissions">'.turnitintool_count_records('turnitintool_submissions','turnitintoolid',$turnitintool->id).'</a>';
		} else {
			$submissioncount='<a'.$dimmed.' href="view.php?id='.$turnitintool->coursemodule.'&do=submissions">'.turnitintool_count_records_select('turnitintool_submissions','turnitintoolid='.$turnitintool->id.' AND userid='.$USER->id).'</a>';
		}
        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($turnitintool->section, $link, $dtstart, $partcount, $submissioncount);
        } else {
            $table->data[] = array ($link, $dtstart, $partcount, $submissioncount);
        }
    }

    echo "<br />";

    turnitintool_print_table($table);

/// Finish the page

    turnitintool_footer($course);

/* ?> */