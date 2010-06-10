<?php //$Id: backuplib.php,v 1.7 2007/04/22 22:07:03 stronk7 Exp $
    //This php script contains all the stuff to backup/restore
    //flv mods

    //This is the "graphical" structure of the flv mod:
    //
    //                     flv                                      
    //                 (CL,pk->id,files)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the backup procedure about this mod
    function flv_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true; 

        ////Iterate over flv table
        $flvs = get_records ("flv","course",$preferences->backup_course,"id");
        if ($flvs) {
            foreach ($flvs as $flv) {
                if (backup_mod_selected($preferences,'flv',$flv->id)) {
                    $status = flv_backup_one_mod($bf,$preferences,$flv);
                }
            }
        }
        return $status;
    }
   
    function flv_backup_one_mod($bf,$preferences,$flv) {

        global $CFG;
    
        if (is_numeric($flv)) {
            $flv = get_record('flv','id',$flv);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print assignment data
        fwrite ($bf,full_tag("ID",4,false,$flv->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"flv"));
        fwrite ($bf,full_tag("NAME",4,false,$flv->name));
		fwrite ($bf,full_tag("INTRO",4,false,$flv->intro));
        fwrite ($bf,full_tag("INTROFORMAT",4,false,$flv->introformat));
        fwrite ($bf,full_tag("TIMECREATED",4,false,$flv->timecreated));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$flv->timemodified));
        fwrite ($bf,full_tag("CONFIGXML",4,false,$flv->configxml));
        fwrite ($bf,full_tag("AUTHOR",4,false,$flv->author));
        fwrite ($bf,full_tag("FLVDATE",4,false,$flv->flvdate));
        fwrite ($bf,full_tag("DESCRIPTION",4,false,$flv->description));
        fwrite ($bf,full_tag("DURATION",4,false,$flv->duration));
        fwrite ($bf,full_tag("FLVFILE",4,false,$flv->flvfile));
        fwrite ($bf,full_tag("HDFILE",4,false,$flv->hdfile));
        fwrite ($bf,full_tag("IMAGE",4,false,$flv->image));
        fwrite ($bf,full_tag("LINK",4,false,$flv->link));
        fwrite ($bf,full_tag("FLVSTART",4,false,$flv->flvstart));
        fwrite ($bf,full_tag("TAGS",4,false,$flv->tags));
        fwrite ($bf,full_tag("TITLE",4,false,$flv->title));
        fwrite ($bf,full_tag("TYPE",4,false,$flv->type));
        fwrite ($bf,full_tag("BACKCOLOR",4,false,$flv->backcolor));
        fwrite ($bf,full_tag("FRONTCOLOR",4,false,$flv->frontcolor));
        fwrite ($bf,full_tag("LIGHTCOLOR",4,false,$flv->lightcolor));
        fwrite ($bf,full_tag("SCREENCOLOR",4,false,$flv->screencolor));
        fwrite ($bf,full_tag("CONTROLBAR",4,false,$flv->controlbar));
        fwrite ($bf,full_tag("HEIGHT",4,false,$flv->height));
        fwrite ($bf,full_tag("PLAYLIST",4,false,$flv->playlist));
        fwrite ($bf,full_tag("PLAYLISTSIZE",4,false,$flv->playlistsize));
        fwrite ($bf,full_tag("SKIN",4,false,$flv->skin));
        fwrite ($bf,full_tag("WIDTH",4,false,$flv->width));
        fwrite ($bf,full_tag("AUTOSTART",4,false,$flv->autostart));
        fwrite ($bf,full_tag("BUFFERLENGTH",4,false,$flv->bufferlength));
        fwrite ($bf,full_tag("DISPLAYCLICK",4,false,$flv->displayclick));
        fwrite ($bf,full_tag("FULLSCREEN",4,false,$flv->fullscreen));
        fwrite ($bf,full_tag("ICONS",4,false,$flv->icons));
        fwrite ($bf,full_tag("ITEM",4,false,$flv->item));
        fwrite ($bf,full_tag("LINKTARGET",4,false,$flv->linktarget));
        fwrite ($bf,full_tag("LOGO",4,false,$flv->logo));
        fwrite ($bf,full_tag("LOGOLINK",4,false,$flv->logolink));
        fwrite ($bf,full_tag("MUTE",4,false,$flv->mute));
        fwrite ($bf,full_tag("QUALITY",4,false,$flv->quality));
        fwrite ($bf,full_tag("FLVREPEAT",4,false,$flv->flvrepeat));
        fwrite ($bf,full_tag("RESIZING",4,false,$flv->resizing));
        fwrite ($bf,full_tag("SHUFFLE",4,false,$flv->shuffle));
        fwrite ($bf,full_tag("STATE",4,false,$flv->state));
        fwrite ($bf,full_tag("STRETCHING",4,false,$flv->stretching));
        fwrite ($bf,full_tag("VOLUME",4,false,$flv->volume));
        fwrite ($bf,full_tag("ABOUTTEXT",4,false,$flv->abouttext));
        fwrite ($bf,full_tag("ABOUTLINK",4,false,$flv->aboutlink));
        fwrite ($bf,full_tag("CLIENT",4,false,$flv->client));
        fwrite ($bf,full_tag("FLVID",4,false,$flv->flvid));
        fwrite ($bf,full_tag("PLUGINS",4,false,$flv->plugins));
        fwrite ($bf,full_tag("STREAMER",4,false,$flv->streamer));
        fwrite ($bf,full_tag("TRACECALL",4,false,$flv->tracecall));
        fwrite ($bf,full_tag("VERSION",4,false,$flv->version));
        fwrite ($bf,full_tag("CAPTIONS",4,false,$flv->captions));
        fwrite ($bf,full_tag("FPVERSION",4,false,$flv->fpversion));
        fwrite ($bf,full_tag("NOTES",4,false,$flv->notes));
        //End mod
        $status = fwrite ($bf,end_tag("MOD",3,true));

        if ($status) {
            // backup files for this flv.
            $status = flv_backup_files($bf,$preferences,$flv);
        }

        return $status;
    }

   ////Return an array of info (name,value)
   function flv_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
       if (!empty($instances) && is_array($instances) && count($instances)) {
           $info = array();
           foreach ($instances as $id => $instance) {
               $info += flv_check_backup_mods_instances($instance,$backup_unique_code);
           }
           return $info;
       }
       //First the course data
       $info[0][0] = get_string("modulenameplural","flv");
       if ($ids = flv_ids ($course)) {
           $info[0][1] = count($ids);
       } else {
           $info[0][1] = 0;
       }
       
       return $info;
   }

   ////Return an array of info (name,value)
   function flv_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        return $info;
    }

    //Return a content encoded to support interactivities linking. Every module
    //should have its own. They are called automatically from the backup procedure.
    function flv_encode_content_links ($content,$preferences) {

        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of flvs
        $buscar="/(".$base."\/mod\/flv\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@FLVINDEX*$2@$',$content);

        //Link to flv view by moduleid
        $buscar="/(".$base."\/mod\/flv\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@FLVVIEWBYID*$2@$',$result);

        //Link to flv view by flvid
        $buscar="/(".$base."\/mod\/flv\/view.php\?r\=)([0-9]+)/";
        $result= preg_replace($buscar,'$@FLVVIEWBYR*$2@$',$result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of flvs id
    function flv_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}flv a
                                 WHERE a.course = '$course'");
    }
   
    function flv_backup_files($bf,$preferences,$flv) {
        global $CFG;
        $status = true;

        if (!file_exists($CFG->dataroot.'/'.$preferences->backup_course.'/'.$flv->flvfile)) {
            return true ; // doesn't exist but we don't want to halt the entire process so still return true.
        }
        
        $status = $status && check_and_create_course_files_dir($preferences->backup_unique_code);

        // if this is somewhere deeply nested we need to do all the structure stuff first.....
        $bits = explode('/',$flv->flvfile);
        $newbit = '';
        for ($i = 0; $i< count($bits)-1; $i++) {
            $newbit .= $bits[$i].'/';
            $status = $status && check_dir_exists($CFG->dataroot.'/temp/backup/'.$preferences->backup_unique_code.'/course_files/'.$newbit,true);
        }

        if ($flv->flvfile === '') {
            $status = $status && backup_copy_course_files($preferences); // copy while ignoring backupdata and moddata!!!
        } else if (strpos($flv->flvfile, 'backupdata') === 0 or strpos($flv->flvfile, $CFG->moddata) === 0) {
            // no copying - these directories must not be shared anyway!
        } else {
            $status = $status && backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$flv->flvfile,
                                                  $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/course_files/".$flv->flvfile);
        }
         
        // now, just in case we check moddata ( going forwards, flvs should use this )
        $status = $status && check_and_create_moddata_dir($preferences->backup_unique_code);
        $status = $status && check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/".$CFG->moddata."/flv/",true);
        
        if ($status) {
            //Only if it exists !! Thanks to Daniel Miksik.
            $instanceid = $flv->id;
            if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/flv/".$instanceid)) {
                $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/flv/".$instanceid,
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/flv/".$instanceid);
            }
        }

        return $status;
    }

?>
