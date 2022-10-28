<?php
class DAO_Resource extends Cerb_ORMHelper {
	const AUTOMATION_KATA = 'automation_kata';
	const DESCRIPTION = 'description';
	const EXTENSION_ID = 'extension_id';
	const EXTENSION_KATA = 'extension_kata';
	const ID = 'id';
	const IS_DYNAMIC = 'is_dynamic';
	const NAME = 'name';
	const STORAGE_EXTENSION = 'storage_extension';
	const STORAGE_KEY = 'storage_key';
	const STORAGE_PROFILE_ID = 'storage_profile_id';
	const STORAGE_SIZE = 'storage_size';
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
			->addField(self::AUTOMATION_KATA)
			->string()
			->setMaxLength('16 bits')
		;
		$validation
			->addField(self::DESCRIPTION)
			->string()
		;
		$validation
			->addField(self::EXTENSION_ID)
			->string()
			->setRequired(true)
			->addValidator($validation->validators()->extension('Extension_ResourceType'))
		;
		$validation
			->addField(self::EXTENSION_KATA)
			->string()
			->setMaxLength('16 bits')
		;
		$validation
			->addField(self::IS_DYNAMIC)
			->bit()
		;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			->setUnique(get_class())
			->setNotEmpty(false)
			->addValidator(function($string, &$error=null) {
				if(0 != strcmp($string, DevblocksPlatform::strAlphaNum($string, '.-_'))) {
					$error = "may only contain letters, numbers, dashes, and dots";
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
			->addField('_content')
			->string($validation::STRING_UTF8MB4)
			->setMaxLength('32 bits')
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
		
		$sql = "INSERT INTO resource () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_RESOURCE, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_RESOURCE;
		self::_updateAbstract($context, $ids, $fields);
		self::_updateContent($ids, $fields);		
		
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
			parent::_update($batch_ids, 'resource', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.resource.update',
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
		parent::_updateWhere('resource', $fields, $where);
	}
	
	private static function _updateContent($ids, &$fields) {
		if(!isset($fields['_content']))
			return false;
		
		$content = $fields['_content'] ?? null;
		unset($fields['_content']);
		
		// If base64 encoded
		if(DevblocksPlatform::strStartsWith($content, 'data:')) {
			if(false !== ($idx = strpos($content, ';base64,'))) {
				$content = base64_decode(substr($content, $idx + strlen(';base64,')));
			}
		}
		
		foreach($ids as $id) {
			Storage_Resource::put($id, $content);
		}
		
		return true;
	}	
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_RESOURCE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	static function count() {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneMaster('SELECT COUNT(id) FROM resource');
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Resource[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, automation_kata, description, is_dynamic, extension_id, extension_kata, storage_size, storage_key, storage_extension, storage_profile_id, updated_at ".
			"FROM resource ".
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
	 * @param integer $id
	 * @return Model_Resource	 */
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
	 * @param string $resource_key
	 * @return Model_Resource|null
	 */
	public static function getByName(string $resource_key) {
		$objects = self::getWhere(sprintf("%s = %s",
			self::NAME,
			self::qstr($resource_key)
		));
		
		if(!is_array($objects) || !count($objects))
			return null;
		
		return current($objects);
	}
	
	/**
	 * @param string $resource_key
	 * @return Model_Resource|null
	 */
	public static function getByNameAndType(string $resource_key, string $extension_id) {
		$objects = self::getWhere(sprintf("%s = %s AND %s = %d",
			self::NAME,
			self::qstr($resource_key),
			self::EXTENSION_ID,
			self::qstr($extension_id),
		));
		
		if(!is_array($objects) || !count($objects))
			return null;
		
		return current($objects);
	}
	
	/**
	 * @param string[] $resource_keys
	 * @return Model_Resource[]
	 */
	public static function getByNames(array $resource_keys) {
		return self::getWhere(sprintf("%s IN (%s)",
			self::NAME,
			implode(',', self::qstrArray($resource_keys))
		));
	}
	
	/**
	 *
	 * @param array $ids
	 * @return Model_Resource[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Resource[]|false
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Resource();
			$object->automation_kata = $row['automation_kata'];
			$object->description = $row['description'];
			$object->extension_id = $row['extension_id'];
			$object->extension_kata = $row['extension_kata'];
			$object->id = intval($row['id']);
			$object->is_dynamic = $row['is_dynamic'] ? 1 : 0;
			$object->name = $row['name'];
			$object->storage_extension = $row['storage_extension'];
			$object->storage_key = $row['storage_key'];
			$object->storage_profile_id = intval($row['storage_profile_id']);
			$object->storage_size = $row['storage_size'];
			$object->updated_at = $row['updated_at'];
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('resource');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		Storage_Resource::delete($ids);
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM resource WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_RESOURCE,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Resource::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Resource', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"resource.id as %s, ".
			"resource.name as %s, ".
			"resource.is_dynamic as %s, ".
			"resource.extension_id as %s, ".
			"resource.description as %s, ".
			"resource.storage_size as %s, ".
			"resource.storage_key as %s, ".
			"resource.storage_extension as %s, ".
			"resource.storage_profile_id as %s, ".
			"resource.updated_at as %s ",
			SearchFields_Resource::ID,
			SearchFields_Resource::NAME,
			SearchFields_Resource::IS_DYNAMIC,
			SearchFields_Resource::EXTENSION_ID,
			SearchFields_Resource::DESCRIPTION,
			SearchFields_Resource::STORAGE_SIZE,
			SearchFields_Resource::STORAGE_KEY,
			SearchFields_Resource::STORAGE_EXTENSION,
			SearchFields_Resource::STORAGE_PROFILE_ID,
			SearchFields_Resource::UPDATED_AT
		);
		
		$join_sql = "FROM resource ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Resource');
		
		return array(
			'primary_table' => 'resource',
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
			SearchFields_Resource::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
	
	static function importFromJson($resource_data) {
		$db = DevblocksPlatform::services()->database();
		
		$storage = new DevblocksStorageEngineDatabase();
		$storage->setOptions([]);
		
		if(!array_key_exists('name', $resource_data))
			return false;
		
		$resource_data = array_merge(
			[
				'data' => '',
				'description' => '',
				'extension_id' => '',
				'extension_kata' => '',
				'automation_kata' => '',
				'is_dynamic' => 0,
				'expires_at' => 0,
				'updated_at' => time(),
			],
			$resource_data
		);
		
		$resource_id = $db->GetOneMaster(sprintf("SELECT id FROM resource WHERE name = %s",
			$db->qstr($resource_data['name'])
		));
		
		if($resource_id) {
			$db->ExecuteMaster(sprintf("UPDATE resource SET name = %s, description = %s, extension_id = %s, extension_kata = %s, automation_kata = %s, is_dynamic = %d, expires_at = %d, updated_at = %d WHERE id = %d",
				$db->qstr($resource_data['name']),
				$db->qstr($resource_data['description']),
				$db->qstr($resource_data['extension_id']),
				$db->qstr($resource_data['extension_kata']),
				$db->qstr($resource_data['automation_kata']),
				$resource_data['is_dynamic'],
				$resource_data['expires_at'],
				$resource_data['updated_at'],
				$resource_id
			));
			
		} else {
			$db->ExecuteMaster(sprintf("INSERT INTO resource (name, description, extension_id, extension_kata, automation_kata, is_dynamic, expires_at, updated_at) ".
				"VALUES (%s, %s, %s, %s, %s, %d, %d, %d)",
				$db->qstr($resource_data['name']),
				$db->qstr($resource_data['description']),
				$db->qstr($resource_data['extension_id']),
				$db->qstr($resource_data['extension_kata']),
				$db->qstr($resource_data['automation_kata']),
				$resource_data['is_dynamic'],
				$resource_data['expires_at'],
				$resource_data['updated_at']
			));
			
			$resource_id = $db->LastInsertId();
		}
		
		if($resource_id) {
			if($resource_data['data']) {
				$data_path = realpath(APP_PATH . '/features/cerberusweb.core/assets/resources/data/' . $resource_data['data']);
				
				if(false !== ($fp = fopen($data_path, 'r'))) {
					$fp_stat = fstat($fp);
					
					$storage_key = $storage->put('resources', $resource_id, $fp);
					
					$sql = sprintf("UPDATE resource SET storage_extension = %s, storage_key = %s, storage_size = %d WHERE id = %d",
						$db->qstr('devblocks.storage.engine.database'),
						$db->qstr($storage_key),
						$fp_stat['size'],
						$resource_id
					);
					$db->ExecuteMaster($sql);
					
					fclose($fp);
				}
			}
		}
		
		return $resource_id;
	}
};

class SearchFields_Resource extends DevblocksSearchFields {
	const ID = 'r_id';
	const NAME = 'r_name';
	const DESCRIPTION = 'r_description';
	const IS_DYNAMIC = 'r_is_dynamic';
	const EXTENSION_ID = 'r_extension_id';
	const STORAGE_SIZE = 'r_storage_size';
	const STORAGE_KEY = 'r_storage_key';
	const STORAGE_EXTENSION = 'r_storage_extension';
	const STORAGE_PROFILE_ID = 'r_storage_profile_id';
	const UPDATED_AT = 'r_updated_at';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'resource.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_RESOURCE => new DevblocksSearchFieldContextKeys('resource.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_RESOURCE, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_RESOURCE), '%s'), self::getPrimaryKey());
			
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
			case SearchFields_Resource::ID:
				$models = DAO_Resource::getIds($values);
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
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'resource', 'description', $translate->_('common.description'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'resource', 'id', $translate->_('common.id'), null, true),
			self::IS_DYNAMIC => new DevblocksSearchField(self::IS_DYNAMIC, 'resource', 'is_dynamic', $translate->_('dao.resource.is_dynamic'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'resource', 'extension_id', $translate->_('common.type'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'resource', 'name', $translate->_('common.name'), null, true),
			self::STORAGE_EXTENSION => new DevblocksSearchField(self::STORAGE_EXTENSION, 'resource', 'storage_extension', $translate->_('common.storage_extension'), null, true),
			self::STORAGE_KEY => new DevblocksSearchField(self::STORAGE_KEY, 'resource', 'storage_key', $translate->_('common.storage_key'), null, true),
			self::STORAGE_PROFILE_ID => new DevblocksSearchField(self::STORAGE_PROFILE_ID, 'resource', 'storage_profile_id', $translate->_('common.storage_profile_id'), null, true),
			self::STORAGE_SIZE => new DevblocksSearchField(self::STORAGE_SIZE, 'resource', 'storage_size', $translate->_('common.size'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'resource', 'updated_at', $translate->_('common.updated'), null, true),
			
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

class Model_Resource {
	public $automation_kata;
	public $description;
	public $id;
	public $is_dynamic;
	public $extension_id;
	public $extension_kata;
	public $name;
	public $storage_extension;
	public $storage_key;
	public $storage_profile_id;
	public $storage_size;
	public $updated_at;
	
	/**
	 * @param bool $as_instance
	 * @return Extension_ResourceType|DevblocksExtensionManifest
	 */
	public function getExtension($as_instance=true) {
		return Extension_ResourceType::get($this->extension_id, $as_instance);
	}
	
	public function getExtensionParams() : array {
		$kata = DevblocksPlatform::services()->kata();
		
		$error = null;
		
		if(!$this->extension_kata)
			return [];
		
		if(false == ($params = $kata->parse($this->extension_kata, $error)))
			return [];
		
		if(false == ($params = $kata->formatTree($params, null, $error)))
			return [];
		
		return $params;
	}
	
	public function getFileContents(&$fp=null) {
		return Storage_Resource::get($this, $fp);
	}
};

class Model_Resource_ContentData {
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

class View_Resource extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'resources';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		
		$this->name = DevblocksPlatform::translateCapitalized('common.resources');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Resource::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_Resource::NAME,
			SearchFields_Resource::DESCRIPTION,
			SearchFields_Resource::EXTENSION_ID,
			SearchFields_Resource::STORAGE_SIZE,
			SearchFields_Resource::UPDATED_AT,
		];
		
		$this->addColumnsHidden([
			SearchFields_Resource::VIRTUAL_CONTEXT_LINK,
			SearchFields_Resource::VIRTUAL_HAS_FIELDSET,
		]);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Resource::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Resource');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Resource', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Resource', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Fields
				case SearchFields_Resource::EXTENSION_ID:
				case SearchFields_Resource::IS_DYNAMIC:
					$pass = true;
					break;
					
					// Virtuals
					case SearchFields_Resource::VIRTUAL_CONTEXT_LINK:
					case SearchFields_Resource::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_RESOURCE;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Resource::EXTENSION_ID:
				$label_map = array_column(Extension_ResourceType::getAll(false), 'name', 'id');
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
			
			case SearchFields_Resource::IS_DYNAMIC:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;

			case SearchFields_Resource::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_Resource::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_Resource::getFields();
		
		$fields = array(
			'text' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Resource::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'description' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Resource::DESCRIPTION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Resource::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_RESOURCE],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Resource::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_RESOURCE, 'q' => ''],
					]
				),
			'isDynamic' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Resource::IS_DYNAMIC),
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Resource::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'size' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Resource::STORAGE_SIZE),
				),
			'type' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Resource::EXTENSION_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Resource::UPDATED_AT),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Resource::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_RESOURCE, $fields, null);
		
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
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_RESOURCE);
		$tpl->assign('custom_fields', $custom_fields);

		// Resource type extensions
		$resource_extensions = Extension_ResourceType::getAll(false);
		$tpl->assign('resource_extensions', $resource_extensions);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/resource/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		
		switch($field) {
			case SearchFields_Resource::EXTENSION_ID:
				$label_map = array_column(Extension_ResourceType::getAll(false), 'name', 'id');
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Resource::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_Resource::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_Resource::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_Resource::DESCRIPTION:
			case SearchFields_Resource::EXTENSION_ID:
			case SearchFields_Resource::NAME:
			case SearchFields_Resource::STORAGE_EXTENSION:
			case SearchFields_Resource::STORAGE_KEY:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_Resource::ID:
			case SearchFields_Resource::STORAGE_PROFILE_ID:
			case SearchFields_Resource::STORAGE_SIZE:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_Resource::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case SearchFields_Resource::IS_DYNAMIC:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_Resource::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_Resource::VIRTUAL_HAS_FIELDSET:
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

class Storage_Resource extends Extension_DevblocksStorageSchema {
	const ID = 'cerb.storage.schema.resources';
	
	public static function getActiveStorageProfile() {
		return DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile', 'devblocks.storage.engine.database');
	}
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.database'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.database'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 0));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/resources/render.tpl");
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.database'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.database'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 0));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/resources/config.tpl");
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
	 * @param Model_Resource|integer $object
	 * @param resource $fp
	 * @return mixed
	 */
	public static function get($object, &$fp=null) {
		if($object instanceof Model_Resource) {
			// Do nothing
		} elseif(is_numeric($object)) {
			$object = DAO_Resource::get($object);
		} else {
			$object = null;
		}
		
		if(empty($object))
			return false;
		
		$key = $object->storage_key;
		$profile = !empty($object->storage_profile_id) ? $object->storage_profile_id : $object->storage_extension;
		
		if(false === ($storage = DevblocksPlatform::getStorageService($profile)))
			return false;
		
		return $storage->get('resources', $key, $fp);
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
		if(false === ($storage_key = $storage->put('resources', $id, $contents)))
			return false;
		
		// Update storage key
		DAO_Resource::update($id, array(
			DAO_Resource::STORAGE_EXTENSION => $storage->manifest->id,
			DAO_Resource::STORAGE_PROFILE_ID => $profile_id,
			DAO_Resource::STORAGE_KEY => $storage_key,
			DAO_Resource::STORAGE_SIZE => $storage_size,
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
		
		$sql = sprintf("SELECT storage_extension, storage_key, storage_profile_id FROM resource WHERE id IN (%s)", implode(',',$ids));
		
		if(false == ($rs = $db->QueryReader($sql)))
			return false;
		
		// Delete the physical files
		
		while($row = mysqli_fetch_assoc($rs)) {
			$profile = !empty($row['storage_profile_id']) ? $row['storage_profile_id'] : $row['storage_extension'];
			
			if(null != ($storage = DevblocksPlatform::getStorageService($profile)))
				if(false === $storage->delete('resources', $row['storage_key']))
					return false;
		}
		
		mysqli_free_result($rs);
		
		return true;
	}
	
	public function getStats() {
		return $this->_stats('resource');
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
		$sql = sprintf("SELECT resource.id, resource.storage_extension, resource.storage_key, resource.storage_profile_id, resource.storage_size ".
			"FROM resource ".
			"WHERE resource.updated_at < %d ".
			"AND (resource.storage_extension = %s AND resource.storage_profile_id = %d) ".
			"ORDER BY resource.id ASC ".
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
		
		$ns = 'resources';
		
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

class Context_Resource extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextUri {
	const ID = CerberusContexts::CONTEXT_RESOURCE;
	const URI = 'resource';
	
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
		return DAO_Resource::random();
	}
	
	function autocompleteUri($term, $uri_params = null) : array {
		$where_sql = sprintf("%s LIKE %s", DAO_Resource::NAME, Cerb_ORMHelper::qstr('%' . $term . '%'));
		
		if(array_key_exists('types', $uri_params) && is_iterable($uri_params['types'])) {
			$where_sql .= sprintf(" AND %s IN (%s)",
				Cerb_ORMHelper::escape(DAO_Resource::EXTENSION_ID),
				implode(',', Cerb_ORMHelper::qstrArray($uri_params['types']))
			);
		}
		
		$resources = DAO_Resource::getWhere(
			$where_sql,
			null,
			null,
			25
		);
		
		if(!is_iterable($resources))
			return [];
		
		return array_map(
			function ($resource) {
				return [
					'caption' => $resource->name,
					'snippet' => $resource->name,
				];
			},
			$resources
		);
	}	
	
	function profileGetUrl($context_id) {
		$url_writer = DevblocksPlatform::services()->url();
		
		if(empty($context_id))
			return '';
		
		return $url_writer->writeNoProxy('c=profiles&type=resource&id='.$context_id, true);
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Resource();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['description'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.description'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->description,
		);
		
		$properties['extension_id'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.type'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->extension_id,
		);
		
		$properties['is_dynamic'] = array(
			'label' => DevblocksPlatform::translateCapitalized('dao.resource.is_dynamic'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_dynamic,
		);
		
		$properties['storage_size'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.size'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => DevblocksPlatform::strPrettyBytes($model->storage_size),
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
		if(null == ($resource = DAO_Resource::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($resource->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $resource->id,
			'name' => $resource->name,
			'permalink' => $url,
			'updated' => $resource->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'description',
			'extension_id',
			'storage_size',
			'is_dynamic',
			'updated_at',
		);
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_Resource::getByName($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($resource, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Resource:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_RESOURCE);
		
		// Polymorph
		if(is_numeric($resource)) {
			$resource = DAO_Resource::get($resource);
		} elseif($resource instanceof Model_Resource) {
			// It's what we want already.
		} elseif(is_array($resource)) {
			$resource = Cerb_ORMHelper::recastArrayToModel($resource, 'Model_Resource');
		} else {
			$resource = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'automation_kata' => $prefix.$translate->_('common.automation'),
			'description' => $prefix.$translate->_('common.description'),
			'extension_id' => $prefix.$translate->_('common.type'),
			'id' => $prefix.$translate->_('common.id'),
			'is_dynamic' => $prefix.$translate->_('dao.resource.is_dynamic'),
			'name' => $prefix.$translate->_('common.name'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'automation_kata' => Model_CustomField::TYPE_MULTI_LINE,
			'description' => Model_CustomField::TYPE_SINGLE_LINE,
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_dynamic' => Model_CustomField::TYPE_CHECKBOX,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
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
		
		$token_values['_context'] = Context_Resource::ID;
		$token_values['_type'] = Context_Resource::URI;
		
		$token_values['_types'] = $token_types;
		
		if($resource) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $resource->name;
			$token_values['automation_kata'] = $resource->automation_kata;
			$token_values['description'] = $resource->description;
			$token_values['extension_id'] = $resource->extension_id;
			$token_values['id'] = $resource->id;
			$token_values['is_dynamic'] = $resource->is_dynamic;
			$token_values['name'] = $resource->name;
			$token_values['updated_at'] = $resource->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($resource, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=resource&id=%d-%s",$resource->id, DevblocksPlatform::strToPermalink($resource->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'content' => '_content',
			'automation_kata' => DAO_Resource::AUTOMATION_KATA,
			'description' => DAO_Resource::DESCRIPTION,
			'extension_id' => DAO_Resource::EXTENSION_ID,
			'id' => DAO_Resource::ID,
			'is_dynamic' => DAO_Resource::IS_DYNAMIC,
			'links' => '_links',
			'name' => DAO_Resource::NAME,
			'updated_at' => DAO_Resource::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['content']['notes'] = 'The optional content of this resource. For binary, base64-encode in [data URI format](https://en.wikipedia.org/wiki/Data_URI_scheme)';
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'content':
				$out_fields['_content'] = $value;
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		return parent::lazyLoadGetKeys();
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_RESOURCE;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.resources');
		$view->renderSortBy = SearchFields_Resource::UPDATED_AT;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.resources');
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Resource::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_RESOURCE;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(!is_numeric($context_id)) {
				if(false == ($model = DAO_Resource::getByName($context_id))) {
					$model = new Model_Resource();
					$model->id = 0;
					$model->name = $context_id;
					
					if($edit) {
						foreach (CerbQuickSearchLexer::getFieldsFromQuery($edit) as $field) {
							$oper = $value = null;
							
							switch($field->key) {
								case 'description':
									CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
									$model->description = $value;
									break;
									
								case 'type':
									CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
									$model->extension_id = $value;
									break;
							}	
						}
					}
				}
				
			} else {
				$model = DAO_Resource::get($context_id);
			}
			
			if(false == $model)
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$context_id = $model->id;
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			} else {
				$model = new Model_Resource();
			}
			
			$tpl->assign('model', $model);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$resource_extensions = Extension_ResourceType::getAll(false);
			$tpl->assign('resource_extensions', $resource_extensions);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/resource/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
