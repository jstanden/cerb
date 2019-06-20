<?php
class BotAction_ScheduleInteractionProactive extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.interaction_proactive.schedule';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'on' => [
					'type' => 'placeholder',
					'required' => true,
					'notes' => 'The placeholder/variable containing the target record',
				],
				'behavior_id' => [
					'type' => 'id',
					'required' => true,
					'notes' => 'The ID of the [behavior](/docs/records/types/behavior/) to execute',
				],
				'interaction' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The name of the [chat interaction](/docs/bots/interactions/chat/) to trigger',
				],
				'interaction_params_json' => [
					'type' => 'json',
					'required' => true,
					'notes' => 'The initial interaction keys and values',
				],
				'expires' => [
					'type' => 'datetime',
					'notes' => 'The date/time to expire an unread proactive interaction (e.g. `now`, `+2 days`, `Friday 8am`)',
				],
				'run_in_simulator' => [
					'type' => 'bit',
					'notes' => 'Trigger proactive interactions in the simulator: `0`=no, `1`=yes',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_create_interaction.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$date = DevblocksPlatform::services()->date();

		$out = null;
		
		@$on = $params['on'];
		@$behavior_id = $params['behavior_id'];
		@$interaction = $tpl_builder->build($params['interaction'], $dict);
		@$interaction_params_json = $tpl_builder->build($params['interaction_params_json'], $dict);
		@$run = $tpl_builder->build($params['run'], $dict);
		@$expires = $tpl_builder->build($params['expires'], $dict);
		
		$event = $trigger->getEvent();
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(empty($on) || empty($on_objects))
			return "[ERROR] At least one target worker is required.";
		
		if(empty($behavior_id))
			return "[ERROR] behavior is required.";
		
		if(empty($interaction))
			return "[ERROR] behavior is required.";
		
		if(empty($expires) || false == (@$expires_at = strtotime($expires)))
			$expires_at = 0;
		
		if(empty($run) || false == (@$run_at = strtotime($run)))
			$run_at = time();
		
		$out = sprintf(">>> Creating proactive interaction:\nInteraction: %s\nRun: %s\nExpires: %s\nParams:\n%s\n",
			$interaction,
			$run_at ? $date->formatTime(DevblocksPlatform::getDateTimeFormat(), $run_at) : 'now',
			$expires_at ? $date->formatTime(DevblocksPlatform::getDateTimeFormat(), $expires_at) : 'never',
			$interaction_params_json . (!empty($interaction_params_json) ? "\n" : '')
		);
		
		if(is_array($on_objects)) {
			$out .= ">>> For:\n";
			
			foreach($on_objects as $on_object) {
				$out .= ' * ' . $on_object->_label . "\n";
			}
		}
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$on = $params['on'];
		@$behavior_id = $params['behavior_id'];
		@$interaction = $tpl_builder->build($params['interaction'], $dict);
		@$interaction_params_json = $tpl_builder->build($params['interaction_params_json'], $dict);
		@$run = $tpl_builder->build($params['run'], $dict);
		@$expires = $tpl_builder->build($params['expires'], $dict);

		$event = $trigger->getEvent();
		
		if(false == ($interaction_params = @json_decode($interaction_params_json, true)))
			$interaction_params = [];
		
		if(empty($expires) || false == (@$expires_at = strtotime($expires)))
			$expires_at = 0;
		
		if(empty($run) || false == (@$run_at = strtotime($run)))
			$run_at = time();
		
		// On workers
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects))
		foreach($on_objects as $on_object) {
			// Create the notification
			DAO_BotInteractionProactive::create($on_object->id, $behavior_id, $interaction, $interaction_params, $trigger->bot_id, $expires_at, $run_at);
		}
	}
};