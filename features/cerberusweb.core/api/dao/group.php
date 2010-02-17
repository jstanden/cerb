<?php
class DAO_Group {
    const CACHE_ALL = 'cerberus_cache_teams_all';
	const CACHE_ROSTERS = 'ch_group_rosters';
    
    const TEAM_ID = 'id';
    const TEAM_NAME = 'name';
    const TEAM_SIGNATURE = 'signature';
    const IS_DEFAULT = 'is_default';
    
	// Teams
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Group
	 */
	static function getTeam($id) {
		$teams = DAO_Group::getTeams(array($id));
		
		if(isset($teams[$id]))
			return $teams[$id];
			
		return null;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return Model_Group[]
	 */
	static function getTeams($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();

		$teams = array();
		
		$sql = sprintf("SELECT t.id , t.name, t.signature, t.is_default ".
			"FROM team t ".
			((is_array($ids) && !empty($ids)) ? sprintf("WHERE t.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY t.name ASC"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		while($row = mysql_fetch_assoc($rs)) {
			$team = new Model_Group();
			$team->id = intval($row['id']);
			$team->name = $row['name'];
			$team->signature = $row['signature'];
			$team->is_default = intval($row['is_default']);
			$teams[$team->id] = $team;
		}
		
		mysql_free_result($rs);
		
		return $teams;
	}
	
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($teams = $cache->load(self::CACHE_ALL))) {
    	    $teams = self::getTeams();
    	    $cache->save($teams, self::CACHE_ALL);
	    }
	    
	    return $teams;
	}
	
	/**
	 * 
	 * @return Model_Team|null
	 */
	static function getDefaultGroup() {
		$groups = self::getAll();
		
		if(is_array($groups))
		foreach($groups as $group) { /* @var $group Model_Group */
			if($group->is_default)
				return $group;
		}
		
		return null;
	}
	
	static function setDefaultGroup($group_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute("UPDATE team SET is_default = 0");
		$db->Execute(sprintf("UPDATE team SET is_default = 1 WHERE id = %d", $group_id));
		
		self::clearCache();
	}
	
	/**
	 * Returns an array of team ticket and task counts, indexed by team id.
	 *
	 * @param array $ids Team IDs to summarize
	 * @return array
	 */
	static function getTeamCounts($ids=array(),$with_tickets=true) { // ,$with_tasks=true,$with_unassigned=false
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();

		$team_totals = array('0' => array('tickets'=>0));

		if($with_tickets) {
			$sql = "SELECT count(*) as hits, t.team_id ".
			    "FROM ticket t ".
			    "WHERE t.category_id = 0 ".
			    "AND t.is_closed = 0 ".
			    (!empty($ids) ? sprintf("AND t.team_id IN (%s) ", implode(',', $ids)) : " ").
			    "GROUP BY t.team_id "
			;
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
			
			while($row = mysql_fetch_assoc($rs)) {
			    $team_id = intval($row['team_id']);
			    $hits = intval($row['hits']);
			    
			    if(!isset($team_totals[$team_id])) {
	                $team_totals[$team_id] = array('tickets'=>0);
			    }
			    
			    $team_totals[$team_id]['tickets'] = $hits;
			    $team_totals[0]['tickets'] += $hits;
			}
			
			mysql_free_result($rs);
		}
		
		return $team_totals;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $name
	 * @return integer
	 */
	static function createTeam($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$newId = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO team (id, name, signature, is_default) ".
			"VALUES (%d,'','',0)",
			$newId
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		self::updateTeam($newId, $fields);

		self::clearCache();
		
		return $newId;
	}

	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @param array $fields
	 */
	static function updateTeam($id, $fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE team SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		self::clearCache();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 */
	static function deleteTeam($id) {
		if(empty($id)) return;
		$db = DevblocksPlatform::getDatabaseService();
		
		/*
		 * Notify anything that wants to know when groups delete.
		 */
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'group.delete',
                array(
                    'group_ids' => array($id),
                )
            )
	    );
		
		$sql = sprintf("DELETE QUICK FROM team WHERE id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		$sql = sprintf("DELETE QUICK FROM category WHERE team_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		// [TODO] DAO_GroupSettings::deleteById();
		$sql = sprintf("DELETE QUICK FROM group_setting WHERE group_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$sql = sprintf("DELETE QUICK FROM worker_to_team WHERE team_id = %d",	$id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		$sql = sprintf("DELETE QUICK FROM group_inbox_filter WHERE group_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

//        DAO_GroupInboxFilter::deleteByMoveCodes(array('t'.$id));

		self::clearCache();
		DAO_Bucket::clearCache();
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK category FROM category LEFT JOIN team ON category.team_id=team.id WHERE team.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' category records.');
		
		$sql = "DELETE QUICK group_setting FROM group_setting LEFT JOIN team ON group_setting.group_id=team.id WHERE team.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' group_setting records.');
		
		$sql = "DELETE QUICK custom_field FROM custom_field LEFT JOIN team ON custom_field.group_id=team.id WHERE custom_field.group_id > 0 AND team.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' custom_field records.');
	}
	
	static function setTeamMember($team_id, $worker_id, $is_manager=false) {
        if(empty($worker_id) || empty($team_id))
            return FALSE;
		
        $db = DevblocksPlatform::getDatabaseService();
        
        $db->Execute(sprintf("REPLACE INTO worker_to_team (agent_id, team_id, is_manager) ".
        	"VALUES (%d, %d, %d)",
        	$worker_id,
        	$team_id,
        	($is_manager?1:0)
       	));
        
        self::clearCache();
	}
	
	static function unsetTeamMember($team_id, $worker_id) {
        if(empty($worker_id) || empty($team_id))
            return FALSE;
            
        $db = DevblocksPlatform::getDatabaseService();
        
		$sql = sprintf("DELETE QUICK FROM worker_to_team WHERE team_id = %d AND agent_id IN (%d)",
		    $team_id,
		    $worker_id
		);
		$db->Execute($sql);

		self::clearCache();
	}
	
	static function getRosters() {
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_ROSTERS))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = sprintf("SELECT wt.agent_id, wt.team_id, wt.is_manager ".
				"FROM worker_to_team wt ".
				"INNER JOIN team t ON (wt.team_id=t.id) ".
				"INNER JOIN worker w ON (w.id=wt.agent_id) ".
				"ORDER BY t.name ASC, w.first_name ASC "
			);
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
			
			$objects = array();
			
			while($row = mysql_fetch_assoc($rs)) {
				$agent_id = intval($row['agent_id']); 
				$team_id = intval($row['team_id']); 
				$is_manager = intval($row['is_manager']);
				
				if(!isset($objects[$team_id]))
					$objects[$team_id] = array();
				
				$member = new Model_TeamMember();
				$member->id = $agent_id;
				$member->team_id = $team_id;
				$member->is_manager = $is_manager;
				$objects[$team_id][$agent_id] = $member;
			}
			
			mysql_free_result($rs);
			
			$cache->save($objects, self::CACHE_ROSTERS);
		}
		
		return $objects;
	}
	
	static function getTeamMembers($team_id) {
		$rosters = self::getRosters();
		
		if(isset($rosters[$team_id]))
			return $rosters[$team_id];
		
		return null;
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
		$cache->remove(self::CACHE_ROSTERS);
		$cache->remove(CerberusApplication::CACHE_HELPDESK_FROMS);
	}
};

class Model_Group {
	public $id;
	public $name;
	public $count;
	public $is_default = 0;
};