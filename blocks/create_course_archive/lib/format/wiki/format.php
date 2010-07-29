<?php

/**
 * Wiki course format
 *
 * This file contains wiki format course
 *
 * @author DFWiki LABS
 * @author Marc Alier i Forment
 * @author David Castro, Ferran Recio, Jordi Piguillem, UPC,
 * and members of DFWikiteam listed at http://morfeo.upc.edu/crom
 * @version  $Id: format.php,v 1.14 2008/01/15 12:22:07 pigui Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package Wiki_Format_Course
 */
require_once($CFG->dirroot.'/mod/wiki/class/wikistorage.class.php');
require_once($CFG->dirroot.'/mod/wiki/weblib.php');
//Scritp WIKI_TREE
$prop = null;
$prop->type = 'text/javascript';
$prop->src = '../mod/wiki/editor/wiki_tree.js';
wiki_script('', $prop);
$WS = new storage();
global $COURSE;
//variable for parser (/mod/wiki/wiki/sintax.php) and use for creation the different links in modul or course wiki //we distinguished between links of modules and courses:

$WS->dfcourse = 1;
$full_wiki = TRUE;
require_once($CFG->dirroot.'/mod/wiki/lib.php');
require_once('formatlib.php');
$preferred_width_left = bounded_number(100, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);
// Bounds for block widths
define('BLOCK_L_MIN_WIDTH', 100);
define('BLOCK_L_MAX_WIDTH', 210);
define('BLOCK_R_MIN_WIDTH', 100);
define('BLOCK_R_MAX_WIDTH', 210);
$preferred_width_left = optional_param('preferred_width_left', blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]));
$preferred_width_right = optional_param('preferred_width_right', blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]));
$preferred_width_left = min($preferred_width_left, BLOCK_L_MAX_WIDTH);
$preferred_width_left = max($preferred_width_left, BLOCK_L_MIN_WIDTH);
$preferred_width_right = min($preferred_width_right, BLOCK_R_MAX_WIDTH);
$preferred_width_right = max($preferred_width_right, BLOCK_R_MIN_WIDTH);
$wikimod= get_record("modules","name","wiki");
$min = get_record_sql('SELECT MIN(`id`) AS minim FROM '.$CFG->prefix.'course_modules WHERE `course`=\''.$course->id.'\' AND `module`=\''.$wikimod->id.'\'');
if( $min->minim == NULL ) {
    //crete a new curs wiki:
    $coursemodule = new_course_module_wiki();
    //initializes variables to see the new course wiki:
    if (! $WS->cm = get_coursemodule_from_id('wiki',$coursemodule)) {
        error(get_string('cmincorrect','wiki'));
    }
    if (! $WS->dfwiki = get_record("wiki", "id", $WS->cm->instance)) {
        error(get_string('cmincorrect','wiki'));
    }
} else {
    //initializes WS class
    $WS->set_info($min->minim);
    $WS->page = optional_param('page',NULL,PARAM_FILE);
    //If we come from an internal Link, we loaded the page indicated by the name of the Link:
    if($WS->page) {
        //This does not serve if there is diverse wikis in a same course. //(2 differents wiki's can have identical names):

        $id = get_record_sql('SELECT id
					FROM '. $CFG->prefix.'wiki WHERE wikicourse='.addslashes($course->id));
        if (! $WS->dfwiki = get_record("wiki","id",$id->id,"course",$course->id )) {
            error(get_string('cmincorrect','wiki'));
        }
        $id = wiki_page_current_version_number( $WS->page, $WS);
        $pagina = get_record('wiki_pages','id',$id,'pagename',addslashes($WS->page),'dfwiki',$WS->dfwiki->id);
    }
    //If we do not come from a Link we showed the first page of the wiki: else    {
        $consulta = get_record('course_modules', 'id', $min->minim);
        if (! $WS->dfwiki = get_record("wiki", "id", $consulta->instance, "wikicourse",$course->id)) {
            if (! $WS->dfwiki = get_record("wiki", "id", $consulta->instance)){
                error(get_string('cmincorrect','wiki'));
            }
            //If the main wiki is deleted. Set the next wiki of the course //as the main wiki.

            else{
                //Hide editing wiki for students
                $consulta->visible=0;
                update_record('course_modules', $consulta);
                //Set wiki as main wiki of the wiki course
                $WS->dfwiki->wikicourse=$course->id;
                update_record('wiki', $WS->dfwiki);
                //Refresh de la secciï¿½n de social_activities. //COMO?

            }
        }
        $WS->page = wiki_get_real_pagename ($WS->dfwiki->pagename);
    }
    //initializes necessary variables to visualize the wiki course: //(Code extracted and modified of mod/wiki/view.php)

    if (! $WS->cm = get_coursemodule_from_instance("wiki", $WS->dfwiki->id, $COURSE->id)) {
        error(get_string('cmincorrect','wiki'));
    }
}
$editing = $PAGE->user_is_editing();
$COURSE->format = clean_param('wiki', PARAM_ALPHA);
if (!file_exists($CFG->dirroot.'/course/format/wiki/format.php')) {
    $COURSE->format = 'weeks'; // Default format is weeks

}
$PAGE = page_create_object(PAGE_COURSE_VIEW, $COURSE->id);
$pageblocks = blocks_setup($PAGE);
if (!isset($USER->editing)) {
    $USER->editing = false;
}
//setup the module
wiki_main_view_setup();
if (blocks_have_content($pageblocks, BLOCK_POS_LEFT)){
    $prop = null;
    $prop->spacing = 0;
    $prop->id = "layout-table";
    $prop->idtd = "left-column";
    $prop->classtd = "blockcourse";
    wiki_table_start($prop);
    $prop = null;
    $prop->width = $preferred_width_left.'px';
    wiki_table_start($prop);
    blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
    wiki_table_end();
    $prop = null;
    $prop->id = "middle-column";
    wiki_change_column($prop);
} else {
    $prop = null;
    $prop->spacing = 0;
    $prop->id = "layout-table";
    $prop->idtd = "middle-column";
    wiki_table_start($prop);
}
//setup and print social activities
$section = 0;
$thissection = $sections[$section];
$summaryformatoptions->noclean = true;
if (empty ($thissection->summary)){
    $thissection->summary= get_string('wiki_res', 'wiki');
}
$recurses= format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);
if (isediting($COURSE->id)) {
    $streditsummary = get_string('editsummary');
    $prop = null;
    $prop->src = $CFG->pixpath.'/t/edit.gif';
    $prop->alt = $streditsummary;
    $prop->class = 'icon edit';
    $image = wiki_img($prop, true);
    $prop = null;
    $prop->href = 'editsection.php?id='.$thissection->id;
    $prop->title = $streditsummary;
    $info = wiki_a($image, $prop, true);
    $recurses = $recurses.$info;
}
print_heading_block($recurses, 'outline');
print_section($COURSE, $thissection, $mods, $modnamesused);
if (isediting($COURSE->id)) {
    print_section_add_menus($COURSE, $section, $modnames);
}
wiki_br();
print_heading_block('Wiki', 'outline');
//print the course
wiki_main_view_content();
// The right column
if ((blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing)) {
    $prop = null;
    $prop->id = "right-column";
    $prop->class = "blockcourse";
    wiki_change_column($prop);
    $prop = null;
    $prop->width = $preferred_width_right.'px';
    wiki_table_start($prop);
    blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
    wiki_table_end();
}
wiki_table_end();
?>

