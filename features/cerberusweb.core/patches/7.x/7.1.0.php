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
		  UNIQUE context_and_id (context, context_id),
		  KEY storage_extension (storage_extension)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['context_avatar'] = 'context_avatar';
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

if(isset($columns['title']) && 0 != strcasecmp('varchar(255)', $columns['title']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker MODIFY COLUMN title varchar(255) not null default ''");
}

if(isset($columns['email']) && 0 != strcasecmp('varchar(255)', $columns['email']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker MODIFY COLUMN email varchar(255) not null default ''");
}

// ===========================================================================
// Fix the arbitrary name length restrictions on the `worker_group` table

if(!isset($tables['worker_group'])) {
	$logger->error("The 'worker_group' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_group');

if(isset($columns['name']) && 0 != strcasecmp('varchar(255)', $columns['name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE worker_group MODIFY COLUMN name varchar(255) not null default ''");
}

// ===========================================================================
// Fix the arbitrary name length restrictions on the `bucket` table

if(!isset($tables['bucket'])) {
	$logger->error("The 'bucket' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('bucket');

if(isset($columns['name']) && 0 != strcasecmp('varchar(255)', $columns['name']['type'])) {
	$db->ExecuteMaster("ALTER TABLE bucket MODIFY COLUMN name varchar(255) not null default ''");
}

// ===========================================================================
// ===========================================================================
// Alter `address` (drop first_name, last_name, contact_person)

if(!isset($tables['address'])) {
	$logger->error("The 'address' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('address');

$changes = array();

if(isset($columns['first_name']))
	$changes[] = "DROP COLUMN first_name";
if(isset($columns['last_name']))
	$changes[] = "DROP COLUMN last_name";
if(isset($columns['contact_person_id']))
	$changes[] = "DROP COLUMN contact_person_id";

if(!empty($changes)) {
	$sql = "ALTER TABLE address " . implode(', ', $changes);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Drop 'contact_person'

if(isset($tables['contact_person'])) {
	$sql = "DROP TABLE contact_person";
	$db->Execute($sql);
	
	unset($tables['contact_person']);
}

// ===========================================================================
// Drop 'openid_to_contact_person'

if(isset($tables['openid_to_contact_person'])) {
	$sql = "DROP TABLE openid_to_contact_person";
	$db->Execute($sql);
	
	unset($tables['openid_to_contact_person']);
}

// ===========================================================================
// Finish up

return TRUE;
