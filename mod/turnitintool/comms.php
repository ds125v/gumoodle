<?php
/**
 * The Turnitin API Communication Class
 *
 * @package turnitintool
 * @subpackage classes
 * @copyright 2010 nLearning Ltd
 */
class turnitintool_commclass {
    /**
     * @var string $apiurl The API Url
     */
    var $apiurl;
    /**
     * @var boolean $encrypt The encrypt parameter for API calls
     */
    var $encrypt;
    /**
     * @var int $accountid The Account ID parameter for API calls
     */
    var $accountid;
    /**
     * @var int $utp The user type parameter for API calls
     */
    var $utp;
    /**
     * @var int $uid The User ID parameter for API calls
     */
    var $uid;
    /**
     * @var string $ufn The User Firstname parameter for API calls
     */
    var $ufn;
    /**
     * @var string $uln The User Lastname parameter for API calls
     */
    var $uln;
    /**
     * @var string $uem The User Email parameter for API calls
     */
    var $uem;
    /**
     * @var string $tiisession Turnitin Session ID parameter for API calls
     */
    var $tiisession;
    /**
     * @var object $loaderbar The Loader Bar Object NULL if no loaderbar is to be displayed
     */
    var $loaderbar;
    /**
     * @var string $result The entires xml result from the API call
     */
    var $result;
    /**
     * @var int $rcode The RCODE returned by the API call
     */
    var $rcode;
    /**
     * @var string $rmessage The RMESSAGE returned by the API call
     */
    var $rmessage;
	/**
	 * A backward compatible constructor / destructor method that works in PHP4 to emulate the PHP5 magic method __construct
	 */
    function turnitintool_commclass($iUid,$iUfn,$iUln,$iUem,$iUtp,&$iLoaderBar){
        if (version_compare(PHP_VERSION,"5.0.0","<")) {
            $this->__construct($iUid,$iUfn,$iUln,$iUem,$iUtp,$iLoaderBar);
        }
    }
    /**
     * The constructor for the class, Calls the startsession() method if we are using sessions
     * 
     * @param int $iUid The User ID passed in the class creation call
     * @param string $iUfn The User First Name passed in the class creation call
     * @param string $iUln The User Last Name passed in the class creation call
     * @param string $iUem The User Email passed in the class creation call
     * @param int $iUtp The User Type passed in the class creation call
     * @param object $iLoaderBar The Loader Bar object passed in the class creation call (may be NULL if no loaderbar is to be used)
     * @param boolean $iUseSession Determines whether we start a session for this call (set to false for SSO calls)
     */
    function __construct($iUid,$iUfn,$iUln,$iUem,$iUtp,&$iLoaderBar) {
        global $CFG;
        $this->callback=false;
        $this->apiurl=$CFG->turnitin_apiurl;
        $this->accountid=$CFG->turnitin_account_id;
        $this->uid=$iUid;
        $this->ufn=$iUfn;
        $this->uln=$iUln;
        $this->uem=$iUem;
        $this->utp=$iUtp;
        $this->loaderbar =& $iLoaderBar;
    }
    /**
     * Calls FID1, FCMD 2 with create_session set to 1 in order to create a session for this user / object call
     */
    function startSession() {
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fid'=>1,
                         'fcmd'=>2,
                         'utp'=>$this->utp,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['create_session']=1;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true);
        $this->tiisession=$this->getSessionid();
    }
    /**
     * Calls FID18, FCMD 2 to kill the session for this user / object call
     */
    function endSession() {
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fid'=>18,
                         'fcmd'=>2,
                         'utp'=>$this->utp,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true);
    }
    /**
     * Converts XML string to array keys (__xmlkeys) and values (__xmlvalues)
     *
     * @param string $string
     * @return boolean
     */
    function xmlToArray($string) {
        $xml_parser = xml_parser_create();
        $output=xml_parse_into_struct($xml_parser, $string, $this->_xmlvalues, $this->_xmlkeys);
        xml_parser_free($xml_parser);
        return $output;
    }
    /**
     * Returns a multidimensional array built in the format array[OBJECTID][fieldname]
     *
     * @return array
     */
    function getSubmissionArray() {
        $output=array();
        $xmlcall=$this->xmlToArray($this->result);
        if (isset($this->_xmlkeys['OBJECTID']) AND is_array($this->_xmlkeys['OBJECTID'])) {
            for ($i=0;$i<count($this->_xmlkeys['OBJECTID']);$i++) {
                
                $pos1 = $this->_xmlkeys['USERID'][$i];
                $pos2 = $this->_xmlkeys['OBJECTID'][$i];
                $pos3 = $this->_xmlkeys['OVERLAP'][$i];
                $pos4 = $this->_xmlkeys['SIMILARITYSCORE'][$i];
                $pos5 = $this->_xmlkeys['SCORE'][$i];
                $pos6 = $this->_xmlkeys['FIRSTNAME'][$i];
                $pos7 = $this->_xmlkeys['LASTNAME'][$i];
                $pos8 = $this->_xmlkeys['TITLE'][$i];
                $pos9 = $this->_xmlkeys['ANON'][$i];
                $pos10 = $this->_xmlkeys['GRADEMARKSTATUS'][$i];
                $pos11 = $this->_xmlkeys['DATE_SUBMITTED'][$i];
                
                $objectid = $this->_xmlvalues[$pos2]['value'];
                
                $output[$objectid]["userid"] = $this->_xmlvalues[$pos1]['value'];
                $output[$objectid]["firstname"]=(isset($this->_xmlvalues[$pos6]['value'])) ? $this->_xmlvalues[$pos6]['value'] : '';
                $output[$objectid]["lastname"]=(isset($this->_xmlvalues[$pos7]['value'])) ? $this->_xmlvalues[$pos7]['value'] : '';
                $output[$objectid]["title"] = $this->_xmlvalues[$pos8]['value'];
                $output[$objectid]["similarityscore"]=(isset($this->_xmlvalues[$pos4]['value']) AND $this->_xmlvalues[$pos4]['value']!="-1") ? $this->_xmlvalues[$pos4]['value'] : NULL;
                
                $output[$objectid]["overlap"]=(isset($this->_xmlvalues[$pos3]['value']) // this is the Originality Percentage Score
                                               AND $this->_xmlvalues[$pos3]['value']!="-1"
                                               AND !is_null($output[$objectid]["similarityscore"])) ? $this->_xmlvalues[$pos3]['value'] : NULL;

                $output[$objectid]["grademark"]=(isset($this->_xmlvalues[$pos5]['value']) AND $this->_xmlvalues[$pos5]['value']!="-1") ? $this->_xmlvalues[$pos5]['value'] : NULL;
                $output[$objectid]["anon"]=(isset($this->_xmlvalues[$pos9]['value']) AND $this->_xmlvalues[$pos9]['value']!="-1") ? $this->_xmlvalues[$pos9]['value'] : NULL;
                $output[$objectid]["grademarkstatus"]=(isset($this->_xmlvalues[$pos10]['value']) AND $this->_xmlvalues[$pos10]['value']!="-1") ? $this->_xmlvalues[$pos10]['value'] : NULL;
                $output[$objectid]["date_submitted"]=(isset($this->_xmlvalues[$pos11]['value']) AND $this->_xmlvalues[$pos11]['value']!="-1") ? $this->_xmlvalues[$pos11]['value'] : NULL;
            }
            return $output;
        } else {
            return $output;
        }
    }
    /**
     * Returns the Session ID for the API call
     *
     * @return string The Session ID String or Empty if not available
     */
    function getSessionid() {
        if ($this->xmlToArray($this->result) AND isset($this->_xmlkeys['SESSIONID'][0])) {
            $pos = $this->_xmlkeys['SESSIONID'][0];
            return $this->_xmlvalues[$pos]['value'];
        } else {
            return '';
        }
    }
    /**
     * Returns the Return Message (rmessage) for the API call
     *
     * @return string The RMESSAGE or Empty if not available
     */
    function getRmessage() {
        if ($this->xmlToArray($this->result)) {
            $pos = $this->_xmlkeys['RMESSAGE'][0];
            return $this->_xmlvalues[$pos]['value'];
        } else {
            return '';
        }
    }
    /**
     * Returns the User ID for the API call
     *
     * @return string The USERID or Empty if not available
     */
    function getUserID() {
        if ($this->xmlToArray($this->result)) {
            $pos = $this->_xmlkeys['USERID'][0];
            return $this->_xmlvalues[$pos]['value'];
        } else {
            return '';
        }
    }
    /**
     * Returns the Class ID for the API call
     *
     * @return string The CLASSID or Empty if not available
     */
    function getClassID() {
        if ($this->xmlToArray($this->result)) {
            $pos = $this->_xmlkeys['CLASSID'][0];
            return $this->_xmlvalues[$pos]['value'];
        } else {
            return '';
        }
    }
    /**
     * Returns the Return Code (rcode) for the API call
     *
     * @return string The RCODE or NULL if not available
     */
    function getRcode() {
        if ($this->xmlToArray($this->result)) {
            $pos = $this->_xmlkeys['RCODE'][0];
            return $this->_xmlvalues[$pos]['value'];
        } else {
            return NULL;
        }
    }
    /**
     * Returns the Error State for the API call
     *
     * @return boolean True API call success or False API failure
     */
    function getRerror() {
        if (is_null($this->getRcode()) OR $this->getRcode()>=API_ERROR_START) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Checks the availability of the API
     *
     * @return boolean Returns a true if the API has returned an RCODE or false if unavailable
     */
    function getAPIunavailable() {
        if (is_null($this->getRcode())) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Returns the Object ID (objectid) for the API call
     *
     * @return string The OBJECTID or Empty String if not available
     */
    function getObjectid() {
        if ($this->xmlToArray($this->result)) {
            $pos = $this->_xmlkeys['OBJECTID'][0];
            return $this->_xmlvalues[$pos]['value'];
        } else {
            return '';
        }
    }
    /**
     * Returns the Assignment ID (ASSIGNMENTID) for the API call
     *
     * @return string The ASSIGNMENTID or Empty String if not available
     */
    function getAssignid() {
        if ($this->xmlToArray($this->result)) {
            $pos = $this->_xmlkeys['ASSIGNMENTID'][0];
            return $this->_xmlvalues[$pos]['value'];
        } else {
            return '';
        }
    }
    /**
     * Returns the Originality Score (ORIGINALITYSCORE) for the API call
     *
     * @return string The ORIGINALITYSCORE or Empty String if not available
     */
    function getScore() {
        if ($this->xmlToArray($this->result) AND isset($this->_xmlkeys['ORIGINALITYSCORE'][0])) {
            $pos = $this->_xmlkeys['ORIGINALITYSCORE'][0];
            return $this->_xmlvalues[$pos]['value'];
        } else {
            return '';
        }
    }
    /**
     * Does a HTTPS Request using cURL and returns the result
     *
     * @param string $method The request method to use POST or GET
     * @param string $url The URL to send the request to
     * @param string $vars A query string style name value pair string (e.g. name=value&name2=value2)
     * @param boolean $timeout Whether to timeout after 240 seconds or not timeout at all
     * @param string $status The status to pass to the loaderbar redraw method
     * @return string The result of the HTTPS call
     */
    function doRequest($method, $url, $vars, $timeout=true, $status="") {
        if (is_callable(array($this->loaderbar,'redrawbar'))) {
            $this->loaderbar->redrawbar($status);
        }
        if ($timeout) {
            set_time_limit(240);
        } else {
            set_time_limit(0);
        }
        $ch = curl_init();
        $useragent="Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ($timeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, 240);
        }
        if ($method == 'POST') {
            //curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        }
        $data = curl_exec($ch);
        if ($data) {
            $result=$data;
        } else {
            $result=curl_error($ch);
        }
        sleep(TII_LATENCY_SLEEP);
        $this->doLogging($vars,$result);
        return $result;
        curl_close($ch);
        
    }
    /**
     * Logging function to log outgoing API calls and the return XML
     *
     * @param string $vars The query variables passed to the API
     * @param string $result The Result of the query
     */
    function doLogging($vars,$result) {
        global $CFG;
        if ($CFG->turnitin_enablediagnostic) {
            // ###### DELETE SURPLUS LOGS #########
            $numkeeps=10;
            $prefix="commslog_";
            $dirpath=$CFG->dataroot."/temp/turnitintool/logs";
            if (!file_exists($dirpath)) {
                mkdir($dirpath,0777,true);
            }
            $dir=opendir($dirpath);
            $files=array();
            while ($entry=readdir($dir)) {
                if (substr(basename($entry),0,1)!="." AND substr_count(basename($entry),$prefix)>0) {
                    $files[]=basename($entry);
                }
            }
            sort($files);
            for ($i=0;$i<count($files)-$numkeeps;$i++) {
                unlink($dirpath."/".$files[$i]);
            }
            // ####################################
            $filepath=$dirpath."/".$prefix.date('Ymd',time()).".log";
            $file=fopen($filepath,'a');
            if (!isset($vars["fid"])) {
                $vars["fid"]="N/A";
            }
            if (!isset($vars["fcmd"])) {
                $vars["fcmd"]="N/A";
            }
            $output="== FID:".$vars["fid"]." | FCMD:".$vars["fcmd"]." ===========================================================\n";
            if ($this->getRerror()) {
                $output.="== SUCCESS ===================================================================\n";
            } else {
                $output.="== ERROR =====================================================================\n";
            }
            $output.="CALL DATE TIME: ".date('r',time())."\n";
            $output.="URL: ".$this->apiurl."\n";
            $output.="------------------------------------------------------------------------------\n";
            $output.="REQUEST VARS: \n".print_r($vars,true)."\n";
            $output.="------------------------------------------------------------------------------\n";
            $output.="RESPONSE: \n".$result."\n";
            $output.="##############################################################################\n\n";
            fwrite($file,$output);
            fclose($file);
        }
    }
    /**
     * Call to API FID4, FCMD6 that deletes an assignment from Turnitin
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function deleteAssignment($post,$status) {
        global $CFG;
        
        if (!turnitintool_check_config()) {
            turnitintool_print_error('configureerror','turnitintool','',NULL,__FILE__,__LINE__);
            exit();
        }
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>6,
                         'cid'=>$post->cid,
                         'ctl'=>stripslashes($post->ctl),
                         'assignid'=>$post->assignid,
                         'assign'=>stripslashes($post->name),
                         'utp'=>2,
                         'fid'=>4,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata["md5"]=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID4, FCMD2/FCMD3 that creates/updates an assignment in Turnitin
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $do The call type 'INSERT' or 'UPDATE'
     * @param string $status The status to pass to the loaderbar class
     */
    function createAssignment($post,$do='INSERT',$status) { 
        
        if (!turnitintool_check_config()) {
            turnitintool_print_error('configureerror','turnitintool','',NULL,__FILE__,__LINE__);
            exit();
        }
        
        if ($do!='INSERT') {
            $thisfcmd=3;
            $userid=$this->uid;
        } else {
            $thisfcmd=2;
            $userid='';
        }
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>$thisfcmd,
                         'cid'=>$post->cid,
                         'ctl'=>stripslashes($post->ctl),
                         'assignid'=>$post->assignid,
                         'utp'=>2,
                         'ced'=>date('Ymd',strtotime('+24 months')), // extend the class end date to be two years
                         'dtstart'=>date('Y-m-d H:i:s',$post->dtstart),
                         'dtdue'=>date('Y-m-d H:i:s',$post->dtdue),
                         'dtpost'=>date('Y-m-d H:i:s',$post->dtpost),
                         'fid'=>4,
                         'uid'=>$userid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        if ($do!='INSERT') {
            $assigndata['newassign']=stripslashes($post->name);
            $assigndata['assign']=stripslashes($post->currentassign);
        } else {
            $assigndata['assign']=stripslashes($post->name);
        }
        $assigndata["md5"]=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata["s_view_report"]=$post->s_view_report;
        if (isset($post->max_points)) {
            $assigndata["max_points"]=$post->max_points;
        }
        if (isset($post->report_gen_speed)) {
            $assigndata["report_gen_speed"]=$post->report_gen_speed;
        }
        if (isset($post->anon)) {
            $assigndata["anon"]=$post->anon;
        }
        if (isset($post->late_accept_flag)) {
            $assigndata["late_accept_flag"]=$post->late_accept_flag;
        }
        if (isset($post->submit_papers_to)) {
            $assigndata["submit_papers_to"]=$post->submit_papers_to;
        }
        if (isset($post->s_paper_check)) {
            $assigndata["s_paper_check"]=$post->s_paper_check;
        }
        if (isset($post->internet_check)) {
            $assigndata["internet_check"]=$post->internet_check;
        }
        if (isset($post->journal_check)) {
            $assigndata["journal_check"]=$post->journal_check;
        }
        if (isset($post->idsync)) {
            $assigndata['idsync']=$post->idsync;
        }
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID2, FCMD4 that changes the owner tutor for a Turnitin class
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function changeOwner($post,$status) {
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>4,
                         'cid'=>$post->cid,
                         'ctl'=>stripslashes($post->ctl),
                         'tem'=>$this->uem,
                         'utp'=>2,
                         'fid'=>2,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['src']=TURNITIN_APISRC;
        $assigndata['new_teacher_email']=$post->new_teacher_email;

        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID5, FCMD2 that submits a paper to Turnitin
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $filepath The filepath of the file to upload
     * @param string $status The status to pass to the loaderbar class
     */
    function submitPaper($post,$filepath,$status) {
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>2,
                         'cid'=>$post->cid,
                         'ctl'=>stripslashes($post->ctl),
                         'assignid'=>$post->assignid,
                         'assign'=>stripslashes($post->assignname),
                         'tem'=>$post->tem,
                         'ptype'=>2,
                         'ptl'=>stripslashes($post->papertitle),
                         'utp'=>1,
                         'fid'=>5,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln,
						 'oid'=>$post->oid
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['src']=TURNITIN_APISRC;
        $assigndata['pdata']='@'.$filepath;

        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,false,$status);
    }
    /**
     * Call to API FID4, FCMD7 that queries the settings for the Turnitin assignment
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function queryAssignment($post,$status) {
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>7,
                         'cid'=>$post->cid,
                         'ctl'=>stripslashes($post->ctl),
                         'assignid'=>$post->assignid,
                         'assign'=>stripslashes($post->assign),
                         'utp'=>2,
                         'fid'=>4,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Converts the Turnitin Assignment settings to an object in the correct format for a create/update assignment call 
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function getAssignmentObject() {
        $output=new object();
        $xmlcall=$this->xmlToArray($this->result);
        if (isset($this->_xmlkeys['ASSIGN']) AND is_array($this->_xmlkeys['ASSIGN'])) {
            for ($i=0;$i<count($this->_xmlkeys['ASSIGN']);$i++) {
                
                $pos1 = $this->_xmlkeys['ASSIGN'][$i];
                $pos2 = $this->_xmlkeys['DTSTART'][$i];
                $pos3 = $this->_xmlkeys['DTDUE'][$i];
                $pos4 = $this->_xmlkeys['DTPOST'][$i];
                $pos5 = $this->_xmlkeys['AINST'][$i];
                $pos6 = $this->_xmlkeys['GENERATE'][$i];
                $pos7 = $this->_xmlkeys['SVIEWREPORTS'][$i];
                $pos8 = $this->_xmlkeys['LATESUBMISSIONS'][$i];
                $pos9 = $this->_xmlkeys['REPOSITORY'][$i];
                $pos10 = $this->_xmlkeys['SEARCHPAPERS'][$i];
                $pos11 = $this->_xmlkeys['SEARCHINTERNET'][$i];
                $pos12 = $this->_xmlkeys['SEARCHJOURNALS'][$i];
                $pos13 = $this->_xmlkeys['ANON'][$i];
                $pos14 = $this->_xmlkeys['MAXPOINTS'][$i];
                
                $output->assign = $this->_xmlvalues[$pos1]['value'];
                $output->dtstart = strtotime($this->_xmlvalues[$pos2]['value']);
                $output->dtdue = strtotime($this->_xmlvalues[$pos3]['value']);
                $output->dtpost = strtotime($this->_xmlvalues[$pos4]['value']);
                if (isset($this->_xmlvalues[$pos5]['value'])) {
                    $output->ainst = $this->_xmlvalues[$pos5]['value'];
                } else {
                    $output->ainst = NULL;
                }
                $output->report_gen_speed = $this->_xmlvalues[$pos6]['value'];
                $output->s_view_report = $this->_xmlvalues[$pos7]['value'];
                $output->late_accept_flag = $this->_xmlvalues[$pos8]['value'];
                $output->submit_papers_to = $this->_xmlvalues[$pos9]['value'];
                $output->s_paper_check = $this->_xmlvalues[$pos10]['value'];
                $output->internet_check = $this->_xmlvalues[$pos11]['value'];
                $output->journal_check = $this->_xmlvalues[$pos12]['value'];
                $output->anon = $this->_xmlvalues[$pos13]['value'];
                $output->maxpoints = $this->_xmlvalues[$pos14]['value'];
                
            }
            return $output;
        } else {
            return $output;
        }
    }
    /**
     * Call to API FID3, FCMD2 to join the user to a Turnitin Class
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function joinClass($post,$status) {
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>2,
                         'cid'=>$post->cid,
                         'ctl'=>stripslashes($post->ctl),
                         'tem'=>stripslashes($post->tem),
                         'utp'=>1,
                         'fid'=>3,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID1, FCMD2 to create a user on the Turnitin System
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function createUser($post,$status) {
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>2,
                         'utp'=>$this->utp,
                         'fid'=>1,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        if (isset($post->dis) AND $post->dis==1) {
            $assigndata['dis']=1;
        } else {
            $assigndata['dis']=0;
        }
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        if (isset($post->idsync)) {
            $assigndata['idsync']=$post->idsync;
        }
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID1, FCMD2 to create a class on the Turnitin System
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function createClass($post,$status) {
        
        if (!isset($post->cid)) {
            $post->cid="";
            $userid="";
        } else {
            $userid=$this->uid;
        }
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>2,
                         'utp'=>2,
                         'fid'=>2,
                         'cid'=>$post->cid,
                         'ctl'=>stripslashes($post->ctl),
                         'uid'=>$userid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        if (isset($post->idsync)) {
            $assigndata['idsync']=$post->idsync;
        }
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID6, FCMD2 to get the Originality Report score
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function getReportScore($post,$status) {
        
        if (!turnitintool_check_config()) {
            turnitintool_print_error('configureerror','turnitintool','',NULL,__FILE__,__LINE__);
            exit();
        }
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>2,
                         'oid'=>$post->paperid,
                         'utp'=>$post->utp,
                         'fid'=>6,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata["md5"]=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID10, FCMD2 to list the Submissions for an Assignment
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function listSubmissions($post,$status) {
        
        if (!turnitintool_check_config()) {
            turnitintool_print_error('configureerror','turnitintool','',NULL,__FILE__,__LINE__);
            exit();
        }
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>2,
                         'assignid'=>$post->assignid,
                         'assign'=>$post->assign,
                         'cid'=>$post->cid,
                         'ctl'=>$post->ctl,
                         'tem'=>$post->tem,
                         'utp'=>$this->utp,
                         'fid'=>10,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata["md5"]=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID6, FCMD1 to Single Sign On to Turnitin's Originality Report for the submission
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @return string Returns the URL to access the Originality Report
     */
    function getReportLink($post) {
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>1,
                         'oid'=>$post->paperid,
                         'utp'=>$this->utp,
                         'fid'=>6,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['src']=TURNITIN_APISRC;
		
		$keys = array_keys($assigndata);
          $values = array_values($assigndata);
        $querystring='';
          for ($i=0;$i<count($values); $i++) {
            if ($i!=0) {
                $querystring .= '&';
              }
            $querystring .= $keys[$i].'='.urlencode($values[$i]);
        }
        
        return $this->apiurl."?".$querystring;
    }
    /**
     * Call to API FID13, FCMD1 to Single Sign On to Turnitin's GradeMark for the submission
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @return string Returns the URL to access the GradeMark
     */
    function getGradeMarkLink($post) {
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>1,
                         'oid'=>$post->paperid,
                         'utp'=>$this->utp,
                         'fid'=>13,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['src']=TURNITIN_APISRC;
		
        $keys = array_keys($assigndata);
          $values = array_values($assigndata);
        $querystring='';
          for ($i=0;$i<count($values); $i++) {
            if ($i!=0) {
                $querystring .= '&';
              }
            $querystring .= $keys[$i].'='.urlencode($values[$i]);
        }
        
        return $this->apiurl."?".$querystring;
    }
    /**
     * Call to API FID7, FCMD1 to Single Sign On to Turnitin's Submission View for the submission
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @return string Returns the URL to access the Submission View
     */
    function getSubmissionURL($post) {
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>1,
                         'oid'=>$post->paperid,
                         'utp'=>$this->utp,
                         'fid'=>7,
                         'assignid'=>$post->assignid,
                         'assign'=>$post->assign,
                         'ctl'=>$post->ctl,
                         'cid'=>$post->cid,
                         'uid'=>$this->uid,
                         'tem'=>$post->tem,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['src']=TURNITIN_APISRC;
		
        $keys = array_keys($assigndata);
          $values = array_values($assigndata);
        $querystring='';
          for ($i=0;$i<count($values); $i++) {
            if ($i!=0) {
                $querystring .= '&';
              }
            $querystring .= $keys[$i].'='.urlencode($values[$i]);
        }
        
        return $this->apiurl."?".$querystring;
    }
    /**
     * Call to API FID7, FCMD2 to download the original paper of the submission
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @return string Returns the URL to access the Download Link
     */
    function getSubmissionDownload($post) {
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>2,
                         'oid'=>$post->paperid,
                         'utp'=>$this->utp,
                         'fid'=>7,
                         'assignid'=>$post->assignid,
                         'assign'=>$post->assign,
                         'ctl'=>$post->ctl,
                         'cid'=>$post->cid,
                         'uid'=>$this->uid,
                         'tem'=>$post->tem,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['src']=TURNITIN_APISRC;
		
        $keys = array_keys($assigndata);
          $values = array_values($assigndata);
        $querystring='';
          for ($i=0;$i<count($values); $i++) {
            if ($i!=0) {
                $querystring .= '&';
              }
            $querystring .= $keys[$i].'='.urlencode($values[$i]);
        }
        
        return $this->apiurl."?".$querystring;
    }
    /**
     * Call to API FID15, FCMD3 to set the GradeMark grade for a particular submission 
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function setGradeMark($post,$status) {
        
        if (!turnitintool_check_config()) {
            turnitintool_print_error('configureerror','turnitintool','',NULL,__FILE__,__LINE__);
            exit();
        }
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>3,
                         'cid'=>$post->cid,
                         'oid'=>$post->oid,
                         'utp'=>2,
                         'fid'=>15,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata["md5"]=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata["score"]=$post->score;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID8, FCMD2 to delete a submission 
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function deleteSubmission($post,$status) {
        
        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>2,
                         'oid'=>$post->paperid,
                         'utp'=>$this->utp,
                         'fid'=>8,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID16, FCMD3 to reveal the name of a student when anonymous marking is switched on
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @param string $status The status to pass to the loaderbar class
     */
    function revealAnon($post,$status) {

        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>3,
                         'oid'=>$post->paperid,
                         'utp'=>$this->utp,
                         'fid'=>16,
                         'uid'=>$this->uid,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['session-id']=$this->tiisession;
        $assigndata['anon_reason']=$post->anon_reason;
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,$status);
    }
    /**
     * Call to API FID99, FCMD1 to migrate Turnitin Open API entries to Moodle Native SRC 12
     */
    function migrateSRCData() {

        $assigndata=array('gmtime'=>$this->tiiGmtime(),
                         'encrypt'=>TII_ENCRYPT,
                         'aid'=>$this->accountid,
                         'diagnostic'=>0,
                         'fcmd'=>2,
                         'utp'=>$this->utp,
                         'fid'=>99,
                         'uem'=>$this->uem,
                         'ufn'=>$this->ufn,
                         'uln'=>$this->uln
                        );
        $assigndata['md5']=$this->doMD5($assigndata);
        $assigndata['src']=TURNITIN_APISRC;
        $this->result=$this->doRequest("POST", $this->apiurl, $assigndata,true,"");
    }
    /**
     * Creates a Turnitin MD5 parameter from the $post object
     *
     * @param object $post The post object that contains the necessary query parameters for the call
     * @return string The MD5 hash of the posted query values
     */
    function doMD5($post) {
        global $CFG;
        $output="";
        ksort($post);
        $postKeys=array_keys($post);
        for ($i=0;$i<count($post);$i++) {
            $thisKey=$postKeys[$i];
            $output.=$post[$thisKey];
        }
        $output.=$CFG->turnitin_secretkey;
        return md5($output);
    }
    /**
     * Creates a Turnitin GMTIME parameter to pass to the API
     *
     * @return string The GMTIME parameter with the last digit stripped off
     */
    function tiiGmtime() {
        $output="";
        $output.=gmdate('YmdH',time());
        $output.=substr(gmdate('i',time()),0,1);
        return $output;
    }

}

/* ?> */