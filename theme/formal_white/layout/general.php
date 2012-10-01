<?php

defined('MOODLE_INTERNAL') || die();

$hasheading = $PAGE->heading;
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));

$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);

$showsidepre = $hassidepre && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT);
$showsidepost = $hassidepost && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT);

$custommenu = $OUTPUT->custom_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$bodyclasses = array();
if ($showsidepre && !$showsidepost) {
    $bodyclasses[] = 'side-pre-only';
} else if ($showsidepost && !$showsidepre) {
    $bodyclasses[] = 'side-post-only';
} else if (!$showsidepost && !$showsidepre) {
    $bodyclasses[] = 'content-only';
}

if ($hascustommenu) {
    $bodyclasses[] = 'has_custom_menu';
}

/************************************************************************************************/
if (!empty($PAGE->theme->settings->customlogourl)) {
    $logourl = $PAGE->theme->settings->customlogourl;
    if (strtolower(substr($logourl, 0, 4)) != 'http') {
        $logourl = $CFG->wwwroot.'/'.$logourl;
    }
} else {
    $logourl = $OUTPUT->pix_url('logo_small', 'theme');
}

$hasframe = !isset($PAGE->theme->settings->noframe) || !$PAGE->theme->settings->noframe;

$displaylogo = !isset($PAGE->theme->settings->headercontent) || $PAGE->theme->settings->headercontent;
/************************************************************************************************/

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo $PAGE->title ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->pix_url('favicon', 'theme')?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
</head>
<body id="<?php p($PAGE->bodyid) ?>" class="<?php p($PAGE->bodyclasses.' '.join(' ', $bodyclasses)) ?>">
    <?php echo $OUTPUT->standard_top_of_body_html(); ?>
    <div id="page">

    <?php if ($hasframe) { ?>
        <div id="frametop">
            <div id="framebottom">
                <div id="frametopright">
                    <div>&nbsp;</div>
                </div>
                <div id="frameleft">
                    <div id="frameright">
                        <div id="wrapper">
<?php } ?>

<!-- begin of page-header -->
                            <?php if ($hasheading) { ?>
                            <div id="page-header">
                            <?php if ($displaylogo) { ?>
                                <div id="headerlogo">
                                    <img src="<?php echo $logourl ?>" alt="Custom logo here" />
                                </div>
                            <?php } else { ?>
                                <h1 class="headerheading"><?php echo $PAGE->heading ?></h1>
                            <?php } ?>

                                <div class="headermenu">
                                    <?php
                                    echo $OUTPUT->login_info();
                                    if (($CFG->langmenu) && (!empty($PAGE->layout_options['langmenu']))) {
                                        echo $OUTPUT->lang_menu();
                                    }
                                    echo $PAGE->headingmenu;
                                ?>
                                </div>
                            </div>
                            <?php } ?>
<!-- end of page-header -->

<!-- begin of custom menu -->
                            <?php if ($hascustommenu) { ?>
                            <div id="custommenu"><?php echo $custommenu; ?></div>
                            <?php } ?>
<!-- end of custom menu -->

<!-- begin of navigation bar -->
                            <?php if ($hasnavbar) { ?>
                            <div class="navbar clearfix">
                                <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
                                <div class="navbutton"><?php echo $PAGE->button; ?></div>
                            </div>
                            <?php } ?>
<!-- end of navigation bar -->

<!-- start of moodle content -->
                            <div id="page-content">
                                <div id="region-main-box">
                                    <div id="region-post-box">

                                        <!-- main mandatory content of the moodle page  -->
                                        <div id="region-main-wrap">
                                            <div id="region-main">
                                                <div class="region-content">
                                                    <?php echo $OUTPUT->main_content() ?>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- end of main mandatory content of the moodle page -->

                                        <!-- left column block - diplayed only if... -->
                                        <?php if ($hassidepre) { ?>
                                        <div id="region-pre" class="block-region">
                                            <div class="region-content">
                                                <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                                            </div>
                                        </div>
                                        <?php } ?>
                                        <!-- end of left column block - diplayed only if... -->

                                        <!-- right column block - diplayed only if... -->
                                        <?php if ($hassidepost) { ?>
                                        <div id="region-post" class="block-region">
                                            <div class="region-content">
                                                <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                                            </div>
                                        </div>
                                        <?php } ?>
                                        <!-- end of right column block - diplayed only if... -->

                                    </div>
                                </div>
                            </div>
<!-- end of moodle content -->

                            <div class="clearfix"></div>

<?php if ($hasframe) { ?>
                        </div> <!-- </wrapper> -->
                    </div> <!-- </frameright> -->
                </div> <!-- </frameleft> -->
                <div id="framebottomright">
                    <div>&nbsp;</div>
                </div>
            </div> <!-- </framebottom> -->
        </div> <!-- </frametop> -->

<?php }

if ($hasfooter) {
    if ($hasframe) { ?>

        <!-- START OF FOOTER -->
        <div id="page-footer">
            <?php if (!empty($PAGE->theme->settings->footnote)) { ?>
            <div id="footerframetop">
                <div id="footerframebottom">
                    <div id="footerframetopright">
                        <div>&nbsp;</div>
                    </div>
                    <div id="footerframeleft">
                        <div id="footerframeright">

                            <!-- the content to show -->
                            <div id="footerwrapper">
                                <?php echo $PAGE->theme->settings->footnote; ?>
                            </div> <!-- </footerwrapper> -->

                        </div> <!-- </footerframeright> -->
                    </div> <!-- </footerframeleft> -->
                    <div id="footerframebottomright">
                        <div>&nbsp;</div>
                    </div>
                </div> <!-- </footerframebottom> -->
            </div> <!-- </footerframetop> -->
            <?php }
            //one more div is waiting to be closed

    } else { ?>

        <!-- START OF FOOTER -->
        <div id="page-footer" class="noframefooter">
            <?php if (!empty($PAGE->theme->settings->footnote)) { ?>
            <div id="page-footer-content">

                <!-- the content to show -->
                <div id="footerwrapper">
                    <?php echo $PAGE->theme->settings->footnote; ?>
                </div> <!-- </footerwrapper> -->

            </div> <!-- </page-footer-content> -->
            <?php }
            //one more div is waiting to be closed

    } ?>
            <div class="moodledocsleft">
                <?php
                echo $OUTPUT->login_info();
                //echo $OUTPUT->home_link();
                ?>
                <div class="moodledocs">
                    <?php echo page_doc_link(get_string('moodledocslink')); ?>
                </div>
                <?php
                if ($PAGE->theme->settings->creditstomoodleorg == 2) {
                    // can not use $OUTPUT->home_link() here because whether $OUTPUT->page->pagetype != 'site-index'
                    // the output of the function is not the classic nice moodle logo $this->pix_url('moodlelogo')
                ?>
                    <div class="sitelink">
                        <a title="Moodle" href="http://moodle.org/">
                            <img style="width:100px;height:30px" src="<?php echo $this->pix_url('moodlelogo') ?>" alt="moodlelogo" />
                        </a>
                    </div>
                <?php
                }
                echo $OUTPUT->standard_footer_html();
                ?>
            </div>
        </div> <!-- </page-footer> -->
    </div> <!-- </page"> -->

    <div class="clearfix"></div>

<?php  } //the waiting div has been closed: </page-footer>
    echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>