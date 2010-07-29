<?php

function archive_book($course, $mod, $relurl)
{
    global $CFG, $ARCHIVE, $COURSE;
    // Load the module instance
    if (! $book = get_record("book", "id", $mod->instance)) {
        error("mymod ID was incorrect");
    }
    // Add to module lising page
	//echo "<h3>Book: {$book->name}</h3>";
    addToModpage('book', $relurl, $mod->section, $book->name, $book->summary);
    // Create the header main page for the module
    $navlinks = array(array('name' => get_string('modulenameplural', 'book'), 'link' => 'mod/book/index.html', 'type' => 'title'));
    $navlinks[] = array('name'=>$book->name, 'link'=>'', 'type'=>'title');
    $navigation = build_archive_navigation($relurl, $navlinks);

/* Actually do the archiving - fill in the body of the main page, call functions to create other
pages, add images to lists to be collected etc. */
	$chapters = get_records('book_chapters', 'bookid', $book->id);
    //# need to sort chapters by pagenum
	usort($chapters, "chapter_order");

    $baseurl = substr($relurl,0, strrpos($relurl, '/')+1);

    $cn = 0;
    foreach($chapters as $id=>$chapter)
    {
    	$cn++;
    	if($id==0)
        	$pagefilename='view.html';
        else
	    	$pagefilename = pageNameToFileName('chapterid_'.$chapter->id);
        $toc = buildTOC($chapters, $id);
    	createBookChapter($book, $chapter, $baseurl, $pagefilename, $navigation, $toc, $cn);
    }
    return true;
}

function buildTOC($chapters, $thisid)
{
    $toc = '<div class="book_toc_numbered"><ul>';
    $cn = 0;
    foreach($chapters as $id=>$chapter)
    {
    	$cn++;
    	if($id==0)
        	$pagefilename='view.html';
        else
	    	$pagefilename = pageNameToFileName('chapterid_'.$chapter->id);
        if($id==$thisid)
           	$toc .= '<li><strong>'.$cn.' '.$chapter->title.'</strong></li>';
        else
           	$toc .= '<li><a href="'.$pagefilename.'">'.$cn.' '.$chapter->title.'</a></li>';

    }
    $toc .= '</ul></div>';
    return $toc;
}

function chapter_order($a, $b)
{
   if ($a->pagenum == $b->pagenum) {
       return 0;
   }
   return ($a->pagenum < $b->pagenum) ? -1 : 1;
}


function createBookChapter($book, $chapter, $baseurl, $pagefilename, $navigation, $toc, $cn)
{
    global $CFG, $ARCHIVE, $COURSE;
    $thispage = $baseurl.$pagefilename;

    $pagestart = getTemplateHeader($COURSE->fullname, $book->name, $navigation, 'mod-book course-30 dir-ltr lang-en_utf8 notinpopup nolayouttable');
    $out = $pagestart;
    $out .= book_tablestart();
    $out .= $toc;
    $out .= book_tablemiddle();
    $out .= '<p class="book_chapter_title">'.$cn.' '.$chapter->title.'</p>';
    $out .= $chapter->content;
    $out .= '</td></tr><tr><td>&nbsp;</td><td>Navigation to go here</td></tr>';
    $out .= book_tableend();
    $out .= getTemplateFooter();
    checkAndWriteFile($thispage, $out);
}

function book_tablestart()
{
	return '<table class="booktable" width="100%" cellspacing="0" cellpadding="2">
<!-- subchapter title and upper navigation row //-->
<tr>
    <td style="width:180px" valign="bottom">
        Table of Contents    </td>
    <td>
        <div class="bookexport"><a title="Print Complete Book" href="print.php?id=783" onclick="this.target=\'_blank\'"><img src="pix/print_book.gif" class="bigicon" alt="Print Complete Book"/></a><a title="Print This Chapter" href="print.php?id=783&amp;chapterid=5" onclick="this.target=\'_blank\'"><img src="pix/print_chapter.gif" class="bigicon" alt="Print This Chapter"/></a></div>
        <div class="booknav"><img src="pix/nav_prev_dis.gif" class="bigicon" alt="" /><a title="Next" href="view.php?id=783&amp;chapterid=6"><img src="pix/nav_next.gif" class="bigicon" alt="Next" /></a></div>
    </td>
</tr>
<!-- toc and chapter row //-->
<tr class="tocandchapter">
    <td style="width:180px" align="left"><div class="clearer">&nbsp;</div>
        <div class="wrap wraplevel2 generalbox ccbox box">
<div class="bt"><div>&nbsp;</div></div>
<div class="i1"><div class="i2"><div class="i3"><div class="book_toc_numbered">';
}

function book_tablemiddle()
{
	return '</div></div></div></div>
<div class="bb"><div>&nbsp;</div></div>
</div>    </td>
    <td align="right"><div class="clearer">&nbsp;</div>
        <div class="wrap wraplevel2 generalbox ccbox box">
<div class="bt"><div>&nbsp;</div></div>
<div class="i1"><div class="i2"><div class="i3"><div class="book_content">';
}

function book_tableend()
{
	return '</tr></table>';
}

?>
