<?php
/**
 * @package   turnitintool
 * @copyright 2010 nLearning Ltd
 */

function xmldb_turnitintool_install() {
    global $DB;

/// Install logging support
    update_log_display_entry('turnitintool', 'view', 'turnitintool', 'name');
    update_log_display_entry('turnitintool', 'add', 'turnitintool', 'name');
    update_log_display_entry('turnitintool', 'update', 'turnitintool', 'name');

}

/* ?> */