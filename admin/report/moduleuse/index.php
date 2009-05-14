<?php
/**
 * A report that displays which courses use each module on a site, and how
 * many instances of the module exist. Also allows navigation to the instances.
 * @author n.barr@admin.gla.ac.uk
 *
 * Derived from admin/report/moduleinstances
 * @copyright &copy; 2006-2007 The Open University
 * @author T.J.Hunt@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');
define('MODULE_TABLE','module_administration_table'); //required for MODULE_TABLE

// Test permissions.
require_login();
require_capability('moodle/site:viewreports', get_context_instance(CONTEXT_SYSTEM, SITEID));

// Set up the admin page stuff.
//#remove? $adminroot = admin_get_root();
admin_externalpage_setup('reportmoduleuse');
admin_externalpage_print_header();

moduleuse_display_report();

//Finish the page
admin_externalpage_print_footer();

/**
 * Main functionality of the moduleuse report.
 */
function moduleuse_display_report() {
	global $CFG, $USER;

    // initialize variables
    $stractivities = get_string('activities');
    $stractivitymodule = get_string('activitymodule');
    $strshowinstances = get_string('showinstances', 'report_moduleuse');

	$moduletype = optional_param('module', '', PARAM_SAFEDIR);
	if($moduletype == '') {
	    /// Get and sort the existing modules

	    if (!$modules = get_records('modules')) {
	        error("No modules found!!");        // Should never happen
	    }

	    foreach ($modules as $module) {
	        $strmodulename = get_string('modulename', "$module->name");
	        // Deal with modules which are lacking the language string
	        if ($strmodulename == '[[modulename]]') {
	            $strmodulename = $module->name;
	        }
	        $modulebyname[$strmodulename] = $module;
	    }
	    ksort($modulebyname, SORT_LOCALE_STRING);

	    /// Print the table of all modules
	    // construct the flexible table ready to display
	    $table = new flexible_table(MODULE_TABLE);
	    $table->define_columns(array('name', 'instances'));
	    $table->define_headers(array($stractivitymodule, $stractivities));
	    $table->define_baseurl($CFG->wwwroot.'/'.$CFG->admin.'report/moduleuse/index.php');
	    $table->set_attribute('id', 'modules');
	    $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
	    $table->setup();

	    foreach ($modulebyname as $modulename => $module) {
	        $icon = "<img src=\"$CFG->modpixpath/$module->name/icon.gif\" class=\"icon\" alt=\"\" />";

	        $count = count_records_select("$module->name",'course<>0');
	        $coursecount = count_records_sql("SELECT COUNT(DISTINCT(course)) FROM {$CFG->prefix}$module->name WHERE course<>0");
	        if($count == 1) {
	            $strinstances = $count . ' ' . get_string('instance', 'report_moduleuse') . ' ';
	        }
	        else {
	            $strinstances = $count . ' ' . get_string('instances', 'report_moduleuse') . ' ';
	        }
	        if($coursecount == 1) {
	            $strinstances .= get_string('incourse', 'report_moduleuse', $coursecount);
	        }
	        else {
	            $strinstances .= get_string('incourses', 'report_moduleuse', $coursecount);
	        }

	        if ($count>0) {
	            $countlink = "<a href=\"index.php?module=$module->name" .
	                "&amp;sesskey={$USER->sesskey}\" title=\"$strshowinstances\">$strinstances</a>";
	        }
	        else {
	            $countlink = "$count";
	        }

	        $extra = '';
	        if (!file_exists("$CFG->dirroot/mod/$module->name/lib.php")) {
	            $extra = ' <span class="notifyproblem">('.get_string('missingfromdisk').')</span>';
	        }

	        $table->add_data(array(
	            '<span>'.$icon.' '.$modulename.$extra.'</span>',
	            $countlink
	        ));
	    }

	    $table->print_html();
	}
	else {
	    // Get a list of all the modules.
	    $modules = get_records('modules');
	    $allmodules = array();
	    foreach ($modules as $module) {
	        $allmodules[$module->name] = get_string('modulename', $module->name);
	    }
	    asort($allmodules);

	    // If a module is specified, generate the report.
	    if ($moduletype) {
	        if (empty($allmodules[$moduletype])) {
	            error(get_string('unknownmoduletype', 'report_moduleinstances'));
	        }
	        $modulename = $allmodules[$moduletype];

	        $sql = "
	    SELECT
	        c.id AS courseid,
	        c.shortname,
	        c.fullname,
	        COUNT(cm.id) AS count
	    FROM
	        {$CFG->prefix}course c,
	        {$CFG->prefix}course_modules cm,
	        {$CFG->prefix}modules modu,
	        {$CFG->prefix}$moduletype m
	    WHERE
	        c.id = cm.course AND
	        cm.instance = m.id AND
	        cm.module = modu.id AND
	        modu.name = '$moduletype'
	    GROUP BY c.id
	    ORDER BY
	        c.shortname ASC";

	        $instances = get_records_sql($sql);

	        if ($instances) {
	            // Set up the table.
	            $table = new flexible_table('moduleinstances');
	            $tablecolumns = array(
	                get_string('shortname') => 'left',
	                get_string('fullname') => 'left',
	                get_string('instancecount', 'report_moduleuse') => 'left',
	            );
	            $headers = array();
	            foreach ($tablecolumns as $name => $notused) {
	                $headers[$name] = get_string($name, 'report_moduleinstances', $modulename);
	            }
	            $table->define_columns(array_keys($tablecolumns));
	            $table->define_headers(array_keys($tablecolumns));
	            foreach ($tablecolumns as $name => $align) {
	                $table->column_style($name, 'text-align', $align);
	            }
	            $table->set_attribute('id', 'types');
	            $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
	            $table->setup();

	            // Add the data to the table.
	            foreach ($instances as $instance) {
	                $data = array();
	                $data[] = "<a href=\"{$CFG->wwwroot}/course/view.php?id={$instance->courseid}\">$instance->shortname</a>";
	                $data[] = $instance->fullname;
	                if($instance->count==1) {
	                    $strinstance = get_string('instance', 'report_moduleuse');
	                }
	                else {
	                    $strinstance = get_string('instances', 'report_moduleuse');
	                }
	                $data[] = "<a href=\"{$CFG->wwwroot}/mod/$moduletype/index.php?id={$instance->courseid}\">{$instance->count} $strinstance</a>";
	                $table->add_data($data);
	            }

	            // Print the table.
	            print_heading(get_string('instancesofmodule', 'report_moduleuse', $modulename));
	            $table->print_html();

	        } else {
	            echo '<p>', get_string('noinstances', 'report_moduleinstances', $modulename), '</p>';
	        }
	    }
	}
}

?>