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

class DAO_Bucket extends Cerb_ORMHelper {
	const CACHE_ALL = 'cerberus_cache_buckets_all';
	
	const ID = 'id';
	const NAME = 'name';
	const GROUP_ID = 'group_id';
	const IS_DEFAULT = 'is_default';
	const REPLY_ADDRESS_ID = 'reply_address_id';
	const REPLY_PERSONAL = 'reply_personal';
	const REPLY_SIGNATURE = 'reply_signature';
	const REPLY_HTML_TEMPLATE_ID = 'reply_html_template_id';
	const UPDATED_AT = 'updated_at';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO bucket () VALUES ()";
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function getGroups() {
		$groups = DAO_Group::getAll();
		$group_buckets = array();
		
		foreach($groups as $group_id => $group) {
			if(false == ($buckets = $group->getBuckets()))
				continue;
			
			foreach($buckets as $bucket_id => $bucket) {
				$group_buckets[$group_id][$bucket_id] = $bucket;
			}
		}
		
		return $group_buckets;
	}
	
	static function getNames(Model_Worker $for_worker=null) {
		$groups = DAO_Group::getAll();
		$names = array();
		
		foreach($groups as $group) {
			$buckets = $group->getBuckets();
			
			if(is_null($for_worker) || $for_worker->isGroupMember($group->id)) {
				foreach($buckets as $bucket) {
					$names[$bucket->id] = $bucket->name;
				}
			}
		}
		
		$names = array_unique($names);
		
		return $names;
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_Bucket[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		if($nocache || null === ($buckets = $cache->load(self::CACHE_ALL))) {
			$buckets = self::getWhere(null, null, false, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($buckets))
				return false;
			
			uasort($buckets, function($a, $b) {
				/* @var $a Model_Bucket */
				/* @var $b Model_Bucket */
				if($a->is_default)
					return -1;
				if($b->is_default)
					return 1;
				
				return strcasecmp($a->name, $b->name);
			});
			
			$cache->save($buckets, self::CACHE_ALL);
		}
		
		return $buckets;
	}
	
	/**
	 *
	 * @param integer $id
	 * @return Model_Bucket
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$buckets = self::getAll();
	
		if(isset($buckets[$id]))
			return $buckets[$id];
			
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_Bucket[]
	 */
	static function getIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);

		if(empty($ids))
			return array();
		
		$buckets = self::getAll();

		$ids = DevblocksPlatform::importVar($ids, 'array:integer');

		$models = array();

		if(is_array($ids) && is_array($buckets))
		foreach($ids as $id) {
			if(isset($buckets[$id]))
				$models[$id] = $buckets[$id];
		}

		return $models;
	}	
	
	/**
	 * 
	 * @param string $where
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param integer $limit
	 * @param array $options
	 * @return Model_Bucket[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=null, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, group_id, reply_address_id, reply_personal, reply_signature, reply_html_template_id, is_default, updated_at ".
			"FROM bucket ".
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
	 * @param array $group_ids
	 * @return Model_Bucket[]
	 */
	static function getByGroup($group_ids) {
		if(!is_array($group_ids))
			$group_ids = array($group_ids);
		
		$group_buckets = array();
		
		$buckets = self::getAll();
		foreach($buckets as $bucket) {
			if(false !== array_search($bucket->group_id, $group_ids)) {
				$group_buckets[$bucket->id] = $bucket;
			}
		}
		return $group_buckets;
	}
	
	/**
	 * 
	 * @param integer $group_id
	 * @return Model_Bucket|NULL
	 */
	static function getDefaultForGroup($group_id) {
		$buckets = DAO_Bucket::getByGroup($group_id);
		
		foreach($buckets as $bucket)
			if($bucket->is_default)
				return $bucket;
			
		return null;
	}
	
	static function getResponsibilities($bucket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$responsibilities = array();
		
		$results = $db->GetArray(sprintf("SELECT worker_id, responsibility_level FROM worker_to_bucket WHERE bucket_id = %d",
			$bucket_id
		));
		
		foreach($results as $row) {
			$responsibilities[$row['worker_id']] = $row['responsibility_level'];
		}
		
		return $responsibilities;
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
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_BUCKET, $batch_ids);
			}

			// Make changes
			parent::_update($batch_ids, 'bucket', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.bucket.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_BUCKET, $batch_ids);
			}
		}
		
		// Clear cache
		self::clearCache();
	}
	
	static function random() {
		return self::_getRandom('bucket');
	}
	
	static function countByGroupId($group_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(id) FROM bucket WHERE group_id = %d",
			$group_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		/*
		 * Notify anything that wants to know when buckets delete.
		 */
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'bucket.delete',
				array(
					'bucket_ids' => $ids,
				)
			)
		);
		
		$default_group = DAO_Group::getDefaultGroup();
			
		$buckets = DAO_Bucket::getIds($ids);
		
		if(is_array($buckets))
		foreach($buckets as $bucket_id => $bucket) {
			if(false == ($group = $bucket->getGroup()))
				continue;
			
			if(false == ($new_bucket = $group->getDefaultBucket()))
				continue;
			
			// Reset any tickets using this bucket
			if($new_bucket->id != $bucket_id) {
				$db->ExecuteMaster(sprintf("UPDATE ticket SET bucket_id = %d WHERE bucket_id = %d",
					$new_bucket->id,
					$bucket_id
				));
				
			// If this was the default bucket for the group, use the global default
			} else {
				
				if($default_group && false != ($default_bucket = $default_group->getDefaultBucket())) {
					$db->ExecuteMaster(sprintf("UPDATE ticket SET group_id = %d, bucket_id = %d WHERE bucket_id = %d",
						$default_group->id,
						$default_bucket->id,
						$bucket_id
					));
				}
				
			}
		}

		$sql = sprintf("DELETE FROM worker_to_bucket WHERE bucket_id IN (%s)", implode(',',$ids));
		$db->ExecuteMaster($sql);
		
		$sql = sprintf("DELETE FROM bucket WHERE id IN (%s)", implode(',',$ids));
		$db->ExecuteMaster($sql);
		
		self::clearCache();
	}
	
	static public function maint() {
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_BUCKET,
					'context_table' => 'bucket',
					'context_key' => 'id',
				)
			)
		);
	}
	
	private static function _getObjectsFromResult($rs) {
		$buckets = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$bucket = new Model_Bucket();
			$bucket->id = intval($row['id']);
			$bucket->name = $row['name'];
			$bucket->group_id = intval($row['group_id']);
			$bucket->reply_address_id = $row['reply_address_id'];
			$bucket->reply_personal = $row['reply_personal'];
			$bucket->reply_signature = $row['reply_signature'];
			$bucket->reply_html_template_id = $row['reply_html_template_id'];
			$bucket->is_default = !empty($row['is_default']) ? 1 : 0;
			$bucket->updated_at = intval($row['updated_at']);
			$buckets[$bucket->id] = $bucket;
		}
		
		mysqli_free_result($rs);
		
		return $buckets;
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Bucket::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"bucket.id as %s, ".
			"bucket.group_id as %s, ".
			"bucket.name as %s, ".
			"bucket.reply_address_id as %s, ".
			"bucket.reply_personal as %s, ".
			"bucket.reply_signature as %s, ".
			"bucket.reply_html_template_id as %s, ".
			"bucket.updated_at as %s, ".
			"bucket.is_default as %s ",
				SearchFields_Bucket::ID,
				SearchFields_Bucket::GROUP_ID,
				SearchFields_Bucket::NAME,
				SearchFields_Bucket::REPLY_ADDRESS_ID,
				SearchFields_Bucket::REPLY_PERSONAL,
				SearchFields_Bucket::REPLY_SIGNATURE,
				SearchFields_Bucket::REPLY_HTML_TEMPLATE_ID,
				SearchFields_Bucket::UPDATED_AT,
				SearchFields_Bucket::IS_DEFAULT
			);
			
		$join_sql = "FROM bucket ".
			(isset($tables['context_link']) ? sprintf("INNER JOIN context_link ON (context_link.to_context = %s AND context_link.to_context_id = bucket.id) ", Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_BUCKET)) : " ").
			'';
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'bucket.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields);
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
	
		array_walk_recursive(
			$params,
			array('DAO_Bucket', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'bucket',
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
			
		$from_context = CerberusContexts::CONTEXT_BUCKET;
		$from_index = 'bucket.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_Bucket::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_Bucket::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_Bucket::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
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
			($has_multiple_values ? 'GROUP BY bucket.id ' : '').
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
			$object_id = intval($row[SearchFields_Bucket::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT bucket.id) " : "SELECT COUNT(bucket.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
};

class SearchFields_Bucket implements IDevblocksSearchFields {
	const ID = 'b_id';
	const GROUP_ID = 'b_group_id';
	const NAME = 'b_name';
	const REPLY_ADDRESS_ID = 'b_reply_address_id';
	const REPLY_PERSONAL = 'b_reply_personal';
	const REPLY_SIGNATURE = 'b_reply_signature';
	const REPLY_HTML_TEMPLATE_ID = 'b_reply_html_template_id';
	const UPDATED_AT = 'b_updated_at';
	const IS_DEFAULT = 'b_is_default';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'bucket', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::GROUP_ID => new DevblocksSearchField(self::GROUP_ID, 'bucket', 'group_id', $translate->_('common.group'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'bucket', 'name', $translate->_('common.name'), null, true),
			self::REPLY_ADDRESS_ID => new DevblocksSearchField(self::REPLY_ADDRESS_ID, 'bucket', 'reply_address_id', $translate->_('dao.bucket.reply_address_id'), null, true),
			self::REPLY_PERSONAL => new DevblocksSearchField(self::REPLY_PERSONAL, 'bucket', 'reply_personal', $translate->_('dao.bucket.reply_personal'), null, true),
			self::REPLY_SIGNATURE => new DevblocksSearchField(self::REPLY_SIGNATURE, 'bucket', 'reply_signature', $translate->_('dao.bucket.reply_signature'), null, true),
			self::REPLY_HTML_TEMPLATE_ID => new DevblocksSearchField(self::REPLY_HTML_TEMPLATE_ID, 'bucket', 'reply_html_template_id', $translate->_('dao.bucket.reply_html_template_id'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'bucket', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::IS_DEFAULT => new DevblocksSearchField(self::IS_DEFAULT, 'bucket', 'is_default', $translate->_('common.default'), Model_CustomField::TYPE_CHECKBOX, true),
				
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null, null, false),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null, null, false),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			CerberusContexts::CONTEXT_BUCKET,
		));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Bucket {
	public $id;
	public $name = '';
	public $group_id = 0;
	public $reply_address_id = 0;
	public $reply_personal;
	public $reply_signature;
	public $reply_html_template_id = 0;
	public $is_default = 0;
	public $updated_at = 0;
	
	/**
	 * 
	 * @return Model_Group
	 */
	public function getGroup() {
		return DAO_Group::get($this->group_id);
	}
	
	public function getResponsibilities() {
		return DAO_Bucket::getResponsibilities($this->id);
	}
	
	/**
	 *
	 * @param integer $bucket_id
	 * @return Model_AddressOutgoing
	 */
	public function getReplyTo() {
		$from_id = 0;
		$froms = DAO_AddressOutgoing::getAll();
		
		// Cascade to bucket
		$from_id = $this->reply_address_id;
		
		// Cascade to group default
		if(empty($from_id) && empty($this->is_default)) {
			$default_bucket = DAO_Bucket::getDefaultForGroup($this->group_id);
			$from_id = $default_bucket->reply_address_id;
		}
		
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
	
	public function getReplyFrom() {
		$froms = DAO_AddressOutgoing::getAll();
		$default_from = DAO_AddressOutgoing::getDefault();
		$default_bucket = DAO_Bucket::getDefaultForGroup($this->group_id);
		
		// Check this bucket
		$from_id = $this->reply_address_id;

		if($from_id && isset($froms[$from_id]))
			return $from_id;

		// Cascade to group default
		if($default_bucket 
				&& $default_bucket->id != $this->id
				&& $from_id = $default_bucket->reply_address_id 
				&& isset($froms[$from_id]))
					return $from_id;
		
		// Cascade to global
		if($default_from 
				&& $from_id = $default_from->address_id
				&& isset($froms[$from_id]))
					return $from_id;
			
		return $from_id;
	}
	
	public function getReplyPersonal($worker_model=null) {
		$froms = DAO_AddressOutgoing::getAll();
		$default_from = DAO_AddressOutgoing::getDefault();
		$default_bucket = DAO_Bucket::getDefaultForGroup($this->group_id);
		
		// Check bucket first
		$personal = $this->reply_personal;
		
		// Cascade to bucket address
		if(empty($personal) 
				&& $this->reply_address_id
				&& isset($froms[$this->reply_address_id])
				&& $from = $froms[$this->reply_address_id])
					$personal = $from->reply_personal;
				
		// Cascade to group default bucket
		if(empty($personal)
				&& $default_bucket
				&& $default_bucket->id != $this->id) {
					$personal = $default_bucket->getReplyPersonal($worker_model);
		}
		
		// Cascade to global
		if(empty($personal) 
				&& $default_from)
					$personal = $default_from->reply_personal;
		
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
	
	public function getReplySignature($worker_model=null) {
		$froms = DAO_AddressOutgoing::getAll();
		$default_from = DAO_AddressOutgoing::getDefault();
		$default_bucket = DAO_Bucket::getDefaultForGroup($this->group_id);
		
		// Check bucket first
		$signature = $this->reply_signature;
		
		// Cascade to bucket address
		if(empty($signature) 
				&& $this->reply_address_id
				&& isset($froms[$this->reply_address_id])
				&& $from = $froms[$this->reply_address_id])
					$signature = $from->reply_signature;
		
		// Cascade to group default bucket
		if(empty($signature) 
				&& $default_bucket
				&& $default_bucket->id != $this->id)
					$signature = $default_bucket->reply_signature;
		
		// Cascade to global
		if(empty($signature) 
				&& $default_from)
					$signature = $default_from->reply_signature;
		
		// If we have a worker model, convert template tokens
		if(empty($worker_model))
			$worker_model = new Model_Worker();
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$token_labels = array();
		$token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_model, $token_labels, $token_values);
		$signature = $tpl_builder->build($signature, $token_values);
		
		return $signature;
	}
	
	public function getReplyHtmlTemplate() {
		$froms = DAO_AddressOutgoing::getAll();
		$default_from = DAO_AddressOutgoing::getDefault();
		$default_bucket = DAO_Bucket::getDefaultForGroup($this->group_id);
		
		// Check bucket first
		$html_template_id = $this->reply_html_template_id;
		
		// Cascade to bucket address
		if(empty($html_template_id) 
				&& $this->reply_address_id
				&& isset($froms[$this->reply_address_id])
				&& $from = $froms[$this->reply_address_id])
					$html_template_id = $from->reply_html_template_id;
		
		// Cascade to group default bucket
		if(empty($html_template_id) 
				&& $default_bucket
				&& $default_bucket->id != $this->id)
					$html_template_id = $default_bucket->reply_html_template_id;
		
		// Cascade to global
		if(empty($html_template_id) 
				&& $default_from)
					$html_template_id = $default_from->reply_html_template_id;
			
		if($html_template_id)
			return DAO_MailHtmlTemplate::get($html_template_id);
		
		return null;
	}
};

class Context_Bucket extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_BUCKET;
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=bucket&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$bucket = DAO_Bucket::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($bucket->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $bucket->id,
			'name' => $bucket->name,
			'permalink' => $url,
			'updated' => $bucket->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			// [TODO] Check group
			//if($worker->isGroupMember($context_id))
				
			return TRUE;
				
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	/**
	 * @return Model_Bucket
	 * @see Extension_DevblocksContext::getRandom()
	 */
	function getRandom() {
		return DAO_Bucket::random();
	}
	
	function getContext($bucket, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Bucket:';
			
		$translate = DevblocksPlatform::getTranslationService();
		
		// Polymorph
		if(is_numeric($bucket)) {
			$bucket = DAO_Bucket::get($bucket);
			
		} elseif($bucket instanceof Model_Bucket) {
			// It's what we want already.
			
		} elseif(is_array($bucket)) {
			$bucket = Cerb_ORMHelper::recastArrayToModel($bucket, 'Model_Bucket');
			
		} else {
			$bucket = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'is_default' => $prefix.$translate->_('common.default'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_default' => Model_CustomField::TYPE_CHECKBOX,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom fields
		
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_BUCKET);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();

		$token_values['_context'] = CerberusContexts::CONTEXT_BUCKET;
		$token_values['_types'] = $token_types;
		
		// Token values
		if(null != $bucket) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $bucket->name;
			$token_values['id'] = $bucket->id;
			$token_values['is_default'] = $bucket->is_default;
			$token_values['name'] = $bucket->name;
			$token_values['updated_at'] = $bucket->updated_at;
			
			if(false != ($replyto = $bucket->getReplyTo()))
				$token_values['replyto_id'] = $replyto->address_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($bucket, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=bucket&id=%d-%s",$bucket->id, DevblocksPlatform::strToPermalink($bucket->name)), true);
		}
		
		// Reply-To Address
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::scrubTokensWithRegexp(
			$merge_token_labels,
			$merge_token_values,
			array(
				'#^contact_(.*)$#',
				'#^org_(.*)$#',
			)
		);
		
		CerberusContexts::merge(
			'replyto_',
			$prefix.'Reply To:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_BUCKET;
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
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Buckets';
		$view->addParams(array(
//			SearchFields_Bucket::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Bucket::IS_DISABLED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_Bucket::NAME;
		$view->renderSortAsc = true;
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
		$view->name = 'Buckets';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				//new DevblocksSearchCriteria(SearchFields_Bucket::CONTEXT_LINK,'=',$context),
				//new DevblocksSearchCriteria(SearchFields_Bucket::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if($context_id && null != ($bucket = DAO_Bucket::get($context_id))) {
			$tpl->assign('bucket', $bucket);
		
			if(false != ($group = $bucket->getGroup())) {
				$tpl->assign('group', $group);
				$tpl->assign('members', $group->getMembers());
			}
		}
		
		// Groups
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Reply-to Addresses
		
		$tpl->assign('replyto_addresses', DAO_AddressOutgoing::getAll());
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_BUCKET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_BUCKET, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}
		
		// Signature
		
		$worker_token_labels = array();
		$worker_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $worker_token_labels, $worker_token_values);
		$tpl->assign('worker_token_labels', $worker_token_labels);

		// HTML templates
		
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);

		// Template
		
		if($edit) {
			// ACL
			
			if(empty($bucket) && !$active_worker->isGroupManager()) {
				$tpl->assign('error_message', "You can only create new buckets if you're the manager of at least one group.");
				$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
				return;
			}
			
			if(!empty($bucket) && !$active_worker->isGroupManager($bucket->group_id)) {
				$tpl->assign('error_message', "Only group managers can modify this bucket.");
				$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
				return;
			}
			
			$tpl->display('devblocks:cerberusweb.core::internal/bucket/peek_edit.tpl');
			
		} else {
			$activity_counts = array(
				'tickets' => DAO_Ticket::countsByBucketId($context_id),
				'comments' => DAO_Comment::count(CerberusContexts::CONTEXT_BUCKET, $context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			$links = array(
				CerberusContexts::CONTEXT_BUCKET => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							CerberusContexts::CONTEXT_BUCKET,
							$context_id,
							array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			$tpl->display('devblocks:cerberusweb.core::internal/bucket/peek.tpl');
			
		}
		
	}
};

class View_Bucket extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'buckets';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = mb_convert_case($translate->_('common.buckets'), MB_CASE_TITLE);
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Bucket::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Bucket::NAME,
			SearchFields_Bucket::GROUP_ID,
			SearchFields_Bucket::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Bucket::REPLY_ADDRESS_ID,
			SearchFields_Bucket::REPLY_PERSONAL,
			SearchFields_Bucket::REPLY_SIGNATURE,
			SearchFields_Bucket::REPLY_HTML_TEMPLATE_ID,
			SearchFields_Bucket::VIRTUAL_CONTEXT_LINK,
			SearchFields_Bucket::VIRTUAL_HAS_FIELDSET,
			SearchFields_Bucket::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Bucket::search(
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
		return $this->_getDataAsObjects('DAO_Bucket', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Bucket', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
//				case SearchFields_Bucket::EXAMPLE:
//					$pass = true;
//					break;
					
				// Virtuals
				case SearchFields_Bucket::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Bucket::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Bucket::VIRTUAL_WATCHERS:
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
//			case SearchFields_Bucket::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_Bucket', $column);
//				break;

//			case SearchFields_Bucket::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn('DAO_Bucket', $column);
//				break;
				
			case SearchFields_Bucket::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_Bucket', CerberusContexts::CONTEXT_BUCKET, $column);
				break;

			case SearchFields_Bucket::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_Bucket', CerberusContexts::CONTEXT_BUCKET, $column);
				break;
				
			case SearchFields_Bucket::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_Bucket', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Bucket', $column, 'bucket.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Bucket::getFields();
		// [TODO] Group name
	
		$fields = array(
			'_fulltext' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Bucket::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'group' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Bucket::GROUP_ID),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Bucket::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Bucket::UPDATED_AT),
				),
			/*
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Bucket::VIRTUAL_WATCHERS),
				),
			*/
		);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_BUCKET, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
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
				case 'group':
					$field_key = SearchFields_Bucket::GROUP_ID;
					
					$oper = DevblocksSearchCriteria::OPER_IN;
					
					if(preg_match('#^([\!\=]+)(.*)#', $v, $matches)) {
						$oper_hint = trim($matches[1]);
						$v = trim($matches[2]);
						
						switch($oper_hint) {
							case '!':
							case '!=':
								$oper = DevblocksSearchCriteria::OPER_NIN;
								break;
								
							default:
								$oper = DevblocksSearchCriteria::OPER_IN;
								break;
						}
					}
					
					$groups = DAO_Group::getAll();
					$patterns = DevblocksPlatform::parseCsvString($v);
					
					if(!is_array($patterns))
						break;
					
					$group_ids = array();
					
					foreach($patterns as $pattern) {
						// Match IDs
						if(is_numeric($pattern) && isset($groups[$pattern])) {
							$group_id = intval($pattern);
							$group_ids[$group_id] = true;
							continue;
						}
							
						foreach($groups as $group_id => $group) {
							if(isset($group_ids[$group_id]))
								continue;
							
							if(false !== stristr($group->name, $pattern)) {
								$group_ids[$group_id] = true;
							}
						}
					}
					
					if(!empty($group_ids)) {
						$params[$field_key] = new DevblocksSearchCriteria(
							$field_key,
							$oper,
							array_keys($group_ids)
						);
					}
					break;
			}
		}
		
		return $params;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_BUCKET);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/bucket/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Bucket::NAME:
			case SearchFields_Bucket::REPLY_PERSONAL:
			case SearchFields_Bucket::REPLY_SIGNATURE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Bucket::ID:
			case SearchFields_Bucket::REPLY_ADDRESS_ID:
			case SearchFields_Bucket::REPLY_HTML_TEMPLATE_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_Bucket::IS_DEFAULT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Bucket::UPDATED_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Bucket::GROUP_ID:
				// [TODO]
				break;
				
			case SearchFields_Bucket::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_Bucket::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_BUCKET);
				break;
				
			case SearchFields_Bucket::VIRTUAL_WATCHERS:
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
			case SearchFields_Bucket::GROUP_ID:
				$groups = DAO_Group::getAll();
				$strings = array();

				foreach($values as $val) {
					if(!isset($groups[$val]))
						continue;

					$strings[] = DevblocksPlatform::strEscapeHtml($groups[$val]->name);
				}
				echo implode(", ", $strings);
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
			case SearchFields_Bucket::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Bucket::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_Bucket::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Bucket::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Bucket::NAME:
			case SearchFields_Bucket::REPLY_PERSONAL:
			case SearchFields_Bucket::REPLY_SIGNATURE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Bucket::ID:
			case SearchFields_Bucket::REPLY_ADDRESS_ID:
			case SearchFields_Bucket::REPLY_HTML_TEMPLATE_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Bucket::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Bucket::IS_DEFAULT:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Bucket::GROUP_ID:
				break;
				
			case SearchFields_Bucket::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Bucket::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Bucket::VIRTUAL_WATCHERS:
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
					//$change_fields[DAO_Bucket::EXAMPLE] = 'some value';
					break;
					
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
			list($objects,$null) = DAO_Bucket::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Bucket::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_Bucket::update($batch_ids, $change_fields);
			}

			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_BUCKET, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};
