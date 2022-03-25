<?php
class DAO_Metric extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const TYPE = 'type';
	const DESCRIPTION = 'description';
	const DIMENSIONS_KATA = 'dimensions_kata';
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	
	const _CACHE_ALL = 'metrics_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
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
			->addField(self::CREATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::DESCRIPTION)
			->string()
		;
		$validation
			->addField(self::DIMENSIONS_KATA)
			->string()
			->setMaxLength(65_536)
		;
		$validation
			->addField(self::TYPE)
			->string()
			->setPossibleValues([
				'counter',
				'gauge'
			])
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
		
		if(!array_key_exists(DAO_Metric::CREATED_AT, $fields))
			$fields[DAO_Metric::CREATED_AT] = time();
		
		$sql = "INSERT INTO metric () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_Metric::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_METRIC;
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
			parent::_update($batch_ids, 'metric', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.metric.update',
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
		parent::_updateWhere('metric', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_METRIC;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		if(array_key_exists(DAO_Metric::DIMENSIONS_KATA, $fields)) {
			$kata = DevblocksPlatform::services()->kata();
			if(false === $kata->validate($fields[DAO_Metric::DIMENSIONS_KATA], CerberusApplication::kataSchemas()->metricDimensions(), $error)) {
				$error = 'Metric dimension: ' . $error;
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Metric[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, description, type, dimensions_kata, created_at, updated_at ".
			"FROM metric ".
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
	 * @return Model_Metric[]
	 */
	static function getAll(bool $nocache=false) : array {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_Metric::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return [];
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @return Model_Metric 
	 */
	static function get(int $id) : ?object {
		if(empty($id))
			return null;
		
		$metrics = self::getAll();
		
		if(array_key_exists($id, $metrics))
			return $metrics[$id];
		
		return null;
	}
	
	/**
	 *
	 * @param array $ids
	 * @return Model_Metric[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	public static function getByName($name) : ?Model_Metric {
		$results = self::getByNames([$name]);
		
		if($results)
			return current($results);
		
		return null;
	}
	
	public static function getByNames(array $names) : array {
		if(!$names)
			return [];
		
		$metrics = self::getAll();
		
		$names_to_ids = array_change_key_case(array_column($metrics, 'id', 'name'), CASE_LOWER);
		$names_to_find = array_map(fn($name) => DevblocksPlatform::strLower($name), $names);
		$results = [];
		
		foreach($names_to_find as $name) {
			if(array_key_exists($name, $names_to_ids)) {
				$metric_id = $names_to_ids[$name];
				$results[$metric_id] = $metrics[$metric_id];
			}
		}
		
		return $results;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Metric[]|false
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Metric();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->description = $row['description'];
			$object->dimensions_kata = $row['dimensions_kata'];
			$object->type = $row['type'];
			$object->created_at = intval($row['created_at']);
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('metric');
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = [$ids];
		
		if(empty($ids))
			return false;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM metric WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_METRIC,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Metric::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Metric', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"metric.id as %s, ".
			"metric.name as %s, ".
			"metric.description as %s, ".
			"metric.type as %s, ".
			"metric.created_at as %s, ".
			"metric.updated_at as %s ",
			SearchFields_Metric::ID,
			SearchFields_Metric::NAME,
			SearchFields_Metric::DESCRIPTION,
			SearchFields_Metric::TYPE,
			SearchFields_Metric::CREATED_AT,
			SearchFields_Metric::UPDATED_AT
		);
		
		$join_sql = "FROM metric ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Metric');
		
		return array(
			'primary_table' => 'metric',
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
			SearchFields_Metric::ID,
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

class SearchFields_Metric extends DevblocksSearchFields {
	const ID = 'm_id';
	const NAME = 'm_name';
	const DESCRIPTION = 'm_description';
	const DIMENSIONS_KATA = 'm_dimensions_kata';
	const TYPE = 'm_type';
	const CREATED_AT = 'm_created_at';
	const UPDATED_AT = 'm_updated_at';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'metric.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_METRIC => new DevblocksSearchFieldContextKeys('metric.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_METRIC, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_METRIC), '%s'), self::getPrimaryKey());
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_METRIC, self::getPrimaryKey());
			
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
			case SearchFields_Metric::ID:
				$models = DAO_Metric::getIds($values);
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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'metric', 'created_at', $translate->_('common.created'), null, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'metric', 'description', $translate->_('common.description'), null, true),
			self::DIMENSIONS_KATA => new DevblocksSearchField(self::DIMENSIONS_KATA, 'metric', 'dimensions_kata', $translate->_('dao.metric.dimensions'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'metric', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'metric', 'name', $translate->_('common.name'), null, true),
			self::TYPE => new DevblocksSearchField(self::TYPE, 'metric', 'type', $translate->_('common.type'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'metric', 'updated_at', $translate->_('common.updated'), null, true),
			
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

class DAO_MetricDimension {
	public static function getIds(array $ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$ids)
			return [];
		
		$results = $db->GetArrayMaster(sprintf("SELECT id, name FROM metric_dimension WHERE id IN (%s)",
			implode(',', DevblocksPlatform::sanitizeArray($ids, 'int'))
		));
		
		return array_combine(
			DevblocksPlatform::sanitizeArray(array_column($results, 'id'), 'int'),
			$results
		);
	}
	
	public static function getByName($name, $options=0) {
		$results = self::getByNames([$name], $options);
		
		if($results)
			return current($results);
		
		return null;
	}
	
	public static function getByNames(array $names, $options=0) : array {
		$db = DevblocksPlatform::services()->database();
		
		if(!$names)
			return [];
		
		if($options & DevblocksORMHelper::OPT_GET_MASTER_ONLY) {
			$results = $db->GetArrayMaster(sprintf("SELECT id, name FROM metric_dimension WHERE name IN (%s)",
				implode(',', $db->qstrArray($names))
			));
			
		} else {
			$results = $db->GetArrayReader(sprintf("SELECT id, name FROM metric_dimension WHERE name IN (%s)",
				implode(',', $db->qstrArray($names))
			));
		}
		
		if(!is_array($results))
			return [];
		
		return array_combine(
			DevblocksPlatform::sanitizeArray(array_column($results, 'id'), 'int'),
			$results
		);
	}
};

class Model_Metric {
	public int $created_at = 0;
	public string $description = '';
	public string $dimensions_kata = '';
	public int $id = 0;
	public string $name = '';
	public string $type = '';
	public int $updated_at = 0;
	
	private ?array $_dimensions = null;
	
	function getDimensions() : array {
		if(is_null($this->_dimensions)) {
			$kata = DevblocksPlatform::services()->kata();
			$error = null;
			
			if(false == ($results = $kata->parse($this->dimensions_kata, $error)))
				return [];
			
			if(false == ($results = $kata->formatTree($results, null, $error)))
				return [];
			
			$dimensions = [];
			
			foreach($results as $key => $data) {
				$type = DevblocksPlatform::services()->string()->strBefore($key, '/');
				$name = DevblocksPlatform::services()->string()->strAfter($key, '/');
				
				$dimensions[$name] = [
					'type' => $type,
					'params' => $data ?? []
				];
			}
			
			$this->_dimensions = $dimensions;
		}
		
		return $this->_dimensions;
	}
};

class Model_MetricValueSampleSet {
	public int $samples = 0;
	public float $sum = 0.0;
	public float $min = 0.0;
	public float $max = 0.0;
	
	function __construct(int $samples=0, float $sum=0, float $min=0, float $max=0) {
		$this->samples = $samples;
		$this->sum = $sum;
		$this->min = $min;
		$this->max = $max;
	}
}

class View_Metric extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'metrics';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.metrics');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Metric::NAME;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_Metric::NAME,
			SearchFields_Metric::DESCRIPTION,
			SearchFields_Metric::TYPE,
			SearchFields_Metric::UPDATED_AT,
		];
		
		$this->addColumnsHidden([
			SearchFields_Metric::DIMENSIONS_KATA,
			SearchFields_Metric::VIRTUAL_CONTEXT_LINK,
			SearchFields_Metric::VIRTUAL_HAS_FIELDSET,
			SearchFields_Metric::VIRTUAL_WATCHERS,
		]);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Metric::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Metric');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Metric', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Metric', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Virtuals
					case SearchFields_Metric::VIRTUAL_CONTEXT_LINK:
					case SearchFields_Metric::VIRTUAL_HAS_FIELDSET:
					case SearchFields_Metric::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_METRIC;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Metric::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_Metric::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
			
			case SearchFields_Metric::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_Metric::getFields();
		
		$fields = array(
			'text' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Metric::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Metric::CREATED_AT),
				),
			'description' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Metric::DESCRIPTION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Metric::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_METRIC],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Metric::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_METRIC, 'q' => ''],
					]
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Metric::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'type' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Metric::TYPE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Metric::UPDATED_AT),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Metric::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Metric::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_METRIC, $fields, null);
		
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
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Metric::VIRTUAL_WATCHERS, $tokens);
			
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_METRIC);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/metric/view.tpl');
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
			case SearchFields_Metric::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_Metric::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_Metric::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_Metric::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_Metric::DESCRIPTION:
			case SearchFields_Metric::DIMENSIONS_KATA:
			case SearchFields_Metric::NAME:
			case SearchFields_Metric::TYPE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_Metric::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_Metric::CREATED_AT:
			case SearchFields_Metric::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case 'placeholder_bool':
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_Metric::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_Metric::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
			
			case SearchFields_Metric::VIRTUAL_WATCHERS:
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

class Context_Metric extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = CerberusContexts::CONTEXT_METRIC;
	const URI = 'metric';
	
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
		return DAO_Metric::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=metric&id='.$context_id, true);
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Metric();
		
		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];
		
		$properties['description'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.description'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->description,
		];
		
		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		];
		
		$properties['type'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.type'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->type,
		];
		
		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];
		
		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($metric = DAO_Metric::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($metric->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $metric->id,
			'name' => $metric->name,
			'permalink' => $url,
			'updated' => $metric->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'description',
			'type',
			'created_at',
			'updated_at',
		);
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_Metric::getByName($alias)))
			return $model->id;
		
		return null;
	}
	
	public function autocomplete($term, $query = null) {
		$list = [];
		
		list($results,) = DAO_Metric::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Metric::NAME,DevblocksSearchCriteria::OPER_LIKE,'%'.$term.'%'),
			),
			25,
			0,
			SearchFields_Metric::NAME,
			true,
			false
		);
		
		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_Metric::NAME];
			$entry->value = $row[SearchFields_Metric::ID];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContext($metric, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Metric:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_METRIC);
		
		// Polymorph
		if(is_numeric($metric)) {
			$metric = DAO_Metric::get($metric);
		} elseif($metric instanceof Model_Metric) {
			// It's what we want already.
			true;
		} elseif(is_array($metric)) {
			$metric = Cerb_ORMHelper::recastArrayToModel($metric, 'Model_Metric');
		} else {
			$metric = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'created_at' => $prefix.$translate->_('common.created'),
			'description' => $prefix.$translate->_('common.description'),
			'dimensions_kata' => $prefix.$translate->_('dao.metric.dimensions_kata'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'type' => $prefix.$translate->_('common.type'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'description' => Model_CustomField::TYPE_SINGLE_LINE,
			'dimensions_kata' => Model_CustomField::TYPE_MULTI_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_METRIC;
		$token_values['_type'] = 'metric';
		$token_values['_types'] = $token_types;
		
		if($metric) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $metric->name;
			$token_values['created_at'] = $metric->created_at;
			$token_values['description'] = $metric->description;
			$token_values['dimensions_kata'] = $metric->dimensions_kata;
			$token_values['id'] = $metric->id;
			$token_values['name'] = $metric->name;
			$token_values['type'] = $metric->type;
			$token_values['updated_at'] = $metric->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($metric, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=metric&id=%d-%s",$metric->id, DevblocksPlatform::strToPermalink($metric->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_Metric::CREATED_AT,
			'id' => DAO_Metric::ID,
			'description' => DAO_Metric::DESCRIPTION,
			'dimensions_kata' => DAO_Metric::DIMENSIONS_KATA,
			'links' => '_links',
			'name' => DAO_Metric::NAME,
			'type' => DAO_Metric::TYPE,
			'updated_at' => DAO_Metric::UPDATED_AT,
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
		
		$lazy_keys['dimensions'] = [
			'label' => 'Dimensions',
			'type' => 'HashMap',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_METRIC;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'dimensions':
				$metric = new Model_Metric();
				$metric->dimensions_kata = $dictionary['dimensions_kata'] ?? $values['dimensions_kata'] ?? '';
				$values[$token] = $metric->getDimensions();
				break;
				
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
		$view->name = 'Metrics';
		$view->renderSortBy = SearchFields_Metric::NAME;
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
		$view->name = 'Metrics';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Metric::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_METRIC;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_Metric::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			} else {
				$model = new Model_Metric();
				$model->id = 0;
				$model->dimensions_kata = "# [TODO] Dimensions are key/value pairs that partition a metric.\n# (use Ctrl+Space for autocompletion)\n";
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
			$tpl->assign('model', $model);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/metric/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
