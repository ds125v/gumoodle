<?php // $Id: emaillog.php,v 1.11 2006/04/05 16:39:18 michaelpenne Exp $
    
    /**
     * emaillog.php - displays a log (or history) of all emails sent by
     *      a specific in a specific course.  Each email log can be viewed
     *      or deleted.
     * For our quickmail version we are not using this file at all. 
     * We only included it here for reference
     * @todo Add a print option?
     * @author Mark Nielsen
     * @version $Id: emaillog.php,v 1.11 2006/04/05 16:39:18 michaelpenne Exp $
     * @package quickmail
     **/    
    
    require_once('../../config.php');
    require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once('block_quickmail.php');
    
    $id = required_param('id', PARAM_INT);    // course id
    $action = optional_param('action', '', PARAM_ALPHA);
    $instanceid = optional_param('instanceid', 0, PARAM_INT);

    $quickmail = new block_quickmail();
    $instance = new stdClass;
    
    require_login();
    
    if (! $course = get_record('course', 'id', $id)) {
        error('Course ID was incorrect');
    }
    
    if ($instanceid) {
        $instance = get_record('block_instance', 'id', $instanceid);
    } else {
        if ($quickmailblock = get_record('block', 'name', 'quickmail')) {
            $instance = get_record('block_instance', 'blockid', $quickmailblock->id, 'pageid', $course->id);
        }
    }

/// This block of code ensures that Quickmail will run 
///     whether it is in the course or not
    if (empty($instance)) {
        if (isGuest()) {
            $haspermission = false;
        } else {
            $haspermission = true;
        }
    } else {
        // create a quickmail block instance
        $quickmail->_load_instance($instance);
        $quickmail->load_defaults();
        
        $haspermission = $quickmail->check_permission();
    }
    
    if (!$haspermission) {
        error('Sorry, you do not have the correct permissions to use Quickmail.');
    }
    
    // log deleting happens here (NOTE: reporting is handled below)
    $dumpresult = false;
    if ($action == 'dump') {
        confirm_sesskey();
        
        // delete a single log or all of them
        if ($emailid = optional_param('emailid', 0, PARAM_INT)) {
            $dumpresult = delete_records('block_quickmail_log', 'id', $emailid);
        } else {
            $dumpresult = delete_records('block_quickmail_log', 'userid', $USER->id);
        }
    }

/// set table columns and headers
    $tablecolumns = array('timesent', 'subject', 'attachment', '');
    $tableheaders = array(get_string('date', 'block_quickmail'), get_string('subject', 'forum'),
                         get_string('attachment', 'block_quickmail'), get_string('action', 'block_quickmail'));

    $table = new flexible_table('bocks-quickmail-emaillog');

/// define table columns, headers, and base url
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($CFG->wwwroot.'/blocks/quickmail/emaillog.php?id='.$course->id.'&amp;instanceid='.$instance->id);

/// table settings
    $table->sortable(true, 'timesent', SORT_DESC);
    $table->collapsible(true);
    $table->initialbars(false);
    $table->pageable(true);

/// column styles (make sure date does not wrap) NOTE: More table styles in styles.php
    $table->column_style('timesent', 'width', '40%');
    $table->column_style('timesent', 'white-space', 'nowrap');

/// set attributes in the table tag
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'emaillog');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('align', 'center');
    $table->set_attribute('width', '80%');

    $table->setup();  
    
/// SQL
    $selectfrom = "SELECT * FROM {$CFG->prefix}block_quickmail_log ";
    $where = "WHERE courseid = $course->id AND userid = $USER->id ";

    if($table->get_sql_where()) {
        $where .= 'AND '.$table->get_sql_where();
    }

    $sort = ' ORDER BY '. $table->get_sql_sort();
           
/// set page size
    $total = count_records('block_quickmail_log', 'courseid', $course->id, 'userid', $USER->id);
    $table->pagesize(10, $total);

    if($table->get_page_start() !== '' && $table->get_page_size() !== '') {
        $limit = ' '.sql_paging_limit($table->get_page_start(), $table->get_page_size());
    } else {
        $limit = '';
    }

    if ($pastemails = get_records_sql($selectfrom.$where.$sort.$limit)) {
        
        foreach ($pastemails as $pastemail) {
            $table->add_data( array(userdate($pastemail->timesent),
                                   $pastemail->subject,
                                   $pastemail->attachment,
                                   "<a href=\"email.php?id=$course->id&amp;instanceid=$instance->id&amp;emailid=$pastemail->id&amp;action=view\">".
                                   "<img src=\"$CFG->pixpath/i/search.gif\" height=\"14\" width=\"14\" alt=\"".get_string('view').'" /></a> '.
                                   "<a href=\"emaillog.php?id=$course->id&amp;instanceid=$instance->id&amp;sesskey=$USER->sesskey&amp;action=dump&amp;emailid=$pastemail->id\">".
                                   "<img src=\"$CFG->pixpath/t/delete.gif\" height=\"11\" width=\"11\" alt=\"".get_string('delete').'" /></a>'
                                   )
                             );
        }
    }
    
/// Start printing everyting
    $stremailhistory = get_string('emailhistory', 'block_quickmail');
    $strquickmail    = get_string('blockname', 'block_quickmail');

    print_header($course->fullname.': '.$stremailhistory, $course->fullname, "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".
                 "$course->shortname</a> -> <a href=\"email.php?id=$course->id&amp;instanceid=$instance->id\">$strquickmail</a> -> $stremailhistory",
                 '', '', true);

    print_heading($stremailhistory);
    
/// delete reporting happens here
    if ($action == 'dump') {
        if ($dumpresult) {
            notify(get_string('deletesuccess', 'block_quickmail'));
        } else {
            notify(get_string('deletefail', 'block_quickmail'));
        }
    }
    
/// print table
    echo '<div id="tablecontainer">';
    $table->print_html();
    echo '</div>';
    
/// links to compose new email or to delete the history log
    echo '<p align="center">'.
         "<a href=\"email.php?id=$course->id&amp;instanceid=$instance->id\">".get_string("composenew", "block_quickmail").'</a> ';
    if (isset($pastemails) and !empty($pastemails)) {
        echo "| <a href=\"emaillog.php?id=$course->id&amp;instanceid=$instance->id&amp;sesskey=".$USER->sesskey."&amp;action=dump\">".get_string('clearhistory', 'block_quickmail').'</a>';
    }
    echo '</p>';

    print_footer();              
?>