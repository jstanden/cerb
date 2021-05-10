<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add `time_actual_secs` to `timetracking_entry`

list($columns, $indexes) = $db->metaTable('timetracking_entry');

if(!isset($columns['time_actual_secs'])) {
	$sql = "ALTER TABLE timetracking_entry ADD COLUMN time_actual_secs INT UNSIGNED NOT NULL DEFAULT 0";
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster("UPDATE timetracking_entry SET time_actual_secs = time_actual_mins * 60");
}

return TRUE;