<?php  //$Id: upgrade.php,v 1.1.6.5 2008/11/14 17:07:08 agrabs Exp $

// This file keeps track of upgrades to 
// the feedback module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_feedback_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2007012310) {

        //create a new table feedback_completedtmp and the field-definition
        $table = new XMLDBTable('feedback_completedtmp');

        $field = new XMLDBField('id');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, true, null, null, null, null);
        $table->addField($field);
        
        $field = new XMLDBField('feedback');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '0', null);
        $table->addField($field);
        
        $field = new XMLDBField('userid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '0', null);
        $table->addField($field);

        $field = new XMLDBField('guestid');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, false, null, null, '', null);
        $table->addField($field);

        $field = new XMLDBField('timemodified');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '0', null);
        $table->addField($field);
        
        $key = new XMLDBKey('PRIMARY');
        $key->setAttributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($key);
        
        $key = new XMLDBKey('feedback');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('feedback'), 'feedback', 'id');
        $table->addKey($key);

        $result = $result && create_table($table);
        ////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////
        //create a new table feedback_valuetmp and the field-definition
        $table = new XMLDBTable('feedback_valuetmp');

        $field = new XMLDBField('id');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, true, null, null, null, null);
        $table->addField($field);
        
        $field = new XMLDBField('course_id');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '0', null);
        $table->addField($field);
        
        $field = new XMLDBField('item');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '0', null);
        $table->addField($field);
        
        $field = new XMLDBField('completed');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '0', null);
        $table->addField($field);
        
        $field = new XMLDBField('tmp_completed');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '0', null);
        $table->addField($field);

        $field = new XMLDBField('value');
        $field->setAttributes(XMLDB_TYPE_TEXT, null, null, null, false, null, null, '', null);
        $table->addField($field);
        
        $key = new XMLDBKey('PRIMARY');
        $key->setAttributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($key);
        
        $key = new XMLDBKey('feedback');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('item'), 'feedback_item', 'id');
        $table->addKey($key);

        $result = $result && create_table($table);
        ////////////////////////////////////////////////////////////
    }

    if ($result && $oldversion < 2007050504) {

        /// Define field random_response to be added to feedback_completed
        $table = new XMLDBTable('feedback_completed');
        $field = new XMLDBField('random_response');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '0', null);
        /// Launch add field1
        $result = $result && add_field($table, $field);

        /// Define field anonymous_response to be added to feedback_completed
        $table = new XMLDBTable('feedback_completed');
        $field = new XMLDBField('anonymous_response');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '1', null);
        /// Launch add field2
        $result = $result && add_field($table, $field);

        /// Define field random_response to be added to feedback_completed
        $table = new XMLDBTable('feedback_completedtmp');
        $field = new XMLDBField('random_response');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '0', null);
        /// Launch add field1
        $result = $result && add_field($table, $field);

        /// Define field anonymous_response to be added to feedback_completed
        $table = new XMLDBTable('feedback_completedtmp');
        $field = new XMLDBField('anonymous_response');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, null, null, '1', null);
        /// Launch add field2
        $result = $result && add_field($table, $field);

        ////////////////////////////////////////////////////////////
    }

    if ($result && $oldversion < 2007102600) {
        // public is a reserved word on Oracle

        $table = new XMLDBTable('feedback_template');
        $field = new XMLDBField('ispublic');
        if (!field_exists($table, $field)) {
            $result = $result && table_column('feedback_template', 'public', 'ispublic', 'integer', 1);
        }
    }

    if ($result && $oldversion < 2008042400) { //New version in version.php
        if($all_nonanonymous_feedbacks = get_records('feedback', 'anonymous', 2)) {
            $update_sql = 'UPDATE '.$CFG->prefix.'feedback_completed SET anonymous_response = 2 WHERE feedback = ';
            foreach ($all_nonanonymous_feedbacks as $fb) {
                $result = $result && execute_sql($update_sql.$fb->id);
            }
        }
    }

    if ($result && $oldversion < 2008042401) { //New version in version.php
        if($result) {
            $concat_radio = sql_concat("'r>>>>>'",'presentation');
            $concat_check = sql_concat("'c>>>>>'",'presentation');
            $concat_dropdown = sql_concat("'d>>>>>'",'presentation');
            
            $update_sql1 = "UPDATE ".$CFG->prefix."feedback_item SET presentation = ".$concat_radio." WHERE typ IN('radio','radiorated')";
            $update_sql2 = "UPDATE ".$CFG->prefix."feedback_item SET presentation = ".$concat_dropdown." WHERE typ IN('dropdown','dropdownrated')";
            $update_sql3 = "UPDATE ".$CFG->prefix."feedback_item SET presentation = ".$concat_check." WHERE typ = 'check'";
            
            $result = $result && execute_sql($update_sql1);
            $result = $result && execute_sql($update_sql2);
            $result = $result && execute_sql($update_sql3);
        }
        if($result) {
            $update_sql1 = "UPDATE ".$CFG->prefix."feedback_item SET typ = 'multichoice' WHERE typ IN('radio','check','dropdown')";
            $update_sql2 = "UPDATE ".$CFG->prefix."feedback_item SET typ = 'multichoicerated' WHERE typ IN('radiorated','dropdownrated')";
            $result = $result && execute_sql($update_sql1);            
            $result = $result && execute_sql($update_sql2);            
        }
    }

    if ($result && $oldversion < 2008042801) {
        $new_log_display = new object();
        $new_log_display->module = 'feedback';
        $new_log_display->action = 'startcomplete';
        $new_log_display->mtable = 'feedback';
        $new_log_display->field = 'name';
        $result = $result && insert_record('log_display', $new_log_display);
        
        $new_log_display = clone($new_log_display);
        $new_log_display->action = 'submit';
        $result = $result && insert_record('log_display', $new_log_display);
        
        $new_log_display = clone($new_log_display);
        $new_log_display->action = 'delete';
        $result = $result && insert_record('log_display', $new_log_display);
        
        $new_log_display = clone($new_log_display);
        $new_log_display->action = 'view';
        $result = $result && insert_record('log_display', $new_log_display);
        
        $new_log_display = clone($new_log_display);
        $new_log_display->action = 'view all';
        $new_log_display->mtable = 'course';
        $new_log_display->field = 'shortname';
        $result = $result && insert_record('log_display', $new_log_display);
    }

    if ($result && $oldversion < 2008042900) {
        /// Define field autonumbering to be added to feedback
        $table = new XMLDBTable('feedback');
        $field = new XMLDBField('autonumbering');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'multiple_submit');
        /// Launch add field2
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008050104) {
        /// Define field site_after_submit to be added to feedback
        $table = new XMLDBTable('feedback');
        $field = new XMLDBField('site_after_submit');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, false, null, null, '', 'autonumbering');
        /// Launch add field2
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008050112) {
        $update_sql = "UPDATE ".$CFG->prefix."feedback_item SET presentation = '-|-' WHERE presentation = '0|0' AND typ = 'numeric'";
        $result = $result && execute_sql($update_sql);            
    }
    return $result;
}

?>
