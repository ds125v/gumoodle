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
 * Library to handle drag and drop course uploads
 *
 * @package    core
 * @subpackage lib
 * @copyright  2012 Davo smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->dirroot.'/repository/upload/lib.php');
require_once($CFG->dirroot.'/course/lib.php');

/**
 * Add the Javascript to enable drag and drop upload to a course page
 *
 * @param object $course The currently displayed course
 * @param array $modnames The list of enabled (visible) modules on this site
 * @return void
 */
function dndupload_add_to_course($course, $modnames) {
    global $CFG, $PAGE;

    $showstatus = optional_param('notifyeditingon', false, PARAM_BOOL);

    // Get all handlers.
    $handler = new dndupload_handler($course, $modnames);
    $jsdata = $handler->get_js_data();
    if (empty($jsdata->types) && empty($jsdata->filehandlers)) {
        return; // No valid handlers - don't enable drag and drop.
    }

    // Add the javascript to the page.
    $jsmodule = array(
        'name' => 'coursedndupload',
        'fullpath' => new moodle_url('/course/dndupload.js'),
        'strings' => array(
            array('addfilehere', 'moodle'),
            array('dndworkingfiletextlink', 'moodle'),
            array('dndworkingfilelink', 'moodle'),
            array('dndworkingfiletext', 'moodle'),
            array('dndworkingfile', 'moodle'),
            array('dndworkingtextlink', 'moodle'),
            array('dndworkingtext', 'moodle'),
            array('dndworkinglink', 'moodle'),
            array('filetoolarge', 'moodle'),
            array('actionchoice', 'moodle'),
            array('servererror', 'moodle'),
            array('upload', 'moodle'),
            array('cancel', 'moodle')
        ),
        'requires' => array('node', 'event', 'panel', 'json', 'anim')
    );
    $vars = array(
        array('courseid' => $course->id,
              'maxbytes' => get_max_upload_file_size($CFG->maxbytes, $course->maxbytes),
              'handlers' => $handler->get_js_data(),
              'showstatus' => $showstatus)
    );

    $PAGE->requires->js_init_call('M.course_dndupload.init', $vars, true, $jsmodule);
}


/**
 * Stores all the information about the available dndupload handlers
 *
 * @package    core
 * @copyright  2012 Davo Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dndupload_handler {

    /**
     * @var array A list of all registered mime types that can be dropped onto a course
     *            along with the modules that will handle them.
     */
    protected $types = array();

    /**
     * @var array  A list of the different file types (extensions) that different modules
     *             will handle.
     */
    protected $filehandlers = array();

    /**
     * Gather a list of dndupload handlers from the different mods
     *
     * @param object $course The course this is being added to (to check course_allowed_module() )
     */
    public function __construct($course, $modnames = null) {
        global $CFG;

        // Add some default types to handle.
        // Note: 'Files' type is hard-coded into the Javascript as this needs to be ...
        // ... treated a little differently.
        $this->add_type('url', array('url', 'text/uri-list', 'text/x-moz-url'), get_string('addlinkhere', 'moodle'),
                        get_string('nameforlink', 'moodle'), 10);
        $this->add_type('text/html', array('text/html'), get_string('addpagehere', 'moodle'),
                        get_string('nameforpage', 'moodle'), 20);
        $this->add_type('text', array('text', 'text/plain'), get_string('addpagehere', 'moodle'),
                        get_string('nameforpage', 'moodle'), 30);

        // Loop through all modules to find handlers.
        $mods = get_plugin_list_with_function('mod', 'dndupload_register');
        foreach ($mods as $component => $funcname) {
            list($modtype, $modname) = normalize_component($component);
            if ($modnames && !array_key_exists($modname, $modnames)) {
                continue; // Module is deactivated (hidden) at the site level.
            }
            if (!course_allowed_module($course, $modname)) {
                continue; // User does not have permission to add this module to the course.
            }
            $resp = $funcname();
            if (!$resp) {
                continue;
            }
            if (isset($resp['files'])) {
                foreach ($resp['files'] as $file) {
                    $this->add_file_handler($file['extension'], $modname, $file['message']);
                }
            }
            if (isset($resp['addtypes'])) {
                foreach ($resp['addtypes'] as $type) {
                    if (isset($type['priority'])) {
                        $priority = $type['priority'];
                    } else {
                        $priority = 100;
                    }
                    $this->add_type($type['identifier'], $type['datatransfertypes'],
                                    $type['addmessage'], $type['namemessage'], $priority);
                }
            }
            if (isset($resp['types'])) {
                foreach ($resp['types'] as $type) {
                    $this->add_type_handler($type['identifier'], $modname, $type['message']);
                }
            }
        }
    }

    /**
     * Used to add a new mime type that can be drag and dropped onto a
     * course displayed in a browser window
     *
     * @param string $identifier The name that this type will be known as
     * @param array $datatransfertypes An array of the different types in the browser
     *                                 'dataTransfer.types' object that will map to this type
     * @param string $addmessage The message to display in the browser when this type is being
     *                           dragged onto the page
     * @param string $namemessage The message to pop up when asking for the name to give the
     *                            course module instance when it is created
     * @param int $priority Controls the order in which types are checked by the browser (mainly
     *                      needed to check for 'text' last as that is usually given as fallback)
     */
    public function add_type($identifier, $datatransfertypes, $addmessage, $namemessage, $priority=100) {
        if ($this->is_known_type($identifier)) {
            throw new coding_exception("Type $identifier is already registered");
        }

        $add = new stdClass;
        $add->identifier = $identifier;
        $add->datatransfertypes = $datatransfertypes;
        $add->addmessage = $addmessage;
        $add->namemessage = $namemessage;
        $add->priority = $priority;
        $add->handlers = array();

        $this->types[$identifier] = $add;
    }

    /**
     * Used to declare that a particular module will handle a particular type
     * of dropped data
     *
     * @param string $type The name of the type (as declared in add_type)
     * @param string $module The name of the module to handle this type
     * @param string $message The message to show the user if more than one handler is registered
     *                        for a type and the user needs to make a choice between them
     */
    public function add_type_handler($type, $module, $message) {
        if (!$this->is_known_type($type)) {
            throw new coding_exception("Trying to add handler for unknown type $type");
        }

        $add = new stdClass;
        $add->type = $type;
        $add->module = $module;
        $add->message = $message;

        $this->types[$type]->handlers[] = $add;
    }

    /**
     * Used to declare that a particular module will handle a particular type
     * of dropped file
     *
     * @param string $extension The file extension to handle ('*' for all types)
     * @param string $module The name of the module to handle this type
     * @param string $message The message to show the user if more than one handler is registered
     *                        for a type and the user needs to make a choice between them
     */
    public function add_file_handler($extension, $module, $message) {
        $extension = strtolower($extension);

        $add = new stdClass;
        $add->extension = $extension;
        $add->module = $module;
        $add->message = $message;

        $this->filehandlers[] = $add;
    }

    /**
     * Check to see if the type has been registered
     *
     * @param string $type The identifier of the type you are interested in
     * @return bool True if the type is registered
     */
    public function is_known_type($type) {
        return array_key_exists($type, $this->types);
    }

    /**
     * Check to see if the module in question has registered to handle the
     * type given
     *
     * @param string $module The name of the module
     * @param string $type The identifier of the type
     * @return bool True if the module has registered to handle that type
     */
    public function has_type_handler($module, $type) {
        if (!$this->is_known_type($type)) {
            throw new coding_exception("Checking for handler for unknown type $type");
        }
        foreach ($this->types[$type]->handlers as $handler) {
            if ($handler->module == $module) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check to see if the module in question has registered to handle files
     * with the given extension (or to handle all file types)
     *
     * @param string $module The name of the module
     * @param string $extension The extension of the uploaded file
     * @return bool True if the module has registered to handle files with
     *              that extension (or to handle all file types)
     */
    public function has_file_handler($module, $extension) {
        foreach ($this->filehandlers as $handler) {
            if ($handler->module == $module) {
                if ($handler->extension == '*' || $handler->extension == $extension) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Gets a list of the file types that are handled by a particular module
     *
     * @param string $module The name of the module to check
     * @return array of file extensions or string '*'
     */
    public function get_handled_file_types($module) {
        $types = array();
        foreach ($this->filehandlers as $handler) {
            if ($handler->module == $module) {
                if ($handler->extension == '*') {
                    return '*';
                } else {
                    // Prepending '.' as otherwise mimeinfo fails.
                    $types[] = '.'.$handler->extension;
                }
            }
        }
        return $types;
    }

    /**
     * Returns an object to pass onto the javascript code with data about all the
     * registered file / type handlers
     *
     * @return object Data to pass on to Javascript code
     */
    public function get_js_data() {
        global $CFG;

        $ret = new stdClass;

        // Sort the types by priority.
        uasort($this->types, array($this, 'type_compare'));

        $ret->types = array();
        if (!empty($CFG->dndallowtextandlinks)) {
            foreach ($this->types as $type) {
                if (empty($type->handlers)) {
                    continue; // Skip any types without registered handlers.
                }
                $ret->types[] = $type;
            }
        }

        $ret->filehandlers = $this->filehandlers;
        $uploadrepo = repository::get_instances(array('type' => 'upload'));
        if (empty($uploadrepo)) {
            $ret->filehandlers = array(); // No upload repo => no file handlers.
        }

        return $ret;
    }

    /**
     * Comparison function used when sorting types by priority
     * @param object $type1 first type to compare
     * @param object $type2 second type to compare
     * @return integer -1 for $type1 < $type2; 1 for $type1 > $type2; 0 for equal
     */
    protected function type_compare($type1, $type2) {
        if ($type1->priority < $type2->priority) {
            return -1;
        }
        if ($type1->priority > $type2->priority) {
            return 1;
        }
        return 0;
    }

}

/**
 * Processes the upload, creating the course module and returning the result
 *
 * @package    core
 * @copyright  2012 Davo Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dndupload_ajax_processor {

    /** Returned when no error has occurred */
    const ERROR_OK = 0;

    /** @var object The course that we are uploading to */
    protected $course = null;

    /** @var context_course The course context for capability checking */
    protected $context = null;

    /** @var int The section number we are uploading to */
    protected $section = null;

    /** @var string The type of upload (e.g. 'Files', 'text/plain') */
    protected $type = null;

    /** @var object The details of the module type that will be created */
    protected $module= null;

    /** @var object The course module that has been created */
    protected $cm = null;

    /** @var dndupload_handler used to check the allowed file types */
    protected $dnduploadhandler = null;

    /** @var string The name to give the new activity instance */
    protected $displayname = null;

    /**
     * Set up some basic information needed to handle the upload
     *
     * @param int $courseid The ID of the course we are uploading to
     * @param int $section The section number we are uploading to
     * @param string $type The type of upload (as reported by the browser)
     * @param string $modulename The name of the module requested to handle this upload
     */
    public function __construct($courseid, $section, $type, $modulename) {
        global $DB;

        if (!defined('AJAX_SCRIPT')) {
            throw new coding_exception('dndupload_ajax_processor should only be used within AJAX requests');
        }

        $this->course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        require_login($this->course, false);
        $this->context = context_course::instance($this->course->id);

        if (!is_number($section) || $section < 0) {
            throw new coding_exception("Invalid section number $section");
        }
        $this->section = $section;
        $this->type = $type;

        if (!$this->module = $DB->get_record('modules', array('name' => $modulename))) {
            throw new coding_exception("Module $modulename does not exist");
        }

        $this->dnduploadhandler = new dndupload_handler($this->course);
    }

    /**
     * Check if this upload is a 'file' upload
     *
     * @return bool true if it is a 'file' upload, false otherwise
     */
    protected function is_file_upload() {
        return ($this->type == 'Files');
    }

    /**
     * Process the upload - creating the module in the course and returning the result to the browser
     *
     * @param string $displayname optional the name (from the browser) to give the course module instance
     * @param string $content optional the content of the upload (for non-file uploads)
     */
    public function process($displayname = null, $content = null) {
        require_capability('moodle/course:manageactivities', $this->context);

        if ($this->is_file_upload()) {
            require_capability('moodle/course:managefiles', $this->context);
            if ($content != null) {
                throw new moodle_exception('fileuploadwithcontent', 'moodle');
            }
        } else {
            if (empty($content)) {
                throw new moodle_exception('dnduploadwithoutcontent', 'moodle');
            }
        }

        require_sesskey();

        $this->displayname = $displayname;

        if ($this->is_file_upload()) {
            $this->handle_file_upload();
        } else {
            $this->handle_other_upload($content);
        }
    }

    /**
     * Handle uploads containing files - create the course module, ask the upload repository
     * to process the file, ask the mod to set itself up, then return the result to the browser
     */
    protected function handle_file_upload() {
        global $CFG;

        // Add the file to a draft file area.
        $draftitemid = file_get_unused_draft_itemid();
        $maxbytes = get_max_upload_file_size($CFG->maxbytes, $this->course->maxbytes);
        $types = $this->dnduploadhandler->get_handled_file_types($this->module->name);
        $repo = repository::get_instances(array('type' => 'upload'));
        if (empty($repo)) {
            throw new moodle_exception('errornouploadrepo', 'moodle');
        }
        $repo = reset($repo); // Get the first (and only) upload repo.
        $details = $repo->process_upload(null, $maxbytes, $types, '/', $draftitemid);
        if (empty($this->displayname)) {
            $this->displayname = $this->display_name_from_file($details['file']);
        }

        // Create a course module to hold the new instance.
        $this->create_course_module();

        // Ask the module to set itself up.
        $moduledata = $this->prepare_module_data($draftitemid);
        $instanceid = plugin_callback('mod', $this->module->name, 'dndupload', 'handle', array($moduledata), 'invalidfunction');
        if ($instanceid === 'invalidfunction') {
            throw new coding_exception("{$this->module->name} does not support drag and drop upload (missing {$this->module->name}_dndupload_handle function");
        }

        // Finish setting up the course module.
        $this->finish_setup_course_module($instanceid);
    }

    /**
     * Handle uploads not containing file - create the course module, ask the mod to
     * set itself up, then return the result to the browser
     *
     * @param string $content the content uploaded to the browser
     */
    protected function handle_other_upload($content) {
        // Check this plugin is registered to handle this type of upload
        if (!$this->dnduploadhandler->has_type_handler($this->module->name, $this->type)) {
            $info = (object)array('modname' => $this->module->name, 'type' => $this->type);
            throw new moodle_exception('moddoesnotsupporttype', 'moodle', $info);
        }

        // Create a course module to hold the new instance.
        $this->create_course_module();

        // Ask the module to set itself up.
        $moduledata = $this->prepare_module_data(null, $content);
        $instanceid = plugin_callback('mod', $this->module->name, 'dndupload', 'handle', array($moduledata), 'invalidfunction');
        if ($instanceid === 'invalidfunction') {
            throw new coding_exception("{$this->module->name} does not support drag and drop upload (missing {$this->module->name}_dndupload_handle function");
        }

        // Finish setting up the course module.
        $this->finish_setup_course_module($instanceid);
    }

    /**
     * Generate the name of the mod instance from the name of the file
     * (remove the extension and convert underscore => space
     *
     * @param string $filename the filename of the uploaded file
     * @return string the display name to use
     */
    protected function display_name_from_file($filename) {
        $pos = textlib::strrpos($filename, '.');
        if ($pos) { // Want to skip if $pos === 0 OR $pos === false.
            $filename = textlib::substr($filename, 0, $pos);
        }
        return str_replace('_', ' ', $filename);
    }

    /**
     * Create the coursemodule to hold the file/content that has been uploaded
     */
    protected function create_course_module() {
        if (!course_allowed_module($this->course, $this->module->name)) {
            throw new coding_exception("The module {$this->module->name} is not allowed to be added to this course");
        }

        $this->cm = new stdClass();
        $this->cm->course = $this->course->id;
        $this->cm->section = $this->section;
        $this->cm->module = $this->module->id;
        $this->cm->modulename = $this->module->name;
        $this->cm->instance = 0; // This will be filled in after we create the instance.
        $this->cm->visible = 1;
        $this->cm->groupmode = $this->course->groupmode;
        $this->cm->groupingid = $this->course->defaultgroupingid;

        if (!$this->cm->id = add_course_module($this->cm)) {
            throw new coding_exception("Unable to create the course module");
        }
        // The following are used inside some few core functions, so may as well set them here.
        $this->cm->coursemodule = $this->cm->id;
        $groupbuttons = ($this->course->groupmode or (!$this->course->groupmodeforce));
        if ($groupbuttons and plugin_supports('mod', $this->module->name, FEATURE_GROUPS, 0)) {
            $this->cm->groupmodelink = (!$this->course->groupmodeforce);
        } else {
            $this->cm->groupmodelink = false;
            $this->cm->groupmode = false;
        }
    }

    /**
     * Gather together all the details to pass on to the mod, so that it can initialise it's
     * own database tables
     *
     * @param int $draftitemid optional the id of the draft area containing the file (for file uploads)
     * @param string $content optional the content dropped onto the course (for non-file uploads)
     * @return object data to pass on to the mod, containing:
     *              string $type the 'type' as registered with dndupload_handler (or 'Files')
     *              object $course the course the upload was for
     *              int $draftitemid optional the id of the draft area containing the files
     *              int $coursemodule id of the course module that has already been created
     *              string $displayname the name to use for this activity (can be overriden by the mod)
     */
    protected function prepare_module_data($draftitemid = null, $content = null) {
        $data = new stdClass();
        $data->type = $this->type;
        $data->course = $this->course;
        if ($draftitemid) {
            $data->draftitemid = $draftitemid;
        } else if ($content) {
            $data->content = $content;
        }
        $data->coursemodule = $this->cm->id;
        $data->displayname = $this->displayname;
        return $data;
    }

    /**
     * Called after the mod has set itself up, to finish off any course module settings
     * (set instance id, add to correct section, set visibility, etc.) and send the response
     *
     * @param int $instanceid id returned by the mod when it was created
     */
    protected function finish_setup_course_module($instanceid) {
        global $DB, $USER;

        if (!$instanceid) {
            // Something has gone wrong - undo everything we can.
            delete_course_module($this->cm->id);
            throw new moodle_exception('errorcreatingactivity', 'moodle', '', $this->module->name);
        }

        $DB->set_field('course_modules', 'instance', $instanceid, array('id' => $this->cm->id));

        $sectionid = add_mod_to_section($this->cm);
        $DB->set_field('course_modules', 'section', $sectionid, array('id' => $this->cm->id));

        set_coursemodule_visible($this->cm->id, true);

        // Rebuild the course cache and retrieve the final info about this module.
        rebuild_course_cache($this->course->id, true);
        $this->course->modinfo = null; // Otherwise we will just get the old version back again.
        $info = get_fast_modinfo($this->course);
        if (!isset($info->cms[$this->cm->id])) {
            // The course module has not been properly created in the course - undo everything.
            delete_course_module($this->cm->id);
            throw new moodle_exception('errorcreatingactivity', 'moodle', '', $this->module->name);
        }
        $mod = $info->cms[$this->cm->id];
        $mod->groupmodelink = $this->cm->groupmodelink;
        $mod->groupmode = $this->cm->groupmode;

        // Trigger mod_created event with information about this module.
        $eventdata = new stdClass();
        $eventdata->modulename = $mod->modname;
        $eventdata->name       = $mod->name;
        $eventdata->cmid       = $mod->id;
        $eventdata->courseid   = $this->course->id;
        $eventdata->userid     = $USER->id;
        events_trigger('mod_created', $eventdata);

        add_to_log($this->course->id, "course", "add mod",
                   "../mod/{$mod->modname}/view.php?id=$mod->id",
                   "{$mod->modname} $instanceid");
        add_to_log($this->course->id, $mod->modname, "add",
                   "view.php?id=$mod->id",
                   "$instanceid", $mod->id);

        $this->send_response($mod);
    }

    /**
     * Send the details of the newly created activity back to the client browser
     *
     * @param cm_info $mod details of the mod just created
     */
    protected function send_response($mod) {
        global $OUTPUT;

        $resp = new stdClass();
        $resp->error = self::ERROR_OK;
        $resp->icon = $mod->get_icon_url()->out();
        $resp->name = $mod->name;
        $resp->link = $mod->get_url()->out();
        $resp->elementid = 'module-'.$mod->id;
        $resp->commands = make_editing_buttons($mod, true, true, 0, $mod->sectionnum);
        $resp->onclick = $mod->get_on_click();

        echo $OUTPUT->header();
        echo json_encode($resp);
        die();
    }
}
