<?php
/**
 * A report that displays deleted users and allows them to be undeleted.
 * @author n.barr@admin.gla.ac.uk
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');

define('PAGELENGTH', 20);

// Test permissions.
require_login();
$sitecontext = get_context_instance(CONTEXT_SYSTEM);

require_capability('moodle/site:viewreports',$sitecontext);
if (!has_capability('moodle/user:update', $sitecontext) || !has_capability('moodle/user:delete', $sitecontext)) {
        error('You do not have the required permission to restore users (delete permission required).');
}

// Set up the admin page stuff.
admin_externalpage_setup('reportdeletedusers');
admin_externalpage_print_header();

$sortorder = optional_param('sort', 'lastname', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', '0', PARAM_INT);
$restoreid = optional_param('restore', '0', PARAM_INT);

if($restoreid == 0) {
	displayDeletedUsers($page, $sortorder, $dir);
} else {
	doRestore($restoreid);
}

//Finish the page
admin_externalpage_print_footer();

function doRestore($userid)
{
	global $sitecontext;
	if (!has_capability('moodle/user:delete', $sitecontext)) {
        error('You do not have the required permission to restore users (delete permission required).');
    }
    $user = get_record('user','id',$userid);
    $user->deleted = 0;
    if(update_record('user', $user)) {
    	echo '<p>'.get_string('user').' <a href="../../../user/view.php?id='.$userid.'">'
        			.fullname($user, true).'</a> '.get_string('hasbeenrestored','report_deletedusers').'</p>';
    	echo '<a href="index.php">'.get_string('continue').'</a>';
    } else {
		echo 'Restore failed <a href="index.php">Continue.</a>';
    }
}

function displayDeletedUsers($page, $sortorder, $dir) {
	global $CFG, $sitecontext;

	$count = count_records('user','deleted',1);
	echo '<p>'.$count. ' '.get_string('deletedusers', 'report_deletedusers').'</p>';
	$startfrom = $page*PAGELENGTH;
	$delusers = get_records('user','deleted',1, $sortorder.' ASC','*',$startfrom , PAGELENGTH);

 	$firstname = 'firstname';
	$lastname = 'lastname';
	$override = new object();
	$override->firstname = 'firstname';
	$override->lastname = 'lastname';
	$fullnamelanguage = get_string('fullnamedisplay', '', $override);

    $columns = array("firstname", "lastname", "username", "email", "city", "country", "lastaccess");

    foreach ($columns as $column) {
        $string[$column] = get_string("$column");
        if ($sortorder != $column) {
            $columnicon = "";
            if ($column == "lastaccess") {
                $columndir = "DESC";
            } else {
                $columndir = "ASC";
            }
        } else {
            $columndir = $dir == "ASC" ? "DESC":"ASC";
            if ($column == "lastaccess") {
                $columnicon = $dir == "ASC" ? "up":"down";
            } else {
                $columnicon = $dir == "ASC" ? "down":"up";
            }
            $columnicon = " <img src=\"$CFG->pixpath/t/$columnicon.gif\" alt=\"\" />";

        }
        $$column = "<a href=\"index.php?sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
    }



	if (($CFG->fullnamedisplay == 'firstname lastname') or ($CFG->fullnamedisplay == 'firstname') or ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
	    $fullnamedisplay = "$firstname/$lastname";
	} else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
	    $fullnamedisplay = "$lastname/$firstname";
	}
	$table->head = array ($fullnamedisplay, $username, $email, $city, $country, $lastaccess, "");
	$table->align = array ("left", "left", "left", "left", "left", "left", "center");
	$table->width = "95%";
	foreach ($delusers as $user) {
	    if(strpos($user->username,$user->timemodified)===false) {
	    	$restorename = $user->username;
	    } else {
	    	$restorename = '';
	    }
	    if ((has_capability('moodle/user:delete', $sitecontext))&&(strlen($restorename)>0)) {
	        $restorebutton = "<a href=\"index.php?restore=$user->id&amp;\">Restore</a>";
	    } else {
	        $restorebutton ="";
	    }
	    if ($user->lastaccess) {
	        $strlastaccess = format_time(time() - $user->lastaccess);
	    } else {
	        $strlastaccess = get_string('never');
	    }
	    $fullname = fullname($user, true);
	    $table->data[] = array ("$fullname", $restorename, "$user->email", "$user->city", "$user->country", $strlastaccess, $restorebutton);
	}
	if (!empty($table)) {
	    print_paging_bar($count, $page, PAGELENGTH, "index.php?sort=$sortorder&amp;");
	    print_table($table);
	    print_paging_bar($count, $page, PAGELENGTH, "index.php?sort=$sortorder&amp;");
	}
}
?>



