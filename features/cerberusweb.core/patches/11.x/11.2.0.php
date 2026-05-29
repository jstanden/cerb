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

// Work-unit count per message; bulk producers (bulk update, reindex, export) bundle
// many records per message, so `syncProgress` sums cardinality to display records,
// not batches. DEFAULT 1 keeps legacy/single-op messages correct without backfill.
if(!array_key_exists('cardinality', $columns)) {
	$changes[] = "ADD COLUMN cardinality MEDIUMINT UNSIGNED NOT NULL DEFAULT 1";
}

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
// Enable the legacy behaviors plugin only if there are non-disabled behavior records

if(
	$revision < 1504  // 11.2
	&& array_key_exists('trigger_event', $tables)
	&& $db->GetOneMaster("SELECT COUNT(*) FROM trigger_event WHERE is_disabled = 0")
) {
	$plugin_behaviors = DevblocksPlatform::getPlugin('cerb.behaviors.legacy');
	$plugin_behaviors->setEnabled(true);
}

// ===========================================================================
// Enable the new background cronjob

$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'enabled', '1')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'lastrun', '0')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'locked', '0')");
$db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'concurrency', '2')");

// Remove retired cron jobs
$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cron.metrics'");
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
		`stem` varchar(128) CHARACTER SET ascii NOT NULL DEFAULT '',
		PRIMARY KEY (token_hash),
		INDEX `stem` (stem(4)),
		INDEX `token` (token(4))
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['search_index_tokens'] = 'search_index_tokens';
	
} else {
	list($columns, ) = $db->metaTable('search_index_tokens');
	
	$changes = [];
	
	if(array_key_exists('stem', $columns) && $columns['stem']['collation'] != 'ascii_general_ci') {
		$changes[] = "MODIFY COLUMN stem VARCHAR(128) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''";
	}
	
	if($changes) {
		$db->ExecuteMaster("ALTER TABLE search_index_tokens " . implode(', ', $changes));
	}
}

// ===========================================================================
// Convert MySQL Fulltext indexes

if($revision < 1506) {
	$search_queue_id = intval($db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.search.index'"));
	
	// Contacts
	if (!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('contact'), $db->qstr('text')))) {
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
	
	if (array_key_exists('fulltext_contact', $tables)) {
		$db->ExecuteMaster('DROP TABLE fulltext_contact');
		unset($tables['fulltext_contact']);
		$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.contact'");
	}
}

// Workers

if($revision < 1506) {
	if (!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('worker'), $db->qstr('text')))) {
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
	
	if (array_key_exists('fulltext_worker', $tables)) {
		$db->ExecuteMaster('DROP TABLE fulltext_worker');
		unset($tables['fulltext_worker']);
		$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.worker'");
	}
}

// Automations

if($revision < 1506) {
	if (!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('automation'), $db->qstr('script')))) {
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
	
	if (array_key_exists('fulltext_automation', $tables)) {
		$db->ExecuteMaster('DROP TABLE fulltext_automation');
		unset($tables['fulltext_automation']);
		$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.automation'");
	}
}

// Email Addresses

if($revision < 1506) {
	if (!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('address'), $db->qstr('text')))) {
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
	
	if (array_key_exists('fulltext_address', $tables)) {
		$db->ExecuteMaster('DROP TABLE fulltext_address');
		unset($tables['fulltext_address']);
		$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.address'");
	}
}

// Organizations

if($revision < 1506) {
	if (!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('org'), $db->qstr('text')))) {
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
	
	if (array_key_exists('fulltext_org', $tables)) {
		$db->ExecuteMaster('DROP TABLE fulltext_org');
		unset($tables['fulltext_org']);
		$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.org'");
	}
}

// Jira Issues

if(array_key_exists('fulltext_jira_issue', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_jira_issue');
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'jira.search.schema.jira_issue'");
}

// Snippets

if($revision < 1506) {
	if (!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $db->qstr('snippet'), $db->qstr('text')))) {
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
	
	if (array_key_exists('fulltext_snippet', $tables)) {
		$db->ExecuteMaster('DROP TABLE fulltext_snippet');
		unset($tables['fulltext_snippet']);
		$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.snippet'");
	}
}

// Message Headers

if($revision < 1506) {
	$message_header_indexes = [
		['name' => 'Message Header From', 'uri' => 'messages.header.from', 'filter' => 'header.from', 'content' => "{{headers['from']}}"],
		['name' => 'Message Header To', 'uri' => 'messages.header.to', 'filter' => 'header.to', 'content' => "{{headers['to']}}"],
		['name' => 'Message Header Cc', 'uri' => 'messages.header.cc', 'filter' => 'header.cc', 'content' => "{{headers['cc']}}"],
		['name' => 'Message Header Delivered-To', 'uri' => 'messages.header.deliveredto', 'filter' => 'header.deliveredTo', 'content' => "{{headers['delivered-to']}} {{headers['envelope-to']}} {{headers['x-envelope-to']}} {{headers['original-to']}}"],
		['name' => 'Message Header Cerb-Mailbox', 'uri' => 'messages.header.cerbmailbox', 'filter' => 'header.cerbMailbox', 'content' => "{{headers['x-cerberus-mailbox']}}"],
		['name' => 'Message Header Forwarded-To', 'uri' => 'messages.header.forwardedto', 'filter' => 'header.forwardedTo', 'content' => "{{headers['x-forwarded-to']}}"],
		['name' => 'Message Header X-Mailer', 'uri' => 'messages.header.mailer', 'filter' => 'header.mailer', 'content' => "{{headers['x-mailer']}}"],
	];
	
	// Reuse counts for all headers
	$message_record_count = intval($db->GetOneMaster("SELECT COUNT(id) FROM message"));
	
	// Checkpoints for incremental indexing
	$message_max = $db->GetRowMaster("SELECT id, created_date FROM message ORDER BY created_date DESC, id DESC LIMIT 1");
	$message_last_indexed_at = intval($message_max['created_date'] ?? 0);
	$message_last_indexed_id = intval($message_max['id'] ?? 0);
	
	$db->ExecuteMaster('SET SESSION group_concat_max_len = 1048576');
	
	// For each header
	foreach ($message_header_indexes as $header_index) {
		// Idempotent: skip if this filter already exists for messages (run once)
		if ($db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %s",
			$db->qstr('message'), $db->qstr($header_index['filter']))))
			continue;
		
		$db->ExecuteMaster(sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
			$db->qstr($header_index['name']),
			$db->qstr($header_index['uri']),
			$db->qstr('message'),
			$db->qstr($header_index['filter']),
			$db->qstr('cerb.search.index.fulltext'),
			$db->qstr(json_encode(['record_query' => '', 'content' => $header_index['content']])),
			50,
			time(),
			time(),
		));
		
		if (!($search_index_id = $db->LastInsertId()))
			continue;
		
		// Checkpoint the current state to prevent incremental indexing of historical content
		$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
			$db->qstr(sprintf('search_index_%d.last_indexed_at', $search_index_id)),
			$message_last_indexed_at,
		));
		$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
			$db->qstr(sprintf('search_index_%d.last_indexed_id', $search_index_id)),
			$message_last_indexed_id,
		));
		
		// Queue a full reindex job. Skip if the queue is missing or empty
		if (!$message_record_count)
			continue;
		
		// Create a reindex queue job
		$db->ExecuteMaster(sprintf("INSERT INTO queue_job (name, singleton_key, queue_id, worker_id, metadata, status_id, count_total, count_available, created_at, updated_at) " .
			"VALUES (%s, %s, %d, 0, %s, 0 /* RUNNING */, %d, %d, %d, %d)",
			$db->qstr('Reindex ' . $header_index['name']),
			$db->qstr(sprintf('search_index:%d:reindex', $search_index_id)),
			$search_queue_id,
			$db->qstr(json_encode(['search_index_id' => $search_index_id, 'record_type' => 'message'])),
			$message_record_count,
			$message_record_count,
			time(),
			time(),
		));
		
		$job_id = $db->LastInsertId();
		
		// One queue_message per 100-record batch, mirroring _reindexCreateJob()
		$db->ExecuteMaster(sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, status_at, message, cardinality) " .
			"SELECT UUID_TO_BIN(UUID()) AS uuid, " .
			"%d AS queue_id, " .
			"%d AS job_id, " .
			"0 /* available */ AS status_id, " .
			"UNIX_TIMESTAMP() AS status_at, " .
			"CONCAT('{\"index_id\":',%d,',\"ids\":[',GROUP_CONCAT(id ORDER BY id),']}') AS message, " .
			"COUNT(id) AS cardinality " .
			"FROM (SELECT id, CEIL(ROW_NUMBER() OVER (ORDER BY id) / 100) AS batch FROM message) AS batched " .
			"GROUP BY batch",
			$search_queue_id,
			$job_id,
			$search_index_id,
		));
	}
}

// Drop the legacy InnoDB FT table
if(array_key_exists('fulltext_message_header', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_message_header');
	unset($tables['fulltext_message_header']);
}

// Clear old indexing progress
$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerberusweb.search.schema.message_headers'");

// Message Content (reuses $message_record_count / $message_max from the headers block above)

if($revision < 1506) {
	if (!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %s",
			$db->qstr('message'),
			$db->qstr('content'))
	)) {
		// Replicates the legacy Search_MessageContent composition: reply quotes
		// stripped from the body, plus sender/subject/mask/org metadata.
		$message_content_template = implode("\n", [
			"{{content|strip_lines('>')|strip_pem_blocks()|strip_data_uris()|strip_url_querystrings()}}",
			"{{sender__label}}",
			"{{ticket_subject}}",
			"{{ticket_mask}}",
			"{{ticket_org__label}}",
		]);
		
		$db->ExecuteMaster(sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
			$db->qstr('Messages'),
			$db->qstr('messages'),
			$db->qstr('message'),
			$db->qstr('content'),
			$db->qstr('cerb.search.index.fulltext'),
			$db->qstr(json_encode(['record_query' => '', 'content' => $message_content_template])),
			0,
			time(),
			time(),
		));
		
		$search_index_id = $db->LastInsertId();
		
		// Checkpoint incremental search indexing
		$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
			$db->qstr(sprintf('search_index_%d.last_indexed_at', $search_index_id)),
			$message_last_indexed_at,
		));
		$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
			$db->qstr(sprintf('search_index_%d.last_indexed_id', $search_index_id)),
			$message_last_indexed_id,
		));
		
		if ($search_queue_id && $message_record_count) {
			$db->ExecuteMaster('SET SESSION group_concat_max_len = 1048576');
			
			$db->ExecuteMaster(sprintf("INSERT INTO queue_job (name, singleton_key, queue_id, worker_id, metadata, status_id, count_total, count_available, created_at, updated_at) " .
				"VALUES (%s, %s, %d, 0, %s, 0 /* RUNNING */, %d, %d, %d, %d)",
				$db->qstr('Reindex Message Content'),
				$db->qstr(sprintf('search_index:%d:reindex', $search_index_id)),
				$search_queue_id,
				$db->qstr(json_encode(['search_index_id' => $search_index_id, 'record_type' => 'message'])),
				$message_record_count,
				$message_record_count,
				time(),
				time(),
			));
			
			$job_id = $db->LastInsertId();
			
			$db->ExecuteMaster(sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, status_at, message, cardinality) " .
				"SELECT UUID_TO_BIN(UUID()) AS uuid, " .
				"%d AS queue_id, " .
				"%d AS job_id, " .
				"0 /* available */ AS status_id, " .
				"UNIX_TIMESTAMP() AS status_at, " .
				"CONCAT('{\"index_id\":',%d,',\"ids\":[',GROUP_CONCAT(id ORDER BY id),']}') AS message, " .
				"COUNT(id) AS cardinality " .
				"FROM (SELECT id, CEIL(ROW_NUMBER() OVER (ORDER BY id) / 100) AS batch FROM message) AS batched " .
				"GROUP BY batch",
				$search_queue_id,
				$job_id,
				$search_index_id,
			));
		}
	}
}

// Drop the legacy InnoDB FT table
if(array_key_exists('fulltext_message_content', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_message_content');
	unset($tables['fulltext_message_content']);
}

// Clear old indexing progress
$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerberusweb.search.schema.message_content'");

// Comments

if($revision < 1506) {
	if (!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %s",
			$db->qstr('comment'),
			$db->qstr('text'))
	)) {
		$db->ExecuteMaster(sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
			$db->qstr('Comments'),
			$db->qstr('comments'),
			$db->qstr('comment'),
			$db->qstr('text'),
			$db->qstr('cerb.search.index.fulltext'),
			$db->qstr(json_encode(['record_query' => '', 'content' => '{{comment|strip_data_uris()|strip_pem_blocks()|strip_url_querystrings()}}'])),
			0,
			time(),
			time(),
		));
		
		$search_index_id = $db->LastInsertId();
		
		$comment_record_count = intval($db->GetOneMaster("SELECT COUNT(id) FROM comment"));
		$comment_max = $db->GetRowMaster("SELECT id, created FROM comment ORDER BY created DESC, id DESC LIMIT 1");
		
		// Checkpoint incremental search indexing
		$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
			$db->qstr(sprintf('search_index_%d.last_indexed_at', $search_index_id)),
			intval($comment_max['created'] ?? 0),
		));
		$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
			$db->qstr(sprintf('search_index_%d.last_indexed_id', $search_index_id)),
			intval($comment_max['id'] ?? 0),
		));
		
		if ($search_queue_id && $comment_record_count) {
			$db->ExecuteMaster('SET SESSION group_concat_max_len = 1048576');
			
			$db->ExecuteMaster(sprintf("INSERT INTO queue_job (name, singleton_key, queue_id, worker_id, metadata, status_id, count_total, count_available, created_at, updated_at) " .
				"VALUES (%s, %s, %d, 0, %s, 0 /* RUNNING */, %d, %d, %d, %d)",
				$db->qstr('Reindex Comments'),
				$db->qstr(sprintf('search_index:%d:reindex', $search_index_id)),
				$search_queue_id,
				$db->qstr(json_encode(['search_index_id' => $search_index_id, 'record_type' => 'comment'])),
				$comment_record_count,
				$comment_record_count,
				time(),
				time(),
			));
			
			$job_id = $db->LastInsertId();
			
			$db->ExecuteMaster(sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, status_at, message, cardinality) " .
				"SELECT UUID_TO_BIN(UUID()) AS uuid, " .
				"%d AS queue_id, " .
				"%d AS job_id, " .
				"0 /* available */ AS status_id, " .
				"UNIX_TIMESTAMP() AS status_at, " .
				"CONCAT('{\"index_id\":',%d,',\"ids\":[',GROUP_CONCAT(id ORDER BY id),']}') AS message, " .
				"COUNT(id) AS cardinality " .
				"FROM (SELECT id, CEIL(ROW_NUMBER() OVER (ORDER BY id) / 100) AS batch FROM comment) AS batched " .
				"GROUP BY batch",
				$search_queue_id,
				$job_id,
				$search_index_id,
			));
		}
	}
}

// Drop the old InnoDB FT table
if(array_key_exists('fulltext_comment_content', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_comment_content');
	unset($tables['fulltext_comment_content']);
}

// Drop the old indexing progress
$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerberusweb.search.schema.comment_content'");

// ===========================================================================
// Knowledgebase Articles (cerberusweb.kb plugin)

if($revision < 1506 && DevblocksPlatform::isPluginEnabled('cerberusweb.kb')) {
	if(!$db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %s",
		$db->qstr('kb_article'),
		$db->qstr('content'))
	)) {
		$db->ExecuteMaster(sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
			$db->qstr('Knowledgebase Articles'),
			$db->qstr('kb.articles'),
			$db->qstr('kb_article'),                       // record_type = context alias
			$db->qstr('content'),                          // record_filter (auto-injects `content:`)
			$db->qstr('cerb.search.index.fulltext'),
			$db->qstr(json_encode(['record_query' => '', 'content' => "{{title}}\n\n{{content|striptags}}"])),
			0,
			time(),
			time(),
		));

		$search_index_id = $db->LastInsertId();

		$kb_record_count = intval($db->GetOneMaster("SELECT COUNT(id) FROM kb_article"));
		$kb_max = $db->GetRowMaster("SELECT id, updated FROM kb_article ORDER BY updated DESC, id DESC LIMIT 1");

		// Checkpoint incremental search indexing
		$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
			$db->qstr(sprintf('search_index_%d.last_indexed_at', $search_index_id)),
			intval($kb_max['updated'] ?? 0),
		));
		$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
			$db->qstr(sprintf('search_index_%d.last_indexed_id', $search_index_id)),
			intval($kb_max['id'] ?? 0),
		));

		if($search_queue_id && $kb_record_count) {
			$db->ExecuteMaster('SET SESSION group_concat_max_len = 1048576');

			$db->ExecuteMaster(sprintf("INSERT INTO queue_job (name, singleton_key, queue_id, worker_id, metadata, status_id, count_total, count_available, created_at, updated_at) " .
				"VALUES (%s, %s, %d, 0, %s, 0 /* RUNNING */, %d, %d, %d, %d)",
				$db->qstr('Reindex Knowledgebase Articles'),
				$db->qstr(sprintf('search_index:%d:reindex', $search_index_id)),
				$search_queue_id,
				$db->qstr(json_encode(['search_index_id' => $search_index_id, 'record_type' => 'kb_article'])),
				$kb_record_count,
				$kb_record_count,
				time(),
				time(),
			));

			$job_id = $db->LastInsertId();

			$db->ExecuteMaster(sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, status_at, message, cardinality) " .
				"SELECT UUID_TO_BIN(UUID()) AS uuid, " .
				"%d AS queue_id, " .
				"%d AS job_id, " .
				"0 /* available */ AS status_id, " .
				"UNIX_TIMESTAMP() AS status_at, " .
				"CONCAT('{\"index_id\":',%d,',\"ids\":[',GROUP_CONCAT(id ORDER BY id),']}') AS message, " .
				"COUNT(id) AS cardinality " .
				"FROM (SELECT id, CEIL(ROW_NUMBER() OVER (ORDER BY id) / 100) AS batch FROM kb_article) AS batched " .
				"GROUP BY batch",
				$search_queue_id,
				$job_id,
				$search_index_id,
			));
		}
	}
}

// Drop the old InnoDB FT table (always, regardless of plugin enabled state)
if(array_key_exists('fulltext_kb_article', $tables)) {
	$db->ExecuteMaster('DROP TABLE fulltext_kb_article');
	unset($tables['fulltext_kb_article']);
}

// Drop the old indexing progress
$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerberusweb.search.schema.kb_article'");

// ===========================================================================
// Convert `custom_field_stringvalue.field_value` to utf8mb4

if(!array_key_exists('custom_field_stringvalue', $tables))
	return FALSE;

list($columns,) = $db->metaTable('custom_field_stringvalue');

if(!array_key_exists('field_value', $columns))
	return FALSE;

$changes = [];

if('ascii_general_ci' != $columns['context']['collation']) {
	$changes[] = "MODIFY COLUMN context varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''";
}

if('utf8mb4_unicode_ci' != $columns['field_value']['collation']) {
	$changes[] = "MODIFY COLUMN field_value varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''";
}

if($changes) {
	$db->ExecuteMaster("ALTER TABLE custom_field_stringvalue " . implode(', ', $changes));
}

// ===========================================================================
// Convert `custom_field_clobvalue.field_value` to utf8mb4

if(!array_key_exists('custom_field_clobvalue', $tables))
	return FALSE;

list($columns,) = $db->metaTable('custom_field_clobvalue');

if(!array_key_exists('field_value', $columns))
	return FALSE;

$changes = [];

if('ascii_general_ci' != $columns['context']['collation']) {
	$changes[] = "MODIFY COLUMN context varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''";
}

if('utf8mb4_unicode_ci' != $columns['field_value']['collation']) {
	$changes[] = 'MODIFY COLUMN field_value MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
}

if($changes) {
	$db->ExecuteMaster("ALTER TABLE custom_field_clobvalue " . implode(', ', $changes));
}

// ===========================================================================
// Convert `mail_queue.name` to utf8mb4

if(!array_key_exists('mail_queue', $tables))
	return FALSE;

list($columns,) = $db->metaTable('mail_queue');

if(!array_key_exists('name', $columns))
	return FALSE;

$changes = [];

if('utf8mb4_unicode_ci' != $columns['name']['collation']) {
	$changes[] = "MODIFY COLUMN `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''";
}

if($changes) {
	$db->ExecuteMaster("ALTER TABLE mail_queue " . implode(', ', $changes));
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

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.queue.job' AND extension_id='cerb.card.widget.queue.job.monitor'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s, %s)",
		$db->qstr('Progress'),
		$db->qstr('cerb.contexts.queue.job'),
		$db->qstr('cerb.card.widget.queue.job.monitor'),
		$db->qstr('{}'),
		time(), time(),
		1, 12,
		$db->qstr('content'),
		$db->qstr("hidden@bool: {{not cerb_record_readable(record__context,record_id,'worker',worker_id)}}")
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.queue.job`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.queue.job'")) {
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
		$db->qstr('Progress'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.queue.job.monitor'),
		$db->qstr('{}'),
		time(),
		1,
		4,
		$db->qstr('content'),
		$db->qstr("hidden@bool: {{not cerb_record_readable(record__context,record_id,'worker',worker_id)}}")
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
// Default `Record Fields` card widget for `cerb.contexts.automation.event`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.automation.event' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.automation.event'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation.event",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension",
					"description",
					"updated",
					"id",
				],
			],
			"search" => [
				"context" => ["cerb.contexts.automation.event.listener"],
				"query" => ["event:{{record_name}}"],
				"label_singular" => ["Listener"],
				"label_plural" => ["Listeners"],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.automation.event`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.automation.event'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.automation.event'),
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
		$db->qstr('Automation Event'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation.event",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension",
					"description",
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
		$db->qstr('Event Listeners'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.worklist'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation.event.listener",
			"query_required" => "event:{{record_name}}",
			"query" => "sort:priority",
			"render_limit" => "25",
			"header_color" => "#6a87db",
			"columns" => [
				"a_name",
				"a_priority",
				"a_workflow_id",
				"a_is_disabled",
				"a_updated_at",
			],
		])),
		time(),
		1,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Default `Record Fields` card widget for `cerb.contexts.automation.event.listener`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.automation.event.listener' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.automation.event.listener'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation.event.listener",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"event_name",
					"workflow_id",
					"priority",
					"is_disabled",
					"created",
					"updated",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.automation.event.listener`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.automation.event.listener'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.automation.event.listener'),
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
		$db->qstr('Automation Event Listener'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation.event.listener",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"event_name",
					"workflow_id",
					"priority",
					"is_disabled",
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
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation.event.listener",
			"context_id" => "{{record_id}}",
			"height" => "",
		])),
		time(),
		1,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Default `Properties` card widget for `cerb.contexts.automation`

// Shift the existing `Statistics` chart widget down so `Properties` can sit on top
$db->ExecuteMaster("UPDATE card_widget SET pos = 2 WHERE record_type = 'cerb.contexts.automation' AND extension_id = 'cerb.card.widget.chart.timeseries' AND name = 'Statistics' AND pos = 3");

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.automation' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.automation'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension_id",
					"description",
					"policy_kata",
					"created",
					"updated",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.automation`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.automation'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.automation'),
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
		$db->qstr('Automation'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension_id",
					"description",
					"policy_kata",
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
		$db->qstr('Policy'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.sheet'),
		$db->qstr(json_encode([
			"data_query" => "type:worklist.records\r\nof:automation\r\nexpand:[policy_kata]\r\nquery:(\r\n  id:{{record_id}}\r\n  limit:1\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
			"cache_secs" => "",
			"placeholder_simulator_kata" => "",
			"sheet_kata" => "layout:\r\n  style: table\r\n  headings@bool: no\r\n  paging@bool: no\r\n\r\ncolumns:\r\n  code/policy_kata:\r\n    params:\r\n      syntax: kata\r\n",
			"toolbar_kata" => "",
		])),
		time(),
		2,
		4,
		$db->qstr('sidebar'),
		$db->qstr('')
	));

	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation",
			"context_id" => "{{record_id}}",
			"height" => "",
		])),
		time(),
		3,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));

	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Script'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.sheet'),
		$db->qstr(json_encode([
			"data_query" => "type:worklist.records\r\nof:automation\r\nexpand:[script]\r\nquery:(\r\n  id:{{record_id}}\r\n  limit:1\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
			"cache_secs" => "",
			"placeholder_simulator_kata" => "",
			"sheet_kata" => "layout:\r\n  style: table\r\n  headings@bool: no\r\n  paging@bool: no\r\n\r\ncolumns:\r\n  code/script:\r\n    params:\r\n      syntax: kata\r\n",
			"toolbar_kata" => "",
		])),
		time(),
		4,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Default `Properties` card widget for `cerb.contexts.automation.timer`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.automation.timer' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.automation.timer'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation.timer",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"is_disabled",
					"is_recurring",
					"recurring_patterns",
					"recurring_timezone",
					"last_ran_at",
					"next_run_at",
					"created",
					"updated",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.automation.timer`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.automation.timer'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.automation.timer'),
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
		$db->qstr('Automation Timer'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation.timer",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"is_disabled",
					"is_recurring",
					"recurring_patterns",
					"recurring_timezone",
					"last_ran_at",
					"next_run_at",
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
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.automation.timer",
			"context_id" => "{{record_id}}",
			"height" => "",
		])),
		time(),
		1,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Default `Properties` card widget for `cerb.contexts.service.token`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.service.token' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.service.token'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.service.token",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"token_hint",
					"scopes",
					"expires_at",
					"last_accessed_at",
					"created",
					"updated",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.service.token`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.service.token'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.service.token'),
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
		$db->qstr('Service Token'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.service.token",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"token_hint",
					"scopes",
					"expires_at",
					"last_accessed_at",
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
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.service.token",
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
// Default `Properties` card widget for `cerberusweb.contexts.gpg_public_key`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerberusweb.contexts.gpg_public_key' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerberusweb.contexts.gpg_public_key'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerberusweb.contexts.gpg_public_key",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"fingerprint",
					"updated",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerberusweb.contexts.gpg_public_key`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.gpg_public_key'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerberusweb.contexts.gpg_public_key'),
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
		$db->qstr('GPG Public Key'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerberusweb.contexts.gpg_public_key",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"fingerprint",
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
		$db->qstr('Public Key'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.sheet'),
		$db->qstr(json_encode([
			"data_query" => "type:worklist.records\r\nof:gpg_public_key\r\nquery:(\r\n  id:{{record_id}}\r\n  limit:1\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
			"cache_secs" => "",
			"placeholder_simulator_kata" => "",
			"sheet_kata" => "layout:\r\n  style: fieldset\r\n  headings@bool: no\r\n  paging@bool: no\r\n\r\ncolumns:\r\n  code/key_text:\r\n    label: Public Key\r\n",
			"toolbar_kata" => "",
		])),
		time(),
		2,
		12,
		$db->qstr('content'),
		$db->qstr('')
	));

	$db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) ".
		"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerberusweb.contexts.gpg_public_key",
			"context_id" => "{{record_id}}",
			"height" => "",
		])),
		time(),
		3,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Default `Properties` card widget for `cerb.contexts.gpg.private.key`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.gpg.private.key' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.gpg.private.key'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.gpg.private.key",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"fingerprint",
					"expires_at",
					"updated_at",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.gpg.private.key`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.gpg.private.key'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.gpg.private.key'),
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
		$db->qstr('GPG Private Key'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.gpg.private.key",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"fingerprint",
					"expires_at",
					"updated_at",
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
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.gpg.private.key",
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
// Default `Properties` card widget for `cerb.contexts.queue`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.queue' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.queue'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.queue",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension_id",
					"created",
					"updated",
					"id",
				],
			],
			"search" => [
				"context" => ["cerb.contexts.queue.job"],
				"query" => ["queue:{{record_name}}"],
				"label_singular" => ["Job"],
				"label_plural" => ["Jobs"],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.queue`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.queue'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.queue'),
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
		$db->qstr('Queue'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.queue",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension_id",
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
		$db->qstr('Jobs'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.worklist'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.queue.job",
			"query_required" => "queue:{{record_name}}",
			"query" => "sort:-created",
			"render_limit" => "25",
			"header_color" => "#6a87db",
			"columns" => [
				"qj_name",
				"qj_status_id",
				"qj_created_at",
			],
		])),
		time(),
		2,
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
			"context" => "cerb.contexts.queue",
			"context_id" => "{{record_id}}",
			"height" => "",
		])),
		time(),
		3,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Default `Properties` card widget for `cerb.contexts.resource`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.resource' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.resource'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.resource",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension_id",
					"description",
					"is_dynamic",
					"storage_size",
					"cache_until",
					"updated",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.resource`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.resource'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.resource'),
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
		$db->qstr('Resource'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.resource",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension_id",
					"description",
					"is_dynamic",
					"storage_size",
					"cache_until",
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
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.resource",
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
// Default `Properties` card widget for `cerb.contexts.search.index`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.search.index' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.search.index'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.search.index",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension_id",
					"record_type",
					"record_filter",
					"priority",
					"uri",
					"created",
					"updated",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Index` card widget for `cerb.contexts.search.index`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.search.index' AND extension_id='cerb.card.widget.search_index'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Index'),
		$db->qstr('cerb.contexts.search.index'),
		$db->qstr('cerb.card.widget.search_index'),
		$db->qstr(json_encode([
			"search_index_id" => "{{record_id}}",
		])),
		time(), time(),
		2, 4,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.search.index`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.search.index'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.search.index'),
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
		$db->qstr('Search Index'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.search.index",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension_id",
					"record_type",
					"record_filter",
					"priority",
					"uri",
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
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.search.index",
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
// Default `Properties` card widget for `cerb.contexts.toolbar`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.toolbar' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.toolbar'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.toolbar",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension",
					"description",
					"created",
					"updated",
					"id",
				],
			],
			"search" => [
				"context" => ["cerb.contexts.toolbar.section"],
				"query" => ["toolbar:{{record_name}}"],
				"label_singular" => ["Section"],
				"label_plural" => ["Sections"],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.toolbar`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.toolbar'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.toolbar'),
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
		$db->qstr('Toolbar'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.toolbar",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"extension",
					"description",
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
		$db->qstr('Sections'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.worklist'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.toolbar.section",
			"query_required" => "toolbar:{{record_name}}",
			"query" => "sort:priority",
			"render_limit" => "25",
			"header_color" => "#6a87db",
			"columns" => [
				"t_name",
				"t_priority",
				"t_workflow_id",
				"t_is_disabled",
				"t_updated_at",
			],
		])),
		time(),
		2,
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
			"context" => "cerb.contexts.toolbar",
			"context_id" => "{{record_id}}",
			"height" => "",
		])),
		time(),
		3,
		4,
		$db->qstr('content'),
		$db->qstr('')
	));
}

// ===========================================================================
// Default `Properties` card widget for `cerb.contexts.toolbar.section`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.toolbar.section' AND extension_id='cerb.card.widget.fields'")) {
	$db->ExecuteMaster(sprintf(
		"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) ".
		"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
		$db->qstr('Properties'),
		$db->qstr('cerb.contexts.toolbar.section'),
		$db->qstr('cerb.card.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.toolbar.section",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"toolbar_name",
					"priority",
					"workflow_id",
					"is_disabled",
					"created",
					"updated",
					"id",
				],
			],
			"toolbar_kata" => "",
		])),
		time(), time(),
		1, 12,
		$db->qstr('content')
	));
}

// ===========================================================================
// Default `Overview` profile tab for `cerb.contexts.toolbar.section`

if($revision < 1506 && !$db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.toolbar.section'")) {
	$db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) ".
		"VALUES (%s, %s, %s, %d, %s, %d)",
		$db->qstr('Overview'),
		$db->qstr('cerb.contexts.toolbar.section'),
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
		$db->qstr('Toolbar Section'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.fields'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.toolbar.section",
			"context_id" => "{{record_id}}",
			"properties" => [
				[
					"name",
					"toolbar_name",
					"priority",
					"workflow_id",
					"is_disabled",
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
		$db->qstr('Discussion'),
		$new_profile_tab_id,
		$db->qstr('cerb.profile.tab.widget.comments'),
		$db->qstr(json_encode([
			"context" => "cerb.contexts.toolbar.section",
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
// Add `retention_days` to `metric`

list($columns, ) = $db->metaTable('metric');

$changes = [];

if(!array_key_exists('retention_days', $columns)) {
	$changes[] = "ADD COLUMN retention_days INT UNSIGNED NOT NULL DEFAULT 0";
}

if($changes) {
	$db->ExecuteMaster("ALTER TABLE metric ".
		implode(', ', $changes)
	);
}

// ===========================================================================
// Queue Job Log

if(!array_key_exists('queue_job_log', $tables)) {
	$sql = sprintf("
		CREATE TABLE `queue_job_log` (
		`id` bigint unsigned NOT NULL AUTO_INCREMENT,
		`job_id` bigint unsigned NOT NULL,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`level` tinyint unsigned NOT NULL DEFAULT 0,
		`message` varchar(1024) NOT NULL DEFAULT '',
		`metadata` mediumtext,
		PRIMARY KEY (id),
		INDEX `job_id` (job_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());

	$tables['queue_job_log'] = 'queue_job_log';
}

// ===========================================================================
// Clear the plugin worklist models

$db->ExecuteMaster("DELETE FROM worker_view_model WHERE view_id IN ('cerb5_plugins','plugins_installed')");
$db->ExecuteMaster("DELETE FROM worker_view_model WHERE class_name IN ('View_PluginLibrary')");

// ===========================================================================
// Finish up

return TRUE;
