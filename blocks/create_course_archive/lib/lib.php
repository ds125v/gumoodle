<?php

function checkdirexists($fullpath)
{
	$dir = str_replace('\\','/',$fullpath);   // comment to sort syntax highlighting in dzPHP '
    $dir = substr($dir, 0, strrpos($dir,'/'));
    $tpos = 0;
    while($tpos < strlen($dir))
    {
        $tpos = strpos($dir, "/", $tpos+1);
        if($tpos == false)
            $tpos = strlen($dir);
        $testdir = substr($dir, 0, $tpos);
        if(!file_exists($testdir))
        {
            //echo "Attempting to create $testdir<br/>";
            mkdir($testdir, 0775);
        }
    }
    if((!file_exists($dir))||(!is_dir($dir)))
        return false;
    else
        return true;
}

function checkAndWriteFile($fname, $ftext=false)
{
	//# need to check content first (images, CSS, URLs etc, should probably do it here)
	global $ARCHIVE;
    $filedepth = 0;
    $tmp = $fname;
    while(strpos($tmp,'/')) {
    	$filedepth++;
        $tmp = substr($tmp, strpos($tmp,'/')+1);
    }
	$ftext = processImages($ftext, $filedepth);

    //# This is possably a good place to fix %somefield% bits as well.
	checkdirexists($ARCHIVE->tmpDir.'/'.$fname);
	$fp = fopen($ARCHIVE->tmpDir.'/'.$fname,'w');
	fwrite($fp, $ftext);
	fclose($fp);
}

function prepareArchiveBlock(&$block)
{
	$blktype = get_record('block','id',$block->blockid);
    $ret = true;
    
    //NSFB - building a list of blocks (with links) for archiving
    //# Will calculate the relative URL here, and use it in next bit (output)
    //# Should also check if archiving code exists, and if not make it greyed with no URL.
   	global $ARCHIVE;
    // really depends on block type... Is it needed

    switch($blktype->name)
    {
    case 'html':
    	$ARCHIVE->blocks[] = array('block' => $block, 'type' => $blktype, 'url' => '');
    	break;
    case 'admin':
        $block->visible = false;
        // Getting rid of it, no need to add to $ARCHIVE->blocks[]
    	break;
    default:
        if($ARCHIVE->showAllBlocks) {
	    	block2html($block, $blktype->name, 'This block type is not yet processed by the archiving code.');
	    	$ARCHIVE->blocks[] = array('block' => $block, 'type' => $blktype, 'url' => '(empty if no further action needed)');
        }
        else {
        	$block->visible = false;
        }
        $ret = false;
    	break;
    }
    return $ret;
}

function block2html(&$block, $title, $content)
{
	static $htmlid = false;

    if($htmlid == false)
    {
    	$htmlid = get_record('block', 'name', 'html')->id;
    }
    /*        [0] => stdClass Object
                (
                    [blockid] => 20
                    [pageid] => 2
                    [pagetype] => course-view
                    [position] => l
                    [weight] => 0
                    [visible] => 1
                    [configdata] =>
                    [id] => 7
                )
     */

    $block->blockid = $htmlid;
    $ob = new object();
    $ob->title = $title;
    $ob->text = $content;
    $block->configdata = base64_encode(serialize($ob));
}

function prePrepareTemplatePage($courseid)
{
	global $ARCHIVE;
    if (! ($course = get_record('course', 'id', $courseid)) ) {
            error('Invalid course id');
        }

    $archivenav = array('newnav' => 1, 'navlinks' => '%_nav_links_go_here%');
    ob_start();
    print_header('%page_title_goes_here%', '%_fullname_goes_here%', $archivenav, 'title', '', true, '', null);
    $ARCHIVE->originalheader = ob_get_contents();
    ob_end_clean();
    ob_start();
    print_footer(NULL, $course);
    $ARCHIVE->originalfooter = ob_get_contents();
    ob_end_clean();

    return true;
}

function prepareTemplatePage($courseid)
{
	global $ARCHIVE, $COURSE;
    if (! ($course = get_record('course', 'id', $courseid)) ) {
            error('Invalid course id');
        }

    $archivenav = array('newnav' => 1, 'navlinks' => '%_nav_links_go_here%');
    //# navmenu($course) provides the drop box navigation in the header,
    //# If this stays it has to reflect the archived course instead.

	if(isset($ARCHIVE->originalheader)) {
	    $ARCHIVE->header = $ARCHIVE->originalheader;
	    $ARCHIVE->footer = $ARCHIVE->originalfooter;
    }
    else {
	    ob_start();
	//    print_header('%page_title_goes_here%', '%_fullname_goes_here%', $archivenav, 'title', '', true, '', navmenu($course));
	    print_header('%page_title_goes_here%', '%_fullname_goes_here%', $archivenav, 'title', '', true, '', null);
	    $ARCHIVE->header = ob_get_contents();
	    ob_end_clean();
	    ob_start();

	    print_footer(NULL, $course);
	    $ARCHIVE->footer = ob_get_contents();
	    ob_end_clean();
    }
    $pagetitle = get_string('archiveof', 'block_create_course_archive').' '.$COURSE->shortname.': %page_title_goes_here%';
    $ARCHIVE->header=str_replace('%page_title_goes_here%', $pagetitle, $ARCHIVE->header);

    //# should run filter on them - but the relative paths will still have to be altered to match
    //# different pages
    $ARCHIVE->footer = replaceLoginLinks($ARCHIVE->footer);
    $ARCHIVE->header = preg_replace('/<body [^>]*>/', '<body class="%page_class_goes_here%">',$ARCHIVE->header);
    //$ARCHIVE->header = preg_replace('%<script^(</script>)*</script>%', '',$ARCHIVE->header);
    $ARCHIVE->header = killJavaScript($ARCHIVE->header);
    $ARCHIVE->header = replaceLoginLinks($ARCHIVE->header);

   	$ARCHIVE->header = processCSSLinks($ARCHIVE->header);

    return true;
}

function getTemplateHeader($pagetitle='', $pageheading='', $navigation=false, $pageclass=false, $fordisplay = false)
{
	global $ARCHIVE;
    if($fordisplay) {
    	$tmp = $ARCHIVE->originalheader;
    } else {
    	$tmp = $ARCHIVE->header;
    }
    $tmp = str_replace('%_fullname_goes_here%', $pagetitle, $tmp);
    $tmp = str_replace('%page_title_goes_here%', $pageheading, $tmp);
    if($navigation) {
    	$tmp = str_replace('%_nav_links_go_here%', $navigation['navlinks'], $tmp);
    }
    else {
    	$tmp = str_replace('%_nav_links_go_here%', '', $tmp);
    }
    if($pageclass) {
    	$tmp = str_replace('%page_class_goes_here%', $pageclass, $tmp);
    }
    else {
    	$tmp = str_replace('%page_class_goes_here%', '', $tmp);
    }
	return $tmp;
}

function getTemplateFooter($fordisplay = false)
{
	global $ARCHIVE;
    if($fordisplay) {
		return $ARCHIVE->originalfooter;
    } else {
		return $ARCHIVE->footer;
    }
}

/*********************************************************************************
*
*  Link processing functions - these are a major part of the archiving process
*  as they allow the existing Moodle code to be reused without major refactoring.
*
*********************************************************************************/


//# This function needs major improvements!
function replaceLoginLinks($source)
{
    $subs = strpos($source,'<div class="logininfo">');
    while($subs != false)
    {
    	$subs = $subs+1;
	    $sube = strpos($source, '</div>', $subs);
	    $source = substr($source, 0, $subs-1) . '<div>Archived Course</div>' .  substr($source, $sube+6);
    	$subs = strpos($source,'<div class="logininfo">');
    }
	return $source;
}

function processImages($source, $filedepth=-1)
{
    global $ARCHIVE;
	if($filedepth==-1)
    {
    	//echo '<h2 style="color:Red;">No file depth for processImages in '.$ARCHIVE->tmpDir.'</h2>';
        $filedepth=0;
    }
	global $CFG;
    $pathstr = '';
    for($n=0; $n<$filedepth; $n++){
    	$pathstr .= '../';
    }
    // Fix local images that were done using $ARCHIVE->root as a path placeholder
    $source = str_replace($ARCHIVE->root.'/', $pathstr, $source);


    //preg_match_all('/<\s*img\s+[^>]*src=[\'"]([^\'"]*)[\'"][^>]*>/', $source, $matches, PREG_PATTERN_ORDER);
    preg_match_all('/\s+src=[\'"]([^\'"]*)[\'"]/', $source, $matches, PREG_PATTERN_ORDER);
    $localimagelist = array();
    $remoteimagelist = array();
    foreach($matches[1] as $u) {
    	// firstly the easy ones....
    	if(substr($u,0,strlen($CFG->wwwroot))==$CFG->wwwroot) {
            $localimagelist[] = substr($u,strlen($CFG->wwwroot));
            $source = str_replace($u, $pathstr .'img'.substr($u,strlen($CFG->wwwroot)), $source);
            //echo '<b>Replaced </b>'.$u.'<b> with </b>'.$pathstr .'img'.substr($u,strlen($CFG->wwwroot)).'<br/>';
        }
        // need to process ones from file.php
    	elseif(substr($u,0,strlen($ARCHIVE->fileroot))==$ARCHIVE->fileroot) {
            $ARCHIVE->files[] = substr($u,strlen($ARCHIVE->fileroot));
            $source = str_replace($u, $pathstr .'files'.substr($u,strlen($ARCHIVE->fileroot)), $source);
            //echo '<b>Replaced </b>'.$u.'<b> with </b>'.$pathstr .'files'.substr($u,strlen($ARCHIVE->fileroot)).'<br/>';
        }
        //Ones that somehow avoided using the new wwwroot...
    	elseif(substr($u,0,strlen($ARCHIVE->realwwwroot))==$ARCHIVE->realwwwroot) {
            $localimagelist[] = substr($u,strlen($ARCHIVE->realwwwroot));
            $source = str_replace($u, $pathstr .'img'.substr($u,strlen($ARCHIVE->realwwwroot)), $source);
            //echo '<b>Replaced </b>'.$u.'<b> with </b>'.$pathstr .'img'.substr($u,strlen($CFG->wwwroot)).'<br/>';
        }
        else {
             $remoteimagelist[] = $u;
        }
    }
    foreach($localimagelist as $u) {
        checkdirexists($ARCHIVE->tmpDir."/img".$u);
       	copy($CFG->dirroot.$u, $ARCHIVE->tmpDir."/img".$u);
    }
/*    echo '<h3>Local images - '.$CFG->wwwroot.'</h3>';
    foreach($localimagelist as $u) {
    	echo $u.'<br/>';
	}
    echo '<h3>Remote images</h3>';
    foreach($remoteimagelist as $u) {
    	echo $u.'<br/>';
	} */
	return $source;
}

function processCSSLinks($source)
{
	global $CFG;
    global $ARCHIVE;
    preg_match_all('/<\s*link\s+[^>]+>/', $source, $matches, PREG_PATTERN_ORDER);
    //echo '<h3>CSSLinks</h3><pre>'; echo htmlentities(print_r($matches,1)); echo '</pre>';
    foreach($matches[0] as $u) {
    	preg_match('/href=[\'"]([^\'"]*)[\'"]/', $u, $m);
        $href = $m[1];
    	preg_match('/rel=[\'"]([^\'"]*)[\'"]/', $u, $m);
        $rel = $m[1];
        if($rel == 'stylesheet')
        {
			$ftype = substr($href, strrpos($href,'.')+1);
            if($ftype=='css')
            	$addtail = '';
            else
            	$addtail = '.css';
	       	if(substr($href,0,strlen($ARCHIVE->realwwwroot))==$ARCHIVE->realwwwroot) {
               	$relhref =  substr($href ,strlen($ARCHIVE->realwwwroot));
	            $source = str_replace($href, $ARCHIVE->root.'/css'.$relhref.$addtail, $source);
                $csssource = url_get_contents($href);
                //# CSS files really need processed, so should call another
                //# function here to do the processing.
                collectCSSImages($csssource, $href);
                checkdirexists($ARCHIVE->tmpDir.'/css'.$relhref);
                $fp = fopen($ARCHIVE->tmpDir."/css".$relhref.$addtail, 'w');
                fwrite($fp, $csssource);
                fclose($fp);
	        }
        }
    }
    return $source;
}

function collectCSSImages(&$csssource, $cssurl) //# More params to be added, $path to css? Maybe easier by http?
{
	global $CFG;
    global $ARCHIVE;
	if($ARCHIVE->realwwwroot==substr($cssurl, 0, strlen($ARCHIVE->realwwwroot)))
    {
    	$cssbase = substr($cssurl, strlen($ARCHIVE->realwwwroot));
        $csspath = substr($cssbase, 0, strrpos($cssbase,'/')+1);
        $cssbase = $CFG->dirroot.$csspath;
        $useurl = false;
    }
    else
    {
        $cssbase = $CFG->dirroot.substr($cssurl, 0, strrpos($cssurl,'/')+1);
    	$useurl=true;
    }
    //echo "CSS base is $cssbase, path is $csspath<br/>";
	preg_match_all('/\\burl\\s*\\(([^\\)]+)\\)/',$csssource,$matches);
    //echo '<pre>'; print_r($matches); echo '</pre>';
    if(!$useurl)
    {
	    foreach($matches[1] as $f)
	    {
        	if(strpos($f,'://'))
            {
				if($ARCHIVE->realwwwroot==substr($f, 0, strlen($ARCHIVE->realwwwroot)))
				{
			    	$f2 = substr($f, strlen($ARCHIVE->realwwwroot));
                    str_replace($f, $f2, $csssource);
                    $f = $f2;
                }
                else
                	$f = false; //# ignore for now...
            }
        	if($f)
            {
		        checkdirexists($ARCHIVE->tmpDir.'/css'.$csspath.$f);
                if((substr($f,0,1)=='/')&&(file_exists($CFG->dirroot.$f))){
			       	copy($CFG->dirroot.$f, $ARCHIVE->tmpDir.'/css'.$csspath.$f);
                }
                elseif(file_exists($cssbase.$f))
			       	copy($cssbase.$f, $ARCHIVE->tmpDir.'/css'.$csspath.$f);
            }
	    }
	}
}

function processScripts($source)
{
//    echo "<h3>Scripts</h3>";
	global $ARCHIVE;
    preg_match_all('/<\s*script\s+[^>]*src\s*=[^>]+>/', $source, $matches, PREG_PATTERN_ORDER);
    foreach($matches[0] as $u) {
    	preg_match('/type=[\'"]([^\'"]*)[\'"]/', $u, $m);
        $type = $m[1];
    	preg_match('/src=[\'"]([^\'"]*)[\'"]/', $u, $m);
        $src = $m[1];
    	echo "$src ($type)<br/>";
    }
    return $source;
}

function processObjects($source)
{
//    echo "<h3>Objects</h3>";
	global $ARCHIVE;
    preg_match_all('/<\s*object\s+[^>]+>/', $source, $matches, PREG_PATTERN_ORDER);
    foreach($matches[0] as $u) {
    	echo htmlentities($u)."<br/>";

    }
    return $source;
}

function processHyperlinks($source)
{
	global $ARCHIVE;
    //echo "<h3>HyperLinks</h3>";
    //preg_match_all('/<\s*a\s+[^>]+>/', $source, $matches, PREG_PATTERN_ORDER);
    preg_match_all('/<\s*a\s+.*href=[\'"]([^\'"]+)[\'"][^>]*>/', $source, $matches, PREG_PATTERN_ORDER);
    for($n=0; $n<sizeof($matches[0]); $n++) {
    	if(substr($matches[1][$n],0,1)!=='#') {
	        //# do something useful...
	    	//echo htmlentities($matches[1][$n])."<br/>";
        }
    }
    return $source;
}

function user_picture($user)
{
    global $ARCHIVE;
	if(!is_object($user))
    {
    	$user = get_record('user','id',$user);
    }
	global $CFG;
	static $collectedPics = array(); // to avoid duplicating copys
    $alt = get_string('pictureof','',fullname($user));
    $out = '';
    if($user->picture==0)
    {
    	$out .= '<img src="'.$CFG->pixpath.'/u/f2.png" alt="'.$alt.'"/>';
    }
    else
    {
    	if(!array_key_exists($user->id,$collectedPics))
        {
            $collectedPics[$user->id] = 'f2.jpg';
        	checkdirexists($ARCHIVE->tmpDir.'/userimg/'.$user->id.'/');
	       	copy($CFG->dataroot.'/user/0/'.$user->id.'/f2.jpg', $ARCHIVE->tmpDir.'/userimg/'.$user->id.'/f2.jpg');
        }
    	$out .= '<img src="'.$ARCHIVE->root.'/userimg/'.$user->id.'/f2.jpg" alt="'.$alt.'"/>';
    }
    return $out;
}

function url_get_contents($url)
{
    $ch = curl_init($url);
 	curl_setopt($ch, CURLOPT_HEADER, 0);

    ob_start();
	curl_exec($ch);
    $out = ob_get_contents();
    ob_end_clean();
	curl_close($ch);
    return $out;
}

function build_archive_navigation($forpage = '', $extranavlinks=null)
{
	global $COURSE;
	$navigation = '<h2 class="accesshide " >You are here</h2> <ul>';
    $coursetext = get_string('archiveof', 'block_create_course_archive').' '.$COURSE->shortname;
    if($forpage=='') {
    	$navigation .= $coursetext;
    }
    else {
    	$toroot = preg_replace('%([^/]*/)[^/]*%','../',$forpage);
        $navigation .= '<li><a href="'.$toroot.'index.html">'.$coursetext.'</a></li>';
        if(is_array($extranavlinks)) {
	        foreach($extranavlinks as $link) {
	        	if(strlen($link['link'])) {
	        	$navigation .= '<li class="first"> <span class="accesshide " >/&nbsp;</span><span class="arrow sep">&#x25BA;</span><a href="'.$toroot.$link['link'].'">'.$link['name'].'</a></li>';
	            }
	            else {
	        		$navigation .= '<li class="first"> <span class="accesshide " >/&nbsp;</span><span class="arrow sep">&#x25BA;</span>'.$link['name'].'</li>';
	            }
	        }
    	}
    }
    $navigation .= '</ul>';
    return(array('newnav' => true, 'navlinks' => $navigation));
}

function addToModpage($modtype, $relurl, $section, $name, $summary, $extra=null)
{
	global $ARCHIVE;

    $relurl2 = buildRelUrl('mod/'.$modtype.'/index.html', $relurl);

    $ARCHIVE->modulelist[$modtype][] = array('relurl'=>$relurl2, 'section'=>$section, 'name'=>$name, 'summary'=>$summary, 'extra'=>$extra);
}

function buildRelUrl($from, $target)
{
	$fparts = explode('/',$from);
    $tparts = explode('/', $target);
    $lim = sizeof($fparts) < sizeof($tparts)? sizeof($fparts) : sizeof($tparts);
    $match = true;
    $start = 0;
    while($match&&($start<$lim))
    {
    	if($fparts[$start]!==$tparts[$start])
        	$match = false;
        else
        	$start++;
    }
    $out = '';
    for($n=$start; $n<sizeof($fparts)-1; $n++)
    	$out .= '../';
    for($n=$start; $n<sizeof($tparts)-1; $n++)
    	$out .= $tparts[$n].'/';
    $out .= $tparts[sizeof($tparts)-1];
    return $out;
}

function build_default_listpage($modname, $entries)
{
	global $COURSE;
    $name = get_string("modulenameplural", $modname);
    $navlinks = array();
    $navlinks[] = array('name' => $name, 'link' => '', 'type' => 'title');
    $relurl = 'mod/'.$modname.'/index.html';
    $navigation = build_archive_navigation($relurl, $navlinks);
    $out = getTemplateHeader($name, $COURSE->fullname, $navigation);
    $week = 0;
    $out .= '<table width="80%"  cellpadding="5" cellspacing="1" class="generaltable boxaligncenter" >';
    $out .= '<tr>
                  <th style="vertical-align:top; text-align:center; white-space:nowrap;"
                  class="header c0" scope="col">Week</th>
                  <th style="vertical-align:top; text-align:left; white-space:nowrap;"
                  class="header c1" scope="col">Name</th>
                  <th style="vertical-align:top; text-align:left; white-space:nowrap;"
                  class="header c2 lastcol" scope="col">
                  Summary</th>
                </tr>';

    foreach($entries as $e)
    {
    	if($e['section'] != $week+1)
        {
        	$week = $e['section']-1;
        	$out .= '<tr class="r1"><td colspan="3"><div class="tabledivider"></div></td></tr>';
    		$out .= '<tr class="r0"><td style=" text-align:center;" class="cell c0">'.$week.'</td>';
        }
        else
        	$out .= '<tr class="r0"><td style=" text-align:center;" class="cell c0">&nbsp;</td>';
        $out .= '<td style=" text-align:left;" class="cell c1"><a href="'.$e['relurl'].'">'.$e['name'].'</a></td>';
        $out .= '<td style=" text-align:left;" class="cell c2">'.$e['summary'].'</td></tr>';
    }

   	$out .= '</table>';

    $out .= getTemplateFooter();
    checkAndWriteFile($relurl, $out);
}

function deltree($f) {
 if (is_dir($f)) {
   foreach(glob($f.'/*') as $sf) {
     if (is_dir($sf) && !is_link($sf)) {
       deltree($sf);
     } else {
       unlink($sf);
     }
   }
 }
 rmdir($f);
}

function killJavaScript($nasty)
{
	// I couldn't get a regex to do this neatly, so sledgehammer...
	$start = strpos($nasty, '<script');
    $nice = $nasty;
    while($start!==false)
    {
        $end = strpos($nice, '</script>', $start)+9;
        if(!$end)
        	return str_replace('script', 'xscript', $nasty);
        else
        	$nice = substr($nice, 0, $start).substr($nice, $end);
		$start = strpos($nice, '<script');
    }
    return $nice;
}

?>
