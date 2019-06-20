<?php
class BotAction_CalculateTimeElapsed extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.calculate_time_elapsed';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'calendar_id' => [
					'type' => 'id',
					'required' => true,
					'notes' => 'The ID of the [calendar](/docs/records/types/calendar/) to use',
				],
				'date_from' => [
					'type' => 'datetime',
					'required' => true,
					'notes' => 'The starting date',
				],
				'date_to' => [
					'type' => 'datetime',
					'required' => true,
					'notes' => 'The ending date',
				],
				'placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to save the result',
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
		
		$calendars = DAO_Calendar::getReadableByActor($trigger->getBot());
		$tpl->assign('calendars', $calendars);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_calc_time_elapsed.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$date = DevblocksPlatform::services()->date();

		$out = null;
		
		@$date_from = $tpl_builder->build($params['date_from'], $dict);
		@$date_to = $tpl_builder->build($params['date_to'], $dict);
		@$calendar_id = $params['calendar_id'];
		@$placeholder = $tpl_builder->build($params['placeholder'], $dict);
		
		if(empty($date_from) || (!is_numeric($date_from) && false == (@$date_from = strtotime($date_from))))
			$date_from = 0;
		
		if(empty($date_to) || (!is_numeric($date_to) && false == (@$date_to = strtotime($date_to))))
			$date_to = 0;
		
		if(!is_numeric($calendar_id)) {
			$value = $dict->$calendar_id;
			if(is_array($value))
				$value = key($value);
			$calendar_id = intval($value);
		}
		
		if(!$calendar_id || false == ($calendar = DAO_Calendar::get($calendar_id))) {
			return false;
		}
		
		$this->run($token, $trigger, $params, $dict);
		
		$out = sprintf(">>> Calculating time elapsed:\nFrom: %s\nTo: %s\nCalendar: %s\nElapsed: %s",
			$date_from ? $date->formatTime(DevblocksPlatform::getDateTimeFormat(), $date_from) : 'never',
			$date_to ? $date->formatTime(DevblocksPlatform::getDateTimeFormat(), $date_to) : 'never',
			$calendar->name,
			_DevblocksTemplateManager::modifier_devblocks_prettysecs($dict->$placeholder)
		);
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$date_from = $tpl_builder->build($params['date_from'], $dict);
		@$date_to = $tpl_builder->build($params['date_to'], $dict);
		@$calendar_id = $params['calendar_id'];
		@$placeholder = $tpl_builder->build($params['placeholder'], $dict);
		
		if(empty($date_from) || (!is_numeric($date_from) && false == (@$date_from = strtotime($date_from))))
			$date_from = 0;
		
		if(empty($date_to) || (!is_numeric($date_to) && false == (@$date_to = strtotime($date_to))))
			$date_to = 0;
		
		if(!is_numeric($calendar_id)) {
			$value = $dict->$calendar_id;
			if(is_array($value))
				$value = key($value);
			$calendar_id = intval($value);
		}
		
		if(!$calendar_id || false == ($calendar = DAO_Calendar::get($calendar_id))) {
			return false;
		}
		
		$calendar_events = $calendar->getEvents($date_from, $date_to);
		$availability = $calendar->computeAvailability($date_from, $date_to, $calendar_events);
		
		// [TODO] Option for counting in available or busy time?
		
		$mins = $availability->getMinutes();
		$secs = strlen(str_replace('0', '', $mins)) * 60;
		
		if($placeholder)
			$dict->$placeholder = $secs;
	}
};