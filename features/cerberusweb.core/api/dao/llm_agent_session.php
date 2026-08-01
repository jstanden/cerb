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

	public static function create(Model_LlmAgentSession $model) : ?Model_LlmAgentSession {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO llm_agent_session (`uuid`,`provider`,`created_at`,`automation_id`,`automation_node`,`user_type`,`user_id`,`user_ip`,`is_read`) ".
			"VALUES (UUID_TO_BIN(%s), %s, %d, %d, %s, %s, %d, %s, %d)",
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
		
		$sql = sprintf("SELECT BIN_TO_UUID(`uuid`) as `uuid`,`provider`,`created_at`,`automation_id`,`automation_node`,`user_type`,`user_id`,`user_ip`,`is_read` ".
			"FROM llm_agent_session ".
			"WHERE `uuid` = UUID_TO_BIN(%s)",
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
		
		$sql = sprintf("SELECT BIN_TO_UUID(`uuid`) as `uuid`,`provider`,`created_at`,`automation_id`,`automation_node`,`user_type`,`user_id`,`user_ip`,`is_read` ".
			"FROM llm_agent_session ".
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