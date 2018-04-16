<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Drop `placeholder_labels_json` and `placeholder_values_json` in `worker_view_model`

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(isset($columns['placeholder_labels_json'])) {
	$sql = 'ALTER TABLE worker_view_model DROP COLUMN placeholder_labels_json';
	$db->ExecuteMaster($sql);
}

if(isset($columns['placeholder_values_json'])) {
	$sql = 'ALTER TABLE worker_view_model DROP COLUMN placeholder_values_json';
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Finish up

return TRUE;
