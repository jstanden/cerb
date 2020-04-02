<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Drop `mailbox.ssl_ignore_validation` bit

list($columns,) = $db->metaTable('mailbox');

if(array_key_exists('ssl_ignore_validation', $columns)) {
	$sql = "ALTER TABLE mailbox DROP COLUMN ssl_ignore_validation";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Drop `mailbox.auth_disable_plain` bit

list($columns,) = $db->metaTable('mailbox');

if(array_key_exists('auth_disable_plain', $columns)) {
	$sql = "ALTER TABLE mailbox DROP COLUMN auth_disable_plain";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Finish up

return true;