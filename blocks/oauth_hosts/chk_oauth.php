<?php

if (file_exists($CFG->dirroot . '/mod/basiclti/OAuth.php'))
    require_once($CFG->dirroot . '/mod/basiclti/OAuth.php');
else
    require_once('OAuth.php');

function boh_checkOAuthRequest($frombrowser = true) {
    $store = new boh_OAuthDataStore();
    $server = new OAuthServer($store);
    $method = new OAuthSignatureMethod_HMAC_SHA1();
    $server->add_signature_method($method);
    $request = OAuthRequest::from_request();
    //echo '<pre>';print_r($request);echo '</pre>';
    $basestring = $request->get_signature_base_string();
    try {
        $server->verify_request($request);
        $valid = true;
    } catch (Exception $e) {
        if ($frombrowser) {
            $message = get_string('oauth_error', 'block_oauth_hosts') . ' (' . $e->getMessage() . ')';
            print_error($message);
        } else {
            echo '<error>' . $e->getMessage() . '</error>';
        }
        exit();
    }
}

class boh_OAuthDataStore {

    function lookup_consumer($consumer_key) {
        global $DB;
        $server = $DB->get_record('block_oauth_peerserver', array('peer_consumerkey'=>$consumer_key));
        if ($server) {
            return new OAuthConsumer($server->peer_consumerkey, $server->secret);
        } else {
            return NULL;
        }
    }

    function lookup_token($consumer, $token_type, $token) {
        // implement me
    }

    function lookup_nonce($consumer, $token, $nonce, $timestamp) {
        //echo "lookup_nonce - $consumer, $token, $nonce, $timestamp<br/>";
        global $DB;
        $dbnonce = $DB->get_record('block_oauth_nonce', array('nonce'=>$nonce, 'sentfrom'=>$consumer->key));
        if ($dbnonce) {
            return true;
        } else {
            //# Insert a new nonce
            $newnonce = new Object;
            $newnonce->nonce = $nonce;
            $newnonce->sentfrom = $consumer->key;
            $newnonce->used = time();
            $DB->insert_record('block_oauth_nonce', $newnonce);
            // clean out expired ones, really should be in s cron job rather than here
            $lim = time() - 24 * 3600;
            $DB->delete_records_select('block_oauth_nonce', "used < '$lim'");
            return false;
        }
        // implement me
    }

    function new_request_token($consumer) {
        // return a new token attached to this consumer
    }

    function new_access_token($token, $consumer) {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token
    }

}
