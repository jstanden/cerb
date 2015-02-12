<?php
class DevblocksEngineTest extends PHPUnit_Framework_TestCase {
    final function __construct($name = null, array $data = array(), $dataName = '') {
        parent::__construct($name, $data, $dataName);
    }
    
    function testGetWebPath() {
    	// HTTP_X_REWRITE_URL should have top precedence
    	
    	$_SERVER = array(
    		'HTTP_X_REWRITE_URL' => 'http://rewrite.example.com/',
    		'REQUEST_URI' => 'http://request_uri.example.com/',
    		'REDIRECT_URL' => 'http://redirect_url.example.com/',
    		'ORIG_PATH_INFO' => 'http://orig_path_info.example.com/',
    	);

    	$actual = DevblocksEngine::getWebPath();
    	
    	$this->assertEquals($_SERVER['HTTP_X_REWRITE_URL'], $actual);
    	
    	// REQUEST_URI should have secondary precedence
    	
    	$_SERVER = array(
    		'REQUEST_URI' => 'http://request_uri.example.com/',
    		'REDIRECT_URL' => 'http://redirect_url.example.com/',
    		'ORIG_PATH_INFO' => 'http://orig_path_info.example.com/',
    	);

    	$actual = DevblocksEngine::getWebPath();
    	
    	$this->assertEquals($_SERVER['REQUEST_URI'], $actual);
    	
    	// REDIRECT_URL should have tertiary precedence
    	
    	$_SERVER = array(
    		'REDIRECT_URL' => 'http://redirect_url.example.com/',
    		'ORIG_PATH_INFO' => 'http://orig_path_info.example.com/',
    	);

    	$actual = DevblocksEngine::getWebPath();
    	
    	$this->assertEquals($_SERVER['REDIRECT_URL'], $actual);
    	
    	// ORIG_PATH_INFO should have quaternary precedence
    	
    	$_SERVER = array(
    		'ORIG_PATH_INFO' => 'http://orig_path_info.example.com/',
    	);

    	$actual = DevblocksEngine::getWebPath();
    	
    	$this->assertEquals($_SERVER['ORIG_PATH_INFO'], $actual);
    }
    
}
