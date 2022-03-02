<?php
class DAO_CustomFieldset extends Cerb_ORMHelper {
	const CONTEXT = 'context';
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const UPDATED_AT = 'updated_at';

	const CACHE_ALL = 'ch_CustomFieldsets';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
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
		// int(10) unsigned
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
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO custom_fieldset () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		self::_updateAbstract($context, $ids, $fields);
		
		parent::_update($ids, 'custom_fieldset', $fields);
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('custom_fieldset', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;
		
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
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CustomFieldset[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, context, owner_context, owner_context_id, updated_at ".
			"FROM custom_fieldset ".
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
	 * @return Model_CustomFieldset
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = DAO_CustomFieldset::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::CACHE_ALL))) {
			$objects = DAO_CustomFieldset::getWhere(
				null,
				DAO_CustomFieldset::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($objects))
				return false;
			
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}
	
	static function getByContext($context) {
		$cfieldsets = DAO_CustomFieldset::getAll();
		$results = array();
		
		foreach($cfieldsets as $cfieldset_id => $cfieldset) { /* @var $cg_group Model_CustomFieldset */
			if(0 == strcasecmp($cfieldset->context, $context))
				$results[$cfieldset_id] = $cfieldset;
		}
		
		return $results;
	}
	
	static function getUsableByActorByContext($actor, $context, $with_admins=true) {
		$fieldsets = self::getByContext($context);
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor, false)))
			return [];
		
		$fieldsets = array_filter($fieldsets, function($fieldset) use ($actor) { /* @var $fieldset Model_CustomFieldset */
			switch($fieldset->owner_context) {
				case CerberusContexts::CONTEXT_APPLICATION:
					// Everyone can use global custom fields
					return true;
					break;
					
				case CerberusContexts::CONTEXT_BOT:
					// Same bot
					if($actor->_context == CerberusContexts::CONTEXT_BOT && $actor->id == $fieldset->owner_context_id)
						return true;
					
					// Can edit the bot
					//return CerberusContexts::isWriteableByActor($fieldset->owner_context, $fieldset->owner_context_id, $actor);
					break;
					
				case CerberusContexts::CONTEXT_GROUP:
					// Member of the group
					return CerberusContexts::isReadableByActor($fieldset->owner_context, $fieldset->owner_context_id, $actor);
					break;
				
				case CerberusContexts::CONTEXT_ROLE:
					// Member of the role
					return CerberusContexts::isReadableByActor($fieldset->owner_context, $fieldset->owner_context_id, $actor);
					break;
					
				case CerberusContexts::CONTEXT_WORKER:
					// Is the same worker
					if($actor->_context == CerberusContexts::CONTEXT_WORKER && $actor->id == $fieldset->owner_context_id)
						return true;
					
					return false;
					break;
			}
		});
		
		return $fieldsets;
	}
	
	static function addToContext($fieldset_ids, $context, $context_ids) {
		CerberusContexts::checkpointChanges($context, $context_ids);
		
		$db = DevblocksPlatform::services()->database();
		
		$fieldsets = DAO_CustomFieldset::getByContext($context);
		
		if(!is_array($context_ids))
			$context_ids = [$context_ids];
		
		$values = [];
		
		foreach($fieldset_ids as $fieldset_id) {
			if(!array_key_exists($fieldset_id, $fieldsets))
				continue;
			
			foreach($context_ids as $context_id) {
				$values[] = sprintf("(%d, %s, %d)",
					$fieldset_id,
					$db->qstr($context),
					$context_id
				);
			}
		}
		
		if($values) {
			$sql = "REPLACE INTO context_to_custom_fieldset (custom_fieldset_id, context, context_id) VALUES " . implode(',', $values);
			$db->ExecuteMaster($sql);
		}
	}
	
	static function addByField($field_id) {
		$db = DevblocksPlatform::services()->database();
		
		if(false == ($custom_field = DAO_CustomField::get($field_id)))
			return;
		
		$sql = sprintf("INSERT IGNORE INTO context_to_custom_fieldset (context, context_id, custom_fieldset_id) ".
			"SELECT context, context_id, %d AS custom_fieldset_id FROM %s WHERE field_id = %d",
			$custom_field->custom_fieldset_id,
			$db->escape(DAO_CustomFieldValue::getValueTableName($field_id)),
			$field_id
		);
		$db->ExecuteMaster($sql);
	}
	
	static function removeFromContext($ids, $context, $context_id) {
		CerberusContexts::checkpointChanges($context, [$context_id]);
		
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = [$ids];
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids))
			return;
		
		$sql = sprintf("DELETE FROM context_to_custom_fieldset WHERE context = %s AND context_id = %d AND custom_fieldset_id IN (%s)",
			$db->qstr($context),
			$context_id,
			implode(',', $ids)
		);
		$db->ExecuteMaster($sql);
		
		// Also remove values from these fieldsets
		DAO_CustomFieldValue::deleteByContextIds($context, $context_id, $ids);
	}
	
	static function getUsedByContext($context, $context_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT custom_fieldset_id FROM context_to_custom_fieldset WHERE context = %s AND context_id = %d",
			$db->qstr($context),
			$context_id
		);
		
		$results = $db->GetArrayReader($sql);
		
		return DAO_CustomFieldset::getIds(array_column($results, 'custom_fieldset_id'));
	}
	
	static function getByOwner($owner_context, $owner_context_id=0) {
		$cfieldsets = DAO_CustomFieldset::getAll();
		$results = array();
		
		foreach($cfieldsets as $cfieldset_id => $cfieldset) { /* @var $cg_group Model_CustomFieldset */
			if(0 == strcasecmp($cfieldset->owner_context, $owner_context)
				&& intval($cfieldset->owner_context_id) == intval($owner_context_id))
				$results[$cfieldset_id] = $cfieldset;
		}
		
		return $results;
	}
	
	public static function getByFieldIds(array $ids) {
		$custom_fields = DAO_CustomField::getIds($ids);
		
		$custom_fieldset_ids = array_unique(
			array_column(
				DevblocksPlatform::objectsToArrays($custom_fields), 
				'custom_fieldset_id'
			)
		);
		
		return DAO_CustomFieldset::getIds($custom_fieldset_ids);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_CustomFieldset[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_CustomFieldset();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->context = $row['context'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('custom_fieldset');
	}
	
	static public function count($owner_context, $owner_context_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT count(*) FROM custom_fieldset ".
			"WHERE owner_context = %s AND owner_context_id = %d",
			$db->qstr($owner_context),
			$owner_context_id
		));
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);

		// Delete custom fields in these fieldsets

		foreach($ids as $id) {
			if(null == ($fieldset = DAO_CustomFieldset::get($id)))
				continue;
			
			$custom_fields = $fieldset->getCustomFields();
			DAO_CustomField::delete(array_keys($custom_fields));
		}
		
		$db->ExecuteMaster(sprintf("DELETE FROM context_to_custom_fieldset WHERE custom_fieldset_id IN (%s)", $ids_list));
		$db->ExecuteMaster(sprintf("DELETE FROM custom_fieldset WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	static function deleteByOwner($owner_context, $owner_context_ids) {
		if(!is_array($owner_context_ids))
			$owner_context_ids = array($owner_context_ids);
		
		foreach($owner_context_ids as $owner_context_id) {
			$fieldsets = DAO_CustomFieldset::getByOwner($owner_context, $owner_context_id);
			DAO_CustomFieldset::delete(array_keys($fieldsets));
		}
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CustomFieldset::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CustomFieldset', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"custom_fieldset.id as %s, ".
			"custom_fieldset.name as %s, ".
			"custom_fieldset.context as %s, ".
			"custom_fieldset.updated_at as %s, ".
			"custom_fieldset.owner_context as %s, ".
			"custom_fieldset.owner_context_id as %s ",
				SearchFields_CustomFieldset::ID,
				SearchFields_CustomFieldset::NAME,
				SearchFields_CustomFieldset::CONTEXT,
				SearchFields_CustomFieldset::UPDATED_AT,
				SearchFields_CustomFieldset::OWNER_CONTEXT,
				SearchFields_CustomFieldset::OWNER_CONTEXT_ID
			);
			
		$join_sql = "FROM custom_fieldset ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_CustomFieldset');
	
		return array(
			'primary_table' => 'custom_fieldset',
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
			SearchFields_CustomFieldset::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
	
	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
		$cache->removeByTags(['schema_records']);
	}

};

class SearchFields_CustomFieldset extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const CONTEXT = 'c_context';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	const UPDATED_AT = 'c_updated_at';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_OWNER = '*_owner';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'custom_fieldset.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CUSTOM_FIELDSET => new DevblocksSearchFieldContextKeys('custom_fieldset.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'custom_fieldset.owner_context', 'custom_fieldset.owner_context_id');
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
			case 'owner':
				$key = 'owner';
				$search_key = 'owner';
				$owner_field = $search_fields[SearchFields_CustomFieldset::OWNER_CONTEXT];
				$owner_id_field = $search_fields[SearchFields_CustomFieldset::OWNER_CONTEXT_ID];
				
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
			case SearchFields_CustomFieldset::CONTEXT:
				return parent::_getLabelsForKeyContextValues();
				break;
				
			case SearchFields_CustomFieldset::ID:
				$models = DAO_CustomFieldset::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'custom_fieldset', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'custom_fieldset', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'custom_fieldset', 'context', $translate->_('common.context'), null, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'custom_fieldset', 'owner_context', $translate->_('common.owner_context'), null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'custom_fieldset', 'owner_context_id', $translate->_('common.owner_context_id'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'custom_fieldset', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_CustomFieldset {
	public $id;
	public $name;
	public $context;
	public $owner_context;
	public $owner_context_id;
	public $updated_at;

	/**
	 *
	 * @return Model_CustomField[]
	 */
	function getCustomFields() {
		if(!$this->id)
			return [];
		
		$fields = DAO_CustomField::getAll();
		$results = array();
		
		foreach($fields as $field_id => $field) {
			if($field->custom_fieldset_id == $this->id)
				$results[$field_id] = $field;
		}
		
		return $results;
	}
	
	function getOwnerDictionary() {
		$labels = $values = [];
		CerberusContexts::getContext($this->owner_context, $this->owner_context_id, $labels, $values);
		return new DevblocksDictionaryDelegate($values);
	}
};

class View_CustomFieldset extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'custom_fieldsets';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Custom Fieldsets');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CustomFieldset::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CustomFieldset::NAME,
			SearchFields_CustomFieldset::CONTEXT,
			SearchFields_CustomFieldset::VIRTUAL_OWNER,
			SearchFields_CustomFieldset::UPDATED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_CustomFieldset::ID,
			SearchFields_CustomFieldset::OWNER_CONTEXT,
			SearchFields_CustomFieldset::OWNER_CONTEXT_ID,
			SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_CustomFieldset::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_CustomFieldset');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_CustomFieldset', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CustomFieldset', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_CustomFieldset::CONTEXT:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK:
				case SearchFields_CustomFieldset::VIRTUAL_OWNER:
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
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_CustomFieldset::CONTEXT:
				$label_map = function(array $values) use ($column) {
					return SearchFields_CustomField::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'contexts[]');
				break;
			
			case SearchFields_CustomFieldset::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_CustomFieldset::OWNER_CONTEXT, DAO_CustomFieldset::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
			
			default:
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_CustomFieldset::getFields();
		
		$contexts = array_column(DevblocksPlatform::objectsToArrays(Extension_DevblocksContext::getAll(false)), 'name', 'id');
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomFieldset::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'context' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomFieldset::CONTEXT),
					'examples' => [
						['type' => 'list', 'values' => $contexts],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_CustomFieldset::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomFieldset::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'score' => 2000,
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:custom_fieldset by:name~25 query:(name:{{term}}*) format:dictionaries',
						'key' => 'name',
						'limit' => 25,
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_CustomFieldset::UPDATED_AT),
				),
		);
		
		// Add dynamic owner.* fields
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('owner', $fields, 'owner', SearchFields_CustomFieldset::VIRTUAL_OWNER);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK);
		
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
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'owner', SearchFields_CustomFieldset::VIRTUAL_OWNER);
					
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

		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/custom_fieldsets/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_CustomFieldset::CONTEXT:
				$label_map = SearchFields_CustomFieldset::getLabelsForKeyValues($field, $values);
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
			case SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_CustomFieldset::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners', 'Owner matches');
				break;
		}
	}

	function getFields() {
		return SearchFields_CustomFieldset::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CustomFieldset::NAME:
			case SearchFields_CustomFieldset::OWNER_CONTEXT:
			case SearchFields_CustomFieldset::OWNER_CONTEXT_ID:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CustomFieldset::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_CustomFieldset::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_CustomFieldset::CONTEXT:
				$in_contexts = DevblocksPlatform::importGPC($_POST['contexts'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$in_contexts);
				break;
				
			case SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_CustomFieldset::VIRTUAL_OWNER:
				$owner_contexts = DevblocksPlatform::importGPC($_POST['owner_context'] ?? null, 'array', []);
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_CustomFieldset extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.custom_fieldset';
	const URI = 'custom_fieldset';
	
	static function isReadableByActor($models, $actor, $ignore_admins=false) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $models, 'owner_', $ignore_admins);
	}
	
	static function isWriteableByActor($models, $actor, $ignore_admins=false) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $models, 'owner_', $ignore_admins);
	}
	
	static function isDeletableByActor($models, $actor, $ignore_admins=false) {
		return self::isWriteableByActor($models, $actor, $ignore_admins);
	}
	
	function getRandom() {
		return DAO_CustomFieldset::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=custom_fieldset&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_CustomFieldset();
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET,
			],
		);
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			]
		);
		
		$properties['updated_date'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(false == ($cfieldset = DAO_CustomFieldset::get($context_id)))
			return null;
			
		return [
			'id' => $context_id,
			'name' => $cfieldset->name,
			'permalink' => '', //$url_writer->writeNoProxy('c=tasks&action=display&id='.$task->id, true),
			'updated' => $cfieldset->updated_at,
		];
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() {
		return array(
			'context',
			'owner__label',
			'updated_at',
		);
	}
	
	function getContext($cfieldset, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Custom Fieldsets:';
		
		$translate = DevblocksPlatform::getTranslationService();

		// Polymorph
		if(is_numeric($cfieldset)) {
			$cfieldset = DAO_CustomFieldset::get($cfieldset);
		} elseif($cfieldset instanceof Model_CustomFieldset) {
		// It's what we want already.
		} elseif(is_array($cfieldset)) {
			$cfieldset = Cerb_ORMHelper::recastArrayToModel($cfieldset, 'Model_CustomFieldset');
		} else {
			$cfieldset = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'context' => $prefix.$translate->_('common.context'),
			'name' => $prefix.$translate->_('common.name'),
			'owner__label' => $prefix.$translate->_('common.owner'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'context' => Model_CustomField::TYPE_MULTI_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'owner__label' => 'context_url',
			'updated_at' => Model_CustomField::TYPE_DATE,
		);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = Context_CustomFieldset::ID;
		$token_values['_type'] = Context_CustomFieldset::URI;
		
		$token_values['_types'] = $token_types;
		
		if($cfieldset) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $cfieldset->name;
			$token_values['id'] = $cfieldset->id;
			$token_values['context'] = $cfieldset->context;
			$token_values['name'] = $cfieldset->name;
			$token_values['updated_at'] = $cfieldset->updated_at;
			
			// For lazy loading
			$token_values['owner__context'] = $cfieldset->owner_context;
			$token_values['owner_id'] = $cfieldset->owner_context_id;
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=custom_fieldset&id=%d-%s",$cfieldset->id, DevblocksPlatform::strToPermalink($cfieldset->name)), true);
		}

		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'context' => DAO_CustomFieldset::CONTEXT,
			'id' => DAO_CustomFieldset::ID,
			'links' => '_links',
			'name' => DAO_CustomFieldset::NAME,
			'owner__context' => DAO_CustomFieldset::OWNER_CONTEXT,
			'owner_id' => DAO_CustomFieldset::OWNER_CONTEXT_ID,
			'updated_at' => DAO_CustomFieldset::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['context']['notes'] = "The [record type](/docs/records/types/) of the fieldset";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['custom_fields'] = [
			'label' => 'Custom Fields',
			'type' => 'Records',
		];
		
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'custom_fields':
				$custom_fieldset = DAO_CustomFieldset::get($context_id);
				$custom_fields = $custom_fieldset->getCustomFields();
				$cfield_values = [
					'custom_fields' => [],
				];
				
				foreach($custom_fields as $cfield) {
					$merge_labels = $merge_values = [];
					CerberusContexts::getContext(CerberusContexts::CONTEXT_CUSTOM_FIELD, $cfield, $merge_labels, $merge_values, null, true, true);
					$cfield_values['custom_fields'][] = $merge_values;
				}
				
				if(!empty($cfield_values['custom_fields']))
					$values = array_merge($values, $cfield_values);
				break;
				
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
		$view->name = 'Custom Fieldsets';
		
		$params_required = array();

		// Restrict contexts
		if(isset($_REQUEST['link_context'])) {
			$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
			if(!empty($link_context)) {
				$params_required['_ownership'] = new DevblocksSearchCriteria(SearchFields_CustomFieldset::CONTEXT, DevblocksSearchCriteria::OPER_EQ, $link_context);
			}
		}
		
		$view->addParamsRequired($params_required, true);
		
		$view->renderSortBy = SearchFields_CustomFieldset::NAME;
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
		$view->name = 'Custom Fieldsets';

		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;
		
		$tpl->assign('view_id', $view_id);
		
		if($context_id) {
			if(false == ($model = DAO_CustomFieldset::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
		} else {
			$owner_context = DevblocksPlatform::importGPC($_REQUEST['owner_context'] ?? null, 'string','');
			$owner_context_id = DevblocksPlatform::importGPC($_REQUEST['owner_context_id'] ?? null, 'integer',0);
		
			$model = new Model_CustomFieldset();
			$model->id = 0;
			$model->owner_context = !empty($owner_context) ? $owner_context : '';
			$model->owner_context_id = $owner_context_id;
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if($model->id && !Context_CustomFieldset::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
			}
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$custom_fields = $model->getCustomFields();
			$tpl->assign('custom_fields', $custom_fields);
			
			// Contexts
			
			$contexts = Extension_DevblocksContext::getAll(false, array('custom_fields'));
			$tpl->assign('contexts', $contexts);
			
			$link_contexts = Extension_DevblocksContext::getAll(false, array('workspace'));
			$tpl->assign('link_contexts', $link_contexts);
			
			// Owner
			$owners_menu = Extension_DevblocksContext::getOwnerTree();
			$tpl->assign('owners_menu', $owners_menu);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/custom_fieldsets/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
