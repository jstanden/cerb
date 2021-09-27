<?php
class DAO_OAuthApp extends Cerb_ORMHelper {
	const CALLBACK_URL = 'callback_url';
	const CLIENT_ID = 'client_id';
	const CLIENT_SECRET = 'client_secret';
	const ID = 'id';
	const NAME = 'name';
	const SCOPES = 'scopes';
	const UPDATED_AT = 'updated_at';
	const URL = 'url';
	
	const _CACHE_ALL = 'oauth_apps_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CALLBACK_URL, DevblocksPlatform::translateCapitalized('dao.oauth_app.callback_url'))
			->url()
			->setRequired(true)
			;
		$validation
			->addField(self::CLIENT_ID, DevblocksPlatform::translateCapitalized('dao.oauth_app.client_id'))
			->string()
			->setRequired(true)
			->setUnique(get_class())
			;
		$validation
			->addField(self::CLIENT_SECRET, DevblocksPlatform::translateCapitalized('dao.oauth_app.client_secret'))
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::ID, DevblocksPlatform::translateCapitalized('common.id'))
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::NAME, DevblocksPlatform::translateCapitalized('common.name'))
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::SCOPES, DevblocksPlatform::translateCapitalized('api.scopes'))
			->string()
			->setMaxLength(16777216)
			->addValidator($validation->validators()->yaml())
			;
		$validation
			->addField(self::UPDATED_AT, DevblocksPlatform::translateCapitalized('common.updated'))
			->timestamp()
			;
		$validation
			->addField(self::URL, DevblocksPlatform::translateCapitalized('common.url'))
			->url()
			;
		$validation
			->addField('_links', DevblocksPlatform::translateCapitalized('common.links'))
			->string()
			->setMaxLength(65535)
			;
		
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO oauth_app () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_OAuthApp::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
			
		$context = "cerberusweb.contexts.oauth.app";
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
			parent::_update($batch_ids, 'oauth_app', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.oauth_app.update',
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
		parent::_updateWhere('oauth_app', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = Context_OAuthApp::ID;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;

		// If new
		if(!$id) {
			// Generate a client id and secret
			if(!array_key_exists(DAO_OAuthApp::CLIENT_ID, $fields)) {
				$client_id = DevblocksPlatform::strLower(CerberusApplication::generatePassword(32));
				$client_secret = DevblocksPlatform::strLower(CerberusApplication::generatePassword(64));
				
				$fields[DAO_OAuthApp::CLIENT_ID] = $client_id;
				$fields[DAO_OAuthApp::CLIENT_SECRET] = $client_secret;
			}
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_OAuthApp[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, url, client_id, client_secret, callback_url, scopes, updated_at ".
			"FROM oauth_app ".
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
	 * @return Model_OAuthApp[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_OAuthApp::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return false;
			
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_OAuthApp	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_OAuthApp[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * 
	 * @param string $client_id
	 * @return Model_OAuthApp|NULL
	 */
	static function getByClientId($client_id) {
		$apps = self::getAll();
		
		// [TODO] Binary search, hash
		foreach($apps as $app) {
			if($app->client_id == $client_id)
				return $app;
		}
		
		return null;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_OAuthApp[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_OAuthApp();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->url = $row['url'];
			$object->client_id = $row['client_id'];
			$object->client_secret = $row['client_secret'];
			$object->callback_url = $row['callback_url'];
			$object->scopes_yaml = $row['scopes'];
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('oauth_app');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		DAO_OAuthToken::deleteByAppIds($ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM oauth_app WHERE id IN (%s)", $ids_list));

		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => Context_OAuthApp::ID,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_OAuthApp::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_OAuthApp', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"oauth_app.id as %s, ".
			"oauth_app.name as %s, ".
			"oauth_app.url as %s, ".
			"oauth_app.client_id as %s, ".
			"oauth_app.callback_url as %s, ".
			"oauth_app.scopes as %s, ".
			"oauth_app.updated_at as %s ",
				SearchFields_OAuthApp::ID,
				SearchFields_OAuthApp::NAME,
				SearchFields_OAuthApp::URL,
				SearchFields_OAuthApp::CLIENT_ID,
				SearchFields_OAuthApp::CALLBACK_URL,
				SearchFields_OAuthApp::SCOPES,
				SearchFields_OAuthApp::UPDATED_AT
			);
			
		$join_sql = "FROM oauth_app ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_OAuthApp');
	
		return array(
			'primary_table' => 'oauth_app',
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
			SearchFields_OAuthApp::ID,
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

class SearchFields_OAuthApp extends DevblocksSearchFields {
	const ID = 'o_id';
	const NAME = 'o_name';
	const URL = 'o_url';
	const CLIENT_ID = 'o_client_id';
	const CALLBACK_URL = 'o_callback_url';
	const SCOPES = 'o_scopes';
	const UPDATED_AT = 'o_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'oauth_app.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			Context_OAuthApp::ID => new DevblocksSearchFieldContextKeys('oauth_app.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, Context_OAuthApp::ID, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(Context_OAuthApp::ID), '%s'), self::getPrimaryKey());
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
			case SearchFields_OAuthApp::ID:
				$models = DAO_OAuthApp::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'oauth_app', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'oauth_app', 'name', $translate->_('common.name'), null, true),
			self::URL => new DevblocksSearchField(self::URL, 'oauth_app', 'url', $translate->_('common.url'), null, true),
			self::CLIENT_ID => new DevblocksSearchField(self::CLIENT_ID, 'oauth_app', 'client_id', $translate->_('dao.oauth_app.client_id'), null, true),
			self::CALLBACK_URL => new DevblocksSearchField(self::CALLBACK_URL, 'oauth_app', 'callback_url', $translate->_('dao.oauth_app.callback_url'), null, true),
			self::SCOPES => new DevblocksSearchField(self::SCOPES, 'oauth_app', 'scopes', $translate->_('api.scopes'), null, false),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'oauth_app', 'updated_at', $translate->_('common.updated'), null, true),

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

class Model_OAuthApp {
	public $id;
	public $name;
	public $url;
	public $client_id;
	public $client_secret;
	public $callback_url;
	public $scopes_yaml;
	public $updated_at;
	
	private $_scopes = null;
	
	function getAvailableScopes() {
		if(!is_null($this->_scopes))
			return $this->_scopes;
		
		if(false == ($scopes = @yaml_parse($this->scopes_yaml)))
			return false;
		
		$this->_scopes = $scopes;
		return $this->_scopes;
	}
	
	function getScopes(array $ids) {
		$scopes = $this->getAvailableScopes();
		return array_intersect_key($scopes, array_flip($ids));
	}
	
	function getScope($id) {
		$scopes = $this->getAvailableScopes();
		
		if(!array_key_exists($id, $scopes))
			return false;
		
		return $scopes[$id];
	}
};

class View_OAuthApp extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'oauth_apps';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translate('OAuth Apps');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_OAuthApp::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_OAuthApp::NAME,
			SearchFields_OAuthApp::URL,
			SearchFields_OAuthApp::CLIENT_ID,
			SearchFields_OAuthApp::UPDATED_AT,
		);
		$this->addColumnsHidden(array(
			SearchFields_OAuthApp::VIRTUAL_CONTEXT_LINK,
			SearchFields_OAuthApp::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_OAuthApp::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_OAuthApp');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_OAuthApp', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_OAuthApp', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Virtuals
				case SearchFields_OAuthApp::VIRTUAL_CONTEXT_LINK:
				case SearchFields_OAuthApp::VIRTUAL_HAS_FIELDSET:
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
		$context = Context_OAuthApp::ID;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_OAuthApp::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_OAuthApp::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_OAuthApp::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_OAuthApp::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'callbackUrl' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_OAuthApp::CALLBACK_URL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'clientId' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_OAuthApp::CLIENT_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_OAuthApp::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_OAuthApp::ID],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_OAuthApp::ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_OAuthApp::ID, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_OAuthApp::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_OAuthApp::UPDATED_AT),
				),
			'url' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_OAuthApp::URL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_OAuthApp::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(Context_OAuthApp::ID, $fields, null);
		
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
		$custom_fields = DAO_CustomField::getByContext(Context_OAuthApp::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/oauth_app/view.tpl');
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
			case SearchFields_OAuthApp::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_OAuthApp::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_OAuthApp::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_OAuthApp::NAME:
			case SearchFields_OAuthApp::URL:
			case SearchFields_OAuthApp::CLIENT_ID:
			case SearchFields_OAuthApp::CALLBACK_URL:
			case SearchFields_OAuthApp::SCOPES:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_OAuthApp::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_OAuthApp::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_OAuthApp::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_OAuthApp::VIRTUAL_HAS_FIELDSET:
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

class Context_OAuthApp extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.oauth.app';
	const URI = 'oauth_app';
	
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
		return DAO_OAuthApp::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=oauth_app&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_OAuthApp();
		
		$properties['callback_url'] = array(
			'label' => mb_ucfirst($translate->_('dao.oauth_app.callback_url')),
			'type' => Model_CustomField::TYPE_URL,
			'value' => $model->callback_url,
			'params' => [],
		);
		
		$properties['client_id'] = array(
			'label' => mb_ucfirst($translate->_('dao.oauth_app.client_id')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->client_id,
			'params' => [],
		);
		
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
		
		$properties['url'] = array(
			'label' => mb_ucfirst($translate->_('common.url')),
			'type' => Model_CustomField::TYPE_URL,
			'value' => $model->url,
			'params' => [],
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($oauth_app = DAO_OAuthApp::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($oauth_app->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $oauth_app->id,
			'name' => $oauth_app->name,
			'permalink' => $url,
			'updated' => $oauth_app->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'client_id',
			'url',
			'updated_at',
		);
	}
	
	function getContext($oauth_app, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'OAuth App:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_OAuthApp::ID);

		// Polymorph
		if(is_numeric($oauth_app)) {
			$oauth_app = DAO_OAuthApp::get($oauth_app);
		} elseif($oauth_app instanceof Model_OAuthApp) {
			// It's what we want already.
		} elseif(is_array($oauth_app)) {
			$oauth_app = Cerb_ORMHelper::recastArrayToModel($oauth_app, 'Model_OAuthApp');
		} else {
			$oauth_app = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'callback_url' => $prefix.$translate->_('dao.oauth_app.callback_url'),
			'client_id' => $prefix.$translate->_('dao.oauth_app.client_id'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'scopes' => $prefix.$translate->_('api.scopes'),
			'url' => $prefix.$translate->_('common.url'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'callback_url' => Model_CustomField::TYPE_URL,
			'client_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'url' => Model_CustomField::TYPE_URL,
			'scopes' => Model_CustomField::TYPE_MULTI_LINE,
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
		
		$token_values['_context'] = Context_OAuthApp::ID;
		$token_values['_type'] = Context_OAuthApp::URI;
		
		$token_values['_types'] = $token_types;
		
		if($oauth_app) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $oauth_app->name;
			$token_values['callback_url'] = $oauth_app->callback_url;
			$token_values['client_id'] = $oauth_app->client_id;
			$token_values['id'] = $oauth_app->id;
			$token_values['name'] = $oauth_app->name;
			$token_values['updated_at'] = $oauth_app->updated_at;
			$token_values['scopes'] = $oauth_app->scopes_yaml;
			$token_values['url'] = $oauth_app->url;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($oauth_app, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=oauth_app&id=%d-%s",$oauth_app->id, DevblocksPlatform::strToPermalink($oauth_app->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'callback_url' => DAO_OAuthApp::CALLBACK_URL,
			'client_id' => DAO_OAuthApp::CLIENT_ID,
			'client_secret' => DAO_OAuthApp::CLIENT_SECRET,
			'id' => DAO_OAuthApp::ID,
			'links' => '_links',
			'name' => DAO_OAuthApp::NAME,
			'updated_at' => DAO_OAuthApp::UPDATED_AT,
			'scopes' => DAO_OAuthApp::SCOPES,
			'url' => DAO_OAuthApp::URL,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['callback_url']['notes'] = "The OAuth2 callback URL of the app";
		$keys['client_id']['notes'] = "The client identifier of the app";
		$keys['client_secret']['notes'] = "The client secret of the app";
		$keys['scopes']['notes'] = "The app's available scopes in YAML format";
		$keys['url']['notes'] = "The app's URL";
		
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
		
		$context = Context_OAuthApp::ID;
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
		$view->name = 'OAuth App';
		/*
		$view->addParams(array(
			SearchFields_OAuthApp::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_OAuthApp::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_OAuthApp::UPDATED_AT;
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
		$view->name = 'OAuth App';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_OAuthApp::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = Context_OAuthApp::ID;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_OAuthApp::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_OAuthApp::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
			} else {
				$model = new Model_OAuthApp();
				
				$model->client_id = DevblocksPlatform::strLower(CerberusApplication::generatePassword(32));
				$model->client_secret = DevblocksPlatform::strLower(CerberusApplication::generatePassword(64));
				
				$model->scopes_yaml = <<< EOD
"profile":
 label: Access your profile information
 endpoints:
  - workers/me: GET

"search":
 label: Search records on your behalf
 endpoints:
  - records/*/search: [GET]

"api:read-only":
 label: Make any read-only API request on your behalf
 endpoints:
  - "*": [GET]

"api":
 label: Make any API request on your behalf
 endpoints:
  - "*" #[GET, PATCH, POST, PUT, DELETE]
EOD;
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$tpl->assign('model', $model);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/oauth_app/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
