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
 * Book view page
 *
 * @package    mod_book
 * @copyright  2004-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id        = optional_param('id', 0, PARAM_INT);        // Course Module ID
$bid       = optional_param('b', 0, PARAM_INT);         // Book id
$chapterid = optional_param('chapterid', 0, PARAM_INT); // Chapter ID
$edit      = optional_param('edit', -1, PARAM_BOOL);    // Edit mode

// =========================================================================
// security checks START - teachers edit; students view
// =========================================================================
if ($id) {
    $cm = get_coursemodule_from_id('book', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $book = $DB->get_record('book', array('id'=>$cm->instance), '*', MUST_EXIST);
} else {
    $book = $DB->get_record('book', array('id'=>$bid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('book', $book->id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $id = $cm->id;
}

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/book:read', $context);

$allowedit  = has_capability('mod/book:edit', $context);
$viewhidden = has_capability('mod/book:viewhiddenchapters', $context);

if ($allowedit) {
    if ($edit != -1 and confirm_sesskey()) {
        $USER->editing = $edit;
    } else {
        if (isset($USER->editing)) {
            $edit = $USER->editing;
        } else {
            $edit = 0;
        }
    }
} else {
    $edit = 0;
}

// read chapters
$chapters = book_preload_chapters($book);

if ($allowedit and !$chapters) {
    redirect('edit.php?cmid='.$cm->id); // No chapters - add new one.
}
// Check chapterid and read chapter data
if ($chapterid == '0') { // Go to first chapter if no given.
    foreach ($chapters as $ch) {
        if ($edit) {
            $chapterid = $ch->id;
            break;
        }
        if (!$ch->hidden) {
            $chapterid = $ch->id;
            break;
        }
    }
}

if (!$chapterid or !$chapter = $DB->get_record('book_chapters', array('id'=>$chapterid, 'bookid'=>$book->id))) {
    print_error('errorchapter', 'mod_book', new moodle_url('/course/view.php', array('id'=>$course->id)));
}

// chapter is hidden for students
if ($chapter->hidden and !$viewhidden) {
    print_error('errorchapter', 'mod_book', new moodle_url('/course/view.php', array('id'=>$course->id)));
}

$PAGE->set_url('/mod/book/view.php', array('id'=>$id, 'chapterid'=>$chapterid));


// Unset all page parameters.
unset($id);
unset($bid);
unset($chapterid);

// Security checks END.

add_to_log($course->id, 'book', 'view', 'view.php?id='.$cm->id.'&amp;chapterid='.$chapter->id, $book->id, $cm->id);

// Read standard strings.
$strbooks = get_string('modulenameplural', 'mod_book');
$strbook  = get_string('modulename', 'mod_book');
$strtoc   = get_string('toc', 'mod_book');

// prepare header
$PAGE->set_title($book->name);
$PAGE->set_heading($course->fullname);

book_add_fake_block($chapters, $chapter, $book, $cm, $edit);

// prepare chapter navigation icons
$previd = null;
$nextid = null;
$last = null;
foreach ($chapters as $ch) {
    if (!$edit and $ch->hidden) {
        continue;
    }
    if ($last == $chapter->id) {
        $nextid = $ch->id;
        break;
    }
    if ($ch->id != $chapter->id) {
        $previd = $ch->id;
    }
    $last = $ch->id;
}

$chnavigation = '';
if ($previd) {
    $chnavigation .= '<a title="'.get_string('navprev', 'book').'" href="view.php?id='.$cm->id.
            '&amp;chapterid='.$previd.'"><img src="'.$OUTPUT->pix_url('nav_prev', 'mod_book').'" class="bigicon" alt="'.get_string('navprev', 'book').'"/></a>';
} else {
    $chnavigation .= '<img src="'.$OUTPUT->pix_url('nav_prev_dis', 'mod_book').'" class="bigicon" alt="" />';
}
if ($nextid) {
    $chnavigation .= '<a title="'.get_string('navnext', 'book').'" href="view.php?id='.$cm->id.
            '&amp;chapterid='.$nextid.'"><img src="'.$OUTPUT->pix_url('nav_next', 'mod_book').'" class="bigicon" alt="'.get_string('navnext', 'book').'" /></a>';
} else {
    $sec = '';
    if ($section = $DB->get_record('course_sections', array('id'=>$cm->section))) {
        $sec = $section->section;
    }
    if ($course->id == $SITE->id) {
        $returnurl = "$CFG->wwwroot/";
    } else {
        $returnurl = "$CFG->wwwroot/course/view.php?id=$course->id#section-$sec";
    }
    $chnavigation .= '<a title="'.get_string('navexit', 'book').'" href="'.$returnurl.'"><img src="'.$OUTPUT->pix_url('nav_exit', 'mod_book').
            '" class="bigicon" alt="'.get_string('navexit', 'book').'" /></a>';

    // we are cheating a bit here, viewing the last page means user has viewed the whole book
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

// =====================================================
// Book display HTML code
// =====================================================

echo $OUTPUT->header();

// upper nav
echo '<div class="navtop">'.$chnavigation.'</div>';

// chapter itself
echo $OUTPUT->box_start('generalbox book_content');
if (!$book->customtitles) {
    $hidden = $chapter->hidden ? 'dimmed_text' : '';
    if (!$chapter->subchapter) {
        $currtitle = book_get_chapter_title($chapter->id, $chapters, $book, $context);
        echo '<p class="book_chapter_title '.$hidden.'">'.$currtitle.'</p>';
    } else {
        $currtitle = book_get_chapter_title($chapters[$chapter->id]->parent, $chapters, $book, $context);
        $currsubtitle = book_get_chapter_title($chapter->id, $chapters, $book, $context);
        echo '<p class="book_chapter_title '.$hidden.'">'.$currtitle.'<br />'.$currsubtitle.'</p>';
    }
}
$chaptertext = file_rewrite_pluginfile_urls($chapter->content, 'pluginfile.php', $context->id, 'mod_book', 'chapter', $chapter->id);
echo format_text($chaptertext, $chapter->contentformat, array('noclean'=>true, 'context'=>$context));

echo $OUTPUT->box_end();

// lower navigation
echo '<div class="navbottom">'.$chnavigation.'</div>';

echo $OUTPUT->footer();
