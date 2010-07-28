<?php  //$Id$

$settings->add(new admin_setting_configtext('block_guenrol_secondsbetweencron', get_string('secondsbetweencron', 'block_guenrol'),
                   get_string('configsecondsbetweencron', 'block_guenrol'), 84000, PARAM_INT));
$settings->add(new admin_setting_configtext('block_guenrol_maxcronseconds', get_string('maxcronseconds', 'block_guenrol'),
                   get_string('configmaxcronseconds', 'block_guenrol'), 300, PARAM_INT));

?>
