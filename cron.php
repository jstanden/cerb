<?php
require(getcwd() . '/framework.config.php');
require(UM_PATH . '/libs/ump/UserMeetPlatform.class.php');
require(UM_PATH . '/api/CerberusApplication.class.php');

$cron_manifests = UserMeetPlatform::getExtensions('com.cerberusweb.cron');

foreach ($cron_manifests as $manifest) {
	$instance = $manifest->createInstance();
	$instance->run();
}
?>