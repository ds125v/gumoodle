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
 * The library file for the memcache cache store.
 *
 * This file is part of the memcache cache store, it contains the API for interacting with an instance of the store.
 *
 * @package    cachestore_memcache
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cache/forms.php');
require_once($CFG->dirroot.'/cache/stores/memcached/lib.php');

/**
 * Form for adding a memcache instance.
 *
 * @copyright  2012 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_memcache_addinstance_form extends cachestore_addinstance_form {

    /**
     * Add the desired form elements.
     */
    protected function configuration_definition() {
        $form = $this->_form;
        $form->addElement('textarea', 'servers', get_string('servers', 'cachestore_memcache'), array('cols' => 75, 'rows' => 5));
        $form->addHelpButton('servers', 'servers', 'cachestore_memcache');
        $form->addRule('servers', get_string('required'), 'required');
        $form->setType('servers', PARAM_RAW);

        $form->addElement('text', 'prefix', get_string('prefix', 'cachestore_memcache'),
                array('maxlength' => 5, 'size' => 5));
        $form->addHelpButton('prefix', 'prefix', 'cachestore_memcache');
        $form->setType('prefix', PARAM_ALPHAEXT);
        $form->setDefault('prefix', 'mdl_');
    }
}