-------------------------------------------------------------------------------
SIDE BAR BLOCK
-------------------------------------------------------------------------------
2006.03.01
The modification contained herein was provided by Open Knowledge Technologies
(http://www.oktech.ca/) and Fernando Oliveira of MoodleFN and First Nations
Schools.

Contributors:
Justin Filip (jfilip@oktech.ca)
Mike Churchward (mike@oktech.ca)
Fernando Oliveira (fernandooliveira@knet.ca)

-------------------------------------------------------------------------------
INSTALLATION:

To install and use, unzip this file into your Moodle root directory making sure
that you 'use folder names'. This will create the following files:

/blocks/side_bar/lang/en/block_side_bar.php
/blocks/side_bar/block_side_bar.php
/blocks/side_bar/block_side_bar-README.txt
/blocks/side_bar/config_global.html
/blocks/side_bar/config_instance.html

Visit your admin section to complete installation of the block. 

-------------------------------------------------------------------------------
FUNCTION:

This block allows you to create separate activities and resources in a course
that do not have to appear in course sections. The block can have multiple
instances of it within a course - each instance will have its own unique group
of activities and resources. Each instance can also have its own configured 
title.

It functions by creating course sections for each instance, starting at a number
beyond what would normally be used by a course. This defaults to section 1000,
but is configurable at the global level.

All resources and activities within a block can be edited and moved around just
like normal activities when editing is turned on. Adding label resources allows
you to add text to the blocks as well.

In a sense, this block combined the main menu block functions and HTML block
functions into one block that can be used in a course.
