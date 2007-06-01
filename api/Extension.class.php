<?php
abstract class CerberusPageExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function isVisible() { return true; }
	function render() { }
	
	/**
	 * @return Model_Activity
	 */
	public function getActivity() {
        return new Model_Activity();
	}
};

abstract class CerberusDisplayPageExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	/**
	 * Enter description here...
	 */
	function render($ticket) {}
	
}

abstract class CerberusLoginPageExtension extends DevblocksExtension { //implements DevblocksHttpRequestHandler {
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
	function authenticate($params=array()) {
		return false;
	}
	
	/**
	 * release any resources tied up by the authenticate process, if necessary
	 */
	function signoff() {
	}
	
}

// [TODO] Convert to a controller extension
abstract class CerberusCronPageExtension extends DevblocksExtension {
    const PARAM_ENABLED = 'enabled';
    const PARAM_DURATION = 'duration';
    const PARAM_TERM = 'term';
    
	function __construct($manifest) {
		$this->DevblocksExtension($manifest, 1);
	}

	/**
	 * runs scheduled task
	 *
	 */
	function run() {
	    $this->_ran();
	}
	
	// [TODO] Hack
	function _ran() {
	    $this->setParam('lastrun',time());
	    $this->setParam('locked',false);
	    $this->saveParams();
	}
	
	public function configure($instance) {
	}
	
	public function saveConfigurationAction() {
	}
	
}

?>
