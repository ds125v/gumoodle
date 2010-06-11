<?php

echo "<script type=\"text/javascript\" src=\"{$CFG->wwwroot}/filter/mediaplugin/audio-player.js\"></script>\n";

echo "<script type=\"text/javascript\">\n";
echo "    AudioPlayer.setup(\"{$CFG->wwwroot}/filter/mediaplugin/mp3player.swf\", {width: 290});\n";
echo "</script>\n";
