<?php

require_once("{$CFG->libdir}/formslib.php");

function build_filter( $firstname, $lastname, $guid, $email ) {

    // ldap filter doesn't like escaped characters
    $lastname = stripslashes( $lastname );
    $firstname = stripslashes( $firstname );

    // if the GUID is supplied then we don't care about anything else
    if (!empty($guid)) {
        return "uid=$guid";
    }

    // if the email is supplied then we don't care about name
    if (!empty($email)) {
        return "mail=$email";
    }

    // otherwise we'll take the name
    if (empty($firstname) and !empty($lastname)) {
        return "sn=$lastname";
    }
    if (!empty($firstname) and empty($lastname)) {
        return "givenname=$firstname";
    }
    if (!empty($firstname) and !empty($lastname)) {
        return "(&(sn=$lastname)(givenname=$firstname))";
    }

    // everything must have been empty
    return false;
}

function guid_ldapsearch( $ldaphost,$ldapdn, $filter ) {
    
    // connect to host
    if (!$dv = ldap_connect( $ldaphost )) {
        debugging( 'Failed to connect to ldap host ' );
        return false;
    }

    // anonymous bind
    if (!ldap_bind( $dv )) {
        debugging( 'Failed anonymous bind to ldap host '.ldap_error( $dv ) );
        return false;
    }

    // settings
    //ldap_set_option( $dv,LDAP_OPT_SIZELIMIT, 26 );

    // search
    if (!$search = @ldap_search($dv, $ldapdn, $filter)) {
        debugging( 'ldap search failed for filter "'.$filter.'" '.ldap_error( $dv ) );
        return false;
    }

    // check for errors returned 
    // (particularly partial results as GUID is limited to 100)
    $error_code = ldap_errno( $dv );
    $error_string = ldap_error( $dv );

    // if error returned then...
    // need to check for string
    if ($error_code != 0) { 
        return $error_string;
    }

    // check if we got any results
    if (ldap_count_entries( $dv, $search) < 1) {
        return array();
    }

    // get results
    if (!$results = ldap_get_entries($dv, $search)) {
        debugging( 'Failed to extract ldap results '.ldap_error( $dv ) );
        return false;
    }

    // unravel results
    $results = cleanupEntry( $results );

    return $results;
}

function cleanUpEntry( $entry ) {
  $retEntry = array();
  for ( $i = 0; $i < $entry['count']; $i++ ) {
    if (is_array($entry[$i])) {
      $subtree = $entry[$i];
      //This condition should be superfluous so just take the recursive call
      //adapted to your situation in order to increase perf.
      if ( ! empty($subtree['dn']) and ! isset($retEntry[$subtree['dn']])) {
        $retEntry[$subtree['dn']] = cleanUpEntry($subtree);
      }
      else {
        $retEntry[] = cleanUpEntry($subtree);
      }
    }
    else {
      $attribute = $entry[$i];
      if ( $entry[$attribute]['count'] == 1 ) {
        $retEntry[$attribute] = $entry[$attribute][0];
      } else {
        for ( $j = 0; $j < $entry[$attribute]['count']; $j++ ) {
          $retEntry[$attribute][] = $entry[$attribute][$j];
        }
      }
    }
  }
  return $retEntry;
}

function print_results( $results, $url ) {
    // basic idea is to print as abbreviated list unless there is 
    // only one

    global $CFG, $DB;

    // check there are some
    if (empty($results)) {
        echo "<div class=\"generalbox\">".get_string( 'noresults','report_guid' )."</div>";
        return;
    }

    // get/set paging information
    $pagesize = 20;
    $page = optional_param( 'page',0,PARAM_INT );
    $firstcol = $page * $pagesize + 1;
    $lastcol = $firstcol + $pagesize - 1;

    // use fancy table thing
    require_once( "{$CFG->libdir}/tablelib.php" );

    // note any external email addresses
    $externalmail = false;

    if (count($results)>1) {
        $table = new flexible_table( 'ldap' );
        $table->pageable( true );
        $table->pagesize( $pagesize, count( $results ) );
        $table->define_baseurl( $url->out(true, array('sesskey'=>sesskey())) );
        $table->define_columns( array('CN','firstname','lastname','email','more') );
        $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
        $table->define_headers( array(
            get_string( 'username' ),
            get_string( 'firstname' ),
            get_string( 'lastname' ),
            get_string( 'email' ),
            '') );
        $table->setup();
        $colnum = 0;
        foreach ($results as $cn => $result) {
            $colnum++;
            if (($colnum<$firstcol) or ($colnum>$lastcol)) {
                continue;
            }
            $guid = $result['uid'];

            // modify guid in url
            $url->param( 'guid', $guid );

            $mailinfo = getEmail( $result );
            $mail = $mailinfo['mail'];
            if (!$mailinfo['primary']) {
                $mail = "<i>$mail</i>";
                $externalmail = true;
            } 
            if ($user=$DB->get_record('user', array('username'=>strtolower($guid)))) {
                $username = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$user->id}&amp;course=1\">$guid</a>";
            }
            else {
                $username = $guid;
            }
            $table->add_data( array(
                $username,
                $result['givenname'],
                $result['sn'],
                $mail,
                '<a href="'.$url->out(true, array('sesskey'=>sesskey())).'">'.
                get_string('more','report_guid').'</a>' ) );
        }
        echo "<div class=\"generalbox\">Number of results = ".count($results)."</div>";
        $table->print_html();

        // if external emails - add note
        if ($externalmail) {
            echo '<div class="generalbox">'.get_string( 'externalmail','report_guid' ).'</div>';
        }

    }
    else {
        print_single( $results );
    }
}

function getEmail( $result ) {
    // try to find an email address to use
    if (!empty($result['mail'])) {
        return array( 'primary'=>true, 'mail'=>$result['mail'] );
    }
    if (!empty($result['emailaddress'])) {
        $mail = ltrim( $result['emailaddress'], '3#' );
        return array( 'primary'=>false, 'mail'=>$mail );
    }
    return array( 'primary'=>true, 'mail'=>'' );
}

function print_single( $results ) {
    global $OUTPUT;

    // just print a single result
    global $CFG, $USER, $DB;

    $result = array_shift( $results );
    $fullname = ucwords(strtolower($result['givenname'].' '.$result['sn']));

    // do they have an email
    $mailinfo = getEmail( $result );

    // do they have a moodle account?
    $username = $result['uid'];
    if ($user = $DB->get_record( 'user', array('username'=>strtolower($username)) )) {
        $displayname = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$user->id}&amp;course=1\">$fullname</a>";
        $create = '';
    }
    else {
        $displayname = $fullname;
        $create = "<a href=\"{$CFG->wwwroot}/report/guid/index.php?action=create&amp;guid=$username&amp;sesskey=".sesskey()."\" >";
        if (!empty( $mailinfo['mail'] )) {
            $create .= get_string('create','report_guid')."</a>";
        }
        else {
            $create = '<i>'.get_string('noemail','report_guid').'</i>';
        }

        // save the record in case we want to create the user
        $USER->report_guid_ldap = $result;
    }
    if (!empty($user)) {
        echo $OUTPUT->user_picture( $user, array('size'=>100) );
    }
    echo "<p><strong>".get_string( 'resultfor','report_guid')." $displayname</strong> $create ($username)</p>\n";
    array_prettyprint( $result );
}

function array_prettyprint( $rows ) {
    echo "<ul>\n";
    foreach ($rows as $name => $row) {
        if (is_array( $row )) {
            echo "<li><strong>$name:</strong>";
            array_prettyprint( $row );
            echo "</li>\n";
        }
        else {
            echo "<li><strong>$name</strong> => $row</li>\n";
        }
    }
    echo "</ul>\n";
}

// create new Moodle user
function createUserFromLdap( $result ) {
    global $DB;

    $user = create_user_record( $result['uid'], 'not cached', 'guid' ); 
    $user->firstname = $result['givenname'];
    $user->lastname = $result['sn'];
    $user->city = 'Glasgow';
    $user->country = 'GB';
    $mailinfo = getEmail( $result );
    $user->email = $mailinfo['mail'];
    if (!empty( $user->email )) {
        $DB->update_record( 'user', $user );

        // if not primary email make this email private
        if (!$mailinfo['primary']) {
            $DB->set_field( 'user','maildisplay', 0, array('id'=>$user->id));
        }
    }

    return $user;
}

// form definition for search
class guidreport_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        // main part
        $mform->addElement('header','guidheader', get_string( 'heading', 'report_guid' ) );
        $mform->addElement('html', '<div>'.get_string('instructions','report_guid' ) );
        $mform->addElement('text', 'firstname', get_string('firstname','report_guid' ) );
        $mform->addElement('text', 'lastname', get_string('lastname','report_guid' ) );
        $mform->addElement('text', 'email', get_string('email','report_guid' ) );
        $mform->addElement('text', 'guid', get_string('guidform','report_guid' ) );

        // action buttons
        $this->add_action_buttons();
    }
}

// form definition for upload form
class guidreportupload_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        // file upload
        $mform->addElement('header','guidupload',get_string('uploadheader', 'report_guid' ) );
        $mform->addElement('html', '<div>'.get_string('uploadinstructions','report_guid' ).'</div>' );
        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'report_guid' ) );
   
        // action buttons
        $this->add_action_buttons();
    }

}
