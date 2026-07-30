<?php
/**
 * The AI-only config satellite for a worker.
 *
 * **Deliberately NOT a record type.** An agent IS a worker (`worker.is_ai`) -- one entity, one context
 * (`cerberusweb.contexts.worker`), one id. Giving `agent` its own context would fragment links, comments,
 * custom fields, watchers, and the activity log across two context strings for the same thing. "Agents" as a
 * browsable list is a FILTERED WORKER VIEW (`worker:(isAi:y)`), not a separate type.
 *
 * So there's no Model_/SearchFields_/View_/Context_ here, and no `id` column: `worker_id` is the primary key,
 * the same shape as `worker_auth_hash` / `worker_pref`. Rows are created on demand -- a worker without one is
 * an agent with no overrides, which is the common case.
 */
class DAO_Agent extends Cerb_ORMHelper {
	const WORKER_ID = 'worker_id';
	const MODEL_ROUTER_ID = 'model_router_id';
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';

	private function __construct() {}

	/**
	 * Every agent row, keyed by worker id. Cached whole (there are few of them, and the model-routing path
	 * reads one on every agent turn).
	 *
	 * @return array `{worker_id => {model_router_id, created_at, updated_at}}`
	 */
	static function getAll($nocache=false) : array {
		$cache = DevblocksPlatform::services()->cache();

		if($nocache || null === ($rows = $cache->load(self::_CACHE_ALL))) {
			$db = DevblocksPlatform::services()->database();

			$rows = [];

			$results = $db->GetArrayMaster("SELECT worker_id, model_router_id, created_at, updated_at FROM agent") ?: [];

			foreach($results as $row)
				$rows[intval($row['worker_id'])] = [
					'model_router_id' => intval($row['model_router_id']),
					'created_at' => intval($row['created_at']),
					'updated_at' => intval($row['updated_at']),
				];

			$cache->save($rows, self::_CACHE_ALL);
		}

		return $rows;
	}

	const _CACHE_ALL = 'cerb:agents:all';

	static function clearCache() : void {
		DevblocksPlatform::services()->cache()->remove(self::_CACHE_ALL);
	}

	/**
	 * @return array `{model_router_id, created_at, updated_at}` -- zeroed defaults when the worker has no row,
	 *               so callers never branch on existence.
	 */
	static function get(int $worker_id) : array {
		return self::getAll()[$worker_id] ?? [
			'model_router_id' => 0,
			'created_at' => 0,
			'updated_at' => 0,
		];
	}

	/** The router this agent selects models through. 0 = none configured → the caller falls back. */
	static function getModelRouterId(int $worker_id) : int {
		return intval(self::get($worker_id)['model_router_id']);
	}

	/**
	 * Create-or-update. There is no `create()` -- a satellite row has no independent existence, so callers
	 * shouldn't have to know whether one exists yet.
	 */
	static function upsert(int $worker_id, array $fields) : void {
		if(!$worker_id)
			return;

		$db = DevblocksPlatform::services()->database();

		$db->ExecuteMaster(sprintf(
			"INSERT INTO agent (worker_id, model_router_id, created_at, updated_at) VALUES (%d, %d, %d, %d) ".
			"ON DUPLICATE KEY UPDATE model_router_id = VALUES(model_router_id), updated_at = VALUES(updated_at)",
			$worker_id,
			intval($fields[self::MODEL_ROUTER_ID] ?? 0),
			time(),
			time()
		));

		self::clearCache();
	}

	/** Called from DAO_Worker::delete() -- the satellite has no meaning without its worker. */
	static function deleteByWorkerIds(array $worker_ids) : void {
		if(!($worker_ids = DevblocksPlatform::sanitizeArray($worker_ids, 'int')))
			return;

		$db = DevblocksPlatform::services()->database();

		$db->ExecuteMaster(sprintf("DELETE FROM agent WHERE worker_id IN (%s)", implode(',', $worker_ids)));

		self::clearCache();
	}
};
