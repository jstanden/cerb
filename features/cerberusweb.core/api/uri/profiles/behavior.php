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
	
	// [TODO] Merge with the version on c=internal
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(false == ($behavior = DAO_TriggerEvent::get($id)))
					throw new Exception_DevblocksAjaxValidationError("Record not found.");
				
				if(!Context_TriggerEvent::isWriteableByActor($behavior, $active_worker))
					throw new Exception_DevblocksAjaxValidationError("You don't have permission to delete this record.");
				
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_BEHAVIOR)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
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
	
	function getEventsMenuByBotAction() {
		@$bot_id = DevblocksPlatform::importGPC($_REQUEST['bot_id'], 'integer', 0);
		
		if(false == ($bot = DAO_Bot::get($bot_id))) {
			return;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		
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
	
	function saveBehaviorImportPopupJsonAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$node_id = DevblocksPlatform::importGPC($_REQUEST['node_id'],'integer', 0);
		@$behavior_json = DevblocksPlatform::importGPC($_REQUEST['behavior_json'],'string', null);
		@$configure = DevblocksPlatform::importGPC($_REQUEST['configure'],'array', array());
		$parent = null;
		
		header('Content-Type: application/json');
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id))) {
			echo json_encode([
				'status' => false,
				'error' => 'Invalid behavior.',
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
	
	function viewExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
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
};
