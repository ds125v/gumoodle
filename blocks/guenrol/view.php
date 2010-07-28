<?php

require_once( '../../config.php' );
require_once( "{$CFG->dirroot}/blocks/guenrol/lib.php" );
require_once( "{$CFG->libdir}/tablelib.php" );

$tick = '✔';

// get parameters
$id = required_param( 'id', PARAM_INT );
$action = optional_param( 'action','',PARAM_ALPHA );

require_login( $id );

// in case I forget, passing $id to require_login sets $COURSE
$course = $COURSE;

// get course context
$context = get_context_instance( CONTEXT_COURSE, $course->id );

// check capability
require_capability('block/guenrol:access',$context);

// get the default role for this course 
$role = get_default_course_role( $course );

// get the user data
$userlist = get_userlist( $course, $context, $role );


// create the table
$table = new flexible_table('block_guenrol_view');
$table->define_baseurl($CFG->wwwroot.'/blocks/guenrol/view.php?id='.$course->id );

// define table columns
$columns = array( 'userid','name','email','coursecode','enrol','enrolled','profile','ldap','method' );
$headers = array(
    get_string( 'userid','block_guenrol' ),
    get_string( 'name','block_guenrol' ),
    get_string( 'email','block_guenrol' ),
    get_string( 'coursecode','block_guenrol' ),
    get_string( 'enrol','block_guenrol' ),
    get_string( 'enrolled','block_guenrol' ),
    get_string( 'profile', 'block_guenrol' ),
    get_string( 'ldap','block_guenrol' ),
    get_string( 'method','block_guenrol' )
    );
$table->define_columns( $columns );
$table->define_headers( $headers );

// table settings
$table->sortable(false, 'name', SORT_DESC);
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

// count users that are IN registry (in_db) but ARE NOT enrolled (empty(enrolled))
// BUT exclude broken users - NO profile (empty(profile_exists)) but NO LDAP record (empty(in_ldap))
// i.e. they need added to the course
$addcount = 0;

// count this that are NOT registry (!in_db) but ARE enrolled (!empty(enrolled))
// i.e. they need removed
$removecount = 0;

// add the data
foreach ($userlist as $username => $user) {
    if ($user->in_db and empty($user->enrolled)) {
        if (!(empty($user->profile_exists) and empty($user->in_ldap))) {
            $addcount++;
        }
    }
    if (!$user->in_db and !empty($user->enrolled)) {
        $removecount++;
    }
    $uname = (!empty($user->userid)) ? "<a href=\"{$CFG->wwwroot}/user/view.php?id={$user->userid}&amp;course={$COURSE->id}\">".
        "$username</a>" : $username;
    $fullname = ucwords(strtolower( $user->firstname )) .' '. ucwords(strtolower( $user->lastname ));
    $enrol = ($user->in_db) ? $tick : '-';
    $enrolled = (!empty($user->enrolled)) ? $tick : '-';
    $profile = ($user->profile_exists) ? $tick : '-';
    $ldap = (!empty($user->in_ldap)) ? $tick : '-';
    $coursecode = (!empty($user->coursecode)) ? $user->coursecode : '-';
    $method = (!empty($user->enrol_method)) ? $user->enrol_method : '-';
    $table->add_data( array( $uname, $fullname, $user->email, $coursecode, $enrol, $enrolled, $profile, $ldap, $method  ) );
}

//
// DISPLAY PAGE
//

// Get title and navigation string.
$title = get_string('blockname','block_guenrol');
$navigation = build_navigation(array(array('name' => $title, 'link' => null, 'type' => 'misc')));

print_header($title, $title, $navigation);

// if process button has been pressed we will do 'the business' here
if (!empty($action) and ($action=='process') and confirm_sesskey()) {
    process_enrollments( $userlist, $course, $context, $role );
}
else if (!empty($action) and ($action=='remove') and confirm_sesskey()) {
    process_remove( $userlist, $course, $context, $role );
}
else {

    // button to create the users
    if (!empty($addcount)) {
        print_box_start();
        echo "<form action=\"{$CFG->wwwroot}/blocks/guenrol/view.php\" method=\"post\">\n";
        echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
        echo "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />\n";
        echo "<input type=\"hidden\" name=\"action\" value=\"process\" />\n";
        echo "<center><input type=\"submit\" value=\"". get_string('process','block_guenrol')." ($addcount)\" /></center>\n";
        echo "</form>\n";
        print_box_end();
    }

    // button to unenroll non registry users
    if (!empty($removecount)) {
        print_box_start();
        echo "<form action=\"{$CFG->wwwroot}/blocks/guenrol/view.php\" method=\"post\">\n";
        echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
        echo "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />\n";
        echo "<input type=\"hidden\" name=\"action\" value=\"remove\" />\n";
        echo "<center><input type=\"submit\" value=\"". get_string('remove','block_guenrol')." ($removecount)\" /></center>\n";
        echo "</form>\n";
        print_box_end();
    }

    // if neither button is displayed...
    if (empty($addcount) and empty($removecount)) {
        print_box_start();
        echo "<p><center>".get_string('nothingtodo','block_guenrol')."</center></p>";
        print_box_end();
    }
    
    // table
    $table->print_html();
}


print_footer();

?>

