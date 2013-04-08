<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php' );

// need course
$id = optional_param('id', 0, PARAM_INT);

// security
require_login();
if (!empty($id)) {
    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    $PAGE->set_course($course);
    $context = $PAGE->context;
} else {
    $context = get_context_instance(CONTEXT_SYSTEM);
    $PAGE->set_context($context);
}
require_capability('moodle/course:update', $context);

// page/theme stuff
$PAGE->set_url('/blocks/corews/corews_test.php', array('id'=>$id));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('testpage', 'block_corews'));
$PAGE->set_heading(get_string('testpage', 'block_corews'));
echo $OUTPUT->header();

// All we will do is send junk to the WS
$result = corews_soap('206105', 'stupidcourse', 'stupidid', true);

// footer
echo $OUTPUT->footer();
