<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit and review page for grade categories and items
 *
 * @package   core_grades
 * @copyright 2008 Nicolas Connault
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/lib.php'; // for preferences
require_once $CFG->dirroot.'/grade/edit/tree/lib.php';

$courseid        = required_param('id', PARAM_INT);
$action          = optional_param('action', 0, PARAM_ALPHA);
$eid             = optional_param('eid', 0, PARAM_ALPHANUM);
$category        = optional_param('category', null, PARAM_INT);
$aggregationtype = optional_param('aggregationtype', null, PARAM_INT);
$showadvanced    = optional_param('showadvanced', -1, PARAM_BOOL); // sticky editing mode

$url = new moodle_url('/grade/edit/tree/index.php', array('id' => $courseid));
if($showadvanced!=-1) {
    $url->param("showadvanced",$showadvanced);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

/// Make sure they can even access this course
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('nocourseid');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('moodle/grade:manage', $context);

// todo $PAGE->requires->js_module() should be used here instead
$PAGE->requires->yui2_lib('event');
$PAGE->requires->yui2_lib('json');
$PAGE->requires->yui2_lib('connection');
$PAGE->requires->yui2_lib('dragdrop');
$PAGE->requires->yui2_lib('element');
$PAGE->requires->yui2_lib('container');
$PAGE->requires->yui2_lib('animation');
$PAGE->requires->js('/grade/edit/tree/functions.js');

/// return tracking object
$gpr = new grade_plugin_return(array('type'=>'edit', 'plugin'=>'tree', 'courseid'=>$courseid));
$returnurl = $gpr->get_return_url(null);

/// Build editing on/off buttons
if (!isset($USER->gradeediting)) {
    $USER->gradeediting = array();
}

$current_view = '';

if (has_capability('moodle/grade:manage', $context)) {
    if (!isset($USER->gradeediting[$course->id])) {
        $USER->gradeediting[$course->id] = 0;
    }

    if ($showadvanced == 1) {
        $USER->gradeediting[$course->id] = 1;
    } else if ($showadvanced == 0) {
        $USER->gradeediting[$course->id] = 0;
    }

    // page params for the turn editing on
    $options = $gpr->get_options();
    $options['sesskey'] = sesskey();

    if ($USER->gradeediting[$course->id]) {
        $options['showadvanced'] = 0;
        $current_view = 'fullview';
    } else {
        $options['showadvanced'] = 1;
        $current_view = 'simpleview';
    }

} else {
    $USER->gradeediting[$course->id] = 0;
    $buttons = '';
}

// Change category aggregation if requested
if (!is_null($category) && !is_null($aggregationtype) && confirm_sesskey()) {
    if (!$grade_category = grade_category::fetch(array('id'=>$category, 'courseid'=>$courseid))) {
        print_error('invalidcategoryid');
    }

    $data = new stdClass();
    $data->aggregation = $aggregationtype;
    grade_category::set_properties($grade_category, $data);
    $grade_category->update();

    grade_regrade_final_grades($courseid);
}

//first make sure we have proper final grades - we need it for locking changes
grade_regrade_final_grades($courseid);

// get the grading tree object
// note: total must be first for moving to work correctly, if you want it last moving code must be rewritten!
$gtree = new grade_tree($courseid, false, false);

if (empty($eid)) {
    $element = null;
    $object  = null;

} else {
    if (!$element = $gtree->locate_element($eid)) {
        print_error('invalidelementid', '', $returnurl);
    }
    $object = $element['object'];
}

$switch = grade_get_setting($course->id, 'aggregationposition', $CFG->grade_aggregationposition);

$strgrades             = get_string('grades');
$strgraderreport       = get_string('graderreport', 'grades');
$strcategoriesedit     = get_string('categoriesedit', 'grades');
$strcategoriesanditems = get_string('categoriesanditems', 'grades');

$moving = false;
$movingeid = false;

if ($action == 'moveselect') {
    if ($eid and confirm_sesskey()) {
        $movingeid = $eid;
        $moving=true;
    }
}

$grade_edit_tree = new grade_edit_tree($gtree, $movingeid, $gpr);

switch ($action) {
    case 'delete':
        if ($eid && confirm_sesskey()) {
            if (!$grade_edit_tree->element_deletable($element)) {
                // no deleting of external activities - they would be recreated anyway!
                // exception is activity without grading or misconfigured activities
                break;
            }
            $confirm = optional_param('confirm', 0, PARAM_BOOL);

            if ($confirm) {
                $object->delete('grade/report/grader/category');
                redirect($returnurl);

            } else {
                $PAGE->set_title($strgrades . ': ' . $strgraderreport);
                $PAGE->set_heading($course->fullname);
                echo $OUTPUT->header();
                $strdeletecheckfull = get_string('deletecheck', '', $object->get_name());
                $optionsyes = array('eid'=>$eid, 'confirm'=>1, 'sesskey'=>sesskey(), 'id'=>$course->id, 'action'=>'delete');
                $optionsno  = array('id'=>$course->id);
                $formcontinue = new single_button(new moodle_url('index.php', $optionsyes), get_string('yes'));
                $formcancel = new single_button(new moodle_url('index.php', $optionsno), get_string('no'), 'get');
                echo $OUTPUT->confirm($strdeletecheckfull, $formcontinue, $formcancel);
                echo $OUTPUT->footer();
                die;
            }
        }
        break;

    case 'autosort':
        //TODO: implement autosorting based on order of mods on course page, categories first, manual items last
        break;

    case 'move':
        if ($eid and confirm_sesskey()) {
            $moveafter = required_param('moveafter', PARAM_ALPHANUM);
            $first = optional_param('first', false,  PARAM_BOOL); // If First is set to 1, it means the target is the first child of the category $moveafter

            if(!$after_el = $gtree->locate_element($moveafter)) {
                print_error('invalidelementid', '', $returnurl);
            }

            $after = $after_el['object'];
            $sortorder = $after->get_sortorder();

            if (!$first) {
                $parent = $after->get_parent_category();
                $object->set_parent($parent->id);
            } else {
                $object->set_parent($after->id);
            }

            $object->move_after_sortorder($sortorder);

            redirect($returnurl);
        }
        break;

    default:
        break;
}

// Hide advanced columns if moving
if ($grade_edit_tree->moving) {
    $original_gradeediting = $USER->gradeediting[$course->id];
    $USER->gradeediting[$course->id] = 0;
}

$current_view_str = '';
if ($current_view != '') {
    if ($current_view == 'simpleview') {
        $current_view_str = get_string('simpleview', 'grades');
    } elseif ($current_view == 'fullview') {
        $current_view_str = get_string('fullview', 'grades');
    }
}

//if we go straight to the db to update an element we need to recreate the tree as
// $grade_edit_tree has already been constructed.
//Ideally we could do the updates through $grade_edit_tree to avoid recreating it
$recreatetree = false;

if ($data = data_submitted() and confirm_sesskey()) {
    // Perform bulk actions first
    if (!empty($data->bulkmove)) {
        $elements = array();

        foreach ($data as $key => $value) {
            if (preg_match('/select_(i[0-9]*)/', $key, $matches)) {
                $elements[] = $matches[1];
            }
        }

        $grade_edit_tree->move_elements($elements, $returnurl);
    }

    // Category and item field updates
    foreach ($data as $key => $value) {
        // Grade category text inputs
        if (preg_match('/^(aggregation|droplow|keephigh)_([0-9]+)$/', $key, $matches)) {
            $param = $matches[1];
            $aid   = $matches[2];

            // Do not allow negative values
            $value = clean_param($value, PARAM_INT);
            $value = ($value < 0) ? 0 : $value;

            $grade_category = grade_category::fetch(array('id'=>$aid, 'courseid'=>$courseid));
            $grade_category->$param = $value;

            $grade_category->update();
            grade_regrade_final_grades($courseid);

            $recreatetree = true;

        // Grade item text inputs
        } elseif (preg_match('/^(grademax|aggregationcoef|multfactor|plusfactor)_([0-9]+)$/', $key, $matches)) {
            $param = $matches[1];
            $aid   = $matches[2];

            $value = unformat_float($value);
            $value = clean_param($value, PARAM_NUMBER);

            $grade_item = grade_item::fetch(array('id'=>$aid, 'courseid'=>$courseid));

            if ($param === 'grademax' and $value < $grade_item->grademin) {
                // better not allow values lower than grade min
                $value = $grade_item->grademin;
            }
            $grade_item->$param = $value;

            $grade_item->update();
            grade_regrade_final_grades($courseid);

            $recreatetree = true;

        // Grade item checkbox inputs
        } elseif (preg_match('/^extracredit_([0-9]+)$/', $key, $matches)) { // Sum extra credit checkbox
            $aid   = $matches[1];
            $value = clean_param($value, PARAM_BOOL);

            $grade_item = grade_item::fetch(array('id'=>$aid, 'courseid'=>$courseid));
            $grade_item->aggregationcoef = $value;

            $grade_item->update();
            grade_regrade_final_grades($courseid);

            $recreatetree = true;

        // Grade category checkbox inputs
        } elseif (preg_match('/^aggregate(onlygraded|subcats|outcomes)_([0-9]+)$/', $key, $matches)) {
            $param = 'aggregate'.$matches[1];
            $aid    = $matches[2];
            $value = clean_param($value, PARAM_BOOL);

            $grade_category = grade_category::fetch(array('id'=>$aid, 'courseid'=>$courseid));
            $grade_category->$param = $value;

            $grade_category->update();
            grade_regrade_final_grades($courseid);

            $recreatetree = true;
        }
    }
}

print_grade_page_head($courseid, 'edittree', $current_view, get_string('categoriesedit', 'grades') . ': ' . $current_view_str);

// Print Table of categories and items
echo $OUTPUT->box_start('gradetreebox generalbox');

echo '<form id="gradetreeform" method="post" action="'.$returnurl.'">';
echo '<div>';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

//did we update something in the db and thus invalidate $grade_edit_tree?
if ($recreatetree) {
    $grade_edit_tree = new grade_edit_tree($gtree, $movingeid, $gpr);
}

echo html_writer::table($grade_edit_tree->table);

echo '<div id="gradetreesubmit">';
if (!$moving) {
    echo '<input class="advanced" type="submit" value="'.get_string('savechanges').'" />';
}

// We don't print a bulk move menu if there are no other categories than course category
if (!$moving && count($grade_edit_tree->categories) > 1) {
    echo '<br /><br />';
    echo '<input type="hidden" name="bulkmove" value="0" id="bulkmoveinput" />';
    $attributes = array('id'=>'menumoveafter');
    echo html_writer::label(get_string('moveselectedto', 'grades'), 'menumoveafter');
    echo html_writer::select($grade_edit_tree->categories, 'moveafter', '', array(''=>'choosedots'), $attributes);
    $OUTPUT->add_action_handler(new component_action('change', 'submit_bulk_move'), 'menumoveafter');
    echo '<div id="noscriptgradetreeform" class="hiddenifjs">
            <input type="submit" value="'.get_string('go').'" />
          </div>';
}

echo '</div>';

echo '</div></form>';

echo $OUTPUT->box_end();

// Print action buttons
echo $OUTPUT->container_start('buttons mdl-align');

if ($moving) {
    echo $OUTPUT->single_button(new moodle_url('index.php', array('id'=>$course->id)), get_string('cancel'), 'get');
} else {
    echo $OUTPUT->single_button(new moodle_url('category.php', array('courseid'=>$course->id)), get_string('addcategory', 'grades'), 'get');
    echo $OUTPUT->single_button(new moodle_url('item.php', array('courseid'=>$course->id)), get_string('additem', 'grades'), 'get');

    if (!empty($CFG->enableoutcomes)) {
        echo $OUTPUT->single_button(new moodle_url('outcomeitem.php', array('courseid'=>$course->id)), get_string('addoutcomeitem', 'grades'), 'get');
    }

    //echo $OUTPUT->(new moodle_url('index.php', array('id'=>$course->id, 'action'=>'autosort')), get_string('autosort', 'grades'), 'get');
}

echo $OUTPUT->container_end();

echo $OUTPUT->footer();

// Restore original show/hide preference if moving
if ($moving) {
    $USER->gradeediting[$course->id] = $original_gradeediting;
}
die;


