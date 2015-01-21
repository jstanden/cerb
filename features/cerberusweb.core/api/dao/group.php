<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
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
	const REPLY_HTML_TEMPLATE_ID = 'reply_html_template_id';
	const CREATED = 'created';
	const UPDATED = 'updated';
	
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
		$sql = "SELECT id, name, is_default, reply_address_id, reply_personal, reply_signature, reply_html_template_id, created, updated ".
			"FROM worker_group ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);

		$objects = self::_getObjectsFromResultSet($rs);

		return $objects;
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		if($nocache || null === ($groups = $cache->load(self::CACHE_ALL))) {
			$groups = DAO_Group::getWhere(null, DAO_Group::NAME, true);
			$cache->save($groups, self::CACHE_ALL);
		}
		
		return $groups;
	}
	
	static function getNames(Model_Worker $for_worker=null) {
		$groups = DAO_Group::getAll();
		$names = array();
		
		foreach($groups as $group) {
			if(is_null($for_worker) || $for_worker->isGroupMember($group->id))
				$names[$group->id] = $group->name;
		}
		
		return $names;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Notification[]
	 */
	static private function _getObjectsFromResultSet($rs) {
		$objects = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Group();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->is_default = intval($row['is_default']);
			$object->reply_address_id = $row['reply_address_id'];
			$object->reply_personal = $row['reply_personal'];
			$object->reply_signature = $row['reply_signature'];
			$object->reply_html_template_id = $row['reply_html_template_id'];
			$object->created = intval($row['created']);
			$object->updated = intval($row['updated']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
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
		
		if(!isset($fields[self::CREATED]))
			$fields[self::CREATED] = time();
		
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
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED]))
			$fields[self::UPDATED] = time();
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_GROUP, $batch_ids);
			}

			// Make changes
			parent::_update($batch_ids, 'worker_group', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.group.update',
						array(
							'fields' => $fields,
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
		
		$sql = sprintf("DELETE FROM worker_group WHERE id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());

		$sql = sprintf("DELETE FROM bucket WHERE group_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$sql = sprintf("DELETE FROM group_setting WHERE group_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$sql = sprintf("DELETE FROM worker_to_group WHERE group_id = %d", $id);
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
		
		$db->Execute("DELETE FROM bucket WHERE group_id NOT IN (SELECT id FROM worker_group)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' bucket records.');
		
		$db->Execute("DELETE FROM group_setting WHERE group_id NOT IN (SELECT id FROM worker_group)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' group_setting records.');
		
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
		
		$sql = sprintf("DELETE FROM worker_to_group WHERE group_id = %d AND worker_id IN (%d)",
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
			
			while($row = mysqli_fetch_assoc($rs)) {
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
			
			mysqli_free_result($rs);
			
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
			"g.name as %s, ".
			"g.created as %s, ".
			"g.updated as %s ",
				SearchFields_Group::ID,
				SearchFields_Group::NAME,
				SearchFields_Group::CREATED,
				SearchFields_Group::UPDATED
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
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Group::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT g.id) " : "SELECT COUNT(g.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOne($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_Group implements IDevblocksSearchFields {
	// Worker
	const ID = 'g_id';
	const NAME = 'g_name';
	const CREATED = 'g_created';
	const UPDATED = 'g_updated';
	
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
			self::CREATED => new DevblocksSearchField(self::CREATED, 'g', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'g', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE),
			
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
	public $reply_address_id = 0;
	public $reply_personal;
	public $reply_signature;
	public $reply_html_template_id = 0;
	public $created;
	public $updated;
	
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
	
	public function getReplyHtmlTemplate($bucket_id=0) {
		// Cascade to bucket
		if($bucket_id && null != ($bucket = DAO_Bucket::get($bucket_id)))
			return $bucket->getReplyHtmlTemplate();
		
		// Cascade to group
		if($this->reply_html_template_id && false != ($html_template = DAO_MailHtmlTemplate::get($this->reply_html_template_id)))
			return $html_template;
		
		// Finally, cascade to reply-to
		if(false != ($replyto = $this->getReplyTo())) {
			if(false != ($html_template = $replyto->getReplyHtmlTemplate()))
				return $html_template;
		}
		
		return null;
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
			
			while($row = mysqli_fetch_assoc($rs)) {
				$gid = intval($row['group_id']);
				
				if(!isset($groups[$gid]))
					$groups[$gid] = array();
				
				$groups[$gid][$row['setting']] = $row['value'];
			}
			
			mysqli_free_result($rs);
			
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

class View_Group extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'groups';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Groups';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Group::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Group::NAME,
			SearchFields_Group::UPDATED,
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
	
	function getQuickSearchFields() {
		$fields = array(
			'_fulltext' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Group::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Group::CREATED),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Group::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Group::UPDATED),
				),
		);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_GROUP, $fields, null);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamsFromQuickSearchFields($fields) {
		$search_fields = $this->getQuickSearchFields();
		$params = DevblocksSearchCriteria::getParamsFromQueryFields($fields, $search_fields);

		// Handle virtual fields and overrides
		if(is_array($fields))
		foreach($fields as $k => $v) {
			switch($k) {
				// ...
			}
		}
		
		$this->renderPage = 0;
		$this->addParams($params, true);
		
		return $params;
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
				
			case SearchFields_Group::CREATED:
			case SearchFields_Group::UPDATED:
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
				
			case SearchFields_Group::CREATED:
			case SearchFields_Group::UPDATED:
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
			'created' => $group->created,
			'name' => $group->name,
			'updated' => $group->updated,
			'permalink' => $url,
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
		} elseif(is_array($group)) {
			$group = Cerb_ORMHelper::recastArrayToModel($group, 'Model_Group');
		} else {
			$group = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'created' => $prefix.$translate->_('common.created'),
			'name' => $prefix.$translate->_('common.name'),
			'updated' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'created' => Model_CustomField::TYPE_DATE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'reply_address_id' => Model_CustomField::TYPE_NUMBER,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();

		$token_values['_context'] = CerberusContexts::CONTEXT_GROUP;
		$token_values['_types'] = $token_types;
		
		// Group token values
		if(null != $group) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $group->name;
			$token_values['id'] = $group->id;
			$token_values['created'] = $group->created;
			$token_values['name'] = $group->name;
			$token_values['updated'] = $group->updated;
			$token_values['reply_address_id'] = $group->reply_address_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($group, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=group&id=%d-%s", $group->id, DevblocksPlatform::strToPermalink($group->name)), true);
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
			case 'buckets':
				// [TODO] Can't $values clobber the lazy load above here?
				$values = $dictionary;

				if(!isset($values['buckets']))
					$values['buckets'] = array(
						'results_meta' => array(
							'labels' => array(),
							'types' => array(),
						),
						'results' => array(),
					);
					
				$buckets = DAO_Bucket::getByGroup($context_id);
				
				if(is_array($buckets))
				foreach($buckets as $bucket) { /* @var $bucket Model_Bucket */
					$bucket_labels = array();
					$bucket_values = array();
					CerberusContexts::getContext(CerberusContexts::CONTEXT_BUCKET, $bucket, $bucket_labels, $bucket_values, null, true);
					
					// Results meta
					if(is_null($values['buckets']['results_meta']['labels']))
						$values['buckets']['results_meta']['labels'] = $bucket_values['_labels'];
					
					if(is_null($values['buckets']['results_meta']['types']))
						$values['buckets']['results_meta']['types'] = $bucket_values['_types'];
					
					// Remove redundancy
					$bucket_values = array_filter($bucket_values, function($values) use (&$bucket_values) {
						$key = key($bucket_values);
						next($bucket_values);
						
						switch($key) {
							case '_labels':
							case '_types':
								return false;
								break;
								
							default:
								if(preg_match('#^(.*)_loaded$#', $key))
									return false;
								break;
						}
						
						return true;
					});
					
					$values['buckets']['results'][] = $bucket_values;
				}
				break;
				
			case 'members':
				$values = $dictionary;

				if(!isset($values['members']))
					$values['members'] = array(
						'results_meta' => array(
							'labels' => array(),
							'types' => array(),
						),
						'results' => array(),
					);
				
				$rosters = DAO_Group::getRosters();
				
				@$roster = $rosters[$context_id];
				
				if(!is_array($roster) || empty($roster))
					break;
				
				if(is_array($roster))
				foreach($roster as $member) { /* @var $member Model_GroupMember */
					$member_labels = array();
					$member_values = array();
					CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $member->id, $member_labels, $member_values, null, true);
					
					if(empty($member_values))
						continue;
					
					// Ignore disabled
					if(isset($member_values['is_disabled']) && $member_values['is_disabled'])
						continue;
					
					// Add a manager value
					$member_values['is_manager'] = $member->is_manager ? true : false;
					$member_values['_types']['is_manager'] = Model_CustomField::TYPE_CHECKBOX;
					
					// Results meta
					if(is_null($values['members']['results_meta']['labels']))
						$values['members']['results_meta']['labels'] = $member_values['_labels'];
					
					if(is_null($values['members']['results_meta']['types']))
						$values['members']['results_meta']['types'] = $member_values['_types'];
					
					// Lazy load
					$member_dict = new DevblocksDictionaryDelegate($member_values);
					$member_dict->address_;
					$member_dict->custom_;
					$member_values = $member_dict->getDictionary(null, false);
					unset($member_dict);
					
					// Remove redundancy
					$member_values = array_filter($member_values, function($values) use (&$member_values) {
						$key = key($member_values);
						next($member_values);
						
						switch($key) {
							case '_labels':
							case '_types':
							case 'address_':
							case 'custom_':
								return false;
								break;
								
							default:
								if(preg_match('#^(.*)_loaded$#', $key))
									return false;
								break;
						}
						
						return true;
					});
					
					$values['members']['results'][] = $member_values;
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
			SearchFields_Group::UPDATED,
		);
		$view->addParams(array(
		), true);
//		$view->renderSortBy = SearchFields_Group::NAME;
//		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		C4_AbstractViewLoader::setView($view_id, $view);

		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
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
