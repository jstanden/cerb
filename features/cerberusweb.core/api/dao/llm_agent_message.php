<?php
class DAO_LlmAgentMessage {
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