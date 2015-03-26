<?php
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

DevblocksPlatform::setExtensionDelegate('Cerb_DevblocksExtensionDelegate');
DevblocksPlatform::setHandlerSession('Cerb_DevblocksSessionHandler');
