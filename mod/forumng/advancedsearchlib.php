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

/**
 * Advanced Search library functions.
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('FORUMNG_SEARCH_RESULTSPERPAGE', 10); // Number of results to display per page

/**
 * Filter search result.
 * @param object $result
 * @return boolean
 */
function forumng_exclude_words_filter($result) {
    $author = trim(optional_param('author', null, PARAM_RAW));
    $drfa = optional_param_array('datefrom', 0, PARAM_INT);
    $drta = optional_param_array('dateto', 0, PARAM_INT);

    // Filter the output based on the input string for "Author name" field
    if (!forumng_find_this_user($result->intref1, $author)) {
        return false;
    }

    // Filter the output based on input date for "Date range from" field
    if (count($drfa) > 1 ) {
        $daterangefrom = make_timestamp($drfa['year'], $drfa['month'], $drfa['day'],
                                    $drfa['hour'], $drfa['minute'], 0);
        if ($daterangefrom && $daterangefrom > $result->timemodified) {
            return false;
        }
    }

    // Filter the output based on input date for "Date range to" field
    if (count($drta) > 1 ) {
        $daterangeto = make_timestamp($drta['year'], $drta['month'], $drta['day'],
                                    $drta['hour'], $drta['minute'], 0);
        if ($daterangeto && $daterangeto < $result->timemodified) {
            return false;
        }
    }
    return true;
}

/**
 * Get search results.
 * @param object $forum
 * @param int $groupid
 * @param string $author
 * @param int $daterangefrom
 * @param int $daterangeto
 * @param int $page
 * @param int $resultsperpage (FORUMNG_SEARCH_RESULTSPERPAGE used as constant)
 * @return object
 */
function forumng_get_results_for_this_forum($forum, $groupid, $author=null, $daterangefrom=0,
        $daterangeto=0, $page, $resultsperpage=FORUMNG_SEARCH_RESULTSPERPAGE) {

    $before = microtime(true);

    global $CFG, $DB, $USER;
    $forumngid = $forum->get_id();
    $context = $forum->get_context();
    $params = array();

    $where = "WHERE d.forumngid = ?";
    $params[] = $forumngid;

    //exclude deleted discussion/post
    $where .= " AND d.deleted = 0 AND p.deleted = 0 AND p.oldversion = 0 ";

    if ($author) {
        list($morewhere, $moreparams) = forumng_get_author_sql($author);
        $where .= $morewhere;
        $params = array_merge($params, $moreparams);
    }
    if ($daterangefrom && !is_array($daterangefrom)) {
        $where .= " AND p.modified>=?";
        $params[] = $daterangefrom;
    }
    if ($daterangeto && !is_array($daterangeto)) {
        $where .= " AND p.modified<=?";
        $params[] = $daterangeto;
    }

    $sql = "SELECT p.modified, p.id, p.discussionid, p.userid, p.parentpostid,
            p.subject AS title, p.message AS summary, u.username, u.firstname,
            u.lastname, p2.subject
            FROM {forumng_posts} p
            INNER JOIN {forumng_discussions} d ON d.id = p.discussionid
            INNER JOIN {user} u ON p.userid = u.id
            INNER JOIN {forumng_posts} p2 ON p2.id = d.postid
            $where
            ORDER BY p.modified DESC, p.id ASC";

    $results = new stdClass;
    $results->success = 1;
    $results->numberofentries = 0;
    $results->done = 0;
    $posts = $DB->get_records_sql($sql, $params, $page, $resultsperpage);
    $groupposts = array();
    foreach ($posts as $post) {
        if (!$post->title) {
            //Add Re: if the post doesn't have a subject
            $post->title = get_string('re', 'forumng', $post->subject);
        }
        $post->title = s(strip_tags($post->title));
        $post->summary = s(strip_tags(shorten_text($post->summary, 250)));
        $post->url = $CFG->wwwroot ."/mod/forumng/discuss.php?d=$post->discussionid" .
                $forum->get_clone_param(mod_forumng::PARAM_PLAIN) . "#p$post->id";

        // Check group
        if ($groupid && $groupid!=mod_forumng::NO_GROUPS) {
            if (groups_is_member($groupid, $post->userid)) {
                $groupposts[] = $post;
            }
        }
    }
    $results->results = $groupposts ? $groupposts : $posts;
    $results->searchtime = microtime(true) - $before;
    $results->numberofentries = count($results->results);

    if (count($results->results) < $resultsperpage) {
        $results->done = 1;
    } else if (!$extrapost = $DB->get_records_sql($sql, $params, $page+$resultsperpage, 1)) {
        $results->done = 1;
    }
    return $results;
}

/**
 * Get search results.
 * @param object $course
 * @param string $author
 * @param int $daterangefrom
 * @param int $daterangeto
 * @param int $page
 * @param int $resultsperpage (FORUMNG_SEARCH_RESULTSPERPAGE used as constant)
 * @return object
 */
function forumng_get_results_for_all_forums($course, $author=null, $daterangefrom=0,
        $daterangeto=0, $page, $resultsperpage=FORUMNG_SEARCH_RESULTSPERPAGE) {

    $before = microtime(true);

    global $CFG, $DB, $USER;

    // Get all forums
    $modinfo = get_fast_modinfo($course);
    $visibleforums = array();
    $accessallgroups = array();
    foreach ($modinfo->cms as $cmid => $cm) {
        if ($cm->modname === 'forumng' && $cm->uservisible) {
            $visibleforums[$cm->instance] = $cm->groupmode;

            // Check access all groups for this forum, if they have it, add to list
            //$forum = mod_forumng::get_from_cmid($cm->id, 0);
            $forum = mod_forumng::get_from_id($cm->instance, mod_forumng::CLONE_DIRECT);
            if ($forum->get_group_mode() == SEPARATEGROUPS) {
                if (has_capability('moodle/site:accessallgroups', $forum->get_context())) {
                    $accessallgroups[] = $cm->instance;
                }
            }
        }
    }
    $forumngids = array_keys($visibleforums);
    $separategroupsforumngids = array_keys($visibleforums, SEPARATEGROUPS);

    $params = array();

    list ($inforumngids, $moreparams) = mod_forumng_utils::get_in_array_sql(
            'd.forumngid' , $forumngids);
    $where = "WHERE $inforumngids";
    $params = array_merge($params, $moreparams);

    list ($inseparategroups, $moreparams) = mod_forumng_utils::get_in_array_sql(
            'd.forumngid', $separategroupsforumngids);
    $where .= " AND ((NOT ($inseparategroups))";
    $params = array_merge($params, $moreparams);

    list ($inaccessallgroups, $moreparams) = mod_forumng_utils::get_in_array_sql(
            'd.forumngid', $accessallgroups);
    $where .= " OR $inaccessallgroups";
    $params = array_merge($params, $moreparams);

    $where .= " OR gm.id IS NOT NULL";
    $where .= " OR d.groupid IS NULL)";

    // Note: Even if you have capability to view the deleted or timed posts,
    // we don't show them for consistency with the full-text search.
    $currenttime = time();
    $where .= " AND (? >= d.timestart OR d.timestart = 0)";
    $params[] = $currenttime;
    $where .= " AND (? < d.timeend OR d.timeend = 0)";
    $params[] = $currenttime;

    //exclude older post versions
    $where .= " AND p.oldversion = 0 ";
    $where .= " AND d.deleted = 0 AND p.deleted = 0 ";

    if ($author) {
        list($morewhere, $moreparams) = forumng_get_author_sql($author);
        $where .= $morewhere;
        $params = array_merge($params, $moreparams);
    }
    if ($daterangefrom && !is_array($daterangefrom)) {
        $where .= " AND p.modified>=?";
        $params[] = $daterangefrom;
    }
    if ($daterangeto && !is_array($daterangeto)) {
        $where .= " AND p.modified<=?";
        $params[] = $daterangeto;
    }

    $sql = "SELECT p.modified, p.id, p.discussionid, gm.id AS useringroup,
            p.userid, p.parentpostid, p.subject AS title, p.message AS summary, u.username,
            u.firstname, u.lastname, d.forumngid, d.groupid, p2.subject AS discussionsubject
            FROM {forumng_posts} p
            INNER JOIN {forumng_discussions} d ON d.id = p.discussionid
            INNER JOIN {forumng_posts} p2 ON p2.id = d.postid
            INNER JOIN {user} u ON p.userid = u.id
            LEFT JOIN {groups_members} gm ON gm.groupid = d.groupid AND gm.userid = $USER->id
            $where
            ORDER BY p.modified DESC, p.id ASC";

    $results = new stdClass;
    $results->success = 1;
    $results->numberofentries = 0;
    $results->done = 0;
    $posts = $DB->get_records_sql($sql, $params, $page, $resultsperpage);
    foreach ($posts as $post) {
        if (!$post->title) {
            // Ideally we would get the parent post that has a subject, but
            // this could involve a while loop that might make numeroous
            // queries, so instead, let's just use the discussion subject
            $post->title = get_string('re', 'forumng', $post->discussionsubject);
        }
        $post->title = s(strip_tags($post->title));
        $post->summary = s(strip_tags(shorten_text($post->summary, 250)));
        $post->url = $CFG->wwwroot . "/mod/forumng/discuss.php?d=$post->discussionid" .
                $forum->get_clone_param(mod_forumng::PARAM_PLAIN) . "#p$post->id";
    }
    $results->results = $posts;
    $results->searchtime = microtime(true) - $before;
    $results->numberofentries = count($results->results);
    if (count($results->results) < $resultsperpage) {
        $results->done = 1;
    } else if (!$extrapost = $DB->get_records_sql($sql, $params, $page+$resultsperpage, 1)) {
        $results->done = 1;
    }
    return $results;
}

/**
 * Find this usr.
 * @param int $groupid
 * @param string $author
 * @return boolean
 */
function forumng_find_this_user($postid, $author=null) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/dmllib.php');
    if (!$author) {
        return true;
    }
    $where = "WHERE p.id = ? ";
    $params = array($postid);
    list($morewhere, $moreparams) = forumng_get_author_sql($author);
    $where .= $morewhere;
    $params = array_merge($params, $moreparams);
    $sql = "SELECT p.id, u.username, u.firstname, u.lastname
            FROM {forumng_posts} p
            INNER JOIN {user} u ON p.userid = u.id
            $where";
    return $DB->record_exists_sql($sql, $params);
}

/**
 * Get author sql
 * @param string $author
 * @param string $t
 * @return array with two elements containing the where sql string and the params array
 */
function forumng_get_author_sql($author, $t='u') {
    $where = " AND ";
    $params = array();
    $author = trim($author);
    $pos = strpos($author, ' ');
    if ($pos) {
        $fname = trim(substr($author, 0, $pos));
        $lname = trim(substr($author, ($pos+1)));
        // Searching for complete first name and last name fully or partially ignoring case.
        // Finds "Mahmoud Kassaei" by typing "Mahmoud k", "Mahmoud kas", "Mahmoud Kassaei", etc.
        $where .= " (UPPER($t.firstname) LIKE UPPER(?) AND UPPER($t.lastname) LIKE UPPER(?))";
        $params[] = $fname;
        $params[] = $lname;
    } else {
        // Searching for user name fully ignoring case
        // Finds "mk4359",  "Mk4359""MK4359", etc.
        $where .= "((UPPER($t.username)=UPPER(?)) ";
        $params[] = $author;

        //search for first name only
        // Finds "Mah",  "Mahmo", "mahmoud", etc.
        $where .= " OR (UPPER($t.firstname) LIKE UPPER(?)) ";
        $params[] = $author . '%';

        //search for surname only
        // Finds "Kass",  "kassa", "Kassaei", etc.
        $where .= " OR (UPPER($t.lastname) LIKE UPPER(?))) ";
        $params[] = $author . '%';
    }
    return array($where, $params);
}

/**
 * Get search results title
 * @param string $query
 * @param string $author
 * @param int $daterangefrom
 * @param int $daterangeto
 * @return string
 */
function forumng_get_search_results_title($query='', $author='',
        $daterangefrom=0, $daterangeto=0) {
    // Set the search results title
    if ($query) {
        if (!($author || $daterangefrom || $daterangeto)) {
            return get_string('searchresultsfor', 'local_ousearch', $query);
        }
    }
    $searchoptions = $query ? $query . ' (' : ' (';
    $searchoptions .= $author ? get_string('author', 'forumng', $author): '';
    $searchoptions .= ($author && ($daterangefrom || $daterangeto)) ? ', ' : '';
    $searchoptions .= $daterangefrom ? get_string('from', 'forumng',
            userdate($daterangefrom)) : '';
    $searchoptions .= ($daterangefrom && $daterangeto) ? ', ' : '';
    $searchoptions .= $daterangeto ? get_string('to', 'forumng', userdate($daterangeto)) : '';
    $searchoptions .= ' )';
    if ($query) {
        return get_string('searchresultsfor', 'local_ousearch', $searchoptions);
    }
    return get_string('searchresults', 'forumng', $searchoptions);
}
