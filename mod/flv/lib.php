<?php  // $Id: lib.php,v 0.2 2009/02/21 matbury Exp $
/**
* Library of functions and constants for module flv
* For more information on the parameters used by JW FLV Player see documentation: http://developer.longtailvideo.com/trac/wiki/FlashVars
* 
* @author Matt Bury - matbury@gmail.com - http://matbury.com/
* @version $Id: index.php,v 0.2 2009/02/21 matbury Exp $
* @licence http://www.gnu.org/copyleft/gpl.html GNU Public Licence
* @package flv
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

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted flv record
 **/
function flv_add_instance($flv)
{
    
    $flv->timecreated = time();

    # May have to add extra stuff in here #
	
	return insert_record('flv', $flv);
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function flv_update_instance($flv)
{

    $flv->timemodified = time();
    $flv->id = $flv->instance;
	
	# May have to add extra stuff in here #
		
    return update_record("flv", $flv);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function flv_delete_instance($id)
{

    if (! $flv = get_record("flv", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("flv", "id", "$flv->id")) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function flv_user_outline($course, $user, $mod, $flv)
{
    return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function flv_user_complete($course, $user, $mod, $flv)
{
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in flv activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function flv_print_recent_activity($course, $isteacher, $timestart)
{
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function flv_cron()
{
    global $CFG;

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $flvid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function flv_grades($flvid)
{
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of flv. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $flvid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function flv_get_participants($flvid)
{
    return false;
}

/**
 * This function returns if a scale is being used by one flv
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $flvid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function flv_scale_used ($flvid,$scaleid)
{
    $return = false;

    //$rec = get_record("flv","id","$flvid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

/**
 * Checks if scale is being used by any instance of flv.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any flv
 */
function flv_scale_used_anywhere($scaleid)
{
    if ($scaleid and record_exists('flv', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function flv_install()
{
     return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function flv_uninstall()
{
    return true;
}

/*
-------------------------------------------------------------------- view.php --------------------------------------------------------------------
*/

/**
* Set moodledata path in $flv object
*
* @param $flv
* @return $flv
*/
function flv_set_moodledata($flv)
{
	global $CFG;
	global $COURSE;
	
	$flv->moodledata = $CFG->wwwroot.'/file.php/'.$COURSE->id.'/';
	
	return $flv;
}

/**
* Assign the correct path to the file parameter (media source) in $flv object
*
* @param $flv
* @return $flv
*/
function flv_set_type($flv)
{
	switch($flv->type) {
		
		// video, sound, image and xml (SMIL playlists) are all served from moodledata course directories
		case 'video':
		$flv->prefix = $flv->moodledata;
		$flv->test_variable = 'case video';
		break;
		
		case 'sound':
		$flv->prefix = $flv->moodledata;
		$flv->test_variable = 'case sound';
		break;
		
		case 'image':
		$flv->prefix = $flv->moodledata;
		$flv->test_variable = 'case image';
		break;
		
		case 'xml':
		$flv->type = ''; // JW FLV Player doesn't recognise 'xml' as a valid parameter
		$flv->prefix = $flv->moodledata;
		$flv->test_variable = 'case playlist';
		break;
		
		
		case 'youtube':
		$flv->prefix = '';
		$flv->test_variable = 'case youtube';
		break;
		
		case 'url':
		$flv->type = ''; // JW FLV Player doesn't recognise 'url' as a valid parameter
		$flv->prefix = '';
		$flv->test_variable = 'case url';
		break;
		
		case 'http':
		$flv->prefix = '';
		$flv->test_variable = 'case http';
		break;
		
		case 'lighttpd':
		$flv->prefix = '';
		$flv->test_variable = 'case lighttpd';
		break;
		
		case 'rtmp':
		$flv->prefix = '';
		$flv->test_variable = 'case rtmp';
		break;
		
		default;
		$flv->type = ''; // Prevent failures due to errant parameters getting passed in
		$flv->prefix = '';
		$flv->test_variable = 'default';
	}
	return $flv;
}

/**
* Assign the correct path to the file parameter (media source) in $flv object
*
* @param $flv
* @return $flv
*/
function flv_set_paths($flv)
{
	global $CFG;
	
	// Set set roots
	$flv->wwwroot = $CFG->wwwroot;
	
	// Only need to call time() function once
	$flv_time = time();
	
	// Check for captions XML URL
	if($flv->captions == '')
	{
		// SWFObject
		$flv->captions_js = '';
		// myAlternativeContent
		$flv->captions_body = '';
	} else {
		// SWFObject
		$flv->captions_js = 'flashvars.captions.file = "'.$flv->moodledata.$flv->captions.'";';
		// myAlternativeContent
		$flv->captions_body = '&amp;captions.file='.$flv->moodledata.$flv->captions;
		 // add captions plugin parameter so there's no need to add it on the mod form
		if($flv->plugins == '')
		{
			$flv->plugins = 'captions';
		} else {
			$flv->plugins = 'captions,'.$flv->plugins;
		}
	}
	
	// Check for configuration XML file URL
	if($flv->configxml == '')
	{
		// SWFObject
		$flv->configxml_js = '';
		// myAlternativeContent
		$flv->configxml_body = '';
	} else {
		// SWFObject
		$flv->configxml_js = 'flashvars.configxml = "'.$flv->moodledata.$flv->configxml.'?'.$flv_time.'";';
		// myAlternativeContent
		$flv->configxml_body = 'configxml='.$flv->moodledata.$flv->configxml.'?'.$flv_time;
	}
	
	// Check for HD Video content URL - HD not working yet!
	if($flv->hdfile == '')
	{
		// SWFObject
		$flv->hdfile_js = '';
		// myAlternativeContent
		$flv->hdfile_body = '';
	} else {
		// SWFObject
		$flv->hdfile_js = 'flashvars.hd.file = "'.$flv->prefix.$flv->hdfile.'?'.$flv_time.'";';
		// myAlternativeContent
		$flv->hdfile_body = '&amp;hd.file='.$flv->prefix.$flv->hdfile.'?'.$flv_time;
	}
	
	// Check for poster image URL 
	if($flv->image == '')
	{
		// SWFObject
		$flv->image_js = '';
		// myAlternativeContent
		$flv->image_body = '';
	} else {
		// SWFObject
		$flv->image_js = 'flashvars.image = "'.$flv->moodledata.$flv->image.'";';
		// myAlternativeContent
		$flv->image_body = '&amp;image='.$flv->moodledata.$flv->image;
	}
	
	// Check for link URL 
	if($flv->link == '')
	{
		// SWFObject
		$flv->link_js = '';
		$flv->linktarget_js = '';
		// myAlternativeContent
		$flv->link_body = '';
		$flv->linktarget_body = '';
	} else {
		// SWFObject
		$flv->link_js = 'flashvars.link = "'.$flv->link.'";';
		$flv->linktarget_js = 'flashvars.linktarget = "'.$flv->linktarget.'";';
		// myAlternativeContent
		$flv->link_body = '&amp;link='.$flv->link;
		$flv->linktarget_body = '&amp;linktarget='.$flv->linktarget;
	}
	
	// Check for logo URL
	if($flv->logo == '')
	{
		// SWFObject
		$flv->logo_js = '';
		$flv->logolink_js = '';
		// myAlternativeContent
		$flv->logo_body = '';
		$flv->logolink_body = '';
	} else {
		// SWFObject
		$flv->logo_js = 'flashvars.logo.file = "'.$flv->moodledata.$flv->logo.'?'.$flv_time.'";';
		$flv->logolink_js = 'flashvars.logo.link = "'.$flv->moodledata.$flv->logolink.'?'.$flv_time.'";'; // Can only link to files in course files directory
		// myAlternativeContent
		$flv->logo_body = '&amp;logo.file='.$flv->moodledata.$flv->logo.'?'.$flv_time;
		$flv->logolink_body = '&amp;logo.link='.$flv->moodledata.$flv->logolink.'?'.$flv_time; // Can only link to files in course files directory
	}
	
	// Check for JW FLV Player skin URL
	if($flv->skin == '')
	{
		// SWFObject
		$flv->skin_js = '';
		// myAlternativeContent
		$flv->skin_body = '';
	} else {
		// SWFObject
		$flv->skin_js = 'flashvars.skin = "'.$flv->wwwroot.'/mod/flv/skins/'.$flv->skin.'";';
		// myAlternativeContent
		$flv->skin_body = '&amp;skin='.$flv->wwwroot.'/mod/flv/skins/'.$flv->skin;
	}
	
	return $flv;
}

/**
* Print alternative FlashVars embed parameters
*
* @param $flv
* @return string
*/
function flv_print_body_flashvars($flv)
{
	$flv_flashvars = '<param name="flashvars" value="'.$flv->configxml_body.
				'&amp;abouttext='.$flv->abouttext.
				'&amp;aboutlink='.$flv->aboutlink.
				'&amp;author='.$flv->author.
				'&amp;autostart='.$flv->autostart.
				'&amp;backcolor='.$flv->backcolor.
				'&amp;bufferlength='.$flv->bufferlength.
				''.$flv->captions_body.
				'&amp;client='.$flv->client.
				'&amp;controlbar='.$flv->controlbar.
				'&amp;date='.$flv->flvdate.
				'&amp;description='.$flv->description.
				'&amp;displayclick='.$flv->displayclick.
				'&amp;file='.$flv->prefix.$flv->flvfile.
				'&amp;frontcolor='.$flv->frontcolor.
				''.$flv->hdfile_body.
				'&amp;icons='.$flv->icons.
				'&amp;id='.$flv->flvid.
				'&amp;item='.$flv->item.
				''.$flv->image_body.
				'&amp;lightcolor='.$flv->lightcolor.
				''.$flv->link_body.
				''.$flv->linktarget_body.
				''.$flv->logo_body.
				''.$flv->logolink_body.
				'&amp;mute='.$flv->mute.
				'&amp;playlist='.$flv->playlist.
				'&amp;playlistsize='.$flv->playlistsize.
				'&amp;plugins='.$flv->plugins.
				'&amp;quality='.$flv->quality.
				'&amp;repeat='.$flv->flvrepeat.
				'&amp;resizing='.$flv->resizing.
				'&amp;screencolor='.$flv->screencolor.
				'&amp;shuffle='.$flv->shuffle.
				''.$flv->skin_body.
				'&amp;start='.$flv->flvstart.
				'&amp;state='.$flv->state.
				'&amp;streamer='.$flv->streamer.
				'&amp;stretching='.$flv->stretching.
				'&amp;tags='.$flv->tags.
				'&amp;title='.$flv->title.
				'&amp;tracecall='.$flv->tracecall.
				'&amp;type='.$flv->type.
				'&amp;version='.$flv->version.
				'&amp;volume='.$flv->volume.'" />';
	return $flv_flashvars;
}

/**
* Construct Javascript flvObject embed code for <head> section of view.php
* Please note: some URLs append a '?'.time(); query to prevent browser caching
*
* @param $flv (mdl_flv DB record for current flv module instance)
* @return string
*/
function flv_print_header_js($flv)
{
	// Build URL to moodledata directory
	$flv = flv_set_moodledata($flv);
	
	// Assign the correct path to the file parameter (media source)
	$flv = flv_set_type($flv);
	
	// Build URLs for FlashVars embed parameters
	$flv = flv_set_paths($flv);
	
	// Build Javascript code for view.php print_header() function
	$flv_header_js = '<script type="text/javascript" src="'.$flv->wwwroot.'/mod/flv/swfobject/swfobject.js"></script>
		<script type="text/javascript">
			var flashvars = {};
			flashvars.abouttext = "'.$flv->abouttext.'";
			flashvars.aboutlink = "'.$flv->aboutlink.'";
			flashvars.author = "'.$flv->author.'";
			flashvars.autostart = "'.$flv->autostart.'";
			flashvars.backcolor = "'.$flv->backcolor.'";
			flashvars.bufferlength = "'.$flv->bufferlength.'";
			'.$flv->captions_js.'
			flashvars.client = "'.$flv->client.'";
			'.$flv->configxml_js.'
			flashvars.controlbar = "'.$flv->controlbar.'";
			flashvars.description = "'.$flv->description.'";
			flashvars.date = "'.$flv->flvdate.'";
			flashvars.displayclick = "'.$flv->displayclick.'";
			flashvars.file = "'.$flv->prefix.$flv->flvfile.'";
			flashvars.frontcolor = "'.$flv->frontcolor.'";
			'.$flv->hdfile_js.'
			flashvars.icons = "'.$flv->icons.'";
			flashvars.id = "'.$flv->flvid.'";
			'.$flv->image_js.'
			flashvars.item = "'.$flv->item.'";
			flashvars.lightcolor = "'.$flv->lightcolor.'";
			'.$flv->link_js.'
			'.$flv->linktarget_js.'
			'.$flv->logo_js.'
			'.$flv->logolink_js.'
			flashvars.mute = "'.$flv->mute.'";
			flashvars.playlist = "'.$flv->playlist.'";
			flashvars.playlistsize = "'.$flv->playlistsize.'";
			flashvars.plugins = "'.$flv->plugins.'";
			flashvars.quality = "'.$flv->quality.'";
			flashvars.repeat = "'.$flv->flvrepeat.'";
			flashvars.resizing = "'.$flv->resizing.'";
			flashvars.screencolor = "'.$flv->screencolor.'";
			flashvars.shuffle = "'.$flv->shuffle.'";
			'.$flv->skin_js.'
			flashvars.start = "'.$flv->flvstart.'";
			flashvars.state = "'.$flv->state.'";
			flashvars.streamer = "'.$flv->streamer.'";
			flashvars.stretching = "'.$flv->stretching.'";
			flashvars.tags = "'.$flv->tags.'";
			flashvars.title = "'.$flv->title.'";
			flashvars.tracecall = "'.$flv->tracecall.'";
			flashvars.type = "'.$flv->type.'";
			flashvars.version = "'.$flv->version.'";
			flashvars.volume = "'.$flv->volume.'";
			
			var params = {};
			params.play = "true";
			params.loop = "true";
			params.menu = "true";
			params.quality = "best";
			params.scale = "noscale";
			params.salign = "tl";
			params.wmode = "opaque";
			params.bgcolor = "";
			params.devicefont = "true";
			params.seamlesstabbing = "true";
			params.allowfullscreen = "'.$flv->fullscreen.'";
			params.allowscriptaccess = "always";
			params.allownetworking = "all";
			var attributes = {};
			attributes.align = "middle";
			swfobject.embedSWF("'.$flv->wwwroot.'/mod/flv/jw/player.swf", "myAlternativeContent", "'.$flv->width.'", "'.$flv->height.'", "'.$flv->fpversion.'", "'.$flv->wwwroot.'/mod/flv/swfobject/expressInstall.swf", flashvars, params, attributes);
		</script>';
	
	//return ''; // uncomment this line to test alternative embed code
	return $flv_header_js;
}

/**
* Construct Javascript flvObject embed code for <body> section of view.php
* Please note: some URLs append a '?'.time(); query to prevent browser caching
*
* @param $flv (mdl_flv DB record for current flv module instance)
* @return string
*/
function flv_print_body($flv)
{
	//
	$flv_body_flashvars = flv_print_body_flashvars($flv);
	
	$flv_body = '<div align="center">
		<div id="myAlternativeContent">
			<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$flv->width.'" height="'.$flv->height.'" id="myFlashContent" align="middle">
				<param name="movie" value="jw/player.swf" />
				<param name="play" value="true" />
				<param name="loop" value="true" />
				<param name="menu" value="true" />
				<param name="quality" value="best" />
				<param name="scale" value="noscale" />
				<param name="salign" value="tl" />
				<param name="wmode" value="opaque" />
				<param name="bgcolor" value="" />
				<param name="devicefont" value="true" />
				<param name="seamlesstabbing" value="true" />
				<param name="allowfullscreen" value="'.$flv->fullscreen.'" />
				<param name="allowscriptaccess" value="sameDomain" />
				<param name="allownetworking" value="all" />
				'.$flv_body_flashvars.'
				<!--[if !IE]>-->
				<object type="application/x-shockwave-flash" data="jw/player.swf" width="'.$flv->width.'" height="'.$flv->height.'" align="middle">
					<param name="play" value="true" />
					<param name="loop" value="true" />
					<param name="menu" value="true" />
					<param name="quality" value="best" />
					<param name="scale" value="noscale" />
					<param name="salign" value="tl" />
					<param name="wmode" value="opaque" />
					<param name="bgcolor" value="" />
					<param name="devicefont" value="true" />
					<param name="seamlesstabbing" value="true" />
					<param name="allowfullscreen" value="'.$flv->fullscreen.'" />
					<param name="allowscriptaccess" value="sameDomain" />
					<param name="allownetworking" value="all" />
					'.$flv_body_flashvars.'
				<!--<![endif]-->
					<div align="center">
  						<p><strong><a href="http://longtailvideo.com/" target="_blank">JW FLV Player 5.0</a> requires <a href="http://www.adobe.com/products/flashplayer/">Flash Player 9.0.115</a> or above installed to function correctly.</strong></p>
  						<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" border="0" /></a></p>
					</div>
				<!--[if !IE]>-->
				</object>
				<!--<![endif]-->
			</object>
		</div>
	</div><div><p>'.$flv->notes.'</div><br/>';
	
	// For testing
	//$flv_body .= '$flv->test_variable = '.$flv->test_variable.'<br/>$flv->prefix = '.$flv->prefix.'<br/>$flv->flvfile = '.$flv->flvfile.print_object($flv);
	
	// Uncomment the next line to test SWFObject embed code
	//return '<div align="center"><div id="myAlternativeContent"></div><div>';
	return $flv_body;
}

/*
---------------------------------------- mod_form.php ----------------------------------------
*/

/**
* true/false options
* @return array
*/
function flv_list_truefalse()
{
	return array('true' => 'true',
				'false' => 'false');
}

/**
* true/false options
* @return array
*/
function flv_list_quality()
{
	return array('best' => 'best',
				'high' => 'high',
				'medium' => 'medium',
				'autohigh' => 'autohigh',
				'autolow' => 'autolow',
				'low' => 'low');
}

/**
* Define target of link when user clicks on 'link' button
* @return array
*/
function flv_list_linktarget()
{
	return array('_blank' => 'new window',
				'_self' => 'same page',
				'none' => 'none');
}

/**
* Define target of link when user clicks on 'link' button (not yet implemented)
* Plugins add things like accessibility, analytics, HD video quality toggle switches, etc.
* There's a list of plugins that use this parameter at: http://www.longtailvideo.com/addons/plugins
* @return array
*/
/*function flv_list_plugins() {
	return array('' => 'none',
				'accessibility-1' => 'XML Captions');
}*/

/**
* Define type of media to serve
* @return array
*/
function flv_list_type()
{
	return array('video' => 'Video',
				'youtube' => 'YouTube',
				'url' => 'Full URL',
				'xml' => 'XML Playlist',
				'sound' => 'Sound',
				'image' => 'Image',
				'http' => 'HTTP (pseudo) Streaming',
				'lighttpd' => 'Lighttpd Streaming',
				'rtmp' => 'RTMP Streaming');
}

/**
* HTTP streaming (Xmoov-php) not yet working!
* 
* For Lighttpd streaming or RTMP (Flash Media Server or Red5),
* enter the path to the gateway in the corresponding empty quotes
* and uncomment the appropriate lines
* e.g. 'path/to/your/gateway.jsp' => 'RTMP');
*
* For RTMP streaming, uncomment and edit this line: //, 'rtmp://yourstreamingserver.com/yourmediadirectory' => 'RTMP'
* to reflect your streaming server's details. It's probably a good idea to change the 'RTMP' bit to the name of your streaming service,
* i.e. 'My Media Server' or 'Acme Media Server'.
* Remember not to include the ".flv" file extensions in video file names when using RTMP.
* @return array
*/
function flv_list_streamer()
{
	global $CFG;
	return array('' => 'none'
				 //, $CFG->wwwroot.'/mod/flv/xmoov/xmoov.php' => 'Xmoov-php (http)'
				 //, 'lighttpd' => 'Lighttpd'
				 //, 'rtmp://yourstreamingserver.com/yourmediadirectory' => 'RTMP'
				 );
}

/**
* Define position of player control bar
* @return array
*/
function flv_list_controlbar()
{
	return array('bottom' => 'bottom',
				'over' => 'over',
				'none' => 'none');
}

/**
* Define position of playlist
* @return array
*/
function flv_list_playlistposition()
{
	return array('bottom' => 'bottom',
				'right' => 'right',
				'over' => 'over',
				'none' => 'none');
}

/**
* Skins define the general appearance of the JW FLV Player
* Skins can be downloaded from: http://www.longtailvideo.com/addons/skins
* Skins (the .swf file only) are kept in /mod/flv/skins/
* New skins must be added to the array below manually for them to show up on the mod_form.php list.
* Copy and paste the following line into the array below then edit it to match the name and filename of your new skin:
				'filename.swf' => 'Name',
* I find alphabetical order works best ;)
* @return array
*/
function flv_list_skins()
{
	return array('' => '',
				'beelden/beelden.xml' => 'Beelden XML Skin',
				'3dpixelstyle.swf' => '3D Pixel Style',
				'atomicred.swf' => 'Atomic Red',
				'bekle.swf' => 'Bekle',
				'bluemetal.swf' => 'Blue Metal',
				'comet.swf' => 'Comet',
				'controlpanel.swf' => 'Control Panel',
				'dangdang.swf' => 'Dangdang',
				'fashion.swf' => 'Fashion',
				'festival.swf' => 'Festival',
				'grungetape.swf' => 'Grunge Tape',
				'icecreamsneaka.swf' => 'Ice Cream Sneaka',
				'kleur.swf' => 'Kleur',
				'magma.swf' => 'Magama',
				'metarby10.swf' => 'Metarby 10',
				'modieus.swf' => 'Modieus',
				'nacht.swf' => 'Nacht',
				'neon.swf' => 'Neon',
				'pearlized.swf' => 'Pearlized',
				'pixelize.swf' => 'Pixelize',
				'playcasso.swf' => 'Playcasso',
				'silverywhite.swf' => 'Silvery White',
				'simple.swf' => 'Simple',
				'snel.swf' => 'Snel',
				'stijl.swf' => 'Stijl',
				'stylish_slim.swf' => 'Stylish Slim',
				'traganja.swf' => 'Traganja');
}

/**
* Define number of seconds of video stream to buffer before playing
* Longer buffer lengths can be given if a lot of users have particularly slow Internet connections
* @return array
*/
function flv_list_bufferlength()
{
	return array('0' => '0',
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
				'6' => '6',
				'7' => '7',
				'8' => '8',
				'9' => '9',
				'10' => '10',
				'11' => '11',
				'12' => '12',
				'13' => '13',
				'14' => '14',
				'15' => '15',
				'16' => '16',
				'17' => '17',
				'18' => '18',
				'19' => '19',
				'20' => '20',
				'21' => '21',
				'22' => '22',
				'23' => '23',
				'24' => '24',
				'25' => '25',
				'26' => '26',
				'27' => '27',
				'28' => '28',
				'29' => '29',
				'30' => '30');
}

/**
* Define action when user clicks on video
* @return array
*/
function flv_list_displayclick()
{
	return array('play' => 'play',
				'link' => 'link',
				'fullscreen' => 'fullscreen',
				'none' => 'none',
				'mute' => 'mute',
				'next' => 'next');
}

/**
* Define playlist repeat behaviour
* @return array
*/
function flv_list_repeat()
{
	return array('none' => 'none',
				'always' => 'always',
				'single' => 'single');
}

/**
* Define scaling properties of video stream
* i.e. the way the video adjusts its dimensions to fit the FLV player window
* @return array
*/
function flv_list_stretching()
{
	return array('uniform' => 'uniform',
				'exactfit' => 'exactfit',
				'fill' => 'fill');
}

/**
* Define default playback volume
* @return array
*/
function flv_list_volume()
{
	return array('0' => '0',
				'5' => '5',
				'10' => '10',
				'15' => '15',
				'20' => '20',
				'25' => '25',
				'30' => '30',
				'35' => '35',
				'40' => '40',
				'45' => '45',
				'50' => '50',
				'55' => '55',
				'60' => '60',
				'65' => '65',
				'70' => '70',
				'75' => '75',
				'80' => '80',
				'85' => '85',
				'90' => '90',
				'95' => '95',
				'100' => '100');
}
/// End of mod/flv/lib.php

?>