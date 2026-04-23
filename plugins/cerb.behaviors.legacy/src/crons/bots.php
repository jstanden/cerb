<?php
class Cron_BotScheduledBehavior extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log('Bot Scheduler');
		$runtime = microtime(true);

		$stop_time = time() + 20; // [TODO] Make configurable

		$logger->info("Starting...");
		
		// Run recurrent behaviors
		
		$this->_runRecurrentBehaviors();
		
		// Run scheduled behaviors
		
		$this->_runScheduledBehaviors($stop_time);
		
		$logger->info("Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
	}
	
	private function _runRecurrentBehaviors() {
		$recurrent_behaviors = Event_RecurrentBehavior::getReadyBehaviors();
		
		foreach($recurrent_behaviors as $behavior) { /* @var $behavior Model_TriggerEvent */
			if(false == ($event = $behavior->getEvent()))
				continue;
			
			$event_model = new Model_DevblocksEvent();
			$event_model->id = Event_RecurrentBehavior::ID;
			$event_model->params = [];
			
			$event->setEvent($event_model, $behavior);
			
			$values = $event->getValues();
			$dict = DevblocksDictionaryDelegate::instance($values);
			
			$behavior->runDecisionTree($dict, false, $event);
			
			// Update the next runtime timestamp
			$patterns = DevblocksPlatform::parseCrlfString($behavior->event_params['repeat_patterns'] ?? null);
			$timezone = $behavior->event_params['timezone'] ?? null;
			$history = $behavior->event_params['repeat_history'] ?? null;
			
			if(!is_array($history))
				$history = [];
			
			if(is_array($patterns)) {
				$run_at = DevblocksPlatform::services()->date()->getNextOccurrence($patterns, $timezone);
				$behavior->event_params['repeat_run_at'] = $run_at;
				
				$history[] = time();
				$behavior->event_params['repeat_history'] = array_slice($history, -25);
				
				DAO_TriggerEvent::update($behavior->id, [
					DAO_TriggerEvent::EVENT_PARAMS_JSON => json_encode($behavior->event_params),
				]);
			}
		}
	}
	
	private function _runScheduledBehaviors($stop_time) {
		$logger = DevblocksPlatform::services()->log();
		
		$last_behavior_id = 0;

		do {
			$behaviors = DAO_ContextScheduledBehavior::getWhere(
				sprintf("%s < %d AND %s > %d",
					DAO_ContextScheduledBehavior::RUN_DATE,
					time(),
					DAO_ContextScheduledBehavior::ID,
					$last_behavior_id
				),
				array(DAO_ContextScheduledBehavior::RUN_DATE),
				array(true),
				25
			);

			if(!empty($behaviors)) {
				foreach($behaviors as $behavior) {
					/* @var $behavior Model_ContextScheduledBehavior */
					try {
						if(empty($behavior->context) || empty($behavior->context_id) || empty($behavior->behavior_id))
							throw new Exception("Incomplete macro.");
					
						// Load context
						if(null == (Extension_DevblocksContext::get($behavior->context)))
							throw new Exception("Invalid context.");
					
						// [TODO] ACL: Ensure access to the context object
							
						// Load macro
						if(null == ($macro = DAO_TriggerEvent::get($behavior->behavior_id))) /* @var $macro Model_TriggerEvent */
							throw new Exception("Invalid macro.");
						
						if($macro->is_disabled)
							throw new Exception("Macro disabled.");
							
						// [TODO] ACL: Ensure the worker owns the macro
					
						// Load event manifest
						if(null == (Extension_DevblocksEvent::get($macro->event_point, false))) /* @var $ext DevblocksExtensionManifest */
							throw new Exception("Invalid event.");

						// Execute
						$behavior->run();

						// Log
						$logger->info(sprintf("Executed behavior %d", $behavior->id));
						
					} catch (Exception $e) {
						$logger->error(sprintf("Failed executing behavior %d: %s", $behavior->id, $e->getMessage()));

						DAO_ContextScheduledBehavior::delete($behavior->id);
					}
					
					$last_behavior_id = $behavior->id;
				}
			}
			
		} while(!empty($behaviors) && $stop_time > time());
	}
};