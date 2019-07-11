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

class Event_GetInteractionsForWorker extends Extension_DevblocksEvent {
	const ID = 'event.interactions.get.worker';
	
	function prepareEventParams(Model_TriggerEvent $behavior=null, &$event_params, &$error) {
		$error = null;
		
		// If everything is fine, invalidate cache
		
		$cache = DevblocksPlatform::services()->cache();
		
		@$old_listen_points = DevblocksPlatform::parseCrlfString($behavior->event_params['listen_points']) ?: [];
		@$new_listen_points = DevblocksPlatform::parseCrlfString($event_params['listen_points']) ?: [];
		
		$listen_points = array_unique(array_merge($old_listen_points, $new_listen_points));
		
		// Clear the cache for the interaction menu
		if(is_array($listen_points))
		foreach($listen_points as $point) {
			$cache_key = sprintf("interactions_%s", DevblocksPlatform::strAlphaNum($point,'','_'));
			$cache->remove($cache_key);
		}
		
		return true;
	}
	
	static function getInteractionsByPointAndWorker($point, $point_params, $worker) {
		if(!($point_params instanceof DevblocksDictionaryDelegate) && is_array($point_params))
			$point_params = DevblocksDictionaryDelegate::instance($point_params);
		
		if(!($point_params instanceof DevblocksDictionaryDelegate))
			$point_params = new DevblocksDictionaryDelegate([]);
		
		$behaviors = Event_GetInteractionsForWorker::getByPointAndWorker($point, $worker);
		$interactions = [];
		
		foreach($behaviors as $behavior) { /* @var $behavior Model_TriggerEvent */
			$actions = [];
			
			$event_model = new Model_DevblocksEvent(
				Event_NewInteractionChatWorker::ID,
				array(
					'point' => $point,
					'point_params' => $point_params,
					'worker_id' => $worker->id,
					'actions' => &$actions,
				)
			);
			
			if(false == ($event = $behavior->getEvent()))
				return;
			
			$event->setEvent($event_model, $behavior);
			
			$values = $event->getValues();
			
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			$behavior->runDecisionTree($dict, false, $event);
			
			foreach($actions as $action) {
				switch(@$action['_action']) {
					case 'return.interaction':
						$interactions[] = [
							'label' => $action['name'],
							'behavior_id' => $action['behavior_id'],
							'bot_id' => $action['bot_id'],
							'interaction' => $action['interaction'],
							'params' => is_array($action['interaction_params']) ? $action['interaction_params'] : [],
						];
						break;
				}
			}
		}
		
		return $interactions;
	}
	
	static function getByPoint($point) {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf('interactions_%s', DevblocksPlatform::strAlphaNum($point,'','_'));
		
		if(null !== ($behaviors = $cache->load($cache_key))) {
			return $behaviors;
			
		} else {
			$behaviors = DAO_TriggerEvent::getByEvent(self::ID);
			
			$behaviors = array_filter($behaviors, function($behavior) use ($point) {
				if(false == (@$listen_points = $behavior->event_params['listen_points']))
					return false;
				
				if(false == ($listen_points = DevblocksPlatform::parseCrlfString($listen_points)) || !is_array($listen_points))
					return false;
				
				if(in_array('*', $listen_points))
					return true;
					
				foreach($listen_points as $listen_point) {
					$regexp = DevblocksPlatform::strToRegExp($listen_point);
					
					if(preg_match($regexp, $point)) {
						return true;
					}
				}
				
				return false;
			});
			
			$cache->save($behaviors, $cache_key, [], 900);
			
			return $behaviors;
		}
	}
	
	static function getByPointAndWorker($point, Model_Worker $worker) {
		$behaviors = Event_GetInteractionsForWorker::getByPoint($point);

		// Don't show worker-owned bots to admins
		$behaviors = array_intersect_key(
			$behaviors,
			array_flip(array_keys(Context_TriggerEvent::isReadableByActor($behaviors, $worker, true), true))
		);
		
		return $behaviors;
	}
	
	static function getInteractionMenu(array $interactions) {
		$interactions_menu = [];
		$url_writer = DevblocksPlatform::services()->url();
		
		if(false == ($bot_ids = array_column($interactions, 'bot_id')))
			return [];
		
		if(false == ($bots = DAO_Bot::getIds($bot_ids)))
			return [];
		
		foreach($bots as $bot) { /* @var $bot Model_Bot */
			$bot_menu = new DevblocksMenuItemPlaceholder();
			$bot_menu->label = $bot->name;
			$bot_menu->image = $url_writer->write(sprintf('c=avatars&context=bot&context_id=%d', $bot->id)) . '?v=' . $bot->updated_at;
			$bot_menu->children = [];
			
			$interactions_menu[$bot->id] = $bot_menu;
		}
		
		DevblocksPlatform::sortObjects($interactions, '[label]');

		foreach($interactions as $interaction) {
			$item_behavior = new DevblocksMenuItemPlaceholder();
			$item_behavior->key = $interaction['behavior_id'];
			$item_behavior->label = $interaction['label'];
			$item_behavior->interaction = $interaction['interaction'];
			$item_behavior->params = $interaction['params'];
			
			$interactions_menu[$interaction['bot_id']]->children[] = $item_behavior;
		}
		
		return $interactions_menu;
	}
	
	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger) {
		$actions = [];
		
		$worker = null;
		
		if(false != ($active_worker = CerberusApplication::getActiveWorker()))
			$worker = $active_worker;
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'point' => 'example.point',
				'point_params' => ["key1" => "val1", "key2" => "val2"],
				'worker_id' => ($worker instanceof Model_Worker) ? $worker->id : 0,
				'actions' => &$actions,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		$labels = array();
		$values = array();
		
		/**
		 * Behavior
		 */
		
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BEHAVIOR, $trigger, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'behavior_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		
		// Interaction
		@$point = $event_model->params['point'];
		$labels['point'] = 'Interaction Point';
		$values['point'] = $point;
		
		@$point_params = $event_model->params['point_params'];
		$labels['point_params'] = 'Interaction Point Parameters';
		$values['point_params'] = ($point_params instanceof DevblocksDictionaryDelegate) ? $point_params : new DevblocksDictionaryDelegate([]);
		
		// Actions
		$values['_actions'] =& $event_model->params['actions'];

		/**
		 * Worker
		 */
		
		@$worker_id = $event_model->params['worker_id'];
		
		$merge_labels = [];
		$merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'worker_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			'behavior_id' => array(
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'behavior_bot_id' => array(
				'label' => 'Bot',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'worker_id' => array(
				'label' => 'Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		DevblocksPlatform::sortObjects($vals_to_ctx, '[label]');
		
		return $vals_to_ctx;
	}
	
	function renderEventParams(Model_TriggerEvent $trigger=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('trigger', $trigger);
		
		$points = [
			'global' => 'Global',
			'mail.compose' => 'Mail:Compose',
			'mail.reply' => 'Mail:Reply',
		];
		
		$contexts = Extension_DevblocksContext::getAll(false);
		
		foreach($contexts as $context) {
			$points['record:'. $context->id] = 'Record:' . $context->name;
		}
		
		$menu = Extension_DevblocksContext::getPlaceholderTree($points, ':','');
		$tpl->assign('menu', $menu);
		
		$tpl->display('devblocks:cerberusweb.core::events/interaction/params_interactions_get_for_worker.tpl');
	}
	
	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();
		
		$types['point'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['point_params'] = null;
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			[
				'return_interaction' => [
					'label' => 'Return interaction',
					'notes' => '',
					'params' => [
						'behavior_id' => [
							'type' => 'id',
							'required' => true,
							'notes' => 'The ID of the [behavior](/docs/records/types/behavior/) to run',
						],
						'name' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The name of the displayed interaction',
						],
						'interaction' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The ID of the displayed interaction',
						],
						'interaction_params_json' => [
							'type' => 'json',
							'notes' => 'The parameters of the displayed interaction',
						],
					],
				],
			]
		;
		
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'return_interaction':
				$tpl->assign('event_point', Event_NewInteractionChatWorker::ID);
				$tpl->display('devblocks:cerberusweb.core::events/interaction/action_return_interaction.tpl');
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'return_interaction':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$behavior_id = intval($params['behavior_id']);
				
				if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
					break;
				
				@$name = $tpl_builder->build($params['name'], $dict);
				@$interaction = $tpl_builder->build($params['interaction'], $dict);
				
				if(empty($name) || empty($interaction))
					break;
				
				@$interaction_params_json = $tpl_builder->build($params['interaction_params_json'], $dict);
				
				if(false == ($interaction_params = json_decode($interaction_params_json, true)))
					$interaction_params = [];
				
				$out = sprintf(">>> Returning interaction\n".
					"Name: %s\n".
					"Label: %s\n".
					"Behavior: %s (#%d)\n".
					"Params:\n%s\n",
					$interaction,
					$name,
					$behavior->title,
					$behavior_id,
					DevblocksPlatform::strFormatJson(json_encode($interaction_params))
				);
				break;
		}
		
		return $out;
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'return_interaction':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$actions =& $dict->_actions;
				
				if(!is_array($actions))
					$actions = [];
				
				@$behavior_id = intval($params['behavior_id']);
				
				if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
					break;
				
				@$name = $tpl_builder->build($params['name'], $dict);
				@$interaction = $tpl_builder->build($params['interaction'], $dict);
				
				if(empty($name) || empty($interaction))
					break;
				
				@$interaction_params_json = $tpl_builder->build($params['interaction_params_json'], $dict);
				if(false == ($interaction_params = json_decode($interaction_params_json, true)))
					$interaction_params = [];
				
				// Sanitize key names and values
				
				$keys = array_map(function($key) {
					return DevblocksPlatform::strAlphaNum($key, '-');
					}, array_keys($interaction_params)
				);
				
				$vals = array_map(function($val) {
					if(!is_string($val))
						$val = strval($val);
					
					$val = trim($val);
					return $val;
					}, $interaction_params
				);
				
				$interaction_params = array_combine($keys, $vals);
				
				$actions[] = [
					'_action' => 'return.interaction',
					'behavior_id' => $behavior_id,
					'bot_id' => $behavior->bot_id,
					'name' => $name,
					'interaction' => $interaction,
					'interaction_params' => $interaction_params,
				];
				break;
		}
	}
};
