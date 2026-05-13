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
	private array $_interaction_extensions = [
		AutomationTrigger_InteractionInternal::ID,
		AutomationTrigger_InteractionWorker::ID,
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
				case 'editorLog':
					return $this->_profileAction_editorLog();
				case 'editorLogRefresh':
					return $this->_profileAction_editorLogRefresh();
				case 'editorUsage':
					return $this->_profileAction_editorUsage();
				case 'editorVisualize':
					return $this->_profileAction_editorVisualize();
				case 'getAutocompleteJson':
					return $this->_profileAction_getAutocompleteJson();
				case 'getExtensionConfig':
					return $this->_profileAction_getExtensionConfig();
				case 'getInteractionsMenu':
					return $this->_profileAction_getInteractionsMenu();
				case 'invokePrompt':
					return $this->_profileAction_invokePrompt();
				case 'renderEditorToolbar':
					return $this->_profileAction_renderEditorToolbar();
				case 'runAutomationEditor':
					return $this->_profileAction_runAutomationEditor();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'sendMessage':
					return $this->_profileAction_sendMessage();
				case 'showExportPopup':
					return $this->_profileAction_showExportPopup();
				case 'startInteraction':
					return $this->_profileAction_startInteraction();
				case 'stepAutomationEditor':
					return $this->_profileAction_stepAutomationEditor();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
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
		$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
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
		
		foreach($results as $record_type => $record_ids) {
			$record_ext = Extension_DevblocksContext::getByAlias($record_type);
			
			foreach($record_ids as $record_id) {
				$data[] = DevblocksDictionaryDelegate::instance([
					'_context' => $record_ext->id,
					'_type' => $record_type,
					'_type_label' => $record_ext->name,
					'id' => $record_id
				]);
			}
		}
		
		// Sort dictionaries by _type_label
		DevblocksPlatform::sortObjects($data, '_type_label');
		
		$sheet_kata = <<< EOD
        layout:
          headings@bool: yes
          filtering@bool: no
          paging@bool: no
          style: columns
        columns:
          text/_type_label:
            label: Type
          card/id:
            label: Record
            params:
              bold@bool: yes
              text_size: 120%
        EOD;
		
		$sheet = $sheets->parse($sheet_kata);
		$layout = $sheets->getLayout($sheet);
		$columns = $sheets->getColumns($sheet);
		$rows = $sheets->getRows($sheet, $data);
		
		if(empty($rows)) {
			echo '(no usage found)';
			return;
		}
		
		$tpl->assign('layout', $layout);
		$tpl->assign('columns', $columns);
		$tpl->assign('rows', $rows);
		
		$tpl->display('devblocks:cerberusweb.core::ui/sheets/render_grid.tpl');
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
		
		$automation = new Model_Automation();
		$automation->script = $script;
		
		if(!($graph = $automation->getSyntaxGraph($error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}

		$tpl->assign('graph', $graph);
		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/tab_visualize.tpl');
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
		
		$form_components = AutomationTrigger_InteractionWorker::getFormComponentMeta();
		
		list($prompt_type, $prompt_name) = array_pad(explode('/', $prompt_key, 2), 2, null);
		
		if(!array_key_exists($prompt_type, $form_components))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$component = new $form_components[$prompt_type]($prompt_name, null, $form[$prompt_key]);
		
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
		
		$initial_state = [
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
		];
		
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
		
		$initial_state = [
			'caller_name' => '',
			'caller_params' => [],
			'client_ip' => DevblocksPlatform::getClientIp(),
			'client_browser_name' => $user_agent['browser'] ?? null,
			'client_browser_platform' => $user_agent['platform'] ?? null,
			'client_browser_version' => $user_agent['version'] ?? null,
			'inputs' => $interaction_params,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		];
		
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
				DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
				DAO_AutomationContinuation::EXPIRES_AT => time()+3600, // 1hr
				DAO_AutomationContinuation::UPDATED_AT => time(),
			]);
			
		} else {
			DAO_AutomationContinuation::update($continuation_token, [
				DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
				DAO_AutomationContinuation::UPDATED_AT => time(),
			]);
		}
		
		return [
			'token' => $continuation_token,
			'state_data' => $state_data,
		];
	}
	
	private function _handleAutomationAwaitForm(Model_AutomationContinuation $continuation) {
		$automator = DevblocksPlatform::services()->automation();
		$validation = DevblocksPlatform::services()->validation();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
		$reset = DevblocksPlatform::importGPC($_POST['reset'] ?? null, 'integer', 0);
		
		$prompts_without_output = ['say'];
		
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
			$validation_values = [];
			
			foreach ($last_prompts as $last_prompt_key => $last_prompt) {
				list($last_prompt_type, $prompt_set_key) = array_pad(explode('/', $last_prompt_key, 2), 2, null);
				
				if (!$prompt_set_key)
					continue;
				
				if (array_key_exists($last_prompt_type, $form_components)) {
					if(in_array($last_prompt_type, $prompts_without_output))
						continue;
					
					$prompt_value = $prompts[$prompt_set_key] ?? null;
					
					$is_required = array_key_exists('required', $last_prompt) && $last_prompt['required'];
					
					$is_set = (is_string($prompt_value) && strlen($prompt_value))
						|| (is_array($prompt_value) && count($prompt_value));
					
					$component = new $form_components[$last_prompt_type]($prompt_set_key, $prompt_value, $last_prompt);
					
					if ($is_required || $is_set) {
						$component->validate($validation);
						
						$validation_values[$prompt_set_key] = $prompt_value;
						
						// Run custom validation if it exists
						if(array_key_exists('validation', $last_prompt)) {
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
								
								$automation->logError(
									$error_message,
									''
								);
							}
						}
					}
					
					$initial_state = $component->setValue($prompt_set_key, $prompt_value, $initial_state);
				}
			}
			
			if ($validation_values) {
				if (false === $validation->validateAll($validation_values, $error))
					$validation_errors[] = $error;
				
				$initial_state = array_merge($initial_state, $validation_values);
			}
			
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
				// Format dictionary keys
				foreach ($last_prompts as $last_prompt_key => $last_prompt) {
					list($last_prompt_type, $prompt_set_key) = array_pad(explode('/', $last_prompt_key, 2), 2, null);
					$prompt_set_key = strval($prompt_set_key);
					$prompt_value = $prompts[$prompt_set_key] ?? null;
					
					if (array_key_exists($last_prompt_type, $form_components)) {
						if(in_array($last_prompt_type, $prompts_without_output))
							continue;
						
						$component = new $form_components[$last_prompt_type]($prompt_set_key, $prompt_value, $last_prompt);
						$initial_state[$prompt_set_key] = $component->formatValue();
					}
				}
				
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
			}
		}
	}
	
	private function _respondAutomationAwait(Model_AutomationContinuation $continuation, DevblocksDictionaryDelegate $automation_results) {
		if($automation_results->getKeyPath('__return.interaction')) {
			$this->_respondAutomationAwaitInteraction($automation_results, $continuation);
		} else if($automation_results->getKeyPath('__return.duration')) {
			$this->_respondAutomationAwaitDuration($automation_results, $continuation);
		} else if($automation_results->getKeyPath('__return.draft')) {
			$this->_respondAutomationAwaitDraft($automation_results, $continuation);
		} else if($automation_results->getKeyPath('__return.record')) {
			$this->_respondAutomationAwaitRecord($automation_results, $continuation);
		} else {
			$this->_respondAutomationAwaitForm($automation_results, $continuation);
		}
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
		]);
		
		$message = $duration_state['message'] ?? 'Waiting...';
		
		$until = $duration_state['until'] ?? '5 seconds';
		$wait_ms = max((@strtotime($until) ?: strtotime('+5 seconds')) - time(), 0) * 1000;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('wait_message', $message);
		$tpl->assign('wait_ms', $wait_ms);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/_await_duration.tpl');
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
		]);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('draft', $draft);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/_await_draft.tpl');
	}
	
	private function _respondAutomationAwaitForm(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		$form_components = AutomationTrigger_InteractionWorker::getFormComponentMeta();
		
		$exit_code = $automation_results->get('__exit');
		
		$form_title = $automation_results->getKeyPath('__return.form.title', null);
		
		if($form_title) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('form_title', $form_title);
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/_set_title.tpl');
		}
		
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
				$elements['end/' . uniqid()] = $automation_results->get('__return', []);
			}
		}
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();
		
		foreach($elements as $element_key => $element_data) {
			list($action_key_type, $var) = array_pad(explode('/', $element_key, 2), 2, null);
			
			if(is_array($element_data) && array_key_exists('hidden', $element_data) && $element_data['hidden'])
				continue;
			
			if(array_key_exists($action_key_type, $form_components)) {
				$value = $automation_results->get($var, null);
				
				if(!array_key_exists($action_key_type, $form_components))
					continue;
				
				$component = new $form_components[$action_key_type]($var, $value, $element_data);
				$component->render($continuation);
			}
		}
		
		// Save session scope
		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE => $exit_code,
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
			DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
			DAO_AutomationContinuation::UPDATED_AT => time(),
		]);
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
		
		$initial_state['__simulate'] = $is_simulator;
		
		// Schema validation on script + policy before running
		if(false === $kata->validate($automation->script, CerberusApplication::kataSchemas()->automation(), $error)) {
			echo json_encode([
				'exit' => 'error',
				'exit_state' => null,
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
				'dict' => DevblocksPlatform::services()->string()->yamlEmit([
					'__exit' => 'error',
					'error' => $error,
				], false),
			]);
			return;
		}
		
		$exit_code = $automation_result->get('__exit');
		$exit_state = $automation_result->getKeyPath('__state.next', null);
		
		$end_state = $automation_result->getDictionary();
		ksort($end_state);
		
		// Move the state info to the end
		$state = $end_state['__state'];
		unset($end_state['__state']);
		$end_state['__state'] = $state;
		unset($state);
		
		// Move expandable to the end
		if(array_key_exists('__expandable', $end_state)) {
			$expandable = $end_state['__expandable'];
			unset($end_state['__expandable']);
			$end_state['__expandable'] = $expandable;
			unset($expandable);
		}
		
		unset($end_state['__simulate']);
		
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
		$tpl->display('devblocks:cerberusweb.core::automations/interactions/menu.tpl');
	}
}
