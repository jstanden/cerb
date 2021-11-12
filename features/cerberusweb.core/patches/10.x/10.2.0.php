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

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %d, %d)",
	$db->qstr('cerb.workers.active'),
	$db->qstr('Seat usage by worker'),
	$db->qstr("record/worker_id:\n  record_type: worker"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %d, %d)",
	$db->qstr('cerb.tickets.open'),
	$db->qstr('Open ticket counts over time by group and bucket'),
	$db->qstr("record/group_id:\n  record_type: group\nrecord/bucket_id:\n  record_type: bucket"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %d, %d)",
	$db->qstr('cerb.snippet.uses'),
	$db->qstr('Snippet usage by worker over time'),
	$db->qstr("record/snippet_id:\n  record_type: snippet\nrecord/worker_id:\n  record_type: worker"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %d, %d)",
	$db->qstr('cerb.behavior.invocations'),
	$db->qstr('Invocation count by behavior and event'),
	$db->qstr("record/behavior_id:\n  record_type: behavior\nextension/event:"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %d, %d)",
	$db->qstr('cerb.behavior.duration'),
	$db->qstr('Invocation duration by behavior and event'),
	$db->qstr("record/behavior_id:\n  record_type: behavior\nextension/event:"),
	time(),
	time()
));

// ===========================================================================
// Migrate `snippet_use_history` to metric

if(array_key_exists('snippet_use_history', $tables)) {
	$metric_id = $db->GetOneMaster("SELECT id FROM metric WHERE name = 'cerb.snippet.uses'");
	
	if($metric_id) {
		$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric_value (metric_id, granularity, bin, samples, sum, min, max, dim0_value_id, dim1_value_id, dim2_value_id, expires_at)  ".
			"SELECT %d, 86400, ts_day, 1, uses, uses, uses, snippet_id, worker_id, 0, 0 from snippet_use_history;",
			$metric_id
		));
	}
	
	$db->ExecuteWriter("DROP TABLE snippet_use_history");
	
	unset($tables['snippet_use_history']);
}

// ===========================================================================
// Migrate `trigger_event_history` to metric

if(array_key_exists('trigger_event_history', $tables)) {
	$metric_id_invocations = $db->GetOneMaster("SELECT id FROM metric WHERE name = 'cerb.behavior.invocations'");
	$metric_id_duration = $db->GetOneMaster("SELECT id FROM metric WHERE name = 'cerb.behavior.duration'");
	
	// Insert behavior triggers as dimensions
	$db->ExecuteWriter("INSERT IGNORE INTO metric_dimension (name) SELECT DISTINCT event_point FROM trigger_event");
	
	if($metric_id_invocations) {
		$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric_value (metric_id, granularity, bin, samples, sum, min, max, dim0_value_id, dim1_value_id, dim2_value_id, expires_at) ".
			"SELECT %d, 86400, th.ts_day, 1, th.uses, th.uses, th.uses, th.trigger_id, (SELECT id FROM metric_dimension WHERE name = b.event_point), 0, 0 from trigger_event_history th inner join trigger_event b on (b.id=th.trigger_id)",
			$metric_id_invocations
		));
	}
	
	if($metric_id_duration) {
		$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric_value (metric_id, granularity, bin, samples, sum, min, max, dim0_value_id, dim1_value_id, dim2_value_id, expires_at) ".
			"SELECT %d, 86400, th.ts_day, 1, th.elapsed_ms, th.elapsed_ms, th.elapsed_ms, th.trigger_id, (SELECT id FROM metric_dimension WHERE name = b.event_point), 0, 0 from trigger_event_history th inner join trigger_event b on (b.id=th.trigger_id)",
			$metric_id_duration
		));
	}
	
	$db->ExecuteWriter("DROP TABLE trigger_event_history");
	
	unset($tables['trigger_event_history']);
}

// ===========================================================================
// Finish up

return TRUE;