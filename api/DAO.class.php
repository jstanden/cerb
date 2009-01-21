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

/**
 * Global Settings DAO
 */
class DAO_Setting extends DevblocksORMHelper {
	static function set($key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Replace('setting',array('setting'=>$db->qstr($key),'value'=>$db->qstr($value)),array('setting'),false);
	}
	
	static function get($key) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT value FROM setting WHERE setting = %s",
			$db->qstr($key)
		);
		$value = $db->GetOne($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $value;
	}
	
	static function getSettings() {
	    $cache = DevblocksPlatform::getCacheService();
	    if(null === ($settings = $cache->load(CerberusApplication::CACHE_SETTINGS_DAO))) {
			$db = DevblocksPlatform::getDatabaseService();
			$settings = array();
			
			$sql = sprintf("SELECT setting,value FROM setting");
			$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			if(is_a($rs,'ADORecordSet'))
			while(!$rs->EOF) {
				$settings[$rs->Fields('setting')] = $rs->Fields('value');
				$rs->MoveNext();
			}
			
			$cache->save($settings, CerberusApplication::CACHE_SETTINGS_DAO);
	    }
		
		return $settings;
	}
};

/**
 * Bayesian Anti-Spam DAO
 */
class DAO_Bayes {
	private function DAO_Bayes() {}
	
	/**
	 * @return CerberusWord[]
	 */
	static function lookupWordIds($words) {
		$db = DevblocksPlatform::getDatabaseService();
		$tmp = array();
		$outwords = array(); // CerberusWord
		
		// Escaped set
		if(is_array($words))
		foreach($words as $word) {
			$tmp[] = $db->escape($word);
		}
		
		if(empty($words))
		    return array();
		
		$sql = sprintf("SELECT id,word,spam,nonspam FROM bayes_words WHERE word IN ('%s')",
			implode("','", $tmp)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// [JAS]: Keep a list of words we can check off as we index them with IDs
		$tmp = array_flip($words); // words are now keys
		
		// Existing Words
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$w = new CerberusBayesWord();
			$w->id = intval($rs->fields['id']);
			$w->word = $rs->fields['word'];
			$w->spam = intval($rs->fields['spam']);
			$w->nonspam = intval($rs->fields['nonspam']);
			
			$outwords[$w->word] = $w;
			unset($tmp[$w->word]); // check off we've indexed this word
			$rs->MoveNext();
		}
		
		// Insert new words
		if(is_array($tmp))
		foreach($tmp as $new_word => $v) {
			$new_id = $db->GenID('bayes_words_seq');
			$sql = sprintf("INSERT INTO bayes_words (id,word) VALUES (%d,%s)",
				$new_id,
				$db->qstr($new_word)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			$w = new CerberusBayesWord();
			$w->id = $new_id;
			$w->word = $new_word;
			$outwords[$w->word] = $w;
		}
		
		return $outwords;
	}
	
	/**
	 * @return array Two element array (keys: spam,nonspam)
	 */
	static function getStatistics() {
		$db = DevblocksPlatform::getDatabaseService();
		
		// [JAS]: [TODO] Change this into a 'replace' index?
		$sql = "SELECT spam, nonspam FROM bayes_stats";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if($rs->NumRows()) {
			$spam = intval($rs->Fields('spam'));
			$nonspam = intval($rs->Fields('nonspam'));
		} else {
			$spam = 0;
			$nonspam = 0;
			$sql = "INSERT INTO bayes_stats (spam, nonspam) VALUES (0,0)";
			$db->Execute($sql);
		}
		
		return array('spam' => $spam,'nonspam' => $nonspam);
	}
	
	static function addOneToSpamTotal() {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = "UPDATE bayes_stats SET spam = spam + 1";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function addOneToNonSpamTotal() {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = "UPDATE bayes_stats SET nonspam = nonspam + 1";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function addOneToSpamWord($word_ids=array()) {
	    if(!is_array($word_ids)) $word_ids = array($word_ids);
	    if(empty($word_ids)) return;
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE bayes_words SET spam = spam + 1 WHERE id IN(%s)", implode(',',$word_ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function addOneToNonSpamWord($word_ids=array()) {
	    if(!is_array($word_ids)) $word_ids = array($word_ids);
	    if(empty($word_ids)) return;
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE bayes_words SET nonspam = nonspam + 1 WHERE id IN(%s)", implode(',',$word_ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
};

/**
 * Worker DAO
 */
class DAO_Worker extends DevblocksORMHelper {
	private function DAO_Worker() {}
	
	const CACHE_ALL = 'ch_workers';
	
	const ID = 'id';
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const TITLE = 'title';
	const EMAIL = 'email';
	const PASSWORD = 'pass';
	const IS_SUPERUSER = 'is_superuser';
	const IS_DISABLED = 'is_disabled';
	const CAN_DELETE = 'can_delete';
	const LAST_ACTIVITY_DATE = 'last_activity_date';
	const LAST_ACTIVITY = 'last_activity';
	
	// [TODO] Convert to ::create($id, $fields)
	static function create($email, $password, $first_name, $last_name, $title) {
		if(empty($email) || empty($password))
			return null;
			
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO worker (id, email, pass, first_name, last_name, title, is_superuser, is_disabled, can_delete) ".
			"VALUES (%d, %s, %s, %s, %s, %s,0,0,0)",
			$id,
			$db->qstr($email),
			$db->qstr(md5($password)),
			$db->qstr($first_name),
			$db->qstr($last_name),
			$db->qstr($title)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::clearCache();
		
		return $id;
	}

	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	static function getAllActive() {
		return self::getAll(false, false);
	}
	
	static function getAllWithDisabled() {
		return self::getAll(false, true);
	}
	
	static function getAll($nocache=false, $with_disabled=true) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($workers = $cache->load(self::CACHE_ALL))) {
    	    $workers = self::getList();
    	    $cache->save($workers, self::CACHE_ALL);
	    }
	    
	    /*
	     * If the caller doesn't want disabled workers then remove them from the results,
	     * but don't bother caching two different versions (always cache all)
	     */
	    if(!$with_disabled) {
	    	foreach($workers as $worker_id => $worker) { /* @var $worker CerberusWorker */
	    		if($worker->is_disabled)
	    			unset($workers[$worker_id]);
	    	}
	    }
	    
	    return $workers;
	}
	
	static function getList($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		$workers = array();
		
		$sql = "SELECT a.id, a.first_name, a.last_name, a.email, a.pass, a.title, a.is_superuser, a.is_disabled, a.can_delete, a.last_activity_date, a.last_activity ".
			"FROM worker a ".
			((!empty($ids) ? sprintf("WHERE a.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY a.last_name, a.first_name "
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$worker = new CerberusWorker();
			$worker->id = intval($rs->fields['id']);
			$worker->first_name = $rs->fields['first_name'];
			$worker->last_name = $rs->fields['last_name'];
			$worker->email = $rs->fields['email'];
			$worker->pass = $rs->fields['pass'];
			$worker->title = $rs->fields['title'];
			$worker->is_superuser = intval($rs->fields['is_superuser']);
			$worker->is_disabled = intval($rs->fields['is_disabled']);
			$worker->can_delete = intval($rs->fields['can_delete']);
			$worker->last_activity_date = intval($rs->fields['last_activity_date']);
			
			if(!empty($rs->fields['last_activity']))
			    $worker->last_activity = unserialize($rs->fields['last_activity']);
			
			$workers[$worker->id] = $worker;
			$rs->MoveNext();
		}
		
		return $workers;		
	}
	
	/**
	 * @return CerberusWorker
	 */
	static function getAgent($id) {
		if(empty($id)) return null;
		
		$workers = self::getAllWithDisabled();
		
		if(isset($workers[$id]))
			return $workers[$id];
			
		return null;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $email
	 * @return integer $id
	 */
	static function lookupAgentEmail($email) {
		if(empty($email)) return null;
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT a.id FROM worker a WHERE a.email = %s",
			$db->qstr($email)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet') && !$rs->EOF) {
			return intval($rs->fields['id']);
		}
		
		return null;		
	}
	
	static function updateAgent($id, $fields, $flush_cache=true) {
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
			
		$sql = sprintf("UPDATE worker SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if($flush_cache) {
			self::clearCache();
		}
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK dashboard FROM dashboard LEFT JOIN worker ON dashboard.agent_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' dashboard records.');
		
		$sql = "DELETE QUICK view_rss FROM view_rss LEFT JOIN worker ON view_rss.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);

		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' view_rss records.');
		
		$sql = "DELETE QUICK worker_mail_forward FROM worker_mail_forward LEFT JOIN worker ON worker_mail_forward.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_mail_forward records.');
		
		$sql = "DELETE QUICK worker_pref FROM worker_pref LEFT JOIN worker ON worker_pref.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_pref records.');
		
		$sql = "DELETE QUICK worker_to_team FROM worker_to_team LEFT JOIN worker ON worker_to_team.agent_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_to_team records.');
		
		$sql = "DELETE QUICK worker_workspace_list FROM worker_workspace_list LEFT JOIN worker ON worker_workspace_list.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_workspace_list records.');
		
		// Routing rules assigned to missing workers
		$sql = "DELETE QUICK team_routing_rule FROM team_routing_rule LEFT JOIN worker ON team_routing_rule.do_assign = worker.id WHERE (team_routing_rule.do_assign IS NOT NULL AND team_routing_rule.do_assign > 0) AND worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' team_routing_rule records assigned to missing workers.');
	}
	
	static function deleteAgent($id) {
		if(empty($id)) return;
		
		// [TODO] Delete worker notes, comments, etc.
		
		/* This event fires before the delete takes place in the db,
		 * so we can denote what is actually changing against the db state
		 */
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'worker.delete',
                array(
                    'worker_id' => $id,
                )
            )
	    );
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM worker WHERE id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE QUICK FROM address_to_worker WHERE worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE QUICK FROM worker_to_team WHERE agent_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$sql = sprintf("DELETE QUICK FROM view_rss WHERE worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$sql = sprintf("DELETE QUICK FROM worker_workspace_list WHERE worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$sql = sprintf("DELETE QUICK FROM team_routing_rule WHERE do_assign = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// Clear assigned workers
		$sql = sprintf("UPDATE ticket SET next_worker_id = 0 WHERE next_worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		// Invalidate caches
		self::clearCache();
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(DAO_Group::CACHE_ROSTERS);
	}
	
	static function login($email, $password) {
		$db = DevblocksPlatform::getDatabaseService();

		// [TODO] Uniquely salt hashes
		$sql = sprintf("SELECT id ".
			"FROM worker ".
			"WHERE is_disabled = 0 ".
			"AND email = %s ".
			"AND pass = MD5(%s)",
				$db->qstr($email),
				$db->qstr($password)
		);
		$worker_id = $db->GetOne($sql); // or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(!empty($worker_id)) {
			return self::getAgent($worker_id);
		}
		
		return null;
	}
	
	static function setAgentTeams($agent_id, $team_ids) {
		if(!is_array($team_ids)) $team_ids = array($team_ids);
		if(empty($agent_id)) return;
		$db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("DELETE QUICK FROM worker_to_team WHERE agent_id = %d",
			$agent_id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		foreach($team_ids as $team_id) {
			$sql = sprintf("INSERT INTO worker_to_team (agent_id, team_id) ".
				"VALUES (%d,%d)",
				$agent_id,
				$team_id
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
		
		// Invalidate caches
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(DAO_Group::CACHE_ROSTERS);
	}
	
	/**
	 * @return Model_TeamMember[]
	 */
	static function getWorkerGroups($worker_id) {
		// Get the cache
		$rosters = DAO_Group::getRosters();

		$memberships = array();
		
		// Remove any groups our desired worker isn't in
		if(is_array($rosters))
		foreach($rosters as $group_id => $members) {
			if(isset($members[$worker_id])) {
				$memberships[$group_id] = $members[$worker_id]; 
			}
		}
		
		return $memberships;
	}
	
	/**
	 * Store the workers last activity (provided by the page extension).
	 * 
	 * @param integer $worker_id
	 * @param Model_Activity $activity
	 */
	static function logActivity($worker_id, Model_Activity $activity) {
	    DAO_Worker::updateAgent($worker_id,array(
	        DAO_Worker::LAST_ACTIVITY_DATE => time(),
	        DAO_Worker::LAST_ACTIVITY => serialize($activity)
	    ),false);
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

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),SearchFields_Worker::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$sql = sprintf("SELECT ".
			"w.id as %s, ".
			"w.last_activity_date as %s ".
			"FROM worker w ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_Worker::ID,
			    SearchFields_Worker::LAST_ACTIVITY_DATE
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = $rs->RecordCount();
		}
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[SearchFields_Worker::ID]);
			$results[$ticket_id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }
    	
}

/**
 * ...
 * 
 */
class SearchFields_Worker implements IDevblocksSearchFields {
	// Worker
	const ID = 'w_id';
	const LAST_ACTIVITY = 'w_last_activity';
	const LAST_ACTIVITY_DATE = 'w_last_activity_date';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		return array(
			SearchFields_Worker::ID => new DevblocksSearchField(SearchFields_Worker::ID, 'w', 'id'),
			SearchFields_Worker::LAST_ACTIVITY => new DevblocksSearchField(SearchFields_Worker::LAST_ACTIVITY, 'w', 'last_activity'),
			SearchFields_Worker::LAST_ACTIVITY_DATE => new DevblocksSearchField(SearchFields_Worker::LAST_ACTIVITY_DATE, 'w', 'last_activity_date'),
		);
	}
};

class DAO_WorkerEvent extends DevblocksORMHelper {
	const ID = 'id';
	const CREATED_DATE = 'created_date';
	const WORKER_ID = 'worker_id';
	const TITLE = 'title';
	const CONTENT = 'content';
	const IS_READ = 'is_read';
	const URL = 'url';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('worker_event_seq');
		
		$sql = sprintf("INSERT INTO worker_event (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'worker_event', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('worker_event', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_WorkerEvent[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, created_date, worker_id, title, content, is_read, url ".
			"FROM worker_event ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WorkerEvent	 */
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
	 * @param ADORecordSet $rs
	 * @return Model_WorkerEvent[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_WorkerEvent();
			$object->id = $rs->fields['id'];
			$object->created_date = $rs->fields['created_date'];
			$object->worker_id = $rs->fields['worker_id'];
			$object->title = $rs->fields['title'];
			$object->url = $rs->fields['url'];
			$object->content = $rs->fields['content'];
			$object->is_read = $rs->fields['is_read'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM worker_event WHERE id IN (%s)", $ids_list));
		
		return true;
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

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),SearchFields_WorkerEvent::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$sql = sprintf("SELECT ".
			"we.id as %s, ".
			"we.created_date as %s, ".
			"we.worker_id as %s, ".
			"we.title as %s, ".
			"we.content as %s, ".
			"we.is_read as %s, ".
			"we.url as %s ".
			"FROM worker_event we ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_WorkerEvent::ID,
			    SearchFields_WorkerEvent::CREATED_DATE,
			    SearchFields_WorkerEvent::WORKER_ID,
			    SearchFields_WorkerEvent::TITLE,
			    SearchFields_WorkerEvent::CONTENT,
			    SearchFields_WorkerEvent::IS_READ,
			    SearchFields_WorkerEvent::URL
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = $rs->RecordCount();
		}
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[SearchFields_WorkerEvent::ID]);
			$results[$ticket_id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }
	
};

class SearchFields_WorkerEvent implements IDevblocksSearchFields {
	// Worker Event
	const ID = 'we_id';
	const CREATED_DATE = 'we_created_date';
	const WORKER_ID = 'we_worker_id';
	const TITLE = 'we_title';
	const CONTENT = 'we_content';
	const IS_READ = 'we_is_read';
	const URL = 'we_url';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'we', 'id', null, $translate->_('worker_event.id')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'we', 'created_date', null, $translate->_('worker_event.created_date')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'we', 'worker_id', null, $translate->_('worker_event.worker_id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'we', 'title', null, $translate->_('worker_event.title')),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'we', 'content', null, $translate->_('worker_event.content')),
			self::IS_READ => new DevblocksSearchField(self::IS_READ, 'we', 'is_read', null, $translate->_('worker_event.is_read')),
			self::URL => new DevblocksSearchField(self::URL, 'we', 'url', null, $translate->_('common.url')),
		);
	}
};

class DAO_ContactOrg extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const STREET = 'street';
	const CITY = 'city';
	const PROVINCE = 'province';
	const POSTAL = 'postal';
	const COUNTRY = 'country';
	const PHONE = 'phone';
	const FAX = 'fax';
	const WEBSITE = 'website';
	const CREATED = 'created';
	const SLA_ID = 'sla_id';
	
	private function __construct() {}
	
	public static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			'id' => $translate->_('contact_org.id'),
			'name' => $translate->_('contact_org.name'),
			'street' => $translate->_('contact_org.street'),
			'city' => $translate->_('contact_org.city'),
			'province' => $translate->_('contact_org.province'),
			'postal' => $translate->_('contact_org.postal'),
			'country' => $translate->_('contact_org.country'),
			'phone' => $translate->_('contact_org.phone'),
			'fax' => $translate->_('contact_org.fax'),
			'website' => $translate->_('contact_org.website'),
			'created' => $translate->_('contact_org.created'),
			'sla_id' => $translate->_('sla.name'),
		);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $fields
	 * @return integer
	 */
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('contact_org_seq');
		
		$sql = sprintf("INSERT INTO contact_org (id,name,street,city,province,postal,country,phone,fax,website,created) ".
  			"VALUES (%d,'','','','','','','','','',%d)",
			$id,
			time()
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($id, $fields);
		return $id;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @param array $fields
	 * @return Model_ContactOrg
	 */
	static function update($ids, $fields) {
		if(!is_array($ids)) $ids = array($ids);
		parent::_update($ids, 'contact_org', $fields);
	}
	
	/**
	 * @param array $ids
	 */
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_list = implode(',', $ids);
		
		$sql = sprintf("DELETE QUICK FROM contact_org WHERE id IN (%s)",
			$id_list
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		// Clear any associated addresses
		$sql = sprintf("UPDATE address SET contact_org_id = 0 WHERE contact_org_id IN (%s)",
			$id_list
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// Tasks
        DAO_Task::deleteBySourceIds('cerberusweb.tasks.org', $ids);
	}
	
	/**
	 * @param string $where
	 * @return Model_ContactOrg[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,name,street,city,province,postal,country,phone,fax,website,created,sla_id,sla_expires ".
			"FROM contact_org ".
			(!empty($where) ? sprintf("WHERE %s ", $where) : " ")
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		return self::_getObjectsFromResultSet($rs);
	}
	
	static private function _getObjectsFromResultSet($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_ContactOrg();
			$object->id = intval($rs->fields['id']);
			$object->name = $rs->fields['name'];
			$object->street = $rs->fields['street'];
			$object->city = $rs->fields['city'];
			$object->province = $rs->fields['province'];
			$object->postal = $rs->fields['postal'];
			$object->country = $rs->fields['country'];
			$object->phone = $rs->fields['phone'];
			$object->fax = $rs->fields['fax'];
			$object->website = $rs->fields['website'];
			$object->created = intval($rs->fields['created']);
			$object->sla_id = intval($rs->fields['sla_id']);
			$object->sla_expires = intval($rs->fields['sla_expires']);
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @return Model_ContactOrg
	 */
	static function get($id) {
		$where = sprintf("%s = %d",
			self::ID,
			$id
		);
		$objects = self::getWhere($where);

		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}	

	/**
	 * Enter description here...
	 *
	 * @param string $name
	 * @param boolean $create_if_null
	 * @return Model_ContactOrg
	 */
	static function lookup($name, $create_if_null=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		@$orgs = self::getWhere(
			sprintf('%s = %s', self::NAME, $db->qstr($name))
		);
		
		if(empty($orgs)) {
			if($create_if_null) {
				$fields = array(
					self::NAME => $name
				);
				return self::create($fields);
			}
		} else {
			return key($orgs);
		}
		
		return NULL;
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
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, SearchFields_ContactOrg::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$select_sql = sprintf("SELECT ".
			"c.id as %s, ".
			"c.name as %s, ".
			"c.street as %s, ".
			"c.city as %s, ".
			"c.province as %s, ".
			"c.postal as %s, ".
			"c.country as %s, ".
			"c.phone as %s, ".
			"c.fax as %s, ".
			"c.website as %s, ".
			"c.created as %s, ".
			"c.sla_id as %s ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_ContactOrg::ID,
			    SearchFields_ContactOrg::NAME,
			    SearchFields_ContactOrg::STREET,
			    SearchFields_ContactOrg::CITY,
			    SearchFields_ContactOrg::PROVINCE,
			    SearchFields_ContactOrg::POSTAL,
			    SearchFields_ContactOrg::COUNTRY,
			    SearchFields_ContactOrg::PHONE,
			    SearchFields_ContactOrg::FAX,
			    SearchFields_ContactOrg::WEBSITE,
			    SearchFields_ContactOrg::CREATED,
			    SearchFields_ContactOrg::SLA_ID
			);
		
		$join_sql = 'FROM contact_org c ';

		// [JAS]: Dynamic table joins
		//(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").

		// Custom field joins
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		
		foreach($tables as $tbl_name => $null) {
			if(substr($tbl_name,0,3)!="cf_")
				continue;
				
			if(0 != ($cf_id = intval(substr($tbl_name,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					unset($tables[$tbl_name]);
					continue;
				}
				
				$select_sql .= sprintf(", cf_%d.field_value as cf_%d ",
					$cf_id,
					$cf_id
				);
				
				$join_sql .= sprintf("LEFT JOIN custom_field_value cf_%d ON ('%s' = cf_%d.source_extension AND c.id=cf_%d.source_id AND cf_%d.field_id=%d) ",
					$cf_id,
					ChCustomFieldSource_Org::ID,
					$cf_id,
					$cf_id,
					$cf_id,
					$cf_id
				);
			}
		}
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sql = $select_sql . $join_sql . $where_sql .
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "");
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = $rs->RecordCount();
		}
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[SearchFields_ContactOrg::ID]);
			$results[$ticket_id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }	
};

class SearchFields_ContactOrg {
	const ID = 'c_id';
	const NAME = 'c_name';
	const STREET = 'c_street';
	const CITY = 'c_city';
	const PROVINCE = 'c_province';
	const POSTAL = 'c_postal';
	const COUNTRY = 'c_country';
	const PHONE = 'c_phone';
	const FAX = 'c_fax';
	const WEBSITE = 'c_website';
	const CREATED = 'c_created';
	const SLA_ID = 'c_sla_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'c', 'id', null, $translate->_('contact_org.id')),
			self::NAME => new DevblocksSearchField(self::NAME, 'c', 'name', null, $translate->_('contact_org.name')),
			self::STREET => new DevblocksSearchField(self::STREET, 'c', 'street', null, $translate->_('contact_org.street')),
			self::CITY => new DevblocksSearchField(self::CITY, 'c', 'city', null, $translate->_('contact_org.city')),
			self::PROVINCE => new DevblocksSearchField(self::PROVINCE, 'c', 'province', null, $translate->_('contact_org.province')),
			self::POSTAL => new DevblocksSearchField(self::POSTAL, 'c', 'postal', null, $translate->_('contact_org.postal')),
			self::COUNTRY => new DevblocksSearchField(self::COUNTRY, 'c', 'country', null, $translate->_('contact_org.country')),
			self::PHONE => new DevblocksSearchField(self::PHONE, 'c', 'phone', null, $translate->_('contact_org.phone')),
			self::FAX => new DevblocksSearchField(self::FAX, 'c', 'fax', null, $translate->_('contact_org.fax')),
			self::WEBSITE => new DevblocksSearchField(self::WEBSITE, 'c', 'website', null, $translate->_('contact_org.website')),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'c', 'created', null, $translate->_('contact_org.created')),
			self::SLA_ID => new DevblocksSearchField(self::SLA_ID, 'c', 'sla_id', null, $translate->_('sla.name')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',null,$field->name);
		}
		
		return $columns;
	}
};

/**
 * Address DAO
 * 
 */
class DAO_Address extends DevblocksORMHelper {
	const ID = 'id';
	const EMAIL = 'email';
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const CONTACT_ORG_ID = 'contact_org_id';
	const PHONE = 'phone';
	const NUM_SPAM = 'num_spam';
	const NUM_NONSPAM = 'num_nonspam';
	const IS_BANNED = 'is_banned';
	const SLA_ID = 'sla_id';
	const SLA_EXPIRES = 'sla_expires';
	const LAST_AUTOREPLY = 'last_autoreply';
	
	private function __construct() {}
	
	public static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			'id' => $translate->_('address.id'),
			'email' => $translate->_('address.email'),
			'first_name' => $translate->_('address.first_name'),
			'last_name' => $translate->_('address.last_name'),
			'phone' => $translate->_('address.phone'),
			'contact_org_id' => $translate->_('address.contact_org_id'),
			'num_spam' => $translate->_('address.num_spam'),
			'num_nonspam' => $translate->_('address.num_nonspam'),
			'is_banned' => $translate->_('address.is_banned'),
			'sla_id' => $translate->_('sla.name'),
//			'last_autoreply' => $translate->_('sla.name'),
		);
	}
	
	/**
	 * Creates a new e-mail address record.
	 *
	 * @param array $fields An array of fields=>values
	 * @return integer The new address ID
	 * 
	 * DAO_Address::create(array(
	 *   DAO_Address::EMAIL => 'user@domain'
	 * ));
	 * 
	 */
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('address_seq');

		if(null == ($email = @$fields[self::EMAIL]))
			return NULL;
		
		// [TODO] Validate
		@$addresses = imap_rfc822_parse_adrlist('<'.$email.'>', 'host');
		
		if(!is_array($addresses) || empty($addresses))
			return NULL;
		
		$address = array_shift($addresses);
		
		if(empty($address->host) || $address->host == 'host')
			return NULL;
		
		$full_address = trim(strtolower($address->mailbox.'@'.$address->host));
			
		// Make sure the address doesn't exist already
		if(null == ($check = self::getByEmail($full_address))) {
			$sql = sprintf("INSERT INTO address (id,email,first_name,last_name,phone,contact_org_id,num_spam,num_nonspam,is_banned,last_autoreply) ".
				"VALUES (%d,%s,'','','',0,0,0,0,0)",
				$id,
				$db->qstr($full_address)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		} else { // update
			$id = $check->id;
			unset($fields[self::ID]);
			unset($fields[self::EMAIL]);
		}

		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'address', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('address', $fields, $where);
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK address_auth FROM address_auth LEFT JOIN address ON address_auth.address_id=address.id WHERE address.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' address_auth records.');
		
		$sql = "DELETE QUICK address_to_worker FROM address_to_worker LEFT JOIN worker ON address_to_worker.worker_id=worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' address_to_worker records.');
	}
	
    static function delete($ids) {
        if(!is_array($ids)) $ids = array($ids);
        if(empty($ids)) return;

		$db = DevblocksPlatform::getDatabaseService();
        
        $address_ids = implode(',', $ids);
        
        $sql = sprintf("DELETE QUICK FROM address WHERE id IN (%s)", $address_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        DAO_AddressAuth::delete($ids);
    }
		
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$addresses = array();
		
		$sql = sprintf("SELECT a.id, a.email, a.first_name, a.last_name, a.contact_org_id, a.phone, a.num_spam, a.num_nonspam, a.is_banned, a.sla_id, a.sla_expires, a.last_autoreply ".
			"FROM address a ".
			((!empty($where)) ? "WHERE %s " : " ").
			"ORDER BY a.email ",
			$where
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$address = new Model_Address();
			$address->id = intval($rs->fields['id']);
			$address->email = $rs->fields['email'];
			$address->first_name = $rs->fields['first_name'];
			$address->last_name = $rs->fields['last_name'];
			$address->contact_org_id = intval($rs->fields['contact_org_id']);
			$address->phone = $rs->fields['phone'];
			$address->num_spam = intval($rs->fields['num_spam']);
			$address->num_nonspam = intval($rs->fields['num_nonspam']);
			$address->is_banned = intval($rs->fields['is_banned']);
			$address->sla_id = intval($rs->fields['sla_id']);
			$address->sla_expires = intval($rs->fields['sla_expires']);
			$address->last_autoreply = intval($rs->fields['last_autoreply']);
			$addresses[$address->id] = $address;
			$rs->MoveNext();
		}
		
		return $addresses;
	}

	/**
	 * @return Model_Address|null
	 */
	static function getByEmail($email) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$results = self::getWhere(sprintf("%s = %s",
			self::EMAIL,
			$db->qstr(strtolower($email))
		));

		if(!empty($results))
			return array_shift($results);
			
		return NULL;
	}
	
	static function getCountByOrgId($org_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(id) FROM address WHERE contact_org_id = %d",
			$org_id
		);
		return intval($db->GetOne($sql));
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return Model_Address
	 */
	static function get($id) {
		if(empty($id)) return null;
		
		$addresses = DAO_Address::getWhere(
			sprintf("%s = %d",
				self::ID,
				$id
		));
		
		if(isset($addresses[$id]))
			return $addresses[$id];
			
		return null;		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $email
	 * @param unknown_type $create_if_null
	 * @return Model_Address
	 */
	static function lookupAddress($email,$create_if_null=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$address = null;
		
		$email = trim(strtolower($email));
		
		$addresses = self::getWhere(sprintf("email = %s",
			$db->qstr($email)
		));
		
		if(is_array($addresses) && !empty($addresses)) {
			$address = array_shift($addresses);
		} elseif($create_if_null) {
			$fields = array(
				self::EMAIL => $email
			);
			$id = DAO_Address::create($fields);
			$address = DAO_Address::get($id);
		}
		
		return $address;
	}
	
	static function addOneToSpamTotal($address_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE address SET num_spam = num_spam + 1 WHERE id = %d",$address_id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function addOneToNonSpamTotal($address_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE address SET num_nonspam = num_nonspam + 1 WHERE id = %d",$address_id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
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
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, SearchFields_Address::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.email as %s, ".
			"a.first_name as %s, ".
			"a.last_name as %s, ".
			"a.contact_org_id as %s, ".
			"o.name as %s, ".
			"a.phone as %s, ".
			"a.num_spam as %s, ".
			"a.num_nonspam as %s, ".
			"a.is_banned as %s, ".
			"a.sla_id as %s ",
			    SearchFields_Address::ID,
			    SearchFields_Address::EMAIL,
			    SearchFields_Address::FIRST_NAME,
			    SearchFields_Address::LAST_NAME,
			    SearchFields_Address::CONTACT_ORG_ID,
			    SearchFields_Address::ORG_NAME,
			    SearchFields_Address::PHONE,
			    SearchFields_Address::NUM_SPAM,
			    SearchFields_Address::NUM_NONSPAM,
			    SearchFields_Address::IS_BANNED,
			    SearchFields_Address::SLA_ID
			 );
		
		$join_sql = 
			"FROM address a ".
			"LEFT JOIN contact_org o ON (o.id=a.contact_org_id) "
		;
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=a.contact_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		// Custom field joins
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		
		foreach($tables as $tbl_name => $null) {
			if(substr($tbl_name,0,3)!="cf_")
				continue;
				
			if(0 != ($cf_id = intval(substr($tbl_name,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					unset($tables[$tbl_name]);
					continue;
				}
				
				$select_sql .= sprintf(", cf_%d.field_value as cf_%d ",
					$cf_id,
					$cf_id
				);
				
				$join_sql .= sprintf("LEFT JOIN custom_field_value cf_%d ON ('%s' = cf_%d.source_extension AND a.id=cf_%d.source_id AND cf_%d.field_id=%d) ",
					$cf_id,
					ChCustomFieldSource_Address::ID,
					$cf_id,
					$cf_id,
					$cf_id,
					$cf_id
				);
			}
		}
	
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sql = $select_sql . $join_sql . $where_sql .  
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "");
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_Address::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = "SELECT count(*) " . $join_sql . $where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
    }
};

class SearchFields_Address implements IDevblocksSearchFields {
	// Address
	const ID = 'a_id';
	const EMAIL = 'a_email';
	const FIRST_NAME = 'a_first_name';
	const LAST_NAME = 'a_last_name';
	const CONTACT_ORG_ID = 'a_contact_org_id';
	const PHONE = 'a_phone';
	const NUM_SPAM = 'a_num_spam';
	const NUM_NONSPAM = 'a_num_nonspam';
	const IS_BANNED = 'a_is_banned';
	const SLA_ID = 'a_sla_id';
	
	const ORG_NAME = 'o_name';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', null, $translate->_('address.id')),
			self::EMAIL => new DevblocksSearchField(self::EMAIL, 'a', 'email', null, $translate->_('address.email')),
			self::FIRST_NAME => new DevblocksSearchField(self::FIRST_NAME, 'a', 'first_name', null, $translate->_('address.first_name')),
			self::LAST_NAME => new DevblocksSearchField(self::LAST_NAME, 'a', 'last_name', null, $translate->_('address.last_name')),
			self::CONTACT_ORG_ID => new DevblocksSearchField(self::CONTACT_ORG_ID, 'a', 'contact_org_id', null, $translate->_('address.contact_org_id')),
			self::PHONE => new DevblocksSearchField(self::PHONE, 'a', 'phone', null, $translate->_('address.phone')),
			self::NUM_SPAM => new DevblocksSearchField(self::NUM_SPAM, 'a', 'num_spam', null, $translate->_('address.num_spam')),
			self::NUM_NONSPAM => new DevblocksSearchField(self::NUM_NONSPAM, 'a', 'num_nonspam', null, $translate->_('address.num_nonspam')),
			self::IS_BANNED => new DevblocksSearchField(self::IS_BANNED, 'a', 'is_banned', null, $translate->_('address.is_banned')),
			self::SLA_ID => new DevblocksSearchField(self::SLA_ID, 'a', 'sla_id', null, $translate->_('sla.name')),
			
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'o', 'name', null, $translate->_('contact_org.name')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',null,$field->name);
		}
		
		return $columns;
	}
};

class DAO_AddressAuth extends DevblocksORMHelper  {
	const ADDRESS_ID = 'address_id';
	const CONFIRM = 'confirm';
	const PASS = 'pass';
	
	static function update($id, $fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$auth = self::get($id);
		
		// Create if necessary
		if(empty($auth)) {
			$sql = sprintf("INSERT INTO address_auth (address_id, confirm, pass) ".
				"VALUES (%d, '', '')",
				$id
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
		unset($auth);
		
		parent::_update($id, 'address_auth', $fields, self::ADDRESS_ID);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return Model_AddressAuth
	 */
	static function get($id) {
		$addresses = self::getWhere(sprintf("%s = %d",self::ADDRESS_ID,$id));
		
		if(isset($addresses[$id]))
			return $addresses[$id];
			
		return null;		
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $where
	 * @return Model_AddressAuth[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT address_id, confirm, pass ".
			"FROM address_auth ".
			(!empty($where) ? sprintf("WHERE %s ", $where) : "")
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_AddressAuth();
			$object->address_id = intval($rs->fields['address_id']);
			$object->confirm = $rs->fields['confirm'];
			$object->pass = $rs->fields['pass'];
			$objects[$object->address_id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
        if(!is_array($ids)) $ids = array($ids);
        if(empty($ids)) return;

		$db = DevblocksPlatform::getDatabaseService();
        
        $address_ids = implode(',', $ids);
        $sql = sprintf("DELETE QUICK FROM address_auth WHERE address_id IN (%s)", $address_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
};

class DAO_AddressToWorker { // extends DevblocksORMHelper
	const ADDRESS = 'address';
	const WORKER_ID = 'worker_id';
	const IS_CONFIRMED = 'is_confirmed';
	const CODE = 'code';
	const CODE_EXPIRE = 'code_expire';

	static function assign($address, $worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($address) || empty($worker_id))
			return NULL;

		$sql = sprintf("INSERT INTO address_to_worker (address, worker_id, is_confirmed, code, code_expire) ".
			"VALUES (%s, %d, 0, '', 0)",
			$db->qstr($address),
			$worker_id
		);
		$db->Execute($sql);

		return $address;
	}

	static function unassign($address) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($address))
			return NULL;
			
		$sql = sprintf("DELETE QUICK FROM address_to_worker WHERE address = %s",
			$db->qstr($address)
		);
		$db->Execute($sql);
	}
	
	static function unassignAll($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($worker_id))
			return NULL;
			
		$sql = sprintf("DELETE QUICK FROM address_to_worker WHERE worker_id = %d",
			$worker_id
		);
		$db->Execute($sql);
	}
	
	static function update($addresses, $fields) {
	    if(!is_array($addresses)) $addresses = array($addresses);
		$db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($addresses))
			return;
		
		foreach($fields as $k => $v) {
		    if(is_null($v))
		        $value = 'NULL';
		    else
		        $value = $db->qstr($v);
		    
			$sets[] = sprintf("%s = %s",
				$k,
				$value
			);
		}
		
		$sql = sprintf("UPDATE %s SET %s WHERE %s IN ('%s')",
			'address_to_worker',
			implode(', ', $sets),
			self::ADDRESS,
			implode("','", $addresses)
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $worker_id
	 * @return Model_AddressToWorker[]
	 */
	static function getByWorker($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT address, worker_id, is_confirmed, code, code_expire ".
			"FROM address_to_worker ".
			"WHERE worker_id = %d",
			$worker_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */ 
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $address
	 * @return Model_AddressToWorker
	 */
	static function getByAddress($address) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT address, worker_id, is_confirmed, code, code_expire ".
			"FROM address_to_worker ".
			"WHERE address = %s",
			$db->qstr($address)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */ 
		
		$addresses = self::_getObjectsFromResult($rs);
		
		if(isset($addresses[$address]))
			return $addresses[$address];
			
		return NULL;
	}
	
	static function getWhere($where) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT address, worker_id, is_confirmed, code, code_expire ".
			"FROM address_to_worker ".
			(!empty($where) ? sprintf("WHERE %s ", $where) : " ");
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */ 

		return self::_getObjectsFromResult($rs);		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param ADORecordSet $rs
	 * @return Model_AddressToWorker[]
	 */
	private static function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_AddressToWorker();
			$object->worker_id = intval($rs->fields['worker_id']);
			$object->address = $rs->fields['address'];
			$object->is_confirmed = intval($rs->fields['is_confirmed']);
			$object->code = $rs->fields['code'];
			$object->code_expire = intval($rs->fields['code_expire']);
			$objects[$object->address] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
};

class DAO_Message extends DevblocksORMHelper {
    const ID = 'id';
    const TICKET_ID = 'ticket_id';
    const CREATED_DATE = 'created_date';
    const ADDRESS_ID = 'address_id';
    const IS_OUTGOING = 'is_outgoing';
    const WORKER_ID = 'worker_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$newId = $db->GenID('message_seq');
		
		$sql = sprintf("INSERT INTO message (id,ticket_id,created_date,is_outgoing,worker_id,address_id) ".
			"VALUES (%d,0,0,0,0,0)",
			$newId
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($newId, $fields);
		
		return $newId;
	}
    
    static function update($id, $fields) {
        parent::_update($id, 'message', $fields);
    }

    static function maint() {
    	$db = DevblocksPlatform::getDatabaseService();
    	$logger = DevblocksPlatform::getConsoleLog();
    	
		$sql = "DELETE QUICK message FROM message LEFT JOIN ticket ON message.ticket_id = ticket.id WHERE ticket.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message records.');
		
		$sql = "DELETE QUICK message_header FROM message_header LEFT JOIN message ON message_header.message_id = message.id WHERE message.id IS NULL";
		$db->Execute($sql);

		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message_header records.');
		
		$sql = "DELETE QUICK message_content FROM message_content LEFT JOIN message ON message_content.message_id = message.id WHERE message.id IS NULL";
		$db->Execute($sql);

		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message_content records.');
		
		$sql = "DELETE QUICK message_note FROM message_note LEFT JOIN message ON message_note.message_id = message.id WHERE message.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' message_note records.');
		
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

        list($tables,$wheres,$selects) = parent::_parseSearchParams($params, array(),SearchFields_Message::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"m.id as %s, ".
			"m.ticket_id as %s ".
			"FROM message m ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_Message::ID,
			    SearchFields_Message::TICKET_ID
			).
			
			// [JAS]: Dynamic table joins
			(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=m.id)" : " ").
			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[SearchFields_Message::ID]);
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

class SearchFields_Message implements IDevblocksSearchFields {
	// Message
	const ID = 'm_id';
	const TICKET_ID = 'm_ticket_id';
	
	// Headers
	const MESSAGE_HEADER_NAME = 'mh_header_name';
	const MESSAGE_HEADER_VALUE = 'mh_header_value';

    // Content
	const MESSAGE_CONTENT = 'mc_content';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		return array(
			SearchFields_Message::ID => new DevblocksSearchField(SearchFields_Message::ID, 'm', 'id'),
			SearchFields_Message::TICKET_ID => new DevblocksSearchField(SearchFields_Message::TICKET_ID, 'm', 'ticket_id'),
			
			SearchFields_Message::MESSAGE_HEADER_NAME => new DevblocksSearchField(SearchFields_Message::MESSAGE_HEADER_NAME, 'mh', 'header_name'),
			SearchFields_Message::MESSAGE_HEADER_VALUE => new DevblocksSearchField(SearchFields_Message::MESSAGE_HEADER_VALUE, 'mh', 'header_value', 'B'),

			SearchFields_Message::MESSAGE_CONTENT => new DevblocksSearchField(SearchFields_Message::MESSAGE_CONTENT, 'mc', 'content', 'B'),
		);
	}
};

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
    	$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

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
    	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

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
    	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

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
    	$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

    	return self::_getObjectsFromResultSet($rs);
    }
    	
    static function get($id) {
    	$objects = self::getList(array($id));
    	return @$objects[$id];
    }
    
    static private function _getObjectsFromResultSet($rs) {
    	$objects = array();
    	
    	if(is_a($rs,'ADORecordSet'))
    	while(!$rs->EOF) {
    		$object = new Model_MessageNote();
    		$object->id = intval($rs->fields['id']);
    		$object->type = intval($rs->fields['type']);
    		$object->message_id = intval($rs->fields['message_id']);
    		$object->created = intval($rs->fields['created']);
    		$object->worker_id = intval($rs->fields['worker_id']);
    		$object->content = $rs->fields['content'];
    		$objects[$object->id] = $object;
    		$rs->MoveNext();
    	}
    	
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
        if(empty($ids)) return;

		$db = DevblocksPlatform::getDatabaseService();
        
        $message_ids = implode(',', $ids);
        $sql = sprintf("DELETE QUICK FROM message_note WHERE id IN (%s)", $message_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
    }
};

class DAO_MessageContent {
    const MESSAGE_ID = 'message_id';
    const CONTENT = 'content';
    
    static function update($message_id, $content) {
        $db = DevblocksPlatform::getDatabaseService();
        
        $db->Replace(
            'message_content',
            array(
                self::MESSAGE_ID => $message_id,
                self::CONTENT => $db->qstr($content),
            ),
            array('message_id'),
            false
        );
    }
    
	static function get($message_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$content = '';
		
		$sql = sprintf("SELECT m.content ".
			"FROM message_content m ".
			"WHERE m.message_id = %d ",
			$message_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet') && !$rs->EOF) {
			return $rs->fields['content'];
		}
		
		return '';
	}
};

class DAO_MessageHeader {
    const MESSAGE_ID = 'message_id';
    const TICKET_ID = 'ticket_id';
    const HEADER_NAME = 'header_name';
    const HEADER_VALUE = 'header_value';
    
    static function update($message_id, $ticket_id, $header, $value) {
        $db = DevblocksPlatform::getDatabaseService();
        
        $header = strtolower($header);
        
        if(empty($header))
            return;
        
        // Insert not replace?  (Can be multiple stacked headers like received?)
        $db->Replace(
            'message_header',
            array(
                self::MESSAGE_ID => $message_id,
                self::TICKET_ID => $ticket_id,
                self::HEADER_NAME => $db->qstr($header),
                self::HEADER_VALUE => $db->qstr('')
            ),
            array('message_id','header_name'),
            false
        );
        
        if(!empty($value) && !empty($message_id) && !empty($header)) {
        	if(is_array($value)) {
        		$value = implode("\r\n",$value);
        	}
        	$db->UpdateBlob(
        		'message_header',
        		self::HEADER_VALUE,
        		$value,
        		'message_id='.$message_id.' AND header_name='.$db->qstr($header)
        	);
        }
    }
    
    static function getAll($message_id) {
        $db = DevblocksPlatform::getDatabaseService();
        
        $sql = "SELECT header_name, header_value ".
            "FROM message_header ".
            "WHERE message_id = ?";
            
        $rs = $db->Execute($sql, array($message_id))
            or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        $headers = array();
            
        while(!$rs->EOF) {
            $headers[$rs->fields['header_name']] = $rs->fields['header_value'];
            $rs->MoveNext();
        }
        
        return $headers;
    }
    
    static function getUnique() {
        $db = DevblocksPlatform::getDatabaseService();
        $headers = array();
        
        $sql = "SELECT header_name FROM message_header GROUP BY header_name";
        $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        if(is_a($rs,'ADORecordSet'))
        while(!$rs->EOF) {
            $headers[] = $rs->fields['header_name'];
            $rs->MoveNext();
        }
        
        sort($headers);
        
        return $headers;
    }
};

class DAO_Attachment extends DevblocksORMHelper {
    const ID = 'id';
    const MESSAGE_ID = 'message_id';
    const DISPLAY_NAME = 'display_name';
    const MIME_TYPE = 'mime_type';
    const FILE_SIZE = 'file_size';
    const FILEPATH = 'filepath';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('attachment_seq');
		
		$sql = sprintf("INSERT INTO attachment (id,message_id,display_name,mime_type,file_size,filepath) ".
		    "VALUES (%d,0,'','',0,'')",
		    $id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'attachment', $fields);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Attachment
	 */
	public static function get($id) {
		$items = self::getList(array($id));
		
		if(isset($items[$id]))
		    return $items[$id];
		    
		return NULL;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return Model_Attachment[]
	 */
	public static function getList($ids=array()) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,message_id,display_name,mime_type,file_size,filepath ".
		    "FROM attachment ".
		    (!empty($ids) ? sprintf("WHERE id IN (%s) ", implode(',', $ids)) : " ").
		    ""
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
		    $object = new Model_Attachment();
		    $object->id = intval($rs->fields['id']);
		    $object->message_id = intval($rs->fields['message_id']);
		    $object->display_name = $rs->fields['display_name'];
		    $object->filepath = $rs->fields['filepath'];
		    $object->mime_type = $rs->fields['mime_type'];
		    $object->file_size = intval($rs->fields['file_size']);
		    $objects[$object->id] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
	
	/**
	 * returns an array of Model_Attachment that
	 * correspond to the supplied message id.
	 *
	 * @param integer $id
	 * @return Model_Attachment[]
	 */
	static function getByMessageId($id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT a.id, a.message_id, a.display_name, a.filepath, a.file_size, a.mime_type ".
			"FROM attachment a ".
			"WHERE a.message_id = %d",
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$attachments = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$attachment = new Model_Attachment();
			$attachment->id = intval($rs->fields['id']);
			$attachment->message_id = intval($rs->fields['message_id']);
			$attachment->display_name = $rs->fields['display_name'];
			$attachment->filepath = $rs->fields['filepath'];
			$attachment->file_size = intval($rs->fields['file_size']);
			$attachment->mime_type = $rs->fields['mime_type'];
			$attachments[$attachment->id] = $attachment;
			$rs->MoveNext();
		}

		return $attachments;
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "SELECT filepath FROM attachment LEFT JOIN message ON attachment.message_id = message.id WHERE message.id IS NULL";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$attachment_path = APP_PATH . '/storage/attachments/';
		
		// Delete the physical files
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			@unlink($attachment_path . $rs->fields['filepath']);
			$rs->MoveNext();
		}
		
		$sql = "DELETE attachment FROM attachment LEFT JOIN message ON attachment.message_id = message.id WHERE message.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' attachment records.');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("SELECT filepath FROM attachment WHERE id IN (%s)", implode(',',$ids));
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$attachment_path = APP_PATH . '/storage/attachments/';
		
		// Delete the physical files
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			@unlink($attachment_path . $rs->fields['filepath']);
			$rs->MoveNext();
		}
		
		// Delete DB manifests
		$sql = sprintf("DELETE attachment FROM attachment WHERE id IN (%s)", implode(',', $ids));
		$db->Execute($sql);
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

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),SearchFields_Attachment::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.message_id as %s, ".
			"a.display_name as %s, ".
			"a.mime_type as %s, ".
			"a.file_size as %s, ".
			"a.filepath as %s, ".
		
			"m.address_id as %s, ".
			"m.created_date as %s, ".
			"m.is_outgoing as %s, ".
		
			"t.id as %s, ".
			"t.mask as %s, ".
			"t.subject as %s, ".
		
			"ad.email as %s ".
		
			"FROM attachment a ".
			"INNER JOIN message m ON (a.message_id = m.id) ".
			"INNER JOIN ticket t ON (m.ticket_id = t.id) ".
			"INNER JOIN address ad ON (m.address_id = ad.id) ".
			"",
			    SearchFields_Attachment::ID,
			    SearchFields_Attachment::MESSAGE_ID,
			    SearchFields_Attachment::DISPLAY_NAME,
			    SearchFields_Attachment::MIME_TYPE,
			    SearchFields_Attachment::FILE_SIZE,
			    SearchFields_Attachment::FILEPATH,
			    
			    SearchFields_Attachment::MESSAGE_ADDRESS_ID,
			    SearchFields_Attachment::MESSAGE_CREATED_DATE,
			    SearchFields_Attachment::MESSAGE_IS_OUTGOING,
			    
			    SearchFields_Attachment::TICKET_ID,
			    SearchFields_Attachment::TICKET_MASK,
			    SearchFields_Attachment::TICKET_SUBJECT,
			    
			    SearchFields_Attachment::ADDRESS_EMAIL
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = $rs->RecordCount();
		}
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[SearchFields_Attachment::ID]);
			$results[$ticket_id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = $rs->RecordCount();
		}
		
		return array($results,$total);
    }
	
};

class SearchFields_Attachment implements IDevblocksSearchFields {
    const ID = 'a_id';
    const MESSAGE_ID = 'a_message_id';
    const DISPLAY_NAME = 'a_display_name';
    const MIME_TYPE = 'a_mime_type';
    const FILE_SIZE = 'a_file_size';
    const FILEPATH = 'a_filepath';
	
    const MESSAGE_ADDRESS_ID = 'm_address_id';
    const MESSAGE_CREATED_DATE = 'm_created_date';
    const MESSAGE_IS_OUTGOING = 'm_is_outgoing';
    
    const TICKET_ID = 't_id';
    const TICKET_MASK = 't_mask';
    const TICKET_SUBJECT = 't_subject';
    
    const ADDRESS_EMAIL = 'ad_email';
    
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', null, $translate->_('attachment.id')),
			self::MESSAGE_ID => new DevblocksSearchField(self::MESSAGE_ID, 'a', 'message_id', null, $translate->_('attachment.message_id')),
			self::DISPLAY_NAME => new DevblocksSearchField(self::DISPLAY_NAME, 'a', 'display_name', null, $translate->_('attachment.display_name')),
			self::MIME_TYPE => new DevblocksSearchField(self::MIME_TYPE, 'a', 'mime_type', null, $translate->_('attachment.mime_type')),
			self::FILE_SIZE => new DevblocksSearchField(self::FILE_SIZE, 'a', 'file_size', null, $translate->_('attachment.file_size')),
			self::FILEPATH => new DevblocksSearchField(self::FILEPATH, 'a', 'filepath', null, $translate->_('attachment.filepath')),
			
			self::MESSAGE_ADDRESS_ID => new DevblocksSearchField(self::MESSAGE_ADDRESS_ID, 'm', 'address_id', null),
			self::MESSAGE_CREATED_DATE => new DevblocksSearchField(self::MESSAGE_CREATED_DATE, 'm', 'created_date', null, $translate->_('message.created_date')),
			self::MESSAGE_IS_OUTGOING => new DevblocksSearchField(self::MESSAGE_IS_OUTGOING, 'm', 'is_outgoing', null, $translate->_('mail.outbound')),
			
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 't', 'id', null, $translate->_('ticket.id')),
			self::TICKET_MASK => new DevblocksSearchField(self::TICKET_MASK, 't', 'mask', null, $translate->_('ticket.mask')),
			self::TICKET_SUBJECT => new DevblocksSearchField(self::TICKET_SUBJECT, 't', 'subject', null, $translate->_('ticket.subject')),
			
			self::ADDRESS_EMAIL => new DevblocksSearchField(self::ADDRESS_EMAIL, 'ad', 'email', null, $translate->_('message.header.from')),
		);
	}
};

class DAO_Sla extends DevblocksORMHelper {
	const CACHE_ALL = 'cerberus_cache_sla_all';
	
	const ID = 'id';
	const NAME = 'name';
	const PRIORITY = 'priority';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO sla (id,name,priority) ".
			"VALUES (%d,'',0)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
		
		return $id;
	}
	
	public static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($slas = $cache->load(self::CACHE_ALL))) {
    	    $slas = self::getWhere();
    	    $cache->save($slas, self::CACHE_ALL);
	    }
	    
	    return $slas;
	}
	
	public static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, priority ".
			"FROM sla ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY priority DESC";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	private static function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_null($rs))
			return array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_Sla();
			$object->id = intval($rs->fields['id']);
			$object->name = $rs->fields['name'];
			$object->priority = intval($rs->fields['priority']);
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function update($ids, $fields) {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
		
		return parent::_update($ids, 'sla', $fields);
	}
	
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$id_list = implode(',', $ids);
		
		$sql = sprintf("DELETE QUICK FROM sla WHERE id IN (%s)", $id_list);
		$db->Execute($sql);
		
		// Ticket
		$sql = sprintf("UPDATE ticket SET sla_id = 0 WHERE sla_id IN (%s)", $id_list);
		$db->Execute($sql);
		
		// Address
		$sql = sprintf("UPDATE address SET sla_id = 0 WHERE sla_id IN (%s)", $id_list);
		$db->Execute($sql);
		
		// Orgs
		$sql = sprintf("UPDATE contact_org SET sla_id = 0 WHERE sla_id IN (%s)", $id_list);
		$db->Execute($sql);
		
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	static public function cascadeOrgSla($org_id, $sla_id) {
		// Update all addresses from this org
		DAO_Address::updateWhere(
			array(
				DAO_Address::SLA_ID => $sla_id
			),
			sprintf("%s = %d",
				DAO_Address::CONTACT_ORG_ID,
				$org_id
			)
		);
		
		// Update SLA+Priority on any open tickets from this org
		$sla_priority = 0;
		if(!empty($sla_id) && null != ($sla = DAO_Sla::get($sla_id))) {
			$sla_priority = $sla->priority;
		}
		
		$org_addresses = DAO_Address::getWhere(sprintf("%s = %d",
			DAO_Address::CONTACT_ORG_ID,
			$org_id
		));
		
		if(!empty($org_addresses)) {
			DAO_Ticket::updateWhere(
				array(
					DAO_Ticket::SLA_ID => $sla_id,
					DAO_Ticket::SLA_PRIORITY => $sla_priority
				),
				sprintf("%s IN (%s)",
					DAO_Ticket::FIRST_WROTE_ID,
					implode(',',array_keys($org_addresses))
				)
			);
		}
	}
	
	static public function cascadeAddressSla($address_id, $sla_id) {
		$sla_priority = 0;
		if(!empty($sla_id) && null != ($sla = DAO_Sla::get($sla_id))) {
			$sla_priority = $sla->priority;
		}
		
		DAO_Ticket::updateWhere(
			array(
				DAO_Ticket::SLA_ID => $sla_id,
				DAO_Ticket::SLA_PRIORITY => $sla_priority
			),
			sprintf("%s = %d",
				DAO_Ticket::FIRST_WROTE_ID,
				$address_id
			)
		);
	}
};

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class DAO_Ticket extends DevblocksORMHelper {
	const ID = 'id';
	const MASK = 'mask';
	const SUBJECT = 'subject';
	const IS_WAITING = 'is_waiting';
	const IS_CLOSED = 'is_closed';
	const IS_DELETED = 'is_deleted';
	const TEAM_ID = 'team_id';
	const CATEGORY_ID = 'category_id';
	const FIRST_MESSAGE_ID = 'first_message_id';
	const LAST_WROTE_ID = 'last_wrote_address_id';
	const FIRST_WROTE_ID = 'first_wrote_address_id';
	const CREATED_DATE = 'created_date';
	const UPDATED_DATE = 'updated_date';
	const DUE_DATE = 'due_date';
	const UNLOCK_DATE = 'unlock_date';
	const SPAM_TRAINING = 'spam_training';
	const SPAM_SCORE = 'spam_score';
	const INTERESTING_WORDS = 'interesting_words';
	const NEXT_ACTION = 'next_action';
	const LAST_ACTION_CODE = 'last_action_code';
	const LAST_WORKER_ID = 'last_worker_id';
	const NEXT_WORKER_ID = 'next_worker_id';
	const SLA_ID = 'sla_id';
	const SLA_PRIORITY = 'sla_priority';
	
	private function DAO_Ticket() {}
	
	public static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			'id' => $translate->_('ticket.id'),
			'mask' => $translate->_('ticket.mask'),
			'subject' => $translate->_('ticket.subject'),
			'is_waiting' => $translate->_('status.waiting'),
			'is_closed' => $translate->_('status.closed'),
			'is_deleted' => $translate->_('status.deleted'),
			'team_id' => $translate->_('ticket.group'),
			'category_id' => $translate->_('ticket.bucket'),
			'updated_date' => $translate->_('ticket.updated'),
			'spam_training' => $translate->_('ticket.spam_training'),
			'spam_score' => $translate->_('ticket.spam_score'),
			'interesting_words' => $translate->_('ticket.interesting_words'),
			'next_action' => $translate->_('ticket.next_action'),
			'next_worker_id' => $translate->_('ticket.next_worker'),
			'sla_id' => $translate->_('ticket.sla_id'),
			'sla_priority' => $translate->_('ticket.sla_priority'),
		);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $mask
	 * @return integer
	 */
	static function getTicketIdByMask($mask) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT t.id FROM ticket t WHERE t.mask = %s",
			$db->qstr($mask)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet') && !$rs->EOF) {
			return intval($rs->fields['id']);
		}
		
		return null;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $mask
	 * return CerberusTicket
	 */
	static function getTicketByMask($mask) {
		if(null != ($id = self::getTicketIdByMask($mask))) {
			return self::getTicket($id);
		}
		
		return NULL;
	}
	
	static function getTicketByMessageId($message_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT mh.ticket_id, mh.message_id ".
			"FROM message_header mh ".
			"WHERE mh.header_name = 'message-id' AND mh.header_value = %s",
			$db->qstr($message_id)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			return array(
				'ticket_id' => intval($rs->fields['ticket_id']),
				'message_id' => intval($rs->fields['message_id'])
			);
		}
		
		return null;
	}
	
	/**
	 * creates a new ticket object in the database
	 *
	 * @param array $fields
	 * @return integer
	 * 
	 * [TODO]: Change $last_wrote argument to an ID rather than string?
	 */
	static function createTicket($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$newId = $db->GenID('ticket_seq');
		
		$sql = sprintf("INSERT INTO ticket (id, mask, subject, first_message_id, last_wrote_address_id, first_wrote_address_id, created_date, updated_date, due_date, unlock_date, team_id, category_id) ".
			"VALUES (%d,'','',0,0,0,%d,%d,0,0,0,0)",
			$newId,
			time(),
			time()
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::updateTicket($newId, $fields);
		
		// send new ticket auto-response
//		DAO_Mail::sendAutoresponse($id, 'new');
		
		return $newId;
	}

	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK ticket_comment FROM ticket_comment LEFT JOIN ticket ON ticket_comment.ticket_id=ticket.id WHERE ticket.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' ticket_comment records.');
		
		$sql = sprintf("DELETE QUICK custom_field_value FROM custom_field_value LEFT JOIN ticket ON (custom_field_value.source_id=ticket.id AND custom_field_value.source_extension='%s') WHERE ticket.id IS NULL",
			ChCustomFieldSource_Ticket::ID
		);
		$db->Execute($sql);

		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' custom_field_value records.');
		
		$sql = "DELETE QUICK requester FROM requester LEFT JOIN ticket ON requester.ticket_id = ticket.id WHERE ticket.id IS NULL";
		$db->Execute($sql);

		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' requester records.');
		
		// Ticket tasks
		$sql = "DELETE QUICK task FROM task LEFT JOIN ticket ON task.source_id = ticket.id WHERE task.source_extension = 'cerberusweb.tasks.ticket' AND ticket.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' task records.');
		
		// Recover any tickets assigned to next_worker_id = NULL
		$sql = "UPDATE ticket LEFT JOIN worker ON ticket.next_worker_id = worker.id SET ticket.next_worker_id = 0 WHERE ticket.next_worker_id > 0 AND worker.id IS NULL";
		$db->Execute($sql);

		$logger->info('[Maint] Fixed ' . $db->Affected_Rows() . ' tickets assigned to missing workers.');
		
		// Recover any tickets assigned to a NULL bucket
		$sql = "UPDATE ticket LEFT JOIN category ON ticket.category_id = category.id SET ticket.category_id = 0 WHERE ticket.category_id > 0 AND category.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Fixed ' . $db->Affected_Rows() . ' tickets in missing buckets.');
	}
	
	static function merge($ids=array()) {
		if(!is_array($ids) || empty($ids) || count($ids) < 2) {
			return false;
		}
		
		$db = DevblocksPlatform::getDatabaseService();
			
		list($tickets, $null) = self::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_ID,DevblocksSearchCriteria::OPER_IN,$ids),
			),
			50, // safety trigger
			0,
			SearchFields_Ticket::TICKET_CREATED_DATE,
			true,
			false
		);
		
		// Merge the rest of the tickets into the oldest
		if(is_array($tickets)) {
			list($oldest_id, $oldest_ticket) = each($tickets);
			unset($tickets[$oldest_id]);
			
			$merge_ticket_ids = array_keys($tickets);
			
			if(empty($oldest_id) || empty($merge_ticket_ids))
				return null;
			
			// Messages
			$sql = sprintf("UPDATE message SET ticket_id = %d WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			// Message headers			
			$sql = sprintf("UPDATE message_header SET ticket_id = %d WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			// Requesters [TODO] This will probably complain about some dupe constraints
			$sql = sprintf("UPDATE requester SET ticket_id = %d WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			// Tasks [TODO] This could use a ticket.merge event point later
			$sql = sprintf("UPDATE task SET source_id = %d WHERE source_extension = %s AND source_id IN (%s)",
				$oldest_id,
				$db->qstr('cerberusweb.tasks.ticket'),
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			
			// [TODO] Audit log entries?
			/*
			 * [TODO] Since the audit log is a plugin, this should be an event point the 
			 * audit log can watch and trigger on.
			 */
			
			DAO_Ticket::updateTicket($merge_ticket_ids, array(
				DAO_Ticket::IS_CLOSED => 1,
				DAO_Ticket::IS_DELETED => 1,
			));

			//[mdf] determine the most recently updated ticket so when merging we get the true last action code, last wrote id, and updated date
			$most_recent_updated_ticket = $oldest_ticket;
			if(is_array($tickets))
			foreach($tickets as $ticket) {
				$last_updated_date = intval($ticket[SearchFields_Ticket::TICKET_UPDATED_DATE]);
				$most_recent_updated_date = intval($most_recent_updated_ticket[SearchFields_Ticket::TICKET_UPDATED_DATE]);
				if($last_updated_date > $most_recent_updated_date) {
					unset($most_recent_updated_ticket);
					$most_recent_updated_ticket = $ticket;
				}
			}
			
			//[mdf] Determine the last worker message of the (already) merged messages so we can correctly update the last_worker_id later
			$messages = DAO_Ticket::getMessagesByTicket($oldest_id);
			
			if(is_array($messages))
			foreach($messages as $message) {
				if(!isset($most_recent_worker_message) 
						|| ($message->created_date > $most_recent_worker_message->created_date
						&& $message->is_outgoing == 1)) {
					unset($most_recent_worker_message);
					$most_recent_worker_message = $message;
				}
			}
			
			DAO_Ticket::updateTicket($oldest_id,array(
				DAO_Ticket::LAST_ACTION_CODE => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_LAST_ACTION_CODE], 
				DAO_Ticket::LAST_WROTE_ID => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_LAST_WROTE_ID], 
				DAO_Ticket::LAST_WORKER_ID => $most_recent_worker_message->worker_id, 
				DAO_Ticket::UPDATED_DATE => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_UPDATED_DATE]
			));			
			
			return $oldest_id;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return CerberusTicket
	 */
	static function getTicket($id) {
		if(empty($id)) return NULL;
		
		$tickets = self::getTickets(array($id));
		
		if(isset($tickets[$id]))
			return $tickets[$id];
			
		return NULL;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return CerberusTicket[]
	 */
	static function getTickets($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$tickets = array();
		if(empty($ids)) return array();
		
		$sql = "SELECT t.id , t.mask, t.subject, t.is_waiting, t.is_closed, t.is_deleted, t.team_id, t.category_id, t.first_message_id, ".
			"t.first_wrote_address_id, t.last_wrote_address_id, t.created_date, t.updated_date, t.due_date, t.unlock_date, t.spam_training, ". 
			"t.spam_score, t.interesting_words, t.next_action, t.last_worker_id, t.next_worker_id, t.sla_id, t.sla_priority ".
			"FROM ticket t ".
			(!empty($ids) ? sprintf("WHERE t.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY t.updated_date DESC"
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$ticket = new CerberusTicket();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->first_message_id = intval($rs->fields['first_message_id']);
			$ticket->team_id = intval($rs->fields['team_id']);
			$ticket->category_id = intval($rs->fields['category_id']);
			$ticket->is_waiting = intval($rs->fields['is_waiting']);
			$ticket->is_closed = intval($rs->fields['is_closed']);
			$ticket->is_deleted = intval($rs->fields['is_deleted']);
			$ticket->last_wrote_address_id = intval($rs->fields['last_wrote_address_id']);
			$ticket->first_wrote_address_id = intval($rs->fields['first_wrote_address_id']);
			$ticket->created_date = intval($rs->fields['created_date']);
			$ticket->updated_date = intval($rs->fields['updated_date']);
			$ticket->due_date = intval($rs->fields['due_date']);
			$ticket->unlock_date = intval($rs->fields['unlock_date']);
			$ticket->spam_score = floatval($rs->fields['spam_score']);
			$ticket->spam_training = $rs->fields['spam_training'];
			$ticket->interesting_words = $rs->fields['interesting_words'];
			$ticket->next_action = $rs->fields['next_action'];
			$ticket->last_worker_id = intval($rs->fields['last_worker_id']);
			$ticket->next_worker_id = intval($rs->fields['next_worker_id']);
			$ticket->sla_id = intval($rs->fields['sla_id']);
			$ticket->sla_priority = intval($rs->fields['sla_priority']);
			$tickets[$ticket->id] = $ticket;
			$rs->MoveNext();
		}
		
		return $tickets;
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('ticket', $fields, $where);
	}
	
	static function updateTicket($ids,$fields) {
		if(!is_array($ids)) $ids = array($ids);
		
		/* This event fires before the change takes place in the db,
		 * so we can denote what is actually changing against the db state
		 */
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'ticket.property.pre_change',
                array(
                    'ticket_ids' => $ids,
                    'changed_fields' => $fields,
                )
            )
	    );
		
        parent::_update($ids,'ticket',$fields);
        
		/* This event fires after the change takes place in the db,
		 * which is important if the listener needs to stack changes
		 */
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'ticket.property.post_change',
                array(
                    'ticket_ids' => $ids,
                    'changed_fields' => $fields,
                )
            )
	    );
	}
	
	/**
	 * @return CerberusMessage[]
	 */
	static function getMessagesByTicket($ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$messages = array();
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.created_date, m.address_id, m.is_outgoing, m.worker_id ".
			"FROM message m ".
			"WHERE m.ticket_id = %d ".
			"ORDER BY m.created_date ASC ",
			$ticket_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$message = new CerberusMessage();
			$message->id = intval($rs->fields['id']);
			$message->ticket_id = intval($rs->fields['ticket_id']);
			$message->created_date = intval($rs->fields['created_date']);
			$message->address_id = intval($rs->fields['address_id']);
			$message->is_outgoing = intval($rs->fields['is_outgoing']);
			$message->worker_id = intval($rs->fields['worker_id']);
			
			$messages[$message->id] = $message;
			$rs->MoveNext();
		}

		return $messages;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id message id
	 * @return CerberusMessage
	 */
	static function getMessage($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$message = null;
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.created_date, m.address_id, m.is_outgoing, m.worker_id ".
			"FROM message m ".
			"WHERE m.id = %d ".
			"ORDER BY m.created_date ASC ",
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet') && !$rs->EOF) {
			$message = new CerberusMessage();
			$message->id = intval($rs->fields['id']);
			$message->ticket_id = intval($rs->fields['ticket_id']);
			$message->created_date = intval($rs->fields['created_date']);
			$message->address_id = intval($rs->fields['address_id']);
			$message->is_outgoing = intval($rs->fields['is_outgoing']);
			$message->worker_id = intval($rs->fields['worker_id']);
		}

		return $message;
	}
	
	static function getRequestersByTicket($ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$addresses = array();
		
		$sql = sprintf("SELECT a.id , a.email ".
			"FROM address a ".
			"INNER JOIN requester r ON (r.ticket_id = %d AND a.id=r.address_id) ".
			"ORDER BY a.email ASC ",
			$ticket_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$address = new Model_Address();
			$address->id = intval($rs->fields['id']);
			$address->email = $rs->fields['email'];
			$addresses[$address->id] = $address;
			$rs->MoveNext();
		}

		return $addresses;
	}
	
	static function createRequester($address_id,$ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Replace(
		    'requester',
		    array("address_id"=>$address_id,"ticket_id"=>$ticket_id),
		    array('address_id','ticket_id')
		);
		return true;
	}
	
	static function deleteRequester($id, $address_id) {
	    if(empty($id) || empty($address_id))
	        return;
	        
        $db = DevblocksPlatform::getDatabaseService();

        $sql = sprintf("DELETE QUICK FROM requester WHERE ticket_id = %d AND address_id = %d",
            $id,
            $address_id
        );
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function analyze($params, $limit=15, $mode="senders", $mode_param=null) { // or "subjects"
		$db = DevblocksPlatform::getDatabaseService();
		list($tables,$wheres) = parent::_parseSearchParams($params, array(),SearchFields_Ticket::getFields());

		$tops = array();
		
		if($mode=="senders") {
			$senders = array();
			
			// [JAS]: Most common sender domains in work pile
			$sql = sprintf("SELECT ".
			    "count(*) as hits, substring(a1.email from position('@' in a1.email)) as domain ".
				"FROM ticket t ".
				"INNER JOIN team tm ON (tm.id = t.team_id) ".
				"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
				"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) "
				).
				
				(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
				(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
				(isset($tables['msg']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
				
				(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
		        "GROUP BY domain HAVING count(*) > 1 ".
		        "ORDER BY hits DESC ";
			
		    $rs_domains = $db->SelectLimit($sql, $limit, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs_domains ADORecordSet */
		    
			$domains = array(); // [TODO] Temporary
		    while(!$rs_domains->EOF) {
		        $hash = md5('domain'.$rs_domains->fields['domain']);
		        $domains[] = $rs_domains->fields['domain']; // [TODO] Temporary
		        $tops[$hash] = array('domain',$rs_domains->fields['domain'],$rs_domains->fields['hits']);
		        $rs_domains->MoveNext();
		    }
		    
		    // [TODO] Temporary
		    $sender_wheres = $wheres;
		    $sender_wheres[] = sprintf("substring(a1.email from position('@' in a1.email)) IN ('%s')",
		        implode("','", $domains)
		    );
		    
			// [JAS]: Most common senders in work pile
			$sql = sprintf("SELECT ".
			    "count(*) as hits, a1.email ".
				"FROM ticket t ".
				"INNER JOIN team tm ON (tm.id = t.team_id) ".
				"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
				"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) "
				).
				
				(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
				(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
				(isset($tables['msg']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
				
				(!empty($sender_wheres) ? sprintf("WHERE %s ",implode(' AND ',$sender_wheres)) : "").
		        "GROUP BY a1.email HAVING count(*) > 1 ".
		        "ORDER BY hits DESC ";
	
		    $rs_senders = $db->SelectLimit($sql, $limit*2, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs_senders ADORecordSet */
		    
		    while(!$rs_senders->EOF) {
		        $hash = md5('sender'.$rs_senders->fields['email']);
		        $senders[$hash] = array('sender',$rs_senders->fields['email'],$rs_senders->fields['hits']);
		        $rs_senders->MoveNext();
		    }
		    
		    uasort($senders, array('DAO_Ticket','sortByCount'));
	        
		    // Thread senders into domains
		    foreach($senders as $hash => $sender) {
	            $domain = substr($sender[1],strpos($sender[1],'@'));
	            $domain_hash = md5('domain' . $domain);
	            if(!isset($tops[$domain_hash])) {
		            continue; // [TODO] Temporary
	            }
	            $tops[$domain_hash][3][$hash] = $sender;
	        }
		 
		} elseif ($mode=="subjects") {
			$prefixes = array();
			
			// [JAS]: Most common subjects in work pile
			$sql = sprintf("SELECT ".
			    "count(*) as hits, substring(t.subject from 1 for 8) as prefix ".
				"FROM ticket t ".
				"INNER JOIN team tm ON (tm.id = t.team_id) ".
				"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
				"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) "
				).
				
				(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
				(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
				(isset($tables['msg']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
				
				(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
		        "GROUP BY substring(t.subject from 1 for 8) ".
		        "ORDER BY hits DESC ";
			
		    $rs_subjects = $db->SelectLimit($sql, $limit, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs_domains ADORecordSet */
		    
			$prefixes = array(); // [TODO] Temporary

		    while(!$rs_subjects->EOF) {
		        $prefixes[] = $rs_subjects->fields['prefix'];
		        $rs_subjects->MoveNext();
		    }

		    foreach($prefixes as $prefix_idx => $prefix) {
			    $prefix_wheres = $wheres;
			    $prefix_wheres[] = sprintf("substring(t.subject from 1 for 8) = %s",
			        $db->qstr($prefix)
			    );
		    	
				// [JAS]: Most common subjects in work pile
				$sql = sprintf("SELECT ".
				    "t.subject ".
					"FROM ticket t ".
					"INNER JOIN team tm ON (tm.id = t.team_id) ".
					"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
					"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) "
					).
					
					(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
					(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
					(isset($tables['msg']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
					
					(!empty($prefix_wheres) ? sprintf("WHERE %s ",implode(' AND ',$prefix_wheres)) : "").
			        "GROUP BY t.id, t.subject ";
		
				// [TODO] $limit here is completely arbitrary
			    $rs_full_subjects = $db->SelectLimit($sql, 2500, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs_senders ADORecordSet */
			    
			    $lines = array();
			    $subjects = array();
			    $patterns = array();
			    $subpatterns = array();
			    
			    while(!$rs_full_subjects->EOF) {
			    	$lines[] = $rs_full_subjects->fields['subject'];
			        $rs_full_subjects->MoveNext();
			    }
			    
			    $patterns = self::findPatterns($lines, 8);
			    
			    if(!empty($patterns)) {
			    	@$pattern = array_shift($patterns);
			        $tophash = md5('subject'.$pattern.'*');
			        $tops[$tophash] = array('subject',$pattern.'*',$rs_full_subjects->RecordCount());

			        if(!empty($patterns)) // thread subpatterns
			    	foreach($patterns as $hits => $pattern) {
				        $hash = md5('subject'.$pattern.'*');
				        $tops[$tophash][3][$hash] = array('subject',$pattern.'*',0);
				    }
			    }
			    
			    @$rs_full_subjects->free();
			    unset($lines);
		    }

		} elseif ($mode=="headers") {
			$tables['mh'] = 'mh';
			$wheres[] = sprintf("mh.header_name=%s",$db->qstr($mode_param));
				
		    $sql = sprintf("SELECT ".
			    "count(t.id) as hits, mh.header_value ".
				"FROM ticket t ".
				"INNER JOIN team tm ON (tm.id = t.team_id) ".
				"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
				"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) "
				).
				
				(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
				(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
				(isset($tables['msg']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
				(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " ").
				
				(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
		        "GROUP BY mh.header_value HAVING mh.header_value <> '' ".
		        "ORDER BY hits DESC ";
		    $rs_imports = $db->SelectLimit($sql, 25, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs_subjects ADORecordSet */
		    
		    while(!$rs_imports->EOF) {
		        $hash = md5('header'.$rs_imports->fields['header_value']);
		        $tops[$hash] = array('header',$rs_imports->fields['header_value'],$rs_imports->fields['hits'],array(),$mode_param);
		        $rs_imports->MoveNext();
		    }
		    
	    }

	    uasort($tops, array('DAO_Ticket','sortByCount'));
        
	    return $tops;
	}
	
    private function sortByCount($a,$b) {
	    if ($a[2] == $b[2]) {
	        return 0;
	    }
        return ($a[2] > $b[2]) ? -1 : 1;        
    }

	private function findPatterns($list, $min_chars=8) {
		$patterns = array();
		$simil = array();
		$simil_hash = array();
		$MAX_PASS = 15;
		$MAX_HITS = 5;
	
		// Remove dupes (not sure this makes much diff)
	//	array_unique($list);
		
		// Sort by longest subjects
		usort($list,array('DAO_Ticket','sortByLen'));
		
		$len = count($list);
		for($x=0;$x<$MAX_PASS;$x++) {
			for($y=0;$y<$len;$y++) {
				if($x==$y) continue; // skip ourselves
				if(!isset($list[$x]) || !isset($list[$y])) break;
				if(0 != ($max = self::str_similar_prefix($list[$x],$list[$y])) && $max >= $min_chars) {
					@$simil[$max] = intval($simil[$max]) + 1;
					@$simil_hash[$max] = trim(substr($list[$x],0,$max));
				}
			}
		}
		
		// Results from optimial # of chars similar from left
		arsort($simil);
	
		$max = current($simil);
		$hits = 0;
		foreach($simil as $k=>$v) {
			if($hits>$MAX_HITS)
				continue;
	
			$patterns[$v] = $simil_hash[$k];
			$hits++; 
		}
	
		return $patterns;
	}
	
	// Sort by strlen (longest to shortest)
	private function sortByLen($a,$b) {
		$asize = strlen($a);
		$bsize = strlen($b);
		if($asize==$bsize) return 0;
		return ($asize>$bsize)?-1:1;
	}
	
	private function str_similar_prefix($str1,$str2) {
		$pos = 0;
		
		$str1 = trim($str1);
		$str2 = trim($str2);
		
		while((isset($str1[$pos]) && isset($str2[$pos])) && $str1[$pos]==$str2[$pos]) {
			$pos++;
		}
		
		return $pos;
	}
    
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		$total = -1;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, SearchFields_Ticket::getFields());
		$start = ($page * $limit); // [JAS]: 1-based
		
		$select_sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.mask as %s, ".
			"t.subject as %s, ".
			"t.is_waiting as %s, ".
			"t.is_closed as %s, ".
			"t.is_deleted as %s, ".
			"t.first_wrote_address_id as %s, ".
			"t.last_wrote_address_id as %s, ".
			"t.first_message_id as %s, ".
			"a1.email as %s, ".
			"a1.num_spam as %s, ".
			"a1.num_nonspam as %s, ".
			"a2.email as %s, ".
			"a1.contact_org_id as %s, ".
			"t.created_date as %s, ".
			"t.updated_date as %s, ".
			"t.due_date as %s, ".
			"t.spam_training as %s, ".
			"t.spam_score as %s, ".
//			"t.num_tasks as %s, ".
			"t.interesting_words as %s, ".
			"t.next_action as %s, ".
			"t.last_action_code as %s, ".
			"t.last_worker_id as %s, ".
			"t.next_worker_id as %s, ".
			"tm.id as %s, ".
			"tm.name as %s, ".
			"t.category_id as %s, ".
			"t.sla_id as %s, ".
			"t.sla_priority as %s ",
//			"cat.name as %s ". // [TODO] BAD LEFT JOINS
			    SearchFields_Ticket::TICKET_ID,
			    SearchFields_Ticket::TICKET_MASK,
			    SearchFields_Ticket::TICKET_SUBJECT,
			    SearchFields_Ticket::TICKET_WAITING,
			    SearchFields_Ticket::TICKET_CLOSED,
			    SearchFields_Ticket::TICKET_DELETED,
			    SearchFields_Ticket::TICKET_FIRST_WROTE_ID,
			    SearchFields_Ticket::TICKET_LAST_WROTE_ID,
			    SearchFields_Ticket::TICKET_FIRST_MESSAGE_ID,
			    SearchFields_Ticket::TICKET_FIRST_WROTE,
			    SearchFields_Ticket::TICKET_FIRST_WROTE_SPAM,
			    SearchFields_Ticket::TICKET_FIRST_WROTE_NONSPAM,
			    SearchFields_Ticket::TICKET_LAST_WROTE,
			    SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID,
			    SearchFields_Ticket::TICKET_CREATED_DATE,
			    SearchFields_Ticket::TICKET_UPDATED_DATE,
			    SearchFields_Ticket::TICKET_DUE_DATE,
			    SearchFields_Ticket::TICKET_SPAM_TRAINING,
			    SearchFields_Ticket::TICKET_SPAM_SCORE,
//			    SearchFields_Ticket::TICKET_TASKS,
			    SearchFields_Ticket::TICKET_INTERESTING_WORDS,
			    SearchFields_Ticket::TICKET_NEXT_ACTION,
			    SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			    SearchFields_Ticket::TICKET_LAST_WORKER_ID,
			    SearchFields_Ticket::TICKET_NEXT_WORKER_ID,
			    SearchFields_Ticket::TEAM_ID,
			    SearchFields_Ticket::TEAM_NAME,
			    SearchFields_Ticket::TICKET_CATEGORY_ID,
			    SearchFields_Ticket::TICKET_SLA_ID,
			    SearchFields_Ticket::TICKET_SLA_PRIORITY
//			    SearchFields_Ticket::CATEGORY_NAME
			);

		$join_sql = 
			"FROM ticket t ".
			"INNER JOIN team tm ON (tm.id = t.team_id) ".
//			"LEFT JOIN category cat ON (cat.id = t.category_id) ". // [TODO] Remove this and use a hash // [TODO] Optimization
			"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
			"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) ".
			// [JAS]: Dynamic table joins
			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id) " : " ").
			(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
			(isset($tables['msg']) || isset($tables['mc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
			(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " "). // [TODO] Choose between first message and all?
			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=msg.id) " : " ")
			;
			
		// Org joins
		if(isset($tables['o'])) {
			$select_sql .= ", o.name as o_name ";
			$join_sql .= "LEFT JOIN contact_org o ON (a1.contact_org_id=o.id) ";
		}
			
		// Custom field joins
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		
		foreach($tables as $tbl_name => $null) {
			if(substr($tbl_name,0,3)!="cf_")
				continue;
				
			if(0 != ($cf_id = intval(substr($tbl_name,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					unset($tables[$tbl_name]);
					continue;
				}
				
				$select_sql .= sprintf(", cf_%d.field_value as cf_%d ",
					$cf_id,
					$cf_id
				);
				
				$join_sql .= sprintf("LEFT JOIN custom_field_value cf_%d ON ('%s' = cf_%d.source_extension AND t.id=cf_%d.source_id AND cf_%d.field_id=%d) ",
					$cf_id,
					ChCustomFieldSource_Ticket::ID,
					$cf_id,
					$cf_id,
					$cf_id,
					$cf_id
				);
			}
		}
			
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sql = $select_sql . $join_sql . $where_sql .
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "");

		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[SearchFields_Ticket::TICKET_ID]);
			$results[$ticket_id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		if($withCounts) {
			$sort_sql = "SELECT count(*) " . $join_sql . $where_sql;
		    $total = $db->GetOne($sort_sql);
		}
		
		return array($results,$total);
    }	
	
};

class SearchFields_Ticket implements IDevblocksSearchFields {
	// Ticket
	const TICKET_ID = 't_id';
	const TICKET_MASK = 't_mask';
	const TICKET_WAITING = 't_is_waiting';
	const TICKET_CLOSED = 't_is_closed';
	const TICKET_DELETED = 't_is_deleted';
	const TICKET_SUBJECT = 't_subject';
	const TICKET_FIRST_MESSAGE_ID = 't_first_message_id';
	const TICKET_FIRST_WROTE_ID = 't_first_wrote_address_id';
	const TICKET_FIRST_WROTE = 't_first_wrote';
	const TICKET_FIRST_WROTE_SPAM = 't_first_wrote_spam';
	const TICKET_FIRST_WROTE_NONSPAM = 't_first_wrote_nonspam';
	const TICKET_FIRST_CONTACT_ORG_ID = 't_first_contact_org_id';
	const TICKET_LAST_WROTE_ID = 't_last_wrote_address_id';
	const TICKET_LAST_WROTE = 't_last_wrote';
	const TICKET_CREATED_DATE = 't_created_date';
	const TICKET_UPDATED_DATE = 't_updated_date';
	const TICKET_DUE_DATE = 't_due_date';
	const TICKET_UNLOCK_DATE = 't_unlock_date';
	const TICKET_SPAM_SCORE = 't_spam_score';
	const TICKET_SPAM_TRAINING = 't_spam_training';
	const TICKET_INTERESTING_WORDS = 't_interesting_words';
	const TICKET_NEXT_ACTION = 't_next_action';
	const TICKET_LAST_ACTION_CODE = 't_last_action_code';
	const TICKET_LAST_WORKER_ID = 't_last_worker_id';
	const TICKET_NEXT_WORKER_ID = 't_next_worker_id';
	const TICKET_CATEGORY_ID = 't_category_id';
	const TICKET_SLA_ID = 't_sla_id';
	const TICKET_SLA_PRIORITY = 't_sla_priority';
	
	// Message
//	const MESSAGE_CONTENT = 'msg_content';
	
	const TICKET_MESSAGE_HEADER = 'mh_header_name';
    const TICKET_MESSAGE_HEADER_VALUE = 'mh_header_value';	

	const TICKET_MESSAGE_CONTENT = 'mc_content';
    
	// Sender
	const SENDER_ADDRESS = 'a1_address';
	
	// Requester
	const REQUESTER_ID = 'ra_id';
	const REQUESTER_ADDRESS = 'ra_email';
	
	// Teams
	const TEAM_ID = 'tm_id';
	const TEAM_NAME = 'tm_name';
	
	// Sender Org
	const ORG_NAME = 'o_name';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 't', 'id', null, $translate->_('ticket.id')),
			self::TICKET_MASK => new DevblocksSearchField(self::TICKET_MASK, 't', 'mask', null, $translate->_('ticket.mask')),
			self::TICKET_SUBJECT => new DevblocksSearchField(self::TICKET_SUBJECT, 't', 'subject',null,$translate->_('ticket.subject')),
			
			self::TICKET_FIRST_MESSAGE_ID => new DevblocksSearchField(self::TICKET_FIRST_MESSAGE_ID, 't', 'first_message_id'),
			
			self::TICKET_FIRST_WROTE_ID => new DevblocksSearchField(self::TICKET_FIRST_WROTE_ID, 't', 'first_wrote_address_id'),
			self::TICKET_FIRST_WROTE => new DevblocksSearchField(self::TICKET_FIRST_WROTE, 'a1', 'email',null,$translate->_('ticket.first_wrote')),
			self::TICKET_LAST_WROTE_ID => new DevblocksSearchField(self::TICKET_LAST_WROTE_ID, 't', 'last_wrote_address_id'),
			self::TICKET_LAST_WROTE => new DevblocksSearchField(self::TICKET_LAST_WROTE, 'a2', 'email',null,$translate->_('ticket.last_wrote')),

			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'o', 'name', null, $translate->_('contact_org.name')),
			self::REQUESTER_ADDRESS => new DevblocksSearchField(self::REQUESTER_ADDRESS, 'ra', 'email',null,$translate->_('ticket.requester')),
			
			self::TICKET_MESSAGE_CONTENT => new DevblocksSearchField(self::TICKET_MESSAGE_CONTENT, 'mc', 'content', 'B', $translate->_('message.content')),
			
			self::TEAM_NAME => new DevblocksSearchField(self::TEAM_NAME,'tm','name',null,$translate->_('common.group')),
			self::TICKET_CATEGORY_ID => new DevblocksSearchField(self::TICKET_CATEGORY_ID, 't', 'category_id',null,$translate->_('common.bucket')),
			self::TICKET_CREATED_DATE => new DevblocksSearchField(self::TICKET_CREATED_DATE, 't', 'created_date',null,$translate->_('ticket.created')),
			self::TICKET_UPDATED_DATE => new DevblocksSearchField(self::TICKET_UPDATED_DATE, 't', 'updated_date',null,$translate->_('ticket.updated')),
			self::TICKET_WAITING => new DevblocksSearchField(self::TICKET_WAITING, 't', 'is_waiting',null,$translate->_('status.waiting')),
			self::TICKET_CLOSED => new DevblocksSearchField(self::TICKET_CLOSED, 't', 'is_closed',null,$translate->_('status.closed')),
			self::TICKET_DELETED => new DevblocksSearchField(self::TICKET_DELETED, 't', 'is_deleted',null,$translate->_('status.deleted')),

			self::TICKET_LAST_ACTION_CODE => new DevblocksSearchField(self::TICKET_LAST_ACTION_CODE, 't', 'last_action_code',null,$translate->_('ticket.last_action')),
			self::TICKET_LAST_WORKER_ID => new DevblocksSearchField(self::TICKET_LAST_WORKER_ID, 't', 'last_worker_id',null,$translate->_('ticket.last_worker')),
			self::TICKET_NEXT_ACTION => new DevblocksSearchField(self::TICKET_NEXT_ACTION, 't', 'next_action',null,$translate->_('ticket.next_action')),
			self::TICKET_NEXT_WORKER_ID => new DevblocksSearchField(self::TICKET_NEXT_WORKER_ID, 't', 'next_worker_id',null,$translate->_('ticket.next_worker')),
			self::TICKET_SPAM_TRAINING => new DevblocksSearchField(self::TICKET_SPAM_TRAINING, 't', 'spam_training',null,$translate->_('ticket.spam_training')),
			self::TICKET_SPAM_SCORE => new DevblocksSearchField(self::TICKET_SPAM_SCORE, 't', 'spam_score',null,$translate->_('ticket.spam_score')),
			self::TICKET_FIRST_WROTE_SPAM => new DevblocksSearchField(self::TICKET_FIRST_WROTE_SPAM, 'a1', 'num_spam',null,$translate->_('address.num_spam')),
			self::TICKET_FIRST_WROTE_NONSPAM => new DevblocksSearchField(self::TICKET_FIRST_WROTE_NONSPAM, 'a1', 'num_nonspam',null,$translate->_('address.num_nonspam')),
			self::TICKET_INTERESTING_WORDS => new DevblocksSearchField(self::TICKET_INTERESTING_WORDS, 't', 'interesting_words',null,$translate->_('ticket.interesting_words')),
			self::TICKET_DUE_DATE => new DevblocksSearchField(self::TICKET_DUE_DATE, 't', 'due_date',null,$translate->_('ticket.due')),
			self::TICKET_UNLOCK_DATE => new DevblocksSearchField(self::TICKET_UNLOCK_DATE, 't', 'unlock_date', null, $translate->_('ticket.unlock_date')),
			self::TICKET_SLA_ID => new DevblocksSearchField(self::TICKET_SLA_ID, 't', 'sla_id',null,$translate->_('sla.name')),
			self::TICKET_SLA_PRIORITY => new DevblocksSearchField(self::TICKET_SLA_PRIORITY, 't', 'sla_priority',null,$translate->_('sla.priority')),
			self::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchField(self::TICKET_FIRST_CONTACT_ORG_ID, 'a1', 'contact_org_id'),
			
			self::REQUESTER_ID => new DevblocksSearchField(self::REQUESTER_ID, 'ra', 'id'),
			
			self::SENDER_ADDRESS => new DevblocksSearchField(self::SENDER_ADDRESS, 'a1', 'email'),
			
			self::TEAM_ID => new DevblocksSearchField(self::TEAM_ID,'tm','id',null,$translate->_('common.group')),
			
			self::TICKET_MESSAGE_HEADER => new DevblocksSearchField(self::TICKET_MESSAGE_HEADER, 'mh', 'header_name'),
			self::TICKET_MESSAGE_HEADER_VALUE => new DevblocksSearchField(self::TICKET_MESSAGE_HEADER_VALUE, 'mh', 'header_value', 'B'),
			
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',null,$field->name);
		}
		
		return $columns;
	}
};

class DAO_ViewRss extends DevblocksORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const HASH = 'hash';
	const WORKER_ID = 'worker_id';
	const CREATED = 'created';
	const SOURCE_EXTENSION = 'source_extension';
	const PARAMS = 'params';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$newId = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO view_rss (id,hash,title,worker_id,created,source_extension,params) ".
			"VALUES (%d,'','',0,0,'','')",
			$newId
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($newId, $fields);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return Model_ViewRss[]
	 */
	static function getList($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,hash,title,worker_id,created,source_extension,params ".
			"FROM view_rss ".
			(!empty($ids) ? sprintf("WHERE id IN (%s)",implode(',',$ids)) : " ").
		"";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return self::_getObjectsFromResults($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $hash
	 * @return Model_ViewRss
	 */
	static function getByHash($hash) {
		if(empty($hash)) return array();
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,hash,title,worker_id,created,source_extension,params ".
			"FROM view_rss ".
			"WHERE hash = %s",
				$db->qstr($hash)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$objects = self::_getObjectsFromResults($rs);
		
		if(empty($objects))
			return null;
		
		return array_shift($objects);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $worker_id
	 * @return Model_ViewRss[]
	 */
	static function getByWorker($worker_id) {
		if(empty($worker_id)) return array();
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,hash,title,worker_id,created,source_extension,params ".
			"FROM view_rss ".
			"WHERE worker_id = %d",
				$worker_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$objects = self::_getObjectsFromResults($rs);
		
		return $objects;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param ADORecordSet $rs
	 * @return Model_ViewRss[]
	 */
	private static function _getObjectsFromResults($rs) { /* @var $rs ADORecordSet */
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_ViewRss();
			$object->id = intval($rs->fields['id']);
			$object->title = $rs->fields['title'];
			$object->hash = $rs->fields['hash'];
			$object->worker_id = intval($rs->fields['worker_id']);
			$object->created = intval($rs->fields['created']);
			$object->source_extension = $rs->fields['source_extension'];
			
			$params = $rs->fields['params'];
			
			if(!empty($params))
				@$object->params = unserialize($params);
			
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}

	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_ViewRss
	 */
	static function getId($id) {
		if(empty($id)) return null;

		$feeds = self::getList($id);
		if(isset($feeds[$id]))
			return $feeds[$id];
		
		return null;
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();

		// [JAS]: Handle our blobs specially
		if(isset($fields[self::PARAMS])) {
			$db->UpdateBlob(
				'view_rss',
				self::PARAMS,
				$fields[self::PARAMS],
				sprintf('id IN (%s)',implode(',',$ids))
			);
			unset($fields[self::PARAMS]);
		}
		
		parent::_update($ids, 'view_rss', $fields);
	}
	
	static function delete($id) {
		if(empty($id)) return;
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM view_rss WHERE id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
};

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class DAO_Group {
    const CACHE_ALL = 'cerberus_cache_teams_all';
	const CACHE_ROSTERS = 'ch_group_rosters';
    
    const TEAM_ID = 'id';
    const TEAM_NAME = 'name';
    const TEAM_SIGNATURE = 'signature';
    
	// Teams
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return CerberusTeam
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
	 * @return CerberusTeam[]
	 */
	static function getTeams($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();

		$teams = array();
		
		$sql = sprintf("SELECT t.id , t.name, t.signature ".
			"FROM team t ".
			((is_array($ids) && !empty($ids)) ? sprintf("WHERE t.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY t.name ASC"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$team = new CerberusTeam();
			$team->id = intval($rs->fields['id']);
			$team->name = $rs->fields['name'];
			$team->signature = $rs->fields['signature'];
			$teams[$team->id] = $team;
			$rs->MoveNext();
		}
		
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
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			if(is_a($rs,'ADORecordSet'))
			while(!$rs->EOF) {
			    $team_id = intval($rs->fields['team_id']);
			    $hits = intval($rs->fields['hits']);
			    
			    if(!isset($team_totals[$team_id])) {
	                $team_totals[$team_id] = array('tickets'=>0);
			    }
			    
			    $team_totals[$team_id]['tickets'] = $hits;
			    $team_totals[0]['tickets'] += $hits;
			        
			    $rs->MoveNext();
			}
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
		
		$sql = sprintf("INSERT INTO team (id, name, signature) ".
			"VALUES (%d,'','')",
			$newId
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::updateTeam($newId, $fields);
		
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
		
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
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 */
	static function deleteTeam($id) {
		if(empty($id)) return;
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM team WHERE id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$sql = sprintf("DELETE QUICK FROM category WHERE team_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// [TODO] DAO_GroupSettings::deleteById();
		$sql = sprintf("DELETE QUICK FROM group_setting WHERE group_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE QUICK FROM worker_to_team WHERE team_id = %d",	$id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$sql = sprintf("DELETE QUICK FROM mail_routing WHERE team_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE QUICK FROM team_routing_rule WHERE team_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        DAO_TeamRoutingRule::deleteByMoveCodes(array('t'.$id));

		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
		$cache->remove(CerberusApplication::CACHE_HELPDESK_FROMS);
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
        
        $db->Replace(
            'worker_to_team',
            array('agent_id' => $worker_id, 'team_id' => $team_id, 'is_manager' => ($is_manager?1:0)),
            array('agent_id','team_id')
        );
        
		// Invalidate caches
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ROSTERS);
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
		
		// Invalidate caches
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ROSTERS);
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
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			$objects = array();
			
			if(is_a($rs,'ADORecordSet'))
			while(!$rs->EOF) {
				$agent_id = intval($rs->fields['agent_id']); 
				$team_id = intval($rs->fields['team_id']); 
				$is_manager = intval($rs->fields['is_manager']);
				
				if(!isset($objects[$team_id]))
					$objects[$team_id] = array();
				
				$member = new Model_TeamMember();
				$member->id = $agent_id;
				$member->team_id = $team_id;
				$member->is_manager = $is_manager;
				$objects[$team_id][$agent_id] = $member;
				
				$rs->MoveNext();
			}
			
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
	
};

class DAO_GroupSettings {
	const CACHE_ALL = 'ch_group_settings';
	
    const SETTING_REPLY_FROM = 'reply_from';
    const SETTING_REPLY_PERSONAL = 'reply_personal';
    const SETTING_REPLY_PERSONAL_WITH_WORKER = 'reply_personal_with_worker';
    const SETTING_SUBJECT_HAS_MASK = 'subject_has_mask';
    const SETTING_SUBJECT_PREFIX = 'subject_prefix';
    const SETTING_SPAM_THRESHOLD = 'group_spam_threshold';
    const SETTING_SPAM_ACTION = 'group_spam_action';
    const SETTING_SPAM_ACTION_PARAM = 'group_spam_action_param';
    const SETTING_AUTO_REPLY = 'auto_reply';
    const SETTING_AUTO_REPLY_ENABLED = 'auto_reply_enabled';
    const SETTING_CLOSE_REPLY = 'close_reply';
    const SETTING_CLOSE_REPLY_ENABLED = 'close_reply_enabled';
    
	static function set($group_id, $key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		$result = $db->Replace(
		    'group_setting',
		    array(
		        'group_id'=>$group_id,
		        'setting'=>$db->qstr($key),
		        'value'=>$db->qstr($value) // BlobEncode/UpdateBlob?
		    ),
		    array('group_id','setting'),
		    false
		);

		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
		
		// Nuke our sender cache
		if($key==self::SETTING_REPLY_FROM) {
			$cache->remove(CerberusApplication::CACHE_HELPDESK_FROMS);
		}
	}
	
	static function get($group_id, $key, $default=null) {
		$value = false;
		
		if(null !== ($group = self::getSettings($group_id)) && isset($group[$key])) {
			$value = $group[$key];
		}
		
		if(false == $value && !is_null($default)) {
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
			$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			if(is_a($rs,'ADORecordSet'))
			while(!$rs->EOF) {
			    $gid = intval($rs->fields['group_id']);
			    
			    if(!isset($groups[$gid]))
			        $groups[$gid] = array();
			    
			    $groups[$gid][$rs->Fields('setting')] = $rs->Fields('value');
				$rs->MoveNext();
			}
			
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

class DAO_KbArticle extends DevblocksORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const UPDATED = 'updated';
	const VIEWS = 'views';
	const FORMAT = 'format';
	const CONTENT = 'content';
	const CONTENT_RAW = 'content_raw';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('kb_seq');
		
		$sql = sprintf("INSERT INTO kb_article (id,title,views,updated,format,content,content_raw) ".
			"VALUES (%d,'',0,%d,0,'','')",
			$id,
			time()
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];

		return null;
	}

	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, title, views, updated, format, content, content_raw ".
			"FROM kb_article ".
			(!empty($where)?sprintf("WHERE %s ",$where):" ").
			"ORDER BY updated DESC "
			;
		$rs = $db->Execute($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param ADORecordSet $rs
	 */
	static private function _createObjectsFromResultSet(ADORecordSet $rs=null) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_KbArticle();
			$object->id = intval($rs->fields['id']);
			$object->title = $rs->fields['title'];
			$object->updated = $rs->fields['updated'];
			$object->views = $rs->fields['views'];
			$object->format = $rs->fields['format'];
			$object->content = $rs->fields['content'];
			$object->content_raw = $rs->fields['content_raw'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}

	static function update($ids, $fields) {
		if(!is_array($ids)) $ids = array($ids);
		parent::_update($ids, 'kb_article', $fields);
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_string = implode(',', $ids);
		
//		// [TODO] Refactor out
//		// CloudGlue removes tags from nuked articles
//		$code_rs = $db->Execute(sprintf("SELECT DISTINCT code AS code FROM kb_article WHERE id IN (%s)", $id_string));
//		while(!$code_rs->EOF) {
//			$code = $code_rs->fields['code'];
//			DAO_CloudGlue::deleteContentIds(UmKbApp::TAG_INDEX_PREFIX.$code, $ids);
//			$code_rs->MoveNext();
//		}

		// Articles
		$db->Execute(sprintf("DELETE QUICK FROM kb_article WHERE id IN (%s)", $id_string));
		
		// Categories
		$db->Execute(sprintf("DELETE QUICK FROM kb_article_to_category WHERE kb_article_id IN (%s)", $id_string));
	}

	static function getCategoriesByArticleId($article_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($article_id))
			return array();
		
		$categories = array();
		
		$rs = $db->Execute(sprintf("SELECT kb_category_id ". 
			"FROM kb_article_to_category ".
			"WHERE kb_article_id = %d",
			$article_id
		));
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$cat_id = intval($rs->fields['kb_category_id']);
			$categories[$cat_id] = $cat_id;
			$rs->MoveNext();
		}
		
		return $categories;
	}
	
	static function setCategories($article_id,$category_ids,$replace=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($category_ids))
			$category_ids = array($category_ids);
		
		if($replace) {
			$db->Execute(sprintf("DELETE QUICK FROM kb_article_to_category WHERE kb_article_id = %d",
				$article_id
			));
		}
		
		$categories = DAO_KbCategory::getWhere();
		
		if(is_array($category_ids) && !empty($category_ids)) {
			foreach($category_ids as $category_id) {
				$pid = $category_id;
				while($pid) {
					$top_category_id = $pid;
					$pid = $categories[$pid]->parent_id;
				}
				
				$db->Replace(
					'kb_article_to_category',
					array('kb_article_id'=>$article_id,'kb_category_id'=>$category_id,'kb_top_category_id'=>$top_category_id),
					array('kb_article_id','kb_category_id'),
					false
				);
			}
		}
		
		return TRUE;
	}
	
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, array(), SearchFields_KbArticle::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"kb.id as %s, ".
			"kb.title as %s, ".
			"kb.updated as %s, ".
			"kb.views as %s, ".
			"kb.format as %s, ".
			"kb.content as %s ".
			"FROM kb_article kb ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_KbArticle::ID,
			    SearchFields_KbArticle::TITLE,
			    SearchFields_KbArticle::UPDATED,
			    SearchFields_KbArticle::VIEWS,
			    SearchFields_KbArticle::FORMAT,
			    SearchFields_KbArticle::CONTENT
			).
			
			// [JAS]: Dynamic table joins
			(isset($tables['katc']) ? "LEFT JOIN kb_article_to_category katc ON (kb.id=katc.kb_article_id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_KbArticle::ID]);
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

class SearchFields_KbArticle implements IDevblocksSearchFields {
	// Table
	const ID = 'kb_id';
	const TITLE = 'kb_title';
	const UPDATED = 'kb_updated';
	const VIEWS = 'kb_views';
	const FORMAT = 'kb_format';
	const CONTENT = 'kb_content';
	
	const CATEGORY_ID = 'katc_category_id';
	const TOP_CATEGORY_ID = 'katc_top_category_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'kb', 'id', null, $translate->_('kb_article.id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'kb', 'title', null, $translate->_('kb_article.title')),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'kb', 'updated', null, $translate->_('kb_article.updated')),
			self::VIEWS => new DevblocksSearchField(self::VIEWS, 'kb', 'views', null, $translate->_('kb_article.views')),
			self::FORMAT => new DevblocksSearchField(self::FORMAT, 'kb', 'format', null, $translate->_('kb_article.format')),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'kb', 'content', null, $translate->_('kb_article.content')),
			
			self::CATEGORY_ID => new DevblocksSearchField(self::CATEGORY_ID, 'katc', 'kb_category_id'),
			self::TOP_CATEGORY_ID => new DevblocksSearchField(self::TOP_CATEGORY_ID, 'katc', 'kb_top_category_id', null, $translate->_('kb_article.topic')),
		);
	}
};	

class DAO_KbCategory extends DevblocksORMHelper {
	const ID = 'id';
	const PARENT_ID = 'parent_id';
	const NAME = 'name';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO kb_category (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'kb_category', $fields);
	}
	
	static function getTreeMap() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$categories = self::getWhere();
		$tree = array();

		// Fake recursion
		foreach($categories as $cat_id => $cat) {
			$pid = $cat->parent_id;
			if(!isset($tree[$pid])) {
				$tree[$pid] = array();
			}
				
			$tree[$pid][$cat_id] = 0;
		}
		
		// Add counts (and bubble up)
		$sql = "SELECT count(*) AS hits, kb_category_id FROM kb_article_to_category GROUP BY kb_category_id";
		$rs = $db->Execute($sql);
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$count_cat = intval($rs->fields['kb_category_id']);
			$count_hits = intval($rs->fields['hits']);
			
			$pid = $count_cat;
			while($pid) {
				@$parent_id = $categories[$pid]->parent_id;
				$tree[$parent_id][$pid] += $count_hits;
				$pid = $parent_id;
			}
			
			$rs->MoveNext();
		}
		
		// [TODO] Filter out empty categories on public
		
		return $tree;
	}
	
	static function getTree($root=0) {
		$levels = array();
		$map = self::getTreeMap();
		
		self::_recurseTree($levels,$map,$root);
		
		return $levels;
	}
	
	// [TODO] Move to Model_KbCategoryTree?
	static private function _recurseTree(&$levels,$map,$node=0,$level=-1) {
		if(!isset($map[$node]) || empty($map[$node]))
			return;

		$level++; // we're dropping down a node

		// recurse through children
		foreach($map[$node] as $pid => $children) {
			$levels[$pid] = $level;
			self::_recurseTree($levels,$map,$pid,$level);
		}
	}
	
	static public function getDescendents($root_id) {
		$tree = self::getTree($root_id);
		@$ids = array_merge(array($root_id),array_keys($tree));
		return $ids;
	}
	
	/**
	 * @param string $where
	 * @return Model_KbCategory[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, parent_id, name ".
			"FROM kb_category ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY name asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_KbCategory	 */
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
	 * @param ADORecordSet $rs
	 * @return Model_KbCategory[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_KbCategory();
			$object->id = $rs->fields['id'];
			$object->parent_id = $rs->fields['parent_id'];
			$object->name = $rs->fields['name'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM kb_category WHERE id IN (%s)", $ids_list));

		$db->Execute(sprintf("DELETE QUICK FROM kb_article_to_category WHERE kb_category_id IN (%s)", $ids_list));
		
		return true;
	}

};

class DAO_Bucket extends DevblocksORMHelper {
	const CACHE_ALL = 'cerberus_cache_buckets_all';
	
    const ID = 'id';
    const NAME = 'name';
    const TEAM_ID = 'team_id';
    const RESPONSE_HRS = 'response_hrs';
    
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
	
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($buckets = $cache->load(self::CACHE_ALL))) {
    	    $buckets = self::getList();
    	    $cache->save($buckets, self::CACHE_ALL);
	    }
	    
	    return $buckets;
	}
	
	static function getList($ids=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT tc.id, tc.name, tc.team_id, tc.response_hrs ".
			"FROM category tc ".
			"INNER JOIN team t ON (tc.team_id=t.id) ".
			(!empty($ids) ? sprintf("WHERE tc.id IN (%s) ", implode(',', $ids)) : "").
			"ORDER BY t.name ASC, tc.name ASC "
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$categories = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$category = new CerberusCategory();
			$category->id = intval($rs->Fields('id'));
			$category->name = $rs->Fields('name');
			$category->team_id = intval($rs->Fields('team_id'));
			$category->response_hrs = intval($rs->Fields('response_hrs'));
			$categories[$category->id] = $category;
			$rs->MoveNext();
		}
		
		return $categories;
	}
	
	static function getByTeam($team_id) {
		$team_buckets = array();
		
		$buckets = self::getAll();
		foreach($buckets as $bucket) {
			if($team_id==$bucket->team_id) {
				$team_buckets[$bucket->id] = $bucket;
			}
		}
		return $team_buckets;
	}
	
	static function create($name,$team_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$buckets = self::getAll();
		$duplicate = false;
		foreach($buckets as $bucket) {
			if($name==$bucket->name && $team_id==$bucket->team_id) {
				$duplicate = true;
				$id = $bucket->id;
				break;
			}
		}

		if(!$duplicate) {
			$id = $db->GenID('generic_seq');
			
			$sql = sprintf("INSERT INTO category (id,name,team_id,response_hrs) ".
				"VALUES (%d,%s,%d,0)",
				$id,
				$db->qstr($name),
				$team_id
			);

			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

			$cache = DevblocksPlatform::getCacheService();
			$cache->remove(self::CACHE_ALL);
		}
		
		return $id;
	}
	
	static function update($id,$fields) {
		parent::_update($id,'category',$fields);

		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM category WHERE id IN (%s)", implode(',',$ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// Reset any tickets using this category
		$sql = sprintf("UPDATE ticket SET category_id = 0 WHERE category_id IN (%s)", implode(',',$ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		// Team Routing Rules
		foreach($ids as $id) {
			DAO_TeamRoutingRule::deleteByMoveCodes(array('c'.$id));
		}
		
		// Clear any view's move counts involving this bucket for all workers
		// [TODO] This is pretty hacky.
		DAO_WorkerPref::clearMoveCounts($ids);
		
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	/**
	 * Returns an array of category ticket counts, indexed by category id.
	 *
	 * @param array $ids Team IDs to summarize
	 * @return array
	 */
	static function getCategoryCountsByTeam($team_id) {
		$db = DevblocksPlatform::getDatabaseService();

		$cat_totals = array('total' => 0);

		if(empty($team_id)) return $cat_totals;
		
		$sql = sprintf("SELECT count(*) as hits, t.category_id, t.team_id ".
		    "FROM ticket t ".
		    "WHERE t.team_id = %d ".
		    "AND t.is_closed = 0 ".
		    "GROUP BY t.category_id, t.team_id ",
		    $team_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
		    $cat_id = intval($rs->fields['category_id']);
		    $team_id = intval($rs->fields['team_id']);
		    $hits = intval($rs->fields['hits']);
		    
		    $cat_totals[$cat_id] = intval($hits);
		    
		    // Non-inbox
		    if($cat_id) {
		        $cat_totals['total'] += $hits;
		    }
		        
		    $rs->MoveNext();
		}
		
		return $cat_totals;
	}	
	
};

class DAO_Mail {
	const ROUTING_ID = 'id';
	const ROUTING_PATTERN = 'pattern';
	const ROUTING_TEAM_ID = 'team_id';
	const ROUTING_POS = 'pos';
	
	static function getMailboxRouting() {
		$db = DevblocksPlatform::getDatabaseService();
		$routing = array();
		
		$sql = "SELECT mr.id, mr.pattern, mr.team_id, mr.pos ".
			"FROM mail_routing mr ".
			"ORDER BY mr.pos ";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$route = new Model_MailRoute();
			$route->id = intval($rs->fields['id']);
			$route->pattern = $rs->fields['pattern'];
			$route->team_id = intval($rs->fields['team_id']);
			$route->pos = intval($rs->Fields('pos'));
			$routing[$route->id] = $route;
			$rs->MoveNext();
		}
		
		return $routing;
	}
	
	static function createMailboxRouting($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		// Move everything down one position in priority
		$sql = "UPDATE mail_routing SET pos=pos+1";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// Insert at top
		$sql = sprintf("INSERT INTO mail_routing (id,pattern,team_id,pos) ".
			"VALUES (%d,%s,%d,%d)",
			$id,
			$db->qstr(''),
			0,
			0
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::updateMailboxRouting($id, $fields);
		
		return $id;
	}
	
	static function updateMailboxRouting($id, $fields) {
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
			
		$sql = sprintf("UPDATE mail_routing SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function deleteMailboxRouting($id) {
		$db = DevblocksPlatform::getDatabaseService();
		if(empty($id)) return;
		
		$sql = sprintf("DELETE QUICK FROM mail_routing WHERE id = %d",
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function getTokenizedText($ticket_id, $source_text) {
		// TODO: actually implement this function...
		return $source_text;
	}

	// Pop3 Accounts
	
	static function createPop3Account($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$newId = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO pop3_account (id, enabled, nickname, host, username, password) ".
			"VALUES (%d,0,'','','','')",
			$newId
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::updatePop3Account($newId, $fields);
		
		return $newId;
	}
	
	static function getPop3Accounts($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		$pop3accounts = array();
		
		$sql = "SELECT id, enabled, nickname, protocol, host, username, password, port ".
			"FROM pop3_account ".
			((!empty($ids) ? sprintf("WHERE id IN (%s)", implode(',', $ids)) : " ").
			"ORDER BY nickname "
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$pop3 = new CerberusPop3Account();
			$pop3->id = intval($rs->fields['id']);
			$pop3->enabled = intval($rs->fields['enabled']);
			$pop3->nickname = $rs->fields['nickname'];
			$pop3->protocol = $rs->fields['protocol'];
			$pop3->host = $rs->fields['host'];
			$pop3->username = $rs->fields['username'];
			$pop3->password = $rs->fields['password'];
			$pop3->port = intval($rs->fields['port']);
			$pop3accounts[$pop3->id] = $pop3;
			$rs->MoveNext();
		}
		
		return $pop3accounts;		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return CerberusPop3Account
	 */
	static function getPop3Account($id) {
		$accounts = DAO_Mail::getPop3Accounts(array($id));
		
		if(isset($accounts[$id]))
			return $accounts[$id];
			
		return null;
	}
	
	static function updatePop3Account($id, $fields) {
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
			
		$sql = sprintf("UPDATE pop3_account SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function deletePop3Account($id) {
		if(empty($id))
			return;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM pop3_account WHERE id = %d",
			$id			
		);
		
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
};

class DAO_Community extends DevblocksORMHelper {
    const ID = 'id';
    const NAME = 'name';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO community (id,name) ".
		    "VALUES (%d,'')",
		    $id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'community', $fields);
	}
	
	public static function get($id) {
		$items = self::getList(array($id));
		
		if(isset($items[$id]))
		    return $items[$id];
		    
		return NULL;
	}
	
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name ".
			"FROM community ".
			(!empty($where)?sprintf("WHERE %s ",$where):" ").
			"ORDER BY name "
			;
		$rs = $db->Execute($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	public static function getList($ids=array()) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,name ".
		    "FROM community ".
		    (!empty($ids) ? sprintf("WHERE id IN (%s) ", implode(',', $ids)) : " ").
		    "ORDER BY name ASC "
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		return self::_createObjectsFromResultSet($rs);
	}
	
	private static function _createObjectsFromResultSet($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
		    $object = new Model_Community();
		    $object->id = intval($rs->fields['id']);
		    $object->name = $rs->fields['name'];
		    $objects[$object->id] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    $id_list = implode(',', $ids);
	    
	    $sql = sprintf("DELETE QUICK FROM community WHERE id IN (%s)", $id_list);
	    $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

	    // Community Tools
		$tools = DAO_CommunityTool::getWhere(sprintf("%s IN (%s)",
			DAO_CommunityTool::COMMUNITY_ID,
			$id_list
		));
		DAO_CommunityTool::delete(array_keys($tools));
	    
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

        list($tables,$wheres) = parent::_parseSearchParams($params, array(), SearchFields_Community::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.title as %s ".
			"FROM community c ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_Community::ID,
			    SearchFields_Community::NAME
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[SearchFields_Community::ID]);
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

class SearchFields_Community implements IDevblocksSearchFields {
	// Table
	const ID = 'c_id';
	const NAME = 'c_name';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'c', 'id'),
			self::NAME => new DevblocksSearchField(self::NAME, 'c', 'name'),
		);
	}
};	

class DAO_WorkerWorkspaceList extends DevblocksORMHelper {
	const ID = 'id';
	const WORKER_ID = 'worker_id';
	const WORKSPACE = 'workspace';
	const LIST_VIEW = 'list_view';
	const LIST_POS = 'list_pos';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($fields))
			return NULL;
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO worker_workspace_list (id, worker_id, workspace, list_view, list_pos) ".
			"VALUES (%d, 0, '', '',0)",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());

		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_WorkerWorkspaceList
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
	 * Enter description here...
	 *
	 * @param string $where
	 * @return Model_WorkerWorkspaceList[]
	 */
	static function getWhere($where) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, worker_id, workspace, list_view, list_pos ".
			"FROM worker_workspace_list ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : " ").
			"ORDER BY list_pos ASC";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_WorkerWorkspaceList();
			$object->id = intval($rs->fields['id']);
			$object->worker_id = intval($rs->fields['worker_id']);
			$object->workspace = $rs->fields['workspace'];
			$object->list_pos = intval($rs->fields['list_pos']);
			
			$list_view = $rs->fields['list_view'];
			if(!empty($list_view)) {
				@$object->list_view = unserialize($list_view);
			}
			
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function getWorkspaces($worker_id = 0) {
		$workspaces = array();
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT DISTINCT workspace AS workspace ".
			"FROM worker_workspace_list ".
			(!empty($worker_id) ? sprintf("WHERE worker_id = %d ",$worker_id) : " ").
			"ORDER BY workspace";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$workspaces[] = $rs->fields['workspace'];
			$rs->MoveNext();
		}
		
		return $workspaces;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'worker_workspace_list', $fields);
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM worker_workspace_list WHERE id IN (%s)", $ids_list)) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
};

class DAO_WorkerPref extends DevblocksORMHelper {
    const SETTING_MOVE_COUNTS = 'move_counts';
    const SETTING_OVERVIEW = 'worker_overview';
    const SETTING_OVERVIEW_FILTER = 'worker_overview_filter';
    const SETTING_OVERVIEW_ASSIGN_TYPE = 'overview_assign_type';
    const SETTING_OVERVIEW_ASSIGN_HOWMANY = 'overview_assign_howmany';
    
    const CACHE_PREFIX = 'ch_workerpref_';
    
	static function set($worker_id, $key, $value) {
		// Persist long-term
		$db = DevblocksPlatform::getDatabaseService();
		$result = $db->Replace(
		    'worker_pref',
		    array(
		        'worker_id'=>$worker_id,
		        'setting'=>$db->qstr($key),
		        'value'=>$db->qstr($value) // BlobEncode/UpdateBlob?
		    ),
		    array('worker_id','setting'),
		    false
		);
		
		// Invalidate cache
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_PREFIX.$worker_id);
	}
	
	static function get($worker_id, $key, $default=null) {
		$value = null;
		
		if(null !== ($worker_prefs = self::getByWorker($worker_id))) {
			if(isset($worker_prefs[$key])) {
				$value = $worker_prefs[$key];
			}
		}
		
		if(null === $value && !is_null($default)) {
		    return $default;
		}
		
		return $value;
	}

	static function getByWorker($worker_id) {
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_PREFIX.$worker_id))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = sprintf("SELECT setting, value FROM worker_pref WHERE worker_id = %d", $worker_id);
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			$objects = array();
			
			if(is_a($rs,'ADORecordSet'))
			while(!$rs->EOF) {
			    $objects[$rs->fields['setting']] = $rs->fields['value'];
			    $rs->MoveNext();
			}
			
			$cache->save($objects, self::CACHE_PREFIX.$worker_id);
		}
		
		return $objects;
	}
	
	// Clear any view's move counts for all workers involving the buckets specified
	// [TODO] This could probably be done much better
	static function clearMoveCounts($bucket_ids) {
		if(!is_array($bucket_ids)) $bucket_ids = array($bucket_ids);
		
		$workers = DAO_Worker::getAll();
		
		// Loop through all workers
		foreach($workers as $worker_id => $worker) {
			// Load their prefs
			if(null !== ($worker_prefs = DAO_WorkerPref::getByWorker($worker_id))) {
				// See if they have a move counts pref
				@$move_counts =& $worker_prefs[DAO_WorkerPref::SETTING_MOVE_COUNTS];
				if(!empty($move_counts) && false !== ($move_counts = @unserialize($move_counts))) {
					// Remove the deleted buckets
					foreach($bucket_ids as $id) {
						unset($move_counts['c'.$id]);
					}
					self::set($worker_id, DAO_WorkerPref::SETTING_MOVE_COUNTS, serialize($move_counts));
				}
			}
		}
	}
};

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
	 * @param ADORecordSet $rs
	 * @return Model_Note[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_Note();
			$object->id = $rs->fields['id'];
			$object->source_extension_id = $rs->fields['source_extension_id'];
			$object->source_id = $rs->fields['source_id'];
			$object->created = $rs->fields['created'];
			$object->worker_id = $rs->fields['worker_id'];
			$object->content = $rs->fields['content'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
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

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),SearchFields_Note::getFields());
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
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_Note::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = "SELECT count(*) " . $join_sql . $where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
    }	
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
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
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'n', 'id'),
			self::SOURCE_EXT_ID => new DevblocksSearchField(self::SOURCE_EXT_ID, 'n', 'source_extension_id'),
			self::SOURCE_ID => new DevblocksSearchField(self::SOURCE_ID, 'n', 'source_id'),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'n', 'created'),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'n', 'worker_id'),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'n', 'content'),
		);
	}
};

class DAO_PreParseRule extends DevblocksORMHelper {
	const CACHE_ALL = 'cerberus_cache_preparse_rules_all';
	
	const ID = 'id';
	const NAME = 'name';
	const CRITERIA_SER = 'criteria_ser';
	const ACTIONS_SER = 'actions_ser';
	const POS = 'pos';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO preparse_rule (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'preparse_rule', $fields);

		self::clearCache();
	}
	
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($rules = $cache->load(self::CACHE_ALL))) {
    	    $rules = self::getWhere();
    	    $cache->save($rules, self::CACHE_ALL);
	    }
	    
	    return $rules;
	}
	
	/**
	 * @param string $where
	 * @return Model_PreParseRule[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, criteria_ser, actions_ser, pos ".
			"FROM preparse_rule ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY pos desc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_PreParseRule	 */
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
	 * Increment the number of times we've matched this filter
	 *
	 * @param integer $id
	 */
	static function increment($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("UPDATE preparse_rule SET pos = pos + 1 WHERE id = %d",
			$id
		));
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_PreParseRule[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_PreParseRule();
			$object->id = $rs->fields['id'];
			$object->name = $rs->fields['name'];
			$object->criteria = !empty($rs->fields['criteria_ser']) ? @unserialize($rs->fields['criteria_ser']) : array();
			$object->actions = !empty($rs->fields['actions_ser']) ? @unserialize($rs->fields['actions_ser']) : array();
			$object->pos = $rs->fields['pos'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM preparse_rule WHERE id IN (%s)", $ids_list));

		self::clearCache();
		
		return true;
	}

	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
};

class DAO_TeamRoutingRule extends DevblocksORMHelper {
    const ID = 'id';
    const NAME = 'name';
    const TEAM_ID = 'team_id';
	const CRITERIA_SER = 'criteria_ser';
    const POS = 'pos';
    const DO_STATUS = 'do_status';
    const DO_SPAM = 'do_spam';
    const DO_MOVE = 'do_move';
    const DO_ASSIGN = 'do_assign';
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO team_routing_rule (id,name,created,team_id,criteria_ser,pos,do_spam,do_status,do_move,do_assign) ".
		    "VALUES (%d,'',%d,0,'',0,'','','',0)",
		    $id,
		    time()
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function increment($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("UPDATE team_routing_rule SET pos = pos + 1 WHERE id = %d",
			$id
		));
	}
	
	public static function update($id, $fields) {
        self::_update($id, 'team_routing_rule', $fields);
	}
	
	public static function get($id) {
		$items = self::getList(array($id));
		
		if(isset($items[$id]))
		    return $items[$id];
		    
		return NULL;
	}
	
	public static function getByTeamId($team_id) {
	    if(empty($team_id)) return array();
	    
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id, name, team_id, criteria_ser, pos, do_spam, do_status, do_move, do_assign ".
		    "FROM team_routing_rule ".
		    "WHERE team_id = %d ".
		    "ORDER BY pos DESC", // subject first + from last, by popularity
		    $team_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return self::_getResultsAsModel($rs);
	}
	
    /**
     * @return Model_TeamRoutingRule[]
     */
	public static function getList($ids=array()) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, team_id, criteria_ser, pos, do_spam, do_status, do_move, do_assign ".
		    "FROM team_routing_rule ".
		    (!empty($ids) ? sprintf("WHERE id IN (%s) ", implode(',', $ids)) : " ").
		    "ORDER BY pos DESC" // subject first + from last, by popularity
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return self::_getResultsAsModel($rs);
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_TeamRoutingRule[]
	 */
	private static function _getResultsAsModel($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
		    $object = new Model_TeamRoutingRule();
		    $object->id = intval($rs->fields['id']);
		    $object->name = $rs->fields['name'];
		    $object->team_id = intval($rs->fields['team_id']);
		    $object->pos = intval($rs->fields['pos']);
		    $object->do_spam = $rs->fields['do_spam'];
            $object->do_status = $rs->fields['do_status'];
            $object->do_move = $rs->fields['do_move'];
            $object->do_assign = $rs->fields['do_assign'];

            // Criteria
		    $criteria_ser = $rs->fields['criteria_ser'];
		    if(!empty($criteria_ser))
		    	@$criteria = unserialize($criteria_ser);
		    if(is_array($criteria))
		    	$object->criteria = $criteria;
            
		    $objects[$object->id] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function deleteByMoveCodes($codes) {
	    if(!is_array($codes)) $codes = array($codes);
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    $code_list = implode("','", $codes);
	    
	    $sql = sprintf("UPDATE team_routing_rule SET do_move = '' WHERE do_move IN ('%s')", $code_list);
	    $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	public static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    $id_list = implode(',', $ids);
	    
	    $sql = sprintf("DELETE QUICK FROM team_routing_rule WHERE id IN (%s)", $id_list);
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

        list($tables,$wheres) = parent::_parseSearchParams($params, array(), SearchFields_TeamRoutingRule::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"trr.id as %s, ".
			"trr.team_id as %s, ".
			"trr.pos as %s ".
			"FROM team_routing_rule trr ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_TeamRoutingRule::ID,
			    SearchFields_TeamRoutingRule::TEAM_ID,
			    SearchFields_TeamRoutingRule::POS
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$row_id = intval($rs->fields[SearchFields_TeamRoutingRule::ID]);
			$results[$row_id] = $result;
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

class SearchFields_TeamRoutingRule implements IDevblocksSearchFields {
	// Table
	const ID = 'trr_id';
	const TEAM_ID = 'trr_team_id';
	const POS = 'trr_pos';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		return array(
			SearchFields_TeamRoutingRule::ID => new DevblocksSearchField(SearchFields_TeamRoutingRule::ID, 'trr', 'id'),
			SearchFields_TeamRoutingRule::TEAM_ID => new DevblocksSearchField(SearchFields_TeamRoutingRule::TEAM_ID, 'trr', 'team_id'),
			SearchFields_TeamRoutingRule::POS => new DevblocksSearchField(SearchFields_TeamRoutingRule::POS, 'trr', 'pos'),
		);
	}
};	

class DAO_FnrQuery extends DevblocksORMHelper {
	const ID = 'id';
	const QUERY = 'query';
	const CREATED = 'created';
	const SOURCE = 'source';
	const NO_MATCH = 'no_match';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('fnr_query_seq');
		
		$sql = sprintf("INSERT INTO fnr_query (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'fnr_query', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_FnrQuery[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, query, created, source, no_match ".
			"FROM fnr_query ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_FnrQuery	 */
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
	 * @param ADORecordSet $rs
	 * @return Model_FnrQuery[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_FnrQuery();
			$object->id = $rs->fields['id'];
			$object->query = $rs->fields['query'];
			$object->created = $rs->fields['created'];
			$object->source = $rs->fields['source'];
			$object->no_match = $rs->fields['no_match'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_list = implode(',', $ids);
		$db->Execute(sprintf("DELETE QUICK FROM fnr_query WHERE id IN (%s)",$id_list));
	}

};

class DAO_FnrTopic extends DevblocksORMHelper {
	const _TABLE = 'fnr_topic';
	
	const ID = 'id';
	const NAME = 'name';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO %s (id,name) ".
			"VALUES (%d,'')",
			self::_TABLE,
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($ids, $fields) {
		parent::_update($ids, self::_TABLE, $fields);
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();

		$ids_string = implode(',', $ids);
		
		$sql = sprintf("DELETE QUICK FROM fnr_topic WHERE id IN (%s)", $ids_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$sql = sprintf("DELETE QUICK FROM fnr_external_resource WHERE topic_id IN (%s)", $ids_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
	
	public function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id, name ".
			"FROM %s ".
			(!empty($where) ? ("WHERE $where ") : " ").
			" ORDER BY name ",
			self::_TABLE
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		return self::_createObjectsFromResultSet($rs);
	}
	
	public static function get($id) {
		$objects = self::getWhere(sprintf("id = %d", $id));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	public static function _createObjectsFromResultSet(ADORecordSet $rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_FnrTopic();
			$object->id = intval($rs->fields['id']);
			$object->name = $rs->fields['name'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
};

class DAO_FnrExternalResource extends DevblocksORMHelper {
	const _TABLE = 'fnr_external_resource';
	
	const ID = 'id';
	const NAME = 'name';
	const URL = 'url';
	const TOPIC_ID = 'topic_id';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO %s (id,name,url,topic_id) ".
			"VALUES (%d,'','',0)",
			self::_TABLE,
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($ids, $fields) {
		parent::_update($ids, self::_TABLE, $fields);
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM %s WHERE id IN (%s)",
			self::_TABLE,
			implode(',', $ids)
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	public function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id, name, url, topic_id ".
			"FROM %s ".
			(!empty($where) ? ("WHERE $where ") : " ").
			" ORDER BY name ",
			self::_TABLE
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		return self::_createObjectsFromResultSet($rs);
	}
	
	public static function get($id) {
		$objects = self::getWhere(sprintf("id = %d", $id));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	public static function _createObjectsFromResultSet(ADORecordSet $rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_FnrTopic();
			$object->id = intval($rs->fields['id']);
			$object->name = $rs->fields['name'];
			$object->topic_id = intval($rs->fields['topic_id']);
			$object->url = $rs->fields['url'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
};

class DAO_MailTemplate extends DevblocksORMHelper {
	const _TABLE = 'mail_template';
	
	const ID = 'id';
	const TITLE = 'title';
	const DESCRIPTION = 'description';
	const FOLDER = 'folder';
	const TEMPLATE_TYPE = 'template_type';
	const OWNER_ID = 'owner_id';
	const CONTENT = 'content';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO %s (id,title,description,folder,template_type,owner_id,content) ".
			"VALUES (%d,'','','',0,0,'')",
			self::_TABLE,
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return array
	 */
	public static function getFolders($type=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$folders = array();
		
		$sql = sprintf("SELECT DISTINCT folder FROM %s %s ORDER BY folder",
			self::_TABLE,
			(!empty($type) ? sprintf("WHERE %s = %d ",self::TEMPLATE_TYPE,$type) : " ")
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$folders[] = $rs->fields['folder'];
			$rs->MoveNext();
		}
		
		return $folders;
	}
	
	public static function update($ids, $fields) {
		// [TODO] Overload CONTENT as BlobUpdate
		parent::_update($ids, self::_TABLE, $fields);
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM %s WHERE id IN (%s)",
			self::_TABLE,
			implode(',', $ids)
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	public function getByType($type) {
		return self::getWhere(sprintf("%s = %d",
			self::TEMPLATE_TYPE,
			$type
		));
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $where
	 * @return Model_MailTemplate[]
	 */
	public function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,title,description,folder,template_type,owner_id,content ".
			"FROM %s ".
			(!empty($where) ? ("WHERE $where ") : " ").
			" ORDER BY folder, title ",
			self::_TABLE
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_MailTemplate
	 */
	public static function get($id) {
		$objects = self::getWhere(sprintf("id = %d", $id));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	public static function _createObjectsFromResultSet(ADORecordSet $rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_MailTemplate();
			$object->id = intval($rs->fields['id']);
			$object->title = $rs->fields['title'];
			$object->description = $rs->fields['description'];
			$object->folder = $rs->fields['folder'];
			$object->template_type = intval($rs->fields['template_type']);
			$object->owner_id = intval($rs->fields['owner_id']);
			$object->content = $rs->fields['content'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
};

class DAO_TicketComment extends DevblocksORMHelper {
	const ID = 'id';
	const TICKET_ID = 'ticket_id';
	const ADDRESS_ID = 'address_id';
	const CREATED = 'created';
	const COMMENT = 'comment';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('ticket_comment_seq');
		
		$sql = sprintf("INSERT INTO ticket_comment (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'ticket_comment', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_TicketComment[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, ticket_id, address_id, created, comment ".
			"FROM ticket_comment ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY created asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	static function getByTicketId($id) {
		return self::getWhere(sprintf("%s = %d",
			self::TICKET_ID,
			$id
		));
	}
	
	static function getCountByTicketId($id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(id) FROM ticket_comment WHERE ticket_id = %d",
			$id
		);
		return $db->GetOne($sql);
	}

	/**
	 * @param integer $id
	 * @return Model_TicketComment	 */
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
	 * @param ADORecordSet $rs
	 * @return Model_TicketComment[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_TicketComment();
			$object->id = $rs->fields['id'];
			$object->ticket_id = $rs->fields['ticket_id'];
			$object->address_id = $rs->fields['address_id'];
			$object->created = $rs->fields['created'];
			$object->comment = $rs->fields['comment'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM ticket_comment WHERE id IN (%s)", $ids_list));
		
		return true;
	}

};

class DAO_CustomField extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const TYPE = 'type';
	const GROUP_ID = 'group_id';
	const SOURCE_EXTENSION = 'source_extension';
	const POS = 'pos';
	const OPTIONS = 'options';
	
	const CACHE_ALL = 'ch_customfields'; 
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('custom_field_seq');
		
		$sql = sprintf("INSERT INTO custom_field (id,name,type,source_extension,group_id,pos,options) ".
			"VALUES (%d,'','','',0,0,'')",
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'custom_field', $fields);
		
		// Invalidate cache on changes
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_CustomField|null
	 */
	static function get($id) {
		$fields = self::getAll();
		
		if(isset($fields[$id]))
			return $fields[$id];
			
		return null;
	}
	
	static function getBySourceAndGroupId($source_ext_id, $group_id) {
		$fields = self::getAll();

		// Filter out groups that don't match
		foreach($fields as $field_id => $field) { /* @var $field Model_CustomField */
			if($group_id != $field->group_id || $source_ext_id != $field->source_extension) {
				unset($fields[$field_id]);
			}
		}
		
		return $fields;
	}
	
	static function getBySource($source_ext_id) {
		$fields = self::getAll();
		
		// Filter fields to only the requested source
		foreach($fields as $idx => $field) { /* @var $field Model_CustomField */
			if(0 != strcasecmp($field->source_extension, $source_ext_id))
				unset($fields[$idx]);
		}
		
		return $fields;
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = "SELECT id, name, type, source_extension, group_id, pos, options ".
				"FROM custom_field ".
				"ORDER BY group_id ASC, pos ASC "
			;
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			$objects = self::_createObjectsFromResultSet($rs);
			
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}
	
	private static function _createObjectsFromResultSet(ADORecordSet $rs) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$objects = array();
		
		if($rs instanceof ADORecordSet)
		while(!$rs->EOF) {
			$object = new Model_CustomField();
			$object->id = intval($rs->fields['id']);
			$object->name = $rs->fields['name'];
			$object->type = $rs->fields['type'];
			$object->source_extension = $rs->fields['source_extension'];
			$object->group_id = intval($rs->fields['group_id']);
			$object->pos = intval($rs->fields['pos']);
			$object->options = DevblocksPlatform::parseCrlfString($rs->fields['options']);
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_string = implode(',', $ids);
		
		$sql = sprintf("DELETE QUICK FROM custom_field WHERE id IN (%s)",$id_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		// [TODO] Nuke options
		if(is_array($ids))
		foreach($ids as $id) {
			DAO_CustomFieldValue::deleteByFieldId($id);
		}
		
		// Invalidate cache on changes
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class DAO_CustomFieldValue extends DevblocksORMHelper {
	const FIELD_ID = 'field_id';
	const SOURCE_EXTENSION = 'source_extension';
	const SOURCE_ID = 'source_id';
	const FIELD_VALUE = 'field_value';
	
	public static function setFieldValue($source_ext_id, $source_id, $field_id, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Replace(
			'custom_field_value',
			array(
				self::FIELD_ID => $field_id,
				self::SOURCE_EXTENSION => $db->qstr($source_ext_id),
				self::SOURCE_ID => $source_id,
				self::FIELD_VALUE => $db->qstr($value),
			),
			array(
				self::SOURCE_ID,
				self::SOURCE_EXTENSION,
				self::FIELD_ID
			),
			false
		);
	}
	
	public static function unsetFieldValue($source_ext_id, $source_id, $field_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM custom_field_value WHERE source_extension = '%s' AND source_id = %d AND field_id = %d",
			$source_ext_id,
			$source_id,
			$field_id
		);
		
		return $db->Execute($sql);
	}
	
	public static function handleFormPost($source_ext_id, $source_id, $field_ids) {
		$fields = DAO_CustomField::getBySource($source_ext_id);
		
		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
			
			@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');

			switch($fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
					if(!empty($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_DROPDOWN:
					if(!empty($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
					$set = !empty($field_value) ? 1 : 0;
					DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $set);
					break;
					
				case Model_CustomField::TYPE_DATE:
					@$date = strtotime($field_value);
					if(!empty($date)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $date);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
			}
		}
		
		return true;
	}
	
	public static function getValuesBySourceIds($source_ext_id, $source_ids) {
		if(!is_array($source_ids)) $source_ids = array($source_ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$results = array();
		
		if(empty($source_ids))
			return array();
		
		$sql = sprintf("SELECT source_id, field_id, field_value ".
			"FROM custom_field_value ".
			"WHERE source_extension = '%s' AND source_id IN (%s)",
			$source_ext_id,
			implode(',', $source_ids)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$source_id = intval($rs->fields['source_id']);
			$field_id = intval($rs->fields['field_id']);
			$field_value = $rs->fields['field_value'];
			
			if(!isset($results[$source_id]))
				$results[$source_id] = array();
				
			$source =& $results[$source_id];
			$source[$field_id] = $field_value;
						
			$rs->MoveNext();
		}
		
		return $results;
	}
	
	public static function getValueCountBySourceId($source_ext_id, $source_id, $group_id=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(v.field_value) ".
			"FROM custom_field_value v ".
			"INNER JOIN custom_field f ON (f.id=v.field_id) ".
			"WHERE v.source_extension = '%s' ". 
			"AND v.source_id = %d ".
			"%s",
			$source_ext_id,
			$source_id,
			(!is_null($group_id) ? sprintf("AND (f.group_id=0 OR f.group_id=%d) ",$group_id) : " ")
		);
		
		return $db->GetOne($sql);
	}
	
	public static function deleteByFieldId($field_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM custom_field_value WHERE field_id = %d",$field_id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
	
//	public static function deleteBySource($source_ext_id, $source_id) {
//		$db = DevblocksPlatform::getDatabaseService();
//		
//		$sql = sprintf("DELETE QUICK FROM custom_field_value WHERE source_extension = '%s' AND source_id = %d",$source_ext_id, $source_id);
//		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
//	}
};

class DAO_Task extends DevblocksORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const WORKER_ID = 'worker_id';
	const PRIORITY = 'priority';
	const DUE_DATE = 'due_date';
	const IS_COMPLETED = 'is_completed';
	const COMPLETED_DATE = 'completed_date';
	const CONTENT = 'content';
	const SOURCE_EXTENSION = 'source_extension';
	const SOURCE_ID = 'source_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('task_seq');
		
		$sql = sprintf("INSERT INTO task (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'task', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('task', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_Task[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, title, worker_id, priority, due_date, content, is_completed, completed_date, source_extension, source_id ".
			"FROM task ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Task	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	static function getUnassignedSourceTotals() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$totals = array();
		
		$sql = "SELECT count(id) as hits, source_extension ".
			"FROM task ".
			"WHERE is_completed = 0 ".
			"GROUP BY source_extension ";
		$rs = $db->Execute($sql);
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$key = !empty($rs->fields['source_extension']) ? $rs->fields['source_extension'] : 'none';
			$totals[$key] = intval($rs->fields['hits']);
			$rs->MoveNext();
		}
		
		return $totals;
	}
	
	static function getAssignedSourceTotals() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$totals = array();
		
		$sql = "SELECT count(id) as hits, worker_id ".
			"FROM task ".
			"WHERE worker_id > 0 ".
			"AND is_completed = 0 ".
			"GROUP BY worker_id ";
		$rs = $db->Execute($sql);
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$totals[$rs->fields['worker_id']] = intval($rs->fields['hits']);
			$rs->MoveNext();
		}
		
		return $totals;
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_Task[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_Task();
			$object->id = $rs->fields['id'];
			$object->title = $rs->fields['title'];
			$object->worker_id = $rs->fields['worker_id'];
			$object->priority = $rs->fields['priority'];
			$object->due_date = $rs->fields['due_date'];
			$object->content = $rs->fields['content'];
			$object->is_completed = $rs->fields['is_completed'];
			$object->completed_date = $rs->fields['completed_date'];
			$object->source_extension = $rs->fields['source_extension'];
			$object->source_id = $rs->fields['source_id'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 */
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		// Tasks
		$db->Execute(sprintf("DELETE QUICK FROM task WHERE id IN (%s)", $ids_list));
		
		return true;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $source
	 * @param array $ids
	 */
	static function deleteBySourceIds($source_extension, $ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		// Tasks
		$db->Execute(sprintf("DELETE QUICK FROM task WHERE source_extension = %s AND source_id IN (%s)",
			$db->qstr($source_extension), 
			$ids_list
		));
		
		return true;
	}

	static function getCountBySourceObjectId($source_extension, $source_id, $include_completed=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(id) ".
			"FROM task ".
			"WHERE source_extension = %s ".
			"AND source_id = %d ".
			(($include_completed) ? " " : "AND is_completed = 0 "),
			$db->qstr($source_extension),
			$source_id
		);
		$total = intval($db->GetOne($sql));
		
		return $total;
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
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, SearchFields_Task::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.due_date as %s, ".
			"t.is_completed as %s, ".
			"t.completed_date as %s, ".
			"t.priority as %s, ".
			"t.title as %s, ".
			"t.content as %s, ".
			"t.worker_id as %s, ".
			"t.source_extension as %s, ".
			"t.source_id as %s ",
//			"o.name as %s ".
			    SearchFields_Task::ID,
			    SearchFields_Task::DUE_DATE,
			    SearchFields_Task::IS_COMPLETED,
			    SearchFields_Task::COMPLETED_DATE,
			    SearchFields_Task::PRIORITY,
			    SearchFields_Task::TITLE,
			    SearchFields_Task::CONTENT,
			    SearchFields_Task::WORKER_ID,
			    SearchFields_Task::SOURCE_EXTENSION,
			    SearchFields_Task::SOURCE_ID
			 );
		
		$join_sql = 
			"FROM task t ";
//			"LEFT JOIN contact_org o ON (o.id=a.contact_org_id) "

			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=a.contact_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		// Custom field joins
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
		
		foreach($tables as $tbl_name => $null) {
			if(substr($tbl_name,0,3)!="cf_")
				continue;
				
			if(0 != ($cf_id = intval(substr($tbl_name,3)))) {
				// Make sure our custom fields still exist
				if(!isset($custom_fields[$cf_id])) {
					unset($tables[$tbl_name]);
					continue;
				}
				
				$select_sql .= sprintf(", cf_%d.field_value as cf_%d ",
					$cf_id,
					$cf_id
				);
				
				$join_sql .= sprintf("LEFT JOIN custom_field_value cf_%d ON ('%s' = cf_%d.source_extension AND t.id=cf_%d.source_id AND cf_%d.field_id=%d) ",
					$cf_id,
					ChCustomFieldSource_Task::ID,
					$cf_id,
					$cf_id,
					$cf_id,
					$cf_id
				);
			}
		}
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sql = $select_sql . $join_sql . $where_sql .  
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "");
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_Task::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = "SELECT count(*) " . $join_sql . $where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
    }	
	
};

class SearchFields_Task implements IDevblocksSearchFields {
	// Task
	const ID = 't_id';
	const DUE_DATE = 't_due_date';
	const IS_COMPLETED = 't_is_completed';
	const COMPLETED_DATE = 't_completed_date';
	const PRIORITY = 't_priority';
	const TITLE = 't_title';
	const CONTENT = 't_content';
	const WORKER_ID = 't_worker_id';
	const SOURCE_EXTENSION = 't_source_extension';
	const SOURCE_ID = 't_source_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 't', 'id', null, $translate->_('task.id')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 't', 'title', null, $translate->_('task.title')),
			self::PRIORITY => new DevblocksSearchField(self::PRIORITY, 't', 'priority', null, $translate->_('task.priority')),
			self::IS_COMPLETED => new DevblocksSearchField(self::IS_COMPLETED, 't', 'is_completed', null, $translate->_('task.is_completed')),
			self::DUE_DATE => new DevblocksSearchField(self::DUE_DATE, 't', 'due_date', null, $translate->_('task.due_date')),
			self::COMPLETED_DATE => new DevblocksSearchField(self::COMPLETED_DATE, 't', 'completed_date', null, $translate->_('task.completed_date')),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 't', 'content', null, $translate->_('task.content')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 't', 'worker_id', null, $translate->_('task.worker_id')),
			self::SOURCE_EXTENSION => new DevblocksSearchField(self::SOURCE_EXTENSION, 't', 'source_extension', null, $translate->_('task.source_extension')),
			self::SOURCE_ID => new DevblocksSearchField(self::SOURCE_ID, 't', 'source_id', null, $translate->_('task.source_id')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',null,$field->name);
		}
		
		return $columns;
	}
};

?>
