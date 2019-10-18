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
	
	function getContextFieldsJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context, true))) {
			echo json_encode(false);
			return;
		}

		$view_class = $context_ext->getViewClass();
		
		if(null == ($view = new $view_class())) { /* @var $view C4_AbstractView */
			echo json_encode(false);
			return;
		}
		
		$results = [];
		$params_avail = $view->getParamsAvailable();
		
		$subtotals = [];
		
		if($view instanceof IAbstractView_Subtotals) /* @var $view IAbstractView_Subtotals */
			$subtotals = $view->getSubtotalFields();
		
		if(is_array($params_avail))
		foreach($params_avail as $param) { /* @var $param DevblocksSearchField */
			if(empty($param->db_label))
				continue;
		
			$results[] = array(
				'key' => $param->token,
				'label' => mb_convert_case($param->db_label, MB_CASE_LOWER),
				'type' => $param->type,
				'sortable' => $param->is_sortable,
				'subtotals' => array_key_exists($param->token, $subtotals),
			);
		}
		
		echo json_encode($results);
	}
	
	function getContextPlaceholdersJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', null);
		
		header('Content-Type: application/json');
		
		$labels = [];
		$values = [];
		
		CerberusContexts::getContext($context, null, $labels, $values, null, true);
		
		if(empty($labels)) {
			echo json_encode(false);
			return;
		}
		
		$types = @$values['_types'] ?: [];
		$results = [];
		
		foreach($labels as $k => $v) {
			$results[] = array(
				'key' => $k,
				'label' => $v,
				'type' => @$types[$k] ?: '',
			);
		}
		
		echo json_encode($results);
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
		$cache = DevblocksPlatform::services()->cache();
		
		header('Content-Type: application/json; charset=utf-8');
		
		$results = [
			'schemaVersion' => $cache->getTagVersion('schema_records'),
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
	
	function queryFieldSuggestionsAction() {
		@$of = DevblocksPlatform::importGPC($_REQUEST['of'], 'string', '');
		@$types = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['types'], 'string', ''));
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($of, true)))
			return;
		
		if(false == ($view = $context_ext->getTempView()))
			return;
		
		$suggestions = $view->getQueryAutocompleteFieldSuggestions($types);
		
		echo DevblocksPlatform::strFormatJson(json_encode($suggestions));
	}
	
	function yamlSuggestionsFormInteractionsAction() {
		$events = DAO_TriggerEvent::getByEvent(Event_FormInteractionWorker::ID);
		
		header('Content-Type: application/json; charset=utf-8');
		
		$suggestions = [
			'' => [
				[
					'caption' => 'behaviors:',
					'snippet' => "behaviors:\r\n- ",
				]
			],
			'behaviors:' => [],
			'behaviors:-:' => [],
		];
		
		// Load form interaction behaviors
		
		foreach($events as $event) {
			$suggestion = [
				'caption' => $event->title,
				'snippet' => sprintf("id: %s\r\n  label: >-\r\n    %s\r\n",
					$event->uri ?: $event->id,
					$event->title
				),
			];
			
			if($event->hasPublicVariables()) {
				$suggestion['snippet'] .= "  inputs:\r\n";
				
				foreach($event->variables as $var) {
					if($var['is_private'])
						continue;
					
					// [TODO] Per variable defaults
					
					$suggestion['snippet'] .= sprintf("    %s: >-\r\n      ~\r\n",
						$var['key']
					);
				}
			}
			
			$suggestion['snippet'] .= '- ';
			
			$suggestions['behaviors:-:'][] = $suggestion;
		}
		
		echo DevblocksPlatform::strFormatJson(json_encode($suggestions));
	}
	
	function querySuggestionsAction() {
		@$context_alias = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$expand = DevblocksPlatform::importGPC($_REQUEST['expand'], 'string', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias($context_alias, true)))
			return;
		
		if(false == ($view = $context_ext->getTempView()))
			return;
		
		$suggestions = $view->getQueryAutocompleteSuggestions();
		
		// Expand
		
		if($expand && array_key_exists('_contexts', $suggestions)) {
			$expand_keys = explode(':', rtrim($expand,':'));
			$expand_prefix = '';
			
			foreach($expand_keys as $expand_key) {
				$expand_key .=  ':';
				$expand_prefix .= $expand_key;
				
				if(array_key_exists($expand_prefix, $suggestions['_contexts'])) {
					if(false == ($expand_context_ext = Extension_DevblocksContext::getByAlias($suggestions['_contexts'][$expand_prefix], true)))
						return;
					
					if(false == ($expand_view = $expand_context_ext->getTempView()))
						return;
					
					$expand_suggestions = $expand_view->getQueryAutocompleteSuggestions();
					
					@$expand_contexts = $expand_suggestions['_contexts'];
					unset($expand_suggestions['_contexts']);
					
					if($expand_contexts) {
						$suggestions['_contexts'] = array_merge(
							$suggestions['_contexts'],
							array_combine(
								array_map(function($k) use ($expand_prefix) {
									return $expand_prefix . $k;
								}, array_keys($expand_contexts)),
								$expand_contexts
							)
						);
					}
					
					if($expand_suggestions) {
						$suggestions = array_merge(
							$suggestions,
							array_combine(
								array_map(function($k) use ($expand_prefix) {
									return $expand_prefix . $k;
								}, array_keys($expand_suggestions)),
								$expand_suggestions
							)
						);
					}
				}
			}
		}
		
		echo DevblocksPlatform::strFormatJson(json_encode($suggestions));
	}
	
	function dataQueryAction() {
		@$data_query = DevblocksPlatform::importGPC($_REQUEST['q'], 'string', '');
		
		$data = DevblocksPlatform::services()->data();
		
		$error = null;
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(false == ($results = $data->executeQuery($data_query, $error))) {
			echo DevblocksPlatform::strFormatJson(json_encode([
				'error' => $error,
			]));
			return;
		}
		
		echo DevblocksPlatform::strFormatJson(json_encode($results));
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
		
		if(in_array('date', $types))
			$sheets->addType('date', $sheets->types()->date());
		
		if(in_array('icon', $types))
			$sheets->addType('icon', $sheets->types()->icon());
		
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