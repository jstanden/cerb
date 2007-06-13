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
    const PARAM_LOCKED = 'locked';
    const PARAM_DURATION = 'duration';
    const PARAM_TERM = 'term';
    const PARAM_LASTRUN = 'lastrun';
    
	function __construct($manifest) {
		$this->DevblocksExtension($manifest, 1);
	}

	/**
	 * runs scheduled task
	 *
	 */
	function run() {
	    // Overloaded by child
	}
	
	function _run() {
	    $this->run();
	    
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
	    $lastrun = $this->getParam(self::PARAM_LASTRUN, time());

	    $secs = self::getIntervalAsSeconds($duration, $term);
	    $ran_at = time();
	    
	    if(!empty($secs)) {
		    $gap = time() - $lastrun; // how long since we last ran
		    $extra = $gap % $secs; // we waited too long to run by this many secs
		    $ran_at = time() - $extra; // go back in time and lie
	    }
	    
	    $this->setParam(self::PARAM_LASTRUN,$ran_at);
	    $this->setParam(self::PARAM_LOCKED,false);
	    $this->saveParams();
	}
	
	/**
	 * @return boolean
	 */
	public function isReadyToRun() {
		$locked = $this->getParam(self::PARAM_LOCKED, 0);
		$enabled = $this->getParam(self::PARAM_ENABLED, false);
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
		$lastrun = $this->getParam(self::PARAM_LASTRUN, 0);
		
		// If we've been locked too long then unlock
	    if($locked && $locked < (time() - 10 * 60)) {
	        $locked = 0;
	    }

	    // Make sure enough time has elapsed.
	    $checkpoint = $lastrun + self::getIntervalAsSeconds($duration, $term);

	    // Ready?
	    return (!$locked && $enabled && time() >= $checkpoint) ? true : false;
	}
	
	static public function getIntervalAsSeconds($duration, $term) {
	    $seconds = 0;
	    
	    if($term=='d') {
	        $seconds = $duration * 24 * 60 * 60; // x hours * mins * secs
	    } elseif($term=='h') {
	        $seconds = $duration * 60 * 60; // x * mins * secs
	    } else {
	        $seconds = $duration * 60; // x * secs
	    }
	    
	    return $seconds;
	}
	
	public function configure($instance) {}
	
	public function saveConfigurationAction() {}
	
}

?>
