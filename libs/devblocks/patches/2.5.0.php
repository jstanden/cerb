<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Modify `devblocks_session`

if(!isset($tables['devblocks_session'])) {
	$logger->error("The 'devblocks_session' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('devblocks_session');

$changes = [];

// Add a session id (secure reference)

if(array_key_exists('session_key', $columns)) {
	$changes[] = "change column session_key session_token varchar(128) not null";
	$changes[] = "add index (session_token)";
}

if(!array_key_exists('session_id', $columns)) {
	$changes[] = "add column session_id char(40) not null default '' first";
}

// Increase the `devblocks_session.user_ip` field for longer IPv6 formats
if(array_key_exists('user_ip', $columns) && 'varchar(64)' != $columns['user_ip']['type'])
	$changes[] = "modify column user_ip varchar(64) not null default ''";

if(!empty($changes)) {
	$sql = sprintf("ALTER TABLE devblocks_session %s", implode(', ', $changes));
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	if(!array_key_exists('session_id', $columns)) {
		$db->ExecuteMaster("UPDATE devblocks_session SET session_id=sha1(rand()) WHERE session_id=''");
		$db->ExecuteMaster("ALTER TABLE devblocks_session DROP PRIMARY KEY, ADD PRIMARY KEY (session_id)");
	}
}

// ===========================================================================
// Finish

return TRUE;