<?php
class DAO_LlmAgentMessage {
	// The neutral usage vector as stored per message (`usage_json`). Assistant turns carry real
	// provider-reported numbers; user/tool turns carry none, so they sum as zero.
	//
	// `reasoning` is a SUBSET of `output`, never an addition to it — the provider bills reasoning as output
	// tokens and reports the split separately. Anything that totals tokens must use `output` alone; anything
	// that wants the visible reply uses `output - reasoning` (see deriveUsage's `output_text`). Only the
	// OpenAI family reports the split at all, so 0 means "not reported", not "no reasoning happened".
	const USAGE_ZERO = ['input' => 0, 'output' => 0, 'reasoning' => 0, 'cache_read' => 0, 'cache_write' => 0];

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
		// Reasoning rides the output side of that rule: each round-trip reasons afresh, so it accretes.
		$acc['reasoning'] = intval($acc['reasoning']) + intval($b['reasoning'] ?? 0);

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
		// The visible half of the output. Clamped at 0 because `reasoning` is only ever as trustworthy as the
		// provider reporting it, and a negative here would read as a bug in the transcript rather than in them.
		$usage['output_text'] = max(0, $usage['output'] - $usage['reasoning']);

		return $usage;
	}

	/**
	 * Pair a tool RESULT to the CALL it answers, and return the key it should be filed under.
	 *
	 * A transcript looks a result up by its call's id (`DevblocksLlmChatResponse_Tool::getId()`), but not every
	 * stored result carries one: Ollama's native `tool` message has only a `name`, so rows written before the
	 * provider stored `tool_call_id` key by the tool's name and never match. Re-keying them here means an
	 * existing transcript renders its results without a data migration.
	 *
	 * `$pending` is the running list of unanswered calls in emission order, `['id' => …, 'name' => …]`; the
	 * matched entry is CONSUMED. That ordering is what makes N parallel calls to the same tool pair to their N
	 * results one-for-one instead of collapsing onto a single name key -- the node runs tool calls in the order
	 * the model emitted them (LlmAgentNode::_applyTurnResponse), so the results follow in that same order.
	 *
	 * Falls back to the key it was given when nothing matches -- a truncated fetch window can show a result
	 * whose call was never loaded, and that degrades to today's behavior rather than mispairing.
	 */
	static function matchToolResultKey(string $key, array &$pending) : string {
		foreach($pending as $idx => $call) {
			if('' !== $call['id'] && $call['id'] === $key) {
				unset($pending[$idx]);
				return $key;
			}
		}

		foreach($pending as $idx => $call) {
			if($call['name'] === $key) {
				unset($pending[$idx]);
				return '' !== $call['id'] ? $call['id'] : $key;
			}
		}

		return $key;
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
	 * Rewrite an in-flight streamed row's content. THE ONLY per-row mutation on this table, and deliberately
	 * narrow: content and its size estimate, nothing else. The tree edges (`parent_uuid`), the identity
	 * (`uuid`, `seq`), and the timestamps are all fixed at insert and must stay that way — a reader walking
	 * the active path mid-stream has to see a stable tree.
	 *
	 * Guarded on `is_streaming` so a finalized turn can never be rewritten: once the flag is cleared the row
	 * is back to being immutable history, which is the invariant the rest of the table depends on.
	 *
	 * The CALLER decides how often to call this. Deltas arrive many times per second; a write per delta would
	 * be pointless load, since nothing reads faster than the transcript poll.
	 */
	public static function updateStreaming(string $uuid, array $message, ?array $usage = null) : bool {
		$db = DevblocksPlatform::services()->database();

		$data_json = json_encode($message);

		$token_est = (is_array($usage) && intval($usage['output'] ?? 0) > 0)
			? intval($usage['output'])
			: intval(ceil(strlen($data_json) / 4));

		return boolval($db->ExecuteWriter(sprintf(
			"UPDATE llm_agent_message SET `data_json` = %s, `token_est` = %d ".
			"WHERE `uuid` = UUID_TO_BIN(%s) AND `is_streaming` = 1",
			$db->qstr($data_json),
			$token_est,
			$db->qstr($uuid)
		)));
	}

	/**
	 * Close out a streamed row: write the final content, the provider's usage and finish reason, and CLEAR the
	 * streaming flag. After this the row is ordinary immutable history and every guard that asks "is the head a
	 * finished assistant turn?" starts answering yes.
	 *
	 * Also the landing point for a turn that ended EARLY (interrupted, or the stream died) — the caller
	 * sanitizes the content first and supplies a finish reason saying so, so a truncated turn is recorded as a
	 * truncated turn rather than being lost or mistaken for a complete one.
	 */
	public static function finalizeStreaming(string $uuid, array $message, ?array $usage = null, ?string $finish_reason = null) : bool {
		$db = DevblocksPlatform::services()->database();

		$data_json = json_encode($message);

		$token_est = (is_array($usage) && intval($usage['output'] ?? 0) > 0)
			? intval($usage['output'])
			: intval(ceil(strlen($data_json) / 4));

		return boolval($db->ExecuteWriter(sprintf(
			"UPDATE llm_agent_message SET `data_json` = %s, `token_est` = %d, `kind` = %s, `usage_json` = %s, ".
			"`finish_reason` = %s, `is_streaming` = 0 ".
			"WHERE `uuid` = UUID_TO_BIN(%s) AND `is_streaming` = 1",
			$db->qstr($data_json),
			$token_est,
			// Re-classify: the row was opened before any content existed, so its kind was necessarily a guess.
			// Whether the turn ended up being text or a tool call is only knowable now.
			$db->qstr(self::_classifyKind($message)),
			(is_array($usage) && $usage) ? $db->qstr(json_encode($usage)) : 'NULL',
			$db->qstr(strval($finish_reason)),
			$db->qstr($uuid)
		)));
	}

	/**
	 * Drop an in-flight row outright. Used when a stream died with nothing worth keeping: an EMPTY assistant
	 * head is worse than no head at all, because `_consumeLLMAsync()` and the retry guards read the head's role
	 * and would treat it as a real (but contentless) turn.
	 *
	 * Guarded on `is_streaming` so this can never delete finalized history.
	 */
	public static function deleteStreaming(string $uuid) : bool {
		$db = DevblocksPlatform::services()->database();

		return boolval($db->ExecuteWriter(sprintf(
			"DELETE FROM llm_agent_message WHERE `uuid` = UUID_TO_BIN(%s) AND `is_streaming` = 1",
			$db->qstr($uuid)
		)));
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

		// Anthropic content blocks, and the OpenAI Responses item list -- both hold a turn's parts in
		// `content`, they just spell the two tool types differently (`function_call` /
		// `function_call_output`). Unrecognized here the row silently classifies as `text`, the transcript
		// mislabels the turn, and Compaction::_userLed() goes blind to an orphaned tool result -- which
		// surfaces as a wire-level 400 on a LATER turn rather than as anything cosmetic.
		if(is_array($message['content'] ?? null)) {
			foreach($message['content'] as $block) {
				$type = is_array($block) ? ($block['type'] ?? '') : '';
				if('tool_result' === $type || 'function_call_output' === $type)
					return 'tool_result';
				if('tool_use' === $type || 'function_call' === $type)
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