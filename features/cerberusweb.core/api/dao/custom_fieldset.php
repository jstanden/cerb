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
		$db->Execute($sql);
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
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_CustomFieldset[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, context, owner_context, owner_context_id ".
			"FROM custom_fieldset ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CustomFieldset
	 */
	static function get($id) {
		$objects = DAO_CustomFieldset::getAll();
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if($nocache || null === ($objects = $cache->load(self::CACHE_ALL))) {
			$objects = DAO_CustomFieldset::getWhere(null, DAO_CustomFieldset::NAME, true);
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
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CustomFieldset();
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
		
		$db->Execute(sprintf("DELETE FROM custom_fieldset WHERE id IN (%s)", $ids_list));
		
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
	
	static function deleteByOwner($owner_context, $owner_context_id) {
		$fieldsets = DAO_CustomFieldset::getByOwner($owner_context, $owner_context_id);
		DAO_CustomFieldset::delete(array_keys($fieldsets));
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_CustomFieldset::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
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
				$args['has_multiple_values'] = true;
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
			$object_id = intval($row[SearchFields_CustomFieldset::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT custom_fieldset.id) " : "SELECT COUNT(custom_fieldset.id) ").
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

class SearchFields_CustomFieldset implements IDevblocksSearchFields {
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
			self::ID => new DevblocksSearchField(self::ID, 'custom_fieldset', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER),
			self::NAME => new DevblocksSearchField(self::NAME, 'custom_fieldset', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'custom_fieldset', 'context', $translate->_('common.context')),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'custom_fieldset', 'owner_context', $translate->_('common.owner_context')),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'custom_fieldset', 'owner_context_id', $translate->_('common.owner_context_id')),
			
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

class View_CustomFieldset extends C4_AbstractView implements IAbstractView_Subtotals {
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_CustomFieldset::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_CustomFieldset', CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $column);
				break;
			
			case SearchFields_CustomFieldset::CONTEXT:
				$label_map = array();
				$contexts = Extension_DevblocksContext::getAll(false);
				
				foreach($contexts as $k => $mft) {
					$label_map[$k] = $mft->name;
				}
				
				$counts = $this->_getSubtotalCountForStringColumn('DAO_CustomFieldset', $column, $label_map, 'in', 'contexts[]');
				break;
			
			case SearchFields_CustomFieldset::VIRTUAL_OWNER:
				$counts = $this->_getSubtotalCountForContextAndIdColumns('DAO_CustomFieldset', CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $column, DAO_CustomFieldset::OWNER_CONTEXT, DAO_CustomFieldset::OWNER_CONTEXT_ID, 'owner_context[]');
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
					//$change_fields[DAO_CustomFieldset::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_CustomFieldset::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_CustomFieldset::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_CustomFieldset::update($batch_ids, $change_fields);
			}

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_CustomFieldset::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_CustomFieldset extends Extension_DevblocksContext {
	function getRandom() {
		//return DAO_CustomFieldset::random();
	}
	
	function getMeta($context_id) {
		$cfieldset = DAO_CustomFieldset::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $context_id,
			'name' => $cfieldset->name,
			'permalink' => '', //$url_writer->writeNoProxy('c=tasks&action=display&id='.$task->id, true),
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
		} else {
			$cfieldset = null;
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_CUSTOM_FIELDSET;
		
		if($cfieldset) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $cfieldset->name;
			$token_values['context'] = $cfieldset->context;
			$token_values['name'] = $cfieldset->name;
			$token_values['owner_context'] = $cfieldset->owner_context;
			$token_values['owner_context_id'] = $cfieldset->owner_context_id;
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
		$view->name = 'Custom Fieldsets';
		
		$params_required = array();

		/*
		// Restrict owners
		$worker_group_ids = array_keys($active_worker->getMemberships());
		$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
		$worker_va_ids = array_keys(DAO_VirtualAttendant::getEditableByWorker($active_worker
		));
		
		$param_ownership = array(
			DevblocksSearchCriteria::GROUP_OR,
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_CustomFieldset::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_WORKER),
				SearchFields_CustomFieldset::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_EQ, $active_worker->id),
			),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_CustomFieldset::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_GROUP),
				SearchFields_CustomFieldset::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN, $worker_group_ids),
			),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_CustomFieldset::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_ROLE),
				SearchFields_CustomFieldset::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN, $worker_role_ids),
			),
			array(
				DevblocksSearchCriteria::GROUP_AND,
				SearchFields_CustomFieldset::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT),
				SearchFields_CustomFieldset::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_CustomFieldset::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN, $worker_va_ids),
			),
		);
		*/
		
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
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
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
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};
