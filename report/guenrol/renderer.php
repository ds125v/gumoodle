<?php

class report_guenrol_renderer extends plugin_renderer_base {

    public function menu($id, $codes) {

        // Sync link.
        $synclink = new moodle_url('/report/guenrol/index.php', array('id' => $id, 'action' => 'sync'));
        echo "<div><a class=\"btn\" href=\"$synclink\">" . get_string('synccourse', 'report_guenrol') . "</a></div>";

        if (empty($codes)) {
            echo '<div class="alert">' . get_string('nocodes', 'report_guenrol') . '</div>';
        } else {
            echo "<p>" . get_string('listofcodes', 'report_guenrol') . "</p>";
            echo '<ul id="guenrol_codes">';
            foreach ($codes as $code) {

                // Establish link for detailed display.
                $link = new moodle_url('/report/guenrol/index.php', array('id' => $id, 'codeid' => $code->id));
                echo "<li><a href=\"$link\">";
                echo "<strong>{$code->code}</strong></a> ";
                if ($code->coursename != '-') {
                    echo "\"{$code->coursename}\" ";
                    echo "({$code->subjectname}) ";
                }
                echo "</li>";
            }

            // If there is more than 1 show aggregated.
            if (count($codes) > 1) {
                $link = new moodle_url('/report/guenrol/index.php', array('id' => $id, 'codeid' => -1));
                echo "<li><a href=\"$link\">";
                echo "<strong>" . get_string('showall', 'report_guenrol') . "</strong></a> ";
                echo "</li>";
            }

            // Link for showing 'removed' users
            $link = new moodle_url('/report/guenrol/index.php', array('id' => $id, 'action' => 'removed'));
            echo "<li><a href=\"$link\">";
            echo "<strong>" . get_string('removed', 'report_guenrol') . "</strong></a></li>";
            echo '</ul>';
        }
    }

    public function code_info($codes, $codeid, $codename, $coursename, $subjectname) {
        if ($codeid > -1) {
            echo "<p>" . get_string('enrolmentscode', 'report_guenrol', $codename) . ' ';
            if ($coursename != '-') {
                echo get_string('coursename', 'report_guenrol', $coursename) . ' ';
                echo get_string('subjectname', 'report_guenrol', $subjectname);
            }
            echo '</p>';

        } else {
            echo "<p>" . get_string('usercodes', 'report_guenrol') . "<p>";
            echo "<ul>";
            foreach ($codes as $code) {
                echo "<li><strong>{$code->code}</strong> ";
                if ($code->coursename != '-') {
                    echo get_string('coursename', 'report_guenrol', $code->coursename) . ' ';
                    echo get_string('subjectname', 'report_guenrol', $code->subjectname);
                }
                echo '</li>';
            }
            echo "</ul>";
        }
    }

    public function list_users($users) {
        echo "<ul id=\"guenrol_users\">";
        foreach ($users as $user) {

            // Be sure not to show deleted accounts.
            if ($user->deleted) {
                continue;
            }

            // Display user (profile) link and data.
            $link = new moodle_url( '/user/profile.php', array('id' => $user->userid));
            echo "<li>";
            echo "<a href=\"$link\"><strong>{$user->username}</strong></a> ";
            echo $user->fullname;
            echo " <small>({$user->code})</small>";
            echo "</li>";
        }
        echo "</ul>";
        echo "<p>" . get_string('totalcodeusers', 'report_guenrol', count($users)) . "</p>";
    }

    public function list_removed_users($id, $users) {
        global $OUTPUT;

        echo "<h3>" . get_string('removedusers', 'report_guenrol') . "</h3>";
        if (!$users) {
            echo '<p class="alert alert-warning">' . get_string('noremovedusers', 'report_guenrol') . '</p>';
            $url = new moodle_url('/report/guenrol/index.php', array('id' => $id));
            echo $OUTPUT->continue_button($url);
            return;
        }

        // Unenrol link.
        $unenrollink = new moodle_url('/report/guenrol/index.php', array('id' => $id, 'action' => 'unenrol'));
        echo "<div><a class=\"btn\" href=\"$unenrollink\">" . get_string('unenrol', 'report_guenrol') . "</a></div>";

        echo "<ul id=\"guenrol_users\">";
        foreach ($users as $user) {

            // Be sure not to show deleted accounts.
            if ($user->deleted) {
                continue;
            }

            // Display user (profile) link and data.
            $link = new moodle_url( '/user/profile.php', array('id' => $user->id));
            echo "<li>";
            echo "<a href=\"$link\"><strong>{$user->username}</strong></a> ";
            echo fullname($user);
            echo "</li>";
        }
        echo "</ul>";
        echo "<p>" . get_string('totalremoveusers', 'report_guenrol', count($users)) . "</p>";
    }

    public function removed($id) {
        global $OUTPUT;

        echo '<p class="alert alert-info">' . get_string('removeddone', 'report_guenrol') . '</p>';
        $url = new moodle_url('/report/guenrol/index.php', array('id' => $id));
        echo $OUTPUT->continue_button($url);
    }

}
