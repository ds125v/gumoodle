<?php

$handlers = array (
    'course_deleted' => array (
        'handlerfile'      => '/local/gusync/lib.php',
        'handlerfunction'  => 'local_gusync_course_deleted',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);
