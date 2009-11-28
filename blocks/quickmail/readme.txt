Quickmail v2.1 (designed for larger classes) README
Document created by Wen Hao Chuang (Email: wchuang@sfsu.edu)
Released on August 19, 2008

NOTE: This version (v2.1) has only been tested on Moodle 1.9.x and might only work with Moodle 1.9.x. 
For earlier version of moodle (1.5, 1.6, 1.7, 1.8), please use version v2.03

Please file your bug report directly to wchuang@sfsu.edu. Thanks!

Please note, similar to the original quickmail, this block DOES require Javascript be ENABLED in your Web browser. Please check with your Web browser setting (in FireFox, go to Tools -> Options -> Content -> Enable JavaScript) to make sure the Javascript is enabled
in your Web browser to ensure this block working correctly. Thanks! 

[Why this block?]:
   "The problem that we found here at San Francisco State University (SFSU) is
    that some of our faculty members have classes that have more than 1,000+
    students. The original quickmail Graphics User Interface (checkboxes) does not
    work well with our faculty's needs. We hacked the Interface (GUI) a little bit so that it
    becomes more user-friendly for larger classes."

Here are a list of files that were modified from the original quickmail block:

Added: selection.js (a javascript file that defines functions used by email.html) and /lang/help
Modified: email.html, email.php, config_instance.html, block_quickmail.php (minor modification)
Same: styles.php, \db\, and most part of \lang folder

Particularlly in this version (v2.1), I changed the email.html to use has_capability() function to replace the deprecated 
functions such as isteacher(), so that it would work better with Moodle 1.9.x.


Added/Removed features:
=======================
1. We removed the "Email history" feature but added a feature that instructors could use their
   external email client (e.g. Outlook, Eudora, thunderbird) for quickmail. The email receipients will be
   included in the BCC field, and the instructor's email will be in the To field. As we have many
   quickmail users we don't want to use our precious server space to keep all these logs (it can
   really add up if users use it very often with attachments, etc.)
2. We added a HELP button for this block, to enhance usability. This was based on our instructors'
   request as they were not quite sure what quickmail could do for them. We also added a help
   button to explain how they could use external email client to keep track of their quickmail
   history
3. We intentionally removed the "Group mode" settings. This is a decision based on our own needs. Please contact with me
   if you would like to re-enable the "group mode".

How to install:
===============
1. Unzip the quickmailv2.zip into your moodle "blocks" folder, create a folder called quickmail
   and put all your files in that folder
2. Move the block_quickmail.php included in \lang\en to your language folder (e.g. \lang\en_utf8).
   If you are using other languages you probably need a language package for proper translation.
   By the way, you might have some luck to get one from the previous quickmail v1. See:
   http://moodle.org/mod/data/view.php?d=13&rid=92
3. Put the \lang\en\help\*.html into the \lang\en\help (or \lang\en_utf8\help for Moodle 1.6 or above) 
   in your moodle installation.
   These are the help button content of the quickmail block.
   You could modify the content based on your needs.

How to uninstall:
=================
1. Simply remove the block\quickmail folder and related quickmail database tables and that's it!

Notes:
======
1. Currently we disabled the group feature, as this is still under testing now. If you are interested to help out with testing or rewriting, please contact with me (Wen).
2. There are two files that has identical filename: block_quickmail.php. One should be put under\lang\en, the other under \blocks\quickmail. These two files should not be confused.
3. Unless you know how to tweak the codes, otherwise we don't recommend installing two versions of quickmails on the same moodle installation.
4. You could remove the 
5. If you created a language pack that you would like me to include for the next release please send me the files, thanks!
6. The implementation of the database part (table) is exactly the same with previous Quickmail v1.
7. If you would like to have different view for the list of "Potential Recipient(s)," you could
   hack around line#88 in email.html code. Thanks for Art Lader contribute this quick question.

We hope that you will enjoy using this block. Thanks!

The newest version could be downloaded from:
http://moodle.org/mod/data/view.php?d=13&rid=764

Wen