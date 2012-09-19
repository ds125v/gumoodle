<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Displays IP address on map.
 *
 * This script is not compatible with IPv6.
 *
 * @package    core
 * @subpackage iplookup
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once('lib.php');

require_login();

$ip   = optional_param('ip', getremoteaddr(), PARAM_HOST);
$user = optional_param('user', 0, PARAM_INT);

if (isset($CFG->iplookup)) {
    //clean up of old settings
    set_config('iplookup', NULL);
}

$PAGE->set_url('/iplookup/index.php', array('id'=>$ip, 'user'=>$user));
$PAGE->set_pagelayout('popup');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

$info = array($ip);
$note = array();

if (!preg_match('/(^\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $ip, $match)) {
    print_error('invalidipformat', 'error');
}

if ($match[1] > 255 or $match[2] > 255 or $match[3] > 255 or $match[4] > 255) {
    print_error('invalidipformat', 'error');
}

if ($match[1] == '127' or $match[1] == '10' or ($match[1] == '172' and $match[2] >= '16' and $match[2] <= '31') or ($match[1] == '192' and $match[2] == '168')) {
    print_error('iplookupprivate', 'error');
}

$info = iplookup_find_location($ip);

if ($info['error']) {
    // can not display
    notice($info['error']);
}

if ($user) {
    if ($user = $DB->get_record('user', array('id'=>$user, 'deleted'=>0))) {
        // note: better not show full names to everybody
        if (has_capability('moodle/user:viewdetails', get_context_instance(CONTEXT_USER, $user->id))) {
            array_unshift($info['title'], fullname($user));
        }
    }
}
array_unshift($info['title'], $ip);

$title = implode(' - ', $info['title']);
$PAGE->set_title(get_string('iplookup', 'admin').': '.$title);
$PAGE->set_heading($title);
echo $OUTPUT->header();

if (empty($CFG->googlemapkey) and empty($CFG->googlemapkey3)) {
    $imgwidth  = 620;
    $imgheight = 310;
    $dotwidth  = 18;
    $dotheight = 30;

    $dx = round((($info['longitude'] + 180) * ($imgwidth / 360)) - $imgwidth - $dotwidth/2);
    $dy = round((($info['latitude'] + 90) * ($imgheight / 180)));

    echo '<div id="map" style="width:'.($imgwidth+$dotwidth).'px; height:'.$imgheight.'px;">';
    echo '<img src="earth.jpeg" style="width:'.$imgwidth.'px; height:'.$imgheight.'px" alt="" />';
    echo '<img src="marker.gif" style="width:'.$dotwidth.'px; height:'.$dotheight.'px; margin-left:'.$dx.'px; margin-bottom:'.$dy.'px;" alt="" />';
    echo '</div>';
    echo '<div id="note">'.$info['note'].'</div>';

} else if (empty($CFG->googlemapkey3)) {
    $PAGE->requires->js(new moodle_url("http://maps.google.com/maps?file=api&v=2&key=$CFG->googlemapkey"));
    $module = array('name'=>'core_iplookup', 'fullpath'=>'/iplookup/module.js');
    $PAGE->requires->js_init_call('M.core_iplookup.init', array($info['latitude'], $info['longitude']), true, $module);

    echo '<div id="map" style="width: 650px; height: 360px"></div>';
    echo '<div id="note">'.$info['note'].'</div>';

} else {
    if (strpos($CFG->wwwroot, 'https:') === 0) {
        $PAGE->requires->js(new moodle_url('https://maps.googleapis.com/maps/api/js', array('key'=>$CFG->googlemapkey3, 'sensor'=>'false')));
    } else {
        $PAGE->requires->js(new moodle_url('http://maps.googleapis.com/maps/api/js', array('key'=>$CFG->googlemapkey3, 'sensor'=>'false')));
    }
    $module = array('name'=>'core_iplookup', 'fullpath'=>'/iplookup/module.js');
    $PAGE->requires->js_init_call('M.core_iplookup.init3', array($info['latitude'], $info['longitude'], $ip), true, $module);

    echo '<div id="map" style="width: 650px; height: 360px"></div>';
    echo '<div id="note">'.$info['note'].'</div>';
}

echo $OUTPUT->footer();
