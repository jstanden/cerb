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

class PageSection_ProfilesAutomation extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // automation 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_AUTOMATION;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'editorLog':
					return $this->_profileAction_editorLog();
				case 'editorLogRefresh':
					return $this->_profileAction_editorLogRefresh();
				case 'editorVisualize':
					return $this->_profileAction_editorVisualize();
				case 'getAutocompleteJson':
					return $this->_profileAction_getAutocompleteJson();
				case 'getExtensionConfig':
					return $this->_profileAction_getExtensionConfig();
				case 'invokePrompt':
					return $this->_profileAction_invokePrompt();
				case 'renderEditorToolbar':
					return $this->_profileAction_renderEditorToolbar();
				case 'runAutomationEditor':
					return $this->_profileAction_runAutomationEditor();
				case 'showExportPopup':
					return $this->_profileAction_showExportPopup();
				case 'stepAutomationEditor':
					return $this->_profileAction_stepAutomationEditor();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
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
		
		header('Content-Type: application/json; charset=utf-8');
		
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
		
		if(false == ($trigger_ext = Extension_AutomationTrigger::get($trigger, true)))
			return;
		
		/** @var $trigger_ext Extension_AutomationTrigger */
		
		$toolbar = $trigger_ext->getEditorToolbar();

		$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar, $toolbar_dict);
		
		DevblocksPlatform::services()->ui()->toolbar()->render($toolbar);
	}
	
	private function _profileAction_showExportPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$fields = DevblocksPlatform::importGPC($_POST['fields'] ?? null, 'array', []);
		
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
		$tpl->display('devblocks:cerberusweb.core::internal/automation/editor/popup_export.tpl');
	}
	
	private function _profileAction_stepAutomationEditor() {
		$output_yaml = DevblocksPlatform::importGPC($_POST['output'] ?? null, 'string');
		
		header('Content-Type: text/plain; charset=utf-8');
		
		if(false == ($output_yaml = DevblocksPlatform::services()->string()->yamlParse($output_yaml, 0)))
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
		
		header('Content-Type: application/json; charset=utf-8');
		
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
		
		if(false == ($trigger_ext = Extension_AutomationTrigger::get($extension_id, true)))
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
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(!$extension_id) {
			echo '[]';
			return;
		}
		
		if(false == ($trigger_ext = Extension_AutomationTrigger::get($extension_id, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		/* @var $trigger_ext Extension_AutomationTrigger */
		
		echo $trigger_ext->getAutocompleteSuggestionsJson();
	}
}
