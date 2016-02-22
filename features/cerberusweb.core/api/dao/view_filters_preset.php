<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class DAO_ViewFiltersPreset extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const VIEW_CLASS = 'view_class';
	const WORKER_ID = 'worker_id';
	const PARAMS_JSON = 'params_json';
	const SORT_JSON = 'sort_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO view_filters_preset () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'view_filters_preset', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('view_filters_preset', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ViewFiltersPreset[]
	 */
	static function getWhere($where=null, $sortBy='name', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, view_class, worker_id, params_json, sort_json ".
			"FROM view_filters_preset ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ViewFiltersPreset	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_ViewFiltersPreset[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ViewFiltersPreset();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->view_class = $row['view_class'];
			$object->worker_id = $row['worker_id'];
			$object->params = DAO_WorkerViewModel::decodeParamsJson($row['params_json']);
			
			// Sorting
			if(!empty($row['sort_json'])) {
				$sort_json = json_decode($row['sort_json'], true);
				if(isset($sort_json['by']))
					$object->sort_by = $sort_json['by'];
				if(isset($sort_json['asc']))
					$object->sort_asc = $sort_json['asc'];
			}
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM view_filters_preset WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ViewFiltersPreset::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"view_filters_preset.id as %s, ".
			"view_filters_preset.name as %s, ".
			"view_filters_preset.view_class as %s, ".
			"view_filters_preset.worker_id as %s ",
				SearchFields_ViewFiltersPreset::ID,
				SearchFields_ViewFiltersPreset::NAME,
				SearchFields_ViewFiltersPreset::VIEW_CLASS,
				SearchFields_ViewFiltersPreset::WORKER_ID
			);
			
		$join_sql = "FROM view_filters_preset ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'view_filters_preset.id',
		//	$select_sql,
		//	$join_sql
		//);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields);
		
		$result = array(
			'primary_table' => 'view_filters_preset',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
		
		return $result;
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
			($has_multiple_values ? 'GROUP BY view_filters_preset.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_ViewFiltersPreset::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT view_filters_preset.id) " : "SELECT COUNT(view_filters_preset.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class Model_ViewFiltersPreset {
	public $id;
	public $name;
	public $view_class;
	public $worker_id;
	public $params;
	public $sort_by;
	public $sort_asc;
};

class SearchFields_ViewFiltersPreset implements IDevblocksSearchFields {
	const ID = 'v_id';
	const NAME = 'v_name';
	const VIEW_CLASS = 'v_view_class';
	const WORKER_ID = 'v_worker_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'view_filters_preset', 'id', $translate->_('dao.view_filters_preset.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'view_filters_preset', 'name', $translate->_('dao.view_filters_preset.name'), null, true),
			self::VIEW_CLASS => new DevblocksSearchField(self::VIEW_CLASS, 'view_filters_preset', 'view_class', $translate->_('dao.view_filters_preset.view_class'), null, true),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'view_filters_preset', 'worker_id', $translate->_('dao.view_filters_preset.worker_id'), null, true),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};
