<?php

    // define the auth plugin that we will use for ldap
    // lookups
    define( 'GUAUTH','guid' );

    /**
     * get the list of enrolled users from the
     * external database
     * Returns a list where the index is the username
     * as we will add stuff to this in due course.
     */
    function get_db_users($course) {
        global $CFG;

        // If $this->enrol_connect() succeeds you MUST remember to call
        // $this->enrol_disconnect() as it is doing some nasty vodoo with $CFG->prefix
        $enroldb = enrol_connect();
        if (!$enroldb) {
            error_log('[ENROL_DB] Could not make a connection');
            return;
        }

        // get the course code(s)
        $localcoursefield = $CFG->enrol_localcoursefield;
        $remoteuserfield = $CFG->enrol_remoteuserfield;
        $remotecoursefield = $CFG->enrol_remotecoursefield;
        $rawcode = addslashes( $course->$localcoursefield );

        // code splits on spaces
        $coursecodes = explode( ' ',$rawcode );

        // prepare array for user data
        $userlist = array();

        // get all the enrollments for this course code
        foreach ($coursecodes as $coursecode) {
            $sql = "select {$remoteuserfield},{$remotecoursefield} from {$CFG->enrol_dbtable} ";
            $sql .= "where {$CFG->enrol_remotecoursefield} = '$coursecode' ";

            if ($rs = $enroldb->Execute( $sql )) {

                // turn return into an array
                while (!$rs->EOF) {
                    $fields = rs_fetch_next_record($rs);
                    $username = $fields->$remoteuserfield;
                    $userlist[$username] = new StdClass;
                    $userlist[$username]->in_db = true;
                    $userlist[$username]->coursecode .= $fields->$remotecoursefield . ' ';
                }
            }
            else {
                error_log( '[block_guenrol] Failed to return data from enrol database' );
                $userlist = false;
            }
        }

        // got the users, so release database
        enrol_disconnect($enroldb);

        return $userlist;
    }

    /**
     * check which users exist in the user table
     * ( i.e. ones we don't need to create )
     * Returns an object for each user or null, indexed
     * on username
     */
    function get_authenticated_users( &$userlist ) {
        $usercount = 0;
        foreach ($userlist as $username => $userobj) {

            // if the user record exists we'll grab it
            if ($user = get_record( 'user','username',$username )) {
                $userlist[ $username ]->userid = $user->id;
                $userlist[ $username ]->profile_exists = true;
                $userlist[ $username ]->firstname = $user->firstname;
                $userlist[ $username ]->lastname = $user->lastname;
                $userlist[ $username ]->email = $user->email;
                $usercount++;
            }
            else {
                $userlist[ $username ]->profile_exists = false;
            }
        }
        return $usercount;
    }

    /**
     * find the users that are already enrolled in
     * the course. 
     * Flags true/false ones that are/are not
     * New records created for ones that are enrolled
     * but not in the original external db
     */
    function get_enrolled_users( &$userlist, $role, $context ) {

        // get enrolled users in default role (typically students)
        // this returns an array of user objects (indexed on userid)
        $users = get_role_users( $role->id, $context, false );

        // anything to do
        if (empty($users)) {
            return 0;
        }

        // interate over array and establish if they exist or not
        foreach ($users as $user) {
            $username = $user->username;
            // $userlist[ $username ]->profile = $user;
            $userlist[ $username ]->firstname = $user->firstname;
            $userlist[ $username ]->lastname = $user->lastname;
            $userlist[ $username ]->email = $user->email;
            $userlist[ $username ]->userid = $user->id;
            $userlist[ $username ]->profile_exists = true;
            $userlist[ $username ]->enrolled = true;
            if (!isset( $userlist[ $username ]->in_db )) {
                $userlist[ $username ]->in_db = false;
            }
        }

        return count( $users );
    } 

    /**
     * get the ldap data for all these accounts 
     */
    function get_ldap_data( &$userlist ) {
        $authplugin = get_auth_plugin(GUAUTH);

        foreach ($userlist as $username => $userdata) {
            
            // if they have a profile then we don't care
            if ( !empty($userlist[ $username ]->profile_exists) ) {
                continue;
            }
 
            // lookup in ldap
            if ($userinfo = $authplugin->get_userinfo( $username )) {
                $userinfo = (object)$userinfo;
                $userlist[ $username ]->ldap_userinfo = $userinfo;
                $userlist[ $username ]->firstname = ucwords(strtolower( $userinfo->firstname ));
                $userlist[ $username ]->lastname = ucwords(strtolower( $userinfo->lastname ));
                $userlist[ $username ]->email = $userinfo->email;
                $userlist[ $username ]->in_ldap = true;
            }
            else {
                $userlist[ $username ]->in_ldap = false;
                $userlist[ $username ]->firstname = '';
                $userlist[ $username ]->lastname = '';
                $userlist[ $username ]->email = '';
            }
        }
    }

    /**
     * Convenience feature to build the data array
     */
    function get_userlist( $course, $context, $role ) {
        $userlist = get_db_users( $course );
        get_authenticated_users( $userlist );
        get_enrolled_users( $userlist, $role, $context );
        get_ldap_data( $userlist );
        return $userlist;
    }

    /*
     * go through the list and process the users
     */
    function process_enrollments( $userlist, $course, $context, $role ) {
        global $CFG;

        // count the number actually processed
        $count  = 0;

        foreach ($userlist as $username => $user) {

            // if they're enrolled, just forget it
            if (!empty($user->enrolled)) {
                continue;
            }

            echo get_string('username','block_guenrol')." $username ";

            // if no profile but in ldap, create a new user
            if (!$user->profile_exists and !empty($user->in_ldap)) {
                $newuser = create_user_record( $username,'','guid' );
                $authplugin = get_auth_plugin(GUAUTH);
                $authplugin->update_user_record( $username );                
                $userid = $newuser->id;

                echo get_string('newprofilecreated','block_guenrol').", ";
            }
            else if ($user->profile_exists) {
                $userid = $user->userid;
            }

            // if should be enrolled but are not the enroll
            if (empty($user->enrolled) and $user->in_db and !empty($userid)) {
                role_assign($role->id, $userid, 0, $context->id, 0, 0, 0, 'database');

                echo get_string('assignedcourseas','block_guenrol')." $role->shortname. ";
            }

            echo "<br />\n";
            $count++;
        }

        if (empty($count)) {
            echo '<p><center>'.get_string( 'nothingtodo','block_guenrol' ).'</center></p>';
        }

        print_continue( "{$CFG->wwwroot}/blocks/guenrol/view.php?id={$course->id}" );
    }

    /**DB Connect
     * NOTE: You MUST  to disconnect
     * when you stop using it -- as this call will
     * sometimes modify $CFG->prefix for the whole of Moodle!
     */
    function enrol_connect() {
        global $CFG;

        // Try to connect to the external database (forcing new connection)
        $enroldb = &ADONewConnection($CFG->enrol_dbtype);
        if ($enroldb->Connect($CFG->enrol_dbhost, $CFG->enrol_dbuser, $CFG->enrol_dbpass, $CFG->enrol_dbname, true)) {
            $enroldb->SetFetchMode(ADODB_FETCH_ASSOC); ///Set Assoc mode always after DB connection
            return $enroldb;
        } else {
            trigger_error("Error connecting to enrolment DB backend with: "
                          . "$CFG->enrol_dbhost,$CFG->enrol_dbuser,$CFG->enrol_dbpass,$CFG->enrol_dbname");
            return false;
        }
    }

    /** DB Disconnect
     */
    function enrol_disconnect($enroldb) {
        global $CFG;

        $enroldb->Close();
    }

?>
