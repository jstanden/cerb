<?php
class DAO_LlmAgentSession {
	public static function create(Model_LlmAgentSession $model) : ?Model_LlmAgentSession {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO llm_agent_session (`uuid`,`provider`,`created_at`,`automation_id`,`automation_node`,`user_type`,`user_id`,`user_ip`) ".
			"VALUES (UUID_TO_BIN(%s), %s, %d, %d, %s, %s, %d, %s)",
			$db->qstr($model->uuid),
			$db->qstr($model->provider),
			$model->created_at,
			$model->automation_id,
			$db->qstr($model->automation_node),
			$db->qstr($model->user_type),
			$model->user_id,
			$db->qstr($model->user_ip),
		);
		
		$result = $db->ExecuteWriter($sql);
		
		if(!$result)
			return null;
		
		return $model;
	}
	
	public static function get(string $session_uuid) : ?Model_LlmAgentSession {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT BIN_TO_UUID(`uuid`) as `uuid`,`provider`,`created_at`,`automation_id`,`automation_node`,`user_type`,`user_id`,`user_ip` ".
			"FROM llm_agent_session ".
			"WHERE `uuid` = %s",
			$db->qstr($session_uuid)
		);
		
		try {
			if (!($row = $db->GetRowReader($sql)))
				return null;
		} catch(Exception_DevblocksDatabaseQueryTimeout) {
			return null;
		}
		
		$llm_session = new Model_LlmAgentSession($row['uuid']);
		$llm_session->provider = $row['provider'];
		$llm_session->created_at = intval($row['created_at']);
		$llm_session->automation_id = intval($row['automation_id']);
		$llm_session->automation_node = $row['automation_node'];
		$llm_session->user_type = $row['user_type'];
		$llm_session->user_id = intval($row['user_id']);
		$llm_session->user_ip = $row['user_ip'];
		return $llm_session;
	}
}

class Model_LlmAgentSession {
	public string $uuid = '';
	public string $provider = '';
	public int $created_at = 0;
	public int $automation_id = 0;
	public string $automation_node = '';
	public string $user_type = '';
	public int $user_id = 0;
	public string $user_ip = '';
	
	public function __construct(?string $uuid = null) {
		$this->uuid = $uuid ?: DevblocksPlatform::services()->string()->uuid();
		$this->created_at = time();
		$this->user_ip = DevblocksPlatform::getClientIp();
	}
}