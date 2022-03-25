<?php
class DAO_WebApiCredentials extends Cerb_ORMHelper {
	const ACCESS_KEY = 'access_key';
	const ID = 'id';
	const NAME = 'name';
	const PARAMS_JSON = 'params_json';
	const SECRET_KEY = 'secret_key';
	const UPDATED_AT = 'updated_at';
	const WORKER_ID = 'worker_id';
	
	const _CACHE_ALL = 'dao_webapi_credentials_all';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::ACCESS_KEY)
			->string()
			->setMaxLength(255)
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// text
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(65535)
			;
		// varchar(255)
		$validation
			->addField(self::SECRET_KEY) // [TODO] Encrypt
			->string()
			->setMaxLength(255)
			->setEditable(false)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKER_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER))
			->setRequired(true)
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
		
		if(!isset($fields[self::ACCESS_KEY]))
			$fields[self::ACCESS_KEY] = DevblocksPlatform::strLower(CerberusApplication::generatePassword(12));
		
		if(!isset($fields[self::SECRET_KEY]))
			$fields[self::SECRET_KEY] = DevblocksPlatform::strLower(CerberusApplication::generatePassword(32));
		
		$sql = "INSERT INTO webapi_credentials () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'webapi_credentials', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.webapi_credentials.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('webapi_credentials', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		$context = CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WebApiCredentials[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, worker_id, access_key, secret_key, params_json, updated_at ".
			"FROM webapi_credentials ".
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
	 * @return Model_WebApiCredentials
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param boolean $nocache
	 * @return <Model_WebApiCredentials[], NULL, array>
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();

		if($nocache || null === ($credentials = $cache->load(self::_CACHE_ALL))) {
			$credentials = self::getWhere(
				null,
				null,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($credentials))
				return false;
			
			$cache->save($credentials, self::_CACHE_ALL);
		}
		
		return $credentials;
	}
	
	static function getByAccessKey($access_key) {
		$credentials = self::getAll();
		
		foreach($credentials as $credential) { /* @var $credential Model_WebApiCredentials */
			if($credential->access_key == $access_key)
				return $credential;
		}
		
		return false;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_WebApiCredentials[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}	
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_WebApiCredentials[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_WebApiCredentials();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->worker_id = $row['worker_id'];
			$object->access_key = $row['access_key'];
			$object->secret_key = $row['secret_key'];
			$object->updated_at = intval($row['updated_at']);
			
			@$params = json_decode($row['params_json'], true);
			$object->params = !empty($params) ? $params : [];
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('webapi_credentials');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM webapi_credentials WHERE id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WebApiCredentials::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_WebApiCredentials', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"webapi_credentials.id as %s, ".
			"webapi_credentials.name as %s, ".
			"webapi_credentials.updated_at as %s, ".
			"webapi_credentials.worker_id as %s, ".
			"webapi_credentials.access_key as %s ",
				SearchFields_WebApiCredentials::ID,
				SearchFields_WebApiCredentials::NAME,
				SearchFields_WebApiCredentials::UPDATED_AT,
				SearchFields_WebApiCredentials::WORKER_ID,
				SearchFields_WebApiCredentials::ACCESS_KEY
			);
			
		$join_sql = "FROM webapi_credentials ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_WebApiCredentials');
	
		return array(
			'primary_table' => 'webapi_credentials',
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
			SearchFields_WebApiCredentials::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
};

class SearchFields_WebApiCredentials extends DevblocksSearchFields {
	const ACCESS_KEY = 'w_access_key';
	const ID = 'w_id';
	const NAME = 'w_name';
	const PARAMS_JSON = 'w_params_json';
	const SECRET_KEY = 'w_secret_key';
	const UPDATED_AT = 'w_updated_at';
	const WORKER_ID = 'w_worker_id';
	
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WORKER_SEARCH = '*_worker_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'webapi_credentials.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL => new DevblocksSearchFieldContextKeys('webapi_credentials.id', self::ID),
			CerberusContexts::CONTEXT_WORKER => new DevblocksSearchFieldContextKeys('webapi_credentials.worker_id', self::WORKER_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL), '%s'), self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_WORKER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKER, 'webapi_credentials.worker_id');
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
			case SearchFields_WebApiCredentials::ID:
				$models = DAO_WebApiCredentials::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'webapi_credentials', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'webapi_credentials', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'webapi_credentials', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'webapi_credentials', 'worker_id', $translate->_('common.worker'), Model_CustomField::TYPE_WORKER, true),
			self::ACCESS_KEY => new DevblocksSearchField(self::ACCESS_KEY, 'webapi_credentials', 'access_key', $translate->_('dao.webapi_credentials.access_key'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::SECRET_KEY => new DevblocksSearchField(self::SECRET_KEY, 'webapi_credentials', 'secret_key', $translate->_('dao.webapi_credentials.secret_key'), null, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'webapi_credentials', 'params_json', $translate->_('dao.webapi_credentials.params_json'), null, false),
			
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WORKER_SEARCH => new DevblocksSearchField(self::VIRTUAL_WORKER_SEARCH, '*', 'worker_search', null, null, false),
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

class Model_WebApiCredentials {
	public $id;
	public $name;
	public $worker_id;
	public $access_key;
	public $secret_key;
	public $params = [];
	public $updated_at = 0;
	
	function getWorker() {
		return DAO_Worker::get($this->worker_id);
	}
};

class View_WebApiCredentials extends C4_AbstractView implements IAbstractView_QuickSearch {
	const DEFAULT_ID = 'webapi_credentials';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Web API Credentials');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_WebApiCredentials::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_WebApiCredentials::NAME,
			SearchFields_WebApiCredentials::ACCESS_KEY,
			SearchFields_WebApiCredentials::WORKER_ID,
			SearchFields_WebApiCredentials::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_WebApiCredentials::ID,
			SearchFields_WebApiCredentials::PARAMS_JSON,
			SearchFields_WebApiCredentials::SECRET_KEY,
			SearchFields_WebApiCredentials::VIRTUAL_HAS_FIELDSET,
			SearchFields_WebApiCredentials::VIRTUAL_WORKER_SEARCH,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_WebApiCredentials::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_WebApiCredentials');
		
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_WebApiCredentials', $size);
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_WebApiCredentials::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WebApiCredentials::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'accessKey' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WebApiCredentials::ACCESS_KEY, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_WebApiCredentials::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WebApiCredentials::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_WebApiCredentials::UPDATED_AT),
				),
			'worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_WebApiCredentials::VIRTUAL_WORKER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'worker.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_WebApiCredentials::WORKER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
		);
		
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
			
			case 'worker':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_WebApiCredentials::VIRTUAL_WORKER_SEARCH);
				break;
			
			default:
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

		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.restapi::view.tpl');
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
			case SearchFields_WebApiCredentials::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_WebApiCredentials::VIRTUAL_WORKER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.worker')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
		}
	}

	function getFields() {
		return SearchFields_WebApiCredentials::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WebApiCredentials::ACCESS_KEY:
			case SearchFields_WebApiCredentials::NAME:
			case SearchFields_WebApiCredentials::PARAMS:
			case SearchFields_WebApiCredentials::SECRET_KEY:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_WebApiCredentials::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_WebApiCredentials::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_WebApiCredentials::WORKER_ID:
				$criteria = $this->_doSetCriteriaWorker($field, $oper);
				break;
				
			case SearchFields_WebApiCredentials::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_WebApiCredentials extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL;
	const URI = 'webapi_credentials';
	
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, self::ID, $models, 'worker_');
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
		return DAO_WebApiCredentials::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=webapi_credentials&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_WebApiCredentials();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['access_key'] = array(
			'label' => mb_ucfirst($translate->_('dao.webapi_credentials.access_key')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->access_key,
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
		if(null == ($webapi_credentials = DAO_WebApiCredentials::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($webapi_credentials->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $webapi_credentials->id,
			'name' => $webapi_credentials->name,
			'permalink' => $url,
			'updated' => $webapi_credentials->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'worker__label',
			'access_key',
			'updated_at',
		);
	}
	
	function getContext($webapi_credentials, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'WebApi Credentials:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL);

		// Polymorph
		if(is_numeric($webapi_credentials)) {
			$webapi_credentials = DAO_WebApiCredentials::get($webapi_credentials);
		} elseif($webapi_credentials instanceof Model_WebApiCredentials) {
			// It's what we want already.
		} elseif(is_array($webapi_credentials)) {
			$webapi_credentials = Cerb_ORMHelper::recastArrayToModel($webapi_credentials, 'Model_WebApiCredentials');
		} else {
			$webapi_credentials = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'access_key' => $prefix.$translate->_('dao.webapi_credentials.access_key'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'params' => $prefix.$translate->_('common.params'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'access_key' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'params' => null,
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
		
		$token_values['_context'] = Context_WebApiCredentials::ID;
		$token_values['_type'] = Context_WebApiCredentials::URI;
		
		$token_values['_types'] = $token_types;
		
		if($webapi_credentials) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $webapi_credentials->name;
			$token_values['access_key'] = $webapi_credentials->access_key;
			$token_values['id'] = $webapi_credentials->id;
			$token_values['name'] = $webapi_credentials->name;
			$token_values['params'] = $webapi_credentials->params;
			$token_values['updated_at'] = $webapi_credentials->updated_at;
			$token_values['worker_id'] = $webapi_credentials->worker_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($webapi_credentials, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=webapi_credentials&id=%d-%s",$webapi_credentials->id, DevblocksPlatform::strToPermalink($webapi_credentials->name)), true);
		}
		
		// Owner
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, '', true);

			CerberusContexts::merge(
				'worker_',
				$prefix.'Worker:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_WebApiCredentials::ID,
			'links' => '_links',
			'name' => DAO_WebApiCredentials::NAME,
			'updated_at' => DAO_WebApiCredentials::UPDATED_AT,
			'worker_id' => DAO_WebApiCredentials::WORKER_ID,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['worker_id']['notes'] = "The ID of the [worker](/docs/records/types/worker/) who owns these API credentials";
		
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
		
		$context = CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL;
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
		$view->name = 'WebApi Credentials';
		/*
		$view->addParams(array(
			SearchFields_WebApiCredentials::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_WebApiCredentials::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_WebApiCredentials::UPDATED_AT;
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
		$view->name = 'WebApi Credentials';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
//			$params_req = array(
//				new DevblocksSearchCriteria(SearchFields_WebApiCredentials::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
//			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_WebApiCredentials::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_WebApiCredentials::isWriteableByActor($model, $active_worker))
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
			$tpl->display('devblocks:cerberusweb.restapi::peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
