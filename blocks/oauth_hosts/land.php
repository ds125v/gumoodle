<?php

require_once('../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once('chk_oauth.php');
require_once('sign_oauth.php');
// verify correct oauth data
// get username and login type
// get USER who matches that
// if USER doesn't exist make a callback to get USER data and create
// do the final bits as below...
boh_checkOAuthRequest();
$username = required_param('user_id', PARAM_TEXT);
// Only one of the following should be included in the landing URL
$courseid = optional_param('courseid', 0, PARAM_INTEGER);
$categoryid = optional_param('categoryid', 0, PARAM_INTEGER);
$wantsurl = optional_param('wantsurl', '', PARAM_RAW);
$USER = get_complete_user_data('username', $username);
if (!$USER) {
    $USER = new Object;
    $USER->id = 0; // This is just to work around the poor design/bugs in moodlelib
    $consumer_key = required_param('oauth_consumer_key', PARAM_TEXT);
    $peer = $DB->get_record('block_oauth_peerserver', array('consumerkey' => $consumer_key));
    $params = array('username' => $username, 'action' => 'userprofile');
    $endpoint = $peer->oauth_url . 'info.php';
    if (strlen($peer->local_consumerkey)) {
        $consumerkey = $peer->local_consumerkey;
    } else {
        $consumerkey = $CFG->wwwroot;
    }
    $params = boh_signParameters($params, $endpoint, "POST", $consumerkey, $peer->secret);
    $userdata = download_file_content($endpoint, null, $params, false, 10, 10);
    $xml = new SimpleXMLElement($userdata);
    $userob = new Object();
    foreach ($xml->xpath("*") as $cxml) {
        $ename = $cxml->getName();
        $userob->$ename = strval($cxml);
    }
    $userob->id = false;
    $user = create_user_record($userob->username, $userob->password, $userob->auth);
    $user->idnumber = $userob->idnumber;
    $user->firstname = $userob->firstname;
    $user->lastname = $userob->lastname;
    $user->email = $userob->email;
    $user->emailstop = $userob->emailstop;
    $user->icq = $userob->icq;
    $user->skype = $userob->skype;
    $user->yahoo = $userob->yahoo;
    $user->aim = $userob->aim;
    $user->msn = $userob->msn;
    $user->phone1 = $userob->phone1;
    $user->phone2 = $userob->phone2;
    $user->institution = $userob->institution;
    $user->department = $userob->department;
    $user->address = $userob->address;
    $user->city = $userob->city;
    $user->country = $userob->country;
    $user->lang = $userob->lang;
    $user->timezone = $userob->timezone;
    $user->secret = $userob->secret;
    $user->url = $userob->url;
    $user->mailformat = $userob->mailformat;
    $user->maildigest = $userob->maildigest;
    $user->maildisplay = $userob->maildisplay;
    $user->htmleditor = $userob->htmleditor;
    $user->autosubscribe = $userob->autosubscribe;
    $user->trackforums = $userob->trackforums;
    $user->screenreader = $userob->screenreader;
    update_record('user', $user);
    $USER = get_complete_user_data('username', $username);
}
if ($USER) {
    complete_user_login($USER);
}
// redirect
if ($courseid > 0) {
    redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
} elseif ($categoryid > 0) {
    redirect($CFG->wwwroot . '/course/category.php?id=' . $categoryid);
} elseif ($wantsurl != '') {
    if (substr($wantsurl, 0, 1) == '/') {
        redirect($CFG->wwwroot . $wantsurl);
    } else {
        redirect($CFG->wwwroot . '/' . $wantsurl);
    }
} else {
    redirect($CFG->wwwroot);
}
?>

