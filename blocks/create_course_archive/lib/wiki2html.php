<?php
/**********************************************************************
Derived from WikiText, copyright (C) Niall S F Barr, 2007-2008
***********************************************************************/
include('link2html.php');

class wiki2html
{
	function process($wikiText, $htmlmode=0, $todir='')
	{
		$out = $wikiText;

    	if($htmlmode==0)
        {
			$out = preg_replace('/([^%])%%%\\s*$[\\n]/m',"$1<br/>",$out);
        }
	/****** split - conversion of the full markup text into lines to be processed */
	    $lines = explode("\n",$out);

	    // pre
	    for($n=0; $n<sizeof($lines); $n++)
	    {
		    if((strlen(trim($lines[$n]))>1)&&(substr($lines[$n],0,1)==" "))
		    {
		        $lines[$n] = "<:pre>".substr($lines[$n],1);
		    }
	    }

    	if($htmlmode==0)
        {
  	      // headings
	        for($n=0; $n<sizeof($lines); $n++)
		    {
			    $lines[$n] = preg_replace("/^!!!(.*)\\z/", "<:h2>\\1", $lines[$n]);
			    $lines[$n] = preg_replace("/^!!(.*)\\z/", "<:h3>\\1", $lines[$n]);
			    $lines[$n] = preg_replace("/^!(.*)\\z/", "<:h4>\\1", $lines[$n]);
		    }

		    // lists
		    for($n=0; $n<sizeof($lines); $n++)
		    {
			    $lines[$n] = preg_replace("/^(\*+)(.*)\\z/", "<:ul \\1>\\2", $lines[$n]);
			    $lines[$n] = preg_replace("/^(#+)(.*)\\z/", "<:ol \\1>\\2", $lines[$n]);
		    }
        }


		// Bold, italic and monospaced
	    for($n=0; $n<sizeof($lines); $n++)
	    {
		    $lines[$n] = preg_replace("/'''(.*?)'''/","<b>$1</b>",$lines[$n]);
		    $lines[$n] = preg_replace("/\*\*(.*?)\*\*/","<b>$1</b>",$lines[$n]);
		    $lines[$n] = preg_replace("/''(.*?)''/","<i>$1</i>",$lines[$n]);
		    //$lines[$n] = preg_replace("/@@(.*?)@@/","<code>$1</code>",$lines[$n]);
	    }

	    // Links
	    for($n=0; $n<sizeof($lines); $n++)
	    {
			//m2html - modified regex to use single [] rather than [[]] round links.
	    	$lines[$n] = preg_replace('%\\[[a-zA-Z0-9-_#+=~ ,./?&:]+(\\|[a-zA-Z0-9-_#+=.~;,?\\\\\\/@, ]+)?\\]%e',"link2html('$0', '$todir')", $lines[$n]);
	        $lines[$n] = preg_replace('/([^"\'])\\b((https?|ftp|file):\/\/[-A-Za-z0-9+&@#\/%?=~_|!:,.;]*[-A-Za-z0-9+&@#\/%=~_|])/','$1<a href="$2">$2</a>', $lines[$n]);
		}
	    //# process blocks...
	    $out = "";

        if($htmlmode==0)
        {
		    $lastBlock = "";
		    $listtypes = "";
		    for($n=0; $n<sizeof($lines); $n++)
		    {
		    	// Prepare to process a line.
		    	preg_match('/^<:(h1|h2|h3|h4|h5|h6|ul|ol|pre)\\s*([^>]*)>(.*)/',$lines[$n], $bhd);
			    if(sizeof($bhd)>1)
			    {
			        $lines[$n] = $bhd[3];
		            $blocktype = $bhd[1];
		        }
		        else
		        {
		        	if(strlen(trim($lines[$n]))>0)
		        		$blocktype = "p";
		            else
		            	$blocktype = "";
		        }
		        // end paragraphs, pre etc
		        if($lastBlock != $blocktype)
		        {
		        	if(($lastBlock == "p")||($lastBlock == "pre"))
		        		$out .= "</$lastBlock>\n";

		            if(($blocktype == "p")||($blocktype == "pre"))
		            	$out .= "<$blocktype>";
		        }

		        //echo "Blocktype $blocktype, last was $lastBlock<br>";

		        // First process lists
		        if(($blocktype=="ul")||($blocktype=="ol"))
		        {
			        $cdepth = strlen($bhd[2]);
		            $lt = substr($bhd[2],0,1); // # or *
			        $prevdepth = strlen($listtypes);
			        //echo "Depth $cdepth, was $prevdepth<br>";
		                $lines[$n] = "<li>".$lines[$n]."</li>";
		            if(($prevdepth!=$cdepth)||($blocktype!=$lastBlock))
		            {
		            	$wdepth = $prevdepth;
		                // close off lists 'til a matching type at depth
		                while(($wdepth > 0)&&(getLT($listtypes, $wdepth)!=$lt)&&($wdepth>$cdepth-1))
		                {
		                	$wtype = getLT($listtypes, $wdepth);
		                    if($wtype == "*")
		                    	$out .= "\n</ul>";
		                    else
		                    	$out .= "\n</ol>";
		                    $wdepth--;
		                    echo "depth now $wdepth<br/>";
		                }
		                $listtypes = substr($listtypes, 0, $wdepth);
		                // build up new list starts
		                while($wdepth < $cdepth)
		                {
		                    if($lt == "*")
		                    	$out .= "<ul>";
		                    else
		                    	$out .= "<ol>";
		                    $listtypes .= $lt;
		                    $wdepth++;
		                }

		            }
		            $depth = $cdepth;
		        }
		        else
		        {
			        $wdepth = strlen($listtypes);
		            while($wdepth > 0)
		            {
		                $wtype = getLT($listtypes, $wdepth);
		                if($wtype == "*")
		                    $out .= "\n</ul>";
		                else
		                    $out .= "\n</ol>";
		                $wdepth--;
		            }
		            $listtypes = "";
		        }

			    switch($blocktype)
			    {
		        case "ul": // lists prepared above
		        case "ol":
		        	break;
		        case "":
		        case "p":
		        case "pre":
		          	break;
		        default:
		            $lines[$n] = "<$blocktype>".$lines[$n]."</$blocktype>";
		            break;
			    }

			    $lastBlock = $blocktype;
			    $out .= $lines[$n]."\n";
		    }

		    // Restore fixed blocks ([--block_n--])
		    $out = preg_replace('/\\[--block_(\\d+)--]/e',"\$savedBlocks[$1];", $out);

        }
        else
        {
	        for($n=0; $n<sizeof($lines); $n++)
		    {
			    $out .= $lines[$n]."\n";
		    }
        }

		return $out;
	}
}

function getLT($liststr, $depth)
{
    if(strlen($liststr)>=$depth)
	    return substr($liststr, $depth-1, 1);
    else
        return " ";
}



?>
