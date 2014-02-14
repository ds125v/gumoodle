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
 * This file contains functions used by the participation report
 *
 * @package    report
 * @subpackage anonymous
 * @copyright  2013 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class report_anonymous_renderer extends plugin_renderer_base {

    public function list_assign($url, $assignments) {
        echo "<h3>" . get_string('anonymousassignments', 'report_anonymous') . "</h3>";
        if (empty($assignments)) {
            echo "<div class=\"alert alert-warning\">" . get_string('noassignments', 'report_anonymous') . "</div>";
            return;
        }
        echo "<ul>";
        foreach ($assignments as $assignment) {
            $url->params(array('mod' => 'assign', 'assign' => $assignment->id));
            echo "<li><a href=\"$url\">";
            echo $assignment->name;
            echo "</a></li>";
        }
        echo "</ul>";
    }

    /**
     * List all turnitintool activities and parts (for selection)
     * @param moodle_url $url
     * @param array $tts list of parts
     */
    public function list_turnitintool($url, $tts) {
        echo "<h3>" . get_string('anonymoustts', 'report_anonymous') . "</h3>";
        if (empty($tts)) {
            echo "<div class=\"alert alert-warning\">" . get_string('notts', 'report_anonymous') . "</div>";
            return;
        }
        echo "<ul>";
        foreach ($tts as $tt) {
            echo "<li>" . $tt->name;
            echo "<ul>";
            foreach ($tt->parts as $part) {
                $url->params(array('mod' => 'turnitintool', 'part' => $part->id));
                echo "<li><a href=\"$url\">";
                echo $part->partname;
                echo "</a></li>";
            }
            echo "</ul></li>";
        }
        echo "</ul>";
    }

    /**
     * List of assignment users
     * @param int $courseid course id
     * @param object $assignment assignment
     * @param array $ausers all assignment users
     * @param array $anotusers all assignment users who did not submit
     * @param boolean $reveal Display full names or not
     */
    public function report_assign($courseid, $assignment, $ausers, $anotusers, $reveal) {
        echo "<h3>" . get_string('assignnotsubmit', 'report_anonymous', $assignment->name) . "</h3>";

        // Keep a track of records with no idnumber.
        $noids = array();
        echo "<ul>";
        $idmessage = false;
        foreach ($anotusers as $u) {
            if ($reveal) {
                $userurl = new moodle_url('/user/view.php', array('id' => $u->id, 'course' => $courseid));
                echo "<li>";
                echo "<a href=\"$userurl\">".fullname($u)."</a>";
                if (!$u->idnumber) {
                    echo "&nbsp;<strong>*</strong>";
                    $idmessage = true;
                }
                echo "</li>";
            } else if ($u->idnumber) {
                echo "<li>{$u->idnumber}</li>";
            } else {
                $noids[$u->id] = $u;
            }
        }
        echo "</ul>";
        if ($idmessage) {
            echo "<p><i><strong>*</strong> " . get_string('asterisk', 'report_anonymous') . "</i></p>";
        }
        echo "<p><strong>" . get_string('totalassignusers', 'report_anonymous', count($ausers)) . "</strong></p>";
        echo "<p><strong>" . get_string('totalnotassignusers', 'report_anonymous', count($anotusers)) . "</strong></p>";
        if (!$reveal && count($noids)) {
            echo "<p><strong>" . get_string('totalnoid', 'report_anonymous', count($noids)) . "</strong></p>";
        }
    }

    /**
     * List of turnitintool users
     * @param int $coursid courseid
     * @param object $part turnitin part
     * @param array $ausers all turnitin users
     * @param array $anotusers all turnitin users who did not submit
     * @param boolean $reveal Show names or not
     */
    public function report_turnitintool($courseid, $part, $ausers, $anotusers, $reveal) {
        echo "<h3>" . get_string('ttnotsubmit', 'report_anonymous', $part->partname) . "</h3>";

        // Keep a track of records with no idnumber.
        $noids = array();
        $idmessage = false;
        echo "<ul>";
        foreach ($anotusers as $u) {
            if ($reveal) {
                $userurl = new moodle_url('/user/view.php', array('id' => $u->id, 'course' => $courseid));
                echo "<li>";
                echo "<a href=\"$userurl\">".fullname($u)."</a>";
                if (!$u->idnumber) {
                    echo "&nbsp;<strong>*</strong>";
                    $idmessage = true;
                }
                echo "</li>";
            } else if ($u->idnumber) {
                echo "<li>{$u->idnumber}</li>";
            } else {
                $noids[$u->id] = $u;
            }
        }
        echo "</ul>";
        if ($idmessage) {
            echo "<p><i><strong>*</strong> " . get_string('asterisk', 'report_anonymous') . "</i></p>";
        }
        echo "<strong>" . get_string('totalttusers', 'report_anonymous', count($ausers)) . "</strong><br />";
        echo "<strong>" . get_string('totalnotttusers', 'report_anonymous', count($anotusers)) . "</strong><br />";
        if (!$reveal && count($noids)) {
            echo "<strong>" . get_string('totalnoid', 'report_anonymous', count($noids)) . "</strong><br />";
        }
    }

    /**
     * Display the additional actions some capabilities allow
     * @param moodle_url $url
     * @param boolean $reveal on/off
     */
    public function actions($context, $url, $reveal) {
        echo "<div class=\"alert\">".get_string('help', 'report_anonymous')."</div>";
        echo "<div>";
        if (has_capability('report/anonymous:shownames', $context)) {
            $showurl = clone($url);
            if ($reveal) {
                $showurl->params(array('reveal' => 0));
                $text = get_string('clickhidenames', 'report_anonymous');
            } else {
                $showurl->params(array('reveal' => 1));
                $text = get_string('clickshownames', 'report_anonymous');
            }
            echo "<a class=\"btn\" href=\"$showurl\">$text</a>";
        }

        if (has_capability('report/anonymous:export', $context)) {
            $url->params(array('export' => 1));
            $text = get_string('export', 'report_anonymous');
            echo "<a class=\"btn\" href=\"$url\">$text</a>";
        }
        echo "</div>";
    }

    public function back_button($url) {
        echo "<div style=\"margin-top: 20px;\">";
        echo "<a class=\"btn\" href=\"$url\">" . get_string('backtolist', 'report_anonymous') . "</a>";
        echo "</div>";
    }

}
