<?php // $Id: version.php,v 1.1.2.4 2009/03/06 15:47:04 mudrd8mz Exp $

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of groupselect
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

$module->version  = 2009030500;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2007101509;  // Requires this Moodle version
$module->cron     = 0;           // Period for cron to check this module (secs)

?>
