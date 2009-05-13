<?php
// manage authentication
$CFG->auth = 'mnet,ldap';
$CFG->registerauth = 0;
$CFG->guestloginbutton = 1;
$CFG->alternateloginurl = '';
$CFG->forgottenpasswordurl = ''; // ** Is this right **
// $CFG->auth_instructions = '';
$CFG->allowemailaddresses = '';
$CFG->denyemailaddresses = '';
$CFG->verifychangedemail = 1;
$CFG->recaptchapublickey = '';
$CFG->recaptchaprivatekey = '';

// LDAP server
// can't do this at the moment

// Location settings
$CFG->timezone = 99; // Server's local time
$CFG->forcetimezone = 99; // Users can choose their own
$CFG->country = 'GB';

// Language settings
$CFG->autolang = 0;
$CFG->lang = 'en_utf8';
$CFG->langmenu = 0;
$CFG->langlist = '';
$CFG->langcache = 0;
$CFG->locale = 'en';
$CFG->latinexcelexport = 0;

// Site policies
$CFG->protectusernames = 1;
$CFG->forcelogin = 0;
$CFG->forceloginforprofiles = 1;
$CFG->opentogoogle = 0;
$CFG->maxbytes = 0; // server limit
$CFG->messaging = 0;
$CFG->allowobjectembed = 0;
$CFG->enabletrusttext = 0;
$CFG->maxeditingtime = 1800; // 30 minutes
$CFG->fullnamedisplay = 'firstname lastname';
$CFG->extendedusernamechars = 0;
$CFG->sitepolicy = 'http://moodle.gla.ac.uk/file.php/1/policy.html';
$CFG->bloglevel = 4; // all site users can see blog entries
$CFG->usetags = 1;
$CFG->keeptagnamecase = 1;
$CFG->cronclionly = 1;
$CFG->cronremotepassword = '';
$CFG->passwordpolicy = 0;
$CFG->minpasswordlength = 8;
$CFG->minpassworddigits = 1;
$CFG->minpasswordlower = 1;
$CFG->minpasswordupper = 1;
$CFG->minpasswordnonalphanum = 1;
$CFG->disableuserimages = 0;
$CFG->emailchangeconfirmation = 1;
$CFG->enablenotes = 1;

// HTTP security
// $CFG->loginhttps = 0;
$CFG->cookiesecure = 0;
$CFG->cookiehttponly = 0;

// Module security
$CFG->restrictmodulesfor = 'none';
$CFG->restrictbydefault = 0;
$CFG->defaultallowedmodules = array();

// Notifications
$CFG->displayloginfailures = 'admin';
$CFG->notifyloginfailures = '';
$CFG->notifyloginthreshold = 10;

// Anti-Virus
$CFG->runclamonupload = 0;
$CFG->pathtoclam = '';
$CFG->quarantinedir = '';
$CFG->clamfailureonupload = 'donothing'; // treat files as ok

// Theme settings
$CFG->themelist = '';
$CFG->allowuserthemes = 0;
$CFG->allowcoursethemes = 0;
$CFG->allowcategorythemes = 0;
$CFG->allowuserblockhiding = 0;
$CFG->showblocksonmodpages = 0;
$CFG->hideactivitytypenavlink = 0;

// Theme selector
// $CFG->theme = 'standardwhite';

// Calendar
$CFG->calendar_adminseesall = 0;
$CFG->calendar_site_timeformat = 0;
$CFG->calendar_startwday = 0; // Sunday
$CFG->calendar_weekend = 65; // Sunday, Saturday
$CFG->calendar_lookahead = 21;
$CFG->calendar_maxevents = 10;
$CFG->enablecalendarexport = 1;

// HTML editor
$CFG->htmleditor = 1;
$CFG->editorbackgroundcolor = '#ffffff';
$CFG->editorfontfamily = 'Trebuchet MS,Verdana,Arial,Helvetica,sans-serif';
$CFG->editorfontsize = '';
$CFG->editorfontlist = 'Trebuchet:Trebuchet MS,Verdana,Arial,Helvetica,sans-serif;Arial:arial,helvetica,sans-serif;Courier New:courier new,courier,monospace;Georgia:georgia,times new roman,times,serif;Tahoma:tahoma,arial,helvetica,sans-serif;Times New Roman:times new roman,times,serif;Verdana:verdana,arial,helvetica,sans-serif;Impact:impact;Wingdings:wingdings';
$CFG->editorkillword = 1;
$CFG->editorspelling = 1;
$CFG->editordictionary = 'en_GB';
$CFG->editorhidebuttons = '';
$CFG->emoticons = ':-){:}smiley{;}:){:}smiley{;}:-D{:}biggrin{;};-){:}wink{;}:-/{:}mixed{;}V-.{:}thoughtful{;}:-P{:}tongueout{;}B-){:}cool{;}^-){:}approve{;}8-){:}wideeyes{;}:o){:}clown{;}:-({:}sad{;}:({:}sad{;}8-.{:}shy{;}:-I{:}blush{;}:-X{:}kiss{;}8-o{:}surprise{;}P-|{:}blackeye{;}8-[{:}angry{;}xx-P{:}dead{;}|-.{:}sleepy{;}}-]{:}evil{;}(h){:}heart{;}(heart){:}heart{;}(y){:}yes{;}(n){:}no{;}(martin){:}martin{;}( ){:}egg';

// HTML settings
$CFG->formatstringstriptags = 1;

// MoodlDocs
$CFG->docroot = 'http://docs.moodle.org';
$CFG->doctonewwindow = 0;

// My moodle
$CFG->mymoodleredirect = 0;
$CFG->mycoursesperpage = 25;

// Course manager
// $CFG->coursemanager = 3; // ** varies per site

// AJAX and Javascript
$CFG->enableajax = 0;
$CFG->disablecourseajax = 1;

// User policies
$CFG->notloggedinroleid = 6; // guest
$CFG->guestroleid = $CFG->notloggedinroleid;
$CFG->defaultuserroleid = 7; // authenticated user
$CFG->nodefaultuserrolelists = 0;
$CFG->defaultcourseroleid = 5; // student
$CFG->creatornewroleid = 3; // teacher
$CFG->autologinguests = 0;
$CFG->nonmetacoursesyncroleids = array();
$CFG->hiddenuserfields = array();
$CFG->allowuserswitchrolestheycantassign = 0;

// System Paths
$CFG->gdversion = 2;
$CFG->zip = '/usr/bin/zip';
$CFG->unzip = '/usr/bin/unzip';
$CFG->pathtodu = '/usr/bin/du';
$CFG->aspellpath = '/usr/bin/aspell';

// Email
$CFG->smtphosts = 'mail-relay.gla.ac.uk';
$CFG->smtpuser = '';
$CFG->smtppass = '';
$CFG->smtpmaxbulk = 1;
$CFG->noreplyaddress = 'noreply@moodle.gla.ac.uk';
$CFG->digestmailtime = 17;
$CFG->sitemailcharset = 0; // UTF-8
$CFG->allowusermailcharset = 0;
$CFG->mailnewline = 'LF';
$CFG->supportname = 'Moodle Admin';
$CFG->supportemail = 'moodsup@udcf.gla.ac.uk';
$CFG->supportpage = '';

// Session Handling
$CFG->dbsessions = 0;
$CFG->sessiontimeout = 7200; // 2 hours
$CFG->sessioncookie = $CFG->dbname; // ** is this a good idea??
$CFG->sessioncookiepath = '/';
$CFG->sessioncookiedomain = '';

// RSS
$CFG->enablerssfeeds = 0;

// Debugging
// $CFG->debug = 0;
// $CFG->debugdisplay = 0;
$CFG->xmlstrictheaders = 0;
$CFG->debugsmtp = 0;
$CFG->perfdebug = 0;

// Statistics
$CFG->enablestats = 0;

// HTTP
$CFG->framename = '_top';
$CFG->slasharguments = 1;
$CFG->getremoteaddrconf = 0;
$CFG->proxyhost = 'wwwcache.gla.ac.uk';
$CFG->proxyport = '8080';
$CFG->proxytype = 'HTTP';
$CFG->proxyuser = '';
$CFG->proxypassword = '';

// Cleanup
$CFG->loglifetime = 0;

// Performance
$CFG->extramemorylimit = '512M';
$CFG->cachetype = 0;
$CFG->rcache = 0;
$CFG->rcachettl = 10;
$CFG->intcachemax = 10;
$CFG->memcachedhosts = '';
$CFG->memcachedpconn = 0;

// Experimental
$CFG->enableglobalsearch = 0;
$CFG->smartpix = 0;
$CFG->enablehtmlpurifier = 0;
$CFG->enablegroupings = 1;
$CFG->selectmanual = 0;
$CFG->experimentalsplitrestore = 0;
?>
