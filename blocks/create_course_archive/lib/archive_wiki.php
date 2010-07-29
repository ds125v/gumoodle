<?php
include('lib/wiki2html.php');

function archive_wiki($course, $mod, $relurl) {
    global $CFG, $ARCHIVE, $COURSE;
    // Load the module instance
    if (! $wiki = get_record("wiki", "id", $mod->instance)) {
        error("wiki ID was incorrect");
    }
    //echo '<pre>'; print_r($wiki); echo '</pre>';
    // Add to module listing page
    addToModpage('wiki', $relurl, $mod->section, $wiki->name, $wiki->summary);

    //$wikipages = get_records('wiki_pages','wiki',$wiki->id);
    //$wikipages = get_records_sql("SELECT *, MAX(version) AS max_version FROM {$CFG->prefix}wiki_pages WHERE wiki='{$wiki->id}' GROUP BY pagename;");

	$wikipages = get_records_sql("SELECT * FROM {$CFG->prefix}wiki_pages INNER JOIN
(SELECT pagename, wiki, MAX( version ) AS max_version FROM {$CFG->prefix}wiki_pages WHERE wiki = '{$wiki->id}' AND flags='1' GROUP BY pagename) AS derivedTable
ON derivedTable.wiki={$CFG->prefix}wiki_pages.wiki AND derivedTable.max_version={$CFG->prefix}wiki_pages.version AND derivedTable.pagename={$CFG->prefix}wiki_pages.pagename;");

    $baseurl = substr($relurl,0, strrpos($relurl, '/')+1);
    $mainpage = $baseurl.pageNameToFileName($wiki->pagename);

    //echo '<pre>'; echo htmlentities(print_r($wikipages,1)); echo '</pre>';
    foreach($wikipages as $page)
    	createWikiPage($wiki, $page, $baseurl, $mainpage);

    return true;
}

function createWikiPage($wiki, $page, $baseurl, $mainpage)
{
    global $CFG, $ARCHIVE, $COURSE;
	//echo "* Wiki page - {$page->pagename}<br/>";
    //$thispage = $baseurl.preg_replace('/[^a-zA-Z0-9]/','_',$page->pagename).'.html';
    $pagefilename = pageNameToFileName($page->pagename);
    $thispage = $baseurl.$pagefilename;
    $navlinks = array(array('name' => get_string('modulenameplural', 'wiki'), 'link' => 'mod/wiki/index.html', 'type' => 'title'));
    if($wiki->pagename == $page->pagename)
    {
	    $navlinks[] = array('name'=>$wiki->name, 'link'=>'', 'type'=>'title');
    }
    else
    {
	    $navlinks[] = array('name'=>$wiki->name, 'link'=>$mainpage, 'type'=>'title');
	    $navlinks[] = array('name'=>$page->pagename, 'link'=>'', 'type'=>'title');
    }
    $navigation = build_archive_navigation($thispage, $navlinks);

    $wikipagestart = getTemplateHeader($COURSE->fullname, $wiki->name, $navigation);
    $wikipagestart .= wikiSectionStart1();
    $out = $wikipagestart;
    $out .= wikiTabs($pagefilename);
    $out .= wikiPageTitle($page->pagename);
    $content=$page->content;

    //echo '<h3>Content</h3>'.htmlentities($content);
    //echo '<h3>Wikified content</h3>'.htmlentities(wiki2html($content, $wiki->htmlmode)).'<h4>End</h4>';

    $w2h = new wiki2html();
	$out .= $w2h->process($content, $wiki->htmlmode);

    //#$out .= "<p>{$page->refs}</p>"; // Needs processing

    $out .= wikiSectionEnd();
    $out .= getTemplateFooter();
    checkAndWriteFile($thispage, $out);

    //Now the Links page
    $out = $wikipagestart;
    $out .= wikiTabs($pagefilename, true);
    $out .= wikiPageTitle($page->pagename);
    $out .= "<p>Need to process this...<br/>{$page->refs}</p>";
    $out .= wikiSectionEnd().getTemplateFooter();
    checkAndWriteFile($baseurl.'links/'.$pagefilename, $out);
    //Now the History page
    if($ARCHIVE->includeWikiHistory)
    {

		$history = get_records_sql("SELECT * FROM {$CFG->prefix}wiki_pages WHERE wiki={$wiki->id} AND pagename='{$page->pagename}' ORDER BY version DESC;");


	    $out = $wikipagestart;
	    $out .= wikiTabs($pagefilename, true);
	    $out .= wikiPageTitle($page->pagename);
        $pagedirname = substr($pagefilename,0,strrpos($pagefilename, '.'));
        foreach($history as $h)
        {
        	create_history_page($baseurl, $wikipagestart, $pagefilename, $pagedirname, $wiki, $page, $h);
            $url = '../'.$pagedirname.'/'.$h->version.'.html';
            $out .= history_link_block($h->version, $url, $h->userid, $h->created, $h->lastmodified);
        }
	    $out .= wikiSectionEnd().getTemplateFooter();
	    checkAndWriteFile($baseurl.'history/'.$pagefilename, $out);
    }
    //Now the Attachments page
    $out = $wikipagestart;
    $out .= wikiTabs($pagefilename, true);
    $out .= wikiPageTitle($page->pagename);
    $out .= "<p>Still to implement</p>";
    $pagedir = "{$CFG->dataroot}/{$wiki->course}/moddata/wiki/{$wiki->id}/{$wiki->id}/{$page->pagename}";
    $pagefiles = array();
    if((file_exists($pagedir))&&(is_dir($pagedir)))
    {
	    if ($handle = opendir($pagedir))
	    {
	   		while (false !== ($file = readdir($handle)))
	        {
	        	if ($file != "." && $file != "..")
	            {
                    $fileinfo = get_record('wiki_pages','pagename','internal://'.$file, 'wiki', $wiki->id);
                    if($fileinfo !== false)
                    {
                    	$fileinfo->meta = unserialize($fileinfo->meta);
                        if($fileinfo->meta['section'] == $page->pagename)
                        {
                    		$pagefiles[$file] = $fileinfo;
                        }
                    }
	       		}
	   		}
	   		closedir($handle);
		}
    }
    $out .= '<div class="wiki attachments Test-Wiki"><h2 class="page title">Attachments of Test Wiki</h2>';
	foreach($pagefiles as $fname=>$fdata)
    {
    	//echo '<pre>'; print_r($fileinfo); echo '</pre>';
    	$outdir = "{$ARCHIVE->tmpDir}/{$baseurl}/attachments/".str_replace('%','_',rawurlencode($page->pagename)).'/';
        checkdirexists($outdir);
       	copy("{$CFG->dataroot}/{$wiki->course}/moddata/wiki/{$wiki->id}/{$wiki->id}/{$page->pagename}/{$fname}", $outdir.$fname);
        $furl = str_replace('%','_',rawurlencode($page->pagename)).'/'.$fname;
        if(isset($fdata->meta['comment']))
	        $desc = $fdata->meta['comment'];
        else
        	$desc = '';
        $out .= format_attachment_info($furl, 'iconurl', $fname, $fdata->meta['size'], $desc,
                                      $fdata->meta['Content-Type'], $fdata->meta['X-Content-Type'],
                                      'uploadtime', 'userpic', 'username', $fdata->hits);
    }
    $out .= '</div>';
    //$out .= '<h3>wiki</h3><pre>'.print_r($wiki, 1).'</pre>';
    //$out .= '<h3>page</h3><pre>'.print_r($page, 1).'</pre>';
    //$out .= '<h3>baseurl</h3><pre>'.print_r($baseurl, 1).'</pre>';
    //$out .= '<h3>mainpage</h3><pre>'.print_r($mainpage, 1).'</pre>';
    $out .= wikiSectionEnd().getTemplateFooter();
    checkAndWriteFile($baseurl.'attachments/'.$pagefilename, $out);

}

function history_link_block($version, $url, $authorid, $created, $lastmod, $references='')
{
	$out = '<table  class="version-info" cellpadding="2" cellspacing="1">
			<tr class="page-version"><td style="vertical-align:top;text-align:right;white-space: nowrap;"><b>'.get_string('version').':</b></td>';
    $out .= '<td><a href="'.$url.'">'.$version.'</a> (<a href="'.$url.'">'.get_string('browse','wiki').'</a>&nbsp;)</td></tr>';

	$out .= '<tr class="page-author"><td style="vertical-align:top;text-align:right;white-space: nowrap;"><b>'.get_string('author','wiki').':</b></td>';
    $out .= '<td style="vertical-align:top;">';
    $out .= user_picture($authorid);
  	$user = get_record('user','id',$authorid);
	$out .= " {$user->firstname} {$user->lastname}";
    $out .= '</td></tr><tr class="page-created"><td style="vertical-align:top;text-align:right;white-space: nowrap;"><b>'.get_string('created','wiki').':</b></td><td>';
    $out .= strftime("%A %d %b %Y, %r", $created);
    $out .= '</td></tr><tr class="page-lastmodified"><td style="vertical-align:top;text-align:right;white-space: nowrap;"><b>'.get_string('lastmodified','wiki').':</b></td><td>';
    $out .= strftime("%A %d %b %Y, %r", $lastmod);
    $out .= '</td></tr><tr class="page-refs"><td style="vertical-align:top;text-align:right;white-space: nowrap;"><b>'.get_string('refs','wiki').':</b></td><td>';
    //<a href="view.php?id=632&amp;page=view/qweqki%2Fsubcat%2Fcategories%3F">qweqki/subcat/categories?</a>, <a href="view.php?id=632&amp;page=second+page">second page</a>, <a href="view.php?id=632&amp;page=with%2C+punctuation">with, punctuation</a>, <a href="view.php?id=632&amp;page=Link.+with.">Link. with.</a>, <a href="view.php?id=632&amp;page=hyphenated-words%3F">hyphenated-words?</a>, <a href="view.php?id=632&amp;page=plus%2Bsigns">plus+signs</a>, <a href="view.php?id=632&amp;page=characters+like+%3F">characters like ?</a>
    $out .= '</td></tr></table><br /><br />';
	return $out;
}

function create_history_page($baseurl, $wikipagestart, $pagefilename, $pagedirname, $wiki, $page, $histpage)
{
    $out = $wikipagestart;
    $out .= wikiTabs($pagefilename, true);
    $out .= wikiPageTitle($page->pagename);
    $content=$histpage->content;

    $w2h = new wiki2html();
	$out .= $w2h->process($content, $wiki->htmlmode, '../');

    $out .= "<p>Need to process this...<br/>{$page->refs}</p>";

    $out .= wikiSectionEnd();
    $out .= getTemplateFooter();
    checkAndWriteFile($baseurl.$pagedirname.'/'.$histpage->version.'.html', $out);
}

function archive_url_wiki($course, $mod)
{
    if (! $wiki = get_record("wiki", "id", $mod->instance)) {
        error("wiki ID was incorrect");
    }
    $mainpage = pageNameToFileName($wiki->pagename);
    return 'mod/'.$mod->modname.'/'.$mod->id.'/'.$mainpage;
}

function pageNameToFileName($pagename)
{
	return str_replace('%','_',rawurlencode($pagename)).'.html';
}

function wikiTabs($pagename, $isChild=false)
{
	global $ARCHIVE;
	if($isChild)
    	$st = '../';
    else
    	$st = '';
	$out = '<div class="tabtree"><ul class="tabrow0">';
	$out .= '<li class="first onerow"><a href="'.$st.$pagename.'"><span>View</span></a><div class="tabrow1 empty">&nbsp;</div></li> ';
	$out .= '<li><a href="'.$st.'links/'.$pagename.'" title="Links"><span>Links</span></a> </li>';
    if($ARCHIVE->includeWikiHistory) {
		$out .= '<li><a href="'.$st.'history/'.$pagename.'" title="History"><span>History</span></a> </li>';
    }
	$out .= '<li class="last"><a href="'.$st.'attachments/'.$pagename.'" title="Attachments"><span>Attachments</span></a> </li>';
	$out .= '</ul></div><div class="clearer"> </div>';

	return $out;
}

function wikiSectionStart1()
{
	return standardContentStart().'<div class="box generalbox generalboxcontent boxaligncenter boxwidthwide"><div class="wiki view">';
}

function wikiPageTitle($title)
{
	return '<h2 class="page title">'.$title.'</h2>';
}

function wikiSectionEnd()
{
	return '</div></div>'.standardContentEnd();
}

function format_attachment_info($url, $iconurl, $filename, $filesize, $description, $mime1, $mime2, $uploadtime, $userpic, $username, $downloadcount)
{
    $out = '';
    $out .= '  <a href="';
    $out .= $url;
    $out .= '">'."\n";
    $out .= '  <img src="';
    $out .= $iconurl;
    $out .= '" alt="[]" class="icon" />';
    $out .= $filename;
    $out .= '</a>, ';
    $out .= $filesize;
    $out .= ' <br />'."\n";
    $out .= '  <p>'."\n";
    $out .= '    <table border="1" cellpadding="2" cellspacing="0">'."\n";
    $out .= '      <tr>'."\n";
    $out .= '        <td class="lighter">';
    $out .= $description;
    $out .= ''."\n";
    $out .= '        <br />'."\n";
    $out .= '        <br /></td>'."\n";
    $out .= '      </tr>'."\n";
    $out .= '    </table>'."\n";
    $out .= '  </p>File is of type: '."\n";
    $out .= '  <tt>';
    $out .= $mime1;
    $out .= '</tt>, '."\n";
    $out .= '  <tt>';
    $out .= $mime2;
    $out .= '</tt>'."\n";
    $out .= '  <br />Uploaded on: ';
    $out .= $uploadtime;
    $out .= ', by   '."\n";
    $out .= '    <img class="userpicture" src="';
    $out .= $userpic;
    $out .= '" height="35" width="35" alt="Filler" /> '."\n";
    $out .= '     ';
    $out .= $username;
    $out .= ' <br />'."\n";
    $out .= '    Downloaded ';
    $out .= $downloadcount;
    $out .= ' times'."\n";
    $out .= '  <br /><br /><br />'."\n";
    return $out;
}


?>
