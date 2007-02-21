<?php
// [JAS]: [TODO] This should move to a /cron URI
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

$cron_manifests = DevblocksPlatform::getExtensions('cerberusweb.cron');

foreach ($cron_manifests as $manifest) {
	$instance = $manifest->createInstance();
	
	if($instance) { 
		$instance->run();
	}
}
?>