<?php
class ExCron extends CerberusCronPageExtension {
	public function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$logger->info("[Example Plugin] Started");
			
		// [TODO] Do something
		
		$logger->info("[Example Plugin] Finished");
	}
	
	public function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";

		// [TODO] Load settings
		
		$tpl->display('devblocks:example.cron::cron/config.tpl');
	}
	
	public function saveConfigurationAction() {
		//@$example_waitdays = DevblocksPlatform::importGPC($_POST['example_waitdays'], 'integer');
		//$this->setParam('example_waitdays', $example_waitdays);
	}
}