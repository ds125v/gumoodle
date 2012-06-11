<?php

// Core settings are specified directly via assignment to $CFG variable.
// Example:
//   $CFG->somecoresetting = 'value';
//
// Plugin settings have to be put into a special array.
// Example:
//   $CFG->forced_plugin_settings = array('pluginname'  => array('settingname' => 'value', 'secondsetting' => 'othervalue'),
//                                        'otherplugin' => array('mysetting' => 'myvalue', 'thesetting' => 'thevalue'));
//
$CFG->forced_plugin_settings = array(
    'auth/guid' => array(
        'field_map_firstname'=>'givenName',
        'field_updatelocal_firstname'=>'onlogin',
        'field_lock_firstname'=>'locked',
        'field_map_lastname'=>'sn',
        'field_updatelocal_lastname'=>'onlogin',
        'field_lock_lastname'=>'locked',
        'field_map_email'=>'mail',
        'field_updatelocal_email'=>'onlogin',
        'field_lock_email'=>'locked',
        'field_map_idnumber'=>'workforceid',
        'field_updatelocal_idnumber'=>'onlogin',
        'field_lock_idnumber'=>'locked',
        'field_map_department'=>'costcenterdescription',
        'field_updatelocal_department'=>'onlogin',
        'field_lock_department'=>'locked',
        'field_map_phone1'=>'telephonenumber',
        'field_updatelocal_phone1'=>'onlogin',
        'field_lock_phone1'=>'locked',
    ),
);
