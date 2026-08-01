<?php
class DAO_LlmAgentSession {
	// The `attachment_link.context` tag used to own a transcript's pasted images by the session's int `id`
	// (the UUID PK can't key the int `context_id`). Shared by the link/unlink, fork/delete, and download-ACL
	// paths. Doubles as the record-context id when the session is registered as a full context later.
	const CONTEXT = 'cerb.contexts.llm.agent.session';

	// Resolve a session UUID to its surrogate int `id` (for attachment ownership). Master read — the session
	// may have just been created this request, so a replica could lag.
	public static function getIdByUuid(string $uuid) : int {
		if('' === $uuid)
			return 0;

		$db = DevblocksPlatform::services()->database();

		return intval($db->GetOneMaster(sprintf(
			"SELECT id FROM llm_agent_session WHERE uuid = UUID_TO_BIN(%s)",
			$db->qstr($uuid)
		)));
	}

	// Store a value in the content-addressed property table (SHA-256 keyed, INSERT IGNORE so identical values
	// across sessions collapse to one row — the whole point of the dedup), returning its hex hash: the reference
	// a session `*_hash` column holds. PHP `hash('sha256',…)` emits the same 64 hex chars as MySQL
	// `SHA2(value,256)`, so a value stored here lands on the exact row a SHA2-based backfill produced. Callers
	// pass the precise bytes they want back — raw text for `system_prompt`, `json_encode()`d for `tools`/`mounts`.
	private static function _storeProperty(string $value) : string {
		$db = DevblocksPlatform::services()->database();
		$hash = hash('sha256', $value);

		$db->ExecuteMaster(sprintf(
			"INSERT IGNORE INTO llm_agent_session_property (`hash`, `value`) VALUES (%s, %s)",
			$db->qstr($hash),
			$db->qstr($value)
		));

		return $hash;
	}

	public static function create(Model_LlmAgentSession $model) : ?Model_LlmAgentSession {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO llm_agent_session (`uuid`,`provider`,`created_at`,`automation_id`,`automation_node`,`user_type`,`user_id`,`user_ip`,`is_read`) ".
			"VALUES (UUID_TO_BIN(%s), %s, %d, %d, %s, %s, %d, %s, %d)",

		// Denormalized config goes to the content-addressed store; the session holds only the hash reference.
		// NULL preserves today's "absent" semantics: no prompt / no tools, and — for mounts — a filesystem that
		// was never enabled (distinct from a hash of `"[]"`, which is enabled but /tmp-only).
		$system_prompt_hash = ('' !== $model->system_prompt) ? self::_storeProperty($model->system_prompt) : null;
		$tools_hash = $model->tools ? self::_storeProperty(json_encode($model->tools)) : null;
		$mounts_hash = is_null($model->mounts) ? null : self::_storeProperty(json_encode($model->mounts));
			$db->qstr($model->uuid),
			$db->qstr($model->provider),
			$model->created_at,
			$model->token_usage,
			$model->automation_id,
			$db->qstr($model->automation_node),
			$db->qstr($model->user_type),
			$model->user_id,
			$db->qstr($model->user_ip),
			$db->qstr($model->is_read),
			is_null($system_prompt_hash) ? 'NULL' : $db->qstr($system_prompt_hash),
			is_null($tools_hash) ? 'NULL' : $db->qstr($tools_hash),
			is_null($mounts_hash) ? 'NULL' : $db->qstr($mounts_hash),
		);

		$result = $db->ExecuteWriter($sql);
		
		if(!$result)
			return null;
		
		return $model;
	}
	
		// Copy the source's image ownership links onto the fork so its attachments aren't orphan-reaped when the
		// origin is deleted. Session-level: a partial fork (at_seq) may over-retain links to attachments past the
		// branch point — harmless, they reap once the fork itself is deleted.
		$source_id = self::getIdByUuid($session_uuid);
		$fork_id = self::getIdByUuid($model->uuid);

		if($source_id && $fork_id) {
			$db->ExecuteMaster(sprintf(
				"INSERT IGNORE INTO attachment_link (attachment_id, context, context_id) ".
				"SELECT attachment_id, context, %d FROM attachment_link WHERE context = %s AND context_id = %d",
				$fork_id,
				$db->qstr(self::CONTEXT),
				$source_id
			));
		}
	public static function get(string $session_uuid) : ?Model_LlmAgentSession {
		$db = DevblocksPlatform::services()->database();
		
		// The denormalized config lives in `llm_agent_session_property` now; hydrate it back via the hash refs
		// and alias the columns to their old names so _getResultsAsModel (and every consumer) is unchanged.
		$sql = sprintf("SELECT BIN_TO_UUID(s.`uuid`) as `uuid`,s.`provider`,s.`provider_params`,BIN_TO_UUID(s.`head_uuid`) as `head_uuid`,s.`created_at`,s.`updated_at`,s.`token_usage`,s.`automation_id`,s.`automation_node`,s.`agent_id`,s.`user_type`,s.`user_id`,s.`user_ip`,s.`is_read`,sp.`value` as `system_prompt`,t.`value` as `tools`,m.`value` as `mounts` ".
			"FROM llm_agent_session s ".
			"LEFT JOIN llm_agent_session_property sp ON sp.`hash` = s.`system_prompt_hash` ".
			"LEFT JOIN llm_agent_session_property t ON t.`hash` = s.`tools_hash` ".
			"LEFT JOIN llm_agent_session_property m ON m.`hash` = s.`mounts_hash` ".
			"WHERE s.`uuid` = UUID_TO_BIN(%s)",
			$db->qstr($session_uuid)
		);

		try {
			if (!($row = $db->GetRowReader($sql)))
				return null;
		} catch(Exception_DevblocksDatabaseQueryTimeout) {
			return null;
		}
		
		return self::_getResultsAsModel($row);
	}
	
	public static function markRead(string $uuid, bool $is_read=true) : bool {
		$db = DevblocksPlatform::services()->database();
		
		$result = $db->ExecuteWriter(sprintf(
			"UPDATE llm_agent_session ".
			"SET is_read = %d ".
			"WHERE `uuid` = UUID_TO_BIN(%s)",
			$is_read ? 1 : 0,
			$db->qstr($uuid)
		));
		
		return boolval($result);
	}
	
	// Advance the session's active-branch leaf (the single implicit cursor). Called on every append
	// and when a provider switch plants a new summary root. `null` clears it (legacy/empty session).
	public static function setHead(string $uuid, ?string $head_uuid) : bool {
		$db = DevblocksPlatform::services()->database();

		// Every append advances the head — the natural place to also stamp last activity, so the transcript
		// list can sort by recency (and retention key on activity) across all append paths for free.
		$result = $db->ExecuteWriter(sprintf(
			"UPDATE llm_agent_session SET `head_uuid` = %s, `updated_at` = %d WHERE `uuid` = UUID_TO_BIN(%s)",
			$head_uuid ? sprintf('UUID_TO_BIN(%s)', $db->qstr($head_uuid)) : 'NULL',
			time(),
			$db->qstr($uuid)
		));

		return boolval($result);
	}

	// Denormalize the active-branch context token estimate onto the session (the LLM node writes this once
	// per turn, right after computing it) so the GUI can display it without re-summing the tree. Also stamps
	// last activity.
	public static function setTokenUsage(string $uuid, int $token_usage) : bool {
		$db = DevblocksPlatform::services()->database();

		$result = $db->ExecuteWriter(sprintf(
			"UPDATE llm_agent_session SET `token_usage` = %d, `updated_at` = %d WHERE `uuid` = UUID_TO_BIN(%s)",
			max(0, $token_usage),
			time(),
			$db->qstr($uuid)
		));

		return boolval($result);
	}

	// Persist the session's system prompt into the content store and point the session at it, writing the hash
	// ONLY when it changed. Source-of-truth for pure-resume + the dev transcript. Comparing the 64-char hash
	// (not the whole blob) makes the unchanged-prompt no-op cheap.
	public static function setSystemPrompt(string $uuid, string $system_prompt) : bool {
		$db = DevblocksPlatform::services()->database();

		$hash = self::_storeProperty($system_prompt);

		$db->ExecuteWriter(sprintf(
			"UPDATE llm_agent_session SET `system_prompt_hash` = %s ".
			"WHERE `uuid` = UUID_TO_BIN(%s) AND (`system_prompt_hash` IS NULL OR `system_prompt_hash` != %s)",
			$db->qstr($hash),
			$db->qstr($uuid),
			$db->qstr($hash)
		));

		return true;
	}

	// Persist the session's literal `tools:` config (the pre-KATA-evaluation authored form — static
	// `cerb:automation:` URIs and any dynamic `{{placeholder}}` inputs kept verbatim) into the content store,
	// writing the hash ONLY when it changed. The stored form is a name→automation-URI map for the dev transcript
	// (trace a tool call back to its `llm.tool`). json_encode is deterministic for a same-key-ordered array, so
	// its hash is a stable change key.
	public static function setTools(string $uuid, array $tools) : bool {
		$db = DevblocksPlatform::services()->database();

		$hash = self::_storeProperty(json_encode($tools));

		$db->ExecuteWriter(sprintf(
			"UPDATE llm_agent_session SET `tools_hash` = %s ".
			"WHERE `uuid` = UUID_TO_BIN(%s) AND (`tools_hash` IS NULL OR `tools_hash` != %s)",
			$db->qstr($hash),
			$db->qstr($uuid),
			$db->qstr($hash)
		));

		return true;
	}

	// Persist the session's resolved agent-filesystem `mounts:` (filesystem, mountpoint, mode) into the content
	// store, writing the hash ONLY when it changed. The session is the source of truth on resume: a turn that
	// omits `mounts:` inherits these rather than losing its volumes (like `tools`/`system_prompt`). An enabled-
	// but-empty `[]` hashes to a real row; only a never-enabled filesystem leaves `mounts_hash` NULL (create()).
	public static function setMounts(string $uuid, array $mounts) : bool {
		$db = DevblocksPlatform::services()->database();

		$hash = self::_storeProperty(json_encode($mounts));

		$db->ExecuteWriter(sprintf(
			"UPDATE llm_agent_session SET `mounts_hash` = %s ".
			"WHERE `uuid` = UUID_TO_BIN(%s) AND (`mounts_hash` IS NULL OR `mounts_hash` != %s)",
			$db->qstr($hash),
			$db->qstr($uuid),
			$db->qstr($hash)
		));

		return true;
	}

	public static function delete(string $uuid) : bool {
		$db = DevblocksPlatform::services()->database();

		DAO_LlmAgentMessage::deleteBySession($uuid);

		// Drop image ownership links so the transcript's attachments orphan-reap (the 24h sweep). Resolve the
		// int id before the row is gone.
		if(($id = self::getIdByUuid($uuid)))
			DAO_Attachment::deleteLinks(self::CONTEXT, [$id]);

		$result = $db->ExecuteWriter(sprintf(
			"DELETE FROM llm_agent_session ".
			"WHERE `uuid` = UUID_TO_BIN(%s)",
			$db->qstr($uuid)
		));
		
		return boolval($result);
	}
	
	/**
	 * @return Model_LlmAgentSession[]
	 */
	public static function search(int $limit=25, bool $is_unread=false, string $before_id='') : array {
		$db = DevblocksPlatform::services()->database();
		
		if($before_id) {
			$before_session = DAO_LlmAgentSession::get($before_id);
		} else {
			$before_session = null;
		}

		$sql = sprintf("SELECT BIN_TO_UUID(s.`uuid`) as `uuid`,s.`provider`,s.`provider_params`,BIN_TO_UUID(s.`head_uuid`) as `head_uuid`,s.`created_at`,s.`updated_at`,s.`token_usage`,s.`automation_id`,s.`automation_node`,s.`agent_id`,s.`user_type`,s.`user_id`,s.`user_ip`,s.`is_read`,sp.`value` as `system_prompt`,t.`value` as `tools`,m.`value` as `mounts` ".
			"FROM llm_agent_session s ".
			"LEFT JOIN llm_agent_session_property sp ON sp.`hash` = s.`system_prompt_hash` ".
			"LEFT JOIN llm_agent_session_property t ON t.`hash` = s.`tools_hash` ".
			"LEFT JOIN llm_agent_session_property m ON m.`hash` = s.`mounts_hash` ".
			"WHERE 1 ".
			"%s ".
			"%s ".
			"ORDER BY created_at DESC ".
			"LIMIT %d",
			($is_unread ? 'AND is_read = 0' : ''),
			($before_session ? sprintf('AND created_at < %d', $before_session->created_at) : ''),
			$limit
		);
		
		try {
			$rows = $db->GetArrayReader($sql);
		} catch (Exception_DevblocksDatabaseQueryTimeout) {
			$rows = [];
		}
		
		$models = array_map(fn($row) => self::_getResultsAsModel($row), $rows);
		
		return array_combine(array_column($models, 'uuid'), $models);
	}
	
	private static function _getResultsAsModel(array $row) : Model_LlmAgentSession {
		$llm_session = new Model_LlmAgentSession($row['uuid']);
		$llm_session->provider = $row['provider'];
		$llm_session->created_at = intval($row['created_at']);
		$llm_session->token_usage = intval($row['token_usage'] ?? 0);
		$llm_session->automation_id = intval($row['automation_id']);
		$llm_session->automation_node = $row['automation_node'];
		$llm_session->user_type = $row['user_type'];
		$llm_session->user_id = intval($row['user_id']);
		$llm_session->user_ip = $row['user_ip'];
		$llm_session->is_read = intval($row['is_read']);
		$llm_session->system_prompt = strval($row['system_prompt'] ?? '');
		$llm_session->tools = (($row['tools'] ?? null) !== null)
			? (json_decode($row['tools'], true) ?: [])
			: [];
		// NULL means the agent filesystem was never enabled for this session; `[]` means it was enabled with
		// no volumes (a /tmp-only scratch filesystem), which is a real configuration, not an absence.
		$llm_session->mounts = (($row['mounts'] ?? null) !== null)
			? (json_decode($row['mounts'], true) ?: [])
			: null;
		return $llm_session;
	}
}

class Model_LlmAgentSession {
	public string $uuid = '';
	public string $provider = '';
	public int $created_at = 0;
	public int $token_usage = 0;
	public int $automation_id = 0;
	public string $automation_node = '';
	public string $user_type = '';
	public int $user_id = 0;
	public string $user_ip = '';
	public int $is_read = 0;
	public string $system_prompt = '';
	public array $tools = [];

	/** Resolved agent-filesystem mounts; null = the filesystem isn't enabled, [] = enabled, /tmp only. */
	public ?array $mounts = null;

	public function __construct(?string $uuid = null) {
		$this->uuid = $uuid ?: DevblocksPlatform::services()->string()->uuid();
		$this->created_at = time();
		$this->user_ip = DevblocksPlatform::getClientIp();
	}
	
	public function getAutomation() : ?Model_Automation {
		if($this->automation_id)
			return DAO_Automation::get($this->automation_id);
		return null;
	}
	
	public function getUser() : ?DevblocksRecordModel {
		if(!$this->user_type || !$this->user_id)
			return null;
		
		return match($this->user_type) {
			'worker' => DAO_Worker::get($this->user_id),
			default => null,
		};
	}
}