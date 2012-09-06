<?php
/* renderers to align Moodle's HTML with that expected by Bootstrap */

class theme_bootstrap_core_renderer extends core_renderer {


    protected static function icon($name, $text=null) {
        if (!$text) {$text = $name;}
        return "<i class=icon-$name>$text</i>";
    }
    protected static function moodle_icon($name) {
        $icons = array(
                'docs' => 'question-sign',
                'book' => 'book',
                'chapter' => 'file',
                'spacer' => 'spacer',
                'generate' => 'gift',
                'add' => 'plus',
                't/hide' => 'eye-open',
                'i/hide' => 'eye-open',
                't/show' => 'eye-close',
                'i/show' => 'eye-close',
                't/add' => 'plus',
                't/right' => 'arrow-right',
                't/left' => 'arrow-left',
                't/up' => 'arrow-up',
                't/down' => 'arrow-down',
                't/edit' => 'edit',
                't/editstring' => 'tag',
                't/copy' => 'duplicate',
                't/delete' => 'remove',
                'i/edit' => 'pencil',
                'i/settings' => 'list-alt',
                'i/grades' => 'grades',
                'i/group' => 'user',
                //'t/groupn' => '?',
                //'t/groupv' => '?',
                't/switch_plus' => 'plus-sign',
                't/switch_minus' => 'minus-sign',
                'i/filter' => 'filter',
                't/move' => 'resize-vertical',
                'i/move_2d' => 'move',
                'i/backup' => 'cog',
                'i/restore' => 'cog',
                'i/return' => 'repeat',
                'i/roles' => 'user',
                'i/user' => 'user',
                'i/users' => 'user',
                'i/publish' => 'publish',
                'i/navigationitem' => 'chevron-right' );
        return self::icon($icons[$name]);
    }
    public function icon_help() {
        return self::icon('question-sign');
    } 

    protected static function a($attributes, $content) {
        return html_writer::tag('a', $content, $attributes);
    }
    protected static function div($attributes, $content) {
        return html_writer::tag('div', $content, $attributes);
    }
    protected static function span($attributes, $content) {
        return html_writer::tag('span', $content, $attributes);
    }

    protected static function ul($items) {
        $lis = array();
        foreach ($items as $key => $string) {
            $lis[] = "<li>$string</li>";
        }
        return '<ul class=unstyled>'.implode($lis).'</ul>';
    }
    public function action_icon($url, pix_icon $pixicon, component_action $action = null, array $attributes = null, $linktext=false) {
        if (!($url instanceof moodle_url)) {
            $url = new moodle_url($url);
        }
        $attributes = (array)$attributes;

        if (empty($attributes['class'])) {
            // let ppl override the class via $options
            $attributes['class'] = 'action-icon';
        }

        $icon = $this->render($pixicon);

        if ($linktext) {
            $text = $pixicon->attributes['alt'];
        } else {
            $text = '';
        }

        return $this->action_link($url, $text.$icon, $action, $attributes);
    }
    public function home_link() {
        global $CFG, $SITE;
        $text = '';
        $linktext = 'Moodle';

        if ($this->page->pagetype == 'site-index') {
            $div_attributes['class'] = "sitelink";
            $text = 'made with ';
            $a_attributes['href'] = 'http://moodle.org/';
        } else if (!empty($CFG->target_release) &&
                $CFG->target_release != $CFG->release) {
            // Special case for during install/upgrade.
            $div_attributes['class'] = "sitelink";
            $text = 'help with ';
            $a_attributes['href'] = 'http://docs.moodle.org/en/Administrator_documentation';
            $a_attributes['target'] = '_blank';
        } else if ($this->page->course->id == $SITE->id ||
                strpos($this->page->pagetype, 'course-view') === 0) {
            $div_attributes['class'] = "homelink";
            $linktext = get_string('home');
            $a_attributes['href'] = $CFG->wwwroot . '/';
        } else {
            $div_attributes['class'] = "homelink";
            $linktext = format_string($this->page->course->shortname, true, array('context' => $this->page->context));
            $a_attributes['href'] = $CFG->wwwroot . '/course/view.php?id=' . $this->page->course->id;
        }
        return self::div($div_attributes, $text . self::a($a_attributes, $linktext));
    }

    protected function render_pix_icon(pix_icon $icon) {
        $iconset = array(
                'docs' => 'question-sign',
                'book' => 'book',
                'chapter' => 'file',
                'spacer' => 'spacer',
                'generate' => 'gift',
                'add' => 'plus',
                't/hide' => 'eye-open',
                'i/hide' => 'eye-open',
                't/show' => 'eye-close',
                'i/show' => 'eye-close',
                't/add' => 'plus',
                't/right' => 'arrow-right',
                't/left' => 'arrow-left',
                't/up' => 'arrow-up',
                't/down' => 'arrow-down',
                't/edit' => 'edit',
                't/editstring' => 'tag',
                't/copy' => 'duplicate',
                't/delete' => 'remove',
                'i/edit' => 'pencil',
                'i/settings' => 'list-alt',
                'i/grades' => 'grades',
                'i/group' => 'user',
                //'t/groupn' => '?',
                //'t/groupv' => '?',
                't/switch_plus' => 'plus-sign',
                't/switch_minus' => 'minus-sign',
                'i/filter' => 'filter',
                't/move' => 'resize-vertical',
                'i/move_2d' => 'move',
                'i/backup' => 'cog',
                'i/restore' => 'cog',
                'i/return' => 'repeat',
                'i/roles' => 'user',
                'i/user' => 'user',
                'i/users' => 'user',
                'i/publish' => 'publish',
                'i/navigationitem' => 'chevron-right' );

        if (isset($iconset[$icon->pix])) {
            return self::icon($iconset[$icon->pix]);
        } else {
            //return parent::render_pix_icon($icon);
            return '<i class=icon-not-assigned data-debug-icon="'.$icon->pix.'"></i>';
        }


    }
    protected function render_custom_menu(custom_menu $menu) {
        if (!$menu->has_children()) {
            return '';
        }
        $content  = '<div class="navbar navbar-fixed-top">' .
        '<div class=navbar-inner>' .
        '<div class=container>' .
        '<ul class=nav>';

        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item);
        }
        $content .= '</ul></div></div><div>'; 
        return $content;
    }

    protected function render_custom_menu_item(custom_menu_item $menunode) {
        // Required to ensure we get unique trackable id's
        static $submenucount = 0;

        if ($menunode->has_children()) {
            $content = '<li class=dropdown>';
            // If the child has menus render it as a sub menu
            $submenucount++;
            if ($menunode->get_url() !== null) {
                $url = $menunode->get_url();
            } else {
                $url = '#cm_submenu_'.$submenucount;
            }

            //$content .= html_writer::link($url, $menunode->get_text(), array('title'=>,));
            $content .= '<a href="'.$url.'" class=dropdown-toggle data-toggle=dropdown>';
            $content .= $menunode->get_title();
            $content .= '<b class=caret></b></a>';
            $content .= '<ul class=dropdown-menu>';
            foreach ($menunode->get_children() as $menunode) {
                $content .= $this->render_custom_menu_item($menunode);
            }
            $content .= '</ul>';
        } else {
            $content = '<li>';
            // The node doesn't have children so produce a final menuitem

            if ($menunode->get_url() !== null) {
                $url = $menunode->get_url();
            } else {
                $url = '#';
            }
            $content .= html_writer::link($url, $menunode->get_text(), array('title'=>$menunode->get_title()));
        }
        $content .= '<li>';
        return $content;
    }
    public function block_controls($controls) {
        if (empty($controls)) {
            return '';
        }
        $controlshtml = array();
        foreach ($controls as $control) {
            $controlshtml[] = self::a(array('href'=>$control['url'], 'title'=>$control['caption']), self::moodle_icon($control['icon']));
        }
        return self::div(array('class'=>'commands'), implode($controlshtml));
    }

    public function list_block_contents($icons, $items) {
        return self::ul($items);
    }

    public function doc_link($path, $text = '') {
        $attributes['href'] = new moodle_url(get_docs_url($path));
        if ($text == '') {
            $linktext = $this->icon_help();
        } else {
            $linktext = $this->icon_help().' '.$text; }
        return self::a($attributes, $linktext);
    }

    public function spacer(array $attributes = null, $br = false) {
        return self::icon('spacer');
        // don't output br's or attributes
    }
    public function error_text($message) {
        if (empty($message)) { return ''; }
        return self::span(array('class'=>'label label-error'), $message);
    }
    public function notification($message, $classes = 'notifyproblem') {
        // TODO rewrite recognized classnames to bootstrap alert equivalent 
        return self::div(array('class'=>'alert '.$classes), clean_text($message));
    }
    protected function render_paging_bar(paging_bar $pagingbar) {
        $output = '<div class="pagination pagination-centered"><ul>';
        $pagingbar = clone($pagingbar);
        $pagingbar->prepare($this, $this->page, $this->target);

        if ($pagingbar->totalcount > $pagingbar->perpage) {
            if (!empty($pagingbar->previouslink)) {
                $output .= "<li>$pagingbar->previouslink</li>";
            }
            if (!empty($pagingbar->firstlink)) {
                $output .= self::li(array('class'=>'disabled'), $pagingbar->firstlink);
            }
            foreach ($pagingbar->pagelinks as $link) {
                $output .= "<li>$link</li>";
            }
            if (!empty($pagingbar->lastlink)) {
                $output .= self::li(array('class'=>'disabled'), $pagingbar->lastlink);
            }
            if (!empty($pagingbar->nextlink)) {
                $output .= "<li>$pagingbar->nextlink</li>";
            }
        }
        return $output."</ul></div>";
    }
    public function navbar() {
        $items = $this->page->navbar->get_items();
        $htmlblocks = array();
        //$divider = '<span class="divider">'.get_separator().'</span>';
        $divider = self::span(array('class'=>'divider'), '/');
        $navbarcontent = '<ul class=breadcrumb>';
        $itemcount = count($items);
        $lis = array();
        for ($i=1;$i <= $itemcount;$i++) {
            $item = $items[$i-1];
            $item->hideicon = true;
            if ($i===$itemcount) {
                $li= "<li>".$this->render($item)."</li>";
            } else {
                $li= "<li>".$this->render($item)." $divider</li>";
            }
            $lis[] = $li;
        }

        $navbarcontent .= join('', $lis).'</ul>';
        return $navbarcontent;
    }
    protected function render_single_button(single_button $button) {
        $attributes = array('type'     => 'submit',
                'class'    => 'btn',
                'value'    => $button->label,
                'disabled' => $button->disabled ? 'disabled' : null,
                'title'    => $button->tooltip);

        if ($button->actions) {
            $id = html_writer::random_id('single_button');
            $attributes['id'] = $id;
            foreach ($button->actions as $action) {
                $this->add_action_handler($action, $id);
            }
        }

        // first the input element
        $output = html_writer::empty_tag('input', $attributes);

        // then hidden fields
        $params = $button->url->params();
        if ($button->method === 'post') {
            $params['sesskey'] = sesskey();
        }
        foreach ($params as $var => $val) {
            $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $var, 'value' => $val));
        }

        // then div wrapper for xhtml strictness
        $output = html_writer::tag('div', $output);

        // now the form itself around it
        if ($button->method === 'get') {
            $url = $button->url->out_omit_querystring(true); // url without params, the anchor part allowed
        } else {
            $url = $button->url->out_omit_querystring();     // url without params, the anchor part not allowed
        }
        if ($url === '') {
            $url = '#'; // there has to be always some action
        }
        $attributes = array('method' => $button->method,
                'class' => 'form-inline',
                'action' => $url,
                'id'     => $button->formid);
        $output = html_writer::tag('form', $output, $attributes);

        return self::div(array('class' => $button->class), $output);
    }
    protected function render_single_select(single_select $select) {
        $select = clone($select);
        if (empty($select->formid)) {
            $select->formid = html_writer::random_id('single_select_f');
        }
        $output = '';
        $params = $select->url->params();
        if ($select->method === 'post') {
            $params['sesskey'] = sesskey();
        }
        foreach ($params as $name=>$value) {
            $output .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>$name, 'value'=>$value));
        }

        if (empty($select->attributes['id'])) {
            $select->attributes['id'] = html_writer::random_id('single_select');
        }

        if ($select->disabled) {
            $select->attributes['disabled'] = 'disabled';
        }

        if ($select->tooltip) {
            $select->attributes['title'] = $select->tooltip;
        }

        if ($select->label) {
            $output .= html_writer::label($select->label, $select->attributes['id'], false, $select->labelattributes);
        }

        if ($select->helpicon instanceof help_icon) {
            $output .= $this->render($select->helpicon);
        } else if ($select->helpicon instanceof old_help_icon) {
            $output .= $this->render($select->helpicon);
        }
        $output .= html_writer::select($select->options, $select->name, $select->selected, $select->nothing, $select->attributes);

        $go = html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('go')));
        $output .= html_writer::tag('noscript', $go);

        $nothing = empty($select->nothing) ? false : key($select->nothing);
        $this->page->requires->js_init_call('M.util.init_select_autosubmit', array($select->formid, $select->attributes['id'], $nothing));

        // then div wrapper for xhtml strictness
        $output = html_writer::tag('div', $output);

        // now the form itself around it
        if ($select->method === 'get') {
            $url = $select->url->out_omit_querystring(true); // url without params, the anchor part allowed
        } else {
            $url = $select->url->out_omit_querystring();     // url without params, the anchor part not allowed
        }
        $formattributes = array('method' => $select->method,
                'class' => 'form-inline',
                'action' => $url,
                'id'     => $select->formid);
        $output = html_writer::tag('form', $output, $formattributes);

        // and finally one more wrapper with class
        return self::div(array('class' => $select->class), $output);
    }
    protected function init_block_hider_js(block_contents $bc) { }

}
