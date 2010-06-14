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
class DAO_Bucket extends DevblocksORMHelper {
	const CACHE_ALL = 'cerberus_cache_buckets_all';
	
    const ID = 'id';
    const POS = 'pos';
    const NAME = 'name';
    const TEAM_ID = 'team_id';
    const IS_ASSIGNABLE = 'is_assignable';
    
	static function getTeams() {
		$categories = self::getAll();
		$team_categories = array();
		
		foreach($categories as $cat) {
			$team_categories[$cat->team_id][$cat->id] = $cat;
		}
		
		return $team_categories;
	}
	
	// [JAS]: This belongs in API, not DAO
	static function getCategoryNameHash() {
	    $category_name_hash = array();
	    $teams = DAO_Group::getAll();
	    $team_categories = self::getTeams();
	
	    foreach($teams as $team_id => $team) {
	        $category_name_hash['t'.$team_id] = $team->name;
	        
	        if(@is_array($team_categories[$team_id]))
	        foreach($team_categories[$team_id] as $category) {
	            $category_name_hash['c'.$category->id] = $team->name . ':' .$category->name;
	        }
	    }
	    
	    return $category_name_hash;
	}
	
	/**
	 * 
	 * @param bool $nocache
	 * @return Model_Bucket[]
	 */
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($buckets = $cache->load(self::CACHE_ALL))) {
    	    $buckets = self::getList();
    	    $cache->save($buckets, self::CACHE_ALL);
	    }
	    
	    return $buckets;
	}
	
	static function get($id) {
		$buckets = self::getAll();
	
		if(isset($buckets[$id]))
			return $buckets[$id];
			
		return null;
	}
	
	static function getNextPos($group_id) {
		if(empty($group_id))
			return 0;
		
		$db = DevblocksPlatform::getDatabaseService();
		if(null != ($next_pos = $db->GetOne(sprintf("SELECT MAX(pos)+1 FROM category WHERE team_id = %d", $group_id))))
			return $next_pos;
			
		return 0;
	}
	
	static function getList($ids=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT tc.id, tc.pos, tc.name, tc.team_id, tc.is_assignable ".
			"FROM category tc ".
			"INNER JOIN team t ON (tc.team_id=t.id) ".
			(!empty($ids) ? sprintf("WHERE tc.id IN (%s) ", implode(',', $ids)) : "").
			"ORDER BY t.name ASC, tc.pos ASC "
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$categories = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$category = new Model_Bucket();
			$category->id = intval($row['id']);
			$category->pos = intval($row['pos']);
			$category->name = $row['name'];
			$category->team_id = intval($row['team_id']);
			$category->is_assignable = intval($row['is_assignable']);
			$categories[$category->id] = $category;
		}
		
		mysql_free_result($rs);
		
		return $categories;
	}
	
	static function getByTeam($team_ids) {
		if(!is_array($team_ids)) $team_ids = array($team_ids);
		$team_buckets = array();
		
		$buckets = self::getAll();
		foreach($buckets as $bucket) {
			if(false !== array_search($bucket->team_id, $team_ids)) {
				$team_buckets[$bucket->id] = $bucket;
			}
		}
		return $team_buckets;
	}
	
	static function getAssignableBuckets($group_ids=null) {
		if(!is_array($group_ids)) $group_ids = array($group_ids);
		
		if(empty($group_ids)) {
			$buckets = self::getAll();
		} else {
			$buckets = self::getByTeam($group_ids);
		}
		
		// Remove buckets that aren't assignable
		if(is_array($buckets))
		foreach($buckets as $id => $bucket) {
			if(!$bucket->is_assignable)
				unset($buckets[$id]);
		}
		
		return $buckets;
	}
	
	static function create($name, $team_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Check for dupes
		$buckets = self::getAll();
		if(is_array($buckets))
		foreach($buckets as $bucket) {
			if(0==strcasecmp($name,$bucket->name) && $team_id==$bucket->team_id) {
				return $bucket->id;
			}
		}

		$id = $db->GenID('generic_seq');
		$next_pos = self::getNextPos($team_id);
		
		$sql = sprintf("INSERT INTO category (id,pos,name,team_id,is_assignable) ".
			"VALUES (%d,%d,%s,%d,1)",
			$id,
			$next_pos,
			$db->qstr($name),
			$team_id
		);

		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		self::clearCache();
		
		return $id;
	}
	
	static function update($id,$fields) {
		parent::_update($id,'category',$fields);

		self::clearCache();
	}
	
	static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		/*
		 * Notify anything that wants to know when buckets delete.
		 */
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'bucket.delete',
                array(
                    'bucket_ids' => $ids,
                )
            )
	    );
		
		$sql = sprintf("DELETE QUICK FROM category WHERE id IN (%s)", implode(',',$ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		// Reset any tickets using this category
		$sql = sprintf("UPDATE ticket SET category_id = 0 WHERE category_id IN (%s)", implode(',',$ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		self::clearCache();
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
};

class Model_Bucket {
	public $id;
	public $pos=0;
	public $name = '';
	public $team_id = 0;
	public $is_assignable = 1;
};