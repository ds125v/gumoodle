<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html<?php echo $direction . '>';
        global $course, $category, $site;
        $departmentlink = '';
        $moodlename =  strtolower(str_replace(' ', '-', strip_tags(format_string(get_site()->shortname))));
        if ($navigation && (is_object($course) || is_object($category))) {

                if (is_object($course)) {
                    $coursecategory = $course->category;
                } else {
                    $coursecategory = $category->id;
                }
                $cats = Array();
                if ($catpath = get_record("course_categories", "id", $coursecategory)) {
                    $cats = substr($catpath->path, 1);
                    $cats = explode('/', $cats);
                    $topcat = get_record("course_categories", "id", $cats[0]);

                list ($first, $rest) = split("</li>", $navigation['navlinks'], 2);
                $departmentlink ='</li><li> <span class="accesshide " >/&nbsp;</span><span class="arrow sep">&#x25BA;</span> <a onclick="this.target=\"_top\" href="'. $CFG->wwwroot.'/course/category.php?id='. $topcat->id .'">'. strip_tags(format_string($topcat->name)) .'</a></li>';
}
        if ((is_object($category) && $category->id == $topcat->id) || (is_object($course) && $course->id == 1)) {
            // don't add anything to breadcrumb
        } else {
            $navigation['navlinks'] = $first . $departmentlink. $rest;
        }

        $catcss = '';
        foreach ($cats as $cat) {
        $catcss .= "category-".$cat." ";
        }
}
        $bt =  substr($bodytags, 0, 8) . "moodle-" . $moodlename . ' ';
        if (isset($catcss)) { $bt .= $catcss; }
        $bt .= substr($bodytags, 8);
        $bodytags = $bt;
?>
<head>
    <?php echo $meta ?>
    <meta name="keywords" content="moodle, <?php echo $title ?> " />
    <title><?php echo $title ?></title>
    <link rel="shortcut icon" href="<?php echo $CFG->httpswwwroot ?>/theme/onepointnine/favicon.ico" />
    <?php include("$CFG->javascript"); ?>
</head>

<body<?php echo $bodytags;
        if ($focus) {
        echo " onload=\"setfocus()\"";
        }
    ?>>

<div id="page">

<?php //Accessibility: 'headermain' is now H1, see theme/standard/styles_layout.css: .headermain
      if ($heading) { 
?>

<div id="headers_container">
<div id="toprow">
<a href="http://www.gla.ac.uk/"><img src="<?php echo $CFG->httpswwwroot ?>/theme/onepointnine/t4/generic/i/180newlogotypeblack160x20.gif" alt="University of Glasgow" width="160" height="20" class="logotype" /></a>
<form method="get"  action="http://www.google.com/u/UofGlasgow">
<div>
<?php if (!isloggedin() or isguestuser()) {  ?> 
<a href="<?php echo $CFG->httpswwwroot ?>/login/index.php">LOG-IN</a>
<?php } else {?>
<a href="<?php echo $CFG->httpswwwroot ?>/login/logout.php">LOG-OUT</a>
<? } ?>
 | <a href="http://www.gla.ac.uk/services/it/helpdesk">IT HELPDESK</a> |  
<input name="q" id="search"  class="searchbox" value="search university web" onfocus="this.value=''"    type="text" size="13"  /><input name="imagefield" type="image" class="go" alt="go" src="<?php echo $CFG->httpswwwroot ?>/theme/onepointnine/t4/generic/i/searcharrow.gif" />
</div></form>
</div>
<div id="bannercontainercontainer">
  <div id="bannercontainer">
   <div id="bannerleft"></div>
   <div id="bannerright"></div>
  </div> 
</div>
<div id="subhead">
<div style="float: right; text-align: right; "></div>
<?php if ($moodlename == "services") { ?>
Part of <a href="/"><?php echo $SITE->fullname ?></a>
<?php } else if ($moodlename == "vets") { ?>
AVMA approved
<?php } else if ($moodlename == "crichton") { ?>
Part of the <a href="http://arts.gla.ac.uk">Faculty of Arts</a>
<?php } else if ($moodlename == "philosophy") { ?>
Part of the <a href="http://arts.gla.ac.uk">Faculty of Arts</a>
<?php } else { ?>
Part of the <a href="/"><?php echo $SITE->fullname ?></a>
<?php } ?>
</div>
</div>
    <div class="navbar clearfix">
        <div class="breadcrumb"><?php print_navigation($navigation); ?></div>
        <div class="navbutton"><?php echo $button; ?></div>
    </div>
<?php } ?>
    <!-- END OF HEADER -->
    <?php print_container_start(false, '', 'content'); ?>
