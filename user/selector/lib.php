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
 * Code for ajax user selectors.
 *
 * @package   user
 * @copyright 1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The default size of a user selector.
 */
define('USER_SELECTOR_DEFAULT_ROWS', 20);

/**
 * Base class for user selectors.
 *
 * In your theme, you must give each user-selector a defined width. If the
 * user selector has name="myid", then the div myid_wrapper must have a width
 * specified.
 */
abstract class user_selector_base {
    /** @var string The control name (and id) in the HTML. */
    protected $name;
    /** @var array Extra fields to search on and return in addition to firstname and lastname. */
    protected $extrafields;
    /** @var object Context used for capability checks regarding this selector (does
     * not necessarily restrict user list) */
    protected $accesscontext;
    /** @var boolean Whether the conrol should allow selection of many users, or just one. */
    protected $multiselect = true;
    /** @var int The height this control should have, in rows. */
    protected $rows = USER_SELECTOR_DEFAULT_ROWS;
    /** @var array A list of userids that should not be returned by this control. */
    protected $exclude = array();
    /** @var array|null A list of the users who are selected. */
    protected $selected = null;
    /** @var boolean When the search changes, do we keep previously selected options that do
     * not match the new search term? */
    protected $preserveselected = false;
    /** @var boolean If only one user matches the search, should we select them automatically. */
    protected $autoselectunique = false;
    /** @var boolean When searching, do we only match the starts of fields (better performance)
     * or do we match occurrences anywhere? */
    protected $searchanywhere = false;
    /** @var mixed This is used by get selected users */
    protected $validatinguserids = null;

    /**  @var boolean Used to ensure we only output the search options for one user selector on
     * each page. */
    private static $searchoptionsoutput = false;

    /** @var array JavaScript YUI3 Module definition */
    protected static $jsmodule = array(
                'name' => 'user_selector',
                'fullpath' => '/user/selector/module.js',
                'requires'  => array('node', 'event-custom', 'datasource', 'json'),
                'strings' => array(
                    array('previouslyselectedusers', 'moodle', '%%SEARCHTERM%%'),
                    array('nomatchingusers', 'moodle', '%%SEARCHTERM%%'),
                    array('none', 'moodle')
                ));


    // Public API ==============================================================

    /**
     * Constructor. Each subclass must have a constructor with this signature.
     *
     * @param string $name the control name/id for use in the HTML.
     * @param array $options other options needed to construct this selector.
     * You must be able to clone a userselector by doing new get_class($us)($us->get_name(), $us->get_options());
     */
    public function __construct($name, $options = array()) {
        global $CFG, $PAGE;

        // Initialise member variables from constructor arguments.
        $this->name = $name;

        // Use specified context for permission checks, system context if not
        // specified
        if (isset($options['accesscontext'])) {
            $this->accesscontext = $options['accesscontext'];
        } else {
            $this->accesscontext = get_context_instance(CONTEXT_SYSTEM);
        }

        if (isset($options['extrafields'])) {
            $this->extrafields = $options['extrafields'];
        } else if (!empty($CFG->showuseridentity) &&
                has_capability('moodle/site:viewuseridentity', $this->accesscontext)) {
            $this->extrafields = explode(',', $CFG->showuseridentity);
        } else {
            $this->extrafields = array();
        }
        if (isset($options['exclude']) && is_array($options['exclude'])) {
            $this->exclude = $options['exclude'];
        }
        if (isset($options['multiselect'])) {
            $this->multiselect = $options['multiselect'];
        }

        // Read the user prefs / optional_params that we use.
        $this->preserveselected = $this->initialise_option('userselector_preserveselected', $this->preserveselected);
        $this->autoselectunique = $this->initialise_option('userselector_autoselectunique', $this->autoselectunique);
        $this->searchanywhere = $this->initialise_option('userselector_searchanywhere', $this->searchanywhere);
    }

    /**
     * All to the list of user ids that this control will not select. For example,
     * on the role assign page, we do not list the users who already have the role
     * in question.
     *
     * @param array $arrayofuserids the user ids to exclude.
     */
    public function exclude($arrayofuserids) {
        $this->exclude = array_unique(array_merge($this->exclude, $arrayofuserids));
    }

    /**
     * Clear the list of excluded user ids.
     */
    public function clear_exclusions() {
        $exclude = array();
    }

    /**
     * @return array the list of user ids that this control will not select.
     */
    public function get_exclusions() {
        return clone($this->exclude);
    }

    /**
     * @return array of user objects. The users that were selected. This is a more sophisticated version
     * of optional_param($this->name, array(), PARAM_INTEGER) that validates the
     * returned list of ids against the rules for this user selector.
     */
    public function get_selected_users() {
        // Do a lazy load.
        if (is_null($this->selected)) {
            $this->selected = $this->load_selected_users();
        }
        return $this->selected;
    }

    /**
     * Convenience method for when multiselect is false (throws an exception if not).
     * @return object the selected user object, or null if none.
     */
    public function get_selected_user() {
        if ($this->multiselect) {
            throw new moodle_exception('cannotcallusgetselecteduser');
        }
        $users = $this->get_selected_users();
        if (count($users) == 1) {
            return reset($users);
        } else if (count($users) == 0) {
            return null;
        } else {
            throw new moodle_exception('userselectortoomany');
        }
    }

    /**
     * If you update the database in such a way that it is likely to change the
     * list of users that this component is allowed to select from, then you
     * must call this method. For example, on the role assign page, after you have
     * assigned some roles to some users, you should call this.
     */
    public function invalidate_selected_users() {
        $this->selected = null;
    }

    /**
     * Output this user_selector as HTML.
     * @param boolean $return if true, return the HTML as a string instead of outputting it.
     * @return mixed if $return is true, returns the HTML as a string, otherwise returns nothing.
     */
    public function display($return = false) {
        global $PAGE;

        // Get the list of requested users.
        $search = optional_param($this->name . '_searchtext', '', PARAM_RAW);
        if (optional_param($this->name . '_clearbutton', false, PARAM_BOOL)) {
            $search = '';
        }
        $groupedusers = $this->find_users($search);

        // Output the select.
        $name = $this->name;
        $multiselect = '';
        if ($this->multiselect) {
            $name .= '[]';
            $multiselect = 'multiple="multiple" ';
        }
        $output = '<div class="userselector" id="' . $this->name . '_wrapper">' . "\n" .
                '<select name="' . $name . '" id="' . $this->name . '" ' .
                $multiselect . 'size="' . $this->rows . '">' . "\n";

        // Populate the select.
        $output .= $this->output_options($groupedusers, $search);

        // Output the search controls.
        $output .= "</select>\n<div>\n";
        $output .= '<input type="text" name="' . $this->name . '_searchtext" id="' .
                $this->name . '_searchtext" size="15" value="' . s($search) . '" />';
        $output .= '<input type="submit" name="' . $this->name . '_searchbutton" id="' .
                $this->name . '_searchbutton" value="' . $this->search_button_caption() . '" />';
        $output .= '<input type="submit" name="' . $this->name . '_clearbutton" id="' .
                $this->name . '_clearbutton" value="' . get_string('clear') . '" />';

        // And the search options.
        $optionsoutput = false;
        if (!user_selector_base::$searchoptionsoutput) {
            $output .= print_collapsible_region_start('', 'userselector_options',
                    get_string('searchoptions'), 'userselector_optionscollapsed', true, true);
            $output .= $this->option_checkbox('preserveselected', $this->preserveselected, get_string('userselectorpreserveselected'));
            $output .= $this->option_checkbox('autoselectunique', $this->autoselectunique, get_string('userselectorautoselectunique'));
            $output .= $this->option_checkbox('searchanywhere', $this->searchanywhere, get_string('userselectorsearchanywhere'));
            $output .= print_collapsible_region_end(true);

            $PAGE->requires->js_init_call('M.core_user.init_user_selector_options_tracker', array(), false, self::$jsmodule);
            user_selector_base::$searchoptionsoutput = true;
        }
        $output .= "</div>\n</div>\n\n";

        // Initialise the ajax functionality.
        $output .= $this->initialise_javascript($search);

        // Return or output it.
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * The height this control will be displayed, in rows.
     *
     * @param integer $numrows the desired height.
     */
    public function set_rows($numrows) {
        $this->rows = $numrows;
    }

    /**
     * @return integer the height this control will be displayed, in rows.
     */
    public function get_rows() {
        return $this->rows;
    }

    /**
     * Whether this control will allow selection of many, or just one user.
     *
     * @param boolean $multiselect true = allow multiple selection.
     */
    public function set_multiselect($multiselect) {
        $this->multiselect = $multiselect;
    }

    /**
     * @return boolean whether this control will allow selection of more than one user.
     */
    public function is_multiselect() {
        return $this->multiselect;
    }

    /**
     * @return string the id/name that this control will have in the HTML.
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Set the user fields that are displayed in the selector in addition to the
     * user's name.
     *
     * @param array $fields a list of field names that exist in the user table.
     */
    public function set_extra_fields($fields) {
        $this->extrafields = $fields;
    }

    // API for sublasses =======================================================

    /**
     * Search the database for users matching the $search string, and any other
     * conditions that apply. The SQL for testing whether a user matches the
     * search string should be obtained by calling the search_sql method.
     *
     * This method is used both when getting the list of choices to display to
     * the user, and also when validating a list of users that was selected.
     *
     * When preparing a list of users to choose from ($this->is_validating()
     * return false) you should probably have an maximum number of users you will
     * return, and if more users than this match your search, you should instead
     * return a message generated by the too_many_results() method. However, you
     * should not do this when validating.
     *
     * If you are writing a new user_selector subclass, I strongly recommend you
     * look at some of the subclasses later in this file and in admin/roles/lib.php.
     * They should help you see exactly what you have to do.
     *
     * @param string $search the search string.
     * @return array An array of arrays of users. The array keys of the outer
     *      array should be the string names of optgroups. The keys of the inner
     *      arrays should be userids, and the values should be user objects
     *      containing at least the list of fields returned by the method
     *      required_fields_sql(). If a user object has a ->disabled property
     *      that is true, then that option will be displayed greyed out, and
     *      will not be returned by get_selected_users.
     */
    public abstract function find_users($search);

    /**
     *
     * Note: this function must be implemented if you use the search ajax field
     *       (e.g. set $options['file'] = '/admin/filecontainingyourclass.php';)
     * @return array the options needed to recreate this user_selector.
     */
    protected function get_options() {
        return array(
            'class' => get_class($this),
            'name' => $this->name,
            'exclude' => $this->exclude,
            'extrafields' => $this->extrafields,
            'multiselect' => $this->multiselect,
            'accesscontext' => $this->accesscontext,
        );
    }

    // Inner workings ==========================================================

    /**
     * @return boolean if true, we are validating a list of selected users,
     *      rather than preparing a list of uesrs to choose from.
     */
    protected function is_validating() {
        return !is_null($this->validatinguserids);
    }

    /**
     * Get the list of users that were selected by doing optional_param then
     * validating the result.
     *
     * @return array of user objects.
     */
    protected function load_selected_users() {
        // See if we got anything.
        if ($this->multiselect) {
            $userids = optional_param_array($this->name, array(), PARAM_INTEGER);
        } else if ($userid = optional_param($this->name, 0, PARAM_INTEGER)) {
            $userids = array($userid);
        }
        // If there are no users there is nobody to load
        if (empty($userids)) {
            return array();
        }

        // If we did, use the find_users method to validate the ids.
        $this->validatinguserids = $userids;
        $groupedusers = $this->find_users('');
        $this->validatinguserids = null;

        // Aggregate the resulting list back into a single one.
        $users = array();
        foreach ($groupedusers as $group) {
            foreach ($group as $user) {
                if (!isset($users[$user->id]) && empty($user->disabled) && in_array($user->id, $userids)) {
                    $users[$user->id] = $user;
                }
            }
        }

        // If we are only supposed to be selecting a single user, make sure we do.
        if (!$this->multiselect && count($users) > 1) {
            $users = array_slice($users, 0, 1);
        }

        return $users;
    }

    /**
     * @param string $u the table alias for the user table in the query being
     *      built. May be ''.
     * @return string fragment of SQL to go in the select list of the query.
     */
    protected function required_fields_sql($u) {
        // Raw list of fields.
        $fields = array('id', 'firstname', 'lastname');
        $fields = array_merge($fields, $this->extrafields);

        // Prepend the table alias.
        if ($u) {
            foreach ($fields as &$field) {
                $field = $u . '.' . $field;
            }
        }
        return implode(',', $fields);
    }

    /**
     * @param string $search the text to search for.
     * @param string $u the table alias for the user table in the query being
     *      built. May be ''.
     * @return array an array with two elements, a fragment of SQL to go in the
     *      where clause the query, and an array containing any required parameters.
     *      this uses ? style placeholders.
     */
    protected function search_sql($search, $u) {
        global $DB, $CFG;
        $params = array();
        $tests = array();

        if ($u) {
            $u .= '.';
        }

        // If we have a $search string, put a field LIKE '$search%' condition on each field.
        if ($search) {
            $conditions = array(
                $DB->sql_fullname($u . 'firstname', $u . 'lastname'),
                $conditions[] = $u . 'lastname'
            );
            foreach ($this->extrafields as $field) {
                $conditions[] = $u . $field;
            }
            if ($this->searchanywhere) {
                $searchparam = '%' . $search . '%';
            } else {
                $searchparam = $search . '%';
            }
            $i = 0;
            foreach ($conditions as $key=>$condition) {
                $conditions[$key] = $DB->sql_like($condition, ":con{$i}00", false, false);
                $params["con{$i}00"] = $searchparam;
                $i++;
            }
            $tests[] = '(' . implode(' OR ', $conditions) . ')';
        }

        // Add some additional sensible conditions
        $tests[] = $u . "id <> :guestid";
        $params['guestid'] = $CFG->siteguest;
        $tests[] = $u . 'deleted = 0';
        $tests[] = $u . 'confirmed = 1';

        // If we are being asked to exclude any users, do that.
        if (!empty($this->exclude)) {
            list($usertest, $userparams) = $DB->get_in_or_equal($this->exclude, SQL_PARAMS_NAMED, 'ex', false);
            $tests[] = $u . 'id ' . $usertest;
            $params = array_merge($params, $userparams);
        }

        // If we are validating a set list of userids, add an id IN (...) test.
        if (!empty($this->validatinguserids)) {
            list($usertest, $userparams) = $DB->get_in_or_equal($this->validatinguserids, SQL_PARAMS_NAMED, 'val');
            $tests[] = $u . 'id ' . $usertest;
            $params = array_merge($params, $userparams);
        }

        if (empty($tests)) {
            $tests[] = '1 = 1';
        }

        // Combing the conditions and return.
        return array(implode(' AND ', $tests), $params);
    }

    /**
     * Used to generate a nice message when there are too many users to show.
     * The message includes the number of users that currently match, and the
     * text of the message depends on whether the search term is non-blank.
     *
     * @param string $search the search term, as passed in to the find users method.
     * @param int $count the number of users that currently match.
     * @return array in the right format to return from the find_users method.
     */
    protected function too_many_results($search, $count) {
        if ($search) {
            $a = new stdClass;
            $a->count = $count;
            $a->search = $search;
            return array(get_string('toomanyusersmatchsearch', '', $a) => array(),
                    get_string('pleasesearchmore') => array());
        } else {
            return array(get_string('toomanyuserstoshow', '', $count) => array(),
                    get_string('pleaseusesearch') => array());
        }
    }

    /**
     * Output the list of <optgroup>s and <options>s that go inside the select.
     * This method should do the same as the JavaScript method
     * user_selector.prototype.handle_response.
     *
     * @param array $groupedusers an array, as returned by find_users.
     * @return string HTML code.
     */
    protected function output_options($groupedusers, $search) {
        $output = '';

        // Ensure that the list of previously selected users is up to date.
        $this->get_selected_users();

        // If $groupedusers is empty, make a 'no matching users' group. If there is
        // only one selected user, set a flag to select them if that option is turned on.
        $select = false;
        if (empty($groupedusers)) {
            if (!empty($search)) {
                $groupedusers = array(get_string('nomatchingusers', '', $search) => array());
            } else {
                $groupedusers = array(get_string('none') => array());
            }
        } else if ($this->autoselectunique && count($groupedusers) == 1 &&
                count(reset($groupedusers)) == 1) {
            $select = true;
            if (!$this->multiselect) {
                $this->selected = array();
            }
        }

        // Output each optgroup.
        foreach ($groupedusers as $groupname => $users) {
            $output .= $this->output_optgroup($groupname, $users, $select);
        }

        // If there were previously selected users who do not match the search, show them too.
        if ($this->preserveselected && !empty($this->selected)) {
            $output .= $this->output_optgroup(get_string('previouslyselectedusers', '', $search), $this->selected, true);
        }

        // This method trashes $this->selected, so clear the cache so it is
        // rebuilt before anyone tried to use it again.
        $this->selected = null;

        return $output;
    }

    /**
     * Output one particular optgroup. Used by the preceding function output_options.
     *
     * @param string $groupname the label for this optgroup.
     * @param array $users the users to put in this optgroup.
     * @param boolean $select if true, select the users in this group.
     * @return string HTML code.
     */
    protected function output_optgroup($groupname, $users, $select) {
        if (!empty($users)) {
            $output = '  <optgroup label="' . htmlspecialchars($groupname) . ' (' . count($users) . ')">' . "\n";
            foreach ($users as $user) {
                $attributes = '';
                if (!empty($user->disabled)) {
                    $attributes .= ' disabled="disabled"';
                } else if ($select || isset($this->selected[$user->id])) {
                    $attributes .= ' selected="selected"';
                }
                unset($this->selected[$user->id]);
                $output .= '    <option' . $attributes . ' value="' . $user->id . '">' .
                        $this->output_user($user) . "</option>\n";
            }
        } else {
            $output = '  <optgroup label="' . htmlspecialchars($groupname) . '">' . "\n";
            $output .= '    <option disabled="disabled">&nbsp;</option>' . "\n";
        }
        $output .= "  </optgroup>\n";
        return $output;
    }

    /**
     * Convert a user object to a string suitable for displaying as an option in the list box.
     *
     * @param object $user the user to display.
     * @return string a string representation of the user.
     */
    public function output_user($user) {
        $out = fullname($user);
        if ($this->extrafields) {
            $displayfields = array();
            foreach ($this->extrafields as $field) {
                $displayfields[] = $user->{$field};
            }
            $out .= ' (' . implode(', ', $displayfields) . ')';
        }
        return $out;
    }

    /**
     * @return string the caption for the search button.
     */
    protected function search_button_caption() {
        return get_string('search');
    }

    // Initialise one of the option checkboxes, either from
    // the request, or failing that from the user_preferences table, or
    // finally from the given default.
    private function initialise_option($name, $default) {
        $param = optional_param($name, null, PARAM_BOOL);
        if (is_null($param)) {
            return get_user_preferences($name, $default);
        } else {
            set_user_preference($name, $param);
            return $param;
        }
    }

    // Output one of the options checkboxes.
    private function option_checkbox($name, $on, $label) {
        if ($on) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }
        $name = 'userselector_' . $name;
        $output = '<p><input type="hidden" name="' . $name . '" value="0" />' .
                // For the benefit of brain-dead IE, the id must be different from the name of the hidden form field above.
                // It seems that document.getElementById('frog') in IE will return and element with name="frog".
                '<input type="checkbox" id="' . $name . 'id" name="' . $name . '" value="1"' . $checked . ' /> ' .
                '<label for="' . $name . 'id">' . $label . "</label></p>\n";
        user_preference_allow_ajax_update($name, PARAM_BOOL);
        return $output;
    }

    /**
     * @param boolean $optiontracker if true, initialise JavaScript for updating the user prefs.
     * @return any HTML needed here.
     */
    protected function initialise_javascript($search) {
        global $USER, $PAGE, $OUTPUT;
        $output = '';

        // Put the options into the session, to allow search.php to respond to the ajax requests.
        $options = $this->get_options();
        $hash = md5(serialize($options));
        $USER->userselectors[$hash] = $options;

        // Initialise the selector.
        $PAGE->requires->js_init_call('M.core_user.init_user_selector', array($this->name, $hash, $this->extrafields, $search), false, self::$jsmodule);
        return $output;
    }
}

// User selectors for managing group members ==================================

/**
 * Base class to avoid duplicating code.
 */
abstract class groups_user_selector_base extends user_selector_base {
    protected $groupid;
    protected $courseid;

    /**
     * @param string $name control name
     * @param array $options should have two elements with keys groupid and courseid.
     */
    public function __construct($name, $options) {
        global $CFG;
        $options['accesscontext'] = get_context_instance(CONTEXT_COURSE, $options['courseid']);
        parent::__construct($name, $options);
        $this->groupid = $options['groupid'];
        $this->courseid = $options['courseid'];
        require_once($CFG->dirroot . '/group/lib.php');
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['groupid'] = $this->groupid;
        $options['courseid'] = $this->courseid;
        return $options;
    }

    /**
     * @param array $roles array in the format returned by groups_calculate_role_people.
     * @return array array in the format find_users is supposed to return.
     */
    protected function convert_array_format($roles, $search) {
        if (empty($roles)) {
            $roles = array();
        }
        $groupedusers = array();
        foreach ($roles as $role) {
            if ($search) {
                $a = new stdClass;
                $a->role = $role->name;
                $a->search = $search;
                $groupname = get_string('matchingsearchandrole', '', $a);
            } else {
                $groupname = $role->name;
            }
            $groupedusers[$groupname] = $role->users;
            foreach ($groupedusers[$groupname] as &$user) {
                unset($user->roles);
                $user->fullname = fullname($user);
            }
        }
        return $groupedusers;
    }
}

/**
 * User selector subclass for the list of users who are in a certain group.
 * Used on the add group memebers page.
 */
class group_members_selector extends groups_user_selector_base {
    public function find_users($search) {
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $roles = groups_get_members_by_role($this->groupid, $this->courseid,
                $this->required_fields_sql('u'), 'u.lastname, u.firstname',
                $wherecondition, $params);
        return $this->convert_array_format($roles, $search);
    }
}

/**
 * User selector subclass for the list of users who are not in a certain group.
 * Used on the add group members page.
 */
class group_non_members_selector extends groups_user_selector_base {
    const MAX_USERS_PER_PAGE = 100;

    /**
     * An array of user ids populated by find_users() used in print_user_summaries()
     */
    private $potentialmembersids = array();

    public function output_user($user) {
        return parent::output_user($user) . ' (' . $user->numgroups . ')';
    }

    /**
     * Returns the user selector JavaScript module
     * @return array
     */
    public function get_js_module() {
        return self::$jsmodule;
    }

    /**
     * Creates a global JS variable (userSummaries) that is used by the group selector
     * to print related information when the user clicks on a user in the groups UI.
     *
     * Used by /group/clientlib.js
     *
     * @global moodle_database $DB
     * @global moodle_page $PAGE
     * @param int $courseid
     */
    public function print_user_summaries($courseid) {
        global $DB, $PAGE;

        $usersummaries = array();

        // Get other groups user already belongs to
        $usergroups = array();
        $potentialmembersids = $this->potentialmembersids;
        if( empty($potentialmembersids)==false ) {
            list($membersidsclause, $params) = $DB->get_in_or_equal($potentialmembersids, SQL_PARAMS_NAMED, 'pm');
            $sql = "SELECT u.id AS userid, g.*
                    FROM {user} u
                    JOIN {groups_members} gm ON u.id = gm.userid
                    JOIN {groups} g ON gm.groupid = g.id
                    WHERE u.id $membersidsclause AND g.courseid = :courseid ";
            $params['courseid'] = $courseid;
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $usergroup) {
                $usergroups[$usergroup->userid][$usergroup->id] = $usergroup;
            }
            $rs->close();

            foreach ($potentialmembersids as $userid) {
                if (isset($usergroups[$userid])) {
                    $usergrouplist = html_writer::start_tag('ul');
                    foreach ($usergroups[$userid] as $groupitem) {
                        $usergrouplist .= html_writer::tag('li', format_string($groupitem->name));
                    }
                    $usergrouplist .= html_writer::end_tag('ul');
                } else {
                    $usergrouplist = '';
                }
                $usersummaries[] = $usergrouplist;
            }
        }

        $PAGE->requires->data_for_js('userSummaries', $usersummaries);
    }

    public function find_users($search) {
        global $DB;

        // Get list of allowed roles.
        $context = get_context_instance(CONTEXT_COURSE, $this->courseid);
        if ($validroleids = groups_get_possible_roles($context)) {
            list($roleids, $roleparams) = $DB->get_in_or_equal($validroleids, SQL_PARAMS_NAMED, 'r');
        } else {
            $roleids = " = -1";
            $roleparams = array();
        }

        // Get the search condition.
        list($searchcondition, $searchparams) = $this->search_sql($search, 'u');

        // Build the SQL
        list($enrolsql, $enrolparams) = get_enrolled_sql($context);
        $fields = "SELECT r.id AS roleid, r.shortname AS roleshortname, r.name AS rolename, u.id AS userid,
                          " . $this->required_fields_sql('u') . ",
                          (SELECT count(igm.groupid)
                             FROM {groups_members} igm
                             JOIN {groups} ig ON igm.groupid = ig.id
                            WHERE igm.userid = u.id AND ig.courseid = :courseid) AS numgroups";
        $sql = "   FROM {user} u
                   JOIN ($enrolsql) e ON e.id = u.id
              LEFT JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.contextid " . get_related_contexts_string($context) . " AND ra.roleid $roleids)
              LEFT JOIN {role} r ON r.id = ra.roleid
              LEFT JOIN {groups_members} gm ON (gm.userid = u.id AND gm.groupid = :groupid)
                  WHERE u.deleted = 0
                        AND gm.id IS NULL
                        AND $searchcondition";
        $orderby = "ORDER BY u.lastname, u.firstname";

        $params = array_merge($searchparams, $roleparams, $enrolparams);
        $params['courseid'] = $this->courseid;
        $params['groupid']  = $this->groupid;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql("SELECT COUNT(DISTINCT u.id) $sql", $params);
            if ($potentialmemberscount > group_non_members_selector::MAX_USERS_PER_PAGE) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $rs = $DB->get_recordset_sql("$fields $sql $orderby", $params);
        $roles =  groups_calculate_role_people($rs, $context);

        //don't hold onto user IDs if we're doing validation
        if (empty($this->validatinguserids) ) {
            if($roles) {
                foreach($roles as $k=>$v) {
                    if($v) {
                        foreach($v->users as $uid=>$userobject) {
                            $this->potentialmembersids[] = $uid;
                        }
                    }
                }
            }
        }

        return $this->convert_array_format($roles, $search);
    }
}
