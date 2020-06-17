<?php
class DAO_OAuthToken extends Cerb_ORMHelper {
	const APP_ID = 'app_id';
	const CREATED_AT = 'created_at';
	const EXPIRES_AT = 'expires_at';
	const TOKEN = 'token';
	const TOKEN_TYPE = 'token_type';
	const WORKER_ID = 'worker_id';
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::APP_ID, DevblocksPlatform::translateCapitalized('dao.oauth_token.app_id'))
			->id()
			->addValidator($validation->validators()->contextId(Context_OAuthApp::ID))
			->setRequired(true)
			;
		$validation
			->addField(self::CREATED_AT, DevblocksPlatform::translateCapitalized('common.created'))
			->timestamp()
			->setRequired(true)
			;
		$validation
			->addField(self::EXPIRES_AT, DevblocksPlatform::translateCapitalized('common.expires'))
			->timestamp()
			->setRequired(true)
			;
		$validation
			->addField(self::TOKEN, DevblocksPlatform::translateCapitalized('common.token'))
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::TOKEN_TYPE, DevblocksPlatform::translateCapitalized('common.type'))
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::WORKER_ID, DevblocksPlatform::translateCapitalized('common.worker'))
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER, true))
			;
			
		return $validation->getFields();
	}
	
	static function createAuthToken(array $fields) {
		if(!array_key_exists(self::TOKEN_TYPE, $fields))
			$fields[self::TOKEN_TYPE] = 'auth';
		
		return self::_create($fields);
	}
	
	static function createAccessToken(array $fields) {
		if(!array_key_exists(self::TOKEN_TYPE, $fields))
			$fields[self::TOKEN_TYPE] = 'access';
		
		return self::_create($fields);
	}
	
	static function createRefreshToken(array $fields) {
		if(!array_key_exists(self::TOKEN_TYPE, $fields))
			$fields[self::TOKEN_TYPE] = 'refresh';
		
		return self::_create($fields);
	}
	
	private static function _create(array $fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!array_key_exists(self::CREATED_AT, $fields))
			$fields[self::CREATED_AT] = time();
		
		$field_keys = $db->escapeArray(array_keys($fields));
		$field_values = $db->qstrArray(array_values($fields));
		
		if(!$field_keys || !$field_values)
			return false;
		
		$sql = sprintf("INSERT INTO oauth_token (%s) VALUES (%s)",
			implode(',', $field_keys),
			implode(',', $field_values)
		);
		return $db->ExecuteMaster($sql);
	}
	
	/**
	 * @return Model_OAuthToken|NULL
	 */
	static function getAuthToken($token_id) {
		return self::_getTokenByTypeAndId('auth', $token_id);
	}
	
	/**
	 * @return Model_OAuthToken|NULL
	 */
	static function getAccessToken($token_id) {
		return self::_getTokenByTypeAndId('access', $token_id);
	}
	
	/**
	 * @return Model_OAuthToken|NULL
	 */
	static function getRefreshToken($token_id) {
		return self::_getTokenByTypeAndId('refresh', $token_id);
	}
	
	/**
	 * 
	 * @param string $token_type
	 * @param string $token_id
	 * @return NULL|Model_OAuthToken
	 */
	private static function _getTokenByTypeAndId($token_type, $token_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT token_type, token, app_id, worker_id, created_at, expires_at ".
			"FROM oauth_token ".
			"WHERE token_type = %s AND token = %s",
			$db->qstr($token_type),
			$db->qstr($token_id)
		);
		
		if(false == ($row = $db->GetRowReader($sql)))
			return null;
		
		$model = new Model_OAuthToken();
		$model->app_id = intval($row['app_id']);
		$model->created_at = intval($row['created_at']);
		$model->expires_at = intval($row['expires_at']);
		$model->token = $row['token'];
		$model->token_type = $row['token_type'];
		$model->worker_id = intval($row['worker_id']);
		
		return $model;
	}
	
	static function deleteByAppId($app_id) {
		return self::deleteByAppIds([$app_id]);
	}
	
	static function deleteByAppIds(array $app_ids) {
		$db = DevblocksPlatform::services()->database();
		
		$app_ids = DevblocksPlatform::sanitizeArray($app_ids, 'integer', []);
		
		if(!$app_ids || !is_array($app_ids))
			return false;
		
		return $db->ExecuteMaster(sprintf("DELETE FROM oauth_token WHERE app_id IN (%s)", implode(',', $app_ids)));
	}
	
	static function deleteByWorkerId($worker_id) {
		return self::deleteByWorkerIds([$worker_id]);
	}
	
	static function deleteByWorkerIds(array $worker_ids) {
		$db = DevblocksPlatform::services()->database();
		
		$worker_ids = DevblocksPlatform::sanitizeArray($worker_ids, 'integer', []);
		
		if(!$worker_ids || !is_array($worker_ids))
			return false;
		
		return $db->ExecuteMaster(sprintf("DELETE FROM oauth_token WHERE worker_id IN (%s)", implode(',', $worker_ids)));
	}
	
	static function deleteAuthToken($token_id) {
		return self::_deleteTokenByTypeAndId('auth', $token_id);
	}
	
	static function deleteAccessToken($token_id) {
		return self::_deleteTokenByTypeAndId('access', $token_id);
	}
	
	static function deleteRefreshToken($token_id) {
		return self::_deleteTokenByTypeAndId('refresh', $token_id);
	}
	
	private static function _deleteTokenByTypeAndId($token_type, $token_id) {
		$db = DevblocksPlatform::services()->database();
		
		return $db->ExecuteMaster(sprintf("DELETE FROM oauth_token WHERE token_type = %s AND token = %s",
			$db->qstr($token_type),
			$db->qstr($token_id)
		));
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		// Remove expired tokens
		$sql = sprintf("DELETE FROM oauth_token WHERE expires_at < %d",
			time()
		);
		$db->ExecuteMaster($sql);
	}
}

class Model_OAuthToken {
	public $token_type = '';
	public $token = '';
	public $app_id = 0;
	public $worker_id = 0;
	public $created_at = 0;
	public $expires_at = 0;
}