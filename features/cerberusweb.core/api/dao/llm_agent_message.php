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

	public static function create(string $session_uuid, array $message, ?string $parent_uuid = null, ?string $kind = null, ?array $usage = null, ?string $finish_reason = null, bool $is_streaming = false) : ?Model_LlmAgentMessage {
		$db = DevblocksPlatform::services()->database();

		$data_json = json_encode($message);

		$model = new Model_LlmAgentMessage();
		$model->uuid = DevblocksPlatform::services()->string()->uuid();
		$model->session_uuid = $session_uuid;
		$model->parent_uuid = $parent_uuid ?? '';
		$model->role = strval($message['role'] ?? '');
		$model->kind = $kind ?? self::_classifyKind($message);
		// Prefer the provider's exact output-token count when it reported usage (assistant turns); else a coarse
		// bytes÷4 estimate (user/tool turns have no usage). This is the message's own size for budgeting/compaction.
		$model->token_est = (is_array($usage) && intval($usage['output'] ?? 0) > 0)
			? intval($usage['output'])
			: intval(ceil(strlen($data_json) / 4));
		// ONE clock read, split into whole seconds + the microseconds elapsed within them. Reading time() and
		// microtime() separately can straddle a tick and pair a second with an offset from the next one.
		// microtime()'s string form (not the float) sidesteps float64's ~0.2us slop at epoch magnitudes, and
		// truncating (not rounding) keeps the offset inside its own second — .9999999 must not become 1000000.
		list($created_frac, $created_sec) = explode(' ', microtime());
		$model->created_at = intval($created_sec);
		$model->created_at_usec = intval($created_frac * 1000000);
		$model->data = $message;
		// Provider-reported neutral usage ({input,output,cache_read,cache_write}) — assistant turns only. Stored
		// in its own column (never in data_json, which is replayed to the API), so summing is a PHP concern.
		$model->usage = is_array($usage) ? $usage : [];
		// Why the provider stopped generating, already normalized by the provider ('' when unreported). Same
		// rationale as usage: its own column, never data_json, so it can't be replayed back into a request.
		$model->finish_reason = strval($finish_reason);
		// A streamed turn opens its row before it has any content and rewrites it as deltas arrive. See
		// beginStreaming()/updateStreaming()/finalizeStreaming() below.
		$model->is_streaming = $is_streaming;

		$result = $db->ExecuteWriter(sprintf(
			"INSERT INTO llm_agent_message (`uuid`,`session_uuid`,`parent_uuid`,`role`,`kind`,`token_est`,`created_at`,`created_at_usec`,`data_json`,`usage_json`,`finish_reason`,`is_streaming`) ".
			"VALUES (UUID_TO_BIN(%s), UUID_TO_BIN(%s), %s, %s, %s, %d, %d, %d, %s, %s, %s, %d)",
			$db->qstr($model->uuid),
			$db->qstr($model->session_uuid),
			$model->parent_uuid ? sprintf('UUID_TO_BIN(%s)', $db->qstr($model->parent_uuid)) : 'NULL',
			$db->qstr($model->role),
			$db->qstr($model->kind),
			$model->token_est,
			$model->created_at,
			$model->created_at_usec,
			$db->qstr($data_json),
			$model->usage ? $db->qstr(json_encode($model->usage)) : 'NULL',
			$db->qstr($model->finish_reason),
			$model->is_streaming ? 1 : 0,
		));

		if(!$result)
			return null;

		return $model;
	}

	/**
	 * Classify a provider-native message into a coarse, provider-agnostic kind
	 * (`text`, `tool_use`, `tool_result`) off structural markers common to the
	 * Anthropic content-block and OpenAI chat shapes. `summary` is set explicitly
	 * by the compaction strategy, never inferred here.
	 */
	static private function _classifyKind(array $message) : string {
		// OpenAI tool-result envelope
		if('tool' === ($message['role'] ?? ''))
			return 'tool_result';

		// OpenAI assistant tool call
		if(!empty($message['tool_calls']))
			return 'tool_use';

		// Anthropic content blocks
		if(is_array($message['content'] ?? null)) {
			foreach($message['content'] as $block) {
				$type = is_array($block) ? ($block['type'] ?? '') : '';
				if('tool_result' === $type)
					return 'tool_result';
				if('tool_use' === $type)
					return 'tool_use';
			}
		}

		return 'text';
	}

	static private function _getRowAsModel(array $row) : Model_LlmAgentMessage {
		$msg = new Model_LlmAgentMessage();
		$msg->uuid = $row['uuid'] ?? '';
		$msg->seq = intval($row['seq'] ?? 0);
		$msg->session_uuid = $row['session_uuid'] ?? '';
		$msg->parent_uuid = $row['parent_uuid'] ?? '';
		$msg->role = $row['role'] ?? '';
		$msg->kind = $row['kind'] ?? '';
		$msg->token_est = intval($row['token_est'] ?? 0);
		$msg->created_at = intval($row['created_at'] ?? 0);
		$msg->created_at_usec = intval($row['created_at_usec'] ?? 0);
		$msg->data = @json_decode($row['data_json'] ?? '', true) ?: [];
		$msg->usage = @json_decode($row['usage_json'] ?? '', true) ?: [];
		$msg->finish_reason = strval($row['finish_reason'] ?? '');
		$msg->is_streaming = boolval($row['is_streaming'] ?? 0);
		return $msg;
	}
	
	static function get(string $uuid) : ?Model_LlmAgentMessage {
		$db = DevblocksPlatform::services()->database();
		
		try {
			$sql =
				"SELECT BIN_TO_UUID(`uuid`) as `uuid`, `seq`, BIN_TO_UUID(`session_uuid`) as `session_uuid`, BIN_TO_UUID(`parent_uuid`) as `parent_uuid`, `role`, `kind`, `token_est`, `created_at`, `created_at_usec`, `data_json`, `usage_json`, `finish_reason`, `is_streaming` ".
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
				"SELECT BIN_TO_UUID(`uuid`) as `uuid`, `seq`, BIN_TO_UUID(`session_uuid`) as `session_uuid`, BIN_TO_UUID(`parent_uuid`) as `parent_uuid`, `role`, `kind`, `token_est`, `created_at`, `created_at_usec`, `data_json`, `usage_json`, `finish_reason`, `is_streaming` ".
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
	
	/**
	 * The active-context path for a cursor: walk from the leaf ($head_uuid) up the `parent_uuid`
	 * tree edges, INCLUDING the nearest ancestor summary node but stopping there — a summary root is
	 * the compaction/switch boundary, so nothing older than it participates in the send. Scoped to the
	 * session so a stray edge can't cross into another. Returns chronological models (like
	 * getMessagesBySession); $last_n 0 = the whole bounded path.
	 *
	 * @return Model_LlmAgentMessage[]
	 */
	static function getActivePath(string $session_uuid, string $head_uuid, int $last_n=0) : array {
		if('' === $head_uuid)
			return [];

		$db = DevblocksPlatform::services()->database();

		try {
			$sql = sprintf(
				"WITH RECURSIVE path AS ( ".
					"SELECT `uuid`, `seq`, `session_uuid`, `parent_uuid`, `role`, `kind`, `token_est`, `created_at`, `created_at_usec`, `data_json`, `usage_json`, `finish_reason`, `is_streaming` ".
					"FROM llm_agent_message WHERE `uuid` = UUID_TO_BIN(%s) ".
					"UNION ALL ".
					"SELECT m.`uuid`, m.`seq`, m.`session_uuid`, m.`parent_uuid`, m.`role`, m.`kind`, m.`token_est`, m.`created_at`, m.`created_at_usec`, m.`data_json`, m.`usage_json`, m.`finish_reason`, m.`is_streaming` ".
					"FROM llm_agent_message m ".
					"JOIN path p ON m.`uuid` = p.`parent_uuid` AND p.`kind` <> 'summary' AND m.`session_uuid` = UUID_TO_BIN(%s) ".
				") ".
				"SELECT BIN_TO_UUID(`uuid`) as `uuid`, `seq`, BIN_TO_UUID(`session_uuid`) as `session_uuid`, BIN_TO_UUID(`parent_uuid`) as `parent_uuid`, `role`, `kind`, `token_est`, `created_at`, `created_at_usec`, `data_json`, `usage_json`, `finish_reason`, `is_streaming` ".
				"FROM path ORDER BY seq DESC",
				$db->qstr($head_uuid),
				$db->qstr($session_uuid)
			);

			if($last_n)
				$sql .= sprintf(" LIMIT %d", $last_n);

			$rows = $db->GetArrayReader($sql);

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
	public string $uuid = '';
	public int $seq = 0;
	public string $session_uuid = '';
	public string $parent_uuid = '';
	public string $role = '';
	public string $kind = '';
	public int $token_est = 0;
	public int $created_at = 0;
	public int $created_at_usec = 0;
	public array $data = [];
	public array $usage = [];
	public string $finish_reason = '';
	// True only while a streamed turn is still being written to this row.
	public bool $is_streaming = false;
}