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
// Convert MySQL Fulltext indexes

// Contacts
if(!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('contact'), $db->qstr('text')))) {
	$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
		"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Contacts'),
		$db->qstr('contacts'),
		$db->qstr('contact'),
		$db->qstr('text'),
		$db->qstr('cerb.search.index.fulltext'),
		$db->qstr(json_encode(['record_query' => '', 'content' => "{{first_name}} {{last_name}}\n{{aliases|join(' ')}}\n{{title}}\n{{email_address}} {{emails|join(' ')}}\n{{org__label}}\n{{username}}"])),
		0,
		time(),
		time(),
	);
	$db->ExecuteMaster($sql);
}

if(array_key_exists('fulltext_contact', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_contact');
	unset($tables['fulltext_contact']);
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.contact'");
}

// Organizations
if(!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('org'), $db->qstr('text')))) {
	$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
		"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Organizations'),
		$db->qstr('orgs'),
		$db->qstr('org'),
		$db->qstr('text'),
		$db->qstr('cerb.search.index.fulltext'),
		$db->qstr(json_encode(['record_query' => '', 'content' => "{{name}}\n{{aliases|join(' ')}}\n{{street}} {{city}} {{province}} {{postal}} {{country}}\n{{website}}\n{{email_address}}"])),
		0,
		time(),
		time(),
	);
	$db->ExecuteMaster($sql);
}

if(array_key_exists('fulltext_org', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_org');
	unset($tables['fulltext_org']);
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.org'");
}
// Jira Issues
if(array_key_exists('fulltext_jira_issue', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_jira_issue');
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'jira.search.schema.jira_issue'");
}

// Snippets
if(!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('snippet'), $db->qstr('text')))) {
	$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
		"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Snippets Content'),
		$db->qstr('snippets'),
		$db->qstr('snippet'),
		$db->qstr('text'),
		$db->qstr('cerb.search.index.fulltext'),
		$db->qstr(json_encode(['record_query' => '', 'content' => "{{title}}\n\n{{content}}"])),
		0,
		time(),
		time(),
	);
	$db->ExecuteMaster($sql);
}

if(array_key_exists('fulltext_snippet', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_snippet');
	unset($tables['fulltext_snippet']);
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.snippet'");
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
