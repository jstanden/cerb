<?php
/** @noinspection SqlResolve */
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// Add extension_id, concurrency, priority to `queue`

list($columns, ) = $db->metaTable('queue');

$changes = [];

if(!array_key_exists('extension_id', $columns)) {
	$changes[] = "ADD COLUMN extension_id VARCHAR(255) NOT NULL DEFAULT ''";
}

if(!array_key_exists('extension_params_json', $columns)) {
	$changes[] = "ADD COLUMN extension_params_json TEXT";
}

if($changes) {
	$db->ExecuteMaster("ALTER TABLE queue ".
		implode(', ', $changes)
	);
	
	// Default the queue extension type
	$db->ExecuteMaster("UPDATE queue SET extension_id = 'cerb.queue.consumer.manual' WHERE extension_id = ''");
}

// ===========================================================================
// Add `job_id` to `queue_message`

list($columns, ) = $db->metaTable('queue_message');

$changes = [];

if(array_key_exists('namespace', $columns)) {
	$changes[] = "DROP COLUMN namespace";
}

if(!array_key_exists('job_id', $columns)) {
	$changes[] = "ADD COLUMN job_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER queue_id";
	$changes[] = "DROP INDEX queue_claimed";
	$changes[] = "ADD INDEX queue_claimed (queue_id, status_id, job_id, consumer_id)";
}

// Promote `message` from TEXT (~64KB) to MEDIUMTEXT (~16MB) so wide record snapshots fit
if(array_key_exists('message', $columns) && 'mediumtext' != $columns['message']['type']) {
	$changes[] = "MODIFY COLUMN message MEDIUMTEXT";
}

//if(!array_key_exists('attempt_count', $columns)) {
//	$changes[] = "ADD COLUMN attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0";
//}

//if(!array_key_exists('locked_until', $columns)) {
//	$changes[] = "ADD COLUMN locked_until INT UNSIGNED NOT NULL DEFAULT 0";
//	$changes[] = "ADD INDEX status_locked (status_id, locked_until)";
//}

// [TODO] Add another hash (SHA-1/xxh3) to be idempotent on dupes
//if(!array_key_exists('payload_hash', $columns)) {
//	$changes[] = "ADD COLUMN payload_hash BINARY(16) NULL";
//	$changes[] = "ADD UNIQUE INDEX payload_hash (queue_id, job_id, payload_hash)";
//}

if($changes) {
	$db->ExecuteMaster("ALTER TABLE queue_message ".
		implode(', ', $changes)
	);
}

// ===========================================================================
// Queue Job

if(!array_key_exists('queue_job', $tables)) {
	$sql = sprintf("
		CREATE TABLE `queue_job` (
		`id` bigint unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`singleton_key` varchar(255) NOT NULL DEFAULT '',
		`queue_id` int unsigned NOT NULL DEFAULT 0,
		`worker_id` int unsigned NOT NULL DEFAULT 0,
		`metadata` mediumtext,
		`status_id` tinyint unsigned NOT NULL DEFAULT 0,
		`count_total` int unsigned NOT NULL default 0,
		`count_available` int unsigned NOT NULL default 0,
		`count_inflight` int unsigned NOT NULL default 0,
		`count_done` int unsigned NOT NULL default 0,
		`count_failed` int unsigned NOT NULL default 0,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`updated_at` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX `queue_status` (status_id, queue_id),
		INDEX `worker_id` (worker_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['queue_job'] = 'queue_job';
}

// ===========================================================================
// Queue Job Chunk

if(!array_key_exists('queue_job_chunk', $tables)) {
	$sql = sprintf("
		CREATE TABLE `queue_job_chunk` (
		`job_id` bigint unsigned NOT NULL,
		`chunk_idx` int unsigned NOT NULL,
		`data` mediumblob NOT NULL,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (job_id, chunk_idx),
		INDEX `idx_job` (job_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['queue_job_chunk'] = 'queue_job_chunk';
}

// ===========================================================================
// Queue Log

if(!array_key_exists('queue_log', $tables)) {
	$sql = sprintf("
		CREATE TABLE `queue_log` (
		`id` bigint unsigned NOT NULL AUTO_INCREMENT,
		`job_id` bigint unsigned NOT NULL DEFAULT 0,
		`consumer_id` binary(16) DEFAULT NULL,
		`message` text,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX `job_created` (job_id, created_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['queue_log'] = 'queue_log';
}

// ===========================================================================
// Update built-in queues

$db->ExecuteWriter("UPDATE queue SET extension_id = 'cerb.queue.consumer.internal' WHERE name = 'cerb.metrics.publish'");
$db->ExecuteWriter("UPDATE queue SET extension_id = 'cerb.queue.consumer.internal' WHERE name = 'cerb.update.migrations'");

if(!$db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.search.index'"))
	$db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.search.index', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");

if(!$db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.records.changed'"))
	$db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.records.changed', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");

if(!$db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.records.import'"))
	$db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.records.import', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");

if(!$db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.records.export'"))
	$db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.records.export', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");

if(!$db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.records.bulk_update'"))
	$db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.records.bulk_update', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");

// ===========================================================================
// Drop the legacy `context_bulk_update` table; replaced by `cerb.records.bulk_update` queue jobs

if(array_key_exists('context_bulk_update', $tables)) {
	$db->ExecuteMaster("DROP TABLE context_bulk_update");
	unset($tables['context_bulk_update']);
}

// ===========================================================================
// Enable the new background cronjob

$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'enabled', '1')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'duration', '1')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'term', 'm')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'lastrun', '0')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'locked', '0')");

// Remove retired cron jobs
$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cron.migrations'");

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
		`stem` varchar(128) CHARACTER SET latin1 NOT NULL DEFAULT '',
		PRIMARY KEY (token_hash),
		INDEX `stem` (stem(4)),
		INDEX `token` (token(4))
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

// Workers
if(!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('worker'), $db->qstr('text')))) {
	$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
		"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Workers'),
		$db->qstr('workers'),
		$db->qstr('worker'),
		$db->qstr('text'),
		$db->qstr('cerb.search.index.fulltext'),
		$db->qstr(json_encode(['record_query' => '', 'content' => "{{first_name}} {{last_name}} {{at_mention_name}}\n{{aliases|join(' ')}}\n{{title}}\n{{email_address}}"])),
		0,
		time(),
		time(),
	);
	$db->ExecuteMaster($sql);
}

if(array_key_exists('fulltext_worker', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_worker');
	unset($tables['fulltext_worker']);
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.worker'");
}

// Automations
if(!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('automation'), $db->qstr('script')))) {
	$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
		"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Automations'),
		$db->qstr('automations'),
		$db->qstr('automation'),
		$db->qstr('script'),
		$db->qstr('cerb.search.index.fulltext'),
		$db->qstr(json_encode(['record_query' => '', 'content' => "{{name}}\n{{extension_id}}\n\n~~~\n{{script\n  |strip_data_uris()\n  |strip_pem_blocks()\n  |strip_url_querystrings()\n}}\n~~~"])),
		0,
		time(),
		time(),
	);
	$db->ExecuteMaster($sql);
}

if(array_key_exists('fulltext_automation', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_automation');
	unset($tables['fulltext_automation']);
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.automation'");
}

// Email Addresses
if(!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('address'), $db->qstr('text')))) {
	$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
		"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
		$db->qstr('Email Addresses'),
		$db->qstr('emails'),
		$db->qstr('address'),
		$db->qstr('text'),
		$db->qstr('cerb.search.index.fulltext'),
		$db->qstr(json_encode(['record_query' => '', 'content' => "{{address}}\n{{contact__label}} {{contact_aliases|join(' ')}}\n{{org__label}} {{org_aliases|join(' ')}}"])),
		0,
		time(),
		time(),
	);
	$db->ExecuteMaster($sql);
}

if(array_key_exists('fulltext_address', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_address');
	unset($tables['fulltext_address']);
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.address'");
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
// Add `created_at` to `worker`

list($columns, ) = $db->metaTable('worker');

if(!array_key_exists('created_at', $columns)) {
	$db->ExecuteMaster("ALTER TABLE worker ADD COLUMN created_at int unsigned NOT NULL DEFAULT 0");
	$db->ExecuteMaster("UPDATE worker w SET w.created_at = COALESCE(
		(SELECT MIN(cal.created) FROM context_activity_log cal WHERE cal.actor_context = 'cerberusweb.contexts.worker' AND cal.actor_context_id = w.id),
		(SELECT MIN(m.created_date) FROM message m WHERE m.worker_id = w.id),
		w.updated
	) WHERE w.created_at = 0");
}

// ===========================================================================
// Service Tokens

if(!array_key_exists('service_token', $tables)) {
	$sql = sprintf("CREATE TABLE `service_token` (
		`id` int unsigned AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`token_hint` varchar(32) NOT NULL DEFAULT '',
		`token_hash` char(64) NOT NULL DEFAULT '',
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`updated_at` int unsigned NOT NULL DEFAULT 0,
		`expires_at` int unsigned NOT NULL DEFAULT 0,
		`last_accessed_at` int unsigned NOT NULL DEFAULT 0,
		`scopes` text NOT NULL,
		PRIMARY KEY (id)
	) ENGINE=%s",
		APP_DB_ENGINE
	);
	$db->ExecuteMaster($sql) or die("[PATCH] Failed to create 'service_token' table.");
	$logger->info("[Patch] Created 'service_token' table.");
}

// ===========================================================================
// Metrics

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %s, %d, %d)",
	$db->qstr('cerb.service.token.uses'),
	$db->qstr('Usage count for service token authentications'),
	$db->qstr('counter'),
	$db->qstr("text/scope:\ntext/client_ip:\n"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %s, %d, %d)",
	$db->qstr('cerb.sessions.seat.kicks'),
	$db->qstr('Count of worker sessions ended to free up a license seat'),
	$db->qstr('counter'),
	$db->qstr("record/worker_id:\n  record_type: worker\n"),
	time(),
	time()
));

$db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) ".
	"VALUES (%s, %s, %s, %s, %d, %d)",
	$db->qstr('cerb.sessions.seat.kicks.duration'),
	$db->qstr('Cumulative idle seconds of worker sessions ended to free up a license seat'),
	$db->qstr('counter'),
	$db->qstr("record/worker_id:\n  record_type: worker\n"),
	time(),
	time()
));

// ===========================================================================
// Update built-in automations

$automation_files = [
	'cerb.reply.isBannedDefunct.json',
];

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;

	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;

	DAO_Automation::importFromJson($automation_data);

	unset($automation_data);
}

// ===========================================================================
// Merge group_setting into worker_group (#1844)

list($columns, ) = $db->metaTable('worker_group');

$changes = [];

if(!array_key_exists('subject_has_mask', $columns))
	$changes[] = "ADD COLUMN subject_has_mask tinyint unsigned NOT NULL DEFAULT 0";

if(!array_key_exists('subject_prefix', $columns))
	$changes[] = "ADD COLUMN subject_prefix varchar(128) NOT NULL DEFAULT ''";

if($changes) {
	$db->ExecuteMaster("ALTER TABLE worker_group " . implode(', ', $changes));

	// Migrate data from group_setting
	if(array_key_exists('group_setting', $tables)) {
		$db->ExecuteMaster("UPDATE worker_group wg INNER JOIN group_setting gs ON (gs.group_id = wg.id AND gs.setting = 'subject_has_mask') SET wg.subject_has_mask = CAST(gs.value AS UNSIGNED)");
		$db->ExecuteMaster("UPDATE worker_group wg INNER JOIN group_setting gs ON (gs.group_id = wg.id AND gs.setting = 'subject_prefix') SET wg.subject_prefix = gs.value");
	}
}

if(array_key_exists('group_setting', $tables))
	$db->ExecuteMaster("DROP TABLE group_setting");

// ===========================================================================
// Default `Monitor` card widget for `cerb.contexts.queue.job`

if(!$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.queue.job' AND extension_id='cerb.card.widget.queue.job.monitor'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Monitor'),
		$db->qstr('cerb.contexts.queue.job'),
		$db->qstr('cerb.card.widget.queue.job.monitor'),
		$db->qstr('{}'),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.queue.job`

if(!$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.queue.job'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.queue.job'),
		$db->qstr('cerb.profile.tab.dashboard'),
		time(),
		$db->qstr(json_encode([
			"layout" => "sidebar_left",
		])),
		1
	));

	$new_profile_tab_id = $db->LastInsertId();

	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Queue Job'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.queue.job",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"queue_id",
					"worker_id",
					"status_id",
					"singleton_key",
					"count_total",
					"count_done",
					"count_failed",
					"created",
					"updated",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(),
		1,
		4,
		$db->qstr('sidebar'),
		$db->qstr('')
	));

	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Monitor'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.queue.job.monitor'),
		$db->qstr('{}'),
		time(),
		1,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));

	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.queue.job",
			"context_id" => "{{record_id}}",
			"height" => "",
		])),
		time(),
		2,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Finish up

return TRUE;
