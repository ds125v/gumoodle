<?php

if (file_exists($CFG->dirroot . '/mod/basiclti/OAuth.php'))
    require_once($CFG->dirroot . '/mod/basiclti/OAuth.php');
else
    require_once('OAuth.php');

// Function derived from one in from blti_util.php

function boh_signParameters($oldparms, $endpoint, $method, $oauth_consumer_key, $oauth_consumer_secret) {
    global $last_base_string;
    if (!isset($oldparms["oauth_callback"])) {
        $oldparms["oauth_callback"] = "about:blank";
    }
    $parms = $oldparms;
    $test_token = '';
    $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
    $test_consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL);
    $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, $method, $endpoint, $parms);
    $acc_req->sign_request($hmac_method, $test_consumer, $test_token);
    // Pass this back up "out of band" for debugging
    $last_base_string = $acc_req->get_signature_base_string();
    $newparms = $acc_req->get_parameters();
    return $newparms;
}

