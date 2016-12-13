<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class DAO_VirtualAttendant extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';
	const IS_DISABLED = 'is_disabled';
	const PARAMS_JSON = 'params_json';
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	
	const CACHE_ALL = 'ch_virtual_attendants';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!isset($fields[DAO_VirtualAttendant::CREATED_AT]))
			$fields[DAO_VirtualAttendant::CREATED_AT] = time();
		
		$sql = "INSERT INTO virtual_attendant () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'virtual_attendant', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.virtual_attendant.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('virtual_attendant', $fields, $where);
	}
	
	static function autocomplete($term) {
		$params = array(
			SearchFields_VirtualAttendant::NAME => new DevblocksSearchCriteria(SearchFields_VirtualAttendant::NAME, DevblocksSearchCriteria::OPER_LIKE, $term.'*'),
		);
		
		list($results, $null) = DAO_VirtualAttendant::search(
			array(),
			$params,
			25,
			0,
			SearchFields_VirtualAttendant::NAME,
			true,
			false
		);
		
		return DAO_VirtualAttendant::getIds(array_keys($results));
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_VirtualAttendant[]
	 */
	static function getWhere($where=null, $sortBy='name', $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, owner_context, owner_context_id, is_disabled, params_json, created_at, updated_at ".
			"FROM virtual_attendant ".
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
	 * @return Model_VirtualAttendant
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = DAO_VirtualAttendant::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}

	static function random() {
		return parent::_getRandom('virtual_attendant');
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if($nocache || null === ($objects = $cache->load(self::CACHE_ALL))) {
			$objects = DAO_VirtualAttendant::getWhere(
				null,
				DAO_VirtualAttendant::NAME,
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
	
	/**
	 *
	 * @param string $context
	 * @param integer $context_id
	 * @return Model_VirtualAttendant[]
	 */
	static function getByOwner($context, $context_id, $with_disabled=false) {
		$vas = DAO_VirtualAttendant::getAll();
		$results = array();

		foreach($vas as $va_id => $va) {
			if(!$with_disabled && $va->is_disabled)
				continue;
			
			if(CerberusContexts::isSameObject(array($context, $context_id), array($va->owner_context, $va->owner_context_id)))
				$results[$va_id] = $va;
		}

		return $results;
	}
	
	static function getReadableByActor($actor) {
		$vas = DAO_VirtualAttendant::getAll();
		$results = array();

		foreach($vas as $va_id => $va) {
			if(CerberusContexts::isReadableByActor($va->owner_context, $va->owner_context_id, $actor))
				$results[$va_id] = $va;
		}
		
		return $results;
	}
	
	static function getWriteableByActor($actor) {
		$vas = DAO_VirtualAttendant::getAll();
		$results = array();

		foreach($vas as $va_id => $va) {
			if(CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $va_id, $actor))
				$results[$va_id] = $va;
		}
		
		return $results;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_VirtualAttendant[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_VirtualAttendant();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$object->is_disabled = $row['is_disabled'] ? true : false;
			$object->created_at = $row['created_at'];
			$object->updated_at = $row['updated_at'];
			
			@$params = json_decode($row['params_json'], true);
			$object->params = $params ?: array();
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM virtual_attendant WHERE id IN (%s)", $ids_list));

		// Cascade
		if(is_array($ids))
		foreach($ids as $id)
			DAO_TriggerEvent::deleteByVirtualAttendant($id);
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	public static function deleteByOwner($context, $context_ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($context) || empty($context_ids))
			return false;
		
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		foreach($context_ids as $context_id) {
			$vas = DAO_VirtualAttendant::getByOwner($context, $context_id, true);
			
			if(is_array($vas) && !empty($vas))
				DAO_VirtualAttendant::delete(array_keys($vas));
		}
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_VirtualAttendant::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_VirtualAttendant', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"virtual_attendant.id as %s, ".
			"virtual_attendant.name as %s, ".
			"virtual_attendant.owner_context as %s, ".
			"virtual_attendant.owner_context_id as %s, ".
			"virtual_attendant.is_disabled as %s, ".
			"virtual_attendant.params_json as %s, ".
			"virtual_attendant.created_at as %s, ".
			"virtual_attendant.updated_at as %s ",
				SearchFields_VirtualAttendant::ID,
				SearchFields_VirtualAttendant::NAME,
				SearchFields_VirtualAttendant::OWNER_CONTEXT,
				SearchFields_VirtualAttendant::OWNER_CONTEXT_ID,
				SearchFields_VirtualAttendant::IS_DISABLED,
				SearchFields_VirtualAttendant::PARAMS_JSON,
				SearchFields_VirtualAttendant::CREATED_AT,
				SearchFields_VirtualAttendant::UPDATED_AT
			);
			
		$join_sql = "FROM virtual_attendant ";
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_VirtualAttendant');
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
		);
	
		array_walk_recursive(
			$params,
			array('DAO_VirtualAttendant', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'virtual_attendant',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT;
		$from_index = 'virtual_attendant.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_VirtualAttendant::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_OWNER:
				if(!is_array($param->value))
					break;
				
				$wheres = array();
					
				foreach($param->value as $owner_context) {
					@list($context, $context_id) = explode(':', $owner_context);
					
					if(empty($context))
						continue;
					
					if(!empty($context_id)) {
						$wheres[] = sprintf("(virtual_attendant.owner_context = %s AND virtual_attendant.owner_context_id = %d)",
							Cerb_ORMHelper::qstr($context),
							$context_id
						);
						
					} else {
						$wheres[] = sprintf("(virtual_attendant.owner_context = %s)",
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
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_VirtualAttendant::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(virtual_attendant.id) ".
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

class SearchFields_VirtualAttendant extends DevblocksSearchFields {
	const ID = 'v_id';
	const NAME = 'v_name';
	const OWNER_CONTEXT = 'v_owner_context';
	const OWNER_CONTEXT_ID = 'v_owner_context_id';
	const IS_DISABLED = 'v_is_disabled';
	const PARAMS_JSON = 'v_params_json';
	const CREATED_AT = 'v_created_at';
	const UPDATED_AT = 'v_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER = '*_owner';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'virtual_attendant.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT => new DevblocksSearchFieldContextKeys('virtual_attendant.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, self::getPrimaryKey());
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
			self::ID => new DevblocksSearchField(self::ID, 'virtual_attendant', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'virtual_attendant', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'virtual_attendant', 'owner_context', $translate->_('common.owner_context'), null, false),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'virtual_attendant', 'owner_context_id', $translate->_('common.owner_context_id'), null, false),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'virtual_attendant', 'is_disabled', $translate->_('common.disabled'), Model_CustomField::TYPE_CHECKBOX, true),
			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'virtual_attendant', 'params_json', $translate->_('common.parameters'), null, false),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'virtual_attendant', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'virtual_attendant', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner'), null, false),
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

class Model_VirtualAttendant {
	public $id;
	public $name;
	public $owner_context;
	public $owner_context_id;
	public $is_disabled;
	public $params;
	public $created_at;
	public $updated_at;
	
	public function getOwnerMeta() {
		if(null != ($ext = Extension_DevblocksContext::get($this->owner_context))) {
			$meta = $ext->getMeta($this->owner_context_id);
			$meta['context'] = $this->owner_context;
			$meta['context_ext'] = $ext;
			return $meta;
		}
	}
	
	function getBehaviors($event_point=null, $with_disabled=false, $sort_by='pos') {
		return DAO_TriggerEvent::getByVirtualAttendant($this->id, $event_point, $with_disabled, $sort_by);
	}
	
	function canUseEvent($event_point) {
		@$events_mode = $this->params['events']['mode'];
		@$events_items = $this->params['events']['items'];
		
		switch($events_mode) {
			case 'allow':
				return in_array($event_point, $events_items);
				break;
				
			case 'deny':
				return !in_array($event_point, $events_items);
				break;
		}
		
		return true;
	}
	
	function filterEventsByAllowed($events) {
		@$events_mode = $this->params['events']['mode'];
		@$events_items = $this->params['events']['items'];
		
		switch($events_mode) {
			case 'allow':
				$events = array_intersect_key($events, array_flip($events_items));
				break;
				
			case 'deny':
				$events = array_diff_key($events, array_flip($events_items));
				break;
		}
		
		return $events;
	}
	
	function filterActionManifestsByAllowed($manifests) {
		@$actions_mode = $this->params['actions']['mode'];
		@$actions_items = $this->params['actions']['items'];
		
		switch($actions_mode) {
			case 'allow':
				$manifests = array_intersect_key($manifests, array_flip($actions_items));
				break;
				
			case 'deny':
				$manifests = array_diff_key($manifests, array_flip($actions_items));
				break;
		}
		
		return $manifests;
	}
	
	function isReadableByActor($actor) {
		if($actor instanceof Model_Worker && $actor->is_superuser)
			return true;
		
		return CerberusContexts::isReadableByActor($this->owner_context, $this->owner_context_id, $actor);
	}
	
	function isWriteableByActor($actor) {
		if($actor instanceof Model_Worker && $actor->is_superuser)
			return true;
		
		return CerberusContexts::isWriteableByActor($this->owner_context, $this->owner_context_id, $actor);
	}
};

class View_VirtualAttendant extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'virtual_attendants';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = mb_convert_case($translate->_('common.bots'), MB_CASE_TITLE);
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_VirtualAttendant::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_VirtualAttendant::NAME,
			SearchFields_VirtualAttendant::VIRTUAL_OWNER,
			SearchFields_VirtualAttendant::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_VirtualAttendant::OWNER_CONTEXT,
			SearchFields_VirtualAttendant::OWNER_CONTEXT_ID,
			SearchFields_VirtualAttendant::PARAMS_JSON,
			SearchFields_VirtualAttendant::VIRTUAL_CONTEXT_LINK,
			SearchFields_VirtualAttendant::VIRTUAL_HAS_FIELDSET,
			SearchFields_VirtualAttendant::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_VirtualAttendant::OWNER_CONTEXT,
			SearchFields_VirtualAttendant::OWNER_CONTEXT_ID,
			SearchFields_VirtualAttendant::PARAMS_JSON,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_VirtualAttendant::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_VirtualAttendant');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_VirtualAttendant', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_VirtualAttendant', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_VirtualAttendant::IS_DISABLED:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_VirtualAttendant::VIRTUAL_CONTEXT_LINK:
				case SearchFields_VirtualAttendant::VIRTUAL_HAS_FIELDSET:
				case SearchFields_VirtualAttendant::VIRTUAL_OWNER:
				case SearchFields_VirtualAttendant::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_VirtualAttendant::IS_DISABLED:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;

			case SearchFields_VirtualAttendant::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_VirtualAttendant::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_CustomFieldset::OWNER_CONTEXT, DAO_CustomFieldset::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
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
		$search_fields = SearchFields_VirtualAttendant::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_VirtualAttendant::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_VirtualAttendant::CREATED_AT),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_VirtualAttendant::ID),
				),
			'isDisabled' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_VirtualAttendant::IS_DISABLED),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_VirtualAttendant::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_VirtualAttendant::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_VirtualAttendant::VIRTUAL_OWNER),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendLinksFromQuickSearchContexts($fields);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/va/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_VirtualAttendant::NAME:
			case SearchFields_VirtualAttendant::OWNER_CONTEXT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_VirtualAttendant::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_VirtualAttendant::IS_DISABLED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_VirtualAttendant::CREATED_AT:
			case SearchFields_VirtualAttendant::UPDATED_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT);
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_OWNER:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$roles = DAO_WorkerRole::getAll();
				$tpl->assign('roles', $roles);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_owner.tpl');
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_VirtualAttendant::IS_DISABLED:
				$this->_renderCriteriaParamBoolean($param);
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
			case SearchFields_VirtualAttendant::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners');
				break;
			
			case SearchFields_VirtualAttendant::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_VirtualAttendant::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_VirtualAttendant::NAME:
			case SearchFields_VirtualAttendant::OWNER_CONTEXT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_VirtualAttendant::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_VirtualAttendant::CREATED_AT:
			case SearchFields_VirtualAttendant::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_VirtualAttendant::IS_DISABLED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
				
			case SearchFields_VirtualAttendant::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
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

class Context_VirtualAttendant extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete { // IDevblocksContextImport
	function getRandom() {
		return DAO_VirtualAttendant::random();
	}
	
	function autocomplete($term) {
		$url_writer = DevblocksPlatform::getUrlService();
		$list = array();
		
		$models = DAO_VirtualAttendant::autocomplete($term);
		
		if(stristr('none',$term) || stristr('empty',$term)) {
			$empty = new stdClass();
			$empty->label = '(no bot)';
			$empty->value = '0';
			$empty->meta = array('desc' => 'Clear the bot');
			$list[] = $empty;
		}
		
		if(is_array($models))
		foreach($models as $bot_id => $bot){
			$entry = new stdClass();
			$entry->label = $bot->name;
			$entry->value = sprintf("%d", $bot_id);
			$entry->icon = $url_writer->write('c=avatars&type=virtual_attendant&id=' . $bot->id, true) . '?v=' . $bot->updated_at;
			
			$meta = array();
			$entry->meta = $meta;
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=virtual_attendant&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		if(false == ($virtual_attendant = DAO_VirtualAttendant::get($context_id)))
			return [];
			
		$url_writer = DevblocksPlatform::getUrlService();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($virtual_attendant->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $virtual_attendant->id,
			'name' => $virtual_attendant->name,
			'permalink' => $url,
			'updated' => $virtual_attendant->updated_at,
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
			'owner__label',
			'is_disabled',
			'updated_at',
		);
	}
	
	function getContext($virtual_attendant, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Virtual Attendant:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT);

		// Polymorph
		if(is_numeric($virtual_attendant)) {
			$virtual_attendant = DAO_VirtualAttendant::get($virtual_attendant);
		} elseif($virtual_attendant instanceof Model_VirtualAttendant) {
			// It's what we want already.
		} elseif(is_array($virtual_attendant)) {
			$virtual_attendant = Cerb_ORMHelper::recastArrayToModel($virtual_attendant, 'Model_VirtualAttendant');
		} else {
			$virtual_attendant = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'created_at' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'is_disabled' => $prefix.$translate->_('common.disabled'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			
			'record_url' => $prefix.$translate->_('common.url.record'),
			
			'owner__label' => $prefix.$translate->_('common.owner'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_disabled' => Model_CustomField::TYPE_CHECKBOX,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT;
		$token_values['_types'] = $token_types;
		
		if($virtual_attendant) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $virtual_attendant->name;
			$token_values['created_at'] = $virtual_attendant->created_at;
			$token_values['id'] = $virtual_attendant->id;
			$token_values['name'] = $virtual_attendant->name;
			$token_values['is_disabled'] = $virtual_attendant->is_disabled;
			$token_values['updated_at'] = $virtual_attendant->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($virtual_attendant, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=virtual_attendant&id=%d-%s",$virtual_attendant->id, DevblocksPlatform::strToPermalink($virtual_attendant->name)), true);
			
			$token_values['owner__context'] = $virtual_attendant->owner_context;
			$token_values['owner_id'] = $virtual_attendant->owner_context_id;
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'behaviors':
				$values = $dictionary;
				
				if(null == ($va = DAO_VirtualAttendant::get($context_id)))
					break;

				$values['behaviors'] = array();

				$behaviors = $va->getBehaviors(null, true, 'pos');

				foreach($behaviors as $behavior) { /* @var $behavior Model_TriggerEvent */
					if(false == ($behavior_json = $behavior->exportToJson()))
						continue;
					
					@$json = json_decode($behavior_json, true);
					
					if(empty($json))
						continue;
					
					$values['behaviors'][$behavior->id] = $json;
				}
				break;
			
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
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
		$view->name = 'Virtual Attendant';
		/*
		$view->addParams(array(
			SearchFields_VirtualAttendant::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_VirtualAttendant::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_VirtualAttendant::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Virtual Attendant';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_VirtualAttendant::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT;
		$active_worker = CerberusApplication::getActiveWorker();

		if(!empty($context_id) && false != ($model = DAO_VirtualAttendant::get($context_id))) {
		} else {
			@$owner_context = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'string','');
			@$owner_context_id = DevblocksPlatform::importGPC($_REQUEST['owner_context_id'],'integer',0);
			
			$model = new Model_VirtualAttendant();
			$model->owner_context = $owner_context;
			$model->owner_context_id = $owner_context_id;
		}
		
		if(empty($context_id) || $edit) {
			if(!$active_worker || !$active_worker->is_superuser)
				return;
			
			if(isset($model))
				$tpl->assign('model', $model);
			
			// Custom Fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Owner
			$owners_menu = Extension_DevblocksContext::getOwnerTree(['app','group','role','worker']);
			$tpl->assign('owners_menu', $owners_menu);
			
			// VA Events
			$event_extensions = DevblocksPlatform::getExtensions('devblocks.event', false);
			DevblocksPlatform::sortObjects($event_extensions, 'name');
			$tpl->assign('event_extensions', $event_extensions);
			
			// VA actions
			$action_extensions = DevblocksPlatform::getExtensions('devblocks.event.action', false);
			DevblocksPlatform::sortObjects($action_extensions, 'params->[label]');
			$tpl->assign('action_extensions', $action_extensions);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/va/peek_edit.tpl');
			
		} else {
			// Counts
			$activity_counts = array(
				'behaviors' => DAO_TriggerEvent::countByBot($context_id),
				'classifiers' => DAO_Classifier::countByBot($context_id),
				//'comments' => DAO_Comment::count($context, $context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
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
			
			// Timeline
			if($context_id) {
				$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments($context, $context_id));
				$tpl->assign('timeline_json', $timeline_json);
			}

			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			$tpl->display('devblocks:cerberusweb.core::internal/va/peek.tpl');
		}
	}
};
