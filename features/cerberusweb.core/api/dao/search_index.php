<?php

use Cerb\Extensions\Extension_SearchIndex;

class DAO_SearchIndex extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const EXTENSION_ID = 'extension_id';
	const EXTENSION_PARAMS_JSON = 'extension_params_json';
	const ID = 'id';
	const NAME = 'name';
	const PRIORITY = 'priority';
	const RECORD_FILTER = 'record_filter';
	const RECORD_TYPE = 'record_type';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	
	private const _CACHE_ALL = 'search_indexes_all';
	
	private function __construct() {}
	
	public static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::EXTENSION_ID, DevblocksPlatform::translateCapitalized('common.type'))
			->string()
			->setRequired(true)
			->addValidator($validation->validators()->extension('Cerb\Extensions\Extension_SearchIndex'))
		;
		$validation
			->addField(self::EXTENSION_PARAMS_JSON)
			->string()
			->setMaxLength(16777215)
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
		;
		$validation
			->addField(self::PRIORITY)
			->uint()
			->setMin(0)
			->setMax(255)
		;
		$validation
			->addField(self::RECORD_FILTER)
			->string()
			->setNotEmpty(false)
			->setUniqueCallback(function(DevblocksValidationField $field, $value, array $scope, &$error=null) {
				if(is_numeric($value)) {
					$error = "Search index filters can't be entirely numeric.";
					return false;
				}
				
				$id = $scope['id'] ?? null;
				$context = $scope['fields'][DAO_SearchIndex::RECORD_TYPE] ?? null;
				
				if(!$context && $id) {
					if(!($field = DAO_SearchIndex::get($id)))
						return false;
					
					$context = $field->record_type;
				}
				
				if(!$context) {
					$error = sprintf("No record type.");
					return false;
				}
				
				if(!($context_ext = Extension_DevblocksContext::getByAlias($context, true))) {
					$error = sprintf("Unknown record type `%s`.", $context);
					return false;
				}
				
				$aliases = Extension_DevblocksContext::getAliasesForContext($context_ext->manifest);
				$alias = $aliases['singular'] ?: $aliases['uri'];
				
				$fields = $context_ext->getKeyMeta(false);
				
				if(array_key_exists(DevblocksPlatform::strLower($value), $fields)) {
					$error = sprintf("A field on %s records already exists for URI `%s`.", $alias, $value);
					return false;
				}
				
				$models = DAO_SearchIndex::getWhere(sprintf("%s = %s AND %s = %s AND id != %d",
					Cerb_ORMHelper::escape(DAO_SearchIndex::RECORD_TYPE),
					Cerb_ORMHelper::qstr($context),
					Cerb_ORMHelper::escape(DAO_SearchIndex::RECORD_FILTER),
					Cerb_ORMHelper::qstr($value),
					$id
				));
				
				if($models) {
					$error = "Filter must be unique within the same record type.";
					return false;
				}
				
				return true;
			})
			->addValidator(function($string, &$error=null) {
				if(0 != strcmp($string, DevblocksPlatform::strAlphaNum($string, '.'))) {
					$error = "may only contain letters, numbers, and dots";
					return false;
				}
				
				if(strlen($string) > 128) {
					$error = "must be shorter than 128 characters.";
					return false;
				}
				
				return true;
			})
		;
		$validation
			->addField(self::RECORD_TYPE)
			->string()
			->addValidator($validation->validators()->context())
		;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::URI)
			->string()
			->setUnique(__CLASS__)
			->setNotEmpty(false)
			->addValidator(function($string, &$error=null) {
				if(0 != strcasecmp($string, DevblocksPlatform::strAlphaNum($string, '.'))) {
					$error = "may only contain lowercase letters, numbers, and dots";
					return false;
				}
				
				if(strlen($string) > 128) {
					$error = "must be shorter than 128 characters.";
					return false;
				}
				
				return true;
			})
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
		
		if(!array_key_exists(DAO_SearchIndex::CREATED_AT, $fields))
			$fields[DAO_SearchIndex::CREATED_AT] = time();
		
		$sql = "INSERT INTO search_index () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_SearchIndex::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = Context_SearchIndex::ID;
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
			parent::_update($batch_ids, 'search_index', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.search_index.update',
						[
							'fields' => $fields,
						]
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('search_index', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = Context_SearchIndex::ID;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_SearchIndex[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, uri, extension_id, extension_params_json, record_type, record_filter, priority, created_at, updated_at ".
			"FROM search_index ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & DevblocksORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_SearchIndex[]
	 */
	static function getAll(bool $nocache = false) : array {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(
				null,
				DAO_SearchIndex::PRIORITY,
				true,
				null,
				DevblocksORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($objects))
				return [];
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}
	
	/**
	 * @param string $record_type
	 * @return Model_SearchIndex[]
	 */
	static function getByRecordType(string $record_type) : array {
		$indexes = self::getAll();
		
		return array_filter($indexes, function(Model_SearchIndex $index) use ($record_type) {
			return CerberusContexts::isSameContext($index->record_type, $record_type);
		});
	}
	
	/**
	 * @param integer $id
	 * @return Model_SearchIndex
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(array_key_exists($id, $objects))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 *
	 * @param array $ids
	 * @return Model_SearchIndex[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	static function getByUri($uri) : ?Model_SearchIndex {
		$search_indexes = DAO_SearchIndex::getAll();
		$uris = array_column($search_indexes, 'id', 'uri');
		
		if(array_key_exists($uri, $uris)) {
			$id = $uris[$uri];
			return $search_indexes[$id];
		}
		
		return null;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_SearchIndex[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_SearchIndex();
			$object->created_at = intval($row['created_at']);
			$object->extension_id = $row['extension_id'];
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->priority = intval($row['priority']);
			$object->record_filter = $row['record_filter'];
			$object->record_type = $row['record_type'];
			$object->updated_at = intval($row['updated_at']);
			$object->uri = $row['uri'];
			
			if(($json = json_decode($row['extension_params_json'] ?? '', true)))
				$object->extension_params = $json;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('search_index');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = Context_SearchIndex::ID;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		// [TODO] Search extension cleanup
		
		$db->ExecuteMaster(sprintf("DELETE FROM search_index WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_SearchIndex::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_SearchIndex', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"search_index.id as %s, ".
			"search_index.name as %s, ".
			"search_index.uri as %s, ".
			"search_index.extension_id as %s, ".
			"search_index.record_filter as %s, ".
			"search_index.record_type as %s, ".
			"search_index.priority as %s, ".
			"search_index.created_at as %s, ".
			"search_index.updated_at as %s ",
			SearchFields_SearchIndex::ID,
			SearchFields_SearchIndex::NAME,
			SearchFields_SearchIndex::URI,
			SearchFields_SearchIndex::EXTENSION_ID,
			SearchFields_SearchIndex::RECORD_FILTER,
			SearchFields_SearchIndex::RECORD_TYPE,
			SearchFields_SearchIndex::PRIORITY,
			SearchFields_SearchIndex::CREATED_AT,
			SearchFields_SearchIndex::UPDATED_AT
		);
		
		$join_sql = "FROM search_index ";
		
		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_SearchIndex');
		
		return [
			'primary_table' => 'search_index',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
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
			SearchFields_SearchIndex::ID,
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

class SearchFields_SearchIndex extends DevblocksSearchFields {
	const CREATED_AT = 'r_created_at';
	const EXTENSION_ID = 'r_extension_id';
	const ID = 'r_id';
	const NAME = 'r_name';
	const PRIORITY = 'r_priority';
	const RECORD_FILTER = 'r_record_filter';
	const RECORD_TYPE = 'r_record_type';
	const UPDATED_AT = 'r_updated_at';
	const URI = 'r_uri';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'search_index.id';
	}
	
	static function getCustomFieldContextKeys() {
		return [
			Context_SearchIndex::ID => new DevblocksSearchFieldContextKeys('search_index.id', self::ID),
		];
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, Context_SearchIndex::ID, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromFieldset($param, Context_SearchIndex::ID, self::getPrimaryKey());
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, Context_SearchIndex::ID, self::getPrimaryKey());
			
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
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
			case SearchFields_SearchIndex::ID:
				$models = DAO_SearchIndex::getIds($values);
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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'search_index', 'created_at', $translate->_('common.created'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'search_index', 'extension_id', $translate->_('common.type'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'search_index', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'search_index', 'name', $translate->_('common.name'), null, true),
			self::PRIORITY => new DevblocksSearchField(self::PRIORITY, 'search_index', 'priority', $translate->_('common.priority'), null, true),
			self::RECORD_FILTER => new DevblocksSearchField(self::RECORD_FILTER, 'search_index', 'record_filter', $translate->_('common.filter'), null, true),
			self::RECORD_TYPE => new DevblocksSearchField(self::RECORD_TYPE, 'search_index', 'record_type', $translate->_('common.record.type'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'search_index', 'updated_at', $translate->_('common.updated'), null, true),
			self::URI => new DevblocksSearchField(self::URI, 'search_index', 'uri', $translate->_('common.uri'), null, true),
			
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

class Model_SearchIndex extends DevblocksRecordModel {
	public int $created_at = 0;
	public string $extension_id = '';
	public array $extension_params = [];
	public int $id = 0;
	public string $name = '';
	public int $priority = 50;
	public string $record_filter = '';
	public string $record_type = '';
	public int $updated_at = 0;
	public string $uri = '';
	
	public function getExtension(bool $as_instance=true) : Extension_SearchIndex|DevblocksExtensionManifest|null {
		return Extension_SearchIndex::get($this->extension_id, $as_instance);
	}
	
	public function getRecordTypeExtension(bool $as_instance=true) : Extension_DevblocksContext|DevblocksExtensionManifest|null {
		return Extension_DevblocksContext::getByAlias($this->record_type, $as_instance);
	}
};

class View_SearchIndex extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'search_indexes';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Search Indexes');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_SearchIndex::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_SearchIndex::NAME,
			SearchFields_SearchIndex::URI,
			SearchFields_SearchIndex::EXTENSION_ID,
			SearchFields_SearchIndex::RECORD_TYPE,
			SearchFields_SearchIndex::RECORD_FILTER,
			SearchFields_SearchIndex::PRIORITY,
			SearchFields_SearchIndex::UPDATED_AT,
		];
		
		$this->addColumnsHidden([
			SearchFields_SearchIndex::VIRTUAL_CONTEXT_LINK,
			SearchFields_SearchIndex::VIRTUAL_HAS_FIELDSET,
			SearchFields_SearchIndex::VIRTUAL_WATCHERS,
		]);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_SearchIndex::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_SearchIndex');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_SearchIndex', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_SearchIndex', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Fields
					case SearchFields_SearchIndex::EXTENSION_ID:
					case SearchFields_SearchIndex::RECORD_TYPE:
					case SearchFields_SearchIndex::VIRTUAL_CONTEXT_LINK:
					case SearchFields_SearchIndex::VIRTUAL_HAS_FIELDSET:
					case SearchFields_SearchIndex::VIRTUAL_WATCHERS:
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
		$context = Context_SearchIndex::ID;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_SearchIndex::EXTENSION_ID:
			case SearchFields_SearchIndex::RECORD_TYPE:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
			
			case SearchFields_SearchIndex::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_SearchIndex::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
			
			case SearchFields_SearchIndex::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_SearchIndex::getFields();
		
		$fields = [
			'text' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_SearchIndex::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'created' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => ['param_key' => SearchFields_SearchIndex::CREATED_AT],
				],
			'fieldset' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_SearchIndex::VIRTUAL_HAS_FIELDSET],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_SearchIndex::ID],
					]
				],
			'id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_SearchIndex::ID],
					'examples' => [
						['type' => 'chooser', 'context' => Context_SearchIndex::ID, 'q' => ''],
					]
				],
			'name' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_SearchIndex::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'priority' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_SearchIndex::PRIORITY],
				],
			'record_filter' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_SearchIndex::RECORD_FILTER, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'record_type' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_SearchIndex::RECORD_TYPE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'type' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_SearchIndex::EXTENSION_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'updated' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => ['param_key' => SearchFields_SearchIndex::UPDATED_AT],
				],
			'uri' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_SearchIndex::URI, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'watchers' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_SearchIndex::VIRTUAL_WATCHERS],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				],
		];
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_SearchIndex::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(Context_SearchIndex::ID, $fields, null);
		
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
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_SearchIndex::VIRTUAL_WATCHERS, $tokens);
			
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
		$custom_fields = DAO_CustomField::getByContext(Context_SearchIndex::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/search_index/view.tpl');
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
			case SearchFields_SearchIndex::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_SearchIndex::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_SearchIndex::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_SearchIndex::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_SearchIndex::EXTENSION_ID:
			case SearchFields_SearchIndex::NAME:
			case SearchFields_SearchIndex::RECORD_FILTER:
			case SearchFields_SearchIndex::RECORD_TYPE:
			case SearchFields_SearchIndex::URI:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_SearchIndex::ID:
			case SearchFields_SearchIndex::PRIORITY:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_SearchIndex::CREATED_AT:
			case SearchFields_SearchIndex::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case 'placeholder_bool':
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_SearchIndex::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_SearchIndex::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
			
			case SearchFields_SearchIndex::VIRTUAL_WATCHERS:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
			
			default:
				// Custom Fields
				if(str_starts_with($field, 'cf_')) {
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

class Context_SearchIndex extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.record.search.index';
	const URI = 'search_index';
	
	static function isReadableByActor($models, $actor) : bool {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) : bool {
		return self::_isWriteableOnlyByAdmin($models, $actor);
	}
	
	static function isDeletableByActor($models, $actor) : bool {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_SearchIndex::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=search_index&id='.$context_id, true);
	}
	
	function profileGetFields($model=null) : array {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_SearchIndex();
		
		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];
		
		$properties['extension_id'] = [
			'label' => DevblocksPlatform::translate('common.type'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->extension_id,
		];
		
		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];
		
		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		];
		
		$properties['priority'] = [
			'label' => DevblocksPlatform::translate('common.priority'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->priority,
		];
		
		$properties['record_filter'] = [
			'label' => DevblocksPlatform::translate('common.filter'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->record_filter,
		];
		
		$properties['record_type'] = [
			'label' => DevblocksPlatform::translate('common.record.type'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->record_type,
		];
		
		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];
		
		$properties['uri'] = [
			'label' => DevblocksPlatform::translate('common.uri'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->uri,
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($search_index = DAO_SearchIndex::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($search_index->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return [
			'id' => $search_index->id,
			'name' => $search_index->name,
			'permalink' => $url,
			'updated' => $search_index->updated_at,
		];
	}
	
	function getDefaultProperties() : array {
		return [
			'record_type',
			'record_filter',
			'extension_id',
			'priority',
			'uri',
			'updated_at',
		];
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(($model = DAO_SearchIndex::getByUri($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($search_index, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Search Index:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_SearchIndex::ID);
		
		// Polymorph
		if(is_numeric($search_index)) {
			$search_index = DAO_SearchIndex::get($search_index);
		} elseif($search_index instanceof Model_SearchIndex) {
			// It's what we want already.
			DevblocksPlatform::noop();
		} elseif(is_array($search_index)) {
			$search_index = Cerb_ORMHelper::recastArrayToModel($search_index, 'Model_SearchIndex');
		} else {
			$search_index = null;
		}
		
		// Token labels
		$token_labels = [
			'_label' => $prefix,
			'extension_id' => $prefix.$translate->_('common.type'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'priority' => $prefix.$translate->_('common.priority'),
			'record_filter' => $prefix.$translate->_('common.filter'),
			'record_type' => $prefix.$translate->_('common.record.type'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'uri' => $prefix.$translate->_('common.uri'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		];
		
		// Token types
		$token_types = [
			'_label' => 'context_url',
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'priority' => Model_CustomField::TYPE_NUMBER,
			'record_filter' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_type' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'uri' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
		];
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_SearchIndex::ID;
		$token_values['_type'] = 'search_index';
		$token_values['_types'] = $token_types;
		
		if($search_index) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $search_index->name;
			$token_values['extension_id'] = $search_index->extension_id;
			$token_values['id'] = $search_index->id;
			$token_values['name'] = $search_index->name;
			$token_values['priority'] = $search_index->priority;
			$token_values['record_filter'] = $search_index->record_filter;
			$token_values['record_type'] = $search_index->record_type;
			$token_values['updated_at'] = $search_index->updated_at;
			$token_values['uri'] = $search_index->uri;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($search_index, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=search_index&id=%d-%s",$search_index->id, DevblocksPlatform::strToPermalink($search_index->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'extension_id' => DAO_SearchIndex::EXTENSION_ID,
			'id' => DAO_SearchIndex::ID,
			'links' => '_links',
			'name' => DAO_SearchIndex::NAME,
			'priority' => DAO_SearchIndex::PRIORITY,
			'record_filter' => DAO_SearchIndex::RECORD_FILTER,
			'record_type' => DAO_SearchIndex::RECORD_TYPE,
			'updated_at' => DAO_SearchIndex::UPDATED_AT,
			'uri' => DAO_SearchIndex::URI,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		return parent::getKeyMeta($with_dao_fields);
	}
	
	function getKeyAutocompleteSuggestions() : array {
		$extensions = DevblocksPlatform::getExtensions(Extension_SearchIndex::POINT);
		
		return [
			'extension_id' => array_keys($extensions),
			'record_type' => self::getAutocompleteRecordTypes(),
		];
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		return parent::lazyLoadGetKeys();
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_SearchIndex::ID;
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
		$view->name = 'Search Index';
		$view->renderSortBy = SearchFields_SearchIndex::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Search Index';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = [
				new DevblocksSearchCriteria(SearchFields_SearchIndex::VIRTUAL_CONTEXT_LINK,'in',[$context.':'.$context_id]),
			];
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = Context_SearchIndex::ID;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(!($model = DAO_SearchIndex::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
				$tpl->assign('search_extension', $model->getExtension());
			}
			
			// Extensions
			$search_extensions = Extension_SearchIndex::getAll(false);
			$tpl->assign('search_extensions', $search_extensions);
			
			// Record types
			$contexts = Extension_DevblocksContext::getAll(false, ['search']);
			$tpl->assign('contexts', $contexts);
			
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
			$tpl->display('devblocks:cerberusweb.core::records/types/search_index/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
