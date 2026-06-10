<?php

use Cerb\Extensions\Extension_QueueConsumer;

class DAO_Queue extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const EXTENSION_ID = 'extension_id';
	const EXTENSION_PARAMS_JSON = 'extension_params_json';
	const ID = 'id';
	const NAME = 'name';
	const RETRY_MAX = 'retry_max';
	const RETRY_WINDOW_SECS = 'retry_window_secs';
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
			->addField(self::EXTENSION_ID)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			->addValidator($validation->validators()->extension('\Cerb\Extensions\Extension_QueueConsumer'))
		;
		$validation
			->addField(self::EXTENSION_PARAMS_JSON)
			->string()
			->setMaxLength(65_535)
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
			->setUnique(__CLASS__)
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
			->addField(self::RETRY_MAX)
			->number()
			->setMin(0)
			->setMax(16)
		;
		$validation
			->addField(self::RETRY_WINDOW_SECS)
			->number()
			->setMin(0)
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
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
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
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
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
		$sql = "SELECT id, name, extension_id, extension_params_json, retry_max, retry_window_secs, created_at, updated_at ".
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
			$object->id = intval($row['id']);
			$object->extension_id = $row['extension_id'];
			$object->name = $row['name'];
			$object->retry_max = intval($row['retry_max']);
			$object->retry_window_secs = intval($row['retry_window_secs']);
			$object->created_at = intval($row['created_at']);
			$object->updated_at = intval($row['updated_at']);
			
			if(false !== ($json = json_decode($row['extension_params_json'] ?? '', true)))
				$object->extension_params = $json;
			
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
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = CerberusContexts::CONTEXT_QUEUE;
		$ids_list = implode(',', self::qstrArray($ids));

		// Cascade: delete child queue jobs (which cascades their messages + fires audit)
		$job_ids = array_map('intval', array_column(
			$db->GetArrayMaster(sprintf("SELECT id FROM queue_job WHERE queue_id IN (%s)", $ids_list)),
			'id'
		));
		if($job_ids)
			DAO_QueueJob::delete($job_ids);

		// Cascade: delete any remaining messages not tied to a job
		DAO_QueueMessage::deleteByQueueIds($ids);

		parent::_deleteAbstractBefore($context, $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM queue WHERE id IN (%s)", $ids_list));

		parent::_deleteAbstractAfter($context, $ids);

		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Queue::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Queue', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"queue.id as %s, ".
			"queue.name as %s, ".
			"queue.extension_id as %s, ".
			"queue.retry_max as %s, ".
			"queue.retry_window_secs as %s, ".
			"queue.created_at as %s, ".
			"queue.updated_at as %s ",
			SearchFields_Queue::ID,
			SearchFields_Queue::NAME,
			SearchFields_Queue::EXTENSION_ID,
			SearchFields_Queue::RETRY_MAX,
			SearchFields_Queue::RETRY_WINDOW_SECS,
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

class SearchFields_Queue extends DevblocksSearchFields {
	const CREATED_AT = 'q_created_at';
	const EXTENSION_ID = 'q_extension_id';
	const ID = 'q_id';
	const NAME = 'q_name';
	const RETRY_MAX = 'q_retry_max';
	const RETRY_WINDOW_SECS = 'q_retry_window_secs';
	const UPDATED_AT = 'q_updated_at';
	
	static private $_fields = null;
	
	static function getTableName() : string {
		return 'queue';
	}
	
	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_Queue::ID);
	}
	
	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_Queue::UPDATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_QUEUE => new DevblocksSearchFieldContextKeys('queue.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, CerberusContexts::CONTEXT_QUEUE, self::getPrimaryKey())))
						return $virtual_where_sql;

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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'queue', 'created_at', $translate->_('common.created'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'queue', 'extension_id', $translate->_('common.extension'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'queue', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'queue', 'name', $translate->_('common.name'), null, true),
			self::RETRY_MAX => new DevblocksSearchField(self::RETRY_MAX, 'queue', 'retry_max', 'Retry max', null, true),
			self::RETRY_WINDOW_SECS => new DevblocksSearchField(self::RETRY_WINDOW_SECS, 'queue', 'retry_window_secs', 'Retry window', null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'queue', 'updated_at', $translate->_('common.updated'), null, true),
		];
		
		// Virtual fields
		if(($virtual_columns = DevblocksSearchField::getVirtualFields()))
			$columns = array_merge($columns, $virtual_columns);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class Model_Queue extends DevblocksRecordModel {
	public $created_at = 0;
	public $extension_id = '';
	public $extension_params = [];
	public $id = 0;
	public $name = '';
	public int $retry_max = 0;
	public int $retry_window_secs = 86400;
	public $updated_at = 0;
	
	public function getExtension() : Extension_QueueConsumer {
		return Extension_QueueConsumer::get($this->extension_id);
	}
};

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
			SearchFields_Queue::EXTENSION_ID,
			SearchFields_Queue::UPDATED_AT,
			SearchFields_Queue::RETRY_MAX,
			SearchFields_Queue::RETRY_WINDOW_SECS,
		];
		
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
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_Queue', $ids, $total);
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
					case SearchFields_Queue::EXTENSION_ID:
						$pass = true;
						break;
						
					// Valid custom fields
					default:
						if(DevblocksPlatform::strStartsWith($field_key, 'cf_')) {
							$pass = $this->_canSubtotalCustomField($field_key);
						} else if (str_starts_with($field_key, '*_')) {
							$pass = $this->_canSubtotalVirtualField($field_key);
						}
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
		
		if(!array_key_exists($column, $fields))
			return [];
		
		switch($column) {
			case SearchFields_Queue::EXTENSION_ID:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				} else if(DevblocksPlatform::strStartsWith($column, '*_')) {
					$counts = $this->_getSubtotalCountForVirtualField($context, $column);
				}
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchDefaultFilter(?DevblocksSearchCriteria $criteria=null) : string {
		return 'name';
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Queue::getFields();
		
		$fields = array(
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Queue::CREATED_AT),
				),
			'extension' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Queue::EXTENSION_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
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
			'retry.max' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Queue::RETRY_MAX),
					'examples' => ['0', '>0', '8'],
				),
			'retry.window' => // human time, e.g. retry.window:>1h or retry.window:<1d
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER_SECONDS,
					'options' => array('param_key' => SearchFields_Queue::RETRY_WINDOW_SECS),
					'examples' => ['>1h', '<1d', '>30m', '<1mo'],
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Queue::UPDATED_AT),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => DevblocksSearchField::VIRTUAL_WATCHERS],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		
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
				return DevblocksSearchCriteria::getWatcherParamFromTokens(DevblocksSearchField::VIRTUAL_WATCHERS, $tokens);
			
			default:
				if($field == 'links' || str_starts_with($field, 'links.'))
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

		// Queue Consumer extensions
		$queue_extensions = Extension_QueueConsumer::getAll(false);
		$tpl->assign('queue_extensions', $queue_extensions);

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
	
	function renderVirtualCriteria($param) : void {
		switch($param->field) {
			default:
				$this->_renderVirtualCriteria($param);
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
			case SearchFields_Queue::EXTENSION_ID:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_Queue::ID:
			case SearchFields_Queue::RETRY_MAX:
			case SearchFields_Queue::RETRY_WINDOW_SECS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Queue::CREATED_AT:
			case SearchFields_Queue::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			default:
				// Custom Fields
				if(str_starts_with($field, 'cf_')) {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				} else if (str_starts_with($field, '*_')) {
					if(($virtual_criteria = $this->_doSetCriteriaVirtual($field, $_POST, $oper)))
						$criteria = $virtual_criteria;
				}
				break;
		}
		
		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_Queue extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete, IDevblocksContextWorkflow {
	const ID = CerberusContexts::CONTEXT_QUEUE;
	const URI = 'queue';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return self::_isWriteableOnlyByAdmin($models, $actor);
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
		$properties['extension_id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.extension'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->extension_id,
		);
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);

		$properties['retry_max'] = array(
			'label' => 'Retry max',
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->retry_max,
		);

		$properties['retry_window_secs'] = array(
			'label' => 'Retry window (secs)',
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->retry_window_secs,
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
	
	function getDefaultProperties() : array {
		return [
			'extension_id',
			'updated_at',
		];
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(($model = DAO_Queue::getByName($alias)))
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
			DevblocksPlatform::noop();
		} elseif(is_array($queue)) {
			$queue = Cerb_ORMHelper::recastArrayToModel($queue, 'Model_Queue');
		} else {
			$queue = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'created_at' => $prefix.$translate->_('common.created'),
			'extension_id' => $prefix.$translate->_('common.extension'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'retry_max' => $prefix.'Retry max',
			'retry_window_secs' => $prefix.'Retry window (secs)',
			'updated_at' => $prefix.$translate->_('common.updated'),
		);

		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
			'retry_max' => Model_CustomField::TYPE_NUMBER,
			'retry_window_secs' => Model_CustomField::TYPE_NUMBER,
			'updated_at' => Model_CustomField::TYPE_DATE,
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
			$token_values['extension_id'] = $queue->extension_id;
			$token_values['id'] = $queue->id;
			$token_values['name'] = $queue->name;
			$token_values['retry_max'] = $queue->retry_max;
			$token_values['retry_window_secs'] = $queue->retry_window_secs;
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
			'extension_id' => DAO_Queue::EXTENSION_ID,
			'id' => DAO_Queue::ID,
			'links' => '_links',
			'name' => DAO_Queue::NAME,
			'retry_max' => DAO_Queue::RETRY_MAX,
			'retry_window_secs' => DAO_Queue::RETRY_WINDOW_SECS,
			'updated_at' => DAO_Queue::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
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
			$params_req = [
				new DevblocksSearchCriteria(DevblocksSearchField::VIRTUAL_CONTEXT_LINK, 'in', [$context.':'.$context_id]),
			];
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
			if(!($model = DAO_Queue::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(empty($context_id) || $edit) {
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$queue_extension = $model->getExtension();
				$tpl->assign('queue_extension', $queue_extension);
				
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
			
			// Extensions
			
			$queue_extensions = Extension_QueueConsumer::getAll(false);
			$tpl->assign('queue_extensions', $queue_extensions);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/queue/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}

	function workflowExport(array $ids, DevblocksWorkflowExportModel $export_model, bool $include_children = false): array {
		$workflow_kata = [
			'records' => [],
		];
		
		$record_uri = CerberusContexts::getContextName($this->id, 'uri');
		
		$models = DAO_Queue::getIds($ids);
		
		foreach($models as $model) {
			$model_key = $export_model->getLabelMapFor(sprintf('%s_%d', $record_uri, $model->id));
			$record_key = sprintf('%s/%s', $record_uri, $model_key);
			
			$workflow_kata['records'][$record_key] = [
				'fields' => [
					'name' => $model->name,
					'extension_id' => $model->extension_id,
				],
			];
		}
		
		return $workflow_kata;
	}
};