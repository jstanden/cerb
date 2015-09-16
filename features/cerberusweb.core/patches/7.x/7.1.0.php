<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add the `context_avatar` table

if(!isset($tables['context_avatar'])) {
	$sql = sprintf("
		CREATE TABLE context_avatar (
		  id int(10) unsigned NOT NULL AUTO_INCREMENT,
		  context varchar(255) NOT NULL,
		  context_id int unsigned NOT NULL,
		  content_type varchar(255) NOT NULL DEFAULT '',
		  is_approved tinyint(1) unsigned NOT NULL DEFAULT '0',
		  updated_at int(10) unsigned NOT NULL DEFAULT '0',
		  storage_extension varchar(255) NOT NULL DEFAULT '',
		  storage_key varchar(255) NOT NULL DEFAULT '',
		  storage_size int(10) unsigned NOT NULL DEFAULT '0',
		  storage_profile_id int(10) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (id),
		  UNIQUE guid (guid),
		  UNIQUE context_and_id (context, context_id),
		  KEY storage_extension (storage_extension)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['context_avatar'] = 'context_avatar';
}

// ===========================================================================
// Finish up

return TRUE;
