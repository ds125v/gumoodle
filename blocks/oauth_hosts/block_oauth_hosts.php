<?php

// block_oauth_hosts - A block and associated code that allows multiple moodles
// to be linked as peers provided they share external user authentication.
// Based on the IMS BasicLTI version of OAuth.
// Niall S F Barr, March 2011, updated August 2012.
require_once($CFG->libdir . '/filelib.php');
require_once('sign_oauth.php');

define('COURSELISTLIMIT', 50);

class block_oauth_hosts extends block_list {

    function init() {
        $this->title = get_string('oauth_hosts', 'block_oauth_hosts');
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('mycourses', 'block_oauth_hosts'));
    }

    function get_content() {
        global $USER, $OUTPUT, $CFG;
        $action = optional_param('oauth_action', '', PARAM_TEXT);
        if (($this->content !== NULL) && ($action == '')) {
            return $this->content;
        }
        $this->content = new stdClass;
        $icon = "<img src=\"" . $OUTPUT->pix_url('i/course') . "\" class=\"icon\" alt=\"" . get_string("coursecategory") . "\" />";

        $this->content->text = '';
        if (isset($USER->username)) {
            $enrolments = getEnrolments($USER->username);
        } else {
            $enrolments = false;
        }
        if ($enrolments && sizeof($enrolments)) {
            foreach ($enrolments as $e) {
                if ($e['wwwroot'] == $CFG->wwwroot) {
                    $linkurl = $CFG->wwwroot . '/course/view.php?id=' . $e['courseid'];
                } else {
                    $linkurl = $CFG->wwwroot . '/blocks/oauth_hosts/jump.php?server=' . urlencode($e['wwwroot']) . '&courseid=' . $e['courseid'];
                }
                $this->content->items[] = "<a href='$linkurl'>{$e['name']}</a>";
                $this->content->icons[] = $icon;
            }
        } else {
            $this->content->items[] = get_string('nocoursesfound', 'block_oauth_hosts');
            ;
            $this->content->icons[] = '';
        }
        $this->content->footer = '';
        return $this->content;
    }

    function applicable_formats() {
        return array('site-index' => true, 'course-view' => true, 'course-view-social' => false, 'mod' => false);
    }

    function instance_allow_config() {
        return true;
    }

    function has_config() {
        return true;
    }

    function config_save($data) {
        $hostdata = new Object();
        $allhosts_list = optional_param('block_oauth_allhosts', false, PARAM_TEXT);
        $server_setup = optional_param('ext_db_settings', 0, PARAM_INT);
        if ($allhosts_list !== false) {
            $hostlist = explode("\n", $allhosts_list);
            $hostobs = array();
            $hosturls = array();
            foreach ($hostlist as $h) {
                if (strlen(trim($h))) {
                    $type = '';
                    $parts = explode(' ', trim(preg_replace('/\s+/', ' ', $h)), 5);
                    if (sizeof($parts) == 5) {
                        $url = $parts[0];
                        $key = $parts[1];
                        $key2 = $parts[2];
                        $secret = $parts[3];
                        $type = $parts[4];
                    } elseif (sizeof($parts) == 4) {
                        $url = $parts[0];
                        $key = $parts[1];
                        $key2 = '';
                        $secret = $parts[2];
                        $type = $parts[3];
                    }
                    if (in_array($type, array('peer', 'source', 'target'))) {
                        if (substr($url, strlen($url) - 1) != '/')
                            $url .= '/';
                        $h2 = new Object;
                        $h2->oauth_url = $url;
                        $h2->peer_consumerkey = $key;
                        $h2->local_consumerkey = $key2;
                        $h2->secret = $secret;
                        $h2->type = $type;
                        $hosturls[$url] = sizeof($hostobs);
                        $hostobs[] = $h2;
                    }
                    //else // just ignore badly formatted stuff for now
                }
            }
            // now update the database without breaking anything...
            $chosts = get_records('block_oauth_peerserver');
            foreach ($chosts as $h) {
                if (array_key_exists($h->oauth_url, $hosturls)) {
                    $hostobs[$hosturls[$h->oauth_url]]->id = $h->id;
                    update_record('block_oauth_peerserver', $hostobs[$hosturls[$h->oauth_url]]);
                } else {
                    delete_records('block_oauth_peerserver', 'id', $h->id);
                }
            }
            foreach ($hostobs as $h) {
                if (!isset($h->id)) {
                    insert_record('block_oauth_peerserver', $h);
                }
            }
        } elseif ($server_setup) {
            $block_oauth_hosts_use_ext_db = optional_param('block_oauth_hosts_use_ext_db', 0, PARAM_INT);
            $block_oauth_hosts_db_server = required_param('block_oauth_hosts_db_server', PARAM_TEXT);
            $block_oauth_hosts_db_name = required_param('block_oauth_hosts_db_name', PARAM_TEXT);
            $block_oauth_hosts_db_user = required_param('block_oauth_hosts_db_user', PARAM_TEXT);
            $block_oauth_hosts_db_password = required_param('block_oauth_hosts_db_password', PARAM_TEXT);
            $block_oauth_hosts_db_tablename = required_param('block_oauth_hosts_db_tablename', PARAM_TEXT);
            set_config('block_oauth_hosts_use_ext_db', $block_oauth_hosts_use_ext_db);
            set_config('block_oauth_hosts_db_server', $block_oauth_hosts_db_server);
            set_config('block_oauth_hosts_db_name', $block_oauth_hosts_db_name);
            set_config('block_oauth_hosts_db_user', $block_oauth_hosts_db_user);
            set_config('block_oauth_hosts_db_password', $block_oauth_hosts_db_password);
            set_config('block_oauth_hosts_db_tablename', $block_oauth_hosts_db_tablename);
        } else {
            $hostdata->oauth_url = required_param('block_oauth_hosts_url', PARAM_URL);
            if (substr($hostdata->oauth_url, strlen($hostdata->oauth_url) - 1) != '/')
                $hostdata->oauth_url .= '/';
            $hostdata->peer_consumerkey = required_param('block_oauth_hosts_peer_consumer_key', PARAM_TEXT);
            $hostdata->local_consumerkey = required_param('block_oauth_hosts_local_consumer_key', PARAM_TEXT);
            $hostdata->secret = required_param('block_oauth_hosts_shared_secret', PARAM_TEXT);
            $hostdata->type = required_param('block_oauth_hosts_access_type', PARAM_TEXT);
            $hostdata->id = optional_param('block_oauth_hosts_id', 0, PARAM_INT);
            if ($hostdata->id > 0) {

                if (optional_param('delete', '', PARAM_TEXT) != '')
                    delete_records('block_oauth_peerserver', 'id', $hostdata->id);
                else
                    update_record('block_oauth_peerserver', $hostdata);
            }
            else
                insert_record('block_oauth_peerserver', $hostdata);
        }
        return false; // don't save for now.
    }

}

function getEnrolments($user) {
    $serverdetails = get_config('local_gusync'); // in 1.9 was block_gusync
    if (isset($serverdetails->dbhost)) {
        $sql = "SELECT * FROM moodleenrolments INNER JOIN moodlecourses ON moodleenrolments.moodlecoursesid=moodlecourses.id  WHERE moodleenrolments.guid='$user' ORDER BY timelastaccess DESC, timestart DESC;";
        $dblink = mysql_connect("{$serverdetails->dbhost}", "{$serverdetails->dbuser}", "{$serverdetails->dbpass}", true);
        if (!$dblink) {
            return false;
        }
        $dbconn = mysql_select_db($serverdetails->dbname, $dblink);
        if (!$dbconn) {
            return false;
        }
        $result = mysql_query($sql, $dblink);
        if (!$result) {
            return false;
        }
        $out = array();
        while ($r = mysql_fetch_assoc($result)) {
            $inf = array();
            $inf['enrolmenttime'] = $r['timestart'];
            $inf['lastaccessed'] = $r['timelastaccess'];
            $inf['site'] = $r['site'];
            $inf['wwwroot'] = $r['wwwroot'];
            $inf['courseid'] = $r['courseid'];
            $inf['shortname'] = $r['shortname'];
            $inf['name'] = $r['name'];
            $inf['starttime'] = $r['startdate'];
            $out[] = $inf;
        }
        mysql_free_result($result);
        return $out;
    } else {
        return false;
    }
}

