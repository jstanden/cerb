<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

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
	'ai.cerb.chooser.toolbar.json',
];

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
	
	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;
	
	DAO_Automation::importFromJson($automation_data);
	
	unset($automation_data);
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
// Finish up

return TRUE;
