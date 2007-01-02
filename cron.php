<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . '/libs/devblocks/Devblocks.class.php');
require(DEVBLOCKS_PATH . '/api/CerberusApplication.class.php');

$cron_manifests = DevblocksPlatform::getExtensions('com.cerberusweb.cron');

foreach ($cron_manifests as $manifest) {
	$instance = $manifest->createInstance();
	$instance->run();
}
?>