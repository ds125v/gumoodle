<?php


/**
 * Handle course completion event
 *
 * @param object $event data record from completion system
 */
function corews_course_completed($event) {
    global $DB;

    // get the basic event info
    $userid = $event->userid;
    $courseid = $event->course;

    // track down the corews block config
    // for that course
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    if (!$block=$DB->get_record('block_instances', array('blockname'=>'corews', 'parentcontextid'=>$context->id))) {
        mtrace('No corews block found');
    }

echo "<pre>"; print_r($event); die;
    return true;
}

/**
 * Make the WS call
 * @param string $employeeid employee number
 * @param string $coursecode course code required by WS
 * @param string $courseid course id required by WS
 * @param boolean $test provide additional debugging info
 */
function corews_soap($employeeid, $coursecode, $courseid, $test=false) {
    global $CFG;

    // make client connection
    if ($test) {
        echo "<p>Making connection to WSDL '{$CFG->block_corews_wsdl}'</p>";
    }
    try {
        $client = new SoapClient($CFG->block_corews_wsdl, array('trace'=>1));
    } catch (SoapFault $e) {
        mtrace( 'Soap constructor failed - '.$e->faultcode );
        return false;
    }

    // extra info for test
    if ($test) {
        echo "<p><b>Connection successful</b> Connection data:</p>";
        echo "<pre>"; 
        print_r( $client->__getFunctions() );
        echo "</pre>";
    }

    // login details
    $loginDetails = new stdClass();
    $loginDetails->password = $CFG->block_corews_password;
    $loginDetails->userName = $CFG->block_corews_username;

    // staffTrainingRecord
    $staffTrainingRecord = new stdClass();
    $staffTrainingRecord->courseCode = $coursecode;
    $staffTrainingRecord->personnelNo = $employeeid;

    // staffTrainingRecordValues
    $staffTrainingRecordValues = new stdClass;
    $staffTrainingRecordValues->courseId = $courseid;
    $staffTrainingRecordValues->endDate = date('dmY');
    $staffTrainingRecordValues->startDate = date('dmY');
    $staffTrainingRecordValues->trainingStatus = 'CO';

    // add
    $add = new stdClass();
    $add->loginDetails = $loginDetails;
    $add->staffTrainingRecord = $staffTrainingRecord;
    $add->staffTrainingRecordValues = $staffTrainingRecordValues;

    // prepare parameters for add call
    $params = (array)$add;

    // make add call
    try {
        $result = $client->add($params);
    } catch (SoapFault $e) {
        mtrace( 'Soap add call failed - message '.$e->getMessage() . 
            ', code '.$e->getCode().
            ', file '.$e->getFile()
        );
        echo "<pre>".htmlspecialchars($client->__getLastRequest())."</pre>";
        return false;
    }
    if ($test) {
        echo "<p>Add call made, results are...</p>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }

}
