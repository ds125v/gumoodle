<?php //$Id: mod_form.php,v 0.2 2009/02/21 matbury Exp $

/**
* Creates instance of FLV activity module
* Adapted from mod_form.php template by Jamie Pratt
*
* By Matt Bury - http://matbury.com/ - matbury@gmail.com
* @version $Id: index.php,v 0.2 2009/02/21 matbury Exp $
* @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
*
* DB Table name (mdl_)flv
*
* SOURCE PARAMETERS:
* @param flvfile
* @param hdfile
* @param type
* @param streamer
*
* APPEARANCE PARAMETERS:
* @param width
* @param height
* @param skin
* @param image
* @param icons
* @param logo
* @param controlbar
* @param playlist
* @param playlistsize
* @param backcolor
* @param frontcolor
* @param lightcolor
* @param screencolor
*
* BEHAVIOUR PARAMETERS:
* @param autostart 
* @param fullscreen 
* @param volume 
* @param mute 
* @param flvstart 
* @param duration 
* @param flvrepeat 
* @param shuffle 
* @param bufferlength 
* @param quality 
* @param displayclick 
* @param link 
* @param linktarget 
* @param item 
* @param resizing 
* @param stretching 
* @param plugins 
* @param captions 
* 
* METADATA PARAMETERS:
* @param author 
* @param flvdate 
* @param title 
* @param description 
* @param tags 
*
* ADVANCED PARAMETERS:
* @param configxml 
* @param version 
* @param flversion 
* @param client 
* @param tracecall 
* @param flvid 
* @param abouttext 
* @param aboutlink 
* 
*/

/**    Copyright (C) 2009  Matt Bury
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once ('moodleform_mod.php');

class mod_flv_mod_form extends moodleform_mod {

	function definition() {

		global $COURSE;
		$mform    =& $this->_form;

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are shown
        $mform->addElement('header', 'general', get_string('general', 'form'));
    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('flvname', 'flv'), array('size'=>'64'));
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', null, 'required', null, 'client');
    /// Adding the optional "intro" and "introformat" pair of fields
    	$mform->addElement('htmleditor', 'intro', get_string('flvintro', 'flv'));
		$mform->setType('intro', PARAM_RAW);
		$mform->addRule('intro', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');

        $mform->addElement('format', 'introformat', get_string('format'));

//--------------------------------------- VIDEO SOURCE ----------------------------------------
	$mform->addElement('header', 'flvsource', get_string('flvsource', 'flv'));
	
//$string['flvfile'] = 'Video URL';
	$mform->addElement('choosecoursefile', 'flvfile', get_string('flvfile', 'flv'), array('courseid'=>$COURSE->id));
	$mform->addRule('flvfile', get_string('required'), 'required', null, 'client');
	$mform->setHelpButton('flvfile', array('flv_videourl', get_string('flvfile', 'flv'), 'flv'));
	
//$string['hdfile'] = 'HD File (requires hd-1 plugin)'; - There's a bug in this so disabled for now.
	//$mform->addElement('choosecoursefile', 'hdfile', get_string('hdfile', 'flv'), array('courseid'=>$COURSE->id));
	//$mform->setHelpButton('hdfile', array('flv_hdfile', get_string('hdfile', 'flv'), 'flv'));
	//$mform->setAdvanced('hdfile');
	
//$string['type'] = 'Type'; // sound, image, video, youtube, camera, http, lighttpd or rtmp
	$mform->addElement('select', 'type', get_string('type', 'flv'), flv_list_type());
	$mform->setDefault('type', 'video');
	$mform->setAdvanced('type');
	$mform->setHelpButton('type', array('flv_videourl', get_string('type', 'flv'), 'flv'));

//$string['streamer'] = 'Streamer';
	$mform->addElement('select', 'streamer', get_string('streamer', 'flv'), flv_list_streamer());
	$mform->setDefault('streamer', '');
	$mform->setAdvanced('streamer');
	$mform->setHelpButton('streamer', array('flv_videourl', get_string('streamer', 'flv'), 'flv'));

////--------------------------------------- APPEARANCE ---------------------------------------
	$mform->addElement('header', 'appearance', get_string('appearance', 'flv'));
	
//$string['notes'] = 'Notes (appear under video)';
    $mform->addElement('htmleditor', 'notes', get_string('notes', 'flv'), array('canUseHtmlEditor'=>'detect','rows'=>30, 'cols'=>65, 'width'=>0,'height'=>0));
	$mform->setType('notes', PARAM_RAW);
	$mform->setHelpButton('notes', array('flv_notes', get_string('notes', 'flv'), 'flv'));
	
//$string['width'] = 'Width';
	$mform->addElement('text', 'width', get_string('width', 'flv'), array('size'=>'4'));
	$mform->addRule('width', get_string('required'), 'required', null, 'client');
	$mform->setDefault('width', '900');

//$string['height'] = 'Height';
	$mform->addElement('text', 'height', get_string('height', 'flv'), array('size'=>'4'));
	$mform->addRule('height', get_string('required'), 'required', null, 'client');
	$mform->setDefault('height', '480');

//$string['skin'] = 'Skin';
	$mform->addElement('select', 'skin', get_string('skin', 'flv'), flv_list_skins());
	$mform->setHelpButton('skin', array('flv_skins', get_string('skin', 'flv'), 'flv'));
	$mform->setDefault('skin', '');

//$string['image'] = 'Image';
	$mform->addElement('choosecoursefile', 'image', get_string('image', 'flv'), array('courseid'=>$COURSE->id));
	$mform->setHelpButton('image', array('flv_image', get_string('image', 'flv'), 'flv'));

//$string['icons'] = 'Icons';
	$mform->addElement('select', 'icons', get_string('icons', 'flv'), flv_list_truefalse());

//$string['logo'] = 'Logo';
	$mform->addElement('choosecoursefile', 'logo', get_string('logo', 'flv'), array('courseid'=>$COURSE->id));
	$mform->setHelpButton('logo', array('flv_logo', get_string('logo', 'flv'), 'flv'));
	$mform->setAdvanced('logo');

//$string['logolink'] = 'Logo link';
	$mform->addElement('choosecoursefile', 'logolink', get_string('logolink', 'flv'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('logolink');

//$string['controlbar'] = 'Control Bar';
	$mform->addElement('select', 'controlbar', get_string('controlbar', 'flv'), flv_list_controlbar());
	$mform->setDefault('controlbar', 'bottom');
	
//$string['playlist'] = 'Play List (position)';
	$mform->addElement('select', 'playlist', get_string('playlist', 'flv'), flv_list_playlistposition());
	$mform->setDefault('playlist', 'none');
	$mform->setAdvanced('playlist');
	$mform->setHelpButton('playlist', array('flv_playlist', get_string('playlist', 'flv'), 'flv'));

//$string['playlistsize'] = 'Play List Size (pixels)';
	$mform->addElement('text', 'playlistsize', get_string('playlistsize', 'flv'), array('size'=>'4'));
	$mform->setDefault('playlistsize', '180');
	$mform->setAdvanced('playlistsize');
	$mform->setHelpButton('playlistsize', array('flv_playlist', get_string('playlistsize', 'flv'), 'flv'));

//$string['backcolor'] = 'Back Color';
	$mform->addElement('text', 'backcolor', get_string('backcolor', 'flv'), array('size'=>'6'));
	$mform->setDefault('backcolor', 'ffffff');
	$mform->setAdvanced('backcolor');
	$mform->setHelpButton('backcolor', array('flv_colors', get_string('backcolor', 'flv'), 'flv'));
	
//$string['frontcolor'] = 'Front Color';
	$mform->addElement('text', 'frontcolor', get_string('frontcolor', 'flv'), array('size'=>'6'));
	$mform->setDefault('frontcolor', '555555');
	$mform->setAdvanced('frontcolor');
	$mform->setHelpButton('frontcolor', array('flv_colors', get_string('frontcolor', 'flv'), 'flv'));
	
//$string['lightcolor'] = 'Light Color';
	$mform->addElement('text', 'lightcolor', get_string('lightcolor', 'flv'), array('size'=>'6'));
	$mform->setDefault('lightcolor', '000000');
	$mform->setAdvanced('lightcolor');
	$mform->setHelpButton('lightcolor', array('flv_colors', get_string('lightcolor', 'flv'), 'flv'));
	
//$string['screencolor'] = 'Screen Color';
	$mform->addElement('text', 'screencolor', get_string('screencolor', 'flv'), array('size'=>'6'));
	$mform->setDefault('screencolor', '000000');
	$mform->setAdvanced('screencolor');
	$mform->setHelpButton('screencolor', array('flv_colors', get_string('screencolor', 'flv'), 'flv'));
	
////--------------------------------------- BEHAVIOUR ---------------------------------------
	$mform->addElement('header', 'behaviour', get_string('behaviour', 'flv'));

//$string['autostart'] = 'Auto Start';
	$mform->addElement('select', 'autostart', get_string('autostart', 'flv'), flv_list_truefalse());
	$mform->setDefault('autostart', 'false');
	
//$string['fullscreen'] = 'Allow Full Screen';
	$mform->addElement('select', 'fullscreen', get_string('fullscreen', 'flv'), flv_list_truefalse());
	$mform->setDefault('fullscreen', 'true');
	
//$string['stretching'] = 'Stretching';
	$mform->addElement('select', 'stretching', get_string('stretching', 'flv'), flv_list_stretching());
	$mform->setDefault('stretching', 'uniform');

//$string['volume'] = 'Volume';
	$mform->addElement('select', 'volume', get_string('volume', 'flv'), flv_list_volume());
	$mform->setDefault('volume', '90');

//$string['mute'] = 'Mute';
	$mform->addElement('select', 'mute', get_string('mute', 'flv'), flv_list_truefalse());
	$mform->setDefault('mute', 'false');

//$string['repeat'] = 'Repeat';
	$mform->addElement('select', 'flvrepeat', get_string('flvrepeat', 'flv'), flv_list_repeat());
	$mform->setDefault('flvrepeat', 'none');

//$string['item'] = 'Item';
	$mform->addElement('text', 'item', get_string('item', 'flv'), array('size'=>'4'));
	$mform->setDefault('item', '');
	$mform->setAdvanced('item');
	
//$string['shuffle'] = 'Shuffle';
	$mform->addElement('select', 'shuffle', get_string('shuffle', 'flv'), flv_list_truefalse());
	$mform->setDefault('shuffle', 'false');
	$mform->setAdvanced('shuffle');
	
//$string['flvstart'] = 'Start (position in seconds)';
	$mform->addElement('text', 'flvstart', get_string('flvstart', 'flv'), array('size'=>'4'));
	$mform->setDefault('flvstart', '0');
	$mform->setAdvanced('flvstart');
	
//$string['duration'] = 'Duration (seconds)';
	//$mform->addElement('text', 'duration', get_string('duration', 'flv'), array('size'=>'4'));
	//$mform->setDefault('duration', '');
	//$mform->setAdvanced('duration');
	
//$string['bufferlength'] = 'Buffer Length (seconds)';
	$mform->addElement('select', 'bufferlength', get_string('bufferlength', 'flv'), flv_list_bufferlength());
	$mform->setDefault('bufferlength', '1');
	$mform->setAdvanced('bufferlength');
	
//$string['quality'] = 'Quality';
	$mform->addElement('select', 'quality', get_string('quality', 'flv'), flv_list_quality());
	$mform->setAdvanced('quality');
	
//$string['displayclick'] = 'Display Click';
	$mform->addElement('select', 'displayclick', get_string('displayclick', 'flv'), flv_list_displayclick());
	$mform->setDefault('displayclick', 'play');
	$mform->setAdvanced('displayclick');
	
//$string['link'] = 'Link';
	$mform->addElement('text', 'link', get_string('link', 'flv'), array('size'=>'64'));
	$mform->setAdvanced('link');
	
//$string['linktarget'] = 'Link Target';
	$mform->addElement('select', 'linktarget', get_string('linktarget', 'flv'), flv_list_linktarget());
	$mform->setDefault('linktarget', '_blank');
	$mform->setAdvanced('linktarget');
	
//$string['resizing'] = 'Resizing';
	$mform->addElement('select', 'resizing', get_string('resizing', 'flv'), flv_list_truefalse());
	$mform->setAdvanced('resizing');
	
//$string['plugins'] = 'Plugins';
	$flv_plugins_att = 'wrap="virtual" rows="3" cols="57"';
	$mform->addElement('textarea', 'plugins', get_string('plugins', 'flv'), $flv_plugins_att);
	$mform->setDefault('plugins', '');
	$mform->setAdvanced('plugins');
	
//$string['captions'] = 'Timed Text Captions';
	$mform->addElement('choosecoursefile', 'captions', get_string('captions', 'flv'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('captions');
	
////--------------------------------------- METADATA ---------------------------------------
	$mform->addElement('header', 'metadata', get_string('metadata', 'flv'));

//$string['author'] = 'Author';
	$mform->addElement('text', 'author', get_string('author', 'flv'), array('size'=>'64'));

//$string['flvdate'] = 'Date';
	$mform->addElement('text', 'flvdate', get_string('flvdate', 'flv'), array('size'=>'64'));

//$string['title'] = 'Title';
	$mform->addElement('text', 'title', get_string('title', 'flv'), array('size'=>'64'));

//$string['description'] = 'FLV Description';
	$mform->addElement('text', 'description', get_string('description', 'flv'), array('size'=>'64'));

//$string['tags'] = 'Tags';
	$mform->addElement('text', 'tags', get_string('tags', 'flv'), array('size'=>'64'));
	$mform->setAdvanced('tags');

////--------------------------------------- ADVANCED ---------------------------------------
	$mform->addElement('header', 'advanced', get_string('advanced', 'flv'));
	
//$string['configxml'] = 'Config XML File';
	$mform->addElement('choosecoursefile', 'configxml', get_string('configxml', 'flv'), array('courseid'=>$COURSE->id));
	$mform->setAdvanced('configxml');
	
//$string['version'] = 'JW Player Version';
	$mform->addElement('text', 'version', get_string('version', 'flv'), array('size'=>'9'));
	$mform->setDefault('version', '5.0');
	$mform->setAdvanced('version');
	
//$string['fpversion'] = 'Flash Player Version';
	$mform->addElement('text', 'fpversion', get_string('fpversion', 'flv'), array('size'=>'9'));
	$mform->setDefault('fpversion', '9.0.115');
	$mform->addRule('fpversion', get_string('required'), 'required', null, 'client');
	$mform->setAdvanced('fpversion');
	
//$string['client'] = 'Client';
	$mform->addElement('text', 'client', get_string('client', 'flv'), array('size'=>'64'));
	$mform->setAdvanced('client');
	
//$string['tracecall'] = 'Trace Call (debugging)';
	$mform->addElement('text', 'tracecall', get_string('tracecall', 'flv'), array('size'=>'64'));
	$mform->setAdvanced('tracecall');
	
//$string['flvid'] = 'ID (Set For Linux Only)';
	$mform->addElement('text', 'flvid', get_string('flvid', 'flv'), array('size'=>'64'));
	$mform->setDefault('flvid', 'jwplayer');
	$mform->setAdvanced('flvid');
	
//$string['licence'] = 'Commercial Licence Required From Jeroen Wijering To Change These';
	$mform->addElement('header', 'licence', get_string('licence', 'flv'));
	$mform->setHelpButton('licence', array('flv_licence', get_string('licence', 'flv'), 'flv'));
	
//$string['abouttext'] = 'About Text';
	$flv_abouttext_att = 'wrap="virtual" rows="3" cols="57"';
	$mform->addElement('textarea', 'abouttext', get_string("abouttext", "flv"), $flv_abouttext_att);
	$mform->setAdvanced('abouttext');
	
//$string['aboutlink'] = 'About Link';
	$mform->addElement('text', 'aboutlink', get_string('aboutlink', 'flv'), array('size'=>'64'));
	$mform->setDefault('aboutlink', 'http://www.longtailvideo.com/players/');
	$mform->setAdvanced('aboutlink');
	
//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
		$this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

	}
}

?>
