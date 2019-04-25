<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add `devblocks_registry.expires_at`

if(!isset($tables['devblocks_registry'])) {
	$logger->error("The 'devblocks_registry' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('devblocks_registry');

$changes = [];

if(!array_key_exists('entry_expires_at', $columns)) {
	$changes[] = 'add column entry_expires_at int unsigned not null default 0';
}

if(!empty($changes)) {
	$sql = sprintf("ALTER TABLE devblocks_registry %s", implode(', ', $changes));
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
}

// ===========================================================================
// Finish

return TRUE;