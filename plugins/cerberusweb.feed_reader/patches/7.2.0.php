<?php 
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Fix missing default fields

if(!isset($tables['feed_item'])) {
	$logger->error("The 'feed_item' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('feed_item');

$changes = array();

if(isset($columns['feed_id']) && is_null($columns['feed_id']['default']))
	$changes[] = 'change column feed_id feed_id int unsigned not null default 0';

if(!empty($changes)) {
	$sql = sprintf("ALTER TABLE feed_item %s", implode(', ', $changes));
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
}

return TRUE;