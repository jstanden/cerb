<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// Automation Event Listener

if(!isset($tables['automation_event_listener'])) {
	$sql = sprintf("
		CREATE TABLE `automation_event_listener` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`event_name` varchar(255) NOT NULL DEFAULT '',
		`is_disabled` tinyint unsigned NOT NULL DEFAULT 0,
		`priority` tinyint unsigned NOT NULL DEFAULT 50,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`updated_at` int unsigned NOT NULL DEFAULT 0,
		`event_kata` mediumtext,
		`workflow_id` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX (event_name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_event_listener'] = 'automation_event_listener';
}

// ===========================================================================
// Drop `automation_event.automations_kata`

list($columns, ) = $db->metaTable('automation_event');

if(array_key_exists('automations_kata', $columns)) {
	// Migrate automation_event KATA to event listeners
	$db->ExecuteMaster("INSERT IGNORE INTO automation_event_listener (name, event_name, priority, created_at, updated_at, event_kata) SELECT 'Default', name, 25, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), automations_kata FROM automation_event");
	
	// Migrate event changeset history to the new 'Default' listener records
	$db->ExecuteMaster("UPDATE IGNORE record_changeset SET record_type = 'automation_event_listener', record_id = (select id from automation_event_listener where name = 'Default' and event_name = (select cast(name as binary) from automation_event where id = record_changeset.record_id)) where record_type = 'automation_event'");
	
	// Drop the old column
	$db->ExecuteMaster('ALTER TABLE automation_event DROP COLUMN automations_kata');
}

// ===========================================================================
// Toolbar Section

if(!isset($tables['toolbar_section'])) {
	$sql = sprintf("
		CREATE TABLE `toolbar_section` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`toolbar_name` varchar(255) NOT NULL DEFAULT '',
		`is_disabled` tinyint unsigned NOT NULL DEFAULT 0,
		`priority` tinyint unsigned NOT NULL DEFAULT 50,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`updated_at` int unsigned NOT NULL DEFAULT 0,
		`toolbar_kata` mediumtext,
		`workflow_id` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX (toolbar_name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['toolbar_section'] = 'toolbar_section';
}

// ===========================================================================
// Drop `toolbar.toolbar_kata`

list($columns, ) = $db->metaTable('toolbar');

if(array_key_exists('toolbar_kata', $columns)) {
	// Migrate automation_event KATA to event listeners
	$db->ExecuteMaster("INSERT IGNORE INTO toolbar_section (name, toolbar_name, priority, created_at, updated_at, toolbar_kata) SELECT 'Default', name, 25, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), toolbar_kata FROM toolbar");
	
	// Migrate event changeset history to the new 'Default' listener records
	$db->ExecuteMaster("UPDATE IGNORE record_changeset SET record_type = 'toolbar_section', record_id = (select id from toolbar_section where name = 'Default' and toolbar_name = (select cast(name as binary) from toolbar where id = record_changeset.record_id)) where record_type = 'toolbar'");
	
	// Drop the old column
	$db->ExecuteMaster('ALTER TABLE toolbar DROP COLUMN toolbar_kata');
}

// ===========================================================================
// Update built-in automations

$automation_files = [
	'ai.cerb.automationBuilder.action.recordGet.json',
	'ai.cerb.automationBuilder.action.recordUpsert.json',
	'ai.cerb.chooser.toolbar.json',
	'cerb.reply.isBannedDefunct.json',
	'cerb.ticket.spam.json',
];

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
	
	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;
	
	DAO_Automation::importFromJson($automation_data);
	
	unset($automation_data);
}

// ===========================================================================
// Cleanup ACL and translations for old plugins

$db->ExecuteWriter("DELETE FROM translation WHERE string_id IN ('acl.reports.group.bots','acl.reports.group.snippets')");

// ===========================================================================
// Update package library

$packages = [
	'cerb_connected_service_anthropic.json',
	'cerb_profile_widget_ticket_participants.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

$db->ExecuteWriter("DELETE FROM package_library WHERE uri IN ('cerb_connected_service_nest')");

// ===========================================================================
// Add event listener for cerb.reply.isBannedDefunct

if(!$db->GetOneMaster("SELECT id FROM automation_event_listener WHERE event_name = 'mail.reply.validate' AND name = 'Banned'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT IGNORE INTO automation_event_listener (name, event_name, priority, created_at, updated_at, event_kata) ".
		"VALUES (%s, %s, %d, %d, %d, %s)",
		$db->qstr('Banned'),
		$db->qstr('mail.reply.validate'),
		100,
		time(),
		time(),
		$db->qstr("automation/isBannedDefunct:\n  uri: cerb:automation:cerb.reply.isBannedDefunct\n  inputs:\n    message: {{message_id}}\n  disabled@bool: {{not(message_sender_is_banned or message_sender_is_defunct)}}")
	));
}

// ===========================================================================
// Add Routing KATA to buckets

list($columns,) = $db->metaTable('bucket');

if(!array_key_exists('routing_kata', $columns)) {
	$sql = "ALTER TABLE bucket ADD COLUMN routing_kata mediumtext";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Modify card_widget.options_kata for conditionality

list($columns, ) = $db->metaTable('card_widget');

if(!array_key_exists('options_kata', $columns)) {
	$db->ExecuteMaster('ALTER TABLE card_widget ADD COLUMN options_kata TEXT');
}

// ===========================================================================
// Modify workspace_widget.options_kata for conditionality

list($columns, ) = $db->metaTable('workspace_widget');

if(!array_key_exists('options_kata', $columns)) {
	$db->ExecuteMaster('ALTER TABLE workspace_widget ADD COLUMN options_kata TEXT');
}

// ===========================================================================
// OAuth App

list($columns, ) = $db->metaTable('oauth_app');

if(!array_key_exists('access_token_ttl', $columns)) {
	$db->ExecuteMaster("ALTER TABLE oauth_app ADD COLUMN access_token_ttl varchar(32) not null default '1 hour'");
	$db->ExecuteMaster("UPDATE oauth_app SET access_token_ttl = '1 hour' WHERE access_token_ttl = ''");
}

if(!array_key_exists('refresh_token_ttl', $columns)) {
	$db->ExecuteMaster("ALTER TABLE oauth_app ADD COLUMN refresh_token_ttl varchar(32) not null default '1 month'");
	$db->ExecuteMaster("UPDATE oauth_app SET refresh_token_ttl = '1 month' WHERE refresh_token_ttl = ''");
}

// ===========================================================================
// Workflows

if(!isset($tables['workflow'])) {
	$sql = sprintf("
		CREATE TABLE `workflow` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`description` varchar(255) NOT NULL DEFAULT '',
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`updated_at` int(10) unsigned NOT NULL DEFAULT 0,
		`workflow_kata` mediumtext,
		`config_kata` mediumtext,
		`resources_kata` mediumtext,
		`has_extensions` tinyint unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['workflow'] = 'workflow';
}

// ===========================================================================
// Convert PHP serialization in the Support Center portal to JSON

if (!array_key_exists('community_tool_property', $tables)) {
	$logger->error("The 'community_tool_property' table does not exist.");
	return FALSE;
}

$results = $db->GetArrayMaster(
	"SELECT tool_code, property_key, property_value FROM community_tool_property " .
	"WHERE property_key IN ('announcements.rss','common.visible_modules','contact.situations','kb.roots') " .
	"AND property_value NOT LIKE '{%'"
) ?? [];

foreach ($results as $result) {
	// Convert arrays from serialize/unserialize to JSON
	$property_value = unserialize($result['property_value'], ['allowed_classes' => false]);
	
	if(!is_array($property_value))
		$property_value = [];
	
	$db->ExecuteMaster(sprintf("UPDATE community_tool_property SET property_value = %s WHERE tool_code = %s AND property_key = %s",
		$db->qstr(json_encode($property_value)),
		$db->qstr($result['tool_code']),
		$db->qstr($result['property_key']),
	));
}

// ===========================================================================
// Convert PHP serialization in mail_to_group_rule

if (!array_key_exists('mail_to_group_rule', $tables)) {
	$logger->error("The 'mail_to_group_rule' table does not exist.");
	return FALSE;
}

$results = $db->GetArrayMaster(
	"SELECT id, criteria_ser, actions_ser FROM mail_to_group_rule WHERE (criteria_ser LIKE 'a:%' OR actions_ser LIKE 'a:%')"
) ?? [];

foreach ($results as $result) {
	// Convert arrays from serialize/unserialize to JSON
	$criteria_ser = unserialize($result['criteria_ser'], ['allowed_classes' => false]);
	$actions_ser = unserialize($result['actions_ser'], ['allowed_classes' => false]);
	
	if(!is_array($criteria_ser))
		$criteria_ser = [];
	if(!is_array($actions_ser))
		$actions_ser = [];
	
	$db->ExecuteMaster(sprintf("UPDATE mail_to_group_rule SET criteria_ser = %s, actions_ser = %s WHERE id = %d",
		$db->qstr(json_encode($criteria_ser)),
		$db->qstr(json_encode($actions_ser)),
		intval($result['id']),
	));
}

if($revision < 1462) {
	$db->ExecuteMaster("DELETE FROM community_session");
}

// ===========================================================================
// Finish up

return TRUE;
