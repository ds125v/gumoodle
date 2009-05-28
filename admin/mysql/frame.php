<?PHP

    require("../../config.php");

    // no debug messages before the meta header
    if ($CFG->debug > E_NOTICE) {
        $CFG->debug = E_NOTICE;
        error_reporting($CFG->debug);
    }

    require_login();


    if ($site = get_site()) {
        if (function_exists('require_capability')) {
            require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));
        } else if (!isadmin()) {
            error("You need to be admin to use this page");
        }
    }

    $stradmin = get_string("administration");
    $strmanagedatabase = get_string("managedatabase");

    if (!empty($_GET['top'])) {
        print_header("$site->shortname: $strmanagedatabase", "$site->fullname",
                     "<a target=_parent href=\"../index.php\">$stradmin</a> -> $strmanagedatabase");
    } else {
        if (function_exists('current_charset')) {
            $charset = current_charset();
        } else {
            //older Moodle versions
            $charset = get_string('thischarset');
        }
        echo "<head><title>$site->shortname: $strmanagedatabase</title></head>\n";
        echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=$charset\" />";
        echo "<frameset rows=70,*>";
        echo "<frame src=\"frame.php?top=1\">";
        echo "<frame src=\"index.php\">";
        echo "</frameset>";
    }

?>
