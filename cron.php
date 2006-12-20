<?php
require(getcwd() . '/framework.config.php');
require(UM_PATH . '/libs/cloudglue/CloudGlue.class.php');
require(UM_PATH . '/api/CerberusApplication.class.php');

$cron_manifests = CgPlatform::getExtensions('com.cerberusweb.cron');

foreach ($cron_manifests as $manifest) {
	$instance = $manifest->createInstance();
	$instance->run();
}
?>