<?php
include_once('../../mod/resource/lib.php');
include_once('archive_resource_file.php');
include_once('lib.php');

function archive_resource($course, $modresource, $relurl) {
    global $CFG, $COURSE, $ARCHIVE;
    //echo "Resource relurl = $relurl<br/>";
    $resource = get_record("resource", "id", $modresource->instance);
    $cm = get_coursemodule_from_instance('resource', $resource->id, $resource->course);
    $strresources = get_string("modulenameplural", "resource");
    $navlinks = array();
    $navlinks[] = array('name' => $strresources, 'link' => 'resources.html', 'type' => 'activityinstance');
    //$navigation = build_navigation($navlinks);
    if(function_exists('archive_resource_'.$resource->type)) {
        addToModpage('resource', $relurl, $modresource->section, $resource->name, $resource->summary);
        echo "Archiving resource {$resource->type}<br/>";
        call_user_func('archive_resource_'.$resource->type, $resource, $cm, $relurl);
    } else {
        echo "<span style='color:gray;'>resource{$resource->type}</span><br/>";
        $navlinks = array(array('name' => get_string('resources'), 'link' => 'mod/resource/index.html', 'type' => 'title'));
        $navlinks[] = array('name'=>$resource->name, 'link'=>'', 'type'=>'title');
        $navigation = build_archive_navigation($relurl, $navlinks);
        $out = getTemplateHeader($resource->name, $COURSE->fullname, $navigation);
        $out .= '<b>Archiving for resource type '.$resource->type.' has not been implemented yet.</b>';
        //	$out = '<pre>'.print_r($resource, true).'</pre><pre>'.print_r($cm, true).'</pre>';
        $out .= getTemplateFooter();
        checkAndWriteFile($relurl, $out);
    }
    return true;
}

function can_archive_resource($mod) {
    $resource = get_record("resource", "id", $mod->instance);
    if(function_exists('archive_resource_'.$resource->type)) return true;
    else return false;
}

function archive_url_resource($course, $mod) {
    $resource = get_record("resource", "id", $mod->instance);
    if(!function_exists('archive_url_resource_'.$resource->type)) return 'mod/'.$mod->modname.'/'.$mod->id.'/view.html';
    else return call_user_func('archive_url_resource_'.$resource->type, $course, $mod, $resource);
}

function archive_resource_directory($resource, $cm, $relurl, $subdir='') {
    $tofilestore = preg_replace('%([^/]*/)[^/]*%','../',$relurl).'files';
    //echo "<h4 style='color: red;'>relurl = $relurl<br/>tofilestore = $tofilestore<br/>subdir = $subdir</h4>";
    global $CFG;
    global $ARCHIVE; // ARCHIVE->files is an array of resource files to be included in archive.
    global $COURSE;
    //echo '<hr/><h3>Directory</h3>';
    //echo 'resource:<pre>'.print_r($resource, true).'</pre>coursemodule:<pre>'.print_r($cm, true).'</pre>';
    //echo 'relurl:<pre>'.print_r($relurl,true).'</pre>';
    //#if $resource->reference is set that is the sub dir of this course to use
    //# section that will go into a recursive function (to get subdirs.)
    if ($resource->reference) {
        $relativepath = "{$resource->course}/{$resource->reference}{$subdir}";
        $archivepath = "/{$resource->reference}{$subdir}";
    } else {
        $relativepath = "{$resource->course}{$subdir}";
        $archivepath = "{$subdir}";
    }
    $formatoptions = new object();
    $formatoptions->noclean = true;
    $formatoptions->para = false; // MDL-12061, <p> in html editor breaks xhtml strict
    $strresource = get_string("modulename", "resource");
    $strresources = get_string("modulenameplural", "resource");
    $relurl2 = substr($relurl, 0, strrpos($relurl,'/')).$subdir.'/view.html';
    $navlinks = array(array('name' => get_string('resources'), 'link' => 'mod/resource/index.html', 'type' => 'title'));
    //# need to run through subdirs here, adding links to parents...
    if(strlen($subdir)) {
        $navlinks[] = array('name'=>$resource->name, 'link'=>$relurl, 'type'=>'title');
        if(strpos($subdir,'/')!==false) {
            $navdirs = explode('/',$subdir);
        } else {
            $navdirs = array($subdir);
        }
        foreach($navdirs as $d) {
            if(strlen($d)) {
                $navlinks[] = array('name'=>$d, 'link'=>'', 'type'=>'title');
            }
        }
    } else {
        $navlinks[] = array('name'=>$resource->name, 'link'=>'', 'type'=>'title');
    }
    $navigation = build_archive_navigation($relurl2, $navlinks);
    $page = getTemplateHeader($resource->name, $COURSE->fullname, $navigation);
    // Code adapted from /mod/resource/type/directory/resource.class.php function display.
    if (trim(strip_tags($resource->summary))) {
        ob_start();
        print_simple_box(format_text($resource->summary, FORMAT_MOODLE, $formatoptions, $COURSE->id), "center");
        print_spacer(10,10);
        $page .= ob_get_contents();
        ob_end_clean();
    }
    $files = get_directory_list("$CFG->dataroot/$relativepath", array($CFG->moddata, 'backupdata'), false, true, true);
    if(!$files) {
        $page .= '<h2>'.get_string("nofilesyet").'</h2>';
    } else {
        $page .= '<div class="generalbox">';
        $strftime = get_string('strftimedatetime');
        $strname = get_string("name");
        $strsize = get_string("size");
        $strmodified = get_string("modified");
        $strfolder = get_string("folder");
        $strfile = get_string("file");
        $page .= '<table cellpadding="4" cellspacing="1" class="files" summary="">';
        $page .= "<tr><th class=\"header name\" scope=\"col\">$strname</th>". "<th align=\"right\" colspan=\"2\" class=\"header size\" scope=\"col\">$strsize</th>". "<th align=\"right\" class=\"header date\" scope=\"col\">$strmodified</th>". "</tr>";
        foreach ($files as $file) {
            if (is_dir("$CFG->dataroot/$relativepath/$file")) { // Must be a directory
                $icon = "folder.gif";
                $relativeurl = "/view.php?blah"; // this isn't used,
                $filesize = display_size(get_directory_size("$CFG->dataroot/$relativepath/$file"));
                $page .= '<tr class="folder">';
                $page .= '<td class="name">';
                $page .= "<a href=\"$file/view.html\">"; // subdir name becomes link
                //# Now need to make sure $file.html exists - run this func recursively? what about /subdir/subdir though....
                archive_resource_directory($resource, $cm, $relurl, $subdir.'/'.$file);
                $page .= "<img src=\"$CFG->pixpath/f/$icon\" class=\"icon\" alt=\"$strfolder\" />&nbsp;$file</a>";
            } else {
                $icon = mimeinfo("icon", $file);
                //$relativeurl = get_file_url("$archivepath/$file");
                $archiveurl = $tofilestore."$archivepath/$file";
                $filesize = display_size(filesize("$CFG->dataroot/$relativepath/$file"));
                $page .= '<tr class="file">';
                $page .= '<td class="name">';
                //ob_start();
                //link_to_popup_window($relativeurl, "resourcedirectory{$resource->id}", "<img src=\"$CFG->pixpath/f/$icon\" class=\"icon\" alt=\"$strfile\" />&nbsp;$file", 450, 600, '');
                //$page .= ob_get_contents();
                //ob_end_clean();
                //echo "<h4 style='color: red;'>Archivepath = $archivepath<br/>relativepath = $relativepath<br/>archiveurl = $archiveurl<br/>file = $file</h4>";
                $page .= archive_file_link($archiveurl, $resource->id, "<img src=\"$CFG->pixpath/f/$icon\" class=\"icon\" alt=\"$strfile\" />&nbsp;$file");
                if(!array_search("$archivepath/$file", $ARCHIVE->files)){
                    $ARCHIVE->files[] = "$archivepath/$file";
                }
            }
            $page .= '</td>';
            $page .= '<td>&nbsp;</td>';
            $page .= '<td align="right" class="size">';
            $page .= $filesize;
            $page .= '</td>';
            $page .= '<td align="right" class="date">';
            $page .= userdate(filemtime("$CFG->dataroot/$relativepath/$file"), $strftime);
            $page .= '</td>';
            $page .= '</tr>';
        }
        $page .= '</table>';
        $page .= '</div>';
    }
    //$page .= '<pre>'.print_r($files, 1).'</pre>';
    //#   echo '<pre>'; print_r($ARCHIVE->files); echo '</pre>';
    $page .= getTemplateFooter();
    $relurl2 = substr($relurl, 0, strrpos($relurl,'/')).$subdir.'/view.html';
    //echo "<h4 style='color: red;'>relurl2 = $relurl2</h4>";
    checkAndWriteFile($relurl2, $page);
    //echo '<hr/>';
    return true;
}
// Used for links to files in directories

function archive_file_link($url, $name, $linkname) {
    return '<a href="'.$url.'">'.$linkname.'</a>';
}
// Called during output buffering for file link URLs on main page

function archive_url_resource_file($course, $mod, $resource) {
    global $ARCHIVE;
    if(isEmbedded($resource)) {
        return 'mod/resource/'.$mod->id.'/view.html';
    } else {
        if(!array_search('/'.$resource->reference, $ARCHIVE->files)) $ARCHIVE->files[] = '/'.$resource->reference;
        return 'files/'.$resource->reference;
    }
}

function archive_resource_file($resource, $cm, $relurl, $subdir='') {
    //echo "<span style='color: red;'>archive_resource_file {$resource->id} - {$resource->options}; relurl=$relurl; subdir=$subdir</span><br/>";
    //echo '<pre>'; print_r($resource); echo '</pre>';
    global $CFG;
    global $ARCHIVE; // ARCHIVE->files is an array of resource files to be included in archive.
    global $COURSE;
    $resourcetype = '';
    $tofilestore = preg_replace('%([^/]*/)[^/]*%','../',$relurl).'files/';
    if(isEmbedded($resource, $resourcetype )) {
        if(!array_search('/'.$resource->reference, $ARCHIVE->files)) $ARCHIVE->files[] = '/'.$resource->reference;
        $relurl2 = substr($relurl, 0, strrpos($relurl,'/')).$subdir.'/view.html';
        $navlinks = array(array('name' => get_string('resources'), 'link' => 'mod/resource/index.html', 'type' => 'title'));
        $navlinks[] = array('name'=>$resource->name, 'link'=>'', 'type'=>'title');
        $navigation = build_archive_navigation($relurl2, $navlinks);
        $page = getTemplateHeader($resource->name, $COURSE->fullname, $navigation);
        $page .= getEmbededFileCode($resource, $resourcetype, $tofilestore.$resource->reference);
        $page .= getTemplateFooter();
        checkAndWriteFile($relurl2, $page);
    }
}
/*function archive_url_resource_text($course, $mod, $resource)
{
    global $ARCHIVE;
	return 'mod/resource/'.$mod->id.'/view.html';
} */

function archive_resource_text($resource, $cm, $relurl, $subdir='') {
    //echo "<span style='color: red;'>archive_resource_file {$resource->id} - {$resource->options}; relurl=$relurl; subdir=$subdir</span><br/>";
    //echo '<pre>'; print_r($resource); echo '</pre>';
    global $CFG;
    global $ARCHIVE; // ARCHIVE->files is an array of resource files to be included in archive.
    global $COURSE;
    $relurl2 = substr($relurl, 0, strrpos($relurl,'/')).$subdir.'/view.html';
    $navlinks = array(array('name' => get_string('resources'), 'link' => 'mod/resource/index.html', 'type' => 'title'));
    $navlinks[] = array('name'=>$resource->name, 'link'=>'', 'type'=>'title');
    $navigation = build_archive_navigation($relurl2, $navlinks);
    $page = getTemplateHeader($resource->name, $COURSE->fullname, $navigation);
    $page .= str_replace("\n", '<br/>', $resource->alltext);
    $page .= getTemplateFooter();
    checkAndWriteFile($relurl2, $page);
}

function archive_resource_html($resource, $cm, $relurl, $subdir='') {
    //echo "<span style='color: red;'>archive_resource_file {$resource->id} - {$resource->options}; relurl=$relurl; subdir=$subdir</span><br/>";
    //echo '<pre>'; print_r($resource); echo '</pre>';
    global $CFG;
    global $ARCHIVE; // ARCHIVE->files is an array of resource files to be included in archive.
    global $COURSE;
    $relurl2 = substr($relurl, 0, strrpos($relurl,'/')).$subdir.'/view.html';
    $navlinks = array(array('name' => get_string('resources'), 'link' => 'mod/resource/index.html', 'type' => 'title'));
    $navlinks[] = array('name'=>$resource->name, 'link'=>'', 'type'=>'title');
    $navigation = build_archive_navigation($relurl2, $navlinks);
    $page = getTemplateHeader($resource->name, $COURSE->fullname);
    $html = processImages($resource->alltext, 3);
    $page .= $html;
    $page .= getTemplateFooter();
    checkAndWriteFile($relurl2, $page);
}
?>

