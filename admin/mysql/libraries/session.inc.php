<?php
/// special Moodle hack compatible with phpmyadmin 2.8.2 only!!
/// $Id: session.inc.php,v 1.11 2008/07/29 09:43:22 skodak Exp $

if (! defined('PHPMYADMIN')) {
    exit;
}

// undo the previous stripslashes magic from common.lib.php
if (get_magic_quotes_gpc()) {
    PMA_arrayWalkRecursive($_GET, 'addslashes', true);
    PMA_arrayWalkRecursive($_POST, 'addslashes', true);
    PMA_arrayWalkRecursive($_COOKIE, 'addslashes', true);
    PMA_arrayWalkRecursive($_REQUEST, 'addslashes', true);
}

//store original pma language request that collides with our moodle lang
if (!empty($_GET['lang'])) {
    $originallang = $_GET['lang'];
    unset($_GET['lang']);
    unset($_REQUEST['lang']);
} else {
    $originallang = false;
}

//start moodle session, connect to database, etc.
require(dirname(__FILE__).'/../../../config.php');
if ($CFG->debug > E_NOTICE) {
    $CFG->debug = E_NOTICE;
    error_reporting($CFG->debug);
}
if (function_exists('require_capability')) {
    require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));
} else if (!isadmin()) {
    error("This page is only accessible to the admin user");
}
if ($CFG->dbtype <> "mysql" and $CFG->dbtype <> "mysqli") {
    error("This section only works if you are using MySQL as your database");
}

// Make directory for uploads
make_upload_directory('mysql');

if (function_exists('current_charset')) {
    $CFG->defaultcharset = current_charset();
} else {
    //older Moodle versions
    $CFG->defaultcharset = get_string('thischarset');
}

// initialize security token with our sesskey if needed
if (!isset($_SESSION[' PMA_token '])) {
    $_SESSION[' PMA_token '] = sesskey();
}

// try to initialize defaut language if needed
if (($originallang === false) and empty($_COOKIE['pma_lang']) and (strtolower($CFG->defaultcharset) == 'utf-8')) {
    $originallang = substr(current_language(), 0, 2).'-utf-8'; // should work for most locales
}

if ($CFG->version < 2007101600) { // < 2.0
    //add special pma tables if possible
    $serverinfo = $db->ServerInfo();
    if ($serverinfo['version'] > '4.1.2') {
        if ($tables = $db->Metatables()) {
            $db->debug = true;
            if (!in_array($CFG->prefix.'pma_bookmark', $tables)) { 
                print_heading('Creating extra tables for fancy phpMyAdmin features');
                modify_database("$CFG->dirroot/$CFG->admin/mysql/scripts/create_tables_moodle.sql");
                print_continue("$CFG->wwwroot/$CFG->admin/mysql/index.php");
                exit;
            }
            if (!in_array($CFG->prefix.'pma_designer_coords', $tables)) { 
                print_heading('Creating extra tables for fancy phpMyAdmin features');
                modify_database("$CFG->dirroot/$CFG->admin/mysql/scripts/create_tables_moodle_designer.sql");
                print_continue("$CFG->wwwroot/$CFG->admin/mysql/index.php");
                exit;
            }
            $db->debug = false;
        }
    }
    //disconnect from the db - pma will reconnect anyway
    $db->Disconnect();
    unset($serverinfo);
    unset($tables);
    unset($db);

} else {
    //TODO: add phpmyadmin table definitions, we can not use old files
    //disconnect from the db - pma will reconnect anyway
    $DB->dispose();
    unset($DB);
}

//put pma language back
if ($originallang !== false) {
    $_GET['lang'] = $originallang;
    $_REQUEST['lang'] = $originallang;
    $GLOBALS['lang'] = $originallang;
}
unset($originallang);

if ($CFG->version < 2007101600) { // < 2.0
    // undo the slashes added by Moodle - pma does not use magic quotes
    PMA_arrayWalkRecursive($_GET, 'stripslashes', true);
    PMA_arrayWalkRecursive($_POST, 'stripslashes', true);
    PMA_arrayWalkRecursive($_COOKIE, 'stripslashes', true);
    PMA_arrayWalkRecursive($_REQUEST, 'stripslashes', true);
}


//no session regeneration in our case
function PMA_secureSession() {
    return;
}

?>
