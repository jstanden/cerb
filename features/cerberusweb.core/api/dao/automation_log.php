<?php
class DAO_AutomationLog extends Cerb_ORMHelper {
	const AUTOMATION_NAME = 'automation_name';
	const AUTOMATION_NODE = 'automation_node';
	const CREATED_AT = 'created_at';
	const ID = 'id';
	const LOG_LEVEL = 'log_level';
	const LOG_MESSAGE = 'log_message';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::AUTOMATION_NAME)
			->string()
			;
		$validation
			->addField(self::AUTOMATION_NODE)
			->string()
			->setMaxLength(1024)
			;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::LOG_LEVEL)
			->number()
			->setMin(0)
			->setMax(7)
			;
		$validation
			->addField(self::LOG_MESSAGE)
			->string()
			->setMaxLength(1024)
			->setRequired(true)
			;
		
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();
		
		$sql = "INSERT INTO automation_log () VALUES ()";
		$db->ExecuteMaster($sql);
		
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		// Make changes
		parent::_update($ids, 'automation_log', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('automation_log', $fields, $where);
	}	
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_AutomationLog[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, automation_name, automation_node, created_at, log_level, log_message ".
			"FROM automation_log ".
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
	 * 
	 * @param array $ids
	 * @return Model_AutomationLog[]
	 */
	static function getIds(array $ids) : array {
		if(!is_array($ids))
			$ids = [$ids];

		if(empty($ids))
			return [];

		if(!method_exists(get_called_class(), 'getWhere'))
			return [];

		$models = [];
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		$results = static::getWhere(sprintf("id IN (%s)",
			implode(',', $ids)
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
	 * @return Model_AutomationLog[]|false
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AutomationLog();
			$object->id = intval($row['id']);
			$object->automation_name = $row['automation_name'];
			$object->automation_node = $row['automation_node'];
			$object->log_level = intval($row['log_level']);
			$object->log_message = $row['log_message'];
			$object->created_at = intval($row['created_at']);
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('automation_log');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = [$ids];
		
		if(empty($ids))
			return false;
		
		$ids_list = implode(',', $db->qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM automation_log WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM automation_log WHERE created_at BETWEEN 0 AND %d",
			time() - 1209600 // 2 weeks
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AutomationLog::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AutomationLog', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"automation_log.id as %s, ".
			"automation_log.automation_name as %s, ".
			"automation_log.automation_node as %s, ".
			"automation_log.created_at as %s, ".
			"automation_log.log_level as %s, ".
			"automation_log.log_message as %s ",
			SearchFields_AutomationLog::ID,
			SearchFields_AutomationLog::AUTOMATION_NAME,
			SearchFields_AutomationLog::AUTOMATION_NODE,
			SearchFields_AutomationLog::CREATED_AT,
			SearchFields_AutomationLog::LOG_LEVEL,
			SearchFields_AutomationLog::LOG_MESSAGE
			);
			
		$join_sql = "FROM automation_log ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AutomationLog');
	
		return array(
			'primary_table' => 'automation_log',
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
			SearchFields_AutomationLog::ID,
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

class SearchFields_AutomationLog extends DevblocksSearchFields {
	const AUTOMATION_NAME = 'a_automation_name';
	const AUTOMATION_NODE = 'a_automation_node';
	const CREATED_AT = 'a_created_at';
	const ID = 'a_id';
	const LOG_LEVEL = 'a_log_level';
	const LOG_MESSAGE = 'a_log_message';

	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'automation_log.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('automation_log.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
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
			self::AUTOMATION_NAME => new DevblocksSearchField(self::AUTOMATION_NAME, 'automation_log', 'automation_name', DevblocksPlatform::translateCapitalized('common.automation'), null, true),
			self::AUTOMATION_NODE => new DevblocksSearchField(self::AUTOMATION_NODE, 'automation_log', 'automation_node', DevblocksPlatform::translateCapitalized('dao.automation_log.automation_node'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'automation_log', 'created_at', DevblocksPlatform::translateCapitalized('common.created'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'automation_log', 'id', $translate->_('common.id'), null, true),
			self::LOG_LEVEL => new DevblocksSearchField(self::LOG_LEVEL, 'automation_log', 'log_level', $translate->_('dao.automation_log.log_level'), null, true),
			self::LOG_MESSAGE => new DevblocksSearchField(self::LOG_MESSAGE, 'automation_log', 'log_message', DevblocksPlatform::translate('dao.automation_log.log_message'), null, true),
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

class Model_AutomationLog {
	public $id = 0;
	public $log_level = 7;
	public $log_message = null;
	public $automation_name = null;
	public $automation_node = null;
	public $created_at = 0;
};
