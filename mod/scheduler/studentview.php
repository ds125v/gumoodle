<?php

    /**
    * @package mod-scheduler
    * @category mod
    * @author Gustav Delius, Valery Fremaux > 1.8
    *
    * This page prints the screen view for the student
    */
    
    /**
    *
    */
    if (!defined('MOODLE_INTERNAL')){
        error("This file cannot be loaded directly");
    }
    
    if ($action){
        include 'studentview.controller.php';
    }
    
    $teacher = get_record('user', 'id', $scheduler->teacher);
    $mygroups = get_groups ($course->id, $USER->id);
    
/// printing head information

    print_heading($scheduler->name, 'center', 4, 'h1');
    if (!empty($scheduler->description)){
        print_simple_box($scheduler->description, 'center', '80%');
    }
    
    print_simple_box_start('center', '80%');
    if (scheduler_has_slot($USER->id, $scheduler, true)) {
        print_string('welcomebackstudent', 'scheduler');
    } else {
        print_string('welcomenewstudent', 'scheduler');
    }
    print_simple_box_end();
    
    // clean all late slots (for every body, anyway, they are passed !!)
    scheduler_free_late_unused_slots($scheduler->id);
    
/// get information about appointment attention    

    $sql = "
        SELECT
           COUNT(*)
        FROM
            {$CFG->prefix}scheduler_slots AS s,
            {$CFG->prefix}scheduler_appointment AS a
        WHERE
            s.id = a.slotid AND
            a.studentid = {$USER->id} AND
            a.attended = 1 AND
            s.schedulerid = {$scheduler->id}
    ";
    $hasattended = count_records_sql($sql);
    
/// get available slots

    $haveunattendedappointments = false;
    if ($slots = scheduler_get_available_slots($USER->id, $scheduler->id, true)) {
        $minhidedate = 0; // very far in the past
        $studentSlots = array();
        $studentAttendedSlots = array();
        foreach($slots as $slot) {
            /// check if other appointement is not "on the way". Student could not apply to it.
            if (scheduler_get_conflicts($scheduler->id, $slot->starttime, $slot->starttime + $slot->duration * 60, 0, $USER->id, SCHEDULER_OTHERS)){
                 continue;
            }
            
            /// check if not mine and late, don't care
            if (!$slot->appointedbyme and $slot->starttime + (60 * $slot->duration) < time()){
                continue;
            }
                     
            /// check what to print in groupsession indication
            if ($slot->exclusivity == 0){
                $slot->groupsession = get_string('yes');
            } else {
                // $consumed = scheduler_get_consumed($scheduler->id, $slot->starttime, $slot->starttime + $slot->duration * 60, $slot->teacher);
                if ($slot->exclusivity > $slot->population){
                    $slot->groupsession = get_string('limited', 'scheduler', ($slot->exclusivity - $slot->population)."/".$slot->exclusivity);
                } else { // should not be visible to students
                    $slot->groupsession = get_string('complete', 'scheduler');
                }
            }
    
/// examine slot situations and elects those which have sense for the current student

            // I am in slot, unconditionnally
            if ($slot->appointedbyme) {
                if ($slot->attended){
                    $studentAttendedSlots[$slot->starttime.'_'.$slot->teacherid] = $slot;
                } else {
                    $studentSlots[$slot->starttime.'_'.$slot->teacherid] = $slot;
                }
                // binary or and and required here to calculate properly
                $haveunattendedappointments = $haveunattendedappointments | ($slot->appointedbyme & !$slot->attended);
            } else {
                // slot is free
                if (!$slot->appointed) {
                    //if student is only allowed one appointment and this student has already had their then skip this record
                    if (($hasattended) and ($scheduler->schedulermode == 'oneonly')){
                        continue;
                    }
                    elseif ($slot->hideuntil <= time()){
                        $studentSlots[$slot->starttime.'_'.$slot->teacherid] = $slot;
                    }
                    $minhidedate = ($slot->hideuntil < $minhidedate || $minhidedate == 0) ? $slot->hideuntil : $minhidedate ;
                } 
                // slot is booked by another student, group booking is allowed and there is still room
                elseif ($slot->appointed and (($slot->exclusivity == 0) || ($slot->exclusivity > $slot->population))) {
                    // there is already a record fot this time/teacher : sure its our's
                    if (array_key_exists($slot->starttime.'_'.$slot->teacherid, $studentSlots)) continue;
                    // else record the slot with this user (not me).
                    $studentSlots[$slot->starttime.'_'.$slot->teacherid] = $slot;
                }
            }
        }
    
/// prepare attended slot table

        if (count($studentAttendedSlots)){
            print_heading(get_string('attendedslots' ,'scheduler'));
            $table->head  = array ($strdate, format_string($scheduler->staffrolename), $strnote, $strgrade);
            $table->align = array ('LEFT', 'CENTER', 'LEFT', 'LEFT');
            $table->size = array ('', '', '40%', '150');
            $table->width = '90%'; 
            $table->data = array();
            $previousdate = '';
            $previoustime = 0;
            $previousendtime = 0;
        
            foreach($studentAttendedSlots as $key => $aSlot){
                /// preparing data
                $startdate = scheduler_userdate($aSlot->starttime,1);
                $starttime = scheduler_usertime($aSlot->starttime,1);
                $endtime = scheduler_usertime($aSlot->starttime + ($aSlot->duration * 60),1);
                $startdatestr = ($startdate == $previousdate) ? '' : $startdate ;
                $starttimestr = ($starttime == $previoustime) ? '' : $starttime ;
                $endtimestr = ($endtime == $previousendtime) ? '' : $endtime ;
                $studentappointment = get_record('scheduler_appointment', 'slotid', $aSlot->id, 'studentid', $USER->id);
                if ($scheduler->scale  > 0){
                    $studentappointment->grade = $studentappointment->grade.'/'.$scheduler->scale;
                }
                
                if (has_capability('mod/scheduler:seeotherstudentsresults', $context)){
                    $appointments = scheduler_get_appointments($aSlot->id);
                    $collegues = '';
                    foreach($appointments as $appstudent){
                        $grade = $appstudent->grade;
                        if ($scheduler->scale > 0){
                            $grade = $grade . '/' . $scheduler->scale;
                        }
                        $student = get_record('user', 'id', $appstudent->studentid);
                        $picture = print_user_picture($appstudent->studentid, $course->id, $student->picture, 0, true, true);
                        $name = fullname($student);
                        if ($appstudent->studentid == $USER->id) $name = "<b>$name</b>" ; // it's me !!
                        $collegues .= " $picture $name ($grade)<br/>";
                    }
                } else {
                  $collegues = $studentappointment->grade;
                }
                
                $studentnotes1 = '';
                $studentnotes2 = '';
                if ($aSlot->notes != ''){
                    $studentnotes1 = '<div class="slotnotes">';
                    $studentnotes1 .= '<b>'.get_string('yourslotnotes', 'scheduler').'</b><br/>';
                    $studentnotes1 .= format_string($aSlot->notes).'</div>';
                }
                if ($studentappointment->appointmentnote != ''){
                    $studentnotes2 .= '<div class="appointmentnote">';
                    $studentnotes2 .= '<b>'.get_string('yourappointmentnote', 'scheduler').'</b><br/>';
                    $studentnotes2 .= format_string($studentappointment->appointmentnote).'</div>';
                }
                $studentnotes = "{$studentnotes1}{$studentnotes2}";
        
                // recording data into table
                $table->data[] = array ("<span class=\"attended\">$startdatestr</span><br/><div class=\"timelabel\">[$starttimestr - $endtimestr]</div>", "<a href=\"../../user/view.php?id={$aSlot->teacherid}&amp;course={$scheduler->course}\">".fullname(get_record('user', 'id', $aSlot->teacherid)).'</a>',$studentnotes, $collegues);
        
                $previoustime = $starttime;
                $previousendtime = $endtime;
                $previousdate = $startdate;
            }
        
            print_table($table);
        }
    
/// prepare appointable slot table

        print_heading(get_string('slots' ,'scheduler'));
        unset($table);
        $table->head  = array ($strdate, $strstart, $strend, get_string('choice', 'scheduler'), format_string($scheduler->staffrolename), get_string('groupsession', 'scheduler'));
        $table->align = array ('LEFT', 'LEFT', 'CENTER', 'CENTER', 'LEFT');
        $table->data = array();
        $previousdate = '';
        $previoustime = 0;
        $previousendtime = 0;
        $canappoint = false;
        foreach($studentSlots as $key => $aSlot){
            $startdate = scheduler_userdate($aSlot->starttime,1);
            $starttime = scheduler_usertime($aSlot->starttime,1);
            $endtime = scheduler_usertime($aSlot->starttime + ($aSlot->duration * 60),1);
            $startdatestr = ($startdate == $previousdate) ? '' : $startdate ;
            $starttimestr = ($starttime == $previoustime) ? '' : $starttime ;
            $endtimestr = ($endtime == $previousendtime) ? '' : $endtime ;
            if ($aSlot->appointedbyme and !$aSlot->attended){
                $radio = "<input type=\"radio\" name=\"slotid\" value=\"{$aSlot->id}\" checked=\"checked\" />\n";
                $table->data[] = array ("<b>$startdatestr</b>", "<b>$starttime</b>", "<b>$endtime</b>", $radio, "<b>"."<a href=\"../../user/view.php?id={$aSlot->teacherid}&amp;course=$scheduler->course\">".fullname(get_record('user', 'id', $aSlot->teacherid)).'</a></b>','<b>'.$aSlot->groupsession.'</b>');
            } else {
                if ($aSlot->appointed and has_capability('mod/scheduler:seeotherstudentsbooking', $context)){
                    $appointments = scheduler_get_appointments($aSlot->id);
                    $collegues = "<div style=\"visibility:hidden; display:none\" id=\"collegues{$aSlot->id}\"><br/>";
                    foreach($appointments as $appstudent){
                        $student = get_record('user', 'id', $appstudent->studentid);
                        $picture = print_user_picture($appstudent->studentid, $course->id, $student->picture, 0, true, true);
                        $name = "<a href=\"view.php?what=viewstudent&amp;id={$cm->id}&amp;studentid={$student->id}&amp;course={$scheduler->course}&amp;order=DESC\">".fullname($student).'</a>';
                        $collegues .= " $picture $name<br/>";
                    }
                    $collegues .= '</div>';
                    $aSlot->groupsession .= " <a href=\"javascript:toggleVisibility('{$aSlot->id}')\"><img name=\"group<?php p($aSlot->id) ?>\" src=\"{$CFG->pixpath}/t/switch_plus.gif\" border=\"0\" title=\"".get_string('whosthere', 'scheduler')."\"></a> {$collegues}";
                }
                $canappoint = true;
                $canusegroup = ($aSlot->appointed) ? 0 : 1;
                $radio = "<input type=\"radio\" name=\"slotid\" value=\"{$aSlot->id}\" onclick=\"checkGroupAppointment($canusegroup)\" />\n";
                $table->data[] = array ($startdatestr, $starttimestr, $endtimestr, $radio, "<a href=\"../../user/view.php?id={$aSlot->teacherid}&amp;course={$scheduler->course}\">".fullname(get_record('user', 'id', $aSlot->teacherid)).'</a>', $aSlot->groupsession);
            }
            $previoustime = $starttime;
            $previousendtime = $endtime;
            $previousdate = $startdate;
        }
    
/// print slot table

        if (count($table->data)){
?>
        <center>
        <form name="appoint" action="view.php" method="get">
        <input type="hidden" name="what" value="savechoice" />
        <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
        <script type="text/javascript">
        function checkGroupAppointment(enable){
            var numgroups = '<?php p(count($mygroups)) ?>';
            if (!enable){
                if (numgroups > 1){ // we have a select. we must force "appointsolo".
                    document.forms['appoint'].elements['appointgroup'].options[0].selected = true;
                }
            }
            document.forms['appoint'].elements['appointgroup'].disabled = !enable;
        }    
        </script>
<?php
            print_table($table);
            
/// add some global script        

?>
                     <script type="text/javascript">
                        function toggleVisibility(id){
                            obj = document.getElementById('collegues' + id);
                            if (obj.style.visibility == "hidden"){
                                obj.style.visibility = "visible";
                                obj.style.display = "block";
                                document.images["group"+id].src='<?php echo $CFG->pixpath."/t/switch_minus.gif" ?>';
                            } else {
                                obj.style.visibility = "hidden";
                                obj.style.display = "none";
                                document.images["group"+id].src='<?php echo $CFG->pixpath."/t/switch_plus.gif" ?>';
                            }
                        }
                     </script>
    <?php
    
            if ($canappoint){
                /*
                Should add a note from the teacher to the student. 
                TODO : addfield into appointments
                print_heading(get_string('savechoice', 'scheduler'), 3);
                echo '<table><tr><td valign="top" align="right"><b>';
                print_string('studentnotes', 'scheduler');
                echo ' :</b></td><td valign="top" align="left"><textarea name="notes" cols="60" rows="20"></textarea></td></tr></table>';
                */
                echo '<br /><input type="submit" value="'.get_string('savechoice', 'scheduler').'" /> ';
                if ($groupmode){
                    if (count($mygroups) == 1){
                        $groups = array_values($mygroups);
                        echo ' <input type="checkbox" name="appointgroup" value="'.$groups[0]->id.'" /> '.get_string('appointformygroup', 'scheduler').': '.$groups[0]->name;                    
                        helpbutton('appointagroup', get_string('appointagroup', 'scheduler'), 'scheduler');
                    }
                    if (count($mygroups) > 1){
                        print_string('appointfor', 'scheduler');
                        foreach($mygroups as $group){
                            $groupchoice[0] = get_string('appointsolo','scheduler');
                            $groupchoice[$group->id] = $group->name;
                        }
                        choose_from_menu($groupchoice, 'appointgroup', '', '');
                        helpbutton('appointagroup', get_string('appointagroup', 'scheduler'), 'scheduler');
                    }
                }
            }
    
            echo '</form>';
            
            if ($haveunattendedappointments and has_capability('mod/scheduler:disengage', $context)){
                echo "<br/><a href=\"view.php?id={$cm->id}&amp;what=disengage\">".get_string('disengage','scheduler').'</a>';
            }
            
            echo '</center>';
    
        }
        else {
            if ($minhidedate > time()){
                $noslots = get_string('noslotsopennow', 'scheduler') .'<br/><br/>';
                $noslots .= get_string('firstslotavailable', 'scheduler') . '<span style="color:#C00000"><b>'.userdate($minhidedate).'</b></span>';
            } else {
                $noslots = get_string('noslotsavailable', 'scheduler') .'<br/><br/>';
            }
            print_simple_box($noslots, 'center', '70%');
        }
    } else {
        notify(get_string('noslots', 'scheduler'));
    }
?>