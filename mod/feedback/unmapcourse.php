<?php // $Id: unmapcourse.php,v 1.3.2.1 2008/05/15 10:33:08 agrabs Exp $
/**
* drops records from feedback_sitecourse_map
*
* @version $Id: unmapcourse.php,v 1.3.2.1 2008/05/15 10:33:08 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

    require_once("../../config.php");
    require_once($CFG->dirroot.'/mod/feedback/lib.php');

    $id = required_param('id', PARAM_INT);
    $cmapid = required_param('cmapid', PARAM_INT);
    
    if ($id) {
        if (! $cm = get_coursemodule_from_id('feedback', $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
        
        if (! $feedback = get_record("feedback", "id", $cm->instance)) {
            error("Course module is incorrect");
        }
    }
    $capabilities = feedback_load_capabilities($cm->id);
    
    if (!$capabilities->mapcourse) {
        error ('access not allowed');
    }


    // cleanup all lost entries after deleting courses or feedbacks
    feedback_clean_up_sitecourse_map();

    if (delete_records('feedback_sitecourse_map', 'id', $cmapid)) {
        redirect (htmlspecialchars('mapcourse.php?id='.$id));
    } else {
        error('Database problem, unable to unmap');
    }

?>