<?php
/** @noinspection SqlResolve */
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// Add `namespace` to `queue_message`

list($columns, ) = $db->metaTable('queue_message');

$changes = [];

if(!array_key_exists('namespace', $columns)) {
	$changes[] = "ADD COLUMN namespace varchar(128) NOT NULL DEFAULT '' AFTER queue_id";
	$changes[] = "DROP INDEX queue_claimed";
	$changes[] = "ADD INDEX queue_claimed (queue_id, namespace, status_id, consumer_id)";
}

if($changes) {
	$db->ExecuteMaster("ALTER TABLE queue_message ".
		implode(', ', $changes)
	);
}

// ===========================================================================
// Finish up

return TRUE;
