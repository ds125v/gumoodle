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
 * @subpackage tiigrade
 * @copyright  2013 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class report_tiigrade_renderer extends plugin_renderer_base {

    /**
     * List all turnitintool activities and parts (for selection)
     * @param moodle_url $url
     * @param array $tts list of parts
     */
    public function list_turnitintool($url, $tts) {
        echo "<h3>" . get_string('tiiactivities', 'report_tiigrade') . "</h3>";
        if (empty($tts)) {
            echo "<div class=\"alert alert-warning\">" . get_string('notts', 'report_tiigrade') . "</div>";
            return;
        }
        echo "<ul>";
        foreach ($tts as $tt) {
            echo "<li>" . $tt->name;
            echo "<ul>";
            foreach ($tt->parts as $part) {
                $url->params(array('part' => $part->id));
                echo "<li><a href=\"$url\">";
                echo $part->partname;
                echo "</a></li>";
            }
            echo "</ul></li>";
        }
        echo "</ul>";
    }

    /**
     * List of turnitintool users
     * @param int $coursid courseid
     * @param object $part turnitin part
     * @param array $submissions
     * @param boolean $reveal Show names or not
     */
    public function report($courseid, $part, $submissions, $reveal) {
        echo "<h3>" . get_string('tiisubmissions', 'report_tiigrade', $part->partname) . "</h3>";

        // Set up table.
        $table = new html_table();
        if ($reveal) {
            $table->head = array(
                get_string('idnumber'),
                get_string('paperid', 'report_tiigrade'),
                get_string('username'),
                get_string('grade'),
                get_string('similarity', 'report_tiigrade'),
                get_string('date'),
            );
        } else {
            $table->head = array(
                get_string('idnumber'),
                get_string('paperid', 'report_tiigrade'),
                get_string('grade'),
                get_string('similarity', 'report_tiigrade'),
                get_string('date'),
            );
        }

        $count = 0;
        foreach ($submissions as $submission) {
            $u = $submission->user;
            if (!$u) {
                continue;
            }
            $idnumber = !empty($u->idnumber) ? $u->idnumber : '<i>('.$u->username.')</i>';
            $grade = $submission->submission_grade ? $submission->submission_grade : '-';
            $similarity = $submission->submission_score ? $submission->submission_score : '-';
            $datestamp = date('d/M/Y', $submission->submission_modified);
            if ($reveal) {
                $userurl = new moodle_url('/user/view.php', array('id' => $u->id, 'course' => $courseid));
                $row = array(
                    $idnumber,
                    $submission->submission_objectid,
                    "<a href=\"$userurl\">".fullname($u)."</a>",
                    $grade,
                    $similarity,
                    $datestamp,
                );
            } else {
                $row = array(
                    $idnumber,
                    $submission->submission_objectid,
                    $grade,
                    $similarity,
                    $datestamp,
                );
            }
            $table->data[] = $row;
            $count++;
        }
        echo  html_writer::table($table);
        echo "<strong>" . get_string('totalsubmissions', 'report_tiigrade', $count) . "</strong><br />";
    }

    /**
     * Display the additional actions some capabilities allow
     * @param moodle_url $url
     * @param boolean $reveal on/off
     */
    public function actions($context, $url, $anonymous, $reveal) {
        echo "<div>";
        if (has_capability('report/tiigrade:shownames', $context) && $anonymous) {
            $showurl = clone($url);
            if ($reveal) {
                $showurl->params(array('reveal' => 0));
                $text = get_string('clickhidenames', 'report_tiigrade');
            } else {
                $showurl->params(array('reveal' => 1));
                $text = get_string('clickshownames', 'report_tiigrade');
            }
            echo "<a class=\"btn\" href=\"$showurl\">$text</a>";
        }

        $url->params(array('export' => 1));
        $text = get_string('export', 'report_tiigrade');
        echo "<a class=\"btn\" href=\"$url\">$text</a>";
        echo "</div>";
    }

    public function back_button($url) {
        echo "<div style=\"margin-top: 20px;\">";
        echo "<a class=\"btn\" href=\"$url\">" . get_string('backtolist', 'report_tiigrade') . "</a>";
        echo "</div>";
    }

}
