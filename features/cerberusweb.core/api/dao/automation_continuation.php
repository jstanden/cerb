<?php
class DAO_AutomationContinuation extends Cerb_ORMHelper {
	const EXPIRES_AT = 'expires_at';
	const PARENT_TOKEN = 'parent_token';
	const ROOT_TOKEN = 'root_token';
	const STATE = 'state';
	const STATE_DATA = 'state_data';
	const TOKEN = 'token';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::EXPIRES_AT)
			->timestamp()
			;
		$validation
			->addField(self::PARENT_TOKEN)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::ROOT_TOKEN)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::STATE)
			->string()
			->setPossibleValues([
				'',
				'await',
				'error',
				'exit',
				'return',
			])
			;
		$validation
			->addField(self::STATE_DATA)
			->string()
			->setMaxLength(16777216)
			;
		$validation
			->addField(self::TOKEN)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::URI)
			->string()
			->addValidator($validation->validators()->uri())
			;
		
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$token = DevblocksPlatform::services()->string()->base64UrlEncode(random_bytes(48));
		
		$sql = sprintf("INSERT INTO automation_continuation (token) VALUES (%s)",
			$db->qstr($token)
		);
		$db->ExecuteMaster($sql);
		
		self::update($token, $fields);
		
		return $token;
	}
	
	static function upsert($fields, $token=null) {
		$db = DevblocksPlatform::services()->database();
		
		if(is_null($token))
			$token = DevblocksPlatform::services()->string()->base64UrlEncode(random_bytes(48));
		
		$sql = sprintf("REPLACE INTO automation_continuation (token) VALUES (%s)",
			$db->qstr($token)
		);
		$db->ExecuteMaster($sql);
		
		self::update($token, $fields);
		
		return $token;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = [$ids];
		
		self::updateWhere($fields, sprintf("token IN (%s)",
			implode(',', $db->qstrArray($ids))
		));
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('automation_continuation', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_AutomationContinuation[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT token, uri, state, state_data, parent_token, root_token, expires_at, updated_at ".
			"FROM automation_continuation ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param string $token
	 * @return Model_AutomationContinuation
	 */
	static function getByToken(string $token) {
		if(empty($token))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %s",
			self::TOKEN,
			Cerb_ORMHelper::qstr($token)
		));
		
		if(array_key_exists($token, $objects))
			return $objects[$token];
		
		return null;
	}
	
	/**
	 * @param string $token
	 * @return Model_AutomationContinuation[]
	 */
	static function getByRootToken(string $token) : iterable {
		if(!$token)
			return [];
		
		return self::getWhere(sprintf("%s = %s OR %s = %s",
			self::TOKEN,
			Cerb_ORMHelper::qstr($token),
			self::ROOT_TOKEN,
			Cerb_ORMHelper::qstr($token)
		));
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_AutomationContinuation[]
	 */
	static function getIds(array $ids) : array {
		if(!is_array($ids))
			$ids = [$ids];

		if(empty($ids))
			return [];

		if(!method_exists(get_called_class(), 'getWhere'))
			return [];

		$db = DevblocksPlatform::services()->database();

		$models = [];

		$results = static::getWhere(sprintf("token IN (%s)",
			implode(',', $db->qstrArray($ids))
		));

		// Sort $models in the same order as $ids
		foreach($ids as $id) {
			if(isset($results[$id]))
				$models[$id] = $results[$id];
		}

		unset($results);

		return $models;
	}	
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AutomationContinuation[]|false
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AutomationContinuation();
			$object->token = $row['token'];
			$object->parent_token = $row['parent_token'];
			$object->root_token = $row['root_token'];
			$object->uri = $row['uri'];
			$object->state = $row['state'];
			$object->expires_at = intval($row['expires_at']);
			$object->updated_at = intval($row['updated_at']);
			
			@$state_data = json_decode($row['state_data'], true);
			$object->state_data = $state_data ?: [];
			
			$objects[$object->token] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('automation_continuation');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = [$ids];
		
		if(empty($ids))
			return false;
		
		$ids_list = implode(',', $db->qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM automation_continuation WHERE token IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM automation_continuation WHERE expires_at BETWEEN 1 AND %d",
			time()
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AutomationContinuation::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AutomationContinuation', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"automation_continuation.token as %s, ".
			"automation_continuation.parent_token as %s, ".
			"automation_continuation.root_token as %s, ".
			"automation_continuation.uri as %s, ".
			"automation_continuation.state as %s, ".
			"automation_continuation.expires_at as %s, ".
			"automation_continuation.updated_at as %s ",
			SearchFields_AutomationContinuation::TOKEN,
			SearchFields_AutomationContinuation::PARENT_TOKEN,
			SearchFields_AutomationContinuation::ROOT_TOKEN,
			SearchFields_AutomationContinuation::URI,
			SearchFields_AutomationContinuation::STATE,
			SearchFields_AutomationContinuation::EXPIRES_AT,
			SearchFields_AutomationContinuation::UPDATED_AT
			);
			
		$join_sql = "FROM automation_continuation ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AutomationContinuation');
	
		return array(
			'primary_table' => 'automation_continuation',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	/**
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_AutomationContinuation::TOKEN,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
};

class SearchFields_AutomationContinuation extends DevblocksSearchFields {
	const EXPIRES_AT = 'a_expires_at';
	const PARENT_TOKEN = 'a_parent_token';
	const ROOT_TOKEN = 'a_root_token';
	const STATE = 'a_state';
	const TOKEN = 'a_token';
	const UPDATED_AT = 'a_updated_at';
	const URI = 'a_uri';

	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'automation_continuation.token';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('automation_continuation.token', self::TOKEN),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				break;
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		return parent::getLabelsForKeyValues($key, $values);
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::EXPIRES_AT => new DevblocksSearchField(self::EXPIRES_AT, 'automation_continuation', 'expires_at', $translate->_('common.expires'), null, true),
			self::PARENT_TOKEN => new DevblocksSearchField(self::PARENT_TOKEN, 'automation_continuation', 'parent_token', null, null, true),
			self::ROOT_TOKEN => new DevblocksSearchField(self::ROOT_TOKEN, 'automation_continuation', 'root_token', null, null, true),
			self::STATE => new DevblocksSearchField(self::STATE, 'automation_continuation', 'state', DevblocksPlatform::translateCapitalized('common.state'), null, true),
			self::TOKEN => new DevblocksSearchField(self::TOKEN, 'automation_continuation', 'token', null, null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'automation_continuation', 'updated_at', $translate->_('common.updated'), null, true),
			self::URI => new DevblocksSearchField(self::STATE, 'automation_continuation', 'uri', DevblocksPlatform::translate('common.uri'), null, true),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_AutomationContinuation {
	public $expires_at = 0;
	public $parent_token = null;
	public $root_token = null;
	public $state = null;
	public $state_data = [];
	public $token = null;
	public $updated_at = 0;
	public $uri = null;
	
	private ?Model_AutomationContinuation  $_parent = null;
	private ?Model_AutomationContinuation  $_root = null;
	private ?Model_Automation $_automation = null;
	
	function getAutomation() : ?Model_Automation {
		if(is_null($this->_automation)) {
			$this->_automation = DAO_Automation::getByUri($this->uri);
		}
		
		return $this->_automation;
	}
	
	public function getParent() : ?Model_AutomationContinuation {
		if(is_null($this->_parent) && $this->parent_token) {
			$this->_parent = DAO_AutomationContinuation::getByToken($this->parent_token);
		}
		
		return $this->_parent;
	}
	
	public function getRoot() : ?Model_AutomationContinuation {
		if(is_null($this->_root) && $this->root_token) {
			$this->_root = DAO_AutomationContinuation::getByToken($this->root_token);
		}
		
		return $this->_root;
	}
};
