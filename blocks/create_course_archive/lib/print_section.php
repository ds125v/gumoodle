<?php

function print_section_archive($course, $section, $mods, $modnamesused, $modinfo=null, $absolute=false, $width="100%") {
    /// Prints a section full of activity modules
    global $CFG, $USER, $ARCHIVE;
    static $initialised;
    static $groupbuttons;
    static $groupbuttonslink;
    static $isediting;
    static $ismoving;
    static $strmovehere;
    static $strmovefull;
    static $strunreadpostsone;
    static $usetracking;
    static $groupings;
    if (!isset($initialised)) {
        $groupbuttons = ($course->groupmode or (!$course->groupmodeforce));
        $groupbuttonslink = (!$course->groupmodeforce);
        $isediting = isediting($course->id);
        $ismoving = $isediting && ismoving($course->id);
        if ($ismoving) {
            $strmovehere = get_string("movehere");
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }
        include_once($CFG->dirroot.'/mod/forum/lib.php');
        if ($usetracking = forum_tp_can_track_forums()) {
            $strunreadpostsone = get_string('unreadpostsone', 'forum');
        }
        $initialised = true;
    }
    $labelformatoptions = new object();
    $labelformatoptions->noclean = true;
    /// Casting $course->modinfo to string prevents one notice when the field is null
    if($modinfo==null) {
        $modinfo = get_fast_modinfo($course);
    }
    //Acccessibility: replace table with list <ul>, but don't output empty list.
    if (!empty($section->sequence)) {
        // Fix bug #5027, don't want style=\"width:$width\".
        echo "<ul class=\"section img-text\">\n";
        $sectionmods = explode(",", $section->sequence);
        foreach ($sectionmods as $modnumber) {
            if (empty($mods[$modnumber])) {
                continue;
            }
            $mod = $mods[$modnumber];
            if ($ismoving and $mod->id == $USER->activitycopy) {
                // do not display moving mod
                continue;
            }
            if (isset($modinfo->cms[$modnumber])) {
                if (!$modinfo->cms[$modnumber]->uservisible) {
                    // visibility shortcut
                    continue;
                }
            } else {
                if (!file_exists("$CFG->dirroot/mod/$mod->modname/lib.php")) {
                    // module not installed
                    continue;
                }
                if (!coursemodule_visible_for_user($mod)) {
                    // full visibility check
                    continue;
                }
            }
            if((function_exists('archive_'.$mod->modname)) && ((!function_exists('can_archive_'.$mod->modname))|| (call_user_func('can_archive_'.$mod->modname, $mod)))) {
                $canarchive = true;
            } else $canarchive = false;
            if($canarchive || !$ARCHIVE->hideUnarchivedMods) {
                echo '<li class="activity '.$mod->modname.'" id="module-'.$modnumber.'">'; // Unique ID
                if ($mod->indent) {
                    print_spacer(12, 20 * $mod->indent, false);
                }
                $extra = $modinfo->cms[$modnumber]->extra;
                if ($mod->modname == "label") {
                    if (!$mod->visible) {
                        echo "<span class=\"dimmed_text\">";
                    }
                    echo format_text($extra, FORMAT_HTML, $labelformatoptions);
                    if (!$mod->visible) {
                        echo "</span>";
                    }
                    if (!empty($CFG->enablegroupings) && !empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
                        if (!isset($groupings)) {
                            $groupings = groups_get_all_groupings($course->id);
                        }
                        echo " <span class=\"groupinglabel\">(".format_string($groupings[$mod->groupingid]->name).')</span>';
                    }
                } else { // Normal activity
                    $instancename = format_string($modinfo->cms[$modnumber]->name, true, $course->id);
                    if (!empty($modinfo->cms[$modnumber]->icon)) {
                        $icon = "$CFG->pixpath/".$modinfo->cms[$modnumber]->icon;
                    } else {
                        $icon = "$CFG->modpixpath/$mod->modname/icon.gif";
                    }
                    //Accessibility: for files get description via icon.
                    $altname = '';
                    if ('resource'==$mod->modname) {
                        if (!empty($modinfo->cms[$modnumber]->icon)) {
                            $possaltname = $modinfo->cms[$modnumber]->icon;
                            $mimetype = mimeinfo_from_icon('type', $possaltname);
                            $altname = get_mimetype_description($mimetype);
                        } else {
                            $altname = $mod->modfullname;
                        }
                    } else {
                        $altname = $mod->modfullname;
                    }
                    // Avoid unnecessary duplication.
                    if (false!==stripos($instancename, $altname)) {
                        $altname = '';
                    }
                    // File type after name, for alphabetic lists (screen reader).
                    if ($altname) {
                        $altname = get_accesshide(' '.$altname);
                    }
                    //NSFB - building a list of mods for archiving
                    //# Will calculate the relative URL here, and use it in next bit (output)
                    //# Should also check if archiving code exists, and if not make it greyed with no URL.
                    if($mod->visible) {
                        global $ARCHIVE;
                        if(function_exists('archive_url_'.$mod->modname)) {
                            $url = call_user_func('archive_url_'.$mod->modname, $course, $mod);
                        } else {
                            $url = 'mod/'.$mod->modname.'/'.$mod->id.'/view.html';
                        }
                        $ARCHIVE->mods[] = array('mod' => $mod, 'url' => $url);
                    }
                    $linkcss = $mod->visible ? "" : " class=\"dimmed\" ";
                    // Only display the link if an archiving function has been defined
                    if($canarchive) {
                        echo '<a '.$extra. // Title unnecessary!
                        ' href="'.$url.'">'. '<img src="'.$icon.'" class="activityicon" alt="" /> <span>'. $instancename.$altname.'</span></a>';
                    } else {
                        echo '<!--<a class="dimmed" href="#">--><img src="'.$icon.'" class="activityicon" alt="" /> <span>'. $instancename.$altname.' ('.$mod->modname.' not archived)</span><!--</a>-->';
                    }
                    //End of NSFB changes (for now)
                    if (!empty($CFG->enablegroupings) && !empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
                        if (!isset($groupings)) {
                            $groupings = groups_get_all_groupings($course->id);
                        }
                        echo " <span class=\"groupinglabel\">(".format_string($groupings[$mod->groupingid]->name).')</span>';
                    }
                }
                echo "</li>\n";
            }
        }
    }
    if (!empty($section->sequence)) {
        echo "</ul><!--class='section'-->\n\n";
    }
}
?>

