<?php
// This file is part of the custom Moodle elegance theme
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
 * Renderers to align Moodle's HTML with that expected by elegance
 *
 * @package    theme_elegance
 * @copyright  2014 Julian Ridden http://moodleman.net
 * @authors    Julian Ridden -  Bootstrap 3 work by Bas Brands, David Scotson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . "/admin/renderer.php");

class theme_elegance_core_admin_renderer extends core_admin_renderer {

    protected function maturity_info($maturity) {
        if ($maturity == MATURITY_STABLE) {
            return ''; // No worries.
        }

        if ($maturity == MATURITY_ALPHA) {
            $notify_level = 'notifyproblem';
        } else {
            $notify_level = 'notifywarning';
        }

        $maturitylevel = get_string('maturity' . $maturity, 'admin');
        $warningtext = get_string('maturitycoreinfo', 'admin', $maturitylevel);
        $doc_link = $this->doc_link('admin/versions', get_string('morehelp'));

        return $this->notification($warningtext . ' ' . $doc_link, $notify_level);
    }

    protected function maturity_warning($maturity) {
        if ($maturity == MATURITY_STABLE) {
            return ''; // No worries.
        }

        $maturitylevel = get_string('maturity' . $maturity, 'admin');
        $maturity_warning = get_string('maturitycorewarning', 'admin', $maturitylevel);
        $maturity_warning .= $this->doc_link('admin/versions', get_string('morehelp'));

        return $this->notification($maturity_warning, 'notifyproblem');
    }

    /**
     * Output a warning message, of the type that appears on the admin notifications page.
     * @param string $message the message to display.
     * @param string $type type class
     * @return string HTML to output.
     */
    protected function warning($message, $type = 'warning') {
        if ($type == 'warning') {
            return $this->notification($message, 'notifywarning');
        } else if ($type == 'error') {
            return $this->notification($message, 'notifyproblem');
        }
    }

    protected function test_site_warning($testsite) {
        if (!$testsite) {
            return '';
        }
        $warningtext = get_string('testsiteupgradewarning', 'admin', $testsite);
        return $this->notification($warningtext, 'notifyproblem');
    }

    protected function release_notes_link() {
        $releasenoteslink = get_string('releasenoteslink', 'admin', 'http://docs.moodle.org/dev/Releases');
        return $this->notification($releasenoteslink, 'notifymessage');
    }
}
