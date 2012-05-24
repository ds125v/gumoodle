<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/auth/ldap/auth.php' );

class auth_plugin_guid extends auth_plugin_ldap {

    /**
     * constructor
     */
    function auth_plugin_guid() {
        parent::auth_plugin_ldap();
        $this->authtype = 'guid';
        $this->errorlogtag = '[AUTH GUID]';
        $this->init_plugin($this->authtype);
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username (without system magic quotes)
     * @param string $password The password (without system magic quotes)
     *
     * @return bool Authentication success or failure.
     */
    function user_login($username, $password) {
        if (! function_exists('ldap_bind')) {
            print_error('auth_ldapnotinstalled', 'auth_ldap');
            return false;
        }

        if (!$username or !$password) {    // Don't allow blank usernames or passwords
            return false;
        }

        //$textlib = textlib_get_instance();
        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);
        $extpassword = textlib::convert($password, 'utf-8', $this->config->ldapencoding);

        $ldapconnection = $this->ldap_connect();
        $ldap_user_dn = $this->ldap_find_userdn($ldapconnection, $extusername);

        // If ldap_user_dn is empty, user does not exist
        if (!$ldap_user_dn) {
            $this->ldap_close();
            return false;
        }

        // University of Glasgow ugly hack
        // use compare rather than bind to make sure all possible
        // users authenticate.
        // Try to compare with current username and password
        $ldap_login = ldap_compare($ldapconnection, $ldap_user_dn, 'userPassword', $extpassword);
        $this->ldap_close();
        // need this because ldap_compare returns -1 for error
        if ($ldap_login===true) {
            return true;
        }

        return false;
    }

    /**
     * Reads user information from ldap and returns it in array()
     *
     * Function should return all information available. If you are saving
     * this information to moodle user-table you should honor syncronization flags
     *
     * @param string $username username
     *
     * @return mixed array with no magic quotes or false on error
     */
    function get_userinfo($username) {
        global $SESSION;

        //$textlib = textlib_get_instance();
        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);

        $ldapconnection = $this->ldap_connect();
        if(!($user_dn = $this->ldap_find_userdn($ldapconnection, $extusername))) {
            return false;
        }

        $search_attribs = array();
        $attrmap = $this->ldap_attributes();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                if (!in_array($value, $search_attribs)) {
                    array_push($search_attribs, $value);
                }
            }
        }

        // Ugly University of Glasgow hack
        // add additional fields to search attributes to get
        // optional emailaddress field
        $search_attribs[] = 'emailaddress';

        if (!$user_info_result = ldap_read($ldapconnection, $user_dn, '(objectClass=*)', $search_attribs)) {
            return false; // error!
        }

        $user_entry = ldap_get_entries_moodle($ldapconnection, $user_info_result);
        if (empty($user_entry)) {
            return false; // entry not found
        }

        // University of Glasgow Ugly Hack
        // if 'mail' field is empty consider using 'emailaddress'
        // field (if not empty). The 3# characters will need stripped
        // from the front (if extant) and as this is their
        // private email address they will need their email
        // visibility set to hidden. This will be stored for later
        // so we can check the visibility setting. 
        $SESSION->gu_email = '';
        if (empty($user_entry[0]['mail'][0])) {
            if (!empty($user_entry[0]['emailaddress'][0])) {
                // check for '3#' code and strip
                $emailaddress = $user_entry[0]['emailaddress'][0];
                $SESSION->gu_email = ltrim( $emailaddress, '3#' );
            }
        }

        $result = array();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            $ldapval = NULL;
            foreach ($values as $value) {
                $entry = array_change_key_case($user_entry[0], CASE_LOWER);
                if (($value == 'dn') || ($value == 'distinguishedname')) {
                    $result[$key] = $user_dn;
                    continue;
                }
                if (!array_key_exists($value, $entry)) {
                    continue; // wrong data mapping!
                }
                if (is_array($entry[$value])) {
                    $newval = textlib::convert($entry[$value][0], $this->config->ldapencoding, 'utf-8');
                } else {
                    $newval = textlib::convert($entry[$value], $this->config->ldapencoding, 'utf-8');
                }
                if (!empty($newval)) { // favour ldap entries that are set
                    $ldapval = $newval;
                }
            }
            if (!is_null($ldapval)) {
                $result[$key] = $ldapval;
            }
        }

        // University of Glasgow Ugly Hack
        // Get the firstname from the email address
        // IF the email address looks like
        // firstname.lastname.nn@glasgow.ac.uk - otherwise don't
        // the lastname comes from LDAP (still) as that handles double-barelled
        // more effectively
        if (!empty($result['email'])) {
            $email = $result['email'];
            preg_match( '/^(\w+)\.(\w+)(\.\w+)?@glasgow\.ac\.uk$/', $email, $matches );

            // if array has firstname and lastname then we'll use only the first
            // because mail screws up double barelled names but LDAP doesn't (sigh!)
            // check both as a sanity check (the mail should have both bits of the name)
            // NOTE: this only works because we are reading these fields from LDAP on
            // every login anyway. If you turn that off they will be ignored.
            if (!empty( $matches[1] ) and !empty( $matches[2] )) {
                $firstname = ucfirst( strtolower( $matches[1] ) );
                $result[ 'firstname' ] = $firstname;
            }
        }

        $this->ldap_close();
        return $result;
    }


    function user_authenticated_hook( &$user, $username, $password ) {
        // Ugly University of Glasgow Hack
        // we're just going to use this to make sure that 'city' and
        // 'country' are set to something. If not we'll go for
        // 'Glasgow' and 'GB'
        global $SESSION, $CFG, $SITE, $DB;

        // check city
        if (empty($user->city)) {
            $DB->set_field( 'user', 'city', 'Glasgow', array('id'=>$user->id));
            $user->city = 'Glasgow';
        }

        // check country
        if (empty($user->country)) {
            $DB->set_field( 'user', 'country', 'GB', array('id'=>$user->id));
            $user->country = 'GB';
        }

        // more. If the user doesn't have an email address
        // and $this->gu_email exists we can use that but
        // we must hide their email address too (privacy)
        // if the gu_email is set then there is no 'mail' field
        // in GUID and we can safely use it to update the record every time
        if (!empty($SESSION->gu_email)) {
            $DB->set_field( 'user', 'email', $SESSION->gu_email, array('id'=>$user->id));

            // if they didn't have email set then this is the first time
            // so make the email private (they can unset this if they want) 
            if (empty($user->email)) {
                $DB->set_field( 'user', 'maildisplay', 0, array('id'=>$user->id));
            }

            $user->email = $SESSION->gu_email;
        }

        // if still no email then message
        if (empty($user->email)) {
            print_header(strip_tags($SITE->fullname), $SITE->fullname, 'home');
            notice( get_string('noemail','auth_guid'),$CFG->wwwroot );
        }
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    function config_form($config, $err, $user_fields) {
        global $CFG, $OUTPUT;

        if (!function_exists('ldap_connect')) { // Is php-ldap really there?
            echo $OUTPUT->notification(get_string('auth_ldap_noextension', 'auth_ldap'));
            return;
        }

        include($CFG->dirroot.'/auth/guid/config.html');
    }


}
