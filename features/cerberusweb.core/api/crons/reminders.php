<?php
class Cron_Reminders extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log();
		$runtime = microtime(true);
		
		$logger->info("[Reminders] Starting...");
		
		$reminders = DAO_Reminder::getWhere(
			sprintf("%s = %d AND %s < %d",
				DAO_Reminder::IS_CLOSED,
				0,
				DAO_Reminder::REMIND_AT,
				time()
			),
			null,
			true,
			25
		);
		
		if(is_array($reminders))
		foreach($reminders as $reminder) {
			$reminder->run();
		}
		
		$logger->info("[Reminders] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}
	
	function configure($instance) {
	}
};