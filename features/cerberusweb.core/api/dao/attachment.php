<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class DAO_Attachment extends DevblocksORMHelper {
    const ID = 'id';
    const MESSAGE_ID = 'message_id';
    const DISPLAY_NAME = 'display_name';
    const MIME_TYPE = 'mime_type';
    const STORAGE_EXTENSION = 'storage_extension';
    const STORAGE_KEY = 'storage_key';
    const STORAGE_SIZE = 'storage_size';
    const STORAGE_PROFILE_ID = 'storage_profile_id';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('attachment_seq');
		
		$sql = sprintf("INSERT INTO attachment (id,message_id,display_name,mime_type,storage_size,storage_extension,storage_key,storage_profile_id) ".
		    "VALUES (%d,0,'','',0,'','',0)",
		    $id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
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
		
		$sql = "SELECT id,message_id,display_name,mime_type,storage_size,storage_extension,storage_key,storage_profile_id ".
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
		    $object->message_id = intval($row['message_id']);
		    $object->display_name = $row['display_name'];
		    $object->mime_type = $row['mime_type'];
		    $object->storage_size = intval($row['storage_size']);
		    $object->storage_extension = $row['storage_extension'];
		    $object->storage_key = $row['storage_key'];
		    $object->storage_profile_id = $row['storage_profile_id'];
		    $objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * returns an array of Model_Attachment that
	 * correspond to the supplied message id.
	 *
	 * @param integer $id
	 * @return Model_Attachment[]
	 */
	static function getByMessageId($id) {
		return self::getWhere(sprintf("%s = %d",
			self::MESSAGE_ID,
			$id
		));
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "SELECT attachment.id FROM attachment LEFT JOIN message ON attachment.message_id = message.id WHERE message.id IS NULL";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		$ids_buffer = array();
		$count = 0;
		
		// Delete the physical files
		while($row = mysql_fetch_assoc($rs)) {
			$ids_buffer[$count++] = $row['id'];
			
			// Flush buffer every 50
			if(0 == $count % 50) {
				Storage_Attachments::delete($ids_buffer);
				$ids_buffer = array();
				$count = 0;
			}
		}
		
		// Finish the rest
		if(!empty($ids_buffer)) {
			Storage_Attachments::delete($ids_buffer);
			unset($ids_buffer);
			unset($count);
		}
		
		$sql = "DELETE attachment FROM attachment LEFT JOIN message ON attachment.message_id = message.id WHERE message.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' attachment records.');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;

		Storage_Attachments::delete($ids);
		
		// Delete DB manifests
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("DELETE attachment FROM attachment WHERE id IN (%s)", implode(',', $ids));
		$db->Execute($sql);
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
		$fields = SearchFields_Attachment::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.message_id as %s, ".
			"a.display_name as %s, ".
			"a.mime_type as %s, ".
			"a.storage_size as %s, ".
			"a.storage_extension as %s, ".
			"a.storage_key as %s, ".
			"a.storage_profile_id as %s, ".
		
			"m.address_id as %s, ".
			"m.created_date as %s, ".
			"m.is_outgoing as %s, ".
		
			"t.id as %s, ".
			"t.mask as %s, ".
			"t.subject as %s, ".
		
			"ad.email as %s ".
		
			"FROM attachment a ".
			"INNER JOIN message m ON (a.message_id = m.id) ".
			"INNER JOIN ticket t ON (m.ticket_id = t.id) ".
			"INNER JOIN address ad ON (m.address_id = ad.id) ".
			"",
			    SearchFields_Attachment::ID,
			    SearchFields_Attachment::MESSAGE_ID,
			    SearchFields_Attachment::DISPLAY_NAME,
			    SearchFields_Attachment::MIME_TYPE,
			    SearchFields_Attachment::STORAGE_SIZE,
			    SearchFields_Attachment::STORAGE_EXTENSION,
			    SearchFields_Attachment::STORAGE_KEY,
			    SearchFields_Attachment::STORAGE_PROFILE_ID,
			    
			    SearchFields_Attachment::MESSAGE_ADDRESS_ID,
			    SearchFields_Attachment::MESSAGE_CREATED_DATE,
			    SearchFields_Attachment::MESSAGE_IS_OUTGOING,
			    
			    SearchFields_Attachment::TICKET_ID,
			    SearchFields_Attachment::TICKET_MASK,
			    SearchFields_Attachment::TICKET_SUBJECT,
			    
			    SearchFields_Attachment::ADDRESS_EMAIL
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
            $total = mysql_num_rows($rs);
		}

		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($row[SearchFields_Attachment::ID]);
			$results[$ticket_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = mysql_num_rows($rs);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
	
};

class SearchFields_Attachment implements IDevblocksSearchFields {
    const ID = 'a_id';
    const MESSAGE_ID = 'a_message_id';
    const DISPLAY_NAME = 'a_display_name';
    const MIME_TYPE = 'a_mime_type';
    const STORAGE_SIZE = 'a_storage_size';
    const STORAGE_EXTENSION = 'a_storage_extension';
    const STORAGE_KEY = 'a_storage_key';
    const STORAGE_PROFILE_ID = 'a_storage_profile_id';
	
    const MESSAGE_ADDRESS_ID = 'm_address_id';
    const MESSAGE_CREATED_DATE = 'm_created_date';
    const MESSAGE_IS_OUTGOING = 'm_is_outgoing';
    
    const TICKET_ID = 't_id';
    const TICKET_GROUP_ID = 't_group_id';
    const TICKET_MASK = 't_mask';
    const TICKET_SUBJECT = 't_subject';
    
    const ADDRESS_EMAIL = 'ad_email';
    
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', $translate->_('attachment.id')),
			self::MESSAGE_ID => new DevblocksSearchField(self::MESSAGE_ID, 'a', 'message_id', $translate->_('attachment.message_id')),
			self::DISPLAY_NAME => new DevblocksSearchField(self::DISPLAY_NAME, 'a', 'display_name', $translate->_('attachment.display_name')),
			self::MIME_TYPE => new DevblocksSearchField(self::MIME_TYPE, 'a', 'mime_type', $translate->_('attachment.mime_type')),
			self::STORAGE_SIZE => new DevblocksSearchField(self::STORAGE_SIZE, 'a', 'storage_size', $translate->_('attachment.storage_size')),
			self::STORAGE_EXTENSION => new DevblocksSearchField(self::STORAGE_EXTENSION, 'a', 'storage_extension', $translate->_('attachment.storage_extension')),
			self::STORAGE_KEY => new DevblocksSearchField(self::STORAGE_KEY, 'a', 'storage_key', $translate->_('attachment.storage_key')),
			self::STORAGE_PROFILE_ID => new DevblocksSearchField(self::STORAGE_PROFILE_ID, 'a', 'storage_profile_id', $translate->_('attachment.storage_profile_id')),
			
			self::MESSAGE_ADDRESS_ID => new DevblocksSearchField(self::MESSAGE_ADDRESS_ID, 'm', 'address_id'),
			self::MESSAGE_CREATED_DATE => new DevblocksSearchField(self::MESSAGE_CREATED_DATE, 'm', 'created_date', $translate->_('message.created_date')),
			self::MESSAGE_IS_OUTGOING => new DevblocksSearchField(self::MESSAGE_IS_OUTGOING, 'm', 'is_outgoing', $translate->_('mail.sent')),
			
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 't', 'id', $translate->_('ticket.id')),
			self::TICKET_GROUP_ID => new DevblocksSearchField(self::TICKET_GROUP_ID, 't', 'team_id'), //, $translate->_('ticket.team_id')
			self::TICKET_MASK => new DevblocksSearchField(self::TICKET_MASK, 't', 'mask', $translate->_('ticket.mask')),
			self::TICKET_SUBJECT => new DevblocksSearchField(self::TICKET_SUBJECT, 't', 'subject', $translate->_('ticket.subject')),
			
			self::ADDRESS_EMAIL => new DevblocksSearchField(self::ADDRESS_EMAIL, 'ad', 'email', $translate->_('message.header.from')),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_Attachment {
	public $id;
	public $message_id;
	public $display_name;
	public $mime_type = '';
	public $storage_extension;
	public $storage_key;
	public $storage_size = 0;
	public $storage_profile_id;

	public function getFileContents(&$fp=null) {
		return Storage_Attachments::get($this, $fp);
	}
};

class Storage_Attachments extends Extension_DevblocksStorageSchema {
	const ID = 'cerberusweb.storage.schema.attachments';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	public static function getActiveStorageProfile() {
		return DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile', 'devblocks.storage.engine.disk');
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(dirname(__FILE__))) . '/templates';
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 7));
		
		$tpl->display("file:{$path}/configuration/tabs/storage/schemas/attachments/render.tpl");
	}	
	
	function renderConfig() {
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(dirname(__FILE__))) . '/templates';
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile', 'devblocks.storage.engine.disk'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days', 7));
		
		$tpl->display("file:{$path}/configuration/tabs/storage/schemas/attachments/config.tpl");
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
			$storage = DevblocksPlatform::getStorageService($profile);
			return $storage->delete('attachments', $row['storage_key']);
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
			"INNER JOIN message ON (message.id=attachment.message_id) ".
			"INNER JOIN ticket ON (ticket.id=message.ticket_id) ".
			"WHERE ticket.is_deleted = 0 ".
			"AND ticket.updated_date < %d ".
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
			"INNER JOIN message ON (message.id=attachment.message_id) ".
			"INNER JOIN ticket ON (ticket.id=message.ticket_id) ".
			"WHERE ticket.is_deleted = 0 ".
			"AND ticket.updated_date >= %d ".
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

class View_Attachment extends C4_AbstractView {
	const DEFAULT_ID = 'attachments';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Attachments';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_Attachment::STORAGE_SIZE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Attachment::MIME_TYPE,
			SearchFields_Attachment::STORAGE_SIZE,
			SearchFields_Attachment::MESSAGE_CREATED_DATE,
			SearchFields_Attachment::ADDRESS_EMAIL,
			SearchFields_Attachment::TICKET_MASK,
		);
		$this->columnsHidden = array(
			SearchFields_Attachment::ID,
			SearchFields_Attachment::MESSAGE_ID,
		);
		
		$this->paramsHidden = array(
			SearchFields_Attachment::ID,
			SearchFields_Attachment::MESSAGE_ID,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Attachment::search(
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/configuration/tabs/attachments/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::STORAGE_EXTENSION:
			case SearchFields_Attachment::STORAGE_KEY:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::TICKET_MASK:
			case SearchFields_Attachment::TICKET_SUBJECT:
			case SearchFields_Attachment::ADDRESS_EMAIL:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
//			case SearchFields_Attachment::ID:
//			case SearchFields_Attachment::MESSAGE_ID:
			case SearchFields_Attachment::TICKET_ID:
			case SearchFields_Attachment::STORAGE_SIZE:
			case SearchFields_Attachment::STORAGE_PROFILE_ID:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Attachment::MESSAGE_IS_OUTGOING:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Attachment::MESSAGE_CREATED_DATE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
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
		return SearchFields_Attachment::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Attachment::DISPLAY_NAME:
			case SearchFields_Attachment::MIME_TYPE:
			case SearchFields_Attachment::STORAGE_EXTENSION:
			case SearchFields_Attachment::STORAGE_KEY:
			case SearchFields_Attachment::TICKET_MASK:
			case SearchFields_Attachment::TICKET_SUBJECT:
			case SearchFields_Attachment::ADDRESS_EMAIL:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Attachment::ID:
			case SearchFields_Attachment::MESSAGE_ID:
			case SearchFields_Attachment::TICKET_ID:
			case SearchFields_Attachment::STORAGE_SIZE:
			case SearchFields_Attachment::STORAGE_PROFILE_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Attachment::MESSAGE_CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_Attachment::MESSAGE_IS_OUTGOING:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
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
					$deleted = true;
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Attachment::search(
				$this->params,
				100,
				$pg++,
				SearchFields_Attachment::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!$deleted) { 
				DAO_Attachment::update($batch_ids, $change_fields);
			} else {
				DAO_Attachment::delete($batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};
