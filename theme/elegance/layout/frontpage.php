<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The Elegance theme is built upon  Bootstrapbase 3 (non-core).
 *
 * @package    theme
 * @subpackage theme_elegance
 * @author     Julian (@moodleman) Ridden
 * @author     Based on code originally written by G J Bernard, Mary Evans, Bas Brands, Stuart Lamour and David Scotson.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$hascopyright = (empty($PAGE->theme->settings->copyright)) ? false : $PAGE->theme->settings->copyright;
$hasfootnote = (empty($PAGE->theme->settings->footnote)) ? false : $PAGE->theme->settings->footnote;
$hastiles = (!empty($PAGE->theme->settings->tiles));
$haslogo = (empty($PAGE->theme->settings->logo)) ? false : $PAGE->theme->settings->logo;
$hasmarketing = (empty($PAGE->theme->settings->togglemarketing)) ? false : $PAGE->theme->settings->togglemarketing;
$hasquicklinks = (empty($PAGE->theme->settings->togglequicklinks)) ? false : $PAGE->theme->settings->togglequicklinks;
$hasfrontpagecontent = (empty($PAGE->theme->settings->frontpagecontent)) ? false : $PAGE->theme->settings->frontpagecontent;

if ($haslogo) {
	$logo = '<div id="logo"></div>';
} else {
	$logo = $SITE->shortname;
}


$knownregionpost = $PAGE->blocks->is_known_region('side-post');

$regions = bootstrap3_grid($hassidepost);
$PAGE->set_popup_notification_allowed(false);
$PAGE->requires->jquery();


echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
	<title><?php echo $OUTPUT->page_title(); ?></title>
	<link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
	<link href='http://fonts.googleapis.com/css?family=Roboto:400,300,700' rel='stylesheet' type='text/css'>
	<link href='http://fonts.googleapis.com/css?family=Bree+Serif' rel='stylesheet' type='text/css'>
	<?php echo $OUTPUT->standard_head_html() ?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $OUTPUT->body_attributes(); ?> onload="init()">

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<nav role="navigation" class="navbar navbar-default">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#moodle-navbar">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="<?php echo $CFG->wwwroot;?>"><?php echo $logo; ?></a>
		</div>

		<div id="moodle-navbar" class="navbar-collapse collapse">
			<?php echo $OUTPUT->custom_menu(); ?>
			<?php echo $OUTPUT->user_menu(); ?>
			<ul class="nav pull-right">
				<li><?php echo $OUTPUT->page_heading_menu(); ?></li>
			</ul>
		</div>
	</div>
</nav>

<div id="page">

	<!-- Start Slideshow -->
    <?php require_once(dirname(__FILE__).'/includes/slideshow.php'); ?>
	<!-- End Slideshow -->
	
    <header id="page-header" class="clearfix">
	    <!-- Start Frontpage Content -->

		    <div id="fontpagecontent" class="container">
			    <?php echo $hasfrontpagecontent; ?>
			</div>

	    <!-- End Frontpage Content -->
	    
        <div id="course-header">
            <?php echo $OUTPUT->course_header(); ?>
        </div>
    </header>
    
    
    
    
    <!-- Start Marketing Spots -->
    <?php 
    	if($hasmarketing==1) {
    		require_once(dirname(__FILE__).'/includes/marketing.php');
    	} else if($hasmarketing==2 && !isloggedin()) {
    		require_once(dirname(__FILE__).'/includes/marketing.php');
    	} else if($hasmarketing==3 && isloggedin()) {
    		require_once(dirname(__FILE__).'/includes/marketing.php');
    	} 
    ?>
    <!-- End Marketing Spots -->

    <div id="page-content" class="container">
	    <div id="region-main" class="<?php echo $regions['content']; ?>">
		    
		    <!-- Start Quick Links -->
		    <?php 
		    	if($hasquicklinks==1) {
		    		require_once(dirname(__FILE__).'/includes/quicklinks.php');
		    	} else if($hasquicklinks==2 && !isloggedin()) {
		    		require_once(dirname(__FILE__).'/includes/quicklinks.php');
		    	} else if($hasquicklinks==3 && isloggedin()) {
		    		require_once(dirname(__FILE__).'/includes/quicklinks.php');
		    	} 
		    ?>
		    <!-- End Quick Links -->

            <?php
            echo $OUTPUT->course_content_header();
            echo $OUTPUT->main_content();
            echo $OUTPUT->course_content_footer();
            ?>
        </div>

        <?php
        if ($hassidepost) {
            echo $OUTPUT->blocks('side-post', $regions['post']);
        }?>

		<?php if (is_siteadmin()) { ?>
        <div id="hidden-blocks" class="<?php echo $regions['content']; ?>">
        		<h4><?php echo get_string('visibleadminonly', 'theme_elegance') ?></h4>
        		<?php echo $OUTPUT->blocks('hidden-dock'); ?>
        </div>
        <?php } ?>
    </div>

</div>

<footer id="page-footer">
	<?php require_once(dirname(__FILE__).'/includes/footer.php'); ?>
</footer>

<?php echo $OUTPUT->standard_end_of_body_html() ?>

<script>
    $('body').show();
    $('.version').text(NProgress.version);
    NProgress.start();
    setTimeout(function() { NProgress.done(); $('.fade').removeClass('out'); }, 1000);

    $("#b-0").click(function() { NProgress.start(); });
    $("#b-40").click(function() { NProgress.set(0.4); });
    $("#b-inc").click(function() { NProgress.inc(); });
    $("#b-100").click(function() { NProgress.done(); });
    
    if(window.chrome) {
				$('.banner li').css('background-size', '100% 100%');
	}
	
    $('.banner').unslider({
				fluid: true,
				dots: true,
				speed: 600,
				keys: true,
				arrows: true,
    			prev: '<',
    			next: '>'
	});	
</script>

<script type="application/javascript">
function init() {

	var width = window.innerWidth;
	var height = window.innerHeight;

	// resize the canvas to fill browser window dynamically
	window.addEventListener('resize', function(event){
		width = window.innerWidth;
		height = window.innerHeight;

		sCtx.canvas.width = width;
		sCtx.canvas.height = height;
	});
//    window.addEventListener('focus', function(event){
//	    window.requestAnimationFrame(renderGame);
//    });

	var vX = 0.0;

	var statCanvas = document.getElementById("clouds");

	if (statCanvas.getContext)
	{
		var sCtx = statCanvas.getContext("2d");

		sCtx.canvas.width = width;
		sCtx.canvas.height = height;

		// Draw Gradient
		var grd=sCtx.createLinearGradient(0,0,0,height);
		grd.addColorStop(0,"#243769");
		grd.addColorStop(1,"#BBBDF3");
		sCtx.fillStyle=grd;

		// Draw Stars
		var starsImg = new Image();
		starsImg.src = '<?php echo $CFG->wwwroot;?>/theme/elegance/pix/bg/frontpage/stars.png';

		// Draw Clouds
		var cloudsImg = new Image();
		cloudsImg.src = '<?php echo $CFG->wwwroot;?>/theme/elegance/pix/bg/frontpage/clouds.png';

		(function renderGame() {
			window.requestAnimationFrame(renderGame);

			sCtx.clearRect(0, 0, width*2, height);

			sCtx.fillStyle=grd;
			sCtx.fillRect(0,0,width, height);

			sCtx.drawImage(starsImg, 0, 0, width, height);

			sCtx.drawImage(cloudsImg, vX, 0, width, height);
			sCtx.drawImage(cloudsImg, width+vX, 0, width, height);

			if (Math.abs(vX) > width) {
				vX = 0;
			}

			vX -= 0.1;
		}());
	}
}
</script>
<canvas id="clouds" style="position:absolute; bottom:0px; left: 0px; z-index:-1; width: 100%; height: 100%;"></canvas>
</body>
</html>
