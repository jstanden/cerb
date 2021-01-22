<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// num_messages_out, num_messages_in

list($columns,) = $db->metaTable('ticket');

$changes = [];

if(!array_key_exists('num_messages_in', $columns)) {
	$changes[] = "ADD COLUMN num_messages_in INT UNSIGNED NOT NULL DEFAULT 0";
}

if(!array_key_exists('num_messages_out', $columns)) {
	$changes[] = "ADD COLUMN num_messages_out INT UNSIGNED NOT NULL DEFAULT 0";
}

if($changes) {
	$sql = "ALTER TABLE ticket " . implode(',', $changes);
	$db->ExecuteMaster($sql);
	
	/** @noinspection SqlWithoutWhere */
	$sql = "UPDATE ticket set num_messages_in=(select count(id) from message where ticket_id=ticket.id and is_outgoing=0), num_messages_out=(select count(id) from message where ticket_id=ticket.id and is_outgoing=1)";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Finish up

return true;