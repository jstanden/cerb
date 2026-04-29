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
// Enable the new background cronjob

$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'enabled', '1')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'duration', '1')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'term', 'm')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'lastrun', '0')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'locked', '0')");

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
// Finish up

return TRUE;
