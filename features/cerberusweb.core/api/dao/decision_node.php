<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
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

class DAO_DecisionNode extends Cerb_ORMHelper {
	const ID = 'id';
	const NODE_TYPE = 'node_type';
	const PARAMS_JSON = 'params_json';
	const PARENT_ID = 'parent_id';
	const POS = 'pos';
	const STATUS_ID = 'status_id';
	const TITLE = 'title';
	const TRIGGER_ID = 'trigger_id';
	
	const CACHE_ALL = 'cerberus_cache_decision_nodes_all';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::NODE_TYPE)
			->string()
			->setMaxLength(16)
			->setRequired(true)
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength('32 bits')
			;
		$validation
			->addField(self::PARENT_ID)
			->id()
			;
		$validation
			->addField(self::POS)
			->uint(2)
			;
		$validation
			->addField(self::STATUS_ID)
			->uint(1)
			;
		$validation
			->addField(self::TITLE)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::TRIGGER_ID)
			->id()
			->setRequired(true)
			;
			
		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
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
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO decision_node (parent_id, trigger_id, node_type, title, pos, status_id, params_json) ".
			"SELECT %d, trigger_id, node_type, title, pos, status_id, params_json FROM decision_node WHERE id = %d",
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
		$cache = DevblocksPlatform::services()->cache();
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
		$db = DevblocksPlatform::services()->database();

		if(empty($sortBy)) {
			$sortBy = DAO_DecisionNode::POS;
			$sortAsc = true;
		}
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, parent_id, trigger_id, node_type, title, status_id, params_json, pos ".
			"FROM decision_node ".
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
	 * @param mysqli_result|false $rs
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
			$object->status_id = intval($row['status_id']);
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
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$ids_list = implode(',', self::qstrArray($ids));
		
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
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_DecisionNode', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"decision_node.id as %s, ".
			"decision_node.parent_id as %s, ".
			"decision_node.trigger_id as %s, ".
			"decision_node.node_type as %s, ".
			"decision_node.title as %s, ".
			"decision_node.status_id as %s, ".
			"decision_node.pos as %s, ".
			"decision_node.params_json as %s ",
				SearchFields_DecisionNode::ID,
				SearchFields_DecisionNode::PARENT_ID,
				SearchFields_DecisionNode::TRIGGER_ID,
				SearchFields_DecisionNode::NODE_TYPE,
				SearchFields_DecisionNode::TITLE,
				SearchFields_DecisionNode::STATUS_ID,
				SearchFields_DecisionNode::POS,
				SearchFields_DecisionNode::PARAMS_JSON
			);
			
		$join_sql = "FROM decision_node ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_DecisionNode');
	
		return array(
			'primary_table' => 'decision_node',
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
			SearchFields_DecisionNode::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}

	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_DecisionNode extends DevblocksSearchFields {
	const ID = 'd_id';
	const PARENT_ID = 'd_parent_id';
	const TRIGGER_ID = 'd_trigger_id';
	const NODE_TYPE = 'd_node_type';
	const TITLE = 'd_title';
	const STATUS_ID = 'd_status_id';
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
		if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
			return self::_getWhereSQLFromCustomFields($param);
		} else {
			return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_DecisionNode::ID:
				$models = DAO_DecisionNode::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
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
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'decision_node', 'id', $translate->_('common.id'), null, true),
			self::PARENT_ID => new DevblocksSearchField(self::PARENT_ID, 'decision_node', 'parent_id', $translate->_('common.parent'), null, true),
			self::TRIGGER_ID => new DevblocksSearchField(self::TRIGGER_ID, 'decision_node', 'trigger_id', $translate->_('dao.decision_node.trigger_id'), null, true),
			self::NODE_TYPE => new DevblocksSearchField(self::NODE_TYPE, 'decision_node', 'node_type', $translate->_('dao.decision_node.node_type'), null, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'decision_node', 'title', $translate->_('common.title'), null, true),
			self::STATUS_ID => new DevblocksSearchField(self::STATUS_ID, 'decision_node', 'status_id', $translate->_('common.status'), null, true),
			self::POS => new DevblocksSearchField(self::POS, 'decision_node', 'pos', $translate->_('common.order'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'decision_node', 'params_json', $translate->_('common.params'), null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_DecisionNode extends DevblocksRecordModel {
	public $id;
	public $parent_id;
	public $trigger_id;
	public $node_type;
	public $title;
	public $status_id;
	public $pos;
	public $params_json;
	public $params;
	
	function getNodes() {
		return DAO_DecisionNode::getByTriggerParent($this->trigger_id, $this->id);
	}
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
			SearchFields_DecisionNode::STATUS_ID,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_DecisionNode::ID,
			SearchFields_DecisionNode::PARENT_ID,
			SearchFields_DecisionNode::TRIGGER_ID,
			SearchFields_DecisionNode::PARAMS_JSON,
			SearchFields_DecisionNode::NODE_TYPE,
			SearchFields_DecisionNode::POS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_DecisionNode::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_DecisionNode');
		
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_DecisionNode', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		// [TODO] Set your template path
		//$tpl->display('devblocks:example.plugin::path/to/view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

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
			case SearchFields_DecisionNode::STATUS_ID:
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
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
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

