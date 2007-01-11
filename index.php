<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

// [JAS]: [TODO] Is an explicit init() really required?
DevblocksPlatform::init();
$session = DevblocksPlatform::getSessionService();

// [JAS]: HTTP Request
$request = DevblocksPlatform::readRequest();
DevblocksPlatform::processRequest($request);

exit;
?>