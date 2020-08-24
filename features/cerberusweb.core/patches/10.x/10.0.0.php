<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add `automation` table

if(!isset($tables['automation'])) {
	$sql = sprintf("
		CREATE TABLE `automation` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`description` varchar(255) NOT NULL DEFAULT '',
		`extension_id` varchar(255) NOT NULL DEFAULT '',
		`extension_params_json` mediumtext,
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`updated_at` int(10) unsigned NOT NULL DEFAULT 0,
		`script` mediumtext,
		`policy_kata` text,
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (extension_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation'] = 'automation';
}

// ===========================================================================
// Add `automation_datastore` table

if(!isset($tables['automation_datastore'])) {
	$sql = sprintf("
		CREATE TABLE `automation_datastore` (
		`data_key` varchar(255) NOT NULL DEFAULT '',
		`data_value` mediumtext,
		`expires_at` int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (`data_key`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['automation_datastore'] = 'automation_datastore';
}

// ===========================================================================
// Drop `email_signature.is_default`

list($columns,) = $db->metaTable('email_signature');

if(array_key_exists('is_default', $columns)) {
	$sql = "ALTER TABLE email_signature DROP COLUMN is_default";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Finish up

return TRUE;
