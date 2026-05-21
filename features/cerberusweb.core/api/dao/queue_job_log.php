<?php
/*
 * Per-batch audit log for queue jobs. Entries are written by the queue service
 * when consumers call reportSuccess()/reportFailure() with a non-empty message
 * or metadata array. Surfaced in the queue-job monitor widget (newest first).
 *
 * `level` is tinyint, forward-compatible with syslog-ish levels:
 *   0 INFO, 1 SUCCESS, 2 WARNING, 3 ERROR
 *
 * `metadata` is a JSON blob with consumer-specific shape — bulk_update stores
 * `{context, record_ids}` for audit/undo; import stores `{created, updated}`;
 * search.index and export are message-only (no metadata) because their work is
 * either idempotent (reindex) or has a durable artifact (the export file).
 */
class DAO_QueueJobLog extends Cerb_ORMHelper {
	const ID = 'id';
	const JOB_ID = 'job_id';
	const CREATED_AT = 'created_at';
	const LEVEL = 'level';
	const MESSAGE = 'message';
	const METADATA = 'metadata';

	/**
	 * Insert a batch of log entries in one statement. Each entry is an array:
	 *   ['job_id' => int, 'created_at' => int, 'level' => int, 'message' => string, 'metadata' => array|null]
	 */
	public static function insertBatch(array $entries) : void {
		if(!$entries) return;

		$db = DevblocksPlatform::services()->database();

		$rows = [];
		foreach($entries as $e) {
			$metadata = $e['metadata'] ?? null;
			$rows[] = sprintf(
				"(%d, %d, %d, %s, %s)",
				intval($e['job_id']),
				intval($e['created_at']),
				intval($e['level']),
				$db->qstr(mb_substr((string)($e['message'] ?? ''), 0, 1024)),
				$metadata ? $db->qstr(json_encode($metadata)) : "NULL"
			);
		}

		$db->ExecuteWriter(sprintf(
			"INSERT INTO queue_job_log (job_id, created_at, level, message, metadata) VALUES %s",
			implode(', ', $rows)
		));
	}

	/**
	 * Newest-first log entries for a job.
	 *
	 * @return Model_QueueJobLog[]
	 */
	public static function getByJobId(int $job_id, int $limit = 50) : array {
		$db = DevblocksPlatform::services()->database();

		$sql = sprintf(
			"SELECT id, job_id, created_at, level, message, metadata FROM queue_job_log " .
			"WHERE job_id = %d ORDER BY id DESC LIMIT %d",
			$job_id,
			max(1, $limit)
		);

		$results = $db->GetArrayReader($sql);

		return array_map(fn($row) => self::_getResultAsModel($row), $results);
	}

	public static function deleteByJobIds(array $job_ids) : void {
		$job_ids = DevblocksPlatform::sanitizeArray($job_ids, 'int');
		if(!$job_ids) return;

		$db = DevblocksPlatform::services()->database();
		$db->ExecuteWriter(sprintf(
			"DELETE FROM queue_job_log WHERE job_id IN (%s)",
			implode(',', $job_ids)
		));
	}

	private static function _getResultAsModel(array $row) : Model_QueueJobLog {
		$m = new Model_QueueJobLog();
		$m->id = intval($row['id']);
		$m->job_id = intval($row['job_id']);
		$m->created_at = intval($row['created_at']);
		$m->level = intval($row['level']);
		$m->message = (string)($row['message'] ?? '');
		$m->metadata = !empty($row['metadata']) ? json_decode($row['metadata'], true) : null;
		return $m;
	}
}

class Model_QueueJobLog {
	public int $id = 0;
	public int $job_id = 0;
	public int $created_at = 0;
	public int $level = 0;
	public string $message = '';
	public ?array $metadata = null;
}
