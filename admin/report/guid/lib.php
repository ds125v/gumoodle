<?php

function build_filter( $firstname, $lastname, $guid, $email ) {

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
    ldap_set_option( $dv,LDAP_OPT_SIZELIMIT, 26 );

    // search
    if (!$search = @ldap_search($dv, $ldapdn, $filter)) {
        debugging( 'ldap search failed for filter "'.$filter.'" '.ldap_error( $dv ) );
        return false;
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

function print_results( $results ) {
    // basic idea is to print as abbreviated list unless there is 
    // only one

    global $CFG;

    // check there are some
    if (empty($results)) {
        echo "<div class=\"generalbox\">".get_string( 'noresults','report_guid' )."</div>";
        return;
    }

    // BODGE: if there are 26 then show 25 and say there are more!!
    if (count($results)>25) {
        while( count($results)>25 ) {
            array_pop( $results );
        }
        $more_results = true;
    }
    else {
        $more_results = false;
    }
    
    // use fancy table thing
    require_once( "{$CFG->libdir}/tablelib.php" );

    if (count($results)>1) {
        $table = new flexible_table( 'ldap' );
        $table->define_columns( array('CN','firstname','lastname','email','more') );
        $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
        $table->define_headers( array(
            get_string( 'username' ),
            get_string( 'firstname' ),
            get_string( 'lastname' ),
            get_string( 'email' ),
            '') );
        $table->setup();
        foreach ($results as $cn => $result) {
            $guid = $result['uid'];
            empty($result['mail']) ? $mail='': $mail=$result['mail'];
            if ($user=get_record('user','username',$guid)) {
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
                '<a href="'."{$CFG->wwwroot}/admin/report/guid/index.php?action=search&amp;guid=$guid&amp;sesskey=".sesskey().'">'.
                get_string('more','report_guid').'</a>' ) );
        }
        $table->print_html();

        // if results truncated display warning
        if ($more_results) {
            echo '<div class="generalbox">'.get_string( 'moreresults','report_guid' ).'</div>';
        }
    }
    else {
        print_single( $results );
    }
}

function print_single( $results ) {
    // just print a single result
    global $CFG,$USER;

    $result = array_shift( $results );
    $fullname = ucwords(strtolower($result['givenname'].' '.$result['sn']));

    // do they have a moodle account?
    $username = $result['uid'];
    if ($user = get_record( 'user','username',$username )) {
        $displayname = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$user->id}&amp;course=1\">$fullname</a>";
        $create = '';
    }
    else {
        $displayname = $fullname;
        $create = "<a href=\"{$CFG->wwwroot}/admin/report/guid/index.php?action=create&amp;guid=$username&amp;sesskey=".sesskey()."\" >".
            get_string('create','report_guid')."</a>";

        // save the record in case we want to create the user
        $USER->report_guid_ldap = $result;
    }
    if (!empty($user)) {
        print_user_picture( $user,1,null,100 );
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
