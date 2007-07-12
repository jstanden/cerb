<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
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
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
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