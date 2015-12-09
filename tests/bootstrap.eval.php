<?php
define('SELENIUM_BROWSER_URL', 'http://localhost');
define('SELENIUM_SERVER_HOST', 'localhost');
define('SELENIUM_SERVER_PORT', 4444);
define('SELENIUM_SERVER_BROWSER', '*firefox');

require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');
require(APP_PATH . '/api/app/Mail.php');

PHPUnit_Extensions_SeleniumTestCase::shareSession(true);

DevblocksPlatform::setExtensionDelegate('Cerb_DevblocksExtensionDelegate');
DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');
