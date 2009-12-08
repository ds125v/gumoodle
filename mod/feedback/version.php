<?php // $Id: version.php,v 1.18.2.27 2009/06/13 13:07:15 agrabs Exp $
/**
* Code fragment to define the version of feedback
* This fragment is called by moodle_needs_upgrading() and /admin/index.php
*
* @version $Id: version.php,v 1.18.2.27 2009/06/13 13:07:15 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

   
    $module->version = 2008050120; // The current module version (Date: YYYYMMDDXX)
    $module->requires = 2007101503;  // Requires this Moodle version
    $feedback_version_intern = 1; //this version is used for restore older backups
    $module->cron = 0; // Period for cron to check this module (secs)

?>
