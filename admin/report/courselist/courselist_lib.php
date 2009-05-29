<?php

class courselistoptions_form extends moodleform {
    function definition (){
        $strsortby = get_string('sortby');
        $strname = get_string('name');
        $strshortname = get_string('shortname');
        $strcreationdate = get_string('creationdate', 'report_courselist');
        $strdisplaystaff = get_string('displaystaff', 'report_courselist');
        $strdisplaycategory = get_string('displaycategory', 'report_courselist');
        $strcategory = get_string('category');
        $strupdate = get_string('update');
        $mform =& $this->_form;
        $mform->addElement('select', 'sortby', $strsortby, array('fullname'=>$strname, 'shortname'=>$strshortname, 'timecreated'=>$strcreationdate, 'category'=>$strcategory));
        $mform->addElement('checkbox', 'cat', $strdisplaycategory);
        $mform->addElement('checkbox', 'staff', $strdisplaystaff);
        $this->add_action_buttons(false, $strupdate);
    }
}

function displayOptionsForm($justgetoptions=0) {
	$options = array();
    $options['staff'] = optional_param('staff', 0, PARAM_INT);
    $options['cat'] = optional_param('cat', 0, PARAM_INT);
	$mform = new courselistoptions_form(null, null, 'GET');
	if ($formdata = $mform->get_data()) {
	    $options['sortby'] = $formdata->sortby;
	}
    else {
	    $options['sortby'] = 'fullname';
        $formdata = new object();
        $formdata->sortby = $options['sortby'];
        $formdata->staff = $options['staff'];
        $formdata->cat = $options['cat'];
        $mform->set_data($formdata);
    }
    if(!$justgetoptions) {
		$mform->display();
    }
    return $options;
}

function get_teachers($context)
{
// this really needs a lot more - only works if no role overrides at the moment I think....
// See /moodle/course/lib.php at about line 1873
	global $CFG;
    $managerroles = split(',', $CFG->coursemanager);
    $rusers = get_role_users($managerroles, $context, true, '', 'r.sortorder ASC, u.lastname ASC');
    return $rusers;
}

function get_category_name($catid) {
	static $catcache = array();
    if(!array_key_exists($catid, $catcache)) {
    	$cat = get_record('course_categories','id',$catid);
        if($cat) {
        	if($cat->parent != 0) {
		        $catcache[$catid] = get_category_name($cat->parent) . ' / ' .$cat->name;
            }
            else {
		        $catcache[$catid] = $cat->name;
            }
        }
        else {
	        $catcache[$catid] = '';
        }
    }
    return $catcache[$catid];
}

?>
