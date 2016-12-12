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

class DAO_Attachment extends Cerb_ORMHelper {
	const ID = 'id';
	const DISPLAY_NAME = 'display_name';
	const MIME_TYPE = 'mime_type';
	const STORAGE_EXTENSION = 'storage_extension';
	const STORAGE_KEY = 'storage_key';
	const STORAGE_SIZE = 'storage_size';
	const STORAGE_PROFILE_ID = 'storage_profile_id';
	const STORAGE_SHA1HASH = 'storage_sha1hash';
	const UPDATED = 'updated';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO attachment () VALUES ()";
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($id, $fields) {
		if(!isset($fields[self::UPDATED]))
			$fields[self::UPDATED] = time();
		
		self::_update($id, 'attachment', $fields);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Attachment
	 */
	public static function get($id) {
		if(empty($id))
			return null;
		
		$items = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($items[$id]))
			return $items[$id];
			
		return NULL;
	}
	
	/**
	 * @param string $where
	 * @return Model_Attachment[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,display_name,mime_type,storage_size,storage_extension,storage_key,storage_profile_id,storage_sha1hash,updated ".
			"FROM attachment ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "");
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	private static function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Attachment();
			$object->id = intval($row['id']);
			$object->display_name = $row['display_name'];
			$object->mime_type = $row['mime_type'];
			$object->storage_size = intval($row['storage_size']);
			$object->storage_extension = $row['storage_extension'];
			$object->storage_key = $row['storage_key'];
			$object->storage_profile_id = $row['storage_profile_id'];
			$object->storage_sha1hash = $row['storage_sha1hash'];
			$object->updated = intval($row['updated']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function setLinks($context, $context_id, $file_ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($file_ids))
			$file_ids = array($file_ids);
		
		$values = [];
		
		foreach($file_ids as $file_id) {
			$values[] = sprintf("(%s, %d, %d)",
				$db->qstr($context),
				$context_id,
				$file_id
			);
		}
		
		if(empty($values))
			return;
			
		$sql = sprintf("REPLACE INTO attachment_link (context, context_id, attachment_id) VALUES %s",
			implode(',', $values)
		);
		return (false !== $db->ExecuteMaster($sql));
	}
	
	static function getLinks($file_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$contexts = [];
		
		$results = $db->GetArrayMaster(sprintf("SELECT context, context_id FROM attachment_link WHERE attachment_id = %d",
			$file_id
		));
		
		foreach($results as $row) {
			if(!isset($contexts[$row['context']]))
				$contexts[$row['context']] = [];
			
			$contexts[$row['context']][] = $row['context_id'];
		}
		
		return $contexts;
	}
	
	static function getLinkCounts($context_id) {
		$db = DevblocksPlatform::getDatabaseService(); 
		
		$results = $db->GetArrayMaster(sprintf("SELECT count(context_id) AS hits, context FROM attachment_link WHERE attachment_id = %d GROUP BY context",
			$context_id
		));
		
		if(!$results)
			return [];
		
		return array_column($results, 'hits', 'context');
	}
	
	static function getByContextIds($context, $context_ids, $merged=true) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);

		$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'int');
		
		if(empty($context) && empty($context_ids))
			return array();
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$results = self::getWhere(sprintf("id in (SELECT attachment_id FROM attachment_link WHERE context = %s AND context_id IN (%s))",
			$db->qstr($context),
			implode(',', $context_ids)
		));
		
		if($merged) {
			return $results;
			
		} else {
			$files = $results;
			
			$sql = sprintf("SELECT attachment_id, context_id FROM attachment_link WHERE context = %s AND context_id IN (%s)",
				$db->qstr($context),
				implode(',', $context_ids)
			);
			$link_results = $db->GetArraySlave($sql);
			$results = [];
			
			foreach($link_results as $row) {
				if(!isset($results[$row['context_id']]))
					$results[$row['context_id']] = [];
				
				$results[$row['context_id']][$row['attachment_id']] = $files[$row['attachment_id']];
			}
			
			return $results;
		}
	}
	
	static function getBySha1Hash($sha1_hash, $file_name=null, $file_size=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id ".
			"FROM attachment ".
			"WHERE storage_sha1hash=%s ".
			"%s ".
			"%s ".
			"ORDER BY id ".
			"LIMIT 1",
			$db->qstr($sha1_hash),
			(!empty($file_name) ? (sprintf("AND display_name=%s", $db->qstr($file_name))) : ''),
			(!empty($file_size) ? (sprintf("AND storage_size=%d", $file_size)) : '')
		);
		
		return $db->GetOneSlave($sql);
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		// Delete attachments where links=0 and created > 1h
		// This also cleans up temporary attachment uploads from the file chooser.
		// If any of these queries fail, we need to stop immediately
		
		if(false === $db->ExecuteMaster("CREATE TEMPORARY TABLE _tmp_maint_attachment (PRIMARY KEY (id)) SELECT id, updated FROM attachment")) {
			$logger->error('[Maint] Failed to create temporary table for purging attachments.');
			return false;
		}
		
		if(false === $db->ExecuteMaster("DELETE FROM _tmp_maint_attachment WHERE id IN (SELECT to_context_id FROM context_link WHERE to_context = 'cerberusweb.contexts.attachment')")) {
			$logger->error('[Maint] Failed to remove valid attachment links from temporary table.');
			return false;
		}
		
		if(false === $db->ExecuteMaster("DELETE FROM _tmp_maint_attachment WHERE updated >= UNIX_TIMESTAMP() - 86400 AND updated != 2147483647")) {
			$logger->error('[Maint] Failed to remove recent attachments from temporary table.');
			return false;
		}
		
		if(false === ($rs = $db->ExecuteMaster("SELECT SQL_CALC_FOUND_ROWS id FROM _tmp_maint_attachment")) || !($rs instanceof mysqli_result)) {
			$logger->error('[Maint] Failed to iterate attachments from temporary table.');
			return false;
		}
		
		if(false === ($count = $db->GetOneMaster("SELECT FOUND_ROWS()"))) {
			$logger->error('[Maint] Failed to count attachments from temporary table.');
			return false;
		}
		
		if(!empty($count)) {
			while($row = mysqli_fetch_row($rs)) {
				DAO_Attachment::delete($row[0]);
			}
			mysqli_free_result($rs);
		}
		
		$db->ExecuteMaster("DROP TABLE _tmp_maint_attachment");
		
		$logger->info('[Maint] Purged ' . $count . ' attachment records.');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($ids))
			$ids = array($ids);
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids))
			return;

		if(false === Storage_Attachments::delete($ids))
			return FALSE;
		
		// Delete links
		$db->ExecuteMaster(sprintf("DELETE FROM attachment_link WHERE id IN (%s)", implode(',', $ids)));
		
		// Delete DB manifests
		$sql = sprintf("DELETE FROM attachment WHERE id IN (%s)", implode(',', $ids));
		$db->ExecuteMaster($sql);
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Attachment::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, array(), 'SearchFields_Attachment', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.display_name as %s, ".
			"a.mime_type as %s, ".
			"a.storage_size as %s, ".
			"a.storage_extension as %s, ".
			"a.storage_key as %s, ".
			"a.storage_profile_id as %s, ".
			"a.storage_sha1hash as %s, ".
			"a.updated as %s ".
			"",
				SearchFields_Attachment::ID,
				SearchFields_Attachment::DISPLAY_NAME,
				SearchFields_Attachment::MIME_TYPE,
				SearchFields_Attachment::STORAGE_SIZE,
				SearchFields_Attachment::STORAGE_EXTENSION,
				SearchFields_Attachment::STORAGE_KEY,
				SearchFields_Attachment::STORAGE_PROFILE_ID,
				SearchFields_Attachment::STORAGE_SHA1HASH,
				SearchFields_Attachment::UPDATED
		);
		
		$join_sql = "FROM attachment a ";
			
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Attachment');
		
		$result = array(
			'primary_table' => 'a',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	/**
	 * Enter description here...
	 *
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
		
		if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
			return false;
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$id = intval($row[SearchFields_Attachment::ID]);
			$results[$id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(a.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_Attachment extends DevblocksSearchFields {
	const ID = 'a_id';
	const DISPLAY_NAME = 'a_display_name';
	const MIME_TYPE = 'a_mime_type';
	const STORAGE_SIZE = 'a_storage_size';
	const STORAGE_EXTENSION = 'a_storage_extension';
	const STORAGE_KEY = 'a_storage_key';
	const STORAGE_PROFILE_ID = 'a_storage_profile_id';
	const STORAGE_SHA1HASH = 'a_storage_sha1hash';
	const UPDATED = 'a_updated';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'a.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_ATTACHMENT => new DevblocksSearchFieldContextKeys('a.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_ATTACHMENT, self::getPrimaryKey());
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
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', $translate->_('attachment.id'), Model_CustomField::TYPE_NUMBER, true),
			self::DISPLAY_NAME => new DevblocksSearchField(self::DISPLAY_NAME, 'a', 'display_name', $translate->_('attachment.display_name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::MIME_TYPE => new DevblocksSearchField(self::MIME_TYPE, 'a', 'mime_type', $translate->_('attachment.mime_type'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STORAGE_SIZE => new DevblocksSearchField(self::STORAGE_SIZE, 'a', 'storage_size', $translate->_('common.size'), Model_CustomField::TYPE_NUMBER, true),
			self::STORAGE_EXTENSION => new DevblocksSearchField(self::STORAGE_EXTENSION, 'a', 'storage_extension', $translate->_('attachment.storage_extension'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STORAGE_KEY => new DevblocksSearchField(self::STORAGE_KEY, 'a', 'storage_key', $translate->_('attachment.storage_key'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STORAGE_PROFILE_ID => new DevblocksSearchField(self::STORAGE_PROFILE_ID, 'a', 'storage_profile_id', $translate->_('attachment.storage_profile_id'), Model_CustomField::TYPE_NUMBER, true),
			self::STORAGE_SHA1HASH => new DevblocksSearchField(self::STORAGE_SHA1HASH, 'a', 'storage_sha1hash', $translate->_('attachment.storage_sha1hash'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'a', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Attachment {
	public $id;
	public $display_name;
	public $mime_type = '';
	public $storage_extension;
	public $storage_key;
	public $storage_size = 0;
	public $storage_profile_id;
	public $storage_sha1hash;
	public $updated;
	
	public function getFileContents(&$fp=null) {
		return Storage_Attachments::get($this, $fp);
	}
	
	public function isReadableByActor($actor) {
		if(!($actor instanceof Model_Worker))
			return false;
		
		if(false == ($ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_ATTACHMENT)))
			return false;
		
		return $ext->authorize($this->id, $actor);
	}
};

class Storage_Attachments extends Extension_DevblocksStorageSchema {
	const ID = 'cerberusweb.storage.schema.attachments';
	
	public static function getActiveStorageProfile() {
		return DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile', 'devblocks.storage.engine.disk');
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 7));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/attachments/render.tpl");
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 7));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/section/storage_profiles/schemas/attachments/config.tpl");
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
	 * @param Model_Attachment | $attachment_id
	 * @return unknown_type
	 */
	public static function get($object, &$fp=null) {
		if($object instanceof Model_Attachment) {
			// Do nothing
		} elseif(is_numeric($object)) {
			$object = DAO_Attachment::get($object);
		} else {
			$object = null;
		}

		if(empty($object))
			return false;
		
		$key = $object->storage_key;
		$profile = !empty($object->storage_profile_id) ? $object->storage_profile_id : $object->storage_extension;
		
		if(false === ($storage = DevblocksPlatform::getStorageService($profile)))
			return false;
			
		return $storage->get('attachments', $key, $fp);
	}
	
	public static function put($id, $contents, $profile=null) {
		if(empty($profile)) {
			$profile = self::getActiveStorageProfile();
		}
		
		if($profile instanceof Model_DevblocksStorageProfile) {
			$profile_id = $profile->id;
		} elseif(is_numeric($profile)) {
			$profile_id = intval($profile_id);
		} elseif(is_string($profile)) {
			$profile_id = 0;
		}

		$storage = DevblocksPlatform::getStorageService($profile);

		if(is_string($contents)) {
			$storage_size = strlen($contents);
		} else if(is_resource($contents)) {
			$stats = fstat($contents);
			$storage_size = $stats['size'];
		} else {
			return false;
		}
		
		// Save to storage
		if(false === ($storage_key = $storage->put('attachments', $id, $contents)))
			return false;
		
		// Update storage key
		DAO_Attachment::update($id, array(
			DAO_Attachment::STORAGE_EXTENSION => $storage->manifest->id,
			DAO_Attachment::STORAGE_PROFILE_ID => $profile_id,
			DAO_Attachment::STORAGE_KEY => $storage_key,
			DAO_Attachment::STORAGE_SIZE => $storage_size,
		));
		
		return $storage_key;
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT storage_extension, storage_key, storage_profile_id FROM attachment WHERE id IN (%s)", implode(',',$ids));
		
		if(false == ($rs = $db->ExecuteSlave($sql)))
			return false;
		
		// Delete the physical files
		
		while($row = mysqli_fetch_assoc($rs)) {
			$profile = !empty($row['storage_profile_id']) ? $row['storage_profile_id'] : $row['storage_extension'];
			
			if(null != ($storage = DevblocksPlatform::getStorageService($profile)))
				if(false === $storage->delete('attachments', $row['storage_key']))
					return FALSE;
		}
		
		mysqli_free_result($rs);
		
		return true;
	}
	
	public function getStats() {
		return $this->_stats('attachment');
	}
	
	public static function archive($stop_time=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Params
		$src_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile'));
		$dst_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_storage_profile'));
		$archive_after_days = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_after_days');
		
		if(empty($src_profile) || empty($dst_profile))
			return;
		
		if(json_encode($src_profile) == json_encode($dst_profile))
			return;
		
		// Find inactive attachments
		$sql = sprintf("SELECT attachment.id, attachment.storage_extension, attachment.storage_key, attachment.storage_profile_id, attachment.storage_size ".
			"FROM attachment ".
			"WHERE attachment.updated < %d ".
			"AND (attachment.storage_extension = %s AND attachment.storage_profile_id = %d) ".
			"ORDER BY attachment.id ASC ".
			"LIMIT 500",
				time()-(86400*$archive_after_days),
				$db->qstr($src_profile->extension_id),
				$src_profile->id
		);
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			self::_migrate($dst_profile, $row);

			if(time() > $stop_time)
				return;
		}
	}
	
	public static function unarchive($stop_time=null) {
		// We don't want to unarchive message content under any condition
		/*
		$db = DevblocksPlatform::getDatabaseService();

		// Params
		$dst_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile'));
		$archive_after_days = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_after_days');
		
		if(empty($dst_profile))
			return;
		
		// Find active attachments
		$sql = sprintf("SELECT attachment.id, attachment.storage_extension, attachment.storage_key, attachment.storage_profile_id, attachment.storage_size ".
			"FROM attachment ".
			"WHERE attachment.updated >= %d ".
			"AND NOT (attachment.storage_extension = %s AND attachment.storage_profile_id = %d) ".
			"ORDER BY attachment.id DESC ",
				time()-(86400*$archive_after_days),
				$db->qstr($dst_profile->extension_id),
				$dst_profile->id
		);
		$rs = $db->ExecuteSlave($sql);
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			self::_migrate($dst_profile, $row, true);

			if(time() > $stop_time)
				return;
		}
		*/
	}
	
	private static function _migrate($dst_profile, $row, $is_unarchive=false) {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$ns = 'attachments';
		
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
		
		// If smaller than 1MB, load into a variable
		if($is_small) {
			if(false === ($data = $src_engine->get($ns, $src_key))) {
				$logger->error(sprintf("[Storage] Error reading %s key (%s) from (%s)",
					$ns,
					$src_key,
					$src_profile->extension_id
				));
				return;
			}
		// Otherwise, allocate a temporary file handle
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

class View_Attachment extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'attachment';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = mb_ucfirst($translate->_('common.attachment'));
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Attachment::UPDATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Attachment::MIME_TYPE,
			SearchFields_Attachment::STORAGE_SIZE,
			SearchFields_Attachment::STORAGE_EXTENSION,
			SearchFields_Attachment::STORAGE_KEY,
			SearchFields_Attachment::UPDATED,
		);

		$this->addColumnsHidden(array(
			SearchFields_Attachment::VIRTUAL_CONTEXT_LINK,
		));
		
		$this->addParamsHidden(array(
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Attachment::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Attachment');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Attachment', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Attachment', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_Attachment::DISPLAY_NAME:
				case SearchFields_Attachment::MIME_TYPE:
				case SearchFields_Attachment::STORAGE_EXTENSION:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_Attachment::VIRTUAL_CONTEXT_LINK:
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
		$context = CerberusContexts::CONTEXT_ATTACHMENT;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::STORAGE_EXTENSION:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_Attachment::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
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
		$search_fields = SearchFields_Attachment::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Attachment::DISPLAY_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Attachment::ID),
				),
			'mimetype' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Attachment::MIME_TYPE),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Attachment::DISPLAY_NAME),
				),
			'size' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Attachment::STORAGE_SIZE),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Attachment::UPDATED),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendLinksFromQuickSearchContexts($fields);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_ATTACHMENT, $fields, null);
		
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ATTACHMENT);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/attachments/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::STORAGE_KEY:
			case SearchFields_Attachment::STORAGE_EXTENSION:
			case SearchFields_Attachment::STORAGE_SHA1HASH:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Attachment::ID:
			case SearchFields_Attachment::STORAGE_SIZE:
			case SearchFields_Attachment::STORAGE_PROFILE_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Attachment::UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Attachment::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
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
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_Attachment::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Attachment::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::STORAGE_KEY:
			case SearchFields_Attachment::STORAGE_EXTENSION:
			case SearchFields_Attachment::STORAGE_SHA1HASH:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Attachment::ID:
			case SearchFields_Attachment::STORAGE_PROFILE_ID:
			case SearchFields_Attachment::STORAGE_SIZE:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Attachment::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Attachment::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
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

class Context_Attachment extends Extension_DevblocksContext implements IDevblocksContextPeek, IDevblocksContextProfile {
	const ID = CerberusContexts::CONTEXT_ATTACHMENT;
	
	function authorize($context_id, Model_Worker $worker) {
		
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if(false == ($model = DAO_Attachment::get($context_id)))
				return false;
			
			if($worker->is_superuser) {
				return $model->storage_sha1hash;
			}

			if(false == ($links = DAO_ContextLink::getAllContextLinks(self::ID, $context_id)))
				return false;
			
			$guids = [];
			
			foreach($links as $link) {
				if(!isset($guids[$link->context]))
					$guids[$link->context] = [];
				
				$guids[$link->context][$link->context_id] = $model->storage_sha1hash;
			}
			
			$links = $guids;
			unset($guids);
			
			// Is it linked to a KB article?  Public
			if(isset($links[CerberusContexts::CONTEXT_KB_ARTICLE]))
				return true;
				
			// If it linked to a file bundle?  Check owners
			if(isset($links[CerberusContexts::CONTEXT_FILE_BUNDLE])) {
				$ids = array_keys($links[CerberusContexts::CONTEXT_FILE_BUNDLE]);
				$models = DAO_FileBundle::getIds($ids);
				
				foreach($links[CerberusContexts::CONTEXT_FILE_BUNDLE] as $id => $guid) {
					$model = $models[$id];
					
					if(CerberusContexts::isReadableByActor($model->owner_context, $model->owner_context_id, $worker))
						return $guid;
				}
			}
			
			// Is it linked to an HTML template?
			if(isset($links[CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE])) {
				$ids = array_keys($links[CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE]);
				$models = DAO_MailHtmlTemplate::getIds($ids);
				
				foreach($links[CerberusContexts::CONTEXT_FILE_BUNDLE] as $id => $guid) {
					$model = $models[$id];
					
					if(CerberusContexts::isReadableByActor($model->owner_context, $model->owner_context_id, $worker))
						return $guid;
				}
			}
			
			// Is it linked to a message?
			if(isset($links[CerberusContexts::CONTEXT_MESSAGE])) {
				$db = DevblocksPlatform::getDatabaseService();
				$group_ids = array_keys(DAO_Group::getPublicGroups()) + array_keys($worker->getMemberships());
				
				$sql = sprintf("SELECT MAX(m.id) FROM message m INNER JOIN ticket t ON (t.id=m.ticket_id) WHERE m.id IN (%s) AND t.group_id in (%s)",
					implode(',', DevblocksPlatform::sanitizeArray(array_keys($links[CerberusContexts::CONTEXT_MESSAGE]), 'int')),
					implode(',', $group_ids)
				);
				$id = $db->GetOneSlave($sql);
				
				if($id && isset($links[CerberusContexts::CONTEXT_MESSAGE][$id]))
					return $links[CerberusContexts::CONTEXT_MESSAGE][$id];
			}
			
			// Is it linked to a comment?
			if(isset($links[CerberusContexts::CONTEXT_COMMENT])) {
				$ids = array_keys($links[CerberusContexts::CONTEXT_COMMENT]);
				$models = DAO_Comment::getIds($ids);

				foreach($links[CerberusContexts::CONTEXT_COMMENT] as $id => $guid) {
					$model = $models[$id];
					
					if(false == ($defer_context = Extension_DevblocksContext::get($model->context)))
						continue;
					
					if($defer_context->authorize($model->context_id, $worker))
						return $guid;
				}
			}
			
			return false;
			
		} catch (Exception $e) {
			// Fail
		}
		
		return false;
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=attachment&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$attachment = DAO_Attachment::get($context_id);

		return array(
			'id' => $attachment->id,
			'name' => $attachment->display_name,
			'permalink' => null,
			'updated' => $attachment->updated,
		);
	}
	
	function getRandom() {
		return null;
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
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
			'mime_type',
			'size',
			'storage_extension',
			'storage_key',
			'updated',
		);
	}
	
	function getContext($attachment, &$token_labels, &$token_values, $prefix=null) {
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ATTACHMENT);

		$translate = DevblocksPlatform::getTranslationService();
		
		if(is_null($prefix))
			$prefix = 'Attachment:';
		
		// Polymorph
		if(is_numeric($attachment)) {
			$attachment = DAO_Attachment::get($attachment);
		} elseif($attachment instanceof Model_Attachment) {
			// It's what we want already.
		} elseif(is_array($attachment)) {
			$attachment = Cerb_ORMHelper::recastArrayToModel($attachment, 'Model_Attachment');
		} elseif(strlen($attachment) == 40) { // SHA-1 HASH
			$attachment = DAO_Attachment::get(intval(DAO_Attachment::getBySha1Hash($attachment)));
		} else {
			$attachment = null;
		}
			
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'mime_type' => $prefix.$translate->_('attachment.mime_type'),
			'name' => $prefix.$translate->_('attachment.display_name'),
			'size' => $prefix.$translate->_('common.size'),
			'storage_extension' => $prefix.$translate->_('attachment.storage_extension'),
			'storage_key' => $prefix.$translate->_('attachment.storage_key'),
			'updated' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'id' => 'id',
			'mime_type' => Model_CustomField::TYPE_SINGLE_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'size' => 'size_bytes',
			'storage_extension' => Model_CustomField::TYPE_SINGLE_LINE,
			'storage_key' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_ATTACHMENT;
		$token_values['_types'] = $token_types;
		
		if(null != $attachment) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $attachment->display_name;
			
			$token_values['id'] = $attachment->id;
			$token_values['mime_type'] = $attachment->mime_type;
			$token_values['name'] = $attachment->display_name;
			$token_values['size'] = $attachment->storage_size;
			$token_values['storage_extension'] = $attachment->storage_extension;
			$token_values['storage_key'] = $attachment->storage_key;
			$token_values['updated'] = $attachment->updated;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($attachment, $token_values);
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = CerberusContexts::CONTEXT_ATTACHMENT;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
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
		
		if(false == $this->getViewClass())
			return;
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = DevblocksPlatform::translate('common.attachments', DevblocksPlatform::TRANSLATE_CAPITALIZE);
		$view->addParams(array(), true);
		$view->renderSortBy = SearchFields_Attachment::UPDATED;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		if(false == $this->getViewClass())
			return;
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = DevblocksPlatform::translate('common.attachments', DevblocksPlatform::TRANSLATE_CAPITALIZE);
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Attachment::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_ATTACHMENT;
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($context_id)) {
			if(false != ($model = DAO_Attachment::get($context_id))) {
				$guid = $model->isReadableByActor($active_worker);
				$tpl->assign('guid', $guid);
			}
		}
		
		if(empty($context_id) || $edit) {
			if(isset($model))
				$tpl->assign('model', $model);
			
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
			$tpl->display('devblocks:cerberusweb.core::internal/attachments/peek_edit.tpl');
			
		} else {
			// Counts
			$activity_counts = array(
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
			
			$tpl->display('devblocks:cerberusweb.core::internal/attachments/peek.tpl');
		}
	}
	
};
