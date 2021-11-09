<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add `queue` table

if(!isset($tables['queue'])) {
	$sql = sprintf("
		CREATE TABLE `queue` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(128) NOT NULL DEFAULT '',
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`updated_at` int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['queue'] = 'queue';
}

// ===========================================================================
// Add `queue_message` table

if(!isset($tables['queue_message'])) {
	$sql = sprintf("
		CREATE TABLE `queue_message` (
			`uuid` binary(16),
			`queue_id` int(10) unsigned NOT NULL,
			`status_id` tinyint unsigned NOT NULL DEFAULT 0,
			`status_at` int unsigned NOT NULL DEFAULT 0,
			`consumer_id` binary(16),
			`message` TEXT,
			PRIMARY KEY (uuid),
			INDEX queue_claimed (queue_id, status_id, consumer_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['queue_message'] = 'queue_message';
}

// ===========================================================================
// Add `automation_resource`

if(!isset($tables['automation_resource'])) {
	$sql = sprintf("
		CREATE TABLE `automation_resource` (
		id int(10) unsigned NOT NULL AUTO_INCREMENT,
		token varchar(255) NOT NULL DEFAULT '',
		mime_type varchar(255) NOT NULL DEFAULT '',
		expires_at int(10) unsigned NOT NULL DEFAULT '0',
		storage_size int(10) unsigned NOT NULL DEFAULT '0',
		storage_key varchar(255) NOT NULL DEFAULT '',
		storage_extension varchar(255) NOT NULL DEFAULT '',
		storage_profile_id int(10) unsigned NOT NULL DEFAULT '0',
		updated_at int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (id),
		UNIQUE KEY `token` (`token`(6)),
		KEY `expires_at` (`expires_at`),
		KEY `storage_extension` (`storage_extension`),
		KEY `updated_at` (`updated_at`)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_resource'] = 'automation_resource';
}

// ===========================================================================
// Update built-in automations

$automation_files = [
	'ai.cerb.automationBuilder.action.function.json',
	'ai.cerb.automationBuilder.action.httpRequest.json',
	'ai.cerb.automationBuilder.interaction.worker.await.promptSheet.json',
	'ai.cerb.editor.mapBuilder.json',
	'ai.cerb.eventHandler.automation.json',
	'ai.cerb.toolbarBuilder.interaction.json',
	'cerb.data.records.json',
	'cerb.mailRouting.moveToGroup.json',
	'cerb.projectBoard.toolbar.task.find.json',
	'cerb.ticket.move.json',
	'cerb.ticket.participants.manage.json',
];

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
	
	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;
	
	DAO_Automation::importFromJson($automation_data);
	
	unset($automation_data);
}

// ===========================================================================
// Finish up

return TRUE;