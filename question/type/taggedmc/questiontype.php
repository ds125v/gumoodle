<?php

require_once( "{$CFG->dirroot}/question/type/multichoice/questiontype.php" );

class question_taggedmc_qtype extends question_multichoice_qtype {

    function name() {
        return 'taggedmc';
    }

    function get_question_options(&$question) {
        // Get additional information from database
        // and attach it to the question object
        if (!$question->options = get_record('question_taggedmc', 'question',
         $question->id)) {
            notify('Error: Missing question options for taggedmc question'.$question->id.'!');
            return false;
        }

        if (!$question->options->answers = get_records_select('question_answers', 'id IN ('.$question->options->answers.')', 'id')) {
           notify('Error: Missing question answers for taggedmc question'.$question->id.'!');
           return false;
        }

        return true;
    }

    function save_question_options($question) {
        $result = new stdClass;
        if (!$oldanswers = get_records("question_answers", "question",
                                       $question->id, "id ASC")) {
            $oldanswers = array();
        }

        // following hack to check at least two answers exist
        $answercount = 0;
        foreach ($question->answer as $key=>$dataanswer) {
            if ($dataanswer != "") {
                $answercount++;
            }
        }
        $answercount += count($oldanswers);
        if ($answercount < 2) { // check there are at lest 2 answers for multiple choice
            $result->notice = get_string("notenoughanswers", "qtype_taggedmc", "2");
            return $result;
        }

        // Insert all the new answers

        $totalfraction = 0;
        $maxfraction = -1;

        $answers = array();

        foreach ($question->answer as $key => $dataanswer) {
            if ($dataanswer != "") {
                if ($answer = array_shift($oldanswers)) {  // Existing answer, so reuse it
                    $answer->answer     = $dataanswer;
                    $answer->fraction   = $question->fraction[$key];
                    $answer->feedback   = $question->feedback[$key];
                    if (!update_record("question_answers", $answer)) {
                        $result->error = "Could not update quiz answer! (id=$answer->id)";
                        return $result;
                    }
                } else {
                    unset($answer);
                    $answer->answer   = $dataanswer;
                    $answer->question = $question->id;
                    $answer->fraction = $question->fraction[$key];
                    $answer->feedback = $question->feedback[$key];
                    if (!$answer->id = insert_record("question_answers", $answer)) {
                        $result->error = "Could not insert quiz answer! ";
                        return $result;
                    }
                }
                $answers[] = $answer->id;

                if ($question->fraction[$key] > 0) {                 // Sanity checks
                    $totalfraction += $question->fraction[$key];
                }
                if ($question->fraction[$key] > $maxfraction) {
                    $maxfraction = $question->fraction[$key];
                }
            }
        }

        $update = true;
        $options = get_record("question_taggedmc", "question", $question->id);
        if (!$options) {
            $update = false;
            $options = new stdClass;
            $options->question = $question->id;

        }
        $options->answers = implode(",",$answers);
        $options->single = $question->single;
        if(isset($question->layout)){
             $options->layout = $question->layout;
        }
        $options->answernumbering = $question->answernumbering;
        $options->shuffleanswers = $question->shuffleanswers;
        $options->correctfeedback = trim($question->correctfeedback);
        $options->partiallycorrectfeedback = trim($question->partiallycorrectfeedback);
        $options->incorrectfeedback = trim($question->incorrectfeedback);
        $options->tags = trim($question->tags);
        if ($update) {
            if (!update_record("question_taggedmc", $options)) {
                $result->error = "Could not update quiz taggedmc options! (id=$options->id)";
                return $result;
            }
        } else {
            if (!insert_record("question_taggedmc", $options)) {
                $result->error = "Could not insert quiz taggedmc options!";
                return $result;
            }
        }

        // delete old answer records
        if (!empty($oldanswers)) {
            foreach($oldanswers as $oa) {
                delete_records('question_answers', 'id', $oa->id);
            }
        }

        /// Perform sanity checks on fractional grades
        if ($options->single) {
            if ($maxfraction != 1) {
                $maxfraction = $maxfraction * 100;
                $result->noticeyesno = get_string("fractionsnomax", "qtype_taggedmc", $maxfraction);
                return $result;
            }
        } else {
            $totalfraction = round($totalfraction,2);
            if ($totalfraction != 1) {
                $totalfraction = $totalfraction * 100;
                $result->noticeyesno = get_string("fractionsaddwrong", "qtype_taggedmc", $totalfraction);
                return $result;
            }
        }
        return true;
    }

    function delete_question($questionid) {
        delete_records("question_taggedmc", "question", $questionid);
        return true;
    }

    /*
     * Might need to copy print_question_formulation_and_controls()
     * as it calls display.php at the end. This will only be an
     * issue if display.php is modified.
     */

    function backup($bf,$preferences,$question,$level=6) {

        $status = true;

        $multichoices = get_records("question_taggedmc","question",$question,"id");
        //If there are multichoices
        if ($multichoices) {
            //Iterate over each multichoice
            foreach ($multichoices as $multichoice) {
                $status = fwrite ($bf,start_tag("TAGGEDMC",$level,true));
                //Print multichoice contents
                fwrite ($bf,full_tag("LAYOUT",$level+1,false,$multichoice->layout));
                fwrite ($bf,full_tag("ANSWERS",$level+1,false,$multichoice->answers));
                fwrite ($bf,full_tag("SINGLE",$level+1,false,$multichoice->single));
                fwrite ($bf,full_tag("SHUFFLEANSWERS",$level+1,false,$multichoice->shuffleanswers));
                fwrite ($bf,full_tag("CORRECTFEEDBACK",$level+1,false,$multichoice->correctfeedback));
                fwrite ($bf,full_tag("PARTIALLYCORRECTFEEDBACK",$level+1,false,$multichoice->partiallycorrectfeedback));
                fwrite ($bf,full_tag("INCORRECTFEEDBACK",$level+1,false,$multichoice->incorrectfeedback));
                fwrite ($bf,full_tag("ANSWERNUMBERING",$level+1,false,$multichoice->answernumbering));
                fwrite ($bf,full_tag("TAGS",$level+1,false,$multichoice->tags));
                $status = fwrite ($bf,end_tag("MULTICHOICE",$level,true));
            }

            //Now print question_answers
            $status = question_backup_answers($bf,$preferences,$question);
        }
        return $status;
    }

    function restore($old_question_id,$new_question_id,$info,$restore) {

        $status = true;

        //Get the multichoices array
        $multichoices = $info['#']['TAGGEDMC'];

        //Iterate over multichoices
        for($i = 0; $i < sizeof($multichoices); $i++) {
            $mul_info = $multichoices[$i];

            //Now, build the question_multichoice record structure
            $multichoice = new stdClass;
            $multichoice->question = $new_question_id;
            $multichoice->layout = backup_todb($mul_info['#']['LAYOUT']['0']['#']);
            $multichoice->answers = backup_todb($mul_info['#']['ANSWERS']['0']['#']);
            $multichoice->single = backup_todb($mul_info['#']['SINGLE']['0']['#']);
            $multichoice->tags = backup_todb($mul_info['#']['TAGS']['0']['#']);
            $multichoice->shuffleanswers = isset($mul_info['#']['SHUFFLEANSWERS']['0']['#'])?backup_todb($mul_info['#']['SHUFFLEANSWERS']['0']['#']):'';
            if (array_key_exists("CORRECTFEEDBACK", $mul_info['#'])) {
                $multichoice->correctfeedback = backup_todb($mul_info['#']['CORRECTFEEDBACK']['0']['#']);
            } else {
                $multichoice->correctfeedback = '';
            }
            if (array_key_exists("PARTIALLYCORRECTFEEDBACK", $mul_info['#'])) {
                $multichoice->partiallycorrectfeedback = backup_todb($mul_info['#']['PARTIALLYCORRECTFEEDBACK']['0']['#']);
            } else {
                $multichoice->partiallycorrectfeedback = '';
            }
            if (array_key_exists("INCORRECTFEEDBACK", $mul_info['#'])) {
                $multichoice->incorrectfeedback = backup_todb($mul_info['#']['INCORRECTFEEDBACK']['0']['#']);
            } else {
                $multichoice->incorrectfeedback = '';
            }
            if (array_key_exists("ANSWERNUMBERING", $mul_info['#'])) {
                $multichoice->answernumbering = backup_todb($mul_info['#']['ANSWERNUMBERING']['0']['#']);
            } else {
                $multichoice->answernumbering = 'abc';
            }

            //We have to recode the answers field (a list of answers id)
            //Extracts answer id from sequence
            $answers_field = "";
            $in_first = true;
            $tok = strtok($multichoice->answers,",");
            while ($tok) {
                //Get the answer from backup_ids
                $answer = backup_getid($restore->backup_unique_code,"question_answers",$tok);
                if ($answer) {
                    if ($in_first) {
                        $answers_field .= $answer->new_id;
                        $in_first = false;
                    } else {
                        $answers_field .= ",".$answer->new_id;
                    }
                }
                //check for next
                $tok = strtok(",");
            }
            //We have the answers field recoded to its new ids
            $multichoice->answers = $answers_field;

            //The structure is equal to the db, so insert the question_shortanswer
            $newid = insert_record ("question_taggedmc",$multichoice);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }

        return $status;
    }

    function decode_content_links_caller($questionids, $restore, &$i) {
        $status = true;

        // Decode links in the question_multichoice table.
        if ($multichoices = get_records_list('question_taggedmc', 'question',
                implode(',',  $questionids), '', 'id, correctfeedback, partiallycorrectfeedback, incorrectfeedback')) {

            foreach ($multichoices as $multichoice) {
                $correctfeedback = restore_decode_content_links_worker($multichoice->correctfeedback, $restore);
                $partiallycorrectfeedback = restore_decode_content_links_worker($multichoice->partiallycorrectfeedback, $restore);
                $incorrectfeedback = restore_decode_content_links_worker($multichoice->incorrectfeedback, $restore);
                if ($correctfeedback != $multichoice->correctfeedback ||
                        $partiallycorrectfeedback != $multichoice->partiallycorrectfeedback ||
                        $incorrectfeedback != $multichoice->incorrectfeedback) {
                    $subquestion->correctfeedback = addslashes($correctfeedback);
                    $subquestion->partiallycorrectfeedback = addslashes($partiallycorrectfeedback);
                    $subquestion->incorrectfeedback = addslashes($incorrectfeedback);
                    if (!update_record('question_taggedmc', $multichoice)) {
                        $status = false;
                    }
                }

                // Do some output.
                if (++$i % 5 == 0 && !defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if ($i % 100 == 0) {
                        echo "<br />";
                    }
                    backup_flush(300);
                }
            }
        }

        return $status;
    }

    function replace_file_links($question, $fromcourseid, $tocourseid, $url, $destination){
        parent::replace_file_links($question, $fromcourseid, $tocourseid, $url, $destination);
        // replace links in the question_match_sub table.
        // We need to use a separate object, because in load_question_options, $question->options->answers
        // is changed from a comma-separated list of ids to an array, so calling update_record on
        // $question->options stores 'Array' in that column, breaking the question.
        $optionschanged = false;
        $newoptions = new stdClass;
        $newoptions->id = $question->options->id;
        $newoptions->correctfeedback = question_replace_file_links_in_html($question->options->correctfeedback, $fromcourseid, $tocourseid, $url, $destination, $optionschanged);
        $newoptions->partiallycorrectfeedback  = question_replace_file_links_in_html($question->options->partiallycorrectfeedback, $fromcourseid, $tocourseid, $url, $destination, $optionschanged);
        $newoptions->incorrectfeedback = question_replace_file_links_in_html($question->options->incorrectfeedback, $fromcourseid, $tocourseid, $url, $destination, $optionschanged);
        if ($optionschanged){
            if (!update_record('question_taggedmc', addslashes_recursive($newoptions))) {
                error('Couldn\'t update \'question_taggedmc\' record '.$newoptions->id);
            }
        }
        $answerchanged = false;
        foreach ($question->options->answers as $answer) {
            $answer->answer = question_replace_file_links_in_html($answer->answer, $fromcourseid, $tocourseid, $url, $destination, $answerchanged);
            if ($answerchanged){
                if (!update_record('question_answers', addslashes_recursive($answer))){
                    error('Couldn\'t update \'question_answers\' record '.$answer->id);
                }
            }
        }
    }
}

// Register this question type with the question bank.
question_register_questiontype(new question_taggedmc_qtype());
