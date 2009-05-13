<?php // $Id: mapcourse.php,v 1.4.2.2 2008/05/15 10:33:08 agrabs Exp $
/**
* print the form to map courses for global feedbacks
*
* @version $Id: mapcourse.php,v 1.4.2.2 2008/05/15 10:33:08 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once("lib.php");
    require_once("$CFG->libdir/tablelib.php");
    
    $id = required_param('id', PARAM_INT); // Course Module ID, or
    $searchcourse = optional_param('searchcourse', '', PARAM_ALPHANUM);
    $coursefilter = optional_param('coursefilter', '', PARAM_INT);
    $courseid = optional_param('courseid', false, PARAM_INT);
    
    if(($formdata = data_submitted('nomatch')) AND !confirm_sesskey()) {
        error('no sesskey defined');
    }
    
    // $SESSION->feedback->current_tab = 'mapcourse';
    $current_tab = 'mapcourse';
    
    if ($id) {
        if (! $cm = get_coursemodule_from_id('feedback', $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
        
        if (! $feedback = get_record("feedback", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
    }
    $capabilities = feedback_load_capabilities($cm->id);
    
    require_login($course->id, true, $cm);
    
    if (!$capabilities->mapcourse) {
        error ('access not allowed');
    }
    
    if ($coursefilter) {
        $map->feedbackid = $feedback->id;
        $map->courseid = $coursefilter;
        // insert a map only if it does exists yet
        $sql = "select id, feedbackid from {$CFG->prefix}feedback_sitecourse_map where feedbackid = $map->feedbackid and courseid = $map->courseid";
        if (!get_records_sql($sql) && !insert_record('feedback_sitecourse_map', $map)) {
            error("Database problem, unable to map feedback = $feedback->id to course = $course->id");
        }
    }
    
    /// Print the page header
    // $strfeedbacks = get_string("modulenameplural", "feedback");
    // $strfeedback = get_string("modulename", "feedback");
    // $navigation = '';
    
    // $feedbackindex = '<a href="'.htmlspecialchars('index.php?id='.$course->id).'">'.$strfeedbacks.'</a> ->';
    // if ($course->category) {
        // $navigation = '<a href="'.htmlspecialchars('../../course/view.php?id='.$course->id).'">'.$course->shortname.'</a> ->';
    // }else if ($courseid > 0 AND $courseid != SITEID) {
        // $usercourse = get_record('course', 'id', $courseid);
        // $navigation = '<a href="'.htmlspecialchars('../../course/view.php?id='.$usercourse->id).'">'.$usercourse->shortname.'</a> ->';
        // $feedbackindex = '';
    // }
    
    // print_header($course->shortname.': '.$feedback->name, $course->fullname,
                // $navigation.' '.$feedbackindex.' <a href="'.htmlspecialchars('view.php?id='.$id).'">'.$feedback->name.'</a> -> '.get_string('mapcourses', 'feedback'),
                // '', '', true, update_module_button($cm->id, $course->id, $strfeedback), navmenu($course, $cm));
    /// Print the page header
    $strfeedbacks = get_string("modulenameplural", "feedback");
    $strfeedback  = get_string("modulename", "feedback");
    $buttontext = update_module_button($cm->id, $course->id, $strfeedback);
    
    $navlinks = array();
    $navlinks[] = array('name' => $strfeedbacks, 'link' => "index.php?id=$course->id", 'type' => 'activity');
    $navlinks[] = array('name' => format_string($feedback->name), 'link' => "", 'type' => 'activityinstance');
    
    $navigation = build_navigation($navlinks);
    
    print_header_simple(format_string($feedback->name), "",
                 $navigation, "", "", true, $buttontext, navmenu($course, $cm));

    include('tabs.php');

    // print_simple_box(get_string('mapcourseinfo', 'feedback'), 'center', '80%');
    print_box(get_string('mapcourseinfo', 'feedback'), 'generalbox boxaligncenter boxwidthwide');
    // print_simple_box_start('center', '70%');
    print_box_start('generalbox boxaligncenter boxwidthwide');
    echo '<form method="post">';
    echo '<input type="hidden" name="id" value="'.$id.'" />';
    echo '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'" />';
    
    $sql = "select c.id, c.shortname from {$CFG->prefix}course c
            where
                c.shortname ".sql_ilike()." '%{$searchcourse}%'
            OR c.fullname ".sql_ilike()." '%{$searchcourse}%'";
    
    if (($courses = get_records_sql_menu($sql)) && !empty($searchcourse)) {
        echo ' ' . get_string('courses') . ': ';
        choose_from_menu ($courses, 'coursefilter', $coursefilter, 'choose');
        echo '<input type="submit" value="'.get_string('mapcourse', 'feedback').'"/>';
        helpbutton('mapcourses', '', 'feedback', true, true);
        echo '<input type="button" value="'.get_string('searchagain').'" onclick="document.location=\'mapcourse.php?id='.$id.'\'"/>';
        echo '<input type="hidden" name="searchcourse" value="'.$searchcourse.'"/>';
        echo '<input type="hidden" name="feedbackid" value="'.$feedback->id.'"/>';
        helpbutton('searchcourses', '', 'feedback', true, true);
    } else {
        echo '<input type="text" name="searchcourse" value="'.$searchcourse.'"/> <input type="submit" value="'.get_string('searchcourses').'"/>';
        helpbutton('searchcourses', '', 'feedback', true, true);
    }
    
    echo '</form>';
    
    if($coursemap = feedback_get_courses_from_sitecourse_map($feedback->id)) {
        $table = new flexible_table('coursemaps');
        $table->define_columns( array('course'));
        $table->define_headers( array(get_string('mappedcourses', 'feedback')));
    
        $table->setup();
    
        foreach ($coursemap as $cmap) {
            $table->add_data(array('<a href="'.htmlspecialchars('unmapcourse.php?id='.$id.'&cmapid='.$cmap->id).'"><img src="'.$CFG->pixpath.'/t/delete.gif" alt="Delete" /></a> ('.$cmap->shortname.') '.$cmap->fullname));
        }
    
        $table->print_html();
    } else {
        echo '<h3>'.get_string('mapcoursenone', 'feedback').'</h3>';
    }
    
    
    // print_simple_box_end();
    print_box_end();
    
    print_footer($course);

?>