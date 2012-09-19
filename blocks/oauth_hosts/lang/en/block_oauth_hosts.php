<?php
$string['oauth_hosts'] = 'OAuth Moodle Courses';
$string['mycourses'] = 'My Courses';
$string['configblockstyle'] = 'Block style';
$string['allcourses'] = 'List all the user\'s courses';
$string['usermoodles'] = 'List Moodles where user has courses';
$string['configtitle'] = 'Block title';
$string['edit_as_text'] = 'Edit all as delimited text';
$string['change_or_delete'] = 'Change or delete';
$string['block_oauth_url'] = 'URL of remote OAuth block';
$string['block_oauth_url_info'] = 'The full URL (including trailing /) of the directory containing land.php and info.php (required for peer and target servers)';
$string['peer_consumer_key'] = 'Consumer key recieved from peer';
$string['peer_consumer_key_info'] = 'The key string, typically the base URL, recieved with requests. (Required for peer and sourse servers.)';
$string['local_consumer_key'] = 'Consumer key sent to peer (blank for base URL)';
$string['local_consumer_key_info'] = 'The key sent with requests to the peer or target server from this server.';
$string['shared_secret'] = 'Shared secret';
$string['shared_secret_info'] = 'The shared secret which is used to sign messages between this pair of servers. (Must be identical on both servers.)';
$string['access_type'] = 'Access type';
$string['access_type_info'] = 'peer to allow users to swap; target for servers that do not list this server\'s courses; source for other systems that do not have courses.';
$string['block_oauth_allhosts'] = 'List Block OAuth url, peer consumer key, [local consumer key,] secret, and type as a space delimited list, with one server per line.';
$string['oauth_error'] = 'OAuth authentication failed - please contact an administrator.';
$string['oauth_manual_user'] = 'OAuth single-sign-on can not be used by manual users.';
$string['servers'] = 'Other OAuth SSO servers';
$string['servers_info'] = 'This is the list of base URLs for the oauth_hosts block on servers that will exchange sign-on using this system. (These do not have to be Moodle servers, but peer and target servers need to have equivalent land.php and info.php pages.)';
$string['db_settings'] = 'Database settings for external course list';
$string['use_ext_db'] = 'Use an external (shared) database for course lists';
$string['db_server'] = 'External database server name or IP address';
$string['db_name'] = 'Database name';
$string['db_user'] = 'Database username';
$string['db_password'] = 'Password';
$string['db_tablename'] = 'Course list table name';
$string['pluginname'] = 'OAuth Moodle Courses';
$string['nocoursesfound'] = 'No courses found';
?>

