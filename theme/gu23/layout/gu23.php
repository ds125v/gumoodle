<?php

$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hasblocks1 = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hasblocks2 = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$bodyclasses = array();
if ($hasblocks1 && !$hasblocks2) {
    $bodyclasses[] = 'blocks1-only';
} else if ($hasblocks2 && !$hasblocks1) {
    $bodyclasses[] = 'blocks2-only';
} else if (!$hasblocks1 && !$hasblocks2) {
    $bodyclasses[] = 'no-blocks';
}
$favicon_url = $OUTPUT->pix_url('favicon', 'theme');
$OUTPUT->doctype(); // throw it away to avoid warning
?>
<!DOCTYPE html>
<html>

<head>
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
    <title><?php echo $PAGE->title ?></title>
    <link rel="shortcut icon" href="<?php echo $favicon_url ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
<!--[if lt IE 9]>
<script src="<?php echo new moodle_url("/theme/simple/html5shiv.js")?>"></script>
<![endif]-->
</head>

<body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses.' '.join(' ', $bodyclasses)) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>

<div id="page">

<header>
  <h1 class="headermain"></h1>
	<div id="siteTools"> <h3>Site tools</h3> <ul>
		<li><a href="http://www.gla.ac.uk/subjects/">Subjects A-Z</a></li>
		<li><a href="http://www.gla.ac.uk/stafflist/">Staff A-Z</a></li>
		<li><a href="http://www.gla.ac.uk/academic/">Academic units A-Z</a></li>
	</ul> </div>
    <?php echo $OUTPUT->login_info(); ?>
	<?php echo $PAGE->headingmenu; ?>
	<div id="head-menu">
		<ul>
		<li><a title="My Home" href="<?php echo $CFG->wwwroot ?>/my">My Home</a></li>
		<li><a title="My Profile" href="<?php echo $CFG->wwwroot.'/user/profile.php?id='.$USER->id ?>">My Profile</a></li>
		<li><a title="All Courses" href="<?php echo $CFG->wwwroot ?>?redirect=0">All Courses</a></li>
		<li><a title="My Glasgow" href="http://www.gla.ac.uk/students/myglasgow/">MyGlasgow</a></li>
		<li><a title="Calendar" href="<?php echo $CFG->wwwroot ?>/calendar/view.php?view=month">Calendar</a></li>
		<li><a title="IT Helpdesk" href="http://www.gla.ac.uk/services/it/helpdesk/">IT Helpdesk</a></li>
		<li><a title="Logout" href="<?php echo $CFG->wwwroot.'/login/logout.php?sesskey='.sesskey() ?>">Logout</a></li>
		</ul>
	</div>

</header>


    <div id="page-content">

      <div class="content-header"><h1><?php echo $PAGE->heading ?></h1> </div>
	<div id="layout" class="yui3-g">

		<div id="blocks1" class="yui3-u">
		<?php if ($hasblocks1) { ?>
		    <div class="region-content">
			<?php echo $OUTPUT->blocks_for_region('side-pre') ?>
		    </div>
		<?php } ?>
		</div>

	    <div id="main" class="yui3-u">
            <div class="navbar clearfix">
                <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
                <div class="navbutton"> <?php echo $PAGE->button; ?></div>
            </div>

		<div class="region-content">
		   <?php echo $OUTPUT->main_content() ?>
		</div>
            </div>

		<div id="blocks2" class="yui3-u">
		<?php if ($hasblocks2) { ?>
		    <div class="region-content">
			<?php echo $OUTPUT->blocks_for_region('side-post') ?>
		    </div>
		<?php } ?>
		</div>
	 </div>
</div>

<footer>
<div class="backToTop curvyRedraw"><a href="#">Back to top</a></div>
    
 <div class="contact">
      <div class="vcard">
        <div class="fn org">University <em>of</em> Glasgow</div>
        <div class="adr"> <!-- <span class="street-address">10 The Square</span>, --><span class="locality">Glasgow</span>, <span class="postal-code">G12 8QQ</span>, <span class="country-name">Scotland</span> </div>
        <div class="phoneEmail">Tel +44 (0) 141 330 2000, <a href="http://www.gla.ac.uk/about/contact/">contact us</a>
<br />
<a href="http://www.gla.ac.uk/about/maps/">Maps and travel</a>
<br /><br />
The University of Glasgow is a registered Scottish charity: registration number SC004401<br />
<a href="http://www.gla.ac.uk/about/contact/">Contact</a>
</div>
</div>
</div>


<ul>
    <li class="helplink"><?php echo page_doc_link(get_string('moodledocslink')) ?></li>
    <li><?php echo $OUTPUT->login_info() ?></li>
    <li><?php echo $OUTPUT->home_link() ?></li>
    <li><a href="http://www.gla.ac.uk/about/accessibility/">Accessibility</a></li>
    <li><a href="http://www.gla.ac.uk/legal/disclaimer/">Disclaimer</a></li>
    <li><a href="http://www.gla.ac.uk/foi/">Freedom of information</a></li>
    <li><a href="http://www.gla.ac.uk/legal/freedomofinformation/foipublicationscheme/">FOI publication scheme</a></li>
    <li><a href="http://www.gla.ac.uk/legal/privacy/">Privacy and cookies</a></li>
    <li><a href="http://www.gla.ac.uk/legal/copyright/">&copy; UofG</a></li>
    <li><?php echo $OUTPUT->standard_footer_html(); ?></li>
</ul>
</footer>
</div>
    <?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
