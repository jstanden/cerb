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
// Finish up

return TRUE;
