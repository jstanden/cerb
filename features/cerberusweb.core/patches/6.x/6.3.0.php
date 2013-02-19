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
// Add a new 'snippet_use_history' table for time-based reports

if(!isset($tables['snippet_use_history'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS snippet_use_history (
			snippet_id INT UNSIGNED NOT NULL DEFAULT 0,
			worker_id INT UNSIGNED NOT NULL DEFAULT 0,
			ts_day INT UNSIGNED NOT NULL DEFAULT 0,
			uses INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (snippet_id, worker_id, ts_day)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['snippet_use_history'] = 'snippet_use_history';
	
	// Copy over the previous per-worker stats
	if(isset($tables['snippet_usage'])) {
		$db->Execute("INSERT INTO snippet_use_history SELECT snippet_id, worker_id, 0 AS ts_day, hits FROM snippet_usage");
	}
}

if(isset($tables['snippet_usage'])) {
	$db->Execute("DROP TABLE snippet_usage");
	unset($tables['snippet_usage']);
}

// ===========================================================================
// Add a new 'trigger_event_history' table for time-based reports

if(!isset($tables['trigger_event_history'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS trigger_event_history (
			trigger_id INT UNSIGNED NOT NULL DEFAULT 0,
			ts_day INT UNSIGNED NOT NULL DEFAULT 0,
			uses INT UNSIGNED NOT NULL DEFAULT 0,
			elapsed_ms INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (trigger_id, ts_day)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['trigger_event_history'] = 'trigger_event_history';
}

// ===========================================================================
// Finish

return TRUE;