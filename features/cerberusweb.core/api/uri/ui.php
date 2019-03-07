<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class Controller_UI extends DevblocksControllerExtension {
	const ID = 'core.controller.ui';

	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		if(false == (CerberusApplication::getActiveWorker()))
			return;

		$stack = $request->path;
		array_shift($stack); // internal

		@$action = array_shift($stack) . 'Action';

		switch($action) {
			case NULL:
				break;

			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this, $action)) {
					try {
						call_user_func(array(&$this, $action));
					} catch (Exception $e) { }
				}
				break;
		}
	}
	
	function behaviorAction() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // ui
		array_shift($stack); // behavior
		@$behavior_uri = array_shift($stack);
		
		if(!$behavior_uri || false == ($behavior = DAO_TriggerEvent::getByUri($behavior_uri))) {
			http_response_code(503);
			echo "<h1>503: Temporarily unavailable</h1>";
			return;
		}
		
		$this->_runBehavior($behavior);
	}
	
	function ajaxBotBehaviorAction() {
		@$behavior_id = DevblocksPlatform::importGPC($_REQUEST['behavior_id'], 'integer', 0);
		
		if(!$behavior_id || false == ($behavior = DAO_TriggerEvent::get($behavior_id))) {
			http_response_code(503);
			echo "<h1>503: Temporarily unavailable</h1>";
			return;
		}
		
		$this->_runBehavior($behavior);
	}

	private function _runBehavior(Model_TriggerEvent $behavior) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($bot = $behavior->getBot())) {
			http_response_code(503);
			echo "<h1>503: Temporarily unavailable</h1>";
			return;
		}
		
		$event = $behavior->getEvent();
		
		// Validate event
		
		if(!($event instanceof Event_AjaxHttpRequest)) {
			http_response_code(503);
			echo "<h1>503: Temporarily unavailable</h1>";
			return;
		}
		
		if($behavior->is_disabled || $bot->is_disabled) {
			http_response_code(503);
			echo "<h1>503: Temporarily unavailable</h1>";
			return;
		}
		
		$variables = [];
		
		$http_request = [
			'body' => DevblocksPlatform::getHttpBody(),
			'client_ip' => DevblocksPlatform::getClientIp(),
			'headers' => DevblocksPlatform::getHttpHeaders(),
			'params' => DevblocksPlatform::getHttpParams(),
			'path' => '',
			'verb' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
		];
		
		// Can this worker run this bot behavior?
		if(!Context_TriggerEvent::isReadableByActor($behavior, $active_worker)) {
			http_response_code(403);
			echo "<h1>403: Access denied</h1>";
			return;
		}
		
		$dicts = Event_AjaxHttpRequest::trigger($behavior->id, $http_request, $active_worker, $variables);
		$dict = $dicts[$behavior->id];
		
		if(!($dict instanceof DevblocksDictionaryDelegate)) {
			http_response_code(503);
			echo "<h1>503: Temporarily unavailable</h1>";
			return;
		}
		
		// HTTP status code

		if(isset($dict->_http_status))
			http_response_code($dict->_http_status);
		
		// HTTP response headers
		
		if(isset($dict->_http_response_headers) && is_array($dict->_http_response_headers)) {
			foreach($dict->_http_response_headers as $header_k => $header_v) {
				header(sprintf("%s: %s",
					$header_k,
					$header_v
				));
			}
		}
		
		// HTTP response body
		
		if(isset($dict->_http_response_body)) {
			echo $dict->_http_response_body;
		}
	}
};