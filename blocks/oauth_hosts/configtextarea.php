<?php

class oauth_setting_configtextarea extends admin_setting_configtextarea {

    function output_html($data, $query = '') {
        $out = '<div class="form-item clearfix" id="admin-' . $this->name . '"><div class="form-label">';
        $out .= '<label for = "id_s__' . $this->name . '"><span class="form-shortname">' . $this->name . '</span></label></div>';
        $out .= '<div class="form-setting"><div class="form-textarea"><textarea name="' . $this->name . '" cols="90" rows="10" wrap="off" id="id_s__' . $this->name . '">';
        $out .= $data;
        $out .= '</textarea></div>';
        $out .= '<div class="form-defaultinfo">Default: ' . $this->defaultsetting . '</div></div>';
        $out .= '<div class="form-description"><p>' . $this->description . '</p></div></div>';
        return $out;
    }

    function config_read($name) {
        global $DB;
        $peers = $DB->get_records('block_oauth_peerserver');
        $out = "";
        foreach ($peers as $p) {
            $out .= "{$p->oauth_url} {$p->peer_consumerkey} {$p->local_consumerkey} {$p->secret} {$p->type}\n";
        }
        return $out;
    }

    function config_write($name, $value) {
        global $DB;
        $hostlist = explode("\n", $value);
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
        $chosts = $DB->get_records('block_oauth_peerserver');
        foreach ($chosts as $h) {
            if (array_key_exists($h->oauth_url, $hosturls)) {
                $hostobs[$hosturls[$h->oauth_url]]->id = $h->id;
                $DB->update_record('block_oauth_peerserver', $hostobs[$hosturls[$h->oauth_url]]);
            } else {
                $DB->delete_records('block_oauth_peerserver', 'id', $h->id);
            }
        }
        foreach ($hostobs as $h) {
            if (!isset($h->id)) {
                $DB->insert_record('block_oauth_peerserver', $h);
            }
        }
        return $value;
    }

}

