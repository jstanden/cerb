<?php
class DAO_BotSession extends Cerb_ORMHelper {
	const SESSION_DATA = 'session_data';
	const SESSION_ID = 'session_id';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::SESSION_DATA)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField(self::SESSION_ID)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$session_id = sha1(json_encode($fields) . time() . random_bytes(32));
		
		$sql = sprintf("INSERT INTO bot_session (session_id) VALUES (%s)",
			$db->qstr($session_id)
		);
		$db->ExecuteMaster($sql);
		
		self::update($session_id, $fields);
		
		return $session_id;
	}
	
	static function upsert($fields, $session_id=null) {
		$db = DevblocksPlatform::services()->database();
		
		if(is_null($session_id))
			$session_id = sha1(json_encode($fields) . time() . uniqid(null, true));
		
		$sql = sprintf("REPLACE INTO bot_session (session_id) VALUES (%s)",
			$db->qstr($session_id)
		);
		$db->ExecuteMaster($sql);
		
		self::update($session_id, $fields);
		
		return $session_id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = array($ids);
		
		self::updateWhere($fields, sprintf("session_id IN (%s)",
			implode(',', $db->qstrArray($ids))
		));
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('bot_session', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_BotSession[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT session_id, session_data, updated_at ".
			"FROM bot_session ".
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
	 * @param integer $id
	 * @return Model_BotSession	
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %s",
			self::SESSION_ID,
			Cerb_ORMHelper::qstr($id)
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_BotSession[]
	 */
	static function getIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);

		if(empty($ids))
			return array();

		if(!method_exists(get_called_class(), 'getWhere'))
			return array();

		$db = DevblocksPlatform::services()->database();

		$models = array();

		$results = static::getWhere(sprintf("session_id IN (%s)",
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
	 * @param resource $rs
	 * @return Model_BotSession[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_BotSession();
			$object->session_id = $row['session_id'];
			$object->updated_at = intval($row['updated_at']);
			
			@$session_data = json_decode($row['session_data'], true);
			$object->session_data = $session_data ?: [];
			
			$objects[$object->session_id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('bot_session');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $db->qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM bot_session WHERE session_id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM bot_session WHERE updated_at < %d",
			(time() - 86400) // 24 hours
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_BotSession::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_BotSession', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"bot_session.session_id as %s, ".
			"bot_session.session_data as %s, ".
			"bot_session.updated_at as %s ",
				SearchFields_BotSession::SESSION_ID,
				SearchFields_BotSession::SESSION_DATA,
				SearchFields_BotSession::UPDATED_AT
			);
			
		$join_sql = "FROM bot_session ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_BotSession');
	
		return array(
			'primary_table' => 'bot_session',
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
		$db = DevblocksPlatform::services()->database();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_BotSession::SESSION_ID,
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

class SearchFields_BotSession extends DevblocksSearchFields {
	const SESSION_ID = 'b_session_id';
	const SESSION_DATA = 'b_session_data';
	const UPDATED_AT = 'b_updated_at';

	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'bot_session.session_id';
	}
	
	static function getCustomFieldContextKeys() {
		// [TODO] Context
		return array(
			'' => new DevblocksSearchFieldContextKeys('bot_session.session_id', self::SESSION_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				break;
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
		}
		
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
			self::SESSION_ID => new DevblocksSearchField(self::SESSION_ID, 'bot_session', 'session_id', null, null, true),
			self::SESSION_DATA => new DevblocksSearchField(self::SESSION_DATA, 'bot_session', 'session_data', null, null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'bot_session', 'updated_at', $translate->_('common.updated'), null, true),
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

class Model_BotSession {
	public $session_id;
	public $session_data;
	public $updated_at;
};
