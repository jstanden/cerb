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

class PageSection_ProfilesWorkflow extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // workflow 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_WORKFLOW;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'refreshSummary':
					return $this->_profileAction_refreshSummary();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showTemplateUpdatePopup':
					return $this->_profileAction_showTemplateUpdatePopup();
				case 'saveTemplateJson':
					return $this->_profileAction_saveTemplateJson();
				case 'saveConfigJson':
					return $this->_profileAction_saveConfigJson();
				case 'saveChangesJson':
					return $this->_profileAction_saveChangesJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_WORKFLOW)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!($model = DAO_Workflow::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Workflow::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(Context_Workflow::ID, $model->id, $model->name);
				
				DAO_Workflow::delete($id);
				
				echo json_encode([
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				]);
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				
				$error = null;
				
				if(empty($id)) { // New
					$fields = [
						DAO_Workflow::NAME => $name,
						DAO_Workflow::CREATED_AT => time(),
						DAO_Workflow::UPDATED_AT => time(),
					];
					
					if(!DAO_Workflow::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Workflow::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_Workflow::create($fields);
					DAO_Workflow::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKFLOW, $id);
					
				} else { // Edit
					$fields = [
						DAO_Workflow::NAME => $name,
						DAO_Workflow::UPDATED_AT => time(),
					];
					
					if(!DAO_Workflow::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Workflow::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_Workflow::update($id, $fields);
					DAO_Workflow::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WORKFLOW, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode([
					'status' => true,
					'context' => CerberusContexts::CONTEXT_WORKFLOW,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				]);
				return;
			}
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			]);
			return;
			
		} catch (Exception) {
			echo json_encode([
				'status' => false,
				'error' => 'An error occurred.',
			]);
			return;
			
		}
	}
	
	private function _profileAction_refreshSummary() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$error = null;
		
		try {
			if(!($active_worker = CerberusApplication::getActiveWorker()))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				DevblocksPlatform::dieWithHttpError(null, 405);
			
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!($workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$tpl->assign('model', $workflow);
			
			if(!$workflow->populateTemplateSummary($tpl, $error))
				throw new Exception_DevblocksAjaxValidationError($error);
			
			$tpl->display('devblocks:cerberusweb.core::records/types/workflow/peek_edit/summary.tpl');
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			DevblocksPlatform::dieWithHttpError(null, 500);
		}
	}

	private function _profileAction_showTemplateUpdatePopup() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		try {
			if(!($workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			// Autocomplete
			
			$autocomplete_suggestions = [
				'' => [
					'config:',
					'extensions:',
					'records:',
				],
				'config:' => [
					'chooser/key:',
					'text/key:',
				],
				'config:chooser:' => [
					'default:',
					'label:',
					'record_query:',
					'record_type:',
					'multiple@bool: no',
				],
				'config:text:' => [
					'default:',
					'label:',
				],
				'extensions:' => [
					'activity:',
					'permission:',
					'translation:',
				],
				'extensions:activity:' => [
					'id: example.activity.id',
					'label: Custom Record Event',
					'message: {{actor}} performed custom event on {{target}}',
				],
				'extensions:permission:' => [
					'id: example.permission.id',
					'label: Custom Permission',
				],
				'extensions:translation:' => [
					'id: example.translation.id',
					'langs:',
				],
				'extensions:translation:langs:' => [
					'en_US: This is a translated string',
					'en_US@raw: This is a translated string with {{placeholders}}',
				],
				'records:' => [],
			];
			
			$record_types = Extension_DevblocksContext::getAll(true, ['records']);
			
			$autocomplete_suggestions['config:chooser:record_type:'] = array_values(array_map(
				fn($record_type) => $record_type->manifest->params['alias'],
				$record_types
			));
			
			foreach($record_types as $record_type) {
				$autocomplete_suggestions['records:'][] = [
					'caption' => $record_type->manifest->params['alias'] . ':',
					'snippet' => $record_type->manifest->params['alias'] . '/${1:resourceName}:',
				];
				
				$key = 'records:' . $record_type->manifest->params['alias'] . ':';
				$autocomplete_suggestions[$key][] = 'fields:';
				$autocomplete_suggestions[$key][] = 'deletionPolicy:';
				$autocomplete_suggestions[$key . 'deletionPolicy:'] = ['delete', 'retain'];
				
				// [TODO] Is this cached for autocompletion already?

//					$dao_class = $record_type->getDaoClass();
//					$dao_fields = $dao_class::getFields();
				
				// [TODO] Required fields with higher score
				// [TODO] Field values autocompletion
				$fields_map = $record_type->getKeyToDaoFieldMap();

//					foreach($fields_map as $dict_key => $dao_key) {
//						DevblocksPlatform::logError(json_encode($dao_fields[$dao_key]));
//					}
				
				$key = 'records:' . $record_type->manifest->params['alias'] . ':fields:';
				$autocomplete_suggestions[$key] = array_map(fn($key) => $key . ':', array_keys($fields_map));
			}
			
			$tpl->assign('autocomplete_suggestions', $autocomplete_suggestions);
			
			$tpl->assign('model', $workflow);
			$tpl->display('devblocks:cerberusweb.core::records/types/workflow/update_template/popup.tpl');
			
		} catch (Exception) {
			DevblocksPlatform::dieWithHttpError(null, 500);
		}
	}
	
	private function _profileAction_saveTemplateJson() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$tpl = DevblocksPlatform::services()->template();
		$kata = DevblocksPlatform::services()->kata();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$template = DevblocksPlatform::importGPC($_POST['template']['kata'] ?? null, 'string', '');
		$config_values_form = DevblocksPlatform::importGPC($_POST['config_values'] ?? [], 'array', []);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$error = null;
		
		try {
			if(!($active_worker = CerberusApplication::getActiveWorker()))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				DevblocksPlatform::dieWithHttpError(null, 405);
			
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!($workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$template_validate = $template;

			// Replace placeholders before schema validation
			if(str_contains($template_validate, '$${')) {
				$lexer = [
					'tag_comment'   => ['$$#', '#'],
					'tag_block'     => ['$$%', '%'],
					'tag_variable'  => ['$${', '}'],
					'interpolation' => ['#$${', '}'],
				];

				if(false === ($template_validate = $tpl_builder->build($template, [], $lexer)))
					throw new Exception_DevblocksValidationError($tpl_builder->getLastError());
			}

			if(false === $kata->validate($template_validate, CerberusApplication::kataSchemas()->workflow(), $error))
				throw new Exception_DevblocksValidationError($error);
			
			// Update template changes
			$workflow->workflow_kata = $template;

			// Load config options
			if(false === ($workflow_config = $workflow->getConfig($error)))
				throw new Exception_DevblocksValidationError($error);

			// Merge saved values and current form state
			$config_values = array_merge($workflow_config, $config_values_form);

			// Possible config options from workflow
			$config_options = $workflow->getConfigOptions($config_values) ?: [];
			$tpl->assign('config_options', $config_options);
			
			$tpl->assign('model', $workflow);
			$html = $tpl->fetch('devblocks:cerberusweb.core::records/types/workflow/update_template/params.tpl');
			
			echo json_encode([
				'html' => $html,
			]);
			
		} catch (Exception_DevblocksValidationError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			echo json_encode([
				'error' => 'An unexpected error occurred.',
			]);
			return;
		}
	}
	
	private function _profileAction_saveConfigJson() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$workflow_kata = DevblocksPlatform::importGPC($_POST['template']['kata'] ?? null, 'string', '');
		$config_values = DevblocksPlatform::importGPC($_POST['config_values'] ?? [], 'array', []);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$tpl = DevblocksPlatform::services()->template();
		$kata = DevblocksPlatform::services()->kata();
		
		try {
			if(!($active_worker = CerberusApplication::getActiveWorker()))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				DevblocksPlatform::dieWithHttpError(null, 405);
			
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!($was_workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$new_workflow = clone $was_workflow;
			$new_workflow->workflow_kata = $workflow_kata;
			$new_workflow->setConfigValues($config_values);
			
			$resource_keys = [];
			
			
			$changes_automation = $was_workflow->getChangesAutomation($new_workflow, $resource_keys);
			
			// [TODO] Display that no changes will be made and disable 'next'
			
			// Summarize the changes that will be completed as a sheet
			
			// [TODO] Use the full sheet width on 'changes'; titleColumn action+record
			$sheet_kata = <<< EOD
              layout:
                headings@bool: yes
                paging@bool: no
              limit: 1000
              columns:
                text/action:
                  label: Action
                text/key:
                  label: Key
                markdown/changes:
                  label: Changes
              EOD;
			
			$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
			
			if(!($sheet = $sheets->parse($sheet_kata, $error)))
				throw new Exception_DevblocksAjaxValidationError('Error: ' . $error);
			
			$changes_kata = $kata->parse($changes_automation->script);
			$changes_kata = $kata->formatTree($changes_kata);
			
			$record_dicts = array_values(array_map(
				function($rk) use ($resource_keys, $changes_kata, $kata) {
					$action = $resource_keys['records'][$rk]['action'];
					$resource_name = DevblocksPlatform::services()->string()->strAfter($rk, '/');
					$changes = '';
					
					if('update' == $action) {
						$changes = "```\n";
						$changes .= $kata->emit($changes_kata['start']['record.update/'.$resource_name]['inputs']['fields'] ?? []);
						$changes .= "\n```";
					}
					
					return DevblocksDictionaryDelegate::instance([
						'key' => $rk,
						'action' => $action,
						'changes' => $changes,
					]);
				},
				array_keys($resource_keys['records'] ?? []),
			));
			
			// Include `extensions` changes
			$extension_dicts = array_values(array_map(
				function($rk) use ($resource_keys, $changes_kata, $kata) {
					$action = $resource_keys['extensions'][$rk]['action'];
					$changes = '';
					
					if('update' == $action && ($resource_keys['extensions'][$rk]['delta'] ?? null)) {
						$changes = "```\n";
						$changes .= $kata->emit($resource_keys['extensions'][$rk]['delta']);
						$changes .= "\n```";
					}
					
					return DevblocksDictionaryDelegate::instance([
						'key' => $rk,
						'action' => $action,
						'changes' => $changes,
					]);
				},
				array_keys($resource_keys['extensions'] ?? []),
			));
			
			$dicts = array_merge($record_dicts, $extension_dicts);
			
			$layout = $sheets->getLayout($sheet);
			$columns = $sheets->getColumns($sheet);
			$rows = $sheets->getRows($sheet, $dicts);
			
			$tpl->assign('model', $new_workflow);
			
			$tpl->assign('layout', $layout);
			$tpl->assign('columns', $columns);
			$tpl->assign('rows', $rows);
			
			$html = $tpl->fetch('devblocks:cerberusweb.core::records/types/workflow/update_template/changes.tpl');
			
			echo json_encode([
				'html' => $html,
			]);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			return;
		}
	}
	
	private function _profileAction_saveChangesJson() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$workflow_kata = DevblocksPlatform::importGPC($_POST['template']['kata'] ?? null, 'string', '');
		$config_values = DevblocksPlatform::importGPC($_POST['config_values'] ?? [], 'array', []);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$kata = DevblocksPlatform::services()->kata();
		$automator = DevblocksPlatform::services()->automation();
		
		try {
			if(!($active_worker = CerberusApplication::getActiveWorker()))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				DevblocksPlatform::dieWithHttpError(null, 405);
			
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!($was_workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$new_workflow = clone $was_workflow;
			$new_workflow->workflow_kata = $workflow_kata;
			$new_workflow->setConfigValues($config_values);
			
			$resource_keys = [];
			
			$initial_state = $new_workflow->getChangesAutomationInitialState();
			
			if (false === ($resources = $was_workflow->getResources($error)))
				throw new Exception_DevblocksAjaxValidationError('[Resources] ' . $error);
			
			$automation = $was_workflow->getChangesAutomation($new_workflow, $resource_keys);
			
			if($automation instanceof Model_Automation && $automation->script) {
				if (false === ($automation_results = $automator->executeScript($automation, $initial_state, $error)))
					throw new Exception_DevblocksAjaxValidationError('[ERROR] ' . $error);
				
				// [TODO] Update `automation` record changeset history
				
				foreach (($resource_keys['records'] ?? []) as $record_key => $record_changes) {
					if(!($record_action = $record_changes['action'] ?? ''))
						continue;
						
					if(in_array($record_action, ['delete', 'detach'])) {
						if (is_array($resources))
							unset($resources['records'][$record_key]);
						
					} else if('create' == $record_action) {
						$record_name = DevblocksPlatform::services()->string()->strAfter($record_key, '/');
					
						$new_record = $automation_results->getKeyPath('records:' . $record_name, null, ':');
					
						if(!($new_record instanceof DevblocksDictionaryDelegate))
							continue;
						
						$new_record_id = $new_record->get('id', 0);
						
						if(is_array($resources) && $new_record_id) {
							$resources['records'][$record_key] = intval($new_record_id);
						}
					}
				}
			}
			
			$new_workflow->resources_kata = $kata->emit($resources);
			
			// [TODO] Drop removed IDs from resources
			
			// Bit flags for extension types
			$has_extensions =
				(str_contains($new_workflow->workflow_kata, 'activity/') ? Model_Workflow::HAS_ACTIVITIES : 0)
				+ (str_contains($new_workflow->workflow_kata, 'permission/') ? Model_Workflow::HAS_PERMISSIONS : 0)
				+ (str_contains($new_workflow->workflow_kata, 'translation/') ? Model_Workflow::HAS_TRANSLATIONS : 0)
			;
			
			$fields = [
				DAO_Workflow::CONFIG_KATA => $new_workflow->config_kata,
				DAO_Workflow::DESCRIPTION => '',
				DAO_Workflow::HAS_EXTENSIONS => $has_extensions,
				DAO_Workflow::RESOURCES_KATA => $new_workflow->resources_kata,
				DAO_Workflow::UPDATED_AT => time(),
				DAO_Workflow::WORKFLOW_KATA => $new_workflow->workflow_kata,
			];
			DAO_Workflow::update($new_workflow->id, $fields);
			
			// Versioning
			try {
				DAO_RecordChangeset::create(
					'workflow',
					$new_workflow->id,
					[
						'template' => $fields[DAO_Workflow::WORKFLOW_KATA] ?? '',
					],
					$active_worker->id ?? 0
				);
				
			} catch (Exception $e) {
				DevblocksPlatform::logError('Error saving workflow changeset: ' . $e->getMessage());
			}
			
			// Conditionally clear cached extensions
			if($has_extensions & Model_Workflow::HAS_PERMISSIONS)
				DevblocksPlatform::clearCache(DevblocksEngine::CACHE_ACL);
			if($has_extensions & Model_Workflow::HAS_ACTIVITIES)
				DevblocksPlatform::clearCache(DevblocksEngine::CACHE_ACTIVITY_POINTS);
			if($has_extensions & Model_Workflow::HAS_TRANSLATIONS)
				DevblocksPlatform::services()->cache()->removeByTags(['translations']);
			
			echo json_encode([
				'success' => true,
			]);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			return;
		}
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
};
