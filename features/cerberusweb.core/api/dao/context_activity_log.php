<?php
class DAO_ContextActivityLog extends C4_ORMHelper {
	const ID = 'id';
	const ACTIVITY_POINT = 'activity_point';
	const ACTOR_CONTEXT = 'actor_context';
	const ACTOR_CONTEXT_ID = 'actor_context_id';
	const TARGET_CONTEXT = 'target_context';
	const TARGET_CONTEXT_ID = 'target_context_id';
	const CREATED = 'created';
	const ENTRY_JSON = 'entry_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO context_activity_log () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'context_activity_log', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('context_activity_log', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContextActivityLog[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, activity_point, actor_context, actor_context_id, target_context, target_context_id, created, entry_json ".
			"FROM context_activity_log ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ContextActivityLog	 */
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
	 * @return Model_ContextActivityLog[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ContextActivityLog();
			$object->id = $row['id'];
			$object->activity_point = $row['activity_point'];
			$object->actor_context = $row['actor_context'];
			$object->actor_context_id = $row['actor_context_id'];
			$object->target_context = $row['target_context'];
			$object->target_context_id = $row['target_context_id'];
			$object->created = $row['created'];
			$object->entry_json = $row['entry_json'];
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
		
		$db->Execute(sprintf("DELETE FROM context_activity_log WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContextActivityLog::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"context_activity_log.id as %s, ".
			"context_activity_log.activity_point as %s, ".
			"context_activity_log.actor_context as %s, ".
			"context_activity_log.actor_context_id as %s, ".
			"context_activity_log.target_context as %s, ".
			"context_activity_log.target_context_id as %s, ".
			"context_activity_log.created as %s, ".
			"context_activity_log.entry_json as %s ",
				SearchFields_ContextActivityLog::ID,
				SearchFields_ContextActivityLog::ACTIVITY_POINT,
				SearchFields_ContextActivityLog::ACTOR_CONTEXT,
				SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
				SearchFields_ContextActivityLog::TARGET_CONTEXT,
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
				SearchFields_ContextActivityLog::CREATED,
				SearchFields_ContextActivityLog::ENTRY_JSON
			);
			
		$join_sql = "FROM context_activity_log ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'context_activity_log.id',
		//	$select_sql,
		//	$join_sql
		//);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'context_activity_log',
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
			($has_multiple_values ? 'GROUP BY context_activity_log.id ' : '').
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
			$object_id = intval($row[SearchFields_ContextActivityLog::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT context_activity_log.id) " : "SELECT COUNT(context_activity_log.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_ContextActivityLog implements IDevblocksSearchFields {
	const ID = 'c_id';
	const ACTIVITY_POINT = 'c_activity_point';
	const ACTOR_CONTEXT = 'c_actor_context';
	const ACTOR_CONTEXT_ID = 'c_actor_context_id';
	const TARGET_CONTEXT = 'c_target_context';
	const TARGET_CONTEXT_ID = 'c_target_context_id';
	const CREATED = 'c_created';
	const ENTRY_JSON = 'c_entry_json';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'context_activity_log', 'id', $translate->_('common.id')),
			self::ACTIVITY_POINT => new DevblocksSearchField(self::ACTIVITY_POINT, 'context_activity_log', 'activity_point', $translate->_('dao.context_activity_log.activity_point')),
			self::ACTOR_CONTEXT => new DevblocksSearchField(self::ACTOR_CONTEXT, 'context_activity_log', 'actor_context', $translate->_('dao.context_activity_log.actor_context')),
			self::ACTOR_CONTEXT_ID => new DevblocksSearchField(self::ACTOR_CONTEXT_ID, 'context_activity_log', 'actor_context_id', $translate->_('dao.context_activity_log.actor_context_id')),
			self::TARGET_CONTEXT => new DevblocksSearchField(self::TARGET_CONTEXT, 'context_activity_log', 'target_context', $translate->_('dao.context_activity_log.target_context')),
			self::TARGET_CONTEXT_ID => new DevblocksSearchField(self::TARGET_CONTEXT_ID, 'context_activity_log', 'target_context_id', $translate->_('dao.context_activity_log.target_context_id')),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'context_activity_log', 'created', $translate->_('common.created')),
			self::ENTRY_JSON => new DevblocksSearchField(self::ENTRY_JSON, 'context_activity_log', 'entry_json', $translate->_('dao.context_activity_log.entry')),
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

class Model_ContextActivityLog {
	public $id;
	public $activity_point;
	public $actor_context;
	public $actor_context_id;
	public $target_context;
	public $target_context_id;
	public $created;
	public $entry_json;
};

class View_ContextActivityLog extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'context_activity_log';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		// [TODO] Name the worklist view
		$this->name = 'Activity Log'; //$translate->_('ContextActivityLog');
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_ContextActivityLog::CREATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_ContextActivityLog::CREATED,
		);
		$this->addColumnsHidden(array(
			SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
			SearchFields_ContextActivityLog::TARGET_CONTEXT,
			SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
			SearchFields_ContextActivityLog::ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
			SearchFields_ContextActivityLog::TARGET_CONTEXT,
			SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
			SearchFields_ContextActivityLog::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ContextActivityLog::search(
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
		return $this->_doGetDataSample('DAO_ContextActivityLog', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
				case SearchFields_ContextActivityLog::TARGET_CONTEXT:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				$label_map = array();
				$translate = DevblocksPlatform::getTranslationService();
				
				$activities = DevblocksPlatform::getActivityPointRegistry();
				if(is_array($activities))
				foreach($activities as $k => $data) {
					@$string_id = $data['params']['label_key'];
					if(!empty($string_id)) {
						$label_map[$k] = $translate->_($string_id);
					}
				}
				$counts = $this->_getSubtotalCountForStringColumn('DAO_ContextActivityLog', $column, $label_map);
				break;
				
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
				$label_map = array();
				$contexts = Extension_DevblocksContext::getAll(false);
				
				foreach($contexts as $k => $mft) {
					$label_map[$k] = $mft->name;
				}
				
				$counts = $this->_getSubtotalCountForStringColumn('DAO_ContextActivityLog', $column, $label_map);
				break;

//			case SearchFields_ContextActivityLog::IS_COMPLETED:
//				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_Task', $column);
//				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Task', $column, 't.id');
				}
				
				break;
		}
		
		return $counts;
	}	
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/activity_log/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
			case SearchFields_ContextActivityLog::ENTRY_JSON:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_ContextActivityLog::ID:
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_ContextActivityLog::CREATED:
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
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
				$strings = array();
				$contexts = Extension_DevblocksContext::getAll(false);
				
				if(is_array($values))
				foreach($values as $v) {
					$string = $v;
					if(isset($contexts[$v])) {
						if(isset($contexts[$v]->name))
							$string = $contexts[$v]->name;
					}
					
					$strings[] = $string;
				}
				
				return implode(' or ', $strings);
				break;
				
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				$strings = array();
				
				$activities = DevblocksPlatform::getActivityPointRegistry();
				$translate = DevblocksPlatform::getTranslationService();
				
				if(is_array($values))
				foreach($values as $v) {
					$string = $v;
					if(isset($activities[$v])) {
						@$string_id = $activities[$v]['params']['label_key'];
						if(!empty($string_id))
							$string = $translate->_($string_id);
					}
					
					$strings[] = $string;
				}
				
				return implode(' or ', $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ContextActivityLog::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
			case SearchFields_ContextActivityLog::ENTRY_JSON:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_ContextActivityLog::ID:
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ContextActivityLog::CREATED:
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
		@set_time_limit(1200); // 20m
		
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
					//$change_fields[DAO_ContextActivityLog::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_ContextActivityLog::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_ContextActivityLog::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_ContextActivityLog::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_ContextActivityLog::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

