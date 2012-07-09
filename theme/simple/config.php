<?php

$THEME->name = 'simple';
$THEME->parents = array('base');
$THEME->parents_exclude_sheets = array(
'base'=>array(
'pagelayout'
)
); 
$THEME->sheets = array('bootstrap','simple','decoration');
$THEME->layouts = array(
    'base' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'standard' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'course' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post'
    ),
    'coursecategory' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'incourse' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'frontpage' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'admin' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'mydashboard' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
        'options' => array('langmenu'=>true),
    ),
    'mypublic' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'login' => array(
        'file' => 'simple.php',
        'regions' => array(),
        'options' => array('langmenu'=>true),
    ),
    'popup' => array(
        'file' => 'simple.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'noblocks'=>true, 'nonavbar'=>true),
    ),
    'frametop' => array(
        'file' => 'simple.php',
        'regions' => array(),
        'options' => array('nofooter'=>true),
    ),
    'maintenance' => array(
        'file' => 'simple.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>true),
    ),
    'embedded' => array(
        'file' => 'embedded.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>true),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => 'simple.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>false, 'noblocks'=>true),
    ),
    // The pagelayout used when a redirection is occuring.
    'redirect' => array(
        'file' => 'simple.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'nonavbar'=>true),
    ),
    'report' => array(
        'file' => 'simple.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    )
);
