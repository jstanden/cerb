<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add a 'params_json' field to the 'workspace_tab' table for arbitrary tab config params

if(!isset($tables['workspace_tab'])) {
	$logger->error("The 'workspace_tab' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_tab');

if(!isset($columns['params_json'])) {
	$db->Execute("ALTER TABLE workspace_tab ADD COLUMN params_json TEXT");
}

// ===========================================================================
// Finish

return TRUE;