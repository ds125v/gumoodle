<?php

/**
 * Wiki course format function library
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC, 
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: formatlib.php,v 1.12 2007/11/26 13:11:34 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Format_Course
 */

/****************************************************
	CREACIÓ D'UN NOU CURS DFWIKI:
	codi extret (i modificat) del course/mod.php
    retorna el course_module
*****************************************************/

function new_course_module_wiki() {
    global $CFG,$course;
    //define the new course_module a $newmod:
    $newmod = New stdClass;
    $newmod->course = $course->id;
    $dfwikimod = get_record('modules', 'name', 'wiki');
    $newmod->module = $dfwikimod->id;
    $newmod->name = "Wiki course";
    $newmod->modulename = "wiki";
    $newmod->editable = 1;
    $newmod->visible = 0; //hidden: only admin edit the wiki.

    $newmod->editor = 'nwiki';
    $newmod->wikicourse = $course->id;
    //creat instance of new course_module:
    $return = wiki_add_instance($newmod);
    $moderr = "$CFG->dirroot/mod/wiki/moderr.html";
    //control. instance (dfwiki) correctly created:
    if (!$return) {
        if (file_exists($moderr)) {
            $form = $newmod;
            include_once($moderr);
            die;
        }
        error(get_string('addinstanceerror','wiki',$newmod->modulename), "view.php?id=$course->id");
    }
    if (is_string($return)) {
        error($return, "view.php?id=$course->id");
    }
    $newmod->instance = $return;
    // course_modules and course_sections each contain a reference // to each other, so we have to update one of them twice.

    if (! $newmod->coursemodule = add_course_module($newmod) ) {
        error(get_string('addcourseerror','wiki'));
    }
    if (! $newmod->section = add_mod_to_section($newmod) ) {
        error(get_string('addcoursesectionerror','wiki'));
    }
    if (! set_field("course_modules", "section", $newmod->section, "id", $newmod->coursemodule)) {
        error(get_string('updatecourseerror','wiki'));
    }
    // make sure visibility is set correctly (in particular in calendar)
    set_coursemodule_visible($newmod->coursemodule, $newmod->visible);
    //load modinfo in table "course":
    rebuild_course_cache($course->id);
    //this is necessary? //---------------------------------------------------------------------------------------

    if (isset($newmod->redirect)) {
        $SESSION->returnpage = $newmod->redirecturl;
    } else {
        $SESSION->returnpage = "$CFG->wwwroot/mod/wiki/view.php?id=$newmod->coursemodule";
    }
    add_to_log($course->id, "course", "add mod", "../mod/wiki/view.php?id=$newmod->coursemodule", "$newmod->modulename$newmod->instance",$newmod->coursemodule);
    add_to_log($course->id, "wiki", "add", "view.php?id=$newmod->coursemodule", "$newmod->instance", $newmod->coursemodule);
    //---------------------------------------------------------------------------------------
    return $newmod->coursemodule;
}

/**************************************************************
	File for view a wiki course. Copy (and modification) from
	/mod/dfwiki/dfwikilib.php
	*************************************************************/

function wiki_main_view_setup() {
    global $CFG,$WS,$USER,$COURSE;
    optional_param($WS->page, false,PARAM_PATH); // Wiki Page Name

    $WS->page=stripslashes($WS->page);
    $WS->pageaction = 'view';
    $WS->groupmember->groupid = 0;
    //Look page version
    $max = get_record_sql('SELECT MAX(`version`) AS maxim
			FROM '. $CFG->prefix.'wiki_pages
			WHERE `pagename`=\''.addslashes($WS->page).'\' AND `dfwiki`='.$WS->dfwiki->id);
    //Loading data of page
    if ($max){
        $WS->pagedata = get_record("wiki_pages", "pagename", addslashes($WS->page),'dfwiki',$WS->dfwiki->id,'version',$max->maxim);
    } else{
        $WS->pagedata->pagename = $WS->page;
        $WS->pagedata->version = 0;
        $WS->pagedata->created = time();
        $WS->pagedata->editable = $WS->dfwiki->editable;
    }
    $WS->uid=optional_param('uid',NULL,PARAM_INT);
    //load user data
    if($WS->uid){
        $WS->member->id = $WS->uid;
    }else if(isset($WS->dfform['selectstudent'])){
        $WS->member->id = $WS->dfform['selectstudent'];
    }else{
        $WS->member->id = $USER->id;
        //for commune wiki or students in group
        if($WS->dfwiki->studentmode == '0'){
            $WS->member->id = '0';
        }
    }
    add_to_log($COURSE->id, "wiki", "$WS->pageaction", "view.php?id={$WS->cm->id}&amp;page=$WS->page", "Wiki{$WS->dfwiki->name}:$WS->page");
}

function wiki_main_view_content(){
    global $WS;
    $WS->dfcontent = optional_param('dfcontent',NULL,PARAM_INT);
    wiki_dfform_param($WS);
    $WS->dfformcontent = optional_param('dfformcontent',NULL,PARAM_RAW);
    $WS->dfsetup = optional_param('dfsetup',NULL,PARAM_INT);
    $WS->page = optional_param('pagename',NULL,PARAM_FILE);
    $WS->ver = optional_param('ver',NULL,PARAM_TEXT);
    $WS->enpage = optional_param('enpage',NULL,PARAM_FILE);
    $WS->wiki_format = array();
    wiki_setup_content();
    wiki_print_content ($WS);
}
?>

