<?php
class DAO_LlmAgentMessage {
	// The neutral usage vector as stored per message (`usage_json`). Assistant turns carry real
	// provider-reported numbers; user/tool turns carry none, so they sum as zero.
	const USAGE_ZERO = ['input' => 0, 'output' => 0, 'cache_read' => 0, 'cache_write' => 0];

	/**
	 * Sum two neutral usage vectors. Missing components read as 0, so a turn's assistant round-trips can be
	 * folded together (and user/tool messages added) without the caller pre-checking.
	 */
	static function addUsage(array $a, array $b) : array {
		foreach(array_keys(self::USAGE_ZERO) as $k)
			$a[$k] = intval($a[$k] ?? 0) + intval($b[$k] ?? 0);

		return $a;
	}

	/**
	 * Fold one round-trip's usage into a TURN's running total — the accretion a reader wants, NOT a raw sum.
	 * A displayed agent turn is several provider round-trips (the tool loop), and every round-trip re-sends the
	 * same growing context, so summing the prompt side (input + both cache halves) N-counts the shared,
	 * mostly-cached history — a 10-tool turn reads as ~1M "in" when the real context is ~90K. So:
	 *
	 *   - OUTPUT sums (each round-trip generates distinct tokens — that IS the turn's output).
	 *   - the PROMPT side (input/cache_read/cache_write) takes the LATEST round-trip that has one — i.e. the
	 *     turn's final context size. A zero-prompt message (a user turn) leaves it untouched.
	 *
	 * Use this for a per-turn chip; use addUsage() for a genuinely cumulative session total (every billed token).
	 */
	static function foldTurnUsage(array $acc, array $b) : array {
		$acc = array_merge(self::USAGE_ZERO, $acc);
		$acc['output'] = intval($acc['output']) + intval($b['output'] ?? 0);

		$prompt = intval($b['input'] ?? 0) + intval($b['cache_read'] ?? 0) + intval($b['cache_write'] ?? 0);

		if($prompt > 0) {
			$acc['input'] = intval($b['input'] ?? 0);
			$acc['cache_read'] = intval($b['cache_read'] ?? 0);
			$acc['cache_write'] = intval($b['cache_write'] ?? 0);
		}

		return $acc;
	}

	/**
	 * Add the two derived figures a reader actually wants: `prompt` (every input component — fresh input plus
	 * both cache halves) and `coverage` (what share of that prompt came from cache, 0-100).
	 *
	 * Shared by every transcript surface (Setup→Developers and the `llmTranscript` await) on purpose: these are
	 * the definitions of "tokens in" and "cached", and two surfaces quoting different numbers for one session
	 * is worse than either number alone.
	 */
	static function deriveUsage(array $usage) : array {
		$usage = array_merge(self::USAGE_ZERO, $usage);
		$usage['prompt'] = $usage['input'] + $usage['cache_read'] + $usage['cache_write'];
		$usage['coverage'] = $usage['prompt'] > 0 ? intval(round(100 * $usage['cache_read'] / $usage['prompt'])) : 0;

		return $usage;
	}

	public static function create(string $session_uuid, array $message) : ?Model_LlmAgentMessage {
		$db = DevblocksPlatform::services()->database();
		
		$model = new Model_LlmAgentMessage();
		$model->session_uuid = $session_uuid;
		$model->created_at = time();
		$model->data = $message;
		
		$result = $db->ExecuteWriter(sprintf(
			"INSERT INTO llm_agent_message (`uuid`,`session_uuid`,`created_at`,`data_json`) ".
			"VALUES (UUID_TO_BIN(%s), UUID_TO_BIN(%s), %d, %s)",
			$db->qstr(DevblocksPlatform::services()->string()->uuid()),
			$db->qstr($model->session_uuid),
			$model->created_at,
			$db->qstr(json_encode($model->data)),
		));
		
		if(!$result)
			return null;
		
		return $model;
	}
	
	static private function _getRowAsModel(array $row) : Model_LlmAgentMessage {
		$msg = new Model_LlmAgentMessage();
		$msg->uuid = $row['uuid'] ?? '';
		$msg->session_uuid = $row['session_uuid'] ?? '';
		$msg->created_at = intval($row['created_at'] ?? 0);
		$msg->data = @json_decode($row['data_json'] ?? '', true) ?: [];
		return $msg;
	}
	
	static function get(string $uuid) : ?Model_LlmAgentMessage {
		$db = DevblocksPlatform::services()->database();
		
		try {
			$sql =
				"SELECT BIN_TO_UUID(`uuid`) as `uuid`, BIN_TO_UUID(`session_uuid`) as `session_uuid`, `created_at`, `data_json` ".
				"FROM llm_agent_message ".
				"WHERE uuid = UUID_TO_BIN(%s)"
			;
			
			$row = $db->GetRowReader(sprintf($sql, $db->qstr($uuid)));
			
			if(!$row) return null;
		
			return self::_getRowAsModel($row);
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
		}
		
		return null;
	}
	
	/**
	 * @param string $session_uuid
	 * @param int $last_n
	 * @return Model_LlmAgentMessage[]
	 */
	static function getMessagesBySession(string $session_uuid, int $last_n=10) : array {
		$db = DevblocksPlatform::services()->database();
		
		try {
			$sql =
				"SELECT BIN_TO_UUID(`uuid`) as `uuid`, BIN_TO_UUID(`session_uuid`) as `session_uuid`, `created_at`, `data_json` ".
				"FROM llm_agent_message ".
				"WHERE session_uuid = UUID_TO_BIN(%s) ".
				"ORDER BY seq DESC"
			;
			
			if($last_n)
				$sql .= sprintf(" LIMIT %d", $last_n);
			
			$rows = $db->GetArrayReader(sprintf($sql,
				$db->qstr($session_uuid)
			));
			
			if(!$rows) return [];
			
			return array_map(
				fn($row) => self::_getRowAsModel($row),
				array_reverse($rows),
			);
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
		}
		
		return [];
	}
	
	public static function deleteBySession(string $uuid) : bool {
		$db = DevblocksPlatform::services()->database();
		
		$result = $db->ExecuteMaster(sprintf(
			"DELETE FROM llm_agent_message WHERE `session_uuid` = UUID_TO_BIN(%s)",
			$db->qstr($uuid)
		));
		
		return boolval($result);
	}
}

class Model_LlmAgentMessage {
	public string $uuid;
	public string $session_uuid;
	public int $created_at;
	public array $data;
}