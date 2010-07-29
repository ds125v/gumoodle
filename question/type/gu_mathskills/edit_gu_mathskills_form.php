<?php 
/**
 * Defines the editing form for the gu_mathskills question type.
 *
 * @copyright &copy; 2007 Jamie Pratt
 * @author Jamie Pratt me@jamiep.org
 * @modified by Niall Barr n.barr@admin.gla.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 */

/**
 * multiple choice editing form definition.
 */
class question_edit_gu_mathskills_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    function definition_inner(&$mform) {
        global $QTYPES;

        //$menu = array(get_string('answersingleno', 'qtype_gu_mathskills'), get_string('answersingleyes', 'qtype_gu_mathskills'));
        //$mform->addElement('select', 'single', get_string('answerhowmany', 'qtype_gu_mathskills'), $menu);
        $mform->setDefault('single', 1);

        $mform->addElement('advcheckbox', 'shuffleanswers', get_string('shuffleanswers', 'qtype_multichoice'), null, null, array(0,1));
        $mform->setHelpButton('shuffleanswers', array('multichoiceshuffle', get_string('shuffleanswers','qtype_multichoice'), 'quiz'));
        $mform->setDefault('shuffleanswers', 1);

        $numberingoptions = $QTYPES[$this->qtype()]->get_numbering_styles();
        $menu = array();
        foreach ($numberingoptions as $numberingoption) {
            $menu[$numberingoption] = get_string('answernumbering' . $numberingoption, 'qtype_multichoice');
        }
        $mform->addElement('select', 'answernumbering', get_string('answernumbering', 'qtype_multichoice'), $menu);
        $mform->setDefault('answernumbering', 'abc');

/*        $mform->addElement('static', 'answersinstruct', get_string('choices', 'qtype_multichoice'), get_string('fillouttwochoices', 'qtype_gu_mathskills'));
        $mform->closeHeaderBefore('answersinstruct');
*/
        //$creategrades = get_grade_options();
        $creategrades = new Object();
        $creategrades->gradeoptions = array(1 => '100%', 0 => 'None');
        $creategrades->gradeoptionsfull = array(1 => '100%', 0 => 'None');
        //echo '<pre>'; print_r($creategrades); echo '</pre>';
        $this->add_per_answer_fields($mform, get_string('choiceno', 'qtype_multichoice', '{no}'),
                $creategrades->gradeoptionsfull, max(5, QUESTION_NUMANS_START));

        $mform->addElement('header', 'overallfeedbackhdr', get_string('noneofabovefeedback', 'qtype_gu_mathskills'));

        foreach (array('correctfeedback', 'incorrectfeedback') as $feedbackname) {
            $mform->addElement('htmleditor', $feedbackname, get_string($feedbackname, 'qtype_gu_mathskills'),
                                array('course' => $this->coursefilesid));
            $mform->setType($feedbackname, PARAM_RAW);
        }

    }

    function get_per_answer_fields(&$mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] =& $mform->createElement('header', 'answerhdr', $label);
        $repeated[] =& $mform->createElement('htmleditor', 'answer', get_string('answer', 'quiz'), array('size' => 50));
        $repeated[] =& $mform->createElement('select', 'fraction', get_string('grade'), $gradeoptions);
        $repeated[] =& $mform->createElement('htmleditor', 'feedback', get_string('feedback', 'quiz'),
                                array('course' => $this->coursefilesid));
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answers';
        return $repeated;
    }

    function set_data($question) {
        if (isset($question->options)){
            $answers = $question->options->answers;
            if (count($answers)) {
                $key = 0;
                foreach ($answers as $answer){
                    $default_values['answer['.$key.']'] = $answer->answer;
                    $default_values['fraction['.$key.']'] = $answer->fraction;
                    $default_values['feedback['.$key.']'] = $answer->feedback;
                    $key++;
                }
            }
            $default_values['single'] =  $question->options->single;
            $default_values['answernumbering'] =  $question->options->answernumbering;
            $default_values['shuffleanswers'] =  $question->options->shuffleanswers;
            $default_values['correctfeedback'] =  $question->options->correctfeedback;
            $default_values['partiallycorrectfeedback'] =  $question->options->partiallycorrectfeedback;
            $default_values['incorrectfeedback'] =  $question->options->incorrectfeedback;
            $question = (object)((array)$question + $default_values);
        }
        parent::set_data($question);
    }

    function qtype() {
        return 'gu_mathskills';
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;

        $totalfraction = 0;
        $maxfraction = -1;

        foreach ($answers as $key => $answer){
            //check no of choices
            $trimmedanswer = trim($answer);
            if (!empty($trimmedanswer)){
                $answercount++;
            }
            //check grades
            if ($answer != '') {
                if ($data['fraction'][$key] > 0) {
                    $totalfraction += $data['fraction'][$key];
                }
                if ($data['fraction'][$key] > $maxfraction) {
                    $maxfraction = $data['fraction'][$key];
                }
            }
        }

        if ($answercount==0){
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_multichoice', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_multichoice', 2);
        } elseif ($answercount==1){
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_multichoice', 2);

        }

        /// Perform sanity checks on fractional grades
            if ($maxfraction != 1) {
                $maxfraction = $maxfraction * 100;
                $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_multichoice', $maxfraction);
        }
        return $errors;
    }
}
?>
