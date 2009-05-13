<?php
	require_once("../../config.php");

	$sCategory = optional_param('Cat');
	$nUID = optional_param('UID');
	$sStatus = optional_param('Status');

	// Stores the collapsed status of a category for a particular user.
	
	// refer to http://docs.moodle.org/en/Development:Coding#Security_issues_.28and_handling_form_and_URL_data.29
	
	
	
	// Check for entry in DB for UID/Category
	if ( record_exists('block_my_courses', 'category_name', $sCategory, 'userid', $nUID) ) {
		// Record exists, update record
		// echo 'Exists';
		set_field('block_my_courses', 'collapsed', $sStatus, 'category_name', $sCategory, 'userid', $nUID);
	} else {
		// no entry found, insert record
		// echo 'Doesn\'t Exist';
		$r = new object();
        	$r->category_name = $sCategory;
		$r->userid = $nUID;
		$r->collapsed = $sStatus;
		insert_record('block_my_courses', $r, false);
	}
	/*
	echo '<br>';
	echo 'Category:' . $sCategory . '<br>';
	echo 'UID:' . $nUID . '<br>';
	echo 'Status:' . $sStatus . '<br>';
	*/
?>