<?php
class DAO_BotInteractionProactive extends Cerb_ORMHelper {
	const ACTOR_BOT_ID = 'actor_bot_id';
	const WORKER_ID = 'worker_id';
	const INTERACTION = 'interaction';
	const INTERACTION_PARAMS_JSON = 'interaction_params_json';
	const BEHAVIOR_ID = 'behavior_id';
	const RUN_AT = 'run_at';
	const UPDATED_AT = 'updated_at';
	const EXPIRES_AT = 'expires_at';
	
	static function create($worker_id, $behavior_id, $interaction, array $interaction_params=[], $actor_bot_id, $expires_at=0, $run_at=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($run_at))
			$run_at = time();
		
		$sql = sprintf("INSERT INTO bot_interaction_proactive (worker_id, behavior_id, interaction, interaction_params_json, run_at, updated_at, actor_bot_id, expires_at) ".
			"VALUES (%d, %d, %s, %s, %d, %d, %d, %d)",
			$worker_id,
			$behavior_id,
			$db->qstr($interaction),
			$db->qstr(json_encode($interaction_params)),
			$run_at,
			time(),
			$actor_bot_id,
			$expires_at
		);
		
		if(false == $db->ExecuteMaster($sql))
			return false;
		
		self::clearCountByWorker($worker_id);
		
		$id = $db->LastInsertId();
		return $id;
	}
	
	static function getCountByWorker($worker_id) {
		$cache = DevblocksPlatform::getCacheService();
		$cache_key = sprintf("interactions_proactive_count_%d", $worker_id);
		
		if(NULL === ($count = $cache->load($cache_key))) {
			$db = DevblocksPlatform::getDatabaseService();
			$count = $db->GetOneSlave(sprintf("SELECT COUNT(*) FROM bot_interaction_proactive WHERE worker_id = %d AND (expires_at = 0 OR expires_at > %d)", $worker_id, time()));
			$cache->save($count, $cache_key, [], 3600);
		}
		
		return $count;
	}
	
	static function getByWorker($worker_id, $limit=1) {
		$limit = DevblocksPlatform::intClamp($limit, 1, 100);
		
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT id, worker_id, actor_bot_id, behavior_id, interaction, interaction_params_json FROM bot_interaction_proactive WHERE worker_id = %d AND (expires_at = 0 OR expires_at > %d) ORDER BY id LIMIT %d", $worker_id, time(), $limit);
		
		return $db->GetArraySlave($sql);
	}
	
	static function clearCountByWorker($worker_id) {
		$cache = DevblocksPlatform::getCacheService();
		$cache_key = sprintf("interactions_proactive_count_%d", $worker_id);
		$cache->remove($cache_key);
	}
	
	static function delete($id, $worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->ExecuteMaster(sprintf("DELETE FROM bot_interaction_proactive WHERE id = %d AND worker_id = %d", $id, $worker_id));
		self::clearCountByWorker($worker_id);
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Delete any expired keys (0=forever)
		$sql = sprintf("SELECT id, worker_id FROM bot_interaction_proactive WHERE expires_at BETWEEN 1 AND %d LIMIT 250",
			time()
		);
		$results = $db->GetArrayMaster($sql);
		
		if(empty($results))
			return true;
		
		$ids = DevblocksPlatform::sanitizeArray(array_column($results, 'id'), 'int');
		$worker_ids = array_unique(array_column($results, 'worker_id'));
		
		if(empty($ids))
			return true;
		
		$sql = sprintf("DELETE FROM bot_interaction_proactive WHERE id IN (%s)", implode(',', $ids));
		$db->ExecuteMaster($sql);
		
		// Clear caches
		if(is_array($worker_ids))
		foreach($worker_ids as $worker_id)
			self::clearCountByWorker($worker_id);
		
		return true;
	}
};
