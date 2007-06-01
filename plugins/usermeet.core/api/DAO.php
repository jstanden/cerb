<?php
class DAO_CommunityTool extends DevblocksORMHelper {
    const ID = 'id';
    const CODE = 'code';
    const COMMUNITY_ID = 'community_id';
    const EXTENSION_ID = 'extension_id';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		$code = self::_generateUniqueCode();
		
		$sql = sprintf("INSERT INTO community_tool (id,code,community_id,extension_id) ".
		    "VALUES (%d,%s,0,'')",
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
	        $exists = $db->GetOne(sprintf("SELECT id FROM community_tool WHERE code = %s",$db->qstr($code)));
	        
	    } while(!empty($exists));
	    
	    return $code;
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'community_tool', $fields);
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
		
		$sql = "SELECT id,code,community_id,extension_id ".
		    "FROM community_tool ".
		    (!empty($ids) ? sprintf("WHERE id IN (%s) ", implode(',', $ids)) : " ").
		    "ORDER BY community_id"
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$objects = array();
		
		while(!$rs->EOF) {
		    $object = new Model_CommunityTool();
		    $object->id = intval($rs->fields['id']);
		    $object->code = $rs->fields['code'];
		    $object->community_id = intval($rs->fields['community_id']);
		    $object->extension_id = $rs->fields['extension_id'];
		    $objects[$object->id] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    $id_list = implode(',', $ids);
	    
	    $sql = sprintf("DELETE FROM community_tool WHERE id IN (%s)", $id_list);
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

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_CommunityTool::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"ct.id as %s, ".
			"ct.code as %s, ".
			"ct.community_id as %s, ".
			"ct.extension_id as %s ".
			"FROM community_tool ct ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_CommunityTool::ID,
			    SearchFields_CommunityTool::CODE,
			    SearchFields_CommunityTool::COMMUNITY_ID,
			    SearchFields_CommunityTool::EXTENSION_ID
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
			$ticket_id = intval($rs->fields[SearchFields_CommunityTool::ID]);
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

class SearchFields_CommunityTool implements IDevblocksSearchFields {
	// Table
	const ID = 'ct_id';
	const CODE = 'ct_code';
	const COMMUNITY_ID = 'ct_community_id';
	const EXTENSION_ID = 'ct_extension_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		return array(
			SearchFields_CommunityTool::ID => new DevblocksSearchField(SearchFields_CommunityTool::ID, 'ct', 'id'),
			SearchFields_CommunityTool::CODE => new DevblocksSearchField(SearchFields_CommunityTool::CODE, 'ct', 'code'),
			SearchFields_CommunityTool::COMMUNITY_ID => new DevblocksSearchField(SearchFields_CommunityTool::COMMUNITY_ID, 'ct', 'community_id'),
			SearchFields_CommunityTool::EXTENSION_ID => new DevblocksSearchField(SearchFields_CommunityTool::EXTENSION_ID, 'ct', 'extension_id'),
		);
	}
};	


?>