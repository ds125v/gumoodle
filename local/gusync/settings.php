<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs condition or error on login page
    $settings = new admin_settingpage(
            'local_gusync', get_string('pluginname', 'local_gusync'));
    $ADMIN->add('localplugins', $settings);

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

}
