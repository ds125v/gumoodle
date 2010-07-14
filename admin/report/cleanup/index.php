<?php

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

/*
// Register our custom form control
MoodleQuickForm::registerElementType('username', "$CFG->dirroot/admin/report/userroles/username.php",
        'MoodleQuickForm_username');

// moodleform for controlling the report
class user_roles_report_form extends moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('header', 'reportsettings', get_string('reportsettings', 'report_userroles'));
        $mform->addElement('username', 'username', get_string('username'));
        $mform->addElement('submit', 'submit', get_string('getreport', 'report_userroles'));
    }
}
$mform = new user_roles_report_form();
*/

// Start the page.
admin_externalpage_setup('reportcleanup');
admin_externalpage_print_header();
print_heading(get_string('heading', 'report_cleanup'));

// get the list of courses
$courses = get_records( 'course' );

// iterate through courses to analyse file area
foreach ($courses as $course) {
    $base = "{$CFG->dataroot}/{$course->id}";
    $backup = "$base/backupdata";
    
    echo "<strong>{$course->shortname} $backup</strong><br /><br /> ";

    // get files in backup folder
    if (is_dir($backup)) {
        $directory=opendir( $backup );
        while (($file = readdir($directory)) !== false) {
            if ($file == '.' or $file == '..') {
                continue;
            }
            $modified = filemtime( $backup.'/'.$file );
            echo "filename: $file, modified $modified <br />   ";
        }
    }

    echo "<br />";
}


admin_externalpage_print_footer();

?>
