<?php
class DAO_GpgPublicKey extends Cerb_ORMHelper {
	const EXPIRES_AT = 'expires_at';
	const FINGERPRINT = 'fingerprint';
	const ID = 'id';
	const KEY_TEXT = 'key_text';
	const NAME = 'name';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::EXPIRES_AT)
			->timestamp()
			;
		$validation
			->addField(self::FINGERPRINT)
			->string()
			->setUnique('DAO_GpgPublicKey')
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::KEY_TEXT)
			->string()
			->setMaxLength('16 bits')
			->setRequired(true)
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
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
		
		$sql = "INSERT INTO gpg_public_key () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_GpgPublicKey::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		$context = CerberusContexts::CONTEXT_GPG_PUBLIC_KEY;
		
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'gpg_public_key', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.gpg_public_key.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('gpg_public_key', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_GpgPublicKey[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, fingerprint, expires_at, key_text, updated_at ".
			"FROM gpg_public_key ".
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
	 * @return Model_GpgPublicKey
	 *
	 */
	static function getByFingerprint($fingerprint) {
		if(16 == strlen($fingerprint)) {
			$objects = DAO_GpgKeyPart::getPublicKeysByPart('fingerprint16', $fingerprint);
		} else {
			$objects = DAO_GpgKeyPart::getPublicKeysByPart('fingerprint', $fingerprint);
		}
		
		// [TODO] This could return multiple matches
		if(!empty($objects))
			return array_shift($objects);
		
		return null;
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_GpgPublicKey[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, DAO_GpgPublicKey::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_GpgPublicKey
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
	 * @return Model_GpgPublicKey[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}	
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_GpgPublicKey[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_GpgPublicKey();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->fingerprint = $row['fingerprint'];
			$object->expires_at = $row['expires_at'];
			$object->key_text = $row['key_text'];
			$object->updated_at = $row['updated_at'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('gpg_public_key');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		$gpg = DevblocksPlatform::services()->gpg();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = CerberusContexts::CONTEXT_GPG_PUBLIC_KEY;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		$results = $db->GetArrayReader(sprintf("SELECT id, fingerprint FROM gpg_public_key WHERE id IN (%s)", $ids_list));

		// Delete from keyring
		if(is_array($results))
		foreach($results as $result) {
			if(isset($result['fingerprint']) && $result['fingerprint'])
				$gpg->deletePublicKey($result['fingerprint']);
		}
		
		$db->ExecuteMaster(sprintf("DELETE FROM gpg_public_key WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_GpgPublicKey::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_GpgPublicKey', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"gpg_public_key.id as %s, ".
			"gpg_public_key.name as %s, ".
			"gpg_public_key.fingerprint as %s, ".
			"gpg_public_key.expires_at as %s, ".
			"gpg_public_key.updated_at as %s ",
				SearchFields_GpgPublicKey::ID,
				SearchFields_GpgPublicKey::NAME,
				SearchFields_GpgPublicKey::FINGERPRINT,
				SearchFields_GpgPublicKey::EXPIRES_AT,
				SearchFields_GpgPublicKey::UPDATED_AT
			);
			
		$join_sql = "FROM gpg_public_key ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_GpgPublicKey');
	
		return array(
			'primary_table' => 'gpg_public_key',
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
			SearchFields_GpgPublicKey::ID,
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

class SearchFields_GpgPublicKey extends DevblocksSearchFields {
	const ID = 'g_id';
	const NAME = 'g_name';
	const FINGERPRINT = 'g_fingerprint';
	const EXPIRES_AT = 'g_expires_at';
	const UPDATED_AT = 'g_updated_at';

	const VIRTUAL_UID = '*_uid';
	const VIRTUAL_UID_NAME = '*_uid_name';
	const VIRTUAL_UID_EMAIL = '*_uid_email';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'gpg_public_key.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_GPG_PUBLIC_KEY => new DevblocksSearchFieldContextKeys('gpg_public_key.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FINGERPRINT:
				// Optimize lookups on id16
				if($param->operator == DevblocksSearchCriteria::OPER_EQ && 16 == strlen($param->value)) {
					return sprintf("id IN (SELECT key_id FROM gpg_key_part WHERE key_context = %s AND part_name = %s AND part_value = %s)",
						Cerb_ORMHelper::qstr(Context_GpgPublicKey::ID),
						Cerb_ORMHelper::qstr('fingerprint16'),
						Cerb_ORMHelper::qstr($param->value)
					);
				} else {
					return sprintf("id IN (SELECT key_id FROM gpg_key_part WHERE key_context = %s AND part_name = %s AND part_value = %s)",
						Cerb_ORMHelper::qstr(Context_GpgPublicKey::ID),
						Cerb_ORMHelper::qstr('fingerprint'),
						Cerb_ORMHelper::qstr($param->value)
					);
				}
				break;
				
			case self::VIRTUAL_UID:
				$oper = in_array($param->operator,
					[
						DevblocksSearchCriteria::OPER_EQ,
						DevblocksSearchCriteria::OPER_NEQ,
						DevblocksSearchCriteria::OPER_LIKE,
						DevblocksSearchCriteria::OPER_NOT_LIKE,
					])
					? $param->operator
					: DevblocksSearchCriteria::OPER_EQ
				;
				$value = $param->value;
				
				if(in_array($oper, [DevblocksSearchCriteria::OPER_LIKE, DevblocksSearchCriteria::OPER_NOT_LIKE]))
					$value = str_replace('*', '%', $value);
				
				return sprintf("id IN (SELECT key_id FROM gpg_key_part WHERE key_context = %s AND part_name = %s AND part_value %s %s)",
					Cerb_ORMHelper::qstr(Context_GpgPublicKey::ID),
					Cerb_ORMHelper::qstr('uid'),
					$oper,
					Cerb_ORMHelper::qstr($value)
				);
				break;
				
			case self::VIRTUAL_UID_NAME:
				$oper = in_array($param->operator,
					[
						DevblocksSearchCriteria::OPER_EQ,
						DevblocksSearchCriteria::OPER_NEQ,
						DevblocksSearchCriteria::OPER_LIKE,
						DevblocksSearchCriteria::OPER_NOT_LIKE,
					])
					? $param->operator
					: DevblocksSearchCriteria::OPER_EQ
				;
				$value = $param->value;
				
				if(in_array($oper, [DevblocksSearchCriteria::OPER_LIKE, DevblocksSearchCriteria::OPER_NOT_LIKE]))
					$value = str_replace('*', '%', $value);
				
				return sprintf("id IN (SELECT key_id FROM gpg_key_part WHERE key_context = %s AND part_name = %s AND part_value %s %s)",
					Cerb_ORMHelper::qstr(Context_GpgPublicKey::ID),
					Cerb_ORMHelper::qstr('name'),
					$oper,
					Cerb_ORMHelper::qstr($value)
				);
				break;
				
			case self::VIRTUAL_UID_EMAIL:
				$oper = in_array($param->operator,
					[
						DevblocksSearchCriteria::OPER_EQ,
						DevblocksSearchCriteria::OPER_NEQ,
						DevblocksSearchCriteria::OPER_LIKE,
						DevblocksSearchCriteria::OPER_NOT_LIKE,
					])
					? $param->operator
					: DevblocksSearchCriteria::OPER_EQ
				;
				$value = $param->value;
				
				if(in_array($oper, [DevblocksSearchCriteria::OPER_LIKE, DevblocksSearchCriteria::OPER_NOT_LIKE]))
					$value = str_replace('*', '%', $value);
				
				return sprintf("id IN (SELECT key_id FROM gpg_key_part WHERE key_context = %s AND part_name = %s AND part_value %s %s)",
					Cerb_ORMHelper::qstr(Context_GpgPublicKey::ID),
					Cerb_ORMHelper::qstr('email'),
					$oper,
					Cerb_ORMHelper::qstr($value)
				);
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY), '%s'), self::getPrimaryKey());
				break;
			
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
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
			case SearchFields_GpgPublicKey::ID:
				$models = DAO_GpgPublicKey::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'gpg_public_key', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'gpg_public_key', 'name', $translate->_('common.name'), null, true),
			self::FINGERPRINT => new DevblocksSearchField(self::FINGERPRINT, 'gpg_public_key', 'fingerprint', $translate->_('common.fingerprint'), null, true),
			self::EXPIRES_AT => new DevblocksSearchField(self::EXPIRES_AT, 'gpg_public_key', 'expires_at', $translate->_('common.expires'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'gpg_public_key', 'updated_at', $translate->_('common.updated'), null, true),

			self::VIRTUAL_UID => new DevblocksSearchField(self::VIRTUAL_UID, '*', 'uid', $translate->_('uid'), null, false),
			self::VIRTUAL_UID_NAME => new DevblocksSearchField(self::VIRTUAL_UID_NAME, '*', 'uid_name', $translate->_('uid.name'), null, false),
			self::VIRTUAL_UID_EMAIL => new DevblocksSearchField(self::VIRTUAL_UID_EMAIL, '*', 'uid_email', $translate->_('uid.email'), null, false),
			
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

class Model_GpgPublicKey extends DevblocksRecordModel {
	public $id;
	public $name;
	public $fingerprint;
	public $key_text;
	public $expires_at;
	public $updated_at;
};

class View_GpgPublicKey extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'gpgpublickey';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('PGP Public Keys');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_GpgPublicKey::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_GpgPublicKey::NAME,
			SearchFields_GpgPublicKey::FINGERPRINT,
			SearchFields_GpgPublicKey::EXPIRES_AT,
			SearchFields_GpgPublicKey::UPDATED_AT,
		);
		$this->addColumnsHidden(array(
			SearchFields_GpgPublicKey::VIRTUAL_CONTEXT_LINK,
			SearchFields_GpgPublicKey::VIRTUAL_HAS_FIELDSET,
			SearchFields_GpgPublicKey::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_GpgPublicKey::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_GpgPublicKey');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_GpgPublicKey', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_GpgPublicKey', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Virtuals
				case SearchFields_GpgPublicKey::VIRTUAL_CONTEXT_LINK:
				case SearchFields_GpgPublicKey::VIRTUAL_HAS_FIELDSET:
				case SearchFields_GpgPublicKey::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_GPG_PUBLIC_KEY;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
//			case SearchFields_GpgPublicKey::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
//				break;

//			case SearchFields_GpgPublicKey::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
//				break;
				
			case SearchFields_GpgPublicKey::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_GpgPublicKey::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_GpgPublicKey::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_GpgPublicKey::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_GpgPublicKey::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'expires' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_GpgPublicKey::EXPIRES_AT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_GpgPublicKey::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_GPG_PUBLIC_KEY],
					]
				),
			'fingerprint' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_GpgPublicKey::FINGERPRINT),
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_GpgPublicKey::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_GpgPublicKey::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'uid' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_GpgPublicKey::VIRTUAL_UID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'uid.name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_GpgPublicKey::VIRTUAL_UID_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'uid.email' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_GpgPublicKey::VIRTUAL_UID_EMAIL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_GpgPublicKey::UPDATED_AT),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_GpgPublicKey::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fingerprint':
				return DevblocksSearchCriteria::getTextParamFromTokens(SearchFields_GpgPublicKey::FINGERPRINT, $tokens);
				break;
				
			case 'uid':
				return DevblocksSearchCriteria::getTextParamFromTokens(SearchFields_GpgPublicKey::VIRTUAL_UID, $tokens);
				break;
				
			case 'uid.name':
				return DevblocksSearchCriteria::getTextParamFromTokens(SearchFields_GpgPublicKey::VIRTUAL_UID_NAME, $tokens);
				break;
				
			case 'uid.email':
				return DevblocksSearchCriteria::getTextParamFromTokens(SearchFields_GpgPublicKey::VIRTUAL_UID_EMAIL, $tokens);
				break;
				
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/gpg_public_key/view.tpl');
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
			case SearchFields_GpgPublicKey::VIRTUAL_UID:
				echo DevblocksPlatform::strEscapeHtml('uid ');
				$this->_renderCriteriaParamString($param,[]);
				break;
				
			case SearchFields_GpgPublicKey::VIRTUAL_UID_NAME:
				echo DevblocksPlatform::strEscapeHtml('uid.name ');
				$this->_renderCriteriaParamString($param,[]);
				break;
				
			case SearchFields_GpgPublicKey::VIRTUAL_UID_EMAIL:
				echo DevblocksPlatform::strEscapeHtml('uid.email ');
				$this->_renderCriteriaParamString($param,[]);
				break;
				
			case SearchFields_GpgPublicKey::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_GpgPublicKey::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_GpgPublicKey::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_GpgPublicKey::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_GpgPublicKey::FINGERPRINT:
			case SearchFields_GpgPublicKey::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_GpgPublicKey::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_GpgPublicKey::EXPIRES_AT:
			case SearchFields_GpgPublicKey::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_GpgPublicKey::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_GpgPublicKey::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_GpgPublicKey::VIRTUAL_WATCHERS:
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

class Context_GpgPublicKey extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextUri { // IDevblocksContextImport
	const ID = CerberusContexts::CONTEXT_GPG_PUBLIC_KEY;
	const URI = 'gpg_public_key';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_GpgPublicKey::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=gpg_public_key&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_GpgPublicKey();
		
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
		
		$properties['fingerprint'] = array(
			'label' => mb_ucfirst($translate->_('common.fingerprint')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->fingerprint,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($gpg_public_key = DAO_GpgPublicKey::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($gpg_public_key->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $gpg_public_key->id,
			'name' => $gpg_public_key->name,
			'permalink' => $url,
			'updated' => $gpg_public_key->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'fingerprint',
			'expires_at',
			'updated_at',
		);
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_GpgPublicKey::getByFingerprint($alias)))
			return $model->id;
		
		return null;
	}
	
	function autocompleteUri($term, $uri_params = null): array {
		$where_sql = sprintf("%s LIKE %s", DAO_GpgPublicKey::NAME, Cerb_ORMHelper::qstr('%' . $term . '%'));
		
		$public_keys = DAO_GpgPublicKey::getWhere(
			$where_sql,
			null,
			null,
			25
		);
		
		if(!is_iterable($public_keys))
			return [];
		
		return array_map(
			function ($public_key) {
				return [
					'caption' => $public_key->name,
					'snippet' => $public_key->fingerprint,
					'description' => DevblocksPlatform::strEscapeHtml($public_key->name . ' (' . $public_key->fingerprint . ')'),
				];
			},
			$public_keys
		);
	}
	
	function getContext($gpg_public_key, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Gpg Public Key:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY);

		// Polymorph
		if(is_numeric($gpg_public_key)) {
			$gpg_public_key = DAO_GpgPublicKey::get($gpg_public_key);
		} elseif($gpg_public_key instanceof Model_GpgPublicKey) {
			// It's what we want already.
		} elseif(is_array($gpg_public_key)) {
			$gpg_public_key = Cerb_ORMHelper::recastArrayToModel($gpg_public_key, 'Model_GpgPublicKey');
		} else {
			$gpg_public_key = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'expires_at' => $prefix.$translate->_('common.expires'),
			'fingerprint' => $prefix.$translate->_('common.fingerprint'),
			'id' => $prefix.$translate->_('common.id'),
			'key_text' => $prefix.$translate->_('common.key'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'expires_at' => Model_CustomField::TYPE_DATE,
			'fingerprint' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'key' => Model_CustomField::TYPE_MULTI_LINE,
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
		
		$token_values['_context'] = Context_GpgPublicKey::ID;
		$token_values['_type'] = Context_GpgPublicKey::URI;
		
		$token_values['_types'] = $token_types;
		
		if($gpg_public_key) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $gpg_public_key->name;
			$token_values['expires_at'] = $gpg_public_key->expires_at;
			$token_values['fingerprint'] = $gpg_public_key->fingerprint;
			$token_values['id'] = $gpg_public_key->id;
			$token_values['key_text'] = $gpg_public_key->key_text;
			$token_values['name'] = $gpg_public_key->name;
			$token_values['updated_at'] = $gpg_public_key->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($gpg_public_key, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=gpg_public_key&id=%d-%s",$gpg_public_key->id, DevblocksPlatform::strToPermalink($gpg_public_key->name)), true);
		}
		
		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'expires_at' => DAO_GpgPublicKey::EXPIRES_AT,
			'fingerprint' => DAO_GpgPublicKey::FINGERPRINT,
			'id' => DAO_GpgPublicKey::ID,
			'key_text' => DAO_GpgPublicKey::KEY_TEXT,
			'links' => '_links',
			'name' => DAO_GpgPublicKey::NAME,
			'updated_at' => DAO_GpgPublicKey::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['expires_at']['notes'] = "The expiration date of the public key";
		$keys['fingerprint']['notes'] = "The fingerprint of the public key";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
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
		
		$context = CerberusContexts::CONTEXT_GPG_PUBLIC_KEY;
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
		$view->name = 'Gpg Public Key';
		/*
		$view->addParams(array(
			SearchFields_GpgPublicKey::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_GpgPublicKey::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_GpgPublicKey::UPDATED_AT;
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
		$view->name = 'Gpg Public Key';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_GpgPublicKey::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_GPG_PUBLIC_KEY;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(is_numeric($context_id) && strlen($context_id) < 16) {
				$model = DAO_GpgPublicKey::get($context_id);
				
			} elseif (is_string($context_id)) {
				if(false != ($model = DAO_GpgPublicKey::getByFingerprint($context_id)))
					$context_id = $model->id;
			}
			
			if(!$model)
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!Context_GpgPublicKey::isWriteableByActor($model, $active_worker))
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
			$tpl->display('devblocks:cerberusweb.core::internal/gpg_public_key/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
