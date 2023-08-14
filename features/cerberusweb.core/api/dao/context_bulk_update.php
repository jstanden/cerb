<?php
class DAO_ContextBulkUpdate extends Cerb_ORMHelper {
	const ACTIONS_JSON = 'actions_json';
	const BATCH_KEY = 'batch_key';
	const CONTEXT = 'context';
	const CONTEXT_IDS = 'context_ids';
	const CREATED_AT = 'created_at';
	const ID = 'id';
	const NUM_RECORDS = 'num_records';
	const STATUS_ID = 'status_id';
	const VIEW_ID = 'view_id';
	const WORKER_ID = 'worker_id';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::ACTIONS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::BATCH_KEY)
			->string()
			->setMaxLength(40)
			->setRequired(true)
			;
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::CONTEXT_IDS)
			->string()
			->setMaxLength(16777215)
			->setRequired(true)
			;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::NUM_RECORDS)
			->uint()
			;
		$validation
			->addField(self::STATUS_ID)
			->uint(1)
			;
		$validation
			->addField(self::VIEW_ID)
			->string()
			->setMaxLength(128)
			;
		$validation
			->addField(self::WORKER_ID)
			->id()
			;
			
		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO context_bulk_update () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function createFromView(C4_AbstractView $view, array $do, $batch_size=null, $dao_class=null, $search_class=null) {
		$context = null;
		
		if(empty($do))
			return false;
		
		if(empty($batch_size) || !is_numeric($batch_size))
			$batch_size = 100;
		
		// Generate batch jobs
		$view_class = get_class($view);
		
		// Autoload classes
		if(false != ($context_ext = Extension_DevblocksContext::getByViewClass($view_class, true))) { /* @var $context_ext Extension_DevblocksContext */
			$context = $context_ext->id;
		
			if(is_null($dao_class))
				$dao_class = $context_ext->getDaoClass();
			
			if(is_null($search_class))
				$search_class = $context_ext->getSearchClass();
		}
		
		if(!$dao_class || !class_exists($dao_class))
			return false;
		
		if(!$search_class || !class_exists($search_class) || !class_implements('DevblocksSearchFields'))
			return false;
		
		if(false == ($pkey = $search_class::getPrimaryKey()) || empty($pkey))
			return false;
		
		$actions_json = json_encode($do);
		$batch_key = uniqid();
		$current_worker = CerberusApplication::getActiveWorker();
		
		$db = DevblocksPlatform::services()->database();
		
		$params = $view->getParams();
		
		if(false == ($query_parts = $dao_class::getSearchQueryComponents(array(), $params)))
			return false;
		
		$db->ExecuteMaster('set @pos=0');
		$db->ExecuteMaster('set group_concat_max_len = 1024000');
		
		$sql = sprintf('CREATE TEMPORARY TABLE _bulk SELECT %s AS id, @pos:=@pos+1 AS pos ', $pkey).
			$query_parts['join'].
			$query_parts['where']
			;
		$db->ExecuteMaster($sql);
		
		$sql = sprintf('INSERT INTO context_bulk_update (batch_key, context, context_ids, num_records, worker_id, view_id, created_at, status_id, actions_json) '.
			'SELECT %s as batch_key, %s as context, GROUP_CONCAT(id) AS context_ids, COUNT(id) as num_records, %d as worker_id, %s as view_id, %d as created_at, 0 as status_id, %s as actions_json '.
			'FROM _bulk '.
			'GROUP BY FLOOR(pos/%d)',
			$db->qstr($batch_key),
			$db->qstr($context),
			($current_worker ? $current_worker->id : 0),
			$db->qstr($view->id),
			time(),
			$db->qstr($actions_json),
			$batch_size
		);
		$db->ExecuteMaster($sql);
		
		$db->ExecuteMaster('DROP TABLE _bulk');
		
		return $batch_key;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				//CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'context_bulk_update', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.context_bulk_update.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				//DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('context_bulk_update', $fields, $where);
	}
	
	/**
	 * 
	 * @param string $cursor
	 * @return Model_ContextBulkUpdate|boolean
	 */
	static function getNextByCursor($cursor) {
		$where = sprintf("status_id = 0 AND batch_key = %s", Cerb_ORMHelper::qstr($cursor));
		
		$results = self::getWhere(
			$where,
			DAO_ContextBulkUpdate::ID,
			true,
			1
		);
		
		if(is_array($results) && !empty($results))
			return array_shift($results);
		
		return false;
	}
	
	/**
	 * 
	 * @param string $cursor
	 * @return integer
	 */
	static function getTotalByCursor($cursor) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT SUM(num_records) FROM context_bulk_update WHERE batch_key = %s", $db->qstr($cursor)));
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContextBulkUpdate[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, batch_key, context, context_ids, num_records, worker_id, view_id, created_at, status_id, actions_json ".
			"FROM context_bulk_update ".
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
	 *
	 * @param bool $nocache
	 * @return Model_ContextBulkUpdate[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ContextBulkUpdate::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ContextBulkUpdate
	 */
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
	 * 
	 * @param array $ids
	 * @return Model_ContextBulkUpdate[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_ContextBulkUpdate[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ContextBulkUpdate();
			$object->id = intval($row['id']);
			$object->batch_key = $row['batch_key'];
			$object->context = $row['context'];
			$object->context_ids = DevblocksPlatform::parseCsvString($row['context_ids']);
			$object->num_records = intval($row['num_records']);
			$object->worker_id = intval($row['worker_id']);
			$object->view_id = $row['view_id'];
			$object->created_at = intval($row['created_at']);
			$object->status_id = intval($row['status_id']);
			
			if(!empty($row['actions_json']) && false != ($actions = json_decode($row['actions_json'], true)))
				$object->actions = $actions;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('context_bulk_update');
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		// Keep rows for 1 week
		$sql = "DELETE FROM context_bulk_update WHERE status_id = 2 AND created_at <= unix_timestamp() - 604800";
		$db->ExecuteMaster($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' completed context_bulk_update records.');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = 'cerberusweb.contexts.context.bulk.update';
		$ids_list = implode(',', self::qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM context_bulk_update WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContextBulkUpdate::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ContextBulkUpdate', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"context_bulk_update.id as %s, ".
			"context_bulk_update.batch_key as %s, ".
			"context_bulk_update.context as %s, ".
			"context_bulk_update.context_ids as %s, ".
			"context_bulk_update.num_records as %s, ".
			"context_bulk_update.worker_id as %s, ".
			"context_bulk_update.view_id as %s, ".
			"context_bulk_update.created_at as %s, ".
			"context_bulk_update.status_id as %s, ".
			"context_bulk_update.actions_json as %s ",
				SearchFields_ContextBulkUpdate::ID,
				SearchFields_ContextBulkUpdate::BATCH_KEY,
				SearchFields_ContextBulkUpdate::CONTEXT,
				SearchFields_ContextBulkUpdate::CONTEXT_IDS,
				SearchFields_ContextBulkUpdate::NUM_RECORDS,
				SearchFields_ContextBulkUpdate::WORKER_ID,
				SearchFields_ContextBulkUpdate::VIEW_ID,
				SearchFields_ContextBulkUpdate::CREATED_AT,
				SearchFields_ContextBulkUpdate::STATUS_ID,
				SearchFields_ContextBulkUpdate::ACTIONS_JSON
			);
			
		$join_sql = "FROM context_bulk_update ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ContextBulkUpdate');
	
		return array(
			'primary_table' => 'context_bulk_update',
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
			SearchFields_ContextBulkUpdate::ID,
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

class SearchFields_ContextBulkUpdate extends DevblocksSearchFields {
	const ID = 'c_id';
	const BATCH_KEY = 'c_batch_key';
	const CONTEXT = 'c_context';
	const CONTEXT_IDS = 'c_context_ids';
	const NUM_RECORDS = 'c_num_records';
	const WORKER_ID = 'c_worker_id';
	const VIEW_ID = 'c_view_id';
	const CREATED_AT = 'c_created_at';
	const STATUS_ID = 'c_status_id';
	const ACTIONS_JSON = 'c_actions_json';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'context_bulk_update.id';
	}
	
	static function getCustomFieldContextKeys() {
		// [TODO] Context
		return array(
			'' => new DevblocksSearchFieldContextKeys('context_bulk_update.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, 'cerberusweb.contexts.context.bulk.update', self::getPrimaryKey());
				break;
			
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ContextBulkUpdate::ID:
				$models = DAO_ContextBulkUpdate::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'context_bulk_update', 'id', $translate->_('common.id'), null, true),
			self::BATCH_KEY => new DevblocksSearchField(self::BATCH_KEY, 'context_bulk_update', 'batch_key', $translate->_('dao.context_bulk_update.batch_key'), null, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'context_bulk_update', 'context', $translate->_('common.context'), null, true),
			self::CONTEXT_IDS => new DevblocksSearchField(self::CONTEXT_IDS, 'context_bulk_update', 'context_ids', $translate->_('dao.context_bulk_update.context_ids'), null, true),
			self::NUM_RECORDS => new DevblocksSearchField(self::NUM_RECORDS, 'context_bulk_update', 'num_records', $translate->_('dao.context_bulk_update.num_records'), null, true),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'context_bulk_update', 'worker_id', $translate->_('common.worker'), null, true),
			self::VIEW_ID => new DevblocksSearchField(self::VIEW_ID, 'context_bulk_update', 'view_id', $translate->_('common.view'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'context_bulk_update', 'created_at', $translate->_('common.created'), null, true),
			self::STATUS_ID => new DevblocksSearchField(self::STATUS_ID, 'context_bulk_update', 'status_id', $translate->_('common.status'), null, true),
			self::ACTIONS_JSON => new DevblocksSearchField(self::ACTIONS_JSON, 'context_bulk_update', 'actions_json', $translate->_('common.actions'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_ContextBulkUpdate {
	public $id = 0;
	public $batch_key = null;
	public $context = null;
	public $context_ids = array();
	public $num_records = 0;
	public $worker_id = 0;
	public $view_id = null;
	public $created_at = 0;
	public $status_id = 0;
	public $actions = array();
	
	function markInProgress() {
		DAO_ContextBulkUpdate::update($this->id, array(
			DAO_ContextBulkUpdate::STATUS_ID => 1,
		));
	}
	
	function markCompleted() {
		DAO_ContextBulkUpdate::update($this->id, array(
			DAO_ContextBulkUpdate::STATUS_ID => 2,
		));
	}
};

