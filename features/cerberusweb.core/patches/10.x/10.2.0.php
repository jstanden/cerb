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
// Add `metric` table

if(!isset($tables['metric'])) {
	$sql = sprintf("
		CREATE TABLE `metric` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(128) NOT NULL DEFAULT '',
		`description` varchar(128) NOT NULL DEFAULT '',
		`dimensions_kata` mediumtext,
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`updated_at` int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE (name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['metric'] = 'metric';
	
	// Configure the scheduler job
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.metrics', 'enabled', '1')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.metrics', 'duration', '1')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.metrics', 'term', 'm')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.metrics', 'lastrun', '0')");
	$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.metrics', 'locked', '0')");
	
	// Add default queues
	$db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at) VALUES ('cerb.metrics.publish', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
}

// ===========================================================================
// Add `metric_dimension` table

if(!isset($tables['metric_dimension'])) {
	$sql = sprintf("
		CREATE TABLE `metric_dimension` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY (id),
		UNIQUE (name)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['metric_dimension'] = 'metric_dimension';
}

// ===========================================================================
// Add `metric_value` table

if(!isset($tables['metric_value'])) {
	$sql = sprintf("
		CREATE TABLE `metric_value` (
		`metric_id` int unsigned NOT NULL,
		`granularity` mediumint unsigned NOT NULL DEFAULT 0,
		`bin` int unsigned NOT NULL DEFAULT 0,
		`samples` mediumint NOT NULL DEFAULT 0,
		`sum` decimal(22,4) NOT NULL DEFAULT 0,
		`min` decimal(22,4) NOT NULL DEFAULT 0,
		`max` decimal(22,4) NOT NULL DEFAULT 0,
		`dim0_value_id` int unsigned NOT NULL DEFAULT 0,
		`dim1_value_id` int unsigned NOT NULL DEFAULT 0,
		`dim2_value_id` int unsigned NOT NULL DEFAULT 0,
		`expires_at` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (metric_id, granularity, bin, dim0_value_id, dim1_value_id, dim2_value_id),
		INDEX metric_dim1 (metric_id, granularity, bin, dim1_value_id, dim2_value_id),
		INDEX metric_dim2 (metric_id, granularity, bin, dim2_value_id),
		INDEX (expires_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['metric_value'] = 'metric_value';
}

// ===========================================================================
// Add default metrics

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %d, %d)",
	$db->qstr('cerb.automation.invocations'),
	$db->qstr('Invocation count by automation and trigger'),
	$db->qstr("record/automation_id:\n  record_type: automation\nextension/trigger:"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %d, %d)",
	$db->qstr('cerb.automation.duration'),
	$db->qstr('Invocation duration by automation and trigger'),
	$db->qstr("record/automation_id:\n  record_type: automation\nextension/trigger:"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %d, %d)",
	$db->qstr('cerb.webhook.invocations'),
	$db->qstr('Invocation count by webhook and client IP'),
	$db->qstr("record/webhook_id:\n  record_type: webhook_listener\ntext/client_ip:"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %d, %d)",
	$db->qstr('cerb.record.search'),
	$db->qstr('Search popup count by record type and worker'),
	$db->qstr("text/record_type:\nrecord/worker_id:\n  record_type: worker"),
	time(),
	time()
));

// ===========================================================================
// Finish up

return TRUE;