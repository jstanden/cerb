<?php

abstract class CerberusPageExtension extends DevblocksExtension implements DevblocksHttpRequestHandler {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function isVisible() { return true; }
	function render() { }
	
	function getLink() {
		$uris = DevblocksPlatform::getMappingRegistry();
		$url = DevblocksPlatform::getUrlService();
		// [JAS]: [TODO] Move this to the platform
		$uri = array_search($this->id,$uris);
		return $url->write("c=".$uri);
	}
	
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest($request) {
//		print_r($request);

		$path = $request->path;
		$command = array_shift($path);
		
		if(method_exists($this,@$path[0])) {
			call_user_method($path[0],$this); // [TODO] Pass HttpRequest as arg?
		}
	}
	
	public function writeResponse($response) {
		CerberusApplication::writeDefaultHttpResponse($response);
	}
	
	/**
	 * @return Model_Activity
	 */
	public function getActivity() {
        return new Model_Activity();
	}
};

/*
 * [JAS]: [TODO] DevblocksHttpRequestHandler is getting popular, does it 
 * need to be implemented on DevblocksExtension by default?
 */
abstract class CerberusDisplayPageExtension extends DevblocksExtension implements DevblocksHttpRequestHandler {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	/**
	 * Enter description here...
	 */
	function render($ticket) {}
	
	public function handleRequest($request) {
//		print_r($request);

		$path = $request->path;
		$command = array_shift($path);
		
		if(method_exists($this,$path[0])) {
			call_user_method($path[0],$this); // [TODO] Pass HttpRequest as arg?
		}
	}
	
	public function writeResponse($response) {
		CerberusApplication::writeDefaultHttpResponse($response);
	}	
	
}

abstract class CerberusLoginPageExtension extends DevblocksExtension implements DevblocksHttpRequestHandler {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest, 1);
	}
	
	/**
	 * draws html form for adding necessary settings (host, port, etc) to be stored in the db
	 */
	function renderConfigForm() {
	}
	
	/**
	 * Receives posted config form, saves to manifest
	 */
	function saveConfiguration() {
//		$field_value = DevblocksPlatform::importGPC($_POST['field_value']);
//		$this->params['field_name'] = $field_value;
//		$this->saveParams()
	}
	
	/**
	 * draws HTML form of controls needed for login information
	 */
	function renderLoginForm() {
	}
	
	/**
	 * pull auth info out of $_POST, check it, return user_id or false
	 * 
	 * @return boolean whether login succeeded
	 */
	function authenticate() {
		return false;
	}
	
	/**
	 * release any resources tied up by the authenticate process, if necessary
	 */
	function signoff() {
	}
	
	public function handleRequest($request) {
		$path = $request->path;
		$command = array_shift($path);
		
		if(method_exists($this,$path[0])) {
			call_user_method($path[0],$this); // [TODO] Pass HttpRequest as arg?
		}
	}
	
	public function writeResponse($response) {
		CerberusApplication::writeDefaultHttpResponse($response);
	}
}

abstract class CerberusCronPageExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest, 1);
	}

	/**
	 * runs scheduled task
	 *
	 */
	function run() {
	}
}

?>
