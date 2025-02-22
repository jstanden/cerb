<?php

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

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
				case 'runBuilderPopup':
					return $this->_profileAction_runBuilderPopup();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showBuilderPopup':
					return $this->_profileAction_showBuilderPopup();
				case 'showTemplateUpdatePopup':
					return $this->_profileAction_showTemplateUpdatePopup();
				case 'showWorkflowDeletePopup':
					return $this->_profileAction_showWorkflowDeletePopup();
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
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			$package_uri = DevblocksPlatform::importGPC($_POST['package'] ?? null, 'string', '');
			
			$mode = 'build';
			
			if(!$id && $package_uri)
				$mode = 'library';
			
			switch($mode) {
				case 'library':
					$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
					
					if (empty($package_uri))
						throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
					
					if (!($package = DAO_PackageLibrary::getByUri($package_uri)))
						throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
					
					if ($package->point != 'workflow')
						throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
					
					$package_json = $package->getPackageJson();
					$records_created = [];
					
					$prompts['current_worker_id'] = $active_worker->id;
					
					try {
						CerberusApplication::packages()->import($package_json, $prompts, $records_created);
						
					} catch (Exception_DevblocksValidationError $e) {
						throw new Exception_DevblocksAjaxValidationError($e->getMessage());
						
					} catch (Exception) {
						throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
					}
					
					if (!array_key_exists(CerberusContexts::CONTEXT_WORKFLOW, $records_created))
						throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
					
					$new_workflow = reset($records_created[CerberusContexts::CONTEXT_WORKFLOW]);
					
					if ($view_id)
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKFLOW, $new_workflow['id']);
					
					echo json_encode([
						'status' => true,
						'context' => CerberusContexts::CONTEXT_WORKFLOW,
						'id' => $new_workflow['id'],
						'label' => $new_workflow['label'],
						'view_id' => $view_id,
					]);
					return;
					
				case 'build':
					$error = null;
					
					if (empty($id)) { // New
						$name = DevblocksPlatform::strAlphaNum(
							DevblocksPlatform::importGPC($_POST['_selection'] ?? null, 'string', 'workflow.empty'),
							'._'
						);
						
						$available_templates = [
							'cerb.auto_dispatcher',
							'cerb.auto_responder',
							'cerb.capture_feedback',
							'cerb.email.auto_watcher',
							'cerb.email.dmarc_reports',
							'cerb.email.org_by_hostname',
							'cerb.email.pgp_inline',
							'cerb.integrations.aws_bedrock.profile_images',
							'cerb.integrations.deepl.translate',
							'cerb.integrations.gitlab.issues',
							'cerb.integrations.ipstack',
							'cerb.integrations.slack.notifications',
							'cerb.login.terms_of_use',
							'cerb.notifications.mention_emailer',
							'cerb.quickstart',
							'cerb.records.reminders',
							'cerb.satisfaction.surveys',
							'cerb.search.simple',
							'cerb.sla',
							'cerb.tutorial',
							'workflow.empty',
						];
						
						if(!in_array($name, $available_templates)) {
							$error = sprintf("Unknown workflow template `%s`", $name);
							throw new Exception_DevblocksAjaxValidationError($error);
						}
						
						// Check if this workflow is already installed
						if(DAO_Workflow::getByName($name)) {
							$error = sprintf("This workflow `%s` is already created.", $name);
							throw new Exception_DevblocksAjaxValidationError($error);
						}
						
						$fields = [
							DAO_Workflow::NAME => $name,
							DAO_Workflow::CREATED_AT => time(),
							DAO_Workflow::UPDATED_AT => time(),
							DAO_Workflow::CONFIG_KATA => '',
							DAO_Workflow::WORKFLOW_KATA => '',
							DAO_Workflow::RESOURCES_KATA => '',
						];
						
						if('workflow.empty' == $name) {
							$name = uniqid('new_workflow.');
							$fields[DAO_Workflow::NAME] = $name;
						}
						
						if (!DAO_Workflow::validate($fields, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						if (!DAO_Workflow::onBeforeUpdateByActor($active_worker, $fields, null, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						$id = DAO_Workflow::create($fields);
						
						if (!empty($view_id) && !empty($id))
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_WORKFLOW, $id);
						
					} else { // Edit
						$fields = [
							DAO_Workflow::UPDATED_AT => time(),
						];
						
						if(!($model = DAO_Workflow::get($id)))
							DevblocksPlatform::dieWithHttpError(null, 404);
						
						$name = $model->name;
						
						if (!DAO_Workflow::validate($fields, $error, $id))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						if (!DAO_Workflow::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						DAO_Workflow::update($id, $fields);
						DAO_Workflow::onUpdateByActor($active_worker, $fields, $id);
					}
					
					if ($id) {
						// Custom field saves
						$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
						if (!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_WORKFLOW, $id, $field_ids, $error))
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
	
	private function _profileAction_showBuilderPopup() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$template_kata = DevblocksPlatform::importGPC($_POST['template_kata'] ?? null, 'string', '');
		
		$tpl = DevblocksPlatform::services()->template();
		$kata = DevblocksPlatform::services()->kata();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		try {
			if(!($workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$error = null;
			
			$builder_kata = [
				'export' => [
					'workflow' => [
						'name' => $workflow->name,
						'version' => gmdate('Y-m-d\T00:00:00\Z'),
						'description' => $workflow->description,
						'website' => 'https://cerb.ai/resources/workflows/',
						'requirements' => [
							'cerb_version' => '>=' . APP_VERSION,
							'cerb_plugins' => 'cerberusweb.core, ',
						]
					],
					'records' => [],
					'label_map' => [],
				],
			];
			
			$record_type_ids = [];
			
			foreach(($workflow->getResources()['records'] ?? []) as $resource_key => $resource_id) {
				list($resource_type, $resource_name) = explode('/', $resource_key, 2);
				$record_type_ids[$resource_type][] = $resource_id;
				$builder_kata['export']['label_map'][$resource_type . '_' . $resource_id] = $resource_name;
			}
			
			// [TODO] Condense as sets in the order defined (break up non-contiguous same record type)
			foreach($record_type_ids as $record_type => $record_ids) {
				$builder_kata['export']['records'][$record_type] = [
					'query' => 'id:[' . implode(',', $record_ids) . ']',
				];
			}
			
			ksort($builder_kata['export']['label_map']);
			
			// Override the workflow section with the most recent KATA from the template
			if(
				$template_kata
				&& ($template_kata = $kata->parse($template_kata, $error))
				&& ($template_kata['workflow'] ?? null)
			) {
				$builder_kata['export']['workflow'] = $template_kata['workflow'];
			}
			
			// Use the current UTC time as the new version
			$builder_kata['export']['workflow']['version'] = gmdate('Y-m-d\TH:i:s\Z');
			
			$workflow->builder_kata = DevblocksPlatform::services()->kata()->emit($builder_kata);
			
			$tpl->assign('model', $workflow);
			$tpl->display('devblocks:cerberusweb.core::records/types/workflow/builder/popup.tpl');
			
		} catch (Throwable) {
			DevblocksPlatform::dieWithHttpError(null, 500);
		}
	}
	
	private function _profileAction_runBuilderPopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			$workflow_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
			$workflow_builder_kata = DevblocksPlatform::importGPC($_POST['workflow_builder_kata'] ?? null, 'string', '');
			
			$export_model = new DevblocksWorkflowExportModel($workflow_builder_kata);
			
			// Save to the model every run
			DAO_Workflow::update($workflow_id, [
				DAO_Workflow::BUILDER_KATA => $workflow_builder_kata,
			]);
			
			echo json_encode([
				'status' => true,
				'workflow_kata' => $export_model->createWorkflowKata(true),
			]);
			
		} catch(Throwable) {
			echo json_encode([
				'status' => false,
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
	
	private function _profileAction_showTemplateUpdatePopup() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$section = DevblocksPlatform::importGPC($_POST['section'] ?? null, 'string', '');
		
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
					'extensions:',
					'records:',
					'workflow:',
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
				'workflow:' => [
					'config:',
					'description: A description of the workflow',
					'name: example.workflow.id',
					'instructions@text:',
					'requirements:',
					'version: ' . gmdate('Y-m-d\T00:00:00\Z'),
					'website: https://cerb.ai/resources/workflows/',
				],
				'workflow:config:' => [
					'chooser/key:',
					'picklist/key:',
					'text/key:',
				],
				'workflow:config:chooser:' => [
					'default:',
					'label:',
					'record_query:',
					'record_type:',
					'multiple@bool: no',
				],
				'workflow:config:picklist:' => [
					'default:',
					'label:',
					'multiple@bool: yes',
					'options@csv: option1, option2, option3',
				],
				'workflow:config:text:' => [
					'default:',
					'label:',
					'multiple@bool: yes',
				],
				'workflow:requirements:' => [
					'cerb_version: >=11.0 <11.2',
					'cerb_plugins: cerberusweb.core, ',
				],
				'workflow:version:' => [
					'2025-12-31T00:00:00Z',
				],
				'workflow:website:' => [
					'https://cerb.ai/resources/workflows/',
				]
			];
			
			$record_types = Extension_DevblocksContext::getAll(true, ['records']);
			
			$autocomplete_suggestions['workflow:config:chooser:record_type:'] = array_values(array_map(
				fn($record_type) => $record_type->manifest->params['alias'],
				$record_types
			));
			
			foreach($record_types as $record_type) {
				$record_suggestions = [
					'caption' => $record_type->manifest->params['alias'] . ':',
					'snippet' => $record_type->manifest->params['alias'] . "/\${1:" . uniqid('record'). "}:\n  fields:\n",
				];
				
				$key = 'records:' . $record_type->manifest->params['alias'] . ':';
				$autocomplete_suggestions[$key][] = 'fields:';
				$autocomplete_suggestions[$key][] = 'deletionPolicy:';
				$autocomplete_suggestions[$key . 'deletionPolicy:'] = ['delete', 'retain'];
				$autocomplete_suggestions[$key][] = 'updatePolicy:';
				$autocomplete_suggestions[$key . 'updatePolicy:'] = ['field_name, other_field_name'];
				
				$fields_meta = $record_type->getKeyMeta();
				
				$key = 'records:' . $record_type->manifest->params['alias'] . ':fields:';
				$autocomplete_suggestions[$key] = [
					'type' => 'record-fields',
					'params' => [
						'parent_key' => true,
					],
				];
				
				$field_snippet_count = 1;
				
				foreach($fields_meta as $field_key => $field_meta) {
					$field_caption = $field_key . ':';
					$field_snippet = $field_key;
					
					if($field_meta['is_immutable'] ?? null)
						continue;
					
					$field_is_required = $field_meta['is_required'] ?? null;
					
					$field_snippet .= match($field_meta['type'] ?? null) {
						'bit' => '@bit',
						'links' => '@list',
						'timestamp' => '@date',
						default => '',
					} . ':';
					
					// Add all required fields to the snippet
					if($field_is_required)
						$record_suggestions['snippet'] .= '    ' . $field_snippet . "\${" . ++$field_snippet_count . ":}\n";
					
					$autocomplete_suggestions[$key][] = [
						'caption' => $field_caption . (($field_meta['is_required'] ?? null) ? '*' : ''),
						'snippet' => $field_snippet,
						'description' => ($field_meta['notes'] ?? ''),
						'score' => $field_is_required ? 2000 : 1000,
					];
				
					$suggest_key = 'records:' . $record_type->manifest->params['alias'] . ':fields:' . $field_key . ':';
					$field_type = $field_meta['type'] ?? null;
					
					if('bit' == $field_type) {
						$autocomplete_suggestions[$suggest_key] = $record_type::getKeyAutocompleteBit();
					} else if('timestamp' == $field_type) {
						$autocomplete_suggestions[$suggest_key] = $record_type::getKeyAutocompleteDate();
					} else if('links' == $field_type) {
						$autocomplete_suggestions[$suggest_key] = $record_type::getKeyAutocompleteLinks();
					}
				}
				
				$autocomplete_suggestions['records:'][] = $record_suggestions;
				
				$key = 'records:' . $record_type->manifest->params['alias'] . ':fields:([^\:]*):';
				
				$autocomplete_suggestions['*'][$key] = [
					'type' => 'record-fields-value',
					'params' => [
						'record_type' => $record_type->manifest->params['alias'],
					],
				];
			}
			
			$tpl->assign('autocomplete_suggestions', $autocomplete_suggestions);
			
			// Default the template content when empty and we recognize the name
			if($workflow->id && empty($workflow->workflow_kata)) {
				$workflow->workflow_kata = match($workflow->name) {
					'cerb.auto_dispatcher' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.auto_dispatcher.kata'),
					'cerb.auto_responder' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.auto_responder.kata'),
					'cerb.capture_feedback' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.capture_feedback.kata'),
					'cerb.email.auto_watcher' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.email.auto_watcher.kata'),
					'cerb.email.dmarc_reports' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.email.dmarc_reports.kata'),
					'cerb.email.org_by_hostname' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.email.org_by_hostname.kata'),
					'cerb.email.pgp_inline' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.email.pgp_inline.kata'),
					'cerb.integrations.aws_bedrock.profile_images' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.integrations.aws_bedrock.profile_images.kata'),
					'cerb.integrations.deepl.translate' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.integrations.deepl.translate.kata'),
					'cerb.integrations.gitlab.issues' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.integrations.gitlab.issues.kata'),
					'cerb.integrations.ipstack' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.integrations.ipstack.kata'),
					'cerb.integrations.slack.notifications' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.integrations.slack.notifications.kata'),
					'cerb.login.terms_of_use' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.login.terms_of_use.kata'),
					'cerb.notifications.mention_emailer' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.notifications.mention_emailer.kata'),
					'cerb.quickstart' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.quickstart.kata'),
					'cerb.records.reminders' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.records.reminders.kata'),
					'cerb.satisfaction.surveys' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.satisfaction.surveys.kata'),
					'cerb.search.simple' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.search.simple.kata'),
					'cerb.sla' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.sla.kata'),
					'cerb.tutorial' => file_get_contents(APP_PATH . '/features/cerberusweb.core/workflows/cerb.tutorial.kata'),
					default => sprintf("workflow:\n  name: %s\n  version: %s\n  description: A description of the workflow\n  website: https://cerb.ai/resources/workflows/\n  requirements:\n    cerb_version: >=11.0 <11.2\n    cerb_plugins: cerberusweb.core, \n\nrecords:\n", $workflow->name, gmdate('Y-m-d\T00:00:00\Z')),
				};
			}
			
			$tpl->assign('section', $section);
			$tpl->assign('model', $workflow);
			$tpl->display('devblocks:cerberusweb.core::records/types/workflow/update_template/popup.tpl');
			
		} catch (Exception) {
			DevblocksPlatform::dieWithHttpError(null, 500);
		}
	}
	
	private function _profileAction_showWorkflowDeletePopup() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$error = null;
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		try {
			if(!($workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$resource_keys = [];
			
			$new_workflow = clone $workflow;
			$new_workflow->workflow_kata = '';
			$new_workflow->config_kata = '';
			
			$resources = $workflow->getResourceRecordDictionaries($error);
			
			$workflow->getChangesAutomation($new_workflow, $resource_keys);
			
			$sheet_kata = <<< EOD
              layout:
                headings@bool: yes
                paging@bool: no
              limit: 1000
              columns:
                text/__action:
                  label: Action
                text/__key:
                  label: Key
                card/_label:
                  label: Record
                  params:
                    bold@bool: yes
              EOD;
			
			$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
			
			if(!($sheet = $sheets->parse($sheet_kata, $error)))
				throw new Exception_DevblocksAjaxValidationError('Error: ' . $error);
			
			$record_dicts = array_values(array_map(
				function($rk) use ($resource_keys, $resources) {
					$action = $resource_keys['records'][$rk]['action'];
					
					$dict = $resources[$rk] ?? DevblocksDictionaryDelegate::instance([]);
					
					$dict->set('__action', $action);
					$dict->set('__key', $rk);
					
					return $dict;
				},
				array_keys($resource_keys['records'] ?? []),
			));
			
			$layout = $sheets->getLayout($sheet);
			$columns = $sheets->getColumns($sheet);
			$rows = $sheets->getRows($sheet, $record_dicts);
			
			$tpl->assign('layout', $layout);
			$tpl->assign('columns', $columns);
			$tpl->assign('rows', $rows);
			
			$tpl->assign('model', $workflow);
			$tpl->display('devblocks:cerberusweb.core::records/types/workflow/delete_workflow/popup.tpl');
			
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
			
			if(false === ($new_template = $workflow->getParsedTemplate($error)))
				throw new Exception_DevblocksValidationError($error);
			
			// Check requirements or error out
			if(is_array($new_template['workflow']['requirements'] ?? null))
				if(!$workflow->checkRequirements($new_template['workflow']['requirements'], $error))
					throw new Exception_DevblocksValidationError($error);
			
			$update_fields = [];
			
			if(($new_template['workflow']['name'] ?? null) && $new_template['workflow']['name'] != $workflow->name) {
				$workflow->name = $new_template['workflow']['name'] ?? uniqid('workflow_');
				$update_fields[DAO_Workflow::NAME] = $workflow->name;
			}
			
			if(($new_template['workflow']['description'] ?? null) && $new_template['workflow']['description'] != $workflow->description) {
				$workflow->description = $new_template['workflow']['description'] ?? '';
				$update_fields[DAO_Workflow::DESCRIPTION] = $workflow->description;
			}
			
			// Verify records all have distinct names
			if($new_template['records'] ?? []) {
				$record_names = [];
				
				foreach(array_keys($new_template['records']) as $record_key) {
					$record_name = DevblocksPlatform::services()->string()->strAfter($record_key, '/');
					
					if(array_key_exists($record_name, $record_names))
						throw new Exception_DevblocksValidationError(sprintf('Record names must be unique (%s)', $record_key));
					
					if(!$record_name)
						throw new Exception_DevblocksValidationError(sprintf('Record names are required (%s)', $record_key));
					
					$record_names[$record_name] = true;
				}
			}
			
			if($workflow->id && $update_fields) {
				if(!DAO_Workflow::validate($update_fields, $error, $workflow->id)) {
					throw new Exception_DevblocksValidationError($error);
				}
				
				DAO_Workflow::update($workflow->id, $update_fields);
			}
			
			// Instructions
			if(($workflow_instructions = ($new_template['workflow']['instructions'] ?? $new_template['workflow']['website'] ?? null))) {
				$workflow_instructions = DevblocksPlatform::parseMarkdown($workflow_instructions, true);
				$workflow_instructions = DevblocksPlatform::purifyHTML($workflow_instructions, true, true);
				$tpl->assign('workflow_instructions', $workflow_instructions);
			}

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
				'workflow_name' => $workflow->name,
				'html' => $html,
			]);
			
		} catch (Exception_DevblocksValidationError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			DevblocksPlatform::logException($e);
			
			echo json_encode([
				'error' => 'An unexpected error occurred.',
			]);
			return;
		}
	}
	
	private function _profileAction_saveConfigJson() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$workflow_kata = DevblocksPlatform::importGPC($_POST['template']['kata'] ?? null, 'string', '');
		$import_kata = DevblocksPlatform::importGPC($_POST['template']['import_resources'] ?? null, 'string', '');
		$config_values = DevblocksPlatform::importGPC($_POST['config_values'] ?? [], 'array', []);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$tpl = DevblocksPlatform::services()->template();
		$kata = DevblocksPlatform::services()->kata();
		$differ = new Differ(new UnifiedDiffOutputBuilder('', true));
		
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
			
			$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
			
			// [TODO] Display that no changes will be made and disable 'next'
			
			// Summarize the changes that will be completed as a sheet
			
			$records_sheet_kata = <<< EOD
              layout:
                headings@bool: no
                paging@bool: no
                style: fieldsets
              limit: 1000
              columns:
                markdown/label:
                  #label: Diff
                  params:
                    value_template@raw:
                      ##### [{{action}}] {{key}}
                code/diff:
                  #label: Diff
                  params:
                    syntax: diff
                    value_template@raw: {{diff}}
              EOD;
			
			if(!($records_sheet = $sheets->parse($records_sheet_kata, $error)))
				throw new Exception_DevblocksAjaxValidationError('Sheet Parse Error: ' . $error);
			
			if($import_kata)
				$was_workflow->importResources($import_kata);
			
			$was_workflow->getChangesAutomation($new_workflow, $resource_keys);
			
			// Include configuration changes
			
			$change_dicts = [];
			
			foreach(array_keys($resource_keys['config'] ?? []) as $config_key) {
				$old_value = $resource_keys['config'][$config_key]['old_value'] ?? '';
				$new_value = $resource_keys['config'][$config_key]['new_value'] ?? '';
				
				$old_config_options = $was_workflow->getConfigOptions([$config_key => $old_value]) ?: [];
				$new_config_options = $was_workflow->getConfigOptions([$config_key => $new_value]) ?: [];
				
				if('chooser' == ($old_config_options[$config_key]['type'] ?? null)) {
					if(array_key_exists('record_label', $old_config_options[$config_key]['params'])) {
						$old_value = $old_config_options[$config_key]['params']['record_label'];
					} elseif(array_key_exists('record_labels', $old_config_options[$config_key]['params'])) {
						$old_value = implode(', ', $old_config_options[$config_key]['params']['record_labels']);
					}
				}
				
				if('chooser' == ($new_config_options[$config_key]['type'] ?? null)) {
					if(array_key_exists('record_label', $new_config_options[$config_key]['params'])) {
						$new_value = $new_config_options[$config_key]['params']['record_label'];
					} elseif(array_key_exists('record_labels', $new_config_options[$config_key]['params'])) {
						$new_value = implode(', ', $new_config_options[$config_key]['params']['record_labels']);
					}
				}
				
				$change_dicts[] = DevblocksDictionaryDelegate::instance([
					'key' => 'config/' . $config_key,
					'action' => $resource_keys['config'][$config_key]['action'] ?? '',
					'diff' => $differ->diff($old_value, $new_value),
				]);
			}
			
			foreach(array_keys($resource_keys['records'] ?? []) as $rk) {
				$action = $resource_keys['records'][$rk]['action'];
				
				$was = '';
				$new = '';
				
				if('update' == $action) {
					$was_fields = array_intersect_key(
						$resource_keys['templates']['was']['records'][$rk]['fields'] ?? [],
						array_fill_keys($resource_keys['records'][$rk]['fields'] ?? [], true)
					);
					
					$was = $kata->emit($was_fields);
					
					$new_fields = array_intersect_key(
						$resource_keys['templates']['new']['records'][$rk]['fields'] ?? [],
						array_fill_keys($resource_keys['records'][$rk]['fields'] ?? [], true)
					);
					
					$new = $kata->emit($new_fields);
				}
				
				$change_dicts[] = DevblocksDictionaryDelegate::instance([
					'key' => $rk,
					'action' => $action,
					'diff' => $differ->diff($was, $new),
				]);
			}
			
			// Include `extensions` changes
			$extension_dicts = array_values(array_map(
				function($rk) use ($resource_keys, $kata) {
					$action = $resource_keys['extensions'][$rk]['action'];
					$changes = '';
					
					if('update' == $action && ($resource_keys['extensions'][$rk]['delta'] ?? null)) {
						$changes .= $kata->emit($resource_keys['extensions'][$rk]['delta']);
					}
					
					return DevblocksDictionaryDelegate::instance([
						'key' => $rk,
						'action' => $action,
						'diff' => $changes,
					]);
				},
				array_keys($resource_keys['extensions'] ?? []),
			));
			
			$dicts = array_merge($change_dicts, $extension_dicts);
			
			$record_layout = $sheets->getLayout($records_sheet);
			$record_columns = $sheets->getColumns($records_sheet);
			$record_rows = $sheets->getRows($records_sheet, $dicts);
			
			$tpl->assign('model', $new_workflow);
			
			$tpl->assign('layout', $record_layout);
			$tpl->assign('columns', $record_columns);
			$tpl->assign('rows', $record_rows);
			
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
		$delete = DevblocksPlatform::importGPC($_POST['delete'] ?? null, 'integer', 0);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$workflows = DevblocksPlatform::services()->workflow();
		
		try {
			if(!($active_worker = CerberusApplication::getActiveWorker()))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				DevblocksPlatform::dieWithHttpError(null, 405);
			
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!($was_workflow = DAO_Workflow::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if($delete) {
				$workflow_kata = '';
				$import_kata = '';
				$config_values = [];
			} else {
				$workflow_kata = DevblocksPlatform::importGPC($_POST['template']['kata'] ?? null, 'string', '');
				$import_kata = DevblocksPlatform::importGPC($_POST['template']['import_resources'] ?? null, 'string', '');
				$config_values = DevblocksPlatform::importGPC($_POST['config_values'] ?? [], 'array', []);
			}
			
			$new_workflow = clone $was_workflow;
			$new_workflow->workflow_kata = $workflow_kata;
			$new_workflow->setConfigValues($config_values);

			if($import_kata) {
				$was_workflow->importResources($import_kata);
				DAO_Workflow::update($was_workflow->id, [
					DAO_Workflow::RESOURCES_KATA => $was_workflow->resources_kata,
				]);
			}
			
			if($delete) {
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_WORKFLOW, $id, $was_workflow->name);
				
				// Bit flags for extension types
				$has_extensions = $was_workflow->getExtensionBits();
				
				if(false === ($workflows->import($new_workflow, $active_worker, $error))) {
					throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				DAO_Workflow::delete($id);
				
				// Conditionally clear cached extensions
				if($has_extensions & Model_Workflow::HAS_PERMISSIONS)
					DevblocksPlatform::clearCache(DevblocksEngine::CACHE_ACL);
				if($has_extensions & Model_Workflow::HAS_ACTIVITIES)
					DevblocksPlatform::clearCache(DevblocksEngine::CACHE_ACTIVITY_POINTS);
				if($has_extensions & Model_Workflow::HAS_TRANSLATIONS)
					DevblocksPlatform::services()->cache()->removeByTags(['translations']);
				
			} else {
				$error = null;
				
				if(false === ($workflows->import($new_workflow, $active_worker, $error))) {
					throw new Exception_DevblocksAjaxValidationError($error);
				}
			}
			
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
