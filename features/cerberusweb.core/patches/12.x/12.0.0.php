<?php /** @noinspection PhpUnusedPrivateMethodInspection */

class CerbPatch_Core_v12_0_0 {
	private ?_DevblocksDatabaseManager $_db;
	private ?_DevblocksLogManager $_logger;
	private array $_tables;
	private int $_revision;
	
	public function __construct() {
		$this->_db = DevblocksPlatform::services()->database();
		$this->_logger = DevblocksPlatform::services()->log();
		$this->_tables = $this->_db->metaTables();
		$this->_revision = (int) $this->_db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");
	}
	
	function run() : bool {
		$methods = new ReflectionClass($this)->getMethods(ReflectionMethod::IS_PRIVATE);
		
		// Sort by line number for definition order
		usort($methods, fn(ReflectionMethod $a, ReflectionMethod $b) => $a->getStartLine() <=> $b->getStartLine());
		
		foreach($methods as $method) {
			$name = $method->getName();
			
			if(!str_starts_with($name, 'patch'))
				continue;
			
			if($method->isStatic() || $method->getNumberOfRequiredParameters())
				continue;
			
			try {
				$this->_logger->info(sprintf("Patching %s...", substr($name, 5)));
				
				$this->$name();
				
			} catch(Throwable) {
				// If anything fails the patch fails
				return false;
			}
		}
		
		return true;
	}
	
	private function patchUpdateQueueSchema() : void {
		// ===========================================================================
		// Add extension_id, concurrency, priority to `queue`
		
		list($columns, ) = $this->_db->metaTable('queue');
		
		$changes = [];
		
		if(!array_key_exists('extension_id', $columns)) {
			$changes[] = "ADD COLUMN extension_id VARCHAR(255) NOT NULL DEFAULT ''";
		}
		
		if(!array_key_exists('extension_params_json', $columns)) {
			$changes[] = "ADD COLUMN extension_params_json TEXT";
		}

		// Per-queue retry policy: `retry_max` (0 = never retry) over `retry_window_secs`.
		if(!array_key_exists('retry_max', $columns)) {
			$changes[] = "ADD COLUMN retry_max TINYINT UNSIGNED NOT NULL DEFAULT 0";
		}
		
		if(!array_key_exists('retry_window_secs', $columns)) {
			$changes[] = "ADD COLUMN retry_window_secs INT UNSIGNED NOT NULL DEFAULT 86400";
		}

		// In-flight messages claimed longer than `claim_window_secs` are reaped as failures (0 = never reap)
		if(!array_key_exists('claim_window_secs', $columns)) {
			$changes[] = "ADD COLUMN claim_window_secs INT UNSIGNED NOT NULL DEFAULT 3600";
		}
		
		if($changes) {
			$this->_db->ExecuteMaster("ALTER TABLE queue ".
				implode(', ', $changes)
			);
			
			// Default the queue extension type
			$this->_db->ExecuteMaster("UPDATE queue SET extension_id = 'cerb.queue.consumer.manual' WHERE extension_id = ''");
		}
	}
	
	private function patchUpdateQueueMessageSchema() : void {
		// ===========================================================================
		// Add `job_id` to `queue_message`
		
		list($columns, ) = $this->_db->metaTable('queue_message');
		
		$changes = [];
		
		if(array_key_exists('namespace', $columns)) {
			$changes[] = "DROP COLUMN namespace";
		}
		
		if(!array_key_exists('job_id', $columns)) {
			$changes[] = "ADD COLUMN job_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER queue_id";
			$changes[] = "DROP INDEX queue_claimed";
			$changes[] = "ADD INDEX queue_claimed (queue_id, status_id, job_id, claim_id)";
		}
		
		// `consumer_id` becomes `claim_id`; CHANGE COLUMN renames it inside an existing `queue_claimed` index too
		if(array_key_exists('consumer_id', $columns) && !array_key_exists('claim_id', $columns)) {
			$changes[] = "CHANGE COLUMN consumer_id claim_id BINARY(16) DEFAULT NULL";
		}
		
		// When the claim was taken, the reaper compares this against the queue's `claim_window_secs`
		$is_adding_claimed_at = !array_key_exists('claimed_at', $columns);
		if($is_adding_claimed_at) {
			$changes[] = "ADD COLUMN claimed_at INT UNSIGNED NOT NULL DEFAULT 0";
		}
		
		// Promote `message` from TEXT (~64KB) to MEDIUMTEXT (~16MB) so wide record snapshots fit
		if(array_key_exists('message', $columns) && 'mediumtext' != $columns['message']['type']) {
			$changes[] = "MODIFY COLUMN message MEDIUMTEXT";
		}
		
		// Work-unit count per message; bulk producers (bulk update, reindex, export) bundle
		// many records per message, so the live count helpers sum cardinality to display
		// records, not batches. DEFAULT 1 keeps legacy/single-op messages correct without backfill.
		if(!array_key_exists('cardinality', $columns)) {
			$changes[] = "ADD COLUMN cardinality MEDIUMINT UNSIGNED NOT NULL DEFAULT 1";
		}
		
		// Split `status_at` into `created_at` and `processed_at`
		if(array_key_exists('status_at', $columns) && !array_key_exists('created_at', $columns)) {
			$changes[] = "CHANGE status_at created_at INT UNSIGNED NOT NULL DEFAULT 0";
		}
		
		if(!array_key_exists('processed_at', $columns)) {
			$changes[] = "ADD COLUMN processed_at INT UNSIGNED NOT NULL DEFAULT 0";
		}
		
		// Attempt counter on each message; backoff reuses `available_at`, and a message only
		// reaches terminal FAILED once `retry_count` hits the queue's `retry_max`.
		if(!array_key_exists('retry_count', $columns)) {
			$changes[] = "ADD COLUMN retry_count TINYINT UNSIGNED NOT NULL DEFAULT 0";
		}
		
		if($changes) {
			$this->_db->ExecuteMaster("ALTER TABLE queue_message ".
				implode(', ', $changes)
			);
		}
		
		// Backfill messages in-flight during the upgrade so they enter the reap window
		// instead of staying claimed-at-0 forever
		if($is_adding_claimed_at)
			$this->_db->ExecuteMaster("UPDATE queue_message SET claimed_at = UNIX_TIMESTAMP() WHERE status_id = 1");
	}
	
	private function patchCreateQueueJobSchema() : void {
		if(!array_key_exists('queue_job', $this->_tables)) {
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
				`created_at` int unsigned NOT NULL DEFAULT 0,
				`updated_at` int unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY (id),
				INDEX `queue_status` (status_id, queue_id),
				INDEX `worker_id` (worker_id)
				) ENGINE=%s
			", APP_DB_ENGINE);
			$this->_db->ExecuteMaster($sql) or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());
		
			$this->_tables['queue_job'] = 'queue_job';
		}
		
		// ===========================================================================
		// Queue Job Chunk
		
		if(!array_key_exists('queue_job_chunk', $this->_tables)) {
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
			$this->_db->ExecuteMaster($sql) or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());
		
			$this->_tables['queue_job_chunk'] = 'queue_job_chunk';
		}
	}
	
	private function patchSyncQueueRecords() : void {
		$this->_db->ExecuteWriter("UPDATE queue SET extension_id = 'cerb.queue.consumer.internal' WHERE name = 'cerb.metrics.publish'");
		$this->_db->ExecuteWriter("UPDATE queue SET extension_id = 'cerb.queue.consumer.internal' WHERE name = 'cerb.update.migrations'");
		
		if(!$this->_db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.search.index'"))
			$this->_db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.search.index', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");
		
		if(!$this->_db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.records.changed'"))
			$this->_db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.records.changed', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");
		
		if(!$this->_db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.records.import'"))
			$this->_db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.records.import', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");
		
		if(!$this->_db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.records.export'"))
			$this->_db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.records.export', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");
		
		if(!$this->_db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.records.bulk_update'"))
			$this->_db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.records.bulk_update', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");
		
		if(!$this->_db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.storage.migrations'"))
			$this->_db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.storage.migrations', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");
		
		// One agent turn per message; the LLM provider round-trip runs here (off the interaction request) so a >30s
		// call can't blow the FPM terminate. Drained by the background queue and, when watched, by the client's poll.
		if(!$this->_db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.llm.agent.requests'"))
			$this->_db->ExecuteWriter("INSERT IGNORE INTO queue (name, created_at, updated_at, extension_id) VALUES ('cerb.llm.agent.requests', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'cerb.queue.consumer.internal')");
		
		if($this->_revision < 1507) {
			// storage.migrations opts into retries (8 attempts spread across 24h), so a transient
			// backend outage self-heals instead of churning forever.
			$this->_db->ExecuteWriter("UPDATE queue SET retry_max=8, retry_window_secs=86400 WHERE name = 'cerb.storage.migrations'");
		}
		
		if($this->_revision < 1540) {
			// The llm.agent turn queue opts into a few retries over a SHORT window (the interaction's gate is waiting on
			// it), so a TRANSIENT provider failure — network/timeout, 429 rate-limit, 503/5xx — self-heals instead of
			// surfacing on the first blip. Non-transient errors (401/400/malformed) are forced terminal in processQueue
			// (RETRY_COUNT_TERMINAL) so they don't burn the budget.
			$this->_db->ExecuteWriter("UPDATE queue SET retry_max=4, retry_window_secs=120 WHERE name = 'cerb.llm.agent.requests'");
		}

		if($this->_revision < 1549) {
			// ...and now opts back OUT. A blind backend retry is the wrong layer for an LLM turn: the queue can't tell a
			// CHEAP failure (429/529 — nothing was generated) from an EXPENSIVE one (our own timeout, where the provider
			// generated a full turn we hung up on and still paid for). Measured live: the same turn produced 19,881
			// output tokens on one attempt and ran to the 32000 cap at 433s on another, so a retry isn't re-running
			// deterministic work — it's a fresh dice roll at full cost, with nobody watching. Retry belongs to the
			// INTERACTION, which knows whether anyone is there and can hand a timeout back to the worker to rephrase
			// (see PLANS/PLAN-llm-next-turn.md). Scoped to rows still at the shipped 4 so a deliberate admin
			// setting — including 0 — is left alone.
			$this->_db->ExecuteWriter("UPDATE queue SET retry_max=0 WHERE name = 'cerb.llm.agent.requests' AND retry_max = 4");
		}
	}
	
	private function patchMigrateStorageQueueDelete() : void {
		// Replace `devblocks_storage_queue_delete` table with `cerb.storage.migrations` queue
		
		if(array_key_exists('devblocks_storage_queue_delete', $this->_tables)) {
			$this->_db->ExecuteWriter("SET SESSION group_concat_max_len = 1000000");
		
			$this->_db->ExecuteWriter(
				"INSERT INTO queue_message (uuid, queue_id, job_id, status_id, created_at, message, cardinality) ".
				"SELECT UUID_TO_BIN(UUID()), (SELECT id FROM queue WHERE name='cerb.storage.migrations'), 0, 0, UNIX_TIMESTAMP(), ".
				"CONCAT('{\"action\":\"delete\",\"ns\":', JSON_QUOTE(storage_namespace), ',\"ext\":', JSON_QUOTE(storage_extension), ".
				"',\"profile\":', storage_profile_id, ',\"keys\":[', GROUP_CONCAT(JSON_QUOTE(storage_key)), ']}'), COUNT(*) ".
				"FROM (SELECT storage_namespace, storage_extension, storage_profile_id, storage_key, ".
				"CEIL(ROW_NUMBER() OVER (PARTITION BY storage_namespace, storage_extension, storage_profile_id ORDER BY storage_key) / 1000) AS batch ".
				"FROM devblocks_storage_queue_delete) AS batched ".
				"GROUP BY storage_namespace, storage_extension, storage_profile_id, batch"
			);
		
			$this->_db->ExecuteWriter("DROP TABLE devblocks_storage_queue_delete");
			unset($this->_tables['devblocks_storage_queue_delete']);
		}
		
		if($this->_revision < 1506)
			$this->_db->ExecuteWriter("DELETE FROM queue WHERE name = 'cerb.update.migrations'");
	}
	
	private function patchContextBulkUpdate() : void {
		// ===========================================================================
		// Drop the legacy `context_bulk_update` table; replaced by `cerb.records.bulk_update` queue jobs
		
		if (array_key_exists('context_bulk_update', $this->_tables)) {
			$this->_db->ExecuteMaster("DROP TABLE context_bulk_update");
			unset($this->_tables['context_bulk_update']);
		}
	}
	
	private function patchLegacyBehaviorsPlugin() : void {
		// ===========================================================================
		// Enable the legacy behaviors plugin only if there are non-disabled behavior records
		
		if (
			$this->_revision < 1504  // 11.2
			&& array_key_exists('trigger_event', $this->_tables)
			&& $this->_db->GetOneMaster("SELECT COUNT(*) FROM trigger_event WHERE is_disabled = 0")
		) {
			$plugin_behaviors = DevblocksPlatform::getPlugin('cerb.behaviors.legacy');
			$plugin_behaviors->setEnabled(true);
		}
	}
	
	private function patchCronBackgroundQueue() : void {
		// ===========================================================================
		// Enable the new background cronjob
		
		$this->_db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'enabled', '1')");
		$this->_db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'lastrun', '0')");
		$this->_db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'locked', '0')");
		$this->_db->ExecuteMaster("REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.background_queue', 'concurrency', '2')");
		
		// Remove retired cron jobs
		$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cron.metrics'");
		$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cron.migrations'");
	}
	
	private function patchCreateSearchIndex() : void {
		// ===========================================================================
		// Search Index
		
		if (!array_key_exists('search_index', $this->_tables)) {
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
			$this->_db->ExecuteMaster($sql) or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());
			
			$this->_tables['search_index'] = 'search_index';
		}
		
		if (!array_key_exists('search_index_tokens', $this->_tables)) {
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
			$this->_db->ExecuteMaster($sql) or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());
			
			$this->_tables['search_index_tokens'] = 'search_index_tokens';
			
		} else {
			list($columns,) = $this->_db->metaTable('search_index_tokens');
			
			$changes = [];
			
			if (array_key_exists('stem', $columns) && $columns['stem']['collation'] != 'ascii_general_ci') {
				$changes[] = "MODIFY COLUMN stem VARCHAR(128) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''";
			}
			
			if ($changes) {
				$this->_db->ExecuteMaster("ALTER TABLE search_index_tokens " . implode(', ', $changes));
			}
		}
	}
	
	private function patchConvertMysqlFulltext() : void {
		$search_queue_id = intval($this->_db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.search.index'"));
		
		// ===========================================================================
		// Convert MySQL Fulltext indexes
		
		if ($this->_revision < 1506) {
			// Contacts
			if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $this->_db->qstr('contact'), $this->_db->qstr('text')))) {
				$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr('Contacts'),
					$this->_db->qstr('contacts'),
					$this->_db->qstr('contact'),
					$this->_db->qstr('text'),
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => "{{first_name}} {{last_name}}\n{{aliases|join(' ')}}\n{{title}}\n{{email_address}} {{emails|join(' ')}}\n{{org__label}}\n{{username}}"])),
					0,
					time(),
					time(),
				);
				$this->_db->ExecuteMaster($sql);
			}
			
			if (array_key_exists('fulltext_contact', $this->_tables)) {
				$this->_db->ExecuteMaster('DROP TABLE fulltext_contact');
				unset($this->_tables['fulltext_contact']);
				$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.contact'");
			}
		}
		
		// Workers
		
		if ($this->_revision < 1506) {
			if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $this->_db->qstr('worker'), $this->_db->qstr('text')))) {
				$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr('Workers'),
					$this->_db->qstr('workers'),
					$this->_db->qstr('worker'),
					$this->_db->qstr('text'),
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => "{{first_name}} {{last_name}} {{at_mention_name}}\n{{aliases|join(' ')}}\n{{title}}\n{{email_address}}"])),
					0,
					time(),
					time(),
				);
				$this->_db->ExecuteMaster($sql);
			}
			
			if (array_key_exists('fulltext_worker', $this->_tables)) {
				$this->_db->ExecuteMaster('DROP TABLE fulltext_worker');
				unset($this->_tables['fulltext_worker']);
				$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.worker'");
			}
		}
		
		// Automations
		
		if ($this->_revision < 1506) {
			if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $this->_db->qstr('automation'), $this->_db->qstr('script')))) {
				$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr('Automations'),
					$this->_db->qstr('automations'),
					$this->_db->qstr('automation'),
					$this->_db->qstr('script'),
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => "{{name}}\n{{extension_id}}\n\n~~~\n{{script\n  |strip_data_uris()\n  |strip_pem_blocks()\n  |strip_url_querystrings()\n}}\n~~~"])),
					0,
					time(),
					time(),
				);
				$this->_db->ExecuteMaster($sql);
			}
			
			if (array_key_exists('fulltext_automation', $this->_tables)) {
				$this->_db->ExecuteMaster('DROP TABLE fulltext_automation');
				unset($this->_tables['fulltext_automation']);
				$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.automation'");
			}
		}
		
		// Email Addresses
		
		if ($this->_revision < 1506) {
			if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $this->_db->qstr('address'), $this->_db->qstr('text')))) {
				$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr('Email Addresses'),
					$this->_db->qstr('emails'),
					$this->_db->qstr('address'),
					$this->_db->qstr('text'),
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => "{{address}}\n{{contact__label}} {{contact_aliases|join(' ')}}\n{{org__label}} {{org_aliases|join(' ')}}"])),
					0,
					time(),
					time(),
				);
				$this->_db->ExecuteMaster($sql);
			}
			
			if (array_key_exists('fulltext_address', $this->_tables)) {
				$this->_db->ExecuteMaster('DROP TABLE fulltext_address');
				unset($this->_tables['fulltext_address']);
				$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.address'");
			}
		}
		
		// Organizations
		
		if ($this->_revision < 1506) {
			if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $this->_db->qstr('org'), $this->_db->qstr('text')))) {
				$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr('Organizations'),
					$this->_db->qstr('orgs'),
					$this->_db->qstr('org'),
					$this->_db->qstr('text'),
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => "{{name}}\n{{aliases|join(' ')}}\n{{street}} {{city}} {{province}} {{postal}} {{country}}\n{{website}}\n{{email_address}}"])),
					0,
					time(),
					time(),
				);
				$this->_db->ExecuteMaster($sql);
			}
			
			if (array_key_exists('fulltext_org', $this->_tables)) {
				$this->_db->ExecuteMaster('DROP TABLE fulltext_org');
				unset($this->_tables['fulltext_org']);
				$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.org'");
			}
		}
		
		// Jira Issues
		
		if (array_key_exists('fulltext_jira_issue', $this->_tables)) {
			$this->_db->ExecuteMaster('DROP TABLE fulltext_jira_issue');
			$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'jira.search.schema.jira_issue'");
		}
		
		// Snippets
		
		if ($this->_revision < 1506) {
			if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %d", $this->_db->qstr('snippet'), $this->_db->qstr('text')))) {
				$sql = sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr('Snippets Content'),
					$this->_db->qstr('snippets'),
					$this->_db->qstr('snippet'),
					$this->_db->qstr('text'),
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => "{{title}}\n\n{{content}}"])),
					0,
					time(),
					time(),
				);
				$this->_db->ExecuteMaster($sql);
			}
			
			if (array_key_exists('fulltext_snippet', $this->_tables)) {
				$this->_db->ExecuteMaster('DROP TABLE fulltext_snippet');
				unset($this->_tables['fulltext_snippet']);
				$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerb.search.schema.snippet'");
			}
		}
		
		// Message Headers
		
		if ($this->_revision < 1506) {
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
			$message_record_count = intval($this->_db->GetOneMaster("SELECT COUNT(id) FROM message"));
			
			// Checkpoints for incremental indexing
			$message_max = $this->_db->GetRowMaster("SELECT id, created_date FROM message ORDER BY created_date DESC, id DESC LIMIT 1");
			$message_last_indexed_at = intval($message_max['created_date'] ?? 0);
			$message_last_indexed_id = intval($message_max['id'] ?? 0);
			
			$this->_db->ExecuteMaster('SET SESSION group_concat_max_len = 1048576');
			
			// For each header
			foreach ($message_header_indexes as $header_index) {
				// Idempotent: skip if this filter already exists for messages (run once)
				if ($this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %s",
					$this->_db->qstr('message'), $this->_db->qstr($header_index['filter']))))
					continue;
				
				$this->_db->ExecuteMaster(sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr($header_index['name']),
					$this->_db->qstr($header_index['uri']),
					$this->_db->qstr('message'),
					$this->_db->qstr($header_index['filter']),
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => $header_index['content']])),
					50,
					time(),
					time(),
				));
				
				if (!($search_index_id = $this->_db->LastInsertId()))
					continue;
				
				// Checkpoint the current state to prevent incremental indexing of historical content
				$this->_db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
					$this->_db->qstr(sprintf('search_index_%d.last_indexed_at', $search_index_id)),
					$message_last_indexed_at,
				));
				$this->_db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
					$this->_db->qstr(sprintf('search_index_%d.last_indexed_id', $search_index_id)),
					$message_last_indexed_id,
				));
				
				// Queue a full reindex job. Skip if the queue is missing or empty
				if (!$message_record_count)
					continue;
				
				// Create a reindex queue job
				$this->_db->ExecuteMaster(sprintf("INSERT INTO queue_job (name, singleton_key, queue_id, worker_id, metadata, status_id, count_total, created_at, updated_at) " .
					"VALUES (%s, %s, %d, 0, %s, 0 /* RUNNING */, %d, %d, %d)",
					$this->_db->qstr('Reindex ' . $header_index['name']),
					$this->_db->qstr(sprintf('search_index:%d:reindex', $search_index_id)),
					$search_queue_id,
					$this->_db->qstr(json_encode(['search_index_id' => $search_index_id, 'record_type' => 'message'])),
					$message_record_count,
					time(),
					time(),
				));
				
				$job_id = $this->_db->LastInsertId();
				
				// One queue_message per 100-record batch, mirroring _reindexCreateJob()
				$this->_db->ExecuteMaster(sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, created_at, message, cardinality) " .
					"SELECT UUID_TO_BIN(UUID()) AS uuid, " .
					"%d AS queue_id, " .
					"%d AS job_id, " .
					"0 /* available */ AS status_id, " .
					"UNIX_TIMESTAMP() AS created_at, " .
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
		if (array_key_exists('fulltext_message_header', $this->_tables)) {
			$this->_db->ExecuteMaster('DROP TABLE fulltext_message_header');
			unset($this->_tables['fulltext_message_header']);
		}
		
		// Clear old indexing progress
		$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerberusweb.search.schema.message_headers'");
		
		// Message Content (reuses $message_record_count / $message_max from the headers block above)
		
		if ($this->_revision < 1506) {
			if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %s",
					$this->_db->qstr('message'),
					$this->_db->qstr('content'))
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
				
				$this->_db->ExecuteMaster(sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr('Messages'),
					$this->_db->qstr('messages'),
					$this->_db->qstr('message'),
					$this->_db->qstr('content'),
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => $message_content_template])),
					0,
					time(),
					time(),
				));
				
				$search_index_id = $this->_db->LastInsertId();
				
				// Checkpoint incremental search indexing
				$this->_db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
					$this->_db->qstr(sprintf('search_index_%d.last_indexed_at', $search_index_id)),
					$message_last_indexed_at,
				));
				$this->_db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
					$this->_db->qstr(sprintf('search_index_%d.last_indexed_id', $search_index_id)),
					$message_last_indexed_id,
				));
				
				if ($search_queue_id && $message_record_count) {
					$this->_db->ExecuteMaster('SET SESSION group_concat_max_len = 1048576');
					
					$this->_db->ExecuteMaster(sprintf("INSERT INTO queue_job (name, singleton_key, queue_id, worker_id, metadata, status_id, count_total, created_at, updated_at) " .
						"VALUES (%s, %s, %d, 0, %s, 0 /* RUNNING */, %d, %d, %d)",
						$this->_db->qstr('Reindex Message Content'),
						$this->_db->qstr(sprintf('search_index:%d:reindex', $search_index_id)),
						$search_queue_id,
						$this->_db->qstr(json_encode(['search_index_id' => $search_index_id, 'record_type' => 'message'])),
						$message_record_count,
						time(),
						time(),
					));
					
					$job_id = $this->_db->LastInsertId();
					
					$this->_db->ExecuteMaster(sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, created_at, message, cardinality) " .
						"SELECT UUID_TO_BIN(UUID()) AS uuid, " .
						"%d AS queue_id, " .
						"%d AS job_id, " .
						"0 /* available */ AS status_id, " .
						"UNIX_TIMESTAMP() AS created_at, " .
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
		if (array_key_exists('fulltext_message_content', $this->_tables)) {
			$this->_db->ExecuteMaster('DROP TABLE fulltext_message_content');
			unset($this->_tables['fulltext_message_content']);
		}
		
		// Clear old indexing progress
		$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerberusweb.search.schema.message_content'");
		
		// Comments
		
		if ($this->_revision < 1506) {
			if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %s",
					$this->_db->qstr('comment'),
					$this->_db->qstr('text'))
			)) {
				$this->_db->ExecuteMaster(sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr('Comments'),
					$this->_db->qstr('comments'),
					$this->_db->qstr('comment'),
					$this->_db->qstr('text'),
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => '{{comment|strip_data_uris()|strip_pem_blocks()|strip_url_querystrings()}}'])),
					0,
					time(),
					time(),
				));
				
				$search_index_id = $this->_db->LastInsertId();
				
				$comment_record_count = intval($this->_db->GetOneMaster("SELECT COUNT(id) FROM comment"));
				$comment_max = $this->_db->GetRowMaster("SELECT id, created FROM comment ORDER BY created DESC, id DESC LIMIT 1");
				
				// Checkpoint incremental search indexing
				$this->_db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
					$this->_db->qstr(sprintf('search_index_%d.last_indexed_at', $search_index_id)),
					intval($comment_max['created'] ?? 0),
				));
				$this->_db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
					$this->_db->qstr(sprintf('search_index_%d.last_indexed_id', $search_index_id)),
					intval($comment_max['id'] ?? 0),
				));
				
				if ($search_queue_id && $comment_record_count) {
					$this->_db->ExecuteMaster('SET SESSION group_concat_max_len = 1048576');
					
					$this->_db->ExecuteMaster(sprintf("INSERT INTO queue_job (name, singleton_key, queue_id, worker_id, metadata, status_id, count_total, created_at, updated_at) " .
						"VALUES (%s, %s, %d, 0, %s, 0 /* RUNNING */, %d, %d, %d)",
						$this->_db->qstr('Reindex Comments'),
						$this->_db->qstr(sprintf('search_index:%d:reindex', $search_index_id)),
						$search_queue_id,
						$this->_db->qstr(json_encode(['search_index_id' => $search_index_id, 'record_type' => 'comment'])),
						$comment_record_count,
						time(),
						time(),
					));
					
					$job_id = $this->_db->LastInsertId();
					
					$this->_db->ExecuteMaster(sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, created_at, message, cardinality) " .
						"SELECT UUID_TO_BIN(UUID()) AS uuid, " .
						"%d AS queue_id, " .
						"%d AS job_id, " .
						"0 /* available */ AS status_id, " .
						"UNIX_TIMESTAMP() AS created_at, " .
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
		if (array_key_exists('fulltext_comment_content', $this->_tables)) {
			$this->_db->ExecuteMaster('DROP TABLE fulltext_comment_content');
			unset($this->_tables['fulltext_comment_content']);
		}
		
		// Drop the old indexing progress
		$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerberusweb.search.schema.comment_content'");
	}
	
	private function patchKnowledgeBasePlugin() : void {
		$search_queue_id = intval($this->_db->GetOneMaster("SELECT id FROM queue WHERE name = 'cerb.search.index'"));
		
		// ===========================================================================
		// Knowledge base Articles (cerberusweb.kb plugin)
		
		if ($this->_revision < 1506 && DevblocksPlatform::isPluginEnabled('cerberusweb.kb')) {
			if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s AND record_filter = %s",
					$this->_db->qstr('kb_article'),
					$this->_db->qstr('content'))
			)) {
				$this->_db->ExecuteMaster(sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
					"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
					$this->_db->qstr('Knowledgebase Articles'),
					$this->_db->qstr('kb.articles'),
					$this->_db->qstr('kb_article'),                       // record_type = context alias
					$this->_db->qstr('content'),                          // record_filter (auto-injects `content:`)
					$this->_db->qstr('cerb.search.index.fulltext'),
					$this->_db->qstr(json_encode(['record_query' => '', 'content' => "{{title}}\n\n{{content|striptags}}"])),
					0,
					time(),
					time(),
				));
				
				$search_index_id = $this->_db->LastInsertId();
				
				$kb_record_count = intval($this->_db->GetOneMaster("SELECT COUNT(id) FROM kb_article"));
				$kb_max = $this->_db->GetRowMaster("SELECT id, updated FROM kb_article ORDER BY updated DESC, id DESC LIMIT 1");
				
				// Checkpoint incremental search indexing
				$this->_db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
					$this->_db->qstr(sprintf('search_index_%d.last_indexed_at', $search_index_id)),
					intval($kb_max['updated'] ?? 0),
				));
				$this->_db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, 'number', %d, 0)",
					$this->_db->qstr(sprintf('search_index_%d.last_indexed_id', $search_index_id)),
					intval($kb_max['id'] ?? 0),
				));
				
				if ($search_queue_id && $kb_record_count) {
					$this->_db->ExecuteMaster('SET SESSION group_concat_max_len = 1048576');
					
					$this->_db->ExecuteMaster(sprintf("INSERT INTO queue_job (name, singleton_key, queue_id, worker_id, metadata, status_id, count_total, created_at, updated_at) " .
						"VALUES (%s, %s, %d, 0, %s, 0 /* RUNNING */, %d, %d, %d)",
						$this->_db->qstr('Reindex Knowledgebase Articles'),
						$this->_db->qstr(sprintf('search_index:%d:reindex', $search_index_id)),
						$search_queue_id,
						$this->_db->qstr(json_encode(['search_index_id' => $search_index_id, 'record_type' => 'kb_article'])),
						$kb_record_count,
						time(),
						time(),
					));
					
					$job_id = $this->_db->LastInsertId();
					
					$this->_db->ExecuteMaster(sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, created_at, message, cardinality) " .
						"SELECT UUID_TO_BIN(UUID()) AS uuid, " .
						"%d AS queue_id, " .
						"%d AS job_id, " .
						"0 /* available */ AS status_id, " .
						"UNIX_TIMESTAMP() AS created_at, " .
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
		if (array_key_exists('fulltext_kb_article', $this->_tables)) {
			$this->_db->ExecuteMaster('DROP TABLE fulltext_kb_article');
			unset($this->_tables['fulltext_kb_article']);
		}
		
		// Drop the old indexing progress
		$this->_db->ExecuteMaster("DELETE FROM cerb_property_store WHERE extension_id = 'cerberusweb.search.schema.kb_article'");
	}
	
	private function patchCustomFieldStringValueUtf8mb4() : void {
		// ===========================================================================
		// Convert `custom_field_stringvalue.field_value` to utf8mb4
		
		if (!array_key_exists('custom_field_stringvalue', $this->_tables))
			throw new Exception();
		
		list($columns,) = $this->_db->metaTable('custom_field_stringvalue');
		
		if (!array_key_exists('field_value', $columns))
			throw new Exception();
		
		$changes = [];
		
		if ('ascii_general_ci' != $columns['context']['collation']) {
			$changes[] = "MODIFY COLUMN context varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''";
		}
		
		if ('utf8mb4_unicode_ci' != $columns['field_value']['collation']) {
			$changes[] = "MODIFY COLUMN field_value varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''";
		}
		
		if ($changes) {
			$this->_db->ExecuteMaster("ALTER TABLE custom_field_stringvalue " . implode(', ', $changes));
		}
	}
	
	private function patchCustomFieldClobValueUtf8mb4() : void {
		// ===========================================================================
		// Convert `custom_field_clobvalue.field_value` to utf8mb4
		
		if (!array_key_exists('custom_field_clobvalue', $this->_tables))
			throw new Exception();
		
		list($columns,) = $this->_db->metaTable('custom_field_clobvalue');
		
		if (!array_key_exists('field_value', $columns))
			throw new Exception();
		
		$changes = [];
		
		if ('ascii_general_ci' != $columns['context']['collation']) {
			$changes[] = "MODIFY COLUMN context varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ''";
		}
		
		if ('utf8mb4_unicode_ci' != $columns['field_value']['collation']) {
			$changes[] = 'MODIFY COLUMN field_value MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		}
		
		if ($changes) {
			$this->_db->ExecuteMaster("ALTER TABLE custom_field_clobvalue " . implode(', ', $changes));
		}
	}
	
	private function patchMailQueueTable() : void {
		// ===========================================================================
		// Convert `mail_queue.name` to utf8mb4
		
		if (!array_key_exists('mail_queue', $this->_tables))
			throw new Exception();
		
		list($columns,) = $this->_db->metaTable('mail_queue');
		
		if (!array_key_exists('name', $columns))
			throw new Exception();
		
		$changes = [];
		
		if ('utf8mb4_unicode_ci' != $columns['name']['collation']) {
			$changes[] = "MODIFY COLUMN `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''";
		}
		
		if ($changes) {
			$this->_db->ExecuteMaster("ALTER TABLE mail_queue " . implode(', ', $changes));
		}
	}
	
	private function patchWorkerTable() : void {
		// ===========================================================================
		// Add `created_at` to `worker`
		
		list($columns,) = $this->_db->metaTable('worker');
		
		if (!array_key_exists('created_at', $columns)) {
			$this->_db->ExecuteMaster("ALTER TABLE worker ADD COLUMN created_at int unsigned NOT NULL DEFAULT 0");
			$this->_db->ExecuteMaster("UPDATE worker w SET w.created_at = COALESCE(
				(SELECT MIN(cal.created) FROM context_activity_log cal WHERE cal.actor_context = 'cerberusweb.contexts.worker' AND cal.actor_context_id = w.id),
				(SELECT MIN(m.created_date) FROM message m WHERE m.worker_id = w.id),
				w.updated
			) WHERE w.created_at = 0");
		}
	}
	
	private function patchServiceTokensTable() : void {
		// ===========================================================================
		// Service Tokens
		
		if (!array_key_exists('service_token', $this->_tables)) {
			$sql = sprintf("CREATE TABLE `service_token` (
				`id` int unsigned AUTO_INCREMENT,
				`name` varchar(255) NOT NULL DEFAULT '',
				`token_hint` varchar(32) NOT NULL DEFAULT '',
				`token_hash` char(64) CHARACTER SET ascii NOT NULL DEFAULT '',
				`created_at` int unsigned NOT NULL DEFAULT 0,
				`updated_at` int unsigned NOT NULL DEFAULT 0,
				`expires_at` int unsigned NOT NULL DEFAULT 0,
				`last_accessed_at` int unsigned NOT NULL DEFAULT 0,
				`scopes` text NOT NULL,
				PRIMARY KEY (id)
			) ENGINE=%s",
				APP_DB_ENGINE
			);
			$this->_db->ExecuteMaster($sql) or die("[PATCH] Failed to create 'service_token' table.");
			$this->_logger->info("[Patch] Created 'service_token' table.");
		}
	}
	
	private function patchCreateMetrics() : void {
		// [TODO] This needs to be gated on revision so it doesn't repeat
		
		// ===========================================================================
		// Metrics
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.service.token.uses'),
			$this->_db->qstr('Usage count for service token authentications'),
			$this->_db->qstr('counter'),
			$this->_db->qstr("record/token_id:\n  record_type: service_token\ntext/scope:\ntext/client_ip:\n"),
			time(),
			time()
		));
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.sessions.seat.kicks'),
			$this->_db->qstr('Count of worker sessions ended to free up a license seat'),
			$this->_db->qstr('counter'),
			$this->_db->qstr("record/worker_id:\n  record_type: worker\n"),
			time(),
			time()
		));
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.sessions.seat.kicks.duration'),
			$this->_db->qstr('Cumulative idle seconds of worker sessions ended to free up a license seat'),
			$this->_db->qstr('counter'),
			$this->_db->qstr("record/worker_id:\n  record_type: worker\n"),
			time(),
			time()
		));
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.scheduler.invocations'),
			$this->_db->qstr('Invocation count by scheduler job'),
			$this->_db->qstr('counter'),
			$this->_db->qstr("extension/job:\n"),
			time(),
			time()
		));
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.scheduler.duration'),
			$this->_db->qstr('Invocation duration (ms) by scheduler job'),
			$this->_db->qstr('counter'),
			$this->_db->qstr("extension/job:\n"),
			time(),
			time()
		));
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.queue.messages.open'),
			$this->_db->qstr('Open (available and in-flight) queue message depth by queue and status'),
			$this->_db->qstr('gauge'),
			$this->_db->qstr("record/queue_id:\n  record_type: queue\nnumber/status_id:\n"),
			time(),
			time()
		));
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.queue.messages.processed'),
			$this->_db->qstr('Processed (done and failed) queue message count by queue, job, and status'),
			$this->_db->qstr('counter'),
			$this->_db->qstr("record/queue_id:\n  record_type: queue\nrecord/job_id:\n  record_type: queue_job\nnumber/status_id:\n"),
			time(),
			time()
		));
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.mail.mailbox.received'),
			$this->_db->qstr('Count of messages downloaded from a mailbox'),
			$this->_db->qstr('counter'),
			$this->_db->qstr("record/mailbox_id:\n  record_type: mailbox\n"),
			time(),
			time()
		));
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.mail.mailbox.errors'),
			$this->_db->qstr('Count of mailbox check failures by mailbox and error status code'),
			$this->_db->qstr('counter'),
			$this->_db->qstr("record/mailbox_id:\n  record_type: mailbox\nnumber/status:\n"),
			time(),
			time()
		));
		
		$this->_db->ExecuteWriter(sprintf("INSERT IGNORE INTO metric (name, description, type, dimensions_kata, created_at, updated_at) " .
			"VALUES (%s, %s, %s, %s, %d, %d)",
			$this->_db->qstr('cerb.search.index.records'),
			$this->_db->qstr('Number of records indexed by search index and engine'),
			$this->_db->qstr('gauge'),
			$this->_db->qstr("record/index_id:\n  record_type: search_index\nextension/engine:\n"),
			time(),
			time()
		));
		
		// cerb.service.token.uses gained a `record/token_id` dimension during 11.2-dev (it only had scope +
		// client_ip). Fix the definition and drop the dimensionless samples — the metric isn't released, so
		// nothing depends on the old data.
		if ($this->_revision < 1506) {
			$this->_db->ExecuteWriter("UPDATE metric SET dimensions_kata = " . $this->_db->qstr("record/token_id:\n  record_type: service_token\ntext/scope:\ntext/client_ip:\n") . " WHERE name = 'cerb.service.token.uses'");
			$this->_db->ExecuteWriter("DELETE FROM metric_value WHERE metric_id = (SELECT id FROM metric WHERE name = 'cerb.service.token.uses')");
		}
	}
	
	private function patchQueueMessageIndexes() : void {
		// ===========================================================================
		// Dedicated covering index for the heartbeat queue-depth gauge. Leads with
		// status_id so the count scans only the open/in-flight slice (skips done/failed),
		// and is covering so the available_at filter needs no row lookups. Kept separate
		// from `queue_claimed` (the queue_id-led dequeue hot path) to avoid widening it.
		list(, $indexes) = $this->_db->metaTable('queue_message');
		
		if (!array_key_exists('queue_depth', $indexes)) {
			$this->_db->ExecuteMaster("ALTER TABLE queue_message ADD INDEX queue_depth (status_id, queue_id, available_at)");
		}
		
		// Job-scoped lookups — the live progress distbar counts (getLiveCountsForJobs) and the
		// completion check (checkForCompletedJobs) filter by job_id, which neither queue_claimed
		// nor queue_depth leads with. Covering over the status_id/available_at buckets so the
		// per-page worklist aggregate avoids a full table scan.
		if (!array_key_exists('job_progress', $indexes)) {
			$this->_db->ExecuteMaster("ALTER TABLE queue_message ADD INDEX job_progress (job_id, status_id, available_at)");
		}
	}
	
	private function patchUpdateAutomations() : void {
		// ===========================================================================
		// Update built-in automations
		
		$automation_files = [
			'ai.cerb.editor.mapBuilder.json',
			'ai.cerb.toolbarBuilder.interaction.json',
			'ai.cerb.toolbarBuilder.menu.json',
			'cerb.reply.isBannedDefunct.json',
		];
		
		foreach ($automation_files as $automation_file) {
			$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
			
			if (!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
				continue;
			
			DAO_Automation::importFromJson($automation_data);
			
			unset($automation_data);
		}
	}
	
	private function patchMergeGroupSettingToWorkerGroup() : void {
		// ===========================================================================
		// Merge group_setting into worker_group (#1844)
		
		list($columns,) = $this->_db->metaTable('worker_group');
		
		$changes = [];
		
		if (!array_key_exists('subject_has_mask', $columns))
			$changes[] = "ADD COLUMN subject_has_mask tinyint unsigned NOT NULL DEFAULT 0";
		
		if (!array_key_exists('subject_prefix', $columns))
			$changes[] = "ADD COLUMN subject_prefix varchar(128) NOT NULL DEFAULT ''";
		
		if ($changes) {
			$this->_db->ExecuteMaster("ALTER TABLE worker_group " . implode(', ', $changes));
			
			// Migrate data from group_setting
			if (array_key_exists('group_setting', $this->_tables)) {
				$this->_db->ExecuteMaster("UPDATE worker_group wg INNER JOIN group_setting gs ON (gs.group_id = wg.id AND gs.setting = 'subject_has_mask') SET wg.subject_has_mask = CAST(gs.value AS UNSIGNED)");
				$this->_db->ExecuteMaster("UPDATE worker_group wg INNER JOIN group_setting gs ON (gs.group_id = wg.id AND gs.setting = 'subject_prefix') SET wg.subject_prefix = gs.value");
			}
		}
		
		if (array_key_exists('group_setting', $this->_tables))
			$this->_db->ExecuteMaster("DROP TABLE group_setting");
	}
	
	private function patchCreateQueueJobWidgets() : void {
		// ===========================================================================
		// Default `Monitor` card widget for `cerb.contexts.queue.job`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.queue.job' AND extension_id='cerb.card.widget.queue.job.monitor'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Progress'),
				$this->_db->qstr('cerb.contexts.queue.job'),
				$this->_db->qstr('cerb.card.widget.queue.job.monitor'),
				$this->_db->qstr('{}'),
				time(), time(),
				1, 12,
				$this->_db->qstr('content'),
				$this->_db->qstr("hidden@bool: {{not cerb_record_readable(record__context,record_id,'worker',worker_id)}}")
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.queue.job`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.queue.job'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.queue.job'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Queue Job'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Progress'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.queue.job.monitor'),
				$this->_db->qstr('{}'),
				time(),
				1,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr("hidden@bool: {{not cerb_record_readable(record__context,record_id,'worker',worker_id)}}")
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.queue.job",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				2,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateAutomationEventWidgets() : void {
		// ===========================================================================
		// Default `Record Fields` card widget for `cerb.contexts.automation.event`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.automation.event' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.automation.event'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.automation.event`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.automation.event'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.automation.event'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Automation Event'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Event Listeners'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.worklist'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateAutomationEventListenerWidgets() : void {
		// ===========================================================================
		// Default `Record Fields` card widget for `cerb.contexts.automation.event.listener`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.automation.event.listener' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.automation.event.listener'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.automation.event.listener`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.automation.event.listener'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.automation.event.listener'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Automation Event Listener'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.automation.event.listener",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				1,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateAutomationWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerb.contexts.automation`
		
		// Shift the existing `Statistics` chart widget down so `Properties` can sit on top
		$this->_db->ExecuteMaster("UPDATE card_widget SET pos = 2 WHERE record_type = 'cerb.contexts.automation' AND extension_id = 'cerb.card.widget.chart.timeseries' AND name = 'Statistics' AND pos = 3");
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.automation' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.automation'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.automation`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.automation'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.automation'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Automation'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Policy'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.sheet'),
				$this->_db->qstr(json_encode([
					"data_query" => "type:worklist.records\r\nof:automation\r\nexpand:[policy_kata]\r\nquery:(\r\n  id:{{record_id}}\r\n  limit:1\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
					"cache_secs" => "",
					"placeholder_simulator_kata" => "",
					"sheet_kata" => "layout:\r\n  style: table\r\n  headings@bool: no\r\n  paging@bool: no\r\n\r\ncolumns:\r\n  code/policy_kata:\r\n    params:\r\n      syntax: kata\r\n",
					"toolbar_kata" => "",
				])),
				time(),
				2,
				4,
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.automation",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				3,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Script'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.sheet'),
				$this->_db->qstr(json_encode([
					"data_query" => "type:worklist.records\r\nof:automation\r\nexpand:[script]\r\nquery:(\r\n  id:{{record_id}}\r\n  limit:1\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
					"cache_secs" => "",
					"placeholder_simulator_kata" => "",
					"sheet_kata" => "layout:\r\n  style: table\r\n  headings@bool: no\r\n  paging@bool: no\r\n\r\ncolumns:\r\n  code/script:\r\n    params:\r\n      syntax: kata\r\n",
					"toolbar_kata" => "",
				])),
				time(),
				4,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateAutomationTimerWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerb.contexts.automation.timer`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.automation.timer' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.automation.timer'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.automation.timer`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.automation.timer'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.automation.timer'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Automation Timer'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.automation.timer",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				1,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateServiceTokenWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerb.contexts.service.token`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.service.token' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.service.token'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.service.token`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.service.token'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.service.token'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Service Token'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.service.token",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				2,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateGpgPublicKeyWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerberusweb.contexts.gpg_public_key`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerberusweb.contexts.gpg_public_key' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerberusweb.contexts.gpg_public_key'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerberusweb.contexts.gpg_public_key`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerberusweb.contexts.gpg_public_key'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerberusweb.contexts.gpg_public_key'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('GPG Public Key'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Public Key'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.sheet'),
				$this->_db->qstr(json_encode([
					"data_query" => "type:worklist.records\r\nof:gpg_public_key\r\nquery:(\r\n  id:{{record_id}}\r\n  limit:1\r\n  sort:[id]\r\n)\r\nformat:dictionaries",
					"cache_secs" => "",
					"placeholder_simulator_kata" => "",
					"sheet_kata" => "layout:\r\n  style: fieldset\r\n  headings@bool: no\r\n  paging@bool: no\r\n\r\ncolumns:\r\n  code/key_text:\r\n    label: Public Key\r\n",
					"toolbar_kata" => "",
				])),
				time(),
				2,
				12,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerberusweb.contexts.gpg_public_key",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				3,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateGpgPrivateKeyWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerb.contexts.gpg.private.key`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.gpg.private.key' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.gpg.private.key'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.gpg.private.key`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.gpg.private.key'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.gpg.private.key'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('GPG Private Key'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.gpg.private.key",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				2,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateQueueWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerb.contexts.queue`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.queue' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.queue'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.queue`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.queue'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.queue'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Queue'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Jobs'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.worklist'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.queue",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				3,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateResourceWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerb.contexts.resource`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.resource' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.resource'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.resource`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.resource'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.resource'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Resource'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.resource",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				2,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateSearchIndexWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerb.contexts.search.index`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.search.index' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.search.index'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Index` card widget for `cerb.contexts.search.index`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.search.index' AND extension_id='cerb.card.widget.search_index'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Index'),
				$this->_db->qstr('cerb.contexts.search.index'),
				$this->_db->qstr('cerb.card.widget.search_index'),
				$this->_db->qstr(json_encode([
					"search_index_id" => "{{record_id}}",
				])),
				time(), time(),
				2, 4,
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.search.index`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.search.index'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.search.index'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Search Index'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.search.index",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				2,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateToolbarWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerb.contexts.toolbar`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.toolbar' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.toolbar'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.toolbar`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.toolbar'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.toolbar'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Toolbar'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Sections'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.worklist'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.toolbar",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				3,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchCreateToolbarSectionWidgets() : void {
		// ===========================================================================
		// Default `Properties` card widget for `cerb.contexts.toolbar.section`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.toolbar.section' AND extension_id='cerb.card.widget.fields'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Properties'),
				$this->_db->qstr('cerb.contexts.toolbar.section'),
				$this->_db->qstr('cerb.card.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('content')
			));
		}
		
		// ===========================================================================
		// Default `Overview` profile tab for `cerb.contexts.toolbar.section`
		
		if ($this->_revision < 1506 && !$this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.toolbar.section'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_tab (name, context, extension_id, updated_at, extension_params_json, pos) " .
				"VALUES (%s, %s, %s, %d, %s, %d)",
				$this->_db->qstr('Overview'),
				$this->_db->qstr('cerb.contexts.toolbar.section'),
				$this->_db->qstr('cerb.profile.tab.dashboard'),
				time(),
				$this->_db->qstr(json_encode([
					"layout" => "sidebar_left",
				])),
				1
			));
			
			$new_profile_tab_id = $this->_db->LastInsertId();
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Toolbar Section'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.fields'),
				$this->_db->qstr(json_encode([
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
				$this->_db->qstr('sidebar'),
				$this->_db->qstr('')
			));
			
			$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
				"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
				$this->_db->qstr('Discussion'),
				$new_profile_tab_id,
				$this->_db->qstr('cerb.profile.tab.widget.comments'),
				$this->_db->qstr(json_encode([
					"context" => "cerb.contexts.toolbar.section",
					"context_id" => "{{record_id}}",
					"height" => "",
				])),
				time(),
				2,
				4,
				$this->_db->qstr('content'),
				$this->_db->qstr('')
			));
		}
	}
	
	private function patchMetricTable() : void {
		// ===========================================================================
		// Add `retention_days` to `metric`
		
		list($columns,) = $this->_db->metaTable('metric');
		
		$changes = [];
		
		if (!array_key_exists('retention_days', $columns)) {
			$changes[] = "ADD COLUMN retention_days INT UNSIGNED NOT NULL DEFAULT 0";
		}
		
		if ($changes) {
			$this->_db->ExecuteMaster("ALTER TABLE metric " .
				implode(', ', $changes)
			);
		}
	}
	
	private function patchQueueJobLogTable() : void {
		// ===========================================================================
		// Queue Job Log
		
		if (!array_key_exists('queue_job_log', $this->_tables)) {
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
			$this->_db->ExecuteMaster($sql) or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());
			
			$this->_tables['queue_job_log'] = 'queue_job_log';
		}
	}
	
	private function patchEncryptS3StorageCredentials() : void {
		// ===========================================================================
		// Migrate plaintext S3 storage credentials to encrypted connected accounts
		
		if (
			array_key_exists('devblocks_storage_profile', $this->_tables)
			&& array_key_exists('connected_service', $this->_tables)
			&& array_key_exists('connected_account', $this->_tables)
		) {
			$encrypt = DevblocksPlatform::services()->encryption();
			
			// Find the S3 storage profiles that still hold plaintext credentials
			$s3_profiles = $this->_db->GetArrayMaster(sprintf("SELECT id, name, params_json FROM devblocks_storage_profile WHERE extension_id = %s",
				$this->_db->qstr('devblocks.storage.engine.s3')
			));
			
			// Keep only the profiles that still carry plaintext keys and aren't migrated yet
			$profiles_to_migrate = [];
			
			if (is_array($s3_profiles))
				foreach ($s3_profiles as $profile) {
					$params = json_decode($profile['params_json'] ?? '', true) ?: [];
					
					if (!array_key_exists('access_key', $params) || array_key_exists('connected_account_id', $params))
						continue;
					
					$profile['params'] = $params;
					$profiles_to_migrate[] = $profile;
				}
			
			if ($profiles_to_migrate) {
				// Resolve our dedicated 'AWS S3' connected service once, matched by name so we don't
				// reuse some other AWS provider the operator configured for a non-S3 purpose. It almost
				// never exists yet, so create it (params are an encrypted empty object).
				$aws_service_id = $this->_db->GetOneMaster(sprintf("SELECT id FROM connected_service WHERE name = %s AND extension_id = %s ORDER BY id ASC LIMIT 1",
					$this->_db->qstr('AWS S3'),
					$this->_db->qstr('cerb.service.provider.aws')
				));
				
				if (!$aws_service_id) {
					$this->_db->ExecuteMaster(sprintf("INSERT INTO connected_service (name, uri, extension_id, params_json, updated_at) " .
						"VALUES (%s, %s, %s, %s, %d)",
						$this->_db->qstr('AWS S3'),
						$this->_db->qstr(''),
						$this->_db->qstr('cerb.service.provider.aws'),
						$this->_db->qstr($encrypt->encrypt(json_encode((object)[]))),
						time()
					));
					
					$aws_service_id = $this->_db->LastInsertId();
				}
				
				foreach ($profiles_to_migrate as $profile) {
					$params = $profile['params'];
					
					// Move only the secret material into the connected account
					$credentials = [
						'access_key' => $params['access_key'],
						'secret_key' => $params['secret_key'] ?? '',
					];
					
					// Allow this credential to sign requests to non-AWS endpoints (e.g. MinIO) so it
					// remains usable for http.request signing, not just storage
					$host = $params['host'] ?? '';
					if ($host && !str_contains(DevblocksPlatform::strLower($host), 'amazonaws.com'))
						$credentials['allow_non_aws_hosts'] = 1;
					
					$this->_db->ExecuteMaster(sprintf("INSERT INTO connected_account (name, owner_context, owner_context_id, service_id, uri, params_json, created_at, updated_at) " .
						"VALUES (%s, %s, %d, %d, %s, %s, %d, %d)",
						$this->_db->qstr(sprintf("%s (S3)", $profile['name'])),
						$this->_db->qstr('cerberusweb.contexts.app'),
						0,
						$aws_service_id,
						$this->_db->qstr(''),
						$this->_db->qstr($encrypt->encrypt(json_encode($credentials))),
						time(),
						time()
					));
					
					$account_id = $this->_db->LastInsertId();
					
					// Replace the plaintext keys with a reference to the connected account
					unset($params['access_key'], $params['secret_key']);
					$params['connected_account_id'] = intval($account_id);
					
					$this->_db->ExecuteMaster(sprintf("UPDATE devblocks_storage_profile SET params_json = %s WHERE id = %d",
						$this->_db->qstr(json_encode($params)),
						$profile['id']
					));
				}
			}
		}
	}
	
	private function patchRemoveSmartyDevblocksTemplates() : void {
		// ===========================================================================
		// Remove the deprecated browser-editable Smarty "custom templates" feature
		//
		// The `devblocks_template` table let Support Center admins override the base
		// Smarty templates from their browser. That mechanism has been removed: it was
		// a Smarty injection surface, and overrides silently broke portals across
		// upgrades (base templates evolve every release).
		//
		// Before dropping the table, preserve each portal's overrides:
		//   - The full set of overrides is exported (in the legacy XML format) into a
		//     `record_changeset` blob on the portal record. The changeset diff viewer
		//     is hard-gated to superusers, so this backup is genuinely admin-only.
		//   - Any `user_styles.css.tpl` override is migrated forward into the portal's
		//     new per-portal stylesheet setting (served from the `c=css` endpoint).
		//   - Home-page HTML is intentionally NOT migrated (the portal home is now
		//     authored in Markdown); it survives only inside the XML backup.
		// Superusers are notified once per affected portal.
		
		if (array_key_exists('devblocks_template', $this->_tables)) {
			// Map portal code => id
			$portal_ids_by_code = [];
			foreach ($this->_db->GetArrayMaster("SELECT id, code FROM community_tool") ?: [] as $row) {
				$portal_ids_by_code[$row['code']] = intval($row['id']);
			}
			
			// Group template overrides by tag (e.g. `portal_{code}`), and capture any
			// stylesheet overrides for forward-migration.
			$overrides_by_tag = [];
			$stylesheets_by_code = [];
			
			foreach ($this->_db->GetArrayMaster("SELECT plugin_id, path, tag, content FROM devblocks_template") ?: [] as $row) {
				$tag = (string)$row['tag'];
				$overrides_by_tag[$tag][] = $row;
				
				if ('support_center/user_styles.css.tpl' == $row['path'] && DevblocksPlatform::strStartsWith($tag, 'portal_')) {
					$code = substr($tag, strlen('portal_'));
					$stylesheets_by_code[$code] = (string)$row['content'];
				}
			}
			
			$notify_worker_ids = array_column($this->_db->GetArrayMaster("SELECT id FROM worker WHERE is_superuser = 1 AND is_disabled = 0") ?: [], 'id');
			
			foreach ($overrides_by_tag as $tag => $rows) {
				// Only back up portal-scoped overrides we can resolve to a portal record
				if (!DevblocksPlatform::strStartsWith($tag, 'portal_'))
					continue;
				
				$code = substr($tag, strlen('portal_'));
				
				if (!array_key_exists($code, $portal_ids_by_code))
					continue;
				
				$portal_id = $portal_ids_by_code[$code];
				
				// Build the legacy export XML shape (mirrors the old portals export):
				// <cerb><templates><template plugin_id= path=>...escaped content...</template></templates></cerb>
				$xml = simplexml_load_string(
					'<?xml version="1.0" encoding="' . LANG_CHARSET_CODE . '"?>' .
					'<cerb><templates></templates></cerb>'
				);
				
				foreach ($rows as $row) {
					$eTemplate = $xml->templates->addChild('template', DevblocksPlatform::strEscapeHtml((string)$row['content']));
					$eTemplate->addAttribute('plugin_id', DevblocksPlatform::strEscapeHtml((string)$row['plugin_id']));
					$eTemplate->addAttribute('path', DevblocksPlatform::strEscapeHtml((string)$row['path']));
				}
				
				// Pretty-print
				$imp = new DOMImplementation;
				$doc = $imp->createDocument("", "");
				$doc->encoding = LANG_CHARSET_CODE;
				$doc->formatOutput = true;
				$node = dom_import_simplexml($xml);
				$node = $doc->importNode($node, true);
				$doc->appendChild($node);
				$backup_xml = $doc->saveXML();
				
				// Store as an admin-only record_changeset blob, written straight into the
				// database storage engine in pure SQL (patches avoid the DAO_ API, which
				// fires events/validation). Mirrors DAO_RecordChangeset::create(): the
				// stored payload is the JSON-wrapped content the superuser diff viewer
				// reads back, so the backup round-trips cleanly.
				$changeset_json = json_encode(['custom_templates_backup' => $backup_xml]);
				$changeset_hash = sha1($changeset_json);
				$changeset_size = strlen($changeset_json);
				
				$this->_db->ExecuteMaster(sprintf(
					"INSERT INTO record_changeset (record_type, record_id, record_key, worker_id, created_at, storage_sha1hash, storage_size, storage_key, storage_extension, storage_profile_id) " .
					"VALUES (%s, %d, %s, 0, %d, %s, %d, '', %s, 0)",
					$this->_db->qstr('community_portal'),
					$portal_id,
					$this->_db->qstr('custom_templates_backup'),
					time(),
					$this->_db->qstr($changeset_hash),
					$changeset_size,
					$this->_db->qstr('devblocks.storage.engine.database')
				));
				$changeset_id = $this->_db->LastInsertId();
				
				// The database storage engine uses the row id as its storage key.
				$this->_db->ExecuteMaster(sprintf(
					"UPDATE record_changeset SET storage_key = %s WHERE id = %d",
					$this->_db->qstr($changeset_id),
					$changeset_id
				));
				
				// Write the raw JSON payload into the engine's chunk table (no gzip/base64),
				// 65535 bytes per chunk, chunk numbers starting at 1.
				$chunk_num = 1;
				foreach (str_split($changeset_json, 65535) as $chunk) {
					$this->_db->ExecuteMaster(sprintf(
						"INSERT INTO storage_record_changeset (id, data, chunk) VALUES (%d, %s, %d)",
						$changeset_id,
						$this->_db->qstr($chunk),
						$chunk_num++
					));
				}
				
				// Notify superusers that a backup was made for this portal
				$entry_json = json_encode([
					'message' => 'activities.custom.other',
					'variables' => [
						'message' => sprintf("The deprecated 'custom templates' for the '%s' Support Center portal were removed in this upgrade. A backup was saved to this record's changeset history (visible to administrators).", $code),
					],
					'urls' => [
						'message' => sprintf("ctx://%s:%d", Context_CommunityTool::ID, $portal_id),
					],
				]);
				
				foreach ($notify_worker_ids as $worker_id) {
					$this->_db->ExecuteMaster(sprintf(
						"INSERT INTO notification (worker_id, created_date, context, context_id, activity_point, entry_json, is_read) " .
						"VALUES (%d, %d, %s, %d, %s, %s, 0)",
						intval($worker_id),
						time(),
						$this->_db->qstr(Context_CommunityTool::ID),
						$portal_id,
						$this->_db->qstr('custom.other'),
						$this->_db->qstr($entry_json)
					));
				}
			}
			
			// Migrate stylesheet overrides into the new per-portal stylesheet setting.
			// These property keys mirror UmScApp::PARAM_USER_STYLESHEET[_UPDATED_AT].
			$cache = DevblocksPlatform::services()->cache();
			
			foreach ($stylesheets_by_code as $code => $css) {
				if ('' === trim($css))
					continue;
				
				$this->_db->ExecuteMaster(sprintf(
					"REPLACE INTO community_tool_property (tool_code, property_key, property_value) VALUES (%s, %s, %s)",
					$this->_db->qstr($code),
					$this->_db->qstr('common.user_stylesheet'),
					$this->_db->qstr($css)
				));
				$this->_db->ExecuteMaster(sprintf(
					"REPLACE INTO community_tool_property (tool_code, property_key, property_value) VALUES (%s, %s, %s)",
					$this->_db->qstr($code),
					$this->_db->qstr('common.user_stylesheet_updated_at'),
					$this->_db->qstr(time())
				));
				
				// Invalidate the portal property cache so the new value is served immediately
				$cache->remove('um_comtoolprops_' . $code);
			}
			
			// Finally, drop the now-unused table
			$this->_db->ExecuteMaster("DROP TABLE devblocks_template");
			unset($this->_tables['devblocks_template']);
		}
	}
	
	private function patchStorageSyncndexes() : void {
		// ===========================================================================
		// Indexes for efficient cron.storage sync
		
		list(, $indexes) = $this->_db->metaTable('message');
		$changes = [];
		if (!array_key_exists('storage_profile_created', $indexes))
			$changes[] = "ADD INDEX storage_profile_created (storage_extension, storage_profile_id, created_date)";
		if ($changes)
			$this->_db->ExecuteMaster("ALTER TABLE message " . implode(', ', $changes));
		
		list(, $indexes) = $this->_db->metaTable('resource');
		$changes = [];
		if (!array_key_exists('storage_profile_updated', $indexes))
			$changes[] = "ADD INDEX storage_profile_updated (storage_extension, storage_profile_id, updated_at)";
		if (array_key_exists('storage_extension', $indexes))
			$changes[] = "DROP INDEX storage_extension";
		if ($changes)
			$this->_db->ExecuteMaster("ALTER TABLE resource " . implode(', ', $changes));
		
		list(, $indexes) = $this->_db->metaTable('context_avatar');
		$changes = [];
		if (!array_key_exists('storage_profile_updated', $indexes))
			$changes[] = "ADD INDEX storage_profile_updated (storage_extension, storage_profile_id, updated_at)";
		if (array_key_exists('storage_extension', $indexes))
			$changes[] = "DROP INDEX storage_extension";
		if ($changes)
			$this->_db->ExecuteMaster("ALTER TABLE context_avatar " . implode(', ', $changes));
	}
	
	private function patchClearWorklistModels() : void {
		// ===========================================================================
		// Clear the plugin worklist models
		
		if ($this->_revision < 1507) {
			$this->_db->ExecuteMaster("DELETE FROM worker_view_model WHERE view_id IN ('cerb5_plugins','plugins_installed')");
			$this->_db->ExecuteWriter("DELETE FROM worker_view_model WHERE class_name IN ('View_Automation', 'View_AutomationEvent', 'View_Mailbox', 'View_MailRoutingRule', 'View_MailTransport', 'View_Metric', 'View_PluginLibrary', 'View_ServiceToken', 'View_TriggerEvent', 'View_Queue', 'View_SearchIndex', 'View_Snippet', 'View_WebhookListener')");
		}
	}
	
	private function patchCreateMetricsExplorerWidgets() : void {
		// ===========================================================================
		// Seed the Dataset (Metrics Explorer) widget on metric record cards
		
		if ($this->_revision < 1507) {
			// Counters: Sum (total of increments; == count for +1 counters). Gauges: faceted avg/min/max
			// (total across the metric's dimensions; degrades to plain for non-dimensioned gauges).
			// Samples (raw count) is defined but hidden by default for both types — toggleable per card.
			$series_kata =
				"series/samples:\n  metric: {{record_name}}\n  function: count\n  label: Samples\n  hidden@bool: yes\n\n" .
				"series/sum:\n  metric: {{record_name}}\n  function: sum\n  label: Sum\n  hidden@bool: {{record_type == 'gauge'}}\n\n" .
				"series/avg:\n  metric: {{record_name}}\n  function: faceted_average\n  label: Average\n  hidden@bool: {{record_type == 'counter'}}\n\n" .
				"series/min:\n  metric: {{record_name}}\n  function: faceted_min\n  label: Min\n  hidden@bool: {{record_type == 'counter'}}\n\n" .
				"series/max:\n  metric: {{record_name}}\n  function: faceted_max\n  label: Max\n  hidden@bool: {{record_type == 'counter'}}\n";
			
			$explorer_params = json_encode([
				'range' => '-24 hours to now',
				'period' => 'hour',
				'chart_as' => 'line',
				'series_kata' => $series_kata,
			]);
			
			// Remove any standalone statistics widget previously seeded on metric cards
			$this->_db->ExecuteMaster("DELETE FROM card_widget WHERE record_type = 'cerb.contexts.metric' AND extension_id = 'cerb.card.widget.chart.timeseries' AND name = 'Statistics'");
			
			// Insert the Dataset widget on metric cards if it isn't already present (idempotent)
			if (!$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.metric' AND extension_id='cerb.card.widget.metrics.explorer'")) {
				$this->_db->ExecuteMaster(sprintf("INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
					"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
					$this->_db->qstr('Dataset'),
					$this->_db->qstr('cerb.contexts.metric'),
					$this->_db->qstr('cerb.card.widget.metrics.explorer'),
					$this->_db->qstr($explorer_params),
					time(),
					time(),
					1,
					8,
					$this->_db->qstr('content')
				));
			}
		}
	}
	
	private function patchTaskProjectTable() : void {
		// ===========================================================================
		// Task Projects (Daily Task Board) — record type backing project_id on tasks
		
		if (!array_key_exists('task_project', $this->_tables)) {
			$sql = sprintf("CREATE TABLE `task_project` (
				`id` bigint unsigned AUTO_INCREMENT,
				`name` varchar(255) NOT NULL DEFAULT '',
				`owner_context` varchar(128) NOT NULL DEFAULT '',
				`owner_context_id` int NOT NULL DEFAULT 0,
				`is_closed` tinyint unsigned NOT NULL DEFAULT 0,
				`created_at` int unsigned NOT NULL DEFAULT 0,
				`updated_at` int unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY (id),
				KEY owner_compound (owner_context, owner_context_id)
			) ENGINE=%s",
				APP_DB_ENGINE
			);
			$this->_db->ExecuteMaster($sql) or die("[PATCH] Failed to create 'task_project' table.");
			$this->_logger->info("[Patch] Created 'task_project' table.");
		}
		
		// Owner + archive columns for installs that created task_project before they existed
		list($tp_columns, $tp_indexes) = $this->_db->metaTable('task_project');
		
		$changes = [];
		
		if (!array_key_exists('owner_context', $tp_columns))
			$changes[] = "ADD COLUMN owner_context VARCHAR(128) NOT NULL DEFAULT ''";
		
		if (!array_key_exists('owner_context_id', $tp_columns))
			$changes[] = "ADD COLUMN owner_context_id INT NOT NULL DEFAULT 0";
		
		if (!array_key_exists('is_closed', $tp_columns))
			$changes[] = "ADD COLUMN is_closed TINYINT UNSIGNED NOT NULL DEFAULT 0";
		
		// Color moved to a per-board-tab mapping; the record no longer carries one
		if (array_key_exists('color', $tp_columns))
			$changes[] = "DROP COLUMN color";
		
		if (!array_key_exists('owner_compound', $tp_indexes))
			$changes[] = "ADD INDEX owner_compound (owner_context, owner_context_id)";
		
		if ($changes) {
			$this->_db->ExecuteMaster("ALTER TABLE task_project " . implode(', ', $changes))
			or die("[PATCH] Failed to add owner/archive columns to 'task_project'.");
			$this->_logger->info("[Patch] Added owner/archive columns to 'task_project'.");
		}
		
		// ===========================================================================
		// Task Project membership + active flag on `task`
		
		list($task_columns, $task_indexes) = $this->_db->metaTable('task');
		
		$changes = [];
		
		// Owning Task Project (0 = none). Renamed from the earlier `task_project_id`.
		if (array_key_exists('task_project_id', $task_columns) && !array_key_exists('project_id', $task_columns)) {
			$changes[] = "CHANGE COLUMN task_project_id project_id BIGINT UNSIGNED NOT NULL DEFAULT 0";
		} else if (!array_key_exists('project_id', $task_columns)) {
			$changes[] = "ADD COLUMN project_id BIGINT UNSIGNED NOT NULL DEFAULT 0";
		}
		
		// "Doing now" flag — an open task in progress vs. merely todo. The Daily Task Board
		// column is otherwise derived from status (done=closed, stash=waiting) + importance (rank).
		if (!array_key_exists('is_active', $task_columns))
			$changes[] = "ADD COLUMN is_active TINYINT UNSIGNED NOT NULL DEFAULT 0";
		
		// Drop the denormalized board-placement columns; placement now derives from
		// status_id / importance / completed_date rather than being stored per task.
		if (array_key_exists('board_lookup', $task_indexes))
			$changes[] = "DROP INDEX board_lookup";
		
		if (array_key_exists('board_date_column', $task_indexes))
			$changes[] = "DROP INDEX board_date_column";
		
		if (array_key_exists('board_date', $task_columns))
			$changes[] = "DROP COLUMN board_date";
		
		if (array_key_exists('board_column', $task_columns))
			$changes[] = "DROP COLUMN board_column";
		
		if (array_key_exists('board_rank', $task_columns))
			$changes[] = "DROP COLUMN board_rank";
		
		if (!array_key_exists('project_lookup', $task_indexes))
			$changes[] = "ADD INDEX project_lookup (project_id)";
		
		if ($changes) {
			$this->_db->ExecuteMaster("ALTER TABLE task " . implode(', ', $changes))
			or die("[PATCH] Failed to update Task Project columns on 'task'.");
			$this->_logger->info("[Patch] Updated Task Project columns on 'task'.");
		}
	}
	
	private function patchRemoveSilhouetteAvatars() : void {
		// ===========================================================================
		// Remove deprecated avatar default-style settings (silhouettes/monograms toggle)
		
		$this->_db->ExecuteMaster("DELETE FROM devblocks_setting WHERE plugin_id = 'cerberusweb.core' AND setting IN ('avatar_default_style_contact','avatar_default_style_worker')");
		$this->_logger->info("[Patch] Removed deprecated avatar_default_style settings.");
	}
	
	private function patchWorkflowResourceTable() : void {
		// ===========================================================================
		// Workflow resources pivot table (hoisted from `workflow.resources_kata`)
		//
		// Replaces the per-record `workflow_id` columns on toolbar_section, automation_event_listener,
		// and mail_routing_rule with a single queryable pivot + reverse lookup. The CREATE and one-time
		// backfill run only when the table is first created (idempotent on re-run).
		
		if (!array_key_exists('workflow_resource', $this->_tables)) {
			$sql = sprintf("
				CREATE TABLE `workflow_resource` (
				`workflow_id` int unsigned NOT NULL,
				`record_type` varchar(255) NOT NULL DEFAULT '',
				`record_alias` varchar(255) NOT NULL DEFAULT '',
				`record_id` bigint unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY (`workflow_id`,`record_type`,`record_alias`),
				KEY `reverse` (`record_type`,`record_id`,`workflow_id`)
				) ENGINE=%s
			", APP_DB_ENGINE);
			$this->_db->ExecuteMaster($sql) or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());
			
			$this->_tables['workflow_resource'] = 'workflow_resource';
			
			// Backfill from each workflow's resources_kata. Pure platform services (no DAO/Model) so this
			// historical patch doesn't drift against the latest schema. Mirrors Model_Workflow::getResources().
			// Guarded on the column still existing (it's dropped further below) so a dev re-create of the pivot
			// table after the drop can't fatal on a missing column.
			$kata = DevblocksPlatform::services()->kata();
			
			list($_workflow_columns,) = $this->_db->metaTable('workflow');
			
			$workflow_rows = array_key_exists('resources_kata', $_workflow_columns)
				? $this->_db->GetArrayMaster("SELECT id, resources_kata FROM workflow")
				: [];
			
			foreach ($workflow_rows as $workflow_row) {
				$workflow_id = intval($workflow_row['id']);
				$err = null;
				
				if (false === ($tree = $kata->parse($workflow_row['resources_kata'] ?? '', $err)))
					continue;
				
				if (false === ($resources = $kata->formatTree($tree ?? [], DevblocksDictionaryDelegate::instance([]), $err)))
					continue;
				
				if (!is_array($resources['records'] ?? null))
					continue;
				
				$values = [];
				
				foreach ($resources['records'] as $record_key => $record_id) {
					$record_type = DevblocksPlatform::services()->string()->strBefore($record_key, '/');
					$record_alias = DevblocksPlatform::services()->string()->strAfter($record_key, '/');
					
					if ('' == $record_type || '' == $record_alias)
						continue;
					
					$values[] = sprintf("(%d, %s, %s, %d)",
						$workflow_id,
						$this->_db->qstr($record_type),
						$this->_db->qstr($record_alias),
						intval($record_id)
					);
				}
				
				if ($values) {
					$this->_db->ExecuteMaster("INSERT INTO workflow_resource (workflow_id, record_type, record_alias, record_id) VALUES " .
						implode(', ', $values));
				}
			}
			
			$this->_logger->info("[Patch] Created and backfilled the workflow_resource pivot table.");
		}
		
		// Drop the now-redundant per-record workflow_id columns (each guarded for idempotency).
		
		foreach (['toolbar_section', 'automation_event_listener', 'mail_routing_rule'] as $_wf_table) {
			if (!array_key_exists($_wf_table, $this->_tables))
				continue;
			
			list($_wf_columns,) = $this->_db->metaTable($_wf_table);
			
			if (array_key_exists('workflow_id', $_wf_columns)) {
				$this->_db->ExecuteMaster(sprintf("ALTER TABLE %s DROP COLUMN workflow_id", $_wf_table));
				$this->_logger->info(sprintf("[Patch] Dropped %s.workflow_id (superseded by workflow_resource).", $_wf_table));
			}
		}
		
		// Drop workflow.resources_kata now that the workflow_resource pivot is the source of truth. The
		// backfill above runs before this, so the data is preserved. Guarded for idempotency.
		
		list($_workflow_columns,) = $this->_db->metaTable('workflow');
		
		if (array_key_exists('resources_kata', $_workflow_columns)) {
			$this->_db->ExecuteMaster("ALTER TABLE workflow DROP COLUMN resources_kata");
			$this->_logger->info("[Patch] Dropped workflow.resources_kata (superseded by workflow_resource).");
		}
	}
	
	private function patchTaskTable() : void {
		// ===========================================================================
		// Convert `task.title` to utf8mb4
		
		if (!array_key_exists('task', $this->_tables))
			throw new Exception();
		
		list($columns,) = $this->_db->metaTable('task');
		
		if (!array_key_exists('title', $columns))
			throw new Exception();
		
		if ('utf8mb4_unicode_ci' != $columns['title']['collation']) {
			$this->_db->ExecuteMaster("ALTER TABLE task MODIFY COLUMN title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''");
			$this->_logger->info("[Patch] Converted task.title to utf8mb4.");
		}
	}
	
	private function patchDropFileBundleTable() : void {
		// ===========================================================================
		// The file_bundle table now belongs to the optional cerb.file_bundles plugin.
		// If there are existing records, enable the plugin to adopt them; otherwise drop
		// the empty table (the plugin recreates it if/when an admin enables it).
		
		if ($this->_revision < 1514 && array_key_exists('file_bundle', $this->_tables)) {
			if ($this->_db->GetOneMaster("SELECT COUNT(*) FROM file_bundle")) {
				if (false != ($plugin_file_bundles = DevblocksPlatform::getPlugin('cerb.file_bundles')))
					$plugin_file_bundles->setEnabled(true);
			} else {
				$this->_db->ExecuteMaster("DROP TABLE file_bundle");
			}
		}
	}
	
	private function patchWidgetTableIcons() : void {
		// ===========================================================================
		// Add per-instance `icon` to widget tables (overrides the widget type's manifest icon)
		
		if (!array_key_exists('workspace_widget', $this->_tables))
			throw new Exception();
		
		list($columns,) = $this->_db->metaTable('workspace_widget');
		
		if (!array_key_exists('icon', $columns)) {
			$this->_db->ExecuteMaster("ALTER TABLE workspace_widget ADD COLUMN icon varchar(64) NOT NULL DEFAULT '' AFTER label");
			$this->_logger->info("[Patch] Added workspace_widget.icon column.");
		}
		
		list($columns,) = $this->_db->metaTable('card_widget');
		
		if (!array_key_exists('icon', $columns)) {
			$this->_db->ExecuteMaster("ALTER TABLE card_widget ADD COLUMN icon varchar(64) NOT NULL DEFAULT '' AFTER name");
			$this->_logger->info("[Patch] Added card_widget.icon column.");
		}
		
		list($columns,) = $this->_db->metaTable('profile_widget');
		
		if (!array_key_exists('icon', $columns)) {
			$this->_db->ExecuteMaster("ALTER TABLE profile_widget ADD COLUMN icon varchar(64) NOT NULL DEFAULT '' AFTER name");
			$this->_logger->info("[Patch] Added profile_widget.icon column.");
		}
		
		// ===========================================================================
		// Curated default icons for built-in card/profile widgets (by name). Only names whose meaning is
		// more specific than the widget type's manifest icon are listed; everything else keeps the type
		// default. The `icon=''` guard never overwrites an icon an admin chose with the picker.
		
		if ($this->_revision < 1515) {
			$widget_icons_by_name = [
				// Record Fields concepts
				'Status' => 'flag', 'Owner' => 'tag',
				// Worklist-backed widgets (override the `list` type default)
				'Tickets' => 'ticket', 'Ticket' => 'ticket', 'Ticket History' => 'ticket',
				'Contact' => 'user', 'Contacts' => 'users', 'Members' => 'users', 'Group' => 'users',
				'Organization' => 'building-office', 'Worker' => 'user',
				'Bucket' => 'inbox', 'Buckets' => 'inbox',
				'Article' => 'book-open', 'Articles' => 'book-open',
				'Behavior' => 'hierarchy', 'Behaviors' => 'hierarchy', 'Behavior Tree' => 'hierarchy',
				'Bot' => 'bot', 'Reminder' => 'bell', 'Reminders' => 'bell',
				'Availability' => 'clock', 'Responsibilities' => 'target',
				'Activity' => 'history', 'Activity Log' => 'history', 'Recent Activity' => 'history',
				// Sheets / specifics
				'Progress' => 'gauge', 'Statistics' => 'chart-line', 'Outbound Mail' => 'chart-line',
				'Dataset' => 'database', 'Metrics' => 'gauge', 'Volume' => 'chart-bar',
				'Public Key' => 'key', 'Subkeys' => 'key', 'UIDs' => 'key',
				'GPG Public Key' => 'key', 'GPG Private Key' => 'key',
				'Signature' => 'signature', 'Snippet' => 'quote', 'Content' => 'file-document',
				'Search Index' => 'search', 'Index' => 'search',
				'Toolbar' => 'toolbox', 'Toolbar Section' => 'toolbox', 'Sections' => 'toolbox',
				'Policy' => 'shield', 'Server' => 'server',
				'Classifier' => 'brain', 'Classification' => 'brain', 'Classifications' => 'brain',
				'Project Board' => 'kanban', 'Project Issues' => 'kanban', 'Board' => 'kanban',
				'Jira Issue' => 'kanban', 'Jira Project' => 'kanban',
			];
			
			foreach ($widget_icons_by_name as $widget_name => $widget_icon) {
				$this->_db->ExecuteMaster(sprintf("UPDATE card_widget SET icon=%s WHERE name=%s AND icon=''",
					$this->_db->qstr($widget_icon), $this->_db->qstr($widget_name)));
				$this->_db->ExecuteMaster(sprintf("UPDATE profile_widget SET icon=%s WHERE name=%s AND icon=''",
					$this->_db->qstr($widget_icon), $this->_db->qstr($widget_name)));
			}
		}
	}
	
	private function patchPackageLibraryIcons() : void {
		// ===========================================================================
		// Add `icon` to `package_library`
		//
		// A cerb-icons name used for the client-rendered 16:9 placeholder art when a package
		// has no embedded image. Populated from a package's `library.image` when that value is
		// an icon name (not a `data:` image); otherwise the client falls back to a per-type icon.
		
		list($columns,) = $this->_db->metaTable('package_library');
		
		if (!array_key_exists('icon', $columns)) {
			$this->_db->ExecuteMaster("ALTER TABLE package_library ADD COLUMN icon varchar(255) NOT NULL DEFAULT '' AFTER point");
		}
		
		// A package's art is single-valued: an avatar blob (embedded image) XOR an `icon` name. Early builds
		// set the icon name without clearing a previously-stored blob, leaving both — so the tile keeps
		// showing the stale image. Enforce the invariant by dropping the blob for any package that now
		// carries an icon name (row + its storage).
		
		if ($this->_revision < 1517) {
			$avatar_ids = array_column($this->_db->GetArrayMaster(
				"SELECT ca.id " .
				"FROM context_avatar ca INNER JOIN package_library pl ON (pl.id = ca.context_id) " .
				"WHERE ca.context = 'cerberusweb.contexts.package.library' AND pl.icon <> ''"
			), 'id');
			
			if ($avatar_ids) {
				Storage_ContextAvatar::delete($avatar_ids);
				$this->_db->ExecuteMaster(sprintf("DELETE FROM context_avatar WHERE id IN (%s)", implode(',', $avatar_ids)));
			}
		}
	}
	
	private function patchLlmAgentSessionTable() : void {
		// ===========================================================================
		// LLM agent harness: branching, compaction, cross-provider replay
		//
		// `llm_agent_session` gains a `provider_params` JSON block (model, authentication, and any provider
		// knobs — the whole `inputs.llm.<provider>` bag) so a session fully describes how to run it.
		// `llm_agent_message` gains a cheap classification of each stored native message (`role`, `kind`) plus a
		// rough token estimate (`token_est`) so the context strategy can budget/compact without parsing every
		// provider-native blob. Storage stays provider-native in `data_json`.
		
		if (array_key_exists('llm_agent_session', $this->_tables)) {
			list($columns,) = $this->_db->metaTable('llm_agent_session');
			
			$changes = [];
			
			if (!array_key_exists('provider_params', $columns))
				$changes[] = "ADD COLUMN provider_params mediumtext DEFAULT NULL AFTER provider";
			
			if(array_key_exists('model', $columns))
				$changes[] = "DROP COLUMN model";
			
			if ($changes)
				$this->_db->ExecuteMaster("ALTER TABLE llm_agent_session " . implode(', ', $changes));
		}
	}
	
	private function patchLlmAgentMessageTable() : void {
		if (array_key_exists('llm_agent_message', $this->_tables)) {
			list($columns,) = $this->_db->metaTable('llm_agent_message');
			
			$changes = [];
			
			if (!array_key_exists('role', $columns))
				$changes[] = "ADD COLUMN role varchar(64) NOT NULL DEFAULT '' AFTER session_uuid";
			
			if (!array_key_exists('kind', $columns))
				$changes[] = "ADD COLUMN kind varchar(32) NOT NULL DEFAULT '' AFTER role";
			
			if (!array_key_exists('token_est', $columns))
				$changes[] = "ADD COLUMN token_est int unsigned NOT NULL DEFAULT 0 AFTER kind";
			
			if (!array_key_exists('parent_uuid', $columns))
				$changes[] = "ADD COLUMN parent_uuid binary(16) DEFAULT NULL AFTER session_uuid";
			
			if ($changes)
				$this->_db->ExecuteMaster("ALTER TABLE llm_agent_message " . implode(', ', $changes));
			
			// Backfill legacy rows: `role` from the JSON envelope and `token_est` from raw byte
			// length (~4 bytes/token). `kind` is provider-specific and left empty; the reader
			// derives it from `data_json` when blank.
			if ($this->_revision < 1518) {
				$this->_db->ExecuteMaster("UPDATE llm_agent_message SET role = JSON_UNQUOTE(JSON_EXTRACT(data_json, '$.role')) WHERE role = '' AND JSON_VALID(data_json) AND JSON_EXTRACT(data_json, '$.role') IS NOT NULL");
				$this->_db->ExecuteMaster("UPDATE llm_agent_message SET token_est = CEIL(CHAR_LENGTH(data_json) / 4) WHERE token_est = 0 AND data_json IS NOT NULL");
			}
		}
	}
	
	private function patchLlmAgentSessionTree() : void {
		// The LLM session gains a `head_uuid` message pointer — the single implicit cursor (the leaf of the
		// active branch). Messages become an append-only TREE via `parent_uuid` (predecessor edge), so a
		// provider/model change just plants a neutral summary node as a new root and advances the head (no
		// clone/rewrite). Backfill: chain each existing message's parent to its seq-predecessor within the
		// same session, and point each session's head at its tip (max-seq) message.
		if ($this->_revision < 1520) {
			list($columns,) = $this->_db->metaTable('llm_agent_session');
			
			if (!array_key_exists('head_uuid', $columns))
				$this->_db->ExecuteMaster("ALTER TABLE llm_agent_session ADD COLUMN head_uuid binary(16) DEFAULT NULL AFTER provider_params");
			
			list(, $msg_indexes) = $this->_db->metaTable('llm_agent_message');
			if (!array_key_exists('parent_uuid', $msg_indexes))
				$this->_db->ExecuteMaster("ALTER TABLE llm_agent_message ADD INDEX parent_uuid (parent_uuid)");
			
			// Tree edges: parent = the prior message in the same session by seq (overwrites the old
			// copy-provenance meaning of parent_uuid; roots get NULL). Window function derived table.
			$this->_db->ExecuteMaster(
				"UPDATE llm_agent_message m " .
				"JOIN (SELECT `uuid`, LAG(`uuid`) OVER (PARTITION BY `session_uuid` ORDER BY `seq`) AS prev_uuid FROM llm_agent_message) p " .
				"ON p.`uuid` = m.`uuid` " .
				"SET m.`parent_uuid` = p.prev_uuid"
			);
			
			// Head = each session's tip (seq is globally unique, so max-seq identifies the leaf).
			$this->_db->ExecuteMaster(
				"UPDATE llm_agent_session s " .
				"JOIN (SELECT `session_uuid`, MAX(`seq`) AS max_seq FROM llm_agent_message GROUP BY `session_uuid`) mx ON mx.`session_uuid` = s.`uuid` " .
				"JOIN llm_agent_message tip ON tip.`session_uuid` = s.`uuid` AND tip.`seq` = mx.max_seq " .
				"SET s.`head_uuid` = tip.`uuid` " .
				"WHERE s.`head_uuid` IS NULL"
			);
		}
	}
	
	private function patchLlmAgentSessionUsage() : void {
		// `llm_agent_session` denormalizes the active-branch context token estimate (`token_usage`) so the GUI
		// (transcript list / sidebar / dev page) can show it without re-summing the tree, and adds `updated_at`
		// (last activity) so lists sort by recency instead of creation — a long-lived session otherwise sinks
		// under its old `created_at`, and any future retention keys on activity, not birth. Both are written per
		// assistant turn (piggybacking the head-advance write). Backfill `updated_at = created_at` for order, and
		// retro-fill `token_usage` with a naive per-session SUM of message `token_est` — pre-11.2 sessions have no
		// forks/summaries, so the whole message set IS the active path (a resume replaces it with an exact count).
		// `token_est` is populated by the rev-1518 block above, which runs first.
		if ($this->_revision < 1522) {
			list($columns, $indexes) = $this->_db->metaTable('llm_agent_session');
			$changes = [];
			
			if (!array_key_exists('token_usage', $columns))
				$changes[] = "ADD COLUMN token_usage int unsigned NOT NULL DEFAULT 0";
			
			if (!array_key_exists('updated_at', $columns))
				$changes[] = "ADD COLUMN updated_at int unsigned NOT NULL DEFAULT 0";
			
			if (!array_key_exists('updated_at', $indexes))
				$changes[] = "ADD INDEX updated_at (updated_at)";
			
			if ($changes)
				$this->_db->ExecuteMaster("ALTER TABLE llm_agent_session " . implode(', ', $changes));
			
			$this->_db->ExecuteMaster("UPDATE llm_agent_session SET updated_at = created_at WHERE updated_at = 0");
			
			$this->_db->ExecuteMaster(
				"UPDATE llm_agent_session s " .
				"JOIN (SELECT session_uuid, SUM(token_est) AS total FROM llm_agent_message GROUP BY session_uuid) m " .
				"ON m.session_uuid = s.uuid " .
				"SET s.token_usage = m.total " .
				"WHERE s.token_usage = 0"
			);
		}
	}
	
	private function patchLlmAgentSessionUuidToId() : void {
		// ===========================================================================
		// `llm_agent_session` gains a surrogate `id` (bigint) alongside its `uuid` PK. Pasted images in agent
		// transcripts are stored as durable attachments owned by the session via `attachment_link`, whose
		// `context_id` is integer — the session `uuid` can't key it — so images link to this int id. The UUID
		// stays the unguessable resume token; `id` is also the forward path to a full record context (record
		// links, worklists, bulk) when the session is registered later. bigint for that future, though the
		// int-keyed `attachment_link.context_id` is the near-term ceiling.
		
		if ($this->_revision < 1523) {
			list($columns,) = $this->_db->metaTable('llm_agent_session');
			
			if (!array_key_exists('id', $columns)) {
				$this->_db->ExecuteMaster(
					"ALTER TABLE llm_agent_session " .
					"ADD COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT FIRST, " .
					"ADD UNIQUE KEY id (id)"
				);
			}
		}
	}
	
	private function patchLlmAgentMessageUsage() : void {
		// ===========================================================================
		// `llm_agent_message` gains `usage_json` — provider-reported neutral token usage ({input, output, cache_read,
		// cache_write}) captured on assistant turns. A dedicated column (NOT in `data_json`, which is replayed to the
		// provider API verbatim) so it can't leak back into a request; powers per-turn tokens + cache-coverage in the
		// Setup→Developers transcript viewer.
		
		if ($this->_revision < 1524) {
			list($columns,) = $this->_db->metaTable('llm_agent_message');
			
			if (!array_key_exists('usage_json', $columns))
				$this->_db->ExecuteMaster("ALTER TABLE llm_agent_message ADD COLUMN usage_json TEXT DEFAULT NULL");
		}
	}
	
	private function patchLlmAgentSessionPropertyStore() : void {
		// ===========================================================================
		// De-duplicate the session's large denormalized config. `system_prompt`, `tools`, and `mounts` are
		// identical across long runs of sessions for the same agent, so they move into a content-addressed store
		// keyed by a SHA-256 of the value; each session references it by hash. `llm_agent_session_property` is the
		// shared hash->value table (kept generic — `llm_agent_message` payloads may de-dupe through it later).
		//
		// This pass creates the property table and the three nullable `*_hash` reference columns. It intentionally
		// no longer (re)creates the `system_prompt`/`tools`/`mounts` blob columns: an already-upgraded DB keeps
		// its existing ones (removing the ADD doesn't drop them, and their data is backfilled into the store
		// manually for now), and they'll be dropped outright once the DAO reads from the store. No revision gate —
		// the metaTable checks make it idempotent, so it self-heals on any `/update`. `agent_id` (a plain column,
		// not a dedup blob) is folded in here so every `llm_agent_session` column add lives in one place. These
		// columns began life across revisions 1525/1526/1538/1541; merged here.

		if(!array_key_exists('llm_agent_session_property', $this->_tables)) {
			$this->_db->ExecuteMaster("
				CREATE TABLE llm_agent_session_property (
					hash char(64) CHARACTER SET ascii NOT NULL,
					value mediumtext NOT NULL,
					PRIMARY KEY (hash)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
			") or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());
			$this->_logger->info("[Patch] Created 'llm_agent_session_property' table.");
			$this->_tables['llm_agent_session_property'] = 'llm_agent_session_property';
		}

		list($columns,) = $this->_db->metaTable('llm_agent_session');

		// `agent_id` — the AI worker a session runs AS (`llm.agent:inputs:agent:`); the actor half of the memory
		// layer's actor/target pair (user_type/user_id is the target). Not a dedup blob — folded in as a plain
		// session column so all `llm_agent_session` column adds live in one idempotent place.
		if(!array_key_exists('agent_id', $columns))
			$this->_db->ExecuteMaster("ALTER TABLE llm_agent_session ADD COLUMN agent_id INT UNSIGNED NOT NULL DEFAULT 0");

		// The new SHA-256 references into `llm_agent_session_property` (hex; matches MySQL `SHA2(value,256)` and
		// PHP `hash('sha256',$value)`). NULL preserves today's semantics: an absent prompt/tools, and — for
		// mounts — a filesystem that was never enabled (distinct from a hash of `"[]"`, enabled but /tmp-only).
		if(!array_key_exists('system_prompt_hash', $columns))
			$this->_db->ExecuteMaster("ALTER TABLE llm_agent_session ADD COLUMN system_prompt_hash CHAR(64) CHARACTER SET ascii DEFAULT NULL");

		if(!array_key_exists('tools_hash', $columns))
			$this->_db->ExecuteMaster("ALTER TABLE llm_agent_session ADD COLUMN tools_hash CHAR(64) CHARACTER SET ascii DEFAULT NULL");

		if(!array_key_exists('mounts_hash', $columns))
			$this->_db->ExecuteMaster("ALTER TABLE llm_agent_session ADD COLUMN mounts_hash CHAR(64) CHARACTER SET ascii DEFAULT NULL");
	}
	
	private function patchAutomationProfileMakeover() : void {
		// ===========================================================================
		// Automation profile 'Overview' tab: lead with the read-only node-graph 'Code' widget instead of the text
		// 'Script' sheet, and move 'Discussion' into the sidebar.
		
		if ($this->_revision < 1527) {
			$automation_tab_id = $this->_db->GetOneMaster("SELECT id FROM profile_tab WHERE context = 'cerb.contexts.automation' AND name = 'Overview' LIMIT 1");
			
			if ($automation_tab_id) {
				// Drop the text-only 'Script' sheet widget (superseded by the Code graph).
				$this->_db->ExecuteMaster(sprintf("DELETE FROM profile_widget WHERE profile_tab_id = %d AND name = 'Script' AND extension_id = 'cerb.profile.tab.widget.sheet'",
					$automation_tab_id
				));
				
				// Move 'Discussion' into the sidebar at pos 3.
				$this->_db->ExecuteMaster(sprintf("UPDATE profile_widget SET zone = 'sidebar', pos = 3 WHERE profile_tab_id = %d AND name = 'Discussion' AND extension_id = 'cerb.profile.tab.widget.comments'",
					$automation_tab_id
				));
				
				// Insert the 'Code' node-graph widget in content, pos 1 — gated by name+extension so it's idempotent.
				if (!$this->_db->GetOneMaster(sprintf("SELECT id FROM profile_widget WHERE profile_tab_id = %d AND name = 'Code' AND extension_id = 'cerb.profile.tab.widget.automation.graph'", $automation_tab_id))) {
					$this->_db->ExecuteMaster(sprintf("INSERT INTO profile_widget (name, profile_tab_id, extension_id, extension_params_json, updated_at, pos, width_units, zone, options_kata) " .
						"VALUES (%s, %d, %s, %s, %d, %d, %d, %s, %s)",
						$this->_db->qstr('Code'),
						$automation_tab_id,
						$this->_db->qstr('cerb.profile.tab.widget.automation.graph'),
						$this->_db->qstr(json_encode(['automation_id' => '{{record_id}}', 'height' => 600, 'minimap' => 1])),
						time(), 1, 4, $this->_db->qstr('content'), $this->_db->qstr('')
					));
				}
			}
		}
	}
	
	private function patchLlmAgentMessageMicroseconds() : void {
		// ===========================================================================
		// [11.2.0] Sub-second precision for LLM message timestamps
		// ===========================================================================
		
		if ($this->_revision < 1528) {
			list($columns,) = $this->_db->metaTable('llm_agent_message');
			
			// Microseconds ELAPSED WITHIN `created_at`, not a timestamp of its own — always 0..999999, so a 3-byte
			// mediumint holds it and `created_at` keeps its meaning for everything already reading it. Buys the
			// resolution to say how long a tool actually took; whole seconds round most tool calls to zero.
			if (!array_key_exists('created_at_usec', $columns))
				$this->_db->ExecuteMaster("ALTER TABLE llm_agent_message ADD COLUMN created_at_usec mediumint unsigned NOT NULL DEFAULT 0");
		}
	}
	
	private function patchAutomationContinuationOwnership() : void {
		// ===========================================================================
		// Continuation ownership & channel isolation: record the trigger `extension_id` and the owning
		// `worker_id` on each parked continuation so a worker can list/resume their own worker-side interactions.
		// Worker and website channels never cross (website stays worker_id=0 with a distinct extension_id).
		
		if ($this->_revision < 1529) {
			list($columns,) = $this->_db->metaTable('automation_continuation');
			
			if (!array_key_exists('extension_id', $columns))
				$this->_db->ExecuteMaster("ALTER TABLE automation_continuation ADD COLUMN extension_id VARCHAR(255) NOT NULL DEFAULT '', ADD INDEX (extension_id)");
			
			if (!array_key_exists('worker_id', $columns))
				$this->_db->ExecuteMaster("ALTER TABLE automation_continuation ADD COLUMN worker_id INT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX (worker_id)");
			
			// Backfill the trigger from the automation. Rows whose automation is gone keep '' → never resumable.
			$this->_db->ExecuteMaster("UPDATE automation_continuation ac INNER JOIN automation a ON a.name = ac.uri SET ac.extension_id = a.extension_id WHERE ac.extension_id = ''");
		}
	}
	
	private function patchAutomationContinuationStateAwait() : void {
		// ===========================================================================
		// `state_await`: the await SUB-STATE of a parked continuation, derived from `__return` when the record is
		// minted/updated -- the await-type key (form/interaction/duration/draft/record/queue). Answers READINESS:
		// is this parked somewhere a UI can re-enter. Pure record metadata -- never written back into automation
		// state. (An earlier revision appended `@resumable` here to carry a per-form opt-in; that suffix is gone,
		// stripped by patchAutomationContinuationResumeScope below.)
		
		if ($this->_revision < 1530) {
			list($columns,) = $this->_db->metaTable('automation_continuation');
			
			if (!array_key_exists('state_await', $columns))
				$this->_db->ExecuteMaster("ALTER TABLE automation_continuation ADD COLUMN state_await VARCHAR(32) NOT NULL DEFAULT '', ADD INDEX (state_await)");
		}
	}
	
	private function patchMailboxNumFails() : void {
		// ===========================================================================
		// `mailbox.num_fails` is a consecutive-failure counter that only ever increments from 0, so a signed
		// TINYINT wastes half its range on impossible negatives (and overflowed at 128). Make it unsigned.
		
		list($columns,) = $this->_db->metaTable('mailbox');
		
		if (array_key_exists('num_fails', $columns) && 'tinyint unsigned' != $columns['num_fails']['type']) {
			$this->_db->ExecuteMaster("UPDATE mailbox SET num_fails = 0 WHERE num_fails < 0");
			$this->_db->ExecuteMaster("ALTER TABLE mailbox MODIFY COLUMN num_fails TINYINT UNSIGNED NOT NULL DEFAULT 0");
		}
	}
	
	private function patchToolbarAgentPane() : void {
		// ===========================================================================
		// `agent.pane` toolbar — the toolbar an editor's agent pane (CerbUI.AgentPane) hosts to launch agent
		// interactions inline into its side pane. Seed the toolbar record only (no sections); its items are authored per
		// environment and gated by the `{{component}}` host state var. Idempotent (existence-guarded).
		
		if (!$this->_db->GetOneMaster("SELECT id FROM toolbar WHERE name = 'agent.pane'")) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO toolbar (name, extension_id, description, created_at, updated_at) VALUES (%s,%s,%s,%d,%d)",
				$this->_db->qstr('agent.pane'),
				$this->_db->qstr('cerb.toolbar.agent.pane'),
				$this->_db->qstr('Agent interactions available inside an editor agent pane'),
				time(),
				time()
			));
		}
	}
	
	private function patchTableAgentFilesystem() : void {
		// ===========================================================================
		// Agent filesystem: `agent_filesystem` (a named virtual volume of files) + `agent_file` (its files).
		// File bodies are inlined in the DB (`content`) for fast access -- no storage-engine get/put round-trips for
		// hot docs. `name` on agent_file holds the virtual path. Admin-managed for now (no import/terminal yet).
		
		if (!isset($this->_tables['agent_filesystem'])) {
			$this->_db->ExecuteMaster("
				CREATE TABLE `agent_filesystem` (
				`id` bigint unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL DEFAULT '',
				`description` varchar(255) NOT NULL DEFAULT '',
				`type` varchar(64) NOT NULL DEFAULT '',
				`is_disabled` tinyint(1) unsigned NOT NULL DEFAULT 0,
				`file_count` int unsigned NOT NULL DEFAULT 0,
				`total_bytes` int unsigned NOT NULL DEFAULT 0,
				`created_at` int unsigned NOT NULL DEFAULT 0,
				`updated_at` int unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `name` (`name`),
				INDEX `updated_at` (`updated_at`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
			") or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());
			$this->_logger->info("[Patch] Created 'agent_filesystem' table.");
			$this->_tables['agent_filesystem'] = 'agent_filesystem';
		}
	}
	
	private function patchTableAgentFile() : void {
		if (!isset($this->_tables['agent_file'])) {
			$this->_db->ExecuteMaster("
				CREATE TABLE `agent_file` (
				`id` bigint unsigned NOT NULL AUTO_INCREMENT,
				`filesystem_id` bigint unsigned NOT NULL DEFAULT 0,
				`name` varchar(1024) NOT NULL DEFAULT '',
				`file_extension` varchar(32) NOT NULL DEFAULT '',
				`frontmatter_json` text,
				`content` mediumtext,
				`sha1` varchar(40) CHARACTER SET ascii NOT NULL DEFAULT '',
				`size` int unsigned NOT NULL DEFAULT 0,
				`created_at` int unsigned NOT NULL DEFAULT 0,
				`updated_at` int unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `filesystem_path` (`filesystem_id`,`name`(700)),
				INDEX `filesystem_file_extension` (`filesystem_id`,`file_extension`),
				INDEX `updated_at` (`updated_at`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
			") or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());
			$this->_logger->info("[Patch] Created 'agent_file' table.");
			$this->_tables['agent_file'] = 'agent_file';
		}
		
		// `agent_file.file_extension` (derived from the path) for cheap `*.ext` filtering within a filesystem.
		// Idempotent guard for anyone who created the table before this column existed.
		
		if (isset($this->_tables['agent_file'])) {
			list($agent_file_columns,) = $this->_db->metaTable('agent_file');
			
			if (!array_key_exists('file_extension', $agent_file_columns)) {
				$this->_db->ExecuteMaster("ALTER TABLE agent_file ADD COLUMN file_extension VARCHAR(32) NOT NULL DEFAULT '' AFTER name, ADD INDEX filesystem_file_extension (filesystem_id, file_extension)");
			}
			
			// `import_uuid` stamps every row an import run touched, so a prune pass can delete whatever the
			// archive no longer contains. Cleared back to '' when the job finishes.
			if (!array_key_exists('import_uuid', $agent_file_columns)) {
				$this->_db->ExecuteMaster("ALTER TABLE agent_file ADD COLUMN import_uuid VARCHAR(36) NOT NULL DEFAULT '', ADD INDEX filesystem_import_uuid (filesystem_id, import_uuid)");
			}
		}
		
		// ===========================================================================
		// Default fulltext search index for agent files (path + body). Existence-guarded, so it's safe to re-run.
		// No index queue job is kicked off here -- a fresh install has no agent files to index yet.
		
		if (isset($this->_tables['search_index']) && !$this->_db->GetOneMaster(sprintf("SELECT id FROM search_index WHERE record_type = %s", $this->_db->qstr('agent_file')))) {
			$this->_db->ExecuteMaster(sprintf("INSERT INTO search_index (name, uri, record_type, record_filter, extension_id, extension_params_json, priority, created_at, updated_at) " .
				"VALUES (%s, %s, %s, %s, %s, %s, %d, %d, %d)",
				$this->_db->qstr('Agent Files'),
				$this->_db->qstr('agent.files'),
				$this->_db->qstr('agent_file'),
				$this->_db->qstr('text'),
				$this->_db->qstr('cerb.search.index.fulltext'),
				$this->_db->qstr(json_encode([
					'record_query' => '',
					'content' => "{{name}}\n\n{{content|strip_data_uris()|strip_pem_blocks()|strip_url_querystrings()}}",
				])),
				0,
				time(),
				time()
			));
			
			$this->_logger->info("[Patch] Created the default 'Agent Files' search index.");
		}
	}
	
	private function patchCreateAgentFilesystemWidgets() : void {
		// ===========================================================================
		// Default `Files` card widget for `cerb.contexts.agent.filesystem` (stats + ZIP import)
		
		if ($this->_revision < 1537 && !$this->_db->GetOneMaster("SELECT id FROM card_widget WHERE record_type='cerb.contexts.agent.filesystem' AND extension_id='cerb.card.widget.agent_filesystem'")) {
			$this->_db->ExecuteMaster(sprintf(
				"INSERT INTO card_widget (name, record_type, extension_id, extension_params_json, created_at, updated_at, pos, width_units, zone) " .
				"VALUES (%s, %s, %s, %s, %d, %d, %d, %d, %s)",
				$this->_db->qstr('Files'),
				$this->_db->qstr('cerb.contexts.agent.filesystem'),
				$this->_db->qstr('cerb.card.widget.agent_filesystem'),
				$this->_db->qstr(json_encode([
					"filesystem_id" => "{{record_id}}",
				])),
				time(), time(),
				2, 4,
				$this->_db->qstr('content')
			));
			
			$this->_logger->info("[Patch] Created the default 'Files' card widget for agent filesystems.");
		}
	}
	
	private function patchWorkerIsAi() : void {
		// ===========================================================================
		// `worker` gains `is_ai` — an AI worker is a first-class worker (assignable, @mentionable, ownable, holds
		// OAuth/API credentials) that can never log in interactively.
		//
		if ($this->_revision < 1541) {
			list($columns,) = $this->_db->metaTable('worker');
			
			if (!array_key_exists('is_ai', $columns))
				$this->_db->ExecuteMaster("ALTER TABLE worker ADD COLUMN is_ai TINYINT UNSIGNED NOT NULL DEFAULT 0");
		}
	}
	
	private function patchLlmAgentMessageFinishReason() : void {
		// ===========================================================================
		// `llm_agent_message` gains `finish_reason` — why the provider stopped generating, normalized across
		// providers (length|stop|tool_calls|filter, else the native token). Its own column for the same reason as
		// `usage_json`: `data_json` is replayed to the provider API verbatim, so nothing here can leak back into a
		// request. `length` means the turn hit its output ceiling and is truncated -- routinely with EMPTY content,
		// which was otherwise indistinguishable from a model that had nothing to say.

		list($columns,) = $this->_db->metaTable('llm_agent_message');

		if (!array_key_exists('finish_reason', $columns))
			$this->_db->ExecuteMaster("ALTER TABLE llm_agent_message ADD COLUMN finish_reason VARCHAR(32) NOT NULL DEFAULT ''");
	}

	private function patchAutomationContinuationResumeScope() : void {
		// ===========================================================================
		// `resume_scope`: WHERE a parked interaction may be reopened, and — by being non-empty at all — WHETHER
		// it may be. This replaces the per-form `await:form: resumable@bool: yes` opt-in (and the `@resumable`
		// suffix on `state_await`): the LAUNCHER decides, because it's the surface that knows whether resuming
		// there makes sense. The command bar supplies `commandbar`; an editor's agent sidebar supplies
		// `agent.pane:<component>`.
		//
		// A readable colon-delimited namespace rather than a hash, so a row's scope is obvious in the DB and a
		// launcher that later wants per-record pinning can append its own segment (`agent.pane:icon:123`) with
		// no schema change. Deliberately NOT keyed on the record: one chat follows the worker from one automation
		// editor to the next, which is the whole point.
		//
		// `state`/`state_await` keep answering the separate question of READINESS (is it parked somewhere a UI
		// can re-enter), which is why `queue` is now resumable too.

		list($columns,) = $this->_db->metaTable('automation_continuation');

		if (!array_key_exists('resume_scope', $columns)) {
			$this->_db->ExecuteMaster("ALTER TABLE automation_continuation ADD COLUMN resume_scope VARCHAR(64) NOT NULL DEFAULT '', ADD INDEX resume_scope (worker_id, resume_scope)");

			// Everything resumable today was launched from the global command bar (the only surface that offered
			// it), so adopt those rows rather than stranding open conversations mid-flight. Then drop the suffix
			// the new model doesn't use.
			$this->_db->ExecuteMaster("UPDATE automation_continuation SET resume_scope = 'commandbar' WHERE resume_scope = '' AND state_await LIKE '%@resumable'");
			$this->_db->ExecuteMaster("UPDATE automation_continuation SET state_await = REPLACE(state_await, '@resumable', '') WHERE state_await LIKE '%@resumable'");
		}
	}

	private function patchAutomationContinuationResumeFields() : void {
		// ===========================================================================
		// The resume DESCRIPTOR: how a parked conversation reads in the agent pane's History and the command bar.
		// Decoration, never a gate -- `resume_scope` alone answers whether a conversation is resumable, and it
		// fails closed for anything but the two conversational launchers (the command bar and an editor's agent
		// pane). These columns compose themselves from the launching toolbar item and the transcript; an optional
		// `await:form: resume:` block overrides a value where the author knows a better one.
		//
		// Split on purpose. `resume_label` is the conversation's name and gets its own column so a future
		// `/rename` can write it with a SINGLE-COLUMN statement: `invokePrompt` is deliberately read-only on the
		// continuation, which is what lets it run beside a live turn, and a read-modify-write would give that up.
		// Everything else (preview/icon/color) is display payload nothing filters or sorts on, so it rides in one
		// blob and its shape can keep moving without a migration per key.
		//
		// utf8mb4 per column, on an otherwise-utf8mb3 table: these hold a worker's own prose (and, later, an
		// LLM-generated title), and MySQL REJECTS a 4-byte character rather than truncating it -- so an emoji
		// would fail the write instead of shortening a string. Same treatment as `message.subject`.

		list($columns,) = $this->_db->metaTable('automation_continuation');

		if (!array_key_exists('resume_label', $columns)) {
			$this->_db->ExecuteMaster("ALTER TABLE automation_continuation ADD COLUMN resume_label VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''");
		}

		if (!array_key_exists('resume_metadata', $columns))
			$this->_db->ExecuteMaster("ALTER TABLE automation_continuation ADD COLUMN resume_metadata TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
	}

	private function patchTableAgentModel() : void {
		// ===========================================================================
		// `agent_model` — a first-class model record: provider + model string + auth + knobs, addressable as
		// `cerb:agent_model:<name>`. `name` IS the uri (alphanumeric/dash/underscore, unique), so a KATA reference
		// reads as the model's own handle. Models are DECOUPLED from personas: an anonymous "Agent" uses one just as
		// well as a named AI worker, and one edit here re-points every automation that references it.
		//
		// `params_kata` carries the open tail of the `llm:<provider>:` block (cache/effort/thinking/compaction/...)
		// in the SAME grammar, because that block is deliberately open -- a closed column list would force a schema
		// migration for every new provider knob.
		//
		// `label`/`icon`/`icon_color` are the DISPLAY override. Most models are reached over the OpenAI-compatible
		// API (llama.cpp, LM Studio, vLLM, z.ai, Qwen), so `provider` alone can't say which vendor a model IS --
		// a z.ai model on `provider: openai` would otherwise paint the OpenAI logo everywhere.

		if(!isset($this->_tables['agent_model'])) {
			$this->_db->ExecuteMaster("
				CREATE TABLE `agent_model` (
				`id` bigint unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(128) NOT NULL DEFAULT '',
				`label` varchar(128) NOT NULL DEFAULT '',
				`icon` varchar(64) NOT NULL DEFAULT '',
				`icon_color` varchar(32) NOT NULL DEFAULT '',
				`provider` varchar(32) NOT NULL DEFAULT '',
				`model` varchar(255) NOT NULL DEFAULT '',
				`api_endpoint_url` varchar(255) NOT NULL DEFAULT '',
				`connected_account_id` int unsigned NOT NULL DEFAULT 0,
				`has_vision` tinyint(1) unsigned NOT NULL DEFAULT 0,
				`has_thinking` tinyint(1) unsigned NOT NULL DEFAULT 0,
				`context_window` int unsigned NOT NULL DEFAULT 0,
				`rating_intelligence` tinyint unsigned NOT NULL DEFAULT 0,
				`rating_speed` tinyint unsigned NOT NULL DEFAULT 0,
				`rating_privacy` tinyint unsigned NOT NULL DEFAULT 0,
				`rating_cost` tinyint unsigned NOT NULL DEFAULT 0,
				`priority` tinyint unsigned NOT NULL DEFAULT 50,
				`params_kata` text,
				`status` tinyint unsigned NOT NULL DEFAULT 0,
				`created_at` int unsigned NOT NULL DEFAULT 0,
				`updated_at` int unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `name` (`name`),
				INDEX `provider` (`provider`),
				INDEX `updated_at` (`updated_at`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
			") or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());

			$this->_tables['agent_model'] = 'agent_model';

		} else {
			list($columns,) = $this->_db->metaTable('agent_model');

			$changes = [];

			if(!array_key_exists('label', $columns))
				$changes[] = "ADD COLUMN label varchar(128) NOT NULL DEFAULT '' AFTER name";

			if(!array_key_exists('icon', $columns))
				$changes[] = "ADD COLUMN icon varchar(64) NOT NULL DEFAULT '' AFTER description";

			if(!array_key_exists('icon_color', $columns))
				$changes[] = "ADD COLUMN icon_color varchar(32) NOT NULL DEFAULT '' AFTER icon";

			if(!array_key_exists('has_thinking', $columns))
				$changes[] = "ADD COLUMN has_thinking tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER has_vision";

			// Ordinal 10/20/30/40 tiers, 0 = unrated. Sparse so a tier can be inserted without a migration.
			foreach(['intelligence', 'speed', 'privacy', 'cost'] as $rating) {
				if(!array_key_exists('rating_' . $rating, $columns))
					$changes[] = sprintf("ADD COLUMN rating_%s tinyint unsigned NOT NULL DEFAULT 0", $rating);
			}

			// Three states replace the is_disabled bit: 0=available, 1=unlisted, 2=disabled.
			if(!array_key_exists('status', $columns))
				$changes[] = "ADD COLUMN status tinyint unsigned NOT NULL DEFAULT 0";

			if(!array_key_exists('priority', $columns))
				$changes[] = "ADD COLUMN priority tinyint unsigned NOT NULL DEFAULT 50";

			if($changes)
				$this->_db->ExecuteMaster("ALTER TABLE agent_model " . implode(', ', $changes));

			list($columns,) = $this->_db->metaTable('agent_model');

			// Carry the old bit over before dropping it, so a disabled model stays disabled.
			if(array_key_exists('is_disabled', $columns)) {
				$this->_db->ExecuteMaster("UPDATE agent_model SET status = 2 WHERE is_disabled = 1");
				$this->_db->ExecuteMaster("ALTER TABLE agent_model DROP COLUMN is_disabled");
			}

			// The capability and rating fields describe a model better than prose, and are queryable.
			if(array_key_exists('description', $columns))
				$this->_db->ExecuteMaster("ALTER TABLE agent_model DROP COLUMN description");
		}
	}

	private function patchDropTableAgentModelRouter() : void {
		// ===========================================================================
		// `agent_model_router` is REMOVED. Once a router sourced its members from a QUERY, the record was only a
		// NAME wrapped around one -- and the name was the fragile half: `hasVision:y` is Cerb's own vocabulary,
		// valid on every install, while `router: vision` demanded a record that had to exist everywhere, forever.
		//
		// Where each job the record did went instead:
		//   - a shared, named pool          -> a custom field on `agent_model` (`tier:gold`), just as opt-in
		//   - taking a model out of service -> `agent_model.status = unlisted`; one switch, every pool
		//   - a caller asking for a pool    -> `llm.router:` with `models_query/<name>:` capability queries
		//   - the install-wide default      -> every AVAILABLE model, in `agent_model.priority` order
		//
		// The table never shipped -- it was created in this same 12.0 patch, never in an 11.x one -- so it is
		// dropped outright rather than deprecated. Only development installs can have one.

		if(isset($this->_tables['agent_model_router'])) {
			$this->_db->ExecuteMaster("DROP TABLE IF EXISTS `agent_model_router`");
			unset($this->_tables['agent_model_router']);
		}
	}

	private function patchTableAgent() : void {
		// ===========================================================================
		// `agent` -- the AI-only config satellite for a worker. NOT a record type: an agent IS a worker
		// (`worker.is_ai`), one entity with one context, so it has no id of its own. `worker_id` is the PRIMARY
		// KEY, which both enforces 1:1 and means there's nothing to keep in sync -- the same shape as
		// `worker_auth_hash` and `worker_pref`.
		//
		// A satellite rather than columns on `worker` because `DAO_Worker::getAll()` caches every worker in one
		// blob (`ch_workers`): agent config is read on a handful of rows and would otherwise be serialized for
		// every human on every request. It also has room to grow -- system prompt, mounts, and token budget are
		// the next things that land here.
		//
		// Rows are created on demand (upsert), so a worker without one is simply an agent with no overrides.

		if(!isset($this->_tables['agent'])) {
			$this->_db->ExecuteMaster("
				CREATE TABLE `agent` (
				`worker_id` int unsigned NOT NULL,
				`created_at` int unsigned NOT NULL DEFAULT 0,
				`updated_at` int unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY (`worker_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
			") or die("[MySQL Error] " . $this->_db->ErrorMsgMaster());

			$this->_tables['agent'] = 'agent';

		} else {
			list($columns,) = $this->_db->metaTable('agent');

			// `model_router_id` went away with the router record. The TABLE stays: an agent is an identity, and
			// the columns already queued for it (system prompt, mounts, token budget) still want this row --
			// dropping and re-adding an unreleased table would be churn for nothing.
			if(array_key_exists('model_router_id', $columns))
				$this->_db->ExecuteMaster("ALTER TABLE agent DROP COLUMN model_router_id");
		}
	}

	private function patchLlmAgentMessageIsStreaming() : void {
		// ===========================================================================
		// `llm_agent_message` gains `is_streaming` — the row is an assistant turn that is STILL BEING WRITTEN.
		//
		// This is the one place the table stops being append-only. A streamed provider turn persists its content
		// as it arrives (throttled), so a reader in a different process — the transcript poll — can watch a long
		// turn progress instead of staring at a spinner, and so an interrupted or dropped turn keeps the tokens
		// we already paid for instead of discarding them.
		//
		// A FLAG rather than a scratch table because it buys the message a real uuid from the first delta: the
		// client patches that one node by id, the transcript renders it through the path it already uses, and
		// "keep the partial" is just clearing the flag.
		//
		// It is also load-bearing for correctness, not only display. `_sessionTurnAlreadyLanded()` — the
		// check-before-call guard that stops a retry from billing a second assistant turn — asks "is the head an
		// assistant message?". A half-written head would answer yes and make the guard lie, so that check now
		// requires assistant AND NOT streaming. Same for `is_in_progress` in the transcript, which would
		// otherwise flip false the instant the row appears and hide the Stop button mid-turn.
		//
		// A row left set (a worker killed mid-stream) is resolved on the session's NEXT turn rather than by a
		// reaper: finalize it if it has usable content, delete it if it's empty. An empty assistant head would
		// break the `head->role === 'assistant'` guards downstream.

		list($columns,) = $this->_db->metaTable('llm_agent_message');

		if(!array_key_exists('is_streaming', $columns))
			$this->_db->ExecuteMaster("ALTER TABLE llm_agent_message ADD COLUMN is_streaming TINYINT UNSIGNED NOT NULL DEFAULT 0");
	}

	private function patchRemoveToolbarWorklistSearch() : void {
		// ===========================================================================
		// Remove the `records.worklist.search` toolbar (superseded before it ever shipped)
		//
		// It was a generic interaction hook in the quick-search bar: an item launched an interaction that handed
		// back a `query` string for the field. The agent pane does that and more from the SAME toolbar the other
		// editors already use, so the search bar now renders `agent.pane` with `component: worklist` and the
		// second toolbar is gone. This only has to clean up installs that ran the 12.0 seed during development;
		// on a fresh install both DELETEs are no-ops.

		$this->_db->ExecuteMaster("DELETE FROM toolbar_section WHERE toolbar_name = 'records.worklist.search'");
		$this->_db->ExecuteMaster("DELETE FROM toolbar WHERE name = 'records.worklist.search'");

		$this->_logger->info("[Patch] Removed the 'records.worklist.search' toolbar.");
	}

	private function patchAutomationEventRecordBulkUpdate() : void {
		if(!$this->_db->GetOneMaster("SELECT 1 FROM automation_event WHERE name = 'record.bulkUpdate'")) {
			$this->_db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, updated_at) VALUES (%s,%s,%s,%d)',
				$this->_db->qstr('record.bulkUpdate'),
				$this->_db->qstr('cerb.trigger.record.bulkUpdate'),
				$this->_db->qstr('Fires for each batch of records during a bulk update. Also fires once at the start and once at the end.'),
				time()
			));
		}
	}

	private function patchLlmAgentSessionIsReadIndex() : void {
		// The transcript viewer tallies Active vs Archived on every list load. Without this the count is a
		// full scan of every session ever recorded, which is the one table that grows per agent turn.
		if(!array_key_exists('llm_agent_session', $this->_tables))
			return;
		
		list(, $indexes) = $this->_db->metaTable('llm_agent_session');
		
		if(!array_key_exists('is_read', $indexes))
			$this->_db->ExecuteMaster("ALTER TABLE llm_agent_session ADD INDEX is_read (is_read)");
	}
	
	private function patchWorkflowCerbAiAgent() : void {
		// Enable the built-in Cerb agent on upgrade (a fresh install gets it from install)
		if($this->_revision >= 1561)
			return;
		
		if($this->_db->GetOneMaster("SELECT id FROM workflow WHERE name = 'cerb.ai.agent'"))
			return;
		
		$this->_db->ExecuteMaster(sprintf(
			"INSERT INTO workflow (name, description, created_at, updated_at, version, workflow_kata, config_kata, has_extensions) ".
			"VALUES (%s, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, '', '', 0)",
			$this->_db->qstr('cerb.ai.agent')
		));
		
		$this->_logger->info("[Patch] Enabled the built-in Cerb agent workflow (cerb.ai.agent).");
	}
}

$patch = new CerbPatch_Core_v12_0_0();
return $patch->run();
