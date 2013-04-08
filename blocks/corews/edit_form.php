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
 * Form for editing corews block instances.
 *
 * @package   corews
 * @copyright 2013 Howard Miller
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_corews_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_coursecode', get_string('coursecode', 'block_corews'), array('size'=>10));
        $mform->setType('config_coursecode', PARAM_ALPHANUM);

        $mform->addElement('text', 'config_courseid', get_string('courseid', 'block_corews'), array('size'=>10));
        $mform->setType('config_courseid', PARAM_ALPHANUM);
    }
}
