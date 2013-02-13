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
// Add a 'created_at' field to the 'task' table

if(!isset($tables['task'])) {
	$logger->error("The 'task' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('task');

if(!isset($columns['created_at'])) {
	$db->Execute("ALTER TABLE task ADD COLUMN created_at INT UNSIGNED NOT NULL DEFAULT 0");
	$db->Execute("UPDATE task SET created_at = updated_date WHERE created_at = 0");
}

// ===========================================================================
// Add an aggregate 'hits' field to the 'snippet' table

if(!isset($tables['snippet'])) {
	$logger->error("The 'snippet' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('snippet');

if(!isset($columns['total_uses'])) {
	$db->Execute("ALTER TABLE snippet ADD COLUMN total_uses INT UNSIGNED NOT NULL DEFAULT 0");
	
	if(isset($tables['snippet_usage'])) {
		$db->Execute("UPDATE snippet SET total_uses = (SELECT IFNULL(SUM(hits),0) FROM snippet_usage WHERE snippet_id=snippet.id)");
	}
}

// ===========================================================================
// Finish

return TRUE;