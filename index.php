<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');

// If this is our first run, redirect to the installer
if('' == APP_DB_DRIVER || '' == APP_DB_HOST || '' == APP_DB_DATABASE) {
    header('Location: install/index.php'); // [TODO] change this to a meta redirect
    exit;
}

require(APP_PATH . '/api/Application.class.php');

// Request
$request = DevblocksPlatform::readRequest();

// [JAS]: [TODO] Is an explicit init() really required?  No anonymous static blocks?
DevblocksPlatform::init();
//DevblocksPlatform::readPlugins();
$session = DevblocksPlatform::getSessionService();

// [JAS]: HTTP Request
DevblocksPlatform::processRequest($request);

exit;
?>