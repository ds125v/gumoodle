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
 * Script to send alert for inappropriate posts, or show form for it.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_forumng.php');

$postid = required_param('p', PARAM_INT);
$cloneid = optional_param('clone', 0, PARAM_INT);
$pageparams = array('p'=>$postid);
if ($cloneid) {
    $pageparams['clone'] = $cloneid;
}

$post = mod_forumng_post::get_from_id($postid, $cloneid);
$discussion = $post->get_discussion();
$d = $discussion->get_id();
$forum = $post->get_forum();
$forumngid = $forum->get_id();
$course = $forum->get_course();


// Check permission
$post->require_view();

if (!$post->can_alert($whynot)) {
    print_error($whynot, 'forumng');
}

// Set up page
$pagename = get_string('alert_pagename', 'forumng');
$url = new moodle_url('mod/forumng/alert.php', $pageparams);
$out = $discussion->init_page($url, $pagename);

// Create the alert form
require_once('alert_form.php');
$customdata = (object)array(
        'forumname' => $forum->get_name(),
        'discussionid' => $d,
        'postid' => $postid,
        'cloneid' => $cloneid,
        'email' => $USER->email,
        'username' => $USER->username,
        'ip' => getremoteaddr(),
        'fullname' => fullname($USER, true),
        'coursename' => $course->shortname,
        'url' => $CFG->wwwroot . '/mod/forumng/discuss.php?' .
                $discussion->get_link_params(mod_forumng::PARAM_PLAIN) . '#p'.$postid
);

$mform = new mod_forumng_alert_form('alert.php', $customdata);

// If cancelled, return to the post
if ($mform->is_cancelled()) {
    redirect('discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_PLAIN) .
            '#p' . $postid);
}

//if the alert form has been submitted successfully, send the email
if ($fromform = $mform->get_data()) {

    $alltext = get_string('alert_emailpreface', 'forumng', $customdata)."\n\n";

    // Print the reasons for reporting
    $alltext .= get_string('alert_reasons', 'forumng', $customdata)."\n";
    if (!empty($fromform->alert_condition1)) {
        $alltext .= '* '.get_string('alert_condition1', 'forumng')."\n";
    }
    if (!empty($fromform->alert_condition2)) {
        $alltext .= '* '.get_string('alert_condition2', 'forumng')."\n";
    }
    if (!empty($fromform->alert_condition3)) {
        $alltext .= '* '.get_string('alert_condition3', 'forumng')."\n";
    }
    if (!empty($fromform->alert_condition4)) {
        $alltext .= '* '.get_string('alert_condition4', 'forumng')."\n";
    }
    if (!empty($fromform->alert_condition5)) {
        $alltext .= '* '.get_string('alert_condition5', 'forumng')."\n";
    }
    if (!empty($fromform->alert_condition6)) {
        $alltext .= '* '.get_string('alert_condition6', 'forumng')."\n";
    }
    if (!empty($fromform->alert_conditionmore)) {
        $alltext .= "\n".$fromform->alert_conditionmore."\n";
    }
    //ccnote is only print when the email is sent to 2 different addresses
    //so that they can decide between themselves who should deal with the report
    $ccnote = '';

    $forumfields = $DB->get_record('forumng', array('id' => $forumngid), '*', MUST_EXIST);
    $siteemail = $CFG->forumng_reportunacceptable;
    $forumemail = $forum->get_reportingemail();
    $emailcopy = 1;
    if (!empty($siteemail) && $forumemail != null && $siteemail != $forumemail ) {
        $emailcopy = 2;
        $fakeuser1 = (object)array(
            'email' => $siteemail,
            'mailformat' => 1,
            'id' => 0,
            'ccnote' => get_string('alert_note', 'forumng', $forumemail )
            );
        $fakeuser2 = (object)array(
            'email' => $forumemail,
            'mailformat' => 1,
            'id' => 0,
            'ccnote' => get_string('alert_note', 'forumng', $siteemail )
            );

    } else if (!empty($siteemail)) {
        $fakeuser = (object)array(
            'email' => $siteemail,
            'mailformat' => 1,
            'id' => 0
        );
    } else {
        $fakeuser = (object)array(
            'email' => $forumemail,
            'mailformat' => 1,
            'id' => 0
        );
    }

    $from = $USER;

    $subject = get_string('alert_emailsubject', 'forumng', $customdata);
    if ($emailcopy == 1) {
        $alltext .= get_string('alert_emailappendix', 'forumng' );
        if (!email_to_user($fakeuser, $from, $subject, $alltext)) {
            print_error('error_sendalert', 'forumng', $fakeuser->email);
        }
    } else {
        //Send 2 emails
        $alltext1 =$fakeuser1->ccnote. "\n\n". $alltext . "\n" .
                get_string('alert_emailappendix', 'forumng' );
        if (!email_to_user($fakeuser1, $from, $subject, $alltext1)) {
            print_error('error_sendalert', 'forumng', $fakeuser1->email);
        }
        $alltext2 =$fakeuser2->ccnote. "\n\n". $alltext . "\n" .
                get_string('alert_emailappendix', 'forumng' );
        if (!email_to_user($fakeuser2, $from, $subject, $alltext2)) {
            print_error('error_sendalert', 'forumng', $fakeuser2->email);
        }
    }
    // Log it after senting out
    $post->log('report post');

    print $out->header();

    print $out->box(get_string('alert_feedback', 'forumng'));
    print $out->continue('discuss.php?' .
            $discussion->get_link_params(mod_forumng::PARAM_HTML) . '#p' . $postid);

} else {
    //show the alert form
    print $out->header();
    print $mform->display();
}

print $out->footer();
