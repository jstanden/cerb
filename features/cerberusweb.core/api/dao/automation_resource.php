<?php
class DAO_AutomationResource extends Cerb_ORMHelper {
	const ID = 'id';
	const EXPIRES_AT = 'expires_at';
	const MIME_TYPE = 'mime_type';
	const STORAGE_EXTENSION = 'storage_extension';
	const STORAGE_KEY = 'storage_key';
	const STORAGE_PROFILE_ID = 'storage_profile_id';
	const STORAGE_SIZE = 'storage_size';
	const TOKEN = 'token';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
		;
		$validation
			->addField(self::MIME_TYPE)
			->string()
		;
		$validation
			->addField(self::TOKEN)
			->string()
			->setRequired(true)
			->setUnique(get_class())
			->setNotEmpty(false)
			->addValidator(function($string, &$error=null) {
				if(0 != strcmp($string, DevblocksPlatform::strAlphaNum($string, '-'))) {
					$error = "may only contain letters, numbers, and dashes";
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
		
		if(!array_key_exists(self::TOKEN, $fields))
			$fields[self::TOKEN] = DevblocksPlatform::services()->string()->uuid();
		
		$sql = "INSERT INTO automation_resource () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_AUTOMATION_RESOURCE, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_RESOURCE;
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
			parent::_update($batch_ids, 'automation_resource', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.automation_resource.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('automation_resource', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_AUTOMATION_RESOURCE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_AutomationResource[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, token, mime_type, expires_at, storage_size, storage_key, storage_extension, storage_profile_id, updated_at ".
			"FROM automation_resource ".
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
	 * @param integer|string $id
	 * @return Model_AutomationResource	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		if(!is_numeric($id))
			return self::getByToken($id);
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param string $resource_token
	 * @return Model_AutomationResource|null
	 */
	public static function getByToken(string $resource_token) : ?Model_AutomationResource {
		$objects = self::getWhere(sprintf("%s = %s",
			self::TOKEN,
			self::qstr($resource_token)
		));
		
		if(!is_array($objects) || !count($objects))
			return null;
		
		return current($objects);
	}
	
	/**
	 * @param string[] $resource_tokens
	 * @return Model_AutomationResource[]
	 */
	public static function getByTokens(array $resource_tokens) {
		return self::getWhere(sprintf("%s IN (%s)",
			self::TOKEN,
			implode(',', self::qstrArray($resource_tokens))
		));
	}
	
	/**
	 *
	 * @param array $ids
	 * @return Model_AutomationResource[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AutomationResource[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AutomationResource();
			$object->expires_at = intval($row['expires_at']);
			$object->id = intval($row['id']);
			$object->mime_type = $row['mime_type'];
			$object->storage_extension = $row['storage_extension'];
			$object->storage_key = $row['storage_key'];
			$object->storage_profile_id = intval($row['storage_profile_id']);
			$object->storage_size = intval($row['storage_size']);
			$object->token = $row['token'];
			$object->updated_at = $row['updated_at'];
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('automation_resource');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return true;
		
		Storage_AutomationResource::delete($ids);
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM automation_resource WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_AUTOMATION_RESOURCE,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		// Delete any expired keys (0=forever)
		$sql = sprintf("DELETE FROM automation_resource WHERE expires_at < %d",
			time()
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AutomationResource::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AutomationResource', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"automation_resource.id as %s, ".
			"automation_resource.expires_at as %s, ".
			"automation_resource.mime_type as %s, ".
			"automation_resource.storage_size as %s, ".
			"automation_resource.storage_key as %s, ".
			"automation_resource.storage_extension as %s, ".
			"automation_resource.storage_profile_id as %s, ".
			"automation_resource.token as %s, ".
			"automation_resource.updated_at as %s ",
			SearchFields_AutomationResource::ID,
			SearchFields_AutomationResource::EXPIRES_AT,
			SearchFields_AutomationResource::MIME_TYPE,
			SearchFields_AutomationResource::STORAGE_SIZE,
			SearchFields_AutomationResource::STORAGE_KEY,
			SearchFields_AutomationResource::STORAGE_EXTENSION,
			SearchFields_AutomationResource::STORAGE_PROFILE_ID,
			SearchFields_AutomationResource::TOKEN,
			SearchFields_AutomationResource::UPDATED_AT
		);
		
		$join_sql = "FROM automation_resource ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AutomationResource');
		
		return array(
			'primary_table' => 'automation_resource',
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
			SearchFields_AutomationResource::ID,
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

class SearchFields_AutomationResource extends DevblocksSearchFields {
	const ID = 'r_id';
	const EXPIRES_AT = 'r_expires_at';
	const MIME_TYPE = 'r_mime_type';
	const STORAGE_SIZE = 'r_storage_size';
	const STORAGE_KEY = 'r_storage_key';
	const STORAGE_EXTENSION = 'r_storage_extension';
	const STORAGE_PROFILE_ID = 'r_storage_profile_id';
	const TOKEN = 'r_token';
	const UPDATED_AT = 'r_updated_at';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'automation_resource.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_AUTOMATION_RESOURCE => new DevblocksSearchFieldContextKeys('automation_resource.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_AUTOMATION_RESOURCE, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_AUTOMATION_RESOURCE), '%s'), self::getPrimaryKey());
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_AutomationResource::ID:
				$models = DAO_AutomationResource::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'token', 'id');
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
			self::ID => new DevblocksSearchField(self::ID, 'automation_resource', 'id', $translate->_('common.id'), null, true),
			self::MIME_TYPE => new DevblocksSearchField(self::MIME_TYPE, 'automation_resource', 'mime_type', $translate->_('attachment.mime_type'), null, true),
			self::STORAGE_EXTENSION => new DevblocksSearchField(self::STORAGE_EXTENSION, 'automation_resource', 'storage_extension', $translate->_('common.storage_extension'), null, true),
			self::STORAGE_KEY => new DevblocksSearchField(self::STORAGE_KEY, 'automation_resource', 'storage_key', $translate->_('common.storage_key'), null, true),
			self::STORAGE_PROFILE_ID => new DevblocksSearchField(self::STORAGE_PROFILE_ID, 'automation_resource', 'storage_profile_id', $translate->_('common.storage_profile_id'), null, true),
			self::STORAGE_SIZE => new DevblocksSearchField(self::STORAGE_SIZE, 'automation_resource', 'storage_size', $translate->_('common.size'), null, true),
			self::TOKEN => new DevblocksSearchField(self::TOKEN, 'automation_resource', 'token', $translate->_('common.token'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'automation_resource', 'updated_at', $translate->_('common.updated'), null, true),
			
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

class Model_AutomationResource {
	public $expires_at;
	public $id;
	public $mime_type;
	public $storage_extension;
	public $storage_key;
	public $storage_profile_id;
	public $storage_size;
	public $token;
	public $updated_at;
	
	public function getFileContents(&$fp=null) {
		return Storage_AutomationResource::get($this, $fp);
	}
};

class Model_AutomationResource_ContentData {
	public $headers = [];
	public $data = null;
	public $expires_at = null;
	public $error = null;
	
	public function writeHeaders() {
		foreach($this->headers as $header)
			header($header);
		
		return true;
	}
	
	public function writeBody() {
		if(!is_resource($this->data))
			return false;
		
		fpassthru($this->data);
		fclose($this->data);
		return true;
	}
}

class View_AutomationResource extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'automation_resources';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		
		$this->name = DevblocksPlatform::translateCapitalized('common.automation.resources');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_AutomationResource::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_AutomationResource::TOKEN,
			SearchFields_AutomationResource::MIME_TYPE,
			SearchFields_AutomationResource::STORAGE_SIZE,
			SearchFields_AutomationResource::UPDATED_AT,
		];
		
		$this->addColumnsHidden([
			SearchFields_AutomationResource::VIRTUAL_CONTEXT_LINK,
			SearchFields_AutomationResource::VIRTUAL_HAS_FIELDSET,
		]);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_AutomationResource::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AutomationResource');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_AutomationResource', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AutomationResource', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					case SearchFields_AutomationResource::MIME_TYPE:
					case SearchFields_AutomationResource::VIRTUAL_CONTEXT_LINK:
					case SearchFields_AutomationResource::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_AUTOMATION_RESOURCE;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_AutomationResource::MIME_TYPE:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
			
			case SearchFields_AutomationResource::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_AutomationResource::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_AutomationResource::getFields();
		
		$fields = array(
			'text' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_AutomationResource::TOKEN, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_AutomationResource::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_AUTOMATION_RESOURCE],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_AutomationResource::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_AUTOMATION_RESOURCE, 'q' => ''],
					]
				),
			'mimetype' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_AutomationResource::MIME_TYPE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'size' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_AutomationResource::STORAGE_SIZE),
				),
			'token' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_AutomationResource::TOKEN, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_AutomationResource::UPDATED_AT),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_AutomationResource::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_AUTOMATION_RESOURCE, $fields, null);
		
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
			
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}
	
	function render() {
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
			case SearchFields_AutomationResource::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_AutomationResource::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_AutomationResource::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_AutomationResource::MIME_TYPE:
			case SearchFields_AutomationResource::STORAGE_EXTENSION:
			case SearchFields_AutomationResource::STORAGE_KEY:
			case SearchFields_AutomationResource::TOKEN:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_AutomationResource::ID:
			case SearchFields_AutomationResource::STORAGE_PROFILE_ID:
			case SearchFields_AutomationResource::STORAGE_SIZE:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_AutomationResource::EXPIRES_AT:
			case SearchFields_AutomationResource::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case SearchFields_AutomationResource::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_AutomationResource::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
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

class Storage_AutomationResource extends Extension_DevblocksStorageSchema {
	const ID = 'cerb.storage.schema.automation.resources';
	
	public static function getActiveStorageProfile() {
		return DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile', 'devblocks.storage.engine.disk');
	}
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 0));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/automation_resources/render.tpl");
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 0));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/automation_resources/config.tpl");
	}
	
	function saveConfig() {
		$active_storage_profile = DevblocksPlatform::importGPC($_POST['active_storage_profile'] ?? null, 'string','');
		$archive_storage_profile = DevblocksPlatform::importGPC($_POST['archive_storage_profile'] ?? null, 'string','');
		$archive_after_days = DevblocksPlatform::importGPC($_POST['archive_after_days'] ?? null, 'integer',0);
		
		if(!empty($active_storage_profile))
			$this->setParam('active_storage_profile', $active_storage_profile);
		
		if(!empty($archive_storage_profile))
			$this->setParam('archive_storage_profile', $archive_storage_profile);
		
		$this->setParam('archive_after_days', $archive_after_days);
		
		return true;
	}
	
	/**
	 * @param Model_AutomationResource|integer $object
	 * @param resource $fp
	 * @return mixed
	 */
	public static function get($object, &$fp=null) {
		if($object instanceof Model_AutomationResource) {
			// Do nothing
		} elseif(is_numeric($object)) {
			$object = DAO_AutomationResource::get($object);
		} else {
			$object = null;
		}
		
		if(empty($object))
			return false;
		
		$key = $object->storage_key;
		$profile = !empty($object->storage_profile_id) ? $object->storage_profile_id : $object->storage_extension;
		
		if(false === ($storage = DevblocksPlatform::getStorageService($profile)))
			return false;
		
		return $storage->get('automation_resources', $key, $fp);
	}
	
	/**
	 * @param int $id
	 * @param string $contents
	 * @param Model_DevblocksStorageProfile|int $profile
	 * @return bool|void
	 */
	public static function put($id, $contents, $profile=null) {
		if(empty($profile)) {
			$profile = self::getActiveStorageProfile();
		}
		
		$profile_id = 0;
		
		if($profile instanceof Model_DevblocksStorageProfile) {
			$profile_id = $profile->id;
		} elseif(is_numeric($profile)) {
			$profile_id = intval($profile);
		}
		
		$storage = DevblocksPlatform::getStorageService($profile);
		
		if(is_string($contents)) {
			$storage_size = strlen($contents);
		} else if(is_resource($contents)) {
			$stats = fstat($contents);
			$storage_size = $stats['size'];
		} else {
			return false;
		}
		
		// Save to storage
		if(false === ($storage_key = $storage->put('automation_resources', $id, $contents)))
			return false;
		
		// Update storage key
		DAO_AutomationResource::update($id, array(
			DAO_AutomationResource::STORAGE_EXTENSION => $storage->manifest->id,
			DAO_AutomationResource::STORAGE_PROFILE_ID => $profile_id,
			DAO_AutomationResource::STORAGE_KEY => $storage_key,
			DAO_AutomationResource::STORAGE_SIZE => $storage_size,
		));
		
		return $storage_key;
	}
	
	/**
	 * @param int[] $ids
	 * @return bool
	 */
	public static function delete($ids) {
		if(!is_array($ids))
			$ids = [$ids];
		
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT storage_extension, storage_key, storage_profile_id FROM automation_resource WHERE id IN (%s)", implode(',',$ids));
		
		if(false == ($rs = $db->QueryReader($sql)))
			return false;
		
		// Delete the physical files
		
		while($row = mysqli_fetch_assoc($rs)) {
			$profile = !empty($row['storage_profile_id']) ? $row['storage_profile_id'] : $row['storage_extension'];
			
			if(null != ($storage = DevblocksPlatform::getStorageService($profile)))
				if(false === $storage->delete('automation_resources', $row['storage_key']))
					return false;
		}
		
		mysqli_free_result($rs);
		
		return true;
	}
	
	public function getStats() {
		return $this->_stats('automation_resource');
	}
	
	public static function archive($stop_time=null) {
		$db = DevblocksPlatform::services()->database();
		
		// Params
		$src_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile'));
		$dst_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_storage_profile'));
		$archive_after_days = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_after_days');
		
		if(empty($src_profile) || empty($dst_profile))
			return;
		
		if(json_encode($src_profile) == json_encode($dst_profile))
			return;
		
		// Find inactive resources
		$sql = sprintf("SELECT automation_resource.id, automation_resource.storage_extension, automation_resource.storage_key, automation_resource.storage_profile_id, automation_resource.storage_size ".
			"FROM automation_resource".
			"WHERE automation_resource.updated_at < %d ".
			"AND (automation_resource.storage_extension = %s AND automation_resource.storage_profile_id = %d) ".
			"ORDER BY automation_resource.id ASC ".
			"LIMIT 500",
			time()-(86400*$archive_after_days),
			$db->qstr($src_profile->extension_id),
			$src_profile->id
		);
		$rs = $db->QueryReader($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			self::_migrate($dst_profile, $row);
			
			if(time() > $stop_time)
				return;
		}
	}
	
	public static function unarchive($stop_time=null) {
		// We never unarchive
	}
	
	private static function _migrate($dst_profile, $row, $is_unarchive=false) {
		$logger = DevblocksPlatform::services()->log();
		
		$ns = 'automation_resources';
		
		$src_key = $row['storage_key'];
		$src_id = $row['id'];
		$src_size = $row['storage_size'];
		
		$src_profile = new Model_DevblocksStorageProfile();
		$src_profile->id = $row['storage_profile_id'];
		$src_profile->extension_id = $row['storage_extension'];
		
		if(empty($src_key) || empty($src_id)
			|| !$src_profile instanceof Model_DevblocksStorageProfile
			|| !$dst_profile instanceof Model_DevblocksStorageProfile
		)
			return;
		
		$src_engine = DevblocksPlatform::getStorageService(!empty($src_profile->id) ? $src_profile->id : $src_profile->extension_id);
		
		$logger->info(sprintf("[Storage] %s %s %d (%d bytes) from (%s) to (%s)...",
			(($is_unarchive) ? 'Unarchiving' : 'Archiving'),
			$ns,
			$src_id,
			$src_size,
			$src_profile->extension_id,
			$dst_profile->extension_id
		));
		
		// Do as quicker strings if under 1MB?
		$is_small = $src_size < 1000000;
		
		// If smaller than 1MB, load into a variable
		if($is_small) {
			if(false === ($data = $src_engine->get($ns, $src_key))) {
				$logger->error(sprintf("[Storage] Error reading %s key (%s) from (%s)",
					$ns,
					$src_key,
					$src_profile->extension_id
				));
				return;
			}
			// Otherwise, allocate a temporary file handle
		} else {
			$fp_in = DevblocksPlatform::getTempFile();
			if(false === $src_engine->get($ns, $src_key, $fp_in)) {
				$logger->error(sprintf("[Storage] Error reading %s key (%s) from (%s)",
					$ns,
					$src_key,
					$src_profile->extension_id
				));
				return;
			}
		}
		
		if($is_small) {
			$loaded_size = strlen($data);
		} else {
			$stats_in = fstat($fp_in);
			$loaded_size = $stats_in['size'];
		}
		
		$logger->info(sprintf("[Storage] Loaded %d bytes of data from (%s)...",
			$loaded_size,
			$src_profile->extension_id
		));
		
		if($is_small) {
			if(false === ($dst_key = self::put($src_id, $data, $dst_profile))) {
				$logger->error(sprintf("[Storage] Error saving %s %d to (%s)",
					$ns,
					$src_id,
					$dst_profile->extension_id
				));
				unset($data);
				return;
			}
		} else {
			if(false === ($dst_key = self::put($src_id, $fp_in, $dst_profile))) {
				$logger->error(sprintf("[Storage] Error saving %s %d to (%s)",
					$ns,
					$src_id,
					$dst_profile->extension_id
				));
				
				if(is_resource($fp_in))
					fclose($fp_in);
				return;
			}
		}
		
		$logger->info(sprintf("[Storage] Saved %s %d to destination (%s) as key (%s)...",
			$ns,
			$src_id,
			$dst_profile->extension_id,
			$dst_key
		));
		
		// Free resources
		if($is_small) {
			unset($data);
		} else {
			@unlink(DevblocksPlatform::getTempFileInfo($fp_in));
			if(is_resource($fp_in))
				fclose($fp_in);
		}
		
		$src_engine->delete($ns, $src_key);
		$logger->info(sprintf("[Storage] Deleted %s %d from source (%s)...",
			$ns,
			$src_id,
			$src_profile->extension_id
		));
		
		$logger->info(''); // blank
	}
};

class Context_AutomationResource extends Extension_DevblocksContext {
	const ID = CerberusContexts::CONTEXT_AUTOMATION_RESOURCE;
	const URI = 'automation_resource';
	
	static function isReadableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
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
		return DAO_AutomationResource::random();
	}
	
	function getMeta($context_id) {
		if(null == ($resource = DAO_AutomationResource::get($context_id)))
			return [];
		
		return array(
			'id' => $resource->id,
			'name' => $resource->token,
			'permalink' => null,
			'updated' => $resource->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'token',
			'mime_type',
			'storage_size',
			'updated_at',
		);
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_AutomationResource::getByToken($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($resource, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Automation Resource:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_AUTOMATION_RESOURCE);
		
		// Polymorph
		if(is_numeric($resource)) {
			$resource = DAO_AutomationResource::get($resource);
		} elseif($resource instanceof Model_AutomationResource) {
			// It's what we want already.
		} elseif(is_array($resource)) {
			$resource = Cerb_ORMHelper::recastArrayToModel($resource, 'Model_AutomationResource');
		} else {
			$resource = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'mime_type' => $prefix.$translate->_('attachment.mime_type'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'size' => $prefix.$translate->_('common.size'),
			'token' => $prefix.$translate->_('common.token'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'mime_type' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
			'size' => Model_CustomField::TYPE_NUMBER,
			'token' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = Context_AutomationResource::ID;
		$token_values['_type'] = Context_AutomationResource::URI;
		
		$token_values['_types'] = $token_types;
		
		if($resource) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $resource->token;
			$token_values['id'] = $resource->id;
			$token_values['mime_type'] = $resource->mime_type;
			$token_values['size'] = intval($resource->storage_size);
			$token_values['token'] = $resource->token;
			$token_values['updated_at'] = $resource->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($resource, $token_values);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'expires_at' => DAO_AutomationResource::EXPIRES_AT,
			'id' => DAO_AutomationResource::ID,
			'links' => '_links',
			'mime_type' => DAO_AutomationResource::MIME_TYPE,
			'size' => DAO_AutomationResource::STORAGE_SIZE,
			'storage_extension' => DAO_AutomationResource::STORAGE_EXTENSION,
			'storage_key' => DAO_AutomationResource::STORAGE_KEY,
			'token' => DAO_AutomationResource::TOKEN,
			'updated_at' => DAO_AutomationResource::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		return parent::getKeyMeta($with_dao_fields);
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
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
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_RESOURCE;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.automation.resources');
		$view->renderSortBy = SearchFields_AutomationResource::UPDATED_AT;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.automation.resources');
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_AutomationResource::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
};
