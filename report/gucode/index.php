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
 * UofG Course Code Report
 *
 * @package    report_gucode
 * @copyright  2013 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__).'/locallib.php');

// Get paramters.
$code = optional_param('code', '', PARAM_TEXT);

// Start the page.
admin_externalpage_setup('reportgucode', '', null, '', array('pagelayout' => 'report'));
$output = $PAGE->get_renderer('report_gucode');
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('heading', 'report_gucode'));

// Form.
$mform = new gucode_form(null, null, 'get');
$mform->display();

if ($mform->is_cancelled()) {
    redirect( "index.php" );
} else if ($data = $mform->get_data()) {
    $code = $data->code;
    if ($code) {
        $sql = 'select * from {enrol_gudatabase_codes} where '.
                $DB->sql_like('code', '?', false);
        $courses = $DB->get_records_sql($sql, array('%'.$code.'%'));
        $output->print_table($courses);
    }
}

echo $OUTPUT->footer();
