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

class PageSection_ProfilesBot extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // bot
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_BOT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'getInteractionsMenu':
					return $this->_profileAction_getInteractionsMenu();
				case 'getProactiveInteractions':
					return $this->_profileAction_getProactiveInteractions();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showExportBotPopup':
					return $this->_profileAction_showExportBotPopup();
				case 'sendMessage':
					return $this->_profileAction_sendMessage();
				case 'startInteraction':
					return $this->_profileAction_startInteraction();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		@$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Model
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(false == ($model = DAO_Bot::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Bot::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_BOT, $model->id, $model->name);
				
				DAO_Bot::delete($id);
				
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
						@$owner = DevblocksPlatform::importGPC($_POST['owner'], 'string', '');
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'bot')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						// Owner
					
						$owner_ctx = '';
						@list($owner_ctx, $owner_ctx_id) = explode(':', $owner, 2);
						
						// Make sure we're given a valid ctx
						
						switch($owner_ctx) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								break;
								
							default:
								$owner_ctx = null;
						}
						
						if(empty($owner_ctx))
							throw new Exception_DevblocksAjaxValidationError("A valid 'Owner' is required.");
						
						// Does the worker have access to this bot?
						if(
							!$active_worker->hasPriv(sprintf("contexts.%s.create", CerberusContexts::CONTEXT_BOT)) 
							|| !CerberusContexts::isOwnableBy($owner_ctx, $owner_ctx_id, $active_worker)
						) {
							throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
						}
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						$prompts['prompt_owner_context'] = $owner_ctx;
						$prompts['prompt_owner_context_id'] = $owner_ctx_id;
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_Bot::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_bot = reset($records_created[Context_Bot::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BOT, $new_bot['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_bot['id'],
							'label' => $new_bot['label'],
							'view_id' => $view_id,
						]);
						return;
						break;
						
					case 'build':
						@$name = DevblocksPlatform::importGPC($_POST['name'], 'string', '');
						@$at_mention_name = DevblocksPlatform::importGPC($_POST['at_mention_name'], 'string', '');
						@$owner = DevblocksPlatform::importGPC($_POST['owner'], 'string', '');
						@$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'], 'integer', 0);
						@$allowed_events = DevblocksPlatform::importGPC($_POST['allowed_events'], 'string', '');
						@$itemized_events = DevblocksPlatform::importGPC($_POST['itemized_events'], 'array', array());
						@$allowed_actions = DevblocksPlatform::importGPC($_POST['allowed_actions'], 'string', '');
						@$itemized_actions = DevblocksPlatform::importGPC($_POST['itemized_actions'], 'array', array());
						@$config_json = DevblocksPlatform::importGPC($_POST['config_json'], 'string', '');
						
						$is_disabled = DevblocksPlatform::intClamp($is_disabled, 0, 1);
						
						// Owner
					
						$owner_ctx = '';
						@list($owner_ctx, $owner_ctx_id) = explode(':', $owner, 2);
						
						// Make sure we're given a valid ctx
						
						switch($owner_ctx) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_ROLE:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_WORKER:
								break;
								
							default:
								$owner_ctx = null;
						}
						
						if(empty($owner_ctx))
							throw new Exception_DevblocksAjaxValidationError("A valid 'Owner' is required.");
						
						// Permissions
						
						$params = array(
							'config' => json_decode($config_json, true),
							'events' => array(
								'mode' => $allowed_events,
								'items' => $itemized_events,
							),
							'actions' => array(
								'mode' => $allowed_actions,
								'items' => $itemized_actions,
							),
						);
						
						// Create or update
						
						if(empty($id)) { // New
							if(!$active_worker->is_superuser)
								throw new Exception_DevblocksAjaxValidationError("Only admins can create new bots.");
							
							$fields = array(
								DAO_Bot::CREATED_AT => time(),
								DAO_Bot::UPDATED_AT => time(),
								DAO_Bot::NAME => $name,
								DAO_Bot::AT_MENTION_NAME => $at_mention_name,
								DAO_Bot::IS_DISABLED => $is_disabled,
								DAO_Bot::OWNER_CONTEXT => $owner_ctx,
								DAO_Bot::OWNER_CONTEXT_ID => $owner_ctx_id,
								DAO_Bot::PARAMS_JSON => json_encode($params),
							);
							
							$error = null;
							
							if(!DAO_Bot::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_Bot::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(false == ($id = DAO_Bot::create($fields)))
								throw new Exception_DevblocksAjaxValidationError("Failed to create a new record.");
							
							DAO_Bot::onUpdateByActor($active_worker, $fields, $id);
							
							if(!empty($view_id) && !empty($id))
								C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_BOT, $id);
							
						} else { // Edit
							if(!$active_worker->is_superuser)
								throw new Exception_DevblocksAjaxValidationError("You do not have permission to modify this record.");
							
							$fields = array(
								DAO_Bot::UPDATED_AT => time(),
								DAO_Bot::NAME => $name,
								DAO_Bot::AT_MENTION_NAME => $at_mention_name,
								DAO_Bot::IS_DISABLED => $is_disabled,
								DAO_Bot::OWNER_CONTEXT => $owner_ctx,
								DAO_Bot::OWNER_CONTEXT_ID => $owner_ctx_id,
								DAO_Bot::PARAMS_JSON => json_encode($params),
							);
							
							if(!DAO_Bot::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_Bot::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_Bot::update($id, $fields);
							DAO_Bot::onUpdateByActor($active_worker, $fields, $id);
						}
			
						if($id) {
							// Custom field saves
							@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
							if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_BOT, $id, $field_ids, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							// Avatar image
							@$avatar_image = DevblocksPlatform::importGPC($_POST['avatar_image'], 'string', '');
							DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_BOT, $id, $avatar_image);
						}
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'label' => $name,
							'view_id' => $view_id,
						));
						return;
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
	
	private function _profileAction_getProactiveInteractions() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		$results = DAO_BotInteractionProactive::getByWorker($active_worker->id, 2);
		
		if(is_array($results) && !empty($results)) {
			$result = array_shift($results);
			
			echo json_encode([
				'behavior_id' => $result['behavior_id'],
				'interaction' => $result['interaction'],
				'interaction_params' => json_decode($result['interaction_params_json'], true),
				'finished' => empty($results),
			]);
			
			// Clear the proactive interaction record
			DAO_BotInteractionProactive::delete($result['id'], $active_worker->id);
			
		} else {
			echo json_encode(false);
		}
	}
	
	private function _profileAction_getInteractionsMenu() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		//$url_writer = DevblocksPlatform::services()->url();
		
		// [TODO] Phase these out by 10.0
		$legacy_interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('global', [], $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($legacy_interactions);
		
		/*
		$interactions = DAO_BotInteraction::getByPoint('worker.global');
		
		// [TODO] Handle this in a more reusable way
		foreach($interactions as $interaction) {
			if(!array_key_exists($interaction->bot_id, $interactions_menu)) {
				if(false == ($bot = DAO_Bot::get($interaction->bot_id)))
					continue;
				
				$bot_menu = new DevblocksMenuItemPlaceholder();
				$bot_menu->label = $bot->name;
				$bot_menu->image = $url_writer->write(sprintf('c=avatars&context=bot&context_id=%d', $bot->id)) . '?v=' . $bot->updated_at;
				$bot_menu->children = [];
				
				$interactions_menu[$interaction->bot_id] = $bot_menu;
			}
			
			$item_behavior = new DevblocksMenuItemPlaceholder();
			$item_behavior->key = $interaction->id;
			$item_behavior->label = $interaction->name;
			$item_behavior->interaction_id = $interaction->id;
			$item_behavior->interaction = 'worker.global';
			$item_behavior->params = [];
			
			$interactions_menu[$interaction->bot_id]->children[] = $item_behavior;
		}
		*/
		
		$tpl->assign('interactions_menu', $interactions_menu);
		$tpl->display('devblocks:cerberusweb.core::console/bot_interactions_menu.tpl');
	}
	
	// This figures out if we're using modern or legacy interactions
	private function _profileAction_startInteraction() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$interaction_id = DevblocksPlatform::importGPC($_POST['interaction_id'], 'integer', 0);
		@$interaction_behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'], 'integer', 0);
		
		// Modern
//		if($interaction_id && !$interaction_behavior_id) {
//			$this->_startBotInteractionAsAutomation();
			
			// Legacy
//		} else if(!$interaction_id && $interaction_behavior_id) {
			$this->_startBotInteractionAsBehavior();
//		}
	}
	
	/**
	 * @deprecated
	 */
	private function _startBotInteractionAsBehavior() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$interaction = DevblocksPlatform::importGPC($_POST['interaction'], 'string', '');
		@$interaction_behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'], 'integer', 0);
		@$browser = DevblocksPlatform::importGPC($_POST['browser'], 'array', []);
		@$interaction_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		@$layer = DevblocksPlatform::importGPC($_POST['layer'], 'string', null);
		
		if(
			!$interaction_behavior_id
			|| false == ($interaction_behavior = DAO_TriggerEvent::get($interaction_behavior_id))
			|| $interaction_behavior->event_point != Event_NewInteractionChatWorker::ID
		) {
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		// Start the session using the behavior
		
		$actions = [];
		
		$client_ip = DevblocksPlatform::getClientIp();
		$client_platform = '';
		$client_browser = '';
		$client_browser_version = '';
		$client_url = @$browser['url'] ?: '';
		$client_time = @$browser['time'] ?: '';
		
		if(false !== (@$client_user_agent_parts = DevblocksPlatform::getClientUserAgent())) {
			$client_platform = @$client_user_agent_parts['platform'] ?: '';
			$client_browser = @$client_user_agent_parts['browser'] ?: '';
			$client_browser_version = @$client_user_agent_parts['version'] ?: '';
		}
		
		$event_model = new Model_DevblocksEvent(
			Event_NewInteractionChatWorker::ID,
			array(
				'worker_id' => $active_worker->id,
				'interaction' => $interaction,
				'interaction_params' => $interaction_params,
				'client_browser' => $client_browser,
				'client_browser_version' => $client_browser_version,
				'client_ip' => $client_ip,
				'client_platform' => $client_platform,
				'client_time' => $client_time,
				'client_url' => $client_url,
				'actions' => &$actions,
			)
		);
		
		if(false == ($event = $interaction_behavior->getEvent()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$event->setEvent($event_model, $interaction_behavior);
		
		$values = $event->getValues();
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$interaction_behavior->runDecisionTree($dict, false, $event);
		
		$behavior_id = null;
		$bot_name = null;
		$dict = [];
		
		foreach($actions as $action) {
			switch($action['_action']) {
				case 'behavior.switch':
					if(isset($action['behavior_id'])) {
						@$behavior_id = $action['behavior_id'];
						@$variables = $action['behavior_variables'];
						
						if(is_array($variables))
							foreach($variables as $k => $v) {
								$dict[$k] = $v;
							}
					}
					break;
				
				case 'bot.name':
					if(false != (@$name = $action['name']))
						$bot_name = $name;
					break;
			}
		}
		
		if(
			!$behavior_id
			|| false == ($behavior = DAO_TriggerEvent::get($behavior_id))
			|| $behavior->event_point != Event_NewMessageChatWorker::ID
		)
			return;
		
		$bot = $behavior->getBot();
		
		if(empty($bot_name))
			$bot_name = $bot->name;
		
		$url_writer = DevblocksPlatform::services()->url();
		$bot_image_url = $url_writer->write(sprintf("c=avatars&w=bot&id=%d", $bot->id)) . '?v=' . $bot->updated_at;
		
		$session_data = [
			'actor' => ['context' => CerberusContexts::CONTEXT_WORKER, 'id' => $active_worker->id],
			'bot_name' => $bot_name,
			'bot_image' => $bot_image_url,
			'behavior_id' => $behavior->id,
			'behaviors' => [
				$behavior->id => [
					'dict' => $dict,
				]
			],
			'interaction' => $interaction,
			'interaction_params' => $interaction_params,
			'client_browser' => $client_browser,
			'client_browser_version' => $client_browser_version,
			'client_ip' => $client_ip,
			'client_platform' => $client_platform,
			'client_time' => $client_time,
			'client_url' => $client_url,
		];
		
		$session_id = DAO_BotSession::create([
			DAO_BotSession::SESSION_DATA => json_encode($session_data),
			DAO_BotSession::UPDATED_AT => time(),
		]);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('bot', $bot);
		$tpl->assign('bot_name', $bot_name);
		$tpl->assign('bot_image_url', $bot_image_url);
		$tpl->assign('session_id', $session_id);
		$tpl->assign('layer', $layer);
		
		$tpl->display('devblocks:cerberusweb.core::console/window.tpl');
	}
	
	private function _profileAction_sendMessage() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$session_id = DevblocksPlatform::importGPC($_POST['session_id'], 'string', '');
		
		// Load the session
		if(false == ($bot_session = DAO_BotSession::get($session_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Modern
//		if(array_key_exists('interaction_id', $bot_session->session_data)) {
//			$this->_consoleSendMessageAsAutomation($bot_session);
			// Legacy
//		} else if(array_key_exists('behavior_id', $bot_session->session_data)) {
			$this->_consoleSendMessageAsBehavior($bot_session);
//		}
	}
	
	/**
	 * @deprecated
	 * @param Model_BotSession $bot_session
	 * @return void|boolean
	 */
	private function _consoleSendMessageAsBehavior(Model_BotSession $bot_session) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		@$layer = DevblocksPlatform::importGPC($_POST['layer'], 'string', '');
		@$message = DevblocksPlatform::importGPC($_POST['message'], 'string', '');
		
		// Load our default behavior for this interaction
		if(false == (@$behavior_id = $bot_session->session_data['behavior_id']))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == (@$bot_name = $bot_session->session_data['bot_name']))
			$bot_name = 'Cerb';
		
		$actions = [];
		
		$event_params = [
			'worker_id' => $active_worker->id,
			'message' => $message,
			'actions' => &$actions,
			
			'bot_name' => $bot_name,
			'bot_image' => @$bot_session->session_data['bot_image'],
			'behavior_id' => $behavior_id,
			'behavior_has_parent' => @$bot_session->session_data['behavior_has_parent'],
			'interaction' => @$bot_session->session_data['interaction'],
			'interaction_params' => @$bot_session->session_data['interaction_params'],
			'client_browser' => @$bot_session->session_data['client_browser'],
			'client_browser_version' => @$bot_session->session_data['client_browser_version'],
			'client_ip' => @$bot_session->session_data['client_ip'],
			'client_platform' => @$bot_session->session_data['client_platform'],
			'client_time' => @$bot_session->session_data['client_time'],
			'client_url' => @$bot_session->session_data['client_url'],
		];
		
		//var_dump($event_params);
		
		$event_model = new Model_DevblocksEvent(
			Event_NewMessageChatWorker::ID,
			$event_params
		);
		
		if(false == ($event = Extension_DevblocksEvent::get($event_model->id, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($event instanceof Event_NewMessageChatWorker))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$event->setEvent($event_model, $behavior);
		
		$values = $event->getValues();
		
		// Are we resuming a scope?
		$resume_dict = @$bot_session->session_data['behaviors'][$behavior->id]['dict'];
		if($resume_dict) {
			$values = array_replace($values, $resume_dict);
		}
		
		$dict = new DevblocksDictionaryDelegate($values);
		
		$resume_path = @$bot_session->session_data['behaviors'][$behavior->id]['path'];
		
		if($resume_path) {
			$behavior->prepareResumeDecisionTree($message, $bot_session, $actions, $dict, $resume_path);
			
			if(false == ($result = $behavior->resumeDecisionTree($dict, false, $event, $resume_path)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
		} else {
			if(false == ($result = $behavior->runDecisionTree($dict, false, $event)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		$values = $dict->getDictionary(null, false);
		$values = array_diff_key($values, $event->getValues());
		
		// Hibernate
		if($result['exit_state'] == 'SUSPEND') {
			// Keep everything as it is
		} else {
			// Start the tree over
			$result['path'] = [];
			
			// Return to the caller if we have one
			@$caller = array_pop($bot_session->session_data['callers']);
			$bot_session->session_data['behavior_has_parent'] = !empty($bot_session->session_data['callers']) ? 1 : 0;
			
			if(is_array($caller)) {
				$caller_behavior_id = $caller['behavior_id'];
				
				if($caller_behavior_id && isset($bot_session->session_data['behaviors'][$caller_behavior_id])) {
					$bot_session->session_data['behavior_id'] = $caller_behavior_id;
					$bot_session->session_data['behaviors'][$caller_behavior_id]['dict']['_behavior'] = $values;
				}
				
				$tpl->display('devblocks:cerberusweb.core::console/prompt_wait.tpl');
			}
		}
		
		$bot_session->session_data['behaviors'][$behavior->id]['dict'] = $values;
		$bot_session->session_data['behaviors'][$behavior->id]['path'] = $result['path'];
		
		if(false == ($bot = $behavior->getBot()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$tpl->assign('bot', $bot);
		$tpl->assign('bot_name', $bot_name);
		$tpl->assign('layer', $layer);
		
		foreach($actions as $params) {
			// Are we handling the next response message in a special way?
			if(isset($params['_prompt']) && is_array($params['_prompt'])) {
				$bot_session->session_data['_prompt'] = $params['_prompt'];
			}
			
			switch(@$params['_action']) {
				case 'behavior.switch':
					@$behavior_return = $params['behavior_return'];
					@$variables = $params['behavior_variables'];
					
					if(!isset($bot_session->session_data['callers']))
						$bot_session->session_data['callers'] = [];
					
					if($behavior_return) {
						$bot_session->session_data['callers'][] = [
							'behavior_id' => $behavior->id,
							'return' => '_behavior', // [TODO] Configurable
						];
					} else {
						$bot_session->session_data['behaviors'][$behavior->id]['dict'] = [];
						$bot_session->session_data['behaviors'][$behavior->id]['path'] = [];
					}
					
					if(false == ($behavior_id = @$params['behavior_id']))
						DevblocksPlatform::dieWithHttpError(null, 404);
					
					if(false == ($new_behavior = DAO_TriggerEvent::get($behavior_id)))
						DevblocksPlatform::dieWithHttpError(null, 404);
					
					if($new_behavior->event_point != Event_NewMessageChatWorker::ID)
						DevblocksPlatform::dieWithHttpError(null, 404);
					
					if(!Context_TriggerEvent::isReadableByActor($new_behavior, $bot))
						DevblocksPlatform::dieWithHttpError(null, 403);
					
					$bot = $new_behavior->getBot();
					$tpl->assign('bot', $bot);
					
					$new_dict = [];
					
					if(is_array($variables))
						foreach($variables as $k => $v) {
							$new_dict[$k] = $v;
						}
					
					$bot_session->session_data['behavior_id'] = $new_behavior->id;
					$bot_session->session_data['behaviors'][$new_behavior->id]['dict'] = $new_dict;
					$bot_session->session_data['behaviors'][$new_behavior->id]['path'] = [];
					
					if($behavior_return)
						$bot_session->session_data['behavior_has_parent'] = 1;
					
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_wait.tpl');
					break;
				
				case 'emote':
					if(false == ($emote = @$params['emote']))
						break;
					
					$tpl->assign('emote', $emote);
					$tpl->assign('delay_ms', 500);
					$tpl->display('devblocks:cerberusweb.core::console/emote.tpl');
					break;
				
				case 'prompt.buttons':
					@$options = $params['options'];
					@$color_from = $params['color_from'];
					@$color_to = $params['color_to'];
					@$color_mid = $params['color_mid'];
					@$style = $params['style'];
					
					if(!is_array($options))
						break;
					
					$tpl->assign('options', $options);
					$tpl->assign('color_from', $color_from);
					$tpl->assign('color_to', $color_to);
					$tpl->assign('color_mid', $color_mid);
					$tpl->assign('style', $style);
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_buttons.tpl');
					break;
				
				case 'prompt.chooser':
					@$context = $params['context'];
					@$query = $params['query'];
					@$selection = $params['selection'];
					@$autocomplete = !empty($params['autocomplete']);
					
					if(!$context)
						break;
					
					$tpl->assign('context', $context);
					$tpl->assign('query', $query);
					$tpl->assign('selection', $selection);
					$tpl->assign('autocomplete', $autocomplete);
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_chooser.tpl');
					break;
				
				case 'prompt.date':
					@$placeholder = $params['placeholder'];
					
					if(empty($placeholder))
						$placeholder = 'e.g. tomorrow 5pm, 2 hours';
					
					$tpl->assign('delay_ms', 0);
					$tpl->assign('placeholder', $placeholder);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_date.tpl');
					break;
				
				case 'prompt.file':
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_file.tpl');
					break;
				
				case 'prompt.images':
					@$images = $params['images'];
					@$labels = $params['labels'];
					
					if(!is_array($images) || !is_array($images))
						break;
					
					$tpl->assign('images', $images);
					$tpl->assign('labels', $labels);
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_images.tpl');
					break;
				
				case 'prompt.text':
					@$placeholder = $params['placeholder'];
					@$default = $params['default'];
					@$mode = $params['mode'];
					
					if(empty($placeholder))
						$placeholder = 'say something';
					
					$tpl->assign('delay_ms', 0);
					$tpl->assign('placeholder', $placeholder);
					$tpl->assign('default', $default);
					$tpl->assign('mode', $mode);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_text.tpl');
					break;
				
				case 'prompt.wait':
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_wait.tpl');
					break;
				
				case 'message.send':
					if(false == ($msg = @$params['message']))
						break;
					
					$delay_ms = DevblocksPlatform::intClamp(@$params['delay_ms'], 0, 10000);
					
					$tpl->assign('message', $msg);
					$tpl->assign('format', @$params['format']);
					$tpl->assign('delay_ms', $delay_ms);
					$tpl->display('devblocks:cerberusweb.core::console/message.tpl');
					break;
				
				case 'script.send':
					if(false == ($script = @$params['script']))
						break;
					
					$tpl->assign('script', $script);
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/script.tpl');
					break;
				
				case 'window.close':
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/window_close.tpl');
					break;
				
				case 'worklist.open':
					$context = @$params['context'] ?: null;
					$view_id = @$params['view_id'] ?: null;
					$q = @$params['q'] ?: null;
					$view_model = @$params['model'] ?: null;
					
					if(!$context || false == ($context_ext = Extension_DevblocksContext::get($context)))
						break;
					
					if(false != ($view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($view_model, $view_id))) {
						$view->is_ephemeral = true;
						$view->persist();
					}
					
					// Open popup
					$tpl->assign('context', $context_ext->id);
					$tpl->assign('delay_ms', 0);
					$tpl->assign('q', $q);
					$tpl->assign('view_id', $view_id);
					$tpl->display('devblocks:cerberusweb.core::console/search_worklist.tpl');
					break;
			}
		}
		
		// Save session scope
		DAO_BotSession::update($bot_session->session_id, [
			DAO_BotSession::SESSION_DATA => json_encode($bot_session->session_data),
			DAO_BotSession::UPDATED_AT => time(),
		]);
	}
	
	private function _profileAction_showExportBotPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		if(false == ($bot = DAO_Bot::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Bot::isWriteableByActor($bot, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$bot_json = $bot->exportToJson();
		
		$package_json = [
			'package' => [
				'name' => $bot->name,
				'revision' => 1,
				'requires' => [
					'cerb_version' => APP_VERSION,
					'plugins' => [],
				],
				'configure' => [
					'placeholders' => [],
					'prompts' => [],
				]
			],
			'bots' => [
				json_decode($bot_json, true)
			]
		];
		
		$tpl->assign('package_json', DevblocksPlatform::strFormatJson(json_encode($package_json)));
		
		$tpl->display('devblocks:cerberusweb.core::internal/bot/export.tpl');
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=bot', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=bot&id=%d-%s", $row[SearchFields_Bot::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Bot::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Bot::ID],
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
