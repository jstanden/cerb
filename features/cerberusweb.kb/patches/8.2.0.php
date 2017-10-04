<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add `updated_at` to the `kb_category` table

if(!isset($tables['kb_category'])) {
	$logger->error("The 'kb_category' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('kb_category');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE kb_category ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE kb_category SET updated_at = %d", time()));
}

// [TODO] Move KB content to a storage table (per language?)

return TRUE;