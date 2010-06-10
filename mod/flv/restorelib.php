<?php //$Id: restorelib.php,v 1.13 2006/09/18 09:13:04 moodler Exp $
    //This php script contains all the stuff to backup/restore
    //flv mods

    //This is the "graphical" structure of the flv mod:   
    //
    //                       flv 
    //                    (CL,pk->id)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function flv_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug
          
            //Now, build the FLV record structure
            $flv->course = $restore->course_id;
            $flv->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
			$flv->intro = backup_todb($info['MOD']['#']['INTRO']['0']['#']);
			$flv->introformat = backup_todb($info['MOD']['#']['INTROFORMAT']['0']['#']);
			$flv->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
			$flv->timemodified = $info['MOD']['#']['TIMEMODIFIED']['0']['#'];
			$flv->configxml = backup_todb($info['MOD']['#']['CONFIGXML']['0']['#']);
			$flv->author = backup_todb($info['MOD']['#']['AUTHOR']['0']['#']);
			$flv->flvdate = backup_todb($info['MOD']['#']['FLVDATE']['0']['#']);
			$flv->description = backup_todb($info['MOD']['#']['DESCRIPTION']['0']['#']);
			$flv->duration = backup_todb($info['MOD']['#']['DURATION']['0']['#']);
			$flv->flvfile = backup_todb($info['MOD']['#']['FLVFILE']['0']['#']);
			$flv->hdfile = backup_todb($info['MOD']['#']['HDFILE']['0']['#']);
			$flv->image = backup_todb($info['MOD']['#']['IMAGE']['0']['#']);
			$flv->link = backup_todb($info['MOD']['#']['LINK']['0']['#']);
			$flv->flvstart = backup_todb($info['MOD']['#']['FLVSTART']['0']['#']);
			$flv->tags = backup_todb($info['MOD']['#']['TAGS']['0']['#']);
			$flv->title = backup_todb($info['MOD']['#']['TITLE']['0']['#']);
			$flv->type = $info['MOD']['#']['TYPE']['0']['#'];
            $flv->backcolor = backup_todb($info['MOD']['#']['BACKCOLOR']['0']['#']);
			$flv->frontcolor = backup_todb($info['MOD']['#']['FRONTCOLOR']['0']['#']);
			$flv->lightcolor = backup_todb($info['MOD']['#']['LIGHTCOLOR']['0']['#']);
			$flv->screencolor = backup_todb($info['MOD']['#']['SCREENCOLOR']['0']['#']);
			$flv->controlbar = backup_todb($info['MOD']['#']['CONTROLBAR']['0']['#']);
			$flv->height = backup_todb($info['MOD']['#']['HEIGHT']['0']['#']);
			$flv->playlist = backup_todb($info['MOD']['#']['PLAYLIST']['0']['#']);
			$flv->playlistsize = backup_todb($info['MOD']['#']['PLAYLISTSIZE']['0']['#']);
			$flv->skin = backup_todb($info['MOD']['#']['SKIN']['0']['#']);
			$flv->width = backup_todb($info['MOD']['#']['WIDTH']['0']['#']);
			$flv->autostart = backup_todb($info['MOD']['#']['AUTOSTART']['0']['#']);
			$flv->bufferlength = backup_todb($info['MOD']['#']['BUFFERLENGTH']['0']['#']);
			$flv->displayclick = backup_todb($info['MOD']['#']['DISPLAYCLICK']['0']['#']);
			$flv->fullscreen = backup_todb($info['MOD']['#']['FULLSCREEN']['0']['#']);
			$flv->icons = backup_todb($info['MOD']['#']['ICONS']['0']['#']);
			$flv->item = backup_todb($info['MOD']['#']['ITEM']['0']['#']);
			$flv->linktarget = backup_todb($info['MOD']['#']['LINKTARGET']['0']['#']);
			$flv->logo = backup_todb($info['MOD']['#']['LOGO']['0']['#']);
			$flv->logolink = backup_todb($info['MOD']['#']['LOGOLINK']['0']['#']);
			$flv->mute = backup_todb($info['MOD']['#']['MUTE']['0']['#']);
			$flv->quality = backup_todb($info['MOD']['#']['QUALITY']['0']['#']);
			$flv->flvrepeat = backup_todb($info['MOD']['#']['FLVREPEAT']['0']['#']);
			$flv->resizing = backup_todb($info['MOD']['#']['RESIZING']['0']['#']);
			$flv->shuffle = backup_todb($info['MOD']['#']['SHUFFLE']['0']['#']);
			$flv->state = backup_todb($info['MOD']['#']['STATE']['0']['#']);
			$flv->stretching = backup_todb($info['MOD']['#']['STRETCHING']['0']['#']);
			$flv->volume = backup_todb($info['MOD']['#']['VOLUME']['0']['#']);
			$flv->abouttext = backup_todb($info['MOD']['#']['ABOUTTEXT']['0']['#']);
			$flv->aboutlink = backup_todb($info['MOD']['#']['ABOUTLINK']['0']['#']);
			$flv->client = backup_todb($info['MOD']['#']['CLIENT']['0']['#']);
			$flv->flvid = backup_todb($info['MOD']['#']['FLVID']['0']['#']);
			$flv->plugins = backup_todb($info['MOD']['#']['PLUGINS']['0']['#']);
			$flv->streamer = backup_todb($info['MOD']['#']['STREAMER']['0']['#']);
			$flv->tracecall = backup_todb($info['MOD']['#']['TRACECALL']['0']['#']);
			$flv->version = backup_todb($info['MOD']['#']['VERSION']['0']['#']);
			$flv->captions = backup_todb($info['MOD']['#']['CAPTIONS']['0']['#']);
			$flv->fpversion = backup_todb($info['MOD']['#']['FPVERSION']['0']['#']);
			$flv->notes = backup_todb($info['MOD']['#']['NOTES']['0']['#']);
            
            //The structure is equal to the db, so insert the flv
            $newid = insert_record ("flv",$flv);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","flv")." \"".format_string(stripslashes($flv->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
   
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

	//This function makes all the necessary calls to xxxx_decode_content_links()
    //function in each module, passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process. It's called from restore_decode_content_links()
    //function in restore process
    function flv_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;
		
		//
		if ($flvs = get_records_sql ("SELECT f.id, f.configxml, f.flvfile, f.hdfile, f.image, f.link, f.skin, f.logo, f.logolink, f.abouttext, f.aboutlink f.streamer, f.captions, f.notes
                                   FROM {$CFG->prefix}flv f
                                   WHERE f.course = $restore->course_id")) {
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($flvs as $flv) {
                //Increment counter
                $i++;
				//
                $configxml = $flv->configxml;
				$flvfile = $flv->flvfile;
				$hdfile = $flv->hdfile;
				$image = $flv->image;
				$link = $flv->link;
				$skin = $flv->skin;
				$logo = $flv->logo;
				$logolink = $flv->logolink;
				$abouttext = $flv->abouttext;
				$aboutlink = $flv->aboutlink;
				$streamer = $flv->streamer;
				$captions = $flv->captions;
				$notes = $flv->notes;
				//
                $r_configxml = restore_decode_content_links_worker($configxml,$restore);
				$r_flvfile = restore_decode_content_links_worker($flvfile,$restore);
				$r_hdfile = restore_decode_content_links_worker($hdfile,$restore);
				$r_image = restore_decode_content_links_worker($image,$restore);
				$r_link = restore_decode_content_links_worker($link,$restore);
				$r_skin = restore_decode_content_links_worker($skin,$restore);
				$r_logo = restore_decode_content_links_worker($logo,$restore);
				$r_logolink = restore_decode_content_links_worker($logolink,$restore);
				$r_abouttext = restore_decode_content_links_worker($abouttext,$restore);
				$r_aboutlink = restore_decode_content_links_worker($aboutlink,$restore);
				$r_streamer = restore_decode_content_links_worker($streamer,$restore);
				$r_captions = restore_decode_content_links_worker($captions,$restore);
				$r_notes = restore_decode_content_links_worker($notes,$restore);
				//
				if ($r_configxml != $configxml || $r_flvfile != $flvfile || $r_hdfile != $hdfile || $r_image != $image || $r_link != $link || $r_skin != $skin || $r_logo != $logo || $r_logolink != $logolink || $r_abouttext != $abouttext || $r_aboutlink != $aboutlink || $r_streamer != $streamer || $r_captions != $captions || $r_notes != $notes) {
                    //Update record
                    $flv->configxml = addslashes($r_configxml);
					$flv->flvfile = addslashes($r_flvfile);
					$flv->hdfile = addslashes($r_hdfile);
					$flv->image = addslashes($r_image);
					$flv->link = addslashes($r_link);
					$flv->skin = addslashes($r_skin);
					$flv->logo = addslashes($r_logo);
					$flv->logolink = addslashes($r_logolink);
					$flv->abouttext = addslashes($r_abouttext);
					$flv->aboutlink = addslashes($r_aboutlink);
					$flv->streamer = addslashes($r_streamer);
					$flv->captions = addslashes($r_captions);
					$flv->notes = addslashes($r_notes);
					//
                    $status = update_record("flv", $flv);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($configxml).'<br />changed to<br />'.s($r_configxml).'<hr /><br />';
							echo '<br /><hr />'.s($flvfile).'<br />changed to<br />'.s($r_flvfile).'<hr /><br />';
							echo '<br /><hr />'.s($hdfile).'<br />changed to<br />'.s($r_hdfile).'<hr /><br />';
							echo '<br /><hr />'.s($image).'<br />changed to<br />'.s($r_image).'<hr /><br />';
							echo '<br /><hr />'.s($link).'<br />changed to<br />'.s($r_link).'<hr /><br />';
							echo '<br /><hr />'.s($skin).'<br />changed to<br />'.s($r_skin).'<hr /><br />';
							echo '<br /><hr />'.s($logo).'<br />changed to<br />'.s($r_logo).'<hr /><br />';
							echo '<br /><hr />'.s($logolink).'<br />changed to<br />'.s($r_logolink).'<hr /><br />';
							echo '<br /><hr />'.s($abouttext).'<br />changed to<br />'.s($r_abouttext).'<hr /><br />';
							echo '<br /><hr />'.s($aboutlink).'<br />changed to<br />'.s($r_aboutlink).'<hr /><br />';
							echo '<br /><hr />'.s($streamer).'<br />changed to<br />'.s($r_streamer).'<hr /><br />';
							echo '<br /><hr />'.s($captions).'<br />changed to<br />'.s($r_captions).'<hr /><br />';
							echo '<br /><hr />'.s($notes).'<br />changed to<br />'.s($r_notes).'<hr /><br />';
                        }
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }
        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function flv_restore_logs($restore,$log) {
                    
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
