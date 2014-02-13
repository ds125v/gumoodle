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

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Base event class.
 *
 * @package    core
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * All other event classes must extend this class.
 *
 * @package    core
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read string $eventname Name of the event (=== class name with leading \)
 * @property-read string $component Full frankenstyle component name
 * @property-read string $action what happened
 * @property-read string $target what/who was target of the action
 * @property-read string $objecttable name of database table where is object record stored
 * @property-read int $objectid optional id of the object
 * @property-read string $crud letter indicating event type
 * @property-read int $level log level (number between 1 and 100)
 * @property-read int $contextid
 * @property-read int $contextlevel
 * @property-read int $contextinstanceid
 * @property-read int $userid who did this?
 * @property-read int $courseid
 * @property-read int $relateduserid
 * @property-read mixed $other array or scalar, can not contain objects
 * @property-read int $timecreated
 */
abstract class base implements \IteratorAggregate {

    /**
     * Other level.
     */
    const LEVEL_OTHER = 0;

    /**
     * Teaching level.
     *
     * Any event that is performed by someone (typically a teacher) and has a teaching value,
     * anything that is affecting the learning experience/environment of the students.
     */
    const LEVEL_TEACHING = 1;

    /**
     * Participating level.
     *
     * Any event that is performed by a user, and is related (or could be related) to his learning experience.
     */
    const LEVEL_PARTICIPATING = 2;

    /** @var array event data */
    protected $data;

    /** @var array the format is standardised by logging API */
    protected $logextra;

    /** @var \context of this event */
    protected $context;

    /**
     * @var bool indicates if event was already triggered,
     *           this prevents second attempt to trigger event.
     */
    private $triggered;

    /**
     * @var bool indicates if event was already dispatched,
     *           this prevents direct calling of manager::dispatch($event).
     */
    private $dispatched;

    /**
     * @var bool indicates if event was restored from storage,
     *           this prevents triggering of restored events.
     */
    private $restored;

    /** @var array list of event properties */
    private static $fields = array(
        'eventname', 'component', 'action', 'target', 'objecttable', 'objectid', 'crud', 'level', 'contextid',
        'contextlevel', 'contextinstanceid', 'userid', 'courseid', 'relateduserid', 'other',
        'timecreated');

    /** @var array simple record cache */
    private $recordsnapshots = array();

    /**
     * Private constructor, use create() or restore() methods instead.
     */
    private final function __construct() {
        $this->data = array_fill_keys(self::$fields, null);
    }

    /**
     * Create new event.
     *
     * The optional data keys as:
     * 1/ objectid - the id of the object specified in class name
     * 2/ context - the context of this event
     * 3/ other - the other data describing the event, can not contain objects
     * 4/ relateduserid - the id of user which is somehow related to this event
     *
     * @param array $data
     * @return \core\event\base returns instance of new event
     *
     * @throws \coding_exception
     */
    public static final function create(array $data = null) {
        global $PAGE, $USER, $CFG;

        $data = (array)$data;

        /** @var \core\event\base $event */
        $event = new static();
        $event->triggered = false;
        $event->restored = false;
        $event->dispatched = false;

        // Set static event data specific for child class.
        $event->init();

        // Set automatic data.
        $event->data['timecreated'] = time();

        $classname = get_class($event);
        $parts = explode('\\', $classname);
        if (count($parts) !== 3 or $parts[1] !== 'event') {
            throw new \coding_exception("Invalid event class name '$classname', it must be defined in component\\event\\ namespace");
        }
        $event->data['eventname'] = '\\'.$classname;
        $event->data['component'] = $parts[0];

        $pos = strrpos($parts[2], '_');
        if ($pos === false) {
            throw new \coding_exception("Invalid event class name '$classname', there must be at least one underscore separating object and action words");
        }
        $event->data['target'] = substr($parts[2], 0, $pos);
        $event->data['action'] = substr($parts[2], $pos+1);

        // Set optional data or use defaults.
        $event->data['objectid'] = isset($data['objectid']) ? $data['objectid'] : null;
        $event->data['courseid'] = isset($data['courseid']) ? $data['courseid'] : null;
        $event->data['userid'] = isset($data['userid']) ? $data['userid'] : $USER->id;
        $event->data['other'] = isset($data['other']) ? $data['other'] : null;
        $event->data['relateduserid'] = isset($data['relateduserid']) ? $data['relateduserid'] : null;

        if (isset($event->context)) {
            if (isset($data['context'])) {
                debugging('Context was already set in init() method, ignoring context parameter', DEBUG_DEVELOPER);
            }

        } else if (!empty($data['context'])) {
            $event->context = $data['context'];

        } else if (!empty($data['contextid'])) {
            $event->context = \context::instance_by_id($data['contextid'], MUST_EXIST);

        } else {
            throw new \coding_exception('context (or contextid) is a required event property, system context may be hardcoded in init() method.');
        }

        $event->data['contextid'] = $event->context->id;
        $event->data['contextlevel'] = $event->context->contextlevel;
        $event->data['contextinstanceid'] = $event->context->instanceid;

        if (!isset($event->data['courseid'])) {
            if ($coursecontext = $event->context->get_course_context(false)) {
                $event->data['courseid'] = $coursecontext->instanceid;
            } else {
                $event->data['courseid'] = 0;
            }
        }

        if (!array_key_exists('relateduserid', $data) and $event->context->contextlevel == CONTEXT_USER) {
            $event->data['relateduserid'] = $event->context->instanceid;
        }

        // Warn developers if they do something wrong.
        if ($CFG->debugdeveloper) {
            static $automatickeys = array('eventname', 'component', 'action', 'target', 'contextlevel', 'contextinstanceid', 'timecreated');
            static $initkeys = array('crud', 'level', 'objecttable');

            foreach ($data as $key => $ignored) {
                if ($key === 'context') {
                    continue;

                } else if (in_array($key, $automatickeys)) {
                    debugging("Data key '$key' is not allowed in \\core\\event\\base::create() method, it is set automatically", DEBUG_DEVELOPER);

                } else if (in_array($key, $initkeys)) {
                    debugging("Data key '$key' is not allowed in \\core\\event\\base::create() method, you need to set it in init() method", DEBUG_DEVELOPER);

                } else if (!in_array($key, self::$fields)) {
                    debugging("Data key '$key' does not exist in \\core\\event\\base");
                }
            }
        }

        // Let developers validate their custom data (such as $this->data['other'], contextlevel, etc.).
        $event->validate_data();

        return $event;
    }

    /**
     * Override in subclass.
     *
     * Set all required data properties:
     *  1/ crud - letter [crud]
     *  2/ level - using a constant self::LEVEL_*.
     *  3/ objecttable - name of database table if objectid specified
     *
     * Optionally it can set:
     * a/ fixed system context
     *
     * @return void
     */
    protected abstract function init();

    /**
     * Let developers validate their custom data (such as $this->data['other'], contextlevel, etc.).
     *
     * Throw \coding_exception or debugging() notice in case of any problems.
     */
    protected function validate_data() {
        // Override if you want to validate event properties when
        // creating new events.
    }

    /**
     * Returns localised general event name.
     *
     * Override in subclass, we can not make it static and abstract at the same time.
     *
     * @return string
     */
    public static function get_name() {
        // Override in subclass with real lang string.
        $parts = explode('\\', get_called_class());
        if (count($parts) !== 3) {
            return get_string('unknownevent', 'error');
        }
        return $parts[0].': '.str_replace('_', ' ', $parts[2]);
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return null;
    }

    /**
     * Define whether a user can view the event or not.
     *
     * @param int|\stdClass $user_or_id ID of the user.
     * @return bool True if the user can view the event, false otherwise.
     */
    public function can_view($user_or_id = null) {
        return is_siteadmin($user_or_id);
    }

    /**
     * Restore event from existing historic data.
     *
     * @param array $data
     * @param array $logextra the format is standardised by logging API
     * @return bool|\core\event\base
     */
    public static final function restore(array $data, array $logextra) {
        $classname = $data['eventname'];
        $component = $data['component'];
        $action = $data['action'];
        $target = $data['target'];

        // Security: make 100% sure this really is an event class.
        if ($classname !== "\\{$component}\\event\\{$target}_{$action}") {
            return false;
        }

        if (!class_exists($classname)) {
            return false;
        }
        $event = new $classname();
        if (!($event instanceof \core\event\base)) {
            return false;
        }

        $event->restored = true;
        $event->triggered = true;
        $event->dispatched = true;
        $event->logextra = $logextra;

        foreach (self::$fields as $key) {
            if (!array_key_exists($key, $data)) {
                debugging("Event restore data must contain key $key");
                $data[$key] = null;
            }
        }
        if (count($data) != count(self::$fields)) {
            foreach ($data as $key => $value) {
                if (!in_array($key, self::$fields)) {
                    debugging("Event restore data cannot contain key $key");
                    unset($data[$key]);
                }
            }
        }
        $event->data = $data;

        return $event;
    }

    /**
     * Returns event context.
     * @return \context
     */
    public function get_context() {
        if (isset($this->context)) {
            return $this->context;
        }
        $this->context = \context::instance_by_id($this->data['contextid'], false);
        return $this->context;
    }

    /**
     * Returns relevant URL, override in subclasses.
     * @return \moodle_url
     */
    public function get_url() {
        return null;
    }

    /**
     * Return standardised event data as array.
     *
     * @return array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Return auxiliary data that was stored in logs.
     *
     * TODO MDL-41331: Properly define this method once logging is finalised.
     *
     * @return array the format is standardised by logging API
     */
    public function get_logextra() {
        return $this->logextra;
    }

    /**
     * Does this event replace legacy event?
     *
     * Note: do not use directly!
     *
     * @return null|string legacy event name
     */
    public static function get_legacy_eventname() {
        return null;
    }

    /**
     * Legacy event data if get_legacy_eventname() is not empty.
     *
     * Note: do not use directly!
     *
     * @return mixed
     */
    protected function get_legacy_eventdata() {
        return null;
    }

    /**
     * Doest this event replace add_to_log() statement?
     *
     * Note: do not use directly!
     *
     * @return null|array of parameters to be passed to legacy add_to_log() function.
     */
    protected function get_legacy_logdata() {
        return null;
    }

    /**
     * Validate all properties right before triggering the event.
     *
     * This throws coding exceptions for fatal problems and debugging for minor problems.
     *
     * @throws \coding_exception
     */
    protected final function validate_before_trigger() {
        global $DB, $CFG;

        if (empty($this->data['crud'])) {
            throw new \coding_exception('crud must be specified in init() method of each method');
        }
        if (!isset($this->data['level'])) {
            throw new \coding_exception('level must be specified in init() method of each method');
        }
        if (!empty($this->data['objectid']) and empty($this->data['objecttable'])) {
            throw new \coding_exception('objecttable must be specified in init() method if objectid present');
        }

        if ($CFG->debugdeveloper) {
            // Ideally these should be coding exceptions, but we need to skip these for performance reasons
            // on production servers.

            if (!in_array($this->data['crud'], array('c', 'r', 'u', 'd'), true)) {
                debugging("Invalid event crud value specified.", DEBUG_DEVELOPER);
            }
            if (!in_array($this->data['level'], array(self::LEVEL_OTHER, self::LEVEL_TEACHING, self::LEVEL_PARTICIPATING))) {
                // Bitwise combination of levels is not allowed at this stage.
                debugging('Event property level must a constant value, see event_base::LEVEL_*', DEBUG_DEVELOPER);
            }
            if (self::$fields !== array_keys($this->data)) {
                debugging('Number of event data fields must not be changed in event classes', DEBUG_DEVELOPER);
            }
            $encoded = json_encode($this->data['other']);
            // The comparison here is not set to strict as whole float numbers will be converted to integers through JSON encoding /
            // decoding and send an unwanted debugging message.
            if ($encoded === false or $this->data['other'] != json_decode($encoded, true)) {
                debugging('other event data must be compatible with json encoding', DEBUG_DEVELOPER);
            }
            if ($this->data['userid'] and !is_number($this->data['userid'])) {
                debugging('Event property userid must be a number', DEBUG_DEVELOPER);
            }
            if ($this->data['courseid'] and !is_number($this->data['courseid'])) {
                debugging('Event property courseid must be a number', DEBUG_DEVELOPER);
            }
            if ($this->data['objectid'] and !is_number($this->data['objectid'])) {
                debugging('Event property objectid must be a number', DEBUG_DEVELOPER);
            }
            if ($this->data['relateduserid'] and !is_number($this->data['relateduserid'])) {
                debugging('Event property relateduserid must be a number', DEBUG_DEVELOPER);
            }
            if ($this->data['objecttable']) {
                if (!$DB->get_manager()->table_exists($this->data['objecttable'])) {
                    debugging('Unknown table specified in objecttable field', DEBUG_DEVELOPER);
                }
                if (!isset($this->data['objectid'])) {
                    debugging('Event property objectid must be set when objecttable is defined', DEBUG_DEVELOPER);
                }
            }
        }
    }

    /**
     * Trigger event.
     */
    public final function trigger() {
        global $CFG;

        if ($this->restored) {
            throw new \coding_exception('Can not trigger restored event');
        }
        if ($this->triggered or $this->dispatched) {
            throw new \coding_exception('Can not trigger event twice');
        }

        $this->validate_before_trigger();

        $this->triggered = true;

        if (isset($CFG->loglifetime) and $CFG->loglifetime != -1) {
            if ($data = $this->get_legacy_logdata()) {
                call_user_func_array('add_to_log', $data);
            }
        }

        if (PHPUNIT_TEST and \phpunit_util::is_redirecting_events()) {
            $this->dispatched = true;
            \phpunit_util::event_triggered($this);
            return;
        }

        \core\event\manager::dispatch($this);

        $this->dispatched = true;

        if ($legacyeventname = static::get_legacy_eventname()) {
            events_trigger_legacy($legacyeventname, $this->get_legacy_eventdata());
        }
    }

    /**
     * Was this event already triggered?
     *
     * @return bool
     */
    public final function is_triggered() {
        return $this->triggered;
    }

    /**
     * Used from event manager to prevent direct access.
     *
     * @return bool
     */
    public final function is_dispatched() {
        return $this->dispatched;
    }

    /**
     * Was this event restored?
     *
     * @return bool
     */
    public final function is_restored() {
        return $this->restored;
    }

    /**
     * Add cached data that will be most probably used in event observers.
     *
     * This is used to improve performance, but it is required for data
     * that was just deleted.
     *
     * @param string $tablename
     * @param \stdClass $record
     *
     * @throws \coding_exception if used after ::trigger()
     */
    public final function add_record_snapshot($tablename, $record) {
        global $DB, $CFG;

        if ($this->triggered) {
            throw new \coding_exception('It is not possible to add snapshots after triggering of events');
        }

        // NOTE: this might use some kind of MUC cache,
        //       hopefully we will not run out of memory here...
        if ($CFG->debugdeveloper) {
            if (!$DB->get_manager()->table_exists($tablename)) {
                debugging("Invalid table name '$tablename' specified, database table does not exist.", DEBUG_DEVELOPER);
            }
        }
        $this->recordsnapshots[$tablename][$record->id] = $record;
    }

    /**
     * Returns cached record or fetches data from database if not cached.
     *
     * @param string $tablename
     * @param int $id
     * @return \stdClass
     *
     * @throws \coding_exception if used after ::restore()
     */
    public final function get_record_snapshot($tablename, $id) {
        global $DB;

        if ($this->restored) {
            throw new \coding_exception('It is not possible to get snapshots from restored events');
        }

        if (isset($this->recordsnapshots[$tablename][$id])) {
            return $this->recordsnapshots[$tablename][$id];
        }

        $record = $DB->get_record($tablename, array('id'=>$id));
        $this->recordsnapshots[$tablename][$id] = $record;

        return $record;
    }

    /**
     * Magic getter for read only access.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        debugging("Accessing non-existent event property '$name'");
    }

    /**
     * Magic setter.
     *
     * Note: we must not allow modification of data from outside,
     *       after trigger() the data MUST NOT CHANGE!!!
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws \coding_exception
     */
    public function __set($name, $value) {
        throw new \coding_exception('Event properties must not be modified.');
    }

    /**
     * Is data property set?
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->data[$name]);
    }

    /**
     * Create an iterator because magic vars can't be seen by 'foreach'.
     *
     * @return \ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->data);
    }
}
