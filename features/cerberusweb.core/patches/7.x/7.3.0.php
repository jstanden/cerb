<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add `status_id` field to nodes

if(!isset($tables['decision_node'])) {
	$logger->error("The 'decision_node' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('decision_node');

if(!isset($columns['status_id']))
	$db->ExecuteMaster("ALTER TABLE decision_node ADD COLUMN status_id tinyint(1) unsigned not null default 0");

// ===========================================================================
// Finish up

return TRUE;
