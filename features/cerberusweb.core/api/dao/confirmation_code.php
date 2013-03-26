<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class DAO_ConfirmationCode extends DevblocksORMHelper {
	const ID = 'id';
	const NAMESPACE_KEY = 'namespace_key';
	const CONFIRMATION_CODE = 'confirmation_code';
	const CREATED = 'created';
	const META_JSON = 'meta_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO confirmation_code () VALUES ()";
		$db->Execute($sql);
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
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, namespace_key, confirmation_code, created, meta_json ".
			"FROM confirmation_code ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ConfirmationCode	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 *
	 * @param unknown_type $namespace_key
	 * @param unknown_type $code
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
	 * @param resource $rs
	 * @return Model_ConfirmationCode[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ConfirmationCode();
			$object->id = $row['id'];
			$object->namespace_key = $row['namespace_key'];
			$object->confirmation_code = $row['confirmation_code'];
			$object->created = $row['created'];
			
			if(!empty($row['meta_json']) && false != (@$json = json_decode($row['meta_json'], true)))
				$object->meta = $json;
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		// Delete confirmation codes older than 12 hours
		$sql = sprintf("DELETE QUICK FROM confirmation_code WHERE created < %d", time() + 43200); // 60s*60m*12h
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' confirmation_code records.');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM confirmation_code WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ConfirmationCode::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
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
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'confirmation_code.id',
		//	$select_sql,
		//	$join_sql
		//);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'confirmation_code',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
	}
	
	/**
	 * Enter description here...
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
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY confirmation_code.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			$total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_ConfirmationCode::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT confirmation_code.id) " : "SELECT COUNT(confirmation_code.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_ConfirmationCode implements IDevblocksSearchFields {
	const ID = 'c_id';
	const NAMESPACE_KEY = 'c_namespace_key';
	const CREATED = 'c_created';
	const CONFIRMATION_CODE = 'c_confirmation_code';
	const META_JSON = 'c_meta_json';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'confirmation_code', 'id', $translate->_('common.iud')),
			self::NAMESPACE_KEY => new DevblocksSearchField(self::NAMESPACE_KEY, 'confirmation_code', 'namespace_key', $translate->_('dao.confirmation_code.namespace_key')),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'confirmation_code', 'created', $translate->_('common.created')),
			self::CONFIRMATION_CODE => new DevblocksSearchField(self::CONFIRMATION_CODE, 'confirmation_code', 'confirmation_code', $translate->_('dao.confirmation_code.confirmation_code')),
			self::META_JSON => new DevblocksSearchField(self::META_JSON, 'confirmation_code', 'meta_json'),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$namespace_key = 'cf_'.$field_id;
		//	$columns[$namespace_key] = new DevblocksSearchField($namespace_key,'field_value',$field->name);
		//}
		
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
};

