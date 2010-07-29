<?php

require_once('../../config.php');
require_once('../../course/lib.php');
require_once('../../backup/lib.php');
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/ajax/ajaxlib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
include_once('lib/archive_forum.php');
include_once('lib/archive_wiki.php');
include_once('lib/archive_book.php');
include_once('lib/archive_resource.php');
require_once('lib/lib.php');
require_once('lib/print_section.php');

$id = optional_param('id', 0, PARAM_INT);
$archiverole = optional_param('role', -1, PARAM_INT);
$showAllBlocks = optional_param('allblocks', false, PARAM_BOOL);
$hideUnarchivedMods = optional_param('hideum', false, PARAM_BOOL);
$userinfo = optional_param('userinfo', false, PARAM_BOOL);
$wikihist = optional_param('wikihist', false, PARAM_BOOL);
if($id>0) {
    if (! ($course = get_record('course', 'id', $id)) ) {
        error('Invalid course id');
    }
    $COURSE = $course;
    prePrepareTemplatePage($course->id);
    $navlinks[] = array( 'name' => 'Archiving', 'link' => '', 'type' => 'title');
    $navigation = build_navigation($navlinks);
//    echo '<pre>'.htmlentities(print_r($navigation,1)).'</pre>';
    echo getTemplateHeader($course->fullname, $course->fullname, $navigation, false, true); //$ARCHIVE->originalheader;
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    require_login($course);
//    echo '<pre>'; print_r($USER); echo '</pre>';
    if($archiverole!=-1) {
        if(has_capability('moodle/course:update', $context)) {
            createArchive($course, $context, $archiverole, $showAllBlocks, $hideUnarchivedMods, $wikihist, $userinfo);
            //echo '<pre>'; print_r($ARCHIVE); echo '</pre>';
        } else {
            echo "<h1>Sorry, you are not allowed to do this...</h1>";
            echo "<a href='../../course/view.php?id={$course->id}'>Return to course</a>";
        }
    } else {
        echo optionsForm($course);
        echo "<a href='../../course/view.php?id={$course->id}'>Return to course</a>";
    }
    echo getTemplateFooter(true);
} else { // no course ID
    require_login();
    echo archiveCoursesList();
}

function createArchive($course, $context, $asRoleID, $showAllBlocks, $hideUnarchivedMods, $wikihist, $userinfo=0) {
    global $CFG, $SESSION;
    global $ARCHIVE;
    add_to_log($course->id, 'create_course_archive', 'Archiving course');
    $ARCHIVE->tmpDir = makeTempDir($course->id);
    $ARCHIVE->realwwwroot = $CFG->wwwroot;
    $ARCHIVE->fileroot = $CFG->wwwroot.'/file.php/'.$course->id;
    $CFG->wwwroot = md5(time());
    // $ARCHIVE_root is used for a place holder to point to the root of the
    // archive, process immages replaces it with the right number of ../ for the file.
    $ARCHIVE->root = md5($CFG->wwwroot);
    $ARCHIVE->modulelist=array();
    $ARCHIVE->showAllBlocks = $showAllBlocks;
    $ARCHIVE->hideUnarchivedMods = $hideUnarchivedMods;
    $ARCHIVE->includeWikiHistory = $wikihist;
    $ARCHIVE->userinfo = $userinfo;
    $CFG->pixpath = $CFG->wwwroot.'/pix';
    $CFG->modpixpath = $CFG->wwwroot.'/mod';
    //    	echo '<pre>'; print_r($CFG); echo '</pre>';
    //NSFB Two global var that will be filled with list of modules that need processed.
    $ARCHIVE->mods = array();
    $ARCHIVE->blocks = array();
    //NSFB Another global var that will be filled with list of file resources to include.
    $ARCHIVE->files = array();

    if($ARCHIVE->userinfo)
    {
    	$courseusers = getUserInfo($context);
        writeFile('users.txt',$courseusers);
    }
    //# Should check role properly
    role_switch($asRoleID, $context);
    prepareTemplatePage($course->id);
    ob_start();
    include('lib/courseview.php');
    $out = ob_get_contents();
    ob_end_clean();
    role_switch(0, $context);
    //  $out = replaceLoginLinks($out);  // Done in template preparation now
    $out = processImages($out, 0);
    //$out = processCSSLinks($out);   // Done in template preparation
    //#    	$out = processScripts($out);
    //#    	$out = processObjects($out);
    $out = processHyperlinks($out, $ARCHIVE->tmpDir);
    checkAndWriteFile('/index.html', $out);
    //$fp = fopen($ARCHIVE->tmpDir.'\\index.html', 'w');
    //fwrite($fp,$out);
    //fclose($fp);
    role_switch($asRoleID, $context);
    //	echo $out;
    /*
    echo '<h3>Blocks</h3>';
    echo '<ul>';
    foreach($ARCHIVE->blocks as $blk)
    {
    	//echo '<pre>'; print_r($mod); echo '</pre>';
        echo '<li><b>'.$blk['type']->name.'</b> ID='.$blk['block']->id;
        if(!$blk['block']->visible) echo ' (hidden)';
        echo '<br/>URL: ' . $blk['url'];
        echo '</li>';
    }
    echo '</ul>';
*/
    echo '<h3>Modules</h3>';
    //echo '<pre>'; print_r($ARCHIVE->mods); echo '</pre>';
    flush();
    foreach($ARCHIVE->mods as $mod) {
        //echo '<h3>Module</h3><pre>'; print_r($mod); echo '</pre>';
        if(function_exists('archive_'.$mod['mod']->modname)) {
            if($mod['mod']->modname !== 'resource') {
            	echo "Archiving mod{$mod['mod']->modname}<br/>";
            }
            $ok = call_user_func('archive_'.$mod['mod']->modname, $course, $mod['mod'], $mod['url']);
        } else {
            echo "<span style='color: gray;'>mod{$mod['mod']->modname}</span><br/>";
            //echo '<div><b>'.$mod['mod']->modfullname.'</b> ('.$mod['mod']->modname.') ID='.$mod['mod']->id;
            //echo ' URL: ' . $mod['url'];
            //echo '</div>';
        }
        flush();
		set_time_limit(60);
    }
    // Module list pages
    foreach($ARCHIVE->modulelist as $modname=>$data) {
        if(function_exists('build_'.$modname.'_listpage')) {
            call_user_func('build_'.$modname.'_listpage', $data);
        } else {
            build_default_listpage($modname, $data);
        }
		set_time_limit(60);
    }
    //echo "<pre>";
    //print_r($ARCHIVE->mods);
    //print_r($ARCHIVE->blocks);
    //echo "</pre>";
    role_switch(0, $context);
    // Process the final bits...
    // Collect files!
    //echo "<pre>"; print_r($ARCHIVE->files); echo "</pre>";
    foreach($ARCHIVE->files as $file) {
        $source = "{$CFG->dataroot}/{$course->id}$file";
        $target = $ARCHIVE->tmpDir .'/files'. $file;
        if(file_exists($source)) {
            checkdirexists($target);
            copy($source, $target);
        } else {
            echo "<span style='bgcolor:yellow; color:#cc0000;'>File $source not found.</span><br/>";
        }
		set_time_limit(60);
    }
    if(!isset($_REQUEST['testmode'])) {
        //echo '<p>Done! Going to try zipping now.</p>';
        $filelist = list_directories_and_files ($ARCHIVE->tmpDir);
        //Convert them to full paths
        $files = array();
        foreach ($filelist as $file) {
            $files[] = "{$ARCHIVE->tmpDir}/$file";
        }
        $status = zip_files($files, "{$ARCHIVE->tmpDir}.zip");
        $to_zip_file = $CFG->dataroot.'/'.$course->id.'/backupdata/'.substr($ARCHIVE->tmpDir,strrpos($ARCHIVE->tmpDir,'/')+1).'.zip';
        //echo "<p>Done! Copy {$ARCHIVE->tmpDir}.zip to $to_zip_file now.</p>";
        copy("{$ARCHIVE->tmpDir}.zip", $to_zip_file);
        //unlink("{$ARCHIVE->tmpDir}.zip");
        deltree($ARCHIVE->tmpDir);
        $backupFilesUrl = $ARCHIVE->realwwwroot.'/files/index.php?id='.$course->id.'&wdir=%2Fbackupdata';
        echo '<p><a href="'.$backupFilesUrl.'">Continue to download static archive '.substr($ARCHIVE->tmpDir,strrpos($ARCHIVE->tmpDir,'/')+1).'.zip</a></p>';
    } else {
        echo '<p>Done!</p>';
    }
}

function archiveCoursesList() {
    global $USER;
    $out = '';
    $courses = get_my_courses($USER->id);
    foreach($courses as $course) {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        if(has_capability('moodle/course:update', $context)) {
            $out .= '<a href="index.php?id='.$course->id.'&at=course">'.$course->shortname.' '.$course->fullname.'</a>';
        }else{
            $out .= $course->shortname.' '.$course->fullname;
        }
        $out .= ' <a href="../course/view.php?id='.$course->id.'">[view]</a><br/>';
    }
    return $out;
}

function makeTempDir($courseid) {
    global $CFG, $ARCHIVE;
    $ARCHIVE->tmpDir = $CFG->dataroot.'/temp/static_'.$courseid.'_'.time();
    mkdir($ARCHIVE->tmpDir);
    return $ARCHIVE->tmpDir;
}

function optionsForm($course) {
    //# This should really use the moodle form library, however for speed
    // of development I'm just using HTML for now.
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $aroles = get_assignable_roles($context);
    $out = '<form>';
    $out .= '<p>This archiving tool allows you to create a zip file containing a static html version of the course. It
    can only be used by people with editing rights for the course. The static html version only contains forums, files
    and other details that were visible to all students in the course at time the copy was made.</p>';
    $out .= 'Archive as user type: <select name="role">';
    foreach($aroles as $k=>$v) {
        $out .= "<option value='$k'";
        if($v=='Student') $out .= ' selected="1"';
        $out .= ">$v</option>";
    }
    $out .= '</select><br/>';
    $out .= 'Include placeholders for unarchived blocks <input type="checkbox" name="allblocks" value="1"/><br/>';
    $out .= 'Completely hide unarchived modules <input type="checkbox" name="hideum" value="1"/><br/>';
    $out .= 'Include Wiki histories <input type="checkbox" name="wikihist" value="1" checked="1"/><br/>';
    $out .= 'Include user information and group support <input type="checkbox" name="userinfo" value="1"/><br/>';
    //$out .= 'Run in test mode (don\'t zip) <input type="checkbox" name="testmode" value="1" checked="1"/><br/>';
    $out .= '<input type="hidden" name="id" value="'.$course->id.'"/>';
    $out .= '<input type="submit" value="Create archive"/></form>';
    return $out;
}


?>

