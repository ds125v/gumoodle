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


require_once($CFG->dirroot . '/question/type/varnumericset/calculatorbase.php');

/**
 * Class for evaluating variants for varnumericset question type.
 *
 * @package    qtype
 * @subpackage varnumericset
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_varnumeric_calculator extends qtype_varnumeric_calculator_base {

    public function get_num_variants_in_form() {
        return 1;
    }

    protected function get_defined_variant($varno, $variantno) {
        //whatever the variant no we always use the first
        //defined variant.
        $variantno = 0;
        return parent::get_defined_variant($varno, $variantno);
    }
}
