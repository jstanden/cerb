<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class DAO_Group extends Cerb_ORMHelper {
	const CACHE_ALL = 'cerberus_cache_groups_all';
	const CACHE_ROSTERS = 'ch_group_rosters';
	
	const ID = 'id';
	const NAME = 'name';
	const REPLY_ADDRESS_ID = 'reply_address_id';
	const REPLY_PERSONAL = 'reply_personal';
	const REPLY_SIGNATURE = 'reply_signature';
	const IS_DEFAULT = 'is_default';
	
	// Groups
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Group
	 */
	static function get($id) {
		$groups = DAO_Group::getAll();
		
		if(isset($groups[$id]))
			return $groups[$id];
			
		return null;
	}
	
	/**
	 * @param string $where
	 * @param string $sortBy
	 * @param bool $sortAsc
	 * @param integer $limit
	 * @return Model_ContactOrg[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, is_default, reply_address_id, reply_personal, reply_signature ".
			"FROM worker_group ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);

		$objects = self::_getObjectsFromResultSet($rs);

		return $objects;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return Model_Group[]
	 */
	static function getGroups($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();

		$groups = array();
		
		$sql = sprintf("SELECT id, name, is_default, reply_address_id, reply_personal, reply_signature ".
			"FROM worker_group ".
			((is_array($ids) && !empty($ids)) ? sprintf("WHERE id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY name ASC"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());

		return self::_getObjectsFromResultSet($rs);
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		if($nocache || null === ($groups = $cache->load(self::CACHE_ALL))) {
			$groups = DAO_Group::getGroups();
			$cache->save($groups, self::CACHE_ALL);
		}
		
		return $groups;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Notification[]
	 */
	static private function _getObjectsFromResultSet($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Group();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->is_default = intval($row['is_default']);
			$object->reply_address_id = $row['reply_address_id'];
			$object->reply_personal = $row['reply_personal'];
			$object->reply_signature = $row['reply_signature'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	/**
	 *
	 * @return Model_Group|null
	 */
	static function getDefaultGroup() {
		$groups = self::getAll();
		
		if(is_array($groups))
		foreach($groups as $group) { /* @var $group Model_Group */
			if($group->is_default)
				return $group;
		}
		
		return null;
	}
	
	static function setDefaultGroup($group_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute("UPDATE worker_group SET is_default = 0");
		$db->Execute(sprintf("UPDATE worker_group SET is_default = 1 WHERE id = %d", $group_id));
		
		self::clearCache();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $name
	 * @return integer
	 */
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO worker_group () VALUES ()";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId();
		
		self::update($id, $fields);

		self::clearCache();
		
		return $id;
	}

	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @param array $fields
	 */
	static function update($ids, $fields) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Get state before changes
			$object_changes = parent::_getUpdateDeltas($batch_ids, $fields, get_class());

			// Make changes
			parent::_update($batch_ids, 'worker_group', $fields);
			
			// Send events
			if(!empty($object_changes)) {
				// Local events
				//self::_processUpdateEvents($object_changes);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.group.update',
						array(
							'objects' => $object_changes,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_GROUP, $batch_ids);
			}
		}
		
		// Clear cache
		self::clearCache();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 */
	static function delete($id) {
		if(empty($id)) return;
		$db = DevblocksPlatform::getDatabaseService();
		
		/*
		 * Notify anything that wants to know when groups delete.
		 */
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'group.delete',
				array(
					'group_ids' => array($id),
				)
			)
		);
		
		$sql = sprintf("DELETE QUICK FROM worker_group WHERE id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());

		$sql = sprintf("DELETE QUICK FROM bucket WHERE group_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$sql = sprintf("DELETE QUICK FROM group_setting WHERE group_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$sql = sprintf("DELETE QUICK FROM worker_to_group WHERE group_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());

		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_GROUP,
					'context_ids' => array($id)
				)
			)
		);
		
		self::clearCache();
		DAO_Bucket::clearCache();
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK bucket FROM bucket LEFT JOIN worker_group ON bucket.group_id = worker_group.id WHERE worker_group.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' bucket records.');
		
		$sql = "DELETE QUICK group_setting FROM group_setting LEFT JOIN worker_group ON group_setting.group_id = worker_group.id WHERE worker_group.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' group_setting records.');
		
		$sql = "DELETE QUICK custom_field FROM custom_field LEFT JOIN custom_fieldset ON custom_field.custom_fieldset_id = custom_fieldset.id WHERE custom_field.custom_fieldset_id > 0 AND custom_fieldset.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' custom_field records.');
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_GROUP,
					'context_table' => 'worker_group',
					'context_key' => 'id',
				)
			)
		);
	}
	
	static function setGroupMember($group_id, $worker_id, $is_manager=false) {
		if(empty($worker_id) || empty($group_id))
			return FALSE;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("REPLACE INTO worker_to_group (worker_id, group_id, is_manager) ".
			"VALUES (%d, %d, %d)",
			$worker_id,
			$group_id,
			($is_manager?1:0)
	   	));
		
		self::clearCache();
	}
	
	static function unsetGroupMember($group_id, $worker_id) {
		if(empty($worker_id) || empty($group_id))
			return FALSE;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM worker_to_group WHERE group_id = %d AND worker_id IN (%d)",
			$group_id,
			$worker_id
		);
		$db->Execute($sql);

		self::clearCache();
	}
	
	static function getRosters() {
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_ROSTERS))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = sprintf("SELECT wt.worker_id, wt.group_id, wt.is_manager ".
				"FROM worker_to_group wt ".
				"INNER JOIN worker_group g ON (wt.group_id=g.id) ".
				"INNER JOIN worker w ON (w.id=wt.worker_id) ".
				"ORDER BY g.name ASC, w.first_name ASC "
			);
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
			
			$objects = array();
			
			while($row = mysql_fetch_assoc($rs)) {
				$worker_id = intval($row['worker_id']);
				$group_id = intval($row['group_id']);
				$is_manager = intval($row['is_manager']);
				
				if(!isset($objects[$group_id]))
					$objects[$group_id] = array();
				
				$member = new Model_GroupMember();
				$member->id = $worker_id;
				$member->group_id = $group_id;
				$member->is_manager = $is_manager;
				$objects[$group_id][$worker_id] = $member;
			}
			
			mysql_free_result($rs);
			
			$cache->save($objects, self::CACHE_ROSTERS);
		}
		
		return $objects;
	}
	
	static function getGroupMembers($group_id) {
		$rosters = self::getRosters();
		
		if(isset($rosters[$group_id]))
			return $rosters[$group_id];
		
		return null;
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
		$cache->remove(self::CACHE_ROSTERS);
	}
	
	public static function random() {
		return self::_getRandom('worker_group');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Group::getFields();

		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"g.id as %s, ".
			"g.name as %s ",
				SearchFields_Group::ID,
				SearchFields_Group::NAME
			);
			
		$join_sql = "FROM worker_group g ".

		// Dynamic joins
		(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.group' AND context_link.to_context_id = g.id) " : " ")
		;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'g.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";

		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
		
		array_walk_recursive(
			$params,
			array('DAO_Group', '_translateVirtualParameters'),
			$args
		);
		
		$result = array(
			'primary_table' => 'g',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_GROUP;
		$from_index = 'g.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_Group::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
				
			case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		}
	}
	
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
			($has_multiple_values ? 'GROUP BY g.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		} else {
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
			$total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_Group::ID]);
			$results[$object_id] = $result;
		}
		
		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT g.id) " : "SELECT COUNT(g.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_Group implements IDevblocksSearchFields {
	// Worker
	const ID = 'g_id';
	const NAME = 'g_name';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'g', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'g', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
				
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
		);
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			CerberusContexts::CONTEXT_GROUP,
		));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Group {
	public $id;
	public $name;
	public $count;
	public $is_default = 0;
	public $reply_address_id;
	public $reply_personal;
	public $reply_signature;
	
	public function getMembers() {
		return DAO_Group::getGroupMembers($this->id);
	}
	
	/**
	 *
	 * @param integer $bucket_id
	 * @return Model_AddressOutgoing
	 */
	public function getReplyTo($bucket_id=0) {
		$from_id = 0;
		$froms = DAO_AddressOutgoing::getAll();
		
		// Cascade to bucket
		if(!empty($bucket_id)
			&& null != ($bucket = DAO_Bucket::get($bucket_id))) {
			
			$from_id = $bucket->reply_address_id;
		}

		// Cascade to group
		if(empty($from_id))
			$from_id = $this->reply_address_id;
		
		// Cascade to global
		if(empty($from_id) || !isset($froms[$from_id])) {
			$from = DAO_AddressOutgoing::getDefault();
			$from_id = $from->address_id;
		}
			
		// Last check
		if(!isset($froms[$from_id]))
			return null;
		
		return $froms[$from_id];
	}
	
	public function getReplyFrom($bucket_id=0) {
		$from_id = 0;
		$froms = DAO_AddressOutgoing::getAll();
		
		// Cascade to bucket
		if(!empty($bucket_id)
			&& null != ($bucket = DAO_Bucket::get($bucket_id))) {
			
			$from_id = $bucket->reply_address_id;
		}

		// Cascade to group
		if(empty($from_id))
			$from_id = $this->reply_address_id;
		
		// Cascade to global
		if(empty($from_id) || !isset($froms[$from_id])) {
			$from = DAO_AddressOutgoing::getDefault();
			$from_id = $from->address_id;
		}
			
		return $from_id;
	}
	
	public function getReplyPersonal($bucket_id=0, $worker_model=null) {
		$froms = DAO_AddressOutgoing::getAll();
		$personal = null;
		
		// Cascade to bucket
		if(!empty($bucket_id)
			&& null != ($bucket = DAO_Bucket::get($bucket_id))) {
			
			$personal = $bucket->reply_personal;
			
			// Cascade to bucket address
			if(empty($personal) && !empty($bucket->reply_address_id) && isset($froms[$bucket->reply_address_id])) {
				$from = $froms[$bucket->reply_address_id];
				$personal = $from->reply_personal;
			}
		}

		// Cascade to group
		if(empty($personal))
			$personal = $this->reply_personal;
			
		// Cascade to group address
		if(empty($personal) && !empty($this->reply_address_id) && isset($froms[$this->reply_address_id])) {
			$from = $froms[$this->reply_address_id];
			$personal = $from->reply_personal;
		}
		
		// Cascade to global
		if(empty($personal)) {
			$from = DAO_AddressOutgoing::getDefault();
			$personal = $from->reply_personal;
		}
		
		// If we have a worker model, convert template tokens
		if(empty($worker_model))
			$worker_model = new Model_Worker();
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$token_labels = array();
		$token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_model, $token_labels, $token_values);
		$personal = $tpl_builder->build($personal, $token_values);
		
		return $personal;
	}
	
	public function getReplySignature($bucket_id=0, $worker_model=null) {
		$froms = DAO_AddressOutgoing::getAll();
		$signature = null;
		
		// Cascade to bucket
		if(!empty($bucket_id)
			&& null != ($bucket = DAO_Bucket::get($bucket_id))) {
			
			$signature = $bucket->reply_signature;
			
			// Cascade to bucket address
			if(empty($signature) && !empty($bucket->reply_address_id) && isset($froms[$bucket->reply_address_id])) {
				$from = $froms[$bucket->reply_address_id];
				$signature = $from->reply_signature;
			}
		}

		// Cascade to group
		if(empty($signature))
			$signature = $this->reply_signature;
			
		// Cascade to group address
		if(empty($signature) && !empty($this->reply_address_id) && isset($froms[$this->reply_address_id])) {
			$from = $froms[$this->reply_address_id];
			$signature = $from->reply_signature;
		}
		
		// Cascade to global
		if(empty($signature)) {
			$from = DAO_AddressOutgoing::getDefault();
			$signature = $from->reply_signature;
		}
		
		// If we have a worker model, convert template tokens
		if(!empty($worker_model)) {
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			$token_labels = array();
			$token_values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_model, $token_labels, $token_values);
			$signature = $tpl_builder->build($signature, $token_values);
		}
		
		return $signature;
	}
};

class DAO_GroupSettings {
	const CACHE_ALL = 'ch_group_settings';
	
	const SETTING_SUBJECT_HAS_MASK = 'subject_has_mask';
	const SETTING_SUBJECT_PREFIX = 'subject_prefix';
	
	static function set($group_id, $key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("REPLACE INTO group_setting (group_id, setting, value) ".
			"VALUES (%d, %s, %s)",
			$group_id,
			$db->qstr($key),
			$db->qstr($value)
		));
		
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	static function get($group_id, $key, $default=null) {
		$value = null;
		
		if(null !== ($group = self::getSettings($group_id)) && isset($group[$key])) {
			$value = $group[$key];
		}
		
		if(null == $value && !is_null($default)) {
			return $default;
		}
		
		return $value;
	}
	
	static function getSettings($group_id=0) {
		$cache = DevblocksPlatform::getCacheService();
		if(null === ($groups = $cache->load(self::CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
	
			$groups = array();
			
			$sql = "SELECT group_id, setting, value FROM group_setting";
			$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg());
			
			while($row = mysql_fetch_assoc($rs)) {
				$gid = intval($row['group_id']);
				
				if(!isset($groups[$gid]))
					$groups[$gid] = array();
				
				$groups[$gid][$row['setting']] = $row['value'];
			}
			
			mysql_free_result($rs);
			
			$cache->save($groups, self::CACHE_ALL);
		}

		// Empty
		if(empty($groups))
			return null;
		
		// Specific group
		if(!empty($group_id)) {
			// Requested group id exists
			if(isset($groups[$group_id]))
				return $groups[$group_id];
			else // doesn't
				return null;
		}
		
		// All groups
		return $groups;
	}
};

class View_Group extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'groups';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Groups';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Group::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Group::NAME,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Group::ID,
			SearchFields_Group::VIRTUAL_HAS_FIELDSET,
			SearchFields_Group::VIRTUAL_CONTEXT_LINK,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Group::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		return DAO_Group::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Group', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Group', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_Group::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
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
			case SearchFields_Group::VIRTUAL_CONTEXT_LINK;
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_Group', CerberusContexts::CONTEXT_GROUP, $column);
				break;
				
			case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_Group', CerberusContexts::CONTEXT_GROUP, $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Group', $column, 'g.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$custom_fields = DAO_CustomField::getByContext(Context_Group::ID);
		$tpl->assign('custom_fields', $custom_fields);

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::groups/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Group::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Group::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;

			case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_GROUP);
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

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Group::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Group::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Group::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Group::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Group::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
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
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
		$custom_fields = array();

		// [TODO] Implement
		return;
		
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
//				case 'is_disabled':
//					$change_fields[DAO_Worker::IS_DISABLED] = intval($v);
//					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;

			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Group::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Group::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Worker::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_GROUP, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Group extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.group';
	
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->isGroupMember($context_id))
				return TRUE;
				
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	function getRandom() {
		return DAO_Group::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=group&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		if(null == ($group = DAO_Group::get($context_id)))
			return false;
		
		$url = $this->profileGetUrl($context_id);
		
		$who = DevblocksPlatform::strToPermalink($group->name);
		
		if(!empty($who))
			$url .= '-' . $who;
		
		return array(
			'id' => $group->id,
			'name' => $group->name,
			'permalink' => $url,
		);
	}
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
		);
	}
	
	function getContext($group, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Group:';
			
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_GROUP);
		
		// Polymorph
		if(is_numeric($group)) {
			$group = DAO_Group::get($group);
		} elseif($group instanceof Model_Group) {
			// It's what we want already.
		} else {
			$group = null;
		}
		
		// Token labels
		$token_labels = array(
			'name' => $prefix.$translate->_('common.name'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = array();

		$token_values['_context'] = CerberusContexts::CONTEXT_GROUP;
		$token_values['_types'] = $token_types;
		
		// Group token values
		if(null != $group) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $group->name;
			$token_values['id'] = $group->id;
			$token_values['name'] = $group->name;
			$token_values['reply_address_id'] = $group->reply_address_id;
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=group&id=%d-%s",$group->id, DevblocksPlatform::strToPermalink($group->name)), true);
		}
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_GROUP;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);

		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Groups';
		$view->view_columns = array(
			SearchFields_Group::NAME,
//			SearchFields_Worker::LAST_NAME,
//			SearchFields_Worker::TITLE,
		);
		$view->addParams(array(
//			SearchFields_Worker::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Worker::IS_DISABLED,'=',0),
		), true);
//		$view->renderSortBy = SearchFields_Group::NAME;
//		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		C4_AbstractViewLoader::setView($view_id, $view);

		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Groups';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Group::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Group::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id) && null != ($group = DAO_Group::get($context_id))) {
			$tpl->assign('group', $group);
		}
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_GROUP, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_GROUP, $context_id);
		if(isset($custom_field_values[$context_id]))
			$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::groups/peek.tpl');
	}
};

class Model_GroupMember {
	public $id;
	public $group_id;
	public $is_manager = 0;
};
