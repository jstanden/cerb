<?php
class DAO_CustomFieldset extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const CONTEXT = 'context';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';

	const CACHE_ALL = 'ch_CustomFieldsets';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO custom_fieldset () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'custom_fieldset', $fields);
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('custom_fieldset', $fields, $where);
	}
	
	public static function linkToContextByFieldIds($context, $context_id, $field_ids) {
		$all_fields = DAO_CustomField::getAll();
		$all_fieldsets = DAO_CustomFieldset::getAll();
		
		$link_fieldset_ids = array();

		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($all_fields[$field_id]))
				continue;
			
			$fieldset_id = $all_fields[$field_id]->custom_fieldset_id;
			
			if(!isset($all_fieldsets[$fieldset_id]))
				continue;
			
			$link_fieldset_ids[$fieldset_id] = true;
		}
		
		if(!empty($link_fieldset_ids))
		foreach(array_keys($link_fieldset_ids) as $fieldset_id) {
			DAO_ContextLink::setLink($context, $context_id, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $fieldset_id);
		}
	}
	
	static function linkToContextsByFieldValues($fieldset_id, $field_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$temp_table = '_links_' . uniqid();
		
		// Generate a temporary table
		$db->ExecuteMaster(sprintf("CREATE TEMPORARY TABLE %s (context VARCHAR(128), context_id INT)", $temp_table));
		
		// Find any contexts linking to it
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO %s SELECT context, context_id FROM custom_field_stringvalue WHERE field_id = %d", $temp_table, $field_id));
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO %s SELECT context, context_id FROM custom_field_numbervalue WHERE field_id = %d", $temp_table, $field_id));
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO %s SELECT context, context_id FROM custom_field_clobvalue WHERE field_id = %d", $temp_table, $field_id));
		
		// Link from the records to the fieldset
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
			"SELECT context, context_id, %s, %s FROM %s",
			$db->qstr(CerberusContexts::CONTEXT_CUSTOM_FIELDSET),
			$db->qstr($fieldset_id),
			$temp_table
		));
		
		// And link back in the other direction
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO context_link (to_context, to_context_id, from_context, from_context_id) ".
			"SELECT context, context_id, %s, %s FROM %s",
			$db->qstr(CerberusContexts::CONTEXT_CUSTOM_FIELDSET),
			$db->qstr($fieldset_id),
			$temp_table
		));
		
		// Drop the temp table
		$db->ExecuteMaster(sprintf("DROP TABLE %s", $temp_table));
		
		return TRUE;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CustomFieldset[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, context, owner_context, owner_context_id ".
			"FROM custom_fieldset ".
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
		$cache = DevblocksPlatform::getCacheService();
		
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
	
	/**
	 *
	 * @param string $context
	 * @param integer $context_id
	 * @return Model_CustomFieldset[]
	 */
	static function getByContextLink($context, $context_id) {
		$cfieldsets = DAO_CustomFieldset::getAll();
		$context_values = DAO_ContextLink::getContextLinks($context, $context_id, CerberusContexts::CONTEXT_CUSTOM_FIELDSET);
		$results = array();
		
		if(!isset($context_values[$context_id]))
			return $results;
		
		if(!is_array($context_values[$context_id]))
			return $results;
		
		foreach($context_values[$context_id] as $cfieldset_id => $ctx_pair) {
			if(isset($cfieldsets[$cfieldset_id]))
				$results[$cfieldset_id] = $cfieldsets[$cfieldset_id];
		}
		
		return $results;
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
	
	/**
	 * @param resource $rs
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
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('custom_fieldset');
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);

		// Delete custom fields in these fieldsets

		foreach($ids as $id) {
			if(null == ($fieldset = DAO_CustomFieldset::get($id)))
				continue;
			
			foreach($fieldset->getCustomFields() as $field_id => $field) {
				DAO_CustomField::delete($field_id);
			}
		}
		
		$db->ExecuteMaster(sprintf("DELETE FROM custom_fieldset WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerberusweb.contexts.custom_fieldset',
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
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_CustomFieldset', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"custom_fieldset.id as %s, ".
			"custom_fieldset.name as %s, ".
			"custom_fieldset.context as %s, ".
			"custom_fieldset.owner_context as %s, ".
			"custom_fieldset.owner_context_id as %s ",
				SearchFields_CustomFieldset::ID,
				SearchFields_CustomFieldset::NAME,
				SearchFields_CustomFieldset::CONTEXT,
				SearchFields_CustomFieldset::OWNER_CONTEXT,
				SearchFields_CustomFieldset::OWNER_CONTEXT_ID
			);
			
		$join_sql = "FROM custom_fieldset ".
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.custom_fieldset' AND context_link.to_context_id = custom_fieldset.id) " : " ")
			;
		
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_CustomFieldset');
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
	
		array_walk_recursive(
			$params,
			array('DAO_CustomFieldset', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'custom_fieldset',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;
		$from_index = 'custom_fieldset.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK:
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
			
			case SearchFields_CustomFieldset::VIRTUAL_OWNER:
				if(!is_array($param->value))
					break;
				
				$wheres = array();
				$args['has_multiple_values'] = true;
					
				foreach($param->value as $owner_context) {
					@list($context, $context_id) = explode(':', $owner_context);
					
					if(empty($context))
						continue;
					
					if(!empty($context_id)) {
						$wheres[] = sprintf("(custom_fieldset.owner_context = %s AND custom_fieldset.owner_context_id = %d)",
							Cerb_ORMHelper::qstr($context),
							$context_id
						);
						
					} else {
						$wheres[] = sprintf("(custom_fieldset.owner_context = %s)",
							Cerb_ORMHelper::qstr($context)
						);
					}
				}
				
				if(!empty($wheres))
					$args['where_sql'] .= 'AND ' . implode(' OR ', $wheres);
				
				break;
		}
	}
	
	/**
	 * Enter description here...
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
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY custom_fieldset.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_CustomFieldset::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT custom_fieldset.id) " : "SELECT COUNT(custom_fieldset.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}

};

class SearchFields_CustomFieldset extends DevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const CONTEXT = 'c_context';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
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
		if('cf_' == substr($param->field, 0, 3)) {
			return self::_getWhereSQLFromCustomFields($param);
		} else {
			return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
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
			self::ID => new DevblocksSearchField(self::ID, 'custom_fieldset', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'custom_fieldset', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'custom_fieldset', 'context', $translate->_('common.context'), null, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'custom_fieldset', 'owner_context', $translate->_('common.owner_context'), null, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'custom_fieldset', 'owner_context_id', $translate->_('common.owner_context_id'), null, true),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null, null, false),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null, null, false),
			
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

	/**
	 *
	 * @return Model_CustomField[]
	 */
	function getCustomFields() {
		$fields = DAO_CustomField::getAll();
		$results = array();
		
		foreach($fields as $field_id => $field) {
			if($field->custom_fieldset_id == $this->id)
				$results[$field_id] = $field;
		}
		
		return $results;
	}
	
	function isReadableByWorker($worker) {
		if(is_a($worker, 'Model_Worker')) {
			// This is what we want
		} elseif (is_numeric($worker)) {
			if(null == ($worker = DAO_Worker::get($worker)))
				return false;
		} else {
			return false;
		}
		
		// Superusers can do anything
		if($worker->is_superuser)
			return true;
		
		switch($this->owner_context) {
			case CerberusContexts::CONTEXT_GROUP:
				if(in_array($this->owner_context_id, array_keys($worker->getMemberships())))
					return true;
				break;
				
			case CerberusContexts::CONTEXT_ROLE:
				if(in_array($this->owner_context_id, array_keys($worker->getRoles())))
					return true;
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				if($worker->id == $this->owner_context_id)
					return true;
				break;
				
			case CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT:
				return false;
				break;
				
			case CerberusContexts::CONTEXT_APPLICATION:
				return true;
				break;
		}
		
		return false;
	}
	
	function isWriteableByWorker($worker) {
		if(is_a($worker, 'Model_Worker')) {
			// This is what we want
		} elseif (is_numeric($worker)) {
			if(null == ($worker = DAO_Worker::get($worker)))
				return false;
		} else {
			return false;
		}
		
		// Superusers can do anything
		if($worker->is_superuser)
			return true;
		
		switch($this->owner_context) {
			case CerberusContexts::CONTEXT_GROUP:
				if(in_array($this->owner_context_id, array_keys($worker->getMemberships())))
					if($worker->isGroupManager($this->owner_context_id))
						return true;
				break;
				
			case CerberusContexts::CONTEXT_ROLE:
				if($worker->is_superuser)
					return true;
				break;
				
			case CerberusContexts::CONTEXT_WORKER:
				if($worker->id == $this->owner_context_id)
					return true;
				break;
				
			case CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT:
				return false;
				break;
				
			case CerberusContexts::CONTEXT_APPLICATION:
				return false;
				break;
		}
		
		return false;
	}
	
	function getOwnerDictionary() {
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
		);

		$this->addColumnsHidden(array(
			SearchFields_CustomFieldset::ID,
			SearchFields_CustomFieldset::OWNER_CONTEXT,
			SearchFields_CustomFieldset::OWNER_CONTEXT_ID,
			SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK,
		));
		
		$this->addParamsHidden(array(
			SearchFields_CustomFieldset::CONTEXT_LINK,
			SearchFields_CustomFieldset::CONTEXT_LINK_ID,
			SearchFields_CustomFieldset::ID,
			SearchFields_CustomFieldset::OWNER_CONTEXT,
			SearchFields_CustomFieldset::OWNER_CONTEXT_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CustomFieldset::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
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
					if('cf_' == substr($field_key,0,3))
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
				$label_map = array();
				$contexts = Extension_DevblocksContext::getAll(false);
				
				foreach($contexts as $k => $mft) {
					$label_map[$k] = $mft->name;
				}
				
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
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomFieldset::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'context' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomFieldset::CONTEXT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_CustomFieldset::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
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
			default:
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/custom_fieldsets/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CustomFieldset::NAME:
			case SearchFields_CustomFieldset::CONTEXT:
			case SearchFields_CustomFieldset::OWNER_CONTEXT:
			case SearchFields_CustomFieldset::OWNER_CONTEXT_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CustomFieldset::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_CustomFieldset::CONTEXT:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context.tpl');
				break;
				
			case SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_CustomFieldset::VIRTUAL_OWNER:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$roles = DAO_WorkerRole::getAll();
				$tpl->assign('roles', $roles);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_owner.tpl');
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_CustomFieldset::CONTEXT:
				$contexts = Extension_DevblocksContext::getAll(false);
				$strings = array();
				
				foreach($param->value as $context_id) {
					if(isset($contexts[$context_id])) {
						$strings[] = sprintf('<b>%s</b>',DevblocksPlatform::strEscapeHtml($contexts[$context_id]->name));
					}
				}
				
				echo implode(' or ', $strings);
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
			case SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_CustomFieldset::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners');
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
				
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_CustomFieldset::CONTEXT:
				@$in_contexts = DevblocksPlatform::importGPC($_REQUEST['contexts'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$in_contexts);
				break;
				
			case SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_CustomFieldset::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_CustomFieldset extends Extension_DevblocksContext {
	function getRandom() {
		return DAO_CustomFieldset::random();
	}
	
	function getMeta($context_id) {
		if(false == ($cfieldset = DAO_CustomFieldset::get($context_id)))
			return null;
			
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $context_id,
			'name' => $cfieldset->name,
			'permalink' => '', //$url_writer->writeNoProxy('c=tasks&action=display&id='.$task->id, true),
			'updated' => 0,
		);
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
			'name',
			'owner__label',
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
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'content' => Model_CustomField::TYPE_MULTI_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'owner__label' => 'context_url',
		);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;
		$token_values['_types'] = $token_types;
		
		if($cfieldset) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $cfieldset->name;
			$token_values['id'] = $cfieldset->id;
			$token_values['context'] = $cfieldset->context;
			$token_values['name'] = $cfieldset->name;
			
			// For lazy loading
			$token_values['owner__context'] = $cfieldset->owner_context;
			$token_values['owner_id'] = $cfieldset->owner_context_id;
		}

		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'custom_fields':
				$custom_fieldset = DAO_CustomFieldset::get($context_id);
				$custom_fields = $custom_fieldset->getCustomFields();
				$cfield_values = array(
					'custom_fields' => array(),
				);
				
				foreach($custom_fields as $cfield) {
					CerberusContexts::getContext(CerberusContexts::CONTEXT_CUSTOM_FIELD, $cfield, $merge_labels, $merge_values, null, true, true);
					$cfield_values['custom_fields'][] = $merge_values;
				}
				
				if(!empty($cfield_values['custom_fields']))
					$values = array_merge($values, $cfield_values);
				
				break;
				
			default:
				/*
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				*/
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
		$view->renderFilters = false;
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
				new DevblocksSearchCriteria(SearchFields_CustomFieldset::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_CustomFieldset::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
};
