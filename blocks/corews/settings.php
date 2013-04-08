<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_corews_wsdl', get_string('wsdl', 'block_corews'),
                   get_string('configwsdl', 'block_corews'), '', PARAM_RAW));
    $settings->add(new admin_setting_configtext('block_corews_username', get_string('username', 'block_corews'),
                   get_string('configusername', 'block_corews'), '', PARAM_ALPHA));
    $settings->add(new admin_setting_configpasswordunmask('block_corews_password', get_string('password', 'block_corews'),
                   get_string('configpassword', 'block_corews'), '', PARAM_RAW));
}

