-------------------------------------------------------------------------------
MY COURSES 2 BLOCK
-------------------------------------------------------------------------------
2009.2.26
This block created by Nate Baxley (nbaxley@illinois.edu) at the University of Illinois College of Education

Contributors:
Nate Baxley (nbaxley@illinois.edu)

-------------------------------------------------------------------------------
INSTALLATION:

To install and use, unzip this file into your Moodle root directory making sure
that you 'use folder names'. This will create the following files:

/blocks/my_courses/lang/en_utf8/block_my_courses.php
/blocks/my_courses/db/install.xml
/blocks/my_courses/ajax_SaveStatus.php
/blocks/my_courses/README.txt
/blocks/my_courses/block_my_courses.php
/blocks/my_courses/styles.php

Login with administrator privileges and go to the Notifications link in the Site Administration block.

-------------------------------------------------------------------------------
FUNCTION:

This block replaces the course_list block.  It offers the advantage of being able to collapse
courses by category and remember the collapsed status from session to session.

The block attempts to find the "current" category and expand that one by default the first 
time a user visits the site.  After that it will look to their saved state.