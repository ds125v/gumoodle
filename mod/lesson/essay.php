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
 * Provides the interface for grading essay questions
 *
 * @package    mod
 * @subpackage lesson
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/lesson/locallib.php');
require_once($CFG->dirroot.'/mod/lesson/essay_form.php');
require_once($CFG->libdir.'/eventslib.php');

$id   = required_param('id', PARAM_INT);             // Course Module ID
$mode = optional_param('mode', 'display', PARAM_ALPHA);

$cm = get_coursemodule_from_id('lesson', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$lesson = new lesson($DB->get_record('lesson', array('id' => $cm->instance), '*', MUST_EXIST));

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/lesson:edit', $context);

$url = new moodle_url('/mod/lesson/essay.php', array('id'=>$id));
if ($mode !== 'display') {
    $url->param('mode', $mode);
}
$PAGE->set_url($url);

$attempt = new stdClass();
$user = new stdClass();
$attemptid = optional_param('attemptid', 0, PARAM_INT);

if ($attemptid > 0) {
    $attempt = $DB->get_record('lesson_attempts', array('id' => $attemptid));
    $answer = $DB->get_record('lesson_answers', array('lessonid' => $lesson->id, 'pageid' => $attempt->pageid));
    $user = $DB->get_record('user', array('id' => $attempt->userid));
    $scoreoptions = array();
    if ($lesson->custom) {
        $i = $answer->score;
        while ($i >= 0) {
            $scoreoptions[$i] = (string)$i;
            $i--;
        }
    } else {
        $scoreoptions[0] = get_string('nocredit', 'lesson');
        $scoreoptions[1] = get_string('credit', 'lesson');
    }
}

/// Handle any preprocessing before header is printed - based on $mode
switch ($mode) {
    case 'grade':
        // Grading form - get the necessary data
        require_sesskey();

        if (empty($attempt)) {
            print_error('cannotfindattempt', 'lesson');
        }
        if (empty($user)) {
            print_error('cannotfinduser', 'lesson');
        }
        if (empty($answer)) {
            print_error('cannotfindanswer', 'lesson');
        }
        break;

    case 'update':
        require_sesskey();

        if (empty($attempt)) {
            print_error('cannotfindattempt', 'lesson');
        }
        if (empty($user)) {
            print_error('cannotfinduser', 'lesson');
        }

        $mform = new essay_grading_form(null, array('scoreoptions'=>$scoreoptions, 'user'=>$user));
        if ($mform->is_cancelled()) {
            redirect("$CFG->wwwroot/mod/lesson/essay.php?id=$cm->id");
        }
        if ($form = $mform->get_data()) {
            if (!$grades = $DB->get_records('lesson_grades', array("lessonid"=>$lesson->id, "userid"=>$attempt->userid), 'completed', '*', $attempt->retry, 1)) {
                print_error('cannotfindgrade', 'lesson');
            }

            $essayinfo = new stdClass;
            $essayinfo = unserialize($attempt->useranswer);

            $essayinfo->graded = 1;
            $essayinfo->score = $form->score;
            $essayinfo->response = clean_param($form->response, PARAM_RAW);
            $essayinfo->sent = 0;
            if (!$lesson->custom && $essayinfo->score == 1) {
                $attempt->correct = 1;
            } else {
                $attempt->correct = 0;
            }

            $attempt->useranswer = serialize($essayinfo);

            $DB->update_record('lesson_attempts', $attempt);

            // Get grade information
            $grade = current($grades);
            $gradeinfo = lesson_grade($lesson, $attempt->retry, $attempt->userid);

            // Set and update
            $updategrade = new stdClass();
            $updategrade->id = $grade->id;
            $updategrade->grade = $gradeinfo->grade;
            $DB->update_record('lesson_grades', $updategrade);
            // Log it
            add_to_log($course->id, 'lesson', 'update grade', "essay.php?id=$cm->id", $lesson->name, $cm->id);

            $lesson->add_message(get_string('changessaved'), 'notifysuccess');

            // update central gradebook
            lesson_update_grades($lesson, $grade->userid);

            redirect(new moodle_url('/mod/lesson/essay.php', array('id'=>$cm->id)));
        } else {
            print_error('invalidformdata');
        }
        break;
    case 'email':
        // Sending an email(s) to a single user or all
        require_sesskey();

        // Get our users (could be singular)
        if ($userid = optional_param('userid', 0, PARAM_INT)) {
            $queryadd = " AND userid = ?";
            if (! $users = $DB->get_records('user', array('id' => $userid))) {
                print_error('cannotfinduser', 'lesson');
            }
        } else {
            $queryadd = '';
            $params = array ("lessonid" => $lesson->id);
            // Need to use inner view to avoid distinct + text
            if (!$users = $DB->get_records_sql("
                SELECT u.*
                  FROM {user} u
                  JOIN (
                           SELECT DISTINCT userid
                             FROM {lesson_attempts}
                            WHERE lessonid = :lessonid
                       ) ui ON u.id = ui.userid", $params)) {
                print_error('cannotfinduser', 'lesson');
            }
        }

        $pages = $lesson->load_all_pages();
        foreach ($pages as $key=>$page) {
            if ($page->qtype !== LESSON_PAGE_ESSAY) {
                unset($pages[$key]);
            }
        }

        // Get only the attempts that are in response to essay questions
        list($usql, $params) = $DB->get_in_or_equal(array_keys($pages));
        if (!empty($queryadd)) {
            $params[] = $userid;
        }
        if (!$attempts = $DB->get_records_select('lesson_attempts', "pageid $usql".$queryadd, $params)) {
            print_error('nooneansweredthisquestion', 'lesson');
        }
        // Get the answers
        list($answerUsql, $parameters) = $DB->get_in_or_equal(array_keys($pages));
        array_unshift($parameters, $lesson->id);
        if (!$answers = $DB->get_records_select('lesson_answers', "lessonid = ? AND pageid $answerUsql", $parameters, '', 'pageid, score')) {
            print_error('cannotfindanswer', 'lesson');
        }
        $options = new stdClass;
        $options->noclean = true;

        foreach ($attempts as $attempt) {
            $essayinfo = unserialize($attempt->useranswer);
            if ($essayinfo->graded && !$essayinfo->sent) {
                // Holds values for the essayemailsubject string for the email message
                $a = new stdClass;

                // Set the grade
                $grades = $DB->get_records('lesson_grades', array("lessonid"=>$lesson->id, "userid"=>$attempt->userid), 'completed', '*', $attempt->retry, 1);
                $grade  = current($grades);
                $a->newgrade = $grade->grade;

                // Set the points
                if ($lesson->custom) {
                    $a->earned = $essayinfo->score;
                    $a->outof  = $answers[$attempt->pageid]->score;
                } else {
                    $a->earned = $essayinfo->score;
                    $a->outof  = 1;
                }

                // Set rest of the message values
                $currentpage = $lesson->load_page($attempt->pageid);
                $a->question = format_text($currentpage->contents, $currentpage->contentsformat, $options);
                $a->response = s($essayinfo->answer);
                $a->comment  = s($essayinfo->response);

                // Fetch message HTML and plain text formats
                $message  = get_string('essayemailmessage2', 'lesson', $a);
                $plaintext = format_text_email($message, FORMAT_HTML);

                // Subject
                $subject = get_string('essayemailsubject', 'lesson', format_string($pages[$attempt->pageid]->title,true));

                $eventdata = new stdClass();
                $eventdata->modulename       = 'lesson';
                $eventdata->userfrom         = $USER;
                $eventdata->userto           = $users[$attempt->userid];
                $eventdata->subject          = $subject;
                $eventdata->fullmessage      = $plaintext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml  = $message;
                $eventdata->smallmessage     = '';

                // Required for messaging framework
                $eventdata->component = 'mod_lesson';
                $eventdata->name = 'graded_essay';

                message_send($eventdata);
                $essayinfo->sent = 1;
                $attempt->useranswer = serialize($essayinfo);
                $DB->update_record('lesson_attempts', $attempt);
                // Log it
                add_to_log($course->id, 'lesson', 'update email essay grade', "essay.php?id=$cm->id", format_string($pages[$attempt->pageid]->title,true).': '.fullname($users[$attempt->userid]), $cm->id);
            }
        }
        $lesson->add_message(get_string('emailsuccess', 'lesson'), 'notifysuccess');
        redirect(new moodle_url('/mod/lesson/essay.php', array('id'=>$cm->id)));
        break;
    case 'display':  // Default view - get the necessary data
    default:
        // Get lesson pages that are essay
        $pages = $lesson->load_all_pages();
        foreach ($pages as $key=>$page) {
            if ($page->qtype !== LESSON_PAGE_ESSAY) {
                unset($pages[$key]);
            }
        }
        if (count($pages) > 0) {
            $params = array ("lessonid" => $lesson->id, "qtype" => LESSON_PAGE_ESSAY);
            // Get only the attempts that are in response to essay questions
            list($usql, $parameters) = $DB->get_in_or_equal(array_keys($pages));
            if ($essayattempts = $DB->get_records_select('lesson_attempts', 'pageid '.$usql, $parameters)) {
                // Get all the users who have taken this lesson, order by their last name
                $ufields = user_picture::fields('u');
                list($sort, $sortparams) = users_order_by_sql('u');
                $params = array_merge($params, $sortparams);
                $sql = "SELECT DISTINCT $ufields
                        FROM {user} u,
                             {lesson_attempts} a
                        WHERE a.lessonid = :lessonid and
                              u.id = a.userid
                        ORDER BY $sort";
                if (!$users = $DB->get_records_sql($sql, $params)) {
                    $mode = 'none'; // not displaying anything
                    $lesson->add_message(get_string('noonehasanswered', 'lesson'));
                }
            } else {
                $mode = 'none'; // not displaying anything
                $lesson->add_message(get_string('noonehasanswered', 'lesson'));
            }
        } else {
            $mode = 'none'; // not displaying anything
            $lesson->add_message(get_string('noessayquestionsfound', 'lesson'));
        }
        break;
}
// Log it
add_to_log($course->id, 'lesson', 'view grade', "essay.php?id=$cm->id", get_string('manualgrading', 'lesson'), $cm->id);

$lessonoutput = $PAGE->get_renderer('mod_lesson');
echo $lessonoutput->header($lesson, $cm, 'essay', false, null, get_string('manualgrading', 'lesson'));

switch ($mode) {
    case 'display':
        // Expects $user, $essayattempts and $pages to be set already

        // Group all the essays by userid
        $studentessays = array();
        foreach ($essayattempts as $essay) {
            // Not very nice :) but basically
            //   this organizes the essays so we know how many
            //   times a student answered an essay per try and per page
            $studentessays[$essay->userid][$essay->pageid][$essay->retry][] = $essay;
        }

        // Setup table
        $table = new html_table();
        $table->head = array(get_string('name'), get_string('essays', 'lesson'), get_string('email', 'lesson'));
        $table->attributes['class'] = 'standardtable generaltable';
        $table->align = array('left', 'left', 'left');
        $table->wrap = array('nowrap', 'nowrap', '');

        // Cycle through all the students
        foreach (array_keys($studentessays) as $userid) {
            $studentname = fullname($users[$userid], true);
            $essaylinks = array();

            // Number of attempts on the lesson
            $attempts = $DB->count_records('lesson_grades', array('userid'=>$userid, 'lessonid'=>$lesson->id));

            // Go through each essay page
            foreach ($studentessays[$userid] as $page => $tries) {
                $count = 0;

                // Go through each attempt per page
                foreach($tries as $try) {
                    if ($count == $attempts) {
                        break;  // Stop displaying essays (attempt not completed)
                    }
                    $count++;

                    // Make sure they didn't answer it more than the max number of attmepts
                    if (count($try) > $lesson->maxattempts) {
                        $essay = $try[$lesson->maxattempts-1];
                    } else {
                        $essay = end($try);
                    }

                    // Start processing the attempt
                    $essayinfo = unserialize($essay->useranswer);

                    // link for each essay
                    $url = new moodle_url('/mod/lesson/essay.php', array('id'=>$cm->id,'mode'=>'grade','attemptid'=>$essay->id,'sesskey'=>sesskey()));
                    $attributes = array();
                    // Different colors for all the states of an essay (graded, if sent, not graded)
                    if (!$essayinfo->graded) {
                        $attributes['class'] = "graded";
                    } elseif (!$essayinfo->sent) {
                        $attributes['class'] = "sent";
                    } else {
                        $attributes['class'] = "ungraded";
                    }
                    $essaylinks[] = html_writer::link($url, userdate($essay->timeseen, get_string('strftimedatetime')).' '.format_string($pages[$essay->pageid]->title,true), $attributes);
                }
            }
            // email link for this user
            $url = new moodle_url('/mod/lesson/essay.php', array('id'=>$cm->id,'mode'=>'email','userid'=>$userid,'sesskey'=>sesskey()));
            $emaillink = html_writer::link($url, get_string('emailgradedessays', 'lesson'));

            $table->data[] = array($OUTPUT->user_picture($users[$userid], array('courseid'=>$course->id)).$studentname, implode("<br />", $essaylinks), $emaillink);
        }

        // email link for all users
        $url = new moodle_url('/mod/lesson/essay.php', array('id'=>$cm->id,'mode'=>'email','sesskey'=>sesskey()));
        $emailalllink = html_writer::link($url, get_string('emailallgradedessays', 'lesson'));

        $table->data[] = array(' ', ' ', $emailalllink);

        echo html_writer::table($table);
        break;
    case 'grade':
        // Grading form
        // Expects the following to be set: $attemptid, $answer, $user, $page, $attempt
        $essayinfo = unserialize($attempt->useranswer);
        $currentpage = $lesson->load_page($attempt->pageid);

        $mform = new essay_grading_form(null, array('scoreoptions'=>$scoreoptions, 'user'=>$user));
        $data = new stdClass;
        $data->id = $cm->id;
        $data->attemptid = $attemptid;
        $data->score = $essayinfo->score;
        $data->question = format_string($currentpage->contents, $currentpage->contentsformat);
        $data->studentanswer = format_string($essayinfo->answer, $essayinfo->answerformat);
        $data->response = $essayinfo->response;
        $mform->set_data($data);

        $mform->display();
        break;
}

echo $OUTPUT->footer();
