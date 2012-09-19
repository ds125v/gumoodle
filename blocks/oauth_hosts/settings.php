<?php

defined('MOODLE_INTERNAL') || die;

$mode = optional_param('mode', 'menu', PARAM_TEXT);

require_once('configtextarea.php');


if ($ADMIN->fulltree) {
    $settings->add(new oauth_setting_configtextarea('block_oauth_hosts_allhosts', "", get_string('block_oauth_allhosts', 'block_oauth_hosts'), "", PARAM_RAW, 100, 11));
}


