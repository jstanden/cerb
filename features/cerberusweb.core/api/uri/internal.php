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

class ChInternalController extends DevblocksControllerExtension {
	const ID = 'core.controller.internal';
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		// Security
		if(null == (CerberusApplication::getActiveWorker())) {
			return $this->redirectRequestToLogin($request);
		}

		$stack = $request->path;
		array_shift($stack); // internal

		@$action = array_shift($stack) . 'Action';

		switch($action) {
			case NULL:
				break;

			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this, $action)) {
					try {
						call_user_func(array(&$this, $action));
					} catch (Exception $e) { }
				}
				break;
		}
	}

	function handleSectionActionAction() {
		// GET has precedence over POST
		@$section_uri = DevblocksPlatform::importGPC(isset($_GET['section']) ? $_GET['section'] : $_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function getBotInteractionsProactiveAction() {
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
	
	function getBotInteractionsMenuAction() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('global', [], $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);
		
		$tpl->display('devblocks:cerberusweb.core::console/bot_interactions_menu.tpl');
	}
	
	function startBotInteractionAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		@$interaction = DevblocksPlatform::importGPC($_REQUEST['interaction'], 'string', '');
		@$interaction_behavior_id = DevblocksPlatform::importGPC($_REQUEST['behavior_id'], 'integer', 0);
		@$browser = DevblocksPlatform::importGPC($_REQUEST['browser'], 'array', []);
		@$interaction_params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'], 'string', null);
		
		if(
			!$interaction_behavior_id
			|| false == ($interaction_behavior = DAO_TriggerEvent::get($interaction_behavior_id))
			|| $interaction_behavior->event_point != Event_NewInteractionChatWorker::ID
		)
			return false;

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
			return;
		
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
	
	function consoleSendMessageAction() {
		@$session_id = DevblocksPlatform::importGPC($_REQUEST['session_id'], 'string', '');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'], 'string', '');
		@$message = DevblocksPlatform::importGPC($_REQUEST['message'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		// Load the session
		if(false == ($interaction = DAO_BotSession::get($session_id)))
			return false;
		
		// Load our default behavior for this interaction
		if(false == (@$behavior_id = $interaction->session_data['behavior_id']))
			return false;
		
		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return;
		
		if(false == (@$bot_name = $interaction->session_data['bot_name']))
			$bot_name = 'Cerb';
		
		$actions = [];
		
		$event_params = [
			'worker_id' => $active_worker->id,
			'message' => $message,
			'actions' => &$actions,
			
			'bot_name' => $bot_name,
			'bot_image' => @$interaction->session_data['bot_image'],
			'behavior_id' => $behavior_id,
			'behavior_has_parent' => @$interaction->session_data['behavior_has_parent'],
			'interaction' => @$interaction->session_data['interaction'],
			'interaction_params' => @$interaction->session_data['interaction_params'],
			'client_browser' => @$interaction->session_data['client_browser'],
			'client_browser_version' => @$interaction->session_data['client_browser_version'],
			'client_ip' => @$interaction->session_data['client_ip'],
			'client_platform' => @$interaction->session_data['client_platform'],
			'client_time' => @$interaction->session_data['client_time'],
			'client_url' => @$interaction->session_data['client_url'],
		];
		
		//var_dump($event_params);
		
		$event_model = new Model_DevblocksEvent(
			Event_NewMessageChatWorker::ID,
			$event_params
		);
		
		if(false == ($event = Extension_DevblocksEvent::get($event_model->id, true)))
			return;
		
		if(!($event instanceof Event_NewMessageChatWorker))
			return;
			
		$event->setEvent($event_model, $behavior);
		
		$values = $event->getValues();
		
		// Are we resuming a scope?
		$resume_dict = @$interaction->session_data['behaviors'][$behavior->id]['dict'];
		if($resume_dict) {
			$values = array_replace($values, $resume_dict);
		}
		
		$dict = new DevblocksDictionaryDelegate($values);
		
		$resume_path = @$interaction->session_data['behaviors'][$behavior->id]['path'];
		
		if($resume_path) {
			$behavior->prepareResumeDecisionTree($message, $interaction, $actions, $dict, $resume_path);
			
			if(false == ($result = $behavior->resumeDecisionTree($dict, false, $event, $resume_path)))
				return;
			
		} else {
			if(false == ($result = $behavior->runDecisionTree($dict, false, $event)))
				return;
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
			@$caller = array_pop($interaction->session_data['callers']);
			$interaction->session_data['behavior_has_parent'] = !empty($interaction->session_data['callers']) ? 1 : 0;
			
			if(is_array($caller)) {
				$caller_behavior_id = $caller['behavior_id'];
				
				if($caller_behavior_id && isset($interaction->session_data['behaviors'][$caller_behavior_id])) {
					$interaction->session_data['behavior_id'] = $caller_behavior_id;
					$interaction->session_data['behaviors'][$caller_behavior_id]['dict']['_behavior'] = $values;
				}
				
				$tpl->display('devblocks:cerberusweb.core::console/prompt_wait.tpl');
			}
		}
		
		$interaction->session_data['behaviors'][$behavior->id]['dict'] = $values;
		$interaction->session_data['behaviors'][$behavior->id]['path'] = $result['path'];
		
		if(false == ($bot = $behavior->getBot()))
			return;
		
		$tpl->assign('bot', $bot);
		$tpl->assign('bot_name', $bot_name);
		$tpl->assign('layer', $layer);
		
		foreach($actions as $params) {
			// Are we handling the next response message in a special way?
			if(isset($params['_prompt']) && is_array($params['_prompt'])) {
				$interaction->session_data['_prompt'] = $params['_prompt'];
			}
			
			switch(@$params['_action']) {
				case 'behavior.switch':
					@$behavior_return = $params['behavior_return'];
					@$variables = $params['behavior_variables'];
					
					if(!isset($interaction->session_data['callers']))
						$interaction->session_data['callers'] = [];
					
					if($behavior_return) {
						$interaction->session_data['callers'][] = [
							'behavior_id' => $behavior->id,
							'return' => '_behavior', // [TODO] Configurable
						];
					} else {
						$interaction->session_data['behaviors'][$behavior->id]['dict'] = [];
						$interaction->session_data['behaviors'][$behavior->id]['path'] = [];
					}
					
					if(false == ($behavior_id = @$params['behavior_id']))
						break;
					
					if(false == ($new_behavior = DAO_TriggerEvent::get($behavior_id)))
						break;
					
					if($new_behavior->event_point != Event_NewMessageChatWorker::ID)
						break;
					
					if(!Context_TriggerEvent::isReadableByActor($new_behavior, $bot))
						break;
					
					$bot = $new_behavior->getBot();
					$tpl->assign('bot', $bot);
					
					$new_dict = [];
					
					if(is_array($variables))
					foreach($variables as $k => $v) {
						$new_dict[$k] = $v;
					}
					
					$interaction->session_data['behavior_id'] = $new_behavior->id;
					$interaction->session_data['behaviors'][$new_behavior->id]['dict'] = $new_dict;
					$interaction->session_data['behaviors'][$new_behavior->id]['path'] = [];
					
					if($behavior_return)
						$interaction->session_data['behavior_has_parent'] = 1;
					
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
		DAO_BotSession::update($interaction->session_id, [
			DAO_BotSession::SESSION_DATA => json_encode($interaction->session_data),
			DAO_BotSession::UPDATED_AT => time(),
		]);
	}
	
	// Post
	function doStopTourAction() {
		$worker = CerberusApplication::getActiveWorker();
		DAO_WorkerPref::set($worker->id, 'assist_mode', 0);
	}

	// Imposter mode
	
	function suAction() {
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(!$active_worker->is_superuser)
			return;
		
		if($active_worker->id == $worker_id)
			return;
		
		if(null != ($switch_worker = DAO_Worker::get($worker_id))) {
			/*
			 * Log activity (worker.impersonated)
			 */
			$ip_address = DevblocksPlatform::getClientIp() ?: 'an unknown IP';
			
			$entry = array(
				//{{actor}} impersonated {{target}} from {{ip}}
				'message' => 'activities.worker.impersonated',
				'variables' => array(
					'target' => $switch_worker->getName(),
					'ip' => $ip_address,
					),
				'urls' => array(
					'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_WORKER, $switch_worker->id),
					)
			);
			CerberusContexts::logActivity('worker.impersonated', CerberusContexts::CONTEXT_WORKER, $worker_id, $entry);
			
			// Imposter
			if($visit->isImposter() && $imposter = $visit->getImposter()) {
				if($worker_id == $imposter->id) {
					$visit->setImposter(null);
				}
			} else if(!$visit->isImposter()) {
				$visit->setImposter($active_worker);
			}
			
			$visit->setWorker($switch_worker);
		}
	}
	
	function suRevertAction() {
		$visit = CerberusApplication::getVisit();
		
		if($visit->isImposter()) {
			if(null != ($imposter = $visit->getImposter())) {
				$visit->setWorker($imposter);
				$visit->setImposter(null);
			}
		}
		
	}
	
	function initConnectionsViewAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$to_context = DevblocksPlatform::importGPC($_REQUEST['to_context'],'string');
		
		if(empty($context) || empty($context_id) || empty($to_context))
			return;
			
		if(null == ($ext_context = Extension_DevblocksContext::get($to_context)))
			return;
			
		if(null != ($view = $ext_context->getView($context, $context_id))) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('view', $view);
			$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
			$tpl->clearAssign('view');
		}
	}
	
	/*
	 * Popups
	 */
	
	function showPeekPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$edit = DevblocksPlatform::importGPC($_REQUEST['edit'], 'string', null);
		
		if(null == ($context_ext = Extension_DevblocksContext::getByAlias($context, true)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextPeek))
			return;
		
		// Template
		
		$context_ext->renderPeekPopup($context_id, $view_id, $edit);
	}

	/*
	 * Permalinks
	 */
	
	function showPermalinkPopupAction() {
		@$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string','');
		
		if(empty($url))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('url', $url);
		$tpl->display('devblocks:cerberusweb.core::internal/peek/popup_peek_permalink.tpl');
	}
	
	/*
	 * Merge
	 */
	
	function showRecordsMergePopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		try {
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				throw new Exception_DevblocksValidationError("Invalid record type.");
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.merge', $context_ext->id)))
				throw new Exception_DevblocksValidationError("You do not have permission to merge these records.");
			
			if($ids) {
				$ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($ids), 'int');
				
				if(is_array($ids)) {
					$models = $context_ext->getModelObjects($ids);
					$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
					ksort($dicts);
					
					$tpl->assign('dicts', $dicts);
				}
			}
			$tpl->assign('aliases', $context_ext->getAliasesForContext($context_ext->manifest));
			$tpl->assign('context_ext', $context_ext);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_chooser.tpl');
			
		} catch (Exception_DevblocksValidationError $e) {
			$tpl->assign('error_message', $e->getMessage());
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			return;
			
		} catch (Exception $e) {
			$tpl->assign('error_message', 'An unexpected error occurred.');
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			return;
		}
	}
	
	function showRecordsMergeMappingPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'array',[]);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$DEBUG = false;
		
		try {
			if($ids)
				$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
			
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				throw new Exception_DevblocksValidationError("Invalid record type.");
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.merge', $context_ext->id)))
				throw new Exception_DevblocksValidationError("You do not have permission to merge these records.");
			
			if(
				empty($ids)
				|| count($ids) < 2
				|| false == ($models = $context_ext->getModelObjects($ids))
				|| count($models) < 2
				)
				throw new Exception_DevblocksValidationError("You haven't provided at least two records to merge.");
			
			$field_labels = $field_values = [];
			CerberusContexts::getContext($context_ext->id, null, $field_labels, $field_values, '', false, false);
			$field_types = $field_values['_types'];
			
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'custom_');
			
			ksort($dicts);
			
			if($DEBUG) {
				var_dump($dicts);
			}
			
			if(!($context_ext instanceof IDevblocksContextMerge))
				throw new Exception_DevblocksValidationError("This record type doesn't support merging.");
			
			$properties = $context_ext->mergeGetKeys();
			$custom_fields = DAO_CustomField::getByContext($context);
			
			// Add custom fields
			foreach(array_keys($field_labels) as $label_key) {
				if(preg_match('#^custom_\d+$#', $label_key))
					$properties[] = $label_key;
			}
			
			$field_values = [];
			
			foreach($properties as $k) {
				if(!isset($field_labels[$k]) || !isset($field_types[$k]))
					continue;
				
				$field_values[$k] = [
					'label' => $field_labels[$k],
					'type' => $field_types[$k],
					'values' => [],
				];
				
				$cfield_id = 0;
				$matches = [];
				
				if(preg_match('#^custom_(\d+)$#', $k, $matches)) {
					$cfield_id = $matches[1];
					
					// If the field doesn't exist anymore, skip
					if(!isset($custom_fields[$cfield_id]))
						continue;
				}
				
				foreach($dicts as $dict) {
					@$v = $dict->$k;
					$handled = false;
					
					// Skip null custom fields
					if(DevblocksPlatform::strStartsWith($k, 'custom_') && 0 == strlen($v))
						continue;
					
					// Label translation
					switch($field_types[$k]) {
						case 'context_url':
							if($v) {
								$dict_key_id = substr($k, 0, -6) . 'id';
								$v = sprintf("%s", $v);
							}
							break;
							
						case Model_CustomField::TYPE_CHECKBOX:
							$v = (1 == $v) ? 'yes' : 'no';
							break;
							
						case Model_CustomField::TYPE_CURRENCY:
							@$currency_id = $dict->get($k . '_currency_id');
							if($currency_id && false != ($currency = DAO_Currency::get($currency_id))) {
								$v = $currency->format($dict->$k);
							}
							break;
							
						case Model_CustomField::TYPE_DROPDOWN:
							@$options = $custom_fields[$cfield_id]->params['options'];
							
							// Ignore invalid options
							if(!in_array($v, $options)) {
								$handled = true;
							}
							break;
							
						case Model_CustomField::TYPE_FILE:
							if($v && false !== ($file = DAO_Attachment::get($v)))
								$v = sprintf("%s (%s) %s", $file->name, $file->mime_type, DevblocksPlatform::strPrettyBytes($file->storage_size));
							break;
							
						case Model_CustomField::TYPE_FILES:
							@$values = $dict->custom[$cfield_id];
							
							if(!is_array($values))
								break;
							
							$file_ids = DevblocksPlatform::parseCsvString($v);
							$ptr =& $field_values[$k]['values'];
							
							if(is_array($file_ids) && false !== ($files = DAO_Attachment::getIds($file_ids))) {
								foreach($files as $file_id => $file) {
									$ptr[$file_id] = sprintf("%s (%s) %s", $file->name, $file->mime_type, DevblocksPlatform::strPrettyBytes($file->storage_size));
								}
							}
							
							asort($ptr);
							$handled = true;
							break;
							
						case Model_CustomField::TYPE_LINK:
							if($v) {
								$dict_key_id = $k . '__label';
								$v = sprintf("%s (#%d)", $dict->$dict_key_id, $dict->$k);
							}
							break;
							
						case Model_CustomField::TYPE_LIST:
							@$values = $dict->custom[$cfield_id];
							
							if(!is_array($values))
								break;
							
							foreach($values as $v)
								$field_values[$k]['values'][$v] = $v;
							
							asort($field_values[$k]['values']);
							
							$handled = true;
							break;
							
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							@$values = $dict->custom[$cfield_id];
							@$options = $custom_fields[$cfield_id]->params['options'];
							
							if(!is_array($values))
								break;
							
							foreach($values as $v)
								if(in_array($v, $options))
									$field_values[$k]['values'][$v] = $v;
							
							asort($field_values[$k]['values']);
							
							$handled = true;
							break;
							
						case Model_CustomField::TYPE_WORKER:
							if($v && false !== ($worker = DAO_Worker::get($v)))
								$v = $worker->getName();
							break;
					}
					
					if(!$handled) {
						if(0 != strlen($v)) {
							if(false === array_search($v, $field_values[$k]['values']))
								$field_values[$k]['values'][$dict->id] = $v;
						}
					}
				}
			}
			
			// Always sort an updated column in descending order (most recent first)
			foreach(['updated', 'updated_at', 'updated_date'] as $kk) {
				if(array_key_exists($kk, $field_values)) {
					arsort($field_values[$kk]['values']);
				}
			}
			
			// Always sort statuses in order
			if(array_key_exists('status', $field_values) && in_array($context_ext->id, [CerberusContexts::CONTEXT_TICKET, CerberusContexts::CONTEXT_TASK])) {
				uasort($field_values['status']['values'], function ($a, $b) {
					$a_status_id = DAO_Ticket::getStatusIdFromText($a);
					$b_status_id = DAO_Ticket::getStatusIdFromText($b);
					return $a_status_id <=> $b_status_id;
				});
			}
			
			if($DEBUG) {
				var_dump($field_values);
			}
			
			$tpl->assign('aliases', $context_ext->getAliasesForContext($context_ext->manifest));
			$tpl->assign('context_ext', $context_ext);
			$tpl->assign('dicts', $dicts);
			$tpl->assign('field_values', $field_values);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_mapping.tpl');
			
		} catch (Exception_DevblocksValidationError $e) {
			$tpl->assign('error_message', $e->getMessage());
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			return;
			
		} catch (Exception $e) {
			$tpl->assign('error_message', 'An unexpected error occurred.');
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			return;
		}
	}
	
	function doRecordsMergeAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'array',[]);
		@$target_id = DevblocksPlatform::importGPC($_REQUEST['target_id'],'integer',0);
		@$keys = DevblocksPlatform::importGPC($_REQUEST['keys'],'array',[]);
		@$values = DevblocksPlatform::importGPC($_REQUEST['values'],'array',[]);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$DEBUG = false;
		
		try {
			if($ids)
				$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
			
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				throw new Exception_DevblocksValidationError("Invalid record type.");
			
			$aliases = $context_ext->getAliasesForContext($context_ext->manifest);
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.merge', $context_ext->id)))
				throw new Exception_DevblocksValidationError("You do not have permission to merge these records.");
			
			if(
				empty($ids)
				|| count($ids) < 2
				|| false == ($models = $context_ext->getModelObjects($ids))
				|| count($models) < 2
				)
				throw new Exception_DevblocksValidationError("You must provide at least two records to merge.");
				
			// Determine target + sources
			
			if(!in_array($target_id, $ids))
				throw new Exception_DevblocksValidationError("Invalid target record.");
			
			$source_ids = array_diff($ids, [$target_id]);
			
			$field_labels = $field_values = [];
			CerberusContexts::getContext($context_ext->id, null, $field_labels, $field_values, '', false, false);
			$field_types = $field_values['_types'];
			
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'custom_');
			
			ksort($dicts);
			
			$changeset = [];
			
			if($DEBUG) {
				var_dump($values);
			}
			
			foreach($keys as $value_key) {
				if(preg_match('#^custom\_(\d+)$#', $value_key)) {
					$cfield_id = intval(substr($value_key, 7));
					
					switch(@$field_types[$value_key]) {
						case Model_CustomField::TYPE_CHECKBOX:
							@$dict_id = $values[$value_key];
							@$value = $dicts[$dict_id]->custom[$cfield_id] ? 1 : 0;
							break;
							
						case Model_CustomField::TYPE_CURRENCY:
							@$dict_id = $values[$value_key];
							$value = $dicts[$dict_id]->get(sprintf('%s_decimal', $value_key));
							break;
							
						case Model_CustomField::TYPE_FILES:
						case Model_CustomField::TYPE_LIST:
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							if(!isset($values[$value_key])) {
								$value = [];
							} else {
								$value = $values[$value_key];
							}
							
							break;
							
						default:
							@$dict_id = $values[$value_key];
							$value = $dicts[$dict_id]->custom[$cfield_id];
							break;
					}
					
				} else {
					@$dict_id = $values[$value_key];
					
					switch(@$field_types[$value_key]) {
						case 'context_url':
							if($value_key) {
								$value_key = substr($value_key, 0, -6) . 'id';
								$value = $dicts[$dict_id]->$value_key;
							}
							break;
							
						default:
							$value = $dicts[$dict_id]->$value_key;
							break;
					}
				}
				
				$changeset[$value_key] = $value;
			}
			
			if($DEBUG) {
				var_dump($target_id);
				var_dump($source_ids);
				var_dump($changeset);
			}
			
			$dao_class = $context_ext->getDaoClass();
			$dao_fields = $custom_fields = [];
			$error = null;
			
			if(!method_exists($dao_class, 'update'))
				throw new Exception_DevblocksValidationError("Not implemented.");
			
			if(!method_exists($context_ext, 'getDaoFieldsFromKeysAndValues'))
				throw new Exception_DevblocksValidationError("Not implemented.");
			
			if(!$context_ext->getDaoFieldsFromKeysAndValues($changeset, $dao_fields, $custom_fields, $error))
				throw new Exception_DevblocksValidationError($error);
			
			if(is_array($dao_fields))
			if(!$dao_class::validate($dao_fields, $error, $target_id))
				throw new Exception_DevblocksValidationError($error);
			
			if($custom_fields)
			if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
				throw new Exception_DevblocksValidationError($error);
			
			if(!$dao_class::onBeforeUpdateByActor($active_worker, $dao_fields, $target_id, $error))
				throw new Exception_DevblocksValidationError($error);
			
			if($DEBUG) {
				var_dump($dao_fields);
				var_dump($custom_fields);
			}
			
			$dao_class::update($target_id, $dao_fields);
			$dao_class::onUpdateByActor($active_worker, $dao_fields, $target_id);
			
			if($custom_fields)
				DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $target_id, $custom_fields);
			
			if(method_exists($dao_class, 'mergeIds'))
				$dao_class::mergeIds($source_ids, $target_id);
			
			foreach($source_ids as $source_id) {
				/*
				 * Log activity (context.merge)
				 */
				$entry = [
					//{{actor}} merged {{context_label}} {{source}} into {{context_label}} {{target}}
					'message' => 'activities.record.merge',
					'variables' => [
						'context' => $context_ext->id,
						'context_label' => DevblocksPlatform::strLower($aliases['singular']),
						'source' => sprintf("%s", $dicts[$source_id]->_label),
						'target' => sprintf("%s", $dicts[$target_id]->_label),
						],
					'urls' => [
						'target' => sprintf("ctx://%s:%d/%s", $context_ext->id, $target_id, DevblocksPlatform::strToPermalink($dicts[$target_id]->_label)),
						],
				];
				CerberusContexts::logActivity('record.merge', $context_ext->id, $target_id, $entry);
			}
			
			// Fire a merge event for plugins
			$eventMgr = DevblocksPlatform::services()->event();
			
			$eventMgr->trigger(
				new Model_DevblocksEvent(
					'record.merge',
					array(
						'context' => $context_ext->id,
						'target_id' => $target_id,
						'source_ids' => $source_ids,
					)
				)
			);
			
			// Nuke the source records
			$dao_class::delete($source_ids);
			
			// Display results
			$tpl->assign('aliases', $context_ext->getAliasesForContext($context_ext->manifest));
			$tpl->assign('context_ext', $context_ext);
			$tpl->assign('target_id', $target_id);
			$tpl->assign('dicts', $dicts);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_results.tpl');
			
		} catch(Exception_DevblocksValidationError $e) {
			$tpl->assign('error_message', $e->getMessage());
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			return;
			
		} catch(Exception $e) {
			$tpl->assign('error_message', 'An unexpected error occurred.');
			$tpl->display('devblocks:cerberusweb.core::internal/merge/merge_error.tpl');
			return;
		}
	}
	
	/*
	 * Import
	 */
	
	function showImportPopupAction() {
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextImport))
			return;
		
		$tpl = DevblocksPlatform::services()->template();

		// Template
		
		$tpl->assign('layer', $layer);
		$tpl->assign('context', $context_ext->id);
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/import/popup_upload.tpl');
	}
	
	function parseImportFileAction() {
		@$csv_file = $_FILES['csv_file'];
		
		// [TODO] Return false in JSON if file is empty, etc.
		
		if(!is_array($csv_file) || !isset($csv_file['tmp_name']) || empty($csv_file['tmp_name'])) {
			exit;
		}
		
		$filename = basename($csv_file['tmp_name']);
		$newfilename = APP_TEMP_PATH . '/' . $filename;
		
		if(!rename($csv_file['tmp_name'], $newfilename)) {
			exit;
		}
		
		$visit = CerberusApplication::getVisit();
		$visit->set('import.last.csv', $newfilename);
		
		exit;
	}

	function showImportMappingPopupAction() {
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
	
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextImport))
			return;

		// Keys
		$keys = $context_ext->importGetKeys();
		$this->_filterImportCustomFields($keys);
		$tpl->assign('keys', $keys);
		
		// Read the first line from the file
		$csv_file = $visit->get('import.last.csv','');
		$fp = fopen($csv_file, 'rt');
		$columns = fgetcsv($fp);
		fclose($fp);

		$tpl->assign('columns', $columns);
		
		// Template
	
		$tpl->assign('layer', $layer);
		$tpl->assign('context', $context_ext->id);
		$tpl->assign('view_id', $view_id);
	
		$tpl->display('devblocks:cerberusweb.core::internal/import/popup_mapping.tpl');
	}
	
	private function _filterImportCustomFields(&$keys) {
		if(false == (CerberusApplication::getActiveWorker()))
			return;
		
		$custom_fields = DAO_CustomField::getAll();
		$custom_fieldsets = DAO_CustomFieldset::getAll();

		if(is_array($keys))
		foreach(array_keys($keys) as $key) {
			if(!DevblocksPlatform::strStartsWith($key, 'cf_'))
				continue;
			
			$cfield_id = substr($key, 3);
			
			if(!isset($custom_fields[$cfield_id])) {
				unset($keys[$key]);
				continue;
			}
			
			$cfield = $custom_fields[$cfield_id];
			
			if(!$cfield->custom_fieldset_id)
				continue;
			
			if(false == ($cfieldset = @$custom_fieldsets[$cfield->custom_fieldset_id])) {
				unset($keys[$key]);
				continue;
			}
			
			if($cfieldset->owner_context == CerberusContexts::CONTEXT_BOT) {
				unset($keys[$key]);
				continue;
			}
		}
	}
	
	function doImportAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$is_preview = DevblocksPlatform::importGPC($_REQUEST['is_preview'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		@$column = DevblocksPlatform::importGPC($_REQUEST['column'],'array',array());
		@$column_custom = DevblocksPlatform::importGPC($_REQUEST['column_custom'],'array',array());
		@$sync_dupes = DevblocksPlatform::importGPC($_REQUEST['sync_dupes'],'array',array());
		
		$visit = CerberusApplication::getVisit();

		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextImport))
			return;

		$view_class = $context_ext->getViewClass();
		$view = new $view_class; /* @var $view C4_AbstractView */
		
		$keys = $context_ext->importGetKeys();

		// Use the context to validate sync options, if available
		if(method_exists($context_ext, 'importValidateSync')) {
			if(true !== ($result = $context_ext->importValidateSync($sync_dupes))) {
				echo $result;
				return;
			}
		}
		
		// Counters
		$line_number = 0;
		
		// CSV
		$csv_file = $visit->get('import.last.csv','');
		
		$fp = fopen($csv_file, "rt");
		if(!$fp)
			return;
		
		// Do we need to consume a first row of headings?
		@fgetcsv($fp, 8192, ',', '"');
		
		while(!feof($fp)) {
			$parts = fgetcsv($fp, 8192, ',', '"');

			if($is_preview && $line_number > 25)
				continue;
			
			if(empty($parts) || (1==count($parts) && is_null($parts[0])))
				continue;
			
			$line_number++;

			// Snippets dictionary
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			$dict = new DevblocksDictionaryDelegate([]);
				
			foreach($parts as $idx => $part) {
				$col = 'column_' . ($idx + 1); // 0-based to 1-based
				$dict->$col = $part;
			}

			// Meta
			$meta = array(
				'line' => $parts,
				'fields' => $field,
				'columns' => $column,
				'virtual_fields' => array(),
			);
			
			$fields = array();
			$custom_fields = [];
			$sync_fields = array();
			
			foreach($field as $idx => $key) {
				if(!isset($keys[$key]))
					continue;
				
				$col = $column[$idx];

				// Are we providing custom values?
				if($col == 'custom') {
					@$val = $tpl_builder->build($column_custom[$idx], $dict);
					
				// Are we referencing a column number from the CSV file?
				} elseif(is_numeric($col)) {
					$val = $parts[$col];
				
				// Otherwise, use a literal value.
				} else {
					$val = $col;
				}

				if(0 == strlen($val))
					continue;
				
				// What type of field is this?
				$type = $keys[$key]['type'];
				$value = null;

				// Can we automatically format the value?
				
				switch($type) {
					case 'ctx_' . CerberusContexts::CONTEXT_ADDRESS:
						if($is_preview) {
							$value = $val;
						} elseif(null != ($addy = DAO_Address::lookupAddress($val, true))) {
								$value = $addy->id;
						}
						break;
						
					case 'ctx_' . CerberusContexts::CONTEXT_ORG:
						if($is_preview) {
							$value = $val;
						} elseif(null != ($org_id = DAO_ContactOrg::lookup($val, true))) {
							$value = $org_id;
						}
						break;
						
					case Model_CustomField::TYPE_CHECKBOX:
						// Attempt to interpret bool values
						if(
							false !== stristr($val, 'yes')
							|| false !== stristr($val, 'y')
							|| false !== stristr($val, 'true')
							|| false !== stristr($val, 't')
							|| intval($val) > 0
						) {
							$value = 1;
							
						} else {
							$value = 0;
						}
						break;
						
					case Model_CustomField::TYPE_DATE:
						@$value = !is_numeric($val) ? strtotime($val) : $val;
						break;
						
					case Model_CustomField::TYPE_DROPDOWN:
						// [TODO] Add where missing
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_LIST:
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_MULTI_CHECKBOX:
						$value = DevblocksPlatform::parseCsvString(str_replace(
							'\"',
							'',
							$val
						));
						break;
						
					case Model_CustomField::TYPE_MULTI_LINE:
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_NUMBER:
						$value = intval($val);
						break;
						
					case Model_CustomField::TYPE_SINGLE_LINE:
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_URL:
						$value = $val;
						break;
						
					case Model_CustomField::TYPE_WORKER:
					case 'ctx_' . CerberusContexts::CONTEXT_WORKER:
						$workers = DAO_Worker::getAllActive();

						$val_worker_id = 0;
						
						if(0 == strcasecmp($val, 'me')) {
							$val_worker_id = $active_worker->id;
						}
							
						foreach($workers as $worker_id => $worker) {
							if(!empty($val_worker_id))
								break;
							
							$worker_name = $worker->getName();
								
							if(false !== stristr($worker_name, $val)) {
								$val_worker_id = $worker_id;
							}
						}
						
						$value = $val_worker_id;
						break;
						
					default:
						$value = $val;
						break;
				}

				/* @var $context_ext IDevblocksContextImport */
				$value = $context_ext->importKeyValue($key, $value);
				
				if($is_preview) {
					echo sprintf("%s => %s<br>",
						$keys[$key]['label'],
						is_array($value) ? sprintf('[%s]', implode(', ', $value)) : $value
					);
				}
				
				if(!is_null($value)) {
					$val = $value;
					
					// Are we setting a custom field?
					$cf_id = null;
					if('cf_' == substr($key,0,3)) {
						$cf_id = substr($key,3);
					}
					
					// Is this a virtual field?
					if(substr($key,0,1) == '_') {
						$meta['virtual_fields'][$key] = $value;
						
					// ...or is it a normal DAO field?
					} else {
						if(is_null($cf_id)) {
							$fields[$key] = $value;
						} else {
							$custom_fields[$cf_id] = $value;
						}
					}
				}

				if(isset($keys[$key]['force_match']) || in_array($key, $sync_dupes)) {
					$sync_fields[] = new DevblocksSearchCriteria($keys[$key]['param'], '=', $val);
				}
			}

			if($is_preview) {
				echo "<hr>";
			}
			
			// Check for dupes
			$meta['object_id'] = null;
			
			if(!empty($sync_fields)) {
				$view->addParams($sync_fields, true);
				$view->renderLimit = 1;
				$view->renderPage = 0;
				$view->renderTotal = false;
				list($results) = $view->getData();
			
				if(!empty($results)) {
					$meta['object_id'] = key($results);
				}
			}
			
			if(!$is_preview)
				$context_ext->importSaveObject($fields, $custom_fields, $meta);
		}
		
		if(!$is_preview) {
			@unlink($csv_file); // nuke the imported file}
			$visit->set('import.last.csv',null);
		}
		
		if(!empty($view_id) && !empty($context)) {
			C4_AbstractView::setMarqueeContextImported($view_id, $context, $line_number);
		}
	}
	
	/*
	 * Links
	 */
	
	function linksOpenAction() {
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_POST['context_id'],'integer');
		@$to_context = DevblocksPlatform::importGPC($_POST['to_context'],'string');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);

		if(null == ($to_context_extension = Extension_DevblocksContext::get($to_context))
			|| null == ($from_context_extension = Extension_DevblocksContext::get($context)))
				return;
			
		$view_id = 'links_' . DevblocksPlatform::strAlphaNum($to_context_extension->id, '_', '_');
		
		if(false != ($view = $to_context_extension->getView($context, $context_id, null, $view_id))) {
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('from_context_extension', $from_context_extension);
			$tpl->assign('from_context_id', $context_id);
			$tpl->assign('to_context_extension', $to_context_extension);
			$tpl->assign('view', $view);
			$tpl->display('devblocks:cerberusweb.core::internal/profiles/profile_links_popup.tpl');
		}
	}
	
	function getLinkCountsJsonAction() {
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_POST['context_id'],'integer');
		
		$contexts = Extension_DevblocksContext::getAll(false);
		
		$counts = DAO_ContextLink::getContextLinkCounts($context, $context_id, []);
		$results = [];
		
		foreach($counts as $ext_id => $count) {
			if(false == (@$context = $contexts[$ext_id]))
				continue;
			
			$results[] = [
				'context' => $ext_id,
				'label' => $context->name,
				'count' => $count,
			];
		}
		
		DevblocksPlatform::sortObjects($results, '[label]');
		$results = array_values($results);
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($results);
	}
	
	/*
	 * Choosers
	 */
	
	function chooserOpenAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string', '');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string', '');
		@$single = DevblocksPlatform::importGPC($_REQUEST['single'],'integer',0);
		@$query = DevblocksPlatform::importGPC($_REQUEST['q'],'string', '');
		@$query_req = DevblocksPlatform::importGPC($_REQUEST['qr'],'string', '');

		if(null == ($context_extension = Extension_DevblocksContext::getByAlias($context, true)))
			return;
		
		if(false == ($view = $context_extension->getChooserView()))
			return;
		
		// Required params
		if(!empty($query_req)) {
			if(false != ($params_req = $view->getParamsFromQuickSearch($query_req)))
				$view->addParamsRequired($params_req);
		}
		
		// Query
		if(!empty($query)) {
			$view->addParamsWithQuickSearch($query, true);
			$view->renderPage = 0;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context', $context_extension);
		$tpl->assign('layer', $layer);
		$tpl->assign('view', $view);
		$tpl->assign('single', $single);
		$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__generic.tpl');
	}
	
	function chooserOpenParamsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$q = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');
		
		// [TODO] This should be able to take a simplified JSON view model
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context))) { /* @var $context_ext Extension_DevblocksContext */
			return;
		}

		if(!isset($context_ext->manifest->params['view_class']))
			return;
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(!($view instanceof $context_ext->manifest->params['view_class'])) {
			C4_AbstractViewLoader::deleteView($view_id);
			$view = null;
		}
		
		if(empty($view)) {
			if(null == ($view = $context_ext->getChooserView($view_id)))
				return;
		}
		
		if(!empty($q)) {
			$view->addParamsWithQuickSearch($q, true);
			$view->setParamsQuery($q);
			$view->renderPage = 0;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context', $context);
		$tpl->assign('layer', $layer);
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/choosers/__worklist.tpl');
	}
	
	function editorOpenTemplateAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$label_prefix = DevblocksPlatform::importGPC($_REQUEST['label_prefix'],'string', '');
		@$key_prefix = DevblocksPlatform::importGPC($_REQUEST['key_prefix'],'string', '');
		@$template = DevblocksPlatform::importGPC($_REQUEST['template'],'string');
		@$placeholders = DevblocksPlatform::importGPC($_REQUEST['placeholders'],'array',[]);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('template', $template);
		
		$tpl->assign('key_prefix', $key_prefix);
		
		$labels = $placeholders;
		$values = $merge_labels = $merge_values = [];
		
		if($context && false != ($context_ext = Extension_DevblocksContext::get($context))) {
			$tpl->assign('context_ext', $context_ext);
			
			if(empty($label_prefix))
				$label_prefix =  $context_ext->manifest->name . ' ';
			
			// Load the context dictionary for scope
			CerberusContexts::getContext($context_ext->id, null, $merge_labels, $merge_values, '', true, false);
			
			CerberusContexts::merge(
				$key_prefix,
				$label_prefix,
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		}
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/editors/__template.tpl');
	}
	
	function serializeViewAction() {
		header("Content-type: application/json");
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			echo json_encode(array(
				'view_name' => $view->name,
				'worklist_model' => C4_AbstractViewLoader::serializeViewToAbstractJson($view, $context),
			));
		}
		
		exit;
	}
	
	function chooserOpenFileAction() {
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'], 'string');
		@$single = DevblocksPlatform::importGPC($_REQUEST['single'], 'integer', 0);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('layer', $layer);
		
		// Single chooser mode?
		$tpl->assign('single', $single);
		
		$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__file.tpl');
	}

	function chooserOpenFileAjaxUploadAction() {
		@$file_name = rawurldecode($_SERVER['HTTP_X_FILE_NAME']);
		@$file_type = $_SERVER['HTTP_X_FILE_TYPE'];
		@$file_size = $_SERVER['HTTP_X_FILE_SIZE'];
		
		$url_writer = DevblocksPlatform::services()->url();
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(empty($file_name) || empty($file_size)) {
			return;
		}
		
		if(empty($file_type))
			$file_type = 'application/octet-stream';
		
		// Copy the HTTP body into a temp file
		
		$fp = DevblocksPlatform::getTempFile();
		$temp_name = DevblocksPlatform::getTempFileInfo($fp);
		
		$body_data = fopen("php://input" , "rb");
		while(!feof($body_data))
			fwrite($fp, fread($body_data, 8192));
		fclose($body_data);
		
		// Reset the temp file pointer
		fseek($fp, 0);
		
		// SHA-1 the temp file
		@$sha1_hash = sha1_file($temp_name, false);
		
		if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $file_name, $file_size))) {
			// Create a record w/ timestamp + ID
			$fields = [
				DAO_Attachment::NAME => $file_name,
				DAO_Attachment::MIME_TYPE => $file_type,
				DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
			];
			$file_id = DAO_Attachment::create($fields);
			
			// Save the file
			Storage_Attachments::put($file_id, $fp);
		}
		
		// Close the temp file
		fclose($fp);
		
		if($file_id) {
			echo json_encode([
				'id' => intval($file_id),
				'name' => $file_name,
				'type' => $file_type,
				'size' => intval($file_size),
				'size_label' => DevblocksPlatform::strPrettyBytes($file_size),
				'sha1_hash' => $sha1_hash,
				'url' => $url_writer->write(sprintf("c=files&id=%d&name=%s", $file_id, urlencode($file_name)), true),
			]);
		}
	}
	
	function chooserOpenFileLoadBundleAction() {
		@$bundle_id = DevblocksPlatform::importGPC($_REQUEST['bundle_id'], 'integer', 0);
		
		$url_writer = DevblocksPlatform::services()->url();
		
		header('Content-Type: application/json; charset=utf-8');
		
		$results = [];
		
		if(false == ($bundle = DAO_FileBundle::get($bundle_id))) {
			echo json_encode($results);
			return;
		}
		
		foreach($bundle->getAttachments() as $attachment) {
			$results[] = array(
				'id' => $attachment->id,
				'name' => $attachment->name,
				'type' => $attachment->mime_type,
				'size' => $attachment->storage_size,
				'size_label' => DevblocksPlatform::strPrettyBytes($attachment->storage_size),
				'sha1_hash' => $attachment->storage_sha1hash,
				'url' => $url_writer->write(sprintf("c=files&id=%d&name=%s", $attachment->id, urlencode($attachment->name)), true),
			);
		}
		
		echo json_encode($results);
	}
	
	function chooserOpenAvatarAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$defaults_string = DevblocksPlatform::importGPC($_REQUEST['defaults'],'string','');
		@$image_width = DevblocksPlatform::importGPC($_REQUEST['image_width'],'integer',0);
		@$image_height = DevblocksPlatform::importGPC($_REQUEST['image_height'],'integer',0);
		
		if(empty($image_width))
			$image_width = 100;
		
		if(empty($image_height))
			$image_height = 100;
		
		$url_writer = DevblocksPlatform::services()->url();
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('image_width', $image_width);
		$tpl->assign('image_height', $image_height);

		if(false != ($avatar = DAO_ContextAvatar::getByContext($context, $context_id))) {
			$contents = 'data:' . $avatar->content_type . ';base64,' . base64_encode(Storage_ContextAvatar::get($avatar));
			$tpl->assign('imagedata', $contents);
		}

		$suggested_photos = [];
		
		// Suggest more extended content
		
		$defaults = [];
		
		$tokens = explode(' ', trim($defaults_string));
		foreach($tokens as $token) {
			@list($k,$v) = explode(':', $token);
			$defaults[trim($k)] = trim($v);
		}
		
		// Per context suggestions
		
		switch($context) {
			case CerberusContexts::CONTEXT_CONTACT:
				// Suggest from the address we're adding to the new contact
				if(empty($context_id) && isset($defaults['email'])) {
					$context_id = intval($defaults['email']);
				}
				
				// Suggest from all of the contact's alternate email addys
				if($context_id && false != ($contact = DAO_Contact::get($context_id))) {
					$addys = $contact->getEmails();
					
					if(is_array($addys))
					foreach($addys as $addy) {
						$suggested_photos[] = array(
							'url' => 'https://gravatar.com/avatar/' . md5($addy->email) . '?s=100&d=404',
							'title' => 'Gravatar: ' . $addy->email,
						);
					}
				}
				
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person1.png', true),
					'title' => 'Silhouette: Male #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person3.png', true),
					'title' => 'Silhouette: Male #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person4.png', true),
					'title' => 'Silhouette: Male #3',
				);
				
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person2.png', true),
					'title' => 'Silhouette: Female #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person5.png', true),
					'title' => 'Silhouette: Female #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person6.png', true),
					'title' => 'Silhouette: Female #3',
				);
				
				break;
				
			case CerberusContexts::CONTEXT_ORG:
				if(false != ($org = DAO_ContactOrg::get($context_id))) {
					// Suggest from all of the org's top email addys w/o contacts
					$addys = $org->getEmailsWithoutContacts(10);
					
					if(is_array($addys))
					foreach($addys as $addy) {
						$suggested_photos[] = array(
							'url' => 'https://gravatar.com/avatar/' . md5($addy->email) . '?s=100&d=404',
							'title' => 'Gravatar: ' . $addy->email,
						);
					}
				}
				
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/building1.png', true),
					'title' => 'Building #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/building2.png', true),
					'title' => 'Building #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/building3.png', true),
					'title' => 'Building #3',
				);
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				// Suggest from the address we're adding to the new worker
				if(empty($context_id)) {
					if(isset($defaults['email']) && false != ($addy = DAO_Address::get($defaults['email']))) {
						$suggested_photos[] = array(
							'url' => 'https://gravatar.com/avatar/' . md5($addy->email) . '?s=100&d=404',
							'title' => 'Gravatar: ' . $addy->email,
						);
					}
					
				} else if($context_id && false != ($worker = DAO_Worker::get($context_id))) {
					if(false != ($email = $worker->getEmailString())) {
						$suggested_photos[] = array(
							'url' => 'https://gravatar.com/avatar/' . md5($email) . '?s=100&d=404',
							'title' => 'Gravatar: ' . $email,
						);
					}
				}
				
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person1.png', true),
					'title' => 'Silhouette: Male #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person3.png', true),
					'title' => 'Silhouette: Male #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person4.png', true),
					'title' => 'Silhouette: Male #3',
				);

				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person2.png', true),
					'title' => 'Silhouette: Female #1',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person5.png', true),
					'title' => 'Silhouette: Female #2',
				);
				$suggested_photos[] = array(
					'url' => $url_writer->write('c=resource&p=cerberusweb.core&f=images/avatars/person6.png', true),
					'title' => 'Silhouette: Female #3',
				);
				
				break;
		}
		
		$tpl->assign('suggested_photos', $suggested_photos);
		
		$tpl->display('devblocks:cerberusweb.core::internal/choosers/avatar_chooser_popup.tpl');
	}

	function contextAddLinksJsonAction() {
		header('Content-type: application/json');
		
		@$from_context = DevblocksPlatform::importGPC($_POST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_POST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_POST['context_id'],'array',array());

		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::setLink($context, $context_id, $from_context, $from_context_id);

		echo json_encode(true);
	}

	function contextDeleteLinksJsonAction() {
		header('Content-type: application/json');
		
		@$from_context = DevblocksPlatform::importGPC($_POST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_POST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_POST['context_id'],'array',array());

		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::deleteLink($context, $context_id, $from_context, $from_context_id);
		
		echo json_encode(true);
	}
	
	// Notifications
	
	/**
	 * Open an event, mark it read, and redirect to its URL.
	 * Used by Home->Notifications view.
	 *
	 */
	function redirectReadAction() {
		$worker = CerberusApplication::getActiveWorker();
		
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;

		array_shift($stack); // internal
		array_shift($stack); // redirectRead
		@$id = array_shift($stack); // id

		if(null != ($notification = DAO_Notification::get($id))) {
			switch($notification->context) {
				case '':
				case CerberusContexts::CONTEXT_APPLICATION:
				case CerberusContexts::CONTEXT_CUSTOM_FIELD:
				case CerberusContexts::CONTEXT_CUSTOM_FIELDSET:
				case CerberusContexts::CONTEXT_MESSAGE:
				case CerberusContexts::CONTEXT_WORKSPACE_PAGE:
				case CerberusContexts::CONTEXT_WORKSPACE_TAB:
				case CerberusContexts::CONTEXT_WORKSPACE_WIDGET:
				case CerberusContexts::CONTEXT_WORKSPACE_WORKLIST:
					// Mark as read before we redirect
					if(empty($notification->is_read)) {
						DAO_Notification::update($id, array(
							DAO_Notification::IS_READ => 1
						));
					
						DAO_Notification::clearCountCache($worker->id);
					}
					break;
			}
			
			DevblocksPlatform::redirectURL($notification->getURL());
		}
		
		DevblocksPlatform::redirectURL('');
		exit;
	}
	
	function showNotificationsBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}

		// Custom Fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_NOTIFICATION, false);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:cerberusweb.core::internal/notifications/bulk.tpl');
	}

	function startNotificationsBulkUpdateJsonAction() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = array();

		// View
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Task fields
		$is_read = trim(DevblocksPlatform::importGPC($_POST['is_read'],'string',''));

		$do = array();

		// Do: Mark Read
		if(0 != strlen($is_read))
			$do['is_read'] = $is_read;

		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Notification::ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}

	function viewNotificationsMarkReadAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());

		$active_worker = CerberusApplication::getActiveWorker();
		

		try {
			if(is_array($row_ids))
			foreach($row_ids as $row_id) {
				$row_id = intval($row_id);
				
				// Only close notifications if the current worker owns them
				if(null != ($notification = DAO_Notification::get($row_id))) {
					if($notification->worker_id == $active_worker->id) {
						
						DAO_Notification::update($notification->id, array(
							DAO_Notification::IS_READ => 1,
						));
					}
				}
				
			}
			
			DAO_Notification::clearCountCache($active_worker->id);
			
		} catch (Exception $e) {
			//
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->render();
		
		exit;
	}
	
	function viewNotificationsExploreAction() {
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
		$keys = [];
		
		$view->renderTotal = false;
		
		do {
			$models = array();
			list($results,) = $view->getData();

			if(is_array($results))
			foreach($results as $event_id => $row) {
				if($event_id==$explore_from)
					$orig_pos = $pos;

				$entry = json_decode($row[SearchFields_Notification::ENTRY_JSON], true);
				
				$content = CerberusContexts::formatActivityLogEntry($entry, 'text');
				$context = $row[SearchFields_Notification::CONTEXT];
				$context_id = $row[SearchFields_Notification::CONTEXT_ID];
				
				// Composite key
				$key = $row[SearchFields_Notification::WORKER_ID]
					. '_' . $context
					. '_' . $context_id
					;
					
				$url = $url_writer->write(sprintf("c=internal&a=redirectRead&id=%d", $row[SearchFields_Notification::ID]));
				
				if(empty($url))
					continue;
				
				if(!empty($context) && !empty($context_id)) {
					// Is this a dupe?
					if(isset($keys[$key])) {
						continue;
					} else {
						$keys[$key] = ++$pos;
					}
				} else {
					++$pos;
				}
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos;
				$model->params = array(
					'id' => $row[SearchFields_Notification::ID],
					'content' => $content,
					'url' => $url,
				);
				$models[] = $model;
			}
			
			if(!empty($models))
				DAO_ExplorerSet::createFromModels($models);

			$view->renderPage++;

		} while(!empty($results));

		// Add the manifest row
		
		DAO_ExplorerSet::set(
			$hash,
			array(
				'title' => $view->name,
				'created' => time(),
				'worker_id' => $active_worker->id,
				'total' => $pos,
				'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=profiles&k=worker&id=me&tab=notifications', true),
			),
			0
		);
		
		// Clamp the starting position based on dupe key folding
		$orig_pos = DevblocksPlatform::intClamp($orig_pos, 1, count($keys));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}

	// Context Activity Log
	
	function showTabActivityLogAction() {
		@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','target');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		
		$tpl = DevblocksPlatform::services()->template();

		if(empty($context) || empty($context_id))
			return;
		
		switch($scope) {
			case 'target':
				$params = array(
					SearchFields_ContextActivityLog::TARGET_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT,'=',$context),
					SearchFields_ContextActivityLog::TARGET_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,'=',$context_id),
				);
				break;
				
			case 'both':
				$params = array(
					array(
						DevblocksSearchCriteria::GROUP_OR,
						array(
							DevblocksSearchCriteria::GROUP_AND,
							SearchFields_ContextActivityLog::TARGET_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT,'=',$context),
							SearchFields_ContextActivityLog::TARGET_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,'=',$context_id),
						),
						array(
							DevblocksSearchCriteria::GROUP_AND,
							SearchFields_ContextActivityLog::ACTOR_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT,'=',$context),
							SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,'=',$context_id),
						),
					),
				);
				break;
				
			default:
			case 'actor':
				$params = array(
					SearchFields_ContextActivityLog::ACTOR_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT,'=',$context),
					SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,'=',$context_id),
				);
				break;
		}
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_ContextActivityLog');
		$defaults->id = 'context_activity_log_'.str_replace('.','_',$context.'_'.$context_id);
		$defaults->is_ephemeral = true;
		$defaults->view_columns = array(
			SearchFields_ContextActivityLog::CREATED
		);
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_ContextActivityLog::CREATED;
		$defaults->renderSortAsc = false;
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->addColumnsHidden(array(
				SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
				SearchFields_ContextActivityLog::ID,
			), true);
			
			$view->addParamsRequired($params, true);
			
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/activity_log/tab.tpl');
	}
	
	// Autocomplete
	
	function autocompleteAction() {
		@$callback = DevblocksPlatform::importGPC($_REQUEST['callback'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$query = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		@$term = DevblocksPlatform::importGPC($_REQUEST['term'],'string','');
		
		header('Content-Type: application/json');

		$list = [];
		
		// [TODO] Abstractly handle '(no record)' blank functionality?
		
		if(false != ($context_ext = Extension_DevblocksContext::get($context))) {
			if($context_ext instanceof IDevblocksContextAutocomplete)
				$list = $context_ext->autocomplete($term, $query);
		}
		
		echo sprintf("%s%s%s",
			!empty($callback) ? ($callback.'(') : '',
			json_encode($list),
			!empty($callback) ? (')') : ''
		);
		exit;
	}

	// Snippets
	
	function showSnippetHelpPopupAction() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/help_popup.tpl');
	}

	function snippetPasteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_ids'],'array',[]);
		$context_id = 0;

		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$token_labels = array();
		$token_values = array();
		
		if(!$id || null == ($snippet = DAO_Snippet::get($id)))
			return;
		
		if(isset($context_ids[$snippet->context]))
			$context_id = intval($context_ids[$snippet->context]);
		
		// Make sure the worker is allowed to view this context+ID
		if($snippet->context && $context_id) {
			if(!CerberusContexts::isReadableByActor($snippet->context, $context_id, $active_worker))
				return;
			
				CerberusContexts::getContext($snippet->context, $context_id, $token_labels, $token_values);
		}

		// Build template
		if($snippet->context && $context_id) {
			@$output = $tpl_builder->build($snippet->content, $token_values);
			
		} else {
			$output = $snippet->content;
		}
		
		// Increment the usage counter
		$snippet->incrementUse($active_worker->id);
		
		header('Content-Type: application/json');
		
		echo json_encode(array(
			'id' => $id,
			'context_id' => $context_id,
			'has_custom_placeholders' => !empty($snippet->custom_placeholders),
			'text' => rtrim(str_replace("\r","",$output),"\r\n") . "\n",
		));
	}
	
	function snippetPlaceholdersAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$id || false == ($snippet = DAO_Snippet:: get($id)))
			return;
		
		if(!Context_Snippet::isReadableByActor($snippet, $active_worker))
			return;

		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('snippet', $snippet);
		$tpl->assign('context_id', $context_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/paste_placeholders.tpl');
	}
	
	function snippetPlaceholdersPreviewAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_POST['context_id'],'integer',0);
		@$placeholders = DevblocksPlatform::importGPC($_POST['placeholders'],'array',array());

		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		if(!$id || false == ($snippet = DAO_Snippet:: get($id)))
			return;
		
		if(!Context_Snippet::isReadableByActor($snippet, $active_worker))
			return;

		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$custom_placeholders = $snippet->custom_placeholders;
		
		$text = $snippet->content;
		
		$labels = array();
		$values = array();
		
		if($context_id) {
			CerberusContexts::getContext($snippet->context, $context_id, $labels, $values);
		}
		
		if(is_array($custom_placeholders))
		foreach($custom_placeholders as $placeholder_key => $placeholder) {
			$value = null;
			
			if(!isset($placeholders[$placeholder_key]))
				$placeholders[$placeholder_key] = '{{' . $placeholder_key . '}}';
			
			switch($placeholder['type']) {
				case Model_CustomField::TYPE_CHECKBOX:
					@$value = $placeholders[$placeholder_key] ? true : false;
					break;
					
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_MULTI_LINE:
					@$value = $placeholders[$placeholder_key];
					break;
			}
			
			$values[$placeholder_key] = $value;
		}
		
		@$text = $tpl_builder->build($text, $values);

		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('text', $text);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/paste_placeholders_preview.tpl');
	}

	function snippetTestAction() {
		@$snippet_context = DevblocksPlatform::importGPC($_REQUEST['snippet_context'],'string','');
		@$snippet_context_id = DevblocksPlatform::importGPC($_REQUEST['snippet_context_id'],'integer',0);
		@$snippet_key_prefix = DevblocksPlatform::importGPC($_REQUEST['snippet_key_prefix'],'string','');
		@$snippet_field = DevblocksPlatform::importGPC($_REQUEST['snippet_field'],'string','');

		$content = '';
		if(isset($_REQUEST[$snippet_field]))
			$content = DevblocksPlatform::importGPC($_REQUEST[$snippet_field]);

		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$tpl = DevblocksPlatform::services()->template();

		$token_labels = $token_values = $merge_labels = $merge_values = [];

		$ctx = Extension_DevblocksContext::get($snippet_context);

		// If no ID is given, randomize one
		if(empty($snippet_context_id) && method_exists($ctx, 'getRandom'))
			$snippet_context_id = $ctx->getRandom();
		
		CerberusContexts::getContext($snippet_context, $snippet_context_id, $merge_labels, $merge_values);
		CerberusContexts::merge($snippet_key_prefix, '', $merge_labels, $merge_values, $token_labels, $token_values);

		// Add prompted placeholders to the valid tokens
		
		@$placeholder_keys = DevblocksPlatform::importGPC($_REQUEST['placeholder_keys'], 'array', array());
		@$placeholder_defaults = DevblocksPlatform::importGPC($_REQUEST['placeholder_defaults'], 'array', array());
		
		foreach($placeholder_keys as $idx => $v) {
			@$placeholder_default = $placeholder_defaults[$idx];
			$token_values[$v] =  (!empty($placeholder_default) ? $placeholder_default : ('{{' . $v . '}}'));
			$token_labels[$v] =  $token_values[$v];
		}
		
		// Tester
		
		$success = false;
		$output = '';

		if(!empty($token_values)) {
			// Tokenize
			//$tokens = $tpl_builder->tokenize($content);
			$unknown_tokens = array();
			
			//$valid_tokens = $tpl_builder->stripModifiers(array_keys($token_labels));
			
			// Test legal values
			//$unknown_tokens = array_diff($tokens, $valid_tokens);
			//$matching_tokens = array_intersect($tokens, $valid_tokens);
			
			if(!empty($unknown_tokens)) {
				$success = false;
				$output = "The following placeholders are unknown: ".
					implode(', ', $unknown_tokens);
				
			} else {
				// Try to build the template
				if(false === (@$out = $tpl_builder->build($content, $token_values))) {
					// If we failed, show the compile errors
					$errors = $tpl_builder->getErrors();
					$success= false;
					$output = @array_shift($errors);
				} else {
					// If successful, return the parsed template
					$success = true;
					$output = $out;
				}
			}
		}

		$tpl->assign('success', $success);
		$tpl->assign('output', $output);
		$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
	}

	function showSnippetBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf("contexts.%s.update.bulk", CerberusContexts::CONTEXT_SNIPPET)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_SNIPPET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/bulk.tpl');
	}
	
	function startSnippetBulkUpdateJsonAction() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = [];
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Snippet fields
		@$owner = trim(DevblocksPlatform::importGPC($_POST['owner'],'string',''));

		$do = [];
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf("contexts.%s.update.bulk", CerberusContexts::CONTEXT_SNIPPET)))
			return;
		
		// Do: Due
		if(0 != strlen($owner))
			$do['owner'] = $owner;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Snippet::ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
	
	// Views

	function viewRefreshAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->render();
		}
	}

	function viewSortByAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy']);

		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->doSortBy($sortBy);
			$view->render();
		}
	}

	function viewPageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));

		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->doPage($page);
			$view->render();
		}
	}

	private function _viewRenderInlineFilters($view, $is_custom=false, $add_mode=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view', $view);
		$tpl->assign('add_mode', $add_mode);
		
		if($is_custom)
			$tpl->assign('is_custom', true);
			
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl');
	}

	function viewAddFilterAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		@$is_custom = DevblocksPlatform::importGPC($_POST['is_custom'],'integer',0);

		@$add_mode = DevblocksPlatform::importGPC($_POST['add_mode'], 'string', null);
		@$query = DevblocksPlatform::importGPC($_POST['query'], 'string', null);
		
		@$field = DevblocksPlatform::importGPC($_POST['field'], 'string', null);
		@$oper = DevblocksPlatform::importGPC($_POST['oper'], 'string', null);
		@$value = DevblocksPlatform::importGPC($_POST['value']);
		@$replace = DevblocksPlatform::importGPC($_POST['replace'], 'string', '');
		@$field_deletes = DevblocksPlatform::importGPC($_POST['field_deletes'],'array',[]);
		
		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;

		if($is_custom && 0 != strcasecmp('cust_',substr($id,0,5)))
			$is_custom = 0;
		
		// If this is a custom worklist we want to swap the req+editable params
		if($is_custom) {
			$original_params = $view->getEditableParams();
			$view->addParams($view->getParamsRequired(), true);
		}
		
		// Nuke criteria
		if(is_array($field_deletes) && !empty($field_deletes)) {
			foreach($field_deletes as $field_delete) {
				$view->doRemoveCriteria($field_delete);
			}
		}
		
		// Remove the same param at the top level
		if($replace) {
			$view->removeParamByField($replace);
		}
		
		// Add
		switch($add_mode) {
			case 'query':
				$view->addParamsWithQuickSearch($query, false);
				break;
				
			default:
				if(!empty($field)) {
					$view->doSetCriteria($field, $oper, $value);
				}
				break;
		}

		// If this is a custom worklist we want to swap the req+editable params back
		if($is_custom) {
			$view->addParamsRequired($view->getEditableParams(), true);
			$view->addParams($original_params, true);
		}
		
		// Reset the paging when adding a filter
		$view->renderPage = 0;
		
		$this->_viewRenderInlineFilters($view, $is_custom, $add_mode);
	}

	function viewCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $id);
		
		if(DevblocksPlatform::strStartsWith($id, ['profile_widget_', 'widget_'])) {
			$error_title = "Configure the widget";
			$tpl->assign('error_title', $error_title);
			
			$error_msg = "This worklist is configured in the widget.";
			$tpl->assign('error_message', $error_msg);
			
			$tpl->display('devblocks:cerberusweb.core::internal/views/view_error.tpl');
			return;
		}

		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
		// Columns
		
		$columns = array();
		$columns_available = $view->getColumnsAvailable();
		
		// Start with the currently selected columns
		if(is_array($view->view_columns))
		foreach($view->view_columns as $token) {
			if(isset($columns_available[$token]) && !isset($columns[$token]))
				$columns[$token] = $columns_available[$token];
		}
		
		// Finally, append the remaining columns
		foreach($columns_available as $token => $col) {
			if(!isset($columns[$token]))
				if($token && $col->db_label)
					$columns[$token] = $col;
		}
		
		$tpl->assign('columns', $columns);
		
		// Custom worklists
		
		if($view->isCustom()) {
			try {
				$worklist_id = substr($view->id,5);
				
				if(!is_numeric($worklist_id))
					throw new Exception("Invalid worklist ID.");
				
				if(null == ($worklist = DAO_WorkspaceList::get($worklist_id)))
					throw new Exception("Can't load worklist.");
				
				if(null == ($workspace_tab = DAO_WorkspaceTab::get($worklist->workspace_tab_id)))
					throw new Exception("Can't load workspace tab.");
				
				if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_tab->workspace_page_id)))
					throw new Exception("Can't load workspace page.");
				
				if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker)) {
					$tpl->display('devblocks:cerberusweb.core::internal/workspaces/customize_no_acl.tpl');
					return;
				}
				
			} catch(Exception $e) {
				// [TODO] Logger
				return;
			}
		}

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view.tpl');
	}

	function viewShowCopyAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$tpl = DevblocksPlatform::services()->template();

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;

		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

		$tpl->display('devblocks:cerberusweb.core::internal/views/copy.tpl');
	}

	function viewDoCopyAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;

		@$list_title = DevblocksPlatform::importGPC($_POST['list_title'],'string', '');
		@$workspace_page_id = DevblocksPlatform::importGPC($_POST['workspace_page_id'],'integer', 0);
		@$workspace_tab_id = DevblocksPlatform::importGPC($_POST['workspace_tab_id'],'integer', 0);

		if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_page_id)))
			return;
		
		if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker))
			return;
		
		if(null == ($workspace_tab = DAO_WorkspaceTab::get($workspace_tab_id)))
			return;

		if($workspace_tab->workspace_page_id != $workspace_page->id)
			return;
		
		if(empty($list_title))
			$list_title = $translate->_('mail.workspaces.new_list');

		// Find the context
		$contexts = Extension_DevblocksContext::getAll();
		$workspace_context = '';
		$view_class = get_class($view);
		foreach($contexts as $context_id => $context) {
			if(0 == strcasecmp($context->params['view_class'], $view_class))
				$workspace_context = $context_id;
		}

		if(empty($workspace_context))
			return;

		// Save the new worklist
		$fields = [
			DAO_WorkspaceList::COLUMNS_JSON => json_encode($view->view_columns),
			DAO_WorkspaceList::CONTEXT => $workspace_context,
			DAO_WorkspaceList::NAME => $list_title,
			DAO_WorkspaceList::OPTIONS_JSON => json_encode($view->options),
			DAO_WorkspaceList::PARAMS_EDITABLE_JSON => json_encode($view->getEditableParams()),
			DAO_WorkspaceList::PARAMS_REQUIRED_JSON => json_encode($view->getParamsRequired()),
			DAO_WorkspaceList::PARAMS_REQUIRED_QUERY => $view->getParamsRequiredQuery(),
			DAO_WorkspaceList::RENDER_LIMIT => $view->renderLimit,
			DAO_WorkspaceList::RENDER_SORT_JSON => json_encode($view->getSorts()),
			DAO_WorkspaceList::RENDER_SUBTOTALS => $view->renderSubtotals,
			DAO_WorkspaceList::WORKSPACE_TAB_ID => $workspace_tab_id,
			DAO_WorkspaceList::WORKSPACE_TAB_POS => 99,
		];
		$new_id = DAO_WorkspaceList::create($fields);
		
		DAO_WorkerViewModel::deleteByViewId('cust_' . $new_id);

		$view->render();
	}

	function viewBulkUpdateWithCursorAction() {
		@$cursor = DevblocksPlatform::importGPC($_REQUEST['cursor'], 'string', '');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(empty($cursor))
			return;
		
		$tpl->assign('cursor', $cursor);
		$tpl->assign('view_id', $view_id);
		
		$total = DAO_ContextBulkUpdate::getTotalByCursor($cursor);
		$tpl->assign('total', $total);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/view_bulk_progress.tpl');
	}
	
	function viewBulkUpdateNextCursorJsonAction() {
		@$cursor = DevblocksPlatform::importGPC($_POST['cursor'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(empty($cursor))
			return;
		
		$update = DAO_ContextBulkUpdate::getNextByCursor($cursor);
		
		// We have another job
		if($update) {
			if(false == ($context_ext = Extension_DevblocksContext::get($update->context)))
				return false;
			
			// Make sure non-admin current workers have access to change these IDs, or remove them
			if(!$active_worker->is_superuser) {
				$acl_results = CerberusContexts::isWriteableByActor($update->context, $update->context_ids, $active_worker);
				
				if(is_array($acl_results)) {
					$acl_results = array_filter($acl_results, function($bool) {
						return $bool;
					});
				}
				
				$update->context_ids = array_keys($acl_results);
			}
			
			$dao_class = $context_ext->getDaoClass();
			$dao_class::bulkUpdate($update);
			
			echo json_encode(array(
				'completed' => false,
				'count' => $update->num_records,
			));
			
		// We're done
		} else {
			echo json_encode(array(
				'completed' => true,
			));
		}
	}
	
	function viewBroadcastTestAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$view->setAutoPersist(false);
		
		$view_class = get_class($view);
		
		if(false == ($context_ext = Extension_DevblocksContext::getByViewClass($view_class, true)))
			return;
		
		/* @var $context_ext IDevblocksContextBroadcast */
		if(!($context_ext instanceof IDevblocksContextBroadcast)) {
			echo "ERROR: This record type does not support broadcasts."; 
			return;
		}
		
		$search_class = $context_ext->getSearchClass();
		
		@$broadcast_to = DevblocksPlatform::importGPC($_POST['broadcast_to'],'array',[]);
		@$broadcast_subject = DevblocksPlatform::importGPC($_POST['broadcast_subject'],'string',null);
		@$broadcast_message = DevblocksPlatform::importGPC($_POST['broadcast_message'],'string',null);
		@$broadcast_format = DevblocksPlatform::importGPC($_POST['broadcast_format'],'string',null);
		@$broadcast_html_template_id = DevblocksPlatform::importGPC($_POST['broadcast_html_template_id'],'integer',0);
		@$broadcast_group_id = DevblocksPlatform::importGPC($_POST['broadcast_group_id'],'integer',0);
		@$broadcast_bucket_id = DevblocksPlatform::importGPC($_POST['broadcast_bucket_id'],'integer',0);
		
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'string','');
		
		// Filter to checked
		if('checks' == $filter && !empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria($search_class::ID, 'in', explode(',', $ids)));
		}
		
		$results = $view->getDataSample(1);
		
		if(empty($results)) {
			$success = false;
			$output = "ERROR: This worklist is empty.";
			
		} else {
			$dict = DevblocksDictionaryDelegate::instance([
				'_context' => $context_ext->id,
				'id' => current($results),
			]);
			
			$broadcast_email_id = 0;
			
			if($broadcast_to) {
				if (false == ($recipients = $context_ext->broadcastRecipientFieldsToEmails($broadcast_to, $dict))) {
					$broadcast_email_id = 0;
					
				} else {
					shuffle($recipients);
					
					if (false == ($email = DAO_Address::lookupAddress($recipients[0], true))) {
						$broadcast_email_id = 0;
					} else {
						$broadcast_email_id = $email->id;
					}
				}
			}
			
			// Load recipient placeholders
			$dict->broadcast_email__context = CerberusContexts::CONTEXT_ADDRESS;
			$dict->broadcast_email_id = $broadcast_email_id;
			$dict->broadcast_email_;
			
			// Templates
			
			if(!empty($broadcast_subject)) {
				$template = "Subject: $broadcast_subject\n\n$broadcast_message";
			} else {
				$template = "$broadcast_message";
			}
			
			$message_properties = [
				'worker_id' => $active_worker->id,
				'content' => $template,
				'content_format' => $broadcast_format,
				'group_id' => $broadcast_group_id ?: $dict->get('group_id', 0),
				'bucket_id' => $broadcast_bucket_id ?: $dict->get('bucket_id', 0),
				'html_template_id' => $broadcast_html_template_id,
			];
			
			CerberusMail::parseBroadcastHashCommands($message_properties);
			
			if(false === (@$out = $tpl_builder->build($message_properties['content'], $dict))) {
				// If we failed, show the compile errors
				$errors = $tpl_builder->getErrors();
				$success= false;
				$output = @array_shift($errors);
				
			} else {
				// If successful, return the parsed template
				$success = true;
				$output = $out;
				
				switch($broadcast_format) {
					case 'parsedown':
						// Markdown
						$output = DevblocksPlatform::parseMarkdown($output);
						
						// HTML Template
						
						$html_template = null;
						
						if($broadcast_html_template_id)
							$html_template = DAO_MailHtmlTemplate::get($broadcast_html_template_id);
						
						if(!$html_template && false != ($group = DAO_Group::get($broadcast_group_id)))
							$html_template = $group->getReplyHtmlTemplate(0);
						
						if($html_template)
							@$output = $tpl_builder->build($html_template->content, array('message_body' => $output));
						
						// HTML Purify
						$output = DevblocksPlatform::purifyHTML($output, true, true);
						break;
						
					default:
						$output = nl2br(DevblocksPlatform::strEscapeHtml($output));
						break;
				}
			}
			
			if($success) {
				$tpl->assign('content', $output);
				$tpl->display('devblocks:cerberusweb.core::internal/editors/preview_popup.tpl');
				
			} else {
				echo $output;
			}
		}
	}
	
	function viewShowExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$tpl->assign('view', $view);

		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return false;
		
		/* @var $context_ext Extension_DevblocksContext */
		
		// Check privs
		if(!$active_worker->hasPriv(sprintf("contexts.%s.export", $context_ext->id)))
			return false;
		
		// Check prefs
		
		$pref_key = sprintf("worklist.%s.export_tokens",
			$context_ext->manifest->getParam('uri', $context_ext->id)
		);
		
		if(null == ($tokens = DAO_WorkerPref::getAsJson($active_worker->id, $pref_key))) {
			$tokens = $context_ext->getCardProperties();
			
			// Push _label into the front of $tokens if not set
			if(!in_array('_label', $tokens))
				array_unshift($tokens, '_label');
		}
		
		// Template
		
		$tpl->assign('tokens', $tokens);
		
		$labels = $values = [];
		CerberusContexts::getContext($context_ext->id, null, $labels, $values, '', true, false);
		$tpl->assign('labels', $labels);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/view_export.tpl');
	}
	
	function doViewExportAction() {
		@$cursor_key = DevblocksPlatform::importGPC($_REQUEST['cursor_key'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header("Content-Type: application/json; charset=" . LANG_CHARSET_CODE);
		
		try {
			if(empty($cursor_key)) {
				@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
				@$tokens = DevblocksPlatform::importGPC($_REQUEST['tokens'], 'array', []);
				@$export_as = DevblocksPlatform::importGPC($_REQUEST['export_as'], 'string', 'csv');
				@$format_timestamps = DevblocksPlatform::importGPC($_REQUEST['format_timestamps'], 'integer', 0);
				
				if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
					return;
				
				if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
					return false;
				
				if($active_worker) {
					// Check prefs
					$pref_key = sprintf("worklist.%s.export_tokens",
						$context_ext->manifest->getParam('uri', $context_ext->id)
					);
					
					DAO_WorkerPref::setAsJson($active_worker->id, $pref_key, $tokens);
				}
				
				if(!isset($_SESSION['view_export_cursors']))
					$_SESSION['view_export_cursors']  = [];
				
				$cursor_key = sha1(serialize([$view_id, $tokens, $export_as, time()]));
				
				$_SESSION['view_export_cursors'][$cursor_key] = [
					'key' => $cursor_key,
					'view_id' => $view_id,
					'tokens' => $tokens,
					'export_as' => $export_as,
					'format_timestamps' => $format_timestamps,
					'page' => 0,
					'rows_exported' => 0,
					'completed' => false,
					'temp_file' => APP_TEMP_PATH . '/' . $cursor_key . '.tmp',
					'attachment_name' => null,
					'attachment_url' => null,
				];
			}
			
			$cursor = $this->_viewIncrementalExport($cursor_key);
			echo json_encode($cursor);
			
		} catch (Exception_DevblocksAjaxError $e) {
			echo json_encode(false);
			return;
		}
		
	}
	
	private function _viewIncrementalExport($cursor_key) {
		if(!isset($_SESSION['view_export_cursors'][$cursor_key]))
			throw new Exception_DevblocksAjaxError("Cursor not found.");
		
		// Load the cursor and do the next step, then return JSON
		$cursor =& $_SESSION['view_export_cursors'][$cursor_key];
		
		if(!is_array($cursor))
			throw new Exception_DevblocksAjaxError("Invalid cursor.");
		
		$mime_type = null;
		
		switch($cursor['export_as']) {
			case 'csv':
				$this->_viewIncrementExportAsCsv($cursor);
				$mime_type = 'text/csv';
				break;
				
			case 'json':
				$this->_viewIncrementExportAsJson($cursor);
				$mime_type = 'application/json';
				break;
				
			case 'xml':
				$this->_viewIncrementExportAsXml($cursor);
				$mime_type = 'text/xml';
				break;
		}
		
		if($cursor['completed']) {
			@$sha1_hash = sha1_file($cursor['temp_file'], false);
			$file_name = 'export.' . $cursor['export_as'];
			
			$url_writer = DevblocksPlatform::services()->url();
			
			// Move the temp file to attachments
			$fields = array(
				DAO_Attachment::NAME => $file_name,
				DAO_Attachment::MIME_TYPE => $mime_type,
				DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
				DAO_Attachment::UPDATED => time(),
			);
			
			if(false == ($id = DAO_Attachment::create($fields)))
				return false;
			
			// [TODO] This is a temporary workaround to allow workers to view exports they create
			$_SESSION['view_export_file_id'] = $id;
			
			$fp = fopen($cursor['temp_file'], 'r');
			Storage_Attachments::put($id, $fp);
			fclose($fp);
			unlink($cursor['temp_file']);
			
			unset($_SESSION['view_export_cursors'][$cursor_key]);
			
			$cursor['attachment_name'] = $file_name;
			$cursor['attachment_url'] = $url_writer->write('c=files&id=' . $id . '&name=' . $file_name);
		}
		
		return $cursor;
	}
	
	private function _viewIncrementExportAsCsv(array &$cursor) {
		$view_id = $cursor['view_id'];
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return;
		
		$view->setAutoPersist(false);
		
		$global_labels = $global_values = [];
		CerberusContexts::getContext($context_ext->id, null, $global_labels, $global_values, null, true);
		$global_types = $global_values['_types'];
		
		// Override display
		$view->view_columns = [];
		$view->renderPage = $cursor['page'];
		$view->renderLimit = 200;
		
		// Append mode to the temp file
		$fp = fopen($cursor['temp_file'], "a");
		
		// If the first page
		if(0 == $cursor['page']) {
			// Headings
			$csv_labels = [];
			
			if(is_array($cursor['tokens']))
			foreach($cursor['tokens'] as $token) {
				$csv_labels[] = trim(@$global_labels[$token]);
			}
			
			fputcsv($fp, $csv_labels);
			
			unset($csv_labels);
		}
		
		$global_labels = null;
		unset($global_labels);
		
		// Rows
		$results = $view->getDataAsObjects();
		
		$count = count($results);
		$dicts = [];
		
		$models = CerberusContexts::getModels($context_ext->id, array_keys($results));
		
		unset($results);
		
		// ACL
		$models = CerberusContexts::filterModelsByActorReadable(get_class($context_ext), $models, $active_worker);
		
		// Models->Dictionaries
		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id);
		unset($models);
		
		foreach($dicts as $dict)
			$dict->scrubKeys('_types');
		
		// Bulk lazy load the tokens across all the dictionaries with a temporary cache
		foreach($cursor['tokens'] as $token) {
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $token, true);
		}
		
		foreach($dicts as $dict) {
			$fields = [];
			
			foreach($cursor['tokens'] as $token) {
				$value = $dict->get($token);
				
				if(@$global_types[$token] == Model_CustomField::TYPE_DATE && $cursor['format_timestamps']) {
					if(empty($value)) {
						$value = '';
					} else if(is_numeric($value)) {
						$value = date('r', $value);
					}
				}
				
				if(is_array($value))
					$value = json_encode($value);
				
				if(!is_string($value) && !is_numeric($value))
					$value = '';
				
				$fields[] = $value;
			}
			
			fputcsv($fp, $fields);
		}
		
		$cursor['page']++;
		$cursor['rows_exported'] += $count;
		
		// If our page isn't full, we're done
		if($count < $view->renderLimit) {
			$cursor['completed'] = true;
		}
		
		fclose($fp);
	}
	
	private function _viewIncrementExportAsJson(array &$cursor) {
		$view_id = $cursor['view_id'];
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		if(null == ($context_mft = Extension_DevblocksContext::getByViewClass(get_class($view))))
			return;
		
		$view->setAutoPersist(false);
		
		$global_labels = array();
		$global_values = array();
		CerberusContexts::getContext($context_mft->id, null, $global_labels, $global_values, null, true);
		$global_types = $global_values['_types'];
		
		// Override display
		$view->view_columns = array();
		$view->renderPage = $cursor['page'];
		$view->renderLimit = 200;
		
		// Append mode to the temp file
		$fp = fopen($cursor['temp_file'], "a");
		
		// If the first page
		if(0 == $cursor['page']) {
			fputs($fp, "{\n\"fields\":");
			
			$fields = array();
			
			// Fields
			
			if(is_array($global_labels))
			foreach($cursor['tokens'] as $token) {
				$fields[$token] = array(
					'label' => @$global_labels[$token],
					'type' => @$global_types[$token],
				);
			}
			
			fputs($fp, json_encode($fields));
			
			fputs($fp, ",\n\"results\": [\n");
		}
		
		// Results
		
		// Rows
		$results = $view->getDataAsObjects();
		
		$count = count($results);
		$dicts = array();
		
		if($cursor['page'] > 0)
			fputs($fp, ",\n");
		
		foreach($results as $row_id => $result) {
			// Secure the exported rows
			if(!CerberusContexts::isReadableByActor($context_mft->id, $result, $active_worker))
				continue;
			
			$labels = array(); // ignore
			$values = array();
			CerberusContexts::getContext($context_mft->id, $result, $labels, $values, null, true, true);
			
			$dicts[$row_id] = DevblocksDictionaryDelegate::instance($values);
			unset($labels);
			unset($values);
		}
		
		unset($results);
			
		// Bulk lazy load the tokens across all the dictionaries with a temporary cache
		foreach($cursor['tokens'] as $token) {
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $token);
		}
		
		$objects = [];
		
		foreach($dicts as $dict) {
			$object = [];
			
			if(is_array($cursor['tokens']))
			foreach($cursor['tokens'] as $token) {
				$value = $dict->$token;
				
				if($global_types[$token] == Model_CustomField::TYPE_DATE && $cursor['format_timestamps']) {
					if(empty($value)) {
						$value = '';
					} else if (is_numeric($value)) {
						$value = date('r', $value);
					}
				}
				
				$object[$token] = $value;
			}
			
			$objects[] = $object;
			
		}
		
		$json = trim(json_encode($objects),'[]');
		fputs($fp, $json);

		$cursor['page']++;
		$cursor['rows_exported'] += $count;
		
		// If our page isn't full, we're done
		if($count < $view->renderLimit) {
			$cursor['completed'] = true;
			fputs($fp, "]\n}");
		}
		
		fclose($fp);
	}
	
	private function _viewIncrementExportAsXml(array &$cursor) {
		$view_id = $cursor['view_id'];
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		if(null == ($context_mft = Extension_DevblocksContext::getByViewClass(get_class($view))))
			return;
		
		$view->setAutoPersist(false);
		
		$global_labels = array();
		$global_values = array();
		CerberusContexts::getContext($context_mft->id, null, $global_labels, $global_values, null, true);
		$global_types = $global_values['_types'];
		
		// Override display
		$view->view_columns = array();
		$view->renderPage = $cursor['page'];
		$view->renderLimit = 200;
		
		// Append mode to the temp file
		$fp = fopen($cursor['temp_file'], "a");
		
		// If the first page
		if(0 == $cursor['page']) {
			fputs($fp, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
			fputs($fp, "<export>\n");
			
			// Meta
			
			$xml_fields = simplexml_load_string("<fields/>"); /* @var $xml SimpleXMLElement */
			
			foreach($cursor['tokens'] as $token) {
				$field = $xml_fields->addChild("field");
				$field->addAttribute('key', $token);
				$field->addChild('label', @$global_labels[$token]);
				$field->addChild('type', @$global_types[$token]);
			}
			
			$dom = dom_import_simplexml($xml_fields);
			fputs($fp, $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement));
			unset($dom);
			
			fputs($fp, "\n<results>\n");
		}
		
		// Content
		
		$results = $view->getDataAsObjects();
		
		$count = count($results);
		$dicts = array();
		
		if(is_array($results))
		foreach($results as $row_id => $result) {
			// Secure the exported rows
			if(!CerberusContexts::isReadableByActor($context_mft->id, $result, $active_worker))
				continue;
			
			$labels = array(); // ignore
			$values = array();
			CerberusContexts::getContext($context_mft->id, $result, $labels, $values, null, true, true);
			
			$dicts[$row_id] = DevblocksDictionaryDelegate::instance($values);
			unset($labels);
			unset($values);
		}
		
		unset($results);
		
		// Bulk lazy load the tokens across all the dictionaries with a temporary cache
		foreach($cursor['tokens'] as $token) {
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $token);
		}
		
		foreach($dicts as $dict) {
			$xml_result = simplexml_load_string("<result/>"); /* @var $xml SimpleXMLElement */
			
			if(is_array($cursor['tokens']))
			foreach($cursor['tokens'] as $token) {
				$value = $dict->$token;

				if($global_types[$token] == Model_CustomField::TYPE_DATE && $cursor['format_timestamps']) {
					if(empty($value)) {
						$value = '';
					} else if(is_numeric($value)) {
						$value = date('r', $value);
					}
				}
				
				if(is_array($value))
					$value = json_encode($value);
				
				if(!is_string($value) && !is_numeric($value))
					$value = '';
				
				$field = $xml_result->addChild("field", htmlspecialchars($value, ENT_QUOTES, LANG_CHARSET_CODE));
				$field->addAttribute("key", $token);
			}
			
			$dom = dom_import_simplexml($xml_result);
			fputs($fp, $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement));
		}
		
		$cursor['page']++;
		$cursor['rows_exported'] += $count;
		// If our page isn't full, we're done
		if($count < $view->renderLimit) {
			$cursor['completed'] = true;
			fputs($fp, "</results>\n");
			fputs($fp, "</export>\n");
		}
		
		fclose($fp);
	}
	
	function viewSaveCustomizeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'string');
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', []);
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);
		@$options = DevblocksPlatform::importGPC($_REQUEST['view_options'],'array', []);
		@$field_deletes = DevblocksPlatform::importGPC($_REQUEST['field_deletes'],'array',[]);
		
		// Sanitize
		$num_rows = DevblocksPlatform::intClamp($num_rows, 1, 500);
		
		// [Security] Filter custom fields
		$custom_fields = DAO_CustomField::getAll();
		foreach($columns as $idx => $column) {
			if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
				$field_id = intval(substr($column, 3));
				@$field = $custom_fields[$field_id]; /* @var $field Model_CustomField */

				// Is this a valid custom field?
				if(empty($field)) {
					unset($columns[$idx]);
					continue;
				}

				// Do we have permission to see it?
				if(!empty($field->group_id)
					&& !$active_worker->isGroupMember($field->group_id)) {
						unset($columns[$idx]);
						continue;
				}
			}
		}
		
		if(null == ($view = C4_AbstractViewLoader::getView($id)))
			return;
		
		$view->doCustomize($columns, $num_rows, $options);
		
		$is_custom = $view->isCustom();
		$is_trigger = DevblocksPlatform::strStartsWith($id, '_trigger_');
		
		if($is_custom || $is_trigger) {
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', $translate->_('views.new_list'));
			$view->name = $title;
		}
		
		if($is_custom) {
			@$params_required_query = DevblocksPlatform::importGPC($_REQUEST['params_required_query'],'string', '');
			$view->setParamsRequiredQuery($params_required_query);
		}
		
		// Reset the paging
		$view->renderPage = 0;
		
		// Handle worklists specially
		if($is_custom) {
			// Check the custom workspace
			try {
				$worklist_id = intval(substr($id,5));
				
				if(empty($worklist_id))
					throw new Exception("Invalid worklist ID.");
				
				if(null == ($list_model = DAO_WorkspaceList::get($worklist_id)))
					throw new Exception("Can't load worklist.");
				
				if(null == ($workspace_tab = DAO_WorkspaceTab::get($list_model->workspace_tab_id)))
					throw new Exception("Can't load workspace tab.");
				
				if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_tab->workspace_page_id)))
					throw new Exception("Can't load workspace page.");
				
				if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker)) {
					throw new Exception("Permission denied to edit workspace.");
				}
				
				// Nuke legacy required criteria on custom views
				if(is_array($field_deletes) && !empty($field_deletes)) {
					foreach($field_deletes as $field_delete) {
						unset($list_model->params_required[$field_delete]);
					}
				}
				
			} catch(Exception $e) {
				return;
			}
			
			// Don't auto-persist this worklist
			$view->setAutoPersist(false);
			$view->persist();
			
			// Persist
			
			$fields = [
				DAO_WorkspaceList::NAME => $title,
				DAO_WorkspaceList::OPTIONS_JSON => json_encode($options),
				DAO_WorkspaceList::COLUMNS_JSON => json_encode($view->view_columns),
				DAO_WorkspaceList::RENDER_LIMIT => $view->renderLimit,
				DAO_WorkspaceList::PARAMS_EDITABLE_JSON => json_encode([]),
				DAO_WorkspaceList::PARAMS_REQUIRED_JSON => json_encode($list_model->params_required),
				DAO_WorkspaceList::PARAMS_REQUIRED_QUERY => $params_required_query,
				DAO_WorkspaceList::RENDER_SORT_JSON => json_encode($view->getSorts()),
				DAO_WorkspaceList::RENDER_SUBTOTALS => $view->renderSubtotals,
			];
			
			DAO_WorkspaceList::update($worklist_id, $fields);
			
			DAO_WorkspaceList::onUpdateByActor($active_worker, $fields, $worklist_id);
		}
	}

	function viewShowQuickSearchPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/quick_search_popup.tpl');
	}
	
	function viewSubtotalAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$toggle = DevblocksPlatform::importGPC($_REQUEST['toggle'],'integer',0);
		@$category = DevblocksPlatform::importGPC($_REQUEST['category'],'string','');

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
			
		// Check the interface
		if(!$view instanceof IAbstractView_Subtotals)
			return;
			
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

		$fields = $view->getSubtotalFields();
		$tpl->assign('subtotal_fields', $fields);

		// If we're toggling on/off, persist our preference
		if($toggle) {
			// hidden->shown
			if(empty($view->renderSubtotals)) {
				$view->renderSubtotals = key($fields);
				
			// hidden->shown ('__' prefix means hidden w/ pref)
			} elseif('__'==substr($view->renderSubtotals,0,2)) {
				$key = ltrim($view->renderSubtotals,'_');
				// Make sure the desired key still exists
				$view->renderSubtotals = isset($fields[$key]) ? $key : key($fields);
				
			} else { // shown->hidden
				$view->renderSubtotals = '__' . $view->renderSubtotals;
				
			}
			
		} else {
			$view->renderSubtotals = $category;
			
		}
		
		// If hidden, no need to draw template
		if(empty($view->renderSubtotals) || '__'==substr($view->renderSubtotals,0,2))
			return;

		$view->renderSubtotals();
	}

	function reparentNodeAction() {
		@$child_id = DevblocksPlatform::importGPC($_REQUEST['child_id'],'integer', 0);
		@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
		
		if(null == ($child_node = DAO_DecisionNode::get($child_id)))
			exit;
		
		$nodes = DAO_DecisionNode::getByTriggerParent($child_node->trigger_id, $parent_id);
		
		// Remove current node if exists
		unset($nodes[$child_node->id]);
		
		$pos = 0;
		
		// Insert child at top of parent
		DAO_DecisionNode::update($child_id, array(
			DAO_DecisionNode::PARENT_ID => $parent_id,
			DAO_DecisionNode::POS => $pos++,
		));
		
		// Renumber children
		foreach(array_keys($nodes) as $node_id) {
			DAO_DecisionNode::update($node_id, array(
				DAO_DecisionNode::PARENT_ID => $parent_id,
				DAO_DecisionNode::POS => $pos++,
			));
		}
		
		exit;
	}
	
	function doDecisionNodeDuplicateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		if(false == ($node = DAO_DecisionNode::get($id)))
			return false;
		
		if(false == ($trigger = DAO_TriggerEvent::get($node->trigger_id)))
			return false;
		
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
	
	function showDecisionReorderPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(!empty($id) && null != ($node = DAO_DecisionNode::get($id))) {
			$trigger_id = $node->trigger_id;
			$tpl->assign('node', $node);
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			if(null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
				$tpl->assign('trigger', $trigger);
			}
		}
		
		$children = DAO_DecisionNode::getByTriggerParent($trigger_id, $id);
		$tpl->assign('children', $children);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_reorder.tpl');
	}
	
	function saveDecisionReorderPopupAction() {
		@$child_ids = DevblocksPlatform::importGPC($_REQUEST['child_id'],'array', array());
		
		if(!empty($child_ids))
		foreach($child_ids as $pos => $child_id) {
			DAO_DecisionNode::update($child_id, array(
				DAO_DecisionNode::POS => $pos,
			));
		}
	}
	
	function saveDecisionDeletePopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);

		if(!empty($id) && null != ($node = DAO_DecisionNode::get($id))) {
			if(null != ($trigger = DAO_TriggerEvent::get($node->trigger_id))) {
				// Load the trigger's tree so we can delete all children from this node
				$data = $trigger->getDecisionTreeData();
				$depths = $data['depths'];
				
				$ids_to_delete = array();

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
			}
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			DAO_TriggerEvent::delete($trigger_id);
			
		}
	}
	
	function showDecisionPopupAction() {
		@$va_id = DevblocksPlatform::importGPC($_REQUEST['va_id'],'integer', 0);
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(!empty($id)) { // Edit node
			// Model
			if(null != ($model = DAO_DecisionNode::get($id))) {
				$tpl->assign('id', $id);
				$tpl->assign('model', $model);
				$tpl->assign('trigger_id', $model->trigger_id);
				$type = $model->node_type;
				$trigger_id = $model->trigger_id;
			}
			
		} elseif(isset($_REQUEST['parent_id'])) { // Add child node
			@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
			@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			
			$tpl->assign('parent_id', $parent_id);
			$tpl->assign('type', $type);
			$tpl->assign('trigger_id', $trigger_id);
			
		} elseif(isset($_REQUEST['trigger_id'])) { // Add child node
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			
			$tpl->assign('trigger_id', $trigger_id);
			$type = 'trigger';
			
			if(empty($trigger_id)) {
				$trigger = null;
				
				$va = DAO_Bot::get($va_id);
				$tpl->assign('va', $va);
				
				$events = Extension_DevblocksEvent::getByContext($va->owner_context, false);

				// Filter the available events by VA
				$events = $va->filterEventsByAllowed($events);
				
				$tpl->assign('events', $events);
				
			} else {
				$trigger = DAO_TriggerEvent::get($trigger_id);
			}
			
		}

		if(!isset($trigger) && !empty($trigger_id))
			if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
				return;
		
		$tpl->assign('trigger', $trigger);
		
		$event = null;
		
		if($trigger)
			if(null == ($event = $trigger->getEvent()))
				return;

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
		}
	}

	function showBehaviorSimulatorPopupAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
		
		$tpl->assign('trigger', $trigger);

		if(null == ($ext_event = Extension_DevblocksEvent::get($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
			return;

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
	
	function runBehaviorSimulatorAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_POST['trigger_id'],'integer', 0);
		@$event_params_json = DevblocksPlatform::importGPC($_POST['event_params_json'],'string', '');
		@$custom_values = DevblocksPlatform::importGPC($_POST['values'],'array', []);
		
		$tpl = DevblocksPlatform::services()->template();
		$logger = DevblocksPlatform::services()->log('Bot');
		
		$logger->setLogLevel(6);
		
		ob_start();
		
		$tpl->assign('trigger_id', $trigger_id);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
		
		$tpl->assign('trigger', $trigger);
		
		if(null == ($ext_event = Extension_DevblocksEvent::get($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
			return;
		
		// Set the base event scope
		
		// [TODO] This is hacky and needs to be handled by the extensions
		
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
		
		// [TODO] Update variables/values on assocated worklists
		
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
	
	function showBehaviorExportPopupAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$node_id = DevblocksPlatform::importGPC($_REQUEST['node_id'],'integer', 0);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;

		$behavior_json = $trigger->exportToJson($node_id);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('behavior_json', $behavior_json);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_export.tpl');
	}
	
	function showBehaviorImportPopupAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$node_id = DevblocksPlatform::importGPC($_REQUEST['node_id'],'integer', 0);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;

		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('node_id', $node_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_import.tpl');
	}
	
	function showBehaviorParamsAction() {
		@$name_prefix = DevblocksPlatform::importGPC($_REQUEST['name_prefix'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);

		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('namePrefix', $name_prefix);
		
		if(null != ($trigger = DAO_TriggerEvent::get($trigger_id)))
			$tpl->assign('macro_params', $trigger->variables);
		
		$tpl->display('devblocks:cerberusweb.core::events/_action_behavior_params.tpl');
	}
	
	function showBehaviorParamsAsJsonAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);

		header('Content-Type: text/plain; charset=utf-8');
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id))) {
			echo '{}';
			return;
		}
		
		echo "{% set json = {} %}\n";
		
		foreach($trigger->variables as $var) {
			if($var['is_private'])
				continue;
			
			echo sprintf("{%% set json = dict_set(json, '%s', '') %%}\n", $var['key']);
		}
		
		echo "{{json|json_encode|json_pretty}}";
	}
	
	function showScheduleBehaviorBulkParamsAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('field_name', 'behavior_params');
		
		if(null != ($trigger = DAO_TriggerEvent::get($trigger_id)))
			$tpl->assign('variables', $trigger->variables);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/assistant/behavior_variables_entry.tpl');
	}
	
	function doDecisionAddConditionAction() {
		@$condition = DevblocksPlatform::importGPC($_REQUEST['condition'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_REQUEST['seq'],'integer', 0);
		@$nonce = DevblocksPlatform::importGPC($_REQUEST['nonce'],'string', '');

		$tpl = DevblocksPlatform::services()->template();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		if(null == ($event = Extension_DevblocksEvent::get($trigger->event_point, true)))
			return; /* @var $event Extension_DevblocksEvent */
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
		$tpl->assign('nonce', $nonce);
			
		$event->renderCondition($condition, $trigger, null, $seq);
	}
	
	function doDecisionAddActionAction() {
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_REQUEST['seq'],'integer', 0);
		@$nonce = DevblocksPlatform::importGPC($_REQUEST['nonce'],'string', '');

		$tpl = DevblocksPlatform::services()->template();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		if(null == ($event = Extension_DevblocksEvent::get($trigger->event_point, true)))
			return; /* @var $event Extension_DevblocksEvent */
			
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
		$tpl->assign('nonce', $nonce);
		
		$event->renderAction($action, $trigger, null, $seq);
	}

	function showDecisionTreeAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;

		if(null == ($event = Extension_DevblocksEvent::get($trigger->event_point, false)))
			return; /* @var $event Extension_DevblocksEvent */
			
		$is_writeable = Context_TriggerEvent::isWriteableByActor($trigger, $active_worker);
		$tpl->assign('is_writeable', $is_writeable);
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/tree.tpl');
	}
	
	function addTriggerVariableAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
		
		$tpl = DevblocksPlatform::services()->template();
		
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
	
	function saveDecisionPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', '');
		@$status_id = DevblocksPlatform::importGPC($_REQUEST['status_id'],'integer', 0);
		@$package_uri = DevblocksPlatform::importGPC($_REQUEST['package'], 'string', '');

		@$active_worker = CerberusApplication::getActiveWorker();
		
		$mode = 'build';
		
		if(!$id && $package_uri)
			$mode = 'library';
		
		switch($mode) {
			case 'library':
				@$behavior_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
				@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
				@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
				@$prompts = DevblocksPlatform::importGPC($_REQUEST['prompts'], 'array', []);
				
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
					
				} elseif(isset($_REQUEST['parent_id'])) { // Create
					@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer', 0);
					@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
					@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
					
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
						@$params = DevblocksPlatform::importGPC($_REQUEST['params'],'array',array());
						DAO_DecisionNode::update($id, array(
							DAO_DecisionNode::PARAMS_JSON => json_encode($params),
						));
						break;
						
					case 'outcome':
						@$nodes = DevblocksPlatform::importGPC($_REQUEST['nodes'],'array',array());
						
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
						@$action_ids = DevblocksPlatform::importGPC($_REQUEST['actions'],'array',array());
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

	function showDecisionNodeMenuAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);

		$tpl = DevblocksPlatform::services()->template();
		
		if(null != ($node = DAO_DecisionNode::get($id)))
			$tpl->assign('node', $node);
		
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		if(!empty($trigger_id))
			$tpl->assign('trigger_id', $trigger_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/menu.tpl');
	}
	
	function deleteDecisionNodeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		if(!empty($id)) {
			DAO_DecisionNode::delete($id);
			
		} elseif(isset($_REQUEST['trigger_id'])) {
			@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
			// [TODO] Make sure this worker owns the trigger (or is group mgr)
			if(!empty($trigger_id))
				DAO_TriggerEvent::delete($trigger_id);
		}
	}
	
	function getTriggerEventParamsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string', '');
		
		if(empty($id))
			return;
		
		if(false == ($ext = Extension_DevblocksEvent::get($id))) /* @var $ext Extension_DevblocksEvent */
			return;
		
		$ext->renderEventParams(null);
	}
	
	function showSnippetPlaceholdersAction() {
		@$name_prefix = DevblocksPlatform::importGPC($_REQUEST['name_prefix'],'string', '');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);

		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('namePrefix', $name_prefix);
		
		if(null != ($snippet = DAO_Snippet::get($id)))
			$tpl->assign('snippet', $snippet);
		
		$tpl->display('devblocks:cerberusweb.core::events/action_set_placeholder_using_snippet_params.tpl');
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
	
	function testDecisionEventSnippetsAction() {
		@$prefix = DevblocksPlatform::importGPC($_REQUEST['prefix'],'string','');
		@$response_format = DevblocksPlatform::importGPC($_REQUEST['format'],'string','');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer',0);
		
		@$placeholders_yaml = DevblocksPlatform::importVar($_REQUEST[$prefix]['placeholder_simulator_yaml'], 'string', '');
		$placeholders = DevblocksPlatform::services()->string()->yamlParse($placeholders_yaml, 0);
		
		$content = '';
		
		if(array_key_exists('field', $_REQUEST) && is_array($_REQUEST['field'])) {
			@$fields = DevblocksPlatform::importGPC($_REQUEST['field'],'array',[]);
		
			if(is_array($fields))
			foreach($fields as $field) {
				@$append = $this->_getValueFromNestedArray($field, $_REQUEST[$prefix]);
				@$append = DevblocksPlatform::importGPC($_REQUEST[$prefix][$field],'string','');
				$content .= !empty($append) ? ('[' . $field . ']: ' . PHP_EOL . $append . PHP_EOL . PHP_EOL) : '';
			}
			
		} else {
			@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'string','');
			@$content = $this->_getValueFromNestedArray($field, $_REQUEST[$prefix]);
		}
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
		
		$event = $trigger->getEvent();
		$event_model = $event->generateSampleEventModel($trigger);
		$event->setEvent($event_model, $trigger);
		$values = $event->getValues();
		
		if(is_array($placeholders))
		foreach($placeholders as $placeholder_key => $placeholder_value) {
			$values[$placeholder_key] = $placeholder_value;
		}
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$tpl = DevblocksPlatform::services()->template();
		
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
				
				if(isset($_REQUEST['is_editor'])) {
					@$is_editor = DevblocksPlatform::importGPC($_REQUEST['is_editor'],'string','');
					@$format = DevblocksPlatform::importGPC($_REQUEST[$prefix][$is_editor],'string','');
					
					switch($format) {
						case 'parsedown':
							if(false != ($output = DevblocksPlatform::parseMarkdown($output))) {

								// HTML template

								@$html_template_id = DevblocksPlatform::importGPC($_REQUEST[$prefix]['html_template_id'],'integer',0);
								$html_template = null;
								
								// Key mapping
								
								@$_group_key = DevblocksPlatform::importGPC($_REQUEST['_group_key'],'string','');
								@$_group_id = intval($values[$_group_key]);

								@$_bucket_key = DevblocksPlatform::importGPC($_REQUEST['_bucket_key'],'string','');
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
					echo DevblocksPlatform::purifyHTML($output, true, true);
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
	
	// Custom templates
	
	function showTemplatePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		if(null != ($template = DAO_DevblocksTemplate::get($id)))
			$tpl->assign('template', $template);
		
		if(DevblocksPlatform::strStartsWith($template->tag, 'portal_')) {
			list(, $portal_code) = explode('_', $template->tag, 2);
			
			if(false == ($portal = DAO_CommunityTool::getByCode($portal_code)))
				return;
			
			$tpl->assign('portal', $portal);
		}
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/templates/peek.tpl');
	}
	
	function saveTemplatePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		
		if(false == ($template = DAO_DevblocksTemplate::get($id)))
			return false;
		
		if(DevblocksPlatform::strStartsWith($template->tag, 'portal_')) {
			list(, $portal_code) = explode('_', $template->tag, 2);
			
			if(false == ($portal = DAO_CommunityTool::getByCode($portal_code)))
				return;
			
			$tpl->assign('portal', $portal);
			
			if(!Context_CommunityTool::isWriteableByActor($portal, $active_worker))
				return false;
		}
		
		if(!empty($do_delete)) {
			DAO_DevblocksTemplate::delete($id);
			
		} else {
			DAO_DevblocksTemplate::update($id, array(
				DAO_DevblocksTemplate::CONTENT => $content,
				DAO_DevblocksTemplate::LAST_UPDATED => time(),
			));
		}
		
		// Clear compiled template
		$tpl_sandbox = DevblocksPlatform::services()->templateSandbox();
		$hash_key = sprintf("devblocks:%s:%s:%s", $template->plugin_id, $template->tag, $template->path);
		$tpl->clearCompiledTemplate($hash_key, APP_BUILD);
		$tpl_sandbox->clearCompiledTemplate($hash_key, null);
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id)))
			$view->render();
	}
	
	function showImportTemplatesPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal_id = DevblocksPlatform::importGPC($_REQUEST['portal_id'],'integer',0);
		
		if(!$portal_id || false == ($portal = DAO_CommunityTool::get($portal_id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/templates/import.tpl');
	}
	
	function saveImportTemplatesPeekAction() {
		@$portal_id = DevblocksPlatform::importGPC($_POST['portal_id'],'integer',0);
		@$file_id = DevblocksPlatform::importGPC($_POST['file_id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!$portal_id || false == ($portal = DAO_CommunityTool::get($portal_id)))
				throw new Exception_DevblocksAjaxError("Invalid portal.");
			
			if(!$file_id || false == ($file = DAO_Attachment::get($file_id)))
				throw new Exception_DevblocksAjaxError("Invalid import file.");
			
			if(!Context_CommunityTool::isWriteableByActor($portal, $active_worker))
				throw new Exception_DevblocksAjaxError(DevblocksPlatform::translate('error.core.no_acl.edit'));
			
			$fp = DevblocksPlatform::getTempFile();
			$filename = DevblocksPlatform::getTempFileInfo($fp);
			
			$file->getFileContents($fp);
			
			DAO_DevblocksTemplate::importXmlFile($filename, 'portal_'.$portal->code);
			
			echo json_encode([
				'success' => true,
			]);
			
		} catch (Exception_DevblocksAjaxError $e) {
			echo json_encode([
				'error' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			echo json_encode([
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
	
	function showExportTemplatesPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$portal = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/templates/export.tpl');
	}
	
	function saveExportTemplatesPeekAction() {
		if(null == ($active_worker = CerberusApplication::getActiveWorker()) || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		@$filename = DevblocksPlatform::importGPC($_POST['filename'],'string','');
		@$author = DevblocksPlatform::importGPC($_POST['author'],'string','');
		@$email = DevblocksPlatform::importGPC($_POST['email'],'string','');
		
		// Build XML file
		$xml = simplexml_load_string(
			'<?xml version="1.0" encoding="' . LANG_CHARSET_CODE . '"?>'.
			'<cerb>'.
			'<templates>'.
			'</templates>'.
			'</cerb>'
		); /* @var $xml SimpleXMLElement */
		
		// Author
		$eAuthor = $xml->templates->addChild('author'); /* @var $eAuthor SimpleXMLElement */
		$eAuthor->addChild('name', htmlspecialchars($author));
		$eAuthor->addChild('email', htmlspecialchars($email));
		
		// Load view
		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			exit;
		
		// Load all data
		$view->renderLimit = -1;
		$view->renderPage = 0;
		$view->setAutoPersist(false);
		list($results,) = $view->getData();
		
		// Add template
		if(is_array($results))
		foreach($results as $result) {
			// Load content
			if(null == ($template = DAO_DevblocksTemplate::get($result[SearchFields_DevblocksTemplate::ID])))
				continue;

			$eTemplate = $xml->templates->addChild('template', htmlspecialchars($template->content)); /* @var $eTemplate SimpleXMLElement */
			$eTemplate->addAttribute('plugin_id', htmlspecialchars($template->plugin_id));
			$eTemplate->addAttribute('path', htmlspecialchars($template->path));
		}
		
		// Format download file
		$imp = new DOMImplementation;
		$doc = $imp->createDocument("", "");
		$doc->encoding = LANG_CHARSET_CODE;
		$doc->formatOutput = true;
		
		$simplexml = dom_import_simplexml($xml); /* @var $dom DOMElement */
		$simplexml = $doc->importNode($simplexml, true);
		$simplexml = $doc->appendChild($simplexml);

		header("Content-type: text/xml");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		
		echo $doc->saveXML();
		exit;
	}
};
