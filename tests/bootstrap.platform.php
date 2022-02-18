<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');
require(APP_PATH . '/api/app/Mail.php');

DevblocksPlatform::init();

$classloader = DevblocksPlatform::services()->classloader();

$classloader->registerPsr4Path(DEVBLOCKS_PATH . 'api/services/automation/', 'Cerb\\AutomationBuilder\\');

$classloader->registerClasses(APP_PATH . '/features/cerberusweb.core/api/dao/abstract_view.php', [
	'C4_AbstractView',
	'CerbQuickSearchLexer',
]);
$classloader->registerClasses(APP_PATH . '/features/cerberusweb.core/api/dao/automation.php', [
	'Model_Automation',
]);

DevblocksPlatform::setTimezone('UTC');
