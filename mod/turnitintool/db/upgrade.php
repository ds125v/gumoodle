<?php 
/**
 * @package   turnitintool
 * @copyright 2010 nLearning Ltd
 */

function xmldb_turnitintool_upgrade($oldversion) {

    global $CFG, $THEME, $DB, $OUTPUT;

    $result = true;

	// Do necessary DB upgrades here
	//function add_field($name, $type, $precision=null, $unsigned=null, $notnull=null, $sequence=null, $enum=null, $enumvalues=null, $default=null, $previous=null)

	if ($result && $oldversion < 2009071501) {
		if (is_callable(array($DB,'get_manager'))) {
			$dbman=$DB->get_manager();
			$table = new xmldb_table('turnitintool_submissions');
			$field = new xmldb_field('submission_gmimaged', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'submission_grade');
	
			if (!$dbman->field_exists($table, $field)) {
				$dbman->add_field($table, $field);
			}
		} else {
			$table = new XMLDBTable('turnitintool_submissions');
			$field = new XMLDBField('submission_gmimaged');
			$field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'submission_grade');
			$result = $result && add_field($table, $field);
		}
	}
	
	if ($result && $oldversion < 2009091401) {
		if (is_callable(array($DB,'get_manager'))) {
			$dbman=$DB->get_manager();
			$table = new xmldb_table('turnitintool');
			$field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, '0', 'intro');
	
			if (!$dbman->field_exists($table, $field)) {
				$dbman->add_field($table, $field);
			}
		} else {
			$table = new XMLDBTable('turnitintool');
			$field = new XMLDBField('introformat');
			$field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, '0', 'intro');
			$result = $result && add_field($table, $field);
		}
	}
	
	if ($result && $oldversion < 2009092901) {
		if (is_callable(array($DB,'get_manager'))) {
			$dbman=$DB->get_manager();
			$table1 = new xmldb_table('turnitintool');
			$field1 = new xmldb_field('resubmit', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'defaultdtpost');
			if ($dbman->field_exists($table1, $field1)) {
				$dbman->rename_field($table1, $field1, 'anon');
			}
			
			$table2 = new xmldb_table('turnitintool_submissions');
			$field2 = new xmldb_field('submission_unanon', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, NULL, null, null, null, '0', 'submission_nmlastname');
			$field3 = new xmldb_field('submission_unanonreason', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'submission_unanon');
			$field4 = new xmldb_field('submission_nmuserid', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
	
			if (!$dbman->field_exists($table2, $field2)) {
				$dbman->add_field($table2, $field2);
			}
			if (!$dbman->field_exists($table2, $field3)) {
				$dbman->add_field($table2, $field3);
			}
			$dbman->change_field_type($table2, $field4);
		} else {
			$table1 = new XMLDBTable('turnitintool');
			$field1 = new XMLDBField('resubmit');
			$field1->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'defaultdtpost');
			$result = $result && rename_field($table1, $field1, 'anon');
			
			$table2 = new XMLDBTable('turnitintool_submissions');
			$field2 = new XMLDBField('submission_unanon');
			$field3 = new XMLDBField('submission_unanonreason');
			$field4 = new XMLDBField('submission_nmuserid');
			$field2->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, null, null, '0', 'submission_nmlastname');
			$result = $result && add_field($table2, $field2);
			$field3->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, 'submission_unanon');
			$result = $result && add_field($table2, $field3);
			$field4->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, null);
			$result = $result && change_field_type($table2, $field4);
		}
	}
	
	if ($result && $oldversion < 2009120501) {
		if (is_callable(array($DB,'get_manager'))) {
			$dbman=$DB->get_manager();
			
			// Launch add index userid
			$table = new xmldb_table('turnitintool_submissions');
			$index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
			if (!$dbman->index_exists($table, $index)) {
				$dbman->add_index($table, $index);
			}
			
			// Launch add index turnitintoolid
			$table = new xmldb_table('turnitintool_submissions');
			$index = new xmldb_index('turnitintoolid', XMLDB_INDEX_NOTUNIQUE, array('turnitintoolid'));
			if (!$dbman->index_exists($table, $index)) {
				$dbman->add_index($table, $index);
			}
		} else {
			$table = new XMLDBTable('turnitintool_submissions');
			
			// Launch add index userid
			$index = new XMLDBIndex('userid');
			$index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));
			$result = $result && add_index($table, $index);
			
			// Launch add index turnitintoolid
			$index = new XMLDBIndex('turnitintoolid');
			$index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('turnitintoolid'));
			$result = $result && add_index($table, $index);
		}
	}
	
	if ($result && $oldversion < 2010012201) {
		if (is_callable(array($DB,'get_manager'))) {
			$dbman=$DB->get_manager();
			
			// Fix fields where '' has been used
			$DB->execute("UPDATE ".$CFG->prefix."turnitintool_submissions SET submission_score=NULL WHERE submission_score=''");
			$DB->execute("UPDATE ".$CFG->prefix."turnitintool_submissions SET submission_grade=NULL WHERE submission_grade=''");
			$DB->execute("UPDATE ".$CFG->prefix."turnitintool_submissions SET submission_objectid=NULL WHERE submission_objectid=''");
			
			$table = new xmldb_table('turnitintool_submissions');
			$field1 = new xmldb_field('submission_score', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, NULL, null, null, null, null, 'submission_objectid');
			$field2 = new xmldb_field('submission_grade', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, NULL, null, null, null, null, 'submission_score');
			$field3 = new xmldb_field('submission_objectid', XMLDB_TYPE_INTEGER, '50', XMLDB_UNSIGNED, NULL, null, null, null, null, 'submission_filename');

			$dbman->change_field_type($table, $field1);
			$dbman->change_field_type($table, $field2);
			$dbman->change_field_type($table, $field3);
			
		} else {
			
			$table = new XMLDBTable('turnitintool_submissions');
			$field1 = new XMLDBField('submission_score');
			$field2 = new XMLDBField('submission_grade');
			$field3 = new XMLDBField('submission_objectid');
			
			// Fix fields where '' has been used
			execute_sql("UPDATE ".$CFG->prefix."turnitintool_submissions SET submission_score=NULL WHERE submission_score=''");
			execute_sql("UPDATE ".$CFG->prefix."turnitintool_submissions SET submission_grade=NULL WHERE submission_grade=''");
			execute_sql("UPDATE ".$CFG->prefix."turnitintool_submissions SET submission_objectid=NULL WHERE submission_objectid=''");
			
			$field1->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'submission_objectid');
			$result = $result && change_field_type($table, $field1);
			
			$field2->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'submission_score');
			$result = $result && change_field_type($table, $field2);
			
			$field3->setAttributes(XMLDB_TYPE_INTEGER, '50', XMLDB_UNSIGNED, null, null, null, null, null, 'submission_filename');
			$result = $result && change_field_type($table, $field3);
		}
	}
	
	if ($result && $oldversion < 2010021901) {
		require_once($CFG->dirroot."/mod/turnitintool/lib.php");
		$loaderbar=NULL;
		if (turnitintool_check_config()) {
			$tii = new turnitintool_commclass("","FID99","Turnitin","fid99@turnitin.com","2",$loaderbar,false);
			$tii->migrateSRCData();
			if (is_callable(array($DB,'get_manager'))) {
				if (!$tii->getRerror()) {
					echo $OUTPUT->notification("Migrating Turnitin SRC Namespace: ".$tii->getRmessage(), 'notifysuccess');
				} else {
					echo $OUTPUT->notification("Migrating Turnitin SRC Namespace: ".$tii->getRmessage());
				}
			} else {
				if (!$tii->getRerror()) {
					notify($tii->getRmessage(), 'notifysuccess');
				} else {
					notify($tii->getRmessage());
				}
			}
			$result = $result && !$tii->getRerror();
		}
	}

    return $result;
}

/* ?> */