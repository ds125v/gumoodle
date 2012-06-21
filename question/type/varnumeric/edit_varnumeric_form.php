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
 * Defines the editing form for the variable numeric question type.
 *
 * @package    qtype
 * @subpackage varnumeric
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/varnumericset/edit_varnumericset_form_base.php');

/**
 * variable numeric question editing form definition.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_edit_form extends qtype_varnumeric_edit_form_base {

    public function qtype() {
        return 'varnumeric';
    }
    protected function add_value_form_fields($mform, $repeated, $repeatedoptions) {
        $repeated[] = $mform->createElement('text', "variant0",
                get_string('value', 'qtype_varnumeric'), array('size' => 40));
        $repeatedoptions["variant0"]['disabledif'] = array('vartype', 'eq', 0);
        $repeatedoptions["variant0"]['helpbutton'] = array('value', 'qtype_varnumeric');
        $mform->setType("variant0", PARAM_RAW_TRIMMED);
        return array($repeated, $repeatedoptions);
    }

    public function validation($data, $files) {
        $data['noofvariants'] = 1;
        return parent::validation($data, $files);
    }
}
