<?php
class DAO_ContextAvatar extends Cerb_ORMHelper {
	const CONTENT_TYPE = 'content_type';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const ID = 'id';
	const IS_APPROVED = 'is_approved';
	const STORAGE_EXTENSION = 'storage_extension';
	const STORAGE_KEY = 'storage_key';
	const STORAGE_PROFILE_ID = 'storage_profile_id';
	const STORAGE_SIZE = 'storage_size';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		$validation
			->addField(self::CONTENT_TYPE)
			->string()
			;
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_APPROVED)
			->bit()
			;
		$validation
			->addField(self::STORAGE_EXTENSION)
			->string()
			;
		$validation
			->addField(self::STORAGE_KEY)
			->string()
			;
		$validation
			->addField(self::STORAGE_PROFILE_ID)
			->id()
			;
		$validation
			->addField(self::STORAGE_SIZE)
			->uint()
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		
		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO context_avatar () VALUES ()";
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
				//CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'context_avatar', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.context_avatar.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				//DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('context_avatar', $fields, $where);
	}
	
	static function upsertWithImage($context, $context_id, $imagedata, $content_type='image/png') {
		if(empty($imagedata) || !is_string($imagedata))
			return false;
		
		// We're given a data URL
		if(DevblocksPlatform::strStartsWith($imagedata,'data:')) {
			$imagedata = substr($imagedata, 5);
			
			// Are we deleting it?
			if($imagedata == 'null') {
				$content_type = '';
				$imagedata = '';
				
			// Is it a base64-encoded png?
			} else if(DevblocksPlatform::strStartsWith($imagedata,'image/png;base64,')) {
				$content_type = 'image/png';
				
				// Decode it to binary
				if(false == ($imagedata = base64_decode(substr($imagedata, 17))))
					return false;
				
				// [TODO] Verify the "magic bytes"
				// [TODO] 89 50 4E 47 0D 0A 1A 0A
				
			// If we don't know what it is, fail.
			} else {
				return false;
			}
		}
		
		if(false == ($avatar = DAO_ContextAvatar::getByContext($context, $context_id))) {
			$fields = array(
				DAO_ContextAvatar::CONTEXT => $context,
				DAO_ContextAvatar::CONTEXT_ID => $context_id,
				DAO_ContextAvatar::CONTENT_TYPE => $content_type,
				DAO_ContextAvatar::IS_APPROVED => 1,
				DAO_ContextAvatar::UPDATED_AT => time(),
			);
			$avatar_id = DAO_ContextAvatar::create($fields);
		} else {
			$fields = array(
				DAO_ContextAvatar::CONTENT_TYPE => $content_type,
				DAO_ContextAvatar::IS_APPROVED => 1,
				DAO_ContextAvatar::UPDATED_AT => time(),
			);
			$avatar_id = $avatar->id;
			DAO_ContextAvatar::update($avatar_id, $fields);
		}
		
		if($avatar_id) {
			// Save the image data
			Storage_ContextAvatar::put($avatar_id, $imagedata);
			
			// Context-specific cascading cache invalidation
			switch($context) {
				case CerberusContexts::CONTEXT_CONTACT:
					// Clear the cache on address avatars when their contact avatar changes
					DAO_Address::updateWhere(array(DAO_Address::UPDATED=>time()), sprintf("%s = %d", DAO_Address::CONTACT_ID, $context_id));
					break;
					
				case CerberusContexts::CONTEXT_ORG:
					// Clear the cache on non-contact address avatars when their org avatar changes
					DAO_Address::updateWhere(array(DAO_Address::UPDATED=>time()), sprintf("%s = 0 AND %s = %d", DAO_Address::CONTACT_ID, DAO_Address::CONTACT_ORG_ID, $context_id));
					break;
			}
		}
		
		return true;
	}
	
	/**
	 * 
	 * @param string $context
	 * @param int $context_id
	 * @return Model_ContextAvatar
	 */
	static function getByContext($context, $context_id) {
		$db = DevblocksPlatform::services()->database();
		
		$results = self::getWhere(sprintf("(%s = %s AND %s = %d)",
			$db->escape(DAO_ContextAvatar::CONTEXT),
			$db->qstr($context),
			$db->escape(DAO_ContextAvatar::CONTEXT_ID),
			$context_id
		));
		
		if(is_array($results) && !empty($results))
			return array_shift($results);
		
		return false;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContextAvatar[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, context, context_id, content_type, is_approved, updated_at, storage_extension, storage_key, storage_size, storage_profile_id ".
			"FROM context_avatar ".
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
	 * @param bool $nocache
	 * @return Model_ContextAvatar[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_ContextAvatar::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			
			// if(!is_array($objects))
			//	return false;
			
			//$cache->save($buckets, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_ContextAvatar
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
	 * @return Model_ContextAvatar[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_ContextAvatar[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ContextAvatar();
			$object->id = $row['id'];
			$object->context = $row['context'];
			$object->context_id = $row['context_id'];
			$object->content_type = $row['content_type'];
			$object->is_approved = $row['is_approved'];
			$object->updated_at = $row['updated_at'];
			$object->storage_extension = $row['storage_extension'];
			$object->storage_key = $row['storage_key'];
			$object->storage_size = $row['storage_size'];
			$object->storage_profile_id = $row['storage_profile_id'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('context_avatar');
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		Storage_ContextAvatar::delete($ids);
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM context_avatar WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_CONTEXT_AVATAR,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	static function deleteByContext($context, $context_ids) {
		// Don't recurse
		if($context == CerberusContexts::CONTEXT_CONTEXT_AVATAR)
			return;
		
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		$db = DevblocksPlatform::services()->database();
		
		$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'integer');
		
		if(empty($context_ids))
			return;
		
		$ids = array();
		
		$results = $db->GetArrayMaster(sprintf("SELECT id FROM context_avatar WHERE %s = %s AND %s IN (%s)",
			$db->escape(DAO_ContextAvatar::CONTEXT),
			$db->qstr($context),
			$db->escape(DAO_ContextAvatar::CONTEXT_ID),
			implode(',', $context_ids)
		));
		
		if(!is_array($results) || empty($results))
			return;
		
		foreach($results as $result)
			$ids[] = $result['id'];
		
		self::delete($ids);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContextAvatar::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ContextAvatar', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"context_avatar.id as %s, ".
			"context_avatar.context as %s, ".
			"context_avatar.context_id as %s, ".
			"context_avatar.content_type as %s, ".
			"context_avatar.is_approved as %s, ".
			"context_avatar.updated_at as %s, ".
			"context_avatar.storage_extension as %s, ".
			"context_avatar.storage_key as %s, ".
			"context_avatar.storage_size as %s, ".
			"context_avatar.storage_profile_id as %s ",
				SearchFields_ContextAvatar::ID,
				SearchFields_ContextAvatar::CONTEXT,
				SearchFields_ContextAvatar::CONTEXT_ID,
				SearchFields_ContextAvatar::CONTENT_TYPE,
				SearchFields_ContextAvatar::IS_APPROVED,
				SearchFields_ContextAvatar::UPDATED_AT,
				SearchFields_ContextAvatar::STORAGE_EXTENSION,
				SearchFields_ContextAvatar::STORAGE_KEY,
				SearchFields_ContextAvatar::STORAGE_SIZE,
				SearchFields_ContextAvatar::STORAGE_PROFILE_ID
			);
			
		$join_sql = "FROM context_avatar ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ContextAvatar');
	
		return array(
			'primary_table' => 'context_avatar',
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
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::services()->database();
		
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
			$object_id = intval($row[SearchFields_ContextAvatar::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(context_avatar.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_ContextAvatar extends DevblocksSearchFields {
	const ID = 'c_id';
	const CONTEXT = 'c_context';
	const CONTEXT_ID = 'c_context_id';
	const CONTENT_TYPE = 'c_content_type';
	const IS_APPROVED = 'c_is_approved';
	const UPDATED_AT = 'c_updated_at';
	const STORAGE_EXTENSION = 'c_storage_extension';
	const STORAGE_KEY = 'c_storage_key';
	const STORAGE_SIZE = 'c_storage_size';
	const STORAGE_PROFILE_ID = 'c_storage_profile_id';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'context_avatar.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_CONTEXT_AVATAR => new DevblocksSearchFieldContextKeys('context_avatar.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_CONTEXT_AVATAR, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_CONTEXT_AVATAR, self::getPrimaryKey());
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
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ContextAvatar::ID:
				$models = DAO_ContextAvatar::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'context_avatar', 'id', $translate->_('dao.context_avatar.id'), Model_CustomField::TYPE_NUMBER, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'context_avatar', 'context', $translate->_('dao.context_avatar.context'), null, true),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'context_avatar', 'context_id', $translate->_('dao.context_avatar.context_id'), null, true),
			self::CONTENT_TYPE => new DevblocksSearchField(self::CONTENT_TYPE, 'context_avatar', 'content_type', $translate->_('dao.context_avatar.content_type'), null, true),
			self::IS_APPROVED => new DevblocksSearchField(self::IS_APPROVED, 'context_avatar', 'is_approved', $translate->_('dao.context_avatar.is_approved'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'context_avatar', 'updated_at', $translate->_('dao.context_avatar.updated_at'), Model_CustomField::TYPE_DATE, true),
			self::STORAGE_EXTENSION => new DevblocksSearchField(self::STORAGE_EXTENSION, 'context_avatar', 'storage_extension', $translate->_('dao.context_avatar.storage_extension'), null, true),
			self::STORAGE_KEY => new DevblocksSearchField(self::STORAGE_KEY, 'context_avatar', 'storage_key', $translate->_('dao.context_avatar.storage_key'), null, true),
			self::STORAGE_SIZE => new DevblocksSearchField(self::STORAGE_SIZE, 'context_avatar', 'storage_size', $translate->_('dao.context_avatar.storage_size'), Model_CustomField::TYPE_NUMBER, true),
			self::STORAGE_PROFILE_ID => new DevblocksSearchField(self::STORAGE_PROFILE_ID, 'context_avatar', 'storage_profile_id', $translate->_('dao.context_avatar.storage_profile_id'), Model_CustomField::TYPE_NUMBER, true),

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

class Model_ContextAvatar {
	public $id;
	public $context;
	public $context_id;
	public $content_type;
	public $is_approved;
	public $updated_at;
	public $storage_extension;
	public $storage_key;
	public $storage_size;
	public $storage_profile_id;
};

class Storage_ContextAvatar extends Extension_DevblocksStorageSchema {
	const ID = 'cerberusweb.storage.schema.context_avatar';
	
	public static function getActiveStorageProfile() {
		return DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile', 'devblocks.storage.engine.disk');
	}

	function render() {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 0));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/context_avatar/render.tpl");
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 0));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/context_avatar/config.tpl");
	}
	
	function saveConfig() {
		@$active_storage_profile = DevblocksPlatform::importGPC($_REQUEST['active_storage_profile'],'string','');
		@$archive_storage_profile = DevblocksPlatform::importGPC($_REQUEST['archive_storage_profile'],'string','');
		@$archive_after_days = DevblocksPlatform::importGPC($_REQUEST['archive_after_days'],'integer',0);
		
		if(!empty($active_storage_profile))
			$this->setParam('active_storage_profile', $active_storage_profile);
		
		if(!empty($archive_storage_profile))
			$this->setParam('archive_storage_profile', $archive_storage_profile);

		$this->setParam('archive_after_days', $archive_after_days);
		
		return true;
	}
	
	/**
	 * @param Model_ContextAvatar | $avatar_id
	 * @return mixed
	 */
	public static function get($object, &$fp=null) {
		if($object instanceof Model_ContextAvatar) {
			// Do nothing
		} elseif(is_numeric($object)) {
			$object = DAO_ContextAvatar::get($object);
		} else {
			$object = null;
		}

		if(empty($object))
			return false;
		
		$key = $object->storage_key;
		$profile = !empty($object->storage_profile_id) ? $object->storage_profile_id : $object->storage_extension;
		
		if(empty($key))
			return false;
		
		if(false === ($storage = DevblocksPlatform::getStorageService($profile)))
			return false;
			
		return $storage->get('context_avatar', $key, $fp);
	}
	
	public static function put($id, $contents, $profile=null) {
		if(empty($profile)) {
			$profile = self::getActiveStorageProfile();
		}
		
		$profile_id = 0;
		
		if($profile instanceof Model_DevblocksStorageProfile) {
			$profile_id = $profile->id;
		} elseif(is_numeric($profile)) {
			$profile_id = intval($profile);
		}

		if(false === ($storage = DevblocksPlatform::getStorageService($profile))) {
			return false;
		}

		if(is_resource($contents)) {
			$stats = fstat($contents);
			$storage_size = $stats['size'];
		} else {
			$storage_size = strlen($contents);
		}
		
		// Save to storage
		if(false === ($storage_key = $storage->put('context_avatar', $id, $contents)))
			return false;
			
		// Update storage key
		DAO_ContextAvatar::update($id, array(
			DAO_ContextAvatar::STORAGE_EXTENSION => $storage->manifest->id,
			DAO_ContextAvatar::STORAGE_PROFILE_ID => $profile_id,
			DAO_ContextAvatar::STORAGE_KEY => $storage_key,
			DAO_ContextAvatar::STORAGE_SIZE => $storage_size,
		));
		
		return $storage_key;
	}
	
	public static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT storage_extension, storage_key, storage_profile_id FROM context_avatar WHERE id IN (%s)", implode(',',$ids));
		
		if(false == ($rs = $db->ExecuteSlave($sql)))
			return false;
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		// Delete the physical files
		
		while($row = mysqli_fetch_assoc($rs)) {
			$profile = !empty($row['storage_profile_id']) ? $row['storage_profile_id'] : $row['storage_extension'];
			
			if(null != ($storage = DevblocksPlatform::getStorageService($profile)))
				if(false === $storage->delete('context_avatar', $row['storage_key']))
					return FALSE;
		}
		
		mysqli_free_result($rs);
		
		return true;
	}
	
	public static function deleteByContext($context, $context_ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'integer');
		
		if(empty($context_ids))
			return;
		
		$results = DAO_ContextAvatar::getWhere(sprintf("%s = %s AND %s IN (%s)",
			$db->escape(DAO_ContextAvatar::CONTEXT),
			$db->qstr($context),
			$db->escape(DAO_ContextAvatar::CONTEXT_ID),
			implode(',', $context_ids)
		));
		
		if(is_array($results) && !empty($results))
			self::delete(array_keys($results));
		
		return true;
	}
	
	public function getStats() {
		return $this->_stats('context_avatar');
	}
	
	public static function archive($stop_time=null) {
		$db = DevblocksPlatform::services()->database();
		
		// Params
		$src_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile'));
		$dst_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_storage_profile'));
		$archive_after_days = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_after_days');
		
		if(empty($src_profile) || empty($dst_profile))
			return;
		
		if(json_encode($src_profile) == json_encode($dst_profile))
			return;
		
		// Find inactive avatars
		$sql = sprintf("SELECT id, storage_extension, storage_key, storage_profile_id, storage_size ".
			"FROM context_avatar ".
			"WHERE updated_at < %d ".
			"AND (storage_extension = %s AND storage_profile_id = %d) ".
			"ORDER BY id ASC ",
				time()-(86400*$archive_after_days),
				$db->qstr($src_profile->extension_id),
				$src_profile->id
		);

		if(false == ($rs = $db->ExecuteSlave($sql)))
			return false;
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			self::_migrate($dst_profile, $row);

			if(time() > $stop_time)
				return;
		}
	}
	
	public static function unarchive($stop_time=null) {
		// We don't want to unarchive avatars under any condition
	}
	
	private static function _migrate($dst_profile, $row, $is_unarchive=false) {
		$logger = DevblocksPlatform::services()->log();
		
		$ns = 'context_avatar';
		
		$src_key = $row['storage_key'];
		$src_id = $row['id'];
		$src_size = $row['storage_size'];
		
		$src_profile = new Model_DevblocksStorageProfile();
		$src_profile->id = $row['storage_profile_id'];
		$src_profile->extension_id = $row['storage_extension'];
		
		if(empty($src_key) || empty($src_id)
			|| !$src_profile instanceof Model_DevblocksStorageProfile
			|| !$dst_profile instanceof Model_DevblocksStorageProfile
			)
			return;
		
		$src_engine = DevblocksPlatform::getStorageService(!empty($src_profile->id) ? $src_profile->id : $src_profile->extension_id);
		
		$logger->info(sprintf("[Storage] %s %s %d (%d bytes) from (%s) to (%s)...",
			(($is_unarchive) ? 'Unarchiving' : 'Archiving'),
			$ns,
			$src_id,
			$src_size,
			$src_profile->extension_id,
			$dst_profile->extension_id
		));

		// Do as quicker strings if under 1MB?
		$is_small = ($src_size < 1000000) ? true : false;
		
		// Allocate a temporary file for retrieving content
		if($is_small) {
			if(false === ($data = $src_engine->get($ns, $src_key))) {
				$logger->error(sprintf("[Storage] Error reading %s key (%s) from (%s)",
					$ns,
					$src_key,
					$src_profile->extension_id
				));
				return;
			}
		} else {
			$fp_in = DevblocksPlatform::getTempFile();
			if(false === $src_engine->get($ns, $src_key, $fp_in)) {
				$logger->error(sprintf("[Storage] Error reading %s key (%s) from (%s)",
					$ns,
					$src_key,
					$src_profile->extension_id
				));
				return;
			}
		}

		if($is_small) {
			$loaded_size = strlen($data);
		} else {
			$stats_in = fstat($fp_in);
			$loaded_size = $stats_in['size'];
		}
		
		$logger->info(sprintf("[Storage] Loaded %d bytes of data from (%s)...",
			$loaded_size,
			$src_profile->extension_id
		));
		
		if($is_small) {
			if(false === ($dst_key = self::put($src_id, $data, $dst_profile))) {
				$logger->error(sprintf("[Storage] Error saving %s %d to (%s)",
					$ns,
					$src_id,
					$dst_profile->extension_id
				));
				unset($data);
				return;
			}
		} else {
			if(false === ($dst_key = self::put($src_id, $fp_in, $dst_profile))) {
				$logger->error(sprintf("[Storage] Error saving %s %d to (%s)",
					$ns,
					$src_id,
					$dst_profile->extension_id
				));
				if(is_resource($fp_in))
					fclose($fp_in);
				return;
			}
		}
		
		$logger->info(sprintf("[Storage] Saved %s %d to destination (%s) as key (%s)...",
			$ns,
			$src_id,
			$dst_profile->extension_id,
			$dst_key
		));
		
		// Free resources
		if($is_small) {
			unset($data);
		} else {
			@unlink(DevblocksPlatform::getTempFileInfo($fp_in));
			if(is_resource($fp_in))
				fclose($fp_in);
		}
		
		$src_engine->delete($ns, $src_key);
		$logger->info(sprintf("[Storage] Deleted %s %d from source (%s)...",
			$ns,
			$src_id,
			$src_profile->extension_id
		));
		
		$logger->info(''); // blank
	}
};
