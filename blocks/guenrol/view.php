<?php

require_once( '../../config.php' );
require_once( "{$CFG->dirroot}/blocks/guenrol/lib.php" );
require_once( "{$CFG->libdir}/tablelib.php" );

$tick = '✔';

require_login();

// get parameters
$id = required_param( 'id', PARAM_INT );

// find course
$course = get_record( 'course','id',$id );

// get course context
$context = get_context_instance( CONTEXT_COURSE, $course->id );

// get the default role for this course 
$role = get_default_course_role( $course );

// get the user data
$userlist = get_userlist( $course, $context, $role );

// create the table
$table = new flexible_table('block_guenrol_view');
$table->define_baseurl($CFG->wwwroot.'/blocks/guenrol/view.php?id='.$course->id );

// define table columns
$columns = array( 'userid','name','email','enrol' );
$headers = array(
    get_string( 'userid','block_guenrol' ),
    get_string( 'name','block_guenrol' ),
    get_string( 'email','block_guenrol' ),
    get_string( 'enrol','block_guenrol' ) 
    );
$table->define_columns( $columns );
$table->define_headers( $headers );

// table settings
$table->sortable(true, 'name', SORT_DESC);
$table->collapsible(true);
$table->initialbars(true);
$table->pageable(false);

// set attributes in the table tag
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'guenrol');
$table->set_attribute('class', 'generaltable generalbox');
$table->set_attribute('align', 'center');
$table->set_attribute('width', '80%');

// initialise table
$table->setup();

// add the data
foreach ($userlist as $username => $user) {
    $fullname = "{$user->firstname} {$user->lastname}";
    if ($user->in_db) {
        $enrol = $tick;
    }
    else {
        $enrol = ' ';
    }
    $table->add_data( array( $username, $fullname, $user->email, $enrol  ) );
}

//
// DISPLAY PAGE
//

// Get title and navigation string.
$title = get_string('blockname','block_guenrol');
$navigation = build_navigation(array(array('name' => $title, 'link' => null, 'type' => 'misc')));

print_header($title, $title, $navigation);

// table
$table->print_html();

print_footer();

?>

