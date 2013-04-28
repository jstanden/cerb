<?php
class DAO_CustomFieldGroup extends Cerb_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const CONTEXT = 'context';
	const OWNER_CONTEXT = 'owner_context';
	const OWNER_CONTEXT_ID = 'owner_context_id';

	const CACHE_ALL = 'ch_customfieldgroups';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO custom_field_group () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'custom_field_group', $fields);
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('custom_field_group', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CustomFieldGroup[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, context, owner_context, owner_context_id ".
			"FROM custom_field_group ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CustomFieldGroup
	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if($nocache || null === ($objects = $cache->load(self::CACHE_ALL))) {
			$objects = DAO_CustomFieldGroup::getWhere();
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}
	
	static function getByContext($context) {
		$cf_groups = DAO_CustomFieldGroup::getAll();
		$results = array();
		
		foreach($cf_groups as $cf_group_id => $cf_group) { /* @var $cg_group Model_CustomFieldGroup */
			if(0 == strcasecmp($cf_group->context, $context))
				$results[$cf_group_id] = $cf_group;
		}
		
		return $results;
	}
	
	/**
	 *
	 * @param string $context
	 * @param integer $context_id
	 * @return Model_CustomFieldGroup[]
	 */
	static function getByContextLink($context, $context_id) {
		$cf_groups = DAO_CustomFieldGroup::getAll();
		$context_values = DAO_ContextLink::getContextLinks($context, $context_id, CerberusContexts::CONTEXT_CUSTOM_FIELD_GROUP);
		$results = array();
		
		if(!isset($context_values[$context_id]))
			return $results;
		
		if(!is_array($context_values[$context_id]))
			return $results;
		
		foreach($context_values[$context_id] as $cf_group_id => $ctx_pair) {
			if(isset($cf_groups[$cf_group_id]))
				$results[$cf_group_id] = $cf_groups[$cf_group_id];
		}
		
		return $results;
	}
	
	static function getByOwner($owner_context, $owner_context_id=0) {
		$cf_groups = DAO_CustomFieldGroup::getAll();
		$results = array();
		
		foreach($cf_groups as $cf_group_id => $cf_group) { /* @var $cg_group Model_CustomFieldGroup */
			if(0 == strcasecmp($cf_group->owner_context, $owner_context)
				&& intval($cf_group->owner_context_id) == intval($owner_context_id))
				$results[$cf_group_id] = $cf_group;
		}
		
		return $results;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_CustomFieldGroup[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CustomFieldGroup();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->context = $row['context'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = $row['owner_context_id'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM custom_field_group WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerberusweb.contexts.custom_field_group',
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CustomFieldGroup::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"custom_field_group.id as %s, ".
			"custom_field_group.name as %s, ".
			"custom_field_group.context as %s, ".
			"custom_field_group.owner_context as %s, ".
			"custom_field_group.owner_context_id as %s ",
				SearchFields_CustomFieldGroup::ID,
				SearchFields_CustomFieldGroup::NAME,
				SearchFields_CustomFieldGroup::CONTEXT,
				SearchFields_CustomFieldGroup::OWNER_CONTEXT,
				SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID
			);
			
		$join_sql = "FROM custom_field_group ".
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.custom_field_group' AND context_link.to_context_id = custom_field_group.id) " : " ")
			;
		
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
	
		array_walk_recursive(
			$params,
			array('DAO_CustomFieldGroup', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'custom_field_group',
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
			
		$from_context = CerberusContexts::CONTEXT_CUSTOM_FIELD_GROUP;
		$from_index = 'custom_field_group.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_CustomFieldGroup::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
			
			case SearchFields_CustomFieldGroup::VIRTUAL_OWNER:
				if(!is_array($param->value))
					break;
				
				$wheres = array();
				$args['has_multiple_values'] = true;
					
				foreach($param->value as $owner_context) {
					@list($context, $context_id) = explode(':', $owner_context);
					
					if(empty($context))
						continue;
					
					if(!empty($context_id)) {
						$wheres[] = sprintf("(custom_field_group.owner_context = %s AND custom_field_group.owner_context_id = %d)",
							Cerb_ORMHelper::qstr($context),
							$context_id
						);
						
					} else {
						$wheres[] = sprintf("(custom_field_group.owner_context = %s)",
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
			($has_multiple_values ? 'GROUP BY custom_field_group.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			$total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_CustomFieldGroup::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT custom_field_group.id) " : "SELECT COUNT(custom_field_group.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
	
	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}

};

class SearchFields_CustomFieldGroup implements IDevblocksSearchFields {
	const ID = 'c_id';
	const NAME = 'c_name';
	const CONTEXT = 'c_context';
	const OWNER_CONTEXT = 'c_owner_context';
	const OWNER_CONTEXT_ID = 'c_owner_context_id';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_OWNER = '*_owner';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'custom_field_group', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'custom_field_group', 'name', $translate->_('common.name')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'custom_field_group', 'context', $translate->_('common.context')),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'custom_field_group', 'owner_context', $translate->_('common.owner_context')),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'custom_field_group', 'owner_context_id', $translate->_('common.owner_context_id')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_OWNER => new DevblocksSearchField(self::VIRTUAL_OWNER, '*', 'owner', $translate->_('common.owner')),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_CustomFieldGroup {
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
			if($field->custom_field_group_id == $this->id)
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
		}
		
		return false;
	}
};

class View_CustomFieldGroup extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'custom_field_groups';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Custom Field Groups');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CustomFieldGroup::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CustomFieldGroup::CONTEXT,
			SearchFields_CustomFieldGroup::NAME,
			SearchFields_CustomFieldGroup::VIRTUAL_OWNER,
		);

		$this->addColumnsHidden(array(
			SearchFields_CustomFieldGroup::ID,
			SearchFields_CustomFieldGroup::OWNER_CONTEXT,
			SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID,
			SearchFields_CustomFieldGroup::VIRTUAL_CONTEXT_LINK,
		));
		
		$this->addParamsHidden(array(
			SearchFields_CustomFieldGroup::CONTEXT_LINK,
			SearchFields_CustomFieldGroup::CONTEXT_LINK_ID,
			SearchFields_CustomFieldGroup::ID,
			SearchFields_CustomFieldGroup::OWNER_CONTEXT,
			SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_CustomFieldGroup::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_CustomFieldGroup', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_CustomFieldGroup', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_CustomFieldGroup::CONTEXT:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_CustomFieldGroup::VIRTUAL_CONTEXT_LINK:
				case SearchFields_CustomFieldGroup::VIRTUAL_OWNER:
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_CustomFieldGroup::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_CustomFieldGroup', CerberusContexts::CONTEXT_CUSTOM_FIELD_GROUP, $column);
				break;
			
			case SearchFields_CustomFieldGroup::CONTEXT:
				$label_map = array();
				$contexts = Extension_DevblocksContext::getAll(false);
				
				foreach($contexts as $k => $mft) {
					$label_map[$k] = $mft->name;
				}
				
				$counts = $this->_getSubtotalCountForStringColumn('DAO_CustomFieldGroup', $column, $label_map, 'in', 'contexts[]');
				break;
			
			case SearchFields_CustomFieldGroup::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns('DAO_CustomFieldGroup', CerberusContexts::CONTEXT_CUSTOM_FIELD_GROUP, $column, DAO_CustomFieldGroup::OWNER_CONTEXT, DAO_CustomFieldGroup::OWNER_CONTEXT_ID, 'owner_context[]');
				break;
			
			default:
				break;
		}
		
		return $counts;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/custom_field_groups/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CustomFieldGroup::NAME:
			case SearchFields_CustomFieldGroup::CONTEXT:
			case SearchFields_CustomFieldGroup::OWNER_CONTEXT:
			case SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CustomFieldGroup::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_CustomFieldGroup::CONTEXT:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context.tpl');
				break;
				
			case SearchFields_CustomFieldGroup::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_CustomFieldGroup::VIRTUAL_OWNER:
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
			case SearchFields_CustomFieldGroup::CONTEXT:
				$contexts = Extension_DevblocksContext::getAll(false);
				$strings = array();
				
				foreach($param->value as $context_id) {
					if(isset($contexts[$context_id])) {
						$strings[] = '<b>'.$contexts[$context_id]->name.'</b>';
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
			case SearchFields_CustomFieldGroup::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_CustomFieldGroup::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners');
				break;
		}
	}

	function getFields() {
		return SearchFields_CustomFieldGroup::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CustomFieldGroup::NAME:
			case SearchFields_CustomFieldGroup::OWNER_CONTEXT:
			case SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_CustomFieldGroup::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_CustomFieldGroup::CONTEXT:
				@$in_contexts = DevblocksPlatform::importGPC($_REQUEST['contexts'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$in_contexts);
				break;
				
			case SearchFields_CustomFieldGroup::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_CustomFieldGroup::VIRTUAL_OWNER:
				@$owner_contexts = DevblocksPlatform::importGPC($_REQUEST['owner_context'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$owner_contexts);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
	
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_CustomFieldGroup::EXAMPLE] = 'some value';
					break;
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_CustomFieldGroup::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_CustomFieldGroup::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_CustomFieldGroup::update($batch_ids, $change_fields);
			}

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_CustomFieldGroup::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_CustomFieldGroup extends Extension_DevblocksContext {
	function getRandom() {
		//return DAO_CustomFieldGroup::random();
	}
	
	function getMeta($context_id) {
		$cf_group = DAO_CustomFieldGroup::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $context_id,
			'name' => $cf_group->name,
			'permalink' => '', //$url_writer->writeNoProxy('c=tasks&action=display&id='.$task->id, true),
		);
	}
	
	function getContext($cf_group, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Custom Field Group:';
		
		$translate = DevblocksPlatform::getTranslationService();

		// Polymorph
		if(is_numeric($cf_group)) {
			$cf_group = DAO_CustomFieldGroup::get($cf_group);
		} elseif($cf_group instanceof Model_CustomFieldGroup) {
			// It's what we want already.
		} else {
			$cf_group = null;
		}
		
		// Token labels
		$token_labels = array(
			'context' => $prefix.$translate->_('common.context'),
			'name' => $prefix.$translate->_('common.name'),
			'owner_context' => $prefix.$translate->_('common.owner_context'),
			'owner_context_id' => $prefix.$translate->_('common.owner_context_id'),
		);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CUSTOM_FIELD_GROUP;
		
		if($snippet) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $cf_group->name;
			$token_values['context'] = $cf_group->context;
			$token_values['name'] = $cf_group->name;
			$token_values['owner_context'] = $cf_group->owner_context;
			$token_values['owner_context_id'] = $cf_group->owner_context_id;
		}

		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_CUSTOM_FIELD_GROUP;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			default:
				/*
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
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
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Custom Field Groups';
		
		$params_required = array();
		
		$worker_group_ids = array_keys($active_worker->getMemberships());
		$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
		
		// Restrict owners
		$param_ownership = array(
			DevblocksSearchCriteria::GROUP_OR,
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_CustomFieldGroup::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_CustomFieldGroup::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_WORKER),
				SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_EQ,$active_worker->id),
			),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_CustomFieldGroup::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_CustomFieldGroup::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_GROUP),
				SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,$worker_group_ids),
			),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_CustomFieldGroup::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_CustomFieldGroup::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_ROLE),
				SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_CustomFieldGroup::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,$worker_role_ids),
			),
		);
		$params_required['_ownership'] = $param_ownership;
		
		// Restrict contexts
		if(isset($_REQUEST['link_context'])) {
			$link_context = DevblocksPlatform::importGPC($_REQUEST['link_context'],'string','');
			if(!empty($link_context)) {
				$params_required['_ownership'] = new DevblocksSearchCriteria(SearchFields_CustomFieldGroup::CONTEXT, DevblocksSearchCriteria::OPER_EQ, $link_context);
			}
		}
		
		$view->addParamsRequired($params_required, true);
		
		$view->renderSortBy = SearchFields_CustomFieldGroup::NAME;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		$view->renderFilters = false;
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Custom Field Groups';

		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_CustomFieldGroup::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_CustomFieldGroup::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};
