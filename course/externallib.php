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
 * External course API
 *
 * @package    core_course
 * @category   external
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * Course external functions
 *
 * @package    core_course
 * @category   external
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.2
 */
class core_course_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function get_course_contents_parameters() {
        return new external_function_parameters(
                array('courseid' => new external_value(PARAM_INT, 'course id'),
                      'options' => new external_multiple_structure (
                              new external_single_structure(
                                    array('name' => new external_value(PARAM_ALPHANUM, 'option name'),
                                          'value' => new external_value(PARAM_RAW, 'the value of the option, this param is personaly validated in the external function.')
                              )
                      ), 'Options, not used yet, might be used in later version', VALUE_DEFAULT, array())
                )
        );
    }

    /**
     * Get course contents
     *
     * @param int $courseid course id
     * @param array $options These options are not used yet, might be used in later version
     * @return array
     * @since Moodle 2.2
     */
    public static function get_course_contents($courseid, $options = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        //validate parameter
        $params = self::validate_parameters(self::get_course_contents_parameters(),
                        array('courseid' => $courseid, 'options' => $options));

        //retrieve the course
        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);

        //check course format exist
        if (!file_exists($CFG->dirroot . '/course/format/' . $course->format . '/lib.php')) {
            throw new moodle_exception('cannotgetcoursecontents', 'webservice', '', null, get_string('courseformatnotfound', 'error', '', $course->format));
        } else {
            require_once($CFG->dirroot . '/course/format/' . $course->format . '/lib.php');
        }

        // now security checks
        $context = context_course::instance($course->id, IGNORE_MISSING);
        try {
            self::validate_context($context);
        } catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $course->id;
            throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
        }

        $canupdatecourse = has_capability('moodle/course:update', $context);

        //create return value
        $coursecontents = array();

        if ($canupdatecourse or $course->visible
                or has_capability('moodle/course:viewhiddencourses', $context)) {

            //retrieve sections
            $modinfo = get_fast_modinfo($course);
            $sections = $modinfo->get_section_info_all();

            //for each sections (first displayed to last displayed)
            $modinfosections = $modinfo->get_sections();
            foreach ($sections as $key => $section) {

                if (!$section->uservisible) {
                    continue;
                }

                // reset $sectioncontents
                $sectionvalues = array();
                $sectionvalues['id'] = $section->id;
                $sectionvalues['name'] = get_section_name($course, $section);
                $sectionvalues['visible'] = $section->visible;
                list($sectionvalues['summary'], $sectionvalues['summaryformat']) =
                        external_format_text($section->summary, $section->summaryformat,
                                $context->id, 'course', 'section', $section->id);
                $sectioncontents = array();

                //for each module of the section
                if (!empty($modinfosections[$section->section])) {
                    foreach ($modinfosections[$section->section] as $cmid) {
                        $cm = $modinfo->cms[$cmid];

                        // stop here if the module is not visible to the user
                        if (!$cm->uservisible) {
                            continue;
                        }

                        $module = array();

                        //common info (for people being able to see the module or availability dates)
                        $module['id'] = $cm->id;
                        $module['name'] = format_string($cm->name, true);
                        $module['modname'] = $cm->modname;
                        $module['modplural'] = $cm->modplural;
                        $module['modicon'] = $cm->get_icon_url()->out(false);
                        $module['indent'] = $cm->indent;

                        $modcontext = context_module::instance($cm->id);

                        if (!empty($cm->showdescription) or $cm->modname == 'label') {
                            // We want to use the external format. However from reading get_formatted_content(), get_content() format is always FORMAT_HTML.
                            list($module['description'], $descriptionformat) = external_format_text($cm->get_content(),
                                FORMAT_HTML, $modcontext->id, $cm->modname, 'intro', $cm->id);
                        }

                        //url of the module
                        $url = $cm->get_url();
                        if ($url) { //labels don't have url
                            $module['url'] = $cm->get_url()->out();
                        }

                        $canviewhidden = has_capability('moodle/course:viewhiddenactivities',
                                            context_module::instance($cm->id));
                        //user that can view hidden module should know about the visibility
                        $module['visible'] = $cm->visible;

                        //availability date (also send to user who can see hidden module when the showavailabilyt is ON)
                        if ($canupdatecourse or ($CFG->enableavailability && $canviewhidden && $cm->showavailability)) {
                            $module['availablefrom'] = $cm->availablefrom;
                            $module['availableuntil'] = $cm->availableuntil;
                        }

                        $baseurl = 'webservice/pluginfile.php';

                        //call $modulename_export_contents
                        //(each module callback take care about checking the capabilities)
                        require_once($CFG->dirroot . '/mod/' . $cm->modname . '/lib.php');
                        $getcontentfunction = $cm->modname.'_export_contents';
                        if (function_exists($getcontentfunction)) {
                            if ($contents = $getcontentfunction($cm, $baseurl)) {
                                $module['contents'] = $contents;
                            }
                        }

                        //assign result to $sectioncontents
                        $sectioncontents[] = $module;

                    }
                }
                $sectionvalues['modules'] = $sectioncontents;

                // assign result to $coursecontents
                $coursecontents[] = $sectionvalues;
            }
        }
        return $coursecontents;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_course_contents_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Section ID'),
                    'name' => new external_value(PARAM_TEXT, 'Section name'),
                    'visible' => new external_value(PARAM_INT, 'is the section visible', VALUE_OPTIONAL),
                    'summary' => new external_value(PARAM_RAW, 'Section description'),
                    'summaryformat' => new external_format_value('summary'),
                    'modules' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'activity id'),
                                    'url' => new external_value(PARAM_URL, 'activity url', VALUE_OPTIONAL),
                                    'name' => new external_value(PARAM_RAW, 'activity module name'),
                                    'description' => new external_value(PARAM_RAW, 'activity description', VALUE_OPTIONAL),
                                    'visible' => new external_value(PARAM_INT, 'is the module visible', VALUE_OPTIONAL),
                                    'modicon' => new external_value(PARAM_URL, 'activity icon url'),
                                    'modname' => new external_value(PARAM_PLUGIN, 'activity module type'),
                                    'modplural' => new external_value(PARAM_TEXT, 'activity module plural name'),
                                    'availablefrom' => new external_value(PARAM_INT, 'module availability start date', VALUE_OPTIONAL),
                                    'availableuntil' => new external_value(PARAM_INT, 'module availability en date', VALUE_OPTIONAL),
                                    'indent' => new external_value(PARAM_INT, 'number of identation in the site'),
                                    'contents' => new external_multiple_structure(
                                          new external_single_structure(
                                              array(
                                                  // content info
                                                  'type'=> new external_value(PARAM_TEXT, 'a file or a folder or external link'),
                                                  'filename'=> new external_value(PARAM_FILE, 'filename'),
                                                  'filepath'=> new external_value(PARAM_PATH, 'filepath'),
                                                  'filesize'=> new external_value(PARAM_INT, 'filesize'),
                                                  'fileurl' => new external_value(PARAM_URL, 'downloadable file url', VALUE_OPTIONAL),
                                                  'content' => new external_value(PARAM_RAW, 'Raw content, will be used when type is content', VALUE_OPTIONAL),
                                                  'timecreated' => new external_value(PARAM_INT, 'Time created'),
                                                  'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                                                  'sortorder' => new external_value(PARAM_INT, 'Content sort order'),

                                                  // copyright related info
                                                  'userid' => new external_value(PARAM_INT, 'User who added this content to moodle'),
                                                  'author' => new external_value(PARAM_TEXT, 'Content owner'),
                                                  'license' => new external_value(PARAM_TEXT, 'Content license'),
                                              )
                                          ), VALUE_DEFAULT, array()
                                      )
                                )
                            ), 'list of module'
                    )
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function get_courses_parameters() {
        return new external_function_parameters(
                array('options' => new external_single_structure(
                            array('ids' => new external_multiple_structure(
                                        new external_value(PARAM_INT, 'Course id')
                                        , 'List of course id. If empty return all courses
                                            except front page course.',
                                        VALUE_OPTIONAL)
                            ), 'options - operator OR is used', VALUE_DEFAULT, array())
                )
        );
    }

    /**
     * Get courses
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     * @since Moodle 2.2
     */
    public static function get_courses($options = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        //validate parameter
        $params = self::validate_parameters(self::get_courses_parameters(),
                        array('options' => $options));

        //retrieve courses
        if (!array_key_exists('ids', $params['options'])
                or empty($params['options']['ids'])) {
            $courses = $DB->get_records('course');
        } else {
            $courses = $DB->get_records_list('course', 'id', $params['options']['ids']);
        }

        //create return value
        $coursesinfo = array();
        foreach ($courses as $course) {

            // now security checks
            $context = context_course::instance($course->id, IGNORE_MISSING);
            $courseformatoptions = course_get_format($course)->get_format_options();
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $course->id;
                throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:view', $context);

            $courseinfo = array();
            $courseinfo['id'] = $course->id;
            $courseinfo['fullname'] = $course->fullname;
            $courseinfo['shortname'] = $course->shortname;
            $courseinfo['categoryid'] = $course->category;
            list($courseinfo['summary'], $courseinfo['summaryformat']) =
                external_format_text($course->summary, $course->summaryformat, $context->id, 'course', 'summary', 0);
            $courseinfo['format'] = $course->format;
            $courseinfo['startdate'] = $course->startdate;
            if (array_key_exists('numsections', $courseformatoptions)) {
                // For backward-compartibility
                $courseinfo['numsections'] = $courseformatoptions['numsections'];
            }

            //some field should be returned only if the user has update permission
            $courseadmin = has_capability('moodle/course:update', $context);
            if ($courseadmin) {
                $courseinfo['categorysortorder'] = $course->sortorder;
                $courseinfo['idnumber'] = $course->idnumber;
                $courseinfo['showgrades'] = $course->showgrades;
                $courseinfo['showreports'] = $course->showreports;
                $courseinfo['newsitems'] = $course->newsitems;
                $courseinfo['visible'] = $course->visible;
                $courseinfo['maxbytes'] = $course->maxbytes;
                if (array_key_exists('hiddensections', $courseformatoptions)) {
                    // For backward-compartibility
                    $courseinfo['hiddensections'] = $courseformatoptions['hiddensections'];
                }
                $courseinfo['groupmode'] = $course->groupmode;
                $courseinfo['groupmodeforce'] = $course->groupmodeforce;
                $courseinfo['defaultgroupingid'] = $course->defaultgroupingid;
                $courseinfo['lang'] = $course->lang;
                $courseinfo['timecreated'] = $course->timecreated;
                $courseinfo['timemodified'] = $course->timemodified;
                $courseinfo['forcetheme'] = $course->theme;
                $courseinfo['enablecompletion'] = $course->enablecompletion;
                $courseinfo['completionstartonenrol'] = $course->completionstartonenrol;
                $courseinfo['completionnotify'] = $course->completionnotify;
                $courseinfo['courseformatoptions'] = array();
                foreach ($courseformatoptions as $key => $value) {
                    $courseinfo['courseformatoptions'][] = array(
                        'name' => $key,
                        'value' => $value
                    );
                }
            }

            if ($courseadmin or $course->visible
                    or has_capability('moodle/course:viewhiddencourses', $context)) {
                $coursesinfo[] = $courseinfo;
            }
        }

        return $coursesinfo;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_courses_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'course id'),
                            'shortname' => new external_value(PARAM_TEXT, 'course short name'),
                            'categoryid' => new external_value(PARAM_INT, 'category id'),
                            'categorysortorder' => new external_value(PARAM_INT,
                                    'sort order into the category', VALUE_OPTIONAL),
                            'fullname' => new external_value(PARAM_TEXT, 'full name'),
                            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                            'summary' => new external_value(PARAM_RAW, 'summary'),
                            'summaryformat' => new external_format_value('summary'),
                            'format' => new external_value(PARAM_PLUGIN,
                                    'course format: weeks, topics, social, site,..'),
                            'showgrades' => new external_value(PARAM_INT,
                                    '1 if grades are shown, otherwise 0', VALUE_OPTIONAL),
                            'newsitems' => new external_value(PARAM_INT,
                                    'number of recent items appearing on the course page', VALUE_OPTIONAL),
                            'startdate' => new external_value(PARAM_INT,
                                    'timestamp when the course start'),
                            'numsections' => new external_value(PARAM_INT,
                                    '(deprecated, use courseformatoptions) number of weeks/topics',
                                    VALUE_OPTIONAL),
                            'maxbytes' => new external_value(PARAM_INT,
                                    'largest size of file that can be uploaded into the course',
                                    VALUE_OPTIONAL),
                            'showreports' => new external_value(PARAM_INT,
                                    'are activity report shown (yes = 1, no =0)', VALUE_OPTIONAL),
                            'visible' => new external_value(PARAM_INT,
                                    '1: available to student, 0:not available', VALUE_OPTIONAL),
                            'hiddensections' => new external_value(PARAM_INT,
                                    '(deprecated, use courseformatoptions) How the hidden sections in the course are displayed to students',
                                    VALUE_OPTIONAL),
                            'groupmode' => new external_value(PARAM_INT, 'no group, separate, visible',
                                    VALUE_OPTIONAL),
                            'groupmodeforce' => new external_value(PARAM_INT, '1: yes, 0: no',
                                    VALUE_OPTIONAL),
                            'defaultgroupingid' => new external_value(PARAM_INT, 'default grouping id',
                                    VALUE_OPTIONAL),
                            'timecreated' => new external_value(PARAM_INT,
                                    'timestamp when the course have been created', VALUE_OPTIONAL),
                            'timemodified' => new external_value(PARAM_INT,
                                    'timestamp when the course have been modified', VALUE_OPTIONAL),
                            'enablecompletion' => new external_value(PARAM_INT,
                                    'Enabled, control via completion and activity settings. Disbaled,
                                        not shown in activity settings.',
                                    VALUE_OPTIONAL),
                            'completionstartonenrol' => new external_value(PARAM_INT,
                                    '1: begin tracking a student\'s progress in course completion
                                        after course enrolment. 0: does not',
                                    VALUE_OPTIONAL),
                            'completionnotify' => new external_value(PARAM_INT,
                                    '1: yes 0: no', VALUE_OPTIONAL),
                            'lang' => new external_value(PARAM_SAFEDIR,
                                    'forced course language', VALUE_OPTIONAL),
                            'forcetheme' => new external_value(PARAM_PLUGIN,
                                    'name of the force theme', VALUE_OPTIONAL),
                            'courseformatoptions' => new external_multiple_structure(
                                new external_single_structure(
                                    array('name' => new external_value(PARAM_ALPHANUMEXT, 'course format option name'),
                                        'value' => new external_value(PARAM_RAW, 'course format option value')
                                )),
                                    'additional options for particular course format', VALUE_OPTIONAL
                             ),
                        ), 'course'
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function create_courses_parameters() {
        $courseconfig = get_config('moodlecourse'); //needed for many default values
        return new external_function_parameters(
            array(
                'courses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'fullname' => new external_value(PARAM_TEXT, 'full name'),
                            'shortname' => new external_value(PARAM_TEXT, 'course short name'),
                            'categoryid' => new external_value(PARAM_INT, 'category id'),
                            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                            'summary' => new external_value(PARAM_RAW, 'summary', VALUE_OPTIONAL),
                            'summaryformat' => new external_format_value('summary', VALUE_DEFAULT),
                            'format' => new external_value(PARAM_PLUGIN,
                                    'course format: weeks, topics, social, site,..',
                                    VALUE_DEFAULT, $courseconfig->format),
                            'showgrades' => new external_value(PARAM_INT,
                                    '1 if grades are shown, otherwise 0', VALUE_DEFAULT,
                                    $courseconfig->showgrades),
                            'newsitems' => new external_value(PARAM_INT,
                                    'number of recent items appearing on the course page',
                                    VALUE_DEFAULT, $courseconfig->newsitems),
                            'startdate' => new external_value(PARAM_INT,
                                    'timestamp when the course start', VALUE_OPTIONAL),
                            'numsections' => new external_value(PARAM_INT,
                                    '(deprecated, use courseformatoptions) number of weeks/topics',
                                    VALUE_OPTIONAL),
                            'maxbytes' => new external_value(PARAM_INT,
                                    'largest size of file that can be uploaded into the course',
                                    VALUE_DEFAULT, $courseconfig->maxbytes),
                            'showreports' => new external_value(PARAM_INT,
                                    'are activity report shown (yes = 1, no =0)', VALUE_DEFAULT,
                                    $courseconfig->showreports),
                            'visible' => new external_value(PARAM_INT,
                                    '1: available to student, 0:not available', VALUE_OPTIONAL),
                            'hiddensections' => new external_value(PARAM_INT,
                                    '(deprecated, use courseformatoptions) How the hidden sections in the course are displayed to students',
                                    VALUE_OPTIONAL),
                            'groupmode' => new external_value(PARAM_INT, 'no group, separate, visible',
                                    VALUE_DEFAULT, $courseconfig->groupmode),
                            'groupmodeforce' => new external_value(PARAM_INT, '1: yes, 0: no',
                                    VALUE_DEFAULT, $courseconfig->groupmodeforce),
                            'defaultgroupingid' => new external_value(PARAM_INT, 'default grouping id',
                                    VALUE_DEFAULT, 0),
                            'enablecompletion' => new external_value(PARAM_INT,
                                    'Enabled, control via completion and activity settings. Disabled,
                                        not shown in activity settings.',
                                    VALUE_OPTIONAL),
                            'completionstartonenrol' => new external_value(PARAM_INT,
                                    '1: begin tracking a student\'s progress in course completion after
                                        course enrolment. 0: does not',
                                    VALUE_OPTIONAL),
                            'completionnotify' => new external_value(PARAM_INT,
                                    '1: yes 0: no', VALUE_OPTIONAL),
                            'lang' => new external_value(PARAM_SAFEDIR,
                                    'forced course language', VALUE_OPTIONAL),
                            'forcetheme' => new external_value(PARAM_PLUGIN,
                                    'name of the force theme', VALUE_OPTIONAL),
                            'courseformatoptions' => new external_multiple_structure(
                                new external_single_structure(
                                    array('name' => new external_value(PARAM_ALPHANUMEXT, 'course format option name'),
                                        'value' => new external_value(PARAM_RAW, 'course format option value')
                                )),
                                    'additional options for particular course format', VALUE_OPTIONAL),
                        )
                    ), 'courses to create'
                )
            )
        );
    }

    /**
     * Create  courses
     *
     * @param array $courses
     * @return array courses (id and shortname only)
     * @since Moodle 2.2
     */
    public static function create_courses($courses) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');

        $params = self::validate_parameters(self::create_courses_parameters(),
                        array('courses' => $courses));

        $availablethemes = get_plugin_list('theme');
        $availablelangs = get_string_manager()->get_list_of_translations();

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['courses'] as $course) {

            // Ensure the current user is allowed to run this function
            $context = context_coursecat::instance($course['categoryid'], IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->catid = $course['categoryid'];
                throw new moodle_exception('errorcatcontextnotvalid', 'webservice', '', $exceptionparam);
            }
            require_capability('moodle/course:create', $context);

            // Make sure lang is valid
            if (array_key_exists('lang', $course) and empty($availablelangs[$course['lang']])) {
                throw new moodle_exception('errorinvalidparam', 'webservice', '', 'lang');
            }

            // Make sure theme is valid
            if (array_key_exists('forcetheme', $course)) {
                if (!empty($CFG->allowcoursethemes)) {
                    if (empty($availablethemes[$course['forcetheme']])) {
                        throw new moodle_exception('errorinvalidparam', 'webservice', '', 'forcetheme');
                    } else {
                        $course['theme'] = $course['forcetheme'];
                    }
                }
            }

            //force visibility if ws user doesn't have the permission to set it
            $category = $DB->get_record('course_categories', array('id' => $course['categoryid']));
            if (!has_capability('moodle/course:visibility', $context)) {
                $course['visible'] = $category->visible;
            }

            //set default value for completion
            $courseconfig = get_config('moodlecourse');
            if (completion_info::is_enabled_for_site()) {
                if (!array_key_exists('enablecompletion', $course)) {
                    $course['enablecompletion'] = $courseconfig->enablecompletion;
                }
                if (!array_key_exists('completionstartonenrol', $course)) {
                    $course['completionstartonenrol'] = $courseconfig->completionstartonenrol;
                }
            } else {
                $course['enablecompletion'] = 0;
                $course['completionstartonenrol'] = 0;
            }

            $course['category'] = $course['categoryid'];

            // Summary format.
            $course['summaryformat'] = external_validate_format($course['summaryformat']);

            if (!empty($course['courseformatoptions'])) {
                foreach ($course['courseformatoptions'] as $option) {
                    $course[$option['name']] = $option['value'];
                }
            }

            //Note: create_course() core function check shortname, idnumber, category
            $course['id'] = create_course((object) $course)->id;

            $resultcourses[] = array('id' => $course['id'], 'shortname' => $course['shortname']);
        }

        $transaction->allow_commit();

        return $resultcourses;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function create_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'course id'),
                    'shortname' => new external_value(PARAM_TEXT, 'short name'),
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function delete_courses_parameters() {
        return new external_function_parameters(
            array(
                'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course ID')),
            )
        );
    }

    /**
     * Delete courses
     *
     * @param array $courseids A list of course ids
     * @since Moodle 2.2
     */
    public static function delete_courses($courseids) {
        global $CFG, $DB;
        require_once($CFG->dirroot."/course/lib.php");

        // Parameter validation.
        $params = self::validate_parameters(self::delete_courses_parameters(), array('courseids'=>$courseids));

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['courseids'] as $courseid) {
            $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

            // Check if the context is valid.
            $coursecontext = context_course::instance($course->id);
            self::validate_context($coursecontext);

            // Check if the current user has enought permissions.
            if (!can_delete_course($courseid)) {
                throw new moodle_exception('cannotdeletecategorycourse', 'error',
                    '', format_string($course->fullname)." (id: $courseid)");
            }

            delete_course($course, false);
        }

        $transaction->allow_commit();

        return null;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function delete_courses_returns() {
        return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function duplicate_course_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course to duplicate id'),
                'fullname' => new external_value(PARAM_TEXT, 'duplicated course full name'),
                'shortname' => new external_value(PARAM_TEXT, 'duplicated course short name'),
                'categoryid' => new external_value(PARAM_INT, 'duplicated course category parent'),
                'visible' => new external_value(PARAM_INT, 'duplicated course visible, default to yes', VALUE_DEFAULT, 1),
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                                'name' => new external_value(PARAM_ALPHAEXT, 'The backup option name:
                                            "activities" (int) Include course activites (default to 1 that is equal to yes),
                                            "blocks" (int) Include course blocks (default to 1 that is equal to yes),
                                            "filters" (int) Include course filters  (default to 1 that is equal to yes),
                                            "users" (int) Include users (default to 0 that is equal to no),
                                            "role_assignments" (int) Include role assignments  (default to 0 that is equal to no),
                                            "comments" (int) Include user comments  (default to 0 that is equal to no),
                                            "completion_information" (int) Include user course completion information  (default to 0 that is equal to no),
                                            "logs" (int) Include course logs  (default to 0 that is equal to no),
                                            "histories" (int) Include histories  (default to 0 that is equal to no)'
                                            ),
                                'value' => new external_value(PARAM_RAW, 'the value for the option 1 (yes) or 0 (no)'
                            )
                        )
                    ), VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Duplicate a course
     *
     * @param int $courseid
     * @param string $fullname Duplicated course fullname
     * @param string $shortname Duplicated course shortname
     * @param int $categoryid Duplicated course parent category id
     * @param int $visible Duplicated course availability
     * @param array $options List of backup options
     * @return array New course info
     * @since Moodle 2.3
     */
    public static function duplicate_course($courseid, $fullname, $shortname, $categoryid, $visible = 1, $options = array()) {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Parameter validation.
        $params = self::validate_parameters(
                self::duplicate_course_parameters(),
                array(
                      'courseid' => $courseid,
                      'fullname' => $fullname,
                      'shortname' => $shortname,
                      'categoryid' => $categoryid,
                      'visible' => $visible,
                      'options' => $options
                )
        );

        // Context validation.

        if (! ($course = $DB->get_record('course', array('id'=>$params['courseid'])))) {
            throw new moodle_exception('invalidcourseid', 'error');
        }

        // Category where duplicated course is going to be created.
        $categorycontext = context_coursecat::instance($params['categoryid']);
        self::validate_context($categorycontext);

        // Course to be duplicated.
        $coursecontext = context_course::instance($course->id);
        self::validate_context($coursecontext);

        $backupdefaults = array(
            'activities' => 1,
            'blocks' => 1,
            'filters' => 1,
            'users' => 0,
            'role_assignments' => 0,
            'comments' => 0,
            'completion_information' => 0,
            'logs' => 0,
            'histories' => 0
        );

        $backupsettings = array();
        // Check for backup and restore options.
        if (!empty($params['options'])) {
            foreach ($params['options'] as $option) {

                // Strict check for a correct value (allways 1 or 0, true or false).
                $value = clean_param($option['value'], PARAM_INT);

                if ($value !== 0 and $value !== 1) {
                    throw new moodle_exception('invalidextparam', 'webservice', '', $option['name']);
                }

                if (!isset($backupdefaults[$option['name']])) {
                    throw new moodle_exception('invalidextparam', 'webservice', '', $option['name']);
                }

                $backupsettings[$option['name']] = $value;
            }
        }

        // Capability checking.

        // The backup controller check for this currently, this may be redundant.
        require_capability('moodle/course:create', $categorycontext);
        require_capability('moodle/restore:restorecourse', $categorycontext);
        require_capability('moodle/backup:backupcourse', $coursecontext);

        if (!empty($backupsettings['users'])) {
            require_capability('moodle/backup:userinfo', $coursecontext);
            require_capability('moodle/restore:userinfo', $categorycontext);
        }

        // Check if the shortname is used.
        if ($foundcourses = $DB->get_records('course', array('shortname'=>$shortname))) {
            foreach ($foundcourses as $foundcourse) {
                $foundcoursenames[] = $foundcourse->fullname;
            }

            $foundcoursenamestring = implode(',', $foundcoursenames);
            throw new moodle_exception('shortnametaken', '', '', $foundcoursenamestring);
        }

        // Backup the course.

        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $USER->id);

        foreach ($backupsettings as $name => $value) {
            $bc->get_plan()->get_setting($name)->set_value($value);
        }

        $backupid       = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();

        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        $bc->destroy();

        // Restore the backup immediately.

        // Check if we need to unzip the file because the backup temp dir does not contains backup files.
        if (!file_exists($backupbasepath . "/moodle_backup.xml")) {
            $file->extract_to_pathname(get_file_packer(), $backupbasepath);
        }

        // Create new course.
        $newcourseid = restore_dbops::create_new_course($params['fullname'], $params['shortname'], $params['categoryid']);

        $rc = new restore_controller($backupid, $newcourseid,
                backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $USER->id, backup::TARGET_NEW_COURSE);

        foreach ($backupsettings as $name => $value) {
            $setting = $rc->get_plan()->get_setting($name);
            if ($setting->get_status() == backup_setting::NOT_LOCKED) {
                $setting->set_value($value);
            }
        }

        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }

                $errorinfo = '';

                foreach ($precheckresults['errors'] as $error) {
                    $errorinfo .= $error;
                }

                if (array_key_exists('warnings', $precheckresults)) {
                    foreach ($precheckresults['warnings'] as $warning) {
                        $errorinfo .= $warning;
                    }
                }

                throw new moodle_exception('backupprecheckerrors', 'webservice', '', $errorinfo);
            }
        }

        $rc->execute_plan();
        $rc->destroy();

        $course = $DB->get_record('course', array('id' => $newcourseid), '*', MUST_EXIST);
        $course->fullname = $params['fullname'];
        $course->shortname = $params['shortname'];
        $course->visible = $params['visible'];

        // Set shortname and fullname back.
        $DB->update_record('course', $course);

        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupbasepath);
        }

        // Delete the course backup file created by this WebService. Originally located in the course backups area.
        $file->delete();

        return array('id' => $course->id, 'shortname' => $course->shortname);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function duplicate_course_returns() {
        return new external_single_structure(
            array(
                'id'       => new external_value(PARAM_INT, 'course id'),
                'shortname' => new external_value(PARAM_TEXT, 'short name'),
            )
        );
    }

    /**
     * Returns description of method parameters for import_course
     *
     * @return external_function_parameters
     * @since Moodle 2.4
     */
    public static function import_course_parameters() {
        return new external_function_parameters(
            array(
                'importfrom' => new external_value(PARAM_INT, 'the id of the course we are importing from'),
                'importto' => new external_value(PARAM_INT, 'the id of the course we are importing to'),
                'deletecontent' => new external_value(PARAM_INT, 'whether to delete the course content where we are importing to (default to 0 = No)', VALUE_DEFAULT, 0),
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                                'name' => new external_value(PARAM_ALPHA, 'The backup option name:
                                            "activities" (int) Include course activites (default to 1 that is equal to yes),
                                            "blocks" (int) Include course blocks (default to 1 that is equal to yes),
                                            "filters" (int) Include course filters  (default to 1 that is equal to yes)'
                                            ),
                                'value' => new external_value(PARAM_RAW, 'the value for the option 1 (yes) or 0 (no)'
                            )
                        )
                    ), VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Imports a course
     *
     * @param int $importfrom The id of the course we are importing from
     * @param int $importto The id of the course we are importing to
     * @param bool $deletecontent Whether to delete the course we are importing to content
     * @param array $options List of backup options
     * @return null
     * @since Moodle 2.4
     */
    public static function import_course($importfrom, $importto, $deletecontent = 0, $options = array()) {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Parameter validation.
        $params = self::validate_parameters(
            self::import_course_parameters(),
            array(
                'importfrom' => $importfrom,
                'importto' => $importto,
                'deletecontent' => $deletecontent,
                'options' => $options
            )
        );

        if ($params['deletecontent'] !== 0 and $params['deletecontent'] !== 1) {
            throw new moodle_exception('invalidextparam', 'webservice', '', $option['deletecontent']);
        }

        // Context validation.

        if (! ($importfrom = $DB->get_record('course', array('id'=>$params['importfrom'])))) {
            throw new moodle_exception('invalidcourseid', 'error');
        }

        if (! ($importto = $DB->get_record('course', array('id'=>$params['importto'])))) {
            throw new moodle_exception('invalidcourseid', 'error');
        }

        $importfromcontext = context_course::instance($importfrom->id);
        self::validate_context($importfromcontext);

        $importtocontext = context_course::instance($importto->id);
        self::validate_context($importtocontext);

        $backupdefaults = array(
            'activities' => 1,
            'blocks' => 1,
            'filters' => 1
        );

        $backupsettings = array();

        // Check for backup and restore options.
        if (!empty($params['options'])) {
            foreach ($params['options'] as $option) {

                // Strict check for a correct value (allways 1 or 0, true or false).
                $value = clean_param($option['value'], PARAM_INT);

                if ($value !== 0 and $value !== 1) {
                    throw new moodle_exception('invalidextparam', 'webservice', '', $option['name']);
                }

                if (!isset($backupdefaults[$option['name']])) {
                    throw new moodle_exception('invalidextparam', 'webservice', '', $option['name']);
                }

                $backupsettings[$option['name']] = $value;
            }
        }

        // Capability checking.

        require_capability('moodle/backup:backuptargetimport', $importfromcontext);
        require_capability('moodle/restore:restoretargetimport', $importtocontext);

        $bc = new backup_controller(backup::TYPE_1COURSE, $importfrom->id, backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);

        foreach ($backupsettings as $name => $value) {
            $bc->get_plan()->get_setting($name)->set_value($value);
        }

        $backupid       = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();

        $bc->execute_plan();
        $bc->destroy();

        // Restore the backup immediately.

        // Check if we must delete the contents of the destination course.
        if ($params['deletecontent']) {
            $restoretarget = backup::TARGET_EXISTING_DELETING;
        } else {
            $restoretarget = backup::TARGET_EXISTING_ADDING;
        }

        $rc = new restore_controller($backupid, $importto->id,
                backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, $restoretarget);

        foreach ($backupsettings as $name => $value) {
            $rc->get_plan()->get_setting($name)->set_value($value);
        }

        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }

                $errorinfo = '';

                foreach ($precheckresults['errors'] as $error) {
                    $errorinfo .= $error;
                }

                if (array_key_exists('warnings', $precheckresults)) {
                    foreach ($precheckresults['warnings'] as $warning) {
                        $errorinfo .= $warning;
                    }
                }

                throw new moodle_exception('backupprecheckerrors', 'webservice', '', $errorinfo);
            }
        } else {
            if ($restoretarget == backup::TARGET_EXISTING_DELETING) {
                restore_dbops::delete_course_content($importto->id);
            }
        }

        $rc->execute_plan();
        $rc->destroy();

        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupbasepath);
        }

        return null;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.4
     */
    public static function import_course_returns() {
        return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function get_categories_parameters() {
        return new external_function_parameters(
            array(
                'criteria' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'key' => new external_value(PARAM_ALPHA,
                                         'The category column to search, expected keys (value format) are:'.
                                         '"id" (int) the category id,'.
                                         '"name" (string) the category name,'.
                                         '"parent" (int) the parent category id,'.
                                         '"idnumber" (string) category idnumber'.
                                         ' - user must have \'moodle/category:manage\' to search on idnumber,'.
                                         '"visible" (int) whether the returned categories must be visible or hidden. If the key is not passed,
                                             then the function return all categories that the user can see.'.
                                         ' - user must have \'moodle/category:manage\' or \'moodle/category:viewhiddencategories\' to search on visible,'.
                                         '"theme" (string) only return the categories having this theme'.
                                         ' - user must have \'moodle/category:manage\' to search on theme'),
                            'value' => new external_value(PARAM_RAW, 'the value to match')
                        )
                    ), 'criteria', VALUE_DEFAULT, array()
                ),
                'addsubcategories' => new external_value(PARAM_BOOL, 'return the sub categories infos
                                          (1 - default) otherwise only the category info (0)', VALUE_DEFAULT, 1)
            )
        );
    }

    /**
     * Get categories
     *
     * @param array $criteria Criteria to match the results
     * @param booln $addsubcategories obtain only the category (false) or its subcategories (true - default)
     * @return array list of categories
     * @since Moodle 2.3
     */
    public static function get_categories($criteria = array(), $addsubcategories = true) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        // Validate parameters.
        $params = self::validate_parameters(self::get_categories_parameters(),
                array('criteria' => $criteria, 'addsubcategories' => $addsubcategories));

        // Retrieve the categories.
        $categories = array();
        if (!empty($params['criteria'])) {

            $conditions = array();
            $wheres = array();
            foreach ($params['criteria'] as $crit) {
                $key = trim($crit['key']);

                // Trying to avoid duplicate keys.
                if (!isset($conditions[$key])) {

                    $context = context_system::instance();
                    $value = null;
                    switch ($key) {
                        case 'id':
                            $value = clean_param($crit['value'], PARAM_INT);
                            break;

                        case 'idnumber':
                            if (has_capability('moodle/category:manage', $context)) {
                                $value = clean_param($crit['value'], PARAM_RAW);
                            } else {
                                // We must throw an exception.
                                // Otherwise the dev client would think no idnumber exists.
                                throw new moodle_exception('criteriaerror',
                                        'webservice', '', null,
                                        'You don\'t have the permissions to search on the "idnumber" field.');
                            }
                            break;

                        case 'name':
                            $value = clean_param($crit['value'], PARAM_TEXT);
                            break;

                        case 'parent':
                            $value = clean_param($crit['value'], PARAM_INT);
                            break;

                        case 'visible':
                            if (has_capability('moodle/category:manage', $context)
                                or has_capability('moodle/category:viewhiddencategories',
                                        context_system::instance())) {
                                $value = clean_param($crit['value'], PARAM_INT);
                            } else {
                                throw new moodle_exception('criteriaerror',
                                        'webservice', '', null,
                                        'You don\'t have the permissions to search on the "visible" field.');
                            }
                            break;

                        case 'theme':
                            if (has_capability('moodle/category:manage', $context)) {
                                $value = clean_param($crit['value'], PARAM_THEME);
                            } else {
                                throw new moodle_exception('criteriaerror',
                                        'webservice', '', null,
                                        'You don\'t have the permissions to search on the "theme" field.');
                            }
                            break;

                        default:
                            throw new moodle_exception('criteriaerror',
                                    'webservice', '', null,
                                    'You can not search on this criteria: ' . $key);
                    }

                    if (isset($value)) {
                        $conditions[$key] = $crit['value'];
                        $wheres[] = $key . " = :" . $key;
                    }
                }
            }

            if (!empty($wheres)) {
                $wheres = implode(" AND ", $wheres);

                $categories = $DB->get_records_select('course_categories', $wheres, $conditions);

                // Retrieve its sub subcategories (all levels).
                if ($categories and !empty($params['addsubcategories'])) {
                    $newcategories = array();

                    // Check if we required visible/theme checks.
                    $additionalselect = '';
                    $additionalparams = array();
                    if (isset($conditions['visible'])) {
                        $additionalselect .= ' AND visible = :visible';
                        $additionalparams['visible'] = $conditions['visible'];
                    }
                    if (isset($conditions['theme'])) {
                        $additionalselect .= ' AND theme= :theme';
                        $additionalparams['theme'] = $conditions['theme'];
                    }

                    foreach ($categories as $category) {
                        $sqlselect = $DB->sql_like('path', ':path') . $additionalselect;
                        $sqlparams = array('path' => $category->path.'/%') + $additionalparams; // It will NOT include the specified category.
                        $subcategories = $DB->get_records_select('course_categories', $sqlselect, $sqlparams);
                        $newcategories = $newcategories + $subcategories;   // Both arrays have integer as keys.
                    }
                    $categories = $categories + $newcategories;
                }
            }

        } else {
            // Retrieve all categories in the database.
            $categories = $DB->get_records('course_categories');
        }

        // The not returned categories. key => category id, value => reason of exclusion.
        $excludedcats = array();

        // The returned categories.
        $categoriesinfo = array();

        // We need to sort the categories by path.
        // The parent cats need to be checked by the algo first.
        usort($categories, "core_course_external::compare_categories_by_path");

        foreach ($categories as $category) {

            // Check if the category is a child of an excluded category, if yes exclude it too (excluded => do not return).
            $parents = explode('/', $category->path);
            unset($parents[0]); // First key is always empty because path start with / => /1/2/4.
            foreach ($parents as $parentid) {
                // Note: when the parent exclusion was due to the context,
                // the sub category could still be returned.
                if (isset($excludedcats[$parentid]) and $excludedcats[$parentid] != 'context') {
                    $excludedcats[$category->id] = 'parent';
                }
            }

            // Check category depth is <= maxdepth (do not check for user who can manage categories).
            if ((!empty($CFG->maxcategorydepth) && count($parents) > $CFG->maxcategorydepth)
                    and !has_capability('moodle/category:manage', $context)) {
                $excludedcats[$category->id] = 'depth';
            }

            // Check the user can use the category context.
            $context = context_coursecat::instance($category->id);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $excludedcats[$category->id] = 'context';

                // If it was the requested category then throw an exception.
                if (isset($params['categoryid']) && $category->id == $params['categoryid']) {
                    $exceptionparam = new stdClass();
                    $exceptionparam->message = $e->getMessage();
                    $exceptionparam->catid = $category->id;
                    throw new moodle_exception('errorcatcontextnotvalid', 'webservice', '', $exceptionparam);
                }
            }

            // Return the category information.
            if (!isset($excludedcats[$category->id])) {

                // Final check to see if the category is visible to the user.
                if ($category->visible
                        or has_capability('moodle/category:viewhiddencategories', context_system::instance())
                        or has_capability('moodle/category:manage', $context)) {

                    $categoryinfo = array();
                    $categoryinfo['id'] = $category->id;
                    $categoryinfo['name'] = $category->name;
                    list($categoryinfo['description'], $categoryinfo['descriptionformat']) =
                        external_format_text($category->description, $category->descriptionformat,
                                $context->id, 'coursecat', 'description', null);
                    $categoryinfo['parent'] = $category->parent;
                    $categoryinfo['sortorder'] = $category->sortorder;
                    $categoryinfo['coursecount'] = $category->coursecount;
                    $categoryinfo['depth'] = $category->depth;
                    $categoryinfo['path'] = $category->path;

                    // Some fields only returned for admin.
                    if (has_capability('moodle/category:manage', $context)) {
                        $categoryinfo['idnumber'] = $category->idnumber;
                        $categoryinfo['visible'] = $category->visible;
                        $categoryinfo['visibleold'] = $category->visibleold;
                        $categoryinfo['timemodified'] = $category->timemodified;
                        $categoryinfo['theme'] = $category->theme;
                    }

                    $categoriesinfo[] = $categoryinfo;
                } else {
                    $excludedcats[$category->id] = 'visibility';
                }
            }
        }

        // Sorting the resulting array so it looks a bit better for the client developer.
        usort($categoriesinfo, "core_course_external::compare_categories_by_sortorder");

        return $categoriesinfo;
    }

    /**
     * Sort categories array by path
     * private function: only used by get_categories
     *
     * @param array $category1
     * @param array $category2
     * @return int result of strcmp
     * @since Moodle 2.3
     */
    private static function compare_categories_by_path($category1, $category2) {
        return strcmp($category1->path, $category2->path);
    }

    /**
     * Sort categories array by sortorder
     * private function: only used by get_categories
     *
     * @param array $category1
     * @param array $category2
     * @return int result of strcmp
     * @since Moodle 2.3
     */
    private static function compare_categories_by_sortorder($category1, $category2) {
        return strcmp($category1['sortorder'], $category2['sortorder']);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function get_categories_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'category id'),
                    'name' => new external_value(PARAM_TEXT, 'category name'),
                    'idnumber' => new external_value(PARAM_RAW, 'category id number', VALUE_OPTIONAL),
                    'description' => new external_value(PARAM_RAW, 'category description'),
                    'descriptionformat' => new external_format_value('description'),
                    'parent' => new external_value(PARAM_INT, 'parent category id'),
                    'sortorder' => new external_value(PARAM_INT, 'category sorting order'),
                    'coursecount' => new external_value(PARAM_INT, 'number of courses in this category'),
                    'visible' => new external_value(PARAM_INT, '1: available, 0:not available', VALUE_OPTIONAL),
                    'visibleold' => new external_value(PARAM_INT, '1: available, 0:not available', VALUE_OPTIONAL),
                    'timemodified' => new external_value(PARAM_INT, 'timestamp', VALUE_OPTIONAL),
                    'depth' => new external_value(PARAM_INT, 'category depth'),
                    'path' => new external_value(PARAM_TEXT, 'category path'),
                    'theme' => new external_value(PARAM_THEME, 'category theme', VALUE_OPTIONAL),
                ), 'List of categories'
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function create_categories_parameters() {
        return new external_function_parameters(
            array(
                'categories' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'name' => new external_value(PARAM_TEXT, 'new category name'),
                                'parent' => new external_value(PARAM_INT,
                                        'the parent category id inside which the new category will be created
                                         - set to 0 for a root category',
                                        VALUE_DEFAULT, 0),
                                'idnumber' => new external_value(PARAM_RAW,
                                        'the new category idnumber', VALUE_OPTIONAL),
                                'description' => new external_value(PARAM_RAW,
                                        'the new category description', VALUE_OPTIONAL),
                                'descriptionformat' => new external_format_value('description', VALUE_DEFAULT),
                                'theme' => new external_value(PARAM_THEME,
                                        'the new category theme. This option must be enabled on moodle',
                                        VALUE_OPTIONAL),
                        )
                    )
                )
            )
        );
    }

    /**
     * Create categories
     *
     * @param array $categories - see create_categories_parameters() for the array structure
     * @return array - see create_categories_returns() for the array structure
     * @since Moodle 2.3
     */
    public static function create_categories($categories) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        $params = self::validate_parameters(self::create_categories_parameters(),
                        array('categories' => $categories));

        $transaction = $DB->start_delegated_transaction();

        $createdcategories = array();
        foreach ($params['categories'] as $category) {
            if ($category['parent']) {
                if (!$DB->record_exists('course_categories', array('id' => $category['parent']))) {
                    throw new moodle_exception('unknowcategory');
                }
                $context = context_coursecat::instance($category['parent']);
            } else {
                $context = context_system::instance();
            }
            self::validate_context($context);
            require_capability('moodle/category:manage', $context);

            // Check name.
            if (textlib::strlen($category['name'])>255) {
                throw new moodle_exception('categorytoolong');
            }

            $newcategory = new stdClass();
            $newcategory->name = $category['name'];
            $newcategory->parent = $category['parent'];
            // Format the description.
            if (!empty($category['description'])) {
                $newcategory->description = $category['description'];
            }
            $newcategory->descriptionformat = external_validate_format($category['descriptionformat']);
            if (isset($category['theme']) and !empty($CFG->allowcategorythemes)) {
                $newcategory->theme = $category['theme'];
            }
            // Check id number.
            if (!empty($category['idnumber'])) { // Same as in course/editcategory_form.php .
                if (textlib::strlen($category['idnumber'])>100) {
                    throw new moodle_exception('idnumbertoolong');
                }
                if ($existing = $DB->get_record('course_categories', array('idnumber' => $category['idnumber']))) {
                    if ($existing->id) {
                        throw new moodle_exception('idnumbertaken');
                    }
                }
                $newcategory->idnumber = $category['idnumber'];
            }

            $newcategory = create_course_category($newcategory);
            // Populate special fields.
            fix_course_sortorder();

            $createdcategories[] = array('id' => $newcategory->id, 'name' => $newcategory->name);
        }

        $transaction->allow_commit();

        return $createdcategories;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function create_categories_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'new category id'),
                    'name' => new external_value(PARAM_TEXT, 'new category name'),
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function update_categories_parameters() {
        return new external_function_parameters(
            array(
                'categories' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'       => new external_value(PARAM_INT, 'course id'),
                            'name' => new external_value(PARAM_TEXT, 'category name', VALUE_OPTIONAL),
                            'idnumber' => new external_value(PARAM_RAW, 'category id number', VALUE_OPTIONAL),
                            'parent' => new external_value(PARAM_INT, 'parent category id', VALUE_OPTIONAL),
                            'description' => new external_value(PARAM_RAW, 'category description', VALUE_OPTIONAL),
                            'descriptionformat' => new external_format_value('description', VALUE_DEFAULT),
                            'theme' => new external_value(PARAM_THEME,
                                    'the category theme. This option must be enabled on moodle', VALUE_OPTIONAL),
                        )
                    )
                )
            )
        );
    }

    /**
     * Update categories
     *
     * @param array $categories The list of categories to update
     * @return null
     * @since Moodle 2.3
     */
    public static function update_categories($categories) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        // Validate parameters.
        $params = self::validate_parameters(self::update_categories_parameters(), array('categories' => $categories));

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['categories'] as $cat) {
            if (!$category = $DB->get_record('course_categories', array('id' => $cat['id']))) {
                throw new moodle_exception('unknowcategory');
            }

            $categorycontext = context_coursecat::instance($cat['id']);
            self::validate_context($categorycontext);
            require_capability('moodle/category:manage', $categorycontext);

            if (!empty($cat['name'])) {
                if (textlib::strlen($cat['name'])>255) {
                     throw new moodle_exception('categorytoolong');
                }
                $category->name = $cat['name'];
            }
            if (!empty($cat['idnumber'])) {
                if (textlib::strlen($cat['idnumber'])>100) {
                    throw new moodle_exception('idnumbertoolong');
                }
                $category->idnumber = $cat['idnumber'];
            }
            if (!empty($cat['description'])) {
                $category->description = $cat['description'];
                $category->descriptionformat = external_validate_format($cat['descriptionformat']);
            }
            if (!empty($cat['theme'])) {
                $category->theme = $cat['theme'];
            }
            if (!empty($cat['parent']) && ($category->parent != $cat['parent'])) {
                // First check if parent exists.
                if (!$parent_cat = $DB->get_record('course_categories', array('id' => $cat['parent']))) {
                    throw new moodle_exception('unknowcategory');
                }
                // Then check if we have capability.
                self::validate_context(get_category_or_system_context((int)$cat['parent']));
                require_capability('moodle/category:manage', get_category_or_system_context((int)$cat['parent']));
                // Finally move the category.
                move_category($category, $parent_cat);
                $category->parent = $cat['parent'];
                // Get updated path by move_category().
                $category->path = $DB->get_field('course_categories', 'path',
                        array('id' => $category->id));
            }
            $DB->update_record('course_categories', $category);
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.3
     */
    public static function update_categories_returns() {
        return null;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function delete_categories_parameters() {
        return new external_function_parameters(
            array(
                'categories' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'category id to delete'),
                            'newparent' => new external_value(PARAM_INT,
                                'the parent category to move the contents to, if specified', VALUE_OPTIONAL),
                            'recursive' => new external_value(PARAM_BOOL, '1: recursively delete all contents inside this
                                category, 0 (default): move contents to newparent or current parent category (except if parent is root)', VALUE_DEFAULT, 0)
                        )
                    )
                )
            )
        );
    }

    /**
     * Delete categories
     *
     * @param array $categories A list of category ids
     * @return array
     * @since Moodle 2.3
     */
    public static function delete_categories($categories) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        // Validate parameters.
        $params = self::validate_parameters(self::delete_categories_parameters(), array('categories' => $categories));

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['categories'] as $category) {
            if (!$deletecat = $DB->get_record('course_categories', array('id' => $category['id']))) {
                throw new moodle_exception('unknowcategory');
            }
            $context = context_coursecat::instance($deletecat->id);
            require_capability('moodle/category:manage', $context);
            self::validate_context($context);
            self::validate_context(get_category_or_system_context($deletecat->parent));

            if ($category['recursive']) {
                // If recursive was specified, then we recursively delete the category's contents.
                category_delete_full($deletecat, false);
            } else {
                // In this situation, we don't delete the category's contents, we either move it to newparent or parent.
                // If the parent is the root, moving is not supported (because a course must always be inside a category).
                // We must move to an existing category.
                if (!empty($category['newparent'])) {
                    if (!$DB->record_exists('course_categories', array('id' => $category['newparent']))) {
                        throw new moodle_exception('unknowcategory');
                    }
                    $newparent = $category['newparent'];
                } else {
                    $newparent = $deletecat->parent;
                }

                // This operation is not allowed. We must move contents to an existing category.
                if ($newparent == 0) {
                    throw new moodle_exception('movecatcontentstoroot');
                }

                $parentcontext = get_category_or_system_context($newparent);
                require_capability('moodle/category:manage', $parentcontext);
                self::validate_context($parentcontext);
                category_delete_move($deletecat, $newparent, false);
            }
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function delete_categories_returns() {
        return null;
    }

}

/**
 * Deprecated course external functions
 *
 * @package    core_course
 * @copyright  2009 Petr Skodak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 * @deprecated Moodle 2.2 MDL-29106 - Please do not use this class any more.
 * @todo MDL-31194 This will be deleted in Moodle 2.5.
 * @see core_course_external
 */
class moodle_course_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.0
     * @deprecated Moodle 2.2 MDL-29106 - Please do not call this function any more.
     * @todo MDL-31194 This will be deleted in Moodle 2.5.
     * @see core_course_external::get_courses_parameters()
     */
    public static function get_courses_parameters() {
        return core_course_external::get_courses_parameters();
    }

    /**
     * Get courses
     *
     * @param array $options
     * @return array
     * @since Moodle 2.0
     * @deprecated Moodle 2.2 MDL-29106 - Please do not call this function any more.
     * @todo MDL-31194 This will be deleted in Moodle 2.5.
     * @see core_course_external::get_courses()
     */
    public static function get_courses($options) {
        return core_course_external::get_courses($options);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.0
     * @deprecated Moodle 2.2 MDL-29106 - Please do not call this function any more.
     * @todo MDL-31194 This will be deleted in Moodle 2.5.
     * @see core_course_external::get_courses_returns()
     */
    public static function get_courses_returns() {
        return core_course_external::get_courses_returns();
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.0
     * @deprecated Moodle 2.2 MDL-29106 - Please do not call this function any more.
     * @todo MDL-31194 This will be deleted in Moodle 2.5.
     * @see core_course_external::create_courses_parameters()
     */
    public static function create_courses_parameters() {
        return core_course_external::create_courses_parameters();
    }

    /**
     * Create  courses
     *
     * @param array $courses
     * @return array courses (id and shortname only)
     * @since Moodle 2.0
     * @deprecated Moodle 2.2 MDL-29106 - Please do not call this function any more.
     * @todo MDL-31194 This will be deleted in Moodle 2.5.
     * @see core_course_external::create_courses()
     */
    public static function create_courses($courses) {
        return core_course_external::create_courses($courses);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.0
     * @deprecated Moodle 2.2 MDL-29106 - Please do not call this function any more.
     * @todo MDL-31194 This will be deleted in Moodle 2.5.
     * @see core_course_external::create_courses_returns()
     */
    public static function create_courses_returns() {
        return core_course_external::create_courses_returns();
    }

}
