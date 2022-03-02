<?php
class DAO_GpgPrivateKey extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const FINGERPRINT = 'fingerprint';
	const EXPIRES_AT = 'expires_at';
	const UPDATED_AT = 'updated_at';
	const KEY_TEXT = 'key_text';
	const PASSPHRASE_ENCRYPTED = 'passphrase_encrypted';
	
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
			->addField(self::FINGERPRINT)
			->string()
			->setRequired(true)
		;
		$validation
			->addField(self::EXPIRES_AT)
			->timestamp()
		;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::KEY_TEXT)
			->string()
			->setMaxLength('16 bits')
		;
		$validation
			->addField(self::PASSPHRASE_ENCRYPTED)
			->string()
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
		
		$sql = "INSERT INTO gpg_private_key () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_GpgPrivateKey::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = Context_GpgPrivateKey::ID;
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
			parent::_update($batch_ids, 'gpg_private_key', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.gpg_private_key.update',
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
		parent::_updateWhere('gpg_private_key', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = Context_GpgPrivateKey::ID;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_GpgPrivateKey[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, fingerprint, expires_at, updated_at, key_text, passphrase_encrypted ".
			"FROM gpg_private_key ".
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
	 * @param string $fingerprint
	 * @return Model_GpgPrivateKey
	 */
	static function getByFingerprint($fingerprint) {
		if(16 == strlen($fingerprint)) {
			$objects = DAO_GpgKeyPart::getPrivateKeysByPart('fingerprint16', $fingerprint);
		} else {
			$objects = DAO_GpgKeyPart::getPrivateKeysByPart('fingerprint', $fingerprint);
		}
		
		// [TODO] This could return multiple matches
		if(!empty($objects))
			return array_shift($objects);
		
		return null;
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_GpgPrivateKey[]
	 */
	static function getAll($nocache=false) {
		return self::getWhere(null, DAO_GpgPrivateKey::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
	}
	
	/**
	 * @param integer $id
	 * @return Model_GpgPrivateKey
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
	 * @return Model_GpgPrivateKey[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_GpgPrivateKey[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_GpgPrivateKey();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->fingerprint = $row['fingerprint'];
			$object->expires_at = $row['expires_at'];
			$object->updated_at = $row['updated_at'];
			$object->key_text = $row['key_text'];
			$object->passphrase_encrypted = $row['passphrase_encrypted'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('gpg_private_key');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		$gpg = DevblocksPlatform::services()->gpg();
		
		if (!is_array($ids))
			$ids = [$ids];
		
		if (empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM gpg_private_key WHERE id IN (%s)", $ids_list));
		
		$results = $db->GetArrayReader(sprintf("SELECT id, fingerprint FROM gpg_private_key WHERE id IN (%s)", $ids_list));
		
		// Delete from keyring
		if (is_array($results)) {
			foreach ($results as $result) {
				if (isset($result['fingerprint']) && $result['fingerprint'])
					$gpg->deletePrivateKey($result['fingerprint']);
			}
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => Context_GpgPrivateKey::ID,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_GpgPrivateKey::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_GpgPrivateKey', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"gpg_private_key.id as %s, ".
			"gpg_private_key.name as %s, ".
			"gpg_private_key.fingerprint as %s, ".
			"gpg_private_key.expires_at as %s, ".
			"gpg_private_key.updated_at as %s ",
			SearchFields_GpgPrivateKey::ID,
			SearchFields_GpgPrivateKey::NAME,
			SearchFields_GpgPrivateKey::FINGERPRINT,
			SearchFields_GpgPrivateKey::EXPIRES_AT,
			SearchFields_GpgPrivateKey::UPDATED_AT
		);
		
		$join_sql = "FROM gpg_private_key ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_GpgPrivateKey');
		
		return array(
			'primary_table' => 'gpg_private_key',
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
			SearchFields_GpgPrivateKey::ID,
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

class SearchFields_GpgPrivateKey extends DevblocksSearchFields {
	const ID = 'g_id';
	const NAME = 'g_name';
	const FINGERPRINT = 'g_fingerprint';
	const EXPIRES_AT = 'g_expires_at';
	const UPDATED_AT = 'g_updated_at';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'gpg_private_key.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			Context_GpgPrivateKey::ID => new DevblocksSearchFieldContextKeys('gpg_private_key.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FINGERPRINT:
				// Optimize lookups on id16
				if($param->operator == DevblocksSearchCriteria::OPER_EQ && 16 == strlen($param->value)) {
					return sprintf("id IN (SELECT key_id FROM gpg_key_part WHERE key_context = %s AND part_name = %s AND part_value = %s)",
						Cerb_ORMHelper::qstr(Context_GpgPrivateKey::ID),
						Cerb_ORMHelper::qstr('fingerprint16'),
						Cerb_ORMHelper::qstr($param->value)
					);
				} else {
					return sprintf("id IN (SELECT key_id FROM gpg_key_part WHERE key_context = %s AND part_name = %s AND part_value = %s)",
						Cerb_ORMHelper::qstr(Context_GpgPrivateKey::ID),
						Cerb_ORMHelper::qstr('fingerprint'),
						Cerb_ORMHelper::qstr($param->value)
					);
				}
			
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, Context_GpgPrivateKey::ID, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(Context_GpgPrivateKey::ID), '%s'), self::getPrimaryKey());
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, Context_GpgPrivateKey::ID, self::getPrimaryKey());
				
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
			case SearchFields_GpgPrivateKey::ID:
				$models = DAO_GpgPrivateKey::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'gpg_private_key', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'gpg_private_key', 'name', $translate->_('common.name'), null, true),
			self::FINGERPRINT => new DevblocksSearchField(self::FINGERPRINT, 'gpg_private_key', 'fingerprint', $translate->_('common.fingerprint'), null, true),
			self::EXPIRES_AT => new DevblocksSearchField(self::EXPIRES_AT, 'gpg_private_key', 'expires_at', $translate->_('common.expires'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'gpg_private_key', 'updated_at', $translate->_('common.updated'), null, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
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

class Model_GpgPrivateKey {
	public $id;
	public $name;
	public $fingerprint;
	public $expires_at;
	public $updated_at;
	public $key_text;
	public $passphrase_encrypted;
};

class View_GpgPrivateKey extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'gpg_private_keys';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('PGP Private Keys');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_GpgPrivateKey::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = array(
			SearchFields_GpgPrivateKey::NAME,
			SearchFields_GpgPrivateKey::FINGERPRINT,
			SearchFields_GpgPrivateKey::EXPIRES_AT,
			SearchFields_GpgPrivateKey::UPDATED_AT,
		);
		$this->addColumnsHidden(array(
			SearchFields_GpgPrivateKey::VIRTUAL_CONTEXT_LINK,
			SearchFields_GpgPrivateKey::VIRTUAL_HAS_FIELDSET,
			SearchFields_GpgPrivateKey::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_GpgPrivateKey::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_GpgPrivateKey');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_GpgPrivateKey', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_GpgPrivateKey', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Virtuals
					case SearchFields_GpgPrivateKey::VIRTUAL_CONTEXT_LINK:
					case SearchFields_GpgPrivateKey::VIRTUAL_HAS_FIELDSET:
					case SearchFields_GpgPrivateKey::VIRTUAL_WATCHERS:
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
		$context = Context_GpgPrivateKey::ID;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_GpgPrivateKey::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_GpgPrivateKey::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
			
			case SearchFields_GpgPrivateKey::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_GpgPrivateKey::getFields();
		
		$fields = array(
			'text' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_GpgPrivateKey::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'expires' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_GpgPrivateKey::EXPIRES_AT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_GpgPrivateKey::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_GpgPrivateKey::ID],
					]
				),
			'fingerprint' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_GpgPrivateKey::FINGERPRINT),
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_GpgPrivateKey::ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_GpgPrivateKey::ID, 'q' => ''],
					]
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_GpgPrivateKey::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_GpgPrivateKey::UPDATED_AT),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_GpgPrivateKey::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_GpgPrivateKey::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(Context_GpgPrivateKey::ID, $fields, null);
		
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
			
			case 'fingerprint':
				return DevblocksSearchCriteria::getTextParamFromTokens(SearchFields_GpgPrivateKey::FINGERPRINT, $tokens);

			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_GpgPrivateKey::VIRTUAL_WATCHERS, $tokens);
			
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
		$custom_fields = DAO_CustomField::getByContext(Context_GpgPrivateKey::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/gpg_private_key/view.tpl');
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
			case SearchFields_GpgPrivateKey::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_GpgPrivateKey::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_GpgPrivateKey::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_GpgPrivateKey::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_GpgPrivateKey::NAME:
			case SearchFields_GpgPrivateKey::FINGERPRINT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_GpgPrivateKey::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_GpgPrivateKey::EXPIRES_AT:
			case SearchFields_GpgPrivateKey::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case 'placeholder_bool':
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_GpgPrivateKey::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_GpgPrivateKey::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
			
			case SearchFields_GpgPrivateKey::VIRTUAL_WATCHERS:
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

class Context_GpgPrivateKey extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.gpg.private.key';
	const URI = 'gpg_private_key';
	
	static function isReadableByActor($models, $actor) {
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
		return DAO_GpgPrivateKey::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=gpg_private_key&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_GpgPrivateKey();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['expires_at'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.expires'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->expires_at,
		);
		
		$properties['fingerprint'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.fingerprint'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->fingerprint,
		);
		
		$properties['updated_at'] = array(
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
		if(null == ($gpg_private_key = DAO_GpgPrivateKey::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($gpg_private_key->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $gpg_private_key->id,
			'name' => $gpg_private_key->name,
			'permalink' => $url,
			'updated' => $gpg_private_key->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_GpgPrivateKey::getByFingerprint($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($gpg_private_key, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Gpg Private Key:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_GpgPrivateKey::ID);
		
		// Polymorph
		if(is_numeric($gpg_private_key)) {
			$gpg_private_key = DAO_GpgPrivateKey::get($gpg_private_key);
		} elseif($gpg_private_key instanceof Model_GpgPrivateKey) {
			// It's what we want already.
		} elseif(is_array($gpg_private_key)) {
			$gpg_private_key = Cerb_ORMHelper::recastArrayToModel($gpg_private_key, 'Model_GpgPrivateKey');
		} else {
			$gpg_private_key = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = Context_GpgPrivateKey::ID;
		$token_values['_type'] = Context_GpgPrivateKey::URI;
		
		$token_values['_types'] = $token_types;
		
		if($gpg_private_key) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $gpg_private_key->name;
			$token_values['id'] = $gpg_private_key->id;
			$token_values['name'] = $gpg_private_key->name;
			$token_values['updated_at'] = $gpg_private_key->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($gpg_private_key, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=gpg_private_key&id=%d-%s",$gpg_private_key->id, DevblocksPlatform::strToPermalink($gpg_private_key->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'expires_at' => DAO_GpgPrivateKey::EXPIRES_AT,
			'fingerprint' => DAO_GpgPrivateKey::FINGERPRINT,
			'id' => DAO_GpgPrivateKey::ID,
			'links' => '_links',
			'name' => DAO_GpgPrivateKey::NAME,
			'updated_at' => DAO_GpgPrivateKey::UPDATED_AT,
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
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_GpgPrivateKey::ID;
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
		$view->name = 'Gpg Private Key';
		/*
		$view->addParams(array(
			SearchFields_GpgPrivateKey::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_GpgPrivateKey::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_GpgPrivateKey::UPDATED_AT;
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
		$view->name = 'Gpg Private Key';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_GpgPrivateKey::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) { // @audited @vulnerable
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('view_id', $view_id);
		
		$context = Context_GpgPrivateKey::ID;
		$active_worker = CerberusApplication::getActiveWorker();
		$model = null;
		
		if($context_id && false == ($model = DAO_GpgPrivateKey::get($context_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_GpgPrivateKey::isWriteableByActor($model, $active_worker))
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
			$tpl->display('devblocks:cerberusweb.core::records/types/gpg_private_key/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
