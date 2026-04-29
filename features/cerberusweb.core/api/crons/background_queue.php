<?php
class Cron_BackgroundQueue extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log('Background Queue');
		$runtime = microtime(true);

		$stop_time = time() + 25;

		$logger->info("Starting...");

		$logger->info("Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
	}
};