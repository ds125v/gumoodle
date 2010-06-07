<?php
/**
 * @package   turnitintool
 * @copyright 2010 nLearning Ltd
 */

require_once($CFG->dirroot.'/mod/turnitintool/lib.php');
require_once($CFG->dirroot.'/mod/turnitintool/version.php');

global $RESOURCE_WINDOW_OPTIONS;

$toplinks = '<div><a href="'.$CFG->wwwroot.'/mod/turnitintool/extras.php">'.get_string("connecttest", "turnitintool").'</a> | <a href="'.$CFG->wwwroot.'/mod/turnitintool/extras.php?do=viewreport">'.get_string("showusage", "turnitintool").'</a> | <a href="'.$CFG->wwwroot.'/mod/turnitintool/extras.php?do=savereport">'.get_string("saveusage", "turnitintool").'</a> | <a href="'.$CFG->wwwroot.'/mod/turnitintool/extras.php?do=unlinkusers">'.get_string("unlinkusers", "turnitintool").'</a> - ('.get_string('moduleversion','turnitintool').': '.$module->version.')</div>';

$settings->add(new admin_setting_heading('turnitin_header', '', $toplinks));

$settings->add(new admin_setting_configtext('turnitin_account_id', get_string("turnitinaccountid", "turnitintool"),
                   get_string("turnitinaccountid_desc", "turnitintool"),''));
                   
$settings->add(new admin_setting_configtext('turnitin_secretkey', get_string("turnitinsecretkey", "turnitintool"),
                   get_string("turnitinsecretkey_desc", "turnitintool"),''));
                   
$settings->add(new admin_setting_configtext('turnitin_apiurl', get_string("turnitinapiurl", "turnitintool"),
                   get_string("turnitinapiurl_desc", "turnitintool"),''));
                   
$options = array(0 => get_string('no', 'turnitintool'),
                    1 => get_string('yes', 'turnitintool'),
                 );
$settings->add(new admin_setting_configselect('turnitin_usegrademark', get_string('turnitinusegrademark', 'turnitintool'),
                   get_string('turnitinusegrademark_desc', 'turnitintool'), 0, $options));
                   
$settings->add(new admin_setting_configselect('turnitin_useanon', get_string('turnitinuseanon', 'turnitintool'),
                   get_string('turnitinuseanon_desc', 'turnitintool'), 0, $options));
                   
if (!isset($CFG->turnitin_agreement)) {
    $CFG->turnitin_agreement=get_string('turnitintoolagreement_default','turnitintool');
}

$settings->add(new admin_setting_configtextarea('turnitin_agreement', get_string('turnitintoolagreement', 'turnitintool'),
                   get_string('turnitintoolagreement_desc', 'turnitintool'), ''));
                   
$settings->add(new admin_setting_configselect('turnitin_studentemail', get_string('turnitinstudentemail', 'turnitintool'),
                   get_string('turnitinstudentemail_desc', 'turnitintool'), 1, $options));

$settings->add(new admin_setting_configselect('turnitin_enablediagnostic', get_string('turnitindiagnostic', 'turnitintool'),
                   get_string('turnitindiagnostic_desc', 'turnitintool'), 0, $options));
                   
/* ?> */