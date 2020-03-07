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

class PageSection_ProfilesBehavior extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // trigger_event 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_BEHAVIOR;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'duplicateNode':
					return $this->_profileAction_duplicateNode();
				case 'getActionParams':
					return $this->_profileAction_getActionParams();
				case 'getConditionParams':
					return $this->_profileAction_getConditionParams();
				case 'getParams':
					return $this->_profileAction_getParams();
				case 'getParamsAsJson':
					return $this->_profileAction_getParamsAsJson();
				case 'getTriggerEventParams':
					return $this->_profileAction_getTriggerEventParams();
				case 'getTriggerVariableParams':
					return $this->_profileAction_getTriggerVariableParams();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'getEventsMenuByBot':
					return $this->_profileAction_getEventsMenuByBot();
				case 'renderDecisionPopup':
					return $this->_profileAction_renderDecisionPopup();
				case 'saveDecisionPopup':
					return $this->_profileAction_saveDecisionPopup();
				case 'renderDecisionNodeMenu':
					return $this->_profileAction_renderDecisionNodeMenu();
				case 'renderDecisionTree':
					return $this->_profileAction_renderDecisionTree();
				case 'renderSimulatorPopup':
					return $this->_profileAction_renderSimulatorPopup();
				case 'runSimulator':
					return $this->_profileAction_runSimulator();
				case 'renderExportPopup':
					return $this->_profileAction_renderExportPopup();
				case 'renderImportPopup':
					return $this->_profileAction_renderImportPopup();
				case 'saveImportPopupJson':
					return $this->_profileAction_saveImportPopupJson();
				case 'reparentNode':
					return $this->_profileAction_reparentNode();
				case 'renderDecisionReorderPopup':
					return $this->_profileAction_renderDecisionReorderPopup();
				case 'saveDecisionReorderPopup':
					return $this->_profileAction_saveDecisionReorderPopup();
				case 'saveDecisionDeletePopup':
					return $this->_profileAction_saveDecisionDeletePopup();
				case 'testDecisionEventSnippets':
					return $this->_profileAction_testDecisionEventSnippets();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _parseActions($action_ids, $scope) {
		$objects = array();
		
		foreach($action_ids as $action_id) {
			$params = DevblocksPlatform::importGPC($scope['action'.$action_id],'array',array());
			
			/*
			 * [TODO] This should probably be given to each action extension so they
			 * can make any last minute changes to the persisted params.  We don't really
			 * want to bury the worklist_model_json stuff here since this is global, and
			 * only set_var_* uses this param.
			 */
			if(isset($params['worklist_model_json'])) {
				$params['worklist_model'] = json_decode($params['worklist_model_json'], true);
				unset($params['worklist_model_json']);
			}
			
			$objects[] = $params;
		}
		
		return $objects;
	}
	
	private function _profileAction_savePeekJson() {
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_BEHAVIOR)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_TriggerEvent::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_TriggerEvent::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_BEHAVIOR, $model->id, $model->title);
				
				DAO_TriggerEvent::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$package_uri = DevblocksPlatform::importGPC($_POST['package'], 'string', '');
				@$import_json = DevblocksPlatform::importGPC($_POST['import_json'],'string', '');
				
				$mode = 'build';
				
				if(!$id && $package_uri) {
					$mode = 'library';
					
				} elseif(!$id && $import_json) {
					$mode = 'import';
				}
				
				switch($mode) {
					case 'library':
						@$prompts = DevblocksPlatform::importGPC($_POST['prompts'], 'array', []);
						@$bot_id = DevblocksPlatform::importGPC($_POST['bot_id'],'integer', 0);
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'behavior')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						// Verify the event can be owned by this context
						
						if(false == ($bot = DAO_Bot::get($bot_id))) {
							throw new Exception_DevblocksAjaxValidationError("The destination bot doesn't exist.");
						}
						
						// Does the worker have access to this bot?
						if(
							!$active_worker->hasPriv(sprintf("contexts.%s.create", CerberusContexts::CONTEXT_BEHAVIOR)) 
							|| !CerberusContexts::isOwnableBy($bot->owner_context, $bot->owner_context_id, $active_worker)
						) {
							throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
						}
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						$prompts['bot_id'] = $bot_id;
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_TriggerEvent::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_behavior = reset($records_created[Context_TriggerEvent::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BEHAVIOR, $new_behavior['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_behavior['id'],
							'label' => $new_behavior['label'],
							'view_id' => $view_id,
						]);
						return;
						break;
					
					case 'import':
						@$bot_id = DevblocksPlatform::importGPC($_POST['bot_id'],'integer', 0);
						@$configure = DevblocksPlatform::importGPC($_POST['configure'],'array', []);
						
						if(empty($import_json))
							throw new Exception_DevblocksAjaxValidationError("The JSON to import is required.", "import_json");
						
						if(
							false == ($json = json_decode($import_json, true))
							|| !isset($json['behavior'])
							|| !isset($json['behavior']['event']['key'])
						) {
							throw new Exception_DevblocksAjaxValidationError("The JSON to import is invalid.", "import_json");
						}
						
						@$event_point = $json['behavior']['event']['key'];
						
						if(
							false == ($event = Extension_DevblocksEvent::get($event_point, true))
							|| !($event instanceof Extension_DevblocksEvent)
						) {
							throw new Exception_DevblocksAjaxValidationError("The imported behavior specifies an invalid event.");
						}
						
						// Verify the event can be owned by this context
						
						if(false == ($bot = DAO_Bot::get($bot_id))) {
							throw new Exception_DevblocksAjaxValidationError("The destination bot doesn't exist.");
						}
						
						// Verify that the bot is allowed to make these events
						
						if(!$bot->canUseEvent($event_point))
							throw new Exception_DevblocksAjaxValidationError("This bot can't listen for this event.");
						
						// Does the worker have access to this bot?
						if(
							!$active_worker->hasPriv(sprintf("contexts.%s.import", CerberusContexts::CONTEXT_BEHAVIOR)) 
							|| !CerberusContexts::isOwnableBy($bot->owner_context, $bot->owner_context_id, $active_worker)
						) {
							throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.import'));
						}
						
						// Verify that the active worker has access to make events for this context
						
						if(
							!isset($event->manifest->params['contexts'])
							|| !isset($event->manifest->params['contexts'][0])
							|| !is_array($event->manifest->params['contexts'][0])
							|| !in_array($bot->owner_context, array_keys($event->manifest->params['contexts'][0]))
						) {
							throw new Exception_DevblocksAjaxValidationError("Unable to assign event to this owner.");
						}
						
						// Allow prompted configuration of the VA behavior import
						
						@$configure_fields = $json['behavior']['configure'];
						
						// Are there configurable fields in this import file?
						if(is_array($configure_fields) && !empty($configure_fields)) {
							// If the worker has provided the configuration, make the changes to JSON array
							if(!empty($configure)) {
								foreach($configure_fields as $config_field_idx => $config_field) {
									if(!isset($config_field['path']))
										continue;
									
									if(!isset($configure[$config_field_idx]))
										continue;
									
									$ptr =& DevblocksPlatform::jsonGetPointerFromPath($json, $config_field['path']);
									
									if(!is_null($ptr))
										$ptr = $configure[$config_field_idx];
								}
								
							// If the worker hasn't been prompted, do that now
							} else {
								$tpl = DevblocksPlatform::services()->template();
								$tpl->assign('import_json', $import_json);
								$tpl->assign('import_fields', $configure_fields);
								$config_html = $tpl->fetch('devblocks:cerberusweb.core::internal/import/prompted/configure_json_import.tpl');
								
								echo json_encode(array(
									'config_html' => $config_html,
								));
								return;
							}
						}
						
						// Create behavior record
						
						$fields = array(
							DAO_TriggerEvent::TITLE => $json['behavior']['title'],
							DAO_TriggerEvent::EVENT_POINT => $event_point,
							DAO_TriggerEvent::VARIABLES_JSON => isset($json['behavior']['variables']) ? json_encode($json['behavior']['variables']) : '',
							DAO_TriggerEvent::EVENT_PARAMS_JSON => isset($json['behavior']['event']['params']) ? json_encode($json['behavior']['event']['params']) : '',
							DAO_TriggerEvent::BOT_ID => $bot->id,
							DAO_TriggerEvent::PRIORITY => @$json['behavior']['priority'] ?: 50,
							DAO_TriggerEvent::IS_DISABLED => 1, // default to disabled until successfully imported
							DAO_TriggerEvent::IS_PRIVATE => @$json['behavior']['is_private'] ? 1 : 0,
							DAO_TriggerEvent::UPDATED_AT => time(),
						);
						
						$error = null;
						
						// Validate
						if(!DAO_TriggerEvent::validate($fields, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						// Check permissions
						if(!DAO_TriggerEvent::onBeforeUpdateByActor($active_worker, $fields, null, $error))
							throw new Exception_DevblocksAjaxValidationError($error);
						
						$behavior_id = DAO_TriggerEvent::create($fields);
						
						DAO_TriggerEvent::onUpdateByActor($active_worker, $fields, $behavior_id);
						
						// Create records for all child nodes and link them to the proper parents
			
						if(isset($json['behavior']['nodes']))
						if(false == DAO_TriggerEvent::recursiveImportDecisionNodes($json['behavior']['nodes'], $behavior_id, 0))
							throw new Exception_DevblocksAjaxValidationError("Failed to import nodes");
						
						// Enable the new behavior since we've succeeded
						
						DAO_TriggerEvent::update($behavior_id, array(
							DAO_TriggerEvent::IS_DISABLED => @$json['behavior']['is_disabled'] ? 1 : 0,
						));
						
						if(!empty($view_id) && !empty($behavior_id))
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BEHAVIOR, $behavior_id);
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'label' => $json['behavior']['title'],
							'view_id' => $view_id,
						));
						return;
						break;
						
					case 'build':
						@$title = DevblocksPlatform::importGPC($_POST['title'],'string', '');
						@$uri = DevblocksPlatform::importGPC($_POST['uri'],'string', '');
						@$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'],'integer', 0);
						@$is_private = DevblocksPlatform::importGPC($_POST['is_private'],'integer', 0);
						@$priority = DevblocksPlatform::importGPC($_POST['priority'],'integer', 0);
						@$event_params = DevblocksPlatform::importGPC($_POST['event_params'],'array', array());
						@$json = DevblocksPlatform::importGPC($_POST['json'],'integer', 0);
			
						$priority = DevblocksPlatform::intClamp($priority, 1, 99);
						
						// Variables
			
						@$var_idxs = DevblocksPlatform::importGPC($_POST['var'],'array',array());
						@$var_keys = DevblocksPlatform::importGPC($_POST['var_key'],'array',array());
						@$var_types = DevblocksPlatform::importGPC($_POST['var_type'],'array',array());
						@$var_labels = DevblocksPlatform::importGPC($_POST['var_label'],'array',array());
						@$var_is_private = DevblocksPlatform::importGPC($_POST['var_is_private'],'array',array());
						
						$variables = [];
						
						if(is_array($var_labels))
						foreach($var_labels as $idx => $v) {
							if(empty($var_labels[$idx]))
								continue;
							
							$var_name = 'var_' . DevblocksPlatform::strAlphaNum(DevblocksPlatform::strToPermalink($v,'_'),'_');
							$key = DevblocksPlatform::strLower(!empty($var_keys[$idx]) ? $var_keys[$idx] : $var_name);
							
							// Variable params
							@$var_idx = $var_idxs[$idx];
							$var_params = isset($_POST['var_params'.$var_idx]) ? DevblocksPlatform::importGPC($_POST['var_params'.$var_idx],'array',array()) : array();
							
							$variables[$key] = array(
								'key' => $key,
								'label' => $v,
								'type' => $var_types[$idx],
								'is_private' => $var_is_private[$idx] ? true : false,
								'params' => $var_params,
							);
						}
						
						// Create behavior
						if(empty($id)) {
							@$bot_id = DevblocksPlatform::importGPC($_POST['bot_id'], 'integer', 0);
							@$event_point = DevblocksPlatform::importGPC($_POST['event_point'],'string', '');
							
							$error = null;
							
							// Make sure the extension is valid
							
							if(false == ($bot = DAO_Bot::get($bot_id)))
								throw new Exception_DevblocksAjaxValidationError("Invalid bot.");
							
							if(null == ($ext = Extension_DevblocksEvent::get($event_point, true)))
								throw new Exception_DevblocksAjaxValidationError("Invalid event.", 'event_point');
							
							if(!$bot->canUseEvent($event_point))
								throw new Exception_DevblocksAjaxValidationError("The bot can't listen for the selected event.");
							
							// Let the event validate the event params
							if(false === $ext->prepareEventParams(null, $event_params, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$fields = [
								DAO_TriggerEvent::BOT_ID => $bot_id,
								DAO_TriggerEvent::EVENT_POINT => $event_point,
								DAO_TriggerEvent::TITLE => $title,
								DAO_TriggerEvent::URI => $uri,
								DAO_TriggerEvent::IS_DISABLED => !empty($is_disabled) ? 1 : 0,
								DAO_TriggerEvent::IS_PRIVATE => !empty($is_private) ? 1 : 0,
								DAO_TriggerEvent::PRIORITY => $priority,
								DAO_TriggerEvent::EVENT_PARAMS_JSON => json_encode($event_params),
								DAO_TriggerEvent::VARIABLES_JSON => json_encode($variables),
								DAO_TriggerEvent::UPDATED_AT => time(),
							];
							
							// Validate
							if(!DAO_TriggerEvent::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							// Check permissions
							if(!DAO_TriggerEvent::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = DAO_TriggerEvent::create($fields);
							DAO_TriggerEvent::onUpdateByActor($active_worker, $fields, $id);
							
							if(!empty($view_id) && !empty($id))
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BEHAVIOR, $id);
							
						// Update trigger
						} else {
							if(false == ($behavior = DAO_TriggerEvent::get($id)))
								throw new Exception_DevblocksAjaxValidationError("Invalid behavior.");
								
							if(!Context_TriggerEvent::isWriteableByActor($behavior, $active_worker))
								throw new Exception_DevblocksAjaxValidationError("You don't have permission to modify this record.");
		
							if(null == ($ext = $behavior->getEvent()))
								throw new Exception_DevblocksAjaxValidationError("Invalid event.");
							
							$error = null;
							
							// Let the event validate the event params
							if(false === $ext->prepareEventParams($behavior, $event_params, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							// Handle deletes
							if(is_array($behavior->variables))
							foreach(array_keys($behavior->variables) as $var) {
								if(!isset($variables[$var])) {
									DAO_DecisionNode::deleteTriggerVar($behavior->id, $var);
								}
							}
							
							$fields = [
								DAO_TriggerEvent::TITLE => $title,
								DAO_TriggerEvent::URI => $uri,
								DAO_TriggerEvent::IS_DISABLED => !empty($is_disabled) ? 1 : 0,
								DAO_TriggerEvent::IS_PRIVATE => !empty($is_private) ? 1 : 0,
								DAO_TriggerEvent::PRIORITY => $priority,
								DAO_TriggerEvent::EVENT_PARAMS_JSON => json_encode($event_params),
								DAO_TriggerEvent::VARIABLES_JSON => json_encode($variables),
								DAO_TriggerEvent::UPDATED_AT => time(),
							];
							
							if(!DAO_TriggerEvent::validate($fields, $error, $behavior->id))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_TriggerEvent::onBeforeUpdateByActor($active_worker, $fields, $behavior->id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_TriggerEvent::update($behavior->id, $fields);
							DAO_TriggerEvent::onUpdateByActor($active_worker, $fields, $behavior->id);
						}
						
						if($id) {
							// Custom field saves
							@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
							if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_BEHAVIOR, $id, $field_ids, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							echo json_encode(array(
								'status' => true,
								'id' => $id,
								'label' => $title,
								'view_id' => $view_id,
							));
							return;
						}
						break;
						
					default:
						throw new Exception_DevblocksAjaxValidationError("Choose library, build, or import.");
						break;
				}
			}
			
			throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
			
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
	
	private function _profileAction_getEventsMenuByBot() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$bot_id = DevblocksPlatform::importGPC($_REQUEST['bot_id'], 'integer', 0);
		
		if(false == ($bot = DAO_Bot::get($bot_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Bot::isReadableByActor($bot, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Get all events
		$events = Extension_DevblocksEvent::getByContext($bot->owner_context, false);

		// Filter the available events by VA
		$events = $bot->filterEventsByAllowed($events);
		
		// Menu
		$labels = [];
		foreach($events as $event) { /* @var $event DevblocksExtensionManifest */
			// Remove deprecated events from creation
			if(@$event->params['deprecated'])
				continue;
			
			if(false == ($label = @$event->params['menu_key']))
				$label = $event->name;
			
			$labels[$event->id] = $label;
		}
		
		$events_menu = Extension_DevblocksContext::getPlaceholderTree($labels, ':', ' ', false);
		
		$tpl->assign('bot', $bot);
		$tpl->assign('events', $events);
		$tpl->assign('events_menu', $events_menu);
		
		$tpl->display('devblocks:cerberusweb.core::internal/peek/menu_behavior_event.tpl');
	}
	
	private function _profileAction_saveImportPopupJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
		@$node_id = DevblocksPlatform::importGPC($_POST['node_id'],'integer', 0);
		@$behavior_json = DevblocksPlatform::importGPC($_POST['behavior_json'],'string', null);
		@$configure = DevblocksPlatform::importGPC($_POST['configure'],'array', array());
		$parent = null;
		
		header('Content-Type: application/json');
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id))) {
			echo json_encode([
				'status' => false,
				'error' => 'Invalid behavior.',
			]);
			return;
		}
		
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker)) {
			echo json_encode([
				'status' => false,
				'error' => 'Access denied.',
			]);
			return;
		}

		if($node_id && false == ($parent = DAO_DecisionNode::get($node_id))) {
			echo json_encode([
				'status' => false,
				'error' => 'Invalid parent node.',
			]);
			return;
		}
		
		if(false == (@$json = json_decode($behavior_json, true))) {
			echo json_encode([
				'status' => false,
				'error' => 'Invalid JSON (unable to decode).',
			]);
			return;
		}
		
		if(!is_array($json) || !isset($json['behavior_fragment'])) {
			echo json_encode([
				'status' => false,
				'error' => 'Invalid JSON (missing fragment).',
			]);
			return;
		}
		
		// Verify event type
		if($trigger->event_point != @$json['behavior_fragment']['event']['key']) {
			echo json_encode([
				'status' => false,
				'error' => sprintf('Imported event (%s) differs from target event (%s).',
					@$json['behavior_fragment']['event']['key'],
					$trigger->event_point
				),
			]);
			return;
		}
		
		@$nodes = $json['behavior_fragment']['nodes'];
		
		$validation = [
			'action' => [],
			'behavior' => ['action','loop','subroutine','switch'],
			'loop' => ['action','loop','switch'],
			'outcome' => ['action','loop','switch'],
			'subroutine' => ['action','loop','switch'],
			'switch' => ['outcome'],
		];
		
		if($parent && $parent instanceof Model_DecisionNode) {
			$parent_type = $parent->node_type;
			$parent_id = $parent->id;
		} else {
			$parent_type = 'behavior';
			$parent_id = 0;
		}
		
		// Validation
		
		foreach($nodes as $node) {
			if(!in_array($node['type'], $validation[$parent_type])) {
				echo json_encode([
					'status' => false,
					'error' => sprintf("Invalid JSON ('%s' can't contain '%s').", $parent->node_type, $node['type']),
				]);
				return;
			}
		}
		
		// Allow prompted configuration of the VA behavior import
		
		@$configure_fields = $json['behavior_fragment']['configure'];
		
		// Are there configurable fields in this import file?
		if(is_array($configure_fields) && !empty($configure_fields)) {
			// If the worker has provided the configuration, make the changes to JSON array
			if(!empty($configure)) {
				foreach($configure_fields as $config_field_idx => $config_field) {
					if(!isset($config_field['path']))
						continue;
					
					if(!isset($configure[$config_field_idx]))
						continue;
					
					$ptr =& DevblocksPlatform::jsonGetPointerFromPath($json, $config_field['path']);
					
					if(!is_null($ptr))
						$ptr = $configure[$config_field_idx];
				}
				
			// If the worker hasn't been prompted, do that now
			} else {
				$tpl = DevblocksPlatform::services()->template();
				$tpl->assign('import_json', $behavior_json);
				$tpl->assign('import_fields', $configure_fields);
				$config_html = $tpl->fetch('devblocks:cerberusweb.core::internal/import/prompted/configure_json_import.tpl');
				
				echo json_encode(array(
					'config_html' => $config_html,
				));
				return;
			}
		}
		
		if(false == DAO_TriggerEvent::recursiveImportDecisionNodes($json['behavior_fragment']['nodes'], $trigger->id, $parent_id)) {
			echo json_encode([
				'status' => false,
				'error' => 'Failed to import behavior fragment.',
			]);
			return;
		}
		
		echo json_encode(array(
			'status' => true,
		));
	}
	
	private function _profileAction_viewExplore() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=behavior', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=behavior&id=%d-%s", $row[SearchFields_TriggerEvent::ID], DevblocksPlatform::strToPermalink($row[SearchFields_TriggerEvent::TITLE])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_TriggerEvent::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	private function _profileAction_renderDecisionNodeMenu() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		
		$trigger = null;
		
		if($trigger_id) {
			if (false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if($id) {
			if (false == ($node = DAO_DecisionNode::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if (false == ($trigger = DAO_TriggerEvent::get($node->trigger_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$tpl->assign('node', $node);
		}
		
		if($trigger && !Context_TriggerEvent::isReadableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('trigger_id', $trigger->id);
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/menu.tpl');
	}
	
	private function _profileAction_renderDecisionTree() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($event = Extension_DevblocksEvent::get($trigger->event_point, false)))
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		/* @var $event Extension_DevblocksEvent */
		
		if(!Context_TriggerEvent::isReadableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$is_writeable = Context_TriggerEvent::isWriteableByActor($trigger, $active_worker);
		$tpl->assign('is_writeable', $is_writeable);
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/tree.tpl');
	}
	
	private function _profileAction_renderDecisionPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$trigger_id = 0;
		$trigger = null;
		$type = null;
		
		if($id) { // Edit node
			if(null == ($model = DAO_DecisionNode::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!Context_TriggerEvent::isWriteableByActor($model->trigger_id, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$type = $model->node_type;
			$trigger_id = $model->trigger_id;
			
			$tpl->assign('id', $id);
			$tpl->assign('model', $model);
			$tpl->assign('trigger_id', $trigger_id);
			
		} elseif(array_key_exists('parent_id', $_REQUEST)) { // Add child node
			@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
			@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			
			if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$tpl->assign('parent_id', $parent_id);
			$tpl->assign('type', $type);
			$tpl->assign('trigger_id', $trigger_id);
			
		} elseif(array_key_exists('trigger_id', $_REQUEST)) { // Add child node
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			
			if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			$tpl->assign('trigger_id', $trigger_id);
		}
		
		if(!isset($trigger) && $trigger_id) {
			if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		$tpl->assign('trigger', $trigger);
		
		$event = null;
		
		if($trigger)
			if(false == ($event = $trigger->getEvent()))
				DevblocksPlatform::dieWithHttpError(null, 404);
		
		$tpl->assign('event', $event);
		
		// Template
		switch($type) {
			case 'subroutine':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/subroutine.tpl');
				break;
			
			case 'switch':
				// Library
				if(!$id) {
					$library_sections = [
						'behavior_switch:' . $event->id,
						'behavior_switch',
					];
					
					$packages = DAO_PackageLibrary::getByPoint($library_sections);
					$tpl->assign('packages', $packages);
				}
				
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/switch.tpl');
				break;
			
			case 'loop':
				if($event) {
					// Action labels
					$labels = $event->getLabels($trigger);
					$tpl->assign('labels', $labels);
					
					$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
					$tpl->assign('placeholders', $placeholders);
					
					$values = $event->getValues();
					$tpl->assign('values', $values);
					
					// Library
					if(!$id) {
						$library_sections = [
							'behavior_loop:' . $event->id,
							'behavior_loop',
						];
						
						$packages = DAO_PackageLibrary::getByPoint($library_sections);
						$tpl->assign('packages', $packages);
					}
				}
				
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/loop.tpl');
				break;
			
			case 'outcome':
				if($event) {
					$conditions = $event->getConditions($trigger);
					$tpl->assign('conditions', $conditions);
					
					// [TODO] Cache this
					$map = array();
					array_walk($conditions, function($v, $k) use (&$map) {
						if(is_array($v) && isset($v['label']))
							$map[$k] = $v['label'];
					});
					
					$conditions_menu = Extension_DevblocksContext::getPlaceholderTree($map);
					$tpl->assign('conditions_menu', $conditions_menu);
					
					// Action labels
					$labels = $event->getLabels($trigger);
					$tpl->assign('labels', $labels);
					
					$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
					$tpl->assign('placeholders', $placeholders);
					
					$values = $event->getValues();
					$tpl->assign('values', $values);
				}
				
				// Nonce scope
				$nonce = uniqid();
				$tpl->assign('nonce', $nonce);
				
				// Template
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/outcome.tpl');
				break;
			
			case 'action':
				if($event) {
					$actions = $event->getActions($trigger);
					$tpl->assign('actions', $actions);
					
					$map = [];
					array_walk($actions, function($v, $k) use (&$map) {
						if(array_key_exists('label', $v))
							$map[$k] = $v['label'];
						
						if(array_key_exists('scope', $v) && 'global' == $v['scope'])
							$map[$k] = '(Common) ' . $map[$k];
					});
					
					$actions_menu = Extension_DevblocksContext::getPlaceholderTree($map);
					$tpl->assign('actions_menu', $actions_menu);
					
					// Action labels
					$labels = $event->getLabels($trigger);
					$tpl->assign('labels', $labels);
					
					$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
					$tpl->assign('placeholders', $placeholders);
					
					$values = $event->getValues();
					$tpl->assign('values', $values);
				}
				
				// Workers
				$tpl->assign('workers', DAO_Worker::getAll());
				
				// Nonce scope
				$nonce = uniqid();
				$tpl->assign('nonce', $nonce);
				
				// Library
				if(!$id) {
					$library_sections = [
						'behavior_action:' . $event->id,
						'behavior_action',
					];
					
					$packages = DAO_PackageLibrary::getByPoint($library_sections);
					$tpl->assign('packages', $packages);
				}
				
				// Template
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/action.tpl');
				break;
			
			default:
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
	}
	
	private function _profileAction_saveDecisionPopup() {
		@$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer', 0);
		@$title = DevblocksPlatform::importGPC($_POST['title'],'string', '');
		@$status_id = DevblocksPlatform::importGPC($_POST['status_id'],'integer', 0);
		@$package_uri = DevblocksPlatform::importGPC($_POST['package'], 'string', '');
		
		$mode = 'build';
		
		if(!$id && $package_uri)
			$mode = 'library';
		
		switch($mode) {
			case 'library':
				@$behavior_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
				@$parent_id = DevblocksPlatform::importGPC($_POST['parent_id'],'integer', 0);
				@$type = DevblocksPlatform::importGPC($_POST['type'],'string', '');
				@$prompts = DevblocksPlatform::importGPC($_POST['prompts'], 'array', []);
				
				header('Content-Type: application/json; charset=utf-8');
				
				try {
					if(empty($package_uri))
						throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
					
					if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
						throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
					
					// Verify the event can be owned by this context
					
					if(false == ($behavior = DAO_TriggerEvent::get($behavior_id))) {
						throw new Exception_DevblocksAjaxValidationError("The destination behavior doesn't exist.");
					}
					
					if(false == ($bot = $behavior->getBot())) {
						throw new Exception_DevblocksAjaxValidationError("The destination bot doesn't exist.");
					}
					
					if(!in_array($type, ['action', 'loop', 'switch']))
						throw new Exception_DevblocksAjaxValidationError(sprintf("'%s' is not supported.", $type));
					
					$point_prefix = 'behavior_' . $type;
					
					if($package->point != $point_prefix && $package->point != $point_prefix . ':' . $behavior->event_point)
						throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
					
					// Does the worker have access to this bot?
					if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.bot.update')
						|| !CerberusContexts::isOwnableBy($bot->owner_context, $bot->owner_context_id, $active_worker)
					)
						throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
					
					$package_json = $package->getPackageJson();
					$records_created = [];
					
					$prompts['behavior_id'] = $behavior_id;
					$prompts['parent_id'] = $parent_id;
					
					CerberusApplication::packages()->import($package_json, $prompts, $records_created);
					
					if(!array_key_exists(CerberusContexts::CONTEXT_BEHAVIOR_NODE, $records_created))
						throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
					
					$new_node = reset($records_created[CerberusContexts::CONTEXT_BEHAVIOR_NODE]);
					
					echo json_encode([
						'status' => true,
						'id' => $new_node['id'],
						'label' => $new_node['label'],
						'type' => $new_node['type'],
					]);
					
				} catch(Exception_DevblocksAjaxValidationError $e) {
					echo json_encode([
						'status' => false,
						'error' => $e->getMessage(),
					]);
					
				} catch(Exception_DevblocksValidationError $e) {
					echo json_encode([
						'status' => false,
						'error' => $e->getMessage(),
					]);
					
				} catch (Exception $e) {
					error_log($e->getMessage());
					
					echo json_encode([
						'status' => false,
						'error' => "An unexpected error occurred.",
					]);
				}
				break;
			
			case 'build':
				
				if(!empty($id)) { // Edit
					if(null != ($model = DAO_DecisionNode::get($id))) {
						$type = $model->node_type;
						$trigger_id = $model->trigger_id;
						
						// Security
						
						if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
							return false;
						
						if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
							return false;
						
						DAO_DecisionNode::update($id, array(
							DAO_DecisionNode::TITLE => $title,
							DAO_DecisionNode::STATUS_ID => $status_id,
						));
					}
					
				} elseif(isset($_POST['parent_id'])) { // Create
					@$parent_id = DevblocksPlatform::importGPC($_POST['parent_id'],'integer', 0);
					@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
					@$type = DevblocksPlatform::importGPC($_POST['type'],'string', '');
					
					// Security
					
					if(!empty($trigger_id)) {
						if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
							return false;
						
						if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
							return false;
					}
					
					$pos = $trigger->getNextPosByParent($parent_id);
					
					$id = DAO_DecisionNode::create(array(
						DAO_DecisionNode::TITLE => $title,
						DAO_DecisionNode::PARENT_ID => $parent_id,
						DAO_DecisionNode::TRIGGER_ID => $trigger_id,
						DAO_DecisionNode::NODE_TYPE => $type,
						DAO_DecisionNode::STATUS_ID => $status_id,
						DAO_DecisionNode::POS => $pos,
						DAO_DecisionNode::PARAMS_JSON => '',
					));
					
					if(false == $id)
						return false;
				}
				
				// Type-specific properties
				switch($type) {
					case 'subroutine':
						// Nothing
						break;
					
					case 'switch':
						// Nothing
						break;
					
					case 'loop':
						@$params = DevblocksPlatform::importGPC($_POST['params'],'array',array());
						DAO_DecisionNode::update($id, array(
							DAO_DecisionNode::PARAMS_JSON => json_encode($params),
						));
						break;
					
					case 'outcome':
						@$nodes = DevblocksPlatform::importGPC($_POST['nodes'],'array',array());
						
						$groups = [];
						$group_key = null;
						
						foreach($nodes as $k) {
							switch($k) {
								case 'any':
								case 'all':
									$groups[] = array(
										'any' => ($k=='any'?1:0),
										'conditions' => array(),
									);
									end($groups);
									$group_key = key($groups);
									break;
								
								default:
									if(!is_numeric($k))
										continue 2;
									
									$condition = DevblocksPlatform::importGPC($_POST['condition'.$k],'array',array());
									$groups[$group_key]['conditions'][] = $condition;
									break;
							}
						}
						
						DAO_DecisionNode::update($id, array(
							DAO_DecisionNode::PARAMS_JSON => json_encode(array('groups'=>$groups)),
						));
						break;
					
					case 'action':
						@$action_ids = DevblocksPlatform::importGPC($_POST['actions'],'array',array());
						$params = [];
						$params['actions'] = $this->_parseActions($action_ids, $_POST);
						DAO_DecisionNode::update($id, array(
							DAO_DecisionNode::PARAMS_JSON => json_encode($params),
						));
						break;
				}
				break;
		}
		
	}
	
	private function _profileAction_reparentNode() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$child_id = DevblocksPlatform::importGPC($_POST['child_id'],'integer', 0);
		@$parent_id = DevblocksPlatform::importGPC($_POST['parent_id'],'integer', 0);
		
		// The parent node must exist
		if($parent_id && null == ($parent_node = DAO_DecisionNode::get($parent_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// The child node must exist
		if(null == ($child_node = DAO_DecisionNode::get($child_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// The trigger must exist
		if(null == ($trigger = DAO_TriggerEvent::get($child_node->trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// The worker must be able to modify the trigger
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// The parent and children are part of the same trigger
		$nodes = DAO_DecisionNode::getByTriggerParent($trigger->id, $parent_id);
		
		// Remove current node if exists
		unset($nodes[$child_node->id]);
		
		$pos = 0;
		
		// Insert child at top of parent
		DAO_DecisionNode::update($child_id, [
			DAO_DecisionNode::PARENT_ID => $parent_id,
			DAO_DecisionNode::POS => $pos++,
		]);
		
		// Renumber children
		foreach(array_keys($nodes) as $node_id) {
			DAO_DecisionNode::update($node_id, [
				DAO_DecisionNode::PARENT_ID => $parent_id,
				DAO_DecisionNode::POS => $pos++,
			]);
		}
		
		DevblocksPlatform::exit();
	}
	
	private function _profileAction_getTriggerEventParams() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string', '');
		
		if(empty($id))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($ext = Extension_DevblocksEvent::get($id))) /* @var $ext Extension_DevblocksEvent */
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$ext->renderEventParams(null);
	}
	
	private function _profileAction_getTriggerVariableParams() {
		$tpl = DevblocksPlatform::services()->template();
		
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
		
		$tpl->assign('seq', uniqid());
		
		$variable_types = DAO_TriggerEvent::getVariableTypes();
		$tpl->assign('variable_types', $variable_types);
		
		switch($type) {
			case Model_CustomField::TYPE_LINK:
				$context_mfts = Extension_DevblocksContext::getAll(false, ['va_variable']);
				$tpl->assign('context_mfts', $context_mfts);
				break;
		}
		
		// New variable
		$var = [
			'key' => '',
			'type' => $type,
			'label' => 'New Variable',
			'is_private' => 1,
			'params' => [],
		];
		$tpl->assign('var', $var);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/trigger_variable.tpl');
	}
	
	private function _profileAction_renderSimulatorPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('trigger', $trigger);
		
		if(null == ($ext_event = Extension_DevblocksEvent::get($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$event_model = $ext_event->generateSampleEventModel($trigger, $context_id);
		$ext_event->setEvent($event_model, $trigger);
		
		if($event_model instanceof Model_DevblocksEvent) {
			$event_params_json = json_encode($event_model->params);
			$tpl->assign('event_params_json', $event_params_json);
		}
		
		$tpl->assign('ext_event', $ext_event);
		$tpl->assign('event_model', $event_model);
		
		$ext_event->getLabels($trigger);
		$values = $ext_event->getValues();
		$dict = new DevblocksDictionaryDelegate($values);
		
		$conditions = $ext_event->getConditions($trigger, false);
		
		$dictionary = [];
		
		// Find all nodes on the behavior
		$nodes = DAO_DecisionNode::getByTriggerParent($trigger->id);
		
		// Filter to outcomes
		$outcomes = array_filter($nodes, function($node) {
			if($node->node_type == 'outcome')
				return true;
			
			return false;
		});
		
		// Build a list of the tokens used in outcomes, and only show those
		if(is_array($outcomes))
			foreach($outcomes as $outcome) {
				if(isset($outcome->params['groups']))
					foreach($outcome->params['groups'] as $group) {
						if(isset($group['conditions']))
							foreach($group['conditions'] as $condition_obj) {
								if(null == (@$condition_token = $condition_obj['condition']))
									continue;
								
								if(null == (@$condition = $conditions[$condition_token]))
									continue;
								
								if(empty($condition['label']) || empty($condition['type']))
									continue;
								
								// [TODO] List variables
								// [TODO] Some types have options, like picklists
								if(!isset($dictionary[$condition_token])) {
									$dictionary[$condition_token] = array(
										'label' => $condition['label'],
										'type' => $condition['type'],
										'value' => $dict->$condition_token,
									);
								}
							}
					}
			}
		
		$tpl->assign('dictionary', $dictionary);
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/simulate.tpl');
	}
	
	private function _profileAction_runSimulator() {
		$tpl = DevblocksPlatform::services()->template();
		$logger = DevblocksPlatform::services()->log('Bot');
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
		@$event_params_json = DevblocksPlatform::importGPC($_POST['event_params_json'],'string', '');
		@$custom_values = DevblocksPlatform::importGPC($_POST['values'],'array', []);
		
		$logger->setLogLevel(6);
		
		ob_start();
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('trigger_id', $trigger_id);
		$tpl->assign('trigger', $trigger);
		
		if(null == ($ext_event = Extension_DevblocksEvent::get($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Set the base event scope
		
		switch($trigger->event_point) {
			case Event_MailReceivedByApp::ID:
				$event_model = $ext_event->generateSampleEventModel($trigger, 0);
				break;
			
			default:
				$event_model = new Model_DevblocksEvent();
				$event_model->id = $trigger->event_point;
				$event_model_params = json_decode($event_params_json, true);
				$event_model->params = is_array($event_model_params) ? $event_model_params : [];
				break;
		}
		
		$ext_event->setEvent($event_model, $trigger);
		
		$tpl->assign('event', $ext_event);
		
		// Values
		
		$values = $ext_event->getValues();
		$values = array_merge($values, $custom_values);
		
		// Get conditions
		
		$conditions = $ext_event->getConditions($trigger, false);
		
		// Sanitize values
		
		if(is_array($values))
			foreach($values as $k => $v) {
				if(
				((isset($conditions[$k]) && $conditions[$k]['type'] == Model_CustomField::TYPE_DATE)
					|| $k == '_current_time')
				) {
					if(!is_numeric($v))
						$values[$k] = strtotime($v);
				}
			}
		
		// Dictionary
		
		$dict = new DevblocksDictionaryDelegate($values);
		
		// Format custom values
		
		if(is_array($trigger->variables))
			foreach($trigger->variables as $var_key => $var) {
				if(!empty($var['is_private']))
					continue;
				
				if(!array_key_exists($var_key, $custom_values))
					continue;
				
				try {
					$custom_value = $trigger->formatVariable($var, $custom_values[$var_key], $dict);
					$dict->set($var_key, $custom_value);
					
				} catch(Exception $e) {}
			}
		
		// Behavior data
		
		$behavior_data = $trigger->getDecisionTreeData();
		$tpl->assign('behavior_data', $behavior_data);
		
		$result = $trigger->runDecisionTree($dict, true, $ext_event);
		$tpl->assign('behavior_path', $result['path']);
		
		if($dict->exists('__simulator_output'))
			$tpl->assign('simulator_output', $dict->__simulator_output);
		
		$logger->setLogLevel(0);
		
		$conditions_output = ob_get_contents();
		
		ob_end_clean();
		
		$conditions_output = preg_replace("/^\[INFO\] \[Bot\] /m", '', strip_tags($conditions_output));
		$tpl->assign('conditions_output', trim($conditions_output));
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/simulator/results.tpl');
	}
	
	private function _profileAction_renderExportPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$node_id = DevblocksPlatform::importGPC($_REQUEST['node_id'],'integer', 0);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$behavior_json = $trigger->exportToJson($node_id);
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('behavior_json', $behavior_json);
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_export.tpl');
	}
	
	private function _profileAction_renderDecisionReorderPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$trigger_id = 0;
		
		if($id) {
			if(false == ($node = DAO_DecisionNode::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$trigger_id = $node->trigger_id;
			$tpl->assign('node', $node);
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		}
		
		if(!$trigger_id)
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$tpl->assign('trigger', $trigger);
		
		if (!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$children = DAO_DecisionNode::getByTriggerParent($trigger_id, $id);
		$tpl->assign('children', $children);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_reorder.tpl');
	}
	
	private function _profileAction_saveDecisionReorderPopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
		@$node_id = DevblocksPlatform::importGPC($_POST['id'],'integer', 0);
		@$child_ids = DevblocksPlatform::importGPC($_POST['child_id'],'array', []);
		
		$trigger = null;
		
		if(!$trigger_id && !$node_id) {
			DevblocksPlatform::dieWithHttpError('', 403);
			
		} else if(!$trigger_id && $node_id) {
			if(false == ($node = DAO_DecisionNode::get($node_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(false == ($trigger = DAO_TriggerEvent::get($node->trigger_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
		} else {
			if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null,403);
		
		if(false == ($children = DAO_DecisionNode::getIds($child_ids)))
			DevblocksPlatform::exit();
		
		if(!empty($child_ids))
			foreach($child_ids as $pos => $child_id) {
				if(false == ($child = $children[$child_id]))
					continue;
				
				if($child->trigger_id != $trigger->id)
					continue;
				
				DAO_DecisionNode::update($child_id, array(
					DAO_DecisionNode::POS => $pos,
				));
			}
	}
	
	private function _profileAction_saveDecisionDeletePopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer', 0);
		
		if($id) {
			if(false == ($node = DAO_DecisionNode::get($id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(false == ($trigger = DAO_TriggerEvent::get($node->trigger_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!Context_TriggerEvent::isDeletableByActor($trigger, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			// Load the trigger's tree so we can delete all children from this node
			$data = $trigger->getDecisionTreeData();
			$depths = $data['depths'];
			
			$ids_to_delete = [];
			
			$found = false;
			foreach($depths as $node_id => $depth) {
				if($node_id == $id) {
					$found = true;
					$ids_to_delete[] = $id;
					continue;
				}
				
				if(!$found)
					continue;
				
				// Continue deleting (queuing IDs) while depth > origin
				if($depth > $depths[$id]) {
					$ids_to_delete[] = $node_id;
				} else {
					$found = false;
				}
			}
			
			DAO_DecisionNode::delete($ids_to_delete);
			
		} elseif(array_key_exists('trigger_id', $_POST)) {
			@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
			
			if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(Context_TriggerEvent::isDeletableByActor($trigger, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_BEHAVIOR, $trigger->id, $trigger->title);
			
			DAO_TriggerEvent::delete($trigger_id);
		}
	}
	
	private function _profileAction_getActionParams() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$action_uid = DevblocksPlatform::importGPC($_POST['action_uid'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_POST['seq'],'integer', 0);
		@$nonce = DevblocksPlatform::importGPC($_POST['nonce'],'string', '');
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($event = Extension_DevblocksEvent::get($trigger->event_point, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		/* @var $event Extension_DevblocksEvent */
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
		$tpl->assign('nonce', $nonce);
		
		$event->renderAction($action_uid, $trigger, null, $seq);
	}
	
	private function _profileAction_getConditionParams() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$condition = DevblocksPlatform::importGPC($_POST['condition'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_POST['seq'],'integer', 0);
		@$nonce = DevblocksPlatform::importGPC($_POST['nonce'],'string', '');
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(null == ($event = Extension_DevblocksEvent::get($trigger->event_point, true)))
			return; /* @var $event Extension_DevblocksEvent */
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
		$tpl->assign('nonce', $nonce);
		
		$event->renderCondition($condition, $trigger, null, $seq);
	}
	
	private function _profileAction_duplicateNode() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer', 0);
		
		if(false == ($node = DAO_DecisionNode::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($trigger = DAO_TriggerEvent::get($node->trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$data = $trigger->getDecisionTreeData();
		$tree =& $data['tree'];
		$recursive_duplicate = null;
		
		$recursive_duplicate = function($node_id, $new_parent_id) use ($tree, &$recursive_duplicate) {
			$new_node_id = DAO_DecisionNode::duplicate($node_id, $new_parent_id);
			
			// Recurse into children
			if(is_array($tree[$node_id]))
				foreach($tree[$node_id] as $child_id)
					$recursive_duplicate($child_id, $new_node_id);
		};
		
		$recursive_duplicate($id, $node->parent_id);
		
		DAO_DecisionNode::clearCache();
	}
	
	private function _profileAction_renderImportPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$node_id = DevblocksPlatform::importGPC($_REQUEST['node_id'],'integer', 0);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isWriteableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('node_id', $node_id);
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_import.tpl');
	}
	
	private function _profileAction_getParams() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		@$name_prefix = DevblocksPlatform::importGPC($_REQUEST['name_prefix'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		
		$tpl->assign('namePrefix', $name_prefix);
		
		if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isReadableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
			
		$tpl->assign('macro_params', $trigger->variables);
		$tpl->display('devblocks:cerberusweb.core::events/_action_behavior_params.tpl');
	}
	
	private function _profileAction_getParamsAsJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		
		header('Content-Type: text/plain; charset=utf-8');
		
		if(false == ($trigger = DAO_TriggerEvent::get($trigger_id))) {
			echo '{}';
			DevblocksPlatform::exit();
		}
		
		if(!Context_TriggerEvent::isReadableByActor($trigger, $active_worker)) {
			echo '{}';
			DevblocksPlatform::exit();
		}
		
		echo "{% set json = {} %}\n";
		
		if(is_array($trigger->variables))
		foreach($trigger->variables as $var) {
			if($var['is_private'])
				continue;
			
			echo sprintf("{%% set json = dict_set(json, '%s', '') %%}\n", $var['key']);
		}
		
		echo "{{json|json_encode|json_pretty}}";
	}
	
	// Convert [nested][string] $path to array
	private function _getValueFromNestedArray($path, array $array) {
		$keys = explode('][', trim($path, '[]'));
		
		$ptr =& $array;
		
		while($key = array_shift($keys)) {
			$ptr =& $ptr[$key];
		}
		
		return $ptr;
	}
	
	private function _profileAction_testDecisionEventSnippets() {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$prefix = DevblocksPlatform::importGPC($_POST['prefix'],'string','');
		@$response_format = DevblocksPlatform::importGPC($_POST['format'],'string','');
		@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer',0);
		
		@$placeholders_yaml = DevblocksPlatform::importVar($_POST[$prefix]['placeholder_simulator_yaml'], 'string', '');
		$placeholders = DevblocksPlatform::services()->string()->yamlParse($placeholders_yaml, 0);
		
		$content = '';
		
		if(array_key_exists('field', $_POST) && is_array($_POST['field'])) {
			@$fields = DevblocksPlatform::importGPC($_POST['field'],'array',[]);
			
			if(is_array($fields))
				foreach($fields as $field) {
					@$append = $this->_getValueFromNestedArray($field, $_POST[$prefix]);
					@$append = DevblocksPlatform::importGPC($_POST[$prefix][$field],'string','');
					$content .= !empty($append) ? ('[' . $field . ']: ' . PHP_EOL . $append . PHP_EOL . PHP_EOL) : '';
				}
			
		} else {
			@$field = DevblocksPlatform::importGPC($_POST['field'],'string','');
			@$content = $this->_getValueFromNestedArray($field, $_POST[$prefix]);
		}
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_TriggerEvent::isReadableByActor($trigger, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$event = $trigger->getEvent();
		$event_model = $event->generateSampleEventModel($trigger);
		$event->setEvent($event_model, $trigger);
		$values = $event->getValues();
		
		if(is_array($placeholders))
			foreach($placeholders as $placeholder_key => $placeholder_value) {
				$values[$placeholder_key] = $placeholder_value;
			}
		
		$success = false;
		$output = '';
		
		if(isset($values)) {
			// Try to build the template
			if(!is_string($content) || false === (@$out = $tpl_builder->build($content, $values))) {
				// If we failed, show the compile errors
				$errors = $tpl_builder->getErrors();
				$success = false;
				$output = @array_shift($errors);
				
			} else {
				// If successful, return the parsed template
				$success = true;
				$output = $out;
				
				if(isset($_POST['is_editor'])) {
					@$is_editor = DevblocksPlatform::importGPC($_POST['is_editor'],'string','');
					@$format = DevblocksPlatform::importGPC($_POST[$prefix][$is_editor],'string','');
					
					switch($format) {
						case 'parsedown':
							if(false != ($output = DevblocksPlatform::parseMarkdown($output))) {
								
								// HTML template
								
								@$html_template_id = DevblocksPlatform::importGPC($_POST[$prefix]['html_template_id'],'integer',0);
								$html_template = null;
								
								// Key mapping
								
								@$_group_key = DevblocksPlatform::importGPC($_POST['_group_key'],'string','');
								@$_group_id = intval($values[$_group_key]);
								
								@$_bucket_key = DevblocksPlatform::importGPC($_POST['_bucket_key'],'string','');
								@$_bucket_id = intval($values[$_bucket_key]);
								
								// Try the given HTML template
								if($html_template_id) {
									$html_template = DAO_MailHtmlTemplate::get($html_template_id);
								}
								
								// Cascade to group/bucket
								if($_group_id && !$html_template && false != ($_group = DAO_Group::get($_group_id))) {
									$html_template = $_group->getReplyHtmlTemplate($_bucket_id);
								}
								
								if($html_template) {
									$tpl_builder = DevblocksPlatform::services()->templateBuilder();
									@$output = $tpl_builder->build($html_template->content, array('message_body' => $output));
								}
							}
							break;
						
						default:
							// [TODO] Default stylesheet for previews?
							$output = nl2br(DevblocksPlatform::strEscapeHtml($output));
							break;
					}
					
					echo sprintf('<html><head><meta http-equiv="content-type" content="text/html; charset=%s"></head><body style="margin:0;">',
						LANG_CHARSET_CODE
					);
					
					$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
					echo DevblocksPlatform::purifyHTML($output, true, true, [$filter]);
					echo '</body></html>';
					return;
				}
			}
		}
		
		if('json' == $response_format) {
			header('Content-Type: application/json; charset=utf-8');
			
			echo json_encode([
				'status' => $success ? true : false,
				'response' => $output
			]);
			
		} else {
			$tpl->assign('success', $success);
			$tpl->assign('output', $output);
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
		}
	}
};
