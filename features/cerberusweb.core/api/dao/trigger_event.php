<?php
class DAO_TriggerEvent extends C4_ORMHelper {
	const CACHE_ALL = 'cerberus_cache_behavior_all';
	
	const ID = 'id';
	const TITLE = 'title';
	const IS_DISABLED = 'is_disabled';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const EVENT_POINT = 'event_point';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO trigger_event () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'trigger_event', $fields);
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('trigger_event', $fields, $where);
		self::clearCache();
	}
	
	/**
	 * 
	 * @param bool $nocache
	 * @return Model_TriggerEvent[]
	 */
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($behaviors = $cache->load(self::CACHE_ALL))) {
    	    $behaviors = self::getWhere();
    	    $cache->save($behaviors, self::CACHE_ALL);
	    }
	    
	    return $behaviors;
	}
	
	/**
	 * 
	 * @param string $context
	 * @param integer $context_id
	 * @return Model_TriggerEvent[]
	 */
	static function getByOwner($context, $context_id) {
		$behaviors = self::getAll();
		$results = array();

		foreach($behaviors as $behavior_id => $behavior) {
			if($behavior->owner_context == $context
				&& $behavior->owner_context_id == $context_id)
					$results[$behavior_id] = $behavior;
		}
		
		return $results;
	}
	
	static function getByEvent($event_id, $with_disabled=false) {
		$behaviors = self::getAll();
		$results = array();
		
		foreach($behaviors as $behavior_id => $behavior) {
			/* @var $behavior Model_TriggerEvent */
			if($behavior->event_point == $event_id) {
				if(!$with_disabled && $behavior->is_disabled)
					continue;
				
				$results[$behavior_id] = $behavior;
			}
		}
		
		return $results;
	}
	
	/**
	 * @param integer $id
	 * @return Model_TriggerEvent
	 */
	static function get($id) {
		$behaviors = self::getAll();
		
		if(isset($behaviors[$id]))
			return $behaviors[$id];
		
		return null;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_TriggerEvent[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, title, is_disabled, owner_context, owner_context_id, event_point ".
			"FROM trigger_event ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_TriggerEvent[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_TriggerEvent();
			$object->id = intval($row['id']);
			$object->title = $row['title'];
			$object->is_disabled = intval($row['is_disabled']);
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = intval($row['owner_context_id']);
			$object->event_point = $row['event_point'];
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
		
		// [TODO] Use DAO_DecisionNode::deleteByTrigger() to cascade
		$db->Execute(sprintf("DELETE FROM decision_node WHERE trigger_id IN (%s)", $ids_list));
		
		$db->Execute(sprintf("DELETE FROM trigger_event WHERE id IN (%s)", $ids_list));
		
		self::clearCache();
		return true;
	}
	
	static function deleteByOwner($context, $context_id) {
		$results = self::getWhere(sprintf("%s = %s AND %s = %d",
			self::OWNER_CONTEXT,
			$context,
			self::OWNER_CONTEXT_ID,
			$context_id
		));
		
		if(is_array($results))
		foreach($results as $result) {
			self::delete($result->id);
		}
		
		return TRUE;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_TriggerEvent::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"trigger_event.id as %s, ".
			"trigger_event.title as %s, ".
			"trigger_event.is_disabled as %s, ".
			"trigger_event.owner_context as %s, ".
			"trigger_event.owner_context_id as %s, ".
			"trigger_event.event_point as %s ",
				SearchFields_TriggerEvent::ID,
				SearchFields_TriggerEvent::TITLE,
				SearchFields_TriggerEvent::IS_DISABLED,
				SearchFields_TriggerEvent::OWNER_CONTEXT,
				SearchFields_TriggerEvent::OWNER_CONTEXT_ID,
				SearchFields_TriggerEvent::EVENT_POINT
			);
			
		$join_sql = "FROM trigger_event ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'trigger.id',
		//	$select_sql,
		//	$join_sql
		//);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'trigger_event',
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
			($has_multiple_values ? 'GROUP BY trigger_event.id ' : '').
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
			$object_id = intval($row[SearchFields_TriggerEvent::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT trigger_event.id) " : "SELECT COUNT(trigger_event.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class SearchFields_TriggerEvent implements IDevblocksSearchFields {
	const ID = 't_id';
	const TITLE = 't_title';
	const IS_DISABLED = 't_is_disabled';
	const OWNER_CONTEXT = 't_owner_context';
	const OWNER_CONTEXT_ID = 't_owner_context_id';
	const EVENT_POINT = 't_event_point';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'trigger_event', 'id', $translate->_('common.id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'trigger_event', 'title', $translate->_('common.title')),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'trigger_event', 'is_disabled', $translate->_('dao.trigger_event.is_disabled')),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'trigger_event', 'owner_context', $translate->_('dao.trigger_event.owner_context')),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'trigger_event', 'owner_context_id', $translate->_('dao.trigger_event.owner_context_id')),
			self::EVENT_POINT => new DevblocksSearchField(self::EVENT_POINT, 'trigger_event', 'event_point', $translate->_('common.event')),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		//}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_TriggerEvent {
	public $id;
	public $title;
	public $is_disabled;
	public $owner_context;
	public $owner_context_id;
	public $event_point;
	
	private $_nodes = array();
	
	/**
	 * @return Extension_DevblocksEvent
	 */
	public function getEvent() {
		if(null == ($event = DevblocksPlatform::getExtension($this->event_point, true))
			|| !$event instanceof Extension_DevblocksEvent)
			return NULL;
		
		return $event;
	}
	
	private function _getNodes() {
		if(empty($this->_nodes))
			$this->_nodes = DAO_DecisionNode::getByTriggerParent($this->id);
		
		return $this->_nodes;
	}
	
	public function getDecisionTreeData() {
		$nodes = $this->_getNodes();
		$tree = $this->_getTree();
		$depths = array();
		$this->_recurseBuildTreeDepths($tree, 0, $depths);
		
		return array('nodes' => $nodes, 'tree' => $tree, 'depths' => $depths);
	}
	
	private function _getTree() {
		$nodes = $this->_getNodes();
		$tree = array(0 => array()); // root
		
		foreach($nodes as $node_id => $node) {
			if(!isset($tree[$node->id]))
				$tree[$node->id] = array();
				
			// Parent chain
			if(!isset($tree[$node->parent_id]))
				$tree[$node->parent_id] = array();
			
			$tree[$node->parent_id][$node->id] = $node->id;
		}
		
		return $tree;
	}
	
	private function _recurseBuildTreeDepths($tree, $node_id, &$out, $depth=0) {
		foreach($tree[$node_id] as $child_id) {
			$out[$child_id] = $depth;
			$this->_recurseBuildTreeDepths($tree, $child_id, $out, $depth+1);
		}
	}
	
	public function runDecisionTree(&$dictionary) {
		$nodes = $this->_getNodes();
		$tree = $this->_getTree();
		$path = array();
		
		// [TODO] This could be more efficient
		$event = DevblocksPlatform::getExtension($this->event_point, true); /* @var $event Extension_DevblocksEvent */
		//var_dump($event);
		
		$this->_recurseRunTree($event, $nodes, $tree, 0, $dictionary, $path);
		
		// [TODO] Run actions in bulk, or run inline?
		
		return $path;
	}
	
	private function _recurseRunTree($event, $nodes, $tree, $node_id, &$dictionary, &$path) {
		$logger = DevblocksPlatform::getConsoleLog("Assistant");
		// Does our current node pass?
		$pass = true;
		
		// If these conditions match...
		if(!empty($node_id)) {
			$logger->info($nodes[$node_id]->node_type . ' :: ' . $nodes[$node_id]->title . ' (' . $node_id . ')');
//			var_dump($nodes[$node_id]->node_type);
//			var_dump($nodes[$node_id]->params);
			
			// Handle the node type
			switch($nodes[$node_id]->node_type) {
				case 'outcome':
					@$cond_groups = $nodes[$node_id]->params['groups'];
					
					if(is_array($cond_groups))
					foreach($cond_groups as $cond_group) {
						@$any = intval($cond_group['any']);
						@$conditions = $cond_group['conditions'];
						$group_pass = true;
						$logger->info(sprintf("Conditions are in '%s' group.", ($any ? 'any' : 'all')));
						
						if(!empty($conditions) && is_array($conditions))
						foreach($conditions as $condition_data) {
							// If something failed and we require all to pass
							if(!$group_pass && empty($any))
								continue;
								
							if(!isset($condition_data['condition']))
								continue;
								
							$condition = $condition_data['condition'];
							
							$group_pass = $event->runCondition($condition, $this, $condition_data, $dictionary);
							
							// Any
							if($group_pass && !empty($any))
								break;
						}
						
						$pass = $group_pass;
						
						// Any condition group failing is enough to stop
						if(empty($pass))
							break;
					}
					
					if($pass)
						EventListener_Triggers::logNode($node_id);
					break;
					
				case 'switch':
					$pass = true;
					EventListener_Triggers::logNode($node_id);
					break;
					
				case 'action':
					$pass = true;
					EventListener_Triggers::logNode($node_id);
					
					// Run all the actions
					if(is_array(@$nodes[$node_id]->params['actions']))
					foreach($nodes[$node_id]->params['actions'] as $params) {
						if(!isset($params['action']))
							continue;

						$action = $params['action'];
						$event->runAction($action, $this, $params, $dictionary);
					}
					break;
			}
			
			if($nodes[$node_id]->node_type == 'outcome') {
				$logger->info($pass ? '...PASS' : '...FAIL');
			}
			$logger->info('');
		}
		
		if($pass)
			$path[$node_id] = $pass;

		$switch = false;
		foreach($tree[$node_id] as $child_id) {
			// Then continue navigating down the tree...
			$parent_type = empty($node_id) ? 'outcome' : $nodes[$node_id]->node_type;
			$child_type = $nodes[$child_id]->node_type;
			
			switch($child_type) {
				// Always run all actions
				case 'action':
					if($pass)
						$this->_recurseRunTree($event, $nodes, $tree, $child_id, $dictionary, $path);
					break;
					
				default:
					switch($parent_type) {
						case 'outcome':
							if($pass)
								$this->_recurseRunTree($event, $nodes, $tree, $child_id, $dictionary, $path);
							break;
							
						case 'switch':
							// Only run the first successful child outcome
							if($pass && !$switch)
								if($this->_recurseRunTree($event, $nodes, $tree, $child_id, $dictionary, $path))
									$switch = true;
							break;
							
						case 'action':
							// No children
							break;
					}
					break;
			}
		}
		
		return $pass;
	}
};

class View_TriggerEvent extends C4_AbstractView {
	const DEFAULT_ID = 'trigger';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		// [TODO] Name the worklist view
		$this->name = $translate->_('Trigger');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_TriggerEvent::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_TriggerEvent::ID,
			SearchFields_TriggerEvent::TITLE,
			SearchFields_TriggerEvent::IS_DISABLED,
			SearchFields_TriggerEvent::OWNER_CONTEXT,
			SearchFields_TriggerEvent::OWNER_CONTEXT_ID,
			SearchFields_TriggerEvent::EVENT_POINT,
		);
		// [TODO] Filter fields
		$this->addColumnsHidden(array(
		));
		
		// [TODO] Filter fields
		$this->addParamsHidden(array(
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_TriggerEvent::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_TriggerEvent', $size);
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
			case SearchFields_TriggerEvent::ID:
			case SearchFields_TriggerEvent::TITLE:
			case SearchFields_TriggerEvent::IS_DISABLED:
			case SearchFields_TriggerEvent::OWNER_CONTEXT:
			case SearchFields_TriggerEvent::OWNER_CONTEXT_ID:
			case SearchFields_TriggerEvent::EVENT_POINT:
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
		return SearchFields_TriggerEvent::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		// [TODO] Move fields into the right data type
		switch($field) {
			case SearchFields_TriggerEvent::ID:
			case SearchFields_TriggerEvent::TITLE:
			case SearchFields_TriggerEvent::IS_DISABLED:
			case SearchFields_TriggerEvent::OWNER_CONTEXT:
			case SearchFields_TriggerEvent::OWNER_CONTEXT_ID:
			case SearchFields_TriggerEvent::EVENT_POINT:
			case 'placeholder_string':
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
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
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_TriggerEvent::EXAMPLE] = 'some value';
					break;
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_TriggerEvent::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_TriggerEvent::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_TriggerEvent::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_TriggerEvent::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

