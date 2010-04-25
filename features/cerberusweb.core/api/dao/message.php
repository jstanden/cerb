<?php
class DAO_Message extends DevblocksORMHelper {
    const ID = 'id';
    const TICKET_ID = 'ticket_id';
    const CREATED_DATE = 'created_date';
    const ADDRESS_ID = 'address_id';
    const IS_OUTGOING = 'is_outgoing';
    const WORKER_ID = 'worker_id';
    const STORAGE_EXTENSION = 'storage_extension';
    const STORAGE_KEY = 'storage_key';
    const STORAGE_PROFILE_ID = 'storage_profile_id';
    const STORAGE_SIZE = 'storage_size';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$newId = $db->GenID('message_seq');
		
		$sql = sprintf("INSERT INTO message (id,ticket_id,created_date,is_outgoing,worker_id,address_id,storage_extension,storage_key,storage_profile_id,storage_size) ".
			"VALUES (%d,0,0,0,0,0,'','',0,0)",
			$newId
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		self::update($newId, $fields);
		
		return $newId;
	}

    static function update($id, $fields) {
        parent::_update($id, 'message', $fields);
    }

	/**
	 * @param string $where
	 * @return Model_Message[]
	 */
	static function getWhere($where=null, $sortBy='created_date', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, ticket_id, created_date, is_outgoing, worker_id, address_id, storage_extension, storage_key, storage_profile_id, storage_size ".
			"FROM message ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Message
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
	
	/**
	 * @param resource $rs
	 * @return Model_Note[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(empty($rs))
			return $objects;
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Message();
			$object->id = $row['id'];
			$object->ticket_id = $row['ticket_id'];
			$object->created_date = $row['created_date'];
			$object->is_outgoing = $row['is_outgoing'];
			$object->worker_id = $row['worker_id'];
			$object->address_id = $row['address_id'];
			$object->storage_extension = $row['storage_extension'];
			$object->storage_key = $row['storage_key'];
			$object->storage_profile_id = $row['storage_profile_id'];
			$object->storage_size = $row['storage_size'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
    
	/**
	 * @return Model_Message[]
	 */
	static function getMessagesByTicket($ticket_id) {
		return self::getWhere(sprintf("%s = %d",
			self::TICKET_ID,
			$ticket_id
		));
	}

    static function maint() {
    	$db = DevblocksPlatform::getDatabaseService();
    	$logger = DevblocksPlatform::getConsoleLog();
    	
		// Purge message content (storage) 
		$sql = "SELECT message.id FROM message LEFT JOIN ticket ON message.ticket_id = ticket.id WHERE ticket.id IS NULL";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());

		$ids_buffer = array();
		$count = 0;
		
		while($row = mysql_fetch_assoc($rs)) {
			$ids_buffer[$count++] = $row['id'];
			
			// Flush buffer every 50
			if(0 == $count % 50) {
				Storage_MessageContent::delete($ids_buffer);
				$ids_buffer = array();
				$count = 0;
			}
		}	
		mysql_free_result($rs);

		// Any remainder
		if(!empty($ids_buffer)) {
			Storage_MessageContent::delete($ids_buffer);
			unset($ids_buffer);
			unset($count);
		}

		// Purge messages without linked tickets  
		$sql = "DELETE QUICK message FROM message LEFT JOIN ticket ON message.ticket_id = ticket.id WHERE ticket.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message records.');
		
		// Headers
		$sql = "DELETE QUICK message_header FROM message_header LEFT JOIN message ON message_header.message_id = message.id WHERE message.id IS NULL";
		$db->Execute($sql);

		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message_header records.');
		
		// Notes
		$sql = "DELETE QUICK message_note FROM message_note LEFT JOIN message ON message_note.message_id = message.id WHERE message.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message_note records.');
		
		// Search indexes

		$sql = "DELETE QUICK fulltext_message_content FROM fulltext_message_content LEFT JOIN message ON fulltext_message_content.id = message.id WHERE message.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_message_content records.');
		
		// Attachments
		DAO_Attachment::maint();
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
		$fields = SearchFields_Message::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres,$selects) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document

		$sql = sprintf("SELECT ".
			"m.id as %s, ".
			"m.address_id as %s, ".
			"m.created_date as %s, ".
			"m.ticket_id as %s, ".
			"m.storage_extension as %s, ".
			"m.storage_key as %s, ".
			"m.storage_profile_id as %s, ".
			"m.storage_size as %s ".
			"FROM message m ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_Message::ID,
			    SearchFields_Message::ADDRESS_ID,
			    SearchFields_Message::CREATED_DATE,
			    SearchFields_Message::TICKET_ID,
			    SearchFields_Message::STORAGE_EXTENSION,
			    SearchFields_Message::STORAGE_KEY,
			    SearchFields_Message::STORAGE_PROFILE_ID,
			    SearchFields_Message::STORAGE_SIZE
			).
			
			// [JAS]: Dynamic table joins
			(isset($tables['t']) ? "INNER JOIN ticket t ON (t.id=m.ticket_id)" : " ").
			(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=m.id)" : " ").
			(isset($tables['ftmc']) ? "INNER JOIN fulltext_message_content ftmc ON (ftmc.id=m.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($row[SearchFields_Message::ID]);
			$results[$ticket_id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = mysql_num_rows($rs);
		}

		mysql_free_result($rs);
		
		return array($results,$total);
    }
};

class SearchFields_Message implements IDevblocksSearchFields {
	// Message
	const ID = 'm_id';
	const ADDRESS_ID = 'm_address_id';
	const CREATED_DATE = 'm_created_date';
	const TICKET_ID = 'm_ticket_id';
	const STORAGE_EXTENSION = 'm_storage_extension';
	const STORAGE_KEY = 'm_storage_key';
	const STORAGE_PROFILE_ID = 'm_storage_profile_id';
	const STORAGE_SIZE = 'm_storage_size';
	
	// Headers
	const MESSAGE_HEADER_NAME = 'mh_header_name';
	const MESSAGE_HEADER_VALUE = 'mh_header_value';

	// Content
	const MESSAGE_CONTENT = 'ftmc_content';
	
	// Ticket
	const TICKET_GROUP_ID = 't_group_id';

	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$columns = array(
			SearchFields_Message::ID => new DevblocksSearchField(SearchFields_Message::ID, 'm', 'id'),
			SearchFields_Message::ADDRESS_ID => new DevblocksSearchField(SearchFields_Message::ADDRESS_ID, 'm', 'address_id'),
			SearchFields_Message::CREATED_DATE => new DevblocksSearchField(SearchFields_Message::CREATED_DATE, 'm', 'created_date'),
			SearchFields_Message::TICKET_ID => new DevblocksSearchField(SearchFields_Message::TICKET_ID, 'm', 'ticket_id'),
			SearchFields_Message::STORAGE_EXTENSION => new DevblocksSearchField(SearchFields_Message::STORAGE_EXTENSION, 'm', 'storage_extension'),
			SearchFields_Message::STORAGE_KEY => new DevblocksSearchField(SearchFields_Message::STORAGE_KEY, 'm', 'storage_key'),
			SearchFields_Message::STORAGE_PROFILE_ID => new DevblocksSearchField(SearchFields_Message::STORAGE_PROFILE_ID, 'm', 'storage_profile_id'),
			SearchFields_Message::STORAGE_SIZE => new DevblocksSearchField(SearchFields_Message::STORAGE_SIZE, 'm', 'storage_size'),
			
			SearchFields_Message::MESSAGE_HEADER_NAME => new DevblocksSearchField(SearchFields_Message::MESSAGE_HEADER_NAME, 'mh', 'header_name'),
			SearchFields_Message::MESSAGE_HEADER_VALUE => new DevblocksSearchField(SearchFields_Message::MESSAGE_HEADER_VALUE, 'mh', 'header_value'),
			
			SearchFields_Message::MESSAGE_CONTENT => new DevblocksSearchField(SearchFields_Message::MESSAGE_CONTENT, 'ftmc', 'content'),
			
			SearchFields_Message::TICKET_GROUP_ID => new DevblocksSearchField(SearchFields_Message::TICKET_GROUP_ID, 't', 'team_id'),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;
	}
};

class Model_Message {
	public $id;
	public $ticket_id;
	public $created_date;
	public $address_id;
	public $is_outgoing;
	public $worker_id;
	public $storage_extension;
	public $storage_key;
	public $storage_profile_id;
	public $storage_size;

	function Model_Message() {}

	function getContent(&$fp=null) {
		if(empty($this->storage_extension) || empty($this->storage_key))
			return '';

		return Storage_MessageContent::get($this, $fp);
	}

	function getHeaders() {
		return DAO_MessageHeader::getAll($this->id);
	}

	function getSender() {
		return DAO_Address::get($this->address_id);
	}
	
	/**
	 * returns an array of the message's attachments
	 *
	 * @return Model_Attachment[]
	 */
	function getAttachments() {
		$attachments = DAO_Attachment::getByMessageId($this->id);
		return $attachments;
	}
};

class Search_MessageContent {
	const ID = 'cerberusweb.search.schema.message_content';
	
	public static function index($stop_time=null) {
		$logger = DevblocksPlatform::getConsoleLog();
		
		if(false == ($search = DevblocksPlatform::getSearchService())) {
			$logger->error("[Search] The search engine is misconfigured.");
			return;
		}
		
		$ns = 'message_content';
		$id = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'last_indexed_id', 0);
		$done = false;
		
		while(!$done && time() < $stop_time) {
			$where = sprintf("%s > %d", DAO_Message::ID, $id);
			$messages = DAO_Message::getWhere($where, 'id', true, 100);
	
			if(empty($messages)) {
				$done = true;
				continue;
			}
			
			foreach($messages as $message) { /* @var $message Model_Message */
				$id = $message->id;
				$logger->info(sprintf("[Search] Indexing %s %d...", 
					$ns,
					$id
				));
				
				if(false !== ($content = Storage_MessageContent::get($message))) {
					$search->index($ns, $id, $content);
				}
				
				flush();
			}
		}
		
		if(!empty($id))
			DAO_DevblocksExtensionPropertyStore::put(self::ID, 'last_indexed_id', $id);
	}
};

class Storage_MessageContent extends Extension_DevblocksStorageSchema {
	const ID = 'cerberusweb.storage.schema.message_content';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
	
	public static function getActiveStorageProfile() {
		return DAO_DevblocksExtensionPropertyStore::get(self::ID, 'active_storage_profile', 'devblocks.storage.engine.database');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(dirname(__FILE__))) . '/templates';
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days'));
		
		$tpl->display("file:{$path}/configuration/tabs/storage/schemas/message_content/render.tpl");
	}	
	
	function renderConfig() {
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(dirname(__FILE__))) . '/templates';
		
		$tpl->assign('active_storage_profile', $this->getParam('active_storage_profile'));
		$tpl->assign('archive_storage_profile', $this->getParam('archive_storage_profile'));
		$tpl->assign('archive_after_days', $this->getParam('archive_after_days'));
		
		$tpl->display("file:{$path}/configuration/tabs/storage/schemas/message_content/config.tpl");
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
	 * @param Model_Message | $message_id
	 * @return unknown_type
	 */
	public static function get($object, &$fp=null) {
		if($object instanceof Model_Message) {
			// Do nothing
		} elseif(is_numeric($object)) {
			$object = DAO_Message::get($object);
		} else {
			$object = null;
		}
		
		if(empty($object))
			return false;
		
		$key = $object->storage_key;
		$profile = !empty($object->storage_profile_id) ? $object->storage_profile_id : $object->storage_extension;
		
		if(false === ($storage = DevblocksPlatform::getStorageService($profile)))
			return false;
			
		$contents = $storage->get('message_content', $key, $fp);
		
		// Convert the appropriate bytes
		if(!mb_check_encoding($contents, LANG_CHARSET_CODE))
			$contents = mb_convert_encoding($contents, LANG_CHARSET_CODE);
			
		return $contents;
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

		// Store the appropriate bytes
		if(!mb_check_encoding($contents, LANG_CHARSET_CODE))
			$contents = mb_convert_encoding($contents, LANG_CHARSET_CODE);
		
		// Save to storage
		if(false === ($storage_key = $storage->put('message_content', $id, $contents)))
			return false;
	    
		if(is_resource($contents)) {
			$stats = fstat($contents);
			$storage_size = $stats['size'];
		} else {
			$storage_size = strlen($contents);
			unset($contents);
		}
			
		// Update storage key
	    DAO_Message::update($id, array(
	        DAO_Message::STORAGE_EXTENSION => $storage->manifest->id,
	        DAO_Message::STORAGE_KEY => $storage_key,
	        DAO_Message::STORAGE_PROFILE_ID => $profile_id,
	        DAO_Message::STORAGE_SIZE => $storage_size,
	    ));
	    
	    return $storage_key;
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT storage_extension, storage_key, storage_profile_id FROM message WHERE id IN (%s)", implode(',',$ids));
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		// Delete the physical files
		
		while($row = mysql_fetch_assoc($rs)) {
			$profile = !empty($row['storage_profile_id']) ? $row['storage_profile_id'] : $row['storage_extension'];
			$storage = DevblocksPlatform::getStorageService($profile);
			$storage->delete('message_content', $row['storage_key']);
		}
		
		mysql_free_result($rs);
		
		return true; 
	}
	
	public function getStats() {
		return $this->_stats('message');
	}
		
	public static function archive($stop_time=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Params
		$dst_profile = DAO_DevblocksStorageProfile::get(DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_storage_profile'));
		$archive_after_days = DAO_DevblocksExtensionPropertyStore::get(self::ID, 'archive_after_days');
				
		if(empty($dst_profile))
			return;

		// Find inactive attachments
		$sql = sprintf("SELECT message.id, message.storage_extension, message.storage_key, message.storage_profile_id, message.storage_size ".
			"FROM message ".
			"INNER JOIN ticket ON (ticket.id=message.ticket_id) ".
			"WHERE ticket.is_deleted = 0 ".
			"AND ticket.updated_date < %d ".
			"AND NOT (message.storage_extension = %s AND message.storage_profile_id = %d) ".
			"ORDER BY message.id ASC ",
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
		$sql = sprintf("SELECT message.id, message.storage_extension, message.storage_key, message.storage_profile_id, message.storage_size ".
			"FROM message ".
			"INNER JOIN ticket ON (ticket.id=message.ticket_id) ".
			"WHERE ticket.is_deleted = 0 ".
			"AND ticket.updated_date >= %d ".
			"AND NOT (message.storage_extension = %s AND message.storage_profile_id = %d) ".
			"ORDER BY message.id DESC ",
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
		
		$ns = 'message_content';
		
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

class DAO_MessageHeader {
    const MESSAGE_ID = 'message_id';
    const HEADER_NAME = 'header_name';
    const HEADER_VALUE = 'header_value';
    
    static function create($message_id, $header, $value) {
    	$db = DevblocksPlatform::getDatabaseService();
    	
        if(empty($header) || empty($value) || empty($message_id))
            return;
    	
        $header = strtolower($header);

        // Handle stacked headers
        if(is_array($value)) {
        	$value = implode("\r\n",$value);
        }
        
		$db->Execute(sprintf("INSERT INTO message_header (message_id, header_name, header_value) ".
			"VALUES (%d, %s, %s)",
			$message_id,
			$db->qstr($header),
			$db->qstr($value)
		));
    }
    
    static function getAll($message_id) {
        $db = DevblocksPlatform::getDatabaseService();
        
        $sql = sprintf("SELECT header_name, header_value ".
            "FROM message_header ".
            "WHERE message_id = %d",
        	$message_id
        );
            
        $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

        $headers = array();
            
        while($row = mysql_fetch_assoc($rs)) {
            $headers[$row['header_name']] = $row['header_value'];
        }
        
        mysql_free_result($rs);
        
        return $headers;
    }
    
    static function getUnique() {
        $db = DevblocksPlatform::getDatabaseService();
        $headers = array();
        
        $sql = "SELECT header_name FROM message_header GROUP BY header_name";
        $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
        
        while($row = mysql_fetch_assoc($rs)) {
            $headers[] = $row['header_name'];
        }
        
        mysql_free_result($rs);
        
        sort($headers);
        
        return $headers;
    }
};
