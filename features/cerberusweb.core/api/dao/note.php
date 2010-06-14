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
class DAO_Note extends DevblocksORMHelper {
	const ID = 'id';
	const SOURCE_EXTENSION_ID = 'source_extension_id';
	const SOURCE_ID = 'source_id';
	const CREATED = 'created';
	const WORKER_ID = 'worker_id';
	const CONTENT = 'content';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('note_seq');
		
		$sql = sprintf("INSERT INTO note (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'note', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_Note[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, source_extension_id, source_id, created, worker_id, content ".
			"FROM note ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
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
			$object = new Model_Note();
			$object->id = $row['id'];
			$object->source_extension_id = $row['source_extension_id'];
			$object->source_id = $row['source_id'];
			$object->created = $row['created'];
			$object->worker_id = $row['worker_id'];
			$object->content = $row['content'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
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
		$fields = SearchFields_Note::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"n.id as %s, ".
			"n.source_extension_id as %s, ".
			"n.source_id as %s, ".
			"n.created as %s, ".
			"n.worker_id as %s, ".
			"n.content as %s ",
			    SearchFields_Note::ID,
			    SearchFields_Note::SOURCE_EXT_ID,
			    SearchFields_Note::SOURCE_ID,
			    SearchFields_Note::CREATED,
			    SearchFields_Note::WORKER_ID,
			    SearchFields_Note::CONTENT
			 );
		
		$join_sql = 
			"FROM note n ";
//			"LEFT JOIN contact_org o ON (o.id=a.contact_org_id) "

			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=a.contact_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sql = $select_sql . $join_sql . $where_sql .  
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "");
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_Note::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = "SELECT count(*) " . $join_sql . $where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }	
	
    static function deleteBySourceIds($source_extension, $source_ids) {
		if(!is_array($source_ids)) $source_ids = array($source_ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $source_ids);
		
		$db->Execute(sprintf("DELETE FROM note WHERE source_extension_id = %s AND source_id IN (%s)", $db->qstr($source_extension), $ids_list));
    }
    
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM note WHERE id IN (%s)", $ids_list));
		
		return true;
	}

};

class SearchFields_Note implements IDevblocksSearchFields {
	// Note
	const ID = 'n_id';
	const SOURCE_EXT_ID = 'n_source_ext_id';
	const SOURCE_ID = 'n_source_id';
	const CREATED = 'n_created';
	const WORKER_ID = 'n_worker_id';
	const CONTENT = 'n_content';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'n', 'id'),
			self::SOURCE_EXT_ID => new DevblocksSearchField(self::SOURCE_EXT_ID, 'n', 'source_extension_id'),
			self::SOURCE_ID => new DevblocksSearchField(self::SOURCE_ID, 'n', 'source_id'),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'n', 'created'),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'n', 'worker_id'),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'n', 'content'),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_Note {
	const EXTENSION_ID = 'cerberusweb.note';
	
	public $id;
	public $source_extension_id;
	public $source_id;
	public $created;
	public $worker_id;
	public $content;
};
