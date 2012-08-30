<?php

$THEME->name = 'gu23';
$THEME->parents = array('bootstrap');
$THEME->parents_exclude_sheets = array(
'bootstrap'=>array(
'decoration'
)
); 
$THEME->sheets = array('gu23');
$THEME->layouts = array(
    'base' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'standard' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'course' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post'
    ),
    'coursecategory' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'incourse' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'frontpage' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'admin' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    'mydashboard' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'mypublic' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => 'side-post',
    ),
    'login' => array(
        'file' => 'gu23.php',
        'regions' => array(),
    ),
    'popup' => array(
        'file' => 'gu23.php',
        'regions' => array(),
        'options' => array('nofooter'=>true, 'noblocks'=>true, 'nonavbar'=>true),
    ),
    'frametop' => array(
        'file' => 'gu23.php',
        'regions' => array(),
    ),
    'maintenance' => array(
        'file' => 'gu23.php',
        'regions' => array(),
    ),
    'embedded' => array(
        'file' => 'embedded.php',
        'regions' => array(),
    ),
    'print' => array(
        'file' => 'gu23.php',
        'regions' => array(),
    ),
    'redirect' => array(
        'file' => 'gu23.php',
        'regions' => array(),
    ),
    'report' => array(
        'file' => 'gu23.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    )
);
