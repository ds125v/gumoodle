<?php

$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$bodyclasses = array();
if ($hassidepre && !$hassidepost) {
    $bodyclasses[] = 'side-pre-only';
} else if ($hassidepost && !$hassidepre) {
    $bodyclasses[] = 'side-post-only';
} else if (!$hassidepost && !$hassidepre) {
    $bodyclasses[] = 'content-only';
}
$favicon_url = $OUTPUT->pix_url('favicon', 'theme');
$OUTPUT->doctype(); // throw it away to avoid warning
?>
<!DOCTYPE html>
<html>

<head>
<meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $PAGE->title ?></title>
    <link rel="shortcut icon" href="<?php echo $favicon_url ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
<!--[if lt IE 9]>
<script src="<?php echo new moodle_url("/theme/bootstrap/html5shiv.js")?>"></script>
<![endif]-->
</head>

<body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses.' '.join(' ', $bodyclasses)) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>

<div class=container>

<header id=page-header>
    <h1><a href=http://www.gla.ac.uk/ class=hide-text title="Go to University homepage" >University of Glasgow</a></h1>
	<div id=siteTools> <h3>Site tools</h3> <ul>
		<li><a href=http://www.gla.ac.uk/subjects/>Subjects A-Z</a></li>
		<li><a href=http://www.gla.ac.uk/stafflist/>Staff A-Z</a></li>
		<li><a href=http://www.gla.ac.uk/academic/>Academic units A-Z</a></li>
	</ul> </div>
    <?php echo $OUTPUT->login_info(); ?>
	<?php echo $PAGE->headingmenu; ?>
	<div id=head-menu>
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
	<div class=container>
<div class=row-fluid>

<?php if ($hassidepre) : ?>
    <aside class="span3">
    <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
    </aside>
<?php endif; ?>

<?php if ($hassidepre AND $hassidepost) : ?>
    <article class="span6">
<?php else : ?>
    <article class="span9">
<?php endif; ?>
        <?php echo core_renderer::MAIN_CONTENT_TOKEN ?>
    </article>

<?php if ($hassidepost) : ?>
    <aside class="span3">
    <?php echo $OUTPUT->blocks_for_region('side-post') ?>
    </aside>
<?php endif; ?>
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
    <li><?php echo $OUTPUT->login_info() ?></li>
    <li class="helplink"><?php echo page_doc_link(get_string('moodledocslink')) ?></li>
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
