<?php

$settings->add(new admin_setting_configtext('block_wiforms_email', get_string('email', 'block_wiforms'),
                   get_string('configemail', 'block_wiforms'), '', PARAM_CLEAN));

$settings->add(new admin_setting_configtext('block_wiforms_subject', get_string('subject', 'block_wiforms'),
                   get_string('configsubject', 'block_wiforms'), get_string('defaultsubject','block_wiforms'), PARAM_CLEAN));
