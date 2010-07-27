<?php
            // fiddle up include path so to include Zend library
            $include_path = ini_get( 'include_path' );
            $include_path .= ":{$CFG->dirroot}/blocks/remotehtml/zend";
            ini_set( 'include_path', $include_path );

            // zend xmlrpc client
            require_once( 'Zend/XmlRpc/Client.php' );
            $client = new Zend_XmlRpc_Client($this->config->remoteurl);
            
            // connect
            try {
                $data = $client->call('gethtml');
            } catch (Exception $e) {
                $this->content = new stdClass;
                $this->content->text = "XmlRpc exception ".$e->getMessage();
                $this->content->footer = '';
                return $this->content;
            }
            $this->content = new stdClass;
            $this->content->text = $data;
            $this->content->footer ='';
            return $this->content;
        }
