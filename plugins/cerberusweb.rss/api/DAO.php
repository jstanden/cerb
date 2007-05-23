<?php
class Model_Feed {
	public $id = 0;
	public $title = '';
	public $code = '';
	public $worker_id = 0;
	public $params = array();
};

class Model_FeedItem {
    public $id = 0;
    public $feed_id = 0;
    public $event_id = 0;
    public $created = 0;
    public $params = array();
};

class DAO_Feed extends DevblocksORMHelper {
    const ID = 'id';
    const TITLE = 'title';
    const CODE = 'code';
    const WORKER_ID = 'worker_id';
    const PARAMS = 'params';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		$code = self::_generateUniqueCode(8);
		
		$sql = sprintf("INSERT INTO feed (id,title,code,worker_id,params) ".
		    "VALUES (%d,'',%s,0,'')",
		    $id,
		    $db->qstr($code)
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($id, $fields);
		
		return $id;
	}
	
	// [TODO] APIize?
	private static function _generateUniqueCode($length=8) {
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    // [JAS]: [TODO] Inf loop check
	    do {
	        $code = substr(md5(rand(0,1000) * microtime()),0,$length);
	        $exists = $db->GetOne(sprintf("SELECT id FROM feed WHERE code = %s",$db->qstr($code)));
	        
	    } while(!empty($exists));
	    
	    return $code;
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'feed', $fields);
	}
	
	public static function get($id) {
		$items = self::getList(array($id));
		
		if(isset($items[$id]))
		    return $items[$id];
		    
		return NULL;
	}
	
	public static function getList($ids=array()) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,title,code,worker_id,params ".
		    "FROM feed f ".
		    (!empty($ids) ? sprintf("WHERE id IN (%s) ", implode(',', $ids)) : " ").
		    ""
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$objects = array();
		
		while(!$rs->EOF) {
		    $object = new Model_Feed();
		    $object->id = intval($rs->fields['id']);
		    $object->code = $rs->fields['code'];
		    $object->title = $rs->fields['title'];
		    $object->worker_id = intval($rs->fields['worker_id']);
		    $object->params = !empty($rs->fields['params']) ? unserialize($rs->fields['params']) : array();
		    
		    $objects[$object->id] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    $id_list = implode(',', $ids);
	    
	    $sql = sprintf("DELETE FROM feed WHERE id IN (%s)", $id_list);
	    $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

	    // [TODO] cascade foreign key constraints	
	}

	/**
	 * @param string $code
	 * @return Model_Feed
	 */
	public static function getByCode($code,$worker_id=0) {
	    $criteria = array();
	    $criteria[] = new DevblocksSearchCriteria(SearchFields_Feed::CODE,DevblocksSearchCriteria::OPER_EQ,$code);
	    
	    if(!empty($worker_id))
	        $criteria[] = new DevblocksSearchCriteria(SearchFields_Feed::WORKER_ID,DevblocksSearchCriteria::OPER_EQ,$worker_id);

	    list($feeds,$feeds_count) = self::search(
            $criteria,
	        1,
	        0,
	        SearchFields_Feed::TITLE,
	        true,
	        false
	    );
	    
	    if(empty($feeds))
	        return null;
	        
	    $feed = array_shift($feeds);
	    return DAO_Feed::get($feed[SearchFields_Feed::ID]);
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

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_Feed::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"f.id as %s, ".
			"f.title as %s, ".
			"f.code as %s, ".
			"f.worker_id as %s, ".
			"f.params as %s ".
			"FROM feed f ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_Feed::ID,
			    SearchFields_Feed::TITLE,
			    SearchFields_Feed::CODE,
			    SearchFields_Feed::WORKER_ID,
			    SearchFields_Feed::PARAMS
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_Feed::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }
};

class SearchFields_Feed implements IDevblocksSearchFields {
	// Table
	const ID = 'f_id';
	const TITLE = 'f_title';
	const CODE = 'f_code';
	const WORKER_ID = 'f_worker_id';
	const PARAMS = 'f_params';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		return array(
			SearchFields_Feed::ID => new DevblocksSearchField(SearchFields_Feed::ID, 'f', 'id'),
			SearchFields_Feed::TITLE => new DevblocksSearchField(SearchFields_Feed::TITLE, 'f', 'title'),
			SearchFields_Feed::CODE => new DevblocksSearchField(SearchFields_Feed::CODE, 'f', 'code'),
			SearchFields_Feed::WORKER_ID => new DevblocksSearchField(SearchFields_Feed::WORKER_ID, 'f', 'worker_id'),
			SearchFields_Feed::PARAMS => new DevblocksSearchField(SearchFields_Feed::PARAMS, 'f', 'PARAMS'),
		);
	}
};	

class DAO_FeedItem extends DevblocksORMHelper {
    const ID = 'id';
    const FEED_ID = 'feed_id';
    const EVENT_ID = 'event_id';
    const CREATED = 'created';
    const PARAMS = 'params';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('feed_item_seq');
		
		$sql = sprintf("INSERT INTO feed_item (id,feed_id,event_id,created,params) ".
		    "VALUES (%d,0,0,%d,'')",
		    $id,
		    time()
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'feed_item', $fields);
	}
	
	public static function get($id) {
		$items = self::getList(array($id));
		
		if(isset($items[$id]))
		    return $items[$id];
		    
		return NULL;
	}
	
	public static function getList($ids=array()) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT fi.id, fi.feed_id, fi.event_id, fi.created, fi.params ".
		    "FROM feed_item fi ".
		    (!empty($ids) ? sprintf("WHERE fi.id IN (%s) ", implode(',', $ids)) : " ").
		    "ORDER BY fi.created ASC "
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$objects = array();
		
		while(!$rs->EOF) {
		    $object = new Model_FeedItem();
		    $object->id = intval($rs->fields['id']);
		    $object->feed_id = intval($rs->fields['feed_id']);
		    $object->event_id = $rs->fields['event_id'];
		    $object->created = intval($rs->fields['created']);
		    
		    $param_string = $rs->fields['params'];
		    if(!empty($param_string))
		        $object->params = unserialize($param_string);
		    
		    $objects[$object->id] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
	    if(empty($ids)) return;
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    $id_list = implode(',', $ids);
	    
	    $sql = sprintf("DELETE FROM feed_item WHERE id IN (%s)", $id_list);
	    $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

	    // [TODO] cascade foreign key constraints	
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

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_FeedItem::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"fi.id as %s, ".
			"fi.created as %s, ".
			"fi.feed_id as %s, ".
			"fi.event_id as %s, ".
			"fi.params as %s ".
			"FROM feed_item fi ",
			    SearchFields_FeedItem::ID,
			    SearchFields_FeedItem::CREATED,
			    SearchFields_FeedItem::FEED_ID,
			    SearchFields_FeedItem::EVENT_ID,
			    SearchFields_FeedItem::PARAMS
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[SearchFields_FeedItem::ID]);
			$results[$ticket_id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }
};

class SearchFields_FeedItem implements IDevblocksSearchFields {
	// Table
	const ID = 'fi_id';
	const FEED_ID = 'fi_feed_id';
	const EVENT_ID = 'fi_event_id';
	const CREATED = 'fi_created';
	const PARAMS = 'fi_params';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		return array(
			SearchFields_FeedItem::ID => new DevblocksSearchField(SearchFields_FeedItem::ID, 'fi', 'id'),
			SearchFields_FeedItem::FEED_ID => new DevblocksSearchField(SearchFields_FeedItem::FEED_ID, 'fi', 'feed_id'),
			SearchFields_FeedItem::EVENT_ID => new DevblocksSearchField(SearchFields_FeedItem::EVENT_ID, 'fi', 'event_id'),
			SearchFields_FeedItem::CREATED => new DevblocksSearchField(SearchFields_FeedItem::CREATED, 'fi', 'created'),
			SearchFields_FeedItem::PARAMS => new DevblocksSearchField(SearchFields_FeedItem::PARAMS, 'fi', 'params'),
		);
	}
};
?>