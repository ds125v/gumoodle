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
 * This file contains classes used to manage the repository plugins in Moodle
 * and was introduced as part of the changes occuring in Moodle 2.0
 *
 * @since 2.0
 * @package   repository
 * @copyright 2009 Dongsheng Cai {@link http://dongsheng.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');

define('FILE_EXTERNAL',  1);
define('FILE_INTERNAL',  2);
define('FILE_REFERENCE', 4);
define('RENAME_SUFFIX', '_2');

/**
 * This class is used to manage repository plugins
 *
 * A repository_type is a repository plug-in. It can be Box.net, Flick-r, ...
 * A repository type can be edited, sorted and hidden. It is mandatory for an
 * administrator to create a repository type in order to be able to create
 * some instances of this type.
 * Coding note:
 * - a repository_type object is mapped to the "repository" database table
 * - "typename" attibut maps the "type" database field. It is unique.
 * - general "options" for a repository type are saved in the config_plugin table
 * - when you delete a repository, all instances are deleted, and general
 *   options are also deleted from database
 * - When you create a type for a plugin that can't have multiple instances, a
 *   instance is automatically created.
 *
 * @package   repository
 * @copyright 2009 Jerome Mouneyrac
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_type {


    /**
     * Type name (no whitespace) - A type name is unique
     * Note: for a user-friendly type name see get_readablename()
     * @var String
     */
    private $_typename;


    /**
     * Options of this type
     * They are general options that any instance of this type would share
     * e.g. API key
     * These options are saved in config_plugin table
     * @var array
     */
    private $_options;


    /**
     * Is the repository type visible or hidden
     * If false (hidden): no instances can be created, edited, deleted, showned , used...
     * @var boolean
     */
    private $_visible;


    /**
     * 0 => not ordered, 1 => first position, 2 => second position...
     * A not order type would appear in first position (should never happened)
     * @var integer
     */
    private $_sortorder;

    /**
     * Return if the instance is visible in a context
     *
     * @todo check if the context visibility has been overwritten by the plugin creator
     *       (need to create special functions to be overvwritten in repository class)
     * @param stdClass $context context
     * @return bool
     */
    public function get_contextvisibility($context) {
        global $USER;

        if ($context->contextlevel == CONTEXT_COURSE) {
            return $this->_options['enablecourseinstances'];
        }

        if ($context->contextlevel == CONTEXT_USER) {
            return $this->_options['enableuserinstances'];
        }

        //the context is SITE
        return true;
    }



    /**
     * repository_type constructor
     *
     * @param int $typename
     * @param array $typeoptions
     * @param bool $visible
     * @param int $sortorder (don't really need set, it will be during create() call)
     */
    public function __construct($typename = '', $typeoptions = array(), $visible = true, $sortorder = 0) {
        global $CFG;

        //set type attributs
        $this->_typename = $typename;
        $this->_visible = $visible;
        $this->_sortorder = $sortorder;

        //set options attribut
        $this->_options = array();
        $options = repository::static_function($typename, 'get_type_option_names');
        //check that the type can be setup
        if (!empty($options)) {
            //set the type options
            foreach ($options as $config) {
                if (array_key_exists($config, $typeoptions)) {
                    $this->_options[$config] = $typeoptions[$config];
                }
            }
        }

        //retrieve visibility from option
        if (array_key_exists('enablecourseinstances',$typeoptions)) {
            $this->_options['enablecourseinstances'] = $typeoptions['enablecourseinstances'];
        } else {
             $this->_options['enablecourseinstances'] = 0;
        }

        if (array_key_exists('enableuserinstances',$typeoptions)) {
            $this->_options['enableuserinstances'] = $typeoptions['enableuserinstances'];
        } else {
             $this->_options['enableuserinstances'] = 0;
        }

    }

    /**
     * Get the type name (no whitespace)
     * For a human readable name, use get_readablename()
     *
     * @return string the type name
     */
    public function get_typename() {
        return $this->_typename;
    }

    /**
     * Return a human readable and user-friendly type name
     *
     * @return string user-friendly type name
     */
    public function get_readablename() {
        return get_string('pluginname','repository_'.$this->_typename);
    }

    /**
     * Return general options
     *
     * @return array the general options
     */
    public function get_options() {
        return $this->_options;
    }

    /**
     * Return visibility
     *
     * @return bool
     */
    public function get_visible() {
        return $this->_visible;
    }

    /**
     * Return order / position of display in the file picker
     *
     * @return int
     */
    public function get_sortorder() {
        return $this->_sortorder;
    }

    /**
     * Create a repository type (the type name must not already exist)
     * @param bool $silent throw exception?
     * @return mixed return int if create successfully, return false if
     */
    public function create($silent = false) {
        global $DB;

        //check that $type has been set
        $timmedtype = trim($this->_typename);
        if (empty($timmedtype)) {
            throw new repository_exception('emptytype', 'repository');
        }

        //set sortorder as the last position in the list
        if (!isset($this->_sortorder) || $this->_sortorder == 0 ) {
            $sql = "SELECT MAX(sortorder) FROM {repository}";
            $this->_sortorder = 1 + $DB->get_field_sql($sql);
        }

        //only create a new type if it doesn't already exist
        $existingtype = $DB->get_record('repository', array('type'=>$this->_typename));
        if (!$existingtype) {
            //create the type
            $newtype = new stdClass();
            $newtype->type = $this->_typename;
            $newtype->visible = $this->_visible;
            $newtype->sortorder = $this->_sortorder;
            $plugin_id = $DB->insert_record('repository', $newtype);
            //save the options in DB
            $this->update_options();

            $instanceoptionnames = repository::static_function($this->_typename, 'get_instance_option_names');

            //if the plugin type has no multiple instance (e.g. has no instance option name) so it wont
            //be possible for the administrator to create a instance
            //in this case we need to create an instance
            if (empty($instanceoptionnames)) {
                $instanceoptions = array();
                if (empty($this->_options['pluginname'])) {
                    // when moodle trying to install some repo plugin automatically
                    // this option will be empty, get it from language string when display
                    $instanceoptions['name'] = '';
                } else {
                    // when admin trying to add a plugin manually, he will type a name
                    // for it
                    $instanceoptions['name'] = $this->_options['pluginname'];
                }
                repository::static_function($this->_typename, 'create', $this->_typename, 0, get_system_context(), $instanceoptions);
            }
            //run plugin_init function
            if (!repository::static_function($this->_typename, 'plugin_init')) {
                if (!$silent) {
                    throw new repository_exception('cannotinitplugin', 'repository');
                }
            }

            if(!empty($plugin_id)) {
                // return plugin_id if create successfully
                return $plugin_id;
            } else {
                return false;
            }

        } else {
            if (!$silent) {
                throw new repository_exception('existingrepository', 'repository');
            }
            // If plugin existed, return false, tell caller no new plugins were created.
            return false;
        }
    }


    /**
     * Update plugin options into the config_plugin table
     *
     * @param array $options
     * @return bool
     */
    public function update_options($options = null) {
        global $DB;
        $classname = 'repository_' . $this->_typename;
        $instanceoptions = repository::static_function($this->_typename, 'get_instance_option_names');
        if (empty($instanceoptions)) {
            // update repository instance name if this plugin type doesn't have muliti instances
            $params = array();
            $params['type'] = $this->_typename;
            $instances = repository::get_instances($params);
            $instance = array_pop($instances);
            if ($instance) {
                $DB->set_field('repository_instances', 'name', $options['pluginname'], array('id'=>$instance->id));
            }
            unset($options['pluginname']);
        }

        if (!empty($options)) {
            $this->_options = $options;
        }

        foreach ($this->_options as $name => $value) {
            set_config($name, $value, $this->_typename);
        }

        return true;
    }

    /**
     * Update visible database field with the value given as parameter
     * or with the visible value of this object
     * This function is private.
     * For public access, have a look to switch_and_update_visibility()
     *
     * @param bool $visible
     * @return bool
     */
    private function update_visible($visible = null) {
        global $DB;

        if (!empty($visible)) {
            $this->_visible = $visible;
        }
        else if (!isset($this->_visible)) {
            throw new repository_exception('updateemptyvisible', 'repository');
        }

        return $DB->set_field('repository', 'visible', $this->_visible, array('type'=>$this->_typename));
    }

    /**
     * Update database sortorder field with the value given as parameter
     * or with the sortorder value of this object
     * This function is private.
     * For public access, have a look to move_order()
     *
     * @param int $sortorder
     * @return bool
     */
    private function update_sortorder($sortorder = null) {
        global $DB;

        if (!empty($sortorder) && $sortorder!=0) {
            $this->_sortorder = $sortorder;
        }
        //if sortorder is not set, we set it as the ;ast position in the list
        else if (!isset($this->_sortorder) || $this->_sortorder == 0 ) {
            $sql = "SELECT MAX(sortorder) FROM {repository}";
            $this->_sortorder = 1 + $DB->get_field_sql($sql);
        }

        return $DB->set_field('repository', 'sortorder', $this->_sortorder, array('type'=>$this->_typename));
    }

    /**
     * Change order of the type with its adjacent upper or downer type
     * (database fields are updated)
     * Algorithm details:
     * 1. retrieve all types in an array. This array is sorted by sortorder,
     * and the array keys start from 0 to X (incremented by 1)
     * 2. switch sortorder values of this type and its adjacent type
     *
     * @param string $move "up" or "down"
     */
    public function move_order($move) {
        global $DB;

        $types = repository::get_types();    // retrieve all types

        // retrieve this type into the returned array
        $i = 0;
        while (!isset($indice) && $i<count($types)) {
            if ($types[$i]->get_typename() == $this->_typename) {
                $indice = $i;
            }
            $i++;
        }

        // retrieve adjacent indice
        switch ($move) {
            case "up":
                $adjacentindice = $indice - 1;
            break;
            case "down":
                $adjacentindice = $indice + 1;
            break;
            default:
            throw new repository_exception('movenotdefined', 'repository');
        }

        //switch sortorder of this type and the adjacent type
        //TODO: we could reset sortorder for all types. This is not as good in performance term, but
        //that prevent from wrong behaviour on a screwed database. As performance are not important in this particular case
        //it worth to change the algo.
        if ($adjacentindice>=0 && !empty($types[$adjacentindice])) {
            $DB->set_field('repository', 'sortorder', $this->_sortorder, array('type'=>$types[$adjacentindice]->get_typename()));
            $this->update_sortorder($types[$adjacentindice]->get_sortorder());
        }
    }

    /**
     * 1. Change visibility to the value chosen
     * 2. Update the type
     *
     * @param bool $visible
     * @return bool
     */
    public function update_visibility($visible = null) {
        if (is_bool($visible)) {
            $this->_visible = $visible;
        } else {
            $this->_visible = !$this->_visible;
        }
        return $this->update_visible();
    }


    /**
     * Delete a repository_type (general options are removed from config_plugin
     * table, and all instances are deleted)
     *
     * @param bool $downloadcontents download external contents if exist
     * @return bool
     */
    public function delete($downloadcontents = false) {
        global $DB;

        //delete all instances of this type
        $params = array();
        $params['context'] = array();
        $params['onlyvisible'] = false;
        $params['type'] = $this->_typename;
        $instances = repository::get_instances($params);
        foreach ($instances as $instance) {
            $instance->delete($downloadcontents);
        }

        //delete all general options
        foreach ($this->_options as $name => $value) {
            set_config($name, null, $this->_typename);
        }

        try {
            $DB->delete_records('repository', array('type' => $this->_typename));
        } catch (dml_exception $ex) {
            return false;
        }
        return true;
    }
}

/**
 * This is the base class of the repository class.
 *
 * To create repository plugin, see: {@link http://docs.moodle.org/dev/Repository_plugins}
 * See an example: {@link repository_boxnet}
 *
 * @package   repository
 * @category  repository
 * @copyright 2009 Dongsheng Cai {@link http://dongsheng.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class repository {
    // $disabled can be set to true to disable a plugin by force
    // example: self::$disabled = true
    /** @var bool force disable repository instance */
    public $disabled = false;
    /** @var int repository instance id */
    public $id;
    /** @var stdClass current context */
    public $context;
    /** @var array repository options */
    public $options;
    /** @var bool Whether or not the repository instance is editable */
    public $readonly;
    /** @var int return types */
    public $returntypes;
    /** @var stdClass repository instance database record */
    public $instance;
    /**
     * Constructor
     *
     * @param int $repositoryid repository instance id
     * @param int|stdClass $context a context id or context object
     * @param array $options repository options
     * @param int $readonly indicate this repo is readonly or not
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array(), $readonly = 0) {
        global $DB;
        $this->id = $repositoryid;
        if (is_object($context)) {
            $this->context = $context;
        } else {
            $this->context = get_context_instance_by_id($context);
        }
        $this->instance = $DB->get_record('repository_instances', array('id'=>$this->id));
        $this->readonly = $readonly;
        $this->options = array();

        if (is_array($options)) {
            // The get_option() method will get stored options in database.
            $options = array_merge($this->get_option(), $options);
        } else {
            $options = $this->get_option();
        }
        foreach ($options as $n => $v) {
            $this->options[$n] = $v;
        }
        $this->name = $this->get_name();
        $this->returntypes = $this->supported_returntypes();
        $this->super_called = true;
    }

    /**
     * Get repository instance using repository id
     *
     * @param int $repositoryid repository ID
     * @param stdClass|int $context context instance or context ID
     * @param array $options additional repository options
     * @return repository
     */
    public static function get_repository_by_id($repositoryid, $context, $options = array()) {
        global $CFG, $DB;

        $sql = 'SELECT i.name, i.typeid, r.type FROM {repository} r, {repository_instances} i WHERE i.id=? AND i.typeid=r.id';

        if (!$record = $DB->get_record_sql($sql, array($repositoryid))) {
            throw new repository_exception('invalidrepositoryid', 'repository');
        } else {
            $type = $record->type;
            if (file_exists($CFG->dirroot . "/repository/$type/lib.php")) {
                require_once($CFG->dirroot . "/repository/$type/lib.php");
                $classname = 'repository_' . $type;
                $contextid = $context;
                if (is_object($context)) {
                    $contextid = $context->id;
                }
                $options['type'] = $type;
                $options['typeid'] = $record->typeid;
                if (empty($options['name'])) {
                    $options['name'] = $record->name;
                }
                $repository = new $classname($repositoryid, $contextid, $options);
                return $repository;
            } else {
                throw new repository_exception('invalidplugin', 'repository');
            }
        }
    }

    /**
     * Get a repository type object by a given type name.
     *
     * @static
     * @param string $typename the repository type name
     * @return repository_type|bool
     */
    public static function get_type_by_typename($typename) {
        global $DB;

        if (!$record = $DB->get_record('repository',array('type' => $typename))) {
            return false;
        }

        return new repository_type($typename, (array)get_config($typename), $record->visible, $record->sortorder);
    }

    /**
     * Get the repository type by a given repository type id.
     *
     * @static
     * @param int $id the type id
     * @return object
     */
    public static function get_type_by_id($id) {
        global $DB;

        if (!$record = $DB->get_record('repository',array('id' => $id))) {
            return false;
        }

        return new repository_type($record->type, (array)get_config($record->type), $record->visible, $record->sortorder);
    }

    /**
     * Return all repository types ordered by sortorder field
     * first repository type in returnedarray[0], second repository type in returnedarray[1], ...
     *
     * @static
     * @param bool $visible can return types by visiblity, return all types if null
     * @return array Repository types
     */
    public static function get_types($visible=null) {
        global $DB, $CFG;

        $types = array();
        $params = null;
        if (!empty($visible)) {
            $params = array('visible' => $visible);
        }
        if ($records = $DB->get_records('repository',$params,'sortorder')) {
            foreach($records as $type) {
                if (file_exists($CFG->dirroot . '/repository/'. $type->type .'/lib.php')) {
                    $types[] = new repository_type($type->type, (array)get_config($type->type), $type->visible, $type->sortorder);
                }
            }
        }

        return $types;
    }

    /**
     * Checks if user has a capability to view the current repository in current context
     *
     * @return bool
     */
    public final function check_capability() {
        $capability = false;
        if (preg_match("/^repository_(.*)$/", get_class($this), $matches)) {
            $type = $matches[1];
            $capability = has_capability('repository/'.$type.':view', $this->context);
        }
        if (!$capability) {
            throw new repository_exception('nopermissiontoaccess', 'repository');
        }
    }

    /**
     * Check if file already exists in draft area
     *
     * @static
     * @param int $itemid
     * @param string $filepath
     * @param string $filename
     * @return bool
     */
    public static function draftfile_exists($itemid, $filepath, $filename) {
        global $USER;
        $fs = get_file_storage();
        $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
        if ($fs->get_file($usercontext->id, 'user', 'draft', $itemid, $filepath, $filename)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Parses the 'source' returned by moodle repositories and returns an instance of stored_file
     *
     * @param string $source
     * @return stored_file|null
     */
    public static function get_moodle_file($source) {
        $params = file_storage::unpack_reference($source, true);
        $fs = get_file_storage();
        return $fs->get_file($params['contextid'], $params['component'], $params['filearea'],
                    $params['itemid'], $params['filepath'], $params['filename']);
    }

    /**
     * Repository method to make sure that user can access particular file.
     *
     * This is checked when user tries to pick the file from repository to deal with
     * potential parameter substitutions is request
     *
     * @param string $source
     * @return bool whether the file is accessible by current user
     */
    public function file_is_accessible($source) {
        if ($this->has_moodle_files()) {
            try {
                $params = file_storage::unpack_reference($source, true);
            } catch (file_reference_exception $e) {
                return false;
            }
            $browser = get_file_browser();
            $context = context::instance_by_id($params['contextid']);
            $file_info = $browser->get_file_info($context, $params['component'], $params['filearea'],
                    $params['itemid'], $params['filepath'], $params['filename']);
            return !empty($file_info);
        }
        return true;
    }

    /**
     * This function is used to copy a moodle file to draft area.
     *
     * It DOES NOT check if the user is allowed to access this file because the actual file
     * can be located in the area where user does not have access to but there is an alias
     * to this file in the area where user CAN access it.
     * {@link file_is_accessible} should be called for alias location before calling this function.
     *
     * @param string $source The metainfo of file, it is base64 encoded php serialized data
     * @param stdClass|array $filerecord contains itemid, filepath, filename and optionally other
     *      attributes of the new file
     * @param int $maxbytes maximum allowed size of file, -1 if unlimited. If size of file exceeds
     *      the limit, the file_exception is thrown.
     * @return array The information about the created file
     */
    public function copy_to_area($source, $filerecord, $maxbytes = -1) {
        global $USER;
        $fs = get_file_storage();

        if ($this->has_moodle_files() == false) {
            throw new coding_exception('Only repository used to browse moodle files can use repository::copy_to_area()');
        }

        $user_context = context_user::instance($USER->id);

        $filerecord = (array)$filerecord;
        // make sure the new file will be created in user draft area
        $filerecord['component'] = 'user';
        $filerecord['filearea'] = 'draft';
        $filerecord['contextid'] = $user_context->id;
        $draftitemid = $filerecord['itemid'];
        $new_filepath = $filerecord['filepath'];
        $new_filename = $filerecord['filename'];

        // the file needs to copied to draft area
        $stored_file = self::get_moodle_file($source);
        if ($maxbytes != -1 && $stored_file->get_filesize() > $maxbytes) {
            throw new file_exception('maxbytes');
        }

        if (repository::draftfile_exists($draftitemid, $new_filepath, $new_filename)) {
            // create new file
            $unused_filename = repository::get_unused_filename($draftitemid, $new_filepath, $new_filename);
            $filerecord['filename'] = $unused_filename;
            $fs->create_file_from_storedfile($filerecord, $stored_file);
            $event = array();
            $event['event'] = 'fileexists';
            $event['newfile'] = new stdClass;
            $event['newfile']->filepath = $new_filepath;
            $event['newfile']->filename = $unused_filename;
            $event['newfile']->url = moodle_url::make_draftfile_url($draftitemid, $new_filepath, $unused_filename)->out();
            $event['existingfile'] = new stdClass;
            $event['existingfile']->filepath = $new_filepath;
            $event['existingfile']->filename = $new_filename;
            $event['existingfile']->url = moodle_url::make_draftfile_url($draftitemid, $new_filepath, $new_filename)->out();
            return $event;
        } else {
            $fs->create_file_from_storedfile($filerecord, $stored_file);
            $info = array();
            $info['itemid'] = $draftitemid;
            $info['file'] = $new_filename;
            $info['title'] = $new_filename;
            $info['contextid'] = $user_context->id;
            $info['url'] = moodle_url::make_draftfile_url($draftitemid, $new_filepath, $new_filename)->out();
            $info['filesize'] = $stored_file->get_filesize();
            return $info;
        }
    }

    /**
     * Get unused filename by appending suffix
     *
     * @static
     * @param int $itemid
     * @param string $filepath
     * @param string $filename
     * @return string
     */
    public static function get_unused_filename($itemid, $filepath, $filename) {
        global $USER;
        $fs = get_file_storage();
        while (repository::draftfile_exists($itemid, $filepath, $filename)) {
            $filename = repository::append_suffix($filename);
        }
        return $filename;
    }

    /**
     * Append a suffix to filename
     *
     * @static
     * @param string $filename
     * @return string
     */
    public static function append_suffix($filename) {
        $pathinfo = pathinfo($filename);
        if (empty($pathinfo['extension'])) {
            return $filename . RENAME_SUFFIX;
        } else {
            return $pathinfo['filename'] . RENAME_SUFFIX . '.' . $pathinfo['extension'];
        }
    }

    /**
     * Return all types that you a user can create/edit and which are also visible
     * Note: Mostly used in order to know if at least one editable type can be set
     *
     * @static
     * @param stdClass $context the context for which we want the editable types
     * @return array types
     */
    public static function get_editable_types($context = null) {

        if (empty($context)) {
            $context = get_system_context();
        }

        $types= repository::get_types(true);
        $editabletypes = array();
        foreach ($types as $type) {
            $instanceoptionnames = repository::static_function($type->get_typename(), 'get_instance_option_names');
            if (!empty($instanceoptionnames)) {
                if ($type->get_contextvisibility($context)) {
                    $editabletypes[]=$type;
                }
             }
        }
        return $editabletypes;
    }

    /**
     * Return repository instances
     *
     * @static
     * @param array $args Array containing the following keys:
     *           currentcontext
     *           context
     *           onlyvisible
     *           type
     *           accepted_types
     *           return_types
     *           userid
     *
     * @return array repository instances
     */
    public static function get_instances($args = array()) {
        global $DB, $CFG, $USER;

        if (isset($args['currentcontext'])) {
            $current_context = $args['currentcontext'];
        } else {
            $current_context = null;
        }

        if (!empty($args['context'])) {
            $contexts = $args['context'];
        } else {
            $contexts = array();
        }

        $onlyvisible = isset($args['onlyvisible']) ? $args['onlyvisible'] : true;
        $returntypes = isset($args['return_types']) ? $args['return_types'] : 3;
        $type        = isset($args['type']) ? $args['type'] : null;

        $params = array();
        $sql = "SELECT i.*, r.type AS repositorytype, r.sortorder, r.visible
                  FROM {repository} r, {repository_instances} i
                 WHERE i.typeid = r.id ";

        if (!empty($args['disable_types']) && is_array($args['disable_types'])) {
            list($types, $p) = $DB->get_in_or_equal($args['disable_types'], SQL_PARAMS_QM, 'param', false);
            $sql .= " AND r.type $types";
            $params = array_merge($params, $p);
        }

        if (!empty($args['userid']) && is_numeric($args['userid'])) {
            $sql .= " AND (i.userid = 0 or i.userid = ?)";
            $params[] = $args['userid'];
        }

        foreach ($contexts as $context) {
            if (empty($firstcontext)) {
                $firstcontext = true;
                $sql .= " AND ((i.contextid = ?)";
            } else {
                $sql .= " OR (i.contextid = ?)";
            }
            $params[] = $context->id;
        }

        if (!empty($firstcontext)) {
           $sql .=')';
        }

        if ($onlyvisible == true) {
            $sql .= " AND (r.visible = 1)";
        }

        if (isset($type)) {
            $sql .= " AND (r.type = ?)";
            $params[] = $type;
        }
        $sql .= " ORDER BY r.sortorder, i.name";

        if (!$records = $DB->get_records_sql($sql, $params)) {
            $records = array();
        }

        $repositories = array();
        if (isset($args['accepted_types'])) {
            $accepted_types = $args['accepted_types'];
            if (is_array($accepted_types) && in_array('*', $accepted_types)) {
                $accepted_types = '*';
            }
        } else {
            $accepted_types = '*';
        }
        // Sortorder should be unique, which is not true if we use $record->sortorder
        // and there are multiple instances of any repository type
        $sortorder = 1;
        foreach ($records as $record) {
            if (!file_exists($CFG->dirroot . '/repository/'. $record->repositorytype.'/lib.php')) {
                continue;
            }
            require_once($CFG->dirroot . '/repository/'. $record->repositorytype.'/lib.php');
            $options['visible'] = $record->visible;
            $options['type']    = $record->repositorytype;
            $options['typeid']  = $record->typeid;
            $options['sortorder'] = $sortorder++;
            // tell instance what file types will be accepted by file picker
            $classname = 'repository_' . $record->repositorytype;

            $repository = new $classname($record->id, $record->contextid, $options, $record->readonly);

            $is_supported = true;

            if (empty($repository->super_called)) {
                // to make sure the super construct is called
                debugging('parent::__construct must be called by '.$record->repositorytype.' plugin.');
            } else {
                // check mimetypes
                if ($accepted_types !== '*' and $repository->supported_filetypes() !== '*') {
                    $accepted_ext = file_get_typegroup('extension', $accepted_types);
                    $supported_ext = file_get_typegroup('extension', $repository->supported_filetypes());
                    $valid_ext = array_intersect($accepted_ext, $supported_ext);
                    $is_supported = !empty($valid_ext);
                }
                // check return values
                if ($returntypes !== 3 and $repository->supported_returntypes() !== 3) {
                    $type = $repository->supported_returntypes();
                    if ($type & $returntypes) {
                        //
                    } else {
                        $is_supported = false;
                    }
                }

                if (!$onlyvisible || ($repository->is_visible() && !$repository->disabled)) {
                    // check capability in current context
                    if (!empty($current_context)) {
                        $capability = has_capability('repository/'.$record->repositorytype.':view', $current_context);
                    } else {
                        $capability = has_capability('repository/'.$record->repositorytype.':view', get_system_context());
                    }
                    if ($record->repositorytype == 'coursefiles') {
                        // coursefiles plugin needs managefiles permission
                        if (!empty($current_context)) {
                            $capability = $capability && has_capability('moodle/course:managefiles', $current_context);
                        } else {
                            $capability = $capability && has_capability('moodle/course:managefiles', get_system_context());
                        }
                    }
                    if ($is_supported && $capability) {
                        $repositories[$repository->id] = $repository;
                    }
                }
            }
        }
        return $repositories;
    }

    /**
     * Get single repository instance
     *
     * @static
     * @param integer $id repository id
     * @return object repository instance
     */
    public static function get_instance($id) {
        global $DB, $CFG;
        $sql = "SELECT i.*, r.type AS repositorytype, r.visible
                  FROM {repository} r
                  JOIN {repository_instances} i ON i.typeid = r.id
                 WHERE i.id = ?";

        if (!$instance = $DB->get_record_sql($sql, array($id))) {
            return false;
        }
        require_once($CFG->dirroot . '/repository/'. $instance->repositorytype.'/lib.php');
        $classname = 'repository_' . $instance->repositorytype;
        $options['typeid'] = $instance->typeid;
        $options['type']   = $instance->repositorytype;
        $options['name']   = $instance->name;
        $obj = new $classname($instance->id, $instance->contextid, $options, $instance->readonly);
        if (empty($obj->super_called)) {
            debugging('parent::__construct must be called by '.$classname.' plugin.');
        }
        return $obj;
    }

    /**
     * Call a static function. Any additional arguments than plugin and function will be passed through.
     *
     * @static
     * @param string $plugin repository plugin name
     * @param string $function funciton name
     * @return mixed
     */
    public static function static_function($plugin, $function) {
        global $CFG;

        //check that the plugin exists
        $typedirectory = $CFG->dirroot . '/repository/'. $plugin . '/lib.php';
        if (!file_exists($typedirectory)) {
            //throw new repository_exception('invalidplugin', 'repository');
            return false;
        }

        $pname = null;
        if (is_object($plugin) || is_array($plugin)) {
            $plugin = (object)$plugin;
            $pname = $plugin->name;
        } else {
            $pname = $plugin;
        }

        $args = func_get_args();
        if (count($args) <= 2) {
            $args = array();
        } else {
            array_shift($args);
            array_shift($args);
        }

        require_once($typedirectory);
        return call_user_func_array(array('repository_' . $plugin, $function), $args);
    }

    /**
     * Scan file, throws exception in case of infected file.
     *
     * Please note that the scanning engine must be able to access the file,
     * permissions of the file are not modified here!
     *
     * @static
     * @param string $thefile
     * @param string $filename name of the file
     * @param bool $deleteinfected
     */
    public static function antivir_scan_file($thefile, $filename, $deleteinfected) {
        global $CFG;

        if (!is_readable($thefile)) {
            // this should not happen
            return;
        }

        if (empty($CFG->runclamonupload) or empty($CFG->pathtoclam)) {
            // clam not enabled
            return;
        }

        $CFG->pathtoclam = trim($CFG->pathtoclam);

        if (!file_exists($CFG->pathtoclam) or !is_executable($CFG->pathtoclam)) {
            // misconfigured clam - use the old notification for now
            require("$CFG->libdir/uploadlib.php");
            $notice = get_string('clamlost', 'moodle', $CFG->pathtoclam);
            clam_message_admins($notice);
            return;
        }

        // do NOT mess with permissions here, the calling party is responsible for making
        // sure the scanner engine can access the files!

        // execute test
        $cmd = escapeshellcmd($CFG->pathtoclam).' --stdout '.escapeshellarg($thefile);
        exec($cmd, $output, $return);

        if ($return == 0) {
            // perfect, no problem found
            return;

        } else if ($return == 1) {
            // infection found
            if ($deleteinfected) {
                unlink($thefile);
            }
            throw new moodle_exception('virusfounduser', 'moodle', '', array('filename'=>$filename));

        } else {
            //unknown problem
            require("$CFG->libdir/uploadlib.php");
            $notice = get_string('clamfailed', 'moodle', get_clam_error_code($return));
            $notice .= "\n\n". implode("\n", $output);
            clam_message_admins($notice);
            if ($CFG->clamfailureonupload === 'actlikevirus') {
                if ($deleteinfected) {
                    unlink($thefile);
                }
                throw new moodle_exception('virusfounduser', 'moodle', '', array('filename'=>$filename));
            } else {
                return;
            }
        }
    }

    /**
     * Repository method to serve the referenced file
     *
     * @see send_stored_file
     *
     * @param stored_file $storedfile the file that contains the reference
     * @param int $lifetime Number of seconds before the file should expire from caches (default 24 hours)
     * @param int $filter 0 (default)=no filtering, 1=all files, 2=html files only
     * @param bool $forcedownload If true (default false), forces download of file rather than view in browser/plugin
     * @param array $options additional options affecting the file serving
     */
    public function send_file($storedfile, $lifetime=86400 , $filter=0, $forcedownload=false, array $options = null) {
        if ($this->has_moodle_files()) {
            $fs = get_file_storage();
            $params = file_storage::unpack_reference($storedfile->get_reference(), true);
            $srcfile = null;
            if (is_array($params)) {
                $srcfile = $fs->get_file($params['contextid'], $params['component'], $params['filearea'],
                        $params['itemid'], $params['filepath'], $params['filename']);
            }
            if (empty($options)) {
                $options = array();
            }
            if (!isset($options['filename'])) {
                $options['filename'] = $storedfile->get_filename();
            }
            if (!$srcfile) {
                send_file_not_found();
            } else {
                send_stored_file($srcfile, $lifetime, $filter, $forcedownload, $options);
            }
        } else {
            throw new coding_exception("Repository plugin must implement send_file() method.");
        }
    }

    /**
     * Return reference file life time
     *
     * @param string $ref
     * @return int
     */
    public function get_reference_file_lifetime($ref) {
        // One day
        return 60 * 60 * 24;
    }

    /**
     * Decide whether or not the file should be synced
     *
     * @param stored_file $storedfile
     * @return bool
     */
    public function sync_individual_file(stored_file $storedfile) {
        return true;
    }

    /**
     * Return human readable reference information
     * {@link stored_file::get_reference()}
     *
     * @param string $reference
     * @param int $filestatus status of the file, 0 - ok, 666 - source missing
     * @return string
     */
    public function get_reference_details($reference, $filestatus = 0) {
        if ($this->has_moodle_files()) {
            $fileinfo = null;
            $params = file_storage::unpack_reference($reference, true);
            if (is_array($params)) {
                $context = get_context_instance_by_id($params['contextid']);
                if ($context) {
                    $browser = get_file_browser();
                    $fileinfo = $browser->get_file_info($context, $params['component'], $params['filearea'], $params['itemid'], $params['filepath'], $params['filename']);
                }
            }
            if (empty($fileinfo)) {
                if ($filestatus == 666) {
                    if (is_siteadmin() || ($context && has_capability('moodle/course:managefiles', $context))) {
                        return get_string('lostsource', 'repository',
                                $params['contextid']. '/'. $params['component']. '/'. $params['filearea']. '/'. $params['itemid']. $params['filepath']. $params['filename']);
                    } else {
                        return get_string('lostsource', 'repository', '');
                    }
                }
                return get_string('undisclosedsource', 'repository');
            } else {
                return $fileinfo->get_readable_fullname();
            }
        }
        return '';
    }

    /**
     * Cache file from external repository by reference
     * {@link repository::get_file_reference()}
     * {@link repository::get_file()}
     * Invoked at MOODLE/repository/repository_ajax.php
     *
     * @param string $reference this reference is generated by
     *                          repository::get_file_reference()
     * @param stored_file $storedfile created file reference
     */
    public function cache_file_by_reference($reference, $storedfile) {
    }

    /**
     * Returns information about file in this repository by reference
     * {@link repository::get_file_reference()}
     * {@link repository::get_file()}
     *
     * Returns null if file not found or is not readable
     *
     * @param stdClass $reference file reference db record
     * @return stdClass|null contains one of the following:
     *   - 'contenthash' and 'filesize'
     *   - 'filepath'
     *   - 'handle'
     *   - 'content'
     */
    public function get_file_by_reference($reference) {
        if ($this->has_moodle_files() && isset($reference->reference)) {
            $fs = get_file_storage();
            $params = file_storage::unpack_reference($reference->reference, true);
            if (!is_array($params) || !($storedfile = $fs->get_file($params['contextid'],
                    $params['component'], $params['filearea'], $params['itemid'], $params['filepath'],
                    $params['filename']))) {
                return null;
            }
            return (object)array(
                'contenthash' => $storedfile->get_contenthash(),
                'filesize'    => $storedfile->get_filesize()
            );
        }
        return null;
    }

    /**
     * Return the source information
     *
     * @param stdClass $url
     * @return string|null
     */
    public function get_file_source_info($url) {
        if ($this->has_moodle_files()) {
            return $this->get_reference_details($url, 0);
        }
        return $url;
    }

    /**
     * Move file from download folder to file pool using FILE API
     *
     * @todo MDL-28637
     * @static
     * @param string $thefile file path in download folder
     * @param stdClass $record
     * @return array containing the following keys:
     *           icon
     *           file
     *           id
     *           url
     */
    public static function move_to_filepool($thefile, $record) {
        global $DB, $CFG, $USER, $OUTPUT;

        // scan for viruses if possible, throws exception if problem found
        self::antivir_scan_file($thefile, $record->filename, empty($CFG->repository_no_delete)); //TODO: MDL-28637 this repository_no_delete is a bloody hack!

        $fs = get_file_storage();
        // If file name being used.
        if (repository::draftfile_exists($record->itemid, $record->filepath, $record->filename)) {
            $draftitemid = $record->itemid;
            $new_filename = repository::get_unused_filename($draftitemid, $record->filepath, $record->filename);
            $old_filename = $record->filename;
            // Create a tmp file.
            $record->filename = $new_filename;
            $newfile = $fs->create_file_from_pathname($record, $thefile);
            $event = array();
            $event['event'] = 'fileexists';
            $event['newfile'] = new stdClass;
            $event['newfile']->filepath = $record->filepath;
            $event['newfile']->filename = $new_filename;
            $event['newfile']->url = moodle_url::make_draftfile_url($draftitemid, $record->filepath, $new_filename)->out();

            $event['existingfile'] = new stdClass;
            $event['existingfile']->filepath = $record->filepath;
            $event['existingfile']->filename = $old_filename;
            $event['existingfile']->url      = moodle_url::make_draftfile_url($draftitemid, $record->filepath, $old_filename)->out();;
            return $event;
        }
        if ($file = $fs->create_file_from_pathname($record, $thefile)) {
            if (empty($CFG->repository_no_delete)) {
                $delete = unlink($thefile);
                unset($CFG->repository_no_delete);
            }
            return array(
                'url'=>moodle_url::make_draftfile_url($file->get_itemid(), $file->get_filepath(), $file->get_filename())->out(),
                'id'=>$file->get_itemid(),
                'file'=>$file->get_filename(),
                'icon' => $OUTPUT->pix_url(file_extension_icon($thefile, 32))->out(),
            );
        } else {
            return null;
        }
    }

    /**
     * Builds a tree of files This function is then called recursively.
     *
     * @static
     * @todo take $search into account, and respect a threshold for dynamic loading
     * @param file_info $fileinfo an object returned by file_browser::get_file_info()
     * @param string $search searched string
     * @param bool $dynamicmode no recursive call is done when in dynamic mode
     * @param array $list the array containing the files under the passed $fileinfo
     * @returns int the number of files found
     *
     */
    public static function build_tree($fileinfo, $search, $dynamicmode, &$list) {
        global $CFG, $OUTPUT;

        $filecount = 0;
        $children = $fileinfo->get_children();

        foreach ($children as $child) {
            $filename = $child->get_visible_name();
            $filesize = $child->get_filesize();
            $filesize = $filesize ? display_size($filesize) : '';
            $filedate = $child->get_timemodified();
            $filedate = $filedate ? userdate($filedate) : '';
            $filetype = $child->get_mimetype();

            if ($child->is_directory()) {
                $path = array();
                $level = $child->get_parent();
                while ($level) {
                    $params = $level->get_params();
                    $path[] = array($params['filepath'], $level->get_visible_name());
                    $level = $level->get_parent();
                }

                $tmp = array(
                    'title' => $child->get_visible_name(),
                    'size' => 0,
                    'date' => $filedate,
                    'path' => array_reverse($path),
                    'thumbnail' => $OUTPUT->pix_url(file_folder_icon(90))->out(false)
                );

                //if ($dynamicmode && $child->is_writable()) {
                //    $tmp['children'] = array();
                //} else {
                    // if folder name matches search, we send back all files contained.
                $_search = $search;
                if ($search && stristr($tmp['title'], $search) !== false) {
                    $_search = false;
                }
                $tmp['children'] = array();
                $_filecount = repository::build_tree($child, $_search, $dynamicmode, $tmp['children']);
                if ($search && $_filecount) {
                    $tmp['expanded'] = 1;
                }

                //}

                if (!$search || $_filecount || (stristr($tmp['title'], $search) !== false)) {
                    $filecount += $_filecount;
                    $list[] = $tmp;
                }

            } else { // not a directory
                // skip the file, if we're in search mode and it's not a match
                if ($search && (stristr($filename, $search) === false)) {
                    continue;
                }
                $params = $child->get_params();
                $source = serialize(array($params['contextid'], $params['component'], $params['filearea'], $params['itemid'], $params['filepath'], $params['filename']));
                $list[] = array(
                    'title' => $filename,
                    'size' => $filesize,
                    'date' => $filedate,
                    //'source' => $child->get_url(),
                    'source' => base64_encode($source),
                    'icon'=>$OUTPUT->pix_url(file_file_icon($child, 24))->out(false),
                    'thumbnail'=>$OUTPUT->pix_url(file_file_icon($child, 90))->out(false),
                );
                $filecount++;
            }
        }

        return $filecount;
    }

    /**
     * Display a repository instance list (with edit/delete/create links)
     *
     * @static
     * @param stdClass $context the context for which we display the instance
     * @param string $typename if set, we display only one type of instance
     */
    public static function display_instances_list($context, $typename = null) {
        global $CFG, $USER, $OUTPUT;

        $output = $OUTPUT->box_start('generalbox');
        //if the context is SYSTEM, so we call it from administration page
        $admin = ($context->id == SYSCONTEXTID) ? true : false;
        if ($admin) {
            $baseurl = new moodle_url('/'.$CFG->admin.'/repositoryinstance.php', array('sesskey'=>sesskey()));
            $output .= $OUTPUT->heading(get_string('siteinstances', 'repository'));
        } else {
            $baseurl = new moodle_url('/repository/manage_instances.php', array('contextid'=>$context->id, 'sesskey'=>sesskey()));
        }

        $namestr = get_string('name');
        $pluginstr = get_string('plugin', 'repository');
        $settingsstr = get_string('settings');
        $deletestr = get_string('delete');
        //retrieve list of instances. In administration context we want to display all
        //instances of a type, even if this type is not visible. In course/user context we
        //want to display only visible instances, but for every type types. The repository::get_instances()
        //third parameter displays only visible type.
        $params = array();
        $params['context'] = array($context, get_system_context());
        $params['currentcontext'] = $context;
        $params['onlyvisible'] = !$admin;
        $params['type']        = $typename;
        $instances = repository::get_instances($params);
        $instancesnumber = count($instances);
        $alreadyplugins = array();

        $table = new html_table();
        $table->head = array($namestr, $pluginstr, $settingsstr, $deletestr);
        $table->align = array('left', 'left', 'center','center');
        $table->data = array();

        $updowncount = 1;

        foreach ($instances as $i) {
            $settings = '';
            $delete = '';

            $type = repository::get_type_by_id($i->options['typeid']);

            if ($type->get_contextvisibility($context)) {
                if (!$i->readonly) {

                    $settingurl = new moodle_url($baseurl);
                    $settingurl->param('type', $i->options['type']);
                    $settingurl->param('edit', $i->id);
                    $settings .= html_writer::link($settingurl, $settingsstr);

                    $deleteurl = new moodle_url($baseurl);
                    $deleteurl->param('delete', $i->id);
                    $deleteurl->param('type', $i->options['type']);
                    $delete .= html_writer::link($deleteurl, $deletestr);
                }
            }

            $type = repository::get_type_by_id($i->options['typeid']);
            $table->data[] = array(format_string($i->name), $type->get_readablename(), $settings, $delete);

            //display a grey row if the type is defined as not visible
            if (isset($type) && !$type->get_visible()) {
                $table->rowclasses[] = 'dimmed_text';
            } else {
                $table->rowclasses[] = '';
            }

            if (!in_array($i->name, $alreadyplugins)) {
                $alreadyplugins[] = $i->name;
            }
        }
        $output .= html_writer::table($table);
        $instancehtml = '<div>';
        $addable = 0;

        //if no type is set, we can create all type of instance
        if (!$typename) {
            $instancehtml .= '<h3>';
            $instancehtml .= get_string('createrepository', 'repository');
            $instancehtml .= '</h3><ul>';
            $types = repository::get_editable_types($context);
            foreach ($types as $type) {
                if (!empty($type) && $type->get_visible()) {
                    $instanceoptionnames = repository::static_function($type->get_typename(), 'get_instance_option_names');
                    if (!empty($instanceoptionnames)) {
                        $baseurl->param('new', $type->get_typename());
                        $instancehtml .= '<li><a href="'.$baseurl->out().'">'.get_string('createxxinstance', 'repository', get_string('pluginname', 'repository_'.$type->get_typename())).  '</a></li>';
                        $baseurl->remove_params('new');
                        $addable++;
                    }
                }
            }
            $instancehtml .= '</ul>';

        } else {
            $instanceoptionnames = repository::static_function($typename, 'get_instance_option_names');
            if (!empty($instanceoptionnames)) {   //create a unique type of instance
                $addable = 1;
                $baseurl->param('new', $typename);
                $output .= $OUTPUT->single_button($baseurl, get_string('createinstance', 'repository'), 'get');
                $baseurl->remove_params('new');
            }
        }

        if ($addable) {
            $instancehtml .= '</div>';
            $output .= $instancehtml;
        }

        $output .= $OUTPUT->box_end();

        //print the list + creation links
        print($output);
    }

    /**
     * Prepare file reference information
     *
     * @param string $source
     * @return string file referece
     */
    public function get_file_reference($source) {
        if ($this->has_moodle_files() && ($this->supported_returntypes() & FILE_REFERENCE)) {
            $params = file_storage::unpack_reference($source);
            if (!is_array($params)) {
                throw new repository_exception('invalidparams', 'repository');
            }
            return file_storage::pack_reference($params);
        }
        return $source;
    }
    /**
     * Decide where to save the file, can be overwriten by subclass
     *
     * @param string $filename file name
     * @return file path
     */
    public function prepare_file($filename) {
        global $CFG;
        if (!file_exists($CFG->tempdir.'/download')) {
            mkdir($CFG->tempdir.'/download/', $CFG->directorypermissions, true);
        }
        if (is_dir($CFG->tempdir.'/download')) {
            $dir = $CFG->tempdir.'/download/';
        }
        if (empty($filename)) {
            $filename = uniqid('repo', true).'_'.time().'.tmp';
        }
        if (file_exists($dir.$filename)) {
            $filename = uniqid('m').$filename;
        }
        return $dir.$filename;
    }

    /**
     * Does this repository used to browse moodle files?
     *
     * @return bool
     */
    public function has_moodle_files() {
        return false;
    }

    /**
     * Return file URL, for most plugins, the parameter is the original
     * url, but some plugins use a file id, so we need this function to
     * convert file id to original url.
     *
     * @param string $url the url of file
     * @return string
     */
    public function get_link($url) {
        return $url;
    }

    /**
     * Download a file, this function can be overridden by subclass. {@link curl}
     *
     * @param string $url the url of file
     * @param string $filename save location
     * @return array with elements:
     *   path: internal location of the file
     *   url: URL to the source (from parameters)
     */
    public function get_file($url, $filename = '') {
        global $CFG;
        $path = $this->prepare_file($filename);
        $fp = fopen($path, 'w');
        $c = new curl;
        $result = $c->download(array(array('url'=>$url, 'file'=>$fp)));
        // Close file handler.
        fclose($fp);
        if (empty($result)) {
            unlink($path);
            return null;
        }
        return array('path'=>$path, 'url'=>$url);
    }

    /**
     * Return size of a file in bytes.
     *
     * @param string $source encoded and serialized data of file
     * @return int file size in bytes
     */
    public function get_file_size($source) {
        // TODO MDL-33297 remove this function completely?
        $browser    = get_file_browser();
        $params     = unserialize(base64_decode($source));
        $contextid  = clean_param($params['contextid'], PARAM_INT);
        $fileitemid = clean_param($params['itemid'], PARAM_INT);
        $filename   = clean_param($params['filename'], PARAM_FILE);
        $filepath   = clean_param($params['filepath'], PARAM_PATH);
        $filearea   = clean_param($params['filearea'], PARAM_AREA);
        $component  = clean_param($params['component'], PARAM_COMPONENT);
        $context    = get_context_instance_by_id($contextid);
        $file_info  = $browser->get_file_info($context, $component, $filearea, $fileitemid, $filepath, $filename);
        if (!empty($file_info)) {
            $filesize = $file_info->get_filesize();
        } else {
            $filesize = null;
        }
        return $filesize;
    }

    /**
     * Return is the instance is visible
     * (is the type visible ? is the context enable ?)
     *
     * @return bool
     */
    public function is_visible() {
        $type = repository::get_type_by_id($this->options['typeid']);
        $instanceoptions = repository::static_function($type->get_typename(), 'get_instance_option_names');

        if ($type->get_visible()) {
            //if the instance is unique so it's visible, otherwise check if the instance has a enabled context
            if (empty($instanceoptions) || $type->get_contextvisibility($this->context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the name of this instance, can be overridden.
     *
     * @return string
     */
    public function get_name() {
        global $DB;
        if ( $name = $this->instance->name ) {
            return $name;
        } else {
            return get_string('pluginname', 'repository_' . $this->options['type']);
        }
    }

    /**
     * What kind of files will be in this repository?
     *
     * @return array return '*' means this repository support any files, otherwise
     *               return mimetypes of files, it can be an array
     */
    public function supported_filetypes() {
        // return array('text/plain', 'image/gif');
        return '*';
    }

    /**
     * Tells how the file can be picked from this repository
     *
     * Maximum value is FILE_INTERNAL | FILE_EXTERNAL | FILE_REFERENCE
     *
     * @return int
     */
    public function supported_returntypes() {
        return (FILE_INTERNAL | FILE_EXTERNAL);
    }

    /**
     * Provide repository instance information for Ajax
     *
     * @return stdClass
     */
    final public function get_meta() {
        global $CFG, $OUTPUT;
        $meta = new stdClass();
        $meta->id   = $this->id;
        $meta->name = format_string($this->get_name());
        $meta->type = $this->options['type'];
        $meta->icon = $OUTPUT->pix_url('icon', 'repository_'.$meta->type)->out(false);
        $meta->supported_types = file_get_typegroup('extension', $this->supported_filetypes());
        $meta->return_types = $this->supported_returntypes();
        $meta->sortorder = $this->options['sortorder'];
        return $meta;
    }

    /**
     * Create an instance for this plug-in
     *
     * @static
     * @param string $type the type of the repository
     * @param int $userid the user id
     * @param stdClass $context the context
     * @param array $params the options for this instance
     * @param int $readonly whether to create it readonly or not (defaults to not)
     * @return mixed
     */
    public static function create($type, $userid, $context, $params, $readonly=0) {
        global $CFG, $DB;
        $params = (array)$params;
        require_once($CFG->dirroot . '/repository/'. $type . '/lib.php');
        $classname = 'repository_' . $type;
        if ($repo = $DB->get_record('repository', array('type'=>$type))) {
            $record = new stdClass();
            $record->name = $params['name'];
            $record->typeid = $repo->id;
            $record->timecreated  = time();
            $record->timemodified = time();
            $record->contextid = $context->id;
            $record->readonly = $readonly;
            $record->userid    = $userid;
            $id = $DB->insert_record('repository_instances', $record);
            $options = array();
            $configs = call_user_func($classname . '::get_instance_option_names');
            if (!empty($configs)) {
                foreach ($configs as $config) {
                    if (isset($params[$config])) {
                        $options[$config] = $params[$config];
                    } else {
                        $options[$config] = null;
                    }
                }
            }

            if (!empty($id)) {
                unset($options['name']);
                $instance = repository::get_instance($id);
                $instance->set_option($options);
                return $id;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * delete a repository instance
     *
     * @param bool $downloadcontents
     * @return bool
     */
    final public function delete($downloadcontents = false) {
        global $DB;
        if ($downloadcontents) {
            $this->convert_references_to_local();
        }
        try {
            $DB->delete_records('repository_instances', array('id'=>$this->id));
            $DB->delete_records('repository_instance_config', array('instanceid'=>$this->id));
        } catch (dml_exception $ex) {
            return false;
        }
        return true;
    }

    /**
     * Hide/Show a repository
     *
     * @param string $hide
     * @return bool
     */
    final public function hide($hide = 'toggle') {
        global $DB;
        if ($entry = $DB->get_record('repository', array('id'=>$this->id))) {
            if ($hide === 'toggle' ) {
                if (!empty($entry->visible)) {
                    $entry->visible = 0;
                } else {
                    $entry->visible = 1;
                }
            } else {
                if (!empty($hide)) {
                    $entry->visible = 0;
                } else {
                    $entry->visible = 1;
                }
            }
            return $DB->update_record('repository', $entry);
        }
        return false;
    }

    /**
     * Save settings for repository instance
     * $repo->set_option(array('api_key'=>'f2188bde132', 'name'=>'dongsheng'));
     *
     * @param array $options settings
     * @return bool
     */
    public function set_option($options = array()) {
        global $DB;

        if (!empty($options['name'])) {
            $r = new stdClass();
            $r->id   = $this->id;
            $r->name = $options['name'];
            $DB->update_record('repository_instances', $r);
            unset($options['name']);
        }
        foreach ($options as $name=>$value) {
            if ($id = $DB->get_field('repository_instance_config', 'id', array('name'=>$name, 'instanceid'=>$this->id))) {
                $DB->set_field('repository_instance_config', 'value', $value, array('id'=>$id));
            } else {
                $config = new stdClass();
                $config->instanceid = $this->id;
                $config->name   = $name;
                $config->value  = $value;
                $DB->insert_record('repository_instance_config', $config);
            }
        }
        return true;
    }

    /**
     * Get settings for repository instance
     *
     * @param string $config
     * @return array Settings
     */
    public function get_option($config = '') {
        global $DB;
        $entries = $DB->get_records('repository_instance_config', array('instanceid'=>$this->id));
        $ret = array();
        if (empty($entries)) {
            return $ret;
        }
        foreach($entries as $entry) {
            $ret[$entry->name] = $entry->value;
        }
        if (!empty($config)) {
            if (isset($ret[$config])) {
                return $ret[$config];
            } else {
                return null;
            }
        } else {
            return $ret;
        }
    }

    /**
     * Filter file listing to display specific types
     *
     * @param array $value
     * @return bool
     */
    public function filter(&$value) {
        $accepted_types = optional_param_array('accepted_types', '', PARAM_RAW);
        if (isset($value['children'])) {
            if (!empty($value['children'])) {
                $value['children'] = array_filter($value['children'], array($this, 'filter'));
            }
            return true; // always return directories
        } else {
            if ($accepted_types == '*' or empty($accepted_types)
                or (is_array($accepted_types) and in_array('*', $accepted_types))) {
                return true;
            } else {
                foreach ($accepted_types as $ext) {
                    if (preg_match('#'.$ext.'$#i', $value['title'])) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Given a path, and perhaps a search, get a list of files.
     *
     * See details on {@link http://docs.moodle.org/dev/Repository_plugins}
     *
     * @param string $path this parameter can a folder name, or a identification of folder
     * @param string $page the page number of file list
     * @return array the list of files, including meta infomation, containing the following keys
     *           manage, url to manage url
     *           client_id
     *           login, login form
     *           repo_id, active repository id
     *           login_btn_action, the login button action
     *           login_btn_label, the login button label
     *           total, number of results
     *           perpage, items per page
     *           page
     *           pages, total pages
     *           issearchresult, is it a search result?
     *           list, file list
     *           path, current path and parent path
     */
    public function get_listing($path = '', $page = '') {
    }

    /**
     * Prepares list of files before passing it to AJAX, makes sure data is in the correct
     * format and stores formatted values.
     *
     * @param array|stdClass $listing result of get_listing() or search() or file_get_drafarea_files()
     * @return array
     */
    public static function prepare_listing($listing) {
        global $OUTPUT;

        $defaultfoldericon = $OUTPUT->pix_url(file_folder_icon(24))->out(false);
        // prepare $listing['path'] or $listing->path
        if (is_array($listing) && isset($listing['path']) && is_array($listing['path'])) {
            $path = &$listing['path'];
        } else if (is_object($listing) && isset($listing->path) && is_array($listing->path)) {
            $path = &$listing->path;
        }
        if (isset($path)) {
            $len = count($path);
            for ($i=0; $i<$len; $i++) {
                if (is_array($path[$i]) && !isset($path[$i]['icon'])) {
                    $path[$i]['icon'] = $defaultfoldericon;
                } else if (is_object($path[$i]) && !isset($path[$i]->icon)) {
                    $path[$i]->icon = $defaultfoldericon;
                }
            }
        }

        // prepare $listing['list'] or $listing->list
        if (is_array($listing) && isset($listing['list']) && is_array($listing['list'])) {
            $listing['list'] = array_values($listing['list']); // convert to array
            $files = &$listing['list'];
        } else if (is_object($listing) && isset($listing->list) && is_array($listing->list)) {
            $listing->list = array_values($listing->list); // convert to array
            $files = &$listing->list;
        } else {
            return $listing;
        }
        $len = count($files);
        for ($i=0; $i<$len; $i++) {
            if (is_object($files[$i])) {
                $file = (array)$files[$i];
                $converttoobject = true;
            } else {
                $file = & $files[$i];
                $converttoobject = false;
            }
            if (isset($file['size'])) {
                $file['size'] = (int)$file['size'];
                $file['size_f'] = display_size($file['size']);
            }
            if (isset($file['license']) &&
                    get_string_manager()->string_exists($file['license'], 'license')) {
                $file['license_f'] = get_string($file['license'], 'license');
            }
            if (isset($file['image_width']) && isset($file['image_height'])) {
                $a = array('width' => $file['image_width'], 'height' => $file['image_height']);
                $file['dimensions'] = get_string('imagesize', 'repository', (object)$a);
            }
            foreach (array('date', 'datemodified', 'datecreated') as $key) {
                if (!isset($file[$key]) && isset($file['date'])) {
                    $file[$key] = $file['date'];
                }
                if (isset($file[$key])) {
                    // must be UNIX timestamp
                    $file[$key] = (int)$file[$key];
                    if (!$file[$key]) {
                        unset($file[$key]);
                    } else {
                        $file[$key.'_f'] = userdate($file[$key], get_string('strftimedatetime', 'langconfig'));
                        $file[$key.'_f_s'] = userdate($file[$key], get_string('strftimedatetimeshort', 'langconfig'));
                    }
                }
            }
            $isfolder = (array_key_exists('children', $file) || (isset($file['type']) && $file['type'] == 'folder'));
            $filename = null;
            if (isset($file['title'])) {
                $filename = $file['title'];
            }
            else if (isset($file['fullname'])) {
                $filename = $file['fullname'];
            }
            if (!isset($file['mimetype']) && !$isfolder && $filename) {
                $file['mimetype'] = get_mimetype_description(array('filename' => $filename));
            }
            if (!isset($file['icon'])) {
                if ($isfolder) {
                    $file['icon'] = $defaultfoldericon;
                } else if ($filename) {
                    $file['icon'] = $OUTPUT->pix_url(file_extension_icon($filename, 24))->out(false);
                }
            }
            if ($converttoobject) {
                $files[$i] = (object)$file;
            }
        }
        return $listing;
    }

    /**
     * Search files in repository
     * When doing global search, $search_text will be used as
     * keyword.
     *
     * @param string $search_text search key word
     * @param int $page page
     * @return mixed {@see repository::get_listing}
     */
    public function search($search_text, $page = 0) {
        $list = array();
        $list['list'] = array();
        return false;
    }

    /**
     * Logout from repository instance
     * By default, this function will return a login form
     *
     * @return string
     */
    public function logout(){
        return $this->print_login();
    }

    /**
     * To check whether the user is logged in.
     *
     * @return bool
     */
    public function check_login(){
        return true;
    }


    /**
     * Show the login screen, if required
     *
     * @return string
     */
    public function print_login(){
        return $this->get_listing();
    }

    /**
     * Show the search screen, if required
     *
     * @return string
     */
    public function print_search() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core', 'files');
        return $renderer->repository_default_searchform();
    }

    /**
     * For oauth like external authentication, when external repository direct user back to moodle,
     * this funciton will be called to set up token and token_secret
     */
    public function callback() {
    }

    /**
     * is it possible to do glboal search?
     *
     * @return bool
     */
    public function global_search() {
        return false;
    }

    /**
     * Defines operations that happen occasionally on cron
     *
     * @return bool
     */
    public function cron() {
        return true;
    }

    /**
     * function which is run when the type is created (moodle administrator add the plugin)
     *
     * @return bool success or fail?
     */
    public static function plugin_init() {
        return true;
    }

    /**
     * Edit/Create Admin Settings Moodle form
     *
     * @param moodleform $mform Moodle form (passed by reference)
     * @param string $classname repository class name
     */
    public static function type_config_form($mform, $classname = 'repository') {
        $instnaceoptions = call_user_func(array($classname, 'get_instance_option_names'), $mform, $classname);
        if (empty($instnaceoptions)) {
            // this plugin has only one instance
            // so we need to give it a name
            // it can be empty, then moodle will look for instance name from language string
            $mform->addElement('text', 'pluginname', get_string('pluginname', 'repository'), array('size' => '40'));
            $mform->addElement('static', 'pluginnamehelp', '', get_string('pluginnamehelp', 'repository'));
            $mform->setType('pluginname', PARAM_TEXT);
        }
    }

    /**
     * Validate Admin Settings Moodle form
     *
     * @static
     * @param moodleform $mform Moodle form (passed by reference)
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $errors array of ("fieldname"=>errormessage) of errors
     * @return array array of errors
     */
    public static function type_form_validation($mform, $data, $errors) {
        return $errors;
    }


    /**
     * Edit/Create Instance Settings Moodle form
     *
     * @param moodleform $mform Moodle form (passed by reference)
     */
    public static function instance_config_form($mform) {
    }

    /**
     * Return names of the general options.
     * By default: no general option name
     *
     * @return array
     */
    public static function get_type_option_names() {
        return array('pluginname');
    }

    /**
     * Return names of the instance options.
     * By default: no instance option name
     *
     * @return array
     */
    public static function get_instance_option_names() {
        return array();
    }

    /**
     * Validate repository plugin instance form
     *
     * @param moodleform $mform moodle form
     * @param array $data form data
     * @param array $errors errors
     * @return array errors
     */
    public static function instance_form_validation($mform, $data, $errors) {
        return $errors;
    }

    /**
     * Create a shorten filename
     *
     * @param string $str filename
     * @param int $maxlength max file name length
     * @return string short filename
     */
    public function get_short_filename($str, $maxlength) {
        if (textlib::strlen($str) >= $maxlength) {
            return trim(textlib::substr($str, 0, $maxlength)).'...';
        } else {
            return $str;
        }
    }

    /**
     * Overwrite an existing file
     *
     * @param int $itemid
     * @param string $filepath
     * @param string $filename
     * @param string $newfilepath
     * @param string $newfilename
     * @return bool
     */
    public static function overwrite_existing_draftfile($itemid, $filepath, $filename, $newfilepath, $newfilename) {
        global $USER;
        $fs = get_file_storage();
        $user_context = get_context_instance(CONTEXT_USER, $USER->id);
        if ($file = $fs->get_file($user_context->id, 'user', 'draft', $itemid, $filepath, $filename)) {
            if ($tempfile = $fs->get_file($user_context->id, 'user', 'draft', $itemid, $newfilepath, $newfilename)) {
                // delete existing file to release filename
                $file->delete();
                // create new file
                $newfile = $fs->create_file_from_storedfile(array('filepath'=>$filepath, 'filename'=>$filename), $tempfile);
                // remove temp file
                $tempfile->delete();
                return true;
            }
        }
        return false;
    }

    /**
     * Delete a temp file from draft area
     *
     * @param int $draftitemid
     * @param string $filepath
     * @param string $filename
     * @return bool
     */
    public static function delete_tempfile_from_draft($draftitemid, $filepath, $filename) {
        global $USER;
        $fs = get_file_storage();
        $user_context = get_context_instance(CONTEXT_USER, $USER->id);
        if ($file = $fs->get_file($user_context->id, 'user', 'draft', $draftitemid, $filepath, $filename)) {
            $file->delete();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Find all external files in this repo and import them
     */
    public function convert_references_to_local() {
        $fs = get_file_storage();
        $files = $fs->get_external_files($this->id);
        foreach ($files as $storedfile) {
            $fs->import_external_file($storedfile);
        }
    }

    /**
     * Called from phpunit between tests, resets whatever was cached
     */
    public static function reset_caches() {
        self::sync_external_file(null, true);
    }

    /**
     * Call to request proxy file sync with repository source.
     *
     * @param stored_file $file
     * @param bool $resetsynchistory whether to reset all history of sync (used by phpunit)
     * @return bool success
     */
    public static function sync_external_file($file, $resetsynchistory = false) {
        global $DB;
        // TODO MDL-25290 static should be replaced with MUC code.
        static $synchronized = array();
        if ($resetsynchistory) {
            $synchronized = array();
        }

        $fs = get_file_storage();

        if (!$file || !$file->get_referencefileid()) {
            return false;
        }
        if (array_key_exists($file->get_id(), $synchronized)) {
            return $synchronized[$file->get_id()];
        }

        // remember that we already cached in current request to prevent from querying again
        $synchronized[$file->get_id()] = false;

        if (!$reference = $DB->get_record('files_reference', array('id'=>$file->get_referencefileid()))) {
            return false;
        }

        if (!empty($reference->lastsync) and ($reference->lastsync + $reference->lifetime > time())) {
            $synchronized[$file->get_id()] = true;
            return true;
        }

        if (!$repository = self::get_repository_by_id($reference->repositoryid, SYSCONTEXTID)) {
            return false;
        }

        if (!$repository->sync_individual_file($file)) {
            return false;
        }

        $fileinfo = $repository->get_file_by_reference($reference);
        if ($fileinfo === null) {
            // does not exist any more - set status to missing
            $file->set_missingsource();
            //TODO: purge content from pool if we set some other content hash and it is no used any more
            $synchronized[$file->get_id()] = true;
            return true;
        }

        $contenthash = null;
        $filesize = null;
        if (!empty($fileinfo->contenthash)) {
            // contenthash returned, file already in moodle
            $contenthash = $fileinfo->contenthash;
            $filesize = $fileinfo->filesize;
        } else if (!empty($fileinfo->filepath)) {
            // File path returned
            list($contenthash, $filesize, $newfile) = $fs->add_file_to_pool($fileinfo->filepath);
        } else if (!empty($fileinfo->handle) && is_resource($fileinfo->handle)) {
            // File handle returned
            $contents = '';
            while (!feof($fileinfo->handle)) {
                $contents .= fread($handle, 8192);
            }
            fclose($fileinfo->handle);
            list($contenthash, $filesize, $newfile) = $fs->add_string_to_pool($content);
        } else if (isset($fileinfo->content)) {
            // File content returned
            list($contenthash, $filesize, $newfile) = $fs->add_string_to_pool($fileinfo->content);
        }

        if (!isset($contenthash) or !isset($filesize)) {
            return false;
        }

        // update files table
        $file->set_synchronized($contenthash, $filesize);
        $synchronized[$file->get_id()] = true;
        return true;
    }

    /**
     * Build draft file's source field
     *
     * {@link file_restore_source_field_from_draft_file()}
     * XXX: This is a hack for file manager (MDL-28666)
     * For newly created  draft files we have to construct
     * source filed in php serialized data format.
     * File manager needs to know the original file information before copying
     * to draft area, so we append these information in mdl_files.source field
     *
     * @param string $source
     * @return string serialised source field
     */
    public static function build_source_field($source) {
        $sourcefield = new stdClass;
        $sourcefield->source = $source;
        return serialize($sourcefield);
    }
}

/**
 * Exception class for repository api
 *
 * @since 2.0
 * @package   repository
 * @category  repository
 * @copyright 2009 Dongsheng Cai {@link http://dongsheng.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_exception extends moodle_exception {
}

/**
 * This is a class used to define a repository instance form
 *
 * @since 2.0
 * @package   repository
 * @category  repository
 * @copyright 2009 Dongsheng Cai {@link http://dongsheng.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class repository_instance_form extends moodleform {
    /** @var stdClass repository instance */
    protected $instance;
    /** @var string repository plugin type */
    protected $plugin;

    /**
     * Added defaults to moodle form
     */
    protected function add_defaults() {
        $mform =& $this->_form;
        $strrequired = get_string('required');

        $mform->addElement('hidden', 'edit',  ($this->instance) ? $this->instance->id : 0);
        $mform->setType('edit', PARAM_INT);
        $mform->addElement('hidden', 'new',   $this->plugin);
        $mform->setType('new', PARAM_FORMAT);
        $mform->addElement('hidden', 'plugin', $this->plugin);
        $mform->setType('plugin', PARAM_PLUGIN);
        $mform->addElement('hidden', 'typeid', $this->typeid);
        $mform->setType('typeid', PARAM_INT);
        $mform->addElement('hidden', 'contextid', $this->contextid);
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'), 'maxlength="100" size="30"');
        $mform->addRule('name', $strrequired, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
    }

    /**
     * Define moodle form elements
     */
    public function definition() {
        global $CFG;
        // type of plugin, string
        $this->plugin = $this->_customdata['plugin'];
        $this->typeid = $this->_customdata['typeid'];
        $this->contextid = $this->_customdata['contextid'];
        $this->instance = (isset($this->_customdata['instance'])
                && is_subclass_of($this->_customdata['instance'], 'repository'))
            ? $this->_customdata['instance'] : null;

        $mform =& $this->_form;

        $this->add_defaults();

        // Add instance config options.
        $result = repository::static_function($this->plugin, 'instance_config_form', $mform);
        if ($result === false) {
            // Remove the name element if no other config options.
            $mform->removeElement('name');
        }
        if ($this->instance) {
            $data = array();
            $data['name'] = $this->instance->name;
            if (!$this->instance->readonly) {
                // and set the data if we have some.
                foreach ($this->instance->get_instance_option_names() as $config) {
                    if (!empty($this->instance->options[$config])) {
                        $data[$config] = $this->instance->options[$config];
                     } else {
                        $data[$config] = '';
                     }
                }
            }
            $this->set_data($data);
        }

        if ($result === false) {
            $mform->addElement('cancel');
        } else {
            $this->add_action_buttons(true, get_string('save','repository'));
        }
    }

    /**
     * Validate moodle form data
     *
     * @param array $data form data
     * @param array $files files in form
     * @return array errors
     */
    public function validation($data, $files) {
        global $DB;
        $errors = array();
        $plugin = $this->_customdata['plugin'];
        $instance = (isset($this->_customdata['instance'])
                && is_subclass_of($this->_customdata['instance'], 'repository'))
            ? $this->_customdata['instance'] : null;
        if (!$instance) {
            $errors = repository::static_function($plugin, 'instance_form_validation', $this, $data, $errors);
        } else {
            $errors = $instance->instance_form_validation($this, $data, $errors);
        }

        $sql = "SELECT count('x')
                  FROM {repository_instances} i, {repository} r
                 WHERE r.type=:plugin AND r.id=i.typeid AND i.name=:name";
        if ($DB->count_records_sql($sql, array('name' => $data['name'], 'plugin' => $data['plugin'])) > 1) {
            $errors['name'] = get_string('erroruniquename', 'repository');
        }

        return $errors;
    }
}

/**
 * This is a class used to define a repository type setting form
 *
 * @since 2.0
 * @package   repository
 * @category  repository
 * @copyright 2009 Dongsheng Cai {@link http://dongsheng.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class repository_type_form extends moodleform {
    /** @var stdClass repository instance */
    protected $instance;
    /** @var string repository plugin name */
    protected $plugin;
    /** @var string action */
    protected $action;

    /**
     * Definition of the moodleform
     */
    public function definition() {
        global $CFG;
        // type of plugin, string
        $this->plugin = $this->_customdata['plugin'];
        $this->instance = (isset($this->_customdata['instance'])
                && is_a($this->_customdata['instance'], 'repository_type'))
            ? $this->_customdata['instance'] : null;

        $this->action = $this->_customdata['action'];
        $this->pluginname = $this->_customdata['pluginname'];
        $mform =& $this->_form;
        $strrequired = get_string('required');

        $mform->addElement('hidden', 'action', $this->action);
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'repos', $this->plugin);
        $mform->setType('repos', PARAM_PLUGIN);

        // let the plugin add its specific fields
        $classname = 'repository_' . $this->plugin;
        require_once($CFG->dirroot . '/repository/' . $this->plugin . '/lib.php');
        //add "enable course/user instances" checkboxes if multiple instances are allowed
        $instanceoptionnames = repository::static_function($this->plugin, 'get_instance_option_names');

        $result = call_user_func(array($classname, 'type_config_form'), $mform, $classname);

        if (!empty($instanceoptionnames)) {
            $sm = get_string_manager();
            $component = 'repository';
            if ($sm->string_exists('enablecourseinstances', 'repository_' . $this->plugin)) {
                $component .= ('_' . $this->plugin);
            }
            $mform->addElement('checkbox', 'enablecourseinstances', get_string('enablecourseinstances', $component));

            $component = 'repository';
            if ($sm->string_exists('enableuserinstances', 'repository_' . $this->plugin)) {
                $component .= ('_' . $this->plugin);
            }
            $mform->addElement('checkbox', 'enableuserinstances', get_string('enableuserinstances', $component));
        }

        // set the data if we have some.
        if ($this->instance) {
            $data = array();
            $option_names = call_user_func(array($classname,'get_type_option_names'));
            if (!empty($instanceoptionnames)){
                $option_names[] = 'enablecourseinstances';
                $option_names[] = 'enableuserinstances';
            }

            $instanceoptions = $this->instance->get_options();
            foreach ($option_names as $config) {
                if (!empty($instanceoptions[$config])) {
                    $data[$config] = $instanceoptions[$config];
                } else {
                    $data[$config] = '';
                }
            }
            // XXX: set plugin name for plugins which doesn't have muliti instances
            if (empty($instanceoptionnames)){
                $data['pluginname'] = $this->pluginname;
            }
            $this->set_data($data);
        }

        $this->add_action_buttons(true, get_string('save','repository'));
    }

    /**
     * Validate moodle form data
     *
     * @param array $data moodle form data
     * @param array $files
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = array();
        $plugin = $this->_customdata['plugin'];
        $instance = (isset($this->_customdata['instance'])
                && is_subclass_of($this->_customdata['instance'], 'repository'))
            ? $this->_customdata['instance'] : null;
        if (!$instance) {
            $errors = repository::static_function($plugin, 'type_form_validation', $this, $data, $errors);
        } else {
            $errors = $instance->type_form_validation($this, $data, $errors);
        }

        return $errors;
    }
}

/**
 * Generate all options needed by filepicker
 *
 * @param array $args including following keys
 *          context
 *          accepted_types
 *          return_types
 *
 * @return array the list of repository instances, including meta infomation, containing the following keys
 *          externallink
 *          repositories
 *          accepted_types
 */
function initialise_filepicker($args) {
    global $CFG, $USER, $PAGE, $OUTPUT;
    static $templatesinitialized = array();
    require_once($CFG->libdir . '/licenselib.php');

    $return = new stdClass();
    $licenses = array();
    if (!empty($CFG->licenses)) {
        $array = explode(',', $CFG->licenses);
        foreach ($array as $license) {
            $l = new stdClass();
            $l->shortname = $license;
            $l->fullname = get_string($license, 'license');
            $licenses[] = $l;
        }
    }
    if (!empty($CFG->sitedefaultlicense)) {
        $return->defaultlicense = $CFG->sitedefaultlicense;
    }

    $return->licenses = $licenses;

    $return->author = fullname($USER);

    if (empty($args->context)) {
        $context = $PAGE->context;
    } else {
        $context = $args->context;
    }
    $disable_types = array();
    if (!empty($args->disable_types)) {
        $disable_types = $args->disable_types;
    }

    $user_context = get_context_instance(CONTEXT_USER, $USER->id);

    list($context, $course, $cm) = get_context_info_array($context->id);
    $contexts = array($user_context, get_system_context());
    if (!empty($course)) {
        // adding course context
        $contexts[] = get_context_instance(CONTEXT_COURSE, $course->id);
    }
    $externallink = (int)get_config(null, 'repositoryallowexternallinks');
    $repositories = repository::get_instances(array(
        'context'=>$contexts,
        'currentcontext'=> $context,
        'accepted_types'=>$args->accepted_types,
        'return_types'=>$args->return_types,
        'disable_types'=>$disable_types
    ));

    $return->repositories = array();

    if (empty($externallink)) {
        $return->externallink = false;
    } else {
        $return->externallink = true;
    }

    $return->userprefs = array();
    $return->userprefs['recentrepository'] = get_user_preferences('filepicker_recentrepository', '');
    $return->userprefs['recentlicense'] = get_user_preferences('filepicker_recentlicense', '');
    $return->userprefs['recentviewmode'] = get_user_preferences('filepicker_recentviewmode', '');

    user_preference_allow_ajax_update('filepicker_recentrepository', PARAM_INT);
    user_preference_allow_ajax_update('filepicker_recentlicense', PARAM_SAFEDIR);
    user_preference_allow_ajax_update('filepicker_recentviewmode', PARAM_INT);


    // provided by form element
    $return->accepted_types = file_get_typegroup('extension', $args->accepted_types);
    $return->return_types = $args->return_types;
    $templates = array();
    foreach ($repositories as $repository) {
        $meta = $repository->get_meta();
        // Please note that the array keys for repositories are used within
        // JavaScript a lot, the key NEEDS to be the repository id.
        $return->repositories[$repository->id] = $meta;
        // Register custom repository template if it has one
        if(method_exists($repository, 'get_upload_template') && !array_key_exists('uploadform_' . $meta->type, $templatesinitialized)) {
            $templates['uploadform_' . $meta->type] = $repository->get_upload_template();
            $templatesinitialized['uploadform_' . $meta->type] = true;
        }
    }
    if (!array_key_exists('core', $templatesinitialized)) {
        // we need to send each filepicker template to the browser just once
        $fprenderer = $PAGE->get_renderer('core', 'files');
        $templates = array_merge($templates, $fprenderer->filepicker_js_templates());
        $templatesinitialized['core'] = true;
    }
    if (sizeof($templates)) {
        $PAGE->requires->js_init_call('M.core_filepicker.set_templates', array($templates), true);
    }
    return $return;
}
/**
 * Small function to walk an array to attach repository ID
 *
 * @param array $value
 * @param string $key
 * @param int $id
 */
function repository_attach_id(&$value, $key, $id){
    $value['repo_id'] = $id;
}
