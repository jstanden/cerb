<?php
/** @noinspection SqlResolve */
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// Add `namespace` to `queue_message`

list($columns, ) = $db->metaTable('queue_message');

$changes = [];

if(!array_key_exists('namespace', $columns)) {
	$changes[] = "ADD COLUMN namespace varchar(128) NOT NULL DEFAULT '' AFTER queue_id";
	$changes[] = "DROP INDEX queue_claimed";
	$changes[] = "ADD INDEX queue_claimed (queue_id, namespace, status_id, consumer_id)";
}

if($changes) {
	$db->ExecuteMaster("ALTER TABLE queue_message ".
		implode(', ', $changes)
	);
}

// ===========================================================================
// Search Index

if(!array_key_exists('search_index', $tables)) {
	$sql = sprintf("
		CREATE TABLE `search_index` (
		`id` int unsigned AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`uri` varchar(128) NOT NULL DEFAULT '',
		`record_type` varchar(128) not null default '',
		`record_filter` varchar(128) not null default '',
		`extension_id` varchar(255) not null default '',
		`extension_params_json` mediumtext,
		`priority` tinyint unsigned NOT NULL DEFAULT 50,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`updated_at` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['search_index'] = 'search_index';
}

if(!array_key_exists('search_index_tokens', $tables)) {
	$sql = sprintf("
		CREATE TABLE `search_index_tokens` (
		`token_hash` bigint NOT NULL DEFAULT 0,
		`token` varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY ('token_hash')
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['search_index_tokens'] = 'search_index_tokens';
}

// ===========================================================================
// Convert `custom_field_clobvalue.field_value` to utf8mb4

if(!array_key_exists('custom_field_clobvalue', $tables))
	return FALSE;

list($columns,) = $db->metaTable('custom_field_clobvalue');

if(!array_key_exists('field_value', $columns))
	return FALSE;

if('utf8mb4_unicode_ci' != $columns['field_value']['collation']) {
	$db->ExecuteMaster("ALTER TABLE custom_field_clobvalue MODIFY COLUMN field_value MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	$db->ExecuteMaster("REPAIR TABLE custom_field_clobvalue");
	$db->ExecuteMaster("OPTIMIZE TABLE custom_field_clobvalue");
}

// ===========================================================================
// Finish up

return TRUE;
