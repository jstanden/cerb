<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class DAO_ConfirmationCode extends Cerb_ORMHelper {
	const CONFIRMATION_CODE = 'confirmation_code';
	const CREATED = 'created';
	const FAILED_ATTEMPTS = 'failed_attempts';
	const ID = 'id';
	const META_JSON = 'meta_json';
	const NAMESPACE_KEY = 'namespace_key';
	
	const TTL_SECS = 900;
	const TTL_INVITE_SECS = 7200;
	const MAX_FAILED_ATTEMPTS = 3;
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CONFIRMATION_CODE)
			->string()
			->setMaxLength(64)
			->setUnique(__CLASS__)
			->setRequired(true)
			;
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
		$validation
			->addField(self::FAILED_ATTEMPTS)
			->uint(4)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::META_JSON)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::NAMESPACE_KEY)
			->string()
			;
			
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO confirmation_code () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		if(!isset($fields[self::CREATED]))
			$fields[self::CREATED] = time();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'confirmation_code', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('confirmation_code', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ConfirmationCode[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, namespace_key, confirmation_code, created, meta_json, failed_attempts ".
			"FROM confirmation_code ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->QueryReader($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ConfirmationCode	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(array_key_exists($id, $objects))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 *
	 * @param string $namespace_key
	 * @param string $code
	 * @return Model_ConfirmationCode
	 */
	static function getByCode($namespace_key, $code) {
		$results = self::getWhere(sprintf("%s = %s AND %s = %s",
			self::NAMESPACE_KEY,
			Cerb_ORMHelper::qstr($namespace_key),
			self::CONFIRMATION_CODE,
			Cerb_ORMHelper::qstr($code)
		));
		
		if(is_array($results))
			return array_shift($results);
			
		return NULL;
	}
	
	/**
	 * The `meta_json` match is an exact string comparison, so $meta must be built in the same key
	 * order the code was created with.
	 *
	 * @param string $namespace_key
	 * @param array $meta
	 * @return Model_ConfirmationCode|null
	 */
	static function getByMeta($namespace_key, array $meta) {
		$results = self::getWhere(sprintf("%s = %s AND %s = %s",
			Cerb_ORMHelper::escape(self::NAMESPACE_KEY),
			Cerb_ORMHelper::qstr($namespace_key),
			Cerb_ORMHelper::escape(self::META_JSON),
			Cerb_ORMHelper::qstr(json_encode($meta))
		), self::CREATED, false, 1);
		
		if(is_array($results))
			return array_shift($results);
		
		return null;
	}
	
	/**
	 * @param integer $id
	 * @return int the running total of failed attempts against this code
	 */
	static function recordFailedAttempt($id) {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("UPDATE confirmation_code SET failed_attempts = failed_attempts + 1 WHERE id = %d",
			$id
		));
		
		return intval($db->GetOneMaster(sprintf("SELECT failed_attempts FROM confirmation_code WHERE id = %d",
			$id
		)));
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_ConfirmationCode[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ConfirmationCode();
			$object->id = $row['id'];
			$object->namespace_key = $row['namespace_key'];
			$object->confirmation_code = $row['confirmation_code'];
			$object->created = $row['created'];
			
			$object->failed_attempts = intval($row['failed_attempts']);
			
			if(!empty($row['meta_json']) && false != ($json = json_decode($row['meta_json'], true)))
				$object->meta = $json;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * How long a code in each namespace stays valid. Consumers enforce this at verification time;
	 * maint() is what stops a dead code from sitting in the table afterward.
	 *
	 * @return array
	 */
	static function getNamespaceTTLs() : array {
		return [
			'login.invite' => self::TTL_INVITE_SECS,
			'support_center.email.confirm' => self::TTL_SECS,
			'support_center.login.recover' => self::TTL_SECS,
			'support_center.login.register.verify' => self::TTL_SECS,
		];
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		$ttls = self::getNamespaceTTLs();
		
		foreach($ttls as $namespace_key => $ttl_secs) {
			$db->ExecuteMaster(sprintf("DELETE FROM confirmation_code WHERE namespace_key = %s AND created < %d",
				self::qstr($namespace_key),
				time() - $ttl_secs
			));
		}
		
		// A namespace we don't know the lifetime of belongs to a plugin, so fall back to 12 hours
		$db->ExecuteMaster(sprintf("DELETE FROM confirmation_code WHERE namespace_key NOT IN (%s) AND created < %d",
			implode(',', self::qstrArray(array_keys($ttls))),
			time() - 43200 // 60s*60m*12h
		));
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$ids_list = implode(',', self::qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM confirmation_code WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ConfirmationCode::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ConfirmationCode', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"confirmation_code.id as %s, ".
			"confirmation_code.namespace_key as %s, ".
			"confirmation_code.created as %s, ".
			"confirmation_code.confirmation_code as %s, ".
			"confirmation_code.meta_json as %s ",
				SearchFields_ConfirmationCode::ID,
				SearchFields_ConfirmationCode::NAMESPACE_KEY,
				SearchFields_ConfirmationCode::CREATED,
				SearchFields_ConfirmationCode::CONFIRMATION_CODE,
				SearchFields_ConfirmationCode::META_JSON
			);
			
		$join_sql = "FROM confirmation_code ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ConfirmationCode');
	
		return array(
			'primary_table' => 'confirmation_code',
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
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_ConfirmationCode::ID,
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

class SearchFields_ConfirmationCode extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAMESPACE_KEY = 'c_namespace_key';
	const CREATED = 'c_created';
	const CONFIRMATION_CODE = 'c_confirmation_code';
	const META_JSON = 'c_meta_json';
	
	static private $_fields = null;
	
	static function getTableName() : string {
		return 'confirmation_code';
	}
	
	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_ConfirmationCode::ID);
	}
	
	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_ConfirmationCode::CREATED);
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('confirmation_code.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ConfirmationCode::ID:
				$models = DAO_ConfirmationCode::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'confirmation_code', 'id');
				break;
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
		
		$columns = [
			self::ID => new DevblocksSearchField(self::ID, 'confirmation_code', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAMESPACE_KEY => new DevblocksSearchField(self::NAMESPACE_KEY, 'confirmation_code', 'namespace_key', $translate->_('dao.confirmation_code.namespace_key'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'confirmation_code', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::CONFIRMATION_CODE => new DevblocksSearchField(self::CONFIRMATION_CODE, 'confirmation_code', 'confirmation_code', $translate->_('dao.confirmation_code.confirmation_code'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::META_JSON => new DevblocksSearchField(self::META_JSON, 'confirmation_code', 'meta_json', null, null, false),
		];
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_ConfirmationCode {
	public $id;
	public $namespace_key;
	public $created;
	public $confirmation_code;
	public $meta;
	public $failed_attempts;
	
	function isExpired(int $ttl_secs=DAO_ConfirmationCode::TTL_SECS) : bool {
		return ($this->created + $ttl_secs) < time();
	}
};

