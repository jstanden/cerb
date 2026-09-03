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

class PageSection_ProfilesAutomation extends Extension_PageSection {
	// Ceiling for the `await:queue:` gate poll's backoff. Deliberately small: once a turn's worker sidecar
	// has been cut by the gateway, this timer is the only thing left that notices the turn landed, so the
	// cap is a direct floor on how long the reader waits after it does.
	const AWAIT_QUEUE_POLL_MS_MAX = 5000;

	private array $_interaction_extensions = [
		AutomationTrigger_InteractionInternal::ID,
		AutomationTrigger_InteractionWorker::ID,
		AutomationTrigger_InteractionWorkerAgent::ID,
		AutomationTrigger_MailDraftValidate::ID,
		AutomationTrigger_MailReplyValidate::ID,
	];
	
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // automation 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_AUTOMATION;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'interruptAgent':
					return $this->_profileAction_interruptAgent();
				case 'echoAgentTurn':
					return $this->_profileAction_echoAgentTurn();
				case 'pollAgentTurn':
					return $this->_profileAction_pollAgentTurn();
				case 'editorLog':
					return $this->_profileAction_editorLog();
				case 'editorLogRefresh':
					return $this->_profileAction_editorLogRefresh();
				case 'editorUsage':
					return $this->_profileAction_editorUsage();
				case 'editorVisualize':
					return $this->_profileAction_editorVisualize();
				case 'formBuilderPreview':
					return $this->_profileAction_formBuilderPreview();
				case 'generatePolicy':
					return $this->_profileAction_generatePolicy();
				case 'getAutocompleteJson':
					return $this->_profileAction_getAutocompleteJson();
				case 'getBulkUpdateFieldset':
					return $this->_profileAction_getBulkUpdateFieldset();
				case 'getExtensionConfig':
					return $this->_profileAction_getExtensionConfig();
				case 'getInteractionsMenu':
					return $this->_profileAction_getInteractionsMenu();
				case 'invokePrompt':
					return $this->_profileAction_invokePrompt();
				case 'listInteractions':
					return $this->_profileAction_listInteractions();
				case 'disposeInteraction':
					return $this->_profileAction_disposeInteraction();
				case 'renderEditorToolbar':
					return $this->_profileAction_renderEditorToolbar();
				case 'resumeInteraction':
					return $this->_profileAction_resumeInteraction();
				case 'runAutomationEditor':
					return $this->_profileAction_runAutomationEditor();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'sendMessage':
					return $this->_profileAction_sendMessage();
				case 'showExportPopup':
					return $this->_profileAction_showExportPopup();
				case 'showFormBuilderPopup':
					return $this->_profileAction_showFormBuilderPopup();
				case 'showFormStatePreviewPopup':
					return $this->_profileAction_showFormStatePreviewPopup();
				case 'submitFormStatePreview':
					return $this->_profileAction_submitFormStatePreview();
				case 'showSheetBuilderPopup':
					return $this->_profileAction_showSheetBuilderPopup();
				case 'showPrimeStatePopup':
					return $this->_profileAction_showPrimeStatePopup();
				case 'showStateDiffPopup':
					return $this->_profileAction_showStateDiffPopup();
				case 'showTemplateWizard':
					return $this->_profileAction_showTemplateWizard();
				case 'applyTemplate':
					return $this->_profileAction_applyTemplate();
				case 'submitPrimeState':
					return $this->_profileAction_submitPrimeState();
				case 'startInteraction':
					return $this->_profileAction_startInteraction();
				case 'stepAutomationEditor':
					return $this->_profileAction_stepAutomationEditor();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
				case 'viewSparklinesJson':
					return $this->_profileAction_viewSparklinesJson();
			}
		}
		return false;
	}

	// Inline sparkline series (stacked runs+errors bars + avg-duration line) for the automations
	// worklist; loaded async so the list paints fast. One metrics.timeseries query per page (no N+1).
	private function _profileAction_viewSparklinesJson() {
		$active_worker = CerberusApplication::getActiveWorker();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? [], 'array', []);
		$ids = array_filter(array_map('intval', $ids));

		$window = DevblocksPlatform::importGPC($_REQUEST['window'] ?? '1d', 'string', '1d');

		$row_series = [];

		// Automations are readable by everyone, but require a logged-in worker. Each row: total runs as
		// a green bar, errors (exit_state:error) stacked on top in red, then avg duration as an orange
		// line in front. Both bars share a `stack` key so they share one scale and stack cumulatively.
		if($active_worker && $ids) {
			foreach($ids as $id) {
				$row_series[$id] = [
					// All runs (green) — total invocations
					['metric' => 'cerb.automation.invocations', 'function' => 'count', 'type' => 'bar', 'label' => 'runs', 'color' => '#2ca02c', 'stack' => 'invocations', 'query' => ['automation_id' => $id], 'missing' => 'zero'],
					// Errors (red) — invocations that ended in error, stacked on top
					['metric' => 'cerb.automation.invocations', 'function' => 'count', 'type' => 'bar', 'label' => 'errors', 'color' => '#d62728', 'stack' => 'invocations', 'query' => ['automation_id' => $id, 'exit_state' => 'error'], 'missing' => 'zero'],
					// Avg duration (orange) — line in front
					['metric' => 'cerb.automation.duration', 'function' => 'avg', 'type' => 'line', 'label' => 'duration', 'color' => '#ff7f0e', 'query' => ['automation_id' => $id], 'missing' => 'zero', 'suffix' => 'ms'],
				];
			}
		}

		$out = $row_series
			? DAO_MetricValue::getSparklines($row_series, $window, $active_worker->timezone ?: null)
			: [];

		// Cast so the response is always a JSON object ({} when empty), keyed by automation id
		echo json_encode((object) $out);
	}
	
	/**
	 * Which blocks an `agent.tool` script uses that it must not: any `await:` at any depth, since a tool has no
	 * continuation to resume into.
	 *
	 * Returns a set keyed by block name, so the caller can report one specific problem rather than a generic
	 * "unsupported" list.
	 */
	private function _collectAgentToolUnsupportedBlocks(string $script) : array {
		$error = null;
		$tree = DevblocksPlatform::services()->kata()->parse($script, $error, true);

		// A genuine syntax error is reported by the schema validation elsewhere -- don't double-report here.
		if(!is_array($tree))
			return [];

		$found = [];

		$walk = function($node) use (&$walk, &$found) {
			if(!is_array($node))
				return;

			foreach($node as $key => $value) {
				// `await:` and `await/<name>:` are the same command.
				if(is_string($key) && ('await' === $key || str_starts_with($key, 'await/')))
					$found['await'] = true;

				$walk($value);
			}
		};

		$walk($tree);

		return $found;
	}
	
	// Collect the `<type>` of every `await:form:elements:<type>/<var>` element in a script (element keys carry a
	// `/`). Used to validate authored form elements against the trigger's advertised getFormComponentMeta() — a
	// type the trigger doesn't advertise is silently dropped at render, so we reject it at save time.
	private function _collectAwaitFormElementTypes(string $script) : array {
		$error = null;
		$tree = DevblocksPlatform::services()->kata()->parse($script, $error, true);

		// A genuine syntax error is reported by the schema validation elsewhere — don't double-report here.
		if(!is_array($tree))
			return [];

		$types = [];

		$walk = function($node) use (&$walk, &$types) {
			if(!is_array($node))
				return;

			foreach($node as $key => $value) {
				if($key === 'elements' && is_array($value)) {
					foreach(array_keys($value) as $el_key) {
						if(is_string($el_key) && str_contains($el_key, '/'))
							$types[] = substr($el_key, 0, strpos($el_key, '/'));
					}
				}

				if(is_array($value))
					$walk($value);
			}
		};

		$walk($tree);

		return array_values(array_unique($types));
	}

	private function _profileAction_savePeekJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->is_superuser)
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_AUTOMATION)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!($model = DAO_Automation::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Automation::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				// The `cerb.` namespace is reserved
				if(
					DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($model->name), 'cerb.')
					&& !DEVELOPMENT_MODE
				) {
					$error = 'The `cerb.` namespace is managed automatically. This automation may not be deleted.';
					throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_AUTOMATION, $model->id, $model->name);
				
				DAO_Automation::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$description = DevblocksPlatform::importGPC($_POST['description'] ?? null, 'string', '');
				$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');
				$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
				$script = DevblocksPlatform::importGPC($_POST['automation_script'] ?? null, 'string', '');
				
				$error = null;
				
				$fields = [];
				
				// Only admins
				if(!$active_worker->is_superuser)
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
				
				if(!$extension_id || !($trigger_ext = Extension_AutomationTrigger::get($extension_id))) {
					throw new Exception_DevblocksAjaxValidationError('Invalid trigger extension.');
				}
				
				/* @var $trigger_ext Extension_AutomationTrigger */
				
				$fields[DAO_Automation::EXTENSION_ID] = $trigger_ext->id;
				
				if(false === ($trigger_ext->validateConfig($params, $error))) {
					throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				$fields[DAO_Automation::EXTENSION_PARAMS_JSON] = json_encode($params);

				// Guard: an `await:form:` element whose `<type>` the trigger doesn't advertise renders as NOTHING
				// at runtime (newFormComponent() returns null → the element is silently skipped, so a
				// uiCommand/agentPrompt/etc. never fires and its result comes back empty). Reject the save with an
				// actionable message instead of leaving the author to debug empty results. `uiCommand`, for example,
				// is only on `interaction.worker.agent` -- not the generic `interaction.worker`.
				if(method_exists($trigger_ext, 'getFormComponentMeta')) {
					$advertised = array_keys($trigger_ext::getFormComponentMeta());
					$unsupported = array_values(array_unique(array_diff(
						$this->_collectAwaitFormElementTypes($script), $advertised
					)));

					if($unsupported) {
						$hint = in_array('uiCommand', $unsupported)
							? ' The `uiCommand` element requires the “interaction.worker.agent” trigger.' : '';
						throw new Exception_DevblocksAjaxValidationError(sprintf(
							'The “%s” trigger does not support the `await:form:` element type%s: %s. They render as nothing at runtime.%s',
							$trigger_ext->manifest->name,
							count($unsupported) === 1 ? '' : 's',
							implode(', ', array_map(fn($t) => '`' . $t . '`', $unsupported)),
							$hint
						));
					}
				}

				// Guard: an agent tool runs to completion inside a single tool call, so an `await:` has nothing
				// to suspend to and would strand the turn. Its `inputs:` block, by contrast, is REQUIRED
				// reading -- that block is the schema the model is shown.
				if(AutomationTrigger_AgentTool::ID === $trigger_ext->id) {
					$offenders = $this->_collectAgentToolUnsupportedBlocks($script);

					if(array_key_exists('await', $offenders))
						throw new Exception_DevblocksAjaxValidationError(
							'An agent tool cannot `await:`. It runs to completion inside a single tool call, so there '
							. 'is nothing to suspend to -- return a `content:` string instead.'
						);
				}

				// Validate policy KATA
				
				$policy_kata = DevblocksPlatform::importGPC($_POST['automation_policy_kata'] ?? null, 'string', '');
				
				$fields[DAO_Automation::POLICY_KATA] = $policy_kata;
				
				if(empty($id)) { // New
					// The `cerb.` namespace is reserved
					if(
						DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($name), 'cerb.')
						&& !DEVELOPMENT_MODE
					) {
						$error = 'The `cerb.` namespace is reserved. Use your own prefix for `Name:`';
						throw new Exception_DevblocksAjaxValidationError($error);
					}
					
					$fields[DAO_Automation::NAME] = $name;
					$fields[DAO_Automation::DESCRIPTION] = $description;
					$fields[DAO_Automation::SCRIPT] = $script;
					$fields[DAO_Automation::UPDATED_AT] = time();
					
					if(!DAO_Automation::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Automation::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Automation::create($fields);
					
				} else { // Edit
					// The `cerb.` namespace is reserved
					if(
						DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($name), 'cerb.')
						&& !DEVELOPMENT_MODE
					) {
						$error = 'The `cerb.` namespace is managed automatically. Clone this automation to modify it.';
						throw new Exception_DevblocksAjaxValidationError($error);
					}
					
					$fields[DAO_Automation::NAME] = $name;
					$fields[DAO_Automation::DESCRIPTION] = $description;
					$fields[DAO_Automation::SCRIPT] = $script;
					$fields[DAO_Automation::UPDATED_AT] = time();
					
					if(!DAO_Automation::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_Automation::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Automation::update($id, $fields);
				}
				
				DAO_Automation::onUpdateByActor($active_worker, $fields, $id);
				
				if($id) {
					// Versioning
					try {
						DAO_RecordChangeset::create(
							'automation',
							$id,
							[
								'script' => $fields[DAO_Automation::SCRIPT] ?? '',
								'policy' => $fields[DAO_Automation::POLICY_KATA] ?? '',
							],
							$active_worker->id ?? 0
						);
						
					} catch (Exception $e) {
						DevblocksPlatform::logError('Error saving automation changeset: ' . $e->getMessage());
					}
					
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_AUTOMATION, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode([
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				]);
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
		}
	}
	
	private function _profileAction_editorUsage() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);

		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$automation_name = DevblocksPlatform::importGPC($_POST['automation_name'] ?? null, 'string');
		$trigger = DevblocksPlatform::importGPC($_POST['trigger'] ?? null, 'string');
		
		if(!($trigger_ext = Extension_AutomationTrigger::get($trigger)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Ask the extension directly for usage details
		/* @var $trigger_ext Extension_AutomationTrigger */
		$results = $trigger_ext->getUsageMeta($automation_name);
		
		// Event listeners
		if(($linked_event_listeners = DAO_AutomationEventListener::getWhere(sprintf("%s = %s AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_AutomationEventListener::EVENT_NAME),
			Cerb_ORMHelper::qstr($trigger_ext->manifest->name),
			Cerb_ORMHelper::escape(DAO_AutomationEventListener::EVENT_KATA),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_event_listeners = array_filter($linked_event_listeners, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w->event_kata, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_event_listeners)
				$results['automation_event_listener'] = array_column($linked_event_listeners, 'id');
		}
		
		// Workflows
		if(($linked_workflows = DAO_Workflow::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_Workflow::WORKFLOW_KATA),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_workflows = array_filter($linked_workflows, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w->workflow_kata, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_workflows)
				$results['workflow'] = array_column($linked_workflows, 'id');
		}
		
		$data = [];

		// Stable per-record-type accent for the tile icon square (hashed into the tag palette)
		$palette = ['blue', 'green', 'purple', 'orange', 'red', 'gray'];

		foreach($results as $record_type => $record_ids) {
			$record_ext = Extension_DevblocksContext::getByAlias($record_type);

			if(!$record_ext)
				continue;

			$icon = $record_ext->params['icon'] ?? 'collection';
			$color = $palette[abs(crc32($record_type)) % count($palette)];

			foreach($record_ids as $record_id) {
				$data[] = DevblocksDictionaryDelegate::instance([
					'_context' => $record_ext->id,
					'_type' => $record_type,
					'_type_label' => $record_ext->name,
					'_type_icon' => $icon,
					'_type_color' => $color,
					'id' => $record_id
				]);
			}
		}

		if(empty($data)) {
			echo '(no usage found)';
			return;
		}

		// Resolve the record label + profile URL for every tile in one pass per context
		DevblocksDictionaryDelegate::bulkLazyLoad($data, '_label');
		DevblocksDictionaryDelegate::bulkLazyLoad($data, 'record_url');

		// Sort dictionaries by _type_label, then record label
		DevblocksPlatform::sortObjects($data, '_type_label');

		$tpl->assign('usage', $data);
		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor_usage.tpl');
	}
	
	private function _profileAction_editorLog() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);

		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$automation_name = DevblocksPlatform::importGPC($_POST['automation_name'] ?? null, 'string');
		$tpl->assign('automation_name', $automation_name);
		
		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/tab_log.tpl');
	}
	
	private function _profileAction_editorLogRefresh() {
		$tpl = DevblocksPlatform::services()->template();
		$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);

		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$automation_name = DevblocksPlatform::importGPC($_POST['automation_name'] ?? null, 'string');
		$page = DevblocksPlatform::importGPC($_POST['page'] ?? 0, 'integer', 0);
		$limit = 10;
		$sheet_dicts = [];
		
		list($results, $total) = DAO_AutomationLog::search(
			[],
			[
				new DevblocksSearchCriteria(SearchFields_AutomationLog::AUTOMATION_NAME,'=',$automation_name)
			],
			$limit,
			$page,
			SearchFields_AutomationLog::CREATED_AT,
			false
		);
		
		foreach($results as $result) {
			$sheet_dicts[] = DevblocksDictionaryDelegate::instance([
				'id' => $result[SearchFields_AutomationLog::ID],
				'name' => $result[SearchFields_AutomationLog::AUTOMATION_NAME],
				'node' => $result[SearchFields_AutomationLog::AUTOMATION_NODE],
				'created_at' => $result[SearchFields_AutomationLog::CREATED_AT],
				'log_level' => $result[SearchFields_AutomationLog::LOG_LEVEL],
				'log_message' => $result[SearchFields_AutomationLog::LOG_MESSAGE],
			]);
		}
		
		$sheet_kata = <<< EOD
		layout:
		  headings@bool: no
		  paging@bool: yes
		  title_column: node
		columns:
		  text/node:
		    label: At
		    params:
		      value_template: {{node}} ({{created_at|date_pretty}})
		  text/log_message:
		    label: Message
		EOD;
		
		if(!($sheet = $sheets->parse($sheet_kata, $error)))
			$sheet = [];
		
		$layout = $sheets->getLayout($sheet);
		$rows = $sheets->getRows($sheet, $sheet_dicts);
		$columns = $sheets->getColumns($sheet);
		
		$tpl->assign('layout', $layout);
		$tpl->assign('rows', $rows);
		$tpl->assign('columns', $columns);
		
		$paging = $sheets->getPaging(
			count($results),
			$page,
			$limit,
			$total
		);
		
		if($layout['paging'] && $paging) {
			$tpl->assign('paging', $paging);
		}
		
		$tpl->display('devblocks:cerberusweb.core::ui/sheets/render.tpl');
	}

	private function _profileAction_editorVisualize() {
		$tpl = DevblocksPlatform::services()->template();

		$script = DevblocksPlatform::importGPC($_POST['script'] ?? null, 'string');
		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');

		$automation = new Model_Automation();
		$automation->script = $script;
		$automation->extension_id = $extension_id;

		if(!($graph = $automation->getSyntaxGraphForViewer($error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}

		$tpl->assign('graph', $graph);
		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/tab_visualize.tpl');
	}

	// Generate a least-privilege `commands:` policy from the live script for the editor's Policy tab (the editor is
	// admin-only). Returns JSON {policy_kata, error}.
	private function _profileAction_generatePolicy() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);

		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$script = DevblocksPlatform::importGPC($_POST['automation_script'] ?? null, 'string');
		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');

		$automation = new Model_Automation();
		$automation->script = $script;
		$automation->extension_id = $extension_id;

		$error = null;
		$policy_kata = $automation->generatePolicyKata($error);

		if(is_null($policy_kata)) {
			echo json_encode(['policy_kata' => null, 'error' => $error ?: 'The script could not be parsed.']);
			return;
		}

		echo json_encode(['policy_kata' => $policy_kata, 'error' => null]);
	}
	
	function _profileAction_invokePrompt() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string', '');
		$prompt_key = DevblocksPlatform::importGPC($_POST['prompt_key'] ?? null, 'string', '');
		$prompt_action = DevblocksPlatform::importGPC($_POST['prompt_action'] ?? null, 'string', '');
		
		if(DevblocksPlatform::strEndsWith($prompt_key,'/'))
			$prompt_key = rtrim($prompt_key,'/');
		
		if(!$prompt_key)
			return;
		
		// Load the execution
		if(!($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Check actor
		
		// [TODO] Do this better
		$session_actor = [
			'context' => $continuation->state_data['actor']['context'] ?? null,
			'context_id' => $continuation->state_data['actor']['id'] ?? null,
		];
		
		if(!CerberusContexts::isSameActor(CerberusApplication::getActiveWorker(), $session_actor))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$dict = $continuation->state_data['dict'];
		$form = $dict['__return']['form']['elements'] ?? [];
		
		if(!array_key_exists($prompt_key, $form))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Read the component registry off THIS continuation's own trigger, not a hardcoded one: a subclass may
		// advertise types the base worker trigger doesn't (interaction.worker.agent adds `uiCommand`), and
		// newFormComponent() is the seam that keeps a differently-shaped Await constructor transparent.
		$trigger_extension = $continuation->getAutomation()?->getTriggerExtension();

		if(!$trigger_extension || !method_exists($trigger_extension, 'getFormComponentMeta'))
			DevblocksPlatform::dieWithHttpError(null, 404);

		$form_components = $trigger_extension::getFormComponentMeta();

		list($prompt_type, $prompt_name) = array_pad(explode('/', $prompt_key, 2), 2, null);

		if(!array_key_exists($prompt_type, $form_components))
			DevblocksPlatform::dieWithHttpError(null, 404);

		if(!($component = $trigger_extension::newFormComponent($prompt_type, $prompt_name, null, $form[$prompt_key])))
			DevblocksPlatform::dieWithHttpError(null, 404);

		$component->invoke($prompt_key, $prompt_action, $continuation);
	}
	
	private function _profileAction_renderEditorToolbar() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$trigger = DevblocksPlatform::importGPC($_POST['trigger'] ?? null, 'string', null);
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.editor.automation.script',
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id
		]);
		
		if(!($trigger_ext = Extension_AutomationTrigger::get($trigger, true)))
			return;
		
		/** @var $trigger_ext Extension_AutomationTrigger */
		
		$toolbar = $trigger_ext->getEditorToolbar();

		$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar, $toolbar_dict);

		DevblocksPlatform::services()->ui()->toolbar()->render($toolbar);

		// Flag whether this trigger supports the visual Form Builder (worker family + interaction.website). The
		// editor's Form Builder button is a static toolbar item, so the client toggles its visibility from this
		// marker on trigger change.
		if($this->_supportsFormComponents($trigger_ext))
			echo '<span data-cerb-supports-form-builder hidden></span>';
	}

	// Automation Builder — render a picked candidate's code-driven CerbUI wizard (the config slide-in). Empty
	// body ⇒ no config step; the client applies the template immediately.
	private function _profileAction_showTemplateWizard() : void {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$template_id = DevblocksPlatform::importGPC($_POST['template_id'] ?? null, 'string', '');

		if(!($template_ext = \Cerb\Extensions\Extension_AutomationTemplate::get($template_id)))
			return;

		try {
			/** @var $template_ext \Cerb\Extensions\Extension_AutomationTemplate */
			echo $template_ext->renderWizard();

		} catch(Throwable $e) {
			// A wizard is plugin code that can reference classes the class loader hasn't registered yet (a new
			// file under an existing loader dir still needs /update). Uncaught, that ends the whole request as a
			// raw 500 with the popup mid-render. `applyTemplate` already catches; this is the other half.
			DevblocksPlatform::logException($e);

			echo sprintf('<div class="cerb-ui-panel--alert">%s</div>',
				DevblocksPlatform::strEscapeHtml('This template could not build its setup form. If you just installed or upgraded a plugin, run Setup > Configure > Plugins > Update. The error was logged.')
			);
		}
	}

	// Automation Builder — turn a candidate's wizard answers into the editor seed. The client seeds the
	// ephemeral (id 0) editor from this; nothing is persisted until the author hits Save.
	private function _profileAction_applyTemplate() : void {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$template_id = DevblocksPlatform::importGPC($_POST['template_id'] ?? null, 'string', '');
		$answers = DevblocksPlatform::importGPC($_POST['answers'] ?? null, 'array', []);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			if(!($template_ext = \Cerb\Extensions\Extension_AutomationTemplate::get($template_id)))
				throw new Exception_DevblocksAjaxValidationError('Invalid template.');

			/** @var $template_ext \Cerb\Extensions\Extension_AutomationTemplate */
			$seed = $template_ext->build($answers);

			if(!$seed || !is_array($seed))
				throw new Exception_DevblocksAjaxValidationError('Invalid template.');

			// The automation TRIGGER this template targets (for the editor's trigger bubble + autocomplete).
			$seed_extension_id = strval($seed['extension_id'] ?? '');
			$seed_trigger = Extension_AutomationTrigger::get($seed_extension_id, false);

			echo json_encode([
				'status' => true,
				'extension_id' => $seed_extension_id,
				'extension_name' => $seed_trigger?->name ?? '',
				'script' => strval($seed['script'] ?? ''),
				'policy_kata' => strval($seed['policy_kata'] ?? ''),
			]);

		} catch(Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(['status' => false, 'error' => $e->getMessage()]);

		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			echo json_encode(['status' => false, 'error' => 'An unknown error occurred.']);
		}
	}

	// Pause or end an interaction the worker is closing. Pause just stores the given name (the conversation
	// stays awaiting and resumable); end sets a terminal state so it drops out of the Resume list and ages
	// out via maint(). The name defaults client-side to the dialog title.
	private function _profileAction_disposeInteraction() : void {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$active_worker = CerberusApplication::getActiveWorker();
		$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string', '');
		$disposition = DevblocksPlatform::importGPC($_POST['disposition'] ?? null, 'string', 'pause');
		$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		if(
			!$active_worker
			|| !$continuation_token
			|| !($continuation = DAO_AutomationContinuation::getByToken($continuation_token))
			|| $continuation->worker_id != $active_worker->id
			|| !in_array($continuation->extension_id, DAO_AutomationContinuation::getWorkerResumableExtensionIds())
			|| '' === strval($continuation->resume_scope)
		) {
			echo json_encode(['status' => 'error', 'error' => "You can't modify that conversation."]);
			return;
		}

		// A single-column write, never a read-modify-write of `state_data`: that blob is rewritten whole with no
		// guard by every turn, so merging into it here would race the conversation this is naming.
		$fields = [
			DAO_AutomationContinuation::RESUME_LABEL => DAO_AutomationContinuation::normalizeResumeText($name),
			DAO_AutomationContinuation::UPDATED_AT => time(),
		];

		if('end' == $disposition)
			$fields[DAO_AutomationContinuation::STATE] = 'exit';

		DAO_AutomationContinuation::update($continuation_token, $fields);

		echo json_encode(['status' => 'ok']);
	}

	// Reopen a worker's own parked interaction from the command bar. Renders the popup shell seeded with the
	// continuation token; panel.tpl's auto-submit (no `__submit`) then re-renders the awaiting form without
	// advancing the automation. Returns the same JSON envelope startInteraction does, so the client's await
	// popup-open path is reused unchanged.
	private function _profileAction_resumeInteraction() : void {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$active_worker = CerberusApplication::getActiveWorker();
		$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string', '');
		$layer = DevblocksPlatform::importGPC($_POST['layer'] ?? null, 'string', '');
		$interaction_style = DevblocksPlatform::importGPC($_POST['interaction_style'] ?? null, 'string', '');
		$caller = DevblocksPlatform::importGPC($_POST['caller'] ?? null, 'array', []);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		// Three gates, each doing a different job: `worker_id` is SECURITY (the ACL), `extension_id` is a SANITY
		// check on the trigger family, and `resume_scope` is ROUTING — which surface may reopen this.
		if(
			!$active_worker
			|| !$continuation_token
			|| !($continuation = DAO_AutomationContinuation::getByToken($continuation_token))
			|| $continuation->worker_id != $active_worker->id
			|| !in_array($continuation->extension_id, DAO_AutomationContinuation::getWorkerResumableExtensionIds())
			|| '' === strval($continuation->resume_scope)
		) {
			echo json_encode(['exit' => 'error', 'error' => "You can't resume that conversation."]);
			return;
		}

		// Routing, enforced — not merely filtered in the list. Reopening an editor-pane chat somewhere without
		// that editor's `command` bridge leaves every `uiCommand` await silently returning an empty result, with
		// no error anywhere; refusing here is what makes that unreachable rather than merely unlikely.
		if($continuation->resume_scope !== DAO_AutomationContinuation::resumeScopeFor($caller)) {
			echo json_encode(['exit' => 'error', 'error' => "That conversation belongs to a different workspace."]);
			return;
		}

		// Parked somewhere a UI can actually re-enter? `queue` counts — a turn left running finishes server-side
		// and the poll picks back up on reopen.
		if(!in_array($continuation->state_await, DAO_AutomationContinuation::getResumableAwaitTypes(), true)) {
			echo json_encode(['exit' => 'error', 'error' => "That conversation can't be reopened from here."]);
			return;
		}

		// Terminal continuations aren't resumable.
		if('await' != $continuation->state) {
			echo json_encode(['exit' => 'error', 'error' => 'That conversation has already ended.']);
			return;
		}

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('layer', $layer);
		$tpl->assign('continuation_token', $continuation_token);

		ob_start();
		// Inline hosts (an editor's agent pane) get the bare panel; the command bar gets the popup shell. Same
		// contract as `startInteraction`, so a resumed chat is indistinguishable from a fresh one.
		if('inline' == $interaction_style) {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/panel.tpl');
		} else {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/popup.tpl');
		}
		$out = ob_get_clean();

		echo json_encode([
			'exit' => 'await',
			'html' => $out,
		]);
	}

	/**
	 * The worker's own resumable conversations for ONE launcher — what an editor's agent pane offers in its
	 * History. Scoped by the posted caller, so the pane only ever sees chats it can actually drive: reopening
	 * one somewhere without that editor's `command` bridge would leave its `uiCommand` awaits silently empty.
	 *
	 * Read-only, and cheap enough to call on every pane open.
	 */
	private function _profileAction_listInteractions() : void {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$active_worker = CerberusApplication::getActiveWorker();
		$caller = DevblocksPlatform::importGPC($_POST['caller'] ?? null, 'array', []);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		// No worker, or a caller with no scope (an unrecognized launcher) → nothing is resumable there.
		if(!$active_worker || '' === ($scope = DAO_AutomationContinuation::resumeScopeFor($caller))) {
			echo json_encode(['status' => true, 'items' => []]);
			return;
		}

		// The same launchers the pane rendered its tiles from, re-parsed here so a resumed conversation inherits
		// the label and picture of the tile that started it -- which is now the AGENT's name and avatar. Scoped
		// by `component` exactly as the host page section builds it, so an agent not enabled on this editor
		// can't name a row in its History.
		$surface = strval($caller['params']['component'] ?? '');

		$identity = DAO_AutomationContinuation::launcherIdentityFromToolbarItems(
			\Cerb\Agent\Pane\Launchers::parse($surface, \Cerb\Agent\Pane\Launchers::newDict($surface))
		);

		echo json_encode([
			'status' => true,
			'items' => array_values(DAO_AutomationContinuation::getResumableRowsForScopes($active_worker->id, [$scope], 25, $identity)),
		]);
	}

	private function _profileAction_sendMessage() : void {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string', '');
		
		if($continuation_token) {
			$this->_consoleSendMessageAsAutomation($continuation_token);
		} else {
			DevblocksPlatform::dieWithHttpError(null, 403);
		}
	}
	
	private function _consoleSendMessageAsAutomation(string $continuation_token) {
		// Load the session
		if(!($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$exit_state = $continuation->state_data['dict']['__exit'] ?? null;
		
		// If the automation exited already, respond
		if('await' != $exit_state) {
			$initial_state = $continuation->state_data['dict'] ?? [];
			$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
			$this->_respondAutomationAwait($continuation, $automation_results);
			return;
		}
		
		$this->_handleAutomationAwait($continuation);
	}
	
	/**
	 * The canonical key order for a simulator state: alphabetical, with the runtime's bookkeeping pushed to the
	 * bottom where it's out of the way. This is what the Output pane has always been emitted in.
	 *
	 * It's factored out because the state DIFF depends on both sides being ordered by the same rule. The two are
	 * authored differently — the prime flow leads with `inputs:` then the trigger's scope keys, while a run's
	 * output lands here — so comparing them as authored reports identical data at different positions as a
	 * rewrite. Canonicalizing an already-canonical Output is a no-op, so the diff's right side still matches the
	 * Output pane line-for-line; only the Input side gets reordered.
	 */
	private function _canonicalizeState(array $state) : array {
		// An editor-only flag injected before the run; never part of either pane, and it would read as removed.
		unset($state['__simulate']);

		ksort($state);

		// Move the state info to the end
		if(array_key_exists('__state', $state)) {
			$runtime_state = $state['__state'];
			unset($state['__state']);
			$state['__state'] = $runtime_state;
		}

		// Move expandable to the end
		if(array_key_exists('__expandable', $state)) {
			$expandable = $state['__expandable'];
			unset($state['__expandable']);
			$state['__expandable'] = $expandable;
		}

		return $state;
	}

	/**
	 * Side-by-side diff of the Run tab's Input against its Output, so an author can see what the automation
	 * actually did rather than eyeballing two YAML docs. Takes the LIVE pane contents (not the last run's
	 * result), so it stays honest after a hand-edit or a Step.
	 */
	private function _profileAction_showStateDiffPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$strings = DevblocksPlatform::services()->string();
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);

		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$input_yaml = DevblocksPlatform::importGPC($_POST['input'] ?? null, 'string', '');
		$output_yaml = DevblocksPlatform::importGPC($_POST['output'] ?? null, 'string', '');

		$error = null;

		// Say which side failed rather than diffing garbage — an unparseable pane means the comparison is a lie.
		if(false === ($input_state = $strings->yamlParse($input_yaml, 0, $error))) {
			$tpl->assign('error', 'The Input state is not valid YAML: ' . $error);
		} else if(false === ($output_state = $strings->yamlParse($output_yaml, 0, $error))) {
			$tpl->assign('error', 'The Output state is not valid YAML: ' . $error);
		} else {
			$tpl->assign('diff_before', $strings->yamlEmit($this->_canonicalizeState($input_state ?: []), false));
			$tpl->assign('diff_after', $strings->yamlEmit($this->_canonicalizeState($output_state ?: []), false));
		}

		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/popup_state_diff.tpl');
	}

	private function _profileAction_showExportPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$kata = DevblocksPlatform::services()->kata();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$fields = DevblocksPlatform::importGPC($_POST['fields'] ?? null, 'array', []);
		
		// Package
		
		$package_data = [
			'package' => [],
			'records' => [
				[
					'uid' => uniqid('automation_'),
					'_context' => 'automation',
					'name' => $fields['name'] ?? '',
					'extension_id' => $fields['extension_id'] ?? '',
					'description' => $fields['description'] ?? '',
					'script' => $fields['script'] ?? '',
					'policy_kata' => $fields['policy_kata'] ?? '',
					'created_at' => time(),
					'updated_at' => time(),
				]
			]
		];
		$tpl->assign('export_json', DevblocksPlatform::strFormatJson($package_data));
		
		// Workflow
		
		$workflow_data = [
			'records' => [
				'automation/' . uniqid() => [
					'fields' => [
						'name' => $fields['name'] ?? '',
						'extension_id' => $fields['extension_id'] ?? '',
						'description' => $fields['description'] ?? '',
						'script' => new DevblocksKataRawString($fields['script'] ?? ''),
						'policy_kata' => new DevblocksKataRawString($fields['policy_kata'] ?? ''),
					]
				]
			]
		];
		$tpl->assign('export_workflow', $kata->emit($workflow_data));
		
		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/popup_export.tpl');
	}
	
	// This figures out if we're using modern or legacy interactions
	private function _profileAction_startInteraction() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$interaction_uri = DevblocksPlatform::importGPC($_POST['interaction_uri'] ?? null, 'string', null);
		$interaction_behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'] ?? null, 'integer', 0);
		
		if($interaction_uri && DevblocksPlatform::strStartsWith($interaction_uri, 'cerb:')) {
			if(($interaction_uri_parts = DevblocksPlatform::services()->ui()->parseURI($interaction_uri))) {
				$interaction_uri = $interaction_uri_parts['context_id'];
			}
		}
		
		$bot_page =
			DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy')
			? new PageSection_ProfilesBot()
			: null
		;
		
		if(
			$interaction_uri
			&& ($automation = DAO_Automation::getByUri($interaction_uri, $this->_interaction_extensions))
		) {
			return $this->_startBotInteractionAsAutomation($automation);
			
		} else if(
			$interaction_uri
			&& is_numeric($interaction_uri)
			&& class_exists('DAO_TriggerEvent')
			&& ($behavior = DAO_TriggerEvent::get($interaction_uri))
			&& $bot_page
		) {
			return $bot_page->startBotInteractionAsFormBehavior($behavior);
			
		} else if(
			$interaction_uri
			&& class_exists('DAO_TriggerEvent')
			&& ($behavior = DAO_TriggerEvent::getByUri($interaction_uri))
			&& $bot_page
		) {
			return $bot_page->startBotInteractionAsFormBehavior($behavior);
			
		} else if(
			$interaction_behavior_id
			&& ($behavior = DAO_TriggerEvent::get($interaction_behavior_id))
			&& $bot_page
		) {
			return $bot_page->startBotInteractionAsConvoBehavior($behavior);
			
		} else {
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
	}
	
	/**
	 * Take the launcher's RESERVED `agent` key out of the interaction params and hand back the trigger state it
	 * becomes: `agent__context` + `agent_id`, the same shape `worker_*` takes, so `{{agent_name}}` and
	 * `{{agent__image_url}}` expand lazily off the context.
	 *
	 * It is consumed rather than passed through because an automation validates its inputs against its own
	 * declared `inputs:` block -- an undeclared one fails the run outright ("Unknown inputs: agent"). Requiring
	 * every agent chat to declare it would make WHICH AGENT something each script configures, and it isn't:
	 * it belongs to the trigger's state, exactly like the active worker does. That is also what lets ONE
	 * interaction serve every agent, with `agent@key: agent_id` instead of a hardcoded id or `@mention`.
	 *
	 * `$interaction_params` is by reference because the key must be gone BEFORE it becomes `inputs`.
	 *
	 * An unresolvable or non-AI reference contributes nothing rather than seeding a scope that lies about who
	 * is running.
	 *
	 * THIS IS THE ENFORCEMENT POINT FOR AGENT ACL, AND THERE ISN'T ONE YET.
	 *
	 * The reference arrives in client-supplied params (`data-interaction-params` -> POST), so today any worker
	 * who can open an agent pane can start ANY agent by editing the posted id -- including one an administrator
	 * configured for themselves. The only gate is `resolveAgentWorker()`: it must be an `is_ai` worker that
	 * isn't disabled. What's missing is a per-agent audience: a query on the agent record for which workers may
	 * use it, checked HERE before the state is seeded, and refusing the launch rather than silently dropping the
	 * keys (a silent drop would run the chat as nobody, which reads as a bug rather than a refusal).
	 *
	 * Bounded today, not safe: an agent's instructions, tools, filesystems, and model pool are reachable this
	 * way, though anything a tool actually DOES is still gated by the automation's own policy, and RESUME is
	 * already safe -- `_profileAction_resumeInteraction()` checks the continuation's `worker_id`, so this is a
	 * start-time hole only.
	 */
	private function _agentStateFor(array &$interaction_params) : array {
		$ref = trim(strval($interaction_params[\Cerb\Agent\Pane\Launchers::INPUT_AGENT] ?? ''));

		unset($interaction_params[\Cerb\Agent\Pane\Launchers::INPUT_AGENT]);

		if('' === $ref)
			return [];

		if(!($worker = \Cerb\AutomationBuilder\Node\LlmAgentNode::resolveAgentWorker($ref)))
			return [];

		return [
			'agent__context' => CerberusContexts::CONTEXT_WORKER,
			'agent_id' => $worker->id,
		];
	}

	private function _startBotInteractionAsAutomation(Model_Automation $automation) : void {
		$automator = DevblocksPlatform::services()->automation();
		
		$interaction_style = DevblocksPlatform::importGPC($_POST['interaction_style'] ?? null, 'string');
		$interaction_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		$browser = DevblocksPlatform::importGPC($_POST['browser'] ?? null, 'array', []);
		$layer = DevblocksPlatform::importGPC($_POST['layer'] ?? null, 'string', '');
		$caller = DevblocksPlatform::importGPC($_POST['caller'] ?? null, 'array', []);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$error = null;
		$user_agent = DevblocksPlatform::getClientUserAgent();
		
		// Before `inputs` is built from them -- see _agentStateFor().
		$agent_state = $this->_agentStateFor($interaction_params);
		
		$initial_state = array_merge([
			'caller_name' => '',
			'caller_params' => [],
			'client_ip' => DevblocksPlatform::getClientIp(),
			'client_browser_name' => $user_agent['browser'] ?? null,
			'client_browser_platform' => $user_agent['platform'] ?? null,
			'client_browser_version' => $user_agent['version'] ?? null,
			'client_url' => $browser['url'] ?? null,
			'inputs' => $interaction_params,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		], $agent_state);
		
		if($caller) {
			$initial_state['caller_name'] = DevblocksPlatform::importGPC($caller['name'] ?? null, 'string', '');
			$initial_state['caller_params'] = DevblocksPlatform::importGPC($caller['params'] ?? null, 'array', []);
		}
		
		$policy = $automation->getPolicy();
		
		if (!$policy->isCallerAllowed($initial_state['caller_name'], DevblocksDictionaryDelegate::instance($initial_state))) {
			$error = sprintf(
				"The automation policy does not allow this command (%s).",
				$initial_state['caller_name']
			);
			
			$automation_results = DevblocksDictionaryDelegate::instance([
				'__exit' => 'await',
				'__return' => [
					'form' => [
						'elements' => [
							'say/__validation' => [
								'content' => $error,
								'style' => 'error',
							],
						],
					],
				]
			]);
			
		} else {
			if(false === ($automation_results = $automator->executeScript($automation, $initial_state, $error)))
				DevblocksPlatform::logError($error);
		}
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('layer', $layer);
		
		if (false === $automation_results) {
			echo json_encode([
				'exit' => 'error',
				'exit_state' => null,
				'dict' => DevblocksPlatform::services()->string()->yamlEmit([
					'__exit' => 'error',
					'error' => $error,
				], false),
			]);
			return;
		}
		
		$exit_code = $automation_results->get('__exit');
		
		// If we're awaiting, start a continuation
		if('await' == $exit_code) {
			ob_start();
			
			list($continuation_token, $state_data) = array_values($this->_startInteractionAutomationSession($automation, $caller, $interaction_params));
			
			$tpl->assign('continuation_token', $continuation_token);
			
			$state_data['dict'] = $automation_results->getDictionary();
			
			DAO_AutomationContinuation::update($continuation_token, [
				DAO_AutomationContinuation::STATE => $exit_code,
				DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
			]);
			
			if('inline' == $interaction_style) {
				$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/panel.tpl');
			} else {
				$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/popup.tpl');
			}
			
			$out = ob_get_clean();
			
			echo json_encode([
				'exit' => $exit_code,
				'html' => $out,
			]);
			
			// Otherwise, if we had a final result, return it immediately
		} else {
			echo json_encode([
				'exit' => $exit_code,
				'return' => $automation_results->getKeyPath('__return', []),
			]);
		}
	}
	
	private function _startInteractionAutomationSession(Model_Automation $automation, array $caller=[], array $interaction_params=[], $continuation_token=null) : array {
		$active_worker = CerberusApplication::getActiveWorker();
		$user_agent = DevblocksPlatform::getClientUserAgent();
		

		// The same promotion the run's dict gets: this one is STORED on the continuation, so it's what every
		// resume reads back.
		$agent_state = $this->_agentStateFor($interaction_params);
		
		$initial_state = array_merge([
			'caller_name' => '',
			'caller_params' => [],
			'client_ip' => DevblocksPlatform::getClientIp(),
			'client_browser_name' => $user_agent['browser'] ?? null,
			'client_browser_platform' => $user_agent['platform'] ?? null,
			'client_browser_version' => $user_agent['version'] ?? null,
			'inputs' => $interaction_params,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		], $agent_state);
		
		if($caller) {
			$initial_state['caller_name'] = DevblocksPlatform::importGPC($caller['name'] ?? '', 'string', '');
			$initial_state['caller_params'] = DevblocksPlatform::importGPC($caller['params'] ?? [], 'array', []);
		}
		
		$dict = DevblocksDictionaryDelegate::instance($initial_state);
		
		$state_data = [
			'actor' => ['context' => CerberusContexts::CONTEXT_WORKER, 'id' => $active_worker->id],
			'caller' => $caller,
			'interaction_params' => $interaction_params,
			'dict' => $dict->getDictionary(),
		];
		
		if(!$continuation_token) {
			$continuation_token = DAO_AutomationContinuation::create([
				DAO_AutomationContinuation::URI => $automation->name,
				DAO_AutomationContinuation::EXTENSION_ID => $automation->extension_id,
				DAO_AutomationContinuation::WORKER_ID => $active_worker->id,
				// WHERE this may later be reopened -- and whether at all. The LAUNCHER opts in by having a scope;
				// a caller we don't recognize resolves to '' and its interactions simply aren't resumable. Only the
				// command bar and an editor's agent pane resolve to anything, and both host real conversations, so
				// this allowlist is the whole gate -- an editor-local chooser never reaches a list.
				DAO_AutomationContinuation::RESUME_SCOPE => DAO_AutomationContinuation::resumeScopeFor($caller),
				DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
				DAO_AutomationContinuation::EXPIRES_AT => time()+3600, // 1hr
				DAO_AutomationContinuation::UPDATED_AT => time(),
			]);

		} else {
			// Re-seeding an existing token is a RESTART, so the old descriptor goes with it -- its name describes a
			// conversation that no longer exists. The restarted flow re-stamps on its next opted-in await.
			DAO_AutomationContinuation::update($continuation_token, array_merge(
				DAO_AutomationContinuation::resumeFieldsCleared(),
				[
					DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
					DAO_AutomationContinuation::UPDATED_AT => time(),
				]
			));
		}
		
		return [
			'token' => $continuation_token,
			'state_data' => $state_data,
		];
	}
	
	private function _handleAutomationAwaitForm(Model_AutomationContinuation $continuation) {
		$automator = DevblocksPlatform::services()->automation();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
		$reset = DevblocksPlatform::importGPC($_POST['reset'] ?? null, 'integer', 0);
		
		
		unset($_POST);
		
		$is_submit = array_key_exists('__submit', $prompts);
		
		if(!($automation = $continuation->getAutomation()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!in_array($automation->extension_id, $this->_interaction_extensions))
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$trigger_extension = $automation->getTriggerExtension(); /* @var $trigger_extension AutomationTrigger_InteractionWorker */
		
		if(!Context_Automation::isReadableByActor($automation, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Restart the session
		if($reset) {
			if($continuation->root_token) {
				// Exit this continuation
				DAO_AutomationContinuation::update($continuation->token,[
					DAO_AutomationContinuation::STATE => 'exit',
				]);
				$continuation = $continuation->getRoot();
				$automation = $continuation->getAutomation();
			}
			
			$this->_startInteractionAutomationSession($automation, $continuation->state_data['caller'], $continuation->state_data['interaction_params'], $continuation->token);
			$continuation = DAO_AutomationContinuation::getByToken($continuation->token);
		}
		
		$form_components = $trigger_extension::getFormComponentMeta();
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		$error = null;
		
		unset($initial_state['__return']['form']['elements']['say/__validation']);
		
		if($is_submit) {
			$last_prompts = ($initial_state['__return']['form']['elements'] ?? null) ?: [];
			$validation_errors = [];
			$initial_state = $this->_applyAwaitFormPromptValues($initial_state, $last_prompts, $prompts, $trigger_extension, $validation_errors, $automation);
			
			// Verify permissions
			$policy = $automation->getPolicy();
			
			if (!$policy->isCallerAllowed($continuation->state_data['caller']['name'] ?? null, DevblocksDictionaryDelegate::instance($continuation->state_data['dict']))) {
				$error = sprintf(
					"The automation policy does not allow this caller (%s).",
					$continuation->state_data['caller']['name']
				);
				
				$initial_state['__exit'] = 'await';
				
				$initial_state['__return']['form']['elements'] = [
					'say/__accessDenied' => [
						'content' => sprintf("# Access denied\n%s",
							$error
						),
						'style' => 'error',
					],
					'submit/' . uniqid() => [
						'continue' => false,
						'reset' => true,
					]
				];
				
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
				
			} else if ($validation_errors) {
				$initial_state['__return']['form']['elements'] = [
						'say/__validation' => [
							'content' => sprintf("# Correct the following errors to continue:\n%s",
								implode("\n", array_map(function ($error) {
									return '* ' . rtrim($error);
								}, $validation_errors))
							),
							'style' => 'error',
						]
					] + $last_prompts;
				
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
				
			} else {
				$initial_state = $this->_formatAwaitFormPromptValues($initial_state, $last_prompts, $prompts, $trigger_extension);
				
				if (false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
					$initial_state['__exit'] = 'await';
					$initial_state['__return'] = [
						'form' => [
							'elements' => [
								'say/__validation' => [
									'content' => $error,
									'style' => 'error',
								],
							],
						],
					];
					$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
				}
			}
		} else {
			$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
		}
		
		$this->_respondAutomationAwait($continuation, $automation_results);
	}
	
	/**
	 * Apply a submitted form's `prompts[<var>]` to a state dict exactly as the live interaction runtime does:
	 * per element, validate (+ optional per-field `validation:` template) then `setValue()` — which writes EVERY
	 * derived key (e.g. agentPrompt's `_mentions`/`__model`/`__llm`/`__images`). Runs `validateAll()` and merges
	 * the validation markers. On failure it fills `$validation_errors` and the caller decides how to surface it
	 * (re-render the form / prepend a `say/__validation`); `formatValue()` is a SEPARATE pass
	 * (`_formatAwaitFormPromptValues`) the caller runs only once validation (and policy) pass. Shared by the
	 * runtime (`_handleAutomationAwaitForm`) and the automation editor's form-state writeback so there's ONE path.
	 */
	private function _applyAwaitFormPromptValues(array $initial_state, array $last_prompts, array $prompts, Extension_AutomationTrigger $trigger_extension, array &$validation_errors, ?Model_Automation $automation = null) : array {
		$validation = DevblocksPlatform::services()->validation();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$form_components = $trigger_extension::getFormComponentMeta();
		$prompts_without_output = ['say'];

		$validation_errors = [];
		$validation_values = [];

		foreach ($last_prompts as $last_prompt_key => $last_prompt) {
			list($last_prompt_type, $prompt_set_key) = array_pad(explode('/', $last_prompt_key, 2), 2, null);

			// An element name becomes a top-level dict key the script reads back as `{{<name>}}` (plus derived
			// siblings like `<name>__model`), so it has to be a name Twig can lex — `{{a-b}}` is the subtraction
			// `a - b` and renders 0 with no error. Skip the element rather than aborting the turn: one bad name
			// is one broken variable, not a broken interaction.
			if (!$prompt_set_key || !_DevblocksKataService::isVariableName($prompt_set_key))
				continue;

			if (array_key_exists($last_prompt_type, $form_components)) {
				if(in_array($last_prompt_type, $prompts_without_output))
					continue;

				$prompt_value = $prompts[$prompt_set_key] ?? null;

				$is_required = is_array($last_prompt) && array_key_exists('required', $last_prompt) && $last_prompt['required'];

				$is_set = (is_string($prompt_value) && strlen($prompt_value))
					|| (is_array($prompt_value) && count($prompt_value));

				if(!($component = $trigger_extension::newFormComponent($last_prompt_type, $prompt_set_key, $prompt_value, $last_prompt)))
					continue;

				if ($is_required || $is_set) {
					$component->validate($validation);

					$validation_values[$prompt_set_key] = $prompt_value;

					// Run custom validation if it exists
					if(is_array($last_prompt) && array_key_exists('validation', $last_prompt)) {
						$validation_set_key = $prompt_set_key . '__custom';
						$validation_dict = DevblocksDictionaryDelegate::instance($initial_state);
						$component->setValue($prompt_set_key, $prompt_value, $validation_dict);

						// The validation template must be a string
						if(is_string($last_prompt['validation'])) {
							$validation_error = trim($tpl_builder->build($last_prompt['validation'], $validation_dict));

							if($validation_error) {
								$validation_values[$validation_set_key] = $prompt_value;

								$validation
									->addField($validation_set_key, $last_prompt['label'] ?? $prompt_set_key)
									->error()
									->setError($validation_error)
								;
							}

						} else {
							$validation_values[$validation_set_key] = false;

							$error_message = sprintf("`%s:validation:` must be a string.", $last_prompt_key);

							$validation
								->addField($validation_set_key, $last_prompt['label'] ?? $prompt_set_key)
								->error()
								->setError($error_message)
							;

							if($automation)
								$automation->logError($error_message, '');
						}
					}
				}

				$initial_state = $component->setValue($prompt_set_key, $prompt_value, $initial_state);
			}
		}

		$error = null;
		if ($validation_values) {
			if (false === $validation->validateAll($validation_values, $error))
				$validation_errors[] = $error;

			$initial_state = array_merge($initial_state, $validation_values);
		}

		return $initial_state;
	}

	// The runtime's post-validation format pass: normalize each element's primary key via `formatValue()` — the
	// one place agentPrompt actually mints/primes its session and links pasted attachments. Run ONLY after
	// validation (and, in the runtime, policy) pass. Shared by the runtime + the form-state writeback.
	private function _formatAwaitFormPromptValues(array $initial_state, array $last_prompts, array $prompts, Extension_AutomationTrigger $trigger_extension) : array {
		$form_components = $trigger_extension::getFormComponentMeta();
		$prompts_without_output = ['say'];

		foreach ($last_prompts as $last_prompt_key => $last_prompt) {
			list($last_prompt_type, $prompt_set_key) = array_pad(explode('/', $last_prompt_key, 2), 2, null);

			// No output binding (e.g. a bare `submit:`) → don't write a "" key. A name the script couldn't read
			// back as `{{<name>}}` is skipped for the same reason (see _applyAwaitFormPromptValues).
			if(!$prompt_set_key || !_DevblocksKataService::isVariableName($prompt_set_key))
				continue;

			$prompt_value = $prompts[$prompt_set_key] ?? null;

			if (array_key_exists($last_prompt_type, $form_components)) {
				if(in_array($last_prompt_type, $prompts_without_output))
					continue;

				if(!($component = $trigger_extension::newFormComponent($last_prompt_type, $prompt_set_key, $prompt_value, $last_prompt)))
					continue;
				$initial_state[$prompt_set_key] = $component->formatValue();
			}
		}

		return $initial_state;
	}

	private function _handleAutomationAwait(Model_AutomationContinuation $continuation) {
		$initial_state = $continuation->state_data['dict'] ?? [];
		$return = $initial_state['__return'] ?? [];
		
		if(array_key_exists('__return', $initial_state)) {
			if(array_key_exists('form', $return)) {
				$this->_handleAutomationAwaitForm($continuation);
			} else if(array_key_exists('interaction', $return)) {
				$this->_handleAutomationAwaitInteraction($continuation);
			} else if(array_key_exists('duration', $return)) {
				$this->_handleAutomationAwaitDuration($continuation);
			} else if(array_key_exists('draft', $return)) {
				$this->_handleAutomationAwaitDraft($continuation);
			} else if(array_key_exists('record', $return)) {
				$this->_handleAutomationAwaitRecord($continuation);
			} else if(array_key_exists('queue', $return)) {
				$this->_handleAutomationAwaitQueue($continuation);
			}
		}
	}
	
	// Normalize `__error` (a bare string from a timeout, or a `{message: ...}` map from an uncaught error) into
	// a display string. Returns '' when there's nothing to show.
	private static function _errorMessage($error) : string {
		if(is_string($error))
			return trim($error);

		if(is_array($error))
			return trim(strval($error['message'] ?? ''));

		return '';
	}

	private function _respondAutomationAwait(Model_AutomationContinuation $continuation, DevblocksDictionaryDelegate $automation_results) {
		// Only re-enter a non-form await responder while GENUINELY awaiting. A terminal exit (error/return/exit)
		// can still carry a stale `__return.<type>` — e.g. a queued turn that FAILED during the advance leaves
		// `__exit=error` beside a live `__return.queue`. Routing on that key would re-poll forever, because the
		// queue/duration/draft/record responders (unlike the form one) never check `__exit`: the gated queue poll
		// would re-render its marker and the browser would loop, unrecoverable, with 'stop' unable to break it.
		// On any terminal exit, fall through to the form responder, which renders the end/error panel and clears
		// the await. This enforces the engine-gate invariant (automation.php `_resumeAwaitGate`, which unsets
		// `__return` on a gate error) at the response layer, covering every await type and the hard-error path.
		if('await' === $automation_results->get('__exit')) {
			if($automation_results->getKeyPath('__return.interaction')) {
				$this->_respondAutomationAwaitInteraction($automation_results, $continuation);
				return;
			} else if($automation_results->getKeyPath('__return.duration')) {
				$this->_respondAutomationAwaitDuration($automation_results, $continuation);
				return;
			} else if($automation_results->getKeyPath('__return.draft')) {
				$this->_respondAutomationAwaitDraft($automation_results, $continuation);
				return;
			} else if($automation_results->getKeyPath('__return.record')) {
				$this->_respondAutomationAwaitRecord($automation_results, $continuation);
				return;
			} else if($automation_results->getKeyPath('__return.queue')) {
				$this->_respondAutomationAwaitQueue($automation_results, $continuation);
				return;
			}
		}

		// A plain form await, OR any terminal exit — the form responder renders the form / the end+error panel.
		$this->_respondAutomationAwaitForm($automation_results, $continuation);
	}
	
	private function _handleAutomationAwaitDuration(Model_AutomationContinuation $continuation) {
		$active_worker = CerberusApplication::getActiveWorker();
		$automator = DevblocksPlatform::services()->automation();
		
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? [], 'array', []);
		
		unset($_POST);
		
		if(!($automation = $continuation->getAutomation()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!in_array($automation->extension_id, $this->_interaction_extensions))
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!Context_Automation::isReadableByActor($automation, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		
		$error = null;
		
		if(array_key_exists('duration', $prompts)) {
			if (false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
				$initial_state['__exit'] = 'error';
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
			}
			
			$exit_code = $automation_results->get('__exit');
			
			$continuation->state_data['dict'] = $automation_results->getDictionary();
			
			// Save session scope
			DAO_AutomationContinuation::update($continuation->token, [
				DAO_AutomationContinuation::STATE => $exit_code,
				DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
				DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
				DAO_AutomationContinuation::UPDATED_AT => time(),
			]);
			
			$this->_respondAutomationAwait($continuation, $automation_results);
			
		} else {
			$automation_results = new DevblocksDictionaryDelegate($initial_state);
			$this->_respondAutomationAwaitDuration($automation_results, $continuation);
		}
	}

	/**
	 * `await:queue:` — the GATED POLL half of the two-loop. Its sidecar moved out to
	 * `POST /queue/nextAgentTurn` (`api/uri/queue.php`) so drains can be routed to their own FPM pool.
	 *
	 * READ-ONLY while pending. The marker render carries no status (the client keeps the last transcript + its
	 * dots spinner), so a poll that finds the turn still cooking has nothing to persist — it re-renders the marker
	 * and touches NOTHING. Only when the gate CLEARS (or errors) do we `executeScript` to advance and persist.
	 * That keeps every ~poll_ms poll a single read (no row churn), and — importantly — means an out-of-band flag
	 * written onto the continuation (e.g. an interrupt request) can't be clobbered by a pending poll's writeback.
	 * When we do advance, the engine's intrinsic gate re-checks and finalizes (advance / `__exit=error`).
	 */
	private function _handleAutomationAwaitQueue(Model_AutomationContinuation $continuation) {
		$active_worker = CerberusApplication::getActiveWorker();
		$automator = DevblocksPlatform::services()->automation();

		unset($_POST);

		if(!($automation = $continuation->getAutomation()))
			DevblocksPlatform::dieWithHttpError(null, 404);

		if(!in_array($automation->extension_id, $this->_interaction_extensions))
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!Context_Automation::isReadableByActor($automation, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		$initial_state = $continuation->state_data['dict'] ?? [];

		$queue_state = $initial_state['__return']['queue'] ?? [];
		$uuids = is_array($queue_state['messages'] ?? null) ? $queue_state['messages'] : [];

		// PENDING → pure read: re-render the marker, no write. (CLEAR/ERROR fall through to advance.)
		if('pending' === DevblocksPlatform::services()->queue()->awaitGate($uuids)) {
			$this->_renderAwaitQueueMarker($queue_state, $continuation->token);
			return;
		}

		$error = null;

		if(false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
			// A hard node error (uncaught exception) makes executeScript return false, but it mutated only a COPY —
			// our `$initial_state` still carries the `__return.queue` await. Clear it so the terminal error can't
			// re-enter the poll (mirrors the engine's own gate-error path, automation.php `_resumeAwaitGate` →
			// unset __return). Without this, `state_await` stays `queue` and the poll loops on the error forever.
			unset($initial_state['__return']);
			$initial_state['__exit'] = 'error';
			$initial_state['__error'] = $error;
			$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
		}

		$exit_code = $automation_results->get('__exit');
		$continuation->state_data['dict'] = $automation_results->getDictionary();

		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE => $exit_code,
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
			DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
			DAO_AutomationContinuation::UPDATED_AT => time(),
		]);

		$this->_respondAutomationAwait($continuation, $automation_results);
	}

	/**
	 * `interruptAgent` — raise a user Stop for a running `llm.agent` turn. Its natural home CAN'T be `invokePrompt`
	 * (that requires the agentPrompt to be the CURRENT await; during a run the continuation is in `await:queue:` /
	 * on_tool, so the prompt isn't in `__return.form`). Instead we set a short-lived shared-cache flag keyed by the
	 * agent SESSION; the running node consumes it at its next tree-safe boundary (after a complete tool tuple,
	 * before the next turn) and yields control back to the interaction. Cache — not the continuation dict — so a
	 * gate poll's writeback can't clobber it and there's no read-modify-write race.
	 */
	private function _profileAction_interruptAgent() : void {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string', '');
		$session_id = DevblocksPlatform::importGPC($_POST['session_id'] ?? null, 'string', '');

		if(!($continuation = DAO_AutomationContinuation::getByToken($continuation_token))) {
			echo json_encode(['error' => 'Unknown interaction.']);
			return;
		}

		if(!($automation = $continuation->getAutomation()) || !Context_Automation::isReadableByActor($automation, $active_worker)) {
			echo json_encode(['error' => 'Access denied.']);
			return;
		}

		// The session must exist and belong to this worker — you can only stop your own agent.
		if('' === $session_id
			|| !($session = DAO_LlmAgentSession::get($session_id))
			|| 'worker' !== $session->user_type
			|| intval($session->user_id) !== intval($active_worker->id ?? 0)) {
			echo json_encode(['error' => 'No agent session.']);
			return;
		}

		DevblocksPlatform::services()->cache()->save(true, \Cerb\AutomationBuilder\Node\LlmAgentNode::interruptCacheKey($session_id), [], 600);

		echo json_encode(['ok' => true]);
	}

	/**
	 * `echoAgentTurn` — put the message the worker just sent into the transcript immediately, before any
	 * worker has picked the turn up.
	 *
	 * Its own action for the same reason `pollAgentTurn` is: `invokePrompt` requires the prompt key to
	 * still be in the CURRENT await's `__return.form.elements`, and an async turn parks on `await:queue:`
	 * whose `__return` carries no form at all. This used to ride `invokePrompt` and got away with it only
	 * by racing the interaction POST -- it fired before the queue await was persisted. A slow model parks
	 * almost immediately, so the race is lost as a matter of course and the echo 404s.
	 *
	 * Display options ride the request because they lived on that unreachable form element. They are
	 * cosmetic, and the session is authorized independently below, so nothing trusted comes from them.
	 */
	private function _profileAction_echoAgentTurn() : void {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string', '');
		$session_id = DevblocksPlatform::importGPC($_POST['session_id'] ?? null, 'string', '');

		// An unknown continuation answers EMPTY, not 404. This request races the interaction POST that
		// persists it -- that is the whole point of an optimistic echo -- so a token that is not there yet
		// is a timing artifact, not a failure. The client drops an empty body, and the transcript poll
		// renders the turn a moment later regardless. A 404 here would raise an error banner for the most
		// ordinary case there is: the first message of a conversation.
		if(!($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
			return;

		if(!($automation = $continuation->getAutomation()) || !Context_Automation::isReadableByActor($automation, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		// An EXISTING session must be the caller's own -- but unlike pollAgentTurn, an absent one is not an
		// error here. On the first message of a conversation the id is freshly minted and nothing has been
		// persisted under it yet, which is precisely the case the optimistic echo exists for. The poll never
		// sees it because a poll only starts once a turn is already running.
		$session = ('' !== $session_id) ? DAO_LlmAgentSession::get($session_id) : null;

		if($session && ('worker' !== $session->user_type || intval($session->user_id) !== intval($active_worker->id ?? 0)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		$data = ['session_id' => $session_id];

		foreach(['view', 'layout', 'thinking', 'tools', 'expand'] as $key) {
			if(($value = DevblocksPlatform::importGPC($_POST[$key] ?? null, 'string', '')))
				$data[$key] = $value;
		}

		$data['tokens'] = DevblocksPlatform::importGPC($_POST['tokens'] ?? null, 'bit', 0);

		// No `fingerprint`: an echo always has something new to draw, so there is nothing to short-circuit
		// against. That is the one way this differs from pollAgentTurn.
		$await = new \Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\LlmTranscriptAwait('', '', $data);

		// Only hand over a row we actually loaded. setSession(null) would CACHE the absence, and the
		// interaction POST this request races may have created it in the meantime.
		if($session)
			$await->setSession($session);

		$await->invoke('', 'echoTurn', $continuation);
	}

	/**
	 * `pollAgentTurn` — hand back the newest transcript turn while a streamed answer is being written, so the
	 * client can watch a long turn fill in instead of staring at a frozen transcript.
	 *
	 * Its home CAN'T be `invokePrompt`, for exactly the reason `interruptAgent` above can't be either — and
	 * this is the whole reason the first attempt failed. `invokePrompt` requires the prompt key to exist in the
	 * CURRENT await's `__return.form.elements`, but a turn only streams while the continuation is parked on
	 * `await:queue:`, whose `__return` holds `{queue: …}` and NO form at all. So every poll 404'd, hit the
	 * failure cutoff, and the elapsed clock froze a couple of seconds in. (`echoTurn` gets away with
	 * `invokePrompt` only because it fires CONCURRENTLY with the interaction POST, before the queue await has
	 * been persisted — it is not a precedent for anything that polls.)
	 *
	 * Display options ride the request because they lived on that same unreachable form element. They're
	 * cosmetic, and the session is authorized independently below, so nothing is trusted here that matters.
	 */
	private function _profileAction_pollAgentTurn() : void {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string', '');
		$session_id = DevblocksPlatform::importGPC($_POST['session_id'] ?? null, 'string', '');

		if(!($continuation = DAO_AutomationContinuation::getByToken($continuation_token))) {
			echo json_encode(['error' => 'Unknown interaction.']);
			return;
		}

		if(!($automation = $continuation->getAutomation()) || !Context_Automation::isReadableByActor($automation, $active_worker)) {
			echo json_encode(['error' => 'Access denied.']);
			return;
		}

		// Same ownership test as interruptAgent: you can only watch your own agent's turn.
		if('' === $session_id
			|| !($session = DAO_LlmAgentSession::get($session_id))
			|| 'worker' !== $session->user_type
			|| intval($session->user_id) !== intval($active_worker->id ?? 0)) {
			echo json_encode(['error' => 'No agent session.']);
			return;
		}

		$data = ['session_id' => $session_id];

		foreach(['view', 'layout', 'thinking', 'tools', 'expand'] as $key) {
			if(($value = DevblocksPlatform::importGPC($_POST[$key] ?? null, 'string', '')))
				$data[$key] = $value;
		}

		$data['tokens'] = DevblocksPlatform::importGPC($_POST['tokens'] ?? null, 'bit', 0);

		// What the client already has on screen. When it still matches, the poll answers from the session
		// head alone instead of walking the whole active path to render markup byte-for-byte identical to
		// what the client would then discard.
		$data['fingerprint'] = DevblocksPlatform::importGPC($_POST['fingerprint'] ?? null, 'string', '');

		$await = new \Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\LlmTranscriptAwait('', '', $data);

		// Reuse the row the ownership check above already loaded, rather than SELECTing it a second time.
		$await->setSession($session);

		$await->invoke('', 'pollTurn', $continuation);
	}

	private function _handleAutomationAwaitDraft(Model_AutomationContinuation $continuation) {
		$active_worker = CerberusApplication::getActiveWorker();
		$automator = DevblocksPlatform::services()->automation();

		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? [], 'array', []);
		
		unset($_POST);
		
		if(!($automation = $continuation->getAutomation()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!in_array($automation->extension_id, $this->_interaction_extensions))
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!Context_Automation::isReadableByActor($automation, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		$draft_state = $initial_state['__return']['draft'] ?? [];
		
		$error = null;
		
		if(array_key_exists('draft', $prompts)) {
			$draft_status = $prompts['draft'];
			$draft_token = $draft_state['token'] ?? null;
			
			if(null != ($output_placeholder = $continuation->state_data['dict']['__return']['draft']['output'] ?? null)) {
				$draft_results = [
					'status' => $draft_status,
					'token' => $draft_token,
				];
				
				switch($draft_status) {
					case 'compose.sent':
					case 'reply.sent':
						if(false != ($message = DAO_Message::getByToken($draft_token))) {
							$draft_results['record'] = DevblocksDictionaryDelegate::getDictionaryFromModel($message, CerberusContexts::CONTEXT_MESSAGE, ['ticket_','customfields']);
						}
						break;
					
					case 'compose.draft':
					case 'reply.draft':
						if(false != ($draft = DAO_MailQueue::getByToken($draft_token))) {
							$draft_results['record'] = DevblocksDictionaryDelegate::getDictionaryFromModel($draft, CerberusContexts::CONTEXT_DRAFT, ['customfields']);
						}
						break;
					
					case 'compose.discard':
					case 'reply.discard':
						break;
				}
				
				$initial_state[$output_placeholder] = $draft_results;
			}
			
			if (false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
				$initial_state['__exit'] = 'error';
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
			}
			
			$exit_code = $automation_results->get('__exit');
			
			$continuation->state_data['dict'] = $automation_results->getDictionary();
			
			// Save session scope
			DAO_AutomationContinuation::update($continuation->token, [
				DAO_AutomationContinuation::STATE => $exit_code,
				DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
				DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
				DAO_AutomationContinuation::UPDATED_AT => time(),
			]);
			
			$this->_respondAutomationAwait($continuation, $automation_results);
			
		} else {
			$automation_results = new DevblocksDictionaryDelegate($initial_state);
			$this->_respondAutomationAwaitDraft($automation_results, $continuation);
		}
	}
	
	private function _handleAutomationAwaitRecord(Model_AutomationContinuation $continuation) {
		$active_worker = CerberusApplication::getActiveWorker();
		$automator = DevblocksPlatform::services()->automation();
		
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? [], 'array', []);
		
		unset($_POST);
		
		if(!($automation = $continuation->getAutomation()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!in_array($automation->extension_id, $this->_interaction_extensions))
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!Context_Automation::isReadableByActor($automation, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		$record_state = $initial_state['__return']['record'] ?? [];
		
		$error = null;
		
		if(array_key_exists('_started', $record_state) && array_key_exists('record_type', $prompts)) {
			if(null != ($output_placeholder = $continuation->state_data['dict']['__return']['record']['output'] ?? null)) {
				$record_results = [
					'event' => $prompts['event'] ?? null,
					'record' => DevblocksDictionaryDelegate::instance([
						'_context' => $prompts['record_type'] ?? null,
						'id' => intval($prompts['record_id'] ?? 0),
					]),
				];
				
				$initial_state[$output_placeholder] = $record_results;
			}
			
			if (false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
				$initial_state['__exit'] = 'error';
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
			}
			
			$exit_code = $automation_results->get('__exit');
			
			$continuation->state_data['dict'] = $automation_results->getDictionary();
			
			// Save session scope
			DAO_AutomationContinuation::update($continuation->token, [
				DAO_AutomationContinuation::STATE => $exit_code,
				DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
				DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
				DAO_AutomationContinuation::UPDATED_AT => time(),
			]);
			
			$this->_respondAutomationAwait($continuation, $automation_results);
			
		} else {
			$automation_results = new DevblocksDictionaryDelegate($initial_state);
			$this->_respondAutomationAwaitRecord($automation_results, $continuation);
		}
	}
	
	private function _respondAutomationAwaitRecord(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		$record_state = $automation_results->getKeyPath('__return.record', []);
		
		if(!($record_uri = DevblocksPlatform::services()->ui()->parseURI($record_state['uri'] ?? null)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!array_key_exists('context', $record_uri) || !$record_uri['context'])
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($context_ext = Extension_DevblocksContext::getByAlias($record_uri['context'], true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();
		$continuation->state_data['dict']['__return']['record']['_started'] = true;
		
		// Save session scope
		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
			DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
			DAO_AutomationContinuation::UPDATED_AT => time(),
			DAO_AutomationContinuation::STATE_AWAIT => DAO_AutomationContinuation::stateAwaitFor($automation_results->getKeyPath('__return', [])),
		]);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context_ext', $context_ext);
		$tpl->assign('record_id', $record_uri['context_id'] ?? 0);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/_await_record.tpl');
	}
	
	private function _respondAutomationAwaitDuration(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		$duration_state = $automation_results->getKeyPath('__return.duration', []);
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();
		$continuation->state_data['dict']['__return']['duration']['started'] = time();
		
		// Save session scope
		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
			DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
			DAO_AutomationContinuation::UPDATED_AT => time(),
			DAO_AutomationContinuation::STATE_AWAIT => DAO_AutomationContinuation::stateAwaitFor($automation_results->getKeyPath('__return', [])),
		]);
		
		$message = $duration_state['message'] ?? 'Waiting...';
		
		$until = $duration_state['until'] ?? '5 seconds';
		$wait_ms = max((@strtotime($until) ?: strtotime('+5 seconds')) - time(), 0) * 1000;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('wait_message', $message);
		$tpl->assign('wait_ms', $wait_ms);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/_await_duration.tpl');
	}

	// Render the `await:queue:` poll: a spinner + a client timer that re-submits after `poll_ms`. Each poll
	// re-enters _handleAutomationAwaitQueue, which advances the queue and re-checks the gate. Unlike duration
	// (a client-owned timer that resumes once), this loops until the server sees the messages finish.
	private function _respondAutomationAwaitQueue(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		// First emission of a NEW queue await (the node just enqueued a turn) → persist the state, then render.
		// The subsequent gate polls are read-only (see _handleAutomationAwaitQueue) and render via the same marker.
		$continuation->state_data['dict'] = $automation_results->getDictionary();

		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
			DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
			DAO_AutomationContinuation::UPDATED_AT => time(),
			DAO_AutomationContinuation::STATE_AWAIT => DAO_AutomationContinuation::stateAwaitFor($automation_results->getKeyPath('__return', [])),
		]);

		$this->_renderAwaitQueueMarker($automation_results->getKeyPath('__return.queue', []), $continuation->token);
	}

	// Render the invisible `data-cerb-await-queue` marker (no "waiting" screen — the panel keeps the last
	// transcript + its dots spinner and polls in the background). Write-free, so the read-only gate poll re-emits
	// with it too. `workers` = parallel sidecars (default 1, capped so one interaction can't monopolize the pool).
	private function _renderAwaitQueueMarker(array $queue_state, string $continuation_token) : void {
		$poll_ms = max(500, intval($queue_state['poll_ms'] ?? 2000));
		$workers = DevblocksPlatform::intClamp($queue_state['workers'] ?? 1, 1, 4);

		// The gate re-emits this marker EVERY cycle and the client re-reads it every cycle, so the pacing
		// belongs here rather than in the browser. A turn that has been running a while has almost
		// certainly outlived its worker sidecar (a long turn's sidecar is cut by the gateway at ~30s and
		// finishes off-request), and until it lands there is nothing for a faster poll to discover.
		//
		// Capped low ON PURPOSE: once the sidecar is gone this timer is the ONLY thing that notices the
		// turn finished, so the ceiling is a direct floor on how long the reader waits afterwards. In the
		// common case a live sidecar re-checks the gate the instant it completes, so the ramp costs
		// nothing at all.
		if(($started_at = intval($queue_state['started_at'] ?? 0)) > 0) {
			$elapsed = max(0, time() - $started_at);
			// The shift is clamped because it is UNBOUNDED in $elapsed, and a resumed continuation supplies a
			// large one by construction (`started_at` is when the turn began, not when this panel opened).
			// Past the platform word size `1 << n` stops growing and starts lying: 63 gives PHP_INT_MIN and
			// 64+ gives 0, so the cap silently inverted into the FASTEST poll the client allows -- 500ms at
			// ~16 minutes elapsed, 2s beyond that -- on exactly the long waits it exists to slow down.
			$poll_ms = min(self::AWAIT_QUEUE_POLL_MS_MAX, $poll_ms * (1 << min(16, intdiv($elapsed, 15))));
		}

		// Does this client need to run a worker sidecar at all? While the turn is IN_FLIGHT somewhere, a
		// sidecar dequeues nothing and returns `{0,0}` in milliseconds — one wasted round trip per gate
		// cycle for the whole turn. Only a message a worker could CLAIM RIGHT NOW needs one, which is
		// narrower than AVAILABLE: a turn waiting out a retry backoff (a 429 requeued with the provider's
		// own Retry-After) is AVAILABLE with `available_at` in the future, and dequeue() skips it.
		$needs_worker = 1;

		// Seconds until the soonest requeued message can be claimed, and why it's waiting — 0/'' when nothing
		// is in a retry wait.
		$retry_in = 0;
		$retry_notice = '';

		$claimable = false;

		if(is_array($queue_state['messages'] ?? null) && $queue_state['messages']) {
			$states = DAO_QueueMessage::getPollStateByUuids($queue_state['messages']);
			$now = time();

			foreach($states as $uuid => $state) {
				if(QueueMessageStatus::AVAILABLE->value !== $state['status_id'])
					continue;

				if($state['available_at'] <= $now) {
					$claimable = true;
					continue;
				}

				// Deferred, and only a consumer's own requeue does that (this queue never retries on its own),
				// so there is something worth saying. Report the SOONEST wait: with several messages the first
				// one due is when anything can happen next.
				$wait = $state['available_at'] - $now;

				if(0 === $retry_in || $wait < $retry_in) {
					$retry_in = $wait;
					$retry_notice = DevblocksPlatform::services()->queue()->getRetryNotice(strval($uuid));
				}
			}

			// Absent statuses mean we can't tell — spawn, because failing to drain is worse than one
			// wasted request.
			$needs_worker = ($claimable || count($states) < count($queue_state['messages'])) ? 1 : 0;
		}

		// Nothing CAN happen until the wait expires, so sleep through it instead of re-running the whole script
		// every couple of seconds to be told the same thing. This is the one case the ramp's low ceiling doesn't
		// apply to — that cap exists because the timer is otherwise the only thing that notices a turn landed,
		// and during a known wait no turn can land. Bounded anyway so one bad `available_at` can't park the poll,
		// and the +250ms keeps us from waking a tick early and burning a cycle.
		if($retry_in > 0)
			$poll_ms = min(15000, ($retry_in * 1000) + 250);

		// Is the POOL why we're still waiting? Only worth asking when our own message is claimable
		// and nothing has taken it — while the turn is IN_FLIGHT we aren't waiting on a slot, and
		// during a retry backoff the notice below already explains itself. That keeps the extra
		// read off every ordinary gate cycle.
		$slots_used = 0;
		$slots_total = 0;
		$waiting = 0;

		if($claimable && 0 === $retry_in) {
			$usage = DevblocksPlatform::services()->queue()->getConcurrencyUsage();

			// Reported whenever we ask, not only when saturated. The CLIENT decides what's worth showing
			// (see panel.tpl), and having the real numbers in the DOM on every throttled cycle is what
			// makes a broken occupancy read visible instead of looking like an idle pool.
			$slots_used = $usage['used'];
			$slots_total = $usage['total'];

			if(($llm_queue = DAO_Queue::getByName('cerb.llm.agent.requests')))
				$waiting = DAO_QueueMessage::countAvailable($llm_queue->id);
		}

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('poll_ms', $poll_ms);
		$tpl->assign('workers', $workers);
		$tpl->assign('needs_worker', $needs_worker);
		$tpl->assign('continuation_token', $continuation_token);

		// ALWAYS assigned, same reason as `notice` below.
		$tpl->assign('slots_used', $slots_used);
		$tpl->assign('slots_total', $slots_total);
		$tpl->assign('waiting', $waiting);

		// One sentence for the reader: what happened, and when it resumes. The reason is best-effort (a
		// consumer may not have left one, or its slot may have aged out); the timing is always ours.
		// ALWAYS assigned — the template service is a singleton, so a conditional assign would leave the
		// previous render's notice standing on the render after the wait clears.
		$tpl->assign('notice', ($retry_in > 0)
			? trim(sprintf('%s Retrying in %s.',
				$retry_notice ?: 'The request could not be sent.',
				DevblocksPlatform::strSecsToString($retry_in, 1)
			))
			: ''
		);

		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/_await_queue.tpl');
	}
	
	private function _respondAutomationAwaitDraft(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		$draft_state = $automation_results->getKeyPath('__return.draft', []);
		
		if(!($draft_uri = DevblocksPlatform::services()->ui()->parseURI($draft_state['uri'] ?? null)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!array_key_exists('context', $draft_uri) || !array_key_exists('context_id', $draft_uri))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(CerberusContexts::CONTEXT_DRAFT != $draft_uri['context'])
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($draft = DAO_MailQueue::get($draft_uri['context_id'])))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();
		$continuation->state_data['dict']['__return']['draft']['token'] = $draft->token;
		
		// Save session scope
		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
			DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
			DAO_AutomationContinuation::UPDATED_AT => time(),
			DAO_AutomationContinuation::STATE_AWAIT => DAO_AutomationContinuation::stateAwaitFor($automation_results->getKeyPath('__return', [])),
		]);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('draft', $draft);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/_await_draft.tpl');
	}
	
	private function _respondAutomationAwaitForm(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		$exit_code = $automation_results->get('__exit');
		
		$form_title = $automation_results->getKeyPath('__return.form.title', null);
		
		if($form_title) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('form_title', $form_title);
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/_set_title.tpl');
		}

		// `state_await` is the await SUB-STATE (which kind of await this is parked on), derived from the current
		// `__return`. Decided here at RENDER time and stored on the continuation — never written into automation
		// state. It answers READINESS; whether this can be reopened at all is `resume_scope`.
		$state_await = DAO_AutomationContinuation::stateAwaitFor($automation_results->getKeyPath('__return', []));

		// The marker that drives the pause/end close menu: a durable interaction parked somewhere a UI can
		// re-enter. Both halves matter — a scope with no re-entrable await is just as unresumable as no scope.
		$is_resumable = 'await' == $automation_results->get('__exit')
			&& '' !== strval($continuation->resume_scope ?? '')
			&& in_array($state_await, DAO_AutomationContinuation::getResumableAwaitTypes(), true);

		if($is_resumable)
			echo '<input type="hidden" name="__cerb_interaction_resumable" value="1">';
		
		$elements = $automation_results->getKeyPath('__return.form.elements', []);

		// Synthesize a submit button on await
		if('await' == $exit_code) {
			if(!array_key_exists('submit', $elements)) {
				$submits = array_filter(array_keys($elements), function($element_key) {
					return ($element_key == 'submit' || DevblocksPlatform::strStartsWith($element_key, 'submit/'));
				});
				
				if(!$submits) {
					$elements['submit/' . uniqid()] = [
						'continue' => true,
						'reset' => true,
					];
				}
			}
			
			// Wait up to a day
			$continuation->expires_at = time() + 86400;
			
			// Synthesize an end action on other states
		} else {
			// We just finished a delegate interaction
			if(null != ($parent_continuation = $continuation->getParent())) {
				$continuation->state_data['__exit'] = $exit_code;
				
				// Save delegate state
				DAO_AutomationContinuation::update($continuation->token, [
					DAO_AutomationContinuation::STATE => $exit_code,
					DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
					DAO_AutomationContinuation::UPDATED_AT => time(),
				]);
				
				if(null != ($output_placeholder = $parent_continuation->state_data['dict']['__return']['interaction']['output'] ?? null)) {
					$parent_continuation->state_data['dict'][$output_placeholder] = $automation_results->get('__return', []);
				}
				
				// Save parent state
				DAO_AutomationContinuation::update($parent_continuation->token, [
					DAO_AutomationContinuation::STATE_DATA => json_encode($parent_continuation->state_data),
					DAO_AutomationContinuation::UPDATED_AT => time(),
				]);
				
				$this->_handleAutomationAwaitInteraction($parent_continuation);
				return;
				
			} else { // Not a delegate
				// A hard error exit (an execution timeout, an uncaught node error) carries its reason on `__error`
				// but no `__return`, so the end below would render blank — the interaction just stops with zero
				// indication of why. Surface the reason as an error `say` first. (`__error` is a bare string from
				// the timeout path, or a `{message}` map from the uncaught-error path.)
				if('error' == $exit_code && ($error_message = self::_errorMessage($automation_results->get('__error')))) {
					$elements['say/__error'] = [
						'content' => sprintf("**The automation stopped with an error:**\n\n%s", $error_message),
						'style' => 'error',
					];
				}

				$elements['end/' . uniqid()] = $automation_results->get('__return', []);
			}
		}
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();

		$trigger_extension = $continuation->getAutomation()?->getTriggerExtension();
		$this->_renderFormElements($elements, $automation_results, $continuation, $trigger_extension);

		// Save session scope. The resume descriptor is refreshed here and nowhere else: this is the only await
		// responder that sees a form, and the values are sticky, so every other await leaves them alone.
		DAO_AutomationContinuation::update($continuation->token, array_merge(
			[
				DAO_AutomationContinuation::STATE => $exit_code,
				DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
				DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
				DAO_AutomationContinuation::UPDATED_AT => time(),
				DAO_AutomationContinuation::STATE_AWAIT => $state_await,
			],
			DAO_AutomationContinuation::resumeFieldsFromAwait($automation_results, $continuation)
		));
	}

	/**
	 * A trigger supports the visual form builder / form-state preview if it exposes BOTH the component registry
	 * (getFormComponentMeta) and the inspector descriptors (getFormComponentSchema). Covers the worker family
	 * (interaction.worker/internal/explore) and interaction.website; mail.* and others don't qualify.
	 */
	private function _supportsFormComponents($trigger_extension) : bool {
		return $trigger_extension instanceof Extension_AutomationTrigger
			&& method_exists($trigger_extension, 'getFormComponentMeta')
			&& method_exists($trigger_extension, 'getFormComponentSchema');
	}

	/**
	 * Render an `await:form:` element set to HTML. Shared by the live interaction runtime
	 * (`_respondAutomationAwaitForm`), the design-time form-builder preview, and the simulator's read-only
	 * form preview. `$is_simulated` sets the `is_automation_simulated` template flag so each `await/<type>.tpl`
	 * skips its live scripting (submit AJAX, autofocus, streaming, paging) — an inert render. `$wrap_for_builder`
	 * is the SEPARATE builder concern: wrap each element in a selectable `.cerb-fb-element` handle and KEEP
	 * `hidden` elements (dimmed) so they stay editable. A read-only preview (simulator/graph) is simulated but
	 * NOT builder-wrapped, so it skips hidden elements like the runtime and emits clean component markup.
	 */
	private function _renderFormElements(array $elements, DevblocksDictionaryDelegate $results, Model_AutomationContinuation $continuation, ?Extension_AutomationTrigger $trigger_extension, bool $is_simulated=false, ?int $only_index=null, bool $wrap_for_builder=false, bool $is_form_fill=false) {
		if(!$trigger_extension || !method_exists($trigger_extension, 'getFormComponentMeta'))
			return;

		$tpl = DevblocksPlatform::services()->template();
		// Website Awaits render through the sandboxed Smarty (a distinct singleton), so the flags must be assigned
		// on BOTH instances or the sandbox-rendered templates (`{$is_automation_simulated}`) won't see them.
		$tpl_sandbox = DevblocksPlatform::services()->templateSandbox();
		$form_components = $trigger_extension::getFormComponentMeta();

		// The simulator form-fill renders inert (no live streaming/paging AJAX) like the builder preview, but its
		// submit must FUNCTION (fire cerb-form-builder-submit so the popup can collect values + write them back).
		$tpl->assign('is_automation_simulated', $is_simulated);
		$tpl->assign('is_automation_form_fill', $is_form_fill);
		$tpl_sandbox->assign('is_automation_simulated', $is_simulated);
		$tpl_sandbox->assign('is_automation_form_fill', $is_form_fill);

		// A form re-rendered because of a validation / access-denied error carries a `say/__validation` (or
		// `say/__accessDenied`). Auto-submitting elements (`submit: is_automatic`, `uiCommand`) must NOT re-fire in
		// that state — they'd re-trigger the same error forever. This render-time flag lets those templates fall
		// back to a manual Continue / render inert.
		$is_form_error = array_key_exists('say/__validation', $elements) || array_key_exists('say/__accessDenied', $elements);
		$tpl->assign('is_automation_form_error', $is_form_error);
		$tpl_sandbox->assign('is_automation_form_error', $is_form_error);

		$index = -1;

		foreach($elements as $element_key => $element_data) {
			$index++;

			// Builder single-element refresh: mock the whole form (siblings feed the continuation) but emit only this one.
			if($wrap_for_builder && $only_index !== null && $index !== $only_index)
				continue;

			list($action_key_type, $var) = array_pad(explode('/', preg_replace('/@.*$/', '', strval($element_key)), 2), 2, null);

			$is_hidden = is_array($element_data) && array_key_exists('hidden', $element_data) && $element_data['hidden'];

			// Only the builder keeps hidden elements (dimmed, still selectable); the runtime + read-only previews skip them.
			if($is_hidden && !$wrap_for_builder)
				continue;

			if(!array_key_exists($action_key_type, $form_components))
				continue;

			$value = $results->get($var, null);
			if(!($component = $trigger_extension::newFormComponent($action_key_type, $var, $value, $element_data)))
				continue;

			if($wrap_for_builder) {
				// Wrap each rendered element so the builder can select / reorder / delete it by model index.
				ob_start();
				$component->render($continuation);
				$html = ob_get_clean();

				printf(
					'<div class="cerb-fb-element%s" data-cerb-fb-index="%d">%s</div>',
					$is_hidden ? ' cerb-fb-element--hidden' : '',
					$index,
					$html
				);
			} else {
				$component->render($continuation);
			}
		}

		$tpl->clearAssign('is_automation_simulated');
		$tpl->clearAssign('is_automation_form_fill');
		$tpl->clearAssign('is_automation_form_error');
		$tpl_sandbox->clearAssign('is_automation_simulated');
		$tpl_sandbox->clearAssign('is_automation_form_fill');
		$tpl_sandbox->clearAssign('is_automation_form_error');
	}

	// Serve the form-builder popup: a palette of the trigger's form components + a live simulated preview.
	private function _profileAction_showFormBuilderPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? '', 'string', '');

		if(!($trigger_extension = Extension_AutomationTrigger::get($extension_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		// Worker family + interaction.website (both expose getFormComponentMeta + getFormComponentSchema). The
		// website Awaits render inert in simulated mode (mock portal schema + mock session), so no live portal.
		if(!$this->_supportsFormComponents($trigger_extension))
			DevblocksPlatform::dieWithHttpError(null, 405);

		$form_components = $trigger_extension::getFormComponentMeta();

		// The client only needs each component's icon (its render class stays server-side).
		$components_client = [];
		foreach($form_components as $type => $meta)
			$components_client[$type] = ['icon' => $meta['icon'] ?? 'form'];

		$schema = method_exists($trigger_extension, 'getFormComponentSchema')
			? $trigger_extension::getFormComponentSchema()
			: [];

		// Record types (aliased contexts) for the chooser inspector's type picker + query scoping.
		$record_types = [];
		foreach(Extension_DevblocksContext::getAll(false) as $context_id => $mft) {
			$alias = $mft->params['alias'] ?? '';
			if(!$alias)
				continue;
			$record_types[] = [
				'context' => $context_id,
				'alias' => $alias,
				'label' => $mft->name,
				'icon' => $mft->params['icon'] ?? 'collection',
			];
		}
		usort($record_types, fn($a, $b) => strcasecmp($a['label'], $b['label']));

		$map_resources = method_exists($trigger_extension, 'getMapResources') ? $trigger_extension::getMapResources() : [];
		$agent_models = method_exists($trigger_extension, 'getAgentModelChoices') ? $trigger_extension::getAgentModelChoices() : [];

		// id → uri for connected accounts (agentPrompt auth): the KATA cerb-uri prefers the readable uri over the id.
		$account_uris = [];
		foreach(DAO_ConnectedAccount::getAll() as $account) {
			if($account->uri)
				$account_uris[$account->id] = $account->uri;
		}

		// Preview presentation (faux window chrome + any stylesheets to load so components render at fidelity).
		$preview = $trigger_extension::getFormPreviewPresentation();

		$tpl->assign('extension_id', $extension_id);
		$tpl->assign('form_components_json', json_encode($components_client));
		$tpl->assign('form_schema_json', json_encode($schema));
		$tpl->assign('record_types_json', json_encode($record_types));
		$tpl->assign('map_resources_json', json_encode($map_resources));
		$tpl->assign('agent_models_json', json_encode($agent_models));
		$tpl->assign('account_uris_json', json_encode((object) $account_uris));
		$tpl->assign('preview_chrome', $preview['chrome'] ?? 'dialog');
		$tpl->assign('preview_stylesheets', $preview['stylesheets'] ?? []);
		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/popup_form_builder.tpl');
	}

	// Open the visual Sheet Builder (CerbUI.SheetBuilder) for an `await:form` sheet element, seeded from the
	// element's current `data`/`schema` KATA (parsed server-side — the client has no KATA parser). Scoped to the
	// trigger's allowed sheet column types (worker = all; website = the restricted public-safe set).
	private function _profileAction_showSheetBuilderPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$kata = DevblocksPlatform::services()->kata();
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? '', 'string', '');

		if(!($trigger_extension = Extension_AutomationTrigger::get($extension_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		if(!$this->_supportsFormComponents($trigger_extension))
			DevblocksPlatform::dieWithHttpError(null, 405);

		// Seed: the element's current data + schema KATA (the inner blocks under `data:` / `schema:`).
		$seed_data_kata = DevblocksPlatform::importGPC($_POST['data'] ?? '', 'string', '');
		$seed_schema_kata = DevblocksPlatform::importGPC($_POST['schema'] ?? '', 'string', '');

		$error = null;
		$initial_schema = null;
		$initial_data = null;

		if($seed_schema_kata !== '' && false !== ($tree = $kata->parse($seed_schema_kata, $error)))
			$initial_schema = $kata->formatTree($tree);
		if($seed_data_kata !== '' && false !== ($tree = $kata->parse($seed_data_kata, $error)))
			$initial_data = $kata->formatTree($tree);

		$allowed_column_types = $trigger_extension::getSheetColumnTypes();
		$cfg = \Cerb\Sheets\SheetBuilder::getClientConfig($allowed_column_types);

		$tpl->assign('extension_id', $extension_id);
		$tpl->assign('column_schema_json', json_encode($cfg['columnSchema']));
		$tpl->assign('layout_schema_json', json_encode($cfg['layoutSchema']));
		$tpl->assign('datasource_schema_json', json_encode($cfg['dataSourceSchema']));
		$tpl->assign('allowed_column_types_json', json_encode($cfg['allowedColumnTypes']));
		$tpl->assign('allowed_datasource_types_json', json_encode($cfg['allowedDataSourceTypes']));
		$tpl->assign('record_types_json', json_encode($cfg['recordTypes']));
		$tpl->assign('sheet_data_automations_json', json_encode($cfg['sheetDataAutomations']));
		// The seed for the KATA→model import (null when starting from a blank element).
		$tpl->assign('initial_json', json_encode(['schema' => $initial_schema, 'data' => $initial_data]));

		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/popup_sheet_builder.tpl');
	}

	// Render an `await:form:` KATA fragment to inert preview HTML for the form builder (no session, mock data).
	private function _profileAction_formBuilderPreview() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? '', 'string', '');
		$form_kata = DevblocksPlatform::importGPC($_POST['kata'] ?? '', 'string', '');

		// Optional: render only the element at this model index (single-component live refresh; preserves scroll).
		$only_index = null;
		if(array_key_exists('only_index', $_POST) && $_POST['only_index'] !== '')
			$only_index = DevblocksPlatform::importGPC($_POST['only_index'], 'integer', 0);

		if(!($trigger_extension = Extension_AutomationTrigger::get($extension_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		if(!$this->_supportsFormComponents($trigger_extension))
			DevblocksPlatform::dieWithHttpError(null, 405);

		$elements = [];

		// The builder posts an `await:form:` block (or a bare `elements:` map). Parse to a tree, then formatTree()
		// to apply `@bool`/`@int`/… annotations and interpolate `{{placeholders}}` against a mock dict — this is
		// the same clean, typed element config the runtime hands each component.
		if($form_kata) {
			$kata = DevblocksPlatform::services()->kata();
			$error = null;

			if(false !== ($tree = $kata->parse($form_kata, $error)) && is_array($tree)) {
				$preview_dict = DevblocksDictionaryDelegate::instance([]);
				$formatted = $kata->formatTree($tree, $preview_dict, $error);

				if(is_array($formatted)) {
					$elements = $formatted['await']['form']['elements']
						?? $formatted['form']['elements']
						?? $formatted['elements']
						?? [];
				}
			}
		}

		$mock = $this->_formBuilderMockContinuation($elements);
		$results = DevblocksDictionaryDelegate::instance($mock->state_data['dict'] ?? []);

		$this->_renderFormElements($elements, $results, $mock, $trigger_extension, true, $only_index, true);
	}

	// A throwaway in-memory continuation with mock values so dynamic components render at design time.
	private function _formBuilderMockContinuation(array $elements) : Model_AutomationContinuation {
		$continuation = new Model_AutomationContinuation();
		$continuation->token = '';

		$dict = [];

		// Seed each element's output variable with a type-appropriate mock value so data-driven
		// components (sheet, chart, transcript) have something to render at design time.
		foreach($elements as $element_key => $element_data) {
			list($type, $var) = array_pad(explode('/', preg_replace('/@.*$/', '', strval($element_key)), 2), 2, null);

			if(!$var)
				continue;

			if(is_array($element_data) && array_key_exists('default', $element_data) && !is_array($element_data['default'])) {
				$dict[$var] = $element_data['default'];
				continue;
			}

			$dict[$var] = $this->_formBuilderMockValue($type);
		}

		// Some components (sheet) re-read their config from `__return.form.elements` on the continuation, exactly
		// as the runtime stores it — mirror that so they render.
		$dict['__return'] = ['form' => ['elements' => $elements]];

		$continuation->state_data = ['dict' => $dict];

		return $continuation;
	}

	private function _formBuilderMockValue(string $type) {
		return match($type) {
			'sheet' => [
				['name' => 'Sample row 1'],
				['name' => 'Sample row 2'],
				['name' => 'Sample row 3'],
			],
			default => null,
		};
	}

	// Render the `await:form:` from the current simulator state as the ACTUAL interaction popup (not a preview):
	// the outer popup IS the dialog, titled with the form's title, and Continue functions. Unlike the design-time
	// builder (which mocks values), this drives off the LIVE, fully-resolved `__return.form` the runtime produced
	// when it hit the await — placeholders interpolated, `@bool`/`@int` applied, current field values seeded. It's
	// rendered inert (no live streaming/paging AJAX) but with a FUNCTIONAL submit; the popup collects the filled
	// `prompts[*]` and posts them back to submitFormStatePreview, which merges them into the Input editor.
	private function _profileAction_showFormStatePreviewPopup() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$tpl = DevblocksPlatform::services()->template();

		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? '', 'string', '');
		$state_yaml = DevblocksPlatform::importGPC($_POST['state'] ?? '', 'string', '');

		$trigger_extension = $extension_id ? Extension_AutomationTrigger::get($extension_id) : null;

		$state = DevblocksPlatform::services()->string()->yamlParse($state_yaml, 0);
		if(!is_array($state))
			$state = [];

		$results = DevblocksDictionaryDelegate::instance($state);

		$exit_code = $results->get('__exit');
		$form_title = $results->getKeyPath('__return.form.title', '');
		$elements = $results->getKeyPath('__return.form.elements', []);

		// A form requires an await:form continuation and the worker-family component templates.
		$has_form = ($exit_code === 'await')
			&& is_array($elements) && $elements
			&& $this->_supportsFormComponents($trigger_extension);

		$elements_html = '';

		if($has_form) {
			// Mirror the runtime (`_respondAutomationAwaitForm`): synthesize a default submit on await when the form
			// declares none, so a form authored without an explicit submit still gets its Continue/Reset.
			$has_submit = (bool) array_filter(array_keys($elements), fn($k) =>
				$k === 'submit' || DevblocksPlatform::strStartsWith($k, 'submit/'));

			if(!$has_submit)
				$elements['submit/preview'] = ['continue' => true, 'reset' => true];

			// The state already carries the resolved form + current field values, so drive the render off the real
			// state dict (no mock): `$results->get($var)` returns each element's current value.
			$continuation = new Model_AutomationContinuation();
			$continuation->token = '';
			$continuation->state_data = ['dict' => $state];

			ob_start();
			$this->_renderFormElements($elements, $results, $continuation, $trigger_extension, true, null, false, true);
			$elements_html = ob_get_clean();
		}

		// Preview presentation (portal chrome + stylesheets for the website trigger; Cerb dialog default).
		$preview = ($trigger_extension && method_exists($trigger_extension, 'getFormPreviewPresentation'))
			? $trigger_extension::getFormPreviewPresentation()
			: ['chrome' => 'dialog', 'stylesheets' => []];

		$tpl->assign('has_form', $has_form);
		$tpl->assign('form_title', $form_title ?: 'Form');
		$tpl->assign('elements_html', $elements_html);
		$tpl->assign('preview_chrome', $preview['chrome'] ?? 'dialog');
		$tpl->assign('preview_stylesheets', $preview['stylesheets'] ?? []);
		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/popup_form_state_preview.tpl');
	}

	// Merge a submitted simulator form back into the Run Input state through the SAME path the live runtime uses
	// (`_applyAwaitFormPromptValues` + `_formatAwaitFormPromptValues`): per-component validate + setValue (persists
	// every derived key, e.g. agentPrompt's _mentions/__model/__llm/__images), then formatValue (mints/primes the
	// agentPrompt session, links attachments) only when validation passes. No executeScript — the user Runs to
	// advance. On a validation error the values are kept and an error `say/__validation` is prepended (as the
	// runtime re-renders), so re-opening the form shows it.
	private function _profileAction_submitFormStatePreview() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'text/plain; charset=utf-8');

		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? '', 'string', '');
		$state_yaml = DevblocksPlatform::importGPC($_POST['state'] ?? '', 'string', '');
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? [], 'array', []);

		$trigger_extension = $extension_id ? Extension_AutomationTrigger::get($extension_id) : null;

		$initial_state = DevblocksPlatform::services()->string()->yamlParse($state_yaml, 0);
		if(!is_array($initial_state))
			$initial_state = [];

		if($this->_supportsFormComponents($trigger_extension)) {
			$last_prompts = ($initial_state['__return']['form']['elements'] ?? null) ?: [];

			// Drop any prior validation notice before re-processing.
			unset($initial_state['__return']['form']['elements']['say/__validation']);

			$validation_errors = [];
			$initial_state = $this->_applyAwaitFormPromptValues($initial_state, $last_prompts, $prompts, $trigger_extension, $validation_errors);

			if($validation_errors) {
				$initial_state['__return']['form']['elements'] = [
					'say/__validation' => [
						'content' => sprintf("# Correct the following errors to continue:\n%s",
							implode("\n", array_map(fn($e) => '* ' . rtrim($e), $validation_errors))),
						'style' => 'error',
					],
				] + $last_prompts;
			} else {
				$initial_state = $this->_formatAwaitFormPromptValues($initial_state, $last_prompts, $prompts, $trigger_extension);
			}
		}

		echo DevblocksPlatform::services()->string()->yamlEmit($initial_state, false);
	}

	// ── Simulator input priming ──────────────────────────────────────────────────────────────────────────

	// Derive a normalized list of "primeable" inputs for an automation: its trigger scope (from the enriched
	// getInputsMeta — TYPED entries only; untyped caller_/client_ are skipped) + the automation's own declared
	// `#inputs:` block. Shared by the prime popup (renders a form) and the writeback (maps values → initial
	// state). Each entry: { origin:'scope'|'input', key, component:'chooser'|'text'|'textarea',
	//   emit:'record'|'records'|'bool'|'scalar'|'array', label, record_type?, context?, text_type?, multiple?, default? }
	private function _getSimulationPrimeInputs(Model_Automation $automation, $trigger_extension) : array {
		$out = [];

		// 1) Event scope — the trigger declares which inputs to prompt for (default = typed getInputsMeta entries,
		//    but a trigger may substitute a friendlier set, e.g. one "example message" for many pre-parser keys).
		if($trigger_extension && method_exists($trigger_extension, 'getSimulationInputs')) {
			foreach($trigger_extension->getSimulationInputs() as $d) {
				$d['origin'] = 'scope';
				$out[] = $d;
			}
		}

		// 2) Automation-declared `#inputs:` — handled abstractly here (uniform across all triggers), fully typed
		//    (text w/ sub-type, record, records, array). Merged after the trigger's scope inputs.
		$kata = DevblocksPlatform::services()->kata();
		$error = null;
		$automation_kata = $kata->parse($automation->script ?? '', $error, true);

		if(is_array($automation_kata) && array_key_exists('inputs', $automation_kata)) {
			$declared = $kata->formatTree($automation_kata['inputs'], DevblocksDictionaryDelegate::instance([]), $error);

			if(is_array($declared)) {
				foreach($declared as $k => $input) {
					if(!is_array($input))
						continue;

					list($input_type, $input_key) = array_pad(explode('/', $k, 2), 2, '');
					if($input_key === '')
						continue;

					$label = strval($input['description'] ?? DevblocksPlatform::strTitleCase(str_replace('_', ' ', $input_key)));
					$required = !empty($input['required']);

					if($input_type === 'record' && ($input['record_type'] ?? '') === '') {
						// Polymorphic single record input (no fixed type) → a ContextChooser across all contexts.
						$out[] = ['origin' => 'input', 'emit' => 'record_context', 'key' => $input_key, 'component' => 'context_chooser',
							'label' => $label, 'required' => $required,
							'contexts_json' => json_encode(Extension_AutomationTrigger::getSimulationChooserContexts())];

					} elseif($input_type === 'record' || $input_type === 'records') {
						$record_type = $input['record_type'] ?? '';
						$ext = $record_type ? Extension_DevblocksContext::getByAlias($record_type, true) : null;
						$d = ['origin' => 'input', 'emit' => $input_type, 'key' => $input_key, 'component' => 'chooser',
							'label' => $label, 'required' => $required, 'record_type' => $record_type, 'context' => ($ext ? $ext->id : '')];
						if($input_type === 'records')
							$d['multiple'] = true;
						if(array_key_exists('default', $input) && !is_array($input['default'])) {
							$d['default'] = $input['default'];
						} elseif($input_type === 'record' && $ext && ($dao = $ext->getDaoClass()) && method_exists($dao, 'random')) {
							$d['default'] = $dao::random();
						}
						$out[] = $d;

					} elseif($input_type === 'array') {
						$default = $input['default'] ?? '';
						$out[] = ['origin' => 'input', 'emit' => 'array', 'key' => $input_key, 'component' => 'textarea',
							'label' => $label, 'required' => $required, 'default' => (is_array($default) ? implode("\n", $default) : strval($default))];

					} else {   // text
						$out[] = ['origin' => 'input', 'emit' => 'scalar', 'key' => $input_key, 'component' => 'text',
							'text_type' => strval($input['type'] ?? 'freeform'), 'label' => $label, 'required' => $required,
							'default' => strval($input['default'] ?? '')];
					}
				}
			}
		}

		return $out;
	}

	// Serve the "Simulate initial state" popup — a generated form (trigger scope + automation inputs), pre-filled
	// with sane defaults (worker = you, sample record ids). The descriptors render directly as cerb-ui-form controls
	// in the template (the simulator owns this UI; no fidelity to a trigger's runtime input surface is needed).
	// Continue posts back to submitPrimeState, which emits the initial-state YAML for the Run Input editor.
	private function _profileAction_showPrimeStatePopup() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$tpl = DevblocksPlatform::services()->template();

		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? '', 'string', '');
		$automation_script = DevblocksPlatform::importGPC($_POST['automation_script'] ?? '', 'string', '');

		$trigger_extension = $extension_id ? Extension_AutomationTrigger::get($extension_id) : null;

		$automation = new Model_Automation();
		$automation->script = $automation_script;
		if($extension_id)
			$automation->extension_id = $extension_id;

		$prime_inputs = $this->_getSimulationPrimeInputs($automation, $trigger_extension);

		// Split the descriptors by origin so the popup can group them: trigger scope → "Event inputs" panel,
		// automation `#inputs:` → "Automation inputs" panel. The template renders each descriptor directly with
		// cerb-ui-form controls — the simulator owns this UI, so it needs no fidelity to how a trigger surfaces
		// its inputs at runtime (the trigger only tells us WHICH inputs exist and, later, how to map the answers).
		$scope_inputs = [];
		$automation_inputs = [];

		foreach($prime_inputs as $d) {
			// Pre-resolve chooser defaults to seed tiles (id → label/image); rendered inert as [data-context-id].
			if(($d['component'] ?? '') === 'chooser') {
				$d['selected'] = [];
				$record_type = $d['record_type'] ?? '';

				if($record_type && isset($d['default']) && $d['default'] !== '' && $d['default'] !== null) {
					$ids = is_array($d['default']) ? $d['default'] : [$d['default']];
					$models = CerberusContexts::getModels($record_type, $ids);
					$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $record_type);

					foreach($ids as $id) {
						if(array_key_exists($id, $dicts)) {
							$d['selected'][] = [
								'id' => $id,
								'label' => $dicts[$id]->get('_label'),
								'image' => $dicts[$id]->get('_image_url'),
							];
						}
					}
				}

			// A context chooser's default carries its own context (e.g. actor → the current worker); seed the tile
			// with the FULL context id so ContextChooser reads it back as `<context>:<id>`.
			} elseif(($d['component'] ?? '') === 'context_chooser') {
				$d['selected'] = [];
				$default_context = $d['default_context'] ?? '';

				if($default_context && !empty($d['default'])) {
					$id = intval($d['default']);
					$models = CerberusContexts::getModels($default_context, [$id]);
					$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $default_context);

					if(array_key_exists($id, $dicts) && ($ctx_ext = Extension_DevblocksContext::getByAlias($default_context, false))) {
						$d['selected'][] = [
							'context' => $ctx_ext->id,
							'id' => $id,
							'label' => $dicts[$id]->get('_label'),
							'image' => $dicts[$id]->get('_image_url'),
						];
					}
				}
			}

			if(($d['origin'] ?? '') === 'scope')
				$scope_inputs[] = $d;
			else
				$automation_inputs[] = $d;
		}

		$tpl->assign('scope_inputs', $scope_inputs);
		$tpl->assign('automation_inputs', $automation_inputs);
		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/popup_prime_state.tpl');
	}

	// Merge a submitted priming form into an initial simulator state and emit it as YAML for the Run Input editor.
	// The event scope is delegated to the trigger (`getSimulationState()` — default = 1:1, but a trigger may expand
	// one answer into many keys); the automation `#inputs:` are mapped abstractly here to `inputs.<name>` (raw id
	// for records — `_validateInputs` expands them at run time). The two fragments are merged.
	private function _profileAction_submitPrimeState() {
		$active_worker = CerberusApplication::getActiveWorker();

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$strings = DevblocksPlatform::services()->string();
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'text/plain; charset=utf-8');

		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? '', 'string', '');
		$automation_script = DevblocksPlatform::importGPC($_POST['automation_script'] ?? '', 'string', '');
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? [], 'array', []);

		$trigger_extension = $extension_id ? Extension_AutomationTrigger::get($extension_id) : null;

		$automation = new Model_Automation();
		$automation->script = $automation_script;
		if($extension_id)
			$automation->extension_id = $extension_id;

		$prime_inputs = $this->_getSimulationPrimeInputs($automation, $trigger_extension);

		// Automation `#inputs:` → `inputs.<name>`, mapped abstractly (uniform across triggers). Built first so
		// `inputs:` leads the emitted dict when present, ahead of the event scope keys.
		$inputs = [];
		foreach($prime_inputs as $d) {
			if(($d['origin'] ?? '') !== 'input')
				continue;

			$key = $d['key'];
			$val = $prompts[$key] ?? null;

			switch($d['emit']) {
				case 'record':
					if($val !== null && $val !== '')
						$inputs[$key] = intval($val);
					break;

				case 'record_context':   // polymorphic input value is "context:id" → emit the raw id like a record
					if($val !== null && $val !== '' && false !== ($sep = strrpos($val, ':')))
						$inputs[$key] = intval(substr($val, $sep + 1));
					break;

				case 'records':
					$inputs[$key] = is_array($val) ? array_values(array_map('intval', $val)) : [];
					break;

				case 'array':
					$inputs[$key] = (is_string($val) && strlen(trim($val)))
						? preg_split('/\r?\n/', trim($val))
						: [];
					break;

				default:   // scalar (automation inputs carry no boolean type)
					if($val !== null && $val !== '')
						$inputs[$key] = $val;
			}
		}

		// Event scope: the trigger turns its own answers into dict key/values (and may validate).
		$error = null;
		$scope = $trigger_extension ? $trigger_extension->getSimulationState($prompts, $error) : [];

		if($error) {
			echo '# ' . str_replace("\n", ' ', $error);
			return;
		}

		// `inputs:` first (when present), then the event scope keys.
		$state = ($inputs ? ['inputs' => $inputs] : []) + $scope;

		// Empty → emit nothing (avoid a bare `--- []`); the run proceeds with an empty state.
		echo $state ? $strings->yamlEmit($state, false) : '';
	}

	private function _handleAutomationAwaitInteraction(Model_AutomationContinuation $continuation) {
		$delegate_token = $continuation->state_data['dict']['__return']['interaction']['token'] ?? null;
		
		// If we don't have a delegate token yet, generate one and continue
		if(null == $delegate_token) {
			$automation_results = DevblocksDictionaryDelegate::instance($continuation->state_data['dict']);
			list($delegate_continuation, $delegate_results) = $this->_respondAutomationAwaitInteraction($automation_results, $continuation, true);
			
			if('await' != $delegate_continuation->state) {
				if($delegate_results->getKeyPath('__return.interaction')) {
					$this->_respondAutomationAwaitInteraction($delegate_results, $delegate_continuation);
				} else {
					$this->_respondAutomationAwaitForm($delegate_results, $delegate_continuation);
				}
				return;
			}
			
			$this->_handleAutomationAwait($delegate_continuation);
			return;
		}
		
		if(false == ($delegate_continuation = DAO_AutomationContinuation::getByToken($delegate_token)))
			DevblocksPlatform::dieWithHttpError("Null delegate continuation", 404);
		
		// Is the delegate completed?
		
		if('await' != $delegate_continuation->state) {
			$automator = DevblocksPlatform::services()->automation();
			
			if(!($automation = $continuation->getAutomation()))
				DevblocksPlatform::dieWithHttpError("Null delegate automation", 404);
			
			$error = null;
			$initial_state = $continuation->state_data['dict'];
			
			if(false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
				$initial_state['__exit'] = 'error';
				$initial_state['__error'] = [
					'message' => 'An unexpected error occurred.'
				];
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
			}
			
			if($automation_results->getKeyPath('__return.interaction')) {
				$this->_respondAutomationAwaitInteraction($automation_results, $continuation);
			} else {
				$this->_respondAutomationAwaitForm($automation_results, $continuation);
			}
			
			return;
		}
		
		$this->_handleAutomationAwait($delegate_continuation);
	}
	
	private function _respondAutomationAwaitInteraction(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation, $return=false) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		// Must have a URI
		if(!($interaction_uri = trim($automation_results->getKeyPath('__return.interaction.uri'))))
			DevblocksPlatform::dieWithHttpError("invalid return interaction uri", 404);
		
		$handler_name = uniqid();
		
		$handlers = [
			$handler_name => [
				'id' => 'automation/' . $handler_name,
				'type' => 'automation',
				'key' => $handler_name,
				'data' => [
					'uri' => $interaction_uri,
				]
			]
		];
		
		$initial_state = [
			'inputs' => $automation_results->getKeyPath('__return.interaction.inputs', []),
		];
		
		$initial_state = array_merge(
			$initial_state,
			$automation_results->getDictionary('caller_', false, 'caller_'),
			$automation_results->getDictionary('client_', false, 'client_'),
			$automation_results->getDictionary('worker_', false, 'worker_')
		);
		
		$delegate_results = $event_handler->handleOnce(
			$this->_interaction_extensions,
			$handlers,
			$initial_state,
			$error,
			null,
			$handler
		);
		
		if(!$delegate_results)
			DevblocksPlatform::dieWithHttpError("null delegate results", 404);
		
		// Copy the parent state (actor, caller, params)
		$state_data = $continuation->state_data ?? [];
		
		$state_data['trigger'] = $this->_interaction_extensions;
		$state_data['dict'] = $delegate_results->getDictionary();
		
		// Create a new continuation to track the delegate
		
		$delegate_continuation = new Model_AutomationContinuation();
		$delegate_continuation->parent_token = $continuation->token;
		$delegate_continuation->root_token = $continuation->root_token ?: $continuation->token;
		$delegate_continuation->updated_at = time();
		$delegate_continuation->expires_at = time() + 1200;
		$delegate_continuation->state = $delegate_results->getKeyPath('__exit');
		$delegate_continuation->state_data = $state_data;
		$delegate_continuation->uri = $handler->name;
		
		$delegate_continuation->token = DAO_AutomationContinuation::create([
			DAO_AutomationContinuation::PARENT_TOKEN => $delegate_continuation->parent_token,
			DAO_AutomationContinuation::ROOT_TOKEN => $delegate_continuation->root_token,
			DAO_AutomationContinuation::UPDATED_AT => $delegate_continuation->updated_at,
			DAO_AutomationContinuation::EXPIRES_AT => $delegate_continuation->expires_at,
			DAO_AutomationContinuation::STATE => $delegate_continuation->state,
			DAO_AutomationContinuation::STATE_DATA => json_encode($delegate_continuation->state_data),
			DAO_AutomationContinuation::URI => $delegate_continuation->uri,
			DAO_AutomationContinuation::EXTENSION_ID => $continuation->extension_id,
			DAO_AutomationContinuation::WORKER_ID => $continuation->worker_id,
		]);
		
		// Update the parent continuation with the delegate token
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();
		$continuation->state_data['dict']['__return']['interaction']['token'] = $delegate_continuation->token;
		
		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
		]);
		
		if($return) {
			return [
				$delegate_continuation,
				$delegate_results,
			];
			
		} else {
			$this->_respondAutomationAwait($delegate_continuation, $delegate_results);
		}
	}
	
	private function _profileAction_stepAutomationEditor() {
		$output_yaml = DevblocksPlatform::importGPC($_POST['output'] ?? null, 'string');
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'text/plain; charset=utf-8');
		
		if(!($output_yaml = DevblocksPlatform::services()->string()->yamlParse($output_yaml, 0)))
			return;
		
		$output_dict = DevblocksDictionaryDelegate::instance($output_yaml);
		
		$field_values = [];
		
		// If a form continuation
		if($output_dict->getKeyPath('__exit') == 'await' 
			&& ($form_elements = $output_dict->getKeyPath('__return.form.elements'))
			&& is_array($form_elements)
			) {
			
			foreach($form_elements as $k => $v) {
				list($k_type, $k_name) = array_pad(explode('/', $k), 2, null);
				
				if(!$k_name || in_array($k_type, ['map','say']))
					continue;
				
				// If a key doesn't exist for this prompt yet, add a default
				if(null === ($field_value = $output_dict->get($k_name))) {
					$default = '';
					
					if('sheet' == $k_type) {
						if(array_key_exists('schema', $v)) {
							$sheets = DevblocksPlatform::services()->sheet();
							
							$selection = current(array_filter(
								$sheets->getColumns($v['schema']),
								fn($c) => 'selection' == ($c['_type'] ?? null)
							));
							
							// Skip if no selection column
							if(!$selection)
								continue;
							
							// If multi-selection, default to an empty array
							if ('multiple' == ($selection['params']['mode'] ?? null))
								$default = [];
						}
						
					} else if('submit' == $k_type) {
						// If no custom buttons on the submit, skip
						if(!array_key_exists('buttons', $v))
							continue;
					}
					
					$field_values[$k_name] = $v['default'] ?? $default;
				} else {
					$field_values[$k_name] = $field_value;
					$output_dict->unset($k_name);
				}
			}
		}
		
		if($field_values) {
			echo "# Set form values\n";
			echo DevblocksPlatform::services()->string()->yamlEmit($field_values, false);
			echo "\n\n";
		}
		
		echo DevblocksPlatform::services()->string()->yamlEmit($output_dict->getDictionary(), false);
	}
	
	private function _profileAction_runAutomationEditor() {
		$automator = DevblocksPlatform::services()->automation();
		$kata = DevblocksPlatform::services()->kata();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$automation_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$is_simulator = DevblocksPlatform::importGPC($_POST['is_simulator'] ?? null, 'integer', 0);
		$automation_name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string');
		$automation_script = DevblocksPlatform::importGPC($_POST['automation_script'] ?? null, 'string');
		$automation_policy = DevblocksPlatform::importGPC($_POST['automation_policy_kata'] ?? null, 'string');
		$start_state = DevblocksPlatform::importGPC($_POST['start_state_yaml'] ?? null, 'string');
		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string');
		$skip_prime = DevblocksPlatform::importGPC($_POST['skip_prime'] ?? null, 'integer', 0);

		$error = null;
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		// Only admins
		if(!$active_worker->is_superuser) {
			echo json_encode([
				'exit' => 'error',
				'exit_state' => null,
				'dict' => DevblocksPlatform::services()->string()->yamlEmit([
					'__exit' => 'error',
					'error' => "Only administrators are allowed to use the automation editor.",
				], false),
			]);
			return;
		}
		
		if(!($automation = DAO_Automation::get($automation_id))) {
			$automation = new Model_Automation();
		}
		
		if($extension_id)
			$automation->extension_id = $extension_id;
		
		$automation->name = $automation_name;
		$automation->script = $automation_script;
		$automation->policy_kata = $automation_policy;
		
		if(false === ($initial_state = DevblocksPlatform::services()->string()->yamlParse($start_state, 0, $error))) {
			echo json_encode([
				'exit' => 'error',
				'exit_state' => null,
				'dict' => DevblocksPlatform::services()->string()->yamlEmit([
					'__exit' => 'error',
					'error' => $error,
				], false),
			]);
			return;
		}
		
		// Simulate with an EMPTY initial state → if the trigger scope or the automation's `#inputs:` declare anything
		// primeable, tell the client to prime it first (an empty state would just fail scope/policy gates). Nothing
		// to prime (e.g. a bare `start: return: output: {{1+2}}` function, or a blank no-trigger script) → run
		// immediately, no extra step. `skip_prime` = the client already ran the prime flow, so honor its result.
		if($is_simulator && !$skip_prime && empty($initial_state)) {
			$prime_trigger = $extension_id ? Extension_AutomationTrigger::get($extension_id) : null;

			if($this->_getSimulationPrimeInputs($automation, $prime_trigger)) {
				echo json_encode(['exit' => 'prime']);
				return;
			}
		}

		$initial_state['__simulate'] = $is_simulator;

		// Schema validation on script + policy before running
		if(false === $kata->validate($automation->script, CerberusApplication::kataSchemas()->automation(), $error)) {
			echo json_encode([
				'exit' => 'error',
				'exit_state' => null,
				// KATA parse/validation errors embed a 1-based `(line N)` in the script; surface it for a line callout.
				'error_line' => (preg_match('/\(line (\d+)\)/', $error, $matches) ? intval($matches[1]) : null),
				'dict' => DevblocksPlatform::services()->string()->yamlEmit([
					'__exit' => 'error',
					'error' => 'Automation: ' . $error,
				], false),
			]);
			return;
		}
		
		if(false === $kata->validate($automation->policy_kata, CerberusApplication::kataSchemas()->automationPolicy(), $error)) {
			echo json_encode([
				'exit' => 'error',
				'exit_state' => null,
				'dict' => DevblocksPlatform::services()->string()->yamlEmit([
					'__exit' => 'error',
					'error' => 'Automation policy: ' . $error,
				], false),
			]);
			return;
		}
		
		if(false === ($automation_result = $automator->executeScript($automation, $initial_state, $error))) {
			echo json_encode([
				'exit' => 'error',
				'exit_state' => null,
				// Runtime/scripting errors may also report a 1-based `(line N)` in the script.
				'error_line' => (preg_match('/\(line (\d+)\)/', $error, $matches) ? intval($matches[1]) : null),
				'dict' => DevblocksPlatform::services()->string()->yamlEmit([
					'__exit' => 'error',
					'error' => $error,
				], false),
			]);
			return;
		}
		
		$exit_code = $automation_result->get('__exit');
		$exit_state = $automation_result->getKeyPath('__state.next', null);
		
		$end_state = $this->_canonicalizeState($automation_result->getDictionary());

		$yaml_out = DevblocksPlatform::services()->string()->yamlEmit($end_state, false);
		
		echo json_encode([
			'exit' => $exit_code,
			'exit_state' => $exit_state,
			'dict' => $yaml_out,
		]);
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
	
	private function _profileAction_getExtensionConfig() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Must be an admin
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', null);
		
		if(!$extension_id)
			return;
		
		if(!($trigger_ext = Extension_AutomationTrigger::get($extension_id, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		/* @var $trigger_ext Extension_AutomationTrigger */
		
		$model = new Model_Automation();
		$model->extension_id = $extension_id;
		
		$trigger_ext->renderConfig($model);
	}
	
	private function _profileAction_getAutocompleteJson() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', null);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		if(!$extension_id) {
			echo '[]';
			return;
		}
		
		if(!($trigger_ext = Extension_AutomationTrigger::get($extension_id, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		/* @var $trigger_ext Extension_AutomationTrigger */
		
		echo $trigger_ext->getAutocompleteSuggestionsJson();
	}
	
	private function _profileAction_getInteractionsMenu() {
		$tpl = DevblocksPlatform::services()->template();

		$interactions_menu = Toolbar_GlobalMenu::getInteractionsMenu();

		$tpl->assign('interactions_menu', $interactions_menu);
		$tpl->display('devblocks:cerberusweb.core::automations/interactions/command_bar.tpl');
	}

	private function _profileAction_getBulkUpdateFieldset() : void {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker)
			DevblocksPlatform::dieWithHttpError(null, 403);

		$handler_name = DevblocksPlatform::importGPC($_POST['handler'] ?? null, 'string', '');
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string', '');
		$idx = DevblocksPlatform::importGPC($_POST['idx'] ?? null, 'integer', 0);

		if($handler_name === '' || $context === '')
			DevblocksPlatform::dieWithHttpError(null, 400);

		// Resolve the handler authoritatively from the event listeners — never trust a uri
		// from the post. Only handlers defined and enabled for this worker are returned.
		$handler = \Cerb\Records\BulkUpdate::getHandlersByName($context, $active_worker)[$handler_name] ?? null;

		if(!$handler)
			DevblocksPlatform::dieWithHttpError(null, 404);

		$automation = DAO_Automation::getByUri((string) ($handler['data']['uri'] ?? ''), [AutomationTrigger_RecordBulkUpdate::ID]);

		if(!$automation)
			DevblocksPlatform::dieWithHttpError(null, 404);

		// Inputs the handler binding fixes are forced overrides applied server-side at run
		// time; omit them from the worker form entirely (don't surface or default them).
		$override_keys = array_keys((array) ($handler['data']['inputs'] ?? []));

		$inputs = [];

		foreach($automation->getInputsMeta() as $input) {
			if(in_array($input['key'] ?? '', $override_keys, true))
				continue;

			// Normalize `allowed_values` to a [value => label] map so the template
			// can iterate without type-checking keys.
			if(!empty($input['allowed_values']) && is_array($input['allowed_values'])) {
				$normalized = [];
				foreach($input['allowed_values'] as $opt_key => $opt_label) {
					if(is_int($opt_key)) {
						$normalized[(string) $opt_label] = (string) $opt_label;
					} else {
						$normalized[(string) $opt_key] = (string) $opt_label;
					}
				}
				$input['allowed_values'] = $normalized;
			}

			$inputs[] = $input;
		}

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('idx', $idx);
		$tpl->assign('handler_key', $handler_name);
		$tpl->assign('automation', $automation);
		$tpl->assign('inputs', $inputs);
		$tpl->display('devblocks:cerberusweb.core::internal/bulkupdate/automation_fieldset.tpl');
	}
}
