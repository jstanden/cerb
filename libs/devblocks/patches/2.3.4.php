<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Increase `devblocks_session.user_ip` length

if(!isset($tables['devblocks_session'])) {
	$logger->error("The 'devblocks_session' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('devblocks_session');

$changes = array();

if(isset($columns['user_ip']) && 'varchar(40)' != $columns['user_ip']['type'])
	$changes[] = "modify column user_ip varchar(40) not null default ''";

if(!empty($changes)) {
	$sql = sprintf("ALTER TABLE devblocks_session %s", implode(', ', $changes));
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
}

// ===========================================================================
// Finish

return TRUE;