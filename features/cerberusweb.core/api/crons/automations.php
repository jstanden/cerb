<?php
class Cron_Automations extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log('Automations');
		$runtime = microtime(true);

		$stop_time = time() + 25;

		$logger->info("Starting...");
		
		// Run automation timers
		$this->_runTimers($stop_time);
		
		$logger->info("Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
	}
	
	private function _runTimers($stop_time) {
		$logger = DevblocksPlatform::services()->log();
		
		$last_timer_id = 0;

		do {
			$timers = DAO_AutomationTimer::getWhere(
				sprintf("%s < %d AND %s > %d",
					DAO_AutomationTimer::RESUME_AT,
					time(),
					DAO_AutomationTimer::ID,
					$last_timer_id
				),
				DAO_AutomationTimer::RESUME_AT,
				true,
				25
			);

			if(!$timers)
				continue;
			
			foreach($timers as $timer) {
				/* @var $timer Model_AutomationTimer */
				try {
					$timer->run();
					
					$logger->info(sprintf("Executed automation timer `%s` (#%d)", $timer->name, $timer->id));
					
				} catch (Exception $e) {
					$logger->error(sprintf("Failed executing automation timer `%s` (#%d): %s", $timer->name, $timer->id, $e->getMessage()));
				}
				
				$last_timer_id = $timer->id;
			}
			
		} while($timers && $stop_time > time());
	}
};