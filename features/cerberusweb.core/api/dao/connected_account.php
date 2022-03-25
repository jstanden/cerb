<?php
class DAO_ConnectedAccount extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const PARAMS_JSON = 'params_json';
	const SERVICE_ID = 'service_id';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	
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
			;
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::SERVICE_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CONNECTED_SERVICE, true))
			;
		$validation
			->addField(self::UPDATED_AT)
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
				if(0 != strcasecmp($string, DevblocksPlatform::strAlphaNum($string, '.-_'))) {
					$error = "may only contain lowercase letters, numbers, dots, and dashes";
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
		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();
		
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO connected_account () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'connected_account', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.connected_account.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('connected_account', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		@$owner_context = $fields[self::OWNER_CONTEXT];
		@$owner_context_id = intval($fields[self::OWNER_CONTEXT_ID]);
		
		// Verify that the actor can use this new owner
		if($owner_context) {
			if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $actor)) {
				$error = DevblocksPlatform::translate('error.core.no_acl.owner');
				return false;
			}
		}
		
		return true;
	}
	
	/*
	 * 
	 */
	static function getByServiceExtension($extension_id) {
		if(is_null($extension_id))
			return self::getAll();
		
		if(false == ($services = DAO_ConnectedService::getByExtension($extension_id)))
			return [];
		
		$accounts = DAO_ConnectedAccount::getByServiceIds(array_keys($services));
		
		return $accounts;
	}
	
	/*
	 * 
	 */
	static function getByServiceIds($service_ids) {
		$service_ids = DevblocksPlatform::sanitizeArray($service_ids, 'int', ['nonzero','unique']);
		
		if(empty($service_ids))
			return [];
		
		return self::getWhere(
			sprintf("%s IN (%s)",
				DAO_ConnectedAccount::SERVICE_ID,
				implode(',', $service_ids)
			)
		);
	}
	
	/*
	 *
	 */
	static function getReadableByActor($actor, $extension_id=null) {
		$accounts = DAO_ConnectedAccount::getByServiceExtension($extension_id);
		return CerberusContexts::filterModelsByActorReadable('Context_ConnectedAccount', $accounts, $actor);
	}
	
	/*
	 *
	 */
	static function getWriteableByActor($actor, $extension_id=null) {
		$accounts = DAO_ConnectedAccount::getByServiceExtension($extension_id);
		return CerberusContexts::filterModelsByActorWriteable('Context_ConnectedAccount', $accounts, $actor);
	}
	
	/*
	 *
	 */
	static function getUsableByActor($actor, $extension_id=null) {
		$accounts = DAO_ConnectedAccount::getByServiceExtension($extension_id);
		return array_intersect_key($accounts, array_flip(array_keys(Context_ConnectedAccount::isUsableByActor($accounts, $actor), true)));
	}
	
	static function autocomplete($term, $as='models') {
		$params = array(
			SearchFields_ConnectedAccount::NAME => new DevblocksSearchCriteria(SearchFields_ConnectedAccount::NAME, DevblocksSearchCriteria::OPER_LIKE, $term.'*'),
		);
		
		list($results,) = DAO_ConnectedAccount::search(
			[],
			$params,
			25,
			0,
			SearchFields_ConnectedAccount::NAME,
			true,
			false
		);
		
		switch($as) {
			case 'ids':
				return array_keys($results);
				
			default:
				return DAO_ConnectedAccount::getIds(array_keys($results));
		}
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ConnectedAccount[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, uri, service_id, owner_context, owner_context_id, params_json, created_at, updated_at ".
			"FROM connected_account ".
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
	 * @return Model_ConnectedAccount[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ConnectedAccount::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			//if(!is_array($objects))
			//	return false;
				
			//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ConnectedAccount
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
	 * @return Model_ConnectedAccount[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 *
	 * @param string $uri
	 * @return Model_ConnectedAccount|NULL
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
	 * @return Model_ConnectedAccount[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ConnectedAccount();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->uri = $row['uri'];
			$object->service_id = intval($row['service_id']);
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = intval($row['owner_context_id']);
			$object->params_json_encrypted = $row['params_json'];
			$object->created_at = intval($row['created_at']);
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function setAndEncryptParams($id, array $params) {
		$encrypt = DevblocksPlatform::services()->encryption();
		$ciphertext = $encrypt->encrypt(json_encode($params));
		
		return DAO_ConnectedAccount::update($id, [
			DAO_ConnectedAccount::PARAMS_JSON => $ciphertext,
		]);
	}
	
	static function random() {
		return self::_getRandom('connected_account');
	}
	
	static function deleteByServiceIds($ids) {
		if(!is_array($ids))
			$ids = [$ids];
		
		$db = DevblocksPlatform::services()->database();
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int', ['unique','nonzero']);
		
		if(empty($ids))
			return;
		
		$results = self::getWhere(
			sprintf("%s IN (%s)",
				self::SERVICE_ID,
				implode(',', $db->qstrArray($ids))
			)
		);
		
		return self::delete(array_keys($results));
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM connected_account WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CONNECTED_ACCOUNT,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ConnectedAccount::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ConnectedAccount', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"connected_account.id as %s, ".
			"connected_account.name as %s, ".
			"connected_account.uri as %s, ".
			"connected_account.service_id as %s, ".
			"connected_account.owner_context as %s, ".
			"connected_account.owner_context_id as %s, ".
			"connected_account.created_at as %s, ".
			"connected_account.updated_at as %s ",
				SearchFields_ConnectedAccount::ID,
				SearchFields_ConnectedAccount::NAME,
				SearchFields_ConnectedAccount::URI,
				SearchFields_ConnectedAccount::SERVICE_ID,
				SearchFields_ConnectedAccount::OWNER_CONTEXT,
				SearchFields_ConnectedAccount::OWNER_CONTEXT_ID,
				SearchFields_ConnectedAccount::CREATED_AT,
				SearchFields_ConnectedAccount::UPDATED_AT
			);
			
		$join_sql = "FROM connected_account ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ConnectedAccount');
	
		return array(
			'primary_table' => 'connected_account',
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
			SearchFields_ConnectedAccount::ID,
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

class SearchFields_ConnectedAccount extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const SERVICE_ID = 'c_service_id';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const CREATED_AT = 'c_created_at';
	const UPDATED_AT = 'c_updated_at';
	const URI = 'c_uri';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	const VIRTUAL_SERVICE_SEARCH = '*_service_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'connected_account.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CONNECTED_ACCOUNT => new DevblocksSearchFieldContextKeys('connected_account.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT), '%s'), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'connected_account.owner_context', 'connected_account.owner_context_id');
				break;
			
			case self::VIRTUAL_SERVICE_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_CONNECTED_SERVICE, 'connected_account.service_id');
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
			case 'extension':
				$key = 'service';
				break;
				
			case 'owner':
				$key = 'owner';
				$search_key = 'owner';
				$owner_field = $search_fields[SearchFields_ConnectedAccount::OWNER_CONTEXT];
				$owner_id_field = $search_fields[SearchFields_ConnectedAccount::OWNER_CONTEXT_ID];
				
				return [
					'key_query' => $key,
					'key_select' => $search_key,
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'sql_select' => sprintf("CONCAT_WS(':',%s.%s,%s.%s)",
						Cerb_ORMHelper::escape($owner_field->db_table),
						Cerb_ORMHelper::escape($owner_field->db_column),
						Cerb_ORMHelper::escape($owner_id_field->db_table),
						Cerb_ORMHelper::escape($owner_id_field->db_column)
					),
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->link('owner'),
				];
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ConnectedAccount::ID:
				$models = DAO_ConnectedAccount::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case SearchFields_ConnectedAccount::SERVICE_ID:
				$models = DAO_ConnectedService::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case 'owner':
				return self::_getLabelsForKeyContextAndIdValues($values);
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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'connected_account', 'created_at', $translate->_('common.created'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'connected_account', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'connected_account', 'name', $translate->_('common.name'), null, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'connected_account', 'owner_context', null, null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'connected_account', 'owner_context_id', null, null, true),
			self::SERVICE_ID => new DevblocksSearchField(self::SERVICE_ID, 'connected_account', 'service_id', $translate->_('common.service.provider'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'connected_account', 'updated_at', $translate->_('common.updated'), null, true),
			self::URI => new DevblocksSearchField(self::URI, 'connected_account', 'uri', $translate->_('common.uri'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), null, false),
			self::VIRTUAL_SERVICE_SEARCH => new DevblocksSearchField(self::VIRTUAL_SERVICE_SEARCH, '*', 'service_search', null, null, false),
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

class Model_ConnectedAccount {
	public $created_at;
	public $id;
	public $name;
	public $owner_context;
	public $owner_context_id;
	public $params_json_encrypted;
	public $service_id = 0;
	public $updated_at;
	public $uri;
	
	public function getService() {
		return DAO_ConnectedService::get($this->service_id);
	}
	
	public function getServiceExtension() {
		if(false == ($service = $this->getService()))
			return null;
		
		return $service->getExtension();
	}
	
	public function decryptParams($actor=null) {
		if($actor && !Context_ConnectedAccount::isReadableByActor($this, $actor))
			return false;
		
		$encrypt = DevblocksPlatform::services()->encryption();
		
		if(false == ($json = $encrypt->decrypt($this->params_json_encrypted)))
			return false;
		
		if(!$json || false == ($params = json_decode($json, true)))
			return false;
		
		return $params;
	}
	
	public function authenticateHttpRequest(Psr\Http\Message\RequestInterface &$request, array &$options, $actor) : bool {
		// Load the extension for this connected account
		if(false == ($ext = $this->getServiceExtension()))
			return false;
		
		if(!Context_ConnectedAccount::isUsableByActor($this, $actor))
			return false;
		
		return $ext->authenticateHttpRequest($this, $request, $options);
	}
};

class View_ConnectedAccount extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'connected_accounts';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.connected_accounts');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ConnectedAccount::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ConnectedAccount::NAME,
			SearchFields_ConnectedAccount::SERVICE_ID,
			SearchFields_ConnectedAccount::URI,
			SearchFields_ConnectedAccount::VIRTUAL_OWNER,
			SearchFields_ConnectedAccount::UPDATED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_ConnectedAccount::OWNER_CONTEXT,
			SearchFields_ConnectedAccount::OWNER_CONTEXT_ID,
			SearchFields_ConnectedAccount::VIRTUAL_CONTEXT_LINK,
			SearchFields_ConnectedAccount::VIRTUAL_HAS_FIELDSET,
			SearchFields_ConnectedAccount::VIRTUAL_SERVICE_SEARCH,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_ConnectedAccount::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ConnectedAccount');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_ConnectedAccount', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ConnectedAccount', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_ConnectedAccount::SERVICE_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_ConnectedAccount::VIRTUAL_CONTEXT_LINK:
				case SearchFields_ConnectedAccount::VIRTUAL_HAS_FIELDSET:
				case SearchFields_ConnectedAccount::VIRTUAL_OWNER:
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
		$context = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_ConnectedAccount::SERVICE_ID:
				$label_map = function($ids) use ($column) {
					return SearchFields_ConnectedAccount::getLabelsForKeyValues($column, $ids);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_CustomFieldset::OWNER_CONTEXT, DAO_CustomFieldset::OWNER_CONTEXT_ID, 'owner_context[]');
				break;

			case SearchFields_ConnectedAccount::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_ConnectedAccount::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ConnectedAccount::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ConnectedAccount::CREATED_AT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ConnectedAccount::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_CONNECTED_ACCOUNT],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ConnectedAccount::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ConnectedAccount::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'service.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ConnectedAccount::SERVICE_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CONNECTED_SERVICE, 'q' => ''],
					]
				),
			'service' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => [
						'param_key' => SearchFields_ConnectedAccount::VIRTUAL_SERVICE_SEARCH,
						'select_key' => 'connected_account.service_id',
					],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CONNECTED_SERVICE, 'q' => ''],
					]
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ConnectedAccount::UPDATED_AT),
				),
			'uri' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ConnectedAccount::URI),
				),
		);
		
		// Add dynamic owner.* fields
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('owner', $fields, 'owner', SearchFields_ConnectedAccount::VIRTUAL_OWNER);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_ConnectedAccount::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $fields, null);
		
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
			
			case 'service':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_ConnectedAccount::VIRTUAL_SERVICE_SEARCH);
				break;
				
			default:
				if($field == 'owner' || substr($field, 0, strlen('owner.')) == 'owner.')
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'owner', SearchFields_ConnectedAccount::VIRTUAL_OWNER);
				
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/connected_account/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			case SearchFields_ConnectedAccount::SERVICE_ID:
				$label_map = function($ids) use ($field) {
					return SearchFields_ConnectedAccount::getLabelsForKeyValues($field, $ids);
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
			case SearchFields_ConnectedAccount::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners', 'Owner matches');
				break;
			
			case SearchFields_ConnectedAccount::VIRTUAL_SERVICE_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.service')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
		}
	}

	function getFields() {
		return SearchFields_ConnectedAccount::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ConnectedAccount::NAME:
			case SearchFields_ConnectedAccount::URI:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ConnectedAccount::ID:
			case SearchFields_ConnectedAccount::SERVICE_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ConnectedAccount::CREATED_AT:
			case SearchFields_ConnectedAccount::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_OWNER:
				$owner_contexts = DevblocksPlatform::importGPC($_POST['owner_context'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
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

class Context_ConnectedAccount extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete, IDevblocksContextUri {
	const ID = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;
	const URI = 'connected_account';
	
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	static function isUsableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $models);
	}
	
	function getRandom() {
		return DAO_ConnectedAccount::random();
	}
	
	function autocompleteUri($term, $uri_params = null): array {
		$where_sql = sprintf("%s LIKE %s", DAO_ConnectedAccount::URI, Cerb_ORMHelper::qstr('%' . $term . '%'));
		
		$connected_accounts = DAO_ConnectedAccount::getWhere(
			$where_sql,
			null,
			null,
			25
		);
		
		if(!is_iterable($connected_accounts))
			return [];
		
		return array_map(
			function ($account) {
				return [
					'caption' => $account->name,
					'snippet' => $account->uri ?: $account->id,
				];
			},
			$connected_accounts
		);
	}
	
	function autocomplete($term, $query=null) {
		$list = [];
		
		$models = DAO_ConnectedAccount::autocomplete($term);
		
		if(stristr('none',$term) || stristr('empty',$term)) {
			$empty = new stdClass();
			$empty->label = '(no account)';
			$empty->value = '0';
			$empty->meta = array('desc' => 'Clear the account');
			$list[] = $empty;
		}
		
		if(is_array($models))
		foreach($models as $account_id => $account){
			$entry = new stdClass();
			$entry->label = $account->name;
			$entry->value = sprintf("%d", $account_id);
			
			$meta = array();
			$entry->meta = $meta;
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=connected_account&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_ConnectedAccount();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CONNECTED_ACCOUNT,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			],
		);
			
		$properties['service'] = array(
			'label' => mb_ucfirst($translate->_('common.service.provider')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => @$model->getService()->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CONNECTED_SERVICE,
			],
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['uri'] = [
			'label' => DevblocksPlatform::translate('common.uri'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->uri,
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($connected_account = DAO_ConnectedAccount::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($connected_account->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $connected_account->id,
			'name' => $connected_account->name,
			'permalink' => $url,
			'updated' => $connected_account->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'owner__label',
			'service__label',
			'updated_at',
		);
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_ConnectedAccount::getByUri($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($connected_account, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Connected Account:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT);

		// Polymorph
		if(is_numeric($connected_account)) {
			$connected_account = DAO_ConnectedAccount::get($connected_account);
		} elseif($connected_account instanceof Model_ConnectedAccount) {
			// It's what we want already.
		} elseif(is_array($connected_account)) {
			$connected_account = Cerb_ORMHelper::recastArrayToModel($connected_account, 'Model_ConnectedAccount');
		} else {
			$connected_account = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'service' => $prefix.$translate->_('common.service.provider'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'uri' => $prefix.$translate->_('common.uri'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'owner__label' => $prefix.$translate->_('common.owner'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'service' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'uri' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
			'owner__label' => 'context_url',
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_ConnectedAccount::ID;
		$token_values['_type'] = Context_ConnectedAccount::URI;
		
		$token_values['_types'] = $token_types;
		
		if($connected_account) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $connected_account->name;
			$token_values['id'] = $connected_account->id;
			$token_values['name'] = $connected_account->name;
			$token_values['service_id'] = $connected_account->service_id;
			$token_values['updated_at'] = $connected_account->updated_at;
			$token_values['uri'] = $connected_account->uri;
			
			$token_values['owner__context'] = $connected_account->owner_context;
			$token_values['owner_id'] = $connected_account->owner_context_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($connected_account, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=connected_account&id=%d-%s",$connected_account->id, DevblocksPlatform::strToPermalink($connected_account->name)), true);
		}
		
		// Service
		$merge_token_labels = $merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_CONNECTED_SERVICE, null, $merge_token_labels, $merge_token_values, '', true);
			CerberusContexts::merge(
				'service_',
				$prefix.'Service:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_ConnectedAccount::ID,
			'links' => '_links',
			'name' => DAO_ConnectedAccount::NAME,
			'owner__context' => DAO_ConnectedAccount::OWNER_CONTEXT,
			'owner_id' => DAO_ConnectedAccount::OWNER_CONTEXT_ID,
			'service_id' => DAO_ConnectedAccount::SERVICE_ID,
			'updated_at' => DAO_ConnectedAccount::UPDATED_AT,
			'uri' => DAO_ConnectedAccount::URI,
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
		
		$keys['service_id']['type'] = "id";
		$keys['service_id']['notes'] = "[Service Provider](/docs/plugins/extensions/points/cerb.connected_service.provider/)";
		
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
				
				$out_fields[DAO_ConnectedAccount::PARAMS_JSON] = $encrypt->encrypt($json);
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
		
		$context = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
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
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$this->name = DevblocksPlatform::translateCapitalized('common.connected_accounts');
		
		$required_query = '';
		
		if($active_worker && !$active_worker->is_superuser) {
			$worker_group_ids = array_keys($active_worker->getManagerships());
			//$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
			
			// Restrict owners
			
			$required_query .= sprintf('(owner.worker:(id:[%d]) OR owner.group:(id:[%s]) ',
				$active_worker->id,
				implode(',', $worker_group_ids)
			);
		}
		$view->setParamsRequiredQuery($required_query);
		
		$view->renderSortBy = SearchFields_ConnectedAccount::NAME;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		
		$active_worker = CerberusApplication::getActiveWorker();

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$this->name = DevblocksPlatform::translateCapitalized('common.connected_accounts');
		
		$required_query = '';
		
		if($active_worker && !$active_worker->is_superuser) {
			$worker_group_ids = array_keys($active_worker->getManagerships());
			//$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
			
			// Restrict owners
			
			$required_query .= sprintf('(owner.worker:(id:[%d]) OR owner.group:(id:[%s]) ',
				$active_worker->id,
				implode(',', $worker_group_ids)
			);
		}
		
		if(!empty($context) && !empty($context_id)) {
			$linked_context_mft = Extension_DevblocksContext::get($context, false);
			
			$required_query .= sprintf("links.%s:(id:%d) ",
				$linked_context_mft->params['alias'],
				$context_id
			);
		}
		
		$view->setParamsRequiredQuery($required_query);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($context_id)) {
			$model = null;
			
			if(!is_numeric($context_id))
				if(false != ($model = DAO_ConnectedAccount::getByUri($context_id)))
					$context_id = $model->id;
			
			if(!$model)
				$model = DAO_ConnectedAccount::get($context_id);
			
			if(!$model)
				DevblocksPlatform::dieWithHttpError(null, 404);
			
		} else {
			$model = new Model_ConnectedAccount();
			$model->owner_context = CerberusContexts::CONTEXT_WORKER;
			$model->owner_context_id = $active_worker->id;
		}
		
		if(empty($context_id) || $edit) {
			if($model && $model->id) {
				if (!Context_ConnectedAccount::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			if(!empty($edit)) {
				$tokens = explode(' ', trim($edit));
				
				foreach($tokens as $token) {
					list($k,$v) = array_pad(explode(':', $token, 2), 2, null);
					
					if(empty($k) || empty($v))
						continue;
					
					switch($k) {
						case 'service.id':
							$model->service_id = intval($v);
							break;
					}
				}
			}
			
			$tpl->assign('model', $model);
			
			if(empty($context_id) && empty($model->service_id)) {
				$service_manifests = Extension_ConnectedServiceProvider::getAll(false);
				
				// Only instantiatable
				$services = array_filter(
					DAO_ConnectedService::getAll(),
					function($service) use ($service_manifests) {
						if(false == (@$service_manifest = $service_manifests[$service->extension_id]))
							return false;
						
						return $service_manifest->hasOption('accounts');
					}
				);
				
				// Sort services by name
				DevblocksPlatform::sortObjects($services, 'name');
				
				$tpl->assign('services', $services);
				$tpl->display('devblocks:cerberusweb.core::internal/connected_account/new.tpl');
				return;
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Owner
			$owners_menu = Extension_DevblocksContext::getOwnerTree([CerberusContexts::CONTEXT_APPLICATION, CerberusContexts::CONTEXT_ROLE, CerberusContexts::CONTEXT_GROUP, CerberusContexts::CONTEXT_WORKER]);
			$tpl->assign('owners_menu', $owners_menu);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/connected_account/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
