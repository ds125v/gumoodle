<?php

require_once( '../../config.php' );

$instance = required_param('instance',PARAM_INT);

// fiddle up include path so to include Zend library
$include_path = ini_get( 'include_path' );
$include_path .= ":{$CFG->dirroot}/blocks/remotehtml/zend";
ini_set( 'include_path', $include_path );

// zend xmlrpc server
require_once( 'Zend/XmlRpc/Server.php' );

// create server
$server = new Zend_XmlRpc_Server();
$server->addFunction('gethtml');
echo $server->handle();

/**
 * Return the html for this instance
 *
 * @return string html message
 */
function gethtml() {
    global $instance;
    global $SITE;

    // get block instance
    if (!$inst = get_record( 'block_instance','id',$instance )) {
        return get_string('instanceidwrong','block_remotehtml');
    }

    // MUST be front page (security)
    if ($inst->pageid != $SITE->id) {
        return get_string('notfrontpage','block_remotehtml');
    }

    $block = block_instance( 'remotehtml', $inst );
    return $block->config->text;
}

