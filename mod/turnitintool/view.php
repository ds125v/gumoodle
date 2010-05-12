<?php
/**
 * @package   turnitintool
 * @copyright 2010 nLearning Ltd
 */

    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->dirroot."/lib/uploadlib.php");
    require_once($CFG->dirroot."/lib/html2text.php");

    require_js($CFG->wwwroot.'/mod/turnitintool/turnitintool.js');
    
    turnitintool_process_api_error();

    $id = required_param('id', PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // turnitintool ID

    if ($id) {
        if (! $cm = get_coursemodule_from_id('turnitintool', $id)) {
            turnitintool_print_error("Course Module ID was incorrect");
        }

        if (! $course = turnitintool_get_record("course", "id", $cm->course)) {
            turnitintool_print_error("Course is misconfigured");
        }

        if (! $turnitintool = turnitintool_get_record("turnitintool", "id", $cm->instance)) {
            turnitintool_print_error("Course module is incorrect");
        }

    } else {
        if (! $turnitintool = turnitintool_get_record("turnitintool", "id", $a)) {
            turnitintool_print_error("Course module is incorrect");
        }
        if (! $course = turnitintool_get_record("course", "id", $turnitintool->course)) {
            turnitintool_print_error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("turnitintool", $turnitintool->id, $course->id)) {
            turnitintool_print_error("Course Module ID was incorrect");
        }
    }
    
    turnitintool_update_choice_cookie($turnitintool);

    require_login($course->id);
    
    $param_jumppage=optional_param('jumppage');
    $param_userid=optional_param('userid');
    $param_post=optional_param('post');
    $param_delete=optional_param('delete');
    $param_update=optional_param('update');
    $param_do=optional_param('do');
    $param_enroll=optional_param('enroll');
    $param_owner=optional_param('owner');
    $param_anonid=optional_param('anonid');
    $param_viewreport=optional_param('viewreport');
    $param_updategrade=optional_param('updategrade');
    $param_up=optional_param('up');
    $param_submissiontype=optional_param('submissiontype');
    $param_submitted=optional_param('submitted');
    $param_delpart=optional_param('delpart');
    $param_s=optional_param('s');
    $param_ob=optional_param('ob');
    

    if (!is_null($param_jumppage)) {
        turnitintool_url_jumpto($param_userid,$param_jumppage,unserialize(base64_decode($param_post)));
        exit();
    }
    
    if (!is_null($param_delete)) {
        if (!$submission = turnitintool_get_record('turnitintool_submissions','id',$param_delete)) {
            print_error('submissiongeterror','turnitintool');
            exit();
        }
        turnitintool_delete_submission($cm,$turnitintool,$USER->id,$submission);
        exit();
    }
    
    if (!is_null($param_update)) {
        $loaderbar = new turnitintool_loaderbarclass(2);
        turnitintool_update_all_report_scores($cm,$turnitintool,true,$loaderbar);
        turnitintool_redirect($CFG->wwwroot.'/mod/turnitintool/view.php?id='.$cm->id.'&do='.$param_do);
        exit();
    }
    
    if (!is_null($param_enroll)) {
        turnitintool_enroll_all_students($cm,$turnitintool);
        turnitintool_redirect($CFG->wwwroot.'/mod/turnitintool/view.php?id='.$cm->id.'&do='.$param_do);
        exit();
    }
    
    if (!is_null($param_do) AND $param_do=="changeowner") {
        turnitintool_ownerprocess($cm,$turnitintool,$param_owner);
        turnitintool_redirect($CFG->wwwroot.'/mod/turnitintool/view.php?id='.$cm->id);
        exit();
    }
    
    if (!is_null($param_do) AND $param_do=="allsubmissions" AND !is_null($param_anonid)) {
        turnitintool_revealuser($cm,$turnitintool,$_POST);
        $loaderbar = new turnitintool_loaderbarclass(2);
        turnitintool_update_all_report_scores($cm,$turnitintool,true,$loaderbar);
        turnitintool_redirect($CFG->wwwroot.'/mod/turnitintool/view.php?id='.$cm->id.'&do=allsubmissions');
        exit();
    }
    
    if (!is_null($param_viewreport)) {
        if (!$submission = turnitintool_get_record('turnitintool_submissions','id',$param_viewreport)) {
            print_error('submissiongeterror','turnitintool');
            exit();
        }
        $loaderbar = new turnitintool_loaderbarclass(3);
        turnitintool_view_report($cm,$turnitintool,$USER->id,$submission,$param_do,$loaderbar);
        exit();
    }
    
    if (!is_null($param_updategrade)) {
        turnitintool_update_grades($cm,$turnitintool,$_POST);
    }
    
    if (!is_null($param_up)) { // Manual Submission to Turnitin
        if (!$submission = turnitintool_get_record('turnitintool_submissions','id',$param_up)) {
            print_error('submissiongeterror','turnitintool');
            exit();
        }
        turnitintool_upload_submission($cm,$turnitintool,$submission);
        exit();
    }
    
    if (!is_null($param_submissiontype) AND $param_do=='submissions') {
        if (isset($param_userid)) {
            $thisuserid=$param_userid;
        } else {
            $thisuserid=$USER->id;
        }
        if ($param_submissiontype==1) {
            $notice=turnitintool_dofileupload($cm,$turnitintool,$thisuserid,$_REQUEST);
        } else if ($param_submissiontype==2) {
            $notice=turnitintool_dotextsubmission($cm,$turnitintool,$thisuserid,$_REQUEST);
        }
        if ($turnitintool->autosubmission AND !empty($notice["subid"])) {
            if (!$submission = turnitintool_get_record('turnitintool_submissions','id',$notice["subid"])) {
                print_error('submissiongeterror','turnitintool');
                exit();
            }
            turnitintool_upload_submission($cm,$turnitintool,$submission);
            exit();
        }
    }
    
    if (!is_null($param_submitted) AND $param_do=='intro') {
        $notice=turnitintool_update_partnames($cm,$turnitintool,$_POST);
    }
    
    if (!is_null($param_delpart) AND $param_do=='intro') {
        $notice=turnitintool_delete_part($cm,$turnitintool,$param_delpart);
    }
    
    if (!is_null($param_submitted) AND $param_do=='notes') {
        $notice=turnitintool_process_notes($cm,$turnitintool,$param_s,$_POST);
    }
    
    if (!is_null($param_submitted) AND $param_do=='options') {
        $notice=turnitintool_process_options($cm,$turnitintool,$_POST);
    }
    
    if (!is_null($param_do) AND $turnitintool->autoupdates AND ($param_do=='allsubmissions' OR $param_do=='submissions')) {
        if ($param_do=='submissions') {
            $getuser=$USER->id;
        } else {
            $getuser=NULL;
        }
        $peruser=false;
        $loaderbar = new turnitintool_loaderbarclass(2);
        turnitintool_update_all_report_scores($cm,$turnitintool,false,$loaderbar);
    }

    add_to_log($course->id, "turnitintool", "view", "view.php?id=$cm->id", "$turnitintool->id");

/// Print the page header
    $strturnitintools = get_string("modulenameplural", "turnitintool");
    $strturnitintool  = get_string("modulename", "turnitintool");
	
    if (!is_callable('build_navigation')) {
        $navigation = array(
						array('title' => $course->shortname, 'url' => $CFG->wwwroot."/course/view.php?id=$course->id", 'type' => 'course'),
                        array('title' => $strturnitintools, 'url' => $CFG->wwwroot."/mod/turnitintool/index.php?id=$course->id", 'type' => 'activity'),
                        array('title' => format_string($turnitintool->name), 'url' => '', 'type' => 'activityinstance')
					  );
    } else {
        $navigation = build_navigation('',$cm);
    }
    
    turnitintool_header($cm,
                        $course,
                        $_SERVER["REQUEST_URI"],
                        $turnitintool->name,
                        $SITE->fullname,
                        $navigation,
                        "",
                        "",
                        true,
                        update_module_button($cm->id, $course->id, $strturnitintool),
                        navmenu($course)
                        );

    // Print the main part of the page
    echo '<div id="turnitintool_style">';
    
    if (!is_null($param_do)) {
        $do=$param_do;
    } else {
        $do='intro';
    }
    
    // $do=ACTION
    // $do=submissions >>> Student Submission Page
    // $do=intro >>> Turnitin Assignment Intro Page
    // $do=allsubmissions >>> Tutor View All Submissions
    // $do=bulkupload >>> Tutor Bulk Upload Student Submissions
    // $do=viewtext >>> View Student Text Submission
    // $do=submissiondetails >>> View Submission Details
    
    $studentdos=array('submissions','intro','viewtext','submissiondetails','notes');
    $graderdos=array('allsubmissions','options','changeowner');
    
    // If an unrecognised DO request produce error
    if (!in_array($do,$studentdos) AND !in_array($do,$graderdos)) {
        turnitintool_print_error('dorequesterror','turnitintool');
        exit();
    } else if (!has_capability('mod/turnitintool:grade', get_context_instance(CONTEXT_MODULE, $cm->id)) AND in_array($do,$graderdos)) {
        turnitintool_print_error('permissiondeniederror','turnitintool');
        exit();
    }
    
    echo '<br />';
    turnitintool_draw_menu($cm,$do);
        
    if ($do=='intro') {
        if (isset($notice['error'])) {
            turnitintool_box_start('generalbox boxwidthwide boxaligncenter error', 'errorbox');
            echo $notice['message'];
            turnitintool_box_end();
        } else {
            $notice=NULL;
        }
        echo turnitintool_introduction($cm,$turnitintool,$notice);
    }
    
    if ($do=='submissions') {
        echo turnitintool_view_student_submissions($cm,$turnitintool);
        if (isset($notice["error"])) {
            turnitintool_box_start('generalbox boxwidthwide boxaligncenter error', 'errorbox');
            echo $notice["error"];
            turnitintool_box_end();
        }
        echo turnitintool_view_submission_form($cm,$turnitintool);
    }
    
    if ($do=='allsubmissions') {
        if (!empty($notice)) {
            turnitintool_box_start('generalbox boxwidthwide boxaligncenter error', 'errorbox');
            echo $notice;
            turnitintool_box_end();
        }
        if (isset($param_ob)) {
            $ob=$param_ob;
        } else {
            $ob=1;
        }
        echo turnitintool_view_all_submissions($cm,$turnitintool,$ob);
    }
    
    if ($do=='notes') {
        echo turnitintool_view_notes($cm,$turnitintool,$param_s,$_POST);
        if (isset($notice['error'])) {
            turnitintool_box_start('generalbox boxwidthwide boxaligncenter error', 'errorbox');
            echo $notice['message'];
            turnitintool_box_end();
        } else {
            $notice=NULL;
        }
        echo turnitintool_addedit_notes($cm,$turnitintool,$param_s,$_POST,$notice);

    }
    
    if ($do=='options') {
        if (!empty($notice)) {
            turnitintool_box_start('generalbox boxwidthwide boxaligncenter', 'general');
            echo $notice;
            turnitintool_box_end();
        }
        echo turnitintool_view_options($cm,$turnitintool);
    }
    
    // Finish the page
    echo '</div>';
    turnitintool_footer($course);

/* ?> */