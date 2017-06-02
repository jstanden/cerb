<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;

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
	
	function getBotInteractionsMenuAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('global', [], $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		
		$tpl->assign('global_interactions_menu', $interactions_menu);
		
		$tpl->display('devblocks:cerberusweb.core::console/bot_interactions_menu.tpl');
	}
	
	function startBotInteractionAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();
		
		@$interaction = DevblocksPlatform::importGPC($_REQUEST['interaction'], 'string', '');
		@$interaction_behavior_id = DevblocksPlatform::importGPC($_REQUEST['behavior_id'], 'integer', 0);
		@$browser = DevblocksPlatform::importGPC($_REQUEST['browser'], 'array', []);
		@$interaction_params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'], 'string', null);
		
		$session = DevblocksPlatform::getSessionService();
		
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
		
		if(false !== ($client_user_agent_parts = UserAgentParser::parse())) {
			$client_platform = @$client_user_agent_parts['platform'] ?: '';
			$client_browser = @$client_user_agent_parts['browser'] ?: '';
			$client_browser_version = @$client_user_agent_parts['version'] ?: '';
		}
		
		$event_model = new Model_DevblocksEvent(
			Event_NewInteractionChatWorker::ID,
			array(
				'worker' => $active_worker,
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
		
		$result = $interaction_behavior->runDecisionTree($dict, false, $event);
		
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
		
		$url_writer = DevblocksPlatform::getUrlService();
		$bot_image_url = $url_writer->write(sprintf("c=avatars&w=bot&id=%d", $bot->id) . '?v=' . $bot->updated_at);
		
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		
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
		$tpl = DevblocksPlatform::getTemplateService();
		
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
		
		$actions = array();
		
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
			$interaction->session_data['behavior_has_parent'] = 0;
			
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
			switch(@$params['_action']) {
				case 'behavior.switch':
					@$behavior_return = $params['behavior_return'];
					
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
					
					$interaction->session_data['behavior_id'] = $new_behavior->id;
					$interaction->session_data['behaviors'][$new_behavior->id]['dict'] = [];
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
					@$style = $params['style'];
					
					if(!is_array($options))
						break;
					
					$tpl->assign('options', $options);
					$tpl->assign('style', $style);
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_buttons.tpl');
					break;
					
				case 'prompt.text':
					@$placeholder = $params['placeholder'];
					
					if(empty($placeholder))
						$placeholder = 'say something';
					
					$tpl->assign('delay_ms', 0);
					$tpl->assign('placeholder', $placeholder);
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
					$q = @$params['q'] ?: null;
					
					if(!$context || false == ($context_ext = Extension_DevblocksContext::get($context)))
						break;
					
					// Open popup
					$tpl->assign('context', $context_ext->id);
					$tpl->assign('delay_ms', 0);
					$tpl->assign('q', $q);
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

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences')));
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
		$active_worker = CerberusApplication::getActiveWorker();
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
			$tpl = DevblocksPlatform::getTemplateService();
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
		
		if(null == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		if(!($context_ext instanceof IDevblocksContextPeek))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();

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
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('url', $url);
		$tpl->display('devblocks:cerberusweb.core::internal/peek/popup_peek_permalink.tpl');
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
		
		$tpl = DevblocksPlatform::getTemplateService();

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
	
		$tpl = DevblocksPlatform::getTemplateService();
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
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		$custom_fields = DAO_CustomField::getAll();
		$custom_fieldsets = DAO_CustomFieldset::getAll();

		if(is_array($keys))
		foreach($keys as $key => $field) {
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
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$is_preview = DevblocksPlatform::importGPC($_REQUEST['is_preview'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		@$column = DevblocksPlatform::importGPC($_REQUEST['column'],'array',array());
		@$column_custom = DevblocksPlatform::importGPC($_REQUEST['column_custom'],'array',array());
		@$sync_dupes = DevblocksPlatform::importGPC($_REQUEST['sync_dupes'],'array',array());
		
		$visit = CerberusApplication::getVisit();
		$db = DevblocksPlatform::getDatabaseService();

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
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			$dict = new DevblocksDictionaryDelegate(array());
				
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
			$custom_fields = array();
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
						
					case Model_CustomField::TYPE_MULTI_CHECKBOX:
						// [TODO] Add where missing
						$value = $val;
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

						$worker_names = array();
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
						$value
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
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$to_context = DevblocksPlatform::importGPC($_REQUEST['to_context'],'string');

		if(null == ($to_context_extension = Extension_DevblocksContext::get($to_context))
			|| null == ($from_context_extension = Extension_DevblocksContext::get($context)))
				return;
		
		$view_id = 'links_' . DevblocksPlatform::strAlphaNum($to_context_extension->id, '_', '_');
			
		if(false != ($view = $to_context_extension->getView($context, $context_id, null, $view_id))) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('from_context_extension', $from_context_extension);
			$tpl->assign('from_context_id', $context_id);
			$tpl->assign('to_context_extension', $to_context_extension);
			$tpl->assign('view', $view);
			$tpl->display('devblocks:cerberusweb.core::internal/profiles/profile_links_popup.tpl');
		}
	}
	
	function getLinkCountsJsonAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		
		$contexts = Extension_DevblocksContext::getAll(false);
		
		$counts = DAO_ContextLink::getContextLinkCounts($context, $context_id, [CerberusContexts::CONTEXT_CUSTOM_FIELDSET]);
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

		if(null == ($context_extension = DevblocksPlatform::getExtension($context, true)))
			return;
		
		if(false == ($view = $context_extension->getChooserView()))
			return;
		
		// Required params
		if(!empty($query_req)) {
			if(false != ($params_req = $view->getParamsFromQuickSearch($query_req)))
				$view->addParamsRequired($params_req);
		}
		
		// Query
		if(!empty($query))
			$view->addParamsWithQuickSearch($query, true);
			
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('context', $context_extension);
		$tpl->assign('layer', $layer);
		$tpl->assign('view', $view);
		$tpl->assign('quick_search_query', $query);
		$tpl->assign('single', $single);
		$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__generic.tpl');
	}
	
	function chooserOpenSnippetAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$contexts = DevblocksPlatform::importGPC($_REQUEST['contexts'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$single = DevblocksPlatform::importGPC($_REQUEST['single'], 'integer', 0);

		if(null != ($context_extension = DevblocksPlatform::getExtension($context, true))) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('context', $context_extension);
			$tpl->assign('layer', $layer);
			
			$view = $context_extension->getChooserView();
			
			// If we're being given contexts to filter down to
			if(!empty($contexts)) {
				$target_contexts = DevblocksPlatform::parseCsvString($contexts);
				$contexts = array('');
				$dicts = array();
				
				if(is_array($target_contexts))
				foreach($target_contexts as $target_context_pair) {
					@list($target_context, $target_context_id) = explode(':', $target_context_pair);

					if(!empty($target_context_id)) {
						// Load the context dictionary for scope
						$labels = array();
						$values = array();
						CerberusContexts::getContext($target_context, $target_context_id, $labels, $values);
	
						$dicts[$target_context] = $values;
					}
					
					// Stack filters for the view
					if(!empty($target_context))
						$contexts[] = $target_context;
				}
				
				// Filter the snippet worklist by target contexts
				if(!empty($contexts)) {
					$view->addParamsRequired(array(
						SearchFields_Snippet::CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT, DevblocksSearchCriteria::OPER_IN, $contexts)
					));
				}
				
				if(!empty($dicts)) {
					$placeholder_values = $view->getPlaceholderValues();
					$placeholder_values['dicts'] = $dicts;
					$view->setPlaceholderValues($placeholder_values);
				}
			}
			
			$tpl->assign('view', $view);
			$tpl->assign('single', $single);
			
			$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__snippet.tpl');
		}
	}

	function chooserOpenParamsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$layer = DevblocksPlatform::importGPC($_REQUEST['layer'],'string');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer',0);
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
		
		// Add placeholders
		
		if(!empty($trigger_id) && null != ($trigger = DAO_TriggerEvent::get($trigger_id))) {
			$event = $trigger->getEvent();
			
			if(method_exists($event,'generateSampleEventModel')) {
				$event_model = $event->generateSampleEventModel($trigger);
				$event->setEvent($event_model, $trigger);
				$values = $event->getValues();
				$view->setPlaceholderValues($values);
			}
			
			$conditions = $event->getConditions($trigger);
			$valctx = $event->getValuesContexts($trigger);
			foreach($valctx as $token => $vtx) {
				$conditions[$token] = $vtx;
			}

			foreach($conditions as $cond_id => $cond) {
				if(substr($cond_id,0,1) == '_')
					unset($conditions[$cond_id]);
			}
			
			$view->setPlaceholderLabels($conditions);
			
		} elseif(null != $active_worker = CerberusApplication::getActiveWorker()) {
			$labels = array();
			$values = array();
			$active_worker->getPlaceholderLabelsValues($labels, $values);
			
			$view->setPlaceholderLabels($labels);
			$view->setPlaceholderValues($values);
		}
		
		if(!empty($q)) {
			$view->addParamsWithQuickSearch($q, true);
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('context', $context);
		$tpl->assign('layer', $layer);
		$tpl->assign('view', $view);
		$tpl->assign('quick_search_query', $q);
		$tpl->display('devblocks:cerberusweb.core::internal/choosers/__worklist.tpl');
	}
	
	function editorOpenTemplateAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$template = DevblocksPlatform::importGPC($_REQUEST['template'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		
		if(false == ($context_ext = DevblocksPlatform::getExtension($context, true)))
			return;
		
		$tpl->assign('context_ext', $context_ext);
		$tpl->assign('template', $template);
		
		// Load the context dictionary for scope
		$labels = array();
		$values = array();
		CerberusContexts::getContext($context_ext->id, null, $labels, $null, '', true, false);

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

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('layer', $layer);
		
		// Single chooser mode?
		$tpl->assign('single', $single);
		
		$tpl->display('devblocks:cerberusweb.core::context_links/choosers/__file.tpl');
	}

	function chooserOpenFileUploadAction() {
		@$files = $_FILES['file_data'];
		@$bundle_ids = DevblocksPlatform::importGPC($_REQUEST['bundle_ids'], 'array:integer', array());
		
		$url_writer = DevblocksPlatform::getUrlService();
		
		$results = array();

		// File uploads
		
		if(is_array($files) && isset($files['tmp_name']))
		foreach(array_keys($files['tmp_name']) as $file_idx) {
			$file_name = $files['name'][$file_idx];
			$file_type = $files['type'][$file_idx];
			$file_size = $files['size'][$file_idx];
			$file_tmp_name = $files['tmp_name'][$file_idx];
		
			if(empty($file_tmp_name) || empty($file_name))
				continue;
			
			@$sha1_hash = sha1_file($file_tmp_name, false);
			
			if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $file_name, $file_size))) {
				// Create a record w/ timestamp + ID
				$fields = array(
					DAO_Attachment::NAME => $file_name,
					DAO_Attachment::MIME_TYPE => $file_type,
					DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
				);
				$file_id = DAO_Attachment::create($fields);
				
				// Save the file
				if(null !== ($fp = fopen($file_tmp_name, 'rb'))) {
					Storage_Attachments::put($file_id, $fp);
					fclose($fp);
				}
			}
			
			if($file_id) {
				$results[] = array(
					'id' => $file_id,
					'name' => $file_name,
					'type' => $file_type,
					'size' => $file_size,
					'sha1_hash' => $sha1_hash,
					'url' => $url_writer->write(sprintf("c=files&id=%d&name=%s", $file_id, urlencode($file_name)), true),
				);
			}
			
			@unlink($file_tmp_name);
		}
		
		// Bundles
		
		if(is_array($bundle_ids) && !empty($bundle_ids)) {
			$bundles = DAO_FileBundle::getIds($bundle_ids);

			if(is_array($bundles))
			foreach($bundles as $bundle) {
				$attachments = $bundle->getAttachments();
				
				if(is_array($attachments))
				foreach($attachments as $attachment) { /* @var $attachment Model_Attachment */
					$results[] = array(
						'id' => $attachment->id,
						'name' => $attachment->name,
						'type' => $attachment->mime_type,
						'size' => $attachment->storage_size,
						'sha1_hash' => $attachment->storage_sha1hash,
						'url' => $url_writer->write(sprintf("c=files&id=%d&name=%s", $attachment->id, urlencode($attachment->name)), true),
					);
				}
			}
		}

		// JSON
		
		echo json_encode($results);
	}
	
	function chooserOpenAvatarAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$defaults_string = DevblocksPlatform::importGPC($_REQUEST['defaults'],'string','');
		
		$url_writer = DevblocksPlatform::getUrlService();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		if(false != ($avatar = DAO_ContextAvatar::getByContext($context, $context_id))) {
			$contents = 'data:' . $avatar->content_type . ';base64,' . base64_encode(Storage_ContextAvatar::get($avatar));
			$tpl->assign('imagedata', $contents);
		}

		$suggested_photos = array();
		
		// Suggest more extended content
		
		$defaults = array();
		
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
		
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());

		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::setLink($context, $context_id, $from_context, $from_context_id);

		echo json_encode(true);
	}

	function contextDeleteLinksJsonAction() {
		header('Content-type: application/json');
		
		@$from_context = DevblocksPlatform::importGPC($_REQUEST['from_context'],'string','');
		@$from_context_id = DevblocksPlatform::importGPC($_REQUEST['from_context_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_ids = DevblocksPlatform::importGPC($_REQUEST['context_id'],'array',array());

		if(is_array($context_ids))
		foreach($context_ids as $context_id)
			DAO_ContextLink::deleteLink($context, $context_id, $from_context, $from_context_id);
		
		echo json_encode(true);
	}
	
	// Notifications
	
	function openNotificationsPopupAction() {
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			return;
		
		if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_NOTIFICATION)))
			return;
		
		$translate = DevblocksPlatform::getTranslationService();
		$view_id = 'my_notifications';
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Notification');
		$defaults->id = $view_id;
		$defaults->name = vsprintf($translate->_('home.my_notifications.view.title'), $active_worker->getName());
		$defaults->view_columns = array(
			SearchFields_Notification::CREATED_DATE,
			SearchFields_Notification::IS_READ,
		);
		$defaults->renderSubtotals = SearchFields_Notification::ACTIVITY_POINT;
		$defaults->renderSortBy = SearchFields_Notification::CREATED_DATE;
		$defaults->renderSortAsc = false;
		$defaults->renderLimit = 10;
		$defaults->is_ephemeral = false;
		$defaults->paramsEditable = array(
			new DevblocksSearchCriteria(SearchFields_Notification::IS_READ, DevblocksSearchCriteria::OPER_EQ, 0),
		);
		
		if(false == ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults)))
			return;
		
		$view->addParamsRequired(array(
			SearchFields_Notification::WORKER_ID => new DevblocksSearchCriteria(SearchFields_Notification::WORKER_ID, DevblocksSearchCriteria::OPER_EQ, $active_worker->id),
		), true);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('popup_title', DevblocksPlatform::translateCapitalized('common.notifications'));
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::search/popup.tpl');
	}

	// Context Activity Log
	
	function showTabActivityLogAction() {
		@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','target');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string');
		
		$tpl = DevblocksPlatform::getTemplateService();

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
			
			$view->addParamsHidden(array(
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

		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		$list = array();
		
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
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/help_popup.tpl');
	}

	function showTabSnippetsAction() {
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('owner_context', $context);
		$tpl->assign('owner_context_id', $context_id);
		
		$view_id = str_replace('.','_',$point) . '_snippets';
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(null == $view) {
			$view = new View_Snippet();
			$view->id = $view_id;
			$view->name = 'Snippets';
		}
		
		if($active_worker->is_superuser && 0 == strcasecmp($context, 'all')) {
			$view->addParamsRequired(array(), true);
		} else {
			$view->addParamsRequired(array(
				SearchFields_Snippet::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT, DevblocksSearchCriteria::OPER_EQ, $context),
				SearchFields_Snippet::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Snippet::OWNER_CONTEXT_ID, DevblocksSearchCriteria::OPER_EQ, $context_id),
			), true);
		}
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	function snippetPasteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$token_labels = array();
		$token_values = array();

		if(null != ($snippet = DAO_Snippet::get($id))) {
			// Make sure the worker is allowed to view this context+ID
			if(!empty($snippet->context)) {
				if(!CerberusContexts::isReadableByActor($snippet->context, $context_id, $active_worker))
					return;
			}
			
			CerberusContexts::getContext($snippet->context, $context_id, $token_labels, $token_values);

			$snippet->incrementUse($active_worker->id);
		}

		// Build template
		if(!empty($context_id)) {
			$output = $tpl_builder->build($snippet->content, $token_values);
			
		} else {
			$output = $snippet->content;
		}
		
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

		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('snippet', $snippet);
		$tpl->assign('context_id', $context_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/paste_placeholders.tpl');
	}
	
	function snippetPlaceholdersPreviewAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$placeholders = DevblocksPlatform::importGPC($_REQUEST['placeholders'],'array',array());

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$id || false == ($snippet = DAO_Snippet:: get($id)))
			return;
		
		if(!Context_Snippet::isReadableByActor($snippet, $active_worker))
			return;

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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
		
		$text = $tpl_builder->build($text, $values);

		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('text', $text);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/paste_placeholders_preview.tpl');
	}

	function snippetTestAction() {
		@$snippet_context = DevblocksPlatform::importGPC($_REQUEST['snippet_context'],'string','');
		@$snippet_context_id = DevblocksPlatform::importGPC($_REQUEST['snippet_context_id'],'integer',0);
		@$snippet_field = DevblocksPlatform::importGPC($_REQUEST['snippet_field'],'string','');

		$content = '';
		if(isset($_REQUEST[$snippet_field]))
			$content = DevblocksPlatform::importGPC($_REQUEST[$snippet_field]);

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$tpl = DevblocksPlatform::getTemplateService();

		$token_labels = array();
		$token_value = array();

		$ctx = Extension_DevblocksContext::get($snippet_context);

		// If no ID is given, randomize one
		if(empty($snippet_context_id) && method_exists($ctx, 'getRandom'))
			$snippet_context_id = $ctx->getRandom();
		
		CerberusContexts::getContext($snippet_context, $snippet_context_id, $token_labels, $token_values);

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
			$tokens = $tpl_builder->tokenize($content);
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
				if(false === ($out = $tpl_builder->build($content, $token_values))) {
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
		
		if(!$active_worker->is_superuser)
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
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
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Snippet fields
		@$owner = trim(DevblocksPlatform::importGPC($_POST['owner'],'string',''));

		$do = array();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Do: Due
		if(0 != strlen($owner))
			$do['owner'] = $owner;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
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

	function viewGetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$tpl->assign('view', $view);

			// [TODO] Detect if we're customizing (swap Editable for Required)
			
			// Do we already have this filter to re-edit?
			$params = $view->getEditableParams();
			
			if(false != ($results = $view->findParam($field, $params, false))) {
				$param = array_shift($results);
				$tpl->assign('param', $param);
			}

			// Render from the View_* implementation.
			$view->renderCriteria($field);
		}
	}

	private function _viewRenderInlineFilters($view, $is_custom=false, $add_mode=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view', $view);
		$tpl->assign('add_mode', $add_mode);
		
		if($is_custom)
			$tpl->assign('is_custom', true);
			
		$tpl->display('devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl');
	}

	// Ajax
	
	function viewToggleFiltersAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$show = DevblocksPlatform::importGPC($_REQUEST['show'],'integer',0);
		
		if(null != ($view = C4_AbstractViewLoader::getView($id))) {
			$view->renderFilters = !empty($show) ? 1 : 0;
		}
	}
	
	function viewAddFilterAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$is_custom = DevblocksPlatform::importGPC($_REQUEST['is_custom'],'integer',0);

		@$add_mode = DevblocksPlatform::importGPC($_REQUEST['add_mode'], 'string', null);
		@$query = DevblocksPlatform::importGPC($_REQUEST['query'], 'string', null);
		
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'], 'string', null);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper'], 'string', null);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		@$replace = DevblocksPlatform::importGPC($_REQUEST['replace'], 'integer', 0);
		@$field_deletes = DevblocksPlatform::importGPC($_REQUEST['field_deletes'],'array',array());
		
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
			$view->removeParamByField($field);
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $id);

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

		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

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

		// View params inside the list for quick render overload
		$list_view = new Model_WorkspaceListView();
		$list_view->title = $list_title;
		$list_view->options = $view->options;
		$list_view->num_rows = $view->renderLimit;
		$list_view->columns = $view->view_columns;
		$list_view->params = $view->getEditableParams();
		$list_view->params_required = $view->getParamsRequired();
		$list_view->sort_by = $view->renderSortBy;
		$list_view->sort_asc = $view->renderSortAsc;
		$list_view->subtotals = $view->renderSubtotals;

		// Save the new worklist
		$fields = array(
			DAO_WorkspaceList::WORKSPACE_TAB_ID => $workspace_tab_id,
			DAO_WorkspaceList::CONTEXT => $workspace_context,
			DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
			DAO_WorkspaceList::LIST_POS => 99,
		);
		$list_id = DAO_WorkspaceList::create($fields);

		$view->render();
	}

	function viewBulkUpdateWithCursorAction() {
		@$cursor = DevblocksPlatform::importGPC($_REQUEST['cursor'], 'string', '');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(empty($cursor))
			return;
		
		$tpl->assign('cursor', $cursor);
		$tpl->assign('view_id', $view_id);
		
		$total = DAO_ContextBulkUpdate::getTotalByCursor($cursor);
		$tpl->assign('total', $total);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/view_bulk_progress.tpl');
	}
	
	function viewBulkUpdateNextCursorJsonAction() {
		@$cursor = DevblocksPlatform::importGPC($_REQUEST['cursor'], 'string', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(empty($cursor))
			return;
		
		$update = DAO_ContextBulkUpdate::getNextByCursor($cursor);
		
		// We have another job
		if($update) {
			if(false == ($context_ext = Extension_DevblocksContext::get($update->context)))
				return false;
			
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
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		if(false == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$view->setAutoPersist(false);
		
		$view_class = get_class($view);
		
		if(false == ($context_ext = Extension_DevblocksContext::getByViewClass($view_class, true)))
			return;
		
		$dao_class = $context_ext->getDaoClass();
		$search_class = $context_ext->getSearchClass();
		
		$pkey = $search_class::getPrimaryKey();

		$tpl = DevblocksPlatform::getTemplateService();
		
		@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
		@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
		@$broadcast_format = DevblocksPlatform::importGPC($_REQUEST['broadcast_format'],'string',null);
		@$broadcast_html_template_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_html_template_id'],'integer',0);
		@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);
		
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'string','');
		
		// Filter to checked
		if('checks' == $filter && !empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria($pkey,'in',explode(',', $ids)));
		}
		
		$results = $view->getDataSample(1);
		
		if(empty($results)) {
			$success = false;
			$output = "There aren't any rows in this view!";
			
		} else {
			@$model = $dao_class::get(current($results));
			
			// Try to build the template
			CerberusContexts::getContext($context_ext->id, $model, $token_labels, $token_values);
			$dict = DevblocksDictionaryDelegate::instance($token_values);
			
			// [TODO] Hack!!!
			switch($context_ext->id) {
				case CerberusContexts::CONTEXT_DOMAIN:
					// Load the contacts from a CSV placeholder
					$contacts = CerberusMail::parseRfcAddresses($dict->contacts_list);
					
					if(empty($contacts))
						break;
					
					shuffle($contacts);
					
					// Randomize the address
					$contact = DAO_Address::lookupAddress($contacts[0]['email'], true);
					
					$dict->contact__context = CerberusContexts::CONTEXT_ADDRESS;
					$dict->contact_id = $contact->id;
					break;
			}

			if(!empty($broadcast_subject)) {
				$template = "Subject: $broadcast_subject\n\n$broadcast_message";
			} else {
				$template = "$broadcast_message";
			}
			
			if(false === ($out = $tpl_builder->build($template, $dict))) {
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
						
						if(!$html_template && false != ($replyto = DAO_AddressOutgoing::getDefault()))
							$html_template = $replyto->getReplyHtmlTemplate();
						
						if($html_template)
							$output = $tpl_builder->build($html_template->content, array('message_body' => $output));
						
						// HTML Purify
						$output = DevblocksPlatform::purifyHTML($output, true);
						break;
						
					default:
						$output = nl2br(DevblocksPlatform::strEscapeHtml($output));
						break;
				}
			}
			
			if($success) {
				header("Content-Type: text/html; charset=" . LANG_CHARSET_CODE);
				echo sprintf('<html><head><meta http-equiv="content-type" content="text/html; charset=%s"></head><body>',
					LANG_CHARSET_CODE
				);
				echo $output;
				echo '</body></html>';
				
			} else {
				echo $output;
			}
		}
	}
	
	function viewShowExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$tpl->assign('view', $view);

		if(null == ($context_ext = Extension_DevblocksContext::getByViewClass(get_class($view), true)))
			return false;
		
		$tokens = $context_ext->getCardProperties();

		// Push _label into the front of $tokens if not set
		if(!in_array('_label', $tokens))
			array_unshift($tokens, '_label');
		
		$tpl->assign('tokens', $tokens);
		
		$labels = array();
		$values = array();
		CerberusContexts::getContext($context_ext->id, null, $labels, $null, '', true, false);
		$tpl->assign('labels', $labels);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/view_export.tpl');
	}

	function doViewExportAction() {
		@$cursor_key = DevblocksPlatform::importGPC($_REQUEST['cursor_key'], 'string', '');
		
		header("Content-Type: application/json; charset=" . LANG_CHARSET_CODE);
		
		try {
			if(empty($cursor_key)) {
				@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
				@$tokens = DevblocksPlatform::importGPC($_REQUEST['tokens'], 'array', array());
				@$export_as = DevblocksPlatform::importGPC($_REQUEST['export_as'], 'string', 'csv');
				@$format_timestamps = DevblocksPlatform::importGPC($_REQUEST['format_timestamps'], 'integer', 0);
				
				if(!isset($_SESSION['view_export_cursors']))
					$_SESSION['view_export_cursors']  = array();
				
				$cursor_key = sha1(serialize(array($view_id, $tokens, $export_as, time())));
				
				$_SESSION['view_export_cursors'][$cursor_key] = array(
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
				);
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
			
			$url_writer = DevblocksPlatform::getUrlService();
			
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
			// Headings
			$csv_labels = array();
			
			if(is_array($cursor['tokens']))
			foreach($cursor['tokens'] as $token) {
				$csv_labels[] = $global_labels[$token];
			}
			
			fputcsv($fp, $csv_labels);
			
			unset($csv_labels);
		}
		
		// Rows
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
			$fields = array();
			
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
		
		foreach($dicts as $dict) {
			$object = array();
			
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
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', array());
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);
		@$options = DevblocksPlatform::importGPC($_REQUEST['view_options'],'array', array());
		
		// Sanitize
		$num_rows = DevblocksPlatform::intClamp($num_rows, 1, 500);

		// [Security] Filter custom fields
		$custom_fields = DAO_CustomField::getAll();
		foreach($columns as $idx => $column) {
			if(substr($column, 0, 3)=="cf_") {
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
		$is_trigger = substr($id,0,9)=='_trigger_';
		
		if($is_custom || $is_trigger) {
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', $translate->_('views.new_list'));
			$view->name = $title;
		}
		
		// Reset the paging
		$view->renderPage = 0;
		
		// Handle worklists specially
		if($is_custom) { // custom workspace
			// Check the custom workspace

			try {
				$list_view_id = intval(substr($id,5));
				
				if(empty($list_view_id))
					throw new Exception("Invalid worklist ID.");
				
				if(null == ($list_model = DAO_WorkspaceList::get($list_view_id)))
					throw new Exception("Can't load worklist.");
				
				if(null == ($workspace_tab = DAO_WorkspaceTab::get($list_model->workspace_tab_id)))
					throw new Exception("Can't load workspace tab.");
				
				if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_tab->workspace_page_id)))
					throw new Exception("Can't load workspace page.");
				
				if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker)) {
					throw new Exception("Permission denied to edit workspace.");
				}
				
			} catch(Exception $e) {
				// [TODO] Logger
				$view->render();
				return;
			}
			
			// Persist Object
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $title;
			$list_view->options = $options;
			$list_view->columns = $view->view_columns;
			$list_view->num_rows = $view->renderLimit;
			$list_view->params = array();
			$list_view->params_required = $view->getParamsRequired();
			$list_view->sort_by = $view->renderSortBy;
			$list_view->sort_asc = $view->renderSortAsc;
			$list_view->subtotals = $view->renderSubtotals;

			DAO_WorkspaceList::update($list_view_id, array(
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view)
			));

			// Syndicate
			$worker_views = DAO_WorkerViewModel::getWhere(sprintf("view_id = %s", Cerb_ORMHelper::qstr($id)));

			// Update any instances of this view with the new required columns + params
			foreach($worker_views as $worker_view) { /* @var $worker_view C4_AbstractViewModel */
				$worker_view->name = $view->name;
				$worker_view->options = $view->options;
				$worker_view->view_columns = $view->view_columns;
				$worker_view->paramsRequired = $view->getParamsRequired();
				$worker_view->renderLimit = $view->renderLimit;
				DAO_WorkerViewModel::setView($worker_view->worker_id, $worker_view->id, $worker_view);
			}
		}

		$view->render();
	}

	function viewShowQuickSearchPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');

		if(null == ($view = C4_AbstractViewLoader::getView($view_id)))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
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
			
		$tpl = DevblocksPlatform::getTemplateService();
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

	/**
	 * Triggers
	 */

	function applyMacroAction() {
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$run_relative = DevblocksPlatform::importGPC($_REQUEST['run_relative'],'string','');
		@$run_date = DevblocksPlatform::importGPC($_REQUEST['run_date'],'string','');
		@$return_url = DevblocksPlatform::importGPC($_REQUEST['return_url'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($return_url) && isset($_SERVER['http_referer']))
			$return_url = $_SERVER['http_referer'];
		
		try {
			if(empty($context) || empty($context_id) || empty($macro_id))
				return;
	
			// ACL: Ensure access to the context object
			if(!CerberusContexts::isReadableByActor($context, $context_id, $active_worker))
				throw new Exception("Access denied to context.");
			
			// Load macro
			if(null == ($macro = DAO_TriggerEvent::get($macro_id))) /* @var $macro Model_TriggerEvent */
				throw new Exception("Invalid macro.");
			
			if(null == ($va = $macro->getBot()))
				throw new Exception("Invalid VA.");
			
			// ACL: Ensure the worker has access to the macro
			if(!Context_Bot::isReadableByActor($va, $active_worker))
				throw new Exception("Access denied to macro.");

			// Relative scheduling
			// [TODO] This is almost wholly redundant with saveMacroSchedulerPopup()
			
			$event = $macro->getEvent();
			$event_model = $event->generateSampleEventModel($macro, $context_id);
			$event->setEvent($event_model, $macro);
			$values = $event->getValues();
			
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			@$run_relative_timestamp = strtotime($tpl_builder->build(sprintf("{{%s|date}}",$run_relative), $values));
			
			if(empty($run_relative_timestamp))
				$run_relative_timestamp = time();
			
			// Recurring events
			// [TODO] This is almost wholly redundant with saveMacroSchedulerPopup()
			@$repeat_freq = DevblocksPlatform::importGPC($_REQUEST['repeat_freq'],'string', '');
			@$repeat_end = DevblocksPlatform::importGPC($_REQUEST['repeat_end'],'string', '');
			
			$repeat_params = array();
			
			if(!empty($repeat_freq)) {
				@$repeat_options = DevblocksPlatform::importGPC($_REQUEST['repeat_options'][$repeat_freq], 'array', array());
				@$repeat_ends = DevblocksPlatform::importGPC($_REQUEST['repeat_ends'][$repeat_end], 'array', array());
	
				switch($repeat_end) {
					case 'date':
						if(isset($repeat_ends['on'])) {
							$repeat_ends['on'] = @strtotime($repeat_ends['on']);
						}
						break;
				}
				
				$repeat_params = array(
					'freq' => $repeat_freq,
					'options' => $repeat_options,
					'end' => array(
						'term' => $repeat_end,
						'options' => $repeat_ends,
					),
				);
			}
			
			// Time
			$run_timestamp = @strtotime($run_date, $run_relative_timestamp) or time();

			// Variables
			@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_keys'],'array',array());
			@$var_vals = DevblocksPlatform::importGPC($_REQUEST['var_vals'],'array',array());

			$vars = DAO_ContextScheduledBehavior::buildVariables($var_keys, $var_vals, $macro);

			// Create
			$behavior_id = DAO_ContextScheduledBehavior::create(array(
				DAO_ContextScheduledBehavior::BEHAVIOR_ID => $macro->id,
				DAO_ContextScheduledBehavior::CONTEXT => $context,
				DAO_ContextScheduledBehavior::CONTEXT_ID => $context_id,
				DAO_ContextScheduledBehavior::RUN_DATE => $run_timestamp,
				DAO_ContextScheduledBehavior::RUN_RELATIVE => $run_relative,
				DAO_ContextScheduledBehavior::RUN_LITERAL => $run_date,
				DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($vars),
				DAO_ContextScheduledBehavior::REPEAT_JSON => json_encode($repeat_params),
			));
			
			// Execute now if the start time is in the past
			if($run_timestamp <= time()) {
				$behavior = DAO_ContextScheduledBehavior::get($behavior_id);
				$behavior->run();
			}
			
		} catch (Exception $e) {
			// System log error?
		}
		
		// Redirect
		DevblocksPlatform::redirectURL($return_url);
		exit;
	}
	
	function renderContextScheduledBehaviorAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('expanded', true);
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl');
	}
	
	function showMacroSchedulerPopupAction() {
		@$job_id = DevblocksPlatform::importGPC($_REQUEST['job_id'],'integer',0);
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$return_url = DevblocksPlatform::importGPC($_REQUEST['return_url'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($job_id)) {
			$tpl->assign('return_url', $return_url);
			
			try {
				if(null == ($macro = DAO_TriggerEvent::get($macro_id)))
					throw new Exception("Missing macro.");
				
				$tpl->assign('macro', $macro);
				
			} catch(Exception $e) {
				DevblocksPlatform::redirectURL($return_url);
				exit;
			}
			
		} else { // Update
			$job = DAO_ContextScheduledBehavior::get($job_id);

			if(null == $job)
				return;
			
			$tpl->assign('job', $job);
			
			$context = $job->context;
			$context_id = $job->context_id;
			$macro_id = $job->behavior_id;
			
			$macro = DAO_TriggerEvent::get($macro_id);
			$tpl->assign('macro', $macro);
			
		}
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		// Verify permission
		$is_writeable = CerberusContexts::isWriteableByActor($context, $context_id, $active_worker);
		$tpl->assign('is_writeable', $is_writeable);
		
		if(empty($job) && !$is_writeable)
			return;

		$event = $macro->getEvent();
		$conditions = $event->getConditions($macro, false);
		$dates = [];
		
		foreach($conditions as $k => $cond) {
			if(isset($cond['type']) && $cond['type'] == Model_CustomField::TYPE_DATE)
				$dates[$k] = $cond;
		}
		
		DevblocksPlatform::sortObjects($dates, '[label]');
		
		$tpl->assign('dates', $dates);
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/display/scheduler_popup.tpl');
	}
	
	function saveMacroSchedulerPopupAction() {
		@$job_id = DevblocksPlatform::importGPC($_REQUEST['job_id'],'integer',0);
		@$run_relative = DevblocksPlatform::importGPC($_REQUEST['run_relative'],'string','');
		@$run_date = DevblocksPlatform::importGPC($_REQUEST['run_date'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($job = DAO_ContextScheduledBehavior::get($job_id)))
			return;
		
		if(null == ($trigger = DAO_TriggerEvent::get($job->behavior_id)))
			return;
		
		// Verify permission
		if(!CerberusContexts::isReadableByActor($job->context, $job->context_id, $active_worker))
			return;
		
		// Load the event with this context
		if(null == ($event = $trigger->getEvent()))
			return;
		
		$event_model = $event->generateSampleEventModel($trigger, $job->context_id);
		$event->setEvent($event_model, $trigger);
		$values = $event->getValues();
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		@$run_relative_timestamp = strtotime($tpl_builder->build(sprintf("{{%s|date}}", $run_relative), $values));
		
		if(empty($run_relative_timestamp))
			$run_relative_timestamp = time();
		
		if($do_delete) {
			DAO_ContextScheduledBehavior::delete($job->id);
			
		} else {
			@$repeat_freq = DevblocksPlatform::importGPC($_REQUEST['repeat_freq'],'string', '');
			@$repeat_end = DevblocksPlatform::importGPC($_REQUEST['repeat_end'],'string', '');
			
			$repeat_params = array();
			
			if(!empty($repeat_freq)) {
				@$repeat_options = DevblocksPlatform::importGPC($_REQUEST['repeat_options'][$repeat_freq], 'array', array());
				@$repeat_ends = DevblocksPlatform::importGPC($_REQUEST['repeat_ends'][$repeat_end], 'array', array());
	
				switch($repeat_end) {
					case 'date':
						if(isset($repeat_ends['on'])) {
							$repeat_ends['on'] = @strtotime($repeat_ends['on']);
						}
						break;
				}
				
				$repeat_params = array(
					'freq' => $repeat_freq,
					'options' => $repeat_options,
					'end' => array(
						'term' => $repeat_end,
						'options' => $repeat_ends,
					),
				);
			}
			
			$run_timestamp = @strtotime($run_date, $run_relative_timestamp) or time();
			
			// Variables
			@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_keys'],'array',array());
			@$var_vals = DevblocksPlatform::importGPC($_REQUEST['var_vals'],'array',array());
			
			$vars = DAO_ContextScheduledBehavior::buildVariables($var_keys, $var_vals, $trigger);
			
			DAO_ContextScheduledBehavior::update($job->id, array(
				DAO_ContextScheduledBehavior::RUN_DATE => $run_timestamp,
				DAO_ContextScheduledBehavior::RUN_RELATIVE => $run_relative,
				DAO_ContextScheduledBehavior::RUN_LITERAL => $run_date,
				DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($vars),
				DAO_ContextScheduledBehavior::REPEAT_JSON => json_encode($repeat_params),
			));
		}
		
		exit;
	}
	
	function showAttendantsTabAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(empty($context))
			return;

		$tpl->assign('owner_context', $context);
		$tpl->assign('owner_context_id', $context_id);
		
		$view_id = str_replace('.','_',$point) . '_attendants';
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(null == $view) {
			$ctx = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_BOT);
			$view = $ctx->getChooserView($view_id);
		}
		
		if($active_worker->is_superuser && 0 == strcasecmp($context, 'all')) {
			$view->addParamsRequired(array(), true);
			
		} else {
			$view->addParamsRequired(array(
				SearchFields_Bot::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_Bot::OWNER_CONTEXT, DevblocksSearchCriteria::OPER_EQ, $context),
				SearchFields_Bot::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_Bot::OWNER_CONTEXT_ID, DevblocksSearchCriteria::OPER_EQ, $context_id),
			), true);
		}
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
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
		foreach($nodes as $node_id => $node) {
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
		$nodes =& $data['nodes'];
		
		$recursive_duplicate = function($node_id, $new_parent_id) use ($tree, $nodes, &$recursive_duplicate) {
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		
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
		if(!empty($trigger))
			if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, true)))
				return;

		$tpl->assign('event', $event);
		
		// Template
		switch($type) {
			case 'subroutine':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/subroutine.tpl');
				break;
				
			case 'switch':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/switch.tpl');
				break;
				
			case 'loop':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/loop.tpl');
				break;
				
			case 'outcome':
				if(null != ($evt = $trigger->getEvent())) {
					$conditions = $evt->getConditions($trigger);
					$tpl->assign('conditions', $conditions);
					
					// [TODO] Cache this
					$map = array();
					array_walk($conditions, function($v, $k) use (&$map) {
						if(is_array($v) && isset($v['label']))
							$map[$k] = $v['label'];
					});
					
					$conditions_menu = Extension_DevblocksContext::getPlaceholderTree($map);
					$tpl->assign('conditions_menu', $conditions_menu);
				}
				
				// Action labels
				$labels = $evt->getLabels($trigger);
				$tpl->assign('labels', $labels);
					
				$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
				$tpl->assign('placeholders', $placeholders);
				
				$values = $evt->getValues();
				$tpl->assign('values', $values);
				
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/outcome.tpl');
				break;
				
			case 'action':
				if(null != ($evt = $trigger->getEvent())) {
					$actions = $evt->getActions($trigger);
					$tpl->assign('actions', $actions);
					
					// [TODO] Cache this
					$map = [];
					array_walk($actions, function($v, $k) use (&$map) {
						if(is_array($v) && isset($v['label']))
							$map[$k] = $v['label'];
					});
					
					$actions_menu = Extension_DevblocksContext::getPlaceholderTree($map);
					$tpl->assign('actions_menu', $actions_menu);
				}
					
				// Workers
				$tpl->assign('workers', DAO_Worker::getAll());
				
				// Action labels
				$labels = $evt->getLabels($trigger);
				$tpl->assign('labels', $labels);
				
				$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
				$tpl->assign('placeholders', $placeholders);
				
				$values = $evt->getValues();
				$tpl->assign('values', $values);

				// Template
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/action.tpl');
				break;
		}
		
		// Free
		$tpl->clearAssign('actions');
		$tpl->clearAssign('conditions');
		$tpl->clearAssign('event');
		$tpl->clearAssign('ext');
		$tpl->clearAssign('id');
		$tpl->clearAssign('model');
		$tpl->clearAssign('parent_id');
		
		$tpl->clearAssign('trigger');
		$tpl->clearAssign('trigger_id');
		$tpl->clearAssign('type');
	}

	function showBehaviorSimulatorPopupAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
		
		$tpl->assign('trigger', $trigger);

		if(null == ($ext_event = DevblocksPlatform::getExtension($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
			return;

		$event_model = $ext_event->generateSampleEventModel($trigger, $context_id);
		$ext_event->setEvent($event_model, $trigger);
		
		$event_params_json = json_encode($event_model->params);
		$tpl->assign('event_params_json', $event_params_json);
		
		$tpl->assign('ext_event', $ext_event);
		$tpl->assign('event_model', $event_model);
		
		$labels = $ext_event->getLabels($trigger);
		$values = $ext_event->getValues();
		$dict = new DevblocksDictionaryDelegate($values);

		$conditions = $ext_event->getConditions($trigger, false);

		$dictionary = array();
		
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
		@$custom_values = DevblocksPlatform::importGPC($_POST['values'],'array', array());
		
		$tpl = DevblocksPlatform::getTemplateService();
		$logger = DevblocksPlatform::getConsoleLog('Bot');
		
		$logger->setLogLevel(6);
		
		ob_start();
		
		$tpl->assign('trigger_id', $trigger_id);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;

		$tpl->assign('trigger', $trigger);
		
 		if(null == ($ext_event = DevblocksPlatform::getExtension($trigger->event_point, true))) /* @var $ext_event Extension_DevblocksEvent */
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
				$event_model->params = is_array($event_model_params) ? $event_model_params : array();
				break;
		}
		
		$ext_event->setEvent($event_model, $trigger);
		
		$tpl->assign('event', $ext_event);
		
		// Format custom values
		
		if(is_array($trigger->variables))
		foreach($trigger->variables as $var_key => $var) {
			if(!empty($var['is_private']))
				continue;
			
			if(!isset($custom_values[$var_key]))
				continue;
			
			try {
				$custom_values[$var_key] = $trigger->formatVariable($var, $custom_values[$var_key]);
				
			} catch(Exception $e) {
				
			}
		}
		
		// Merge baseline values with user overrides
		
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('behavior_json', $behavior_json);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_export.tpl');
	}
	
	function showBehaviorImportPopupAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$node_id = DevblocksPlatform::importGPC($_REQUEST['node_id'],'integer', 0);
		
		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;

		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('node_id', $node_id);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/_import.tpl');
	}
	
	function showBehaviorParamsAction() {
		@$name_prefix = DevblocksPlatform::importGPC($_REQUEST['name_prefix'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('namePrefix', $name_prefix);
		
		if(null != ($trigger = DAO_TriggerEvent::get($trigger_id)))
			$tpl->assign('macro_params', $trigger->variables);
		
		$tpl->display('devblocks:cerberusweb.core::events/_action_behavior_params.tpl');
	}
	
	function showScheduleBehaviorBulkParamsAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('field_name', 'behavior_params');
		
		if(null != ($trigger = DAO_TriggerEvent::get($trigger_id)))
			$tpl->assign('variables', $trigger->variables);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/assistant/behavior_variables_entry.tpl');
	}
	
	function doDecisionAddConditionAction() {
		@$condition = DevblocksPlatform::importGPC($_REQUEST['condition'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_REQUEST['seq'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, true)))
			return; /* @var $event Extension_DevblocksEvent */
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
			
		$event->renderCondition($condition, $trigger, null, $seq);
	}
	
	function doDecisionAddActionAction() {
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string', '');
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer', 0);
		@$seq = DevblocksPlatform::importGPC($_REQUEST['seq'],'integer', 0);

		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;
			
		if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, true)))
			return; /* @var $event Extension_DevblocksEvent */
			
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		$tpl->assign('seq', $seq);
			
		$event->renderAction($action, $trigger, null, $seq);
	}

	function showDecisionTreeAction() {
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		if(null == ($trigger = DAO_TriggerEvent::get($trigger_id)))
			return;

		if(false == ($va = $trigger->getBot()))
			return;
		
		if(null == ($event = DevblocksPlatform::getExtension($trigger->event_point, false)))
			return; /* @var $event Extension_DevblocksEvent */
			
		$is_writeable = Context_Bot::isWriteableByActor($va, $active_worker);
		$tpl->assign('is_writeable', $is_writeable);
		
		$tpl->assign('trigger', $trigger);
		$tpl->assign('event', $event);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/tree.tpl');
	}
	
	function addTriggerVariableAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('seq', uniqid());
		
		$variable_types = DAO_TriggerEvent::getVariableTypes();
		$tpl->assign('variable_types', $variable_types);

		// New variable
		$var = array(
			'key' => '',
			'type' => $type,
			'label' => 'New Variable',
			'is_private' => 1,
			'params' => array(),
		);
		$tpl->assign('var', $var);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/editors/trigger_variable.tpl');
	}
	
	function saveDecisionPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', '');
		@$status_id = DevblocksPlatform::importGPC($_REQUEST['status_id'],'integer', 0);

		@$active_worker = CerberusApplication::getActiveWorker();
		
		$fields = array();
		
		// DAO
		
		if(!empty($id)) { // Edit
			if(null != ($model = DAO_DecisionNode::get($id))) {
				$type = $model->node_type;
				$trigger_id = $model->trigger_id;

				// Security
		
				if(false == ($trigger = DAO_TriggerEvent::get($trigger_id)))
					return false;
				
				if(false == ($va = $trigger->getBot()))
					return false;
				
				if(!Context_Bot::isWriteableByActor($va, $active_worker))
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
				
				if(false == ($va = $trigger->getBot()))
					return false;
				
				if(!Context_Bot::isWriteableByActor($va, $active_worker))
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

				$groups = array();
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
								continue;
							
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
				$params = array();
				$params['actions'] = $this->_parseActions($action_ids, $_POST);
				DAO_DecisionNode::update($id, array(
					DAO_DecisionNode::PARAMS_JSON => json_encode($params),
				));
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

		$tpl = DevblocksPlatform::getTemplateService();
		
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

		$tpl = DevblocksPlatform::getTemplateService();
		
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
		@$trigger_id = DevblocksPlatform::importGPC($_REQUEST['trigger_id'],'integer',0);

		$content = '';
		
		if(is_array($_REQUEST['field'])) {
			@$fields = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		
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
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$success = false;
		$output = '';

		if(isset($values)) {
			// Try to build the template
			if(!$content || !is_string($content) || false === ($out = $tpl_builder->build($content, $values))) {
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
								
								// Field mapping
								
								@$_replyto_field = DevblocksPlatform::importGPC($_REQUEST['_replyto_field'],'string','');
								@$_replyto_id = DevblocksPlatform::importGPC($_REQUEST[$prefix][$_replyto_field],'integer',0);

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
								
								// Cascade to current reply-to
								if($_replyto_id && !$html_template && false != ($replyto = DAO_AddressOutgoing::get($_replyto_id))) {
									$html_template = $replyto->getReplyHtmlTemplate();
								}
								
								// Cascade to default reply-to
								if(!$html_template && false != ($replyto = DAO_AddressOutgoing::getDefault())) {
									$html_template = $replyto->getReplyHtmlTemplate();
								}
								
								if($html_template) {
									$tpl_builder = DevblocksPlatform::getTemplateBuilder();
									$output = $tpl_builder->build($html_template->content, array('message_body' => $output));
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
					echo DevblocksPlatform::purifyHTML($output, true);
					echo '</body></html>';
					return;
				}
			}
		}

		$tpl->assign('success', $success);
		$tpl->assign('output', $output);
		$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
	}
	
	// Utils

	function transformMarkupToHTMLAction() {
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'],'string', '');
		@$data = DevblocksPlatform::importGPC($_REQUEST['data'],'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		
		$body = '';
		
		switch($format) {
			case 'markdown':
			case 'parsedown':
				$body = DevblocksPlatform::parseMarkdown($data);
				break;
				
			case 'html':
			default:
				$body = $data;
				break;
		}
		
		$tpl->assign('body', $body);
		
		$tpl->display('devblocks:cerberusweb.core::internal/html_editor/preview.tpl');
	}

	// Comments

	function showTabContextCommentsAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$comments = DAO_Comment::getByContext($context, $context_id);
		$tpl->assign('comments', $comments);

		$tpl->display('devblocks:cerberusweb.core::internal/comments/tab.tpl');
	}

	function commentShowPopupAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$notify_workers = array();
		
		// Automatically tell anybody associated with this context object
		switch($context) {
			case CerberusContexts::CONTEXT_WORKER:
				$notify_workers[] = $context_id;
				break;
				
			case CerberusContexts::CONTEXT_GROUP:
				if(null != ($group = DAO_Group::get($context_id))) {
					$members = $group->getMembers();
					$notify_workers = array_keys($members);
				}
				break;
		}
		
		$tpl->assign('notify_workers', $notify_workers);

		$tpl->display('devblocks:cerberusweb.core::internal/comments/peek.tpl');
	}
};
