<?php
class DAO_Task extends C4_ORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const WORKER_ID = 'worker_id';
	const UPDATED_DATE = 'updated_date';
	const DUE_DATE = 'due_date';
	const IS_COMPLETED = 'is_completed';
	const COMPLETED_DATE = 'completed_date';
	const SOURCE_EXTENSION = 'source_extension';
	const SOURCE_ID = 'source_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('task_seq');
		
		$sql = sprintf("INSERT INTO task (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'task', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('task', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_Task[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, title, worker_id, due_date, updated_date, is_completed, completed_date, source_extension, source_id ".
			"FROM task ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Task	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getUnassignedSourceTotals() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$totals = array();
		
		$sql = "SELECT count(id) as hits, source_extension ".
			"FROM task ".
			"WHERE is_completed = 0 ".
			"GROUP BY source_extension ";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$key = !empty($row['source_extension']) ? $row['source_extension'] : 'none';
			$totals[$key] = intval($row['hits']);
		}
		
		mysql_free_result($rs);
		
		return $totals;
	}
	
	static function getAssignedSourceTotals() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$totals = array();
		
		$sql = "SELECT count(id) as hits, worker_id ".
			"FROM task ".
			"WHERE worker_id > 0 ".
			"AND is_completed = 0 ".
			"GROUP BY worker_id ";
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			$totals[$row['worker_id']] = intval($row['hits']);
		}
		
		mysql_free_result($rs);
		
		return $totals;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Task[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Task();
			$object->id = $row['id'];
			$object->title = $row['title'];
			$object->worker_id = $row['worker_id'];
			$object->updated_date = $row['updated_date'];
			$object->due_date = $row['due_date'];
			$object->is_completed = $row['is_completed'];
			$object->completed_date = $row['completed_date'];
			$object->source_extension = $row['source_extension'];
			$object->source_id = $row['source_id'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 */
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Tasks
		$db->Execute(sprintf("DELETE QUICK FROM task WHERE id IN (%s)", $ids_list));
		
		// Custom fields
		DAO_CustomFieldValue::deleteBySourceIds(ChCustomFieldSource_Task::ID, $ids);
		
		// Notes
		DAO_Note::deleteBySourceIds(ChNotesSource_Task::ID, $ids);
		
		return true;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $source
	 * @param array $ids
	 */
	static function deleteBySourceIds($source_extension, $ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		// Tasks
		$db->Execute(sprintf("DELETE QUICK FROM task WHERE source_extension = %s AND source_id IN (%s)",
			$db->qstr($source_extension), 
			$ids_list
		));
		
		return true;
	}

	static function getCountBySourceObjectId($source_extension, $source_id, $include_completed=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(id) ".
			"FROM task ".
			"WHERE source_extension = %s ".
			"AND source_id = %d ".
			(($include_completed) ? " " : "AND is_completed = 0 "),
			$db->qstr($source_extension),
			$source_id
		);
		$total = intval($db->GetOne($sql));
		
		return $total;
	}
	
    /**
     * Enter description here...
     *
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
		$fields = SearchFields_Task::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.updated_date as %s, ".
			"t.due_date as %s, ".
			"t.is_completed as %s, ".
			"t.completed_date as %s, ".
			"t.title as %s, ".
			"t.worker_id as %s, ".
			"t.source_extension as %s, ".
			"t.source_id as %s ",
//			"o.name as %s ".
			    SearchFields_Task::ID,
			    SearchFields_Task::UPDATED_DATE,
			    SearchFields_Task::DUE_DATE,
			    SearchFields_Task::IS_COMPLETED,
			    SearchFields_Task::COMPLETED_DATE,
			    SearchFields_Task::TITLE,
			    SearchFields_Task::WORKER_ID,
			    SearchFields_Task::SOURCE_EXTENSION,
			    SearchFields_Task::SOURCE_ID
			 );
		
		$join_sql = 
			"FROM task t ";
//			"LEFT JOIN contact_org o ON (o.id=a.contact_org_id) "

			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=a.contact_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			't.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql =	(!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY t.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_Task::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT t.id) " : "SELECT COUNT(t.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }	
	
};

class SearchFields_Task implements IDevblocksSearchFields {
	// Task
	const ID = 't_id';
	const UPDATED_DATE = 't_updated_date';
	const DUE_DATE = 't_due_date';
	const IS_COMPLETED = 't_is_completed';
	const COMPLETED_DATE = 't_completed_date';
	const TITLE = 't_title';
	const WORKER_ID = 't_worker_id';
	const SOURCE_EXTENSION = 't_source_extension';
	const SOURCE_ID = 't_source_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 't', 'id', $translate->_('task.id')),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 't', 'updated_date', $translate->_('task.updated_date')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 't', 'title', $translate->_('task.title')),
			self::IS_COMPLETED => new DevblocksSearchField(self::IS_COMPLETED, 't', 'is_completed', $translate->_('task.is_completed')),
			self::DUE_DATE => new DevblocksSearchField(self::DUE_DATE, 't', 'due_date', $translate->_('task.due_date')),
			self::COMPLETED_DATE => new DevblocksSearchField(self::COMPLETED_DATE, 't', 'completed_date', $translate->_('task.completed_date')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 't', 'worker_id', $translate->_('task.worker_id')),
			self::SOURCE_EXTENSION => new DevblocksSearchField(self::SOURCE_EXTENSION, 't', 'source_extension', $translate->_('task.source_extension')),
			self::SOURCE_ID => new DevblocksSearchField(self::SOURCE_ID, 't', 'source_id', $translate->_('task.source_id')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));
		
		return $columns;
	}
};

class Model_Task {
	public $id;
	public $title;
	public $worker_id;
	public $created;
	public $due_date;
	public $is_completed;
	public $completed_date;
	public $source_extension;
	public $source_id;
};

class View_Task extends C4_AbstractView {
	const DEFAULT_ID = 'tasks';
	const DEFAULT_TITLE = 'All Open Tasks';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = self::DEFAULT_TITLE;
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Task::DUE_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::UPDATED_DATE,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			);
		
		$this->params = array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
		);
	}

	function getData() {
		$objects = DAO_Task::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->assign('timestamp_now', time());

		// Pull the results so we can do some row introspection
		$results = $this->getData();
		$tpl->assign('results', $results);

//		$source_renderers = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
		
		// Make a list of unique source_extension and load their renderers
		$source_extensions = array();
		if(is_array($results) && isset($results[0]))
		foreach($results[0] as $rows) {
			$source_extension = $rows[SearchFields_Task::SOURCE_EXTENSION];
			if(!isset($source_extensions[$source_extension]) 
				&& !empty($source_extension)
				&& null != ($mft = DevblocksPlatform::getExtension($source_extension))) {
				$source_extensions[$source_extension] = $mft->createInstance();
			} 
		}
		$tpl->assign('source_renderers', $source_extensions);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/tasks/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = APP_PATH . '/features/cerberusweb.core/templates/';
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_Task::TITLE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Task::SOURCE_EXTENSION:
				$source_renderers = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
				$tpl->assign('sources', $source_renderers);
				$tpl->display('file:' . $tpl_path . 'tasks/criteria/source.tpl');
				break;
				
			case SearchFields_Task::IS_COMPLETED:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Task::UPDATED_DATE:
			case SearchFields_Task::DUE_DATE:
			case SearchFields_Task::COMPLETED_DATE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Task::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__worker.tpl');
				break;

			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$translate = DevblocksPlatform::getTranslationService();
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Task::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
						$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
						continue;
					else
						$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;
				
			case SearchFields_Task::SOURCE_EXTENSION:
				$sources = $ext = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);			
				$strings = array();
				
				foreach($values as $val) {
					if(!isset($sources[$val]))
						continue;
					else
						$strings[] = $sources[$val]->getSourceName();
				}
				echo implode(", ", $strings);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Task::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Task::ID]);
		unset($fields[SearchFields_Task::SOURCE_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Task::ID]);
		unset($fields[SearchFields_Task::SOURCE_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0)
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Task::TITLE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Task::SOURCE_EXTENSION:
				@$sources = DevblocksPlatform::importGPC($_REQUEST['sources'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$sources);
				break;
				
			case SearchFields_Task::UPDATED_DATE:
			case SearchFields_Task::COMPLETED_DATE:
			case SearchFields_Task::DUE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;

			case SearchFields_Task::IS_COMPLETED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Task::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
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
				case 'due':
					@$date = strtotime($v);
					$change_fields[DAO_Task::DUE_DATE] = intval($date);
					break;
				case 'status':
					if(1==intval($v)) { // completed
						$change_fields[DAO_Task::IS_COMPLETED] = 1;
						$change_fields[DAO_Task::COMPLETED_DATE] = time();
					} else { // active
						$change_fields[DAO_Task::IS_COMPLETED] = 0;
						$change_fields[DAO_Task::COMPLETED_DATE] = 0;
					}
					break;
				case 'worker_id':
					$change_fields[DAO_Task::WORKER_ID] = intval($v);
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
		
		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Task::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_Task::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Task::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_Task::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}	
};