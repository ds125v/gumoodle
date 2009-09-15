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
    function get_db_users() {
        global $COURSE, $CFG;

        // If $this->enrol_connect() succeeds you MUST remember to call
        // $this->enrol_disconnect() as it is doing some nasty vodoo with $CFG->prefix
        $enroldb = enrol_connect();
        if (!$enroldb) {
            error_log('[ENROL_DB] Could not make a connection');
            return;
        }

        // get the course code
        $localcoursefield = $CFG->enrol_localcoursefield;
        $remoteuserfield = $CFG->enrol_remoteuserfield;
        $coursecode = addslashes( $COURSE->$localcoursefield );

        // get all the enrollments for this course code
        $sql = "select {$remoteuserfield} from {$CFG->enrol_dbtable} ";
        $sql .= "where {$CFG->enrol_remotecoursefield} = '$coursecode' ";

        if ($rs = $enroldb->Execute( $sql )) {

            // turn return into an array
            $userlist = array();
            while (!$rs->EOF) {
                $fields = rs_fetch_next_record($rs);
                $userlist[$fields->$remoteuserfield] = new StdClass;
                $userlist[$fields->$remoteuserfield]->in_db = true;
            }
        }
        else {
            error_log( '[block_guenrol] Failed to return data from enrol database' );
            $userlist = false;
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
                $userlist[ $username ]->profile = $user;
                $userlist[ $username ]->profile_exists = true;
                $usercount++;
            }
            else {
                $userlist[ $username ]->profile = null;
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

        // interate over array and establish if they exist or not
        foreach ($users as $user) {
            $username = $user->username;
            $userlist[ $username ]->profile = $user;
            $userlist[ $username ]->profile_exists = true;
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
            if ($userinfo = $authplugin->get_userinfo( $username )) {
                $userlist[ $username ]->ldap_userinfo = $userinfo;
            }
        }
    }

    /**
     * Convenience feature to build the data array
     */
    function get_userlist( $context, $role ) {
        $userlist = get_db_users();
        get_authenticated_users( $userlist );
        get_enrolled_users( $userlist, $role, $context );
        get_ldap_data( $userlist );
        return $userlist;
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
