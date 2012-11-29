<?php
/***********************************************************************
 | Cerb(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class DAO_WorkspaceWidget extends C4_ORMHelper {
	const ID = 'id';
	const EXTENSION_ID = 'extension_id';
	const WORKSPACE_TAB_ID = 'workspace_tab_id';
	const LABEL = 'label';
	const UPDATED_AT = 'updated_at';
	const PARAMS_JSON = 'params_json';
	const POS = 'pos';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO workspace_widget () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'workspace_widget', $fields);
		
		// Log the context update
		//DevblocksPlatform::markContextChanged('example.context', $ids);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_widget', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WorkspaceWidget[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, extension_id, workspace_tab_id, label, updated_at, params_json, pos ".
			"FROM workspace_widget ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WorkspaceWidget
	 */
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
	 * @param resource $rs
	 * @return Model_WorkspaceWidget[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WorkspaceWidget();
			$object->id = $row['id'];
			$object->extension_id = $row['extension_id'];
			$object->workspace_tab_id = $row['workspace_tab_id'];
			$object->label = $row['label'];
			$object->updated_at = $row['updated_at'];
			$object->pos = $row['pos'];
			
			if(false != ($params = @json_decode($row['params_json'], true)))
				$object->params = $params;
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM workspace_widget WHERE id IN (%s)", $ids_list));
		
		// Fire event
		/*
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerberusweb.contexts.',
					'context_ids' => $ids
				)
			)
		);
		*/
		
		return true;
	}
	
	static function deleteByTab($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM workspace_widget WHERE workspace_tab_id IN (%s)", $ids_list));		
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WorkspaceWidget::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"workspace_widget.id as %s, ".
			"workspace_widget.extension_id as %s, ".
			"workspace_widget.workspace_tab_id as %s, ".
			"workspace_widget.label as %s, ".
			"workspace_widget.updated_at as %s, ".
			"workspace_widget.params_json as %s, ".
			"workspace_widget.pos as %s ",
				SearchFields_WorkspaceWidget::ID,
				SearchFields_WorkspaceWidget::EXTENSION_ID,
				SearchFields_WorkspaceWidget::WORKSPACE_TAB_ID,
				SearchFields_WorkspaceWidget::LABEL,
				SearchFields_WorkspaceWidget::UPDATED_AT,
				SearchFields_WorkspaceWidget::PARAMS_JSON,
				SearchFields_WorkspaceWidget::POS
			);
			
		$join_sql = "FROM workspace_widget ";
		
		$has_multiple_values = false;
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		array_walk_recursive(
			$params,
			array('DAO_WorkspaceWidget', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'has_multiple_values' => &$has_multiple_values
			)
		);
	
		return array(
			'primary_table' => 'workspace_widget',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		//$from_context = CerberusContexts::CONTEXT_EXAMPLE;
		//$from_index = 'example.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			/*
			case SearchFields_EXAMPLE::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
			*/
		}
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
			($has_multiple_values ? 'GROUP BY workspace_widget.id ' : '').
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
			$object_id = intval($row[SearchFields_WorkspaceWidget::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT workspace_widget.id) " : "SELECT COUNT(workspace_widget.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_WorkspaceWidget implements IDevblocksSearchFields {
	const ID = 'w_id';
	const EXTENSION_ID = 'w_extension_id';
	const WORKSPACE_TAB_ID = 'w_workspace_tab_id';
	const LABEL = 'w_label';
	const UPDATED_AT = 'w_updated_at';
	const PARAMS_JSON = 'w_params_json';
	const POS = 'w_pos';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'workspace_widget', 'id', $translate->_('common.id')),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'workspace_widget', 'extension_id', $translate->_('common.extension')),
			self::WORKSPACE_TAB_ID => new DevblocksSearchField(self::WORKSPACE_TAB_ID, 'workspace_widget', 'workspace_tab_id', null),
			self::LABEL => new DevblocksSearchField(self::LABEL, 'workspace_widget', 'label', $translate->_('common.label')),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'workspace_widget', 'updated_at', $translate->_('common.updated')),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'workspace_widget', 'params_json', $translate->_('common.params')),
			self::POS => new DevblocksSearchField(self::POS, 'workspace_widget', 'pos', null),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_WorkspaceWidget {
	public $id = 0;
	public $extension_id = '';
	public $workspace_tab_id = 0;
	public $label = '';
	public $updated_at = 0;
	public $params = array();
};
