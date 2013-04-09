<?php

////////////////////////////////////////////////////////////////////////////////
//  Code fragment to define the module version etc.
//  This fragment is called by /admin/index.php
////////////////////////////////////////////////////////////////////////////////

defined('MOODLE_INTERNAL') || die();

$module->version  = 2012111400;
$module->requires  = 2011120500;       // Requires this Moodle version
$module->maturity  = MATURITY_STABLE;
$module->release = '1.3.3 (Build: 2012111400, based on mod_choice 2011112900)';

$module->component = 'mod_choicegroup';     // Full name of the plugin (used for diagnostics)
$module->cron     = 0;

