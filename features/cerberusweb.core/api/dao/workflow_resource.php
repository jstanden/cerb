<?php
/*
 * Pivot table hoisted from `workflow.resources_kata` so the records a workflow manages can be
 * queried — most importantly in reverse ("is this record managed by a workflow?"). The blob in
 * `resources_kata` can't answer that without parsing every workflow.
 *
 * Kept deliberately minimal: raw `$db` SQL, no context/search/View/ORM-abstract machinery. The
 * forward map (workflow -> its records) and the reverse map (record -> its workflow) are both
 * derived from one cached snapshot of the whole table. Even ~1000 workflows is only ~100KB, and
 * workflows change rarely, so the snapshot is cached across requests and invalidated only when a
 * workflow is imported/changed/deleted (via DAO_Workflow::clearCache). After the first lookup it's
 * free for the rest of the request.
 */
class DAO_WorkflowResource extends Cerb_ORMHelper {
	const _CACHE_MAP = 'workflow_resources_map';

	private static $_map = null;

	private function __construct() {}

	/**
	 * Replace a workflow's pivot rows from its parsed `resources['records']` map.
	 *
	 * @param int $workflow_id
	 * @param array $records ['<record_type>/<record_alias>' => record_id]
	 */
	static function setByWorkflow(int $workflow_id, array $records) : void {
		$db = DevblocksPlatform::services()->database();

		if(!$workflow_id)
			return;

		$db->ExecuteMaster(sprintf("DELETE FROM workflow_resource WHERE workflow_id = %d", $workflow_id));

		$values = [];

		foreach($records as $record_key => $record_id) {
			$record_type = DevblocksPlatform::services()->string()->strBefore($record_key, '/');
			$record_alias = DevblocksPlatform::services()->string()->strAfter($record_key, '/');

			if('' == $record_type || '' == $record_alias)
				continue;

			$values[] = sprintf("(%d, %s, %s, %d)",
				$workflow_id,
				$db->qstr($record_type),
				$db->qstr($record_alias),
				intval($record_id)
			);
		}

		if($values) {
			$db->ExecuteMaster("INSERT INTO workflow_resource (workflow_id, record_type, record_alias, record_id) VALUES " .
				implode(', ', $values));
		}

		self::clearCache();
	}

	/**
	 * @param int[] $ids
	 */
	static function deleteByWorkflowIds(array $ids) : void {
		$db = DevblocksPlatform::services()->database();

		$ids = DevblocksPlatform::sanitizeArray($ids, 'int', ['nonzero', 'unique']);

		if(empty($ids))
			return;

		$db->ExecuteMaster(sprintf("DELETE FROM workflow_resource WHERE workflow_id IN (%s)",
			implode(',', $ids)
		));

		self::clearCache();
	}

	/**
	 * Load the whole table once into forward + reverse maps. Cached per-request (static) and across
	 * requests (platform cache).
	 *
	 * @return array{reverse:array<string,int>, forward:array<int,array<string,int>>}
	 */
	static function getMaps() : array {
		$cache = DevblocksPlatform::services()->cache();

		if(null !== self::$_map)
			return self::$_map;

		if(null === ($maps = $cache->load(self::_CACHE_MAP))) {
			$db = DevblocksPlatform::services()->database();

			$maps = [
				'reverse' => [], // "<context_id>:<record_id>" => workflow_id
				'forward' => [], // workflow_id => ['<record_type>/<record_alias>' => record_id]
			];

			$rows = $db->GetArrayReader("SELECT workflow_id, record_type, record_alias, record_id FROM workflow_resource");

			// resources_kata keys use the short URI alias (e.g. `toolbar_section`), but reverse-lookup
			// callers pass the full context id (Context_X::ID). Normalize the alias to its context id so
			// the reverse key matches. Memoized per distinct alias (the alias map is platform-cached).
			$ctx_by_alias = [];

			foreach($rows as $row) {
				$workflow_id = intval($row['workflow_id']);
				$record_type = $row['record_type'];
				$record_alias = $row['record_alias'];
				$record_id = intval($row['record_id']);

				if(!array_key_exists($record_type, $ctx_by_alias)) {
					$ctx = Extension_DevblocksContext::getByAlias($record_type, false);
					$ctx_by_alias[$record_type] = ($ctx instanceof DevblocksExtensionManifest) ? $ctx->id : $record_type;
				}

				$maps['reverse'][$ctx_by_alias[$record_type] . ':' . $record_id] = $workflow_id;
				$maps['forward'][$workflow_id][$record_type . '/' . $record_alias] = $record_id;
			}

			$cache->save($maps, self::_CACHE_MAP);
		}

		self::$_map = $maps;

		return $maps;
	}

	/**
	 * @return int The managing workflow id, or 0 if the record isn't workflow-managed.
	 */
	static function getWorkflowIdByRecord(string $record_type, int $record_id) : int {
		$maps = self::getMaps();
		return intval($maps['reverse'][$record_type . ':' . $record_id] ?? 0);
	}

	static function clearCache() : void {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_MAP);
		self::$_map = null;
	}
}
