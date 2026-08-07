<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

		// Invoke action from the path
		if(false === ($this->_invoke($action))) {
			if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
				trigger_error(
					sprintf('Call to undefined ui action `%s::%s`',
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
			case 'calendarEventsJson':
				return $this->_uiAction_calendarEventsJson();
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
			case 'getReplyCommandsJson':
				return $this->_uiAction_getReplyCommandsJson();
			case 'iconsJson':
				return $this->_uiAction_iconsJson();
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
			case 'kataSuggestionsMetricNamesJson':
				return $this->_uiAction_kataSuggestionsMetricNamesJson();
			case 'kataSuggestionsRecordFieldJson':
				return $this->_uiAction_kataSuggestionsRecordFieldJson();
			case 'kataSuggestionsRecordFieldsJson':
				return $this->_uiAction_kataSuggestionsRecordFieldsJson();
			case 'kataSuggestionsRecordFieldsValueJson':
				return $this->_uiAction_kataSuggestionsRecordFieldsValueJson();
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
			case 'sheetBuilderData':
				return $this->_uiAction_sheetBuilderData();
			case 'sheetBuilderPreview':
				return $this->_uiAction_sheetBuilderPreview();
		}
		return false;
	}
	
	private function _uiAction_kataSuggestionsRecordFieldJson() {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
		
	private function _uiAction_kataSuggestionsRecordFieldsJson() : void {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		
		if(!($record_type = $params['record_type'] ?? null))
			return;
		
		if(!($context_ext = Extension_DevblocksContext::getByAlias($record_type, true)))
			return;
		
		if(!($fields = $context_ext->getKeyMeta()))
			return;

		// Bulk actions (e.g. `records.update:`) can't update unique fields, so omit them
		$skip_unique = !empty($params['skip_unique']);

		$custom_fields = DAO_CustomField::getMetaByContext($context_ext->id);
		
		$dao_fieldmap = array_merge($fields, $custom_fields);
		
		ksort($dao_fieldmap);
		
		$dao_fieldmap = array_filter(
			$dao_fieldmap,
			function($key_meta) use ($skip_unique) {
				$key = $key_meta['key'] ?? null;
				$type = $key_meta['type'] ?? null;
				$is_immutable = $key_meta['is_immutable'] ?? false;

				if($skip_unique && ($key_meta['is_unique'] ?? false))
					return false;

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
							'caption' => $key . ':' . ($is_required ? '*' : ''),
							'snippet' => $key . ':',
							'description' => ($key_meta['notes'] ?? ''),
							'score' => $is_required ? 2000 : 1000,
						];
					},
					$dao_fieldmap
				)
			)
		);
	}
	
	private function _uiAction_kataSuggestionsRecordFieldsValueJson() : void {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		
		try {
			if (!($record_type = $params['record_type'] ?? null))
				throw new Exception_DevblocksAjaxValidationError();
			
			if (!($field_name = $params['field_name'] ?? null))
				throw new Exception_DevblocksAjaxValidationError();
			
			if (!($context_ext = Extension_DevblocksContext::getByAlias($record_type, true)))
				throw new Exception_DevblocksAjaxValidationError();
			
			if (!($record_field_suggestions = $context_ext->getKeyAutocompleteSuggestions()))
				throw new Exception_DevblocksAjaxValidationError();
			
			echo json_encode($record_field_suggestions[$field_name] ?? []);
			
		} catch (Throwable) {
			echo json_encode([]);
			return;
		}
	}
		
	private function _uiAction_kataSuggestionsRecordTypeJson() : void {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$suggestions = [];

		foreach(Extension_DevblocksContext::getAll(false) as $mft) { /* @var $mft DevblocksExtensionManifest */
			if(!($alias = $mft->params['alias'] ?? null))
				continue;

			$icon = $mft->params['icon'] ?? 'circle';

			$suggestions[$alias] = [
				'caption' => $alias,
				'snippet' => $alias,
				'docHTML' => sprintf(
					'<span class="cerb-icons cerb-icon-%s"></span> %s',
					DevblocksPlatform::strEscapeHtml($icon),
					DevblocksPlatform::strEscapeHtml($alias)
				),
			];
		}

		ksort($suggestions);

		echo json_encode(array_values($suggestions));
	}
	
	private function _uiAction_kataSuggestionsMetricDimensionJson() : void {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
	
	private function _uiAction_kataSuggestionsMetricNamesJson() : void {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$prefix = DevblocksPlatform::importGPC($_POST['prefix'] ?? null, 'string', '');

		$suggestions = [];

		// Offer the current-record placeholder (resolves to the metric's name on a metric card)
		if($prefix === '' || false !== stripos('{{record_name}}', $prefix)) {
			$suggestions[] = [
				'caption' => '{{record_name}}',
				'snippet' => '{{record_name}}',
				'docHTML' => '<b>{{record_name}}</b><br>The current record&#39;s name',
			];
		}

		foreach(DAO_Metric::getAll() as $metric) { /* @var $metric Model_Metric */
			if($prefix !== '' && false === stripos($metric->name, $prefix))
				continue;

			$doc = $metric->type . ($metric->description ? (' — ' . $metric->description) : '');

			$suggestions[] = [
				'caption' => $metric->name,
				'snippet' => $metric->name,
				'docHTML' => '<b>' . DevblocksPlatform::strEscapeHtml($metric->name) . '</b><br>' . DevblocksPlatform::strEscapeHtml($doc),
			];
		}

		echo json_encode($suggestions);
	}

	private function _uiAction_kataSuggestionsAutomationCommandParamsJson() {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
		
		if(!($automation = DAO_Automation::getByUri($uri_parts['context_id']))) {
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
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
				if(!($context_ext = Extension_DevblocksContext::getByAlias($uri_parts[1], true)))
					return;
				
				if(!($context_ext instanceof IDevblocksContextUri))
					return;
				
				$uri_params = $params[$uri_parts[1]] ?? [];
				
				if(!($results = $context_ext->autocompleteUri($uri_parts[2], $uri_params)))
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
	
	// The full list of cerb-icons names — drives CerbUI.IconPicker (fetched once, client-cached).
	private function _uiAction_iconsJson() {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		$icons = DevblocksPlatform::services()->ui()->getCerbIcons();
		echo json_encode(array_values($icons));
	}

	private function _uiAction_kataSuggestionsIconJson() {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$prefix = DevblocksPlatform::importGPC($_POST['prefix'] ?? null, 'string', null);
		
		$icons = DevblocksPlatform::services()->ui()->getCerbIcons(
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
						'docHTML' => sprintf('<span class="cerb-icons cerb-icon-%s"></span>', $icon),
					];
				},
				$icons
			)
		);
	}
	
	private function _uiAction_getContextFieldsJson() {
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null, 'string', null);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		if(!($results = $cache->load($cache_key))) {
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
	
	// The inline `#command` autocomplete for the mail reply/compose composers (ported from the legacy
	// cerbTextEditorAutocompleteReplies `_sourceCommand`). `mode` (reply|compose) gates the ticket-only commands.
	private function _uiAction_getReplyCommandsJson() {
		$mode = DevblocksPlatform::importGPC($_REQUEST['mode'] ?? null, 'string', 'reply');
		if(!in_array($mode, ['reply','compose']))
			$mode = 'reply';
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		// command => [label, value, description, modes]. `value` is inserted verbatim (note the trailing space /
		// newline). `modes` limits a command to specific composers; omit = all.
		$commands = [
			['command' => '#attach', 'label' => '#attach', 'value' => '#attach ', 'description' => 'Attach a file bundle by alias'],
			['command' => '#comment', 'label' => '#comment', 'value' => '#comment ', 'description' => 'Add a ticket comment with @mention notifications'],
			['command' => '#cut', 'label' => '#cut', 'value' => "#cut\n", 'description' => 'Ignore everything below this line'],
			['command' => '#delete_quote_from_here', 'label' => '#delete_quote_from_here', 'value' => '#delete_quote_from_here', 'description' => 'Remove remaining quoted text from this line', 'modes' => ['reply']],
			['command' => '#original_message', 'label' => '#original_message', 'value' => '#original_message', 'description' => 'Insert the full original message placeholder', 'modes' => ['reply']],
			['command' => '#signature', 'label' => '#signature', 'value' => "#signature\n", 'description' => 'Insert the signature placeholder'],
			['command' => '#snippet', 'label' => '#snippet', 'value' => '#snippet ', 'description' => 'Insert a snippet'],
			['command' => '#start comment', 'label' => '#start comment', 'value' => "#start comment\nYour multiple line comment goes here.\n#end\n", 'description' => 'Add a multiple line ticket comment with @mention notifications'],
			['command' => '#start note', 'label' => '#start note', 'value' => "#start note\nYour multiple line sticky note goes here.\n#end\n", 'description' => 'Add a multiple line sticky note with @mention notifications'],
			['command' => '#unwatch', 'label' => '#unwatch', 'value' => "#unwatch\n", 'description' => 'Stop watching this ticket', 'modes' => ['reply']],
			['command' => '#watch', 'label' => '#watch', 'value' => "#watch\n", 'description' => 'Start watching this ticket', 'modes' => ['reply']],
		];
		
		// #attach only applies when the file bundles plugin is enabled
		$has_file_bundles = DevblocksPlatform::isPluginEnabled('cerb.file_bundles');
		
		$results = array_values(array_filter($commands, function($cmd) use ($mode, $has_file_bundles) {
			if(isset($cmd['modes']) && !in_array($mode, $cmd['modes']))
				return false;
			if('#attach' === $cmd['command'] && !$has_file_bundles)
				return false;
			return true;
		}));
		
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
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'image/png');
		
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
			$validator = DevblocksPlatform::services()->validation()->validators()->image(
				max_width: 4096,
				max_height: 4096,
				max_size: 2_048_000,
			);
			
			$bytes = $resource->getFileContents();
			
			if(!($validator('data:image/png;base64,' . base64_encode($bytes), $error)))
				DevblocksPlatform::dieWithHttpError('ERROR: Resource ' . $error, 500);
			
			echo $bytes;
		}
	}
	
	// [TODO] Remove this in 12.0
	private function _uiAction_behavior() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy'))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		array_shift($stack); // ui
		array_shift($stack); // behavior
		@$behavior_uri = array_shift($stack);
		
		if(!$behavior_uri || !($behavior = DAO_TriggerEvent::getByUri($behavior_uri)))
			return DevblocksPlatform::dieWithHttpError('Temporarily unavailable', 503);
		
		$this->_runBehavior($behavior);
	}
	
	private function _runBehavior($behavior) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy'))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($behavior instanceof Model_TriggerEvent))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
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
				DevblocksPlatform::services()->http()->setHeader($header_k, $header_v);
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
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
		
		// `inline` (default 1) embeds the fragment; `inline=0` runs the popup title script (a real Dialog title)
		$is_inline = DevblocksPlatform::importGPC($_POST['inline'] ?? null, 'integer', 1) ? true : false;
		
		// Optional handling of a leading YAML frontmatter block (--- ... ---):
		//   keep (default) | ignore (strip it) | code (render as a ```yaml block) | table (key/value table)
		$frontmatter_mode = DevblocksPlatform::importGPC($_POST['frontmatter'] ?? null, 'string', 'keep');
		$prefix_html = '';
		
		if('keep' != $frontmatter_mode && preg_match('/^---\r?\n(.*?)\r?\n---[ \t]*(?:\r?\n|$)/s', $content, $m)) {
			$frontmatter = $m[1];
			$content = substr($content, strlen($m[0]));
			
			if('ignore' == $frontmatter_mode) {
				// strip it — render only the body
			} else if('table' == $frontmatter_mode && '' !== ($prefix_html = self::_renderFrontmatterTable($frontmatter))) {
				// rendered as a key/value table into $prefix_html
			} else {
				// code (or a table that failed to parse) — fold the raw YAML into a fenced code block
				$content = "```yaml\n" . $frontmatter . "\n```\n\n" . $content;
			}
		}
		
		$output = DevblocksPlatform::parseMarkdown($content);
		
		$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
		$output = DevblocksPlatform::purifyHTML($output, true, true, [$filter]);
		
		if('' !== $prefix_html)
			$output = $prefix_html . $output;
		
		$tpl->assign('is_inline', $is_inline);
		$tpl->assign('css_class', $is_dark_mode ? 'emailBodyHtml' : 'emailBodyHtmlLight');
		$tpl->assign('content', $output);
		$tpl->display('devblocks:cerberusweb.core::internal/editors/preview_popup.tpl');
	}
	
	private static function _renderFrontmatterTable($yaml) : string {
		$error = null;
		$data = DevblocksPlatform::services()->string()->yamlParse($yaml, 0, $error);
		
		if(!is_array($data) || !$data)
			return '';
		
		$rows = '';
		
		foreach($data as $k => $v) {
			if(is_bool($v))
				$v = $v ? 'true' : 'false';
			else if(is_array($v))
				$v = implode(', ', array_map(fn($x) => is_scalar($x) ? strval($x) : json_encode($x), $v));
			else if(is_null($v))
				$v = '';
			else if(!is_scalar($v))
				$v = json_encode($v);
			
			$rows .= sprintf('<tr><th style="text-align:left;vertical-align:top;padding:2px 12px 2px 0;white-space:nowrap;opacity:0.7;">%s</th><td style="padding:2px 0;">%s</td></tr>',
				htmlspecialchars(strval($k), ENT_QUOTES),
				nl2br(htmlspecialchars(strval($v), ENT_QUOTES))
			);
		}
		
		if('' === $rows)
			return '';
		
		return '<table style="margin:0 0 1em 0;border-collapse:collapse;font-size:0.9em;">' . $rows . '</table>';
	}
	
	private function _uiAction_queryFieldSuggestions() {
		$of = DevblocksPlatform::importGPC($_REQUEST['of'] ?? null, 'string', '');
		@$types = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['types'], 'string', ''));
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
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

	// Feeds CerbUI.Calendar: a calendar's events for a [from,to] range, day-keyed (the shape
	// CerbUI.cal.dedupeServerEvents expects). No occlusion — that only trims/splits availability
	// blocks and would fragment spanning-strip events. Epochs are absolute; the client interprets
	// them at the tzOffsetMinutes passed by the widget (the worker's timezone).
	private function _uiAction_calendarEventsJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$calendar_id = DevblocksPlatform::importGPC($_REQUEST['calendar_id'] ?? null, 'integer', 0);
		$from = DevblocksPlatform::importGPC($_REQUEST['from'] ?? null, 'integer', 0);
		$to = DevblocksPlatform::importGPC($_REQUEST['to'] ?? null, 'integer', 0);

		$empty = ['events' => new stdClass()];

		if(!$calendar_id || false == ($calendar = DAO_Calendar::get($calendar_id))) {
			echo json_encode($empty);
			return;
		}

		if(!Context_Calendar::isReadableByActor($calendar, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		if($from <= 0 || $to <= 0 || $to < $from) {
			echo json_encode($empty);
			return;
		}

		// Cap the range (a year view fetches ~365 days) to bound abuse.
		if(($to - $from) > (400 * 86400))
			$to = $from + (400 * 86400);

		$calendar_events = $calendar->getEvents($from, $to);

		$out = [];

		foreach($calendar_events as $ts => $ts_events) {
			$rows = [];

			foreach($ts_events as $ev) {
				$rows[] = [
					'context' => $ev['context'] ?? null,
					'context_id' => $ev['context_id'] ?? null,
					'label' => $ev['label'] ?? '',
					'color' => $ev['color'] ?? null,
					'ts' => $ev['ts'] ?? null,
					'ts_end' => $ev['ts_end'] ?? null,
					'ts_range_start' => $ev['ts_range_start'] ?? ($ev['ts'] ?? null),
					'ts_range_end' => $ev['ts_range_end'] ?? ($ev['ts_end'] ?? null),
					'is_available' => intval($ev['is_available'] ?? 0),
				];
			}

			$out[strval($ts)] = $rows;
		}

		echo json_encode(['events' => empty($out) ? new stdClass() : $out]);
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
		
		if(in_array('code', $types))
			$sheets->addType('code', $sheets->types()->code());
		
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
	
	// Resolve the Sheet Builder's active dataset (mode + config) into rows + paging. Shared by the two
	// sheet-builder endpoints. dataQuery runs the assembled worklist.records query; automation/manual go
	// through the shared sheet()->resolveDataSet() resolver.
	private function _sheetBuilderResolveRows(&$error=null) : ?array {
		$sheets = DevblocksPlatform::services()->sheet();
		
		$mode = DevblocksPlatform::importGPC($_POST['mode'] ?? null, 'string', 'manual');
		$page = DevblocksPlatform::importGPC($_POST['page'] ?? null, 'integer', 0);
		$limit = DevblocksPlatform::importGPC($_POST['limit'] ?? null, 'integer', 10);
		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string', '');
		
		// Both the "Records" builder mode and the raw "Data query" mode resolve via a data query.
		if('dataQuery' == $mode || 'records' == $mode) {
			$data = DevblocksPlatform::services()->data();
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$data_query = DevblocksPlatform::importGPC($_POST['data_query'] ?? null, 'string', '');
			
			if(false === ($data_query = $tpl_builder->build($data_query, []))) {
				$error = implode("\n", $tpl_builder->getErrors());
				return null;
			}
			
			if(false == ($results = $data->executeQuery($data_query, [], $error)))
				return null;
			
			return ['data' => $results['data'] ?? [], 'paging' => $results['_']['paging'] ?? []];
		}
		
		if('automation' == $mode) {
			$uri = DevblocksPlatform::importGPC($_POST['automation_uri'] ?? null, 'string', '');
			$inputs_kata = DevblocksPlatform::importGPC($_POST['automation_inputs'] ?? null, 'string', '');
			$inputs = [];
			
			if($inputs_kata) {
				if(false === ($inputs = DevblocksPlatform::services()->kata()->parse($inputs_kata, $error)))
					return null;
				
				$inputs = DevblocksPlatform::services()->kata()->formatTree($inputs);
			}
			
			$resolved = $sheets->resolveDataSet(['automation' => ['uri' => $uri, 'inputs' => $inputs]], compact('page', 'limit', 'filter'));
			return ['data' => $resolved['data'], 'paging' => $resolved['paging']];
		}
		
		// manual
		$rows_json = DevblocksPlatform::importGPC($_POST['rows'] ?? null, 'string', '');
		$rows = [];
		
		if($rows_json) {
			$rows = json_decode($rows_json, true);
			
			if(!is_array($rows)) {
				$error = 'Sample rows must be a JSON array of objects.';
				return null;
			}
		}
		
		$resolved = $sheets->resolveDataSet($rows, compact('page', 'limit', 'filter'));
		return ['data' => $resolved['data'], 'paging' => $resolved['paging']];
	}
	
	// Sheet Builder: resolve the dataset → return sample rows + the union of their keys (drives the
	// column key-pickers). Superuser-only (dev tool).
	private function _uiAction_sheetBuilderData() {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$error = null;
		
		if(null === ($resolved = $this->_sheetBuilderResolveRows($error))) {
			echo json_encode(['error' => $error]);
			return;
		}
		
		$out_rows = [];
		$keys = [];
		
		foreach($resolved['data'] as $row) {
			// worklist.records rows are DevblocksDictionaryDelegate objects; literal/automation rows are arrays.
			$arr = ($row instanceof DevblocksDictionaryDelegate) ? $row->getDictionary() : (is_array($row) ? $row : null);
			
			if(!is_array($arr))
				continue;
			
			$out_rows[] = $arr;
			
			foreach(array_keys($arr) as $k) {
				// Skip internal double-underscore keys (e.g. __index)
				if(is_string($k) && DevblocksPlatform::strStartsWith($k, '__'))
					continue;
				
				$keys[$k] = true;
			}
		}
		
		ksort($keys);
		
		echo DevblocksPlatform::strFormatJson(json_encode([
			'data' => $out_rows,
			'keys' => array_keys($keys),
		]));
	}
	
	// Sheet Builder: resolve the dataset + parse the sheet KATA + register the requested column types →
	// render the sheet HTML. A dataset-aware sibling of _uiAction_sheet(). Superuser-only.
	private function _uiAction_sheetBuilderPreview() {
		$tpl = DevblocksPlatform::services()->template();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$sheets = DevblocksPlatform::services()->sheet()->newInstance();
		$sheet_kata = DevblocksPlatform::importGPC($_POST['sheet_kata'] ?? null, 'string', '');
		$types = DevblocksPlatform::importGPC($_POST['types'] ?? null, 'array', []);
		
		$error = null;
		
		if(null === ($resolved = $this->_sheetBuilderResolveRows($error))) {
			$tpl->assign('success', false);
			$tpl->assign('output', $error);
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
			return;
		}
		
		if(!($sheet = $sheets->parse($sheet_kata, $error))) {
			$tpl->assign('success', false);
			$tpl->assign('output', $error);
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
			return;
		}
		
		// The builder emits a `sheet:` wrapper (data + schema{layout,columns}); the sheet service works on the
		// schema subtree. Unwrap it here (tolerate a flat layout:/columns: too).
		if(is_array($sheet['sheet'] ?? null))
			$sheet = $sheet['sheet']['schema'] ?? $sheet['sheet'];
		
		// Register only the requested types (the client passes its allowed set); text is always the fallback.
		$type_funcs = [
			'card' => 'card', 'code' => 'code', 'date' => 'date', 'icon' => 'icon',
			'interaction' => 'interaction', 'link' => 'link', 'markdown' => 'markdown',
			'search' => 'search', 'search_button' => 'searchButton', 'selection' => 'selection',
			'slider' => 'slider', 'time_elapsed' => 'timeElapsed', 'toolbar' => 'toolbar',
		];
		
		foreach($type_funcs as $type => $fn) {
			if(in_array($type, $types))
				$sheets->addType($type, $sheets->types()->$fn());
		}
		
		$sheets->addType('text', $sheets->types()->text());
		$sheets->setDefaultType('text');
		
		$layout = $sheets->getLayout($sheet);
		$columns = $sheets->getColumns($sheet);
		$rows = $sheets->getRows($sheet, $resolved['data']);
		
		$tpl->assign('layout', $layout);
		$tpl->assign('columns', $columns);
		$tpl->assign('rows', $rows);
		
		if($layout['paging'] && $resolved['paging'])
			$tpl->assign('paging', $resolved['paging']);
		
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
