<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class editsection_form extends moodleform {

    function definition() {

        $mform  = $this->_form;
        $course = $this->_customdata['course'];
        $mform->addElement('checkbox', 'usedefaultname', get_string('sectionusedefaultname'));
        $mform->setDefault('usedefaultname', true);

        $mform->addElement('text', 'name', get_string('sectionname'), array('size'=>'30'));
        $mform->setType('name', PARAM_TEXT);
        $mform->disabledIf('name','usedefaultname','checked');

        /// Prepare course and the editor

        $mform->addElement('editor', 'summary_editor', get_string('summary'), null, $this->_customdata['editoroptions']);
        $mform->addHelpButton('summary_editor', 'summary');
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->_registerCancelButton('cancel');
    }

    public function definition_after_data() {
        global $CFG, $DB;

        $mform  = $this->_form;
        $course = $this->_customdata['course'];

        if (!empty($CFG->enableavailability)) {
            $mform->addElement('header', '', get_string('availabilityconditions', 'condition'));
            // Grouping conditions - only if grouping is enabled at site level
            if (!empty($CFG->enablegroupmembersonly)) {
                $options = array();
                $options[0] = get_string('none');
                if ($groupings = $DB->get_records('groupings', array('courseid' => $course->id))) {
                    foreach ($groupings as $grouping) {
                        $context = context_course::instance($course->id);
                        $options[$grouping->id] = format_string(
                                $grouping->name, true, array('context' => $context));
                    }
                }
                $mform->addElement('select', 'groupingid', get_string('groupingsection', 'group'), $options);
                $mform->addHelpButton('groupingid', 'groupingsection', 'group');
            }

            // Date and time conditions
            $mform->addElement('date_time_selector', 'availablefrom',
                    get_string('availablefrom', 'condition'), array('optional' => true));
            $mform->addElement('date_time_selector', 'availableuntil',
                    get_string('availableuntil', 'condition'), array('optional' => true));

            // Conditions based on grades
            $gradeoptions = array();
            $items = grade_item::fetch_all(array('courseid' => $course->id));
            $items = $items ? $items : array();
            foreach ($items as $id => $item) {
                $gradeoptions[$id] = $item->get_name();
            }
            asort($gradeoptions);
            $gradeoptions = array(0 => get_string('none', 'condition')) + $gradeoptions;

            $grouparray = array();
            $grouparray[] = $mform->createElement('select', 'conditiongradeitemid', '', $gradeoptions);
            $grouparray[] = $mform->createElement('static', '', '',
                    ' ' . get_string('grade_atleast', 'condition').' ');
            $grouparray[] = $mform->createElement('text', 'conditiongrademin', '', array('size' => 3));
            $grouparray[] = $mform->createElement('static', '', '',
                    '% ' . get_string('grade_upto', 'condition') . ' ');
            $grouparray[] = $mform->createElement('text', 'conditiongrademax', '', array('size' => 3));
            $grouparray[] = $mform->createElement('static', '', '', '%');
            $group = $mform->createElement('group', 'conditiongradegroup',
                    get_string('gradecondition', 'condition'), $grouparray);

            // Get full version (including condition info) of section object
            $ci = new condition_info_section($this->_customdata['cs']);
            $fullcs = $ci->get_full_section();
            $count = count($fullcs->conditionsgrade) + 1;

            // Grade conditions
            $this->repeat_elements(array($group), $count, array(), 'conditiongraderepeats',
                    'conditiongradeadds', 2, get_string('addgrades', 'condition'), true);
            $mform->addHelpButton('conditiongradegroup[0]', 'gradecondition', 'condition');

            // Conditions based on completion
            $completion = new completion_info($course);
            if ($completion->is_enabled()) {
                $completionoptions = array();
                $modinfo = get_fast_modinfo($course);
                foreach ($modinfo->cms as $id => $cm) {
                    // Add each course-module if it:
                    // (a) has completion turned on
                    // (b) does not belong to current course-section
                    if ($cm->completion && ($fullcs->id != $cm->section)) {
                        $completionoptions[$id] = $cm->name;
                    }
                }
                asort($completionoptions);
                $completionoptions = array(0 => get_string('none', 'condition')) +
                        $completionoptions;

                $completionvalues = array(
                    COMPLETION_COMPLETE => get_string('completion_complete', 'condition'),
                    COMPLETION_INCOMPLETE => get_string('completion_incomplete', 'condition'),
                    COMPLETION_COMPLETE_PASS => get_string('completion_pass', 'condition'),
                    COMPLETION_COMPLETE_FAIL => get_string('completion_fail', 'condition'));

                $grouparray = array();
                $grouparray[] = $mform->createElement('select', 'conditionsourcecmid', '',
                        $completionoptions);
                $grouparray[] = $mform->createElement('select', 'conditionrequiredcompletion', '',
                        $completionvalues);
                $group = $mform->createElement('group', 'conditioncompletiongroup',
                        get_string('completioncondition', 'condition'), $grouparray);

                $count = count($fullcs->conditionscompletion) + 1;
                $this->repeat_elements(array($group), $count, array(),
                        'conditioncompletionrepeats', 'conditioncompletionadds', 2,
                        get_string('addcompletions', 'condition'), true);
                $mform->addHelpButton('conditioncompletiongroup[0]',
                        'completionconditionsection', 'condition');
            }

            // Availability conditions - set up form values
            if (!empty($CFG->enableavailability)) {
                $num = 0;
                foreach ($fullcs->conditionsgrade as $gradeitemid => $minmax) {
                    $groupelements = $mform->getElement(
                            'conditiongradegroup[' . $num . ']')->getElements();
                    $groupelements[0]->setValue($gradeitemid);
                    $groupelements[2]->setValue(is_null($minmax->min) ? '' :
                            format_float($minmax->min, 5, true, true));
                    $groupelements[4]->setValue(is_null($minmax->max) ? '' :
                            format_float($minmax->max, 5, true, true));
                    $num++;
                }

                if ($completion->is_enabled()) {
                    $num = 0;
                    foreach ($fullcs->conditionscompletion as $othercmid => $state) {
                        $groupelements = $mform->getElement('conditioncompletiongroup[' . $num . ']')->getElements();
                        $groupelements[0]->setValue($othercmid);
                        $groupelements[1]->setValue($state);
                        $num++;
                    }
                }
            }

            // Do we display availability info to students?
            $showhide = array(
                CONDITION_STUDENTVIEW_SHOW => get_string('showavailabilitysection_show', 'condition'),
                CONDITION_STUDENTVIEW_HIDE => get_string('showavailabilitysection_hide', 'condition'));
            $mform->addElement('select', 'showavailability',
                    get_string('showavailabilitysection', 'condition'), $showhide);

            $mform->setDefault('showavailability', $this->_customdata['showavailability']);
        }

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Conditions: Don't let them set dates which make no sense
        if (array_key_exists('availablefrom', $data) &&
                $data['availablefrom'] && $data['availableuntil'] &&
                $data['availablefrom'] > $data['availableuntil']) {
            $errors['availablefrom'] = get_string('badavailabledates', 'condition');
        }

        return $errors;
    }
}
