<?php

$OUTPUT->doctype(); // throw it away to avoid warning
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
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
/* <html <?php echo $OUTPUT->htmlattributes() ?>> */
?><!DOCTYPE html>
<html>
<head>
    <title><?php echo $PAGE->title ?></title>
    <link rel="shortcut icon" href="<?php echo $favicon_url ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
<!--[if lt IE 9]>
<script src="<?php echo new moodle_url("/theme/bootstrap/html5shiv.js")?>"></script>
<![endif]-->
</head>
<body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses.' '.join(' ', $bodyclasses)) ?>">

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<div id="page">
            <header>
		<h1 class="headermain">header goes here</h1>
                    <?php echo $OUTPUT->login_info() ?>
			<?php echo $PAGE->headingmenu ?>
	    </header>



            <div class="navbar">
                <div class="breadcrumb"><?php echo $OUTPUT->navbar() ?></div>
                <div class="navbutton"> <?php echo $PAGE->button ?></div>
            </div>
    <div id="page-content">

	<h1><?php echo $PAGE->heading ?></h1>

            <div id="layout" class="yui3-g">
		<?php if ($hasblocks1) { ?>
		    <div id="blocks1" class="yui3-u"><div class="region-content"> <?php echo $OUTPUT->blocks_for_region('side-pre') ?></div></div>
		<?php } ?>

	        <div id="main" class="yui3-u"><div class="region-content"> <?php echo $OUTPUT->main_content() ?></div></div>

		<?php if ($hasblocks2) { ?>
		    <div id="blocks2" class="yui3-u"><div class="region-content"> <?php echo $OUTPUT->blocks_for_region('side-post') ?></div></div>
		<?php } ?>

	   </div>
    </div>
<footer>
<p class="pull-right"><a href="#">Back to top</a>
<p class="helplink"><?php echo page_doc_link(get_string('moodledocslink')) ?></p>
<?php
echo $OUTPUT->login_info();
echo $OUTPUT->home_link();
echo $OUTPUT->standard_footer_html();
?>
</footer> 

</div>
<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
