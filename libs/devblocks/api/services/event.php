<?php
class _DevblocksEventManager {
    private static $instance = null;
    
    private function __construct() {}

    /**
     * @return _DevblocksEventManager
     */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksEventManager();
		}
		return self::$instance;
	}
	
	function trigger(Model_DevblocksEvent $event) {
	    /*
	     * [TODO] Look at the hash and spawn our listeners for this particular point
	     */
		$events = DevblocksPlatform::getEventRegistry();

		if(null == ($listeners = @$events[$event->id])) {
		    $listeners = array();
		}

		// [TODO] Make sure we can't get a double listener
	    if(isset($events['*']) && is_array($events['*']))
	    foreach($events['*'] as $evt) {
	        $listeners[] = $evt;
	    }
		
		if(is_array($listeners) && !empty($listeners))
		foreach($listeners as $listener) { /* @var $listener DevblocksExtensionManifest */
			// Extensions can be invoked on these plugins even by workers who cannot see them
            if(null != ($manifest = DevblocksPlatform::getExtension($listener,false,true))) {
            	if(method_exists($manifest, 'createInstance')) {
		    		$inst = $manifest->createInstance(); /* @var $inst DevblocksEventListenerExtension */
		    		if($inst instanceof DevblocksEventListenerExtension)
            			$inst->handleEvent($event);
            	}
            }
		}
		
	}
};