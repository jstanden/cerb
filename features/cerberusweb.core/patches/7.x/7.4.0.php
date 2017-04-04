<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Remove `is_private` from `trigger_event`

if(!isset($tables['trigger_event'])) {
	$logger->error("The 'trigger_event' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('trigger_event');

if(isset($columns['is_private'])) {
	$db->ExecuteMaster(sprintf("UPDATE trigger_event SET event_params_json = %s WHERE is_private = 1 AND event_point LIKE 'event.macro.%%'",
		$db->qstr(json_encode(['visibility' => 'bots']))
	));
	$db->ExecuteMaster("ALTER TABLE trigger_event DROP COLUMN is_private");
}

// ===========================================================================
// Add `context_saved_search` table

if(!isset($tables['context_saved_search'])) {
	$sql = sprintf("
	CREATE TABLE `context_saved_search` (
		id int unsigned auto_increment,
		name varchar(255) not null default '',
		context varchar(255) not null default '',
		tag varchar(128) not null default '',
		owner_context varchar(255) not null default '',
		owner_context_id int unsigned not null default 0,
		`query` text,
		updated_at int unsigned not null default 0,
		primary key (id),
		index (context),
		index owner (owner_context, owner_context_id)
	) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['context_saved_search'] = 'context_saved_search';
}

// ===========================================================================
// Finish up

return TRUE;
