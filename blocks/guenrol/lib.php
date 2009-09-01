<?php

    /**
     * get the list of enrolled users from the
     * external database
     */
    function get_enrolled_users() {
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
                $userlist[] = $fields->$remoteuserfield;
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
    function get_existing_users( $userlist ) {
        $usercheck = array();
        foreach ($userlist as $username) {

            // if the user record exists we'll grab it
            if ($user = get_record( 'user','username',$username )) {
                $usercheck[ $username ] = $user;
                // $this->count_existingusers++;
            }
            else {
                $usercheck[ $username ] = null;
            }
        }
        return $usercheck;
    }

    /**DB Connect
     * NOTE: You MUST remember to disconnect
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
