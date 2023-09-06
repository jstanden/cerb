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
		if(null == (CerberusApplication::getActiveWorker())) {
			if($request->is_ajax) {
				DevblocksPlatform::dieWithHttpError(null, 401);
			} else {
				DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('common.access_denied'), 403);
			}
		}

		$stack = $request->path;
		array_shift($stack); // ui
		@$action = array_shift($stack);
		
		if(!$action)
			return;

		// Default action, call arg as a method suffixed with Action
		if(false === ($this->_invoke($action))) {
			if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
				trigger_error(
					sprintf('Call to undefined profile tab action `%s::%s`',
						get_class($this),
						$action
					),
					E_USER_NOTICE
				);
			}
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
	}
	
	private function _invoke($action) {
		switch($action) {
			case 'behavior':
				return $this->_uiAction_behavior();
			case 'dataQuery':
				return $this->_uiAction_dataQuery();
			case 'dataQuerySuggestions':
				return $this->_uiAction_dataQuerySuggestions();
			case 'getContextFieldsJson':
				return $this->_uiAction_getContextFieldsJson();
			case 'getContextPlaceholdersJson':
				return $this->_uiAction_getContextPlaceholdersJson();
			case 'getMentionsJson':
				return $this->_uiAction_getMentionsJson();
			case 'image':
				return $this->_uiAction_image();
			case 'kataSuggestionsAutomationCommandParamsJson':
				return $this->_uiAction_kataSuggestionsAutomationCommandParamsJson();
			case 'kataSuggestionsAutomationInputsJson':
				return $this->_uiAction_kataSuggestionsAutomationInputsJson();
			case 'kataSuggestionsCerbUriJson':
				return $this->_uiAction_kataSuggestionsCerbUriJson();
			case 'kataSuggestionsIconJson':
				return $this->_uiAction_kataSuggestionsIconJson();
			case 'kataSuggestionsMetricDimensionJson':
				return $this->_uiAction_kataSuggestionsMetricDimensionJson();
			case 'kataSuggestionsRecordFieldJson':
				return $this->_uiAction_kataSuggestionsRecordFieldJson();
			case 'kataSuggestionsRecordFieldsJson':
				return $this->_uiAction_kataSuggestionsRecordFieldsJson();
			case 'kataSuggestionsRecordTypeJson':
				return $this->_uiAction_kataSuggestionsRecordTypeJson();
			case 'markdownPreview':
				return $this->_uiAction_markdownPreview();
			case 'queryFieldSuggestions':
				return $this->_uiAction_queryFieldSuggestions();
			case 'querySuggestionMeta':
				return $this->_uiAction_querySuggestionMeta();
			case 'querySuggestions':
				return $this->_uiAction_querySuggestions();
			case 'resource':
				return $this->_uiAction_resource();
			case 'sheet':
				return $this->_uiAction_sheet();
		}
		return false;
	}
	
	private function _uiAction_kataSuggestionsRecordFieldJson() {
		header('Content-Type: application/json');
		
		$prefix = DevblocksPlatform::importGPC($_POST['prefix'] ?? null, 'string', null);
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		
		$record_type = $params['record_type'] ?? null;
		
		if(!($context_ext = Extension_DevblocksContext::getByAlias($record_type, true)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextAutocomplete))
			return;
		
		/* @var $context_ext IDevblocksContextAutocomplete */
		
		if(!($results = $context_ext->autocomplete($prefix)))
			return;
		
		echo json_encode(
			array_column(
				DevblocksPlatform::objectsToArrays($results),
				'label'
			)
		);
	}
		
	private function _uiAction_kataSuggestionsRecordFieldsJson() {
		header('Content-Type: application/json');
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		
		if(!($record_type = $params['record_type'] ?? null))
			return;
		
		if(!($context_ext = Extension_DevblocksContext::getByAlias($record_type, true)))
			return;
		
		if(!($fields = $context_ext->getKeyMeta()))
			return;
		
		$custom_fields = DAO_CustomField::getMetaByContext($context_ext->id);
		
		$dao_fieldmap = array_merge($fields, $custom_fields);
		
		ksort($dao_fieldmap);
		
		$dao_fieldmap = array_filter(
			$dao_fieldmap,
			function($key_meta) {
				$key = $key_meta['key'] ?? null;
				$type = $key_meta['type'] ?? null;
				$is_immutable = $key_meta['is_immutable'] ?? false;
				
				return $key && $type && !$is_immutable;
			}
		);
		
		echo json_encode(
			array_values(
				array_map(
					function($key_meta) {
						$key = $key_meta['key'] ?? null;
						$is_required = $key_meta['is_required'] ?? false;
						
						return [
							'caption' => $key . ':',
							'snippet' => $key . ':',
							'score' => $is_required ? 2000 : 1000,
						];
					},
					$dao_fieldmap
				)
			)
		);
	}
		
	private function _uiAction_kataSuggestionsRecordTypeJson() {
		header('Content-Type: application/json');
		
		echo json_encode(
			array_values(
				Extension_DevblocksContext::getUris()
			)
		);
	}
	
	private function _uiAction_kataSuggestionsMetricDimensionJson() {
		header('Content-Type: application/json');
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		
		if(!($metric_name = $params['metric'] ?? null))
			return;
		
		if(!($metric = DAO_Metric::getByName($metric_name)))
			return;
		
		if(
			!($dimensions = $metric->getDimensions())
			|| !is_iterable($dimensions)
		)
			return;
		
		echo json_encode(
			array_values(
				array_map(
					function($dimension_key) use ($dimensions) {
						$dimension_type = $dimensions[$dimension_key]['type'] ?? '';
						$dimension_snippet = $dimension_key . ':';
						
						// [TODO] Placeholders/defaults (e.g. `ip: 1.2.3.4`)
						if('record' == $dimension_type) {
							$dimension_type = 'Record ID of type `' . ($dimensions[$dimension_key]['params']['record_type'] ?? '') . '`';
							$dimension_snippet .= " \${1:123}";
							
						} elseif('number' == $dimension_type) {
							$dimension_type = 'Number';
							$dimension_snippet .= " \${1:Value}";
							
						} elseif('text' == $dimension_type) {
							$dimension_type = 'Text';
							$dimension_snippet .= " \${1:Value}";
						}
						
						return [
							'caption' => $dimension_key . ':',
							'snippet' => $dimension_snippet,
							'docHTML' => "<b>" . DevblocksPlatform::strEscapeHtml($dimension_key) . ":</b><br>" . DevblocksPlatform::strEscapeHtml($dimension_type),
						];
					},
					array_keys($dimensions)
				)
			)
		);
	}
	
	private function _uiAction_kataSuggestionsAutomationCommandParamsJson() {
		header('Content-Type: application/json');
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		
		$name = $params['name'] ?? null;
		$key_path = $params['key_path'] ?? null;
		$key_fullpath = $params['key_fullpath'] ?? null;
		$script = $params['script'] ?? null;
		$prefix = $params['prefix'] ?? null;
		
		if(!$name) {
			echo json_encode([]);
			return;
		}
		
		if(!($command = Extension_AutomationApiCommand::getAsInstance($name))) {
			echo json_encode([]);
			return;
		}
		
		echo json_encode($command->getAutocompleteSuggestions($key_path, $prefix, $key_fullpath, $script));
	}
	
	private function _uiAction_kataSuggestionsAutomationInputsJson() {
		header('Content-Type: application/json');
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		
		if(!array_key_exists('uri', $params)) {
			echo json_encode([]);
			return;
		}
		
		if(false === ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($params['uri']))) {
			echo json_encode([]);
			return;
		}
		
		if(!CerberusContexts::isSameContext(CerberusContexts::CONTEXT_AUTOMATION, $uri_parts['context'])) {
			echo json_encode([]);
			return;
		}
		
		if(false == ($automation = DAO_Automation::getByUri($uri_parts['context_id']))) {
			echo json_encode([]);
			return;
		}
		
		$dict = DevblocksDictionaryDelegate::getDictionaryFromModel($automation,CerberusContexts::CONTEXT_AUTOMATION, ['inputs']);
		
		$inputs = $dict->get('inputs', []);
		
		echo json_encode(
			array_values(
				array_map(
					function($input_key) use ($inputs) {
						return [
							'caption' => $input_key . ':',
							'snippet' => $inputs[$input_key]['key'] . ': ',
						];
					},
					array_keys($inputs)
				)
			)
		);
	}
	
	private function _uiAction_kataSuggestionsCerbUriJson() {
		header('Content-Type: application/json');
		
		$prefix = DevblocksPlatform::importGPC($_POST['prefix'] ?? null, 'string', null);
		$params = DevblocksPlatform::strParseQueryString(DevblocksPlatform::importGPC($_POST['params'] ?? null, 'string', null));
		
		$uri_parts = explode(':', $prefix);
		
		switch(count($uri_parts)) {
			// cerb:record_type:
			case 1:
			case 2:
				// If we were given specific record types, only return those
				if(is_array($params) && !empty($params)) {
					$record_types = Extension_DevblocksContext::getByAliases(array_keys($params), false);
				} else { // otherwise, return every record type that supports card popups
					$record_types = Extension_DevblocksContext::getAll(false, ['cards']);
				}
				
				echo json_encode(
					array_values(
						array_map(
							fn($mft) => 'cerb:' . ($mft->params['alias'] ?? '') . ':',
							$record_types
						)
					)
				);
				return;
				
			// cerb:record_type:123
			case 3:
				if(false == ($context_ext = Extension_DevblocksContext::getByAlias($uri_parts[1], true)))
					return;
				
				if(!($context_ext instanceof IDevblocksContextUri))
					return;
				
				$uri_params = $params[$uri_parts[1]] ?? [];
				
				if(false == ($results = $context_ext->autocompleteUri($uri_parts[2], $uri_params)))
					return;
				
				if(!is_iterable($results))
					return;
				
				$results = array_values(
					array_map(
						function ($result) use ($uri_parts) {
							if(
								is_array($result)
								&& array_key_exists('caption', $result)
								&& array_key_exists('snippet', $result)
							) {
								$result['caption'] = 'cerb:' . $uri_parts[1] . ':' . $result['caption'];
								$result['snippet'] = 'cerb:' . $uri_parts[1] . ':' . $result['snippet'];
							} else if (is_string($result)) {
								return 'cerb:' . $uri_parts[1] . ':' . $result;
							}
							
							return $result;
						},
						$results
					),
				);
				
				echo json_encode(array_values($results));
				return;
		}
		
		echo json_encode([]);
	}
	
	private function _uiAction_kataSuggestionsIconJson() {
		header('Content-Type: application/json');
		
		$prefix = DevblocksPlatform::importGPC($_POST['prefix'] ?? null, 'string', null);
		
		$icons = PageSection_SetupDevelopersReferenceIcons::getIcons(
			25,
			0,
			$prefix
		);
		
		echo json_encode(
			array_map(
				function($icon) {
					return [
						'caption' => $icon,
						'snippet' => $icon,
						'docHTML' => sprintf('<span class="glyphicons glyphicons-%s"></span>', $icon),
					];
				},
				$icons
			)
		);
	}
	
	private function _uiAction_getContextFieldsJson() {
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null, 'string', null);
		
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
	
	private function _uiAction_getContextPlaceholdersJson() {
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null, 'string', null);
		
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
	
	private function _uiAction_getMentionsJson() {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = 'ui:autocomplete:mentions';
		
		header('Content-Type: application/json');
		
		if(false == ($results = $cache->load($cache_key))) {
			$results = [];
			
			$workers = DAO_Worker::getAllActive();
			
			foreach ($workers as $worker) {
				if (!$worker->at_mention_name)
					continue;
				
				$results[] = [
					'_type' => 'worker',
					'label' => $worker->getName(),
					'value' => '@' . $worker->at_mention_name . ' ',
					'title' => $worker->title,
					'mention' => '@' . $worker->at_mention_name,
					'image_url' => $worker->getImageUrl(),
					'id' => $worker->id,
				];
			}
			
			$saved_searches = DAO_ContextSavedSearch::getByContext(CerberusContexts::CONTEXT_WORKER);
			
			foreach ($saved_searches as $search) {
				if (!$search->tag)
					continue;
				
				$results[] = [
					'_type' => 'saved_search',
					'label' => $search->name,
					'value' => '@' . $search->tag . ' ',
					'image_url' => $search->getImageUrl(),
					'mention' => '@' . $search->tag,
					'id' => $search->id,
				];
			}
			
			$cache->save($results, $cache_key, ['schema_mentions'], 300);
		}
		
		echo json_encode($results);
	}
	
	private function _uiAction_image() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		if('GET' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		array_shift($stack); // ui
		array_shift($stack); // image
		$token = strval(array_shift($stack) ?? null);
		
		$error = null;
		
		if(36 !== strlen($token))
			DevblocksPlatform::dieWithHttpError('Not found', 404);
		
		if(!$token || !($resource = DAO_AutomationResource::getByToken($token)))
			DevblocksPlatform::dieWithHttpError('Not found', 404);
		
		header('Content-Type: image/png');
		
		// If larger than 1MB, use streams
		if($resource->storage_size > 1_000_000) {
			$fp = DevblocksPlatform::getTempFile();
			
			if(!($resource->getFileContents($fp)))
				DevblocksPlatform::dieWithHttpError('ERROR: Resource data not found.', 404);
			
			$magic_bytes = fread($fp, 8);
			
			// Verify the "magic bytes": 89 50 4E 47 0D 0A 1A 0A
			if('89504e470d0a1a0a' != bin2hex(substr($magic_bytes,0,8))) {
				$error = "is not a valid PNG image.";
				DevblocksPlatform::dieWithHttpError('ERROR: Resource ' . $error, 500);
			}
			
			fseek($fp, 0);
			fpassthru($fp);
			fclose($fp);
			
		} else { // Otherwise output as a string
			$validator = DevblocksPlatform::services()->validation()->validators()->image();
			$bytes = $resource->getFileContents();
			
			if(!($validator('data:image/png;base64,' . base64_encode($bytes), $error)))
				DevblocksPlatform::dieWithHttpError('ERROR: Resource ' . $error, 500);
			
			echo $bytes;
		}
	}
	
	private function _uiAction_behavior() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		array_shift($stack); // ui
		array_shift($stack); // behavior
		@$behavior_uri = array_shift($stack);
		
		if(!$behavior_uri || false == ($behavior = DAO_TriggerEvent::getByUri($behavior_uri)))
			return DevblocksPlatform::dieWithHttpError('Temporarily unavailable', 503);
		
		$this->_runBehavior($behavior);
	}
	
	private function _runBehavior(Model_TriggerEvent $behavior) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!($bot = $behavior->getBot())) {
			return DevblocksPlatform::dieWithHttpError('Temporarily unavailable', 503);
		}
		
		$event = $behavior->getEvent();
		
		// Validate event
		
		if(!($event instanceof Event_AjaxHttpRequest)) {
			return DevblocksPlatform::dieWithHttpError('Forbidden', 403);
		}
		
		if($behavior->is_disabled || $bot->is_disabled) {
			return DevblocksPlatform::dieWithHttpError('Temporarily unavailable', 503);
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
			return DevblocksPlatform::dieWithHttpError('Forbidden', 403);
		}
		
		$dicts = Event_AjaxHttpRequest::trigger($behavior->id, $http_request, $active_worker, $variables);
		$dict = $dicts[$behavior->id];
		
		if(!($dict instanceof DevblocksDictionaryDelegate)) {
			return DevblocksPlatform::dieWithHttpError('Temporarily unavailable', 503);
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
	
	private function _uiAction_querySuggestionMeta() {
		$data = DevblocksPlatform::services()->data();
		$cache = DevblocksPlatform::services()->cache();
		
		header('Content-Type: application/json; charset=utf-8');
		
		$results = [
			'schemaVersion' => $cache->getTagVersion('schema_records'),
			'recordTypes' => array_values(Extension_DevblocksContext::getUris()),
			'dataQueryTypes' => array_keys($data->getTypes()),
		];
		
		echo DevblocksPlatform::strFormatJson(json_encode($results));
	}
	
	private function _uiAction_dataQuerySuggestions() {
		$data = DevblocksPlatform::services()->data();
		
		$type = DevblocksPlatform::importGPC($_REQUEST['type'] ?? null, 'string', '');
		$of = DevblocksPlatform::importGPC($_REQUEST['of'] ?? null, 'string', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		$params = [];
		
		if($of)
			$params['of'] = $of;
		
		echo DevblocksPlatform::strFormatJson(json_encode($data->getTypeMeta($type, $params)));
	}
	
	private function _uiAction_markdownPreview() {
        if('POST' != DevblocksPlatform::getHttpMethod())
            DevblocksPlatform::dieWithHttpError(null, 405);
        
        $tpl = DevblocksPlatform::services()->template();
		
		$active_worker = CerberusApplication::getActiveWorker();
		$is_dark_mode = DAO_WorkerPref::get($active_worker->id, 'dark_mode', 0);

        $content = DevblocksPlatform::importGPC($_POST['content'] ?? null, 'string','');
        
        $output = DevblocksPlatform::parseMarkdown($content);
        
        $filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
        $output = DevblocksPlatform::purifyHTML($output, true, true, [$filter]);

        $tpl->assign('is_inline', true);
        $tpl->assign('css_class', $is_dark_mode ? 'emailBodyHtml' : 'emailBodyHtmlLight');
        $tpl->assign('content', $output);
        $tpl->display('devblocks:cerberusweb.core::internal/editors/preview_popup.tpl');
    }
    
	private function _uiAction_queryFieldSuggestions() {
		$of = DevblocksPlatform::importGPC($_REQUEST['of'] ?? null, 'string', '');
		@$types = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['types'], 'string', ''));
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(!($context_ext = Extension_DevblocksContext::getByAlias($of, true)))
			return;
		
		if(!($view = $context_ext->getTempView()))
			return;
		
		$suggestions = $view->getQueryAutocompleteFieldSuggestions($types);
		
		echo DevblocksPlatform::strFormatJson(json_encode($suggestions));
	}
	
	private function _uiAction_querySuggestions() {
		$context_alias = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null, 'string', '');
		$expand = DevblocksPlatform::importGPC($_REQUEST['expand'] ?? null, 'string', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(!($context_ext = Extension_DevblocksContext::getByAlias($context_alias, true)))
			return;
		
		if(!($view = $context_ext->getTempView()))
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
					
					$expand_contexts = $expand_suggestions['_contexts'] ?? null;
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
	
	private function _uiAction_dataQuery() {
		$active_worker = CerberusApplication::getActiveWorker();
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$data_query = DevblocksPlatform::importGPC($_REQUEST['q'] ?? null, 'string', '');
		
		$error = null;
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(false === ($data_query = $tpl_builder->build($data_query, []))) {
			echo json_encode(implode("\n", $tpl_builder->getErrors()));
			return;
		}
		
		if(false == ($results = $data->executeQuery($data_query, [], $error))) {
			echo DevblocksPlatform::strFormatJson(json_encode([
				'error' => $error,
			]));
			return;
		}
		
		echo DevblocksPlatform::strFormatJson(json_encode($results));
	}
	
	private function _uiAction_resource() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		array_shift($stack); // ui
		@array_shift($stack); // resource
		@$resource_key = array_shift($stack); // e.g. map.world

		if(false == ($resource = DAO_Resource::getByName($resource_key)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($resource_ext = $resource->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		// Verify allowed resource types on this endpoint
		if(
			!in_array($resource_ext->id, [
				ResourceType_Map::ID,
				ResourceType_MapPoints::ID,
				ResourceType_MapProperties::ID,
			])) {
			DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(false == ($resource_content = $resource_ext->getContentData($resource)))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		if($resource_content->error) {
			DevblocksPlatform::dieWithHttpError($resource_content->error, 500);
		}
		
		if($resource_content->expires_at) {
			$resource_content->headers = array_merge($resource_content->headers, [
				'Pragma: cache',
				sprintf('Cache-control: max-age=%d', $resource_content->expires_at - time()),
				'Expires: ' . gmdate('D, d M Y H:i:s', $resource_content->expires_at) . ' GMT',
				'Accept-Ranges: bytes',
			]);
		}
		
		$resource_content->writeHeaders();
		$resource_content->writeBody();
	}
	
	private function _uiAction_sheet() {
		$tpl = DevblocksPlatform::services()->template();
		$data = DevblocksPlatform::services()->data();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$sheets = DevblocksPlatform::services()->sheet()->newInstance();
		$data_query = DevblocksPlatform::importGPC($_POST['data_query'] ?? null, 'string', '');
		$sheet_kata = DevblocksPlatform::importGPC($_POST['sheet_kata'] ?? null, 'string', '');
		$types = DevblocksPlatform::importGPC($_POST['types'] ?? null, 'array', []);
		
		$error = null;
		
		if(!($results = $data->executeQuery($data_query, [], $error))) {
			echo $error;
			return;
		}
		
		if (!($sheet = $sheets->parse($sheet_kata, $error))) {
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
		
		if(in_array('interaction', $types))
			$sheets->addType('interaction', $sheets->types()->interaction());
		
		if(in_array('link', $types))
			$sheets->addType('link', $sheets->types()->link());
		
		if(in_array('markdown', $types))
			$sheets->addType('markdown', $sheets->types()->markdown());
		
		if(in_array('search', $types))
			$sheets->addType('search', $sheets->types()->search());
		
		if(in_array('search_button', $types))
			$sheets->addType('search_button', $sheets->types()->searchButton());
		
		if(in_array('selection', $types))
			$sheets->addType('selection', $sheets->types()->selection());
		
		if(in_array('slider', $types))
			$sheets->addType('slider', $sheets->types()->slider());
		
		if(in_array('time_elapsed', $types))
			$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
		
		if(in_array('toolbar', $types))
			$sheets->addType('toolbar', $sheets->types()->toolbar());
		
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
		} else if('grid' == $layout['style']) {
			$tpl->assign('layout_style', 'grid');
			$tpl->display('devblocks:cerberusweb.core::ui/sheets/render_grid.tpl');
		} else if('columns' == $layout['style']) {
			$tpl->assign('layout_style', 'columns');
			$tpl->display('devblocks:cerberusweb.core::ui/sheets/render_grid.tpl');
		} else {
			$tpl->display('devblocks:cerberusweb.core::ui/sheets/render.tpl');
		}
	}
};