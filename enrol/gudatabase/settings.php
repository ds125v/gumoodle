<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Database enrolment plugin settings and presets.
 *
 * @package    enrol
 * @subpackage database
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_gudatabase_settings', '', get_string('pluginname_desc', 'enrol_database')));

    $settings->add(new admin_setting_heading('enrol_gudatabase_exdbheader', get_string('settingsheaderdb', 'enrol_database'), ''));

    $options = array('', "access","ado_access", "ado", "ado_mssql", "borland_ibase", "csv", "db2", "fbsql", "firebird", "ibase", "informix72", "informix", "mssql", "mssql_n", "mssqlnative", "mysql", "mysqli", "mysqlt", "oci805", "oci8", "oci8po", "odbc", "odbc_mssql", "odbc_oracle", "oracle", "postgres64", "postgres7", "postgres", "proxy", "sqlanywhere", "sybase", "vfp");
    $options = array_combine($options, $options);
    $settings->add(new admin_setting_configselect('enrol_gudatabase/dbtype', get_string('dbtype', 'enrol_database'), get_string('dbtype_desc', 'enrol_database'), '', $options));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/dbhost', get_string('dbhost', 'enrol_database'), get_string('dbhost_desc', 'enrol_database'), 'localhost'));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/dbuser', get_string('dbuser', 'enrol_database'), '', ''));

    $settings->add(new admin_setting_configpasswordunmask('enrol_gudatabase/dbpass', get_string('dbpass', 'enrol_database'), '', ''));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/dbname', get_string('dbname', 'enrol_database'), '', ''));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/dbencoding', get_string('dbencoding', 'enrol_database'), '', 'utf-8'));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/dbsetupsql', get_string('dbsetupsql', 'enrol_database'), get_string('dbsetupsql_desc', 'enrol_database'), ''));

    $settings->add(new admin_setting_configcheckbox('enrol_gudatabase/dbsybasequoting', get_string('dbsybasequoting', 'enrol_database'), get_string('dbsybasequoting_desc', 'enrol_database'), 0));

    $settings->add(new admin_setting_configcheckbox('enrol_gudatabase/debugdb', get_string('debugdb', 'enrol_database'), get_string('debugdb_desc', 'enrol_database'), 0));



    $settings->add(new admin_setting_heading('enrol_gudatabase_localheader', get_string('settingsheaderlocal', 'enrol_database'), ''));

    $options = array('id'=>'id', 'idnumber'=>'idnumber', 'shortname'=>'shortname');
    $settings->add(new admin_setting_configselect('enrol_gudatabase/localcoursefield', get_string('localcoursefield', 'enrol_database'), '', 'idnumber', $options));

    $options = array('id'=>'id', 'idnumber'=>'idnumber', 'email'=>'email', 'username'=>'username'); // only local users if username selected, no mnet users!
    $settings->add(new admin_setting_configselect('enrol_gudatabase/localuserfield', get_string('localuserfield', 'enrol_database'), '', 'idnumber', $options));

    $options = array('id'=>'id', 'shortname'=>'shortname', 'fullname'=>'fullname');
    $settings->add(new admin_setting_configselect('enrol_gudatabase/localrolefield', get_string('localrolefield', 'enrol_database'), '', 'shortname', $options));

    $options = array('id'=>'id', 'idnumber'=>'idnumber');
    $settings->add(new admin_setting_configselect('enrol_gudatabase/localcategoryfield', get_string('localcategoryfield', 'enrol_database'), '', 'id', $options));


    $settings->add(new admin_setting_heading('enrol_gudatabase_remoteheader', get_string('settingsheaderremote', 'enrol_database'), ''));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/remoteenroltable', get_string('remoteenroltable', 'enrol_database'), get_string('remoteenroltable_desc', 'enrol_database'), ''));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/remotecoursefield', get_string('remotecoursefield', 'enrol_database'), get_string('remotecoursefield_desc', 'enrol_database'), ''));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/remoteuserfield', get_string('remoteuserfield', 'enrol_database'), get_string('remoteuserfield_desc', 'enrol_database'), ''));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/remoterolefield', get_string('remoterolefield', 'enrol_database'), get_string('remoterolefield_desc', 'enrol_database'), ''));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(get_context_instance(CONTEXT_SYSTEM));
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_gudatabase/defaultrole', get_string('defaultrole', 'enrol_database'), get_string('defaultrole_desc', 'enrol_database'), $student->id, $options));
    }

    $settings->add(new admin_setting_configcheckbox('enrol_gudatabase/ignorehiddencourses', get_string('ignorehiddencourses', 'enrol_database'), get_string('ignorehiddencourses_desc', 'enrol_database'), 0));

    $options = array(ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
                     ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
                     ENROL_EXT_REMOVED_SUSPEND        => get_string('extremovedsuspend', 'enrol'),
                     ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'));
    $settings->add(new admin_setting_configselect('enrol_gudatabase/unenrolaction', get_string('extremovedaction', 'enrol'), get_string('extremovedaction_help', 'enrol'), ENROL_EXT_REMOVED_UNENROL, $options));



    $settings->add(new admin_setting_heading('enrol_gudatabase_newcoursesheader', get_string('settingsheadernewcourses', 'enrol_database'), ''));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/newcoursetable', get_string('newcoursetable', 'enrol_database'), get_string('newcoursetable_desc', 'enrol_database'), ''));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/newcoursefullname', get_string('newcoursefullname', 'enrol_database'), '', 'fullname'));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/newcourseshortname', get_string('newcourseshortname', 'enrol_database'), '', 'shortname'));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/newcourseidnumber', get_string('newcourseidnumber', 'enrol_database'), '', 'idnumber'));

    $settings->add(new admin_setting_configtext('enrol_gudatabase/newcoursecategory', get_string('newcoursecategory', 'enrol_database'), '', ''));

    if (!during_initial_install()) {
        require_once($CFG->dirroot.'/course/lib.php');
        $options = array();
        $parentlist = array();
        make_categories_list($options, $parentlist);
        $settings->add(new admin_setting_configselect('enrol_gudatabase/defaultcategory', get_string('defaultcategory', 'enrol_database'), get_string('defaultcategory_desc', 'enrol_database'), 1, $options));
        unset($parentlist);
    }

    $settings->add(new admin_setting_configtext('enrol_gudatabase/templatecourse', get_string('templatecourse', 'enrol_database'), get_string('templatecourse_desc', 'enrol_database'), ''));
}
