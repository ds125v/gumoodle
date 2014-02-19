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
 * This file describes jQuery plugins available in the moodle
 * core component. These can be included in page using:
 *   $PAGE->requires->jquery();
 *   $PAGE->requires->jquery_plugin('migrate', 'core');
 *   $PAGE->requires->jquery_plugin('ui', 'core');
 *   $PAGE->requires->jquery_plugin('ui-css', 'core');
 *
 * Please note that other moodle plugins can not use the sample
 * jquery plugin names, only one is loaded if collision detected.
 *
 * Any Moodle plugin may add jquery/plugins.php and include extra
 * jQuery plugins.
 *
 * Themes or other plugin may blacklist any jquery plugin,
 * for example to override default jQueryUI theme.
 *
 * @package    core
 * @copyright  2013 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$plugins = array(
    'bootstrap' => array('files' => array('bootstrap.js')),
    'fitvids'	=> array('files' => array('fitvids-1.0.3.js')),
    'modernizr'     => array('files' => array('modernizr_2.6.2.js')),
    'unslider'     => array('files' => array('unslider_1.0.js')),
    'eventswipe'     => array('files' => array('eventswipe_0.5.js')),
    'nprogress'     => array('files' => array('nprogress.js')),
    'backstretch'     => array('files' => array('backstretch_2.0.4.js')),
    'alert'     => array('files' => array('bootstrap_plugins/alert_3.0.3.js')),
    'carousel'     => array('files' => array('bootstrap_plugins/carousel_3.0.3.js')),
    'collapse'     => array('files' => array('bootstrap_plugins/collapse_3.0.3.js')),
    'modal'     => array('files' => array('bootstrap_plugins/modal_3.0.3.js')),
    'scrollspy'     => array('files' => array('bootstrap_plugins/scrollspy_3.0.3.js')),
    'tab'     => array('files' => array('bootstrap_plugins/tab_3.0.3.js')),
    'tooltip'     => array('files' => array('bootstrap_plugins/tooltip_3.0.3.js')),
    'transition'     => array('files' => array('bootstrap_plugins/transition_3.0.3.js'))
);