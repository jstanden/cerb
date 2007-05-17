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
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
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