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

class report_anonymous {

    /**
     * get blind assignments for this course
     * @param int $id course id
     * @return array
     */
    public static function get_assignments($id) {
        global $DB;

        $assignments = $DB->get_records('assign', array('blindmarking' => 1, 'course' => $id));
        return $assignments;
    }

    /**
     * Get anonymous turnitin assignments and the
     * parts that go with them
     * @param int $id course id
     * @return array
     */
    public static function get_tts($id) {
        global $DB;

        $tts = $DB->get_records('turnitintool', array('anon' => 1, 'course' => $id));
        foreach ($tts as $id => $tt) {
            $parts = $DB->get_records('turnitintool_parts', array('turnitintoolid' => $id, 'deleted' => 0));
            $tts[$id]->parts = $parts;
        }

        return $tts;
    }

    /**
     * can the user view the data submitted
     * some checks
     * @param string $mod which module (turnitintool or assign)
     * @param int $assignid assignment id
     * @param int $partid turnitintool part id
     * @param array $assignments list of valid assignments
     * @param array $tts list of valid tts
     * @return boolean true if ok
     */
    public static function allowed_to_view($mod, $assignid, $partid, $assignments, $tts) {
        if ($mod == 'assign') {
            return array_key_exists($assignid, $assignments);
        } else if ($mod == 'turnitintool') {
            foreach ($tts as $tt) {
                if (array_key_exists($partid, $tt->parts)) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Get the list of potential users for the assignment activity
     * @param object $context current role context
     * @return array list of users
     */
    public static function get_assign_users($context) {
        $idsonly = false;
        $currentgroup = null;
        if ($idsonly) {
            return get_enrolled_users($context, "mod/assign:submit", $currentgroup, 'u.id');
        } else {
            return get_enrolled_users($context, "mod/assign:submit", $currentgroup);
        }
    }

    /**
     * get list of users who have not submitted
     * @param int $assignid assignment id
     * @param array $users list of user objects
     * @return array list of user objects not submitted
     */
    public static function get_assign_notsubmitted($assignid, $users) {
        global $DB;

        $notsubusers = array();
        foreach ($users as $user) {
            if (!$DB->get_record('assign_submission', array('userid' => $user->id, 'assignment' => $assignid))) {
                $notsubusers[$user->id] = $user;
            }
        }

        return $notsubusers;
    }

    /**
     * sort users using callback
     */
    public static function sort_users($users, $onname=false) {
        uasort($users, function($a, $b) use ($onname) {
            if ($onname) {
                return strcasecmp(fullname($a), fullname($b));
            } else {
                return strcasecmp($a->idnumber, $b->idnumber);
            }
        });
        return $users;
    }

    /**
     * Get the list of potential users for the turnitintool activity
     * @param object $context current role context
     * @return array list of users
     */
    public static function get_turnitintool_users($context) {
        return get_enrolled_users($context, "mod/turnitintool:submit");
    }

    /**
     * get list of users who have not submitted
     * @param int $ttid turnitintool id
     * @param int $partid part
     * @param array $users list of user objects
     * @return array list of user objects not submitted
     */
    public static function get_turnitintool_notsubmitted($ttid, $partid, $users) {
        global $DB;

        $notsubusers = array();
        foreach ($users as $user) {
            if (!$DB->get_record('turnitintool_submissions',
                    array('userid' => $user->id, 'turnitintoolid' => $ttid, 'submission_part' => $partid))) {
                $notsubusers[$user->id] = $user;
            }
        }

        return $notsubusers;
    }

    public static function export($users, $reveal, $filename, $activityname, $partname) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/excellib.class.php');

        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        $workbook->send($filename);
        // Adding the worksheet.
        $myxls = $workbook->add_worksheet(get_string('workbook', 'report_anonymous'));

        // Titles.
        $myxls->write_string(0, 0, get_string('turnitinname', 'report_anonymous'));
        $myxls->write_string(0, 1, $activityname);
        $myxls->write_string(1, 0, get_string('partname', 'report_anonymous'));
        $myxls->write_string(1, 1, $partname);

        // Headers.
        $myxls->write_string(3, 0, '#');
        $myxls->write_string(3, 1, get_string('idnumber'));
        $myxls->write_string(3, 2, get_string('email'));
        if ($reveal) {
            $myxls->write_string(3, 4, get_string('username'));
            $myxls->write_string(3, 5, get_string('fullname'));
        }

        // Add some data.
        $row = 4;
        foreach ($users as $user) {
            $myxls->write_number($row, 0, $row);
            if ($user->idnumber) {
                $myxls->write_string($row, 1, $user->idnumber);
            } else {
                $myxls->write_string($row, 1, '-');
            }
            $myxls->write_string($row, 2, $user->email);
            if ($reveal) {
                $myxls->write_string($row, 3, $user->username);
                $myxls->write_string($row, 4, fullname($user));
            }
            $row++;
        }
        $workbook->close();
    }

}
