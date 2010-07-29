<?php

function link2html($linkSrc, $todir='')
{
	$linkSrc = substr($linkSrc, 1, strlen($linkSrc)-2);
	$link = str_replace('%','_',rawurlencode($linkSrc)).'.html';

	//echo "**Link from $linkSrc becomes $link<br/>";
    return '<a href="'.$todir.$link.'">'.$linkSrc.'</a>';
}

?>
