<?php

function isEmbedded($resource, &$type=false) {
    $mimetype = mimeinfo("type", $resource->reference);
    $embedded = false;
    $resourcetype='unknown';
    if ($resource->options != "forcedownload") { // TODO nicolasconnault 14-03-07: This option should be renamed "embed"
        if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) { // It's an image
            $resourcetype = "image";
            $embedded = true;
        } else if ($mimetype == "audio/mp3") { // It's an MP3 audio file
            $resourcetype = "mp3";
            $embedded = true;
        } else if ($mimetype == "video/x-flv") { // It's a Flash video file
            $resourcetype = "flv";
            $embedded = true;
        } else if (substr($mimetype, 0, 10) == "video/x-ms") { // It's a Media Player file
            $resourcetype = "mediaplayer";
            $embedded = true;
        } else if ($mimetype == "video/quicktime") { // It's a Quicktime file
            $resourcetype = "quicktime";
            $embedded = true;
        } else if ($mimetype == "application/x-shockwave-flash") { // It's a Flash file
            $resourcetype = "flash";
            $embedded = true;
        } else if ($mimetype == "video/mpeg") { // It's a Mpeg file
            $resourcetype = "mpeg";
            $embedded = true;
        } else if ($mimetype == "text/html") { // It's a web page
            $resourcetype = "html";
        } else if ($mimetype == "application/zip") { // It's a zip archive
            $resourcetype = "zip";
            $embedded = true;
        } else if ($mimetype == 'application/pdf' || $mimetype == 'application/x-pdf') {
            $resourcetype = "pdf";
            $embedded = true;
        } else if ($mimetype == "audio/x-pn-realaudio") { // It's a realmedia file
            $resourcetype = "rm";
            $embedded = true;
        }
    }
    if($type!==false) $type = $resourcetype;
    return $embedded;
}

function getEmbededFileCode($resource, $resourcetype, $fullurl) {
	global $CFG;
    $out = '';
    if ($resourcetype == "image") {
        $out .= '<div class="resourcecontent resourceimg">';
        $out .= "<img title=\"".strip_tags(format_string($resource->name,true))."\" class=\"resourceimage\" src=\"$fullurl\" alt=\"\" />";
        $out .= '</div>';
    } else if ($resourcetype == "mp3") {
        if (!empty($THEME->resource_mp3player_colors)) {
            $c = $THEME->resource_mp3player_colors; // You can set this up in your theme/xxx/config.php
        } else {
            $c = 'bgColour=000000&btnColour=ffffff&btnBorderColour=cccccc&iconColour=000000&'. 'iconOverColour=00cc00&trackColour=cccccc&handleColour=ffffff&loaderColour=ffffff&'. 'font=Arial&fontColour=FF33FF&buffer=10&waitForPlay=no&autoPlay=yes';
        }
        $c .= '&volText='.get_string('vol', 'resource').'&panText='.get_string('pan','resource');
        $c = htmlentities($c);
        $id = 'filter_mp3_'.time(); //we need something unique because it might be stored in text cache
        $cleanurl = addslashes_js($fullurl);
        // If we have Javascript, use UFO to embed the MP3 player, otherwise depend on plugins
        $out .= '<div class="resourcecontent resourcemp3">';
        $out .= '<span class="mediaplugin mediaplugin_mp3" id="'.$id.'"></span>'. '<script type="text/javascript">'."\n". '//<![CDATA['."\n". 'var FO = { movie:"'.$CFG->wwwroot.'/lib/mp3player/mp3player.swf?src='.$cleanurl.'",'."\n". 'width:"600", height:"70", majorversion:"6", build:"40", flashvars:"'.$c.'", quality: "high" };'."\n". 'UFO.create(FO, "'.$id.'");'."\n". '//]]>'."\n". '</script>'."\n";
        $out .= '<noscript>';
        $out .= "<object type=\"audio/mpeg\" data=\"$fullurl\" width=\"600\" height=\"70\">";
        $out .= "<param name=\"src\" value=\"$fullurl\" />";
        $out .= '<param name="quality" value="high" />';
        $out .= '<param name="autoplay" value="true" />';
        $out .= '<param name="autostart" value="true" />';
        $out .= '</object>';
        $out .= '<p><a href="' . $fullurl . '">' . $fullurl . '</a></p>';
        $out .= '</noscript>';
        $out .= '</div>';
    } else if ($resourcetype == "flv") {
        $id = 'filter_flv_'.time(); //we need something unique because it might be stored in text cache
        $cleanurl = addslashes_js($fullurl);
        // If we have Javascript, use UFO to embed the FLV player, otherwise depend on plugins
        $out .= '<div class="resourcecontent resourceflv">';
        $out .= '<span class="mediaplugin mediaplugin_flv" id="'.$id.'"></span>'. '<script type="text/javascript">'."\n". '//<![CDATA['."\n". 'var FO = { movie:"'.$CFG->wwwroot.'/filter/mediaplugin/flvplayer.swf?file='.$cleanurl.'",'."\n". 'width:"600", height:"400", majorversion:"6", build:"40", allowscriptaccess:"never", quality: "high" };'."\n". 'UFO.create(FO, "'.$id.'");'."\n". '//]]>'."\n". '</script>'."\n";
        $out .= '<noscript>';
        $out .= "<object type=\"video/x-flv\" data=\"$fullurl\" width=\"600\" height=\"400\">";
        $out .= "<param name=\"src\" value=\"$fullurl\" />";
        $out .= '<param name="quality" value="high" />';
        $out .= '<param name="autoplay" value="true" />';
        $out .= '<param name="autostart" value="true" />';
        $out .= '</object>';
        $out .= '<p><a href="' . $fullurl . '">' . $fullurl . '</a></p>';
        $out .= '</noscript>';
        $out .= '</div>';
    } else if ($resourcetype == "mediaplayer") {
        $out .= '<div class="resourcecontent resourcewmv">';
        $out .= '<object type="video/x-ms-wmv" data="' . $fullurl . '">';
        $out .= '<param name="controller" value="true" />';
        $out .= '<param name="autostart" value="true" />';
        $out .= "<param name=\"src\" value=\"$fullurl\" />";
        $out .= '<param name="scale" value="noScale" />';
        $out .= "<a href=\"$fullurl\">$fullurl</a>";
        $out .= '</object>';
        $out .= '</div>';
    } else if ($resourcetype == "mpeg") {
        $out .= '<div class="resourcecontent resourcempeg">';
        $out .= '<object classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95"
                      codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsm p2inf.cab#Version=5,1,52,701"
                      type="application/x-oleobject">';
        $out .= "<param name=\"fileName\" value=\"$fullurl\" />";
        $out .= '<param name="autoStart" value="true" />';
        $out .= '<param name="animationatStart" value="true" />';
        $out .= '<param name="transparentatStart" value="true" />';
        $out .= '<param name="showControls" value="true" />';
        $out .= '<param name="Volume" value="-450" />';
        $out .= '<!--[if !IE]>-->';
        $out .= '<object type="video/mpeg" data="' . $fullurl . '">';
        $out .= '<param name="controller" value="true" />';
        $out .= '<param name="autostart" value="true" />';
        $out .= "<param name=\"src\" value=\"$fullurl\" />";
        $out .= "<a href=\"$fullurl\">$fullurl</a>";
        $out .= '<!--<![endif]-->';
        $out .= '<a href="' . $fullurl . '">' . $fullurl . '</a>';
        $out .= '<!--[if !IE]>-->';
        $out .= '</object>';
        $out .= '<!--<![endif]-->';
        $out .= '</object>';
        $out .= '</div>';
    } else if ($resourcetype == "rm") {
        $out .= '<div class="resourcecontent resourcerm">';
        $out .= '<object classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" width="320" height="240">';
        $out .= '<param name="src" value="' . $fullurl . '" />';
        $out .= '<param name="controls" value="All" />';
        $out .= '<!--[if !IE]>-->';
        $out .= '<object type="audio/x-pn-realaudio-plugin" data="' . $fullurl . '" width="320" height="240">';
        $out .= '<param name="controls" value="All" />';
        $out .= '<a href="' . $fullurl . '">' . $fullurl .'</a>';
        $out .= '</object>';
        $out .= '<!--<![endif]-->';
        $out .= '</object>';
        $out .= '</div>';
    } else if ($resourcetype == "quicktime") {
        $out .= '<div class="resourcecontent resourceqt">';
        $out .= '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"';
        $out .= '        codebase="http://www.apple.com/qtactivex/qtplugin.cab">';
        $out .= "<param name=\"src\" value=\"$fullurl\" />";
        $out .= '<param name="autoplay" value="true" />';
        $out .= '<param name="loop" value="true" />';
        $out .= '<param name="controller" value="true" />';
        $out .= '<param name="scale" value="aspect" />';
        $out .= '<!--[if !IE]>-->';
        $out .= "<object type=\"video/quicktime\" data=\"$fullurl\">";
        $out .= '<param name="controller" value="true" />';
        $out .= '<param name="autoplay" value="true" />';
        $out .= '<param name="loop" value="true" />';
        $out .= '<param name="scale" value="aspect" />';
        $out .= '<!--<![endif]-->';
        $out .= '<a href="' . $fullurl . '">' . $fullurl . '</a>';
        $out .= '<!--[if !IE]>-->';
        $out .= '</object>';
        $out .= '<!--<![endif]-->';
        $out .= '</object>';
        $out .= '</div>';
    } else if ($resourcetype == "flash") {
        $out .= '<div class="resourcecontent resourceswf">';
        $out .= '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">';
        $out .= "<param name=\"movie\" value=\"$fullurl\" />";
        $out .= '<param name="autoplay" value="true" />';
        $out .= '<param name="loop" value="true" />';
        $out .= '<param name="controller" value="true" />';
        $out .= '<param name="scale" value="aspect" />';
        $out .= '<!--[if !IE]>-->';
        $out .= "<object type=\"application/x-shockwave-flash\" data=\"$fullurl\">";
        $out .= '<param name="controller" value="true" />';
        $out .= '<param name="autoplay" value="true" />';
        $out .= '<param name="loop" value="true" />';
        $out .= '<param name="scale" value="aspect" />';
        $out .= '<!--<![endif]-->';
        $out .= '<a href="' . $fullurl . '">' . $fullurl . '</a>';
        $out .= '<!--[if !IE]>-->';
        $out .= '</object>';
        $out .= '<!--<![endif]-->';
        $out .= '</object>';
        $out .= '</div>';
    }
    elseif ($resourcetype == 'zip') {
        $out .= '<div class="resourcepdf">';
        $out .= get_string('clicktoopen', 'resource') . '<a href="' . $fullurl . '">' . format_string($resource->name) . '</a>';
        $out .= '</div>';
    }
    elseif ($resourcetype == 'pdf') {
        $out .= '<div class="resourcepdf">';
        $out .= '<object data="' . $fullurl . '" type="application/pdf">';
        $out .= get_string('clicktoopen', 'resource') . '<a href="' . $fullurl . '">' . format_string($resource->name) . '</a>';
        $out .= '</object>';
        $out .= '</div>';
    }
    return $out;
}
?>

