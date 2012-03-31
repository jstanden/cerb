<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
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

class DAO_ContextScheduledBehavior extends C4_ORMHelper {
	const ID = 'id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const BEHAVIOR_ID = 'behavior_id';
	const RUN_DATE = 'run_date';
	const VARIABLES_JSON = 'variables_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();

		$sql = "INSERT INTO context_scheduled_behavior () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields) {
		parent::_update($ids, 'context_scheduled_behavior', $fields);
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('context_scheduled_behavior', $fields, $where);
	}

	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContextScheduledBehavior[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		// SQL
		$sql = "SELECT id, context, context_id, behavior_id, run_date, variables_json ".
			"FROM context_scheduled_behavior ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;
		$rs = $db->Execute($sql);

		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ContextScheduledBehavior	 */
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
	 * Enter description here ...
	 * @param unknown_type $context
	 * @param unknown_type $context_id
	 * @return Model_ContextScheduledBehavior
	 */
	static public function getByContext($context, $context_id) {
		$objects = self::getWhere(
			sprintf("%s = %s AND %s = %d",
				self::CONTEXT,
				C4_ORMHelper::qstr($context),
				self::CONTEXT_ID,
				$context_id
			),
			self::RUN_DATE,
			true
		);

		return $objects;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_ContextScheduledBehavior[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();

		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ContextScheduledBehavior();
			$object->id = $row['id'];
			$object->context = $row['context'];
			$object->context_id = $row['context_id'];
			$object->behavior_id = $row['behavior_id'];
			$object->run_date = $row['run_date'];
			if(!empty($row['variables_json']))
				$object->variables = @json_decode($row['variables_json'], true);
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

		$db->Execute(sprintf("DELETE FROM context_scheduled_behavior WHERE id IN (%s)", $ids_list));

		return true;
	}
	
	static function deleteByBehavior($behavior_ids, $only_context=null, $only_context_id=null) {
		if(!is_array($behavior_ids)) $behavior_ids = array($behavior_ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($behavior_ids))
			return;

		DevblocksPlatform::sanitizeArray($behavior_ids, 'integer');
		$ids_list = implode(',', $behavior_ids);
		
		$wheres = array();
		
		$wheres[] = sprintf("behavior_id IN (%s)",
			$ids_list
		);
		
		// Are we limiting this delete to a single context or object?
		if(!empty($only_context)) {
			$wheres[] = sprintf("context = %s",
				$db->qstr($only_context)
			);
			
			if(!empty($only_context_id)) {
				$wheres[] = sprintf("context_id = %d",
					$only_context_id
				);
			}
		}
		
		// Join where clauses
		$where = implode(' AND ', $wheres);
		
		// Query
		$db->Execute(sprintf("DELETE FROM context_scheduled_behavior WHERE %s", $where));
		
		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContextScheduledBehavior::getFields();

		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);

		$select_sql = sprintf("SELECT ".
			"context_scheduled_behavior.id as %s, ".
			"context_scheduled_behavior.context as %s, ".
			"context_scheduled_behavior.context_id as %s, ".
			"context_scheduled_behavior.behavior_id as %s, ".
			"context_scheduled_behavior.run_date as %s, ".
			"context_scheduled_behavior.variables_json as %s, ".
			"trigger_event.title as %s, ".
			"trigger_event.owner_context as %s, ".
			"trigger_event.owner_context_id as %s ",
				SearchFields_ContextScheduledBehavior::ID,
				SearchFields_ContextScheduledBehavior::CONTEXT,
				SearchFields_ContextScheduledBehavior::CONTEXT_ID,
				SearchFields_ContextScheduledBehavior::BEHAVIOR_ID,
				SearchFields_ContextScheduledBehavior::RUN_DATE,
				SearchFields_ContextScheduledBehavior::VARIABLES_JSON,
				SearchFields_ContextScheduledBehavior::BEHAVIOR_NAME,
				SearchFields_ContextScheduledBehavior::BEHAVIOR_OWNER_CONTEXT,
				SearchFields_ContextScheduledBehavior::BEHAVIOR_OWNER_CONTEXT_ID
		);
			
		$join_sql = "FROM context_scheduled_behavior ".
			"INNER JOIN trigger_event ON (context_scheduled_behavior.behavior_id=trigger_event.id) "
			;

		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";

		// Translate virtual fields
		
		array_walk_recursive(
			$params,
			array('DAO_ContextScheduledBehavior', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'has_multiple_values' => &$has_multiple_values
			)
		);
		
		return array(
			'primary_table' => 'context_scheduled_behavior',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}

	private static function _translateVirtualParameters($param, $key, &$args) {
		$join_sql =& $args['join_sql'];
		$where_sql =& $args['where_sql']; 
		$has_multiple_values =& $args['has_multiple_values'];
		
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$param_key = $param->field;
		settype($param_key, 'string');
		switch($param_key) {
			case SearchFields_ContextScheduledBehavior::VIRTUAL_OWNER:
				//self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $join_sql, $where_sql);
				break;
			case SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET:
				break;
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
			($has_multiple_values ? 'GROUP BY context_scheduled_behavior.id ' : '').
			$sort_sql
			;
			
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
			$object_id = intval($row[SearchFields_ContextScheduledBehavior::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
			($has_multiple_values ? "SELECT COUNT(DISTINCT context_scheduled_behavior.id) " : "SELECT COUNT(context_scheduled_behavior.id) ").
			$join_sql.
			$where_sql;
			$total = $db->GetOne($count_sql);
		}

		mysql_free_result($rs);

		return array($results,$total);
	}

	static function buildVariables($var_keys, $var_vals, $trigger) {
		$vars = array();
		foreach($var_keys as $idx => $var) {
			if(isset($var_vals[$idx])) {
				@$var_mft = $trigger->variables[$var];
				$val = $var_vals[$idx];
				
				if(!empty($var_mft)) {
					switch($var_mft['type']) {
						case Model_CustomField::TYPE_DATE:
							@$val = strtotime($val);				
							break;
					}
				}
				
				// Parse dates
				$vars[$var] = $val;
			}
		}
		return $vars;
	}
};

class SearchFields_ContextScheduledBehavior implements IDevblocksSearchFields {
	const ID = 'c_id';
	const CONTEXT = 'c_context';
	const CONTEXT_ID = 'c_context_id';
	const BEHAVIOR_ID = 'c_behavior_id';
	const RUN_DATE = 'c_run_date';
	const VARIABLES_JSON = 'c_variables_json';
	
	const BEHAVIOR_NAME = 'b_behavior_name';
	const BEHAVIOR_OWNER_CONTEXT = 'b_behavior_owner_context';
	const BEHAVIOR_OWNER_CONTEXT_ID = 'b_behavior_owner_context_id';
	
	const VIRTUAL_OWNER = '*_owner';
	const VIRTUAL_TARGET = '*_target';

	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();

		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'context_scheduled_behavior', 'id', $translate->_('common.id')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'context_scheduled_behavior', 'context', $translate->_('common.context')),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'context_scheduled_behavior', 'context_id', $translate->_('common.context_id')),
			self::BEHAVIOR_ID => new DevblocksSearchField(self::BEHAVIOR_ID, 'context_scheduled_behavior', 'behavior_id', $translate->_('common.behavior')),
			self::RUN_DATE => new DevblocksSearchField(self::RUN_DATE, 'context_scheduled_behavior', 'run_date', $translate->_('dao.context_scheduled_behavior.run_date')),
			self::VARIABLES_JSON => new DevblocksSearchField(self::VARIABLES_JSON, 'context_scheduled_behavior', 'variables_json', $translate->_('dao.context_scheduled_behavior.variables_json')),
			
			self::BEHAVIOR_NAME => new DevblocksSearchField(self::BEHAVIOR_NAME, 'trigger_event', 'title', $translate->_('common.name')),
			self::BEHAVIOR_OWNER_CONTEXT => new DevblocksSearchField(self::BEHAVIOR_OWNER_CONTEXT, 'trigger_event', 'owner_context', $translate->_('dao.trigger_event.owner_context')),
			self::BEHAVIOR_OWNER_CONTEXT_ID => new DevblocksSearchField(self::BEHAVIOR_OWNER_CONTEXT_ID, 'trigger_event', 'owner_context_id', $translate->_('dao.trigger_event.owner_context_id')),

			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner')),
			self::VIRTUAL_TARGET => new DevblocksSearchField(self::VIRTUAL_TARGET, '*', 'target', $translate->_('common.target')),
		);

		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;
	}
};

class Model_ContextScheduledBehavior {
	public $id;
	public $context;
	public $context_id;
	public $behavior_id;
	public $run_date;
	public $variables = array();
};

class View_ContextScheduledBehavior extends C4_AbstractView {
	const DEFAULT_ID = 'contextscheduledbehavior';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();

		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Scheduled Behavior');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ContextScheduledBehavior::RUN_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ContextScheduledBehavior::RUN_DATE,
			SearchFields_ContextScheduledBehavior::BEHAVIOR_NAME,
			SearchFields_ContextScheduledBehavior::VIRTUAL_OWNER,
			SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET,
		);
		$this->addColumnsHidden(array(
			SearchFields_ContextScheduledBehavior::BEHAVIOR_ID,
			SearchFields_ContextScheduledBehavior::BEHAVIOR_OWNER_CONTEXT,
			SearchFields_ContextScheduledBehavior::BEHAVIOR_OWNER_CONTEXT_ID,
			SearchFields_ContextScheduledBehavior::CONTEXT,
			SearchFields_ContextScheduledBehavior::CONTEXT_ID,
			SearchFields_ContextScheduledBehavior::ID,
			SearchFields_ContextScheduledBehavior::VARIABLES_JSON,
		));

		$this->addParamsHidden(array(
			SearchFields_ContextScheduledBehavior::BEHAVIOR_ID,
			SearchFields_ContextScheduledBehavior::BEHAVIOR_OWNER_CONTEXT,
			SearchFields_ContextScheduledBehavior::BEHAVIOR_OWNER_CONTEXT_ID,
			SearchFields_ContextScheduledBehavior::CONTEXT,
			SearchFields_ContextScheduledBehavior::CONTEXT_ID,
			SearchFields_ContextScheduledBehavior::ID,
			SearchFields_ContextScheduledBehavior::VARIABLES_JSON,
			SearchFields_ContextScheduledBehavior::VIRTUAL_OWNER,
			SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET,
		));

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ContextScheduledBehavior::search(
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
		return $this->_doGetDataSample('DAO_ContextScheduledBehavior', $size);
	}

	function render() {
		$this->_sanitize();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		switch($this->renderTemplate) {
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/va/scheduled_behavior/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_ContextScheduledBehavior::BEHAVIOR_NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_ContextScheduledBehavior::RUN_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
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
		return SearchFields_ContextScheduledBehavior::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContextScheduledBehavior::BEHAVIOR_NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_ContextScheduledBehavior::RUN_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m

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
					//$change_fields[DAO_ContextScheduledBehavior::EXAMPLE] = 'some value';
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_ContextScheduledBehavior::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_ContextScheduledBehavior::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));

		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
				
			DAO_ContextScheduledBehavior::update($batch_ids, $change_fields);
	
			unset($batch_ids);
		}

		unset($ids);
	}
};

	