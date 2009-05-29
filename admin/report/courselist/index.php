<?php
/**
 * A report that provides a list of courses on the site, along with some
 * information about each course.
 * @author n.barr@admin.gla.ac.uk
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once $CFG->libdir.'/formslib.php';
require_once('courselist_lib.php');

define('CLPAGESIZE', 20);

$makecsv = optional_param('csv', 0, PARAM_INT);

$page = optional_param('page', 1, PARAM_INT);

define('MODULE_TABLE','module_administration_table'); //required for MODULE_TABLE

// Test permissions.
require_login();
require_capability('moodle/site:viewreports', get_context_instance(CONTEXT_SYSTEM, SITEID));


if($makecsv==0) {
	// Set up the admin page stuff.
	admin_externalpage_setup('reportcourselist');
	admin_externalpage_print_header();

	// load some strings
	$strpage = get_string('page');
	$strcourse = get_string('course');
	$strcategory = get_string('category');
	$strof = get_string('of', 'report_courselist');
	$strcoursesfound = get_string('coursesfound', 'report_courselist');
	$strprev = get_string('prev', 'report_courselist');
	$strnext = get_string('next', 'report_courselist');
	$strallcsv = get_string('allcsv', 'report_courselist');
}

$options = displayOptionsForm($makecsv); // if $makecsv==1 don't display form

$courses = get_courses('all', 'c.'.$options['sortby'].' ASC'); //get_records('course', null, '', 'shortname' );

if($makecsv==0) {
	$optionslink = "";
	while(list($k, $v) = each($options)) {
		$optionslink .= $k.'='.$v.'&';
	}
	$first = ($page-1) * CLPAGESIZE;
	$last =  ($page * CLPAGESIZE)-1;
	if($last >= sizeof($courses)){
		$last = sizeof($courses)-1;
	}
	$pagecount = ceil(sizeof($courses) / CLPAGESIZE);

	echo '<p>'.sizeof($courses).' '.$strcoursesfound.'<br/>';
	echo $strpage . ' '.$page.' '.$strof.' '.$pagecount.'.';
	$np = $page+1;
	$pp = $page-1;
	if($page > 1) {
		echo ' <a href="?'.$optionslink.'page='.($page-1).'">'.$strprev.'</a>';
	}
	if($page < $pagecount) {
		echo ' <a href="?'.$optionslink.'page='.($page+1).'">'.$strnext.'</a>';
	}
	echo ' <a href="?'.$optionslink.'csv=1">'.$strallcsv.'</a>';
	echo '</p>';

	for($n=$first; $n<=$last; $n++)
	{
		$c = $courses[$n];
	    echo "<p>$strcourse $c->id <b><a href=\"{$CFG->wwwroot}/course/view.php?id={$c->id}\">{$c->shortname}</a></b> {$c->fullname}<br/> ";
	    if($options['cat']==1) {
		    echo $strcategory .': '. get_category_name($c->category) .'<br/>';
	    }
	    if($options['staff']==1) {
		    $teachers = get_teachers($c->context);
		    if($teachers) {
				foreach($teachers as $t) {
			    	echo "{$t->firstname} {$t->lastname}<br/>";
	            }
	        }
	    }
		echo "</p>";
	}
	//echo "<pre>"; print_r($courses); echo "</pre>";

	//Finish the page
	admin_externalpage_print_footer();
}
else { // Send a CSV
	header('Content-type: text/plain');
	for($n=0; $n<sizeof($courses); $n++)
	{
		$c = $courses[$n];
	    echo "$c->id, $c->shortname, $c->fullname, ";
	    echo get_category_name($c->category). ', ';
	    if($options['staff']==1) {
		    $teachers = get_teachers($c->context);
		    if($teachers) {
				foreach($teachers as $t) {
			    	echo "{$t->firstname} {$t->lastname}, ";
	            }
	        }
	    }
		echo "\n";
	}
}

?>
