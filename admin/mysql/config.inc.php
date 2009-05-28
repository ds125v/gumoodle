<?php
/* Servers configuration */
$i = 0;

global $CFG;

$i++;
$cfg['Servers'][$i]['pmadb']         = $CFG->dbname;
$cfg['Servers'][$i]['bookmarktable'] = $CFG->prefix.'pma_bookmark';
$cfg['Servers'][$i]['relation']      = $CFG->prefix.'pma_relation';
$cfg['Servers'][$i]['table_info']    = $CFG->prefix.'pma_table_info';
$cfg['Servers'][$i]['table_coords']  = $CFG->prefix.'pma_table_coords';
$cfg['Servers'][$i]['pdf_pages']     = $CFG->prefix.'pma_pdf_pages';
$cfg['Servers'][$i]['column_info']   = $CFG->prefix.'pma_column_info';
$cfg['Servers'][$i]['history']       = $CFG->prefix.'pma_history';
$cfg['Servers'][$i]['designer_coords'] = $CFG->prefix.'pma_designer_coords';

$cfg['Servers'][$i]['host']          = $CFG->dbhost;
$cfg['Servers'][$i]['extension']     = $CFG->dbtype;
$cfg['Servers'][$i]['connect_type']  = 'tcp';
$cfg['Servers'][$i]['compress']      = false;
$cfg['Servers'][$i]['controluser']   = $CFG->dbuser;
$cfg['Servers'][$i]['controlpass']   = $CFG->dbpass;
$cfg['Servers'][$i]['auth_type']     = 'config';
$cfg['Servers'][$i]['user']          = $CFG->dbuser;
$cfg['Servers'][$i]['password']      = $CFG->dbpass;
$cfg['Servers'][$i]['only_db']       = $CFG->dbname;
/* End of servers configuration */

$cfg['AllowAnywhereRecoding'] = false;
$cfg['DefaultCharset'] = $CFG->defaultcharset;
$cfg['RecodingEngine'] = 'auto';
$cfg['IconvExtraParams'] = '//TRANSLIT';

$cfg['UploadDir']             = "$CFG->dataroot/mysql";         // Directory for uploaded files that can be executed by
$cfg['SaveDir']               = "$CFG->dataroot/mysql";         // Directory where phpMyAdmin can save exported data on
$cfg['docSQLDir']             = "$CFG->dataroot/mysql";         // Directory for docSQL imports, phpMyAdmin can import
$cfg['TempDir']               = "$CFG->dataroot/mysql";         // Directory where phpMyAdmin can save temporary files.

?>
