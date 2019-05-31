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
		// Security
		if(null == (CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);

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
	
	function querySuggestionMetaAction() {
		$data = DevblocksPlatform::services()->data();
		
		header('Content-Type: application/json; charset=utf-8');
		
		$results = [
			'schemaVersion' => time() + 60,
			'recordTypes' => array_values(Extension_DevblocksContext::getUris()),
			'dataQueryTypes' => $data->getTypes(),
		];
		
		echo DevblocksPlatform::strFormatJson(json_encode($results));
	}
	
	function dataQuerySuggestionsAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'], 'string', '');
		@$of = DevblocksPlatform::importGPC($_REQUEST['of'], 'string', '');
		
		$data = DevblocksPlatform::services()->data();
		
		header('Content-Type: application/json; charset=utf-8');
		
		$params = [];
		
		if($of)
			$params['of'] = $of;
		
		echo DevblocksPlatform::strFormatJson(json_encode($data->getTypeMeta($type, $params)));
	}
	
	function sheetAction() {
		@$data_query = DevblocksPlatform::importGPC($_REQUEST['data_query'], 'string', '');
		@$sheet_yaml = DevblocksPlatform::importGPC($_REQUEST['sheet_yaml'], 'string', '');
		@$types = DevblocksPlatform::importGPC($_REQUEST['types'], 'array', []);
		
		$tpl = DevblocksPlatform::services()->template();
		$data = DevblocksPlatform::services()->data();
		$sheets = DevblocksPlatform::services()->sheet()->newInstance();
		$error = null;
		
		if(false == ($results = $data->executeQuery($data_query, $error))) {
			echo $error;
			return;
		}
		
		if(false == ($sheet = $sheets->parseYaml($sheet_yaml, $error))) {
			$tpl->assign('success', false);
			$tpl->assign('output', $error);
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
			return;
		}
		
		if(in_array('card', $types))
			$sheets->addType('card', $sheets->types()->card());
		
		if(in_array('custom', $types))
			$sheets->addType('custom', $sheets->types()->custom());
		
		if(in_array('date', $types))
			$sheets->addType('date', $sheets->types()->date());
		
		if(in_array('link', $types))
			$sheets->addType('link', $sheets->types()->link());
		
		if(in_array('search', $types))
			$sheets->addType('search', $sheets->types()->search());
		
		if(in_array('search_button', $types))
			$sheets->addType('search_button', $sheets->types()->searchButton());
		
		if(in_array('slider', $types))
			$sheets->addType('slider', $sheets->types()->slider());
		
		if(in_array('time_elapsed', $types))
			$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
		
		$sheets->addType('text', $sheets->types()->text());
		$sheets->setDefaultType('text');
		
		$layout = $sheets->getLayout($sheet);
		$columns = $sheets->getColumns($sheet);
		$rows = $sheets->getRows($sheet, $results['data']);
		
		$tpl->assign('layout', $layout);
		$tpl->assign('columns', $columns);
		$tpl->assign('rows', $rows);
		
		if($layout['paging'] && array_key_exists('paging', $results['_']))
			$tpl->assign('paging', $results['_']['paging']);
		
		if('fieldsets' == $layout['style']) {
			$tpl->display('devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl');
		} else {
			$tpl->display('devblocks:cerberusweb.core::ui/sheets/render.tpl');
		}
	}
};