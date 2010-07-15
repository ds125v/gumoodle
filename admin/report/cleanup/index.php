<?php

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

// get paramters
$age = optional_param( 'age',90,PARAM_INT );
$action = optional_param( 'action','',PARAM_ALPHA );

// Start the page.
admin_externalpage_setup('reportcleanup');
admin_externalpage_print_header();
print_heading(get_string('heading', 'report_cleanup'));

// check for delete
if ($action=='delete' and confirm_sesskey()) {
    
    // print confirm form
    print_box_start();
    echo "<div><center>".get_string('confirm','report_cleanup',display_size($USER->cleanup_totalfilesize))."</center></div>";
    echo "<form action=\"{$CFG->wwwroot}/admin/report/cleanup/index.php\" method=\"post\">\n";
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"confirmed\" />\n";
    echo "<input type=\"submit\" value=\"".get_string('confirmbutton','report_cleanup')."\" />\n";
    echo "</form>\n";
    print_box_end();
    admin_externalpage_print_footer();
    die;
}

// check for confirmed delete
if ($action=='confirmed' and confirm_sesskey()) {

    // get list of files to delete
    $filelist = $USER->cleanup_filelist;

    // display the list of files as we delete them
    print_box_start();
    echo "<ul>\n";

    // delete the files
    foreach ($filelist as $path) {
        echo "<li>".get_string('deleted','report_cleanup',$path)."</li>\n";
    }

    // close the page
    echo "</ul>\n";
    print_continue("{$CFG->wwwroot}/admin/report/cleanup/index.php");
    print_box_end();
    admin_externalpage_print_footer();
    die;
}

// form to select age
print_box_start();
echo "<form action=\"{$CFG->wwwroot}/admin/report/cleanup/index.php\" method=\"post\">\n";
echo "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey."\" />\n";
echo get_string('ageindays','report_cleanup');
echo "<input type=\"text\" name=\"age\" value=\"$age\" />";
echo "<input type=\"submit\" value=\"".get_string('update')."\" />\n";
echo "</form>\n";
print_box_end();

// get the list of courses
$courses = get_records( 'course' );

// save the list of files
$filelist = array();

// total size of files (in bytes)
$totalfilesize = 0;

// simple table for results
echo "<table class=\"files\" cellpadding=\"2\" style=\"width:100%\">";
echo "<tr><th>".get_string('coursefile','report_cleanup')."</th><th>".get_string('modified','report_cleanup')."</th>";
echo "<th>".get_string('filesize','report_cleanup')."</th></tr>\n";

// iterate through courses to analyse file area
foreach ($courses as $course) {
    $base = "{$CFG->dataroot}/{$course->id}";
    $backup = "$base/backupdata";

    // list the files
    $displayfiles = '';

    // oldest file to show
    $oldest = time() - $age*86400;

    // get files in backup folder
    if (is_dir($backup)) {
        $directory=opendir( $backup );
        while (($file = readdir($directory)) !== false) {
            if ($file == '.' or $file == '..') {
                continue;
            }
            $path = "$backup/$file";
            $modified = filemtime( $path );
            $filesize = filesize( $path );
            $totalfilesize += $filesize;

            // it needs to be older than $age before we care about it
            if ($modified < $oldest) {
                $human_modified = userdate( $modified, get_string('strftimedatetime'));
                $human_size = display_size( $filesize );
                $displayfiles .= "<tr class=\"folder\"><td>$file</td><td>$human_modified</td><td align=\"right\">$human_size</td></tr>";
                $filelist[] = $path;
            }
        }
    }

    // display list of files
    if (!empty($displayfiles)) {
        $url = "{$CFG->wwwroot}/course/view.php?id={$course->id}";
        echo "<tr><td colspan=\"3\"><a href=\"$url\">{$course->fullname}</a></td></tr>";
        echo "$displayfiles\n";
    }
}

// close table
echo "</table>";

// if there was nothing
if (empty($filelist)) {
    echo "<center>".get_string('nofiles','report_cleanup')."</center>\n";
}
else {
    print_box_start();

    // display total file size
    echo "<div><strong>".get_string('totalsize','report_cleanup',display_size($totalfilesize))."</strong></div>\n";

    // display delete button (out of the way)
    echo "<form action=\"{$CFG->wwwroot}/admin/report/cleanup/index.php\" method=\"post\">\n";
    echo "<center>\n";
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"delete\" />";
    echo "<input type=\"submit\" value=\"".get_string('delete','report_cleanup',count($filelist))."\" />\n";
    echo "</center>\n";
    echo "</form>\n";
    print_box_end();

    // save files for later
    $USER->cleanup_filelist = $filelist;
    $USER->cleanup_totalfilesize = $totalfilesize;
}

admin_externalpage_print_footer();

?>
