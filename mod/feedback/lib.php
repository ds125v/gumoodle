<?php // $Id: lib.php,v 1.7.2.17 2009/06/13 13:07:15 agrabs Exp $
/**
* includes the main-part of feedback-functions
*
* @version $Id: lib.php,v 1.7.2.17 2009/06/13 13:07:15 agrabs Exp $
* @author Andreas Grabs
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package feedback
*/

/// Library of functions and constants for module feedback

define('FEEDBACK_INCLUDE_TEST', 1);
define('FEEDBACK_ANONYMOUS_YES', 1);
define('FEEDBACK_ANONYMOUS_NO', 2);
define('FEEDBACK_MIN_ANONYMOUS_COUNT_IN_GROUP', 2);
define('FEEDBACK_DECIMAL', '.');
define('FEEDBACK_THOUSAND', ',');
define('FEEDBACK_RESETFORM_RESET', 'feedback_reset_data_');
define('FEEDBACK_RESETFORM_DROP', 'feedback_drop_feedback_');
define('FEEDBACK_MAX_PIX_LENGTH', '400'); //max. Breite des grafischen Balkens in der Auswertung

$feedback_names = feedback_load_feedback_items('mod/feedback/item');

/**
* this will create a new instance and return the id number 
* of the new instance.
* @param object $feedback the object given by mod_feedback_mod_form
* @return int
*/
function feedback_add_instance($feedback) {

    $feedback->timemodified = time();
    $feedback->id = '';

    //check if openenable and/or closeenable is set and set correctly to save in db
    if(empty($feedback->openenable)) {
        $feedback->timeopen = 0;
    }
    if(empty($feedback->closeenable)) {
        $feedback->timeclose = 0;
    }
    if(empty($feedback->site_after_submit)) {
        $feedback->site_after_submit = '';
    }

    //saving the feedback in db
    if(!$feedback->id = insert_record("feedback", $feedback)) {
        return false;
    }
    
    feedback_set_events($feedback);
    
    return $feedback->id;
}

/**
* this will update a given instance
* @param object $feedback the object given by mod_feedback_mod_form
* @return boolean
*/
function feedback_update_instance($feedback) {

    $feedback->timemodified = time();
    $feedback->id = $feedback->instance;

    //check if openenable and/or closeenable is set and set correctly to save in db
    if(empty($feedback->openenable)) {
        $feedback->timeopen = 0;
    }
    if(empty($feedback->closeenable)) {
        $feedback->timeclose = 0;
    }
    if(empty($feedback->site_after_submit)) {
        $feedback->site_after_submit = '';
    }

    //save the feedback into the db
    if(!update_record("feedback", $feedback)) {
        return false;
    }

    //create or update the new events
    feedback_set_events($feedback);
     
    return true;
}

/**
* this will delete a given instance.
* all referenced data also will be deleted
* @param int $id the instanceid of feedback
* @return boolean
*/
function feedback_delete_instance($id) {
    //get all referenced items
    $feedbackitems = get_records('feedback_item', 'feedback', $id);
    
    //deleting all referenced items and values
    if (is_array($feedbackitems)){
        foreach($feedbackitems as $feedbackitem){
            @delete_records("feedback_value", "item", $feedbackitem->id);
            @delete_records("feedback_valuetmp", "item", $feedbackitem->id);
        }
        @delete_records("feedback_item", "feedback", $id);
    }
    
    //deleting the referenced tracking data
    @delete_records('feedback_tracking', 'feedback', $id);
    
    //deleting the completeds
    @delete_records("feedback_completed", "feedback", $id);
    
    //deleting the unfinished completeds
    @delete_records("feedback_completedtmp", "feedback", $id);
    
    //deleting old events
    @delete_records('event', 'modulename', 'feedback', 'instance', $id);
    return @delete_records("feedback", "id", $id);
}

/**
* this is called after deleting all instances if the course will be deleted.
* only templates have to be deleted
* @param object $course
* @return boolean
*/
function feedback_delete_course($course) {
    //delete all templates of given course
    return delete_records('feedback_template', 'course', $course->id);
}

/**
* Return a small object with summary information about what a 
* user has done with a given particular instance of this module
* Used for user activity reports.
* $return->time = the time they did it
* $return->info = a short text description
* @param object $course
* @param object $user
* @param object $mod
* @param object $feedback
* @return object
*/
function feedback_user_outline($course, $user, $mod, $feedback) {

    return NULL;
}

/**
 * Returns all users who has completed a specified feedback since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 */
function feedback_get_recent_mod_activity(&$activities, &$index, $timemodified, $courseid, $cmid, $userid="", $groupid="")  {
    global $CFG, $COURSE, $USER;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = get_record('course', 'id', $courseid);
    }

    $modinfo =& get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];

    if ($userid) {
        $userselect = "AND u.id = $userid";
    } else {
        $userselect = "";
    }

    if ($groupid) {
        $groupselect = "AND gm.groupid = $groupid";
        $groupjoin   = "JOIN {$CFG->prefix}groups_members gm ON  gm.userid=u.id";
    } else {
        $groupselect = "";
        $groupjoin   = "";
    }
    
    if (!$feedbackitems = get_records_sql("SELECT fk . * , fc . * , u.firstname, u.lastname, u.email, u.picture
                                            FROM {$CFG->prefix}feedback_completed fc
                                                JOIN {$CFG->prefix}feedback fk ON fk.id = fc.feedback
                                                JOIN {$CFG->prefix}user u ON u.id = fc.userid
                                                $groupjoin
                                            WHERE fc.timemodified > $timemodified AND fk.id = $cm->instance
                                                $userselect $groupselect
                                            ORDER BY fc.timemodified DESC")) {
         return;
    }


    $cm_context      = get_context_instance(CONTEXT_MODULE, $cm->id);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cm_context);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cm_context);
    $groupmode       = groups_get_activity_groupmode($cm, $course);

    if (is_null($modinfo->groups)) {
        $modinfo->groups = groups_get_user_groups($course->id); // load all my groups and cache it in modinfo
    }

    $aname = format_string($cm->name,true);
    foreach ($feedbackitems as $feedbackitem) {
// test the objects	: print_r($feedbackitem);
        if ($feedbackitem->userid != $USER->id) {
       
            if ($groupmode == SEPARATEGROUPS and !$accessallgroups) { 
                $usersgroups = groups_get_all_groups($course->id, $feedbackitem->userid, $cm->groupingid);
                if (!is_array($usersgroups)) {
                    continue;
                }
                $usersgroups = array_keys($usersgroups);
                $interset = array_intersect($usersgroups, $modinfo->groups[$cm->id]);
                if (empty($intersect)) {
                    continue;
                }
            }
       }

        $tmpactivity = new object();

        $tmpactivity->type      = 'feedback';
        $tmpactivity->cmid      = $cm->id;
        $tmpactivity->name      = $aname;
        $tmpactivity->sectionnum= $cm->sectionnum;
        $tmpactivity->timestamp = $feedbackitem->timemodified;
        
        $tmpactivity->content->feedbackid = $feedbackitem->id;
        $tmpactivity->content->feedbackuserid = $feedbackitem->userid;
        
        $tmpactivity->user->userid   = $feedbackitem->userid;
        $tmpactivity->user->fullname = fullname($feedbackitem, $viewfullnames);
        $tmpactivity->user->picture  = $feedbackitem->picture;
        
        $activities[$index++] = $tmpactivity;
    }

  return;
}

/**
 * Prints all users who has completed a specified feedback since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 */
function feedback_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';

    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    print_user_picture($activity->user->userid, $courseid, $activity->user->picture);
    echo "</td><td>";

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo "<img src=\"$CFG->modpixpath/{$activity->type}/icon.gif\" ".
             "class=\"icon\" alt=\"$modname\" />";
        echo "<a href=\"$CFG->wwwroot/mod/feedback/view.php?id={$activity->cmid}\">{$activity->name}</a>";
        echo '</div>';
    }
	
	echo '<div class="title">';
    echo '</div>';
	
    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->userid}&amp;course=$courseid\">"
         ."{$activity->user->fullname}</a> - ".userdate($activity->timestamp);
    echo '</div>';

    echo "</td></tr></table>";

    return;
}

/**
* Print a detailed representation of what a  user has done with
* a given particular instance of this module, for user activity reports.
* @param object $course
* @param object $user
* @param object $mod
* @param object $feedback
* @return object
*/
function feedback_user_complete($course, $user, $mod, $feedback) {

    return true;
}


function feedback_cron () {
    return true;
}


function feedback_get_participants($feedbackid) {

    return false;
}

function feedback_scale_used ($feedbackid,$scaleid) {
    $return = false;

    return $return;
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all responses from the specified feedback
 * and clean up any related data.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function feedback_reset_userdata($data) {
    global $CFG;
    
    $resetfeedbacks = array();
    $dropfeedbacks = array();
    $status = array();
    $componentstr = get_string('modulenameplural', 'feedback');
    
    //get the relevant entries from $data
    foreach($data as $key => $value) {
        switch(true) {
            case substr($key, 0, strlen(FEEDBACK_RESETFORM_RESET)) == FEEDBACK_RESETFORM_RESET:
                if($value == 1) {
                    $templist = explode('_', $key);
                    if(isset($templist[3]))$resetfeedbacks[] = intval($templist[3]);
                }
            break;
            case substr($key, 0, strlen(FEEDBACK_RESETFORM_DROP)) == FEEDBACK_RESETFORM_DROP:
                if($value == 1) {
                    $templist = explode('_', $key);
                    if(isset($templist[3]))$dropfeedbacks[] = intval($templist[3]);
                }
            break;
        }
    }
    
    //reset the selected feedbacks
    foreach($resetfeedbacks as $id) {
        $feedback = get_record('feedback', 'id', $id);
        feedback_delete_all_completeds($id);
        $status[] = array('component'=>$componentstr.':'.$feedback->name, 'item'=>get_string('resetting_data','feedback'), 'error'=>false);
    }
    
    //drop the selected feedbacks
    // foreach($dropfeedbacks as $id) {
        // $cm = get_coursemodule_from_instance('feedback', $id);
        // feedback_delete_instance($id);
        // feedback_delete_course_module($cm->id);
        // $status[] = array('component'=>$componentstr, 'item'=>get_string('drop_feedback','feedback'), 'error'=>false);
    // }
    return $status;
}

/**
 * Called by course/reset.php
 * @param $mform form passed by reference
 */
function feedback_reset_course_form_definition(&$mform) {
    global $COURSE;
    $mform->addElement('header', 'feedbackheader', get_string('modulenameplural', 'feedback'));
    
    if(!$feedbacks = get_records('feedback', 'course', $COURSE->id, 'name')){
        return;
    }

    $mform->addElement('static', 'hint', get_string('resetting_data','feedback'));
    foreach($feedbacks as $feedback) {
        $mform->addElement('checkbox', FEEDBACK_RESETFORM_RESET.$feedback->id, $feedback->name);
        // $mform->addElement('checkbox', FEEDBACK_RESETFORM_DROP.$feedback->id, get_string('drop_feedback','feedback'));
    }
}

/**
 * Course reset form defaults.
 */
function feedback_reset_course_form_defaults($course) {
    $return = array();
    if(!$feedbacks = get_records('feedback', 'course', $course->id, 'name')){
        return;
    }
    foreach($feedbacks as $feedback) {
        $return[FEEDBACK_RESETFORM_RESET.$feedback->id] = true;
        // $return[FEEDBACK_RESETFORM_DROP.$feedback->id] = false;
    }
    return $return;
}

/** 
 * Called by course/reset.php and shows the formdata by coursereset.
 * it prints checkboxes for each feedback available at the given course
 * there are two checkboxes: 1) delete userdata and keep the feedback 2) delete userdata and drop the feedback
 * @param object $course
 * @return void
 */
function feedback_reset_course_form($course) {
    echo get_string('resetting_feedbacks', 'feedback'); echo ':<br />';
    if(!$feedbacks = get_records('feedback', 'course', $course->id, 'name'))return;
    
    foreach($feedbacks as $feedback) {
        echo '<p>';
        echo get_string('name','feedback').': '.$feedback->name.'<br />';
        print_checkbox(FEEDBACK_RESETFORM_RESET.$feedback->id, 1, true, get_string('resetting_data','feedback'), '', '');  echo '<br />';
        print_checkbox(FEEDBACK_RESETFORM_DROP.$feedback->id, 1, false, get_string('drop_feedback','feedback'), '', '');
        echo '</p>';
    }
}

/** 
 *  This creates new events given as timeopen and closeopen by $feedback.
 *  @param object $feedback
 *  @return void
 */
function feedback_set_events($feedback) {
    // adding the feedback to the eventtable (I have seen this at quiz-module)
    delete_records('event', 'modulename', 'feedback', 'instance', $feedback->id);

    // the open-event
    if($feedback->timeopen > 0) {
        $event = NULL;
        $event->name          = get_string('start', 'feedback').' '.$feedback->name;
        $event->description = $feedback->summary;
        $event->courseid     = $feedback->course;
        $event->groupid      = 0;
        $event->userid        = 0;
        $event->modulename  = 'feedback';
        $event->instance     = $feedback->id;
        $event->eventtype    = 'open';
        $event->timestart    = $feedback->timeopen;
        $event->visible      = instance_is_visible('feedback', $feedback);
        if($feedback->timeclose > 0) {
            $event->timeduration = ($feedback->timeclose - $feedback->timeopen);
        } else {
            $event->timeduration = 0;
        }
    
        add_event($event);
    }

    // the close-event
    if($feedback->timeclose > 0) {
        $event = NULL;
        $event->name          = get_string('stop', 'feedback').' '.$feedback->name;
        $event->description = $feedback->summary;
        $event->courseid     = $feedback->course;
        $event->groupid      = 0;
        $event->userid        = 0;
        $event->modulename  = 'feedback';
        $event->instance     = $feedback->id;
        $event->eventtype    = 'close';
        $event->timestart    = $feedback->timeclose;
        $event->visible      = instance_is_visible('feedback', $feedback);
        $event->timeduration = 0;

        add_event($event);
    }
}

/** 
 *  this function is called by {@link feedback_delete_userdata()}
 *  it drops the feedback-instance from the course_module table
 *  @param int $id the id from the coursemodule
 *  @return boolean
 */
function feedback_delete_course_module($id) {
    if (!$cm = get_record('course_modules', 'id', $id)) {
        return true;
    }
    return delete_records('course_modules', 'id', $cm->id);
}



////////////////////////////////////////////////
//functions to handle capabilities
////////////////////////////////////////////////

/** 
 *  returns the context-id related to the given coursemodule-id
 *  @param int $cmid the coursemodule-id
 *  @return object $context
 */
function feedback_get_context($cmid) {
    static $context;
    
    if(isset($context)) return $context;
    
    if (!$context = get_context_instance(CONTEXT_MODULE, $cmid)) {
            print_error('badcontext');
    }
    return $context;
}

/** 
 *  get the capabilities for the feedback
 *  @param int $cmid
 *  @return object the available capabilities from current user
 */
function feedback_load_capabilities($cmid) {
    static $cb;
    
    if(isset($cb)) return $cb;
    
    $context = feedback_get_context($cmid);
    
    $cb = new object;
    $cb->view = has_capability('mod/feedback:view', $context, NULL, false);
    $cb->complete = has_capability('mod/feedback:complete', $context, NULL, false);
    $cb->deletesubmissions = has_capability('mod/feedback:deletesubmissions', $context, NULL, false);
    $cb->mapcourse = has_capability('mod/feedback:mapcourse', $context, NULL, false);
    $cb->edititems = has_capability('mod/feedback:edititems', $context, NULL, false);
    $cb->viewreports = has_capability('mod/feedback:viewreports', $context, NULL, false);
    $cb->receivemail = has_capability('mod/feedback:receivemail', $context, NULL, false);
    $cb->createprivatetemplate = has_capability('mod/feedback:createprivatetemplate', $context, NULL, false);
    $cb->createpublictemplate = has_capability('mod/feedback:createpublictemplate', $context, NULL, false);
    $cb->deletetemplate = has_capability('mod/feedback:deletetemplate', $context, NULL, false);
    
    $cb->siteadmin = has_capability('moodle/site:doanything', $context);
    
    $cb->viewhiddenactivities = has_capability('moodle/course:viewhiddenactivities', $context, NULL, false);
    
    return $cb;

}

/** 
 *  get the capabilities for the course.
 *  this is used by feedback/index.php
 *  @param int $courseid
 *  @return object the available capabilities from current user
 */
function feedback_load_course_capabilities($courseid) {
    static $ccb;
    
    if(isset($ccb)) return $ccb;
    
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    
    $ccb = new object;
    $ccb->view = has_capability('mod/feedback:view', $context, NULL, false);
    $ccb->complete = has_capability('mod/feedback:complete', $context, NULL, false);
    $ccb->deletesubmissions = has_capability('mod/feedback:deletesubmissions', $context, NULL, false);
    $ccb->mapcourse = has_capability('mod/feedback:mapcourse', $context, NULL, false);
    $ccb->edititems = has_capability('mod/feedback:edititems', $context, NULL, false);
    $ccb->viewreports = has_capability('mod/feedback:viewreports', $context, NULL, false);
    $ccb->receivemail = has_capability('mod/feedback:receivemail', $context, NULL, false);
    $ccb->createprivatetemplate = has_capability('mod/feedback:createprivatetemplate', $context, NULL, false);
    $ccb->createpublictemplate = has_capability('mod/feedback:createpublictemplate', $context, NULL, false);
    $ccb->deletetemplate = has_capability('mod/feedback:deletetemplate', $context, NULL, false);
    
    $ccb->siteadmin = has_capability('moodle/site:doanything', $context);
    
    $ccb->viewhiddenactivities = has_capability('moodle/course:viewhiddenactivities', $context, NULL, false);
    
    return $ccb;

}

/** 
 *  returns true if the current role is faked by switching role feature
 *  @return boolean
 */
function feedback_check_is_switchrole(){
    global $USER;
    if(isset($USER->switchrole) AND is_array($USER->switchrole) AND count($USER->switchrole) > 0) {
        return true;
    }
    return false;
}

/** 
 *  get users which have the complete-capability
 *  @param object $cm
 *  @param int $group single groupid
 *  @return object the userrecords
 */
function feedback_get_complete_users($cm, $group = false) {
    global $CFG;
    
    if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
            print_error('badcontext');
    }

    $fromgroup = '';
    $wheregroup = '';
    if($group) {
        $fromgroup = ', '.$CFG->prefix.'groups_members g';
        $wheregroup = ' AND g.groupid = '.$group.' AND g.userid = c.userid';
    }
    $sql = 'SELECT u.* FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'feedback_completed c'.$fromgroup.'
              WHERE u.id = c.userid AND c.feedback = '.$cm->instance.'
              '.$wheregroup.'
              ORDER BY u.lastname';

    return get_records_sql($sql);
}

/** 
 *  get users which have the viewreports-capability
 *  @param int $cmid
 *  @param mixed $groups single groupid or array of groupids - group(s) user is in
 *  @return object the userrecords
 */
function feedback_get_viewreports_users($cmid, $groups = false) {

    if (!$context = get_context_instance(CONTEXT_MODULE, $cmid)) {
            print_error('badcontext');
    }
    
    //description of the call below: get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='', $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context, 'mod/feedback:viewreports', '', 'lastname', '', '', $groups, '', false);
}

/** 
 *  get users which have the receivemail-capability
 *  @param int $cmid
 *  @param mixed $groups single groupid or array of groupids - group(s) user is in
 *  @return object the userrecords
 */
function feedback_get_receivemail_users($cmid, $groups = false) {

    if (!$context = get_context_instance(CONTEXT_MODULE, $cmid)) {
            print_error('badcontext');
    }
    
    //description of the call below: get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='', $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context, 'mod/feedback:receivemail', '', 'lastname', '', '', $groups, '', false);
                     //get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='', $limitnum='', $groups='', $exceptions='', $doanything=true, $view=false, $useviewallgroups=false)
}

////////////////////////////////////////////////
//functions to handle the templates
////////////////////////////////////////////////
////////////////////////////////////////////////

/** 
 *  creates a new template-record.
 *  @param int $courseid
 *  @param string $name the name of template shown in the templatelist
 *  @param int $ispublic 0:privat 1:public
 *  @return int the new templateid
 */
function feedback_create_template($courseid, $name, $ispublic = 0) {
    $templ->id = '';
    $templ->course = $courseid;
    
    $templ->name = addslashes($name);
    
    $templ->ispublic = $ispublic;
    return insert_record('feedback_template', $templ);
}

/** 
 *  creates new template items.
 *  all items will be copied and the attribute feedback will be set to 0
 *  and the attribute template will be set to the new templateid
 *  @param object $feedback
 *  @param string $name the name of template shown in the templatelist
 *  @param int $ispublic 0:privat 1:public
 *  @return boolean
 */
function feedback_save_as_template($feedback, $name, $ispublic = 0) {
    $feedbackitems = get_records('feedback_item', 'feedback', $feedback->id);
    if(!is_array($feedbackitems)){
        return false;
    }
    
    if(!$newtempl = feedback_create_template($feedback->course, $name, $ispublic)) {
        return false;
    }
    //create items of this new template
    foreach($feedbackitems as $item) {
        $item->id = '';
        $item->feedback = 0;
        $item->template = $newtempl;
        $item->name = addslashes($item->name);
        $item->presentation = addslashes($item->presentation);
        insert_record('feedback_item', $item);
    }
    return true;
}

/** 
 *  deletes all feedback_items related to the given template id
 *  @param int $id the templateid
 *  @return void
 */
function feedback_delete_template($id) {
    @delete_records("feedback_item", "template", $id);
    @delete_records("feedback_template", "id", $id);
}

/** 
 *  creates new feedback_item-records from template.
 *  if $deleteold is set true so the existing items of the given feedback will be deleted
 *  if $deleteold is set false so the new items will be appanded to the old items
 *  @param object $feedback
 *  @param int $templateid
 *  @param boolean $deleteold
 */
function feedback_items_from_template($feedback, $templateid, $deleteold = false) {
    //get all templateitems
    if(!$templitems = get_records('feedback_item', 'template', $templateid)) {
        return false;
    }
    
    //if deleteold then delete all old items before
    //get all items
    if($deleteold) {
        if($feedbackitems = get_records('feedback_item', 'feedback', $feedback->id)){
            //delete all items of this feedback
            foreach($feedbackitems as $item) {
                feedback_delete_item($item->id, false);
            }
            //delete tracking-data
            @delete_records('feedback_tracking', 'feedback', $feedback->id);
            @delete_records('feedback_completed', 'feedback', $feedback->id);
            @delete_records('feedback_completedtmp', 'feedback', $feedback->id);
            $positionoffset = 0;
        }
    }else {
        //if the old items are kept the new items will be appended
        //therefor the new position has an offset
        $positionoffset = count_records('feedback_item', 'feedback', $feedback->id);
    }
    
    foreach($templitems as $newitem) {
        $newitem->id = '';
        $newitem->feedback = $feedback->id;
        $newitem->template = 0;
        $newitem->name = addslashes($newitem->name);
        $newitem->presentation = addslashes($newitem->presentation);
        $newitem->position = $newitem->position + $positionoffset;
        insert_record('feedback_item', $newitem);
    }
}

/** 
 *  get the list of available templates.
 *  if the $onlyown param is set true so only templates from own course will be served
 *  this is important for droping templates
 *  @param object $course
 *  @param boolean $onlyown
 *  @return array the template recordsets
 */
function feedback_get_template_list($course, $onlyown = false) {
    if($onlyown) {
        $templates = get_records('feedback_template', 'course', $course->id);
    } else {
        $templates = get_records_select('feedback_template', 'course = ' . $course->id . ' OR ispublic = 1');
    }
    return $templates;
}

////////////////////////////////////////////////
//Handling der Items
////////////////////////////////////////////////
////////////////////////////////////////////////

/** 
 *  load the available item plugins from given subdirectory of $CFG->dirroot
 *  the default is "mod/feedback/item"
 *  @param string $dir the subdir
 *  @return array pluginnames as string
 */
function feedback_load_feedback_items($dir = 'mod/feedback/item') {
    global $CFG;
    $names =get_list_of_plugins($dir);
    $ret_names = array();

    foreach($names as $name) {
        require_once($CFG->dirroot.'/'.$dir.'/'.$name.'/lib.php');
        if(class_exists('feedback_item_'.$name)) {
          $ret_names[] = $name;
        }
    }
    return $ret_names;
}

/** 
 *  load the available item plugins to use as dropdown-options
 *  @return array pluginnames as string
 */
function feedback_load_feedback_items_options() {
    global $CFG;
    
    $feedback_options = array("pagebreak" => get_string('add_pagebreak', 'feedback'));
    
    if (!$feedback_names = feedback_load_feedback_items('mod/feedback/item')) {
        return array();
    }
    
    foreach($feedback_names as $fn) {
        $feedback_options[$fn] = get_string($fn,'feedback');
    }
    asort($feedback_options);
    $feedback_options = array_merge( array(' ' => get_string('select_item_type', 'feedback')), $feedback_options );    
    return $feedback_options;
}

/** 
 *  creates a new item-record
 *  @param object $data the data from edit_item_form
 *  @return int the new itemid
 */
function feedback_create_item($data) {
    $item = new object;
    $item->id = '';
    $item->feedback = intval($data->feedbackid);

    $item->template=0;
    if (isset($data->templateid)) {
            $item->template = intval($data->templateid);
    }    

    $itemname = trim($data->itemname);
    $item->name = addslashes($itemname ? $data->itemname : get_string('no_itemname', 'feedback'));
    
    //get the used class from item-typ
    $itemclass = 'feedback_item_'.$data->typ;
    //get the instance of the item class
    $itemobj = new $itemclass();
    $item->presentation = addslashes($itemobj->get_presentation($data));
    
    $item->hasvalue = $itemobj->get_hasvalue();
    
    $item->typ = $data->typ;
    $item->position = $data->position;

    $item->required=0;
    if (isset($data->required)) {
            $item->required=$data->required;
    }    

    return insert_record('feedback_item', $item);
}

/** 
 *  save the changes of a given item.
 *  @param object $item
 *  @param object $data the data from edit_item_form
 *  @return boolean
 */
function feedback_update_item($item, $data = null){
    if($data != null){
        $itemname = trim($data->itemname);
        $item->name = addslashes($itemname ? $data->itemname : get_string('no_itemname', 'feedback'));
    
        //get the used class from item-typ
        $itemclass = 'feedback_item_'.$data->typ;
        //get the instance of the item class
        $itemobj = new $itemclass();
        $item->presentation = addslashes($itemobj->get_presentation($data));

        $item->required=0;
        if (isset($data->required)) {
            $item->required=$data->required;
        } 
    }else {
        $item->name = addslashes($item->name);
        $item->presentation = addslashes($item->presentation);
    }

    return update_record("feedback_item", $item);
}

/** 
 *  deletes a item and also deletes all related values
 *  @param int $itemid
 *  @param boolean $renumber should the kept items renumbered Yes/No
 *  @return void
 */
function feedback_delete_item($itemid, $renumber = true){
    $item = get_record('feedback_item', 'id', $itemid);
    @delete_records("feedback_value", "item", $itemid);
    @delete_records("feedback_valuetmp", "item", $itemid);
    delete_records("feedback_item", "id", $itemid);
    if($renumber) {
        feedback_renumber_items($item->feedback);
    }
}

/** 
 *  deletes all items of the given feedbackid
 *  @param int $feedbackid
 *  @return void
 */
function feedback_delete_all_items($feedbackid){
    if(!$items = get_records('feedback_item', 'feedback', $feedbackid)) {
        return;
    }
    foreach($items as $item) {
        feedback_delete_item($item->id, false);
    }
    @delete_records('feedback_completedtmp', 'feedback', $feedbackid);
    @delete_records('feedback_completed', 'feedback', $feedbackid);
}

/** 
 *  this function toggled the item-attribute required (yes/no)
 *  @param object $item
 *  @return boolean
 */
function feedback_switch_item_required($item) {
    if($item->required == 1) {
        $item->required = 0;
    } else {
        $item->required = 1;
    }
    $item->name = addslashes($item->name);
    $item->presentation = addslashes($item->presentation);
    return update_record('feedback_item', $item);
}

/** 
 *  renumbers all items of the given feedbackid
 *  @param int $feedbackid
 *  @return void
 */
function feedback_renumber_items($feedbackid){
    $items = get_records('feedback_item', 'feedback', $feedbackid, 'position');
    $pos = 1;
    if($items) {
        foreach($items as $item){
            $item->position = $pos;
            $pos++;
            feedback_update_item($item);
        }
    }
}

/** 
 *  this decreases the position of the given item
 *  @param object $item
 *  @return void
 */
function feedback_moveup_item($item){
    if($item->position == 1) return;
    $item_before = get_record_select('feedback_item', 'feedback = '.$item->feedback.' AND position = '.$item->position . ' - 1');
    $item_before->position = $item->position;
    $item->position--;
    feedback_update_item($item_before);
    feedback_update_item($item);
}

/** 
 *  this increased the position of the given item
 *  @param object $item
 *  @return void
 */
function feedback_movedown_item($item){
    if(!$item_after = get_record_select('feedback_item', 'feedback = '.$item->feedback.' AND position = '.$item->position . ' + 1'))
    {
        return;
    }
    
    $item_after->position = $item->position;
    $item->position++;
    feedback_update_item($item_after);
    feedback_update_item($item);
}

/** 
 *  here the position of the given item will be set to the value in $pos
 *  @param object $moveitem
 *  @param int $pos
 *  @return boolean
 */
function feedback_move_item($moveitem, $pos){
    if($moveitem->position == $pos) return true;
    if(!$allitems = get_records('feedback_item', 'feedback', $moveitem->feedback, 'position')){
        return false;
    }
    if(is_array($allitems)) {
        $index = 1;
        foreach($allitems as $item) {
            if($item->id == $moveitem->id) continue; //the moving item is handled special
            
            if($index == $pos) {
                $moveitem->position = $index;
                feedback_update_item($moveitem);
                $index++;
            }
            $item->position = $index;
            feedback_update_item($item);
            $index++;
        }
        if($pos >= count($allitems)) {
            $moveitem->position = $index;
            feedback_update_item($moveitem);
        }
        return true;
    }
    return false;
}

/** 
 *  prints the given item.
 *  if $readonly is set true so the ouput only is for showing responses and not for editing or completing.
 *  each item-class has an own print_item function implemented.
 *  @param object $item the item what we want to print out
 *  @param mixed $value the value if $readonly is set true and we showing responses
 *  @param boolean $readonly
 *  @param boolean $edit should the item print out for completing or for editing?
 *  @param boolean $highlightrequire if this set true and the value are false on completing so the item will be highlighted
 *  @return void
 */
function feedback_print_item($item, $value = false, $readonly = false, $edit = false, $highlightrequire = false){
    if($item->typ == 'pagebreak') return;
    if($readonly)$ro = 'readonly="readonly" disabled="disabled"';
        
    //get the class of the given item-typ
    $itemclass = 'feedback_item_'.$item->typ;
    //get the instance of the item-class
    $itemobj = new $itemclass();
    $itemobj->print_item($item, $value, $readonly, $edit, $highlightrequire);
}

/** 
 *  if the user completes a feedback and there is a pagebreak so the values are saved temporary.
 *  the values are saved permanently not until the user click on save button
 *  @param object $feedbackcompleted
 *  @return object temporary saved completed-record
 */
function feedback_set_tmp_values($feedbackcompleted){
    //first we create a completedtmp
    $tmpcpl = new object();
    foreach($feedbackcompleted as $key => $value) {
        $tmpcpl->{$key} = $value;
    }
    // $tmpcpl = $feedbackcompleted;
    $tmpcpl->id = '';
    $tmpcpl->timemodified = time();
    if(!$tmpcpl->id = insert_record('feedback_completedtmp', $tmpcpl)) {
        error('failed create completedtmp');
    }
    //get all values of original-completed
    if(!$values = get_records('feedback_value', 'completed', $feedbackcompleted->id)) {
        return;
    }
    foreach($values as $value) {
        $value->id = '';
        $value->completed = $tmpcpl->id;
        $value->value = addslashes($value->value);
        insert_record('feedback_valuetmp', $value);
    }
    return $tmpcpl;
}

/** 
 *  this saves the temporary saved values permanently
 *  @param object $feedbackcompletedtmp the temporary completed
 *  @param object $feedbackcompleted the target completed
 *  @param int $userid
 *  @return int the id of the completed
 */
function feedback_save_tmp_values($feedbackcompletedtmp, $feedbackcompleted, $userid) {
    $tmpcplid = $feedbackcompletedtmp->id;
    if(!$feedbackcompleted) {

        //first we create a completedtmp
        $newcpl = new object();
        foreach($feedbackcompletedtmp as $key => $value) {
            $newcpl->{$key} = $value;
        }

        $newcpl->id = '';
        $newcpl->userid = $userid;
        $newcpl->timemodified = time();
        if(!$newcpl->id = insert_record('feedback_completed', $newcpl)) {
            error('failed create completed');
        }
        //get all values of tmp-completed
        if(!$values = get_records('feedback_valuetmp', 'completed', $feedbackcompletedtmp->id)) {
            return false;
        }

        foreach($values as $value) {
            $value->id = '';
            $value->completed = $newcpl->id;
            insert_record('feedback_value', $value);
        }
        //drop all the tmpvalues
        delete_records('feedback_valuetmp', 'completed', $tmpcplid);
        delete_records('feedback_completedtmp', 'id', $tmpcplid);
        return $newcpl->id;
    }else {
        //first drop all existing values
        delete_records('feedback_value', 'completed', $feedbackcompleted->id);
        //update the current completed
        $feedbackcompleted->timemodified = time();
        update_record('feedback_completed', $feedbackcompleted);
        //save all the new values from feedback_valuetmp
        //get all values of tmp-completed
        if(!$values = get_records('feedback_valuetmp', 'completed', $feedbackcompletedtmp->id)) {
            return false;
        }
        foreach($values as $value) {
            $value->id = '';
            $value->completed = $feedbackcompleted->id;
            insert_record('feedback_value', $value);
        }
        //drop all the tmpvalues
        delete_records('feedback_valuetmp', 'completed', $tmpcplid);
        delete_records('feedback_completedtmp', 'id', $tmpcplid);
        return $feedbackcompleted->id;
    }
}

/** 
 *  deletes the given temporary completed and all related temporary values
 *  @param int $tmpcplid
 *  @return void
 */
function feedback_delete_completedtmp($tmpcplid) {
        delete_records('feedback_valuetmp', 'completed', $tmpcplid);
        delete_records('feedback_completedtmp', 'id', $tmpcplid);
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the pagebreaks
////////////////////////////////////////////////

/** 
 *  this creates a pagebreak.
 *  a pagebreak is a special kind of item
 *  @param int $feedbackid
 *  @return mixed false if there already is a pagebreak on last position or the id of the pagebreak-item
 */
function feedback_create_pagebreak($feedbackid) {
    //check if there already is a pagebreak on the last position
    $lastposition = count_records('feedback_item', 'feedback', $feedbackid);
    if($lastposition == feedback_get_last_break_position($feedbackid)) {
        return false;
    }
    
    $item = new object();
    $item->id = '';
    $item->feedback = $feedbackid;

    $item->template=0;

    $item->name = '';
    
    $item->presentation = '';
    $item->hasvalue = 0;
    
    $item->typ = 'pagebreak';
    $item->position = $lastposition + 1;

    $item->required=0;

    return insert_record('feedback_item', $item);
}

/** 
 *  get all positions of pagebreaks in the given feedback
 *  @param int $feedbackid
 *  @return array all ordered pagebreak positions
 */
function feedback_get_all_break_positions($feedbackid) {
    if(!$allbreaks = get_records_select_menu('feedback_item', "typ = 'pagebreak' AND feedback = ".$feedbackid, 'position', 'id, position')) return false;
    return array_values($allbreaks);
}

/** 
 *  get the position of the last pagebreak
 *  @param int $feedbackid
 *  @return int the position of the last pagebreak
 */
function feedback_get_last_break_position($feedbackid) {
    if(!$allbreaks = feedback_get_all_break_positions($feedbackid)) return false;
    return $allbreaks[count($allbreaks) - 1];
}

/** 
 *  this returns the position where the user can continue the completing.
 *  @param int $feedbackid
 *  @param int $courseid
 *  @param string $guestid this id will be saved temporary and is unique
 *  @return int the position to continue
 */
function feedback_get_page_to_continue($feedbackid, $courseid = false, $guestid) {
    global $CFG, $USER;
    
    //is there any break?
    
    if(!$allbreaks = feedback_get_all_break_positions($feedbackid)) return false;
    
    if($courseid) {
        $courseselect = "fv.course_id = ".$courseid;
    }else {
        $courseselect = "1";
    }
    
    if($guestid) {
        $userselect = "AND fc.guestid = '".$guestid."'";
        $usergroup = "GROUP BY fc.guestid";
    }else {
        $userselect = "AND fc.userid = ".$USER->id;
        $usergroup = "GROUP BY fc.userid";
    }
    
    
    $sql =  "SELECT MAX(fi.position)
                FROM ".$CFG->prefix."feedback_completedtmp AS fc, ".$CFG->prefix."feedback_valuetmp AS fv, ".$CFG->prefix."feedback_item AS fi
                WHERE fc.id = fv.completed
                    ".$userselect."
                    AND fc.feedback = ".$feedbackid."
                    AND ".$courseselect."
                    AND fi.id = fv.item
                ".$usergroup;
    
    $lastpos = get_field_sql($sql);

    //the index of found pagebreak is the searched pagenumber
    foreach($allbreaks as $pagenr => $br) {
        if($lastpos < $br) return $pagenr;
    }
    return count($allbreaks);
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the values
////////////////////////////////////////////////

/** 
 *  this saves the values of an completed.
 *  if the param $tmp is set true so the values are saved temporary in table feedback_valuetmp
 *  if there is already a completed and the userid is set so the values are updated
 *  on all other things new value records will be created
 *  @param object $data the data from complete form
 *  @param int $userid
 *  @param boolean $tmp
 *  @return mixed false on error or the completeid
 */
function feedback_save_values($data, $usrid, $tmp = false) {
    $tmpstr = $tmp ? 'tmp' : '';
         $time = time(); //arb
         $timemodified = mktime(0, 0, 0, date('m', $time),date('d', $time),date('Y', $time)); //arb
//         $timemodified = time();      
    if($usrid == 0) {
        return feedback_create_values($data, $usrid, $timemodified, $tmp);
    }
    if(!$data['completedid'] or !$completed = get_record('feedback_completed'.$tmpstr, 'id', $data['completedid'])){
        return feedback_create_values($data, $usrid, $timemodified, $tmp);
    }else{
        $completed->timemodified = $timemodified;
        return feedback_update_values($data, $completed, $tmp);
    }
}

/** 
 *  this saves the values from anonymous user such as guest on the main-site
 *  @param object $data the data form complete_guest form
 *  @param string $guestid the unique guestidentifier
 *  @return mixed false on error or the completeid
 */
function feedback_save_guest_values($data, $guestid) {
    $timemodified = time();
    if(!$completed = get_record('feedback_completedtmp', 'id', $data['completedid'])){
        return feedback_create_values($data, 0, $timemodified, true, $guestid);
    }else {
        $completed->timemodified = $timemodified;
        return feedback_update_values($data, $completed, true);
    }
}

/** 
 *  get the value from the given item related to the given completed.
 *  the value can come as temporary or as permanently value. the deciding is done by $tmp
 *  @param int $completeid
 *  @param int $itemid
 *  @param boolean $tmp
 *  @return mixed the value, the type depends on plugin-definition
 */
function feedback_get_item_value($completedid, $itemid, $tmp = false) {
    $tmpstr = $tmp ? 'tmp' : '';
    return get_field('feedback_value'.$tmpstr, 'value', 'completed', $completedid, 'item', $itemid);
}

/** 
 *  this function checks the correctness of values.
 *  the rules for this are implemented in the class of each item.
 *  it can be the required attribute or the value self e.g. numeric.
 *  the params first/lastitem are given to determine the visible range between pagebreaks.
 *  @param object $data the data of complete form
 *  @param int $firstitem the position of firstitem for checking
 *  @param int $lastitem the position of lastitem for checking
 *  @return boolean
 */
function feedback_check_values($data, $firstitem, $lastitem) {
    //get all items between the first- and lastitem
    $select = "feedback = ".intval($data['feedbackid'])."
                    AND position >= ".$firstitem."
                    AND position <= ".$lastitem."
                    AND hasvalue = 1";
    if(!$feedbackitems = get_records_select('feedback_item', $select)) {
        //if no values are given so no values can be wrong ;-)
        return true;
    }

    foreach($feedbackitems as $item) {
        //the name of the input field of the completeform is given in a special form:
        //<item-typ>_<item-id> eg. numeric_234
        //this is the key to get the value for the correct item
        $formvalname = $item->typ . '_' . $item->id;
        
        //check if the value is set
        if((!isset($data[$formvalname])) AND ($item->required == 1)) {
            return false;
        }
        
        //if there is a value so save it temporary
        $value = isset($data[$formvalname]) ? $data[$formvalname] : '';

        //get the class of the item-typ
        $itemclass = 'feedback_item_'.$item->typ;
        //get the instance of the item-class
        $itemobj = new $itemclass();
        
        //now we let check the value by the item-class
        if(!$itemobj->check_value($value, $item)) {
            return false;
        }
    }
    //if no wrong values so we can return true
    return true;
}

/** 
 *  this function create a complete-record and the related value-records.
 *  depending on the $tmp (true/false) the values are saved temporary or permanently
 *  @param object $data the data of the complete form
 *  @param int $userid
 *  @param int $timemodified
 *  @param boolean $tmp
 *  @param string $guestid a unique identifier to save temporary data
 *  @return mixed false on error or the completedid
 */
function feedback_create_values($data, $usrid, $timemodified, $tmp = false, $guestid = false){
    $tmpstr = $tmp ? 'tmp' : '';
    //first we create a new completed record
    $completed = null;
    $completed->id = '';
    $completed->feedback = $data['feedbackid'];
    $completed->userid = $usrid;
    $completed->guestid = $guestid;
    $completed->timemodified = $timemodified;
    $completed->anonymous_response = $data['anonymous_response'];
    
    if(!$completedid = insert_record('feedback_completed'.$tmpstr, $completed)) {
        return false;
    }
    
    $completed = null;
    $completed = get_record('feedback_completed'.$tmpstr, 'id', $completedid);

    //$data includes an associative array. the keys are in the form like abc_xxx
    //with explode we make an array with(abc, xxx) and (abc=typ und xxx=itemnr)
    $keys = array_keys($data);
    $errcount = 0;
    foreach($keys as $key){
        //ensure the keys are what we want
        if(eregi('([a-z0-9]{1,})_([0-9]{1,})',$key)){            
            $value = null;
            $itemnr = explode('_', $key);
            $value->id = '';
            $value->item = intval($itemnr[1]);
            $value->completed = $completed->id;
            $value->course_id = intval($data['courseid']);
            
            //get the class of item-typ
            $itemclass = 'feedback_item_'.$itemnr[0];
            //get the instance of item-class
            $itemobj = new $itemclass();
            //the kind of values can be absolutely different so we run create_value directly by the item-class
            $value->value = $itemobj->create_value($data[$key]);

            if(!insert_record('feedback_value'.$tmpstr, $value)) {
                $errcount++;
            }
        }
    }
    
    //if nothing is wrong so we can return the completedid otherwise false
    return $errcount == 0 ? $completed->id : false;
}

/** 
 *  this function updates a complete-record and the related value-records.
 *  depending on the $tmp (true/false) the values are saved temporary or permanently
 *  @param object $data the data of the complete form
 *  @param object $completed
 *  @param boolean $tmp
 *  @return int the completedid
 */
function feedback_update_values($data, $completed, $tmp = false){
    $tmpstr = $tmp ? 'tmp' : '';
    
    update_record('feedback_completed'.$tmpstr, $completed);
    //get the values of this completed
    $values = get_records('feedback_value'.$tmpstr,'completed', $completed->id);
    
    //$data includes an associative array. the keys are in the form like abc_xxx
    //with explode we make an array with(abc, xxx) and (abc=typ und xxx=itemnr)
    $keys = array_keys($data);
    foreach($keys as $key){
        //ensure the keys are what we want
        if(eregi('([a-z0-9]{1,})_([0-9]{1,})',$key)){            
            //build the new value to update([id], item, completed, value)
            $itemnr = explode('_', $key);
            $newvalue = null;
            $newvalue->id = '';
            $newvalue->item = intval($itemnr[1]);
            $newvalue->completed = $completed->id;
            $newvalue->course_id = intval($data['courseid']);
            
            //get the class of item-typ
            $itemclass = 'feedback_item_'.$itemnr[0];
            //get the instace of the item-class
            $itemobj = new $itemclass();
            //the kind of values can be absolutely different so we run create_value directly by the item-class
            $newvalue->value = $itemobj->create_value($data[$key]);
            
            //check, if we have to create or update the value
            $exist = false;
            foreach($values as $value){
                if($value->item == $newvalue->item){
                    $newvalue->id = $value->id;
                    $exist = true;
                    break;
                }
            }
            if($exist){
                update_record('feedback_value'.$tmpstr, $newvalue);
            }else {
                insert_record('feedback_value'.$tmpstr, $newvalue);
            }
            
        }
    }

    return $completed->id;
}

/** 
 *  get the values of an item depending on the given groupid.
 *  if the feedback is anonymous so the values are shuffled
 *  @param object $item
 *  @param int $groupid
 *  @param int $courseid
 *  @return array the value-records
 */
function feedback_get_group_values($item, $groupid = false, $courseid = false){
    global $CFG;

    //if the groupid is given?
    if(intval($groupid) > 0) {
        $query = 'SELECT fbv .  *
                        FROM '.$CFG->prefix . 'feedback_value AS fbv, '.$CFG->prefix . 'feedback_completed AS fbc, '.$CFG->prefix . 'groups_members AS gm
                        WHERE fbv.item = '.$item->id . '
                             AND fbv.completed = fbc.id 
                             AND fbc.userid = gm.userid 
                             AND gm.groupid = '.$groupid . '
                        ORDER BY fbc.timemodified';
        $values = get_records_sql($query);
    }else {
        if ($courseid) {
             $values = get_records_select('feedback_value', "item = '$item->id' and course_id = '$courseid'");
        } else {
             $values = get_records('feedback_value', 'item', $item->id);
        }
    }    
    if(get_field('feedback', 'anonymous', 'id', $item->feedback) == FEEDBACK_ANONYMOUS_YES) {
        if(is_array($values))
            shuffle($values);
    }
    return $values;
}

/** 
 *  check for multiple_submit = false.
 *  if the feedback is global so the courseid must be given
 *  @param int $feedbackid
 *  @param int $courseid
 *  @return boolean true if the feedback already is submitted otherwise false
 */
function feedback_is_already_submitted($feedbackid, $courseid = false) {
    global $USER;
    
    $select = 'userid = '.$USER->id.' AND feedback = '.$feedbackid;
    if(!$trackings = get_records_select_menu('feedback_tracking', $select, '', 'id, completed')) {
        return false;
    }

    if($courseid) {
        $select = 'completed IN ('.implode(',',$trackings).') AND course_id = '.$courseid;
        if(!$values = get_records_select('feedback_value', $select)) {
            return false;
        }
    }

    return true;
}

/** 
 *  if the completion of a feedback will be continued eg. by pagebreak or by multiple submit so the complete must be found.
 *  if the param $tmp is set true so all things are related to temporary completeds
 *  @param int $feedbackid
 *  @param boolean $tmp
 *  @param int $courseid
 *  @param string $guestid
 *  return int the id of the found completed
 */
function feedback_get_current_completed($feedbackid, $tmp = false, $courseid = false, $guestid = false) {
    global $USER, $CFG;
    
    $tmpstr = $tmp ? 'tmp' : '';
    
    if(!$courseid) {
        if($guestid) {
            return get_record('feedback_completed'.$tmpstr,'feedback', $feedbackid, 'guestid', $guestid);
        }else {
            return get_record('feedback_completed'.$tmpstr,'feedback', $feedbackid, 'userid', $USER->id);
        }
    }
    
    $courseid = intval($courseid);
    
    if($guestid) {
        $userselect = "AND fc.guestid = '".$guestid."'";
    }else {
        $userselect = "AND fc.userid = ".$USER->id;
    }
    //if courseid is set the feedback is global. there can be more than one completed on one feedback
    $sql =  "SELECT fc.* FROM
                    ".$CFG->prefix."feedback_value".$tmpstr." AS fv, ".$CFG->prefix."feedback_completed".$tmpstr." AS fc
                WHERE fv.course_id = ".$courseid."
                    AND fv.completed = fc.id
                    ".$userselect."
                    AND fc.feedback = ".$feedbackid;

    if(!$sqlresult = get_records_sql($sql)) return false;
    foreach($sqlresult as $r) {
        return get_record('feedback_completed'.$tmpstr, 'id', $r->id);
    }
}

/** 
 *  get the completeds depending on the given groupid.
 *  @param object $feedback
 *  @param int $groupid
 *  @return mixed array of found completeds otherwise false
 */
function feedback_get_completeds_group($feedback, $groupid = false, $courseid = false) {
    global $CFG;
    if(intval($groupid) > 0){
        $query = 'SELECT fbc.*
                        FROM '.$CFG->prefix . 'feedback_completed AS fbc, '.$CFG->prefix . 'groups_members AS gm
                        WHERE  fbc.feedback = '.$feedback->id . '
                            AND gm.groupid = '.$groupid . '
                            AND fbc.userid = gm.userid';
        if($values = get_records_sql($query)) {
            return $values;
        }else {return false;}
    }else {
        if($courseid) {
            $query = 'SELECT DISTINCT fbc.*
                        FROM '.$CFG->prefix . 'feedback_completed AS fbc, '.$CFG->prefix . 'feedback_value AS fbv
                        WHERE fbc.id = fbv.completed
                            AND fbc.feedback = '.$feedback->id . '
                            AND fbv.course_id = '.$courseid . '
                        ORDER BY random_response';
            if ($values = get_records_sql($query)) {
                return $values;
            } else {
                return false;
            }

        }else {
            if($values = get_records('feedback_completed', 'feedback', $feedback->id)){
                return $values;
            }else{return false;}
        }
    }
}

/** 
 *  get the count of completeds depending on the given groupid.
 *  @param object $feedback
 *  @param int $groupid
 *  @param int $courseid
 *  @return mixed count of completeds or false
 */
function feedback_get_completeds_group_count($feedback, $groupid = false, $courseid = false) {
    global $CFG;
    if($courseid > 0 AND !$groupid <= 0) {
        $sql = 'SELECT id, COUNT( item ) ci
                    FROM  '.$CFG->prefix . 'feedback_value 
                    WHERE  course_id  = '.$courseid.'
                    GROUP  BY  item ORDER BY ci DESC';
        if($foundrecs = get_records_sql($sql)) {
            $foundrecs = array_values($foundrecs);
            return $foundrecs[0]->ci;
        }
        return false;
    }
    if($values = feedback_get_completeds_group($feedback, $groupid)) {
        return sizeof($values);
    }else {
        return false;
    }
}

/* get the own groupid.
@param object $course
@param object $cm
function feedback_get_groupid($course, $cm) {
    $groupmode = groupmode($course, $cm);
    
    //get groupid
    if($groupmode > 0 && !isadmin()) {
        if($mygroupid = mygroupid($course->id)) {
            return $mygroupid[0]; //get the first groupid
        }
    }else {
        return false;
    }
}
 */

/** 
 *  deletes all completed-recordsets from a feedback.
 *  all related data such as values also will be deleted
 *  @param int $feedbackid
 *  @return void
 */
function feedback_delete_all_completeds($feedbackid) {
    if(!$completeds = get_records('feedback_completed', 'feedback', $feedbackid)) return;
    foreach($completeds as $completed) {
        feedback_delete_completed($completed->id);
    }
}

/** 
 *  deletes a completed given by completedid.
 *  all related data such values or tracking data also will be deleted
 *  @param int $completedid
 *  @return boolean
 */
function feedback_delete_completed($completedid) {
    if(!$completed = get_record('feedback_completed', 'id', $completedid)) {
        return false;
    }
    //first we delete all related values
    @delete_records('feedback_value', 'completed', $completed->id);
    
    //now we delete all tracking data
    if($tracking = get_record_select('feedback_tracking', " completed = ".$completed->id." AND feedback = ".$completed->feedback." ")) {
        @delete_records('feedback_tracking', 'completed', $completed->id);
    }
        
    //last we delete the completed-record
    return delete_records('feedback_completed', 'id', $completed->id);
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle sitecourse mapping
////////////////////////////////////////////////

/** 
 *  checks if the course and the feedback is in the table feedback_sitecourse_map.
 *  @param int $feedbackid
 *  @param int $courseid
 *  @return int the count of records
 */
function feedback_is_course_in_sitecourse_map($feedbackid, $courseid) {
    global $CFG;
    return count_records('feedback_sitecourse_map', 'feedbackid', $feedbackid, 'courseid', $courseid);
}

/** 
 *  checks if the feedback is in the table feedback_sitecourse_map.
 *  @param int $feedbackid
 *  @return boolean
 */
function feedback_is_feedback_in_sitecourse_map($feedbackid) {
    global $CFG;
    return record_exists('feedback_sitecourse_map', 'feedbackid', $feedbackid);
}

/** 
 *  gets the feedbacks from table feedback_sitecourse_map.
 *  this is used to show the global feedbacks on the feedback block
 *  all feedbacks with the following criteria will be selected:<br />
 *  1) all feedbacks which id are listed together with the courseid in sitecoursemap and<br />
 *  2) all feedbacks which not are listed in sitecoursemap
 *  @param int $courseid
 *  @return array the feedback-records
 */
function feedback_get_feedbacks_from_sitecourse_map($courseid) {
    global $CFG;
    
    //first get all feedbacks listed in sitecourse_map with named courseid
    $sql = "SELECT f.id as id, cm.id as cmid, f.name as name, f.timeopen as timeopen, f.timeclose as timeclose
              FROM {$CFG->prefix}feedback f, {$CFG->prefix}course_modules cm, {$CFG->prefix}feedback_sitecourse_map sm, {$CFG->prefix}modules m
              WHERE f.id = cm.instance
                AND f.course = '".SITEID."'
                AND m.id = cm.module 
                AND m.name = 'feedback'
                AND sm.courseid = $courseid 
                AND sm.feedbackid = f.id";
    
    if(!$feedbacks1 = get_records_sql($sql)) {
        $feedbacks1 = array();
    }
    
    //second get all feedbacks not listed in sitecourse_map
    $feedbacks2 = array();
    $sql = "SELECT f.id as id, cm.id as cmid, f.name as name, f.timeopen as timeopen, f.timeclose as timeclose
              FROM {$CFG->prefix}feedback f, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
              WHERE f.id = cm.instance
                AND f.course = '".SITEID."'
                AND m.id = cm.module
                AND m.name = 'feedback'";
    if(!$allfeedbacks = get_records_sql($sql)) {
        $allfeedbacks = array();
    }
    foreach($allfeedbacks as $a) {
        if(!record_exists('feedback_sitecourse_map', 'feedbackid', $a->id)) {
            $feedbacks2[] = $a;
        }
    }
    
    return array_merge($feedbacks1, $feedbacks2);
    
}

/** 
 *  gets the courses from table feedback_sitecourse_map.
 *  @param int $feedbackid
 *  @return array the course-records
 */
function feedback_get_courses_from_sitecourse_map($feedbackid) {
    global $CFG;
    
    $sql = "select f.id, f.courseid, c.fullname, c.shortname
                from {$CFG->prefix}feedback_sitecourse_map f, {$CFG->prefix}course c
                where c.id = f.courseid
                and f.feedbackid = $feedbackid
                order by c.fullname";
    
    return get_records_sql($sql);
    
}

/** 
 *  removes non existing courses or feedbacks from sitecourse_map.
 *  it shouldn't be called all too often
 *  a good place for it could be the mapcourse.php or unmapcourse.php
 *  @return void
 */
function feedback_clean_up_sitecourse_map() {
    $maps = get_records('feedback_sitecourse_map');
    foreach($maps as $map) {
        if(!get_record('course', 'id', $map->courseid)) {
            delete_records('feedback_sitecourse_map', 'courseid', $map->courseid, 'feedbackid', $map->feedbackid);
            continue;
        }
        if(!get_record('feedback', 'id', $map->feedbackid)) {
            delete_records('feedback_sitecourse_map', 'courseid', $map->courseid, 'feedbackid', $map->feedbackid);
            continue;
        }
        
    }
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//not relatable functions
////////////////////////////////////////////////

/** 
 *  prints the option items of a selection-input item (dropdownlist).
 *  @param int $startval the first value of the list
 *  @param int $endval the last value of the list
 *  @param int $selectval which item should be selected
 *  @param int $interval the stepsize from the first to the last value
 *  @return void
 */
function feedback_print_numeric_option_list($startval, $endval, $selectval = '', $interval = 1){
    for($i = $startval; $i <= $endval; $i += $interval){
        if($selectval == ($i)){
            $selected = 'selected="selected"';
        }else{
            $selected = '';
        }
        echo '<option '.$selected.'>'.$i.'</option>';
    }
}

/** 
 *  sends an email to the teachers of the course where the given feedback is placed.
 *  @param object $cm the coursemodule-record
 *  @param $feedback
 *  @param $course
 *  @param $userid
 *  @return void
 */
function feedback_send_email($cm, $feedback, $course, $userid) {
    
    global $CFG;
    
    if ($feedback->email_notification == 0) {  // No need to do anything
        return;
    }
    
    $user = get_record('user', 'id', $userid);
    
    if (groupmode($course, $cm) == SEPARATEGROUPS) {    // Separate groups are being used
        $groups = get_records_sql_menu("SELECT g.name, g.id
                                          FROM {$CFG->prefix}groups g,
                                                 {$CFG->prefix}groups_members m
                                          WHERE g.courseid = '$course->id'
                                             AND g.id = m.groupid
                                             AND m.userid = '$userid'
                                             ORDER BY name ASC");
        $groups = array_values($groups);
        
        $teachers = feedback_get_receivemail_users($cm->id, $groups);
    } else {
        $teachers = feedback_get_receivemail_users($cm->id);
    }

    if ($teachers) {

        $strfeedbacks = get_string('modulenameplural', 'feedback');
        $strfeedback  = get_string('modulename', 'feedback');
        $strcompleted  = get_string('completed', 'feedback');
        $printusername = $feedback->anonymous == FEEDBACK_ANONYMOUS_NO ? fullname($user) : get_string('anonymous_user', 'feedback');
        
        foreach ($teachers as $teacher) {
            unset($info);
            $info->username = $printusername;
            $info->feedback = format_string($feedback->name,true);
            $info->url = $CFG->wwwroot.'/mod/feedback/show_entries.php?id='.$cm->id.'&userid='.$userid.'&do_show=showentries';

            $postsubject = $strcompleted.': '.$info->username.' -> '.$feedback->name;
            $posttext = feedback_send_email_text($info, $course);
            $posthtml = ($teacher->mailformat == 1) ? feedback_send_email_html($info, $course, $cm) : '';
            
            if($feedback->anonymous == FEEDBACK_ANONYMOUS_NO) {
                @email_to_user($teacher, $user, $postsubject, $posttext, $posthtml);
            }else {
                @email_to_user($teacher, $teacher, $postsubject, $posttext, $posthtml);
            }
        }
    }
}

/** 
 *  sends an email to the teachers of the course where the given feedback is placed.
 *  @param object $cm the coursemodule-record
 *  @param $feedback
 *  @param $course
 *  @return void
 */
function feedback_send_email_anonym($cm, $feedback, $course) {
    
    global $CFG;
    
    if ($feedback->email_notification == 0) {             // No need to do anything
        return;
    }
    
    // $teachers = get_course_teachers($course->id);
    $teachers = feedback_get_receivemail_users($cm->id);

    if ($teachers) {

        $strfeedbacks = get_string('modulenameplural', 'feedback');
        $strfeedback  = get_string('modulename', 'feedback');
        $strcompleted  = get_string('completed', 'feedback');
        $printusername = get_string('anonymous_user', 'feedback');
        
        foreach ($teachers as $teacher) {
            unset($info);
            $info->username = $printusername;
            $info->feedback = format_string($feedback->name,true);
            $info->url = $CFG->wwwroot.'/mod/feedback/show_entries_anonym.php?id='.$cm->id;

            $postsubject = $strcompleted.': '.$info->username.' -> '.$feedback->name;
            $posttext = feedback_send_email_text($info, $course);
            $posthtml = ($teacher->mailformat == 1) ? feedback_send_email_html($info, $course, $cm) : '';
            
            @email_to_user($teacher, $teacher, $postsubject, $posttext, $posthtml);
        }
    }
}

/** 
 *  send the text-part of the email
 *  @param object $info includes some infos about the feedback you want to send
 *  @param object $course
 *  @return string the text you want to post
 */
function feedback_send_email_text($info, $course) {
    $posttext  = $course->shortname.' -> '.get_string('modulenameplural', 'feedback').' -> '.
                    $info->feedback."\n";
    $posttext .= '---------------------------------------------------------------------'."\n";
    $posttext .= get_string("emailteachermail", "feedback", $info)."\n";
    $posttext .= '---------------------------------------------------------------------'."\n";
    return $posttext;
}


/** 
 *  send the html-part of the email
 *  @param object $info includes some infos about the feedback you want to send
 *  @param object $course
 *  @return string the text you want to post
 */
function feedback_send_email_html($info, $course, $cm) {
    global $CFG;
    $posthtml  = '<p><font face="sans-serif">'.
                '<a href="'.$CFG->wwwroot.htmlspecialchars('/course/view.php?id='.$course->id).'">'.$course->shortname.'</a> ->'.
                '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/feedback/index.php?id='.$course->id).'">'.get_string('modulenameplural', 'feedback').'</a> ->'.
                '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/feedback/view.php?id='.$cm->id).'">'.$info->feedback.'</a></font></p>';
    $posthtml .= '<hr /><font face="sans-serif">';
    $posthtml .= '<p>'.get_string('emailteachermailhtml', 'feedback', $info).'</p>';
    $posthtml .= '</font><hr />';
    return $posthtml;
}

/** 
 *  print some errors to inform users about this.
 *  @return void
 */
function feedback_print_errors() {
 
    global $SESSION;
        
    if(empty($SESSION->feedback->errors)) {
        return;
    }

    // print_simple_box_start("center", "60%", "#FFAAAA", 20, "noticebox");
    print_box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');
    print_heading(get_string('handling_error', 'feedback'));

    echo '<p align="center"><b><font color="black"><pre>';
    print_r($SESSION->feedback->errors) . "\n";
    echo '</pre></font></b></p>';
    
    // print_simple_box_end();
    print_box_end();
    echo '<br /><br />';
    $SESSION->feedback->errors = array(); //remove errors
} 

function feedback_encode_target_url($url) {
    if (strpos($url, '?')) {
        list($part1, $part2) = explode('?', $url, 2); //maximal 2 parts
        return $part1 . '?' . htmlentities($part2);
    } else {
        return $url;
    }
}
?>
