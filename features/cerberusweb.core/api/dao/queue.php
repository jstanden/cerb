<?php
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Uuid;

class DAO_Queue extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	
	const _CACHE_ALL = 'queues_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
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
			->addField(self::NAME)
			->string()
			->setRequired(true)
			->setUnique(get_class())
			->addValidator(function($string, &$error=null) {
				if(0 != strcmp($string, DevblocksPlatform::strAlphaNum($string, '._'))) {
					$error = "may only contain letters, numbers, underscores, and dots";
					return false;
				}
				
				if(strlen($string) > 255) {
					$error = "must be shorter than 255 characters.";
					return false;
				}
				
				return true;
			})
		;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
		;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
		;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
		;
		
		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!array_key_exists(DAO_Queue::CREATED_AT, $fields))
			$fields[DAO_Queue::CREATED_AT] = time();
		
		$sql = "INSERT INTO queue () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_Queue::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_QUEUE;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'queue', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.queue.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('queue', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_QUEUE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Queue[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, created_at, updated_at ".
			"FROM queue ".
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
	 * @return Model_Queue[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_Queue::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		
			if(!is_array($objects))
				return [];
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @return Model_Queue
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$queues = self::getAll();
		
		if(array_key_exists($id, $queues))
			return $queues[$id];
		
		return null;
	}
	
	/**
	 *
	 * @param array $ids
	 * @return Model_Queue[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	static function getByName(string $name) : ?Model_Queue {
		$queues = self::getByNames([$name]);
		
		if(!$queues)
			return null;
		
		return current($queues);
	}

	static function getByNames(array $names) : array {
		if(empty($names))
			return [];
		
		$queues = self::getAll();
		$queue_names_to_ids = array_change_key_case(array_column($queues, 'id', 'name'), CASE_LOWER);
		$queues_names_to_find = array_map(fn($name) => DevblocksPlatform::strLower($name), $names);
		$results = [];
		
		foreach($queues_names_to_find as $name) {
			if(array_key_exists($name, $queue_names_to_ids)) {
				$queue_id = $queue_names_to_ids[$name];
				$results[$queue_id] = $queues[$queue_id];
			}
		}
		
		return $results;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Queue[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Queue();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->created_at = $row['created_at'];
			$object->updated_at = $row['updated_at'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('queue');
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return false;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM queue WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_QUEUE,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Queue::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Queue', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"queue.id as %s, ".
			"queue.name as %s, ".
			"queue.created_at as %s, ".
			"queue.updated_at as %s ",
			SearchFields_Queue::ID,
			SearchFields_Queue::NAME,
			SearchFields_Queue::CREATED_AT,
			SearchFields_Queue::UPDATED_AT
		);
		
		$join_sql = "FROM queue ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Queue');
		
		return array(
			'primary_table' => 'queue',
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
	 * @return array|false
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
			SearchFields_Queue::ID,
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

class DAO_QueueMessage {
	const STATUS_AVAILABLE = 0;
	const STATUS_IN_FLIGHT = 1;
	const STATUS_FAILED = 2;
	const STATUS_COMPLETE = 3;
	
	/**
	 * @param Model_Queue $queue
	 * @param array $messages
	 * @return array|false
	 */
	static function enqueue(Model_Queue $queue, array $messages) {
		$db = DevblocksPlatform::services()->database();
		$nodeProvider = new RandomNodeProvider();
		
		if(!is_array($messages) || empty($messages))
			return false;
		
		$results = [];
		$insert_values = [];
		
		foreach($messages as $message) {
			$uuid = Uuid::uuid6($nodeProvider->getNode());
			$message_uuid = $uuid->getHex();
			
			$insert_values[] = sprintf("(%s, %d, %d, %d, %s, %s)",
				'0x' . $db->escape($message_uuid),
				$queue->id,
				self::STATUS_AVAILABLE,
				time(),
				$db->escape('NULL'),
				$db->qstr(json_encode($message))
			);
			
			$results[] = $message_uuid->toString();
		}
		
		$db->ExecuteWriter(
			sprintf("INSERT INTO queue_message (uuid, queue_id, status_id, status_at, consumer_id, message) VALUES %s",
			implode(',', $insert_values)
		));
		
		return $results;
	}
	
	static function dequeue(Model_Queue $queue, ?int $limit=1, &$consumer_id=null) : array {
		$db = DevblocksPlatform::services()->database();
		$nodeProvider = new RandomNodeProvider();
		
		if(!is_numeric($limit) || !$limit)
			$limit = 1;
		
		$uuid = Uuid::uuid6($nodeProvider->getNode());
		$consumer_id = '0x' . $uuid->getHex();
		
		$db->ExecuteWriter(
			sprintf("UPDATE queue_message SET status_id=%d, status_at=%d, consumer_id=%s WHERE queue_id=%d AND status_id=%d LIMIT %d",
				self::STATUS_IN_FLIGHT,
				time(),
				$db->escape($consumer_id),
				$queue->id,
				self::STATUS_AVAILABLE,
				$limit
			)
		);
		
		$results = $db->GetArrayMaster(sprintf("SELECT uuid, message FROM queue_message WHERE status_id=%d AND consumer_id=%s",
			self::STATUS_IN_FLIGHT,
			$db->escape($consumer_id)
		));
		
		$messages = [];
		
		if(!$results)
			return $messages;
		
		foreach($results as $result) {
			$message = new Model_QueueMessage();
			$message->uuid = Uuid::fromBytes($result['uuid'])->getHex()->toString();
			$message->queue_id = intval($queue->id);
			$message->message = json_decode($result['message'], true);
			$messages[] = $message;
		}
		
		unset($results);
		
		return $messages;
	}
	
	static function reportSuccess(array $message_uuids) {
		self::_reportStatus(DAO_QueueMessage::STATUS_COMPLETE, $message_uuids);
	}
	
	static function reportFailure(array $message_uuids) {
		self::_reportStatus(DAO_QueueMessage::STATUS_FAILED, $message_uuids);
	}
	
	static private function _reportStatus($status_id, $message_uuids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$message_uuids)
			return;
		
		$insert_values = array_map(
			fn($uuid) => '0x' . $db->escape($uuid),
			$message_uuids
		);
		
		$db->ExecuteWriter(sprintf("UPDATE queue_message SET status_id=%d WHERE uuid IN (%s)",
			$status_id,
			implode(',', $insert_values)
		));
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		$before = time()-86400;
		
		$db->ExecuteWriter(sprintf("DELETE FROM queue_message WHERE status_id = 3 AND status_at < %d",
			$before
		));
	}
}

class SearchFields_Queue extends DevblocksSearchFields {
	const ID = 'q_id';
	const NAME = 'q_name';
	const CREATED_AT = 'q_created_at';
	const UPDATED_AT = 'q_updated_at';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'queue.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_QUEUE => new DevblocksSearchFieldContextKeys('queue.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_QUEUE, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_QUEUE), '%s'), self::getPrimaryKey());
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_QUEUE, self::getPrimaryKey());
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Queue::ID:
				$models = DAO_Queue::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
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
		
		$columns = [
			self::ID => new DevblocksSearchField(self::ID, 'queue', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'queue', 'name', $translate->_('common.name'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'queue', 'created_at', $translate->_('common.created'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'queue', 'updated_at', $translate->_('common.updated'), null, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		];
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class Model_Queue {
	public $id;
	public $name;
	public $created_at;
	public $updated_at;
};

class Model_QueueMessage {
	public string $uuid = '';
	public int $queue_id = 0;
	public $message = null;
}

class View_Queue extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'queues';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.queues');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Queue::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_Queue::NAME,
			SearchFields_Queue::CREATED_AT,
			SearchFields_Queue::UPDATED_AT,
		];
		
		$this->addColumnsHidden([
			SearchFields_Queue::VIRTUAL_CONTEXT_LINK,
			SearchFields_Queue::VIRTUAL_HAS_FIELDSET,
			SearchFields_Queue::VIRTUAL_WATCHERS,
		]);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Queue::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Queue');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Queue', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Queue', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Virtuals
					case SearchFields_Queue::VIRTUAL_CONTEXT_LINK:
					case SearchFields_Queue::VIRTUAL_HAS_FIELDSET:
					case SearchFields_Queue::VIRTUAL_WATCHERS:
						$pass = true;
						break;
					
					// Valid custom fields
					default:
						if(DevblocksPlatform::strStartsWith($field_key, 'cf_'))
							$pass = $this->_canSubtotalCustomField($field_key);
						break;
				}
				
				if($pass)
					$fields[$field_key] = $field_model;
			}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_QUEUE;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Queue::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_Queue::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
			
			case SearchFields_Queue::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
			
			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Queue::getFields();
		
		$fields = array(
			'text' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Queue::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Queue::CREATED_AT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Queue::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_QUEUE],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Queue::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_QUEUE, 'q' => ''],
					]
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Queue::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Queue::UPDATED_AT),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Queue::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Queue::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_QUEUE, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Queue::VIRTUAL_WATCHERS, $tokens);
			
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_QUEUE);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/queue/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		
		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Queue::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_Queue::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_Queue::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_Queue::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_Queue::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_Queue::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_Queue::CREATED_AT:
			case SearchFields_Queue::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case 'placeholder_bool':
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_Queue::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_Queue::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
			
			case SearchFields_Queue::VIRTUAL_WATCHERS:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
			
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}
		
		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_Queue extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = CerberusContexts::CONTEXT_QUEUE;
	const URI = 'queue';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admin workers can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Queue::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=queue&id='.$context_id, true);
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Queue();
		
		$properties['created'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($queue = DAO_Queue::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($queue->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $queue->id,
			'name' => $queue->name,
			'permalink' => $url,
			'updated' => $queue->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_Queue::getByName($alias)))
			return $model->id;
		
		return null;
	}
	
	public function autocomplete($term, $query = null) {
		$list = [];
		
		list($results,) = DAO_Queue::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Queue::NAME,DevblocksSearchCriteria::OPER_LIKE,'%'.$term.'%'),
			),
			25,
			0,
			SearchFields_Queue::NAME,
			true,
			false
		);
		
		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_Queue::NAME];
			$entry->value = $row[SearchFields_Queue::ID];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($queue, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Queue:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_QUEUE);
		
		// Polymorph
		if(is_numeric($queue)) {
			$queue = DAO_Queue::get($queue);
		} elseif($queue instanceof Model_Queue) {
			// It's what we want already.
			true;
		} elseif(is_array($queue)) {
			$queue = Cerb_ORMHelper::recastArrayToModel($queue, 'Model_Queue');
		} else {
			$queue = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'created_at' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_QUEUE;
		$token_values['_type'] = 'queue';
		$token_values['_types'] = $token_types;
		
		if($queue) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $queue->name;
			$token_values['created_at'] = $queue->created_at;
			$token_values['id'] = $queue->id;
			$token_values['name'] = $queue->name;
			$token_values['updated_at'] = $queue->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($queue, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=queue&id=%d-%s",$queue->id, DevblocksPlatform::strToPermalink($queue->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_Queue::CREATED_AT,
			'id' => DAO_Queue::ID,
			'links' => '_links',
			'name' => DAO_Queue::NAME,
			'updated_at' => DAO_Queue::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_QUEUE;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $dictionary);
				$values = array_merge($values, $defaults);
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Queues';
		$view->renderSortBy = SearchFields_Queue::NAME;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Queue';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Queue::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_QUEUE;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_Queue::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/queue/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};