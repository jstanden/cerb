<?php
class EventListener_Triggers extends DevblocksEventListenerExtension {
	static $_traversal_log = array();
	static $_trigger_log = array();
	static $_trigger_stack = array();
	static $_depth = 0;
	
	static function increaseDepth($trigger_id) {
		++self::$_depth;
		
		self::$_trigger_log[] = $trigger_id;
		self::$_trigger_stack[] = $trigger_id;
	}
	
	static function decreaseDepth() {
		--self::$_depth;
		array_pop(self::$_trigger_stack);
	}
	
	/**
	 * Are we currently nested inside this trigger at any depth?
	 * @param integer $trigger_id
	 */
	static function inception($trigger_id) {
		return in_array($trigger_id, self::$_trigger_stack);
	}
	
	static function triggerHasSprung($trigger_id) {
		return in_array($trigger_id, self::$_trigger_log);
	}
	
	static function logNode($node_id) {
		self::$_traversal_log[] = $node_id;
	}
	
	static function getDepth() {
		return self::$_depth;
	}
	
	static function getTriggerStack() {
		return self::$_trigger_stack;
	}
	
	static function getTriggerLog() {
		return self::$_trigger_log;
	}
	
	static function getNodeLog() {
		return self::$_traversal_log;
	}
	
	static function setNodeLog(array $log) {
		self::$_traversal_log = $log;
	}
	
	static function clear() {
		self::$_traversal_log = array();
		self::$_trigger_log = array();
		self::$_trigger_stack = array();
		self::$_depth = 0;
	}
	
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		$logger = DevblocksPlatform::services()->log('Bot');
		
		$logger->info(sprintf("EVENT: %s",
			$event->id
		));
		
		// Keep track of the runners to return at the end
		
		$runners = [];
		$triggers = [];
		
		// Load all VAs
		
		$trigger_vas = DAO_Bot::getAll();
		
		// Are we limited to only one trigger on this event, or all of them?
		
		if(isset($event->params['_whisper']['_trigger_id'][0])) {
			if(null != ($trigger = DAO_TriggerEvent::get($event->params['_whisper']['_trigger_id'][0]))) {
				$triggers[$trigger->id] = $trigger;
			}
			unset($event->params['_whisper']['_trigger_id']);
			
		} else {
			$triggers = DAO_TriggerEvent::getByEvent($event->id, false);
		}
		
		// Filter by matching event params on triggers
		if(isset($event->params['_whisper']['event_params']) && isset($event->params['_whisper']['event_params'])) {
			foreach($triggers as $trigger_id => $trigger) {
				$pass = true;
				
				foreach($event->params['_whisper']['event_params'] as $k => $v) {
					if(!$pass)
						break;
					
					@$ref_v = $trigger->event_params[$k];
					
					if(is_array($ref_v)) {
						if(!in_array($v, $ref_v)) {
							$pass = false;
						}
						
					} else {
						if($ref_v != $v) {
							$pass = false;
						}
					}
				}
				
				if(!$pass)
					unset($triggers[$trigger_id]);
			}
			
			unset($event->params['_whisper']['event_params']);
		}
		
		// We're restricting the scope of the event
		if(isset($event->params['_whisper']) && is_array($event->params['_whisper']) && !empty($event->params['_whisper'])) {
			foreach($triggers as $trigger_id => $trigger) { /* @var $trigger Model_TriggerEvent */
				if(!(@$trigger_va = $trigger_vas[$trigger->bot_id]))
					continue;
				
				if($trigger_va->is_disabled)
					continue;
				
				if (
					null != ($allowed_ids = ($event->params['_whisper'][$trigger_va->owner_context] ?? null))
					&& in_array($trigger_va->owner_context_id, !is_array($allowed_ids) ? array($allowed_ids) : $allowed_ids)
				) {
					// We're allowed to see this event
				} else {
					// We're not allowed to see this event
					//$logger->info(sprintf("Removing trigger %d (%s) since it is not in this whisper",
					//	$trigger_id,
					//	$trigger->title
					//));
					unset($triggers[$trigger_id]);
				}
			}
		}
		
		if(empty($triggers))
			return;
		
		if(null == ($mft = Extension_DevblocksEvent::get($event->id, false)))
			return;
		
		if(null == ($event_ext = $mft->createInstance())
			|| !$event_ext instanceof Extension_DevblocksEvent)  /* @var $event_ext Extension_DevblocksEvent */
			return;
		
		// Load only if needed
		$dict = null;
		
		foreach($triggers as $trigger) { /* @var $trigger Model_TriggerEvent */
			if(!(@$trigger_va = $trigger_vas[$trigger->bot_id]))
				continue;
			
			if(self::inception($trigger->id)) {
				$logger->info(sprintf("Skipping trigger %d (%s) because we're currently inside of it.",
					$trigger->id,
					$trigger->title
				));
				continue;
			}
			
			/*
			 * If a top level trigger already ran as a consequence of the
			 * event chain, don't run it again.
			 */
			if(self::getDepth() == 0 && self::triggerHasSprung($trigger->id)) {
				$logger->info(sprintf("Skipping trigger %d (%s) because it has already run this event chain.",
					$trigger->id,
					$trigger->title
				));
				continue;
			}
			
			self::increaseDepth($trigger->id);
			
			$behavior_started_at = microtime(true);
			
			$logger->info(sprintf("Running behavior %s (#%d) for %s (#%d)",
				$trigger->title,
				$trigger->id,
				$trigger_va->name,
				$trigger->bot_id
			));
			
			// Load the intermediate data ONCE! (if at least one VA is responding)
			if(is_null($dict)) {
				$event_ext->setEvent($event, $trigger);
				$values = $event_ext->getValues();
				
				// Lazy-loader dictionary
				$dict = new DevblocksDictionaryDelegate($values);
				
				// We're preloading some variable values
				if(isset($event->params['_variables']) && is_array($event->params['_variables'])) {
					foreach($event->params['_variables'] as $var_key => $var_val) {
						if(!array_key_exists($var_key, $trigger->variables))
							continue;
						
						switch($trigger->variables[$var_key]['type']) {
							case Model_CustomField::TYPE_LINK:
								$link_context = $trigger->variables[$var_key]['params']['context'] ?? null;
								if($link_context && DevblocksPlatform::strEndsWith($var_key, '_id')) {
									$ctx_key = mb_substr($var_key, 0, -3) . '__context';
									$dict->set($ctx_key, $link_context);
								}
								$dict->$var_key = $var_val;
								break;
							
							default:
								$dict->$var_key = $var_val;
								break;
						}
					}
				}
				
				unset($values);
			}
			
			$trigger->runDecisionTree($dict, false, $event_ext);
			
			// Snapshot the dictionary of the behavior at conclusion
			$runners[$trigger->id] = $dict;
			
			// Log behavior duration
			$logger->info(sprintf('Exiting behavior %s (#%d); duration %dms',
					$trigger->title,
					$trigger->id,
					microtime(true)-$behavior_started_at)
			);
			
			self::decreaseDepth();
		}
		
		/*
		 * Clear our event chain when we finish all triggers and we're
		 * no longer nested.
		 */
		if(0 == self::getDepth()) {
			self::clear();
		}
		
		return $runners;
	}
};