<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
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
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // trigger_event 
		@$id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_BEHAVIOR;

		if(null == ($trigger_event = DAO_TriggerEvent::get($id))) {
			return;
		}
		$tpl->assign('trigger_event', $trigger_event);

		// Context

		if(false == ($context_ext = Extension_DevblocksContext::get($context, true)))
			return;

		// Dictionary
		
		$labels = $values = [];
		CerberusContexts::getContext($context, $trigger_event, $labels, $values, '', true, false);
		$dict = DevblocksDictionaryDelegate::instance($values);
		$tpl->assign('dict', $dict);
	
		// Tab persistence
		
		$point = 'profiles.trigger_event.tab';
		$tpl->assign('point', $point);
		
		if(null == (@$tab_selected = $stack[0])) {
			$tab_selected = $visit->get($point, '');
		}
		$tpl->assign('tab_selected', $tab_selected);
		
		// Properties
		
		$properties = [];
		
		$properties['bot_id'] = array(
			'label' => mb_ucfirst($translate->_('common.bot')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $trigger_event->bot_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_BOT,
			]
		);
		
		$properties['event_point'] = array(
			'label' => mb_ucfirst($translate->_('common.event')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $trigger_event->event_point,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $trigger_event->updated_at,
		);
		
		$properties['is_disabled'] = array(
			'label' => mb_ucfirst($translate->_('common.disabled')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $trigger_event->is_disabled,
		);
	
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_BEHAVIOR, $trigger_event->id)) or [];
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_BEHAVIOR, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_BEHAVIOR, $trigger_event->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_BEHAVIOR => array(
				$trigger_event->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_BEHAVIOR,
						$trigger_event->id,
						array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		$tpl->assign('properties', $properties);
			
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_BEHAVIOR);
		$tpl->assign('tab_manifests', $tab_manifests);

		// Card search buttons
		$search_buttons = $context_ext->getCardSearchButtons($dict, []);
		$tpl->assign('search_buttons', $search_buttons);
				
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/behavior.tpl');
	}
	
	// [TODO] Merge with the version on c=internal
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
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
				@$mode = DevblocksPlatform::importGPC($_REQUEST['mode'], 'string', '');
				
				if($id)
					$mode = 'build';
				
				switch($mode) {
					case 'import':
						@$import_json = DevblocksPlatform::importGPC($_REQUEST['import_json'],'string', '');
						@$bot_id = DevblocksPlatform::importGPC($_REQUEST['bot_id'],'integer', 0);
						@$configure = DevblocksPlatform::importGPC($_REQUEST['configure'],'array', []);
						
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
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'label' => $json['behavior']['title'],
							'view_id' => $view_id,
						));
						return;
						break;
						
					case 'build':
						@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', '');
						@$is_disabled = DevblocksPlatform::importGPC($_REQUEST['is_disabled'],'integer', 0);
						@$is_private = DevblocksPlatform::importGPC($_REQUEST['is_private'],'integer', 0);
						@$priority = DevblocksPlatform::importGPC($_REQUEST['priority'],'integer', 0);
						@$event_params = DevblocksPlatform::importGPC($_REQUEST['event_params'],'array', array());
						@$json = DevblocksPlatform::importGPC($_REQUEST['json'],'integer', 0);
			
						$priority = DevblocksPlatform::intClamp($priority, 1, 99);
						
						// Variables
			
						@$var_idxs = DevblocksPlatform::importGPC($_REQUEST['var'],'array',array());
						@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_key'],'array',array());
						@$var_types = DevblocksPlatform::importGPC($_REQUEST['var_type'],'array',array());
						@$var_labels = DevblocksPlatform::importGPC($_REQUEST['var_label'],'array',array());
						@$var_is_private = DevblocksPlatform::importGPC($_REQUEST['var_is_private'],'array',array());
						
						$variables = array();
						
						if(is_array($var_labels))
						foreach($var_labels as $idx => $v) {
							if(empty($var_labels[$idx]))
								continue;
							
							$var_name = 'var_' . DevblocksPlatform::strAlphaNum(DevblocksPlatform::strToPermalink($v,'_'),'_');
							$key = DevblocksPlatform::strLower(!empty($var_keys[$idx]) ? $var_keys[$idx] : $var_name);
							
							// Variable params
							@$var_idx = $var_idxs[$idx];
							$var_params = isset($_REQUEST['var_params'.$var_idx]) ? DevblocksPlatform::importGPC($_REQUEST['var_params'.$var_idx],'array',array()) : array();
							
							$variables[$key] = array(
								'key' => $key,
								'label' => $v,
								'type' => $var_types[$idx],
								'is_private' => $var_is_private[$idx],
								'params' => $var_params,
							);
						}
						
						// Create behavior
						if(empty($id)) {
							@$bot_id = DevblocksPlatform::importGPC($_REQUEST['bot_id'], 'integer', 0);
							@$event_point = DevblocksPlatform::importGPC($_REQUEST['event_point'],'string', '');
							
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
							
							// Let the event validate the event params
							if(false === $ext->prepareEventParams($behavior, $event_params, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
								
							// Handle deletes
							if(is_array($behavior->variables))
							foreach($behavior->variables as $var => $data) {
								if(!isset($variables[$var])) {
									DAO_DecisionNode::deleteTriggerVar($behavior->id, $var);
								}
							}
							
							$fields = [
								DAO_TriggerEvent::TITLE => $title,
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
						}
						break;
						
					default:
						throw new Exception_DevblocksAjaxValidationError("Choose build or import.");
						break;
				}
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
	
	function showBuilderTabAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();

		if(empty($id))
			return;

		if(false == ($behavior = DAO_TriggerEvent::get($id)))
			return;
			
		if(false == ($event = $behavior->getEvent()))
			return;
		
		if(false == ($va = $behavior->getBot()))
			return;
		
		$tpl->assign('behavior', $behavior);
		$tpl->assign('event', $event->manifest);
		$tpl->assign('va', $va);
		
		$tpl->display('devblocks:cerberusweb.core::internal/bot/behavior/tab.tpl');
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
		$labels = array_column(DevblocksPlatform::objectsToArrays($events), 'name', 'id');
		
		// Remove deprecated events from creation
		unset($labels['event.api.mobile_behavior']);
		unset($labels['event.mail.reply.during.ui.worker']);
		
		$events_menu = Extension_DevblocksContext::getPlaceholderTree($labels);
		
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
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=trigger_event', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.trigger.event.explore.toolbar',
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=trigger_event&id=%d-%s", $row[SearchFields_TriggerEvent::ID], DevblocksPlatform::strToPermalink($row[SearchFields_TriggerEvent::NAME])), true);
				
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
