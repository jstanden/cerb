<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$settings = DevblocksPlatform::services()->pluginSettings();
$tables = $db->metaTables();

// ===========================================================================
// Increase the size of the twitter_message.content field to accomodate new length limit

if(!isset($tables['twitter_message'])) {
	$logger->error("The 'twitter_message' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('twitter_message');

if(@$columns['content'] && 0 == strcasecmp($columns['content']['type'], 'varchar(255)')) {
	$db->ExecuteMaster("ALTER TABLE twitter_message MODIFY COLUMN content VARCHAR(320) NOT NULL DEFAULT ''");
}

return TRUE;