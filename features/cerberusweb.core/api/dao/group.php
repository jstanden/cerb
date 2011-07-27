<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class DAO_Group extends C4_ORMHelper {
    const CACHE_ALL = 'cerberus_cache_teams_all';
	const CACHE_ROSTERS = 'ch_group_rosters';
    
    const TEAM_ID = 'id';
    const TEAM_NAME = 'name';
    const REPLY_ADDRESS_ID = 'reply_address_id';
    const REPLY_PERSONAL = 'reply_personal';
    const REPLY_SIGNATURE = 'reply_signature';
    const IS_DEFAULT = 'is_default';
    
	// Teams
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Group
	 */
	static function get($id) {
		$teams = DAO_Group::getAll();
		
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
		
		$sql = sprintf("SELECT id , name, is_default, reply_address_id, reply_personal, reply_signature ".
			"FROM team ".
			((is_array($ids) && !empty($ids)) ? sprintf("WHERE id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY name ASC"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		while($row = mysql_fetch_assoc($rs)) {
			$team = new Model_Group();
			$team->id = intval($row['id']);
			$team->name = $row['name'];
			$team->is_default = intval($row['is_default']);
			$team->reply_address_id = $row['reply_address_id'];
			$team->reply_personal = $row['reply_personal'];
			$team->reply_signature = $row['reply_signature'];
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
		
		$sql = "INSERT INTO team () VALUES ()";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId(); 
		
		self::updateTeam($id, $fields);

		self::clearCache();
		
		return $id;
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
		
		$sql = sprintf("DELETE QUICK FROM group_setting WHERE group_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$sql = sprintf("DELETE QUICK FROM worker_to_team WHERE team_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => CerberusContexts::CONTEXT_GROUP,
                	'context_ids' => array($id)
                )
            )
	    );
		
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
		
		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.maint',
                array(
                	'context' => CerberusContexts::CONTEXT_GROUP,
                	'context_table' => 'team',
                	'context_key' => 'id',
                )
            )
	    );
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
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Group::getFields();

		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"g.id as %s, ".
			"g.name as %s ",
			    SearchFields_Group::ID,
			    SearchFields_Group::NAME
			);
			
		$join_sql = "FROM team g ".

		// Dynamic joins
		(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.group' AND context_link.to_context_id = g.id) " : " ")
		;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'g.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";

		$result = array(
			'primary_table' => 'g',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
		
		return $result;
	}	
	
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY g.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_Group::ID]);
			$results[$object_id] = $result;
		}
		
		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT g.id) " : "SELECT COUNT(g.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }	
};

class SearchFields_Group implements IDevblocksSearchFields {
	// Worker
	const ID = 'g_id';
	const NAME = 'g_name';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'g', 'id', $translate->_('common.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'g', 'name', $translate->_('common.name')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(Context_Group::ID);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_Group {
	public $id;
	public $name;
	public $count;
	public $is_default = 0;
	public $reply_address_id;
	public $reply_personal;
	public $reply_signature;
	
	public function getMembers() {
		return DAO_Group::getTeamMembers($this->id);
	}
	
	/**
	 * 
	 * @param integer $bucket_id
	 * @return Model_AddressOutgoing
	 */
	public function getReplyTo($bucket_id=0) {
		$from_id = 0;
		$froms = DAO_AddressOutgoing::getAll();
		
		// Cascade to bucket
		if(!empty($bucket_id)
			&& null != ($bucket = DAO_Bucket::get($bucket_id))) {
			
			$from_id = $bucket->reply_address_id;
		}

		// Cascade to group
		if(empty($from_id))
			$from_id = $this->reply_address_id;
		
		// Cascade to global
		if(empty($from_id) || !isset($froms[$from_id])) {
			$from = DAO_AddressOutgoing::getDefault();
			$from_id = $from->address_id;
		}
			
		// Last check
		if(!isset($froms[$from_id]))
			return null;
		
		return $froms[$from_id];
	}
	
	public function getReplyFrom($bucket_id=0) {
		$from_id = 0;
		$froms = DAO_AddressOutgoing::getAll();
		
		// Cascade to bucket
		if(!empty($bucket_id)
			&& null != ($bucket = DAO_Bucket::get($bucket_id))) {
			
			$from_id = $bucket->reply_address_id;
		}

		// Cascade to group
		if(empty($from_id))
			$from_id = $this->reply_address_id;
		
		// Cascade to global
		if(empty($from_id) || !isset($froms[$from_id])) {
			$from = DAO_AddressOutgoing::getDefault();
			$from_id = $from->address_id;
		}
			
		return $from_id;
	}
	
	public function getReplyPersonal($bucket_id=0, $worker_model=null) {
		$froms = DAO_AddressOutgoing::getAll();
		$personal = null;
		
		// Cascade to bucket
		if(!empty($bucket_id)
			&& null != ($bucket = DAO_Bucket::get($bucket_id))) {
			
			$personal = $bucket->reply_personal;
			
			// Cascade to bucket address
			if(empty($personal) && !empty($bucket->reply_address_id) && isset($froms[$bucket->reply_address_id])) {
				$from = $froms[$bucket->reply_address_id];
				$personal = $from->reply_personal;
			}
		}

		// Cascade to group
		if(empty($personal))
			$personal = $this->reply_personal;
			
		// Cascade to group address
		if(empty($personal) && !empty($this->reply_address_id) && isset($froms[$this->reply_address_id])) {
			$from = $froms[$this->reply_address_id];
			$personal = $from->reply_personal;
		}
		
		// Cascade to global
		if(empty($personal)) {
			$from = DAO_AddressOutgoing::getDefault();
			$personal = $from->reply_personal;
		}
		
		// If we have a worker model, convert template tokens
		if(empty($worker_model))
			$worker_model = new Model_Worker();
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$token_labels = array();
		$token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_model, $token_labels, $token_values);
		$personal = $tpl_builder->build($personal, $token_values);
		
		return $personal;
	}
	
	public function getReplySignature($bucket_id=0, $worker_model=null) {
		$froms = DAO_AddressOutgoing::getAll();
		$signature = null;
		
		// Cascade to bucket
		if(!empty($bucket_id)
			&& null != ($bucket = DAO_Bucket::get($bucket_id))) {
			
			$signature = $bucket->reply_signature;
			
			// Cascade to bucket address
			if(empty($signature) && !empty($bucket->reply_address_id) && isset($froms[$bucket->reply_address_id])) {
				$from = $froms[$bucket->reply_address_id];
				$signature = $from->reply_signature;
			}
		}

		// Cascade to group
		if(empty($signature))
			$signature = $this->reply_signature;
			
		// Cascade to group address
		if(empty($signature) && !empty($this->reply_address_id) && isset($froms[$this->reply_address_id])) {
			$from = $froms[$this->reply_address_id];
			$signature = $from->reply_signature;
		}
		
		// Cascade to global
		if(empty($signature)) {
			$from = DAO_AddressOutgoing::getDefault();
			$signature = $from->reply_signature;
		}
		
		// If we have a worker model, convert template tokens
		if(!empty($worker_model)) {
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			$token_labels = array();
			$token_values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_model, $token_labels, $token_values);
			$signature = $tpl_builder->build($signature, $token_values);
		}
		
		return $signature;
	}
};

class DAO_GroupSettings {
	const CACHE_ALL = 'ch_group_settings';
	
    const SETTING_SUBJECT_HAS_MASK = 'subject_has_mask';
    const SETTING_SUBJECT_PREFIX = 'subject_prefix';
    
	static function set($group_id, $key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("REPLACE INTO group_setting (group_id, setting, value) ".
			"VALUES (%d, %s, %s)",
			$group_id,
			$db->qstr($key),
			$db->qstr($value)
		));
		
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	static function get($group_id, $key, $default=null) {
		$value = null;
		
		if(null !== ($group = self::getSettings($group_id)) && isset($group[$key])) {
			$value = $group[$key];
		}
		
		if(null == $value && !is_null($default)) {
		    return $default;
		}
		
		return $value;
	}
	
	static function getSettings($group_id=0) {
	    $cache = DevblocksPlatform::getCacheService();
	    if(null === ($groups = $cache->load(self::CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
	
			$groups = array();
			
			$sql = "SELECT group_id, setting, value FROM group_setting";
			$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); 
			
			while($row = mysql_fetch_assoc($rs)) {
			    $gid = intval($row['group_id']);
			    
			    if(!isset($groups[$gid]))
			        $groups[$gid] = array();
			    
			    $groups[$gid][$row['setting']] = $row['value'];
			}
			
			mysql_free_result($rs);
			
			$cache->save($groups, self::CACHE_ALL);
	    }

	    // Empty
	    if(empty($groups))
	    	return null;
	    
	    // Specific group
	    if(!empty($group_id)) {
		    // Requested group id exists
	    	if(isset($groups[$group_id]))
	    		return $groups[$group_id];
	    	else // doesn't
	    		return null;
	    }
	    
	    // All groups
		return $groups;
	}
};

class View_Group extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'groups';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Groups';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Group::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Group::NAME,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Group::ID,
		));
		$this->addParamsHidden(array(
			SearchFields_Group::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		return DAO_Group::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Booleans
//				case SearchFields_Group::IS_COMPLETED:
//					$pass = true;
//					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
//			case SearchFields_Group::EXAMPLE:
//				$counts = $this->_getSubtotalCountForStringColumn('DAO_Group', $column);
//				break;

			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Group', $column, 'g.id');
				}
				
				break;
		}
		
		return $counts;
	}	
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$custom_fields = DAO_CustomField::getByContext(Context_Group::ID);
		$tpl->assign('custom_fields', $custom_fields);

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
				$tpl->display('devblocks:cerberusweb.core::groups/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::groups/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Group::NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
//			case SearchFields_Notification::WORKER_ID:
//				$workers = DAO_Worker::getAll();
//				$strings = array();
//
//				foreach($values as $val) {
//					if(empty($val))
//					$strings[] = "Nobody";
//					elseif(!isset($workers[$val]))
//					continue;
//					else
//					$strings[] = $workers[$val]->getName();
//				}
//				echo implode(", ", $strings);
//				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Group::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Group::NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case 'placeholder_date':
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
		$custom_fields = array();

		// [TODO] Implement
		return;
		
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
//				case 'is_disabled':
//					$change_fields[DAO_Worker::IS_DISABLED] = intval($v);
//					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;

			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Group::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Group::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Worker::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_GROUP, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Group extends Extension_DevblocksContext {
	const ID = 'cerberusweb.contexts.group';
	
	function getMeta($context_id) {
		$group = DAO_Group::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$who = sprintf("%d-%s",
			$group->id,
			DevblocksPlatform::strToPermalink($group->name)
		);
		
		return array(
			'id' => $group->id,
			'name' => $group->name,
			'permalink' => $url_writer->writeNoProxy('c=profiles&type=group&who='.$who, true),
		);
	}
	
	function getContext($group, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Group:';
			
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_GROUP);
		
		// Polymorph
		if(is_numeric($group)) {
			$group = DAO_Group::get($group);
		} elseif($group instanceof Model_Group) {
			// It's what we want already.
		} else {
			$group = null;
		}
		
		// Token labels
		$token_labels = array(
			'name' => $prefix.$translate->_('common.name'),
		);
		
		// Custom fields
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['worker_custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Group token values
		if(null != $group) {
			$token_values['id'] = $group->id;
			$token_values['name'] = $group->name;
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_GROUP, $group->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $group)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $group)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
//		// Worker email
//		@$worker_email = !is_null($worker) ? $worker->email : null;
//		$merge_token_labels = array();
//		$merge_token_values = array();
//		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $worker_email, $merge_token_labels, $merge_token_values, null, true);

//		CerberusContexts::merge(
//			'address_',
//			'',
//			$merge_token_labels,
//			$merge_token_values,
//			$token_labels,
//			$token_values
//		);		
		
		return true;		
	}
	
	function getChooserView() {
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Groups';
		$view->view_columns = array(
			SearchFields_Group::NAME,
//			SearchFields_Worker::LAST_NAME,
//			SearchFields_Worker::TITLE,
		);
		$view->addParams(array(
//			SearchFields_Worker::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Worker::IS_DISABLED,'=',0),
		), true);
//		$view->renderSortBy = SearchFields_Group::NAME;
//		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		C4_AbstractViewLoader::setView($view_id, $view);

		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Groups';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Group::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Group::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};

class Model_TeamMember {
	public $id;
	public $team_id;
	public $is_manager = 0;
};
