<?php
namespace Cerb\Records;

use DAO_DevblocksStorageProfile;
use DAO_Queue;
use DAO_QueueJob;
use DAO_QueueMessage;
use DevblocksPlatform;
use Extension_DevblocksStorageEngine;
use Extension_DevblocksStorageSchema;
use Model_Queue;
use Model_QueueJob;
use Model_QueueMessage;
use QueueJobStatus;
use QueueMessageStatus;

/**
 * Producer and consumer for the `cerb.storage.migrations` queue. Moves stored objects between
 * storage profiles in the background. The queue serves admin migrations
 * (`enqueueProfileMigration` → `createJob`): move an entire source profile's objects as one
 * tracked, progress-reporting job. (Incremental lifecycle archival no longer uses this queue — each
 * schema's `Extension_DevblocksStorageSchema::archive()` does a synchronous cursor-paced sweep from
 * `cron.storage`, like the search indexer.)
 *
 * Each `migrate` message carries an explicit source + destination, so draining is idempotent. The
 * per-object move lives on `Extension_DevblocksStorageSchema::migrateObjectsToProfile()` (framework
 * layer); this class only orchestrates the queue (and the admin job's lifecycle).
 */
class StorageMigration {
	const string QUEUE_NAME = 'cerb.storage.migrations';
	const int BATCH_SIZE = 100;

	public static function getMigrateSingletonKey(string $schema_id, string $src_extension, int $src_profile_id) : string {
		return 'storage.migrate:' . sha1(sprintf('%s:%s:%d', $schema_id, $src_extension, $src_profile_id));
	}

	/**
	 * Create the singleton migration job and bulk-produce its `migrate` messages from an inner
	 * `SELECT id ...` (which may include JOINs/WHERE). Returns the existing open job unchanged if
	 * one already matches the singleton key. `$src`/`$dst` are resolved
	 * `['extension'=>.., 'profile_id'=>..]` locations.
	 */
	public static function createJob(string $schema_id, string $name, string $singleton_key, int $worker_id, string $inner_select_sql, array $dst, ?string &$error=null, ?array $src=null) : ?Model_QueueJob {
		$db = DevblocksPlatform::services()->database();

		if(!($queue = DAO_Queue::getByName(self::QUEUE_NAME))) {
			$error = 'The storage migrations queue is not registered';
			return null;
		}

		$record_count = intval($db->GetOneMaster(sprintf("SELECT COUNT(*) FROM (%s) AS c", $inner_select_sql)));

		if($record_count < 1) {
			$error = 'There are no objects to migrate';
			return null;
		}

		// Pre-flight: don't queue (potentially hundreds of) messages for a destination
		// that's down or misconfigured — every one would just fail into retry backoff.
		if(!self::_isDestinationWritable($schema_id, $dst, $error))
			return null;

		$model = new Model_QueueJob();
		$model->queue_id = $queue->id;
		$model->name = $name;
		$model->singleton_key = $singleton_key;
		$model->count_total = $record_count;
		$model->status_id = QueueJobStatus::RUNNING->value;
		$model->worker_id = $worker_id;
		$model->metadata = [
			'schema' => $schema_id,
			'src' => $src,
			'dst' => $dst,
			'record_count' => $record_count,
		];

		if(!($job = DAO_QueueJob::create($model))) {
			$error = 'Failed to create the migration job';
			return null;
		}

		// Singleton dedupe: create() hands back an already-open job for this key. If it already
		// has messages, don't enqueue a second set — return the existing job as-is.
		if($db->GetOneMaster(sprintf("SELECT 1 FROM queue_message WHERE job_id = %d LIMIT 1", $job->id)))
			return $job;

		self::_insertMigrateMessages($queue->id, $job->id, self::_migratePrefix($schema_id, $src, $dst), $inner_select_sql);

		return $job;
	}

	/**
	 * Enqueue one fire-and-forget (`job_id = 0`) `migrate` message for an already-known set of ids.
	 * Used by the incremental producer (`Extension_DevblocksStorageSchema::archive()`) and by the
	 * consumer's failure re-enqueue. The message carries an explicit resolved source + destination, so
	 * draining is idempotent (a row already off `$src` is skipped, not re-moved).
	 *
	 * @param array{extension:string,profile_id:int} $src resolved source
	 * @param array{extension:string,profile_id:int} $dst resolved destination
	 * @param int[] $ids
	 * @param int $available_at when the message becomes claimable (0 = immediately)
	 */
	public static function enqueueMigrateBatch(string $schema_id, array $src, array $dst, array $ids, int $available_at=0) : void {
		if(!$ids)
			return;

		if(!($queue = DAO_Queue::getByName(self::QUEUE_NAME)))
			return;

		$message = [
			'action' => 'migrate',
			'schema' => $schema_id,
			'src' => ['extension' => strval($src['extension']), 'profile_id' => intval($src['profile_id'])],
			'dst' => ['extension' => strval($dst['extension']), 'profile_id' => intval($dst['profile_id'])],
			'ids' => array_values($ids),
		];

		DAO_QueueMessage::enqueue($queue, [$message], 0, $available_at, count($ids));
	}

	/**
	 * The constant JSON prefix shared by every `migrate` message of one production run; CONCAT only
	 * varies the id list per batch. The resolved source (when known) makes draining idempotent — a row
	 * that already moved off it is skipped rather than re-moved.
	 *
	 * @param array{extension:string,profile_id:int}|null $src
	 * @param array{extension:string,profile_id:int} $dst
	 */
	private static function _migratePrefix(string $schema_id, ?array $src, array $dst) : string {
		$src_json = $src
			? sprintf('"src":%s,', json_encode(['extension' => strval($src['extension']), 'profile_id' => intval($src['profile_id'])]))
			: ''
			;

		$dst_json = json_encode(['extension' => strval($dst['extension']), 'profile_id' => intval($dst['profile_id'])]);

		return sprintf('{"action":"migrate","schema":%s,%s"dst":%s,"ids":[',
			json_encode($schema_id),
			$src_json,
			$dst_json
		);
	}

	/**
	 * Bulk-produce batched `migrate` messages from an inner `SELECT id ...` (which may include
	 * JOINs/WHERE) in one INSERT…SELECT: rows are numbered and grouped into batches of BATCH_SIZE,
	 * each becoming one message whose id list is GROUP_CONCAT'd into the constant `$prefix`.
	 */
	private static function _insertMigrateMessages(int $queue_id, int $job_id, string $prefix, string $inner_select_sql) : void {
		$db = DevblocksPlatform::services()->database();

		// Large id batches can exceed the default GROUP_CONCAT cap and silently truncate the JSON
		$db->ExecuteMaster("SET SESSION group_concat_max_len = 1000000");

		$db->ExecuteMaster(sprintf(
			"INSERT INTO queue_message (uuid, queue_id, job_id, status_id, created_at, message, cardinality) ".
			"SELECT UUID_TO_BIN(UUID()), %d, %d, 0, UNIX_TIMESTAMP(), ".
			"CONCAT(%s, GROUP_CONCAT(id ORDER BY id), %s), ".
			"COUNT(id) ".
			"FROM (SELECT id, CEIL(ROW_NUMBER() OVER (ORDER BY id) / %d) AS batch FROM (%s) AS s) AS batched ".
			"GROUP BY batch",
			$queue_id,
			$job_id,
			$db->qstr($prefix),
			$db->qstr(']}'),
			self::BATCH_SIZE,
			$inner_select_sql
		));
	}

	/**
	 * Admin producer: move every object of `(schema, src engine+profile)` to `$dst`.
	 *
	 * @param int|string $dst destination profile id, or engine extension id (profile_id 0)
	 */
	public static function enqueueProfileMigration(string $schema_id, string $src_extension, int $src_profile_id, int|string $dst, int $worker_id=0, ?string &$error=null) : ?Model_QueueJob {
		$db = DevblocksPlatform::services()->database();

		if(!(($schema = DevblocksPlatform::getExtension($schema_id, true)) instanceof Extension_DevblocksStorageSchema)) {
			$error = 'Invalid storage schema';
			return null;
		}
		
		/* @var $schema Extension_DevblocksStorageSchema */

		if(!($table = $schema->getStorageTableName())) {
			$error = 'This storage schema does not support migration';
			return null;
		}

		if(!($dst_resolved = Extension_DevblocksStorageSchema::resolveProfileConfig($dst))) {
			$error = 'Invalid destination';
			return null;
		}

		if($dst_resolved['extension'] === $src_extension && $dst_resolved['profile_id'] === $src_profile_id) {
			$error = 'The source and destination are the same';
			return null;
		}

		$inner = sprintf("SELECT id FROM %s WHERE storage_extension = %s AND storage_profile_id = %d",
			$db->escape($table),
			$db->qstr($src_extension),
			$src_profile_id
		);

		// 'Migrate {schema}: {src} -> {dst}'
		$name = sprintf("Migrate %s: %s -> %s",
			$schema->manifest->name,
			self::label($src_extension, $src_profile_id),
			self::label($dst_resolved['extension'], $dst_resolved['profile_id'])
		);

		return self::createJob(
			$schema_id,
			$name,
			self::getMigrateSingletonKey($schema_id, $src_extension, $src_profile_id),
			$worker_id,
			$inner,
			$dst_resolved,
			$error,
			['extension' => $src_extension, 'profile_id' => $src_profile_id]
		);
	}

	/**
	 * Queue consumer: drain `migrate` messages, moving each batch's objects to its destination.
	 * Dispatched from `QueueConsumer_Internal` for the `cerb.storage.migrations` queue.
	 */
	public static function processQueue(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job) : int {
		$queue_service = DevblocksPlatform::services()->queue();

		$processed = 0;
		$claim_id = null;
		$job_id = $queue_job?->id ?? null;
		$schemas = [];

		// One message at a time (each already carries up to BATCH_SIZE ids) but loop so
		// concurrent workers can interleave on the same job within the $stop_time budget.
		while($stop_time > time()) {
			if(!($messages = $queue_service->dequeue($queue->name, 1, $claim_id, $job_id)))
				break;

			foreach($messages as $message) {
				$action = $message->message['action'] ?? '';

				if($action === 'delete') {
					self::_processDeleteMessage($queue, $message);
					$processed += $message->cardinality;
					continue;
				}

				if($action !== 'migrate' || !($schema_id = $message->message['schema'] ?? '')) {
					$message->reportStatus(QueueMessageStatus::FAILED, sprintf('Invalid storage queue message (action: %s)', $action));
					continue;
				}

				$ids = $message->message['ids'] ?? [];
				$dst = $message->message['dst'] ?? null;

				// The resolved source lets us skip a row that already moved off it (idempotent)
				$src = $message->message['src'] ?? null;
				$src = (is_array($src) && isset($src['extension']))
					? ['extension' => strval($src['extension']), 'profile_id' => intval($src['profile_id'])]
					: null;

				if(!array_key_exists($schema_id, $schemas))
					$schemas[$schema_id] = DevblocksPlatform::getExtension($schema_id, true);

				$schema = $schemas[$schema_id];

				if(!($schema instanceof Extension_DevblocksStorageSchema)) {
					$message->reportStatus(QueueMessageStatus::FAILED, sprintf('Unknown storage schema: %s', $schema_id));
					continue;
				}

				$result = [];
				$ok = $schema->migrateObjectsToProfile($ids, $dst, $result, $src);

				if($ok) {
					$message->reportStatus(QueueMessageStatus::DONE, sprintf('Migrated %d, skipped %d', $result['done'], $result['skipped']));
				} else {
					// Report the whole message as failed; the queue's retry policy (retry_max/
					// retry_window_secs) decides whether it's re-driven with a backoff or goes
					// terminal. Re-running is idempotent — migrateObjectsToProfile() skips a row
					// already off $src — so each retry only re-attempts the still-failed ids.
					// Make the log say so: "retrying N in ~Xs" until retries are exhausted.
					$message->reportStatus(QueueMessageStatus::FAILED, self::_failureLog(
						$queue, $message,
						sprintf('Migrated %d, skipped %d', $result['done'], $result['skipped']),
						$result['failed']
					));
				}

				$processed += $message->cardinality;
			}
		}

		return $processed;
	}

	/**
	 * Drain a batched `delete` message: delete the keys via the engine's batchDelete (native S3
	 * DeleteObjects / gatekeeper multi-object). Any keys not confirmed deleted fail the whole
	 * message; the queue's retry policy re-drives it with a backoff (re-running is idempotent).
	 */
	private static function _processDeleteMessage(Model_Queue $queue, Model_QueueMessage $message) : void {
		$ns = $message->message['ns'] ?? '';
		$ext = $message->message['ext'] ?? '';
		$profile = intval($message->message['profile'] ?? 0);
		$keys = $message->message['keys'] ?? [];

		if(!$ns || !$ext || !is_array($keys) || !$keys) {
			$message->reportStatus(QueueMessageStatus::FAILED, 'Invalid storage delete message');
			return;
		}

		if(!($engine = DevblocksPlatform::getStorageService($profile ?: $ext))) {
			$message->reportStatus(QueueMessageStatus::FAILED, sprintf('Unknown storage engine: %s', $ext));
			return;
		}

		// Verify before delete: drop any key a live record still references on this engine/profile (its
		// bytes are still in use). This protects against a stale delete whose deterministic key was
		// re-written by a later migration — better to leave orphaned bytes than destroy live content.
		$table = Extension_DevblocksStorageSchema::getTableForNamespace($ns) ?? '';
		$keys = Extension_DevblocksStorageSchema::filterDeletableKeys($table, $ext, $profile, $keys);

		if(!$keys) {
			$message->reportStatus(QueueMessageStatus::DONE, 'Nothing to delete (keys still referenced)');
			return;
		}

		$deleted = $engine->batchDelete($ns, $keys);

		// Normalize: array = keys deleted; false = none; anything else (true/void) = all
		if(is_array($deleted))
			$deleted_keys = $deleted;
		else
			$deleted_keys = ($deleted === false) ? [] : $keys;

		$failed = array_values(array_diff($keys, $deleted_keys));

		if($failed) {
			// Report failure for the whole message; the queue's retry policy decides whether to
			// re-drive it with a backoff or go terminal. Re-running is idempotent — already-deleted
			// keys are absent (batchDelete no-ops) and still-referenced keys are filtered out above.
			$message->reportStatus(QueueMessageStatus::FAILED, self::_failureLog(
				$queue, $message,
				sprintf('Deleted %d', count($deleted_keys)),
				count($failed)
			));
		} else {
			$message->reportStatus(QueueMessageStatus::DONE, sprintf('Deleted %d', count($deleted_keys)));
		}
	}

	/**
	 * Pre-flight reachability check for a migration destination. Returns false and sets `$error`
	 * when the destination is unreachable/not writable, so the caller can refuse to queue work.
	 *
	 * We deliberately do NOT call Extension_DevblocksStorageEngine::testConfig(): the S3 engine's
	 * testConfig reads its bucket/host/region from `$_POST` (the profile-edit form), so it only
	 * works from the storage settings page — in a migration request those fields are absent and it
	 * fails. Instead we build the engine from its SAVED config via getStorageService() (profile
	 * params + decrypted credentials) and probe writability exactly the way the migration will:
	 * write a tiny object into the schema's namespace and delete it. id 0 can never collide with a
	 * real record (ids are positive), and using the schema's real namespace avoids creating a stray
	 * `storage_*` table on the database engine.
	 *
	 * @param array{extension:string,profile_id:int} $dst
	 */
	private static function _isDestinationWritable(string $schema_id, array $dst, ?string &$error=null) : bool {
		$extension_id = strval($dst['extension'] ?? '');
		$profile_id = intval($dst['profile_id'] ?? 0);
		$label = self::label($extension_id, $profile_id);

		// A profile id resolves the saved params + credentials; a bare engine id (profile_id 0)
		// builds the engine with its defaults (disk/database/cerb-cloud).
		$engine = DevblocksPlatform::getStorageService($profile_id ?: $extension_id);

		if(!($engine instanceof Extension_DevblocksStorageEngine)) {
			$error = sprintf('The destination (%s) is unavailable', $label);
			return false;
		}

		$namespace = '';
		if(($schema = DevblocksPlatform::getExtension($schema_id, true)) instanceof Extension_DevblocksStorageSchema)
			$namespace = $schema->getStorageNamespace();

		// No namespace to probe — the engine resolved, which is all we can verify here.
		if($namespace === '')
			return true;

		try {
			// DB engine put() returns the (falsy) id 0, so check strictly against false.
			if(false === ($probe_key = $engine->put($namespace, 0, 'CERB'))) {
				$error = sprintf('The destination (%s) is unreachable or not writable', $label);
				return false;
			}

			$engine->delete($namespace, $probe_key);

		} catch(\Throwable $e) {
			$error = sprintf('The destination (%s) is unreachable: %s', $label, $e->getMessage());
			return false;
		}

		return true;
	}

	/**
	 * Build a retry-aware failure log line. The queue's retry policy decides (via the shared
	 * disposition helper) whether this failure will be re-driven with a backoff or is terminal,
	 * so the log reads "…, retrying N in ~Xs (retry A of B)" until retries are exhausted, then
	 * "…, failed N (no retries left)". `$prefix` is the per-action summary (e.g. "Migrated 0,
	 * skipped 0" or "Deleted 0"); `$failed` is the count being retried/given up on.
	 */
	private static function _failureLog(Model_Queue $queue, Model_QueueMessage $message, string $prefix, int $failed) : string {
		$disposition = \_DevblocksQueueService::getRetryDisposition($message->retry_count, $queue->retry_max, $queue->retry_window_secs, time());

		if($disposition['will_retry'])
			return sprintf('%s, retrying %d in ~%s (retry %d of %d)',
				$prefix,
				$failed,
				DevblocksPlatform::strSecsToString($disposition['backoff_secs'], 1),
				$disposition['next_retry_count'],
				$queue->retry_max
			);

		return sprintf('%s, failed %d (no retries left)', $prefix, $failed);
	}

	/**
	 * A human label for a storage location — the profile name, else the engine name.
	 */
	public static function label(string $extension_id, int $profile_id) : string {
		if($profile_id && ($profile = DAO_DevblocksStorageProfile::get($profile_id)))
			return $profile->name;

		if(($manifest = DevblocksPlatform::getExtension($extension_id, false)))
			return $manifest->name;

		return $extension_id;
	}
}
