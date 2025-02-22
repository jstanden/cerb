<?php
class DAO_LlmAgentSession {
	public static function create(string $provider) : ?Model_LlmAgentSession {
		$db = DevblocksPlatform::services()->database();
		
		$model = new Model_LlmAgentSession();
		$model->uuid = DevblocksPlatform::services()->string()->uuid();
		$model->provider = $provider;
		$model->created_at = time();
		
		$sql = "INSERT INTO llm_agent_session (`uuid`, `provider`, `created_at`) VALUES (%s, %s, %d)";
		
		$result = $db->ExecuteWriter(sprintf($sql,
			$db->qstr($model->uuid),
			$db->qstr($model->provider),
			$model->created_at
		));
		
		if(!$result)
			return null;
		
		$model->id = $db->lastInsertId();
		
		return $model;
	}
}

class Model_LlmAgentSession {
	public int $id;
	public string $uuid;
	public string $provider;
	public int $created_at;
}