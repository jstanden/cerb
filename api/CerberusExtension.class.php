<?php

abstract class CerberusModuleExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function isVisible() { return true; }
	function render() { }
	
	function getLink() {
		return DEVBLOCKS_WEBPATH . "index.php?c=".$this->manifest->id."&a=click";
	}
	function click() { 
//		echo "You clicked: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		CerberusApplication::setActiveModule($this->manifest->id);
	}
};

abstract class CerberusDisplayModuleExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	/**
	 * Enter description here...
	 */
	function render($ticket) {}
}

abstract class CerberusLoginModuleExtension extends DevblocksExtension {
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
//		$field_value = $_POST['field_value'];
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
}

abstract class CerberusCronModuleExtension extends DevblocksExtension {
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