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

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo $PAGE->title ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->pix_url('favicon', 'theme')?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
</head>
<body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses.' '.join(' ', $bodyclasses)) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>

<div id="page">

<!-- START OF HEADER -->

    <?php if ($hasheading || $hasnavbar) { ?>
    <div id="wrapper" class="clearfix">

        <div id="page-header">
            <div id="page-header-wrapper" class="clearfix">
                   <?php if ($hasheading) { ?>
                <h1 class="headermain"><?php echo $PAGE->heading ?></h1>
 <div id="siteTools">
      <h3>Site tools</h3>
      <ul>
      <li id="stPageId1"><a href="http://www.gla.ac.uk/subjects/">Subjects A-Z</a></li>
      <li id="stPageId2"><a href="http://www.gla.ac.uk/stafflist/">Staff A-Z</a></li>
      <li id="stPageId3"><a href="http://www.gla.ac.uk/academic/">Academic units A-Z</a></li>
      </ul>
    </div>
                    <?php
                        echo $OUTPUT->login_info();
                           echo $PAGE->headingmenu;
                    ?>
<div id="head-menu">
<ul>
<li><a title="Courses" href="#">Courses</a></li>
<li><a title="Research" href="#">Research</a></li>
<li><a title="About us" href="#">About us</a></li>
<li><a title="Student life" href="#">Student life</a></li>
<li><a title="Alumni" href="#">Alumni</a></li>
<li><a title="Support us" href="#">Support us</a></li>
<li><a title="Contact" href="#">Contact</a></li>
</ul>
</div>
</div>
                <?php } ?>
            </div>
        </div>


<?php } ?>

<!-- END OF HEADER -->

<!-- START OF CONTENT -->

        <div id="page-content-wrapper" class="clearfix">
            <div id="page-content">
		<div class="content-header"><h1><?php echo $PAGE->heading ?></h1></div>
                <div id="region-main-box" >
                <div id="region-post-box" >

                        <div id="region-main-wrap">
                            <div id="region-main">
<div class="yui3-g">
                                <div class="region-content yui3-u">
                         <div class="yui3-g">
<div class="yui3-u"> 
        <?php if ($hasnavbar) { ?>
            <div class="navbar clearfix">
                <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
                <div class="navbutton"> <?php echo $PAGE->button; ?></div>
            </div>
        <?php } ?>
           <?php echo $OUTPUT->main_content() ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($hassidepre) { ?>
                        <div id="region-pre" class="yui3-u-5-24">
                            <div class="region-content">
                                <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                            </div>
                        </div>
                        <?php } ?>

                        <?php if ($hassidepost) { ?>
                        <div id="region-post" class="yui3-u-5-24">
                            <div class="region-content">
                                <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                            </div>
                        </div>
                        </div>
                        <?php } ?>

                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- END OF CONTENT -->

<!-- START OF FOOTER -->

        <?php if ($hasfooter) { ?>
        <div id="page-footer" class="clearfix">
<div class="backToTop curvyRedraw"><a href="#">Back to top</a></div>
<div class="clear"></div>
    
<!-- navigation object : 0k Generic Footer addres -->   <div class="contact">
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


<!-- navigation object : 0k Generic Footer links --><ul>
            <li class="helplink"><?php echo page_doc_link(get_string('moodledocslink')) ?></li>
            <?php
                echo '<li>'.$OUTPUT->login_info().'</li>';
                echo '<li>'.$OUTPUT->home_link().'</li>';
            ?>
      <li id="fPageId1"><a href="http://www.gla.ac.uk/about/accessibility/">Accessibility</a></li>
      <li id="fPageId2"><a href="http://www.gla.ac.uk/legal/disclaimer/">Disclaimer</a></li>
      <li id="fPageId3"><a href="http://www.gla.ac.uk/foi/">Freedom of information</a></li>
      <li id="fPageId4"><a href="http://www.gla.ac.uk/legal/freedomofinformation/foipublicationscheme/">FOI publication scheme</a></li>
      <li id="fPageId5"><a href="http://www.gla.ac.uk/legal/privacy/">Privacy and cookies</a></li>
      <li id="fPageId6"><a href="http://www.gla.ac.uk/legal/copyright/">&copy; UofG</a></li>
</ul>
            <?php
                echo $OUTPUT->standard_footer_html();
            ?>
        <?php } ?>

    <?php if ($hasheading || $hasnavbar) { ?>
        </div> <!-- END #wrapper -->
    <?php } ?>
<div class="clear"></div>
</div> <!-- END #page -->

<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
