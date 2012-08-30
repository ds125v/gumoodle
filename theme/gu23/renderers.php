<?php
/* some documentation here */

class theme_gu23_core_renderer extends core_renderer {
     
	
    protected function render_pix_icon(pix_icon $icon) {
        $attributes = $icon->attributes;
        $attributes['src'] = $this->pix_url($icon->pix, $icon->component);
        $iconset = array(
        'docs' => 'question-sign',
        'book' => 'book',
        'chapter' => 'file',
        'spacer' => 'spacer',
        'generate' => 'gift',
        'add' => 'plus',
        't/hide' => 'eye-open',
        't/show' => 'eye-close',
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
            return '<i class="icon-'.$iconset[$icon->pix].'"></i> ';
        } else {
            //return parent::render_pix_icon($icon);
            return '<i class="icon-not-assigned" data-moodle-icon="'.$icon->pix.'"></i> ';
        }
        
        
    }
    
 		
}
