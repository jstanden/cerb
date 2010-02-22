<?php
class DAO_MessageNote extends DevblocksORMHelper {
    const ID = 'id';
    const TYPE = 'type';
    const MESSAGE_ID = 'message_id';
    const WORKER_ID = 'worker_id';
    const CREATED = 'created';
    const CONTENT = 'content';

    static function create($fields) {
    	$db = DevblocksPlatform::getDatabaseService();
    	
    	$id = $db->GenID('message_note_seq');
    	
    	$sql = sprintf("INSERT INTO message_note (id,type,message_id,worker_id,created,content) ".
    		"VALUES (%d,0,0,0,%d,'')",
    		$id,
    		time()
    	);
    	$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

    	self::update($id, $fields);
    }

    static function getByMessageId($message_id) {
    	$db = DevblocksPlatform::getDatabaseService();
    	
    	$sql = sprintf("SELECT id,type,message_id,worker_id,created,content ".
    		"FROM message_note ".
    		"WHERE message_id = %d ".
    		"ORDER BY id ASC",
    		$message_id
    	);
    	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

    	return self::_getObjectsFromResultSet($rs);
    }
    
    static function getByTicketId($ticket_id) {
    	$db = DevblocksPlatform::getDatabaseService();
    	
    	$sql = sprintf("SELECT n.id,n.type,n.message_id,n.worker_id,n.created,n.content ".
    		"FROM message_note n ".
    		"INNER JOIN message m ON (m.id=n.message_id) ".
    		"WHERE m.ticket_id = %d ".
    		"ORDER BY n.id ASC",
    		$ticket_id
    	);
    	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

    	return self::_getObjectsFromResultSet($rs);
    }

    static function getList($ids) {
    	if(!is_array($ids)) $ids = array($ids);
    	$db = DevblocksPlatform::getDatabaseService();
    	
    	$sql = sprintf("SELECT n.id,n.type,n.message_id,n.worker_id,n.created,n.content ".
    		"FROM message_note n ".
    		"WHERE n.id IN (%s) ".
    		"ORDER BY n.id ASC",
    		implode(',', $ids)
    	);
    	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

    	return self::_getObjectsFromResultSet($rs);
    }
    	
    static function get($id) {
    	$objects = self::getList(array($id));
    	return @$objects[$id];
    }
    
    static private function _getObjectsFromResultSet($rs) {
    	$objects = array();
    	
    	while($row = mysql_fetch_assoc($rs)) {
    		$object = new Model_MessageNote();
    		$object->id = intval($row['id']);
    		$object->type = intval($row['type']);
    		$object->message_id = intval($row['message_id']);
    		$object->created = intval($row['created']);
    		$object->worker_id = intval($row['worker_id']);
    		$object->content = $row['content'];
    		$objects[$object->id] = $object;
    	}
    	
    	mysql_free_result($rs);
    	
    	return $objects;
    }
    
    static function update($ids, $fields) {
    	if(!is_array($ids)) $ids = array($ids);
    	$db = DevblocksPlatform::getDatabaseService();

    	// Update our blob manually
    	if($fields[self::CONTENT]) {
    		$db->UpdateBlob('message_note', self::CONTENT, $fields[self::CONTENT], 'id IN('.implode(',',$ids).')');
    		unset($fields[self::CONTENT]);
    	}
    	
    	parent::_update($ids, 'message_note', $fields);
    }
    
    static function delete($ids) {
        if(!is_array($ids)) $ids = array($ids);

		if(empty($ids))
			return;

		$db = DevblocksPlatform::getDatabaseService();
        
        $message_ids = implode(',', $ids);
        $sql = sprintf("DELETE QUICK FROM message_note WHERE id IN (%s)", $message_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
    }
};

class Model_MessageNote {
	const TYPE_NOTE = 0;
	const TYPE_WARNING = 1;
	const TYPE_ERROR = 2;

	public $id;
	public $type;
	public $message_id;
	public $created;
	public $worker_id;
	public $content;
};
