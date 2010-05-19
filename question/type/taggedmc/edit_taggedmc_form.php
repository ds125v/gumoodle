<?php

require_once( "{$CFG->dirroot}/question/type/multichoice/edit_multichoice_form.php" );

class question_edit_taggedmc_form extends question_edit_multichoice_form {

    function qtype() {
        return 'taggedmc';
    }


}
