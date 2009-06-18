<?php // $Id: mod_form.php,v 1.2.2.6 2009/05/30 16:46:43 agrabs Exp $
/**
* print the form to add or edit a feedback-instance
*
* @version $Id: mod_form.php,v 1.2.2.6 2009/05/30 16:46:43 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_feedback_mod_form extends moodleform_mod {

    function definition() {
        global $CFG;

        $mform    =& $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        
        $mform->addElement('text', 'name', get_string('name', 'feedback'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('htmleditor', 'summary', get_string("description", "feedback"), array('rows' => 20));
        $mform->setType('summary', PARAM_RAW);
        $mform->addRule('summary', null, 'required', null, 'client');

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'timinghdr', get_string('timing', 'form'));
        
        $enableopengroup = array();
        $enableopengroup[] =& $mform->createElement('checkbox', 'openenable', get_string('feedbackopen', 'feedback'));
        $enableopengroup[] =& $mform->createElement('date_time_selector', 'timeopen', '');
        $mform->addGroup($enableopengroup, 'enableopengroup', get_string('feedbackopen', 'feedback'), ' ', false);
        $mform->setHelpButton('enableopengroup', array('timeopen', get_string('feedbackopens', 'feedback'), 'feedback'));
        $mform->disabledIf('enableopengroup', 'openenable', 'notchecked');
        
        $enableclosegroup = array();
        $enableclosegroup[] =& $mform->createElement('checkbox', 'closeenable', get_string('feedbackclose', 'feedback'));
        $enableclosegroup[] =& $mform->createElement('date_time_selector', 'timeclose', '');
        $mform->addGroup($enableclosegroup, 'enableclosegroup', get_string('feedbackclose', 'feedback'), ' ', false);
        $mform->setHelpButton('enableclosegroup', array('timeclose', get_string('feedbackcloses', 'feedback'), 'feedback'));
        $mform->disabledIf('enableclosegroup', 'closeenable', 'notchecked');
        
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'feedbackhdr', get_string('feedback_options', 'feedback'));
        
        $options=array();
        $options[1]  = get_string('anonymous', 'feedback');
        $options[2]  = get_string('non_anonymous', 'feedback');
        $mform->addElement('select', 'anonymous', get_string('anonymous_edit', 'feedback'), $options);
        
        $mform->addElement('selectyesno', 'publish_stats', get_string('publish_stats_on_students', 'feedback'));
        $mform->addElement('selectyesno', 'email_notification', get_string('email_notification', 'feedback'));
        $mform->setHelpButton('email_notification', array('emailnotification', get_string('email_notification', 'feedback'), 'feedback'));
        
        // check if there is existing responses to this feedback
        if (is_numeric($this->_instance) AND $this->_instance and $feedback = get_record("feedback", "id", $this->_instance)) {
            $completedFeedbackCount = feedback_get_completeds_group_count($feedback);
        } else {
            $completedFeedbackCount = false;
        }
        
        if($completedFeedbackCount) {
            $multiple_submit_value = $feedback->multiple_submit ? get_string('yes') : get_string('no');
            $mform->addElement('text', 'multiple_submit_static', get_string('multiple_submit', 'feedback'), array('size'=>'4','disabled'=>'disabled', 'value'=>$multiple_submit_value));
            $mform->addElement('hidden', 'multiple_submit', '');
            $mform->setHelpButton('multiple_submit_static', array('multiplesubmit', get_string('multiple_submit', 'feedback'), 'feedback'));
        }else {
            $mform->addElement('selectyesno', 'multiple_submit', get_string('multiple_submit', 'feedback'));
            $mform->setHelpButton('multiple_submit', array('multiplesubmit', get_string('multiple_submit', 'feedback'), 'feedback'));
        }
        $mform->addElement('selectyesno', 'autonumbering', get_string('autonumbering', 'feedback'));
        $mform->setHelpButton('autonumbering', array('autonumbering', get_string('autonumbering', 'feedback'), 'feedback'));
        
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'aftersubmithdr', get_string('after_submit', 'feedback'));

        $mform->addElement('htmleditor', 'page_after_submit', get_string("page_after_submit", "feedback"), array('rows' => 20));
        $mform->setType('page_after_submit', PARAM_RAW);
        
        $mform->addElement('text', 'site_after_submit', get_string('url_for_continue_button', 'feedback'), array('size'=>'64','maxlength'=>'255'));
        $mform->setType('site_after_submit', PARAM_TEXT);
        $mform->setHelpButton('site_after_submit', array('url_for_continue', get_string('url_for_continue_button', 'feedback'), 'feedback'));
        //-------------------------------------------------------------------------------
        $features = new stdClass;
        $features->groups = true;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $features->gradecat = false;
        $features->idnumber = false;
        $this->standard_coursemodule_elements($features);
        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values){
        if (empty($default_values['timeopen'])) {
            $default_values['openenable'] = 0;
        } else {
            $default_values['openenable'] = 1;
        }
        if (empty($default_values['timeclose'])) {
            $default_values['closeenable'] = 0;
        } else {
            $default_values['closeenable'] = 1;
        }

    }

    function validation($data){

    }

}
?>