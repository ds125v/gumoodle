<?php // $Id: block_side_bar.php,v 1.11 2009/05/07 05:52:32 fmarier Exp $

/**
 * Allows for arbitrarily adding resources or activities to extra (non-standard
 * course sections with instance configuration for the block title.
 * 
 * (Code modified from site_main_menu block).
 *
 * @author Open Knowledge Technologies
 * @author Justin Filip <jfilip@oktech.ca>
 */

class block_side_bar extends block_list {

    function init() {
        global $CFG;

        $this->title = get_string('sidebar', 'block_side_bar');
        $this->version = 2008050200;

    /// Make sure the global section start value is set.
        if (!isset($CFG->block_side_bar_section_start)) {
            set_config('block_side_bar_section_start', 1000);
        }
    }

    function get_content() {
        global $USER, $CFG;
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        if (!isset($this->config->title)) {
            $this->config->title = '';
        }

        $course = get_record('course', 'id', $this->instance->pageid);
        
        $isteacher = isteacher($this->instance->pageid);
        $isediting = isediting($this->instance->pageid);
        $ismoving  = ismoving($this->instance->pageid);
        
        $section_start = $CFG->block_side_bar_section_start;

    /// Create a new section for this block (if necessary).
        if (empty($this->config->section)) {
            $sql = "SELECT MAX(section) as sectionid
                    FROM {$CFG->prefix}course_sections
                    WHERE course='{$this->instance->pageid}'";
            $rec = get_record_sql($sql);
            
            $sectionnum = $rec->sectionid;

            if ($sectionnum < $section_start) {
                $sectionnum = $section_start;
            } else {
                $sectionnum++;
            }
            
            $section = new stdClass;
            $section->course   = $this->instance->pageid;
            $section->section  = $sectionnum;
            $section->summary  = '';
            $section->sequence = '';
            $section->visible  = 1;
            $section->id = insert_record('course_sections', $section);
            
            if (empty($section->id)) {
                error('Could not add new section to course.');
            }
            
            
            
        /// Store the section number and ID of the DB record for that section.
            $this->config->section    = $section->section;
            $this->config->section_id = $section->id;
            parent::instance_config_commit();
        } else {

            if (empty($this->config->section_id)) {
                $section = get_record('course_sections', 'course', $this->instance->pageid,
                                      'section', $this->config->section);

                $this->config->section_id = $section->id;
                parent::instance_config_commit();
            } else {
                $section = get_record('course_sections', 'id', $this->config->section_id);
            }
            
        /// Double check that the section number hasn't been modified by something else.
        /// Fixes problem found by Charlotte Owen when moving 'center column' course sections.
            if ($section->section != $this->config->section) {
                $section->section = $this->config->section;
                
                update_record('course_sections', $section);
            }
        }

        if (!empty($section) || $isediting) {
            get_all_mods($this->instance->pageid, $mods, $modnames, $modnamesplural, $modnamesused);
        }

        $groupbuttons = $course->groupmode;
        $groupbuttonslink = (!$course->groupmodeforce);

        if ($ismoving) {
            $strmovehere = get_string('movehere');
            $strmovefull = strip_tags(get_string('movefull', '', "'$USER->activitycopyname'"));
            $strcancel= get_string('cancel');
            $stractivityclipboard = $USER->activitycopyname;
        }

        $modinfo = unserialize($course->modinfo);
        $editbuttons = '';

        if ($ismoving) {
            $this->content->icons[] = '&nbsp;<img align="bottom" src="'.$CFG->pixpath.'/t/move.gif" height="11" width="11" alt="" />';
            $this->content->items[] = $USER->activitycopyname.'&nbsp;(<a href="'.$CFG->wwwroot.'/course/mod.php?cancelcopy=true&amp;sesskey='.$USER->sesskey.'">'.$strcancel.'</a>)';
        }

        if (!empty($section) && !empty($section->sequence)) {
            $sectionmods = explode(',', $section->sequence);
            foreach ($sectionmods as $modnumber) {
                if (empty($mods[$modnumber])) {
                    continue;
                }
                $mod = $mods[$modnumber];
                if ($isediting && !$ismoving) {
                    if ($groupbuttons) {
                        if (! $mod->groupmodelink = $groupbuttonslink) {
                            $mod->groupmode = $course->groupmode;
                        }

                    } else {
                        $mod->groupmode = false;
                    }
                    $editbuttons = '<br />'.make_editing_buttons($mod, true, true);
                } else {
                    $editbuttons = '';
                }
                if ($mod->visible || $isteacher) {
                    if ($ismoving) {
                        if ($mod->id == $USER->activitycopy) {
                            continue;
                        }
                        $this->content->items[] = '<a title="'.$strmovefull.'" href="'.$CFG->wwwroot.'/course/mod.php?moveto='.$mod->id.'&amp;sesskey='.$USER->sesskey.'">'.
                            '<img height="16" width="80" src="'.$CFG->pixpath.'/movehere.gif" alt="'.$strmovehere.'" border="0" /></a>';
                        $this->content->icons[] = '';
                   }
                    $instancename = urldecode($modinfo[$modnumber]->name);
                    $instancename = format_string($instancename, true, $this->instance->pageid);
                    $linkcss = $mod->visible ? '' : ' class="dimmed" ';
                    if (!empty($modinfo[$modnumber]->extra)) {
                        $extra = urldecode($modinfo[$modnumber]->extra);
                    } else {
                        $extra = '';
                    }
                    if (!empty($modinfo[$modnumber]->icon)) {
                        $icon = $CFG->pixpath.'/'.urldecode($modinfo[$modnumber]->icon);
                    } else {
                        $icon = $CFG->modpixpath.'/'.$mod->modname.'/icon.gif';
                    }

                    if ($mod->modname == 'label') {
                        $this->content->items[] = format_text($extra, FORMAT_HTML).$editbuttons;
                        $this->content->icons[] = '';
                    } else {
                        $this->content->items[] = '<a title="'.$mod->modfullname.'" '.$linkcss.' '.$extra.
                            ' href="'.$CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">'.$instancename.'</a>'.$editbuttons;
                        $this->content->icons[] = '<img src="'.$icon.'" height="16" width="16" alt="'.$mod->modfullname.'" />';
                    }
                }
            }
        }

        if ($ismoving) {
            $this->content->items[] = '<a title="'.$strmovefull.'" href="'.$CFG->wwwroot.'/course/mod.php?movetosection='.$section->id.'&amp;sesskey='.$USER->sesskey.'">'.
                                      '<img height="16" width="80" src="'.$CFG->pixpath.'/movehere.gif" alt="'.$strmovehere.'" border="0" /></a>';
            $this->content->icons[] = '';
        }

        if ($isediting && $modnames) {
            $this->content->footer = print_section_add_menus($course, $this->config->section, $modnames, true, true);
        } else {
            $this->content->footer = '';
        }

        return $this->content;
    }

    function instance_delete() {
        global $CFG;

        if (empty($this->instance)) {
            return true;
        }
        
        // Cleanup the section created by this block and any course modules.
        if (isset($this->config->section)) {
            $section = get_record('course_sections', 'section', $this->config->section,
                                  'course', $this->instance->pageid);

            if (!empty($section)) {
                if ($modules = get_records('course_modules', 'section', $section->id)) {
                    $mods = array();
                    foreach ($modules as $module) {
                        if (!isset($mods[$module->module])) {
                            $mods[$module->module] = get_field('modules', 'name', 'id', $module->module);
                        }
                        
                        $mod_lib = $CFG->dirroot . '/mod/' . $mods[$module->module] . '/lib.php';
                        
                        if (file_exists($mod_lib)) {
                            require_once($mod_lib);
                            
                            $delete_func = $mods[$module->module] . '_delete_instance';
                            
                            if (function_exists($delete_func)) {
                                $delete_func($module->instance);
                            }
                        }
                    }
                }
                
            }
            
            delete_records('course_sections', 'id', $section->id);
        }
        
        return true;
    }

    function specialization() {
        if (!empty($this->config->title)) {
            $this->title = $this->config->title;
        }
    }

    function has_config() {
        return true;
    }

    function config_save($data) {
        if (!empty($data->block_side_bar_section_start)) {
            set_config('block_side_bar_section_start',
                       intval($data->block_side_bar_section_start));
        }
    }

    function instance_allow_multiple() {
        return true;
    }

    function applicable_formats() {
        return array(
            'site-index' => true,
            'course'     => true
        );
    }

    function after_restore($restore) {
        global $CFG;

        // correct section_id for new course
        $sql = "select id from {$CFG->prefix}course_sections ".
            "where course={$this->instance->pageid} ".
            "and section={$this->config->section} ";
        $rec = get_record_sql( $sql );
        $this->config->section_id = $rec->id;
        parent::instance_config_commit();
        return true;
    }

}

?>
