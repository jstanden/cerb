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

class Event_RecurrentBehavior extends Extension_DevblocksEvent {
	const ID = 'event.behavior.recurrent';
	
	function renderEventParams(Model_TriggerEvent $trigger=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		// Timezones
		$date = DevblocksPlatform::services()->date();
		$tpl->assign('timezones', $date->getTimezones());
		
		if(!isset($trigger->event_params['repeat_patterns']) || !$trigger->event_params['repeat_patterns'])
			$trigger->event_params['repeat_patterns'] = sprintf("# https://en.wikipedia.org/wiki/Cron#CRON_expression\r\n# [min] [hour] [dom] [month] [dow]\r\n");
		
		if(!isset($trigger->event_params['timezone']) || !$trigger->event_params['timezone'])
			$trigger->event_params['timezone'] = DevblocksPlatform::getTimezone();
		
		$tpl->assign('trigger', $trigger);
		$tpl->display('devblocks:cerberusweb.core::events/recurrent/params_recurrent_behavior.tpl');
	}
	
	function prepareEventParams(Model_TriggerEvent $behavior=null, &$event_params, &$error) {
		$error = null;
		
		@$history = $behavior->event_params['repeat_history'];
		$event_params['repeat_history'] = $history ?: [];
		
		@$patterns = DevblocksPlatform::parseCrlfString($event_params['repeat_patterns']);
		@$timezone = $event_params['timezone'] ?: DevblocksPlatform::getTimezone();
		
		// Validate all patterns
		foreach($patterns as $pattern) {
			if(DevblocksPlatform::strStartsWith($pattern, '#'))
				continue;
			
			if(!Cron\CronExpression::isValidExpression($pattern)) {
				$error = sprintf("Invalid cron expression:<br><tt>%s</tt>", DevblocksPlatform::strEscapeHtml($pattern));
				return false;
			}
		}
		
		$run_at = Event_RecurrentBehavior::getNextOccurrence($patterns, $timezone);
		
		// Store the next run time
		$event_params['repeat_run_at'] = $run_at;
		
		return true;
	}
	
	/**
	 * 
	 * @return Model_TriggerEvent[]
	 */
	static function getReadyBehaviors() {
		$behaviors = DAO_TriggerEvent::getByEvent(Event_RecurrentBehavior::ID);
		
		$behaviors = array_filter($behaviors, function($behavior) {
			@$run_at = $behavior->event_params['repeat_run_at'];
			
			if($run_at && $run_at < time())
				return true;
			
			return false;
		});
		
		return $behaviors;
	}
	
	static function getNextOccurrence(array $patterns, $timezone=null) {
		if(empty($timezone))
			$timezone = DevblocksPlatform::getTimezone();
		
		$earliest = null;
		$now = new DateTime('now', new DateTimeZone($timezone));
		
		foreach($patterns as $pattern) {
			// Skip commented lines
			if(empty($pattern) || DevblocksPlatform::strStartsWith($pattern, '#'))
				continue;
			
			$cron = Cron\CronExpression::factory($pattern);
			$next = $cron->getNextRunDate($now);
			
			$next_ts = $next->getTimestamp();
			
			if(!$earliest || $next_ts < $earliest)
				$earliest = $next_ts;
		}
		
		return $earliest;
	}
	
	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger) {
		return new Model_DevblocksEvent(
			self::ID,
			[]
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
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		DevblocksPlatform::sortObjects($vals_to_ctx, '[label]');
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();
		
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
			[]
		;
		
		return $actions;
	}
	
	function getActionDefaultOn() {
		return 'bot_id';
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
		}
		
		return '';
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
		}
	}
};
