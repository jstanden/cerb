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
	const CONFIG_KATA = 'config_kata';
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';

	private function __construct() {}

	/**
	 * Every agent row, keyed by worker id. Cached whole (there are few of them, and the agent-pane launcher
	 * list plus every agent turn read them).
	 *
	 * The PARSED `config_kata` rides along under `config`. Parsing is the expensive part and the result is the
	 * same for every reader in the request, so caching the tree rather than the text is what keeps
	 * `Cerb\Agent\Config::getEnabledAgents()` cheap enough to call on every pane render. The raw text is kept
	 * too -- the editor needs what was typed, not what it parsed to.
	 *
	 * @return array `{worker_id => {config_kata, config, created_at, updated_at}}`
	 */
	static function getAll($nocache=false) : array {
		$cache = DevblocksPlatform::services()->cache();

		if($nocache || null === ($rows = $cache->load(self::_CACHE_ALL))) {
			$db = DevblocksPlatform::services()->database();

			$rows = [];

			$results = $db->GetArrayMaster("SELECT worker_id, config_kata, created_at, updated_at FROM agent") ?: [];

			foreach($results as $row)
				$rows[intval($row['worker_id'])] = [
					'config_kata' => strval($row['config_kata'] ?? ''),
					'config' => \Cerb\Agent\Config::parse($row['config_kata'] ?? ''),
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
	 * @return array `{config_kata, config, created_at, updated_at}` -- zeroed defaults when the worker has no
	 *               row, so callers never branch on existence.
	 */
	static function get(int $worker_id) : array {
		return self::getAll()[$worker_id] ?? [
			'config_kata' => '',
			'config' => [],
			'created_at' => 0,
			'updated_at' => 0,
		];
	}

	/** The parsed config tree alone -- what `Cerb\Agent\Config::resolve()` takes. */
	static function getConfig(int $worker_id) : array {
		return self::get($worker_id)['config'] ?? [];
	}

	/**
	 * Create-or-touch. There is no `create()` -- a satellite row has no independent existence, so callers
	 * shouldn't have to know whether one exists yet.
	 *
	 * `$fields` is the writable column map (only `config_kata` today). Omitting it touches `updated_at` and
	 * nothing else, which is what a caller flipping a worker to AI wants: the row exists, the config is
	 * whatever it already was.
	 */
	static function upsert(int $worker_id, array $fields = []) : void {
		if(!$worker_id)
			return;

		$db = DevblocksPlatform::services()->database();

		$cols = ['worker_id', 'created_at', 'updated_at'];
		$vals = [$worker_id, time(), time()];
		$updates = ['updated_at = VALUES(updated_at)'];

		if(array_key_exists(self::CONFIG_KATA, $fields)) {
			$cols[] = 'config_kata';
			$vals[] = $db->qstr(strval($fields[self::CONFIG_KATA]));
			$updates[] = 'config_kata = VALUES(config_kata)';
		}

		$db->ExecuteMaster(sprintf(
			"INSERT INTO agent (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s",
			implode(', ', $cols),
			implode(', ', $vals),
			implode(', ', $updates)
		));

		self::clearCache();
	}

	/**
	 * Write `config_kata`, validated against the same schema the editor autocompletes from.
	 *
	 * Validation lives HERE rather than only in the peek's save so every writer passes through it -- an import,
	 * a package, or a future automation command can't plant a config the reader will silently ignore. A blank
	 * config is legal: it means an agent with no overrides.
	 */
	static function setConfigKata(int $worker_id, string $config_kata, ?string &$error = null) : bool {
		if(!$worker_id)
			return false;

		$config_kata = trim($config_kata);

		if('' !== $config_kata) {
			$kata = DevblocksPlatform::services()->kata();

			if(false === $kata->validate($config_kata, CerberusApplication::kataSchemas()->agent(), $error))
				return false;
		}

		self::upsert($worker_id, [self::CONFIG_KATA => $config_kata]);

		return true;
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
