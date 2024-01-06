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
	private $_interaction_extensions = [
		AutomationTrigger_InteractionInternal::ID,
		AutomationTrigger_InteractionWorker::ID,
		AutomationTrigger_MailDraftValidate::ID,
		AutomationTrigger_MailReplyValidate::ID,
	];
	
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
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
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
				$package_uri = DevblocksPlatform::importGPC($_POST['package'] ?? null, 'string', '');
				$import_json = DevblocksPlatform::importGPC($_POST['import_json'] ?? null, 'string', '');
				
				$mode = 'build';
				
				if(!$id && $package_uri) {
					$mode = 'library';
					
				} elseif(!$id && $import_json) {
					$mode = 'import';
				}
				
				switch($mode) {
					case 'library':
						$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
						$owner = DevblocksPlatform::importGPC($_POST['owner'] ?? null, 'string', '');
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'bot')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						// Owner
					
						list($owner_ctx, $owner_ctx_id) = array_pad(explode(':', $owner, 2), 2, null);
						
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
						
					case 'build':
						$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
						$at_mention_name = DevblocksPlatform::importGPC($_POST['at_mention_name'] ?? null, 'string', '');
						$owner = DevblocksPlatform::importGPC($_POST['owner'] ?? null, 'string', '');
						$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'] ?? null, 'integer', 0);
						$allowed_events = DevblocksPlatform::importGPC($_POST['allowed_events'] ?? null, 'string', '');
						$itemized_events = DevblocksPlatform::importGPC($_POST['itemized_events'] ?? null, 'array', array());
						$allowed_actions = DevblocksPlatform::importGPC($_POST['allowed_actions'] ?? null, 'string', '');
						$itemized_actions = DevblocksPlatform::importGPC($_POST['itemized_actions'] ?? null, 'array', array());
						$config_json = DevblocksPlatform::importGPC($_POST['config_json'] ?? null, 'string', '');
						
						$is_disabled = DevblocksPlatform::intClamp($is_disabled, 0, 1);
						
						$profile_image_changed = false;
						
						// Owner
					
						$owner_ctx = '';
						list($owner_ctx, $owner_ctx_id) = array_pad(explode(':', $owner, 2), 2, null);
						
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
							$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
							if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_BOT, $id, $field_ids, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							// Avatar image
							$avatar_image = DevblocksPlatform::importGPC($_POST['avatar_image'] ?? null, 'string', '');
							$profile_image_changed = DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_BOT, $id, $avatar_image);
						}
						
						$event_data = [
							'status' => true,
							'id' => $id,
							'label' => $name,
							'view_id' => $view_id,
						];
						
						if($profile_image_changed) {
							$url_writer = DevblocksPlatform::services()->url();
							$type = 'bot';
							$event_data['record_image_url'] =
								$url_writer->write(sprintf('c=avatars&type=%s&id=%d', rawurlencode($type), $id), true)
								. '?v=' . time()
							;
						}
						
						echo json_encode($event_data);
						return;
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
		
		$interactions_menu = Toolbar_GlobalMenu::getInteractionsMenu();
		
		$tpl->assign('interactions_menu', $interactions_menu);
		$tpl->display('devblocks:cerberusweb.core::console/bot_interactions_menu.tpl');
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
		
		if($interaction_uri && false != ($automation = DAO_Automation::getByUri($interaction_uri, $this->_interaction_extensions))) {
			return $this->_startBotInteractionAsAutomation($automation);

		} else if($interaction_uri && is_numeric($interaction_uri) && false != ($behavior = DAO_TriggerEvent::get($interaction_uri))) {
			return $this->_startBotInteractionAsFormBehavior($behavior);
			
		} else if($interaction_uri && false != ($behavior = DAO_TriggerEvent::getByUri($interaction_uri))) {
			return $this->_startBotInteractionAsFormBehavior($behavior);
			
		} else if($interaction_behavior_id && false != ($behavior = DAO_TriggerEvent::get($interaction_behavior_id))) {
			return $this->_startBotInteractionAsConvoBehavior($behavior);
			
		} else {
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
	}
	
	/**
	 * @param Model_TriggerEvent $interaction_behavior
	 * @throws SmartyException
	 * @deprecated
	 */
	private function _startBotInteractionAsFormBehavior(Model_TriggerEvent $interaction_behavior) {
		$interaction_style = DevblocksPlatform::importGPC($_POST['interaction_style'] ?? null, 'string', null);
		$interaction_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		$layer = DevblocksPlatform::importGPC($_POST['layer'] ?? null, 'string', '');
		
		if(!$layer)
			$layer = uniqid();
		
		$tpl = DevblocksPlatform::services()->template();
		$url_writer = DevblocksPlatform::services()->url();
		
		if(false == ($bot = $interaction_behavior->getBot())) {
			$error = 'Invalid bot #' . intval($interaction_behavior->bot_id);
			DevblocksPlatform::dieWithHttpError($error, 404);
		}
		
		$session = $this->_startInteractionFormSession($interaction_behavior, $interaction_params);
		
		$tpl->assign('layer', $layer);
		$tpl->assign('bot_id', $bot->id);
		$tpl->assign('bot_name', $bot->name);
		$tpl->assign('bot_image_url', $url_writer->write(sprintf("c=avatars&w=bot&id=%d", $bot->id)) . '?v=' . $bot->updated_at);
		$tpl->assign('session_id', $session->session_id);
		$tpl->assign('interaction_style', $interaction_style);
		
		if('inline' == $interaction_style) {
			$tpl->display('devblocks:cerberusweb.core::console/form/panel.tpl');
		} else {
			$tpl->display('devblocks:cerberusweb.core::console/form/popup.tpl');
		}
	}
	
	/**
	 * @deprecated
	 */
	private function _startInteractionFormSession(Model_TriggerEvent $interaction_behavior, array $interaction_params) {
		if(!$interaction_behavior || $interaction_behavior->event_point != Event_FormInteractionWorker::ID)
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		// Start the session using the behavior
		
		$client_ip = DevblocksPlatform::getClientIp();
		$client_platform = '';
		$client_browser = '';
		$client_browser_version = '';
		
		if(null != ($client_user_agent_parts = DevblocksPlatform::getClientUserAgent())) {
			$client_platform = $client_user_agent_parts['platform'] ?? '';
			$client_browser = $client_user_agent_parts['browser'] ?? '';
			$client_browser_version = $client_user_agent_parts['version'] ?? '';
		}
		
		$behavior_dict = DevblocksDictionaryDelegate::instance([]);
		
		if(is_array($interaction_params))
		foreach($interaction_params as $k => &$v) {
			if(DevblocksPlatform::strStartsWith($k, 'var_')) {
				if(!array_key_exists($k, $interaction_behavior->variables))
					continue;
				
				$value = $interaction_behavior->formatVariable($interaction_behavior->variables[$k], $v, $behavior_dict);
				
				$behavior_dict->set($k, $value);
			}
		}
		
		$behavior_dict->set('client_browser', $client_browser);
		$behavior_dict->set('client_browser_version', $client_browser_version);
		$behavior_dict->set('client_ip', $client_ip);
		$behavior_dict->set('client_platform', $client_platform);
		
		$initial_state = $behavior_dict->getDictionary(null, false);
		
		$session_data = [
			'behavior_id' => $interaction_behavior->id,
			'dict' => $initial_state,
			'initial_state' => $initial_state,
		];
		
		$created_at = time();
		
		$session_id = DAO_BotSession::create([
			DAO_BotSession::SESSION_DATA => json_encode($session_data),
			DAO_BotSession::UPDATED_AT => $created_at,
		]);
		
		$model = new Model_BotSession();
		$model->session_id = $session_id;
		$model->session_data = $session_data;
		$model->updated_at = $created_at;
		
		return $model;
	}
	
	/**
	 * @deprecated
	 */
	private function _startBotInteractionAsConvoBehavior(Model_TriggerEvent $interaction_behavior) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$interaction = DevblocksPlatform::importGPC($_POST['interaction'] ?? null, 'string', '');
		$browser = DevblocksPlatform::importGPC($_POST['browser'] ?? null, 'array', []);
		$interaction_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		$layer = DevblocksPlatform::importGPC($_POST['layer'] ?? null, 'string', null);
		
		if($interaction_behavior->event_point != Event_NewInteractionChatWorker::ID) {
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		// Start the session using the behavior
		
		$actions = [];
		
		$client_ip = DevblocksPlatform::getClientIp();
		$client_platform = '';
		$client_browser = '';
		$client_browser_version = '';
		$client_url = ($browser['url'] ?? null) ?: '';
		$client_time = ($browser['time'] ?? null) ?: '';
		
		if(null != ($client_user_agent_parts = DevblocksPlatform::getClientUserAgent())) {
			$client_platform = $client_user_agent_parts['platform'] ?? '';
			$client_browser = $client_user_agent_parts['browser'] ?? '';
			$client_browser_version = $client_user_agent_parts['version'] ?? '';
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
						$behavior_id = $action['behavior_id'] ?? null;
						$variables = $action['behavior_variables'] ?? null;
						
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
	
	private function _profileAction_sendMessage() : void {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string', '');
		
		if($continuation_token) {
			$this->_consoleSendMessageAsAutomation($continuation_token);
			
		} else {
			$session_id = DevblocksPlatform::importGPC($_POST['session_id'] ?? null, 'string', '');
			
			// Load the session
			if(!($bot_session = DAO_BotSession::get($session_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			// Legacy
			if(array_key_exists('behavior_id', $bot_session->session_data)) {
				if(!($behavior = DAO_TriggerEvent::get($bot_session->session_data['behavior_id'])))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if($behavior->event_point == Event_FormInteractionWorker::ID) {
					$this->_consoleSendMessageAsFormBehavior($bot_session, $behavior);
					
				} else if($behavior->event_point == Event_NewMessageChatWorker::ID) {
					$this->_consoleSendMessageAsConvoBehavior($bot_session, $behavior);
				
				} else {
					DevblocksPlatform::dieWithHttpError(null, 403);
				}
			}
		}
	}

	// [TODO] Still needed?
	// Reset using initial state
	private function _resetFormState(Model_BotSession &$bot_session) {
		$bot_session->session_data = [
			'behavior_id' => $bot_session->session_data['behavior_id'],
			'dict' => $bot_session->session_data['initial_state'],
			'initial_state' => $bot_session->session_data['initial_state'],
		];
	}
	
	/**
	 * @deprecated
	 * @param Model_BotSession $bot_session
	 * @return void|boolean
	 */
	private function _consoleSendMessageAsFormBehavior(Model_BotSession $bot_session, Model_TriggerEvent $behavior) {
		// [TODO] Handle reset
		
		// If we're resetting the scope, delete the session and state key
		if(array_key_exists('reset', $_POST)) {
			// [TODO] Treat this as an interaction end prompt
			$this->_resetFormState($bot_session);
		}
		
		$state_id = $bot_session->session_id;
		
		// Resuming
		if($state_id) {
			try {
				// [TODO] dict, $is_submit
				if(false === $this->_renderFormState($bot_session, $behavior))
					DevblocksPlatform::dieWithHttpError(null, 500);
				
			} catch (Exception $e) {
				error_log($e->getMessage());
			}
			
		} else {
			DevblocksPlatform::dieWithHttpError(null, 500);
		}
	}
	
	/**
	 * @param Model_BotSession $interaction
	 * @param DevblocksDictionaryDelegate $dict
	 * @param string $state_key
	 * @param bool $is_submit
	 * @return bool
	 * @throws SmartyException
	 */
	private function _renderFormState(Model_BotSession $interaction, Model_TriggerEvent $behavior) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
		
		$is_submit = array_key_exists('__submit', $prompts);
		
		$actions = [];
		
		if(!$behavior || $behavior->event_point != Event_FormInteractionWorker::ID)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$event_params = [
			'prompts' => $prompts,
			'actions' => &$actions,
			
			'worker_id' => $active_worker ? $active_worker->id : 0,
			
			'client_browser' => $interaction->session_data['dict']['client_browser'] ?? '',
			'client_browser_version' => $interaction->session_data['dict']['client_browser_version'] ?? '',
			'client_ip' => $interaction->session_data['dict']['client_ip'] ?? '',
			'client_platform' => $interaction->session_data['dict']['client_platform'] ?? '',
		];
		
		$event_model = new Model_DevblocksEvent(
			Event_FormInteractionWorker::ID,
			$event_params
		);
		
		if(false == ($event = Extension_DevblocksEvent::get($event_model->id, true)))
			return false;
		
		if(!($event instanceof Event_FormInteractionWorker))
			return false;
		
		$event->setEvent($event_model, $behavior);
		
		$values = $event->getValues();
		
		// Are we resuming a scope?
		$resume_dict = @$interaction->session_data['dict'];
		if($resume_dict) {
			$values = array_replace($values, $resume_dict);
		}
		
		// [TODO] Temp
		unset($values['_types']);
		unset($values['_labels']);
		
		$behavior_dict = new DevblocksDictionaryDelegate($values);
		
		$resume_path = $interaction->session_data['path'] ?? '';
		
		if($resume_path) {
			// Did we try to submit?
			if($is_submit) {
				$this->_prepareResumeDecisionTree($behavior, $prompts, $interaction, $actions, $behavior_dict, $resume_path);
				
				$form_validation_errors = $interaction->session_data['form_validation_errors'];
				
				if($form_validation_errors) {
					$tpl->assign('errors', $form_validation_errors);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_errors.tpl');
					
					// If we had validation errors, repeat the form state
					$actions = $interaction->session_data['form_state'];
					
					// Simulate the end state
					$result = [
						'exit_state' => 'SUSPEND',
						'path' => $resume_path,
					];
					
				} else {
					if(false == ($result = $behavior->resumeDecisionTree($behavior_dict, false, $event, $resume_path)))
						return false;
				}
				
				// Re-render without changing state
			} else {
				// If we had validation errors, repeat the form state
				$actions = $interaction->session_data['form_state'];
				$exit_state = $interaction->session_data['exit_state'];
				
				// Simulate the end state
				$result = [
					'exit_state' => $exit_state,
					'path' => $resume_path,
				];
			}
			
		} else {
			if(!($result = $behavior->runDecisionTree($behavior_dict, false, $event)))
				return false;
		}
		
		$values = $behavior_dict->getDictionary(null, false);
		$values = array_diff_key($values, $event->getValues());
		
		$interaction->session_data['dict'] = $values;
		$interaction->session_data['path'] = $result['path'];
		$interaction->session_data['form_state'] = $actions;
		$interaction->session_data['exit_state'] = $result['exit_state'];
		
		foreach($actions as $params) {
			switch(@$params['_action']) {
				case 'interaction.end':
					//$this->_resetFormState($interaction);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_end.tpl');
					break;
				
				case 'prompt.captcha':
					$captcha = DevblocksPlatform::services()->captcha();
					
					$label = $params['label'] ?? null;
					$var = $params['_prompt']['var'] ?? null;
					
					$otp_key = $var . '__otp';
					$otp = $behavior_dict->get($otp_key);
					
					$image_bytes = $captcha->createImage($otp);
					$tpl->assign('image_bytes', $image_bytes);
					
					$tpl->assign('label', $label);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_captcha.tpl');
					break;
				
				case 'prompt.checkboxes':
					$label = $params['label'] ?? null;
					$options = $params['options'] ?? null;
					$default = $params['default'] ?? null;
					$var = $params['_prompt']['var'] ?? null;
					
					$tpl->assign('label', $label);
					$tpl->assign('options', $options);
					$tpl->assign('default', $default);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_checkboxes.tpl');
					break;
				
				case 'prompt.chooser':
					$label = $params['label'] ?? null;
					$var = $params['_prompt']['var'] ?? null;
					$record_type = $params['record_type'] ?? null;
					$record_query = $params['record_query'] ?? null;
					$record_query_required = $params['record_query_required'] ?? null;
					$selection = $params['selection'] ?? null;
					@$autocomplete = !empty($params['autocomplete']);
					$default = $params['default'] ?? null;
					
					if(!$behavior_dict->exists($var)) {
						$records = [];
						
						if(is_array($default) && $default) {
							foreach($default as $record_id) {
								$records[] = DevblocksDictionaryDelegate::instance([
									'_context' => $record_type,
									'id' => $record_id,
								]);
							
								if('single' == $selection)
									break;
							}
						}
						
					} else {
						$records = array_map(function($record) {
							if($record instanceof DevblocksDictionaryDelegate)
								return $record;
							
							return DevblocksDictionaryDelegate::instance($record);
						}, $behavior_dict->get($var,[]));
					}
					
					$tpl->assign('label', $label);
					$tpl->assign('record_type', $record_type);
					$tpl->assign('record_query', $record_query);
					$tpl->assign('record_query_required', $record_query_required);
					$tpl->assign('selection', $selection);
					$tpl->assign('autocomplete', $autocomplete);
					$tpl->assign('var', $var);
					$tpl->assign('params', $params);
					$tpl->assign('records', $records);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_chooser.tpl');
					break;
				
				case 'prompt.compose':
					$draft_id = $params['draft_id'] ?? null;
					$var = $params['_prompt']['var'] ?? null;
					
					$tpl->assign('draft_id', $draft_id);
					$tpl->assign('var', $var);
					$tpl->assign('params', $params);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_compose.tpl');
					break;
				
				case 'prompt.files':
					$label = $params['label'] ?? null;
					$var = $params['_prompt']['var'] ?? null;
					$selection = $params['selection'] ?? null;
					
					$records = array_map(function($record) {
						if($record instanceof DevblocksDictionaryDelegate)
							return $record;
						
						return DevblocksDictionaryDelegate::instance($record);
					}, $behavior_dict->get($var,[]));
					
					$tpl->assign('label', $label);
					$tpl->assign('selection', $selection);
					$tpl->assign('var', $var);
					$tpl->assign('params', $params);
					$tpl->assign('records', $records);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_files.tpl');
					break;
				
				case 'prompt.radios':
					$label = $params['label'] ?? null;
					$style = $params['style'] ?? null;
					$orientation = $params['orientation'] ?? null;
					$options = $params['options'] ?? null;
					$default = $params['default'] ?? null;
					$var = $params['_prompt']['var'] ?? null;
					
					$tpl->assign('label', $label);
					$tpl->assign('orientation', $orientation);
					$tpl->assign('options', $options);
					$tpl->assign('default', $default);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					
					if($style == 'buttons') {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_buttons.tpl');
					} else if($style == 'picklist') {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_picklist.tpl');
					} else {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_radios.tpl');
					}
					break;
				
				case 'prompt.reply':
					$draft_id = $params['draft_id'] ?? null;
					$var = $params['_prompt']['var'] ?? null;
					
					$tpl->assign('draft_id', $draft_id);
					$tpl->assign('var', $var);
					$tpl->assign('params', $params);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_reply.tpl');
					break;
				
				case 'prompt.sheet':
					$label = $params['label'] ?? null;
					$data = $params['data'] ?? null;
					$schema = $params['schema'] ?? null;
					$var = $params['_prompt']['var'] ?? null;
					
					$sheets = DevblocksPlatform::services()->sheet();
					@$sheet_kata = DevblocksPlatform::importGPC($schema, 'string', null);
					$sheet = $sheets->parse($sheet_kata, $error);
					
					if(!array_key_exists('layout', $sheet))
						$sheet['layout'] = [];
					
					$sheets->addType('card', $sheets->types()->card());
					$sheets->addType('date', $sheets->types()->date());
					$sheets->addType('icon', $sheets->types()->icon());
					$sheets->addType('link', $sheets->types()->link());
					$sheets->addType('markdown', $sheets->types()->markdown());
					$sheets->addType('search', $sheets->types()->search());
					$sheets->addType('search_button', $sheets->types()->searchButton());
					$sheets->addType('selection', $sheets->types()->selection());
					$sheets->addType('slider', $sheets->types()->slider());
					$sheets->addType('text', $sheets->types()->text());
					$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
					$sheets->setDefaultType('text');
					
					$sheet_data = @json_decode($data, true) ?: [];
					
					$layout = $sheets->getLayout($sheet);
					$tpl->assign('layout', $layout);
					
					$rows = $sheets->getRows($sheet, $sheet_data);
					$tpl->assign('rows', $rows);
					
					$columns = $sheets->getColumns($sheet);
					$tpl->assign('columns', $columns);
					
					$tpl->assign('label', $label);
					$tpl->assign('var', $var);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_sheet.tpl');
					break;
				
				case 'prompt.text':
					$label = $params['label'] ?? null;
					$placeholder = $params['placeholder'] ?? null;
					$default = $params['default'] ?? null;
					$mode = $params['mode'] ?? null;
					$var = $params['_prompt']['var'] ?? null;
					$max_length = $params['max_length'] ?? null;
					
					$tpl->assign('label', $label);
					$tpl->assign('placeholder', $placeholder);
					$tpl->assign('default', $default);
					$tpl->assign('mode', $mode);
					$tpl->assign('var', $var);
					$tpl->assign('max_length', $max_length);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_text.tpl');
					break;
				
				case 'respond.text':
					if(false == ($msg = @$params['message']))
						break;
					
					$tpl->assign('message', $msg);
					$tpl->assign('format', $params['format'] ?? '');
					$tpl->assign('style', $params['style'] ?? '');
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_text.tpl');
					break;
				
				case 'respond.sheet':
					$sheets = DevblocksPlatform::services()->sheet()->newInstance();
					$error = null;
					
					if(false == ($results = DevblocksPlatform::services()->data()->executeQuery($params['data_query'], [], $error)))
						break;
					
					if(false == ($sheet = $sheets->parse($params['sheet_kata'], $error)))
						break;
					
					$sheets->addType('card', $sheets->types()->card());
					$sheets->addType('date', $sheets->types()->date());
					$sheets->addType('icon', $sheets->types()->icon());
					$sheets->addType('link', $sheets->types()->link());
					$sheets->addType('markdown', $sheets->types()->markdown());
					$sheets->addType('search', $sheets->types()->search());
					$sheets->addType('search_button', $sheets->types()->searchButton());
					$sheets->addType('selection', $sheets->types()->selection());
					$sheets->addType('slider', $sheets->types()->slider());
					$sheets->addType('text', $sheets->types()->text());
					$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
					$sheets->setDefaultType('text');
					
					$sheet_dicts = $results['data'];
					
					$layout = $sheets->getLayout($sheet);
					$tpl->assign('layout', $layout);
					
					$rows = $sheets->getRows($sheet, $sheet_dicts);
					$tpl->assign('rows', $rows);
					
					$columns = $sheets->getColumns($sheet);
					$tpl->assign('columns', $columns);
					
					if(array_key_exists('_', $results) && array_key_exists('paging', $results['_']))
						$tpl->assign('paging', $results['_']['paging']);
					
					if($layout['style'] == 'fieldsets') {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_sheet_fieldsets.tpl');
					} else {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_sheet.tpl');
					}
					break;
				
				// [TODO] This shouldn't be the default
				default:
					$tpl->assign('continue_options', [
						'continue' => true,
						'reset' => true,
					]);
					$tpl->display('devblocks:cerberusweb.core::console/form/continue.tpl');
					break;
			}
		}
		
		if($result['exit_state'] != 'SUSPEND') {
			// [TODO] We want to return our state to the caller here
			
			$tpl->assign('continue_options', [
				'continue' => false,
				'reset' => true,
			]);
			$tpl->display('devblocks:cerberusweb.core::console/form/continue.tpl');
		}
		
		// Save session scope
		DAO_BotSession::update($interaction->session_id, [
			DAO_BotSession::SESSION_DATA => json_encode($interaction->session_data),
			DAO_BotSession::UPDATED_AT => time(),
		]);
		
		return true;
	}
	
	private function _prepareResumeDecisionTree(Model_TriggerEvent $behavior, $prompts, &$interaction, &$actions, DevblocksDictionaryDelegate &$dict, &$resume_path) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		unset($interaction->session_data['form_validation_errors']);
		
		// Do we have special prompt handling instructions?
		if(array_key_exists('form_state', $interaction->session_data)) {
			$form_state = $interaction->session_data['form_state'];
			$validation_errors = [];
			
			foreach($form_state as $form_element) {
				if(!array_key_exists('_prompt', $form_element))
					continue;
				
				$prompt_var = $form_element['_prompt']['var'];
				$prompt_value = ($prompts[$prompt_var] ?? null) ?: null;
				
				// If we lazy loaded a sub dictionary on the last attempt, clear it
				if(DevblocksPlatform::strEndsWith($prompt_var, '_id'))
					$dict->scrubKeys(substr($prompt_var, 0, -2));
				
				// Prompt-specific options
				switch(@$form_element['_action']) {
					case 'prompt.captcha':
						$otp_key = $prompt_var . '__otp';
						$otp = $dict->get($otp_key);
						
						if(!$otp || !is_string($otp) || !is_string($prompt_value) || 0 !== strcasecmp($otp, $prompt_value)) {
							$validation_errors[] = 'Your CAPTCHA text does not match the image.';
							
							// Re-generate the challenge
							$otp = CerberusApplication::generatePassword(4);
							$dict->set($otp_key, $otp);
						}
						break;
					
					case 'prompt.checkboxes':
						if(is_null($prompt_value))
							$prompt_value = [];
						break;
					
					case 'prompt.chooser':
						$record_type = $form_element['record_type'] ?? null;
						
						if(is_null($prompt_value))
							$prompt_value = [];
						
						$prompt_value = array_map(function($record_id) use ($record_type) {
							return DevblocksDictionaryDelegate::instance([
								'_context' => $record_type,
								'id' => $record_id,
							]);
						}, $prompt_value);
						break;
					
					case 'prompt.compose':
						$prompt_value = intval($prompt_value);
						
						if(!$prompt_value) {
							$validation_errors[] = 'You must compose a message before continuing.';
						} else {
							// [TODO] This can be a draft
							$ticket_dict = DevblocksDictionaryDelegate::instance([
								'_context' => CerberusContexts::CONTEXT_TICKET,
								'id' => $prompt_value,
							]);
							
							$prompt_value = $ticket_dict;
						}
						break;
					
					case 'prompt.files':
						if(is_null($prompt_value)) {
							$prompt_value = [];
						} elseif (is_string($prompt_value)) {
							$prompt_value = [intval($prompt_value)];
						} elseif (!is_array($prompt_value)) {
							$prompt_value = [];
						}
						
						$prompt_value = array_map(function($record_id) {
							return DevblocksDictionaryDelegate::instance([
								'_context' => 'attachment',
								'id' => $record_id,
							]);
						}, $prompt_value);
						break;
					
					case 'prompt.reply':
						$prompt_value = intval($prompt_value);
						
						if(!$prompt_value) {
							$validation_errors[] = 'You must reply to a message before continuing.';
						} else {
							$message_dict = DevblocksDictionaryDelegate::instance([
								'_context' => CerberusContexts::CONTEXT_MESSAGE,
								'id' => $prompt_value,
							]);
							
							$prompt_value = $message_dict;
						}
						break;
					
					case 'prompt.sheet':
						if(is_null($prompt_value))
							$prompt_value = [];
						break;
				}
				
				$dict->set($prompt_var, $prompt_value);
				
				if(false != ($format_tpl = ($form_element['_prompt']['format'] ?? null))) {
					$var_message = $tpl_builder->build($format_tpl, $dict);
					$dict->set($prompt_var, $var_message);
				}
				
				if(false != ($validate_tpl = ($form_element['_prompt']['validate'] ?? null))) {
					$validation_result = trim($tpl_builder->build($validate_tpl, $dict));
					
					if(!empty($validation_result)) {
						$validation_errors[] = $validation_result;
					}
				}
			}
			
			$interaction->session_data['form_validation_errors'] = $validation_errors;
		}
		
		return true;
	}
	
	/**
	 * @deprecated
	 * @param Model_BotSession $bot_session
	 * @return void|boolean
	 */
	private function _consoleSendMessageAsConvoBehavior(Model_BotSession $bot_session, Model_TriggerEvent $behavior) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$layer = DevblocksPlatform::importGPC($_POST['layer'] ?? null, 'string', '');
		$message = DevblocksPlatform::importGPC($_POST['message'] ?? null, 'string', '');
		
		if(false == (@$bot_name = $bot_session->session_data['bot_name']))
			$bot_name = 'Cerb';
		
		$actions = [];
		
		$event_params = [
			'worker_id' => $active_worker->id,
			'message' => $message,
			'actions' => &$actions,
			
			'bot_name' => $bot_name,
			'bot_image' => $bot_session->session_data['bot_image'] ?? null,
			'behavior_id' => $behavior->id,
			'behavior_has_parent' => $bot_session->session_data['behavior_has_parent'] ?? null,
			'interaction' => $bot_session->session_data['interaction'] ?? null,
			'interaction_params' => $bot_session->session_data['interaction_params'] ?? [],
			'client_browser' => $bot_session->session_data['client_browser'] ?? null,
			'client_browser_version' => $bot_session->session_data['client_browser_version'] ?? null,
			'client_ip' => $bot_session->session_data['client_ip'] ?? null,
			'client_platform' => $bot_session->session_data['client_platform'] ?? null,
			'client_time' => $bot_session->session_data['client_time'] ?? null,
			'client_url' => $bot_session->session_data['client_url'] ?? null,
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
		$resume_dict = $bot_session->session_data['behaviors'][$behavior->id]['dict'] ?? null;
		if($resume_dict) {
			$values = array_replace($values, $resume_dict);
		}
		
		$dict = new DevblocksDictionaryDelegate($values);
		
		$resume_path = $bot_session->session_data['behaviors'][$behavior->id]['path'] ?? null;
		
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
		
		$tpl->assign('layer', $layer);
		
		// Hibernate
		if($result['exit_state'] == 'SUSPEND') {
			// Keep everything as it is
			DevblocksPlatform::noop();
		} else {
			// Start the tree over
			$result['path'] = [];
			
			// Return to the caller if we have one
			$caller = null;	
			if(
				array_key_exists('callers', $bot_session->session_data) 
				&& is_array($bot_session->session_data['callers'])
			) {
				$caller = array_pop($bot_session->session_data['callers']);
			}
			$bot_session->session_data['behavior_has_parent'] = !empty($bot_session->session_data['callers'] ?? []) ? 1 : 0;
			@$caller_result_key = $caller['return'] ?? '_behavior';
			
			if(is_array($caller)) {
				$caller_behavior_id = $caller['behavior_id'];
				
				if($caller_behavior_id && isset($bot_session->session_data['behaviors'][$caller_behavior_id])) {
					$bot_session->session_data['behavior_id'] = $caller_behavior_id;
					$bot_session->session_data['behaviors'][$caller_behavior_id]['dict'][$caller_result_key] = $values;
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
		
		foreach($actions as $params) {
			// Are we handling the next response message in a special way?
			if(isset($params['_prompt']) && is_array($params['_prompt'])) {
				$bot_session->session_data['_prompt'] = $params['_prompt'];
			}
			
			switch(@$params['_action']) {
				case 'behavior.switch':
					$behavior_return = $params['behavior_return'] ?? null;
					$variables = $params['behavior_variables'] ?? null;
					@$var_key = $params['var'] ?: '_behavior';
					
					if(!isset($bot_session->session_data['callers']))
						$bot_session->session_data['callers'] = [];
					
					if($behavior_return) {
						$bot_session->session_data['callers'][] = [
							'behavior_id' => $behavior->id,
							'return' => $var_key,
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
					$options = $params['options'] ?? null;
					$color_from = $params['color_from'] ?? null;
					$color_to = $params['color_to'] ?? null;
					$color_mid = $params['color_mid'] ?? null;
					$style = $params['style'] ?? null;
					
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
					$context = $params['context'] ?? null;
					$query = $params['query'] ?? null;
					$selection = $params['selection'] ?? null;
					$autocomplete = (bool)($params['autocomplete'] ?? false);
					
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
					$placeholder = $params['placeholder'] ?? null;
					
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
					$images = $params['images'] ?? null;
					$labels = $params['labels'] ?? null;
					
					if(!is_array($images) || !is_array($images))
						break;
					
					$tpl->assign('images', $images);
					$tpl->assign('labels', $labels);
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_images.tpl');
					break;
				
				case 'prompt.text':
					$placeholder = $params['placeholder'] ?? null;
					$default = $params['default'] ?? null;
					$mode = $params['mode'] ?? null;
					
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
		
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'integer',0);
		
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
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
	
	private function _startBotInteractionAsAutomation(Model_Automation $automation) {
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
		
		header('Content-Type: application/json; charset=utf-8');
		
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
		
		if(false == ($automation = $continuation->getAutomation()))
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
		
		if(false == ($draft_uri = DevblocksPlatform::services()->ui()->parseURI($draft_state['uri'] ?? null)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!array_key_exists('context', $draft_uri) || !array_key_exists('context_id', $draft_uri))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(CerberusContexts::CONTEXT_DRAFT != $draft_uri['context'])
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($draft = DAO_MailQueue::get($draft_uri['context_id'])))
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
			
			if(false == ($automation = $continuation->getAutomation()))
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
};
