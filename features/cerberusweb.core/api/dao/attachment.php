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
	
	// [TODO] Move this??
	static function getByContextIds($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);

		if(empty($context_ids))
			return array();
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,display_name,mime_type,storage_size,storage_extension,storage_key,storage_profile_id,storage_sha1hash,updated ".
			"FROM attachment ".
			"INNER JOIN attachment_link ON (attachment.id=attachment_link.attachment_id) ".
			"WHERE attachment_link.context = %s AND attachment_link.context_id IN (%s) ",
			$db->qstr($context),
			implode(',', $context_ids)
		);
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
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
		$db->ExecuteMaster("CREATE TEMPORARY TABLE _tmp_maint_attachment (PRIMARY KEY (id)) SELECT id, updated FROM attachment");
		$db->ExecuteMaster("DELETE FROM _tmp_maint_attachment WHERE id IN (SELECT attachment_id FROM attachment_link)");
		$db->ExecuteMaster("DELETE FROM _tmp_maint_attachment WHERE updated >= UNIX_TIMESTAMP() - 86400 AND updated != 2147483647");
		$rs = $db->ExecuteMaster("SELECT SQL_CALC_FOUND_ROWS id FROM _tmp_maint_attachment");
		$count = $db->GetOneMaster("SELECT FOUND_ROWS();");

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
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;

		if(false === Storage_Attachments::delete($ids))
			return FALSE;
		
		// Delete links
		foreach($ids as $id)
			DAO_AttachmentLink::removeAllByAttachment($id);
		
		// Delete DB manifests
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("DELETE attachment FROM attachment WHERE id IN (%s)", implode(',', $ids));
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
		
		$join_sql = "FROM attachment a ".
			(isset($tables['al']) ? "INNER JOIN attachment_link al ON (al.attachment_id=a.id)" : " ")
			;
			
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Attachment');
		
		$result = array(
			'primary_table' => 'a',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
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
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY a.id ' : '').
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
	
	const LINK_CONTEXT = 'al_context';
	const LINK_CONTEXT_ID = 'al_context_id';
	
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
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', $translate->_('attachment.id'), Model_CustomField::TYPE_NUMBER, true),
			self::DISPLAY_NAME => new DevblocksSearchField(self::DISPLAY_NAME, 'a', 'display_name', $translate->_('attachment.display_name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::MIME_TYPE => new DevblocksSearchField(self::MIME_TYPE, 'a', 'mime_type', $translate->_('attachment.mime_type'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STORAGE_SIZE => new DevblocksSearchField(self::STORAGE_SIZE, 'a', 'storage_size', $translate->_('attachment.storage_size'), Model_CustomField::TYPE_NUMBER, true),
			self::STORAGE_EXTENSION => new DevblocksSearchField(self::STORAGE_EXTENSION, 'a', 'storage_extension', $translate->_('attachment.storage_extension'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STORAGE_KEY => new DevblocksSearchField(self::STORAGE_KEY, 'a', 'storage_key', $translate->_('attachment.storage_key'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STORAGE_PROFILE_ID => new DevblocksSearchField(self::STORAGE_PROFILE_ID, 'a', 'storage_profile_id', $translate->_('attachment.storage_profile_id'), Model_CustomField::TYPE_NUMBER, true),
			self::STORAGE_SHA1HASH => new DevblocksSearchField(self::STORAGE_SHA1HASH, 'a', 'storage_sha1hash', $translate->_('attachment.storage_sha1hash'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'a', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::LINK_CONTEXT => new DevblocksSearchField(self::LINK_CONTEXT, 'al', 'context', $translate->_('common.context'), null, false),
			self::LINK_CONTEXT_ID => new DevblocksSearchField(self::LINK_CONTEXT_ID, 'al', 'context_id', $translate->_('common.context_id'), null, false),
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
	
	public function getLinks() {
		return DAO_AttachmentLink::getByAttachmentId($this->id);
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

		// Save to storage
		if(false === ($storage_key = $storage->put('attachments', $id, $contents)))
			return false;
		
		if(is_string($contents)) {
			$storage_size = strlen($contents);
			unset($contents);
		} else if(is_resource($contents)) {
			$stats = fstat($contents);
			$storage_size = $stats['size'];
		} else {
			return false;
		}
		
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
			"ORDER BY attachment.id ASC ",
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

class View_AttachmentLink extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'attachment_links';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Attachments';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_AttachmentLink::ATTACHMENT_STORAGE_SIZE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_AttachmentLink::ATTACHMENT_MIME_TYPE,
			SearchFields_AttachmentLink::ATTACHMENT_STORAGE_SIZE,
			SearchFields_AttachmentLink::LINK_CONTEXT,
			SearchFields_AttachmentLink::ATTACHMENT_UPDATED,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_AttachmentLink::ID,
			SearchFields_AttachmentLink::LINK_CONTEXT_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_AttachmentLink::ID,
			SearchFields_AttachmentLink::LINK_CONTEXT_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_AttachmentLink::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AttachmentLink');
		
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AttachmentLink', $size, 'guid');
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Strings
				case SearchFields_AttachmentLink::ATTACHMENT_DISPLAY_NAME:
				case SearchFields_AttachmentLink::ATTACHMENT_MIME_TYPE:
				case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_EXTENSION:
				case SearchFields_AttachmentLink::LINK_CONTEXT:
					$pass = true;
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
		$context = CerberusContexts::CONTEXT_ATTACHMENT_LINK;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_AttachmentLink::ATTACHMENT_DISPLAY_NAME:
			case SearchFields_AttachmentLink::ATTACHMENT_MIME_TYPE:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;

			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_EXTENSION:
				$label_map = array();
				$manifests = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
				if(is_array($manifests))
				foreach($manifests as $k => $mft) {
					$label_map[$k] = $mft->name;
				}
				
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_AttachmentLink::LINK_CONTEXT:
				$label_map = array();
				$manifests = Extension_DevblocksContext::getAll(false);
				if(is_array($manifests))
				foreach($manifests as $k => $mft) {
					$label_map[$k] = $mft->name;
				}
				
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'contexts[]');
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_AttachmentLink::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_AttachmentLink::ATTACHMENT_DISPLAY_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fileName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_AttachmentLink::ATTACHMENT_DISPLAY_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fileSize' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_AttachmentLink::ATTACHMENT_STORAGE_SIZE),
					'examples' => array(
						'=25000',
						'>=50000',
						'<=100000'
					),
				),
			'guid' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_AttachmentLink::GUID, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_AttachmentLink::ID),
				),
			'mimeType' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_AttachmentLink::ATTACHMENT_MIME_TYPE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'examples' => array(
						'application/octet-stream',
						'application/pdf',
						'application/zip',
						'image/jpeg',
						'image/png',
						'text/html',
					),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_AttachmentLink::ATTACHMENT_UPDATED),
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

		// Contexts
		$contexts = Extension_DevblocksContext::getAll();
		$tpl->assign('contexts', $contexts);
		
		// Storage extensions
		$storage_extensions = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
		$tpl->assign('storage_extensions', $storage_extensions);
		
		// Storage profiles
		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);
		
		// [TODO] Move
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::configuration/section/storage_attachments/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_AttachmentLink::GUID:
			case SearchFields_AttachmentLink::ATTACHMENT_DISPLAY_NAME:
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_EXTENSION:
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_KEY:
			case SearchFields_AttachmentLink::ATTACHMENT_MIME_TYPE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_SIZE:
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_PROFILE_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_AttachmentLink::ATTACHMENT_UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case SearchFields_AttachmentLink::LINK_CONTEXT:
				$tpl->assign('contexts', Extension_DevblocksContext::getAll());
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context.tpl');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_EXTENSION:
				$label_map = array();
				$manifests = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);

				$strings = array();
				foreach($values as $v) {
					if(isset($manifests[$v]))
						$strings[] = DevblocksPlatform::strEscapeHtml($manifests[$v]->name);
				}
				if(!empty($strings))
					echo implode(', ', $strings);
				
				break;
				
			case SearchFields_AttachmentLink::LINK_CONTEXT:
				$contexts = Extension_DevblocksContext::getAll();
				$strings = array();
				foreach($values as $v) {
					if(isset($contexts[$v]))
						$strings[] = DevblocksPlatform::strEscapeHtml($contexts[$v]->name);
				}
				if(!empty($strings))
					echo implode(', ', $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_AttachmentLink::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_AttachmentLink::GUID:
			case SearchFields_AttachmentLink::ATTACHMENT_DISPLAY_NAME:
			case SearchFields_AttachmentLink::ATTACHMENT_MIME_TYPE:
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_EXTENSION:
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_KEY:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_SIZE:
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_PROFILE_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_AttachmentLink::ATTACHMENT_UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_AttachmentLink::LINK_CONTEXT:
				@$contexts = DevblocksPlatform::importGPC($_REQUEST['contexts'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$contexts);
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
		$deleted = false;

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'deleted':
					$deleted = !empty($v);
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_AttachmentLink::search(
				$this->view_columns,
				$this->getParams(),
				100,
				$pg++,
				SearchFields_AttachmentLink::GUID,
				true,
				false
			);
			
			foreach($objects as $o)
				$ids[] = $o[SearchFields_AttachmentLink::GUID];
			
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!$deleted) {
				//DAO_AttachmentLink::update($batch_ids, $change_fields);
			} else {
				foreach($batch_ids as $batch_id) {
					DAO_AttachmentLink::deleteByGUID($batch_id);
				}
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class DAO_AttachmentLink extends Cerb_ORMHelper {
	const GUID = 'guid';
	const ATTACHMENT_ID = 'attachment_id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	
	/**
	 * @param string $where
	 * @return Model_Attachment[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT guid, attachment_id, context, context_id ".
			"FROM attachment_link ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "");
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * ...
	 *
	 * @param string $guid
	 * @return Model_AttachmentLink
	 */
	static function getByGUID($guid) {
		$links = self::getWhere(sprintf("%s = %s",
			self::GUID,
			self::qstr($guid)
		));
		
		if(!empty($links))
			return array_shift($links);
		
		return array();
	}
	
	/**
	 * ...
	 *
	 * @param string $guid
	 * @return Model_AttachmentLink
	 */
	static function getByGUIDs($guids) {
		if(!is_array($guids) || empty($guids))
			return array();
		
		array_walk(
			$guids,
			function(&$e) {
				$e = Cerb_ORMHelper::qstr($e);
			}
		);
		
		if(empty($guids))
			return array();
		
		$links = self::getWhere(sprintf("%s IN (%s)",
			self::GUID,
			implode(', ', $guids)
		));
		
		return $links;
	}
	
	static function getByAttachmentId($id) {
		$links = self::getWhere(sprintf("%s = %d",
			self::ATTACHMENT_ID,
			$id
		));
		
		return $links;
	}
	
	static function create($attachment_id, $context, $context_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// We do this in a separate query for binary logging consistency
		// [TODO] Can't this be done in PHP?
		$uuid = $db->GetOneMaster("SELECT UUID()");
		
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO attachment_link (attachment_id, context, context_id, guid) ".
			"VALUES (%d, %s, %d, %s)",
			$attachment_id,
			$db->qstr($context),
			$context_id,
			$db->qstr($uuid)
		));
		
		return $uuid;
	}
	
	static function addLinks($context, $context_id, $attachment_ids) {
		if(!is_array($attachment_ids))
			$attachment_ids = array($attachment_ids);
		
		foreach($attachment_ids as $attachment_id)
			DAO_AttachmentLink::create($attachment_id, $context, $context_id);
		
		return TRUE;
	}
	
	/**
	 * ...
	 *
	 * @param string $context
	 * @param integer $context_id
	 * @param array $attachment_ids
	 */
	static function setLinks($context, $context_id, $attachment_ids) {
		if(!is_array($attachment_ids))
			$attachment_ids = array($attachment_ids);
		
		// Load the links for the context ID and compare the attachments
		$a_map = self::getLinksAndAttachments($context, $context_id);
		//$links = $a_map['links'];
		$attachments = $a_map['attachments'];
		
		$deleted_ids = array_diff(array_keys($attachments), $attachment_ids);
		$new_ids = array_diff($attachment_ids, array_keys($attachments));
		
		// Remove those that are missing
		if(!empty($deleted_ids))
		foreach($deleted_ids as $deleted_id)
			DAO_AttachmentLink::deleteByAttachmentAndContext($deleted_id, $context, $context_id);
			
		// Add those that are new
		if(!empty($new_ids))
		foreach($new_ids as $new_id)
			DAO_AttachmentLink::create($new_id, $context, $context_id);
			
		return TRUE;
	}
	
	static function getLinksAndAttachments($context, $context_id) {
		if(empty($context) || empty($context_id))
			return array();
			
		$file_ids = array();
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT attachment_id, context, context_id, guid ".
			"FROM attachment_link ".
			"WHERE attachment_link.context = %s ".
			"AND attachment_link.context_id = %d ",
			$db->qstr($context),
			$context_id
		);
		$rs = $db->ExecuteSlave($sql);
		
		$links = self::_getObjectsFromResult($rs);
		
		foreach($links as $link) {
			$file_ids[] = $link->attachment_id;
		}
		
		if(empty($file_ids)) {
			$files = array();
		} else {
			$files = DAO_Attachment::getWhere(sprintf("%s IN (%s)",
				DAO_Attachment::ID,
				implode(',', $file_ids)
			));
		}
		
		return array(
			'links' => $links,
			'attachments' => $files,
		);
	}
	
	static function getByContextIds($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);

		if(empty($context_ids))
			return array();
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT ".
			"attachment_id, context, context_id, guid ".
			"FROM attachment_link ".
			"WHERE attachment_link.context = %s ".
			"AND attachment_link.context_id IN (%s) ",
			$db->qstr($context),
			implode(',', $context_ids)
		);
		$rs = $db->ExecuteSlave($sql);

		return self::_getObjectsFromResult($rs);
	}
	
	static function getIdsByContext($attachment_ids, $context=null) {
		if(!is_array($attachment_ids))
			$attachment_ids = array($attachment_ids);
		
		if(empty($attachment_ids))
			return array();
			
		$db = DevblocksPlatform::getDatabaseService();
		$rows = $db->GetArraySlave(sprintf("SELECT attachment_id, context, context_id ".
			"FROM attachment_link ".
			"WHERE attachment_id IN (%s) ".
			((!empty($context)) ? sprintf("AND context = %s ", $db->qstr($context)) : ""),
			implode(',', $attachment_ids)
		));
		
		$results = array();
		
		foreach($rows as $row) {
			if(!isset($results[$row['attachment_id']]))
				$results[$row['attachment_id']] = array();
				
			if(!isset($results[$row['attachment_id']][$row['context']]))
				$results[$row['attachment_id']][$row['context']] = array();
			
			$results[$row['attachment_id']][$row['context']][$row['context_id']] = $row['attachment_id'];
		}
		
		return $results;
	}
	
	static function removeAllByAttachment($attachment_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->ExecuteMaster(sprintf("DELETE FROM attachment_link WHERE attachment_id = %d",
			$attachment_id
		));
	}
	
	static function removeAllByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
			
		if(empty($context_ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		$db->ExecuteMaster(sprintf("DELETE FROM attachment_link WHERE context = %s AND context_id IN (%s)",
			$db->qstr($context),
			implode(',', $context_ids)
		));
	}
	
	static function deleteByAttachmentAndContext($attachment_id, $context, $context_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->ExecuteMaster(sprintf("DELETE FROM attachment_link WHERE attachment_id = %d AND context = %s AND context_id = %d",
			$attachment_id,
			$db->qstr($context),
			$context_id
		));
	}
	
	static function deleteByAttachment($attachment_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->ExecuteMaster(sprintf("DELETE FROM attachment_link WHERE attachment_id = %d",
			$attachment_id
		));
	}
	
	static function deleteByGUID($guid) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->ExecuteMaster(sprintf("DELETE FROM attachment_link WHERE guid = %s",
			$db->qstr($guid)
		));
	}
		
	private static function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AttachmentLink();
			$object->guid = $row['guid'];
			$object->attachment_id = intval($row['attachment_id']);
			$object->context = $row['context'];
			$object->context_id = intval($row['context_id']);
			$objects[$object->guid] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AttachmentLink::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, array(), 'SearchFields_AttachmentLink', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"al.attachment_id as %s, ".
			"al.context as %s, ".
			"al.context_id as %s, ".
			"al.guid as %s, ".
			"a.display_name as %s, ".
			"a.mime_type as %s, ".
			"a.storage_size as %s, ".
			"a.storage_extension as %s, ".
			"a.storage_key as %s, ".
			"a.storage_profile_id as %s, ".
			"a.storage_sha1hash as %s, ".
			"a.updated as %s ".
			"",
				SearchFields_AttachmentLink::ID,
				SearchFields_AttachmentLink::LINK_CONTEXT,
				SearchFields_AttachmentLink::LINK_CONTEXT_ID,
				SearchFields_AttachmentLink::GUID,
				SearchFields_AttachmentLink::ATTACHMENT_DISPLAY_NAME,
				SearchFields_AttachmentLink::ATTACHMENT_MIME_TYPE,
				SearchFields_AttachmentLink::ATTACHMENT_STORAGE_SIZE,
				SearchFields_AttachmentLink::ATTACHMENT_STORAGE_EXTENSION,
				SearchFields_AttachmentLink::ATTACHMENT_STORAGE_KEY,
				SearchFields_AttachmentLink::ATTACHMENT_STORAGE_PROFILE_ID,
				SearchFields_AttachmentLink::ATTACHMENT_STORAGE_SHA1HASH,
				SearchFields_AttachmentLink::ATTACHMENT_UPDATED
		);
		
		$join_sql = "FROM attachment_link al ".
			"INNER JOIN attachment a ON (al.attachment_id=a.id) "
			//(isset($tables['al']) ? "INNER JOIN attachment_link al ON (al.attachment_id=a.id)" : " ")
			;
			
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AttachmentLink');
		
		$result = array(
			'primary_table' => 'al',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
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
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY al.attachment_id ' : '').
			$sort_sql;
		
		if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
			return false;

		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$id = $row[SearchFields_AttachmentLink::GUID];
			$results[$id] = $row;
		}
		
		$total = count($results);

		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(al.attachment_id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
		
};

class SearchFields_AttachmentLink extends DevblocksSearchFields {
	const ID = 'al_attachment_id';
	const LINK_CONTEXT = 'al_context';
	const LINK_CONTEXT_ID = 'al_context_id';
	const GUID = 'al_guid';
	const ATTACHMENT_DISPLAY_NAME = 'a_display_name';
	const ATTACHMENT_MIME_TYPE = 'a_mime_type';
	const ATTACHMENT_STORAGE_SIZE = 'a_storage_size';
	const ATTACHMENT_STORAGE_EXTENSION = 'a_storage_extension';
	const ATTACHMENT_STORAGE_KEY = 'a_storage_key';
	const ATTACHMENT_STORAGE_PROFILE_ID = 'a_storage_profile_id';
	const ATTACHMENT_STORAGE_SHA1HASH = 'a_storage_sha1hash';
	const ATTACHMENT_UPDATED = 'a_updated';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'al.attachment_id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_ATTACHMENT => new DevblocksSearchFieldContextKeys('al.attachment_id', self::ID),
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
			self::ID => new DevblocksSearchField(self::ID, 'al', 'attachment_id', $translate->_('attachment.id'), null, true),
			self::LINK_CONTEXT => new DevblocksSearchField(self::LINK_CONTEXT, 'al', 'context', $translate->_('common.context'), null, true),
			self::LINK_CONTEXT_ID => new DevblocksSearchField(self::LINK_CONTEXT_ID, 'al', 'context_id', $translate->_('common.context_id'), null, true),
			self::GUID => new DevblocksSearchField(self::GUID, 'al', 'guid', $translate->_('common.guid'), null, true),
			self::ATTACHMENT_DISPLAY_NAME => new DevblocksSearchField(self::ATTACHMENT_DISPLAY_NAME, 'a', 'display_name', $translate->_('attachment.display_name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ATTACHMENT_MIME_TYPE => new DevblocksSearchField(self::ATTACHMENT_MIME_TYPE, 'a', 'mime_type', $translate->_('attachment.mime_type'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ATTACHMENT_STORAGE_SIZE => new DevblocksSearchField(self::ATTACHMENT_STORAGE_SIZE, 'a', 'storage_size', $translate->_('attachment.storage_size'), Model_CustomField::TYPE_NUMBER, true),
			self::ATTACHMENT_STORAGE_EXTENSION => new DevblocksSearchField(self::ATTACHMENT_STORAGE_EXTENSION, 'a', 'storage_extension', $translate->_('attachment.storage_extension'), null, true),
			self::ATTACHMENT_STORAGE_KEY => new DevblocksSearchField(self::ATTACHMENT_STORAGE_KEY, 'a', 'storage_key', $translate->_('attachment.storage_key'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ATTACHMENT_STORAGE_PROFILE_ID => new DevblocksSearchField(self::ATTACHMENT_STORAGE_PROFILE_ID, 'a', 'storage_profile_id', $translate->_('attachment.storage_profile_id'), null, true),
			self::ATTACHMENT_STORAGE_SHA1HASH => new DevblocksSearchField(self::ATTACHMENT_STORAGE_SHA1HASH, 'a', 'storage_sha1hash', $translate->_('attachment.storage_sha1hash'), null, true),
			self::ATTACHMENT_UPDATED => new DevblocksSearchField(self::ATTACHMENT_UPDATED, 'a', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_AttachmentLink {
	public $guid;
	public $attachment_id;
	public $context;
	public $context_id;

	/**
	 * @return Model_Attachment
	 */
	public function getAttachment() {
		return DAO_Attachment::get($this->attachment_id);
	}
	
	/**
	 * @return Extension_DevblocksContext
	 */
	public function getContext() {
		return DevblocksPlatform::getExtension($this->context, true, true);
	}
};

class Context_Attachment extends Extension_DevblocksContext {
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
		// [TODO]
		//return DAO_Attachment::random();
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
			'updated',
		);
	}
	
	function getContext($attachment, &$token_labels, &$token_values, $prefix=null) {
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ATTACHMENT);

		if(is_null($prefix))
			$prefix = 'Attachment:';
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// Polymorph
		if(is_numeric($attachment)) {
			$attachment = DAO_Attachment::get($attachment);
		} elseif($attachment instanceof Model_Attachment) {
			// It's what we want already.
		} elseif(is_array($attachment)) {
			$attachment = Cerb_ORMHelper::recastArrayToModel($attachment, 'Model_Attachment');
		} elseif(strlen($attachment) == 36) { // GUID
			$attachment_link = DAO_AttachmentLink::getByGUID($attachment);
			$attachment = $attachment_link->getAttachment();
		} else {
			$attachment = null;
		}
			
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'mime_type' => $prefix.$translate->_('attachment.mime_type'),
			'name' => $prefix.$translate->_('attachment.display_name'),
			'size' => $prefix.$translate->_('attachment.storage_size'),
			'updated' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'id' => 'id',
			'mime_type' => Model_CustomField::TYPE_SINGLE_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'size' => 'size_bytes',
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
		
		if(false == $this->getViewClass())
			return;
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Attachment Links';
		$view->addParams(array(), true);
		$view->renderSortBy = SearchFields_AttachmentLink::ATTACHMENT_UPDATED;
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
		$view->name = 'Attachment Links';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_AttachmentLink::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_AttachmentLink::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
};

class Context_AttachmentLink extends Extension_DevblocksContext {
	function getMeta($context_id) {
		$link = DAO_AttachmentLink::getByGUID($context_id);

		return array(
			'id' => $link->guid,
			'name' => $link->guid,
			'permalink' => null,
			'updated' => 0,
		);
	}
	
	function getRandom() {
		// [TODO]
		//return DAO_AttachmentLink::random();
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
			'guid',
			'attachment_name',
			'context',
			'context_id',
		);
	}
	
	function getContext($model, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Attachment Link:';
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// Polymorph
		if(is_string($model) && strlen($model) == 36) { // GUID
			$model = DAO_AttachmentLink::getByGUID($model);
		} elseif($model instanceof Model_AttachmentLink) {
			// It's what we want already.
		} elseif(is_array($model)) {
			$model = Cerb_ORMHelper::recastArrayToModel($model, 'Model_AttachmentLink');
		} else {
			$model = null;
		}
			
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'guid' => $prefix.$translate->_('common.guid'),
			'context' => $prefix.$translate->_('common.context'),
			'context_id' => $prefix.$translate->_('common.context_id'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'guid' => Model_CustomField::TYPE_SINGLE_LINE,
			'context' => Model_CustomField::TYPE_SINGLE_LINE,
			'context_id' => Model_CustomField::TYPE_NUMBER,
		);
		
		// Token values
		
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_ATTACHMENT_LINK;
		$token_values['_types'] = $token_types;
		
		if(null != $model) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $model->guid;
			
			$token_values['guid'] = $model->guid;
			$token_values['attachment_id'] = $model->attachment_id;
			$token_values['context'] = $model->context;
			$token_values['context_id'] = $model->context_id;
		}
		
		// Attachment
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ATTACHMENT, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'attachment_',
			$prefix.'Attachment:',
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

		$context = CerberusContexts::CONTEXT_ATTACHMENT_LINK;
		$context_id = $dictionary['guid'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
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
		$view->name = 'Attachment Links';
		$view->addParams(array(), true);
		$view->renderSortBy = SearchFields_AttachmentLink::ATTACHMENT_UPDATED;
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
		$view->name = 'Attachment Links';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_AttachmentLink::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_AttachmentLink::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
};