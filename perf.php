<?php
die("Access denied."); // uncomment to test
require(getcwd() . '/framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');
require(APP_PATH . '/api/Application.class.php');

// [TODO]: If this is our first run, redirect to the installer

// [JAS]: [TODO] Is an explicit init() really required?  No anonymous static blocks?
//DevblocksPlatform::init();

$db = DevblocksPlatform::getDatabaseService();
$perf = NewPerfMonitor($db);
echo $perf->SuspiciousSQL();
echo $perf->ExpensiveSQL();
exit;