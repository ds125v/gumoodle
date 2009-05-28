<?php
include_once('../../mod/forum/lib.php');
include_once('lib.php');

function archive_forum($course, $modforum, $relurl) {
    global $CFG, $ARCHIVE, $COURSE;
    //$out = getTemplateHeader();
    if (! $forum = get_record("forum", "id", $modforum->instance)) {
        error("Forum ID was incorrect");
    }
    $numdiscs = count_records('forum_discussions', 'course', $COURSE->id, 'forum', $forum->id);
    addToModpage('forum', $relurl, $modforum->section, $forum->name, $forum->intro, $numdiscs);
    $navlinks = array(array('name' => get_string('forums', 'forum'), 'link' => 'mod/forum/index.html', 'type' => 'title'));
    $navlinks[] = array('name'=>$forum->name, 'link'=>'', 'type'=>'title');
    $navigation = build_archive_navigation($relurl, $navlinks);
    $out = getTemplateHeader($COURSE->fullname, $forum->name, $navigation);
    //# I may have to create my own derivative of this, fixing links etc.
    //# alternative is to filter it later. (That could be made easier by
    //# changing $CFG->wwwroot to an easy to find value...)
    // Capturing Moodle forum code verson (leaves in inappropriate links etc.)
    //ob_start();
    //   forum_print_latest_discussions($course, $forum, -1, 'header', '', -1, -1, 0, $CFG->forum_manydiscussions, $modforum);
    //$forumhtml = ob_get_contents();
    //ob_end_clean();
    //processHyperlinks($forumhtml,);
    //$out .= $forumhtml;
    //URLs and image paths need fixed... (either that or replace forum_print_latest_discussions with custom code...)
    // Now that the path is a large randomish number, that is relatively easy - discuss.php?# -> discuss_#.html
    //echo '<pre>'; print_r($forum); echo '</pre>';
    //# Plan is to replace $forumhtml with my own code, the first db access of which is
    //# here. The layout will duplicate this list, but then link to pages with
    //# the full thread.
    //# Start with code from forum/lib.php line 4630
    $out .= startForumTable();
    $tlps = get_records('forum_discussions', 'forum', $forum->id, 'timemodified DESC');
    if($tlps) {
        // get number of replies ($replies[$discussion->id]['replies'] Yuk!)
        $replies = forum_count_discussion_replies($forum->id);
        foreach($tlps as $disc) {
            $firstpost = get_record('forum_posts', 'id', $disc->firstpost);
            $fpuser = get_record('user', 'id', $firstpost->userid);
            $lpuser = get_record('user', 'id', $disc->usermodified);
            //echo '<pre>'; print_r($firstpost); echo '</pre>';
            // code from forum/lib.php
            // forum_print_discussion_header($discussion, $forum, $group, $strdatestring, $cantrack, $forumtracked, $canviewparticipants, $context);
            $out .= '<tr class="discussion r0">';
            $out .= '<td class="topic starter">';
            $out .= '<a href="discuss_'.$disc->id.'.html">';
            $out .= $disc->name.'</a></td>';
            // icon (started by user?)
            //		   ob_start();
            //		   print_user_picture($fpuser, $forum->course);
            //		   $pichtml = ob_get_contents();
            //		   ob_end_clean();
            //           $out .= '<td class="picture">'.$pichtml."</td>\n";
            $out .= '<td class="picture">'.user_picture($fpuser)."</td>\n";
            // started by
            $out .= '<td class="author">'.$fpuser->firstname.' '.$fpuser->lastname."</td>\n";
            // replies
            if(isset($replies[$disc->id]->replies)) {
                $out .= '<td class="replies"><a href="discuss_'.$disc->id.'.html">'.$replies[$disc->id]->replies."</a></td>\n";
            } else {
                $out .= '<td class="replies">0</td>';
            }
            // last post (by<br/>time)
            $out .= '<td class="lastpost">'.$lpuser->firstname.' '.$lpuser->lastname.'<br />';
            $out .= userdate($disc->timemodified)."</td>\n";
            $out .= '</tr>';
            createDiscussionPage($forum, $disc, $relurl);
        }
    }
    $out .= '</tbody></table>';
    $out .= getTemplateFooter();
    checkAndWriteFile($relurl, $out);
    return true;
}

function startForumTable() {
    $out = '<table cellspacing="0" class="forumheaderlist">';
    $out .= '<thead>';
    $out .= '<tr>';
    $out .= '<th class="header topic" scope="col">'.get_string('discussion', 'forum').'</th>';
    $out .= '<th class="header author" colspan="2" scope="col">'.get_string('startedby', 'forum').'</th>';
    $out .= '<th class="header replies" scope="col">'.get_string('replies', 'forum').'</th>';
    $out .= '<th class="header lastpost" scope="col">'.get_string('lastpost', 'forum').'</th>';
    $out .= '</tr>';
    $out .= '</thead>';
    $out .= '<tbody>';
    return $out;
}

function createDiscussionPage($forum, $disc, $relurl) {
    //echo '<h3>Discussion page</h3>';
    //echo '<pre>'; print_r($disc); echo '</pre>';
    //echo '<p>relurl:'.$relurl.'</p>';
    //echo '<hr/>';
    global $CFG;
    global $COURSE;
    global $ARCHIVE;
    $navlinks = array(array('name' => get_string('forums', 'forum'), 'link' => 'mod/forum/index.html', 'type' => 'title'));
    $navlinks[] = array('name'=>$forum->name, 'link'=>$relurl, 'type'=>'title');
    $navlinks[] = array('name'=>$disc->name, 'link'=>'', 'type'=>'title');
    $navigation = build_archive_navigation($relurl, $navlinks);
    $page = getTemplateHeader($COURSE->fullname, $forum->name, $navigation, 'mod-forum');
    //forum_print_discussion($COURSE, $cm, $forum, $discussion, $post, $displaymode, $canreply, $canrate);
    $posts = forum_get_all_discussion_posts($disc->id, true, true);
    //echo '<pre>'; print_r($posts); echo '</pre>';
    foreach($posts as $p) {
        $page .= displayPost($p);
    }
    $page .= getTemplateFooter();
    $relurl2 = substr($relurl, 0, strrpos($relurl,'/')).'/discuss_'.$disc->id.'.html';
    checkAndWriteFile($relurl2, $page);
    return true;
}

function displayPost($p) {
    $out = '<a id="p'.$p->id.'"></a><table cellspacing="0" class="forumpost"><tr class="header">';
    $out .= '<td class="picture left">';
    $out .= user_picture($p->userid);
    $out .= '</td><td class="topic"><div class="subject">'.$p->subject.'</div>';
    $out .= '<div class="author">by '.$p->firstname.' '.$p->lastname.' - '.userdate($p->created).'</div></td></tr>';
    $out .= '<td class="left side"></td><td class="content">';
    $out .= $p->message;
    $out .= '<div class="commands">';
    if($p->parent > 0) $out .= '<a href="#p'.$p->parent.'">Show parent</a>';
    $out .= '</div></td></tr></table>';
    if((isset($p->children))&&(sizeof($p->children)>0)) {
        $out .= '<div class="indent">';
        foreach($p->children as $c) $out .= displayPost($c);
        $out .= '</div>';
    }
    return $out;
}

function build_forum_listpage($entries) {
    global $COURSE;
    $name = get_string("modulenameplural", 'forum');
    $navlinks = array();
    $navlinks[] = array('name' => $name, 'link' => '', 'type' => 'title');
    $relurl = 'mod/forum/index.html';
    $navigation = build_archive_navigation($relurl, $navlinks);
    $out = getTemplateHeader($name, $COURSE->fullname, $navigation);
    $week = 0;
    $out .= '<table width="80%"  cellpadding="5" cellspacing="1" class="generaltable boxaligncenter" >';
    $out .= '<tr>
                  <th style="vertical-align:top; text-align:left;;white-space:nowrap;"
                  class="header c0" scope="col">Forum</th>
                  <th style="vertical-align:top; text-align:left;;white-space:nowrap;"
                  class="header c1" scope="col">Description</th>
                  <th style="vertical-align:top; text-align:center;;white-space:nowrap;"
                  class="header c2" scope="col">Discussions</th>
                  </tr>';
    $extralist = false;
    foreach($entries as $e) {
        if($e['section'] == 1) {
            $out .= '<tr class="r0"><td style=" text-align:center;" class="cell c0">'.$e['name'].'</td>';
            $out .= '<td style=" text-align:left;" class="cell c1"><a href="'.$e['relurl'].'">'.$e['summary'].'</a></td>';
            $out .= '<td style=" text-align:center;" class="cell c2">'.$e['extra'].'</td></tr>';
        } else $extralist = true;
    }
    $out .= '</table>';
    if($extralist) {
        $out .= '<br/><br/>';
        $out .= '<table width="80%"  cellpadding="5" cellspacing="1" class="generaltable boxaligncenter" >';
        $out .= '<tr>
                      <th style="vertical-align:top; text-align:center; white-space:nowrap;"
                  	  class="header c0" scope="col">Week</th>
                      <th style="vertical-align:top; text-align:left;;white-space:nowrap;"
	                  class="header c0" scope="col">Forum</th>
	                  <th style="vertical-align:top; text-align:left;;white-space:nowrap;"
	                  class="header c1" scope="col">Description</th>
	                  <th style="vertical-align:top; text-align:center;;white-space:nowrap;"
	                  class="header c2" scope="col">Discussions</th>
	                  </tr>';
        foreach($entries as $e) {
            if($e['section'] != 1) {
                if($e['section'] != $week+1) {
                    $week = $e['section']-1;
                    $out .= '<tr class="r1"><td colspan="4"><div class="tabledivider"></div></td></tr>';
                    $out .= '<tr class="r0"><td style=" text-align:center;" class="cell c0">'.$week.'</td>';
                } else $out .= '<tr class="r0"><td style=" text-align:center;" class="cell c0">&nbsp;</td>';
                $out .= '<td style=" text-align:center;" class="cell c0">'.$e['name'].'</td>';
                $out .= '<td style=" text-align:left;" class="cell c1"><a href="'.$e['relurl'].'">'.$e['summary'].'</a></td>';
                $out .= '<td style=" text-align:center;" class="cell c2">'.$e['extra'].'</td></tr>';
            }
        }
        $out .= '</table>';
    }
    $out .= getTemplateFooter();
    checkAndWriteFile($relurl, $out);
}
?>

