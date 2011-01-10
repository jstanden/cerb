<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class DAO_Attachment extends DevblocksORMHelper {
    const ID = 'id';
    const DISPLAY_NAME = 'display_name';
    const MIME_TYPE = 'mime_type';
    const STORAGE_EXTENSION = 'storage_extension';
    const STORAGE_KEY = 'storage_key';
    const STORAGE_SIZE = 'storage_size';
    const STORAGE_PROFILE_ID = 'storage_profile_id';
    const UPDATED = 'updated';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		
	    if(!isset($fields[self::UPDATED]))
	    	$fields[self::UPDATED] = time();
	    
		$sql = "INSERT INTO attachment () VALUES ()";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'attachment', $fields);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Attachment
	 */
	public static function get($id) {
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
		
		$sql = "SELECT id,display_name,mime_type,storage_size,storage_extension,storage_key,storage_profile_id,updated ".
			"FROM attachment ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "");
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	private static function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
		    $object = new Model_Attachment();
		    $object->id = intval($row['id']);
		    $object->display_name = $row['display_name'];
		    $object->mime_type = $row['mime_type'];
		    $object->storage_size = intval($row['storage_size']);
		    $object->storage_extension = $row['storage_extension'];
		    $object->storage_key = $row['storage_key'];
		    $object->storage_profile_id = $row['storage_profile_id'];
		    $object->updated = intval($row['updated']);
		    $objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	// [TODO] Move this??
	static function getByContextIds($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);

		if(empty($context_ids))
			return array();
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,display_name,mime_type,storage_size,storage_extension,storage_key,storage_profile_id,updated ".
			"FROM attachment ".
			"INNER JOIN attachment_link ON (attachment.id=attachment_link.attachment_id) ".
			"WHERE attachment_link.context = %s AND attachment_link.context_id IN (%s) ",
			$db->qstr($context),
			implode(',', $context_ids)
		);
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		// Delete attachments where links=0 and created > 24h
		$db->Execute(sprintf("DELETE attachment ".
			"FROM attachment ".
			"LEFT JOIN attachment_link ON (attachment.id = attachment_link.attachment_id) ".
			"WHERE attachment_link.attachment_id IS NULL ".
			"AND attachment.updated <= %d",
			(time()-86400)
		)); 
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' attachment records.');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;

		Storage_Attachments::delete($ids);
		
		// Delete links
		foreach($ids as $id)
			DAO_AttachmentLink::removeAllByAttachment($id);
		
		// Delete DB manifests
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("DELETE attachment FROM attachment WHERE id IN (%s)", implode(',', $ids));
		$db->Execute($sql);
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Attachment::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		
		$select_sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.display_name as %s, ".
			"a.mime_type as %s, ".
			"a.storage_size as %s, ".
			"a.storage_extension as %s, ".
			"a.storage_key as %s, ".
			"a.storage_profile_id as %s, ".
			"a.updated as %s ".
			"",
			    SearchFields_Attachment::ID,
			    SearchFields_Attachment::DISPLAY_NAME,
			    SearchFields_Attachment::MIME_TYPE,
			    SearchFields_Attachment::STORAGE_SIZE,
			    SearchFields_Attachment::STORAGE_EXTENSION,
			    SearchFields_Attachment::STORAGE_KEY,
			    SearchFields_Attachment::STORAGE_PROFILE_ID,
			    SearchFields_Attachment::UPDATED
		);
		
		$join_sql = "FROM attachment a ".
			(isset($tables['al']) ? "INNER JOIN attachment_link al ON (al.attachment_id=a.id)" : " ")
			;
			
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$has_multiple_values = false;
		
		$result = array(
			'primary_table' => 'a',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
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
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents(array(),$params,$sortBy,$sortAsc);

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
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_Attachment::ID]);
			$results[$id] = $result;
		}

		if($withCounts) {
			$count_sql = 
				"SELECT COUNT(a.id) ".
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
};

class SearchFields_Attachment implements IDevblocksSearchFields {
    const ID = 'a_id';
    const DISPLAY_NAME = 'a_display_name';
    const MIME_TYPE = 'a_mime_type';
    const STORAGE_SIZE = 'a_storage_size';
    const STORAGE_EXTENSION = 'a_storage_extension';
    const STORAGE_KEY = 'a_storage_key';
    const STORAGE_PROFILE_ID = 'a_storage_profile_id';
    const UPDATED = 'a_updated';
	
    const LINK_CONTEXT = 'al_context';
    const LINK_CONTEXT_ID = 'al_context_id';
    
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', $translate->_('attachment.id')),
			self::DISPLAY_NAME => new DevblocksSearchField(self::DISPLAY_NAME, 'a', 'display_name', $translate->_('attachment.display_name')),
			self::MIME_TYPE => new DevblocksSearchField(self::MIME_TYPE, 'a', 'mime_type', $translate->_('attachment.mime_type')),
			self::STORAGE_SIZE => new DevblocksSearchField(self::STORAGE_SIZE, 'a', 'storage_size', $translate->_('attachment.storage_size')),
			self::STORAGE_EXTENSION => new DevblocksSearchField(self::STORAGE_EXTENSION, 'a', 'storage_extension', $translate->_('attachment.storage_extension')),
			self::STORAGE_KEY => new DevblocksSearchField(self::STORAGE_KEY, 'a', 'storage_key', $translate->_('attachment.storage_key')),
			self::STORAGE_PROFILE_ID => new DevblocksSearchField(self::STORAGE_PROFILE_ID, 'a', 'storage_profile_id', $translate->_('attachment.storage_profile_id')),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'a', 'updated', $translate->_('common.updated')),

			self::LINK_CONTEXT => new DevblocksSearchField(self::LINK_CONTEXT, 'al', 'context', $translate->_('common.context')),
			self::LINK_CONTEXT_ID => new DevblocksSearchField(self::LINK_CONTEXT_ID, 'al', 'context_id', $translate->_('common.context_id')),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

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
	public $updated;

	public function getFileContents(&$fp=null) {
		return Storage_Attachments::get($this, $fp);
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
		
		$tpl->display("devblocks:cerberusweb.core::configuration/tabs/storage/schemas/attachments/render.tpl");
	}	
	
	function renderConfig() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 7));
		
		$tpl->display("devblocks:cerberusweb.core::configuration/tabs/storage/schemas/attachments/config.tpl");
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
			
		if(is_resource($contents)) {
			$stats = fstat($contents);
			$storage_size = $stats['size'];
		} else {
			$storage_size = strlen($contents);
			unset($contents);
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
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		// Delete the physical files
		
		while($row = mysql_fetch_assoc($rs)) {
			$profile = !empty($row['storage_profile_id']) ? $row['storage_profile_id'] : $row['storage_extension'];
			if(null != ($storage = DevblocksPlatform::getStorageService($profile)))
				$storage->delete('attachments', $row['storage_key']);
		}
		
		mysql_free_result($rs);
		
		return true;
	}
	
	public function getStats() {
		return $this->_stats('attachment');
	}
	
	public static function archive($stop_time=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Params
		$dst_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_storage_profile'));
		$archive_after_days = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_after_days');
		
		if(empty($dst_profile))
			return;
		
		// Find inactive attachments
		$sql = sprintf("SELECT attachment.id, attachment.storage_extension, attachment.storage_key, attachment.storage_profile_id, attachment.storage_size ".
			"FROM attachment ".
			"WHERE attachment.updated < %d ".
			"AND NOT (attachment.storage_extension = %s AND attachment.storage_profile_id = %d) ".
			"ORDER BY attachment.id ASC ",
				time()-(86400*$archive_after_days),
				$db->qstr($dst_profile->extension_id),
				$dst_profile->id
		);
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			self::_migrate($dst_profile, $row);

			if(time() > $stop_time)
				return;
		}
	}
	
	public static function unarchive($stop_time=null) {
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
		$rs = $db->Execute($sql);
		
		while($row = mysql_fetch_assoc($rs)) {
			self::_migrate($dst_profile, $row, true);

			if(time() > $stop_time)
				return;
		}
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

class View_AttachmentLink extends C4_AbstractView {
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
//			SearchFields_AttachmentLink::LINK_CONTEXT,
			SearchFields_AttachmentLink::LINK_CONTEXT_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_AttachmentLink::ID,
//			SearchFields_AttachmentLink::LINK_CONTEXT,
			SearchFields_AttachmentLink::LINK_CONTEXT_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_AttachmentLink::search(
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AttachmentLink', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// [TODO] Move
		$tpl->display('devblocks:cerberusweb.core::configuration/tabs/attachments/view.tpl');
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
			default:
				echo '';
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
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_SIZE:
			case SearchFields_AttachmentLink::ATTACHMENT_STORAGE_PROFILE_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_AttachmentLink::ATTACHMENT_UPDATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
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
				$this->getParams(),
				100,
				$pg++,
				SearchFields_AttachmentLink::GUID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!$deleted) { 
				//DAO_AttachmentLink::update($batch_ids, $change_fields);
			} else {
				if(!empty($batch_ids))
				foreach($batch_ids as $batch_id)
					DAO_AttachmentLink::deleteByGUID($batch_id);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class DAO_AttachmentLink extends C4_ORMHelper {
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
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * ...
	 * 
	 * @param string $guid
	 * @return Model_AttachmentLink
	 */
	static function getByGUID($guid) {
		return array_shift(self::getWhere(sprintf("%s = %s",
			self::GUID,
			self::qstr($guid)
		)));
	}
	
	static function create($attachment_id, $context, $context_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("INSERT IGNORE INTO attachment_link (attachment_id, context, context_id, guid) ".
			"VALUES (%d, %s, %d, UUID())",
			$attachment_id,
			$db->qstr($context),
			$context_id
		));
	}
	
	/**
	 * ...
	 * 
	 * @param unknown_type $context
	 * @param unknown_type $context_id
	 * @param unknown_type $attachment_ids
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
			DAO_AttachmentLink::deleteByAttachment($deleted_id);
			
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
		$rs = $db->Execute($sql);
		
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
		$rs = $db->Execute($sql);

		return self::_getObjectsFromResult($rs);
	}
	
	static function getIdsByContext($attachment_ids, $context=null) {
		if(!is_array($attachment_ids))
			$attachment_ids = array($attachment_ids);
		
		if(empty($attachment_ids))
			return array();
			
		$db = DevblocksPlatform::getDatabaseService();
		$rows = $db->GetArray(sprintf("SELECT attachment_id, context, context_id ".
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
		$db->Execute(sprintf("DELETE FROM attachment_link WHERE attachment_id = %d", 
			$attachment_id
		));
	}
	
	static function removeAllByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
			
		if(empty($context_ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("DELETE FROM attachment_link WHERE context = %s AND context_id IN (%s)",
			$db->qstr($context),
			implode(',', $context_ids)
		));
	}
	
	static function deleteByAttachment($attachment_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("DELETE FROM attachment_link WHERE attachment_id = %d",
			$attachment_id
		));
	}
	
	static function deleteByGUID($guid) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("DELETE FROM attachment_link WHERE guid = %s",
			$db->qstr($guid)
		));
	}
		
	private static function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
		    $object = new Model_AttachmentLink();
		    $object->guid = $row['guid'];
		    $object->attachment_id = intval($row['attachment_id']);
		    $object->context = $row['context'];
		    $object->context_id = intval($row['context_id']);
		    $objects[$object->guid] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AttachmentLink::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		
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
			    SearchFields_AttachmentLink::ATTACHMENT_UPDATED
		);
		
		$join_sql = "FROM attachment_link al ".
			"INNER JOIN attachment a ON (al.attachment_id=a.id) "
			//(isset($tables['al']) ? "INNER JOIN attachment_link al ON (al.attachment_id=a.id)" : " ")
			;
			
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$has_multiple_values = false;
		
		$result = array(
			'primary_table' => 'al',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
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
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents(array(),$params,$sortBy,$sortAsc);

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
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_AttachmentLink::ID]);
			$results[$id] = $result;
		}

		if($withCounts) {
			$count_sql = 
				"SELECT COUNT(al.attachment_id) ".
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
    	
};

class SearchFields_AttachmentLink implements IDevblocksSearchFields {
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
    const ATTACHMENT_UPDATED = 'a_updated';
    
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'al', 'attachment_id', $translate->_('attachment.id')),
			self::LINK_CONTEXT => new DevblocksSearchField(self::LINK_CONTEXT, 'al', 'context', $translate->_('common.context')),
			self::LINK_CONTEXT_ID => new DevblocksSearchField(self::LINK_CONTEXT_ID, 'al', 'context_id', $translate->_('common.context_id')),
			self::GUID => new DevblocksSearchField(self::GUID, 'al', 'guid', $translate->_('common.guid')),
			self::ATTACHMENT_DISPLAY_NAME => new DevblocksSearchField(self::ATTACHMENT_DISPLAY_NAME, 'a', 'display_name', $translate->_('attachment.display_name')),
			self::ATTACHMENT_MIME_TYPE => new DevblocksSearchField(self::ATTACHMENT_MIME_TYPE, 'a', 'mime_type', $translate->_('attachment.mime_type')),
			self::ATTACHMENT_STORAGE_SIZE => new DevblocksSearchField(self::ATTACHMENT_STORAGE_SIZE, 'a', 'storage_size', $translate->_('attachment.storage_size')),
			self::ATTACHMENT_STORAGE_EXTENSION => new DevblocksSearchField(self::ATTACHMENT_STORAGE_EXTENSION, 'a', 'storage_extension', $translate->_('attachment.storage_extension')),
			self::ATTACHMENT_STORAGE_KEY => new DevblocksSearchField(self::ATTACHMENT_STORAGE_KEY, 'a', 'storage_key', $translate->_('attachment.storage_key')),
			self::ATTACHMENT_STORAGE_PROFILE_ID => new DevblocksSearchField(self::ATTACHMENT_STORAGE_PROFILE_ID, 'a', 'storage_profile_id', $translate->_('attachment.storage_profile_id')),
			self::ATTACHMENT_UPDATED => new DevblocksSearchField(self::ATTACHMENT_UPDATED, 'a', 'updated', $translate->_('common.updated')),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

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
