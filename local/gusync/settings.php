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
 * GUID Enrolment sync
 *
 * @package    local_gusync
 * @copyright  2012 Howard miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage(
            'local_gusync', get_string('pluginname', 'local_gusync'));
    $ADMIN->add('localplugins', $settings);

    $average = get_config('local_gusync', 'average');
    $average = empty($average) ? '-' : $average;
    $settings->add(new admin_setting_heading('gusync_avaerage', '',
            get_string('displayaverage', 'local_gusync',
            $average)));

    $settings->add(new admin_setting_configtext(
            'local_gusync/dbhost', get_string('dbhost', 'local_gusync'),
            get_string('configdbhost', 'local_gusync'), '', PARAM_RAW));

    $settings->add(new admin_setting_configtext(
            'local_gusync/dbuser', get_string('dbuser', 'local_gusync'),
            get_string('configdbuser', 'local_gusync'), '', PARAM_RAW));

    $settings->add(new admin_setting_configpasswordunmask(
            'local_gusync/dbpass', get_string('dbpass', 'local_gusync'),
            get_string('configdbpass', 'local_gusync'), '', PARAM_RAW));

    $settings->add(new admin_setting_configtext(
            'local_gusync/dbname', get_string('dbname', 'local_gusync'),
            get_string('configdbname', 'local_gusync'), '', PARAM_RAW));

    $settings->add(new admin_setting_configtext(
            'local_gusync/timelimit', get_string('timelimit', 'local_gusync'),
            get_string('configtimelimit', 'local_gusync'), 30, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox(
            'local_gusync/testing', get_string('testing', 'local_gusync'),
            get_string('configtesting', 'local_gusync'), 1));

}
