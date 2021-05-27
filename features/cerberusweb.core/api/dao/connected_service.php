<?php
class DAO_ConnectedService extends Cerb_ORMHelper {
	const EXTENSION_ID = 'extension_id';
	const ID = 'id';
	const NAME = 'name';
	const PARAMS_JSON = 'params_json';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	
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
			->addField(self::EXTENSION_ID)
			->string()
			->setRequired(true)
			->addValidator(function($value, &$error) {
				if(false == (Extension_ConnectedServiceProvider::get($value))) {
					$error = sprintf("(%s) is not a valid service provider (%s) extension ID.",
						$value,
						Extension_ConnectedServiceProvider::POINT
					);
					return false;
				}
				
				return true;
			})
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::UPDATED_AT, DevblocksPlatform::translateCapitalized('common.updated'))
			->timestamp()
			;
		$validation
			->addField(self::URI, DevblocksPlatform::translate('common.uri'))
			->string()
			->setUnique(get_class())
			->setNotEmpty(false)
			->addFormatter(function(&$value, &$error=null) {
				$value = DevblocksPlatform::strLower($value);
				return true;
			})
			->addValidator(function($string, &$error=null) {
				if(0 != strcasecmp($string, DevblocksPlatform::strAlphaNum($string, '-'))) {
					$error = "may only contain lowercase letters, numbers, and dashes";
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
		
		$sql = "INSERT INTO connected_service () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_CONNECTED_SERVICE, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
			
		$context = CerberusContexts::CONTEXT_CONNECTED_SERVICE;
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
			parent::_update($batch_ids, 'connected_service', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.connected_service.update',
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
		parent::_updateWhere('connected_service', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_CONNECTED_SERVICE;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ConnectedService[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, uri, extension_id, updated_at, params_json ".
			"FROM connected_service ".
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
	 * @return Model_ConnectedService[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ConnectedService::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ConnectedService
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
	 * @return Model_ConnectedService[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * 
	 * @param string $extension_id
	 * @return Model_ConnectedService[]
	 */
	static function getByExtension($extension_id) {
		return self::getWhere(
			sprintf("%s = %s",
				self::EXTENSION_ID,
				Cerb_ORMHelper::qstr($extension_id)
			)
		);
	}
	
	/**
	 * 
	 * @param array $extension_ids
	 * @return Model_ConnectedService[]
	 */
	static function getByExtensions(array $extension_ids) {
		if(empty($extension_ids))
			return [];
		
		return self::getWhere(
			sprintf("%s IN (%s)",
				self::EXTENSION_ID,
				implode(',', Cerb_ORMHelper::qstrArray($extension_ids))
			)
		);
	}
	
	/**
	 * 
	 * @param string $uri
	 * @return Model_ConnectedService|NULL
	 */
	static function getByUri($uri) {
		if(empty($uri))
			return null;
		
		$results = self::getWhere(
			sprintf("%s = %s",
				self::URI,
				Cerb_ORMHelper::qstr($uri)
			)
		);
		
		if(empty($results))
			return null;
		
		return array_shift($results);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_ConnectedService[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ConnectedService();
			$object->id = intval($row['id']);
			$object->extension_id = $row['extension_id'];
			$object->name = $row['name'];
			$object->uri = $row['uri'];
			$object->updated_at = intval($row['updated_at']);
			$object->params_json_encrypted = $row['params_json'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function setAndEncryptParams($id, array $params) {
		$encrypt = DevblocksPlatform::services()->encryption();
		$ciphertext = $encrypt->encrypt(json_encode($params));
		
		return DAO_ConnectedService::update($id, [
			DAO_ConnectedService::PARAMS_JSON => $ciphertext,
		]);
	}
	
	static function random() {
		return self::_getRandom('connected_service');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		DAO_ConnectedAccount::deleteByServiceIds($ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM connected_service WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CONNECTED_SERVICE,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ConnectedService::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ConnectedService', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"connected_service.id as %s, ".
			"connected_service.name as %s, ".
			"connected_service.uri as %s, ".
			"connected_service.extension_id as %s, ".
			"connected_service.updated_at as %s ",
				SearchFields_ConnectedService::ID,
				SearchFields_ConnectedService::NAME,
				SearchFields_ConnectedService::URI,
				SearchFields_ConnectedService::EXTENSION_ID,
				SearchFields_ConnectedService::UPDATED_AT
			);
			
		$join_sql = "FROM connected_service ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ConnectedService');
	
		return array(
			'primary_table' => 'connected_service',
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
			SearchFields_ConnectedService::ID,
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

class SearchFields_ConnectedService extends DevblocksSearchFields {
	const EXTENSION_ID = 'c_extension_id';
	const ID = 'c_id';
	const NAME = 'c_name';
	const URI = 'c_uri';
	const UPDATED_AT = 'c_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'connected_service.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('connected_service.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CONNECTED_SERVICE, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_CONNECTED_SERVICE), '%s'), self::getPrimaryKey());
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
			case SearchFields_ConnectedService::EXTENSION_ID:
				return self::_getLabelsForKeyExtensionValues(Extension_ConnectedServiceProvider::POINT);
				break;
				
			case SearchFields_ConnectedService::ID:
				$models = DAO_ConnectedService::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'connected_service', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'connected_service', 'name', $translate->_('common.name'), null, true),
			self::URI => new DevblocksSearchField(self::URI, 'connected_service', 'uri', $translate->_('common.uri'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'connected_service', 'extension_id', $translate->_('common.type'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'connected_service', 'updated_at', $translate->_('common.updated'), null, true),

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

class Model_ConnectedService {
	public $id;
	public $name;
	public $uri;
	public $extension_id;
	public $updated_at;
	public $params_json_encrypted;
	
	/**
	 * @return Extension_ConnectedServiceProvider
	 */
	public function getExtension() {
		return Extension_ConnectedServiceProvider::get($this->extension_id);
	}
	
	public function decryptParams($actor=null) {
		if($actor && !Context_ConnectedService::isReadableByActor($this, $actor))
			return false;
		
		$encrypt = DevblocksPlatform::services()->encryption();
		
		if(false == ($json = $encrypt->decrypt($this->params_json_encrypted)))
			return false;
		
		if(!$json || false == ($params = json_decode($json, true)))
			return false;
		
		return $params;
	}
};

class View_ConnectedService extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'connected_services';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.connected_services');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ConnectedService::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ConnectedService::NAME,
			SearchFields_ConnectedService::URI,
			SearchFields_ConnectedService::EXTENSION_ID,
			SearchFields_ConnectedService::UPDATED_AT,
		);
		$this->addColumnsHidden(array(
			SearchFields_ConnectedService::VIRTUAL_CONTEXT_LINK,
			SearchFields_ConnectedService::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_ConnectedService::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ConnectedService');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_ConnectedService', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ConnectedService', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_ConnectedService::EXTENSION_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_ConnectedService::VIRTUAL_CONTEXT_LINK:
				case SearchFields_ConnectedService::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_CONNECTED_SERVICE;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_ConnectedService::EXTENSION_ID:
				$label_map = function($ids) use ($column) {
					return SearchFields_ConnectedService::getLabelsForKeyValues($column, $ids);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;

			case SearchFields_ConnectedService::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_ConnectedService::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_ConnectedService::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ConnectedService::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ConnectedService::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_CONNECTED_SERVICE],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ConnectedService::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CONNECTED_SERVICE, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ConnectedService::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'type' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ConnectedService::EXTENSION_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ConnectedService::UPDATED_AT),
				),
			'uri' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ConnectedService::URI, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_ConnectedService::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CONNECTED_SERVICE, $fields, null);
		
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONNECTED_SERVICE);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Providers
		$providers = Extension_ConnectedServiceProvider::getAll(false);
		$tpl->assign('providers', $providers);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/connected_service/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			case SearchFields_ConnectedService::EXTENSION_ID:
				$label_map = function($ids) use ($field) {
					return SearchFields_ConnectedService::getLabelsForKeyValues($field, $ids);
				};
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
			case SearchFields_ConnectedService::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_ConnectedService::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ConnectedService::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ConnectedService::EXTENSION_ID:
			case SearchFields_ConnectedService::NAME:
			case SearchFields_ConnectedService::URI:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ConnectedService::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ConnectedService::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_ConnectedService::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_ConnectedService::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
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

class Context_ConnectedService extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_CONNECTED_SERVICE;
	const URI = 'connected_service';
	
	static function isReadableByActor($models, $actor) {
		// Only admins can read
		return self::isWriteableByActor($models, $actor);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admins can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		// Admins can do whatever they want
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_ConnectedService::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=connected_service&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_ConnectedService();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['extension_id'] = array(
			'label' => mb_ucfirst($translate->_('common.type')),
			'type' => 'extension',
			'value' => $model->extension_id,
			'params' => [
				'context' => SearchFields_ConnectedService::EXTENSION_ID,
			],
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['uri'] = array(
			'label' => $translate->_('common.uri'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->uri,
			'params' => [],
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$connected_service = DAO_ConnectedService::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($connected_service->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $connected_service->id,
			'name' => $connected_service->name,
			'permalink' => $url,
			'updated' => $connected_service->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'uri',
			'extension_id',
			'updated_at',
		);
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_ConnectedService::getByUri($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($connected_service, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Connected Service:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONNECTED_SERVICE);

		// Polymorph
		if(is_numeric($connected_service)) {
			$connected_service = DAO_ConnectedService::get($connected_service);
		} elseif($connected_service instanceof Model_ConnectedService) {
			// It's what we want already.
		} elseif(is_array($connected_service)) {
			$connected_service = Cerb_ORMHelper::recastArrayToModel($connected_service, 'Model_ConnectedService');
		} else {
			$connected_service = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'extension_id' => $prefix.$translate->_('common.type') . ' ' . $translate->_('common.ID'),
			'extension_name' => $prefix.$translate->_('common.type'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'uri' => $prefix.$translate->_('common.uri'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'extension_name' => Model_CustomField::TYPE_SINGLE_LINE,
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
		$token_values = [];
		
		$token_values['_context'] = Context_ConnectedService::ID;
		$token_values['_type'] = Context_ConnectedService::URI;
		
		$token_values['_types'] = $token_types;
		
		if($connected_service) {
			$connected_service_ext = $connected_service->getExtension();
			
			$token_values['_loaded'] = true;
			$token_values['_label'] = $connected_service->name;
			$token_values['id'] = $connected_service->id;
			$token_values['name'] = $connected_service->name;
			$token_values['extension_id'] = $connected_service->extension_id;
			$token_values['extension_name'] = $connected_service_ext ? $connected_service_ext->manifest->name : null;
			$token_values['updated_at'] = $connected_service->updated_at;
			$token_values['uri'] = $connected_service->uri;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($connected_service, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=connected_service&id=%d-%s",$connected_service->id, DevblocksPlatform::strToPermalink($connected_service->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_ConnectedService::ID,
			'links' => '_links',
			'name' => DAO_ConnectedService::NAME,
			'extension_id' => DAO_ConnectedService::EXTENSION_ID,
			'updated_at' => DAO_ConnectedService::UPDATED_AT,
			'uri' => DAO_ConnectedService::URI,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['params'] = [
			'key' => 'params',
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
				$encrypt = DevblocksPlatform::services()->encryption();
				
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_ConnectedService::PARAMS_JSON] = $encrypt->encrypt($json);
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
		
		$context = CerberusContexts::CONTEXT_CONNECTED_SERVICE;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
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
		$view->name = 'Connected Service';
		/*
		$view->addParams(array(
			SearchFields_ConnectedService::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_ConnectedService::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_ConnectedService::UPDATED_AT;
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
		$view->name = 'Connected Service';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_ConnectedService::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_CONNECTED_SERVICE;
		
		if(!empty($context_id)) {
			if(false == ($model = DAO_ConnectedService::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
		} else {
			$model = new Model_ConnectedService();
			$model->id = 0;
		}
		
		if(empty($context_id) || $edit) {
			if($model && $model->id) {
				if(!Context_ConnectedService::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$service_exts = Extension_ConnectedServiceProvider::getAll(false);
			$tpl->assign('service_exts', $service_exts);

			$params = $model->decryptParams($active_worker);
			$tpl->assign('params', $params);
			
			// Library
			if(!$context_id) {
				$packages = DAO_PackageLibrary::getByPoint('connected_service');
				$tpl->assign('packages', $packages);
			}
			
			if(isset($model))
				$tpl->assign('model', $model);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/connected_service/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
