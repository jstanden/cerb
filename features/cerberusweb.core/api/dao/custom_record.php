<?php
class DAO_CustomRecord extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const NAME_PLURAL = 'name_plural';
	const PARAMS_JSON = 'params_json';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	
	const _CACHE_ALL = 'custom_records_all';
	
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
			;
		$validation
			->addField(self::NAME_PLURAL)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::URI)
			->string()
			->setRequired(true)
			->setUnique(get_class())
			->addFormatter(function(&$value, &$error=null) {
				$value = DevblocksPlatform::strLower($value);
				return true;
			})
			->addValidator(function($string, &$error=null) {
				if(0 != strcasecmp($string, DevblocksPlatform::strAlphaNum($string, '_'))) {
					$error = "may only contain lowercase letters, numbers, and underscores";
					return false;
				}
					
				if(strlen($string) > 64) {
					$error = "must be shorter than 64 characters.";
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
		
		$sql = "INSERT INTO custom_record () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		$sql = sprintf("
			CREATE TABLE `custom_record_%d` (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(255) DEFAULT '',
				owner_context VARCHAR(255) DEFAULT '',
				owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
				created_at INT UNSIGNED NOT NULL DEFAULT 0,
				updated_at INT UNSIGNED NOT NULL DEFAULT 0,
				primary key (id),
				index (created_at),
				index (updated_at),
				index owner (owner_context, owner_context_id)
			) ENGINE=%s;
			",
			$id,
			APP_DB_ENGINE
		);
		
		if(!$db->ExecuteMaster($sql))
			return false;
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		$cache = DevblocksPlatform::services()->cache();
		
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_CUSTOM_RECORD;
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
			parent::_update($batch_ids, 'custom_record', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.custom_record.update',
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
		parent::_updateWhere('custom_record', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_CUSTOM_RECORD;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CustomRecord[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, name_plural, params_json, updated_at, uri ".
			"FROM custom_record ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_CustomRecord[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_CustomRecord::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return false;
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_CustomRecord
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$custom_records = DAO_CustomRecord::getAll();
		
		if(array_key_exists($id, $custom_records))
			return $custom_records[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_CustomRecord[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	static function getByUri($uri) {
		$custom_records = DAO_CustomRecord::getAll();
		$uris = array_column($custom_records, 'id', 'uri');
		
		if(isset($uris[$uri])) {
			$id = $uris[$uri];
			return $custom_records[$id];
		}
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_CustomRecord[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_CustomRecord();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->name_plural = $row['name_plural'];
			$object->updated_at = $row['updated_at'];
			$object->uri = $row['uri'];
			
			@$params = json_decode($row['params_json'], true) ?: [];
			$object->params = $params;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('custom_record');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		$settings = DevblocksPlatform::services()->pluginSettings();

		if(!is_array($ids))
			$ids = [$ids];
		
		if(empty($ids))
			return;
		
		if(is_array($ids))
		foreach($ids as $id) {
			$context = sprintf('contexts.custom_record.%d', $id);
			$table_name = sprintf('custom_record_%d', $id);
			
			$sql = sprintf("SELECT count(id) FROM %s",
				$db->escape($table_name)
			);
			$count = $db->GetOneMaster($sql);
			
			// All records must be deleted first, or we refuse to delete the record type
			if($count)
				continue;
			
			// Drop the table
			$sql = sprintf("DROP TABLE %s", $table_name);
			$db->ExecuteMaster($sql);
			
			// Remove prefs
			$settings->delete('cerberusweb.core', [
				sprintf("card:contexts.custom_record.%d", $id),
			]);
			
			// Remove the PHP class
			@unlink(APP_STORAGE_PATH . sprintf('classes/abstract_record_%d', $id));
			
			// Delete custom record custom fields
			
			$custom_fields = DAO_CustomField::getByContext($context, true, false);
			$custom_fieldset_ids = array_diff(array_column($custom_fields, 'custom_fieldset_id'), [0]);
			$custom_field_ids = array_keys($custom_fields);
			
			if($custom_field_ids)
				DAO_CustomField::delete($custom_field_ids);
			
			if($custom_fieldset_ids)
				DAO_CustomFieldset::delete($custom_fieldset_ids);
			
			$db->ExecuteMaster(sprintf("DELETE FROM custom_record WHERE id = %d", $id));
			
			// Fire event
			$eventMgr = DevblocksPlatform::services()->event();
			$eventMgr->trigger(
				new Model_DevblocksEvent(
					'context.delete',
					array(
						'context' => CerberusContexts::CONTEXT_CUSTOM_RECORD,
						'context_ids' => [$id]
					)
				)
			);
		}
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CustomRecord::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CustomRecord', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"custom_record.id as %s, ".
			"custom_record.name as %s, ".
			"custom_record.name_plural as %s, ".
			"custom_record.uri as %s, ".
			"custom_record.params_json as %s, ".
			"custom_record.updated_at as %s ",
				SearchFields_CustomRecord::ID,
				SearchFields_CustomRecord::NAME,
				SearchFields_CustomRecord::NAME_PLURAL,
				SearchFields_CustomRecord::URI,
				SearchFields_CustomRecord::PARAMS_JSON,
				SearchFields_CustomRecord::UPDATED_AT
			);
			
		$join_sql = "FROM custom_record ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_CustomRecord');
	
		return array(
			'primary_table' => 'custom_record',
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
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::services()->database();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_CustomRecord::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(custom_record.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
		$cache->remove(DevblocksPlatform::CACHE_CONTEXTS);
		$cache->remove(DevblocksPlatform::CACHE_CONTEXTS_INSTANCES);
		$cache->remove(DevblocksPlatform::CACHE_CONTEXT_ALIASES);
		$cache->removeByTags(['schema_records','schema_workspaces','ui_search_menu']);
	}
};

class SearchFields_CustomRecord extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const NAME_PLURAL = 'c_name_plural';
	const PARAMS_JSON = 'c_params_json';
	const UPDATED_AT = 'c_updated_at';
	const URI = 'c_uri';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'custom_record.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CUSTOM_RECORD => new DevblocksSearchFieldContextKeys('custom_record.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CUSTOM_RECORD, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_CUSTOM_RECORD)), self::getPrimaryKey());
				break;
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
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
			case SearchFields_CustomRecord::ID:
				$models = DAO_CustomRecord::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'custom_record', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'custom_record', 'name', $translate->_('common.name'), null, true),
			self::NAME_PLURAL => new DevblocksSearchField(self::NAME_PLURAL, 'custom_record', 'name_plural', $translate->_('common.plural'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'custom_record', 'params_json', null, null, false),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'custom_record', 'updated_at', $translate->_('common.updated'), null, true),
			self::URI => new DevblocksSearchField(self::URI, 'custom_record', 'uri', $translate->_('common.uri'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
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

class Model_CustomRecord {
	public $id;
	public $name;
	public $name_plural;
	public $params = [];
	public $updated_at;
	public $uri;
	
	function getContext() {
		return sprintf("contexts.custom_record.%d", $this->id);
	}
	
	function getContextClass() {
		return sprintf("Context_AbstractCustomRecord_%d", $this->id);
	}
	
	function getDaoClass() {
		return sprintf("DAO_AbstractCustomRecord_%d", $this->id);
	}
	
	function getModelClass() {
		return sprintf("Model_AbstractCustomRecord_%d", $this->id);
	}
	
	function getSearchClass() {
		return sprintf("SearchFields_AbstractCustomRecord_%d", $this->id);
	}
	
	function getViewClass() {
		return sprintf("View_AbstractCustomRecord_%d", $this->id);
	}
	
	function getRecordOwnerContexts() {
		@$owner_contexts = $this->params['owners']['contexts'] ?: [];
		return $owner_contexts;
	}
	
	function hasOption($option) {
		if(!is_array($this->params))
			return false;
		
		if(!isset($this->params['options']) || !is_array($this->params['options']))
			return false;
		
		return in_array($option, $this->params['options']);
	}
};

class View_CustomRecord extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'custom_records';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.custom_records');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CustomRecord::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CustomRecord::NAME,
			SearchFields_CustomRecord::NAME_PLURAL,
			SearchFields_CustomRecord::URI,
			SearchFields_CustomRecord::UPDATED_AT,
		);
		$this->addColumnsHidden(array(
			SearchFields_CustomRecord::PARAMS_JSON,
			SearchFields_CustomRecord::VIRTUAL_CONTEXT_LINK,
			SearchFields_CustomRecord::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CustomRecord::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_CustomRecord');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_CustomRecord', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CustomRecord', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Virtuals
				case SearchFields_CustomRecord::VIRTUAL_CONTEXT_LINK:
				case SearchFields_CustomRecord::VIRTUAL_HAS_FIELDSET:
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
		$counts = array();
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_CUSTOM_RECORD;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
//			case SearchFields_CustomRecord::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
//				break;

//			case SearchFields_CustomRecord::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
//				break;
				
			case SearchFields_CustomRecord::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_CustomRecord::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
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
		$search_fields = SearchFields_CustomRecord::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomRecord::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_CustomRecord::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_CUSTOM_RECORD],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CustomRecord::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CUSTOM_RECORD, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomRecord::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'name.plural' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomRecord::NAME_PLURAL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_CustomRecord::UPDATED_AT),
				),
			'uri' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomRecord::URI, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_CustomRecord::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CUSTOM_RECORD, $fields, null);
		
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
				break;
			
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CUSTOM_RECORD);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/custom_records/view.tpl');
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
			case SearchFields_CustomRecord::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_CustomRecord::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_CustomRecord::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CustomRecord::NAME:
			case SearchFields_CustomRecord::NAME_PLURAL:
			case SearchFields_CustomRecord::URI:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CustomRecord::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_CustomRecord::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_CustomRecord::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_CustomRecord::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
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

class Context_CustomRecord extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	const ID = CerberusContexts::CONTEXT_CUSTOM_RECORD;
	
	static function isReadableByActor($models, $actor) {
		// Only admins can read
		return self::isWriteableByActor($models, $actor);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admins can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			CerberusContexts::denyEverything($models);
		
		// Admins can do whatever they want
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeleteableByActor($models, $actor) {
		$db = DevblocksPlatform::services()->database();
		
		$dicts = CerberusContexts::polymorphModelsToDictionaries($models, self::ID);
		
		// Default our results to write access
		$results = self::isWriteableByActor($dicts, $actor);
		
		// Only allow a custom record to be deleted if it's empty
		foreach(array_keys($dicts) as $dict_id) {
			// If not writeable, skip
			if(!$results[$dict_id])
				continue;
			
			// If writeable, there must be no records of this type left
			$sql = sprintf("SELECT count(id) FROM custom_record_%d",
				$dict_id
			);
			$count = $db->GetOneMaster($sql);

			$results[$dict_id] = empty($count);
		}
		
		if(is_array($models)) {
			return $results;
		} else {
			return reset($results);
		}
	}

	function getRandom() {
		return DAO_CustomRecord::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=custom_record&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_CustomRecord();
		
		$properties['id'] = array(
			'label' => mb_ucfirst($translate->_('common.id')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.singular')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CUSTOM_RECORD,
			],
		);
		
		$properties['name_plural'] = array(
			'label' => mb_ucfirst($translate->_('common.plural')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->name_plural,
		);
		
		$properties['uri'] = array(
			'label' => DevblocksPlatform::translate('common.uri'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->uri,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$custom_record = DAO_CustomRecord::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($custom_record->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $custom_record->id,
			'name' => $custom_record->name,
			'permalink' => $url,
			'updated' => $custom_record->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'name_plural',
			'uri',
			'updated_at',
		);
	}
	
	function getContext($custom_record, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Custom Record:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CUSTOM_RECORD);

		// Polymorph
		if(is_numeric($custom_record)) {
			$custom_record = DAO_CustomRecord::get($custom_record);
		} elseif($custom_record instanceof Model_CustomRecord) {
			// It's what we want already.
		} elseif(is_array($custom_record)) {
			$custom_record = Cerb_ORMHelper::recastArrayToModel($custom_record, 'Model_CustomRecord');
		} else {
			$custom_record = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'name_plural' => $prefix.$translate->_('common.plural'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'uri' => $prefix.$translate->_('common.uri'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'name_plural' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'uri' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CUSTOM_RECORD;
		$token_values['_types'] = $token_types;
		
		if($custom_record) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $custom_record->name;
			$token_values['id'] = $custom_record->id;
			$token_values['name'] = $custom_record->name;
			$token_values['name_plural'] = $custom_record->name_plural;
			$token_values['updated_at'] = $custom_record->updated_at;
			$token_values['uri'] = $custom_record->uri;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($custom_record, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=custom_record&id=%d-%s",$custom_record->id, DevblocksPlatform::strToPermalink($custom_record->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_CustomRecord::ID,
			'links' => '_links',
			'name' => DAO_CustomRecord::NAME,
			'name_plural' => DAO_CustomRecord::NAME_PLURAL,
			'updated_at' => DAO_CustomRecord::UPDATED_AT,
			'uri' => DAO_CustomRecord::URI,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['name']['notes'] = "The singular name of the record; `Issue`";
		$keys['name_plural']['notes'] = "The plural name of the record; `Issues`";
		$keys['uri']['notes'] = "The alias of the record (e.g. `issue`); used in URLs, API, etc.";
		
		$keys['params'] = [
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value object',
			'type' => 'object',
		];
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'params':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_CustomRecord::PARAMS_JSON] = $json;
				break;
			
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
		
		$context = CerberusContexts::CONTEXT_CUSTOM_RECORD;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
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
		$view->name = DevblocksPlatform::translateCapitalized('common.custom_records');
		/*
		$view->addParams(array(
			SearchFields_CustomRecord::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_CustomRecord::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_CustomRecord::NAME;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = DevblocksPlatform::translateCapitalized('common.custom_record');
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CustomRecord::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_CUSTOM_RECORD;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_CustomRecord::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_CustomRecord::isWriteableByActor($model, $active_worker))
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
			
			if(empty($context_id)) {
				$roles = DAO_WorkerRole::getAll();
				$roles = array_filter($roles, function($role) { /* @var $role Model_WorkerRole */
					return 'itemized' == $role->privs_mode;
				});
				$tpl->assign('roles', $roles);
			}
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/custom_records/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
