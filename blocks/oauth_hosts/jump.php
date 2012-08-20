<?php

require_once('../../config.php');
require_once('sign_oauth.php');
require_login(SITEID, false);
$hostid = optional_param('hostid', 0, PARAM_INT);
if ($hostid == 0) {
    $serverroot = trim(required_param('server', PARAM_CLEAN));
}
$wantsurl = optional_param('wantsurl', '', PARAM_RAW);
$courseid = optional_param('courseid', 0, PARAM_INT);
if ($hostid == 0) {
    $rhost = $DB->get_record('block_oauth_peerserver', array('peer_consumerkey' => $serverroot));
    //If serverroot known, but no OAuth data just redirect to the Moodle course rather than showing an error
    if ($rhost === false) {
        redirect($serverroot . '/course/view.php?id=' . $courseid);
        exit();
    }
} else {
    $rhost = $DB->get_record('block_oauth_peerserver', array('id' => $hostid));
}
if ($rhost === false) {
    $message = get_string('oauth_error', 'block_oauth_hosts') . ' (Unknown remote host)';
    print_error($message);
}
if ($USER->auth === 'manual') {
    print_error(get_string('oauth_manual_user', 'block_oauth_hosts'));
}
if (strlen($rhost->local_consumerkey)) {
    $consumerkey = $rhost->local_consumerkey;
} else {
    $consumerkey = $CFG->wwwroot;
}
$params = array();
$params["user_id"] = $USER->username;
$params["user_auth"] = $USER->auth;
if ($courseid > 0) {
    $params["courseid"] = $courseid;
} elseif (strlen($wantsurl)) {
    $params["wantsurl"] = $wantsurl;
}
$params["oauth_callback"] = "about:blank";
$endpoint = $rhost->oauth_url . 'land.php';
$params = boh_signParameters($params, $endpoint, "GET", $consumerkey, $rhost->secret);
//echo '<pre>'; print_r($params); echo '</pre>';echo "<a href='". redirectLaunchURL($params, $endpoint) ."'>Niall's jump</a><br/>";
redirect(redirectLaunchURL($params, $endpoint));

function redirectLaunchURL($newparms, $endpoint) {
    global $last_base_string;
    $data = "";
    foreach ($newparms as $key => $value) {
        $data .= htmlspecialchars($key) . "=" . urlencode(htmlspecialchars($value)) . "&";
    }
    $data = substr($data, 0, strlen($data) - 1);
    if (strpos($endpoint, '?')) {
        $endpoint = substr($endpoint, 0, strpos($endpoint, '?'));
    }
    return $endpoint . "?" . $data;
}
?>


