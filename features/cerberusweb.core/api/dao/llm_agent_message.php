<?php
class DAO_LlmAgentMessage {
	public static function create(string $session_uuid, array $message) : ?Model_LlmAgentMessage {
		$db = DevblocksPlatform::services()->database();
		
		$model = new Model_LlmAgentMessage();
		$model->session_uuid = $session_uuid;
		$model->created_at = time();
		$model->data = $message;
		
		$result = $db->ExecuteWriter(sprintf("INSERT INTO llm_agent_message (`session_uuid`,`created_at`,`data_json`) VALUES (%s, %d, %s)",
			$db->qstr($model->session_uuid),
			$model->created_at,
			$db->qstr(json_encode($model->data)),
		));
		
		if(!$result)
			return null;
		
		$model->id = $db->lastInsertId();
		
		return $model;
	}
	
	/**
	 * @param string $uuid
	 * @param int $last_n
	 * @return Model_LlmAgentMessage[]
	 */
	static function getMessagesBySession(string $session_uuid, int $last_n=10) : array {
		$db = DevblocksPlatform::services()->database();
		
		try {
			$sql =
				"SELECT id, session_uuid, created_at, data_json ".
				"FROM llm_agent_message ".
				"WHERE session_uuid = %s"
			;
			
			if($last_n)
				$sql .= sprintf(" ORDER BY id DESC LIMIT %d", $last_n);
			
			$rows = $db->GetArrayReader(sprintf($sql,
				$db->qstr($session_uuid)
			));
			
			if(!$rows) return [];
			
			return array_map(
				function($row) {
					$msg = new Model_LlmAgentMessage();
					$msg->id = intval($row['id']);
					$msg->session_uuid = $row['session_uuid'];
					$msg->created_at = intval($row['created_at']);
					$msg->data = @json_decode($row['data_json'] ?? '', true) ?: [];
					return $msg;
				},
				array_reverse($rows),
			);
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
		}
		
		return [];
	}
}

class Model_LlmAgentMessage {
	public int $id;
	public string $session_uuid;
	public int $created_at;
	public array $data;
}