<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class DAO_DecisionNode extends Cerb_ORMHelper {
	const CACHE_ALL = 'cerberus_cache_decision_nodes_all';
	
	const ID = 'id';
	const PARENT_ID = 'parent_id';
	const TRIGGER_ID = 'trigger_id';
	const NODE_TYPE = 'node_type';
	const TITLE = 'title';
	const PARAMS_JSON = 'params_json';
	const POS = 'pos';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Automatically append to parent
		if(!isset($fields[self::POS])
			&& isset($fields[self::PARENT_ID])
			&& isset($fields[self::TRIGGER_ID])
			) {
			$pos = $db->GetOneMaster(sprintf("SELECT MAX(pos) FROM decision_node WHERE parent_id = %d AND trigger_id = %d",
				$fields[self::PARENT_ID],
				$fields[self::TRIGGER_ID]
			));
			$fields[self::POS] = empty($pos) ? intval($pos) : (1+intval($pos));
		}
		
		$sql = "INSERT INTO decision_node () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function duplicate($id, $new_parent_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO decision_node (parent_id, trigger_id, node_type, title, pos, params_json) ".
			"SELECT %d, trigger_id, node_type, title, pos, params_json FROM decision_node WHERE id = %d",
			$new_parent_id,
			$id
		);
		
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'decision_node', $fields);
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('decision_node', $fields, $where);
		self::clearCache();
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_DecisionNode[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		if($nocache || null === ($nodes = $cache->load(self::CACHE_ALL))) {
			$nodes = self::getWhere(
				array(),
				DAO_DecisionNode::POS,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($nodes))
				return false;
			
			$cache->save($nodes, self::CACHE_ALL);
		}
		
		return $nodes;
	}
	
	static function getByTriggerParent($trigger_id, $parent_id=null) {
		$nodes = self::getAll();
		$results = array();
		
		foreach($nodes as $node_id => $node) {
			/* @var $node Model_DecisionNode */
			if($node->trigger_id == $trigger_id) {
				if(!is_null($parent_id) && $node->parent_id != $parent_id)
					continue;
					
				$results[$node_id] = $node;
			}
		}
		
		return $results;
	}
	
	/**
	 * @param integer $id
	 * @return Model_DecisionNode
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$nodes = self::getAll();

		if(isset($nodes[$id]))
			return $nodes[$id];
			
		return null;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_DecisionNode[]
	 */
	static function getWhere($where=null, $sortBy=DAO_DecisionNode::POS, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		if(empty($sortBy)) {
			$sortBy = DAO_DecisionNode::POS;
			$sortAsc = true;
		}
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, parent_id, trigger_id, node_type, title, params_json, pos ".
			"FROM decision_node ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_DecisionNode[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_DecisionNode();
			$object->id = intval($row['id']);
			$object->parent_id = intval($row['parent_id']);
			$object->trigger_id = intval($row['trigger_id']);
			$object->node_type = $row['node_type'];
			$object->title = $row['title'];
			$object->pos = intval($row['pos']);
			
			$object->params_json = $row['params_json'];
			if(!empty($object->params_json))
				@$object->params = json_decode($object->params_json, true);
			
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
		
		$db->ExecuteMaster(sprintf("DELETE FROM decision_node WHERE id IN (%s)", $ids_list));
		
		self::clearCache();
		return true;
	}
	
	public static function deleteTriggerVar($trigger_id, $var) {
		// Nuke any deleted variables from criteria/actions
		$nodes = DAO_DecisionNode::getByTriggerParent($trigger_id);
		
		foreach($nodes as $node_id => $node) {
			$changed = false;
			$params = $node->params;
			
			switch($node->node_type) {
				case 'outcome':
					if(isset($params['groups']))
					foreach($params['groups'] as $group_idx => $group) {
						if(isset($group['conditions']))
						foreach($group['conditions'] as $cond_idx => $cond) {
							if(isset($cond['condition']) && $cond['condition'] == $var) {
								unset($params['groups'][$group_idx]['conditions'][$cond_idx]);
								$changed = true;
							}
						}
					}
					break;
					
				case 'action':
					if(isset($params['actions']))
					foreach($params['actions'] as $idx => $action) {
						if(isset($action['action']) && $action['action'] == $var) {
							unset($params['actions'][$idx]);
							$changed = true;
						}
					}
					break;
			}
			
			if($changed) {
				DAO_DecisionNode::update($node_id, array(
					DAO_DecisionNode::PARAMS_JSON => json_encode($params),
				));
			}
		}

		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_DecisionNode::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_DecisionNode', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"decision_node.id as %s, ".
			"decision_node.parent_id as %s, ".
			"decision_node.trigger_id as %s, ".
			"decision_node.node_type as %s, ".
			"decision_node.title as %s, ".
			"decision_node.pos as %s, ".
			"decision_node.params_json as %s ",
				SearchFields_DecisionNode::ID,
				SearchFields_DecisionNode::PARENT_ID,
				SearchFields_DecisionNode::TRIGGER_ID,
				SearchFields_DecisionNode::NODE_TYPE,
				SearchFields_DecisionNode::TITLE,
				SearchFields_DecisionNode::POS,
				SearchFields_DecisionNode::PARAMS_JSON
			);
			
		$join_sql = "FROM decision_node ";
		
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_DecisionNode');
	
		return array(
			'primary_table' => 'decision_node',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
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
			($has_multiple_values ? 'GROUP BY decision_node.id ' : '').
			$sort_sql;
			
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
			$object_id = intval($row[SearchFields_DecisionNode::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT decision_node.id) " : "SELECT COUNT(decision_node.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_DecisionNode extends DevblocksSearchFields {
	const ID = 'd_id';
	const PARENT_ID = 'd_parent_id';
	const TRIGGER_ID = 'd_trigger_id';
	const NODE_TYPE = 'd_node_type';
	const TITLE = 'd_title';
	const POS = 'd_pos';
	const PARAMS_JSON = 'd_params_json';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'decision_node.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('decision_node.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		if('cf_' == substr($param->field, 0, 3)) {
			return self::_getWhereSQLFromCustomFields($param);
		} else {
			return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
		}
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
			self::ID => new DevblocksSearchField(self::ID, 'decision_node', 'id', $translate->_('common.id'), null, true),
			self::PARENT_ID => new DevblocksSearchField(self::PARENT_ID, 'decision_node', 'parent_id', $translate->_('dao.decision_node.parent_id'), null, true),
			self::TRIGGER_ID => new DevblocksSearchField(self::TRIGGER_ID, 'decision_node', 'trigger_id', $translate->_('dao.decision_node.trigger_id'), null, true),
			self::NODE_TYPE => new DevblocksSearchField(self::NODE_TYPE, 'decision_node', 'node_type', $translate->_('dao.decision_node.node_type'), null, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'decision_node', 'title', $translate->_('common.title'), null, true),
			self::POS => new DevblocksSearchField(self::POS, 'decision_node', 'pos', $translate->_('common.order'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'decision_node', 'params_json', $translate->_('dao.decision_node.params'), null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_DecisionNode {
	public $id;
	public $parent_id;
	public $trigger_id;
	public $node_type;
	public $title;
	public $pos;
	public $params_json;
	public $params;
};

class View_DecisionNode extends C4_AbstractView {
	const DEFAULT_ID = 'decisionnode';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Decisions');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_DecisionNode::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_DecisionNode::TITLE,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_DecisionNode::ID,
			SearchFields_DecisionNode::PARENT_ID,
			SearchFields_DecisionNode::TRIGGER_ID,
			SearchFields_DecisionNode::PARAMS_JSON,
			SearchFields_DecisionNode::NODE_TYPE,
			SearchFields_DecisionNode::POS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_DecisionNode::ID,
			SearchFields_DecisionNode::PARENT_ID,
			SearchFields_DecisionNode::TRIGGER_ID,
			SearchFields_DecisionNode::PARAMS_JSON,
			SearchFields_DecisionNode::NODE_TYPE,
			SearchFields_DecisionNode::POS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_DecisionNode::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_DecisionNode');
		
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_DecisionNode', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		// [TODO] Set your template path
		$tpl->display('devblocks:example.plugin::path/to/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		// [TODO] Move the fields into the proper data type
		switch($field) {
			case SearchFields_DecisionNode::ID:
			case SearchFields_DecisionNode::PARENT_ID:
			case SearchFields_DecisionNode::TRIGGER_ID:
			case SearchFields_DecisionNode::NODE_TYPE:
			case SearchFields_DecisionNode::TITLE:
			case SearchFields_DecisionNode::POS:
			case SearchFields_DecisionNode::PARAMS_JSON:
			case 'placeholder_string':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			/*
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
			*/
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_DecisionNode::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		// [TODO] Move fields into the right data type
		switch($field) {
			case SearchFields_DecisionNode::ID:
			case SearchFields_DecisionNode::PARENT_ID:
			case SearchFields_DecisionNode::TRIGGER_ID:
			case SearchFields_DecisionNode::NODE_TYPE:
			case SearchFields_DecisionNode::TITLE:
			case SearchFields_DecisionNode::POS:
			case SearchFields_DecisionNode::PARAMS_JSON:
			case 'placeholder_string':
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			/*
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
			*/
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

