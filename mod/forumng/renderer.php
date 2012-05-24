<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * ForumNG module renderer class
 * @see core_renderer Core renderer (you can call methods in this)
 * @package    mod
 * @subpackage forumng
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_forumng_renderer extends plugin_renderer_base {

    /**
     * Displays a discussion (main part of discussion page) with given options.
     * @param mod_forumng_discussion $discussion
     * @param object $options
     * @return string HTML content of discussion
     */
    public function render_discussion($discussion, $options) {
        // Get main bit of discussion
        $content = $discussion->get_root_post()->display_with_children($options);

        // Get lock post, if any
        $lockpost = $discussion->get_lock_post();
        if ($lockpost) {
            $content = '<div class="forumng-lockmessage">' .
                $lockpost->display(true,
                    array(mod_forumng_post::OPTION_NO_COMMANDS=>true)) .
            '</div>' . $content;
        }

        return $content;
    }

    /**
     * Opens table tag and displays header row ready for calling
     * render_discussion_list_item() a bunch of times.
     * @param mod_forumng $forum
     * @param int $groupid Group ID for display; may be NO_GROUPS or ALL_GROUPS
     * @param string $baseurl Base URL of current page
     * @param int $sort mod_forumng::SORT_xx constant for sort order
     * @return string HTML code for start of table
     */
    public function render_discussion_list_start($forum, $groupid, $baseurl,
        $sort, $sortreverse=false) {
        global $CFG;
        $th = "<th scope='col' class='header c";

        // Work out sort headers
        $baseurl = preg_replace('~&sort=[a-z]~', '', $baseurl);
        $baseurl = preg_replace('~&page=[0-9]+~', '', $baseurl);
        $sortdata = array();
        $reversechar = ($sortreverse) ? '' : 'r';
        foreach (array(mod_forumng::SORT_DATE, mod_forumng::SORT_SUBJECT, mod_forumng::SORT_AUTHOR,
                mod_forumng::SORT_POSTS, mod_forumng::SORT_UNREAD,
                mod_forumng::SORT_GROUP) as $possiblesort) {
            $data = new stdClass;
            if ($sort == $possiblesort) {
                $data->before = '<a ' . 'id="sortlink_' .
                        mod_forumng::get_sort_letter($possiblesort) .
                        '" href="' . s($baseurl) . '&amp;sort=' .
                        mod_forumng::get_sort_letter($possiblesort) . $reversechar .
                        '&amp;sortlink=' . mod_forumng::get_sort_letter($possiblesort) .
                        '" class="forumng-sortlink" '.
                        'title="'. mod_forumng::get_sort_title($possiblesort) . ' ' .
                        $this->get_sort_order_text($sort, !$sortreverse) . '">';
                $data->after = '</a>' . $this->get_sort_arrow($sort, $sortreverse);
            } else {
                $data->before = '<a ' . 'id="sortlink_' .
                        mod_forumng::get_sort_letter($possiblesort) . '" href="' . s($baseurl) .
                        '&amp;sort=' . mod_forumng::get_sort_letter($possiblesort) .
                        '&amp;sortlink=' . mod_forumng::get_sort_letter($possiblesort) .
                        '" title="'. mod_forumng::get_sort_title($possiblesort) . ' ' .
                        $this->get_sort_order_text($possiblesort) . '" class="forumng-sortlink">';
                $data->after = '</a>';
            }

            $sortdata[$possiblesort] = $data;
        }

        // Check group header
        if ($groupid == mod_forumng::ALL_GROUPS) {
            $grouppart = $sortdata[mod_forumng::SORT_GROUP]->before .
                get_string('group') . $sortdata[mod_forumng::SORT_GROUP]->after .
                "</th>{$th}3'>";
            $nextnum = 4;
        } else {
            $grouppart = '';
            $nextnum = 3;
        }
        $afternum = $nextnum + 1;

        if ($forum->can_mark_read()) {
            $unreadpart = "</th>{$th}$nextnum forumng-unreadcount'>" .
                $sortdata[mod_forumng::SORT_UNREAD]->before .
                get_string('unread', 'forumng') .
                $sortdata[mod_forumng::SORT_UNREAD]->after .
                "</th>{$th}$afternum lastcol'>";

        } else {
            $unreadpart = "</th>{$th}$nextnum lastcol'>";
        }

        return "<table class='generaltable forumng-discussionlist'><tr>" .
            "{$th}0'>" .
            $sortdata[mod_forumng::SORT_SUBJECT]->before .
            get_string('discussion', 'forumng') .
            $sortdata[mod_forumng::SORT_SUBJECT]->after .
            "</th>{$th}1'>" .
            $sortdata[mod_forumng::SORT_AUTHOR]->before .
            get_string('startedby', 'forumng') .
            $sortdata[mod_forumng::SORT_AUTHOR]->after .
            "</th>{$th}2'>" .
            $grouppart .
            $sortdata[mod_forumng::SORT_POSTS]->before .
            get_string('posts', 'forumng') .
            $sortdata[mod_forumng::SORT_POSTS]->after .
            $unreadpart .
            $sortdata[mod_forumng::SORT_DATE]->before .
            get_string('lastpost', 'forumng') .
            $sortdata[mod_forumng::SORT_DATE]->after .
            '</th></tr>';
    }

    /**
     * Displays a short version (suitable for including in discussion list)
     * of this discussion including a link to view the discussion and to
     * mark it read (if enabled).
     * @param mod_forumng_discussion $discussion Discussion
     * @param int $groupid Group ID for display; may be NO_GROUPS or ALL_GROUPS
     * @param bool $last True if this is the last item in the list
     * @return string HTML code to print out for this discussion
     */
    public function render_discussion_list_item(mod_forumng_discussion $discussion,
            $groupid, $last) {
        global $CFG;
        $showgroups = $groupid == mod_forumng::ALL_GROUPS;

        // Work out CSS classes to use for discussion
        $classes = '';
        $alts = array();
        $icons = array();
        if ($discussion->is_deleted()) {
            $classes .= ' forumng-deleted';
            $alts[] = get_string('alt_discussion_deleted', 'forumng');
            $icons[] = array(); // No icon, text will be output on its own
        }
        if (!$discussion->is_within_time_period()) {
            $classes .= ' forumng-timeout';
            $icon = 'timeout';
            $alts[] = get_string('alt_discussion_timeout', 'forumng');
            $icons[] = array('timeout', 'mod_forumng');
        }
        if ($discussion->is_sticky()) {
            $classes .= ' forumng-sticky';
            $alts[] = get_string('alt_discussion_sticky', 'forumng');
            $icons[] = array('sticky', 'mod_forumng');
        }
        if ($discussion->is_locked()) {
            $classes .= ' forumng-locked';
            $alts[] = get_string('alt_discussion_locked', 'forumng');
            $icons[] = array('i/unlock', 'moodle');
        }

        // Classes for Moodle table styles
        static $rownum = 0;
        $classes .= ' r' . $rownum;
        $rownum = 1 - $rownum;
        if ($last) {
            $classes .= ' lastrow';
        }

        $courseid = $discussion->get_forum()->get_course_id();

        // Start row
        $canmarkread = $discussion->get_forum()->can_mark_read();
        if ($canmarkread) {
            $unreadposts = $discussion->get_num_unread_posts();
            $classes = $unreadposts ? $classes . ' forumng-discussion-unread' : $classes;
        }

        $result = "<tr class='forumng-discussion-short$classes'>";

        // Subject, with icons
        $result .= "<td class='forumng-subject cell c0'>";
        foreach ($icons as $index => $icon) {
            $alt = $alts[$index];
            if ($icon) {
                $url = $this->pix_url($icon[0], $icon[1]);
                $result .= "<img src='$url' alt='$alt' title='$alt' /> ";
            } else {
                $result .= "<span class='accesshide'>$alt:</span> ";
            }
        }
        $result .= "<a href='discuss.php?" .
                $discussion->get_link_params(mod_forumng::PARAM_HTML) . "'>" .
                format_string($discussion->get_subject(), true, $courseid) . "</a></td>";

        // Author
        $poster = $discussion->get_poster();
        $result .= "<td class='forumng-startedby cell c1'>" .
            $this->user_picture($poster, array('courseid' => $courseid)) .
            $discussion->get_forum()->display_user_link($poster) . "</td>";

        $num = 2;

        // Group
        if ($showgroups) {
            $result .= '<td class="cell c' . $num . '">'
                . ($discussion->get_group_name()) . '</td>';
            $num++;
        }

        // Number of posts
        $result .= '<td class="cell c' . $num . '">'
            . ($discussion->get_num_posts()) . '</td>';

        $num++;

        // Number of unread posts
        if ($canmarkread) {
            $result .= '<td class="cell forumng-unreadcount c3">';
            if ($unreadposts) {
                $result .=
                '<a href="discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_HTML) .
                '#firstunread">' . $unreadposts . '</a>' .
                '<form method="post" action="markread.php"><div>&nbsp;&nbsp;&nbsp;'.
                $discussion->get_link_params(mod_forumng::PARAM_FORM) .
                '<input type="hidden" name="back" value="view" />' .
                '<input type="image" title="' .
                    get_string('markdiscussionread', 'forumng') .
                    '" src="' . $this->pix_url('t/clear') . '" ' .
                    'class="iconsmall" alt="' .
                    get_string('markdiscussionread', 'forumng') .
                '" /></div></form>';
            } else {
                $result .= $unreadposts;
            }

            $result .= '</td>';
            $num = 4;
        }

        // Last post
        $last = $discussion->get_last_post_user();

        $result .= '<td class="cell c' . $num .' lastcol forumng-lastpost">' .
            mod_forumng_utils::display_date($discussion->get_time_modified()) . "<br/>" .
            "<a href='{$CFG->wwwroot}/user/view.php?id={$last->id}&amp;" .
            "course=$courseid'>" . fullname($last, has_capability(
                'moodle/site:viewfullnames',
                $discussion->get_forum()->get_context())) . "</a></td>";

        $result .= "</tr>";
        return $result;
    }

    /**
     * Displays divider within discussion list.
     * @param mod_forumng $forum
     * @param int $groupid Group ID for display; may be NO_GROUPS or ALL_GROUPS
     * @return string HTML code for end of table
     */
    public function render_discussion_list_divider($forum, $groupid) {
        $showgroups = $groupid == mod_forumng::ALL_GROUPS;
        $count = 4 + ($showgroups ? 1 : 0) + ($forum->can_mark_read() ? 1 : 0);
        return '<tr class="forumng-divider"><td colspan="' .
            $count . '"></td></tr>';
    }

    /**
     * Closes table tag after calling render_discussion_list_start() and
     * render_discussion_list_end().
     * @param mod_forumng $forum
     * @param int $groupid Group ID for display; may be NO_GROUPS or ALL_GROUPS
     * @return string HTML code for end of table
     */
    public function render_discussion_list_end($forum, $groupid) {
        return '</table>';
    }

    /**
     * Opens table tag and displays header row ready for calling
     * render_draft_list_item() a bunch of times.
     * @return string HTML code for start of table
     */
    public function render_draft_list_start() {
        $result = '<div class="forumng-drafts"><div class="forumng-heading"><h3>' .
            get_string('drafts', 'forumng') . '</h3>';
        $result .= $this->help_icon('drafts', 'forumng') . '</div>';

        $th = "<th scope='col' class='header c";
        $result .= "<table class='generaltable'><tr>" .
            "{$th}0'>" . get_string('draft', 'forumng') .
            "</th>{$th}1'>" . get_string('discussion', 'forumng') .
            "</th>{$th}2 lastcol'>" . get_string('date') . '</th></tr>';

        return $result;
    }

    protected static function get_post_summary($subject, $message) {
        $summary = '<strong>' . format_string($subject) . '</strong> ' . strip_tags($message);
        $summary = str_replace('<strong></strong>', '', $summary);
        $summary = self::nice_shorten_text($summary);
        return $summary;
    }

    public function render_draft_list_item($forum, $draft, $last) {
        global $CFG;

        // Classes for Moodle table styles
        static $rownum = 0;
        $classes = ' r' . $rownum;
        $rownum = 1 - $rownum;
        if ($last) {
            $classes .= ' lastrow';
        }

        $summary = self::get_post_summary($draft->get_subject(),
                $draft->get_formatted_message($forum));

        $result = '<tr class="' . $classes . '">';
        $link = '<a href="editpost.php?draft=' . $draft->get_id() .
                $forum->get_clone_param(mod_forumng::PARAM_PLAIN) . '"' .
                ($draft->is_reply()
                    ? ' class="forumng-draftreply-' . $draft->get_discussion_id() . '-' .
                        $draft->get_parent_post_id() . '"'
                        : '') . '>';
        $result .= '<td class="cell c0">'. $link . $summary . '</a> '.
            '<a href="deletedraft.php?draft=' . $draft->get_id() .
            $forum->get_clone_param(mod_forumng::PARAM_PLAIN) .
            '" title="' . get_string('deletedraft', 'forumng') .
            '"><img src="' . $this->pix_url('t/delete') . '" alt="' .
            get_string('deletedraft', 'forumng') . '"/></a></td>';

        if ($draft->is_reply()) {
            $result .= '<td class="cell c1">' .
                format_string($draft->get_discussion_subject()) . ' ';
            $result .= '<span class="forumng-draft-inreplyto">' .
                get_string('draft_inreplyto', 'forumng',
                    $forum->display_user_link($draft->get_reply_to_user()));
            $result .= '</span></td>';
        } else {
            $result .= '<td class="cell c1">' .
                get_string('draft_newdiscussion', 'forumng') . '</td>';
        }

        $result .= '<td class="cell c2 lastcol">' .
            mod_forumng_utils::display_date($draft->get_saved()) . '</td>';

        $result .= '</tr>';
        return $result;
    }

    /**
     * Closes table tag after draft list.
     * @return string HTML code for end of table
     */
    public function render_draft_list_end() {
        return '</table></div>';
    }

    /**
     * Displays a skip link to the flagged posts box
     * @param $flagged array of $flagged posts
     * @return string HTML for link and wrapper or '' if no flagged posts
     */
    public function render_flagged_list_link($flagged) {
        $numberflagged = count($flagged);
        if ($numberflagged == 0) {
            return '';
        }

        $flaggedtxt = get_string('flaggedpostslink', 'forumng', $numberflagged);

        return '<div class="forumng-flagged-link"><a href="#forumng-flaggedposts">' .
            '<img src="' . $this->pix_url('flag.on', 'mod_forumng'). '" alt="' .
            get_string('flagon', 'forumng') . '"/> ' . $flaggedtxt. '</a></div>';
    }

    /**
     * Opens table tag and displays header row ready for calling.
     * render_flagged_list_item() a bunch of times.
     * @return string HTML code for start of table
     */
    public function render_flagged_list_start() {
        global $CFG;

        $result = '<div class="forumng-flagged" id="forumng-flaggedposts">
            <div class="forumng-heading"><h3>' .
            get_string('flaggedposts', 'forumng') . '</h3>';
        $result .= $this->help_icon('flaggedposts', 'forumng') . '</div>';

        $th = "<th scope='col' class='header c";

        $result .= "<table class='generaltable'><tr>" .
            "{$th}0'>" . get_string('post', 'forumng') .
            "</th>{$th}1'>" . get_string('discussion', 'forumng') .
            "</th>{$th}2 lastcol'>" . get_string('date') . '</th></tr>';

        return $result;
    }

    /**
     * Displays a flagged item.
     * @param mod_forumng_post $post
     * @param bool $last
     * @return string HTML code for table row
     */
    public function render_flagged_list_item($post, $last) {
        global $CFG;

        // Classes for Moodle table styles
        static $rownum = 0;
        $classes = ' r' . $rownum;
        $rownum = 1 - $rownum;
        if ($last) {
            $classes .= ' lastrow';
        }

        $result = '<tr class="' . $classes . '">';

        // Post cell
        $result .= '<td class="cell c0">';

        // Get post URL
        $discussion = $post->get_discussion();
        $link = '<a href="discuss.php?' .
                $discussion->get_link_params(mod_forumng::PARAM_HTML) .
                '#p' . $post->get_id() . '">';

        // Get post summary
        $summary = self::get_post_summary($post->get_subject(), $post->get_formatted_message());
        $result .= $link . $summary . '</a>';

        $result .= '<small> ' . get_string('postby', 'forumng',
            $post->get_forum()->display_user_link($post->get_user())) .
            '</small>';

        // Show flag icon. (Note: I tried to use &nbsp; before this so the
        // icon never ends up on a line of its own, but it does not work.)
        $result .= ' <form class="forumng-flag" action="flagpost.php" method="post"><div>' .
            '<input type="hidden" name="p" value="' . $post->get_id() . '" />'.
            '<input type="hidden" name="back" value="view" />'.
            '<input type="hidden" name="flag" value="0" />'.
            '<input type="image" title="' . get_string('clearflag', 'forumng') .
            '" src="' . $this->pix_url('flag.on', 'mod_forumng'). '" alt="' .
            get_string('flagon', 'forumng') .
            '" /></div></form></td>';

        // Discussion cell
        $result .= '<td class="cell c1"><a href="discuss.php?' .
                $discussion->get_link_params(mod_forumng::PARAM_HTML) . '">' .
                format_string($discussion->get_subject()) . '</a></td>';

        // Date cell
        $result .= '<td class="cell c2 lastcol">' .
            mod_forumng_utils::display_date($post->get_created()) . '</td></tr>';
        return $result;
    }

    /**
     * Closes table tag after flagged post list.
     * @return string HTML code for end of table
     */
    public function render_flagged_list_end() {
        return '</table></div>';
    }

    /**
     * Display intro section for forum.
     * @param mod_forumng $forum Forum
     * @return string Intro HTML or '' if none
     */
    public function render_intro($forum) {
        // Don't output anything if no text, so we don't get styling around
        // something blank
        $text = $forum->get_intro();
        if (trim($text) === '') {
            return '';
        }

        // Make fake activity object in required format, and use to format
        // intro for module with standard function (which handles images etc.)
        $activity = (object)array('intro' => $forum->get_intro(),
                'introformat' => $forum->get_intro_format());
        $intro = format_module_intro(
                'forumng', $activity, $forum->get_course_module_id(true));

        // Box styling appears to be consistent with some other modules
        $intro = html_writer::tag('div', $intro, array('class' => 'generalbox box',
                'id' => 'intro'));

        return $intro;
    }

    /**
     * Display post button for forum.
     * @param mod_forumng $forum Forum
     * @param int $groupid Group
     * @return string Post button
     */
    public function render_post_button($forum, $groupid) {
        return '<div id= "forumng-buttons"><form action="editpost.php" method="get" ' .
                'class="forumng-post-button"><div>' .
                $forum->get_link_params(mod_forumng::PARAM_FORM) .
                ($groupid != mod_forumng::NO_GROUPS
                    ? '<input type="hidden" name="group" value="' . (int)$groupid . '" />'
                    : '') .
                '<input type="submit" value="' .
                get_string('addanewdiscussion', 'forumng') . '" /></div></form>' .
                $this->render_paste_button($forum, $groupid) . '</div>';
    }

    /**
     * Display paste button for forum.
     * @param mod_forumng $forum Forum
     * @param int $groupid Group
     * @return string Paste discussion button
     */
    public function render_paste_button($forum, $groupid) {
        global $SESSION;
        if (isset($SESSION->forumng_copyfrom)) {
            $cmid = required_param('id', PARAM_INT);
            return '<form action="feature/copy/paste.php" method="get" '.
                    'class="forumng-paste-buttons">' .
                    '<div><input type="submit" name="paste" value="' .
                    get_string('pastediscussion', 'forumng') . '" />' .
                    '<input type="submit" name="cancel" value="' . get_string('cancel') . '" />' .
                    '<input type="hidden" name="cmid" value="' . $cmid . '" />' .
                    $forum->get_clone_param(mod_forumng::PARAM_FORM) .
                    ($groupid != mod_forumng::NO_GROUPS
                    ? '<input type="hidden" name="group" value="' . (int)$groupid . '" />'
                    : '') . '</div></form>';
        } else {
            return '';
        }
    }

    /**
     * Display 'Switch to simple/standard view' link, except in cases where
     * browser is not supported for 'standard' view anyhow.
     * @return string HTML for the switch link.
     */
    public function render_switch_link() {
        global $PAGE;
        if ($PAGE->devicetypeinuse == 'legacy') {
            return '';
        }
        $simple = get_user_preferences('forumng_simplemode', '');
        if ($simple) {
            return '<div class="forumng-switchlinkblock">' .
                    get_string('switchto_standard_text', 'forumng') .
                    ' <a href="viewmode.php?simple=0">' .
                    get_string('switchto_standard_link', 'forumng') . '</a></div>';
        } else {
            return '<div class="accesshide forumng-switchlinkblock">' .
                    get_string('switchto_simple_text', 'forumng') .
                    ' <a id="forumng-switchlinkid" class="forumng-switchlink" href="viewmode.php' .
                    '?simple=1">' . get_string('switchto_simple_link', 'forumng') . '</a></div>';
        }
    }

    /**
     * Display subscribe options.
     * @param mod_forumng $forum Forum
     * @param string $text Textual note
     * @param int $subscribed
     * @param bool $button True if subscribe/unsubscribe button should be shown
     * @param bool $viewlink True if 'view subscribers' link should be shown
     * @return string HTML code for this area
     */
    public function render_subscribe_options($forum, $text, $subscribed,
        $button, $viewlink) {
        $out = '<div class="forumng-subscribe-options">' .
            '<h3>' . get_string('subscription', 'forumng') . '</h3>' .
            '<p>' . $text . '</p>';
        $cm = $forum->get_course_module();
        if ($button) {
            $outsubmit = '';
            $currentgroupid = mod_forumng::get_activity_group($cm, true);
            if ($currentgroupid == mod_forumng::NO_GROUPS) {
                $currentgroupid = 0;
            }
            if ($subscribed == mod_forumng::FULLY_SUBSCRIBED ||
                    $subscribed == mod_forumng::FULLY_SUBSCRIBED_GROUPMODE) {
                $outsubmit .= '<input type="submit" name="submitunsubscribe" value="' .
                        get_string('unsubscribeshort', 'forumng') . '" />';
            } else if ($subscribed == mod_forumng::PARTIALLY_SUBSCRIBED) {
                //print both subscribe button and unsubscribe button
                $outsubmit .= '<input type="submit" name="submitsubscribe" value="' .
                    get_string('subscribelong', 'forumng') . '" />' .
                    '<input type="submit" name="submitunsubscribe" value="' .
                    get_string('unsubscribelong', 'forumng') . '" />';
            } else if ($subscribed == mod_forumng::NOT_SUBSCRIBED) {
                //default unsubscribed, print subscribe button
                $outsubmit .= '<input type="submit" name="submitsubscribe" value="' .
                        get_string('subscribeshort', 'forumng') . '" />';
            } else if ($subscribed == mod_forumng::THIS_GROUP_PARTIALLY_SUBSCRIBED) {
                $outsubmit .= '<input type="submit" name="submitsubscribe_thisgroup" value="' .
                    get_string('subscribegroup', 'forumng') . '" />' .
                    '<input type="submit" name="submitunsubscribe_thisgroup" value="' .
                    get_string('unsubscribegroup_partial', 'forumng') . '" />'.
                    '<input type="hidden" name="g" value="' . $currentgroupid . '" />';
            } else if ($subscribed == mod_forumng::THIS_GROUP_SUBSCRIBED) {
                $outsubmit .= '<input type="submit" name="submitunsubscribe_thisgroup" value="' .
                    get_string('unsubscribegroup', 'forumng') . '" />'.
                    '<input type="hidden" name="g" value="' . $currentgroupid . '" />';
            } else if ($subscribed == mod_forumng::THIS_GROUP_NOT_SUBSCRIBED) {
                $outsubmit .= '<input type="submit" name="submitsubscribe_thisgroup" value="' .
                    get_string('subscribegroup', 'forumng') . '" />'.
                    '<input type="hidden" name="g" value="' . $currentgroupid . '" />';
            }

            $out .= '&nbsp;<form action="subscribe.php" method="post"><div>' .
                $forum->get_link_params(mod_forumng::PARAM_FORM) .
                '<input type="hidden" name="back" value="view" />' .
                $outsubmit . '</div></form>';
        }
        if ($viewlink) {
            $out .= ' <div class="forumng-subscribe-admin">' .
                '<a href="subscribers.php?' .
                $forum->get_link_params(mod_forumng::PARAM_HTML) . '">' .
                get_string('viewsubscribers', 'forumng') . '</a></div>';
        }
        $out .= '</div>';
        return $out;
    }
    /**
     * Display subscribe option for discussions.
     * @param discussion $discussion Forum
     * @param string $text Textual note
     * @param bool $subscribe True if user can subscribe, False if user can unsubscribe
     * @return string HTML code for this area
     */
    public function render_discussion_subscribe_option($discussion, $subscribe) {
        global $USER;
        if ($subscribe) {
            $status = get_string('subscribestate_discussionunsubscribed', 'forumng');
            $submit = 'submitsubscribe';
            $button = get_string('subscribediscussion', 'forumng');
        } else {
            $status = get_string('subscribestate_discussionsubscribed', 'forumng',
                    '<strong>' . $USER->email . '</strong>' );
            $submit = 'submitunsubscribe';
            $button = get_string('unsubscribediscussion', 'forumng');
        }
        return '<div class="forumng-subscribe-options" id="forumng-subscribe-options">' .
            '<h3>' . get_string('subscription', 'forumng') . '</h3>' .
            '<p>' . $status .
            '</p>' . '&nbsp;<form action="subscribe.php" method="post"><div>' .
            $discussion->get_link_params(mod_forumng::PARAM_FORM) .
            '<input type="hidden" name="back" value="discuss" />' .
            '<input type="submit" name="' . $submit . '" value="' .
            $button . '" /></div></form></div>';
    }

    /**
     * Display a post. This method is used for:
     * - The normal HTML display of a post
     * - HTML email of a post
     * - Text-only email of a post
     * These are all combined in one method since ordinarily they change at
     * the same time (i.e. if adding/hiding information it is usually added to
     * or hidden from all views).
     *
     * $options is an associative array from a mod_forumng_post::OPTION_xx constant.
     * All available options are always set - if they were not set by
     * the user, they will have been set to false before this call happens,
     * so there is no need to use empty() or isset().
     *
     * Options are as follows. These are available in email mode:
     *
     * OPTION_TIME_ZONE (int) - Moodle time zone
     * OPTION_VIEW_FULL_NAMES (bool) - If user is allowed to see full names
     * OPTION_EMAIL (bool) - True if this is an email (false = standard view)
     * OPTION_DIGEST (bool) - True if this is part of an email digest
     * OPTION_COMMAND_REPLY (bool) - True if 'Reply' link should be displayed
     *   (available in email too)
     *
     * These options only apply in non-email usage:
     *
     * OPTION_SUMMARY (bool) - True if the entire post should not be displayed,
     *   only a short summary
     * OPTION_NO_COMMANDS (bool) - True if this post is being printed on its own
     * OPTION_COMMAND_EDIT (bool) - Display 'Edit' command
     * OPTION_COMMAND_DELETE (bool) - Display 'Edit' command
     * OPTION_COMMAND_SPLIT (bool) - Display 'Split' command
     * OPTION_RATINGS_VIEW (bool) - True to display current ratings
     * OPTION_RATINGS_EDIT (bool) - True to display ratings edit combo
     * OPTION_LEAVE_DIV_OPEN (bool) - True to not close post div (means that
     *   child posts can be added within).
     * OPTION_EXPANDED (bool) - True to show full post, otherwise abbreviate
     * OPTION_DISCUSSION_SUBJECT (bool) - If true, and only IF post is a
     *   discussion root, includes subject (HTML, shortened as it would be for
     *   header display) as a hidden field.
     *
     * @param mod_forumng_post $post Post object
     * @param bool $html True if using HTML, false to output in plain text
     * @param array $options Associative array of name=>option, as above
     * @return string HTML or text of post
     */
    public function render_post($post, $html, $options) {
        global $CFG, $USER, $THEME;
        $discussion = $post->get_discussion();

        $expanded = $options[mod_forumng_post::OPTION_EXPANDED];
        $export = $options[mod_forumng_post::OPTION_EXPORT];
        $email = $options[mod_forumng_post::OPTION_EMAIL];

        // When posts are deleted we hide a lot of info - except when the person
        // viewing it has the ability to view deleted posts.
        $deletedhide = $post->get_deleted()
            && !$options[mod_forumng_post::OPTION_VIEW_DELETED_INFO];
        // Hide deleted messages if they have no replies
        if ($deletedhide && ($export || !$email) && !$post->has_children()) {
            // note: !email check is to deal with posts that are deleted
            // between when the mail list finds them, and when it sends out
            // mail. It would be confusing to send out a blank email so let's
            // not do that. Also, ->has_children() is not safe to call during
            // email processing because it doesn't load the whole discussion.
            return '';
        }

        // Save some bandwidth by not sending link full paths except in emails
        if ($options[mod_forumng_post::OPTION_FULL_ADDRESSES]) {
            $linkprefix = $CFG->wwwroot . '/mod/forumng/';
        } else {
            $linkprefix = '';
        }

        $postnumber = (($options[mod_forumng_post::OPTION_NO_COMMANDS] || $email) &&
            !$options[mod_forumng_post::OPTION_VISIBLE_POST_NUMBERS])
            ? '' : $post->get_number();

        $lf = "\n";

        // Initialise result
        $out = '';
        if ($html) {
            if ($export) {
                $out .= '<hr />';
            }
            // Basic intro
            $classes = $expanded ? ' forumng-full' : ' forumng-short';
            $classes .= $post->is_important() ? ' forumng-important' : '';
            $classes .= (!$email && !$options[mod_forumng_post::OPTION_UNREAD_NOT_HIGHLIGHTED] &&
                $post->is_unread()) ? ' forumng-unread' : ' forumng-read';
            $classes .= $post->get_deleted() ? ' forumng-deleted' : '';
            $classes .= ' forumng-p' .$postnumber;
            $out .= $lf . '<div class="forumng-post' . $classes . '">' .
                    '<div class="post-deco"><div class="post-deco-bar"></div></div><a id="p' .
                    $post->get_id() . '"></a>';
            if ($options[mod_forumng_post::OPTION_FIRST_UNREAD]) {
                $out .= '<a id="firstunread"></a>';
            }

            // Theme hooks
            if (!empty($THEME->forumng_post_hooks)) {
                for ($i=1; $i<=$THEME->forumng_post_hooks; $i++) {
                    $out .= '<div class="forumng-'. $i .'"></div>';
                }
            }
        }

        if ($html || $options[mod_forumng_post::OPTION_VISIBLE_POST_NUMBERS]) {
            // Accessible text giving post a number so we can make links unique
            // etc.
            if ($postnumber) {
                $data = new stdClass;
                $data->num = $postnumber;
                if ($post->get_parent()) {
                    if ($html) {
                        $data->parent = '<a class="forumng-parentlink" href="#p' .
                            $post->get_parent()->get_id() .
                            '">' . $post->get_parent()->get_number() . '</a>';
                    } else {
                        $data->parent = $post->get_parent()->get_number();
                    }
                    $data->info = '';
                    if ($post->is_unread()) {
                        $data->info = get_string('postinfo_unread', 'forumng');
                    }
                    if (!$expanded) {
                        $data->info .= ' ' . get_string('postinfo_short', 'forumng');
                    }
                    if ($post->get_deleted()) {
                        $data->info .= ' ' . get_string('postinfo_deleted', 'forumng');
                    }
                    $data->info = trim($data->info);
                    if ($data->info) {
                        $data->info = ' (' . $data->info . ')';
                    }
                    $info = get_string('postnumreply', 'forumng', $data);
                } else {
                    $info = get_string('postnum', 'forumng', $data);
                }
                if ($options[mod_forumng_post::OPTION_VISIBLE_POST_NUMBERS]) {
                    if (!$html) {
                        $out .= "## " . $info . "\n";
                    }
                }
            }
        }

        // Discussion subject (root only)
        if ($options[mod_forumng_post::OPTION_DISCUSSION_SUBJECT] &&
            $post->is_root_post()) {
            $out .= '<input type="hidden" name="discussion_subject" value="' .
                shorten_text(htmlspecialchars($post->get_subject())) .
                '" />';
        }

        // Pictures (HTML version only)
        if ($html) {
            $out .= $lf . html_writer::start_tag('div', array('class' => 'forumng-pic-info'));
        }
        if ($html && !$export && $options[mod_forumng_post::OPTION_USER_IMAGE]) {
            $out .= $lf . html_writer::start_tag('div', array('class' => 'forumng-pic'));

            // User picture
            $out .= $deletedhide ? '' : $post->display_user_picture();

            // Group pictures if any - only for expanded version
            if ($expanded) {
                $grouppics = $post->display_group_pictures();
                if ($grouppics) {
                    $out .= '<div class="forumng-grouppicss">' . $grouppics .
                      '</div>';
                }
            }

            $out .=  html_writer::end_tag('div');
        }

        // Link used to expand post
        $expandlink = '';
        if (!$expanded && !$deletedhide) {
            $expandlink = $this->render_expand_link($linkprefix, $discussion, $post);
        }

        // Byline
        $by = new stdClass;
        $by->name = $deletedhide ? '' : fullname($post->get_user(),
            $options[mod_forumng_post::OPTION_VIEW_FULL_NAMES]);
        $by->date = $deletedhide ? '' : userdate($post->get_created(),
                get_string('strftimedatetime', 'langconfig'),
                $options[mod_forumng_post::OPTION_TIME_ZONE]);

        if ($html) {
            $out .= $lf . '<div class="forumng-info"><h2 class="forumng-author">';
            $out .= $post->is_important() ? '<img src="' .
            $this->pix_url('exclamation_mark', 'mod_forumng') . '" alt="' .
            get_string('important', 'forumng') . '" ' .
            'title = "' . get_string('important', 'forumng') . '"/>' : '';
            if ($export) {
                $out .=  $by->name;
            } else {
                $out .= '<a href="' . $CFG->wwwroot . '/user/view.php?id=' .
                    $post->get_user()->id .
                    ($post->get_forum()->is_shared() ? '' : '&amp;course=' .
                    $post->get_forum()->get_course_id()) .
                    '">' . $by->name . '</a>';
            }
            if ($postnumber) {
                if ($options[mod_forumng_post::OPTION_VISIBLE_POST_NUMBERS]) {
                    $out .= html_writer::tag('small', ' ' . $info,
                            array('class' => 'accesshide', 'style' => 'position:static'));
                } else {
                    $out .= '<span class="accesshide"> ' . $info . ' </span>';
                }
            }
            $out .= $deletedhide ? '' : '</h2> <span class="forumng-separator">&#x2022;</span> ';
            $out .= '<span class="forumng-date">' . $by->date . '</span>';
            if ($edituser = $post->get_edit_user()) {
                $out .= ' <span class="forumng-separator">&#x2022;</span> ' .
                    '<span class="forumng-edit">';
                $edit = new stdClass;
                $edit->date = userdate($post->get_modified(),
                    get_string('strftimedatetime', 'langconfig'),
                    $options[mod_forumng_post::OPTION_TIME_ZONE]);
                $edit->name = fullname($edituser,
                    $options[mod_forumng_post::OPTION_VIEW_FULL_NAMES]);
                if ($edituser->id == $post->get_user()->id) {
                    $out .= get_string('editbyself', 'forumng', $edit->date);
                } else {
                    $out .= get_string('editbyother', 'forumng', $edit);
                }

                if ($options[mod_forumng_post::OPTION_COMMAND_HISTORY]) {
                    $out .= ' (<a href="history.php?' .
                            $post->get_link_params(mod_forumng::PARAM_HTML) . '">' .
                            get_string('history', 'forumng') . '</a>)';
                }
                $out .= '</span>';
            }
            if ($options[mod_forumng_post::OPTION_SELECTABLE]) {
                $out .= '<span class="forumng-separator"> &#x2022; </span>' .
                        '<input type="checkbox" name="selectp' .
                        $post->get_id() . '" id="id_selectp' . $post->get_id() .
                        '" /><label class="accesshide" for="id_selectp' .
                        $post->get_id() . '">' .
                        get_string('selectlabel', 'forumng', $postnumber) . '</label>';
            }
            if ($options[mod_forumng_post::OPTION_FLAG_CONTROL]) {
                $out .= '<div class="forumng-flag">' .
                    '<input type="image" title="' . get_string(
                        $post->is_flagged() ? 'clearflag' : 'setflag', 'forumng') .
                    '" src="' . $this->pix_url('flag.' .
                        ($post->is_flagged() ? 'on' : 'off'), 'mod_forumng') . '" alt="' .
                        get_string($post->is_flagged() ? 'flagon' : 'flagoff',
                            'forumng') .
                    '" name="action.flag.p_' . $post->get_id() . '.timeread_' .
                        $options[mod_forumng_post::OPTION_READ_TIME] . '.flag_' .
                        ($post->is_flagged() ? 0 : 1) .
                    '"/></div>';
            }
            // End: forumng-info.
            $out .= html_writer::end_tag('div');
            // End: forumng-pic-info.
            $out .=  html_writer::end_tag('div');
        } else {
            $out .= $by->name . ' - ' . $by->date . $lf;

            $out .= mod_forumng_cron::EMAIL_DIVIDER;
        }

        // Add a outer div to main contents
        if ($html) {
            $out .= '<div class="forumng-post-outerbox">';
        }
        if ($html && $post->get_deleted()) {
            $out .= '<p class="forumng-deleted-info"><strong>' .
                get_string('deletedpost', 'forumng') . '</strong> ';
            if ($deletedhide) {
                $out .= get_string($post->get_delete_user()->id == $post->get_user()->id
                    ? 'deletedbyauthor' : 'deletedbymoderator', 'forumng',
                    userdate($post->get_deleted()));
            } else {
                $a = new stdClass;
                $a->date = userdate($post->get_deleted());
                $a->user = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' .
                    $post->get_delete_user()->id . '&amp;course=' .
                    $post->get_forum()->get_course_id() . '">'  .
                    fullname($post->get_delete_user(),
                        $options[mod_forumng_post::OPTION_VIEW_FULL_NAMES]) . '</a>';
                $out .= get_string('deletedbyuser', 'forumng', $a);
            }
            $out .= '</p>';
        }

        // Get subject. This may make a db query when showing a single post
        // (which includes parent subject).
        if ($options[mod_forumng_post::OPTION_EMAIL]
            || $options[mod_forumng_post::OPTION_NO_COMMANDS]) {
            $subject = $post->get_effective_subject(true);
        } else {
            $subject = $post->get_subject();
        }

        // Otherwise, subject is only displayed if it has changed
        if ($subject !== null && $expanded && !$deletedhide) {
            if ($html) {
                $out .= $lf . '<h3 class="forumng-subject">';
                if ($options[mod_forumng_post::OPTION_DIGEST]) {
                    // Digest contains link to original post
                    $out .=
                        '<a href="' . $linkprefix .
                        'discuss.php?' . $discussion->get_link_params(mod_forumng::PARAM_HTML) .
                        '#p' . $post->get_id() . '">' .
                        format_string($subject) . '</a>';
                } else {
                    $out .= format_string($subject);
                }
                $out .= '</h3>';
            } else {
                $out .= format_string($subject, true);
                if ($options[mod_forumng_post::OPTION_DIGEST]) {
                    // Link to original post
                    $out .= " <{$linkprefix}discuss.php?" .
                            $discussion->get_link_params(mod_forumng::PARAM_HTML) .
                            $discussion->get_id() . '#p' . $post->get_id() . '>';
                }
                $out .= $lf;
            }
        }

        // Get content of actual message in HTML
        if ($html) {
            $message = $post->get_formatted_message();

            if (!$expanded && !$deletedhide) {
                // When not expanded and no subject, we include a summary of the
                // message
                $stripped = strip_tags(
                    preg_replace('~<script.*?</script>~s', '', $message));
                $messagetosummarise = $subject !== null
                    ? '<h3>' . $subject . '</h3>&nbsp;' . $stripped
                    : $stripped;
                $summary = self::nice_shorten_text($messagetosummarise, 50);
                $out .= $lf . '<div class="forumng-summary"><div class="forumng-text">' .
                     $summary . '</div> ' . $expandlink . '</div>';
            }
        }

        // Start of post main section
        if ($expanded && !$deletedhide) {
            if ($html) {
                $out .= '<div class="forumng-postmain">';
            }

            // Attachments
            $attachments = $post->get_attachment_names();
            if (count($attachments)) {
                if ($html) {
                    $out .= $lf;
                    if (count($attachments) == 1) {
                        $attachmentlabel = get_string('attachment', 'forumng');
                    } else {
                        $attachmentlabel = get_string('attachments', 'forumng');
                    }
                    $out .= '<span class="accesshide">' . $attachmentlabel .
                            '</span><ul class="forumng-attachments">';
                }
                foreach ($attachments as $attachment) {
                    if ($html) {
                        require_once($CFG->libdir . '/filelib.php');
                        $iconsrc = $this->pix_url('/f/' . mimeinfo('icon', $attachment));
                        $alt = get_mimetype_description(
                            mimeinfo('type', $attachment));

                        $out .= '<li><a href="' . $post->get_attachment_url($attachment) . '">' .
                                '<img src="' . $iconsrc . '" alt="' . $alt . '" /> <span>' .
                                htmlspecialchars($attachment) . '</span></a> </li>';
                    } else {
                        // Right-align the entry to 70 characters
                        $padding = 70 - strlen($attachment);
                        if ($padding > 0) {
                            $out .= str_repeat(' ', $padding);
                        }

                        // Add filename
                        $out .= $attachment . $lf;
                    }
                }

                if ($html) {
                    $out .= '</ul>' . $lf;
                } else {
                    $out .= $lf; // Extra line break after attachments
                }
            }

            // Display actual content
            if ($html) {
                if ($options[mod_forumng_post::OPTION_PRINTABLE_VERSION]) {
                    $message = preg_replace('~<a[^>]*\shref\s*=\s*[\'"](http:.*?)[\'"][^>]*>' .
                    '(?!(http:|www\.)).*?</a>~', "$0 [$1]", $message);
                }
                $out .= $lf . '<div class="forumng-message">' . $message . '</div>';
            } else {
                $out .= $post->get_email_message();
                $out .= "\n\n";
            }

            if ($html) {
                $out .= $lf . '<div class="forumng-postfooter">';
            }

            // Ratings
            $ratings = '';
            $ratingclasses = '';
            if ($options[mod_forumng_post::OPTION_RATINGS_VIEW]) {
                $ratingclasses .= ' forumng-canview';
                if ($post->get_num_ratings() >=
                    $post->get_forum()->get_rating_threshold()) {
                    if ($html) {
                        $ratings .= '<div class="forumng-rating">';
                        $a = new stdClass;
                        $a->avg = '<strong id="rating_for_' . $post->get_id() . '">' .
                            $post->get_average_rating(true) . '</strong>';
                        $a->num = '<span class="forumng-count">' .
                            $post->get_num_ratings() . '</span>';
                        $ratings .= get_string('averagerating', 'forumng', $a);
                        $ratings .= '</div>';
                    } else {
                        $ratings .= strip_tags($post->get_average_rating(true));
                    }
                }
            }
            if ($options[mod_forumng_post::OPTION_RATINGS_EDIT] && $html) {
                $ratingclasses .= ' forumng-canedit';
                $ratings .= '<div class="forumng-editrating">' .
                    get_string('yourrating', 'forumng') . ' ';
                $ratings .= html_writer::select(
                    $post->get_forum()->get_rating_options(),
                    'rating' . $post->get_id(),
                    $post->get_own_rating(),
                    array(mod_forumng_post::NO_RATING => '-'));
                $ratings .= '</div>';
            }
            if ($ratings) {
                $out .= '<div class="forumng-ratings' . $ratingclasses .
                  '">' . $ratings . '</div>';
            }

            // Commands at bottom of mail
            $mobileclass = '';
            if ($html) {
                $commandsarray = array();
                $expires = $post->can_ignore_edit_time_limit() ? '' :
                    '&amp;expires=' . ($post->get_edit_time_limit()-time());
                $expandparam = !empty($options[mod_forumng_post::OPTION_CHILDREN_EXPANDED]) ?
                        '&amp;expand=1' : '';

                // Jump box
                if ($options[mod_forumng_post::OPTION_JUMP_PREVIOUS] ||
                        $options[mod_forumng_post::OPTION_JUMP_NEXT] ||
                        $options[mod_forumng_post::OPTION_JUMP_PARENT]) {

                    $nextid = $options[mod_forumng_post::OPTION_JUMP_NEXT];
                    $pid = $options[mod_forumng_post::OPTION_JUMP_PREVIOUS];
                    $parentid = $options[mod_forumng_post::OPTION_JUMP_PARENT];
                    if ($jumptotext = $this->render_commands_jumpto($nextid, $pid, $parentid)) {
                        $thiscommand = '<span class="forumng-jumpto-label">' .
                                get_string('jumpto', 'forumng') . '</span>' . $jumptotext;
                        $commandsarray['forumng-jumpto'] = $thiscommand;
                    }
                }

                //Direct link
                if ($options[mod_forumng_post::OPTION_COMMAND_DIRECTLINK]) {
                    $commandsarray['forumng-permalink'] = '<a href="discuss.php?' .
                            $discussion->get_link_params(mod_forumng::PARAM_HTML) . '#p' .
                            $post->get_id() . '" title="' .
                            get_string('directlinktitle', 'forumng').'">' .
                            get_string('directlink', 'forumng', $postnumber) . '</a>';
                }

                // Alert link
                if ($options[mod_forumng_post::OPTION_COMMAND_REPORT]) {
                    $commandsarray['forumng-alert'] = '<a href="' . $linkprefix . 'alert.php?' .
                            $post->get_link_params(mod_forumng::PARAM_HTML) .
                            $expandparam . '" title="'.get_string('alert_linktitle', 'forumng').'">' .
                            get_string('alert_link', 'forumng', $postnumber) .
                            '</a>';
                }

                // Split link
                if ($options[mod_forumng_post::OPTION_COMMAND_SPLIT]) {
                    $commandsarray['forumng-split'] = '<a href="' . $linkprefix .
                            'splitpost.php?' .
                            $post->get_link_params(mod_forumng::PARAM_HTML) .
                            $expandparam . '">' .
                            get_string('split', 'forumng', $postnumber) .
                            '</a>';
                }

                // Delete link
                if ($options[mod_forumng_post::OPTION_COMMAND_DELETE]) {
                    $commandsarray ['forumng-delete'] = '<a' . $mobileclass . ' href="' . $linkprefix .
                            'deletepost.php?' .
                            $post->get_link_params(mod_forumng::PARAM_HTML, true) .
                            $expandparam . $expires . '">' .
                            get_string('delete', 'forumng', $postnumber) .
                            '</a>';
                }

                // Undelete link
                if ($options[mod_forumng_post::OPTION_COMMAND_UNDELETE]) {
                    $commandsarray['forumng-undelete'] = '<a href="' . $linkprefix .
                            'deletepost.php?' .
                            $post->get_link_params(mod_forumng::PARAM_HTML) .
                            $expandparam . '&amp;delete=0">' .
                            get_string('undelete', 'forumng', $postnumber) .
                            '</a>';
                }

                // Edit link
                if ($options[mod_forumng_post::OPTION_COMMAND_EDIT]) {
                    $commandsarray['forumng-edit'] = '<a' . $mobileclass . ' href="' . $linkprefix .
                            'editpost.php?' .
                            $post->get_link_params(mod_forumng::PARAM_HTML) .
                            $expandparam . $expires. '">' .
                            get_string('edit', 'forumng', $postnumber) .
                            '</a>';
                }

                // Reply link
                if ($options[mod_forumng_post::OPTION_COMMAND_REPLY]) {
                    $commandsarray['forumng-replylink'] = '<a' . $mobileclass . ' href="' .
                            $linkprefix . 'editpost.php?replyto=' . $post->get_id() .
                            $post->get_forum()->get_clone_param(mod_forumng::PARAM_HTML) .
                            $expandparam . '">' .
                            get_string('reply', 'forumng', $postnumber) . '</a>';
                }

                if (count($commandsarray)) {
                    $out .= $lf . $this->render_commands($commandsarray);
                }
            } else {
                // Reply link
                if ($options[mod_forumng_post::OPTION_COMMAND_REPLY]) {
                    $out .= mod_forumng_cron::EMAIL_DIVIDER;
                    if ($options[mod_forumng_post::OPTION_EMAIL]) {
                        $course = $post->get_forum()->get_course();
                        $out .= get_string("postmailinfo", "forumng",
                            $course->shortname) . $lf;
                    }
                    $out .= "{$linkprefix}editpost.php?replyto=" .
                            $post->get_id() .
                            $post->get_forum()->get_clone_param(mod_forumng::PARAM_PLAIN) .
                            $lf;
                }

                // Only the reply command is available in text mode
            }

            // End: forumng-postfooter and forumng-postmain.
            if ($html) {
                $out .= html_writer::end_tag('div') . html_writer::end_tag('div');
            }
        }

        // End of post div
        if ($html) {
            // Useful empty div at end of post.
            $out .= html_writer::tag('div', '', array('class' => 'forumng-endpost'));

            // End: forumng-post-outerbox.
            $out .= html_writer::end_tag('div');

            // Export has a couple blank lines after post (but within div, for validity).
            if ($export) {
                $out .= '<br /><br />';
            }

            // End: forumng-post.
            $out .= html_writer::end_tag('div');
        }

        return $out;
    }

    /**
     * Renders the jumpto buttons.
     * @param int $nextid id of the next unread post
     * @param int $pid id of the previous unread post
     * @param int $parentid id of the parent post
     * @return string HTML code for the jumpto buttons
     */
    public function render_commands_jumpto($nextid, $pid, $parentid) {
        $output = '';
        if ($nextid) {
            $output .= ' <a href="#p'. $nextid . '" class="forumng-next">' .
                    get_string('jumpnext', 'forumng') . '</a>';
        }
        if ($pid) {
            if ($nextid) {
                $output .= ' (<a href="#p'. $pid . '" class="forumng-prev">' .
                        get_string('jumppreviousboth', 'forumng') . '</a>)';
            } else {
                $output .= ' <a href="#p'. $pid . '" class="forumng-prev">' .
                        get_string('jumpprevious', 'forumng') . '</a>';
            }
        }
        if ($parentid) {
            $output .= ' <a href="#p'. $parentid . '" class="forumng-parent">' .
                    get_string('jumpparent', 'forumng') . '</a>';
        }
        return $output;
    }

    /**
     * Renders array of commands that go at the bottom of each message.
     * @param array $commandsarray Array of HTML strings
     * @return string HTML code for the commands buttons
     */
    public function render_commands($commandsarray) {
        $out = '<ul class="forumng-commands">';
        foreach ($commandsarray as $class => $html) {
            $out .= '<li class="' . $class . '">' . $html . '</li>';
        }
        $out .= '</ul>';
        return $out;
    }

    /**
     * Renders the expand link for each post.
     * @param string $linkprefix prefix of the expand link url
     * @param mod_forumng_discussion $discussion object
     * @param mod_forumng_post $post object
     * @return string HTML code for the expand link
     */
    public function render_expand_link($linkprefix, $discussion, $post) {
        $out = '&nbsp;<span class="forumng-expandcontainer">[<a class="forumng-expandlink" ' .
                'href="' . $linkprefix . 'discuss.php?' .
                $discussion->get_link_params(mod_forumng::PARAM_HTML) .
                '&amp;expand=1#p' .
                $post->get_id() . '"><span class="forumng-expandtext">' . get_string('expandall', 'forumng') .
                '</span></a>] <img src="' . $this->pix_url('spacer') .
                '" width="16" height="16" alt="" /></span>';
        return $out;
    }

    private static function nice_shorten_text($text, $length=40) {
        $text = trim($text);
        $summary = shorten_text($text, $length);
        $summary = preg_replace('~\s*\.\.\.(<[^>]*>)*$~', '$1', $summary);
        $dots = $summary != $text ? '...' : '';
        return $summary. $dots;
    }

    /**
     * Called when displaying a group of posts together on one page.
     * @param mod_forumng_discussion $discussion Forum object
     * @param string $html HTML that has already been created for the group
     *   of posts
     * @return string Modified (if necessary) HTML
     */
    public function render_post_group($discussion, $html) {
        // Add rating form if there are any rating selects
        $hasratings = strpos($html, '<div class="forumng-editrating">') !== false;
        $hasflags = strpos($html, '<div class="forumng-flag">') !== false;
        if ($hasflags || $hasratings) {
            $script = '<script type="text/javascript">' .
                'document.getElementById("forumng-actionform").autocomplete=false;' .
                '</script>';
            $html = '<form method="post" id="forumng-actionform" ' .
                'action="action.php"><div>' . $script . $html .
                $discussion->get_link_params(mod_forumng::PARAM_FORM);
            if ($hasratings) {
                $html .= '<input type="submit" id="forumng-saveallratings" value="' .
                    get_string('saveallratings', 'forumng') . '" name="action.rate"/>';
            }
            $html .=  '</div></form>';
        }
        return $html;
    }

    /**
     * Displays the reply/edit form on a discussion page. Usually this form is
     * hidden by CSS and only displayed when JavaScript activates it.
     * @param mod_forumng $forum
     * @return string HTMl for form
     */
    public function render_ajax_forms($forum) {
        global $CFG;
        if (!ajaxenabled()) {
            return '';
        }

        require_once($CFG->dirroot . '/mod/forumng/editpost_form.php');
        // Reply form
        $mform = new mod_forumng_editpost_form('editpost.php',
            array('params'=>array(), 'isdiscussion'=>false, 'ispost'=>true,
                'islock'=>false, 'forum'=>$forum, 'edit'=>false, 'post'=>null,
                'ajaxversion'=>1, 'isroot'=>false));
        $result = $mform->get_html();
        // Edit form
        $mform = new mod_forumng_editpost_form('editpost.php',
            array('params'=>array(), 'isdiscussion'=>false, 'ispost'=>true,
                'islock'=>false, 'forum'=>$forum, 'edit'=>true, 'post'=>null,
                'ajaxversion'=>2, 'isroot'=>false));
        $result .= $mform->get_html();
        // Edit form (discussion)
        $mform = new mod_forumng_editpost_form('editpost.php',
            array('params'=>array(), 'isdiscussion'=>false, 'ispost'=>true,
                'islock'=>false, 'forum'=>$forum, 'edit'=>true, 'post'=>null,
                'ajaxversion'=>3, 'isroot'=>true));
        $result .= $mform->get_html();

        return '<div id="forumng-formhome">' . $result . '</div>';
    }

    /**
     * Returns the full img tag for the sort arrow gif.
     * @return string
     */
    private function get_sort_arrow($sort, $sortreverse=false) {
        $letter = mod_forumng::get_sort_letter($sort);
        $up = 'sortorder-up';
        $down = 'sortorder-down';
        switch ($letter) {
            case 'd' :
                $imgname = ($sortreverse) ? $up : $down;
                break;
            case 's' :
                $imgname = ($sortreverse) ? $down : $up;
                break;
            case 'a' :
                $imgname = ($sortreverse) ? $down : $up;
                break;
            case 'p' :
                $imgname = ($sortreverse) ? $up : $down;
                break;
            case 'u' :
                $imgname = ($sortreverse) ? $up : $down;
                break;
            case 'g' :
                $imgname = ($sortreverse) ? $down : $up;
                break;
            default:
                throw new coding_exception("Unknown sort letter: $letter");
        }
        $imgtag = '<span class="forumng-sortcurrent">' . $this->pix_icon($imgname,
                get_string('sorted', 'forumng', $this->get_sort_order_text($sort, $sortreverse)),
                'forumng') . '</span>';
        return $imgtag;
    }

    /**
     * Returns the apropriate language string text for the current sort.
     * e.g. a-Z or Z-a for text columns, recent first or oldest first for date columns and
     * highest first or lowest first for numeric columns.
     * @param string $sort Sort parameter
     * @param bool $sortreverse True if sort is reversed
     * @return string
     */
    private function get_sort_order_text($sort, $sortreverse=false) {
        $letter = mod_forumng::get_sort_letter($sort);
        switch ($letter) {
            case 'd' :
                return (!$sortreverse) ? get_string('date_desc', 'forumng')
                        : get_string('date_asc', 'forumng');
            case 's' :
                return (!$sortreverse) ? get_string('text_asc', 'forumng')
                        : get_string('text_desc', 'forumng');
            case 'a' :
                return (!$sortreverse) ? get_string('text_asc', 'forumng')
                        : get_string('text_desc', 'forumng');
            case 'p' :
                return (!$sortreverse) ? get_string('numeric_desc', 'forumng')
                        : get_string('numeric_asc', 'forumng');
            case 'u' :
                return (!$sortreverse) ? get_string('numeric_desc', 'forumng')
                        : get_string('numeric_asc', 'forumng');
            case 'g' :
                return (!$sortreverse) ? get_string('text_asc', 'forumng')
                        : get_string('text_desc', 'forumng');
            default:
                throw new coding_exception("Unknown sort letter: $letter");
        }
    }

    public function render_unread_skip_link() {
        $out = '<div id="forumng-unread-skip"><a href="#firstunread" class="skip">';
        $out .= get_string('skiptofirstunread', 'forumng');
        $out .= '</a></div>';
        return $out;
    }

    public function render_feed_links($atomurl, $rssurl) {
        // Icon (decoration only) and Atom/RSS links
        $strrss = get_string('rss');
        $stratom = get_string('atom', 'forumng');
        $feed = '<div class="forumng-feedlinks">';
        $feed .= '<a class="forumng-iconlink" href="'. htmlspecialchars($atomurl) . '">';
        $feed .= "<img src='" . $this->pix_url('i/rss') . "' alt=''/> " .
            '<span class="forumng-textbyicon">' . $stratom . '</span></a> ';
        $feed .= '<a href="'. htmlspecialchars($rssurl) . '">' . $strrss . '</a> ';
        $feed .= '</div>';
        return $feed;
    }

    /**
     * Returns html for forumng search entry form for the header
     * @param string $querytext user query
     * @param string $linkfields passing through forum link parameters for form submission
     * @param string $help help string if needed
     * @return string
     */
    public function render_search_form($querytext, $linkfields, $help='') {
        $strsearchthisactivity = get_string('searchthisforum', 'forumng');
        $queryhtml = htmlspecialchars($querytext);
        $out = html_writer::start_tag('form', array('action' => 'search.php', 'method' => 'get'));
        $out .= html_writer::start_tag('div');
        $out .= $linkfields;
        $out .= html_writer::tag('label', $strsearchthisactivity,
                array('for' => 'forumng_searchquery'));
        $out .= $help;
        $out .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'query',
                'id' => 'forumng_searchquery', 'value' => $queryhtml));
        $out .= html_writer::empty_tag('input', array('type' => 'submit',
                'id' => 'ousearch_searchbutton', 'value' => '', 'alt' => get_string('search'),
                'title' => get_string('search')));
        $out .= html_writer::end_tag('div');
        $out .= html_writer::end_tag('form');
        return $out;
    }

    /**
     * Print a message along with three buttons buttoneone/buttontwo/Cancel
     *
     * If a string or moodle_url is given instead of a single_button, method defaults to post.
     *
     * @param string $message The question to ask the user.
     * @param single_button $buttonone The single_button component representing the buttontwo response.
     * @param single_button $buttontwo The single_button component representing the buttontwo response.
     * @param single_button $cancel The single_button component representing the Cancel response.
     * @return string HTML fragment
     */
    public function confirm_three_button($message, $buttonone, $buttontwo, $cancel) {
        if (!($buttonone instanceof single_button)) {
            throw new coding_exception('The buttonone param must be an instance of a single_button.');
        }

        if (!($buttontwo instanceof single_button)) {
            throw new coding_exception('The buttontwo param must be an instance of a single_button.');
        }

        if (!($cancel instanceof single_button)) {
            throw new coding_exception('The cancel param must be an instance of a single_button.');
        }

        $output = $this->box_start('generalbox', 'notice');
        $output .= html_writer::tag('p', $message);
        $buttons = $this->render($buttonone) . $this->render($buttontwo) . $this->render($cancel);
        $output .= html_writer::tag('div', $buttons, array('class' => 'buttons'));
        $output .= $this->box_end();
        return $output;
    }

    /**
     * Compiles the html message content for the rejection email.
     *
     * @param object $group The details of one group
     * @param string $coursename
     * @return string HTML
     */
    public function deletion_email($messagetext) {
        $out = '';
        $out .= html_writer::start_tag('html');
        $out .= html_writer::start_tag('body');
        $out .= $messagetext;
        $out .= html_writer::end_tag('body');
        $out .= html_writer::end_tag('html');

        return $out;
    }

    /**
     * Compiles the html message content for the rejection email.
     *
     * @param object $group The details of one group
     * @param string $coursename
     * @return string HTML
     */
    public function delete_form_html($messagehtml) {
        return html_writer::tag('div', htmlentities($messagehtml, ENT_QUOTES),
                array('id' => 'delete-form-html'));
    }
}
