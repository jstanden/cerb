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

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$newId = $db->GenID('message_seq');
		
		$sql = sprintf("INSERT INTO message (id,ticket_id,created_date,is_outgoing,worker_id,address_id,storage_extension,storage_key) ".
			"VALUES (%d,0,0,0,0,0,'','')",
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
	 * @return Model_Note[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, ticket_id, created_date, is_outgoing, worker_id, address_id, storage_extension, storage_key ".
			"FROM message ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY created_date asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Note	 */
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
		$sql = "SELECT storage_extension, storage_key FROM message LEFT JOIN ticket ON message.ticket_id = ticket.id WHERE ticket.id IS NULL";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());

		while($row = mysql_fetch_assoc($rs)) {
			$storage = DevblocksPlatform::getStorageService($row['storage_extension']);
			$storage->delete('message_content',$row['storage_key']);
		}	
		mysql_free_result($rs);	

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
			"m.ticket_id as %s, ".
			"m.storage_extension as %s, ".
			"m.storage_key as %s ".
			"FROM message m ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_Message::ID,
			    SearchFields_Message::TICKET_ID,
			    SearchFields_Message::STORAGE_EXTENSION,
			    SearchFields_Message::STORAGE_KEY
			).
			
			// [JAS]: Dynamic table joins
			(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=m.id)" : " ").
			
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
	const TICKET_ID = 'm_ticket_id';
	const STORAGE_EXTENSION = 'm_storage_extension';
	const STORAGE_KEY = 'm_storage_key';
	
	// Headers
	const MESSAGE_HEADER_NAME = 'mh_header_name';
	const MESSAGE_HEADER_VALUE = 'mh_header_value';

	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$columns = array(
			SearchFields_Message::ID => new DevblocksSearchField(SearchFields_Message::ID, 'm', 'id'),
			SearchFields_Message::TICKET_ID => new DevblocksSearchField(SearchFields_Message::TICKET_ID, 'm', 'ticket_id'),
			SearchFields_Message::STORAGE_EXTENSION => new DevblocksSearchField(SearchFields_Message::STORAGE_EXTENSION, 'm', 'storage_extension'),
			SearchFields_Message::STORAGE_KEY => new DevblocksSearchField(SearchFields_Message::STORAGE_KEY, 'm', 'storage_key'),
			
			SearchFields_Message::MESSAGE_HEADER_NAME => new DevblocksSearchField(SearchFields_Message::MESSAGE_HEADER_NAME, 'mh', 'header_name'),
			SearchFields_Message::MESSAGE_HEADER_VALUE => new DevblocksSearchField(SearchFields_Message::MESSAGE_HEADER_VALUE, 'mh', 'header_value'),
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

	function Model_Message() {}

	function getContent() {
		if(empty($this->storage_extension) || empty($this->storage_key))
			return '';
			
		return DAO_MessageContent::get($this->storage_extension, $this->storage_key);
	}

	function getHeaders() {
		return DAO_MessageHeader::getAll($this->id);
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

class DAO_MessageContent {
	/**
	 * 
	 * @param string $storage_extension
	 * @param integer $id
	 * @param string $content
	 * @return string storage key
	 */
    static function set($storage_extension, $id, $content) {
    	$storage = DevblocksPlatform::getStorageService($storage_extension);
    	return $storage->put('message_content', $id, $content);
    }
    
	static function get($storage_extension, $storage_key) {
    	$storage = DevblocksPlatform::getStorageService($storage_extension);
    	return $storage->get('message_content', $storage_key);
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