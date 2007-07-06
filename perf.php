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
echo $perf->SuspiciousSQL(50);
echo $perf->ExpensiveSQL(50);
echo $perf->InvalidSQL(10);
exit;