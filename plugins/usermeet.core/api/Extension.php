<?php
abstract class Extension_UsermeetTool extends DevblocksExtension implements DevblocksHttpRequestHandler {
    function __construct($manifest) {
        // [TODO] Refactor to __construct
        parent::DevblocksExtension($manifest);
    }
    
    /*
     * Site Key
     * Site Name
     * Site URL
     */
    
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
	    $path = $request->path;

		@$a = DevblocksPlatform::importGPC($_REQUEST['a'],'string');
	    
		if(empty($a)) {
    	    @$action = array_shift($path) . 'Action';
		} else {
		    @$action = $a . 'Action';
		}

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
//	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action)); // [TODO] Pass HttpRequest as arg?
				}
	            break;
	    }
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
	}
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure($instance) {
	}
	
	public function saveConfigurationAction() {
	}
    
};

abstract class Extension_UsermeetWidget extends DevblocksExtension {
    function __construct($manifest) {
        // [TODO] Refactor to __construct
        parent::DevblocksExtension($manifest);
    }
    
    /*
     */
};

?>