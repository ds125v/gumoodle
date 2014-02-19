<?php
// This file is part of the custom Moodle elegance theme
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
 * Renderers to align Moodle's HTML with that expected by elegance
 *
 * @package    theme_elegance
 * @copyright  2014 Julian Ridden http://moodleman.net
 * @authors    Julian Ridden -  Bootstrap 3 work by Bas Brands, David Scotson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class theme_elegance_core_renderer extends core_renderer {

    protected function mycourses($CFG,$sidebar){
        $mycourses = enrol_get_users_courses($_SESSION['USER']->id);

        $courselist = array();
        foreach ($mycourses as $key=>$val){
            $courselist[] = $val->id;
        }

        $content = '';

        for($x=1;$x<=sizeof($courselist);$x++){
            $course = get_course($courselist[$x-1]);
            $title = $course->fullname;

            if ($course instanceof stdClass) {
                require_once($CFG->libdir. '/coursecatlib.php');
                $course = new course_in_list($course);
            }

            $url = $CFG->wwwroot."/theme/elegance/pix/coursenoimage.jpg";
            foreach ($course->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                        '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                        $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                if (!$isimage) {
                    $url = $CFG->wwwroot."/theme/elegance/pix/coursenoimage.jpg";
                }
            }

            $content .= '<div class="view view-second view-mycourse '.(($x%3==0)?'view-nomargin':'').'">
                            <img src="'.$url.'" />
                            <div class="mask">
                                <h2>'.$title.'</h2>
                                <a href="'.$CFG->wwwroot.'/course/view.php?id='.$courselist[$x-1].'" class="info">Enter</a>
                            </div>
                        </div>';
        }
        return $content;
    }
    
    /*
     * This renders a notification message.
     * Uses bootstrap compatible html.
     */
    public function notification($message, $classes = 'notifyproblem') {
        $message = clean_text($message);
    
        if ($classes == 'notifyproblem') {
            return html_writer::div($message, 'alert alert-danger');
        }
        if ($classes == 'notifywarning') {
            return html_writer::div($message, 'alert alert-warning');
        }
        if ($classes == 'notifysuccess') {
            return html_writer::div($message, 'alert alert-success');
        }
        if ($classes == 'notifymessage') {
            return html_writer::div($message, 'alert alert-info');
        }
        if ($classes == 'redirectmessage') {
            return html_writer::div($message, 'alert alert-block alert-info');
        }
        return html_writer::div($message, $classes);
    }
    
    /*
     * This renders the navbar.
     * Uses bootstrap compatible html.
     */
    public function navbar() {
        $breadcrumbs = '';
        foreach ($this->page->navbar->get_items() as $item) {
            $item->hideicon = true;
            $breadcrumbs .= '<li>'.$this->render($item).'</li>';
        }
        return "<ol class=breadcrumb>$breadcrumbs</ol>";
    }
    
    /*
     * Overriding the custom_menu function ensures the custom menu is
     * always shown, even if no menu items are configured in the global
     * theme settings page.
     */
    public function custom_menu($custommenuitems = '') {
        global $CFG;
    
        if (!empty($CFG->custommenuitems)) {
            $custommenuitems .= $CFG->custommenuitems;
        }
        $custommenu = new custom_menu($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
    }
    
    /*
     * This renders the bootstrap top menu.
     *
     * This renderer is needed to enable the Bootstrap style navigation.
     */
    protected function render_custom_menu(custom_menu $menu) {
        global $CFG, $USER;
    
        // TODO: eliminate this duplicated logic, it belongs in core, not
        // here. See MDL-39565.
    
        $content = '<ul class="nav navbar-nav">';
        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item, 1);
        }
    
        return $content.'</ul>';
    }
    
    /*
     * Overriding the custom_menu function ensures the custom menu is
     * always shown, even if no menu items are configured in the global
     * theme settings page.
     */
    public function user_menu() {
        global $CFG;
        $usermenu = new custom_menu('', current_language());
        return $this->render_user_menu($usermenu);
    }
    
    /*
     * This renders the bootstrap top menu.
     *
     * This renderer is needed to enable the Bootstrap style navigation.
     */
    protected function render_user_menu(custom_menu $menu) {
        global $CFG, $USER, $DB;
    
        $addusermenu = true;
        $addlangmenu = true;
        $addmessagemenu = true;
    
        if (!isloggedin() || isguestuser()) {
            $addmessagemenu = false;
        }
    
        /*
         $messagecount = $DB->count_records('message', array('useridto' => $USER->id));
         if ($messagecount<1) {
         $addmessagemenu = false;
         }
         */
    
        if ($addmessagemenu) {
            $messages = $this->get_user_messages();
            $messagecount = count($messages);
            $messagemenu = $menu->add(
                '<i class="fa fa-comments"></i>' . $messagecount . ' ' . get_string('messages', 'message'),
                new moodle_url('#'),
                get_string('messages', 'message'),
                9999
            );
            foreach ($messages as $message) {
    
                $senderpicture = new user_picture($message->from);
                $senderpicture->link = false;
                $senderpicture = $this->render($senderpicture);
    
                $messagecontent = $senderpicture;
                $messagecontent .= html_writer::start_span('msg-body');
                $messagecontent .= html_writer::start_span('msg-title');
                $messagecontent .= html_writer::span($message->from->firstname . ': ', 'msg-sender');
                $messagecontent .= $message->text;
                $messagecontent .= html_writer::end_span();
                $messagecontent .= html_writer::start_span('msg-time');
                $messagecontent .= html_writer::tag('i', '', array('class' => 'icon-time'));
                $messagecontent .= html_writer::span($message->date);
                $messagecontent .= html_writer::end_span();
    
                $messageurl = new moodle_url('/message/index.php', array('user1' => $USER->id, 'user2' => $message->from->id));
                $messagemenu->add($messagecontent, $messageurl, $message->state);
            }
        }
    
        $langs = get_string_manager()->get_list_of_translations();
        if (count($langs) < 2
        or empty($CFG->langmenu)
        or ($this->page->course != SITEID and !empty($this->page->course->lang))) {
            $addlangmenu = false;
        }
    
        if ($addlangmenu) {
            $language = $menu->add(get_string('language'), new moodle_url('#'), get_string('language'), 10000);
            foreach ($langs as $langtype => $langname) {
                $language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }
    
        if (!$menu->has_children() && $addlangmenu === false) {
            return '';
        }
    
        if ($addusermenu) {
            if (isloggedin()) {
                $usermenu = $menu->add('<i class="fa fa-user"></i>' .fullname($USER), new moodle_url('#'), fullname($USER), 10001);
                $usermenu->add(
                    '<i class="fa fa-briefcase"></i>' . get_string('mydashboard','theme_elegance'),
                    new moodle_url('/my', array('id'=>$USER->id)),
                    get_string('mydashboard','theme_elegance')
                );
    
                $usermenu->add(
                    '<i class="fa fa-user"></i>' . get_string('viewprofile'),
                    new moodle_url('/user/profile.php', array('id'=>$USER->id)),
                    get_string('viewprofile')
                );
    
                $usermenu->add(
                    '<i class="fa fa-cog"></i>' . get_string('editmyprofile'),
                    new moodle_url('/user/edit.php', array('id'=>$USER->id)),
                    get_string('editmyprofile')
                );
                
                $usermenu->add(
                    '<i class="fa fa-calendar"></i>' . get_string('calendar','calendar'),
                    new moodle_url('/calendar/view.php?view=month', array('sesskey'=>sesskey(), 'alt'=>'logout')),
                    get_string('calendar','calendar')
                );
                
                $usermenu->add(
                    '<i class="fa fa-lock"></i>' . get_string('logout'),
                    new moodle_url('/login/logout.php', array('sesskey'=>sesskey(), 'alt'=>'logout')),
                    get_string('logout')
                );
            } else {
                $usermenu = $menu->add('<i class="fa fa-key"></i>' .get_string('login'), new moodle_url('/login/index.php'), get_string('login'), 10001);
            }
        }
    
        $content = '<ul class="nav navbar-nav navbar-right">';
        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item, 1);
        }
    
        return $content.'</ul>';
    }

   protected function process_user_messages() {
    
        $messagelist = array();
    
        foreach ($usermessages as $message) {
            $cleanmsg = new stdClass();
            $cleanmsg->from = fullname($message);
            $cleanmsg->msguserid = $message->id;
    
            $userpicture = new user_picture($message);
            $userpicture->link = false;
            $picture = $this->render($userpicture);
    
            $cleanmsg->text = $picture . ' ' . $cleanmsg->text;
    
            $messagelist[] = $cleanmsg;
        }
    
        return $messagelist;
    }
    
    protected function get_user_messages() {
        global $USER, $DB;
        $messagelist = array();
        $maxmessages = 5;
    
        $readmessagesql = "SELECT id, smallmessage, useridfrom, useridto, timecreated, fullmessageformat, notification
                             FROM {message_read}
                            WHERE useridto = :userid
                         ORDER BY timecreated DESC
                            LIMIT $maxmessages";
        $newmessagesql = "SELECT id, smallmessage, useridfrom, useridto, timecreated, fullmessageformat, notification
                            FROM {message}
                           WHERE useridto = :userid";
    
        $readmessages = $DB->get_records_sql($readmessagesql, array('userid' => $USER->id));
    
        $newmessages = $DB->get_records_sql($newmessagesql, array('userid' => $USER->id));
    
        foreach ($newmessages as $message) {
            $messagelist[] = $this->bootstrap_process_message($message, 'new');
        }
    
        foreach ($readmessages as $message) {
            $messagelist[] = $this->bootstrap_process_message($message, 'old');
        }
        return $messagelist;
    
    }
    
    protected function bootstrap_process_message($message, $state) {
        global $DB;
        $messagecontent = new stdClass();
    
        if ($message->notification) {
            $messagecontent->text = get_string('unreadnewnotification', 'message');
        } else {
            if ($message->fullmessageformat == FORMAT_HTML) {
                $message->smallmessage = html_to_text($message->smallmessage);
            }
            if (core_text::strlen($message->smallmessage) > 15) {
                $messagecontent->text = core_text::substr($message->smallmessage, 0, 15).'...';
            } else {
                $messagecontent->text = $message->smallmessage;
            }
        }
    
        if ((time() - $message->timecreated ) <= (3600 * 3)) {
            $messagecontent->date = format_time(time() - $message->timecreated);
        } else {
            $messagecontent->date = userdate($message->timecreated, get_string('strftimetime', 'langconfig'));
        }
    
        $messagecontent->from = $DB->get_record('user', array('id' => $message->useridfrom));
        $messagecontent->state = $state;
        return $messagecontent;
    }



    /*
     * This code renders the custom menu items for the
     * bootstrap dropdown menu.
     */
    protected function render_custom_menu_item(custom_menu_item $menunode, $level = 0 ) {
        static $submenucount = 0;
    
        if ($menunode->has_children()) {
    
            if ($level == 1) {
                $dropdowntype = 'dropdown';
            } else {
                $dropdowntype = 'dropdown-submenu';
            }
    
            $content = html_writer::start_tag('li', array('class'=>$dropdowntype));
            // If the child has menus render it as a sub menu.
            $submenucount++;
            if ($menunode->get_url() !== null) {
                $url = $menunode->get_url();
            } else {
                $url = '#cm_submenu_'.$submenucount;
            }
            $link_attributes = array(
                'href'=>$url,
                'class'=>'dropdown-toggle',
                'data-toggle'=>'dropdown',
                'title'=>$menunode->get_title(),
            );
            $content .= html_writer::start_tag('a', $link_attributes);
            $content .= $menunode->get_text();
            if ($level == 1) {
                $content .= '<b class="caret"></b>';
            }
            $content .= '</a>';
            $content .= '<ul class="dropdown-menu">';
            foreach ($menunode->get_children() as $menunode) {
                $content .= $this->render_custom_menu_item($menunode, 0);
            }
            $content .= '</ul>';
        } else {
            $content = '<li>';
            // The node doesn't have children so produce a final menuitem.
            if ($menunode->get_url() !== null) {
                $url = $menunode->get_url();
            } else {
                $url = '#';
            }
            $content .= html_writer::link($url, $menunode->get_text(), array('title'=>$menunode->get_title()));
        }
        return $content;
    }
    
    /**
     * Renders tabtree
     *
     * @param tabtree $tabtree
     * @return string
     */
    protected function render_tabtree(tabtree $tabtree) {
        if (empty($tabtree->subtree)) {
            return '';
        }
        $firstrow = $secondrow = '';
        foreach ($tabtree->subtree as $tab) {
            $firstrow .= $this->render($tab);
            if (($tab->selected || $tab->activated) && !empty($tab->subtree) && $tab->subtree !== array()) {
                $secondrow = $this->tabtree($tab->subtree);
            }
        }
        return html_writer::tag('ul', $firstrow, array('class' => 'nav nav-tabs')) . $secondrow;
    }
    
    /**
     * Renders tabobject (part of tabtree)
     *
     * This function is called from {@link core_renderer::render_tabtree()}
     * and also it calls itself when printing the $tabobject subtree recursively.
     *
     * @param tabobject $tabobject
     * @return string HTML fragment
     */
    protected function render_tabobject(tabobject $tab) {
        if ($tab->selected or $tab->activated) {
            return html_writer::tag('li', html_writer::tag('a', $tab->text), array('class' => 'active'));
        } else if ($tab->inactive) {
            return html_writer::tag('li', html_writer::tag('a', $tab->text), array('class' => 'disabled'));
        } else {
            if (!($tab->link instanceof moodle_url)) {
                // Backward compatibility when link was passed as quoted string.
                $link = "<a href=\"$tab->link\" title=\"$tab->title\">$tab->text</a>";
            } else {
                $link = html_writer::link($tab->link, $tab->text, array('title' => $tab->title));
            }
            return html_writer::tag('li', $link);
        }
    }
    
    /*
     * This renders a notification message.
     * Uses bootstrap compatible html.
    */
    public function page_heading($tag = 'h1') {
        $heading = parent::page_heading();
        if ($this->page->pagelayout == 'frontpage') {
            $heading .= '<h3>' . $this->page->theme->settings->subtitle . '</h3>';
        }
        return $heading;
    }
}

function theme_elegance_get_nav_links($course, $sections, $sectionno) {
        // FIXME: This is really evil and should by using the navigation API.
        $courseformat = course_get_format($course);
        $course = $courseformat->get_course();
        $previousarrow= '<i class="fa fa-chevron-circle-left"></i>';
        $nextarrow= '<i class="fa fa-chevron-circle-right"></i>';
        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
            or !$course->hiddensections;

        $links = array('previous' => '', 'next' => '');
        $back = $sectionno - 1;
        while ($back > 0 and empty($links['previous'])) {
            if ($canviewhidden || $sections[$back]->uservisible) {
                $params = array('id' => 'previous_section');
                if (!$sections[$back]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $previouslink = html_writer::start_tag('div', array('class' => 'nav_icon'));
                $previouslink .= $previousarrow;
                $previouslink .= html_writer::end_tag('div');
                $previouslink .= html_writer::start_tag('span', array('class' => 'text'));
                $previouslink .= html_writer::start_tag('span', array('class' => 'nav_guide'));
                $previouslink .= get_string('previoussection', 'theme_elegance');
                $previouslink .= html_writer::end_tag('span');
                $previouslink .= html_writer::empty_tag('br');
                $previouslink .= $courseformat->get_section_name($sections[$back]);
                $previouslink .= html_writer::end_tag('span');
                $links['previous'] = html_writer::link(course_get_url($course, $back), $previouslink, $params);
            }
            $back--;
        }

        $forward = $sectionno + 1;
        while ($forward <= $course->numsections and empty($links['next'])) {
            if ($canviewhidden || $sections[$forward]->uservisible) {
                $params = array('id' => 'next_section');
                if (!$sections[$forward]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $nextlink = html_writer::start_tag('div', array('class' => 'nav_icon'));
                $nextlink .= $nextarrow;
                $nextlink .= html_writer::end_tag('div');
                $nextlink .= html_writer::start_tag('span', array('class' => 'text'));
                $nextlink .= html_writer::start_tag('span', array('class' => 'nav_guide'));
                $nextlink .= get_string('nextsection', 'theme_elegance');
                $nextlink .= html_writer::end_tag('span');
                $nextlink .= html_writer::empty_tag('br');
                $nextlink .= $courseformat->get_section_name($sections[$forward]);
                $nextlink .= html_writer::end_tag('span');
                $links['next'] = html_writer::link(course_get_url($course, $forward), $nextlink, $params);
            }
            $forward++;
        }

        return $links;
    }


include_once($CFG->dirroot . "/course/format/topics/renderer.php");
class theme_elegance_format_topics_renderer extends format_topics_renderer {

    protected function get_nav_links($course, $sections, $sectionno) {
        return theme_elegance_get_nav_links($course, $sections, $sectionno);
    }

    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        // Can we view the section in question?
        if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
            // This section doesn't exist
            print_error('unknowncoursesection', 'error', null, $course->fullname);
            return;
        }

        if (!$sectioninfo->uservisible) {
            if (!$course->hiddensections) {
                echo $this->start_section_list();
                echo $this->section_hidden($displaysection);
                echo $this->end_section_list();
            }
            // Can't view this section.
            return;
        }

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, $displaysection);
        $thissection = $modinfo->get_section_info(0);
        if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
            echo $this->start_section_list();
            echo $this->section_header($thissection, $course, true, $displaysection);
            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();
        }

        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);

        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
        $sectiontitle = '';
        $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation header headingblock'));
        // Title attributes
        $titleattr = 'title';
        if (!$thissection->visible) {
            $titleattr .= ' dimmed_text';
        }
        $sectiontitle .= html_writer::tag('div', get_section_name($course, $displaysection), array('class' => $titleattr));
        $sectiontitle .= html_writer::end_tag('div');
        echo $sectiontitle;

        // Now the list of sections..
        echo $this->start_section_list();

        echo $this->section_header($thissection, $course, true, $displaysection);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();

        echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
        echo $this->section_footer();
        echo $this->end_section_list();

        // Display section bottom navigation.
        $sectionbottomnav = '';
        $sectionbottomnav .= html_writer::start_tag('nav', array('id' => 'section_footer'));
        $sectionbottomnav .= $sectionnavlinks['previous']; 
        $sectionbottomnav .= $sectionnavlinks['next']; 
        // $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection), array('class' => 'mdl-align'));
        $sectionbottomnav .= html_writer::empty_tag('br', array('style'=>'clear:both'));
        $sectionbottomnav .= html_writer::end_tag('nav');
        echo $sectionbottomnav;

        // Close single-section div.
        echo html_writer::end_tag('div');
    }
}

include_once($CFG->dirroot . "/course/format/weeks/renderer.php");
class theme_elegance_format_weeks_renderer extends format_weeks_renderer {

    protected function get_nav_links($course, $sections, $sectionno) {
        return theme_elegance_get_nav_links($course, $sections, $sectionno);
    }

    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        // Can we view the section in question?
        if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
            // This section doesn't exist
            print_error('unknowncoursesection', 'error', null, $course->fullname);
            return;
        }

        if (!$sectioninfo->uservisible) {
            if (!$course->hiddensections) {
                echo $this->start_section_list();
                echo $this->section_hidden($displaysection);
                echo $this->end_section_list();
            }
            // Can't view this section.
            return;
        }

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, $displaysection);
        $thissection = $modinfo->get_section_info(0);
        if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
            echo $this->start_section_list();
            echo $this->section_header($thissection, $course, true, $displaysection);
            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();
        }

        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);

        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
        $sectiontitle = '';
        $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation header headingblock'));
        // Title attributes
        $titleattr = 'title';
        if (!$thissection->visible) {
            $titleattr .= ' dimmed_text';
        }
        $sectiontitle .= html_writer::tag('div', get_section_name($course, $displaysection), array('class' => $titleattr));
        $sectiontitle .= html_writer::end_tag('div');
        echo $sectiontitle;

        // Now the list of sections..
        echo $this->start_section_list();

        echo $this->section_header($thissection, $course, true, $displaysection);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();

        echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
        echo $this->section_footer();
        echo $this->end_section_list();

        // Display section bottom navigation.
        $sectionbottomnav = '';
        $sectionbottomnav .= html_writer::start_tag('nav', array('id' => 'section_footer'));
        $sectionbottomnav .= $sectionnavlinks['previous']; 
        $sectionbottomnav .= $sectionnavlinks['next']; 
        // $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection), array('class' => 'mdl-align'));
        $sectionbottomnav .= html_writer::empty_tag('br', array('style'=>'clear:both'));
        $sectionbottomnav .= html_writer::end_tag('nav');
        echo $sectionbottomnav;

        // Close single-section div.
        echo html_writer::end_tag('div');
    }
}

// Requires V2.6.1.3+ of Collapsed Topics format.
if (file_exists("$CFG->dirroot/course/format/topcoll/renderer.php")) {
    include_once($CFG->dirroot . "/course/format/topcoll/renderer.php");
    class theme_elegance_format_topcoll_renderer extends format_topcoll_renderer {

        protected function get_nav_links($course, $sections, $sectionno) {
            return theme_elegance_get_nav_links($course, $sections, $sectionno);
        }

        public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
            global $PAGE;

            $modinfo = get_fast_modinfo($course);
            $course = course_get_format($course)->get_course();

            // Can we view the section in question?
            if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
                // This section doesn't exist
                print_error('unknowncoursesection', 'error', null, $course->fullname);
                return;
            }

            if (!$sectioninfo->uservisible) {
                if (!$course->hiddensections) {
                    echo $this->start_section_list();
                    echo $this->section_hidden($displaysection);
                    echo $this->end_section_list();
                }
                // Can't view this section.
                return;
            }

            // Copy activity clipboard..
            echo $this->course_activity_clipboard($course, $displaysection);
            $thissection = $modinfo->get_section_info(0);
            if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                echo $this->start_section_list();
                echo $this->section_header($thissection, $course, true, $displaysection);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
                echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
                echo $this->section_footer();
                echo $this->end_section_list();
            }

            // Start single-section div
            echo html_writer::start_tag('div', array('class' => 'single-section'));

            // The requested section page.
            $thissection = $modinfo->get_section_info($displaysection);

            // Title with section navigation links.
            $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
            $sectiontitle = '';
            $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation header headingblock'));
            // Title attributes
            $titleattr = 'title';
            if (!$thissection->visible) {
                $titleattr .= ' dimmed_text';
            }
            $sectiontitle .= html_writer::tag('div', get_section_name($course, $displaysection), array('class' => $titleattr));
            $sectiontitle .= html_writer::end_tag('div');
            echo $sectiontitle;

            // Now the list of sections..
            echo $this->start_section_list();

            echo $this->section_header($thissection, $course, true, $displaysection);
            // Show completion help icon.
            $completioninfo = new completion_info($course);
            echo $completioninfo->display_help_icon();

            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();

            // Display section bottom navigation.
            $sectionbottomnav = '';
            $sectionbottomnav .= html_writer::start_tag('nav', array('id' => 'section_footer'));
            $sectionbottomnav .= $sectionnavlinks['previous']; 
            $sectionbottomnav .= $sectionnavlinks['next']; 
            // $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection), array('class' => 'mdl-align'));
            $sectionbottomnav .= html_writer::empty_tag('br', array('style'=>'clear:both'));
            $sectionbottomnav .= html_writer::end_tag('nav');
            echo $sectionbottomnav;

            // Close single-section div.
            echo html_writer::end_tag('div');
        }
    }
}

if (file_exists("$CFG->dirroot/course/format/grid/renderer.php")) {
    include_once($CFG->dirroot . "/course/format/grid/renderer.php");
    class theme_elegance_format_grid_renderer extends format_grid_renderer {

        protected function get_nav_links($course, $sections, $sectionno) {
            return theme_elegance_get_nav_links($course, $sections, $sectionno);
        }

        public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
            global $PAGE;

            $modinfo = get_fast_modinfo($course);
            $course = course_get_format($course)->get_course();

            // Can we view the section in question?
            if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
                // This section doesn't exist
                print_error('unknowncoursesection', 'error', null, $course->fullname);
                return;
            }

            if (!$sectioninfo->uservisible) {
                if (!$course->hiddensections) {
                    echo $this->start_section_list();
                    echo $this->section_hidden($displaysection);
                    echo $this->end_section_list();
                }
                // Can't view this section.
                return;
            }

            // Copy activity clipboard..
            echo $this->course_activity_clipboard($course, $displaysection);
            $thissection = $modinfo->get_section_info(0);
            if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                echo $this->start_section_list();
                echo $this->section_header($thissection, $course, true, $displaysection);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
                echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
                echo $this->section_footer();
                echo $this->end_section_list();
            }

            // Start single-section div
            echo html_writer::start_tag('div', array('class' => 'single-section'));

            // The requested section page.
            $thissection = $modinfo->get_section_info($displaysection);

            // Title with section navigation links.
            $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
            $sectiontitle = '';
            $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation header headingblock'));
            // Title attributes
            $titleattr = 'title';
            if (!$thissection->visible) {
                $titleattr .= ' dimmed_text';
            }
            $sectiontitle .= html_writer::tag('div', get_section_name($course, $displaysection), array('class' => $titleattr));
            $sectiontitle .= html_writer::end_tag('div');
            echo $sectiontitle;

            // Now the list of sections..
            echo $this->start_section_list();

            echo $this->section_header($thissection, $course, true, $displaysection);
            // Show completion help icon.
            $completioninfo = new completion_info($course);
            echo $completioninfo->display_help_icon();

            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();

            // Display section bottom navigation.
            $sectionbottomnav = '';
            $sectionbottomnav .= html_writer::start_tag('nav', array('id' => 'section_footer'));
            $sectionbottomnav .= $sectionnavlinks['previous']; 
            $sectionbottomnav .= $sectionnavlinks['next']; 
            // $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection), array('class' => 'mdl-align'));
            $sectionbottomnav .= html_writer::empty_tag('br', array('style'=>'clear:both'));
            $sectionbottomnav .= html_writer::end_tag('nav');
            echo $sectionbottomnav;

            // Close single-section div.
            echo html_writer::end_tag('div');
        }
    }
}

if (file_exists("$CFG->dirroot/course/format/noticebd/renderer.php")) {
    include_once($CFG->dirroot . "/course/format/noticebd/renderer.php");
    class theme_elegance_format_noticebd_renderer extends format_noticebd_renderer {

        protected function get_nav_links($course, $sections, $sectionno) {
            return theme_elegance_get_nav_links($course, $sections, $sectionno);
        }

        public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
            global $PAGE;

            $modinfo = get_fast_modinfo($course);
            $course = course_get_format($course)->get_course();

            // Can we view the section in question?
            if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
                // This section doesn't exist
                print_error('unknowncoursesection', 'error', null, $course->fullname);
                return;
            }

            if (!$sectioninfo->uservisible) {
                if (!$course->hiddensections) {
                    echo $this->start_section_list();
                    echo $this->section_hidden($displaysection);
                    echo $this->end_section_list();
                }
                // Can't view this section.
                return;
            }

            // Copy activity clipboard..
            echo $this->course_activity_clipboard($course, $displaysection);

            // General section if non-empty.
            $thissection = $sections[0];
            //if ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing()) {
                echo $this->start_section_list();
                echo $this->section_header($thissection, $course, true, $displaysection);
                $this->print_noticeboard($course);
                if (($PAGE->user_is_editing()) && (is_siteadmin($USER))) {
                    print_section($course, $thissection, $mods, $modnamesused, true, "100%", false, $displaysection);
                    print_section_add_menus($course, 0, $modnames, false, false, $displaysection);
                }
                echo $this->section_footer();
                echo $this->end_section_list();
            //}

            // Start single-section div
            echo html_writer::start_tag('div', array('class' => 'single-section'));

            // The requested section page.
            $thissection = $modinfo->get_section_info($displaysection);

            // Title with section navigation links.
            $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
            $sectiontitle = '';
            $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation header headingblock'));
            // Title attributes
            $titleattr = 'title';
            if (!$thissection->visible) {
                $titleattr .= ' dimmed_text';
            }
            $sectiontitle .= html_writer::tag('div', get_section_name($course, $displaysection), array('class' => $titleattr));
            $sectiontitle .= html_writer::end_tag('div');
            echo $sectiontitle;

            // Now the list of sections..
            echo $this->start_section_list();

            echo $this->section_header($thissection, $course, true, $displaysection);
            // Show completion help icon.
            $completioninfo = new completion_info($course);
            echo $completioninfo->display_help_icon();

            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();

            // Display section bottom navigation.
            $sectionbottomnav = '';
            $sectionbottomnav .= html_writer::start_tag('nav', array('id' => 'section_footer'));
            $sectionbottomnav .= $sectionnavlinks['previous']; 
            $sectionbottomnav .= $sectionnavlinks['next']; 
            // $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection), array('class' => 'mdl-align'));
            $sectionbottomnav .= html_writer::empty_tag('br', array('style'=>'clear:both'));
            $sectionbottomnav .= html_writer::end_tag('nav');
            echo $sectionbottomnav;

            // Close single-section div.
            echo html_writer::end_tag('div');
        }
    }
}

// Requires V2.6.1.1+ of Columns format.
if (file_exists("$CFG->dirroot/course/format/columns/renderer.php")) {
    include_once($CFG->dirroot . "/course/format/columns/renderer.php");
    class theme_elegance_format_columns_renderer extends format_columns_renderer {

        protected function get_nav_links($course, $sections, $sectionno) {
            return theme_elegance_get_nav_links($course, $sections, $sectionno);
        }

        public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
            global $PAGE;

            $modinfo = get_fast_modinfo($course);
            $course = course_get_format($course)->get_course();

            // Can we view the section in question?
            if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
                // This section doesn't exist
                print_error('unknowncoursesection', 'error', null, $course->fullname);
                return;
            }

            if (!$sectioninfo->uservisible) {
                if (!$course->hiddensections) {
                    echo $this->start_section_list();
                    echo $this->section_hidden($displaysection);
                    echo $this->end_section_list();
                }
                // Can't view this section.
                return;
            }

            // Copy activity clipboard..
            echo $this->course_activity_clipboard($course, $displaysection);
            $thissection = $modinfo->get_section_info(0);
            if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                echo $this->start_section_list();
                echo $this->section_header($thissection, $course, true, $displaysection);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
                echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
                echo $this->section_footer();
                echo $this->end_section_list();
            }

            // Start single-section div
            echo html_writer::start_tag('div', array('class' => 'single-section'));

            // The requested section page.
            $thissection = $modinfo->get_section_info($displaysection);

            // Title with section navigation links.
            $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
            $sectiontitle = '';
            $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation header headingblock'));
            // Title attributes
            $titleattr = 'title';
            if (!$thissection->visible) {
                $titleattr .= ' dimmed_text';
            }
            $sectiontitle .= html_writer::tag('div', get_section_name($course, $displaysection), array('class' => $titleattr));
            $sectiontitle .= html_writer::end_tag('div');
            echo $sectiontitle;

            // Now the list of sections..
            echo $this->start_section_list();

            echo $this->section_header($thissection, $course, true, $displaysection);
            // Show completion help icon.
            $completioninfo = new completion_info($course);
            echo $completioninfo->display_help_icon();

            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();

            // Display section bottom navigation.
            $sectionbottomnav = '';
            $sectionbottomnav .= html_writer::start_tag('nav', array('id' => 'section_footer'));
            $sectionbottomnav .= $sectionnavlinks['previous']; 
            $sectionbottomnav .= $sectionnavlinks['next']; 
            // $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection), array('class' => 'mdl-align'));
            $sectionbottomnav .= html_writer::empty_tag('br', array('style'=>'clear:both'));
            $sectionbottomnav .= html_writer::end_tag('nav');
            echo $sectionbottomnav;

            // Close single-section div.
            echo html_writer::end_tag('div');
        }
    }
}