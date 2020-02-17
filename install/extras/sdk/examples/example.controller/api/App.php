<?php
class ExampleController extends DevblocksControllerExtension {
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		array_shift($stack); // example
		
		@$action = array_shift($stack) . 'Action';

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;
				
			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
				break;
		}
		
		exit;
	}

	function writeResponse(DevblocksHttpResponse $response) {
		return;
	}
	
	function testAction() {
		echo DevblocksPlatform::strEscapeHtml("Test Response!");
	}
};