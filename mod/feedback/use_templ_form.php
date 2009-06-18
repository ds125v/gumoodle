<?php // $Id: use_templ_form.php,v 1.2.2.1 2008/05/15 10:33:09 agrabs Exp $
/**
* prints the form to confirm use template
*
* @version $Id: use_templ_form.php,v 1.2.2.1 2008/05/15 10:33:09 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

require_once $CFG->libdir.'/formslib.php';

class mod_feedback_use_templ_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        //headline
        $mform->addElement('header', 'general', '');
        
        // visible elements
        $mform->addElement('radio', 'deleteolditems', get_string('delete_old_items', 'feedback'), '', 1);
        $mform->addElement('radio', 'deleteolditems', get_string('append_new_items', 'feedback'), '', 0);
        $mform->setType('deleteolditems', PARAM_INT);

        // hidden elements
        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'templateid');
        $mform->addElement('hidden', 'do_show');
        $mform->addElement('hidden', 'confirmadd');

        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();

    }
}
?>
