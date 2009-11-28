<?php // $Id: email.php,v 2.02 2008/06/26 09:55:18 whchuang Exp $

    /**
     * email.php - Used by Quickmail for sending emails to users enrolled in a specific course.
     *      Calls email.html at the end.
     *
     * @Original author Mark Nielsen and Michael Penney. 
     * @Updated by Bibek Bhattarai, Neha Arora, Wen Hao Chuang
     * @version $Id: email.php,v 2.02 2008/06/26 09:55:18 whchuang Exp $
     * @package quickmailv2
     **/
    
	/** 
 	* Updated feature:
 	*	- Replaces checkbox with selection list for effective usability in case of larger classes
	* - For simplicity purpose removed the options group mailing for now, will be added in next version
 	**/ 

    //Load libary files
    require_once('../../config.php');
    require_once($CFG->libdir.'/blocklib.php');
	
	//Read parameter values courseid, quickmail instance id, and action  
    $id = required_param('id', PARAM_INT);  // course id
    $instanceid = optional_param('instanceid', 0, PARAM_INT);
    $action = optional_param('action', '', PARAM_ALPHA);
    
	//Setup quickmail block

    $instance = new stdClass;;
	
    if (!$course = get_record('course', 'id', $id)) {
        error('Course ID was incorrect');
    }	
	
    //Check user have logged in to the system or not
    require_login();
    $context = get_context_instance(CONTEXT_COURSE, $course->id); //[NEW]

    if (isset($USER->realuser)) {
        error('You cannot access email, while loging in as other user');
    }

    if ($instanceid) {
        $instance = get_record('block_instance', 'id', $instanceid);
    } else {
        if ($quickmailblock = get_record('block', 'name', 'quickmail')) {
            $instance = get_record('block_instance', 'blockid', $quickmailblock->id, 'pageid', $course->id);
        }
    }

/// This block of code ensures that Quickmail will run 
/// whether it is in the course or not - now we will use has_capability [NEW], not isGuest()

    if (empty($instance)) {
        $groupmode = groupmode($course);
        if (has_capability('block/quickmail:cansend', get_context_instance(CONTEXT_BLOCK, $instanceid))) {
            $haspermission = true;
        } else {
            $haspermission = false;
        }
    } else {
        // create a quickmail block instance
        $quickmail = block_instance('quickmail', $instance);
        $quickmail->load_defaults();
        
        $groupmode     = $quickmail->groupmode();
        $haspermission = $quickmail->check_permission();
    }
    
	//Check if user have permissions to use Quickmail
    if (!$haspermission) {
        error('Sorry, you do not have the correct permissions to use Quickmail.');
    }
	
	//Get list of users enrolled in the course including teachers and students
	//[OLD] get_course_users()   [NEW] get_users_by_capability()
    if (!$courseusers = get_users_by_capability($context, 'moodle/course:view', 'u.*', 'u.lastname, u.firstname', '', '', '', '', false)) {
        error('No course users found to email');
    }
    
  // if action is view display the email user wants to view
	if ($action == 'view') {
        // viewing an old email.  Hitting the db and puting it into the object $form (apply with our context?)
        $emailid = required_param('emailid', PARAM_INT);
        $form = get_record('block_quickmail_log', 'id', $emailid);
        // $mail_list = explode(',', stripslashes($form->mailto)); // convert mailto back to an array (OLD)
        $form->mailto = explode(',', $form->mailto); // convert mailto back to an array [NEW]
    }
	else if ($form = data_submitted()) {   // data was submitted to be mailed
    	confirm_sesskey();

        if (!empty($form->cancel)) {
            // cancel button was hit...
            redirect("$CFG->wwwroot/course/view.php?id=$course->id");
        }
        
        // prepare variables for email      
        $form->subject = stripslashes($form->subject);
        $form->subject = clean_param($form->subject, PARAM_CLEAN);
        $form->subject = strip_tags($form->subject, '<lang>');        // Strip all tags except lang
        $form->subject = break_up_long_words($form->subject);

        $form->message = stripslashes($form->message); // needed to get slashes off of the post
        $form->message = clean_param($form->message, PARAM_CLEANHTML);

        // get the correct formating for the emails
        $form->plaintxt = format_text_email($form->message, $form->format); // plain text
        $form->html = format_text($form->message, $form->format);        // html
       	$mail_list = explode(',',stripslashes($form->mailuser));
		
		// make sure the user didn't miss anything
		if (sizeof($mail_list)<=1 && $mail_list[0]=="") {
            $form->error = get_string('toerror', 'block_quickmail');
        } else if (!$form->subject) {
            $form->error = get_string('subjecterror', 'block_quickmail');
        } else if (!$form->message) {
            $form->error = get_string('messageerror', 'block_quickmail');
        }
        
        // process the attachment
        $attachment = $attachname = '';
        
        if (has_capability('moodle/course:managefiles', $context)) {
            $form->attachment = trim($form->attachment);
            if (isset($form->attachment) and !empty($form->attachment)) {
                $form->attachment = clean_param($form->attachment, PARAM_PATH);
            
                if (file_exists($CFG->dataroot.'/'.$course->id.'/'.$form->attachment)) {
                    $attachment = $course->id.'/'.$form->attachment;
            
                    $pathparts = pathinfo($form->attachment);
                    $attachname = $pathparts['basename'];
                } else {
                    $form->error = get_string('attachmenterror', 'block_quickmail', $form->attachment);
                }
            }
        } else {
            require_once($CFG->libdir.'/uploadlib.php');
        
            $um = new upload_manager('attachment', false, true, $course, false, 0, true);

            // process the student posted attachment if it exists
            if ($um->process_file_uploads('temp/block_quickmail')) {
                // original name gets saved in the database
                $form->attachment = $um->get_original_filename();

                // check if file is there
                if (file_exists($um->get_new_filepath())) {
                    // get path to the file without $CFG->dataroot
                    $attachment = 'temp/block_quickmail/'.$um->get_new_filename();

                    // get the new name (name may change due to filename collisions)
                    $attachname = $um->get_new_filename();
                } else {
                    $form->error = get_string("attachmenterror", "block_quickmail", $form->attachment);
                }
            } else {
                $form->attachment = ''; // no attachment
            }
        }		
          
        // no errors, then email
        if(!isset($form->error)) {
            $mailedto = array(); // holds all the userid of successful emails
            $blockedTo = array();
            $failedTo = array();
            //print_heading(get_string('pleasewait', 'block_quickmail'), 'center', 3);  // inform the user to wait

            // run through each user id and send a copy of the email to him/her
            // not sending 1 email with CC to all user ids because emails were required to be kept private
            foreach ($mail_list as $userid) {
                $userid = stripslashes($userid);
                $userid = str_replace("\"","",$userid);   
                if (!$courseusers[$userid]->emailstop) {
                        $mailresult = email_to_user($courseusers[$userid], $USER, $form->subject, $form->plaintxt, $form->html, $attachment, $attachname);
                        // checking for errors, if there is an error, store the name
                        if (!$mailresult || (string) $mailresult == 'emailstop') {
                            $form->error = get_string('emailfailerror', 'block_quickmail');
                            $form->usersfail['emailfail'][] = $courseusers[$userid]->lastname.', '.$courseusers[$userid]->firstname;
                            $failedTo[] = $userid;
                    	} else {
                            // success
                        	$mailedto[] = $userid;						
                    	}
                } else {
                    // blocked email
                    $form->error = get_string('emailfailerror', 'block_quickmail');
                    $form->usersfail['emailstop'][] = $courseusers[$userid]->lastname.', '.$courseusers[$userid]->firstname;
                    $blockedTo[] = $userid;
                }
            }            
            // cleanup - delete the uploaded file
            if (isset($um) and file_exists($um->get_new_filepath())) {
                unlink($um->get_new_filepath());
            }

            if(count($mailedto)>0){
				$messagetext = $messagetext."----------------------------------------------------\n";
				$messagetext = $messagetext."Following email was successfully sent to: \n";
				$messagetext = $messagetext."----------------------------------------------------\n";				
				foreach($mailedto as $userid){
					$messagetext = $messagetext.$courseusers[$userid]->email.";\t";
				}
				$messagetext = $messagetext."\n";
			}
			
			if(count($blockedTo)>0){
				$messagetext = $messagetext."\n-------------------------------------------------------------------------------\n";
				$messagetext = $messagetext."Following user(s) have chosen not to recieve any email through iLearn: \n";
				$messagetext = $messagetext."-------------------------------------------------------------------------------\n";
				foreach($blockedTo as $userid){
					$messagetext = $messagetext.$courseusers[$userid]->email.";\t";
				}
				$messagetext = $messagetext."\n";
			}
			
			if(count($failedTo)>0){
				$messagetext = $messagetext."\n---------------------------------------------------------------------------------------------------------------\n";
				$messagetext = $messagetext."iLearn was unable to send email to following user(s), please contact iLearn support to report the error: \n";
				$messagetext = $messagetext."-----------------------------------------------------------------------------------------------------------------\n";
				foreach($failedTo as $userid){
					$messagetext = $messagetext.$courseusers[$userid]->email.";\t";
				}
				$messagetext = $messagetext."\n";
			}
			
			$messagetext = $messagetext."\n==================================\nMessage Content\n----------------------------------\n";
			$messagetext = $messagetext."\n".$form->plaintxt;
			$messagetext = $messagetext."\n\n=================================\n";
			$form->subject = "Quickmail dispatch receipt: ".$form->subject;
			$mailresult = email_to_user($USER, $USER, $form->subject, $messagetext, '', $attachment, $attachname);
			                    	        	
            // cleanup - delete the uploaded file
            if (isset($um) and file_exists($um->get_new_filepath())) {
                unlink($um->get_new_filepath());
            }

            // prepare an object for the insert_record function (can be removed as we don't use history?)
            $emaillog = new stdClass;
            $emaillog->courseid   = $course->id;
            $emaillog->userid     = $USER->id;
            $emaillog->mailto     = implode(',', $mailedto);
            $emaillog->subject    = addslashes($form->subject);
            $emaillog->message    = addslashes($form->message);
            $emaillog->attachment = $form->attachment;
            $emaillog->format     = $form->format;
            $emaillog->timesent   = time();
			
			if (!insert_record('block_quickmail_log', $emaillog)) {
                error('Email not logged.');
            }

            if(!isset($form->error)) {  // if no emailing errors, we are done
                // inform of success and continue
                redirect("$CFG->wwwroot/course/view.php?id=$course->id", get_string('successfulemail', 'block_quickmail'));
                // print_footer($course); [OLD]
                // exit(); [OLD]
            }
        }
        // so people can use quotes.  It will display correctly in the subject input text box
        // $form->subject = htmlentities($form->subject, ENT_QUOTES);  [OLD]
		$form->subject = s($form->subject);

    } else {
        // set them as blank
        $form->subject = $form->message = $form->format = $form->attachment = '';		
    }

    // get the default format       
    if ($usehtmleditor = can_use_richtext_editor()) {
        $defaultformat = FORMAT_HTML;
    } else {
        $defaultformat = FORMAT_MOODLE;
    }
	
    // set up some strings
    $readonly       = '';
    $strchooseafile = get_string('chooseafile', 'resource');
    $strquickmail   = get_string('blockname', 'block_quickmail');

/// Header setup
    if ($course->category) {
        $navigation = "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }
	
    print_header($course->fullname.': '.$strquickmail, $course->fullname, $navigation);

    // print the email form START
    print_heading($strquickmail);
    
    // error printing

    if (isset($form->error)) {
        echo '<b><center><font color="#FF0000" align="center"> !! Error !!';
		notify($form->error);
		echo '</font></center></b>';
        if (isset($form->usersfail)) {
            $errorstring = '';
            
            if (isset($form->usersfail['emailfail'])) {
                $errorstring .= get_string('emailfail', 'block_quickmail').'<br />';
                foreach($form->usersfail['emailfail'] as $user) {
                    $errorstring .= $user.'<br />';
                }               
            }

            if (isset($form->usersfail['emailstop'])) {
                $errorstring .= get_string('emailstop', 'block_quickmail').'<br />';
                foreach($form->usersfail['emailstop'] as $user) {
                    $errorstring .= $user.'<br />';
                }               
            }
            notify($errorstring);
            
            // print continue button
            print_continue("$CFG->wwwroot/course/view.php?id=$course->id");
            print_footer($course);
            exit();
        }
    }

 	print_simple_box_start('center'); 	       
	require($CFG->dirroot.'/blocks/quickmail/email.html');  // email form [NEW]
    print_simple_box_end();
    
    if ($usehtmleditor) {
        use_html_editor('message');
    }

    print_footer($course);
?>
