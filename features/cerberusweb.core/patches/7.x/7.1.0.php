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
// Fix the arbitrary name length restrictions on the `address` table

if(!isset($tables['address'])) {
	$logger->error("The 'address' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('address');

if(isset($columns['first_name']) && 0 != strcasecmp('varchar(128)', $columns['first_name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE address MODIFY COLUMN first_name varchar(128) not null default ''");
}

if(isset($columns['last_name']) && 0 != strcasecmp('varchar(128)', $columns['last_name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE address MODIFY COLUMN last_name varchar(128) not null default ''");
}

// ===========================================================================
// Fix the arbitrary name length restrictions on the `worker` table

if(!isset($tables['worker'])) {
	$logger->error("The 'worker' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker');

if(isset($columns['first_name']) && 0 != strcasecmp('varchar(128)', $columns['first_name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker MODIFY COLUMN first_name varchar(128) not null default ''");
}

if(isset($columns['last_name']) && 0 != strcasecmp('varchar(128)', $columns['last_name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker MODIFY COLUMN last_name varchar(128) not null default ''");
}
// ===========================================================================
// Finish up

return TRUE;
