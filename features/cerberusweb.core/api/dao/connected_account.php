<?php
class DAO_ConnectedAccount extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const EXTENSION_ID = 'extension_id';
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const PARAMS_JSON = 'params_json';
	const UPDATED_AT = 'updated_at';
	
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
			->setRequired(true)
			->addValidator(function($value, &$error) {
				if(false == ($extension = Extension_ServiceProvider::get($value))) {
					$error = sprintf("(%s) is not a valid service provider (%s) extension ID.",
						$value,
						Extension_ServiceProvider::POINT
					);
					return false;
				}
				
				return true;
			})
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
			->addField(self::UPDATED_AT)
			->timestamp()
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
	
	static public function onBeforeUpdateByActor($actor, $fields, $id=null, &$error=null) {
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
	
	static function getByExtension($extension_id) {
		$accounts = DAO_ConnectedAccount::getAll();
		
		if(!is_null($extension_id)) {
			$accounts = array_filter($accounts, function($account) use ($extension_id) {
				return $account->extension_id == $extension_id;
			});
		}
		
		return $accounts;
	}
	
	static function getReadableByActor($actor, $extension_id=null) {
		$accounts = DAO_ConnectedAccount::getAll();
		
		if(!is_null($extension_id)) {
			$accounts = array_filter($accounts, function($account) use ($extension_id) {
				return $account->extension_id == $extension_id;
			});
		}
		
		return CerberusContexts::filterModelsByActorReadable('Context_ConnectedAccount', $accounts, $actor);
	}
	
	static function getWriteableByActor($actor, $extension_id=null) {
		$accounts = DAO_ConnectedAccount::getAll();
		
		if(!is_null($extension_id)) {
			$accounts = array_filter($accounts, function($account) use ($extension_id) {
				return $account->extension_id == $extension_id;
			});
		}
		
		return CerberusContexts::filterModelsByActorWriteable('Context_ConnectedAccount', $accounts, $actor);
	}
	
	static function getUsableByActor($actor, $extension_id=null) {
		$accounts = DAO_ConnectedAccount::getAll();
		
		if(!is_null($extension_id)) {
			$accounts = array_filter($accounts, function($account) use ($extension_id) {
				return $account->extension_id == $extension_id;
			});
		}
		
		return array_intersect_key($accounts, array_flip(array_keys(Context_ConnectedAccount::isUsableByActor($accounts, $actor), true)));
	}
	
	static function autocomplete($term, $as='models') {
		$params = array(
			SearchFields_ConnectedAccount::NAME => new DevblocksSearchCriteria(SearchFields_ConnectedAccount::NAME, DevblocksSearchCriteria::OPER_LIKE, $term.'*'),
		);
		
		list($results, $null) = DAO_ConnectedAccount::search(
			array(),
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
				break;
				
			default:
				return DAO_ConnectedAccount::getIds(array_keys($results));
				break;
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
		$sql = "SELECT id, name, extension_id, owner_context, owner_context_id, params_json, created_at, updated_at ".
			"FROM connected_account ".
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
	static function getIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);

		if(empty($ids))
			return array();

		if(!method_exists(get_called_class(), 'getWhere'))
			return array();

		$db = DevblocksPlatform::services()->database();

		$ids = DevblocksPlatform::importVar($ids, 'array:integer');

		$models = array();

		$results = static::getWhere(sprintf("id IN (%s)",
			implode(',', $ids)
		));

		// Sort $models in the same order as $ids
		foreach($ids as $id) {
			if(isset($results[$id]))
				$models[$id] = $results[$id];
		}

		unset($results);

		return $models;
	}	
	
	/**
	 * @param resource $rs
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
			$object->extension_id = $row['extension_id'];
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
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ConnectedAccount', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"connected_account.id as %s, ".
			"connected_account.name as %s, ".
			"connected_account.extension_id as %s, ".
			"connected_account.owner_context as %s, ".
			"connected_account.owner_context_id as %s, ".
			"connected_account.created_at as %s, ".
			"connected_account.updated_at as %s ",
				SearchFields_ConnectedAccount::ID,
				SearchFields_ConnectedAccount::NAME,
				SearchFields_ConnectedAccount::EXTENSION_ID,
				SearchFields_ConnectedAccount::OWNER_CONTEXT,
				SearchFields_ConnectedAccount::OWNER_CONTEXT_ID,
				SearchFields_ConnectedAccount::CREATED_AT,
				SearchFields_ConnectedAccount::UPDATED_AT
			);
			
		$join_sql = "FROM connected_account ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ConnectedAccount');
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
	
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
			$object_id = intval($row[SearchFields_ConnectedAccount::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(connected_account.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_ConnectedAccount extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const EXTENSION_ID = 'c_extension_id';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const CREATED_AT = 'c_created_at';
	const UPDATED_AT = 'c_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	
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
				
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'connected_account.owner_context', 'connected_account.owner_context_id');
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
			self::ID => new DevblocksSearchField(self::ID, 'connected_account', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'connected_account', 'name', $translate->_('common.name'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'connected_account', 'extension_id', $translate->_('common.service.provider'), null, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'connected_account', 'owner_context', null, null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'connected_account', 'owner_context_id', null, null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'connected_account', 'created_at', $translate->_('common.created'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'connected_account', 'updated_at', $translate->_('common.updated'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), null, false),
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
	public $id;
	public $name;
	public $extension_id;
	public $owner_context;
	public $owner_context_id;
	public $params_json_encrypted;
	public $created_at;
	public $updated_at;
	
	public function getExtension() {
		return Extension_ServiceProvider::get($this->extension_id);
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
	
	public function canAuthenticateHttpRequests() {
		// Load the extension for this connected account
		if(false == ($ext = Extension_ServiceProvider::get($this->extension_id)))
			return false;
		
		if($ext instanceof IServiceProvider_HttpRequestSigner)
			return true;
		
		return false;
	}
	
	public function authenticateHttpRequest(&$ch, &$verb, &$url, &$body, &$headers, $actor) {
		// Load the extension for this connected account
		if(false == ($ext = Extension_ServiceProvider::get($this->extension_id)))
			return false;
		
		if(!Context_ConnectedAccount::isUsableByActor($this, $actor))
			return false;
		
		// Check the interface on the service
		if(!($ext instanceof IServiceProvider_HttpRequestSigner))
			return false;
		
		return $ext->authenticateHttpRequest($this, $ch, $verb, $url, $body, $headers);
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
			SearchFields_ConnectedAccount::EXTENSION_ID,
			SearchFields_ConnectedAccount::VIRTUAL_OWNER,
			SearchFields_ConnectedAccount::UPDATED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_ConnectedAccount::OWNER_CONTEXT,
			SearchFields_ConnectedAccount::OWNER_CONTEXT_ID,
			SearchFields_ConnectedAccount::VIRTUAL_CONTEXT_LINK,
			SearchFields_ConnectedAccount::VIRTUAL_HAS_FIELDSET,
		));
		
		$this->addParamsHidden(array(
			SearchFields_ConnectedAccount::OWNER_CONTEXT,
			SearchFields_ConnectedAccount::OWNER_CONTEXT_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ConnectedAccount::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
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
				case SearchFields_ConnectedAccount::EXTENSION_ID:
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
			case SearchFields_ConnectedAccount::EXTENSION_ID:
				$label_map = array_column(DevblocksPlatform::objectsToArrays(Extension_ServiceProvider::getAll(false)), 'name', 'id');
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
				if('cf_' == substr($column,0,3)) {
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
			'service' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ConnectedAccount::EXTENSION_ID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ConnectedAccount::UPDATED_AT),
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

		// Extension manifests
		$provider_mfts = Extension_ServiceProvider::getAll(false);
		$tpl->assign('provider_mfts', $provider_mfts);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/connected_account/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_ConnectedAccount::EXTENSION_ID:
				$label_map = array_column(DevblocksPlatform::objectsToArrays(Extension_ServiceProvider::getAll(false)), 'name', 'id');
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
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
		}
	}

	function getFields() {
		return SearchFields_ConnectedAccount::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ConnectedAccount::NAME:
			case SearchFields_ConnectedAccount::EXTENSION_ID:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ConnectedAccount::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ConnectedAccount::CREATED_AT:
			case SearchFields_ConnectedAccount::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_ConnectedAccount::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
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

class Context_ConnectedAccount extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $models);
	}
	
	static function isUsableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $models);
	}
	
	function getRandom() {
		return DAO_ConnectedAccount::random();
	}
	
	function autocomplete($term, $query=null) {
		$url_writer = DevblocksPlatform::services()->url();
		$list = array();
		
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
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			],
		);
			
		$properties['extension'] = array(
			'label' => mb_ucfirst($translate->_('common.service.provider')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->extension_id,
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
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$connected_account = DAO_ConnectedAccount::get($context_id);
		$url_writer = DevblocksPlatform::services()->url();
		
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
			'service',
			'updated_at',
		);
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
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;
		$token_values['_types'] = $token_types;
		
		if($connected_account) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $connected_account->name;
			$token_values['id'] = $connected_account->id;
			$token_values['name'] = $connected_account->name;
			$token_values['service'] = $connected_account->extension_id;
			$token_values['updated_at'] = $connected_account->updated_at;
			
			$token_values['owner__context'] = $connected_account->owner_context;
			$token_values['owner_id'] = $connected_account->owner_context_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($connected_account, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=connected_account&id=%d-%s",$connected_account->id, DevblocksPlatform::strToPermalink($connected_account->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'extension_id' => DAO_ConnectedAccount::EXTENSION_ID,
			'id' => DAO_ConnectedAccount::ID,
			'links' => '_links',
			'name' => DAO_ConnectedAccount::NAME,
			'owner__context' => DAO_ConnectedAccount::OWNER_CONTEXT,
			'owner_id' => DAO_ConnectedAccount::OWNER_CONTEXT_ID,
			'service' => DAO_ConnectedAccount::EXTENSION_ID,
			'updated_at' => DAO_ConnectedAccount::UPDATED_AT,
		];
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'links':
				$this->_getDaoFieldsLinks($value, $out_fields, $error);
				break;
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
			
			default:
				if(DevblocksPlatform::strStartsWith($token, 'custom_')) {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
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
		
		$params_req = array();
		
		if($active_worker && !$active_worker->is_superuser) {
			$worker_group_ids = array_keys($active_worker->getManagerships());
			$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
			
			// Restrict owners
			
			$params = $view->getParamsFromQuickSearch(sprintf('(owner.worker:(id:[%d]) OR owner.group:(id:[%s])',
				$active_worker->id,
				implode(',', $worker_group_ids)
			));
			
			$params_req['_ownership'] = $params[0];
		}
		
		$view->addParamsRequired($params_req, true);
		
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
		
		$params_req = array();
		
		if($active_worker && !$active_worker->is_superuser) {
			$worker_group_ids = array_keys($active_worker->getManagerships());
			$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
			
			// Restrict owners
			
			$params = $view->getParamsFromQuickSearch(sprintf('(owner.worker:(id:[%d]) OR owner.group:(id:[%s])',
				$active_worker->id,
				implode(',', $worker_group_ids)
			));
			
			$params_req['_ownership'] = $params[0];
		}
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_ConnectedAccount::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_CONNECTED_ACCOUNT;
		
		if(!empty($context_id)) {
			$model = DAO_ConnectedAccount::get($context_id);
		} else {
			$model = new Model_ConnectedAccount();
		}
		
		if(empty($context_id) || $edit) {
			if(!empty($edit)) {
				$tokens = explode(' ', trim($edit));
				
				foreach($tokens as $token) {
					@list($k,$v) = explode(':', $token);
					
					if(empty($k) || empty($v))
						continue;
					
					switch($k) {
						case 'service':
							$model->extension_id = $v;
							break;
					}
				}
			}
			
			$tpl->assign('model', $model);
			
			if(empty($context_id) && empty($model->extension_id)) {
				$services = Extension_ServiceProvider::getAll(false);
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
			// Links
			$links = array(
				$context => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							array(CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			// Dictionary
			$labels = $values = [];
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			// Card search buttons
			$search_buttons = $context_ext->getCardSearchButtons($dict, []);
			$tpl->assign('search_buttons', $search_buttons);
			
			$tpl->display('devblocks:cerberusweb.core::internal/connected_account/peek.tpl');
		}
	}
};
