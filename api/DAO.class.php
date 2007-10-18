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

class DAO_Setting extends DevblocksORMHelper {
	static function set($key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Replace('setting',array('setting'=>$key,'value'=>$value),array('setting'),true);
	}
	
	static function get($key) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT value FROM setting WHERE setting = %s",
			$db->qstr($key)
		);
		$value = $db->GetOne($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $value;
	}
	
	// [TODO] Cache as static/singleton or load up in a page scope object?
	static function getSettings() {
	    $cache = DevblocksPlatform::getCacheService();
	    if(false === ($settings = $cache->load(CerberusApplication::CACHE_SETTINGS_DAO))) {
			$db = DevblocksPlatform::getDatabaseService();
			$settings = array();
			
			$sql = sprintf("SELECT setting,value FROM setting");
			$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			while(!$rs->EOF) {
				$settings[$rs->Fields('setting')] = $rs->Fields('value');
				$rs->MoveNext();
			}
			
			$cache->save($settings, CerberusApplication::CACHE_SETTINGS_DAO);
	    }
		
		return $settings;
	}
};

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

// [TODO] Add a cached ::getAll()
class DAO_Worker extends DevblocksORMHelper {
	private function DAO_Worker() {}
	
	const ID = 'id';
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const TITLE = 'title';
	const EMAIL = 'email';
	const PASSWORD = 'pass';
	const IS_SUPERUSER = 'is_superuser';
	const CAN_DELETE = 'can_delete';
	const LAST_ACTIVITY_DATE = 'last_activity_date';
	const LAST_ACTIVITY = 'last_activity';
	
	// [TODO] Convert to ::create($id, $fields)
	static function create($email, $password, $first_name, $last_name, $title) {
		if(empty($email) || empty($password))
			return null;
			
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO worker (id, email, pass, first_name, last_name, title, is_superuser, can_delete) ".
			"VALUES (%d, %s, %s, %s, %s, %s,0,0)",
			$id,
			$db->qstr($email),
			$db->qstr(md5($password)),
			$db->qstr($first_name),
			$db->qstr($last_name),
			$db->qstr($title)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
	static function getList($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		$workers = array();
		
		$sql = "SELECT a.id, a.first_name, a.last_name, a.email, a.title, a.is_superuser, a.can_delete, a.last_activity_date, a.last_activity ".
			"FROM worker a ".
			((!empty($ids) ? sprintf("WHERE a.id IN (%s)",implode(',',$ids)) : " ").
			"ORDER BY a.last_name, a.first_name "
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$worker = new CerberusWorker();
			$worker->id = intval($rs->fields['id']);
			$worker->first_name = $rs->fields['first_name'];
			$worker->last_name = $rs->fields['last_name'];
			$worker->email = $rs->fields['email'];
			$worker->title = $rs->fields['title'];
			$worker->is_superuser = intval($rs->fields['is_superuser']);
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
		
		$agents = DAO_Worker::getList(array($id));
		
		if(isset($agents[$id]))
			return $agents[$id];
			
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
		
		if(!$rs->EOF) {
			return intval($rs->fields['id']);
		}
		
		return null;		
	}
	
	static function updateAgent($id, $fields) {
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
		
	}
	
	static function deleteAgent($id) {
		if(empty($id)) return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM worker WHERE id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM worker_to_team WHERE agent_id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$sql = sprintf("DELETE FROM ticket_rss WHERE worker_id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		// [TODO] Cascade using DAO_WorkerWorkspaceList::delete
		$sql = sprintf("DELETE FROM worker_workspace_list WHERE worker_id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function login($email, $password) {
		$db = DevblocksPlatform::getDatabaseService();

		// [TODO] Uniquely salt hashes
		$sql = sprintf("SELECT id ".
			"FROM worker ".
			"WHERE email = %s ".
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

		$sql = sprintf("DELETE FROM worker_to_team WHERE agent_id = %d",
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
	}
	
	/**
	 * @return Model_TeamMember[]
	 */
	static function getGroupMemberships($agent_id) {
		if(empty($agent_id)) return;
		$db = DevblocksPlatform::getDatabaseService();
		$ids = array();
		
		$sql = sprintf("SELECT wt.team_id, wt.is_manager ".
			"FROM worker_to_team wt ".
			"INNER JOIN team t ON (wt.team_id=t.id) ".
			"WHERE wt.agent_id = %d ".
			"ORDER BY t.name ASC ",
			$agent_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$groups = array();
		
		while(!$rs->EOF) {
			$team_id = intval($rs->fields['team_id']); 
			$is_manager = intval($rs->fields['is_manager']);
			
			$member = new Model_TeamMember();
			$member->id = $agent_id;
			$member->team_id = $team_id;
			$member->is_manager = $is_manager;
			$groups[$team_id] = $member;
			
			$rs->MoveNext();
		}
		
		return $groups;
	}
	
	// [TODO] Test where this is used
	static function searchAgents($query, $limit=10) {
		$db = DevblocksPlatform::getDatabaseService();
		if(empty($query)) return null;
		
		$sql = sprintf("SELECT w.id FROM worker w WHERE w.email LIKE '%s%%' LIMIT 0,%d",
			$query,
			$limit
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$ids = array();
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
			
		return DAO_Worker::getList($ids);
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
	    ));
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

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_Worker::getFields());
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

class DAO_ContactOrg extends DevblocksORMHelper {
	const ID = 'id';
	const ACCOUNT_NUMBER = 'account_number';
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
	
	private function __construct() {}
	
	public static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			'id' => $translate->_('contact_org.id'),
			'account_number' => $translate->_('contact_org.account_number'),
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
		
		$sql = sprintf("INSERT INTO contact_org (id,account_number,name,street,city,province,postal,country,phone,fax,website,created) ".
  			"VALUES (%d,'','','','','','','','','','',%d)",
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
		
		$sql = sprintf("DELETE FROM contact_org WHERE id IN (%s)",
			$id_list
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		// Clear any associated addresses
		$sql = sprintf("UPDATE address SET contact_org_id = 0 WHERE contact_org_id IN (%s)",
			$id_list
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	/**
	 * @param string $where
	 * @return Model_ContactOrg[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,account_number,name,street,city,province,postal,country,phone,fax,website,created ".
			"FROM contact_org ".
			(!empty($where) ? sprintf("WHERE %s ", $where) : " ")
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		return self::_getObjectsFromResultSet($rs);
	}
	
	static private function _getObjectsFromResultSet($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_ContactOrg();
			$object->id = intval($rs->fields['id']);
			$object->account_number = $rs->fields['account_number'];
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
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_ContactOrg::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$sql = sprintf("SELECT ".
			"c.id as %s, ".
			"c.account_number as %s, ".
			"c.name as %s, ".
			"c.street as %s, ".
			"c.city as %s, ".
			"c.province as %s, ".
			"c.postal as %s, ".
			"c.country as %s, ".
			"c.phone as %s, ".
			"c.fax as %s, ".
			"c.website as %s, ".
			"c.created as %s ".
			"FROM contact_org c ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_ContactOrg::ID,
			    SearchFields_ContactOrg::ACCOUNT_NUMBER,
			    SearchFields_ContactOrg::NAME,
			    SearchFields_ContactOrg::STREET,
			    SearchFields_ContactOrg::CITY,
			    SearchFields_ContactOrg::PROVINCE,
			    SearchFields_ContactOrg::POSTAL,
			    SearchFields_ContactOrg::COUNTRY,
			    SearchFields_ContactOrg::PHONE,
			    SearchFields_ContactOrg::FAX,
			    SearchFields_ContactOrg::WEBSITE,
			    SearchFields_ContactOrg::CREATED
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
	const ACCOUNT_NUMBER = 'c_account_number';
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
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'c', 'id', null, $translate->_('contact_org.id')),
			self::ACCOUNT_NUMBER => new DevblocksSearchField(self::ACCOUNT_NUMBER, 'c', 'account_number', null, $translate->_('contact_org.account_number')),
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
		);
	}
};

class DAO_Address extends DevblocksORMHelper {
	const ID = 'id';
	const EMAIL = 'email';	
	const FIRST_NAME = 'first_name';	
	const LAST_NAME = 'last_name';	
	const CONTACT_ORG_ID = 'contact_org_id';	

	private function __construct() {}
	
	public static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			'id' => $translate->_('address.id'),
			'email' => $translate->_('address.email'),
			'first_name' => $translate->_('address.first_name'),
			'last_name' => $translate->_('address.last_name'),
			'contact_org_id' => $translate->_('address.contact_org_id'),
		);
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('address_seq');

		if(null == ($email = @$fields[self::EMAIL]))
			return NULL;
		
		// [TODO] Validate
		$addresses = imap_rfc822_parse_adrlist('<'.$email.'>', 'host');
		
		if(!is_array($addresses) || empty($addresses))
			return NULL;
		
		$address = array_shift($addresses);
		
		if(empty($address->host) || $address->host == 'host')
			return NULL;
		
		$sql = sprintf("INSERT INTO address (id,email,first_name,last_name,contact_org_id) ".
			"VALUES (%d,%s,'','',0)",
			$id,
			$db->qstr(trim(strtolower($address->mailbox.'@'.$address->host)))
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'address', $fields);
	}
	
    static function delete($ids) {
        if(!is_array($ids)) $ids = array($ids);
        if(empty($ids)) return;

		$db = DevblocksPlatform::getDatabaseService();
        
        $address_ids = implode(',', $ids);
        
        $sql = sprintf("DELETE FROM address WHERE id IN (%s)", $address_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        DAO_AddressAuth::delete($ids);
    }
		
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$addresses = array();
		
		$sql = sprintf("SELECT a.id, a.email, a.first_name, a.last_name, a.contact_org_id ".
			"FROM address a ".
			((!empty($where)) ? "WHERE %s " : " ").
			"ORDER BY a.email ",
			$where
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$address = new Model_Address();
			$address->id = intval($rs->fields['id']);
			$address->email = $rs->fields['email'];
			$address->first_name = $rs->fields['first_name'];
			$address->last_name = $rs->fields['last_name'];
			$address->contact_org_id = intval($rs->fields['contact_org_id']);
			$addresses[$address->id] = $address;
			$rs->MoveNext();
		}
		
		return $addresses;
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
		$id = null;
		
		$sql = sprintf("SELECT id FROM address WHERE email = %s",
			$db->qstr(trim(strtolower($email)))
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$id = $rs->fields['id'];
		} elseif($create_if_null) {
			$fields = array(
				self::EMAIL => $email
			);
			$id = DAO_Address::create($fields);
		}
		
		return $id;
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

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_Address::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"a.id as %s, ".
			"a.email as %s, ".
			"a.first_name as %s, ".
			"a.last_name as %s, ".
			"a.contact_org_id as %s, ".
			"o.name as %s ".
			"FROM address a ".
			"LEFT JOIN contact_org o ON (o.id=a.contact_org_id) ",
			    SearchFields_Address::ID,
			    SearchFields_Address::EMAIL,
			    SearchFields_Address::FIRST_NAME,
			    SearchFields_Address::LAST_NAME,
			    SearchFields_Address::CONTACT_ORG_ID,
			    SearchFields_Address::ORG_NAME
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=a.contact_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").
			
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
			$id = intval($rs->fields[SearchFields_Address::ID]);
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

class SearchFields_Address implements IDevblocksSearchFields {
	// Address
	const ID = 'a_id';
	const EMAIL = 'a_email';
	const FIRST_NAME = 'a_first_name';
	const LAST_NAME = 'a_last_name';
	const CONTACT_ORG_ID = 'a_contact_org_id';
	
	const ORG_NAME = 'o_name';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		return array(
			self::ID => new DevblocksSearchField(self::ID, 'a', 'id', null, $translate->_('address.id')),
			self::EMAIL => new DevblocksSearchField(self::EMAIL, 'a', 'email', null, $translate->_('address.email')),
			self::FIRST_NAME => new DevblocksSearchField(self::FIRST_NAME, 'a', 'first_name', null, $translate->_('address.first_name')),
			self::LAST_NAME => new DevblocksSearchField(self::LAST_NAME, 'a', 'last_name', null, $translate->_('address.last_name')),
			self::CONTACT_ORG_ID => new DevblocksSearchField(self::CONTACT_ORG_ID, 'a', 'contact_org_id', null, $translate->_('address.contact_org_id')),
			
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'o', 'name', null, $translate->_('contact_org.name')),
		);
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
        $sql = sprintf("DELETE FROM address_auth WHERE address_id IN (%s)", $address_ids);
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
			
		$sql = sprintf("DELETE FROM address_to_worker WHERE address = %s",
			$db->qstr($address)
		);
		$db->Execute($sql);
	}
	
	static function unassignAll($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($worker_id))
			return NULL;
			
		$sql = sprintf("DELETE FROM address_to_worker WHERE worker_id = %d",
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
    const IS_ADMIN = 'is_admin';
    const MESSAGE_TYPE = 'message_type';
    const CREATED_DATE = 'created_date';
    const ADDRESS_ID = 'address_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$newId = $db->GenID('message_seq');
		
		$sql = sprintf("INSERT INTO message (id,ticket_id,message_type,created_date,address_id) ".
			"VALUES (%d,0,'',0,0)",
			$newId
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($newId, $fields);
		
		return $newId;
	}
    
    static function update($id, $fields) {
        parent::_update($id, 'message', $fields);
    }
    
    static function delete($ids) {
        if(!is_array($ids)) $ids = array($ids);
        if(empty($ids)) return;

		$db = DevblocksPlatform::getDatabaseService();
        
        $message_ids = implode(',', $ids);
        $sql = sprintf("DELETE FROM message WHERE id IN (%s)", $message_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        // Content
        $sql = sprintf("DELETE FROM message_content WHERE message_id IN (%s)", $message_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        // Headers
        $sql = sprintf("DELETE FROM message_header WHERE message_id IN (%s)", $message_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        // Notes
        $sql = sprintf("DELETE FROM message_note WHERE message_id IN (%s)", $message_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
        
        // Attachments
        $sql = sprintf("DELETE FROM attachment WHERE message_id IN (%s)", $message_ids);
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
    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_Message::getFields());
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
    const MESSAGE_ID = 'message_id';
    const WORKER_ID = 'worker_id';
    const CREATED = 'created';
    const CONTENT = 'content';

    static function create($fields) {
    	$db = DevblocksPlatform::getDatabaseService();
    	
    	$id = $db->GenID('message_note_seq');
    	
    	$sql = sprintf("INSERT INTO message_note (id,message_id,worker_id,created,content) ".
    		"VALUES (%d,0,0,%d,'')",
    		$id,
    		time()
    	);
    	$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

    	self::update($id, $fields);
    }

    static function getByMessageId($message_id) {
    	$db = DevblocksPlatform::getDatabaseService();
    	
    	$sql = sprintf("SELECT id,message_id,worker_id,created,content ".
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
    	
    	$sql = sprintf("SELECT n.id,n.message_id,n.worker_id,n.created,n.content ".
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
    	
    	$sql = sprintf("SELECT n.id,n.message_id,n.worker_id,n.created,n.content ".
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
    	
    	while(!$rs->EOF) {
    		$object = new Model_MessageNote();
    		$object->id = intval($rs->fields['id']);
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
        $sql = sprintf("DELETE FROM message_note WHERE id IN (%s)", $message_ids);
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
                self::CONTENT => '',
            ),
            array('message_id'),
            true
        );
        
        if(!empty($content))
            $db->UpdateBlob('message_content', self::CONTENT, $content, 'message_id='.$message_id);
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
		
		if(!$rs->EOF) {
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
                self::HEADER_NAME => $header,
                self::HEADER_VALUE => ''
            ),
            array('message_id','header_name'),
            true
        );
        
        $db->UpdateBlob('message_header', self::HEADER_VALUE, $value, 'message_id='.$message_id.' AND header_name='.$db->qstr($header));
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
            $headers[$rs->fields['header_name']] = DAO_MessageHeader::_decodeHeader($rs->fields['header_value']);
            $rs->MoveNext();
        }
        
        return $headers;
    }
    
    static function getUnique() {
        $db = DevblocksPlatform::getDatabaseService();
        $headers = array();
        
        $sql = "SELECT header_name FROM message_header GROUP BY header_name";
        $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        while(!$rs->EOF) {
            $headers[] = $rs->fields['header_name'];
            $rs->MoveNext();
        }
        
        sort($headers);
        
        return $headers;
    }
    
    /**
     * DDH: stolen from PEAR (BSD license)  I found a bug in a different part of
     * 		their header parsing while looking for this, but it appears to work
     * 		cleanly.  Dunno how much of a time hit this might be.
     * 
     * Given a header, this function will decode it
     * according to RFC2047. Probably not *exactly*
     * conformant, but it does pass all the given
     * examples (in RFC2047).
     *
     * @param string Input header value to decode
     * @return string Decoded header value
     * @access public
     */
    static function _decodeHeader($input)
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);

        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {

            $encoded  = $matches[1];
            $charset  = $matches[2];
            $encoding = $matches[3];
            $text     = $matches[4];

            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }

            $input = str_replace($encoded, $text, $input);
        }

        return $input;
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
		
		while(!$rs->EOF) {
		    $object = new Model_Attachment();
		    $object->id = intval($rs->fields['id']);
		    $object->message_id = intval($rs->fields['id']);
		    $object->display_name = $rs->fields['display_name'];
		    $object->filepath = $rs->fields['filepath'];
		    $object->mime_type = $rs->fields['mime_type'];
		    $object->file_size = intval($rs->fields['file_size']);
		    $objects[$object->id] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    $id_list = implode(',', $ids);
	    
	    $sql = sprintf("DELETE FROM attachment WHERE id IN (%s)", $id_list);
	    $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

	    // [TODO] cascade foreign key constraints	
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
	const SPAM_TRAINING = 'spam_training';
	const SPAM_SCORE = 'spam_score';
	const INTERESTING_WORDS = 'interesting_words';
	const NEXT_ACTION = 'next_action';
	const LAST_ACTION_CODE = 'last_action_code';
	const LAST_WORKER_ID = 'last_worker_id';
	const NEXT_WORKER_ID = 'next_worker_id';
	
	private function DAO_Ticket() {}
	
	/**
	 * Enter description here...
	 *
	 * @param string $mask
	 * @return CerberusTicket
	 */
	static function getTicketIdByMask($mask) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT t.id FROM ticket t WHERE t.mask = %s",
			$db->qstr($mask)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			return intval($rs->fields['id']);
//			return DAO_Ticket::getTicket($ticket_id);
		}
		
		return null;
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
	 * returns an array of Model_Attachment that
	 * correspond to the supplied message id.
	 *
	 * @param integer $id
	 * @return Model_Attachment[]
	 */
	static function getAttachmentsByMessage($id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT a.id, a.message_id, a.display_name, a.filepath, a.file_size, a.mime_type ".
			"FROM attachment a WHERE a.message_id = %d",
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$attachments = array();
		while(!$rs->EOF) {
			$attachment = new Model_Attachment();
			$attachment->id = intval($rs->fields['id']);
			$attachment->message_id = intval($rs->fields['message_id']);
			$attachment->display_name = $rs->fields['display_name'];
			$attachment->filepath = $rs->fields['filepath'];
			$attachment->file_size = intval($rs->fields['file_size']);
			$attachment->mime_type = $rs->fields['mime_type'];
			$attachments[] = $attachment;
			$rs->MoveNext();
		}

		return $attachments;
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
		
		$sql = sprintf("INSERT INTO ticket (id, mask, subject, first_message_id, last_wrote_address_id, first_wrote_address_id, created_date, updated_date, due_date, team_id, category_id) ".
			"VALUES (%d,'','',0,0,0,%d,%d,0,0,0)",
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

	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 */
	static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
	    if(empty($ids)) return;
	    
        $db = DevblocksPlatform::getDatabaseService();
	    $ticket_ids = implode(',', $ids);

	    // Tickets
	    
        $sql = sprintf("DELETE FROM ticket WHERE id IN (%s)", $ticket_ids);
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        // Requester
        
        $sql = sprintf("DELETE FROM requester WHERE ticket_id IN (%s)", $ticket_ids); 
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
        
        // Messages
        
        do{
	        list($messages, $messages_count) = DAO_Message::search(
	            array(
	                new DevblocksSearchCriteria(SearchFields_Message::TICKET_ID,DevblocksSearchCriteria::OPER_IN,$ids),
	            ),
	            100,
	            0,
	            SearchFields_Message::ID,
	            true,
	            true
	        );
            DAO_Message::delete(array_keys($messages));	        
	
        } while($messages_count);
        
	}
	
	static function merge($ids=array()) {
		if(!is_array($ids) || empty($ids) || count($ids) < 2) {
			return false;
		}
		
		$db = DevblocksPlatform::getDatabaseService();
			
		list($tickets, $null) = self::search(
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
			
			$sql = sprintf("UPDATE message SET ticket_id = %d WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			$sql = sprintf("UPDATE message_header SET ticket_id = %d WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			// [TODO] This will probably complain about some dupe constraints
			$sql = sprintf("UPDATE requester SET ticket_id = %d WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			self::delete($merge_ticket_ids);
			
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
		
		$sql = "SELECT t.id , t.mask, t.subject, t.is_closed, t.is_deleted, t.team_id, t.category_id, t.first_message_id, ".
			"t.first_wrote_address_id, t.last_wrote_address_id, t.created_date, t.updated_date, t.due_date, t.spam_training, ". 
			"t.spam_score, t.interesting_words, t.next_action, t.last_worker_id, t.next_worker_id ".
			"FROM ticket t ".
			(!empty($ids) ? sprintf("WHERE t.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY t.updated_date DESC"
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ticket = new CerberusTicket();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = DAO_MessageHeader::_decodeHeader($rs->fields['subject']);
			$ticket->first_message_id = intval($rs->fields['first_message_id']);
			$ticket->team_id = intval($rs->fields['team_id']);
			$ticket->category_id = intval($rs->fields['category_id']);
			$ticket->is_closed = intval($rs->fields['is_closed']);
			$ticket->is_deleted = intval($rs->fields['is_deleted']);
			$ticket->last_wrote_address_id = intval($rs->fields['last_wrote_address_id']);
			$ticket->first_wrote_address_id = intval($rs->fields['first_wrote_address_id']);
			$ticket->created_date = intval($rs->fields['created_date']);
			$ticket->updated_date = intval($rs->fields['updated_date']);
			$ticket->due_date = intval($rs->fields['due_date']);
			$ticket->spam_score = floatval($rs->fields['spam_score']);
			$ticket->spam_training = $rs->fields['spam_training'];
			$ticket->interesting_words = $rs->fields['interesting_words'];
			$ticket->next_action = $rs->fields['next_action'];
			$ticket->last_worker_id = intval($rs->fields['last_worker_id']);
			$ticket->next_worker_id = intval($rs->fields['next_worker_id']);
			$tickets[$ticket->id] = $ticket;
			$rs->MoveNext();
		}
		
		return $tickets;
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('ticket', $fields, $where);
	}
	
	static function updateTicket($id,$fields) {
        parent::_update($id,'ticket',$fields);
	}
	
	/**
	 * @return CerberusMessage[]
	 */
	static function getMessagesByTicket($ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$messages = array();
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.message_type, m.created_date, m.address_id ".
			"FROM message m ".
			"WHERE m.ticket_id = %d ".
			"ORDER BY m.created_date ASC ",
			$ticket_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$message = new CerberusMessage();
			$message->id = intval($rs->fields['id']);
			$message->ticket_id = intval($rs->fields['ticket_id']);
			$message->message_type = $rs->fields['message_type'];
			$message->created_date = intval($rs->fields['created_date']);
			$message->address_id = intval($rs->fields['address_id']);
			
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
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.message_type, m.created_date, m.address_id ".
			"FROM message m ".
			"WHERE m.id = %d ".
			"ORDER BY m.created_date ASC ",
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		if(!$rs->EOF) {
			$message = new CerberusMessage();
			$message->id = intval($rs->fields['id']);
			$message->ticket_id = intval($rs->fields['ticket_id']);
			$message->message_type = $rs->fields['message_type'];
			$message->created_date = intval($rs->fields['created_date']);
			$message->address_id = intval($rs->fields['address_id']);
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

        $sql = sprintf("DELETE FROM requester WHERE ticket_id = %d AND address_id = %d",
            $id,
            $address_id
        );
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function analyze($params, $limit=15, $mode="senders", $mode_param=null) { // or "subjects"
		$db = DevblocksPlatform::getDatabaseService();
		list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_Ticket::getFields());

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
				
				(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$sender_wheres)) : "").
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

    static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		$total = -1;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_Ticket::getFields());
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.mask as %s, ".
			"t.subject as %s, ".
			"t.is_closed as %s, ".
			"t.is_deleted as %s, ".
//			"t.first_wrote_address_id as %s, ".
//			"t.last_wrote_address_id as %s, ".
			"a1.email as %s, ".
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
			"t.category_id as %s ".
//			"cat.name as %s ". // [TODO] BAD LEFT JOINS
			"FROM ticket t ".
			"INNER JOIN team tm ON (tm.id = t.team_id) ".
//			"LEFT JOIN category cat ON (cat.id = t.category_id) ". // [TODO] Remove this and use a hash // [TODO] Optimization
			"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
			"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) ",
			    SearchFields_Ticket::TICKET_ID,
			    SearchFields_Ticket::TICKET_MASK,
			    SearchFields_Ticket::TICKET_SUBJECT,
			    SearchFields_Ticket::TICKET_CLOSED,
			    SearchFields_Ticket::TICKET_DELETED,
//			    SearchFields_Ticket::TICKET_FIRST_WROTE_ID,
//			    SearchFields_Ticket::TICKET_LAST_WROTE_ID,
			    SearchFields_Ticket::TICKET_FIRST_WROTE,
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
			    SearchFields_Ticket::TICKET_CATEGORY_ID
//			    SearchFields_Ticket::CATEGORY_NAME
			).
			
			// [JAS]: Dynamic table joins
			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
			(isset($tables['msg']) || isset($tables['mc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
			(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " "). // [TODO] Choose between first message and all?
			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=msg.id) " : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				// properly display quoted-printable ticket subjects
				if ($f == SearchFields_Ticket::TICKET_SUBJECT) {
					$result[$f] = DAO_MessageHeader::_decodeHeader($v);
				} else {
					$result[$f] = $v;
				}
			}
			$ticket_id = intval($rs->fields[SearchFields_Ticket::TICKET_ID]);
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

class SearchFields_Ticket implements IDevblocksSearchFields {
	// Ticket
	const TICKET_ID = 't_id';
	const TICKET_MASK = 't_mask';
	const TICKET_CLOSED = 't_is_closed';
	const TICKET_DELETED = 't_is_deleted';
	const TICKET_SUBJECT = 't_subject';
	const TICKET_FIRST_WROTE_ID = 't_first_wrote_id';
	const TICKET_FIRST_WROTE = 't_first_wrote';
	const TICKET_FIRST_CONTACT_ORG_ID = 't_first_contact_org_id';
	const TICKET_LAST_WROTE_ID = 't_last_wrote_id';
	const TICKET_LAST_WROTE = 't_last_wrote';
	const TICKET_CREATED_DATE = 't_created_date';
	const TICKET_UPDATED_DATE = 't_updated_date';
	const TICKET_DUE_DATE = 't_due_date';
	const TICKET_SPAM_SCORE = 't_spam_score';
	const TICKET_SPAM_TRAINING = 't_spam_training';
	const TICKET_INTERESTING_WORDS = 't_interesting_words';
	const TICKET_NEXT_ACTION = 't_next_action';
	const TICKET_LAST_ACTION_CODE = 't_last_action_code';
	const TICKET_LAST_WORKER_ID = 't_last_worker_id';
	const TICKET_NEXT_WORKER_ID = 't_next_worker_id';
	const TICKET_CATEGORY_ID = 't_category_id';
	
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
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		return array(
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 't', 'id'),
			self::TICKET_MASK => new DevblocksSearchField(self::TICKET_MASK, 't', 'mask', null, $translate->_('ticket.mask')),
			self::TICKET_CLOSED => new DevblocksSearchField(self::TICKET_CLOSED, 't', 'is_closed',null,$translate->_('status.closed')),
			self::TICKET_DELETED => new DevblocksSearchField(self::TICKET_DELETED, 't', 'is_deleted',null,$translate->_('status.deleted')),
			self::TICKET_SUBJECT => new DevblocksSearchField(self::TICKET_SUBJECT, 't', 'subject',null,$translate->_('ticket.subject')),
			self::TICKET_FIRST_WROTE_ID => new DevblocksSearchField(self::TICKET_FIRST_WROTE_ID, 't', 'first_wrote_address_id'),
			self::TICKET_FIRST_WROTE => new DevblocksSearchField(self::TICKET_FIRST_WROTE, 'a1', 'email',null,$translate->_('ticket.first_wrote')),
			self::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchField(self::TICKET_FIRST_CONTACT_ORG_ID, 'a1', 'contact_org_id'),
			self::TICKET_LAST_WROTE_ID => new DevblocksSearchField(self::TICKET_LAST_WROTE_ID, 't', 'last_wrote_address_id'),
			self::TICKET_LAST_WROTE => new DevblocksSearchField(self::TICKET_LAST_WROTE, 'a2', 'email',null,$translate->_('ticket.last_wrote')),
			self::TICKET_CREATED_DATE => new DevblocksSearchField(self::TICKET_CREATED_DATE, 't', 'created_date',null,$translate->_('ticket.created')),
			self::TICKET_UPDATED_DATE => new DevblocksSearchField(self::TICKET_UPDATED_DATE, 't', 'updated_date',null,$translate->_('ticket.updated')),
			self::TICKET_DUE_DATE => new DevblocksSearchField(self::TICKET_DUE_DATE, 't', 'due_date',null,$translate->_('ticket.due')),
			self::TICKET_SPAM_TRAINING => new DevblocksSearchField(self::TICKET_SPAM_TRAINING, 't', 'spam_training'),
			self::TICKET_SPAM_SCORE => new DevblocksSearchField(self::TICKET_SPAM_SCORE, 't', 'spam_score',null,$translate->_('ticket.spam_score')),
			self::TICKET_INTERESTING_WORDS => new DevblocksSearchField(self::TICKET_INTERESTING_WORDS, 't', 'interesting_words'),
			self::TICKET_NEXT_ACTION => new DevblocksSearchField(self::TICKET_NEXT_ACTION, 't', 'next_action',null,$translate->_('ticket.next_action')),
			self::TICKET_LAST_ACTION_CODE => new DevblocksSearchField(self::TICKET_LAST_ACTION_CODE, 't', 'last_action_code',null,$translate->_('ticket.last_action')),
			self::TICKET_LAST_WORKER_ID => new DevblocksSearchField(self::TICKET_LAST_WORKER_ID, 't', 'last_worker_id',null,$translate->_('ticket.last_worker')),
			self::TICKET_NEXT_WORKER_ID => new DevblocksSearchField(self::TICKET_NEXT_WORKER_ID, 't', 'next_worker_id',null,$translate->_('ticket.next_worker')),
			self::TICKET_CATEGORY_ID => new DevblocksSearchField(self::TICKET_CATEGORY_ID, 't', 'category_id',null,$translate->_('common.bucket')),
			
			self::TICKET_MESSAGE_HEADER => new DevblocksSearchField(self::TICKET_MESSAGE_HEADER, 'mh', 'header_name'),
			self::TICKET_MESSAGE_HEADER_VALUE => new DevblocksSearchField(self::TICKET_MESSAGE_HEADER_VALUE, 'mh', 'header_value', 'B'),

			self::TICKET_MESSAGE_CONTENT => new DevblocksSearchField(self::TICKET_MESSAGE_CONTENT, 'mc', 'content', 'B', $translate->_('message.content')),
			
			self::REQUESTER_ID => new DevblocksSearchField(self::REQUESTER_ID, 'ra', 'id'),
			self::REQUESTER_ADDRESS => new DevblocksSearchField(self::REQUESTER_ADDRESS, 'ra', 'email'),
			
			self::SENDER_ADDRESS => new DevblocksSearchField(self::SENDER_ADDRESS, 'a1', 'email'),
			
			self::TEAM_ID => new DevblocksSearchField(self::TEAM_ID,'tm','id',null,$translate->_('common.group')),
			self::TEAM_NAME => new DevblocksSearchField(self::TEAM_NAME,'tm','name',null,$translate->_('common.group')),
		);
	}
};

class DAO_TicketRss extends DevblocksORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const HASH = 'hash';
	const WORKER_ID = 'worker_id';
	const CREATED = 'created';
	const PARAMS = 'params';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$newId = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO ticket_rss (id,hash,title,worker_id,created,params) ".
			"VALUES (%d,'','',0,0,'')",
			$newId
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($newId, $fields);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return Model_TicketRss[]
	 */
	static function getList($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,hash,title,worker_id,created,params ".
			"FROM ticket_rss ".
			(!empty($ids) ? sprintf("WHERE id IN (%s)",implode(',',$ids)) : " ").
		"";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return self::_getObjectsFromResults($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $hash
	 * @return Model_TicketRss
	 */
	static function getByHash($hash) {
		if(empty($hash)) return array();
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,hash,title,worker_id,created,params ".
			"FROM ticket_rss ".
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
	 * @return Model_TicketRss[]
	 */
	static function getByWorker($worker_id) {
		if(empty($worker_id)) return array();
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,hash,title,worker_id,created,params ".
			"FROM ticket_rss ".
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
	 * @return Model_TicketRss[]
	 */
	private static function _getObjectsFromResults($rs) { /* @var $rs ADORecordSet */
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_TicketRss();
			$object->id = intval($rs->fields['id']);
			$object->title = $rs->fields['title'];
			$object->hash = $rs->fields['hash'];
			$object->worker_id = intval($rs->fields['worker_id']);
			$object->created = intval($rs->fields['created']);
			
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
	 * @return Model_TicketRss
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
				'ticket_rss',
				self::PARAMS,
				$fields[self::PARAMS],
				sprintf('id IN (%s)',implode(',',$ids))
			);
			unset($fields[self::PARAMS]);
		}
		
		parent::_update($ids, 'ticket_rss', $fields);
	}
	
	static function delete($id) {
		if(empty($id)) return;
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM ticket_rss WHERE id = %d",
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
			((!empty($ids)) ? sprintf("WHERE t.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY t.name ASC"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
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
	    if($nocache || false == ($teams = $cache->load(self::CACHE_ALL))) {
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
		
		$sql = sprintf("DELETE FROM team WHERE id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM worker_to_team WHERE team_id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$sql = sprintf("DELETE FROM mail_routing WHERE team_id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM team_routing_rule WHERE team_id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

        DAO_TeamRoutingRule::deleteByMoveCodes(array('t'.$id));

		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
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
	}
	
	static function unsetTeamMember($team_id, $worker_id) {
        if(empty($worker_id) || empty($team_id))
            return FALSE;
            
        $db = DevblocksPlatform::getDatabaseService();
        
		$sql = sprintf("DELETE FROM worker_to_team WHERE team_id = %d AND agent_id IN (%d)",
		    $team_id,
		    $worker_id
		);
		$db->Execute($sql);
	}
	
	static function getTeamMembers($team_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$members = array();
		
		$sql = "SELECT wt.agent_id, wt.is_manager ".
			"FROM worker_to_team wt ".
			(!empty($team_id) ? sprintf("WHERE wt.team_id = %d ", $team_id) : " ")
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$member = new Model_TeamMember();
			$member->id = intval($rs->fields['agent_id']);
			$member->is_manager = intval($rs->fields['is_manager']);
			$member->team_id = intval($rs->fields['team_id']);
			$members[$member->id] = $member;
			$rs->MoveNext();
		}
		
		return $members;
	}
	
};

class DAO_GroupSettings {
    const SETTING_REPLY_FROM = 'reply_from';
    const SETTING_REPLY_PERSONAL = 'reply_personal';
    const SETTING_SPAM_THRESHOLD = 'group_spam_threshold';
    const SETTING_SPAM_ACTION = 'group_spam_action';
    const SETTING_SPAM_ACTION_PARAM = 'group_spam_action_param';
    const SETTING_AUTO_REPLY = 'auto_reply';
    const SETTING_AUTO_REPLY_ENABLED = 'auto_reply_enabled';
    
	static function set($group_id, $key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		$result = $db->Replace(
		    'group_setting',
		    array(
		        'group_id'=>$group_id,
		        'setting'=>$key,
		        'value'=>$db->qstr($value) // BlobEncode/UpdateBlob?
		    ),
		    array('group_id','setting'),
		    true
		);
	}
	
	static function get($group_id, $key, $default=null) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT value FROM group_setting WHERE setting = %s AND group_id = %d",
			$db->qstr($key),
			$group_id
		);
		$value = $db->GetOne($sql);
		
		if(false == $value && !is_null($default)) {
		    return $default;
		}
		
		return $value;
	}
	
	// [TODO] Cache as static/singleton or load up in a page scope object?
	static function getSettings($group_id=0) {
		$db = DevblocksPlatform::getDatabaseService();

		// [TODO] Make this more efficient (cache until a setter comes around)
		
		$groups = array();
		
		$sql = "SELECT group_id, setting, value ".
		    "FROM group_setting ".
		    (!empty($group_id) ? sprintf("WHERE group_id = %d",$group_id) : "")
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
		    $gid = intval($rs->fields['group_id']);
		    
		    if(!isset($groups[$gid]))
		        $groups[$gid] = array();
		    
		    $groups[$gid][$rs->Fields('setting')] = $rs->Fields('value');
			$rs->MoveNext();
		}
		
		if(!empty($group_id)) {
		    if(!empty($groups))
		        return $groups[$group_id];
		    return array();
		}

		return $groups;
	}
};

class DAO_Bucket extends DevblocksORMHelper {
	const CACHE_ALL = 'cerberus_cache_buckets_all';
	
    const ID = 'id';
    const NAME = 'name';
    const TEAM_ID = 'team_id';
    
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
	    if($nocache || false == ($buckets = $cache->load(self::CACHE_ALL))) {
    	    $buckets = self::getList();
    	    $cache->save($buckets, self::CACHE_ALL);
	    }
	    
	    return $buckets;
	}
	
	static function getList($ids=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT tc.id, tc.name, tc.team_id ".
			"FROM category tc ".
			"INNER JOIN team t ON (tc.team_id=t.id) ".
			(!empty($ids) ? sprintf("WHERE tc.id IN (%s) ", implode(',', $ids)) : "").
			"ORDER BY t.name ASC, tc.name ASC "
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$categories = array();
		
		while(!$rs->EOF) {
			$category = new CerberusCategory();
			$category->id = intval($rs->Fields('id'));
			$category->name = $rs->Fields('name');
			$category->team_id = intval($rs->Fields('team_id'));
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
			
			$sql = sprintf("INSERT INTO category (id,name,team_id) ".
				"VALUES (%d,%s,%d)",
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
	
	static function update($id,$name) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("UPDATE category SET name=%s WHERE id = %d",
			$db->qstr($name),
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	static function delete($ids) {
	    if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM category WHERE id IN (%s)", implode(',',$ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// Reset any tickets using this category
		$sql = sprintf("UPDATE ticket SET category_id = 0 WHERE category_id IN (%s)", implode(',',$ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// Clear any view's move counts involving this bucket for all workers
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
		
		$sql = sprintf("DELETE FROM mail_routing WHERE id = %d",
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
		
		$sql = sprintf("DELETE FROM pop3_account WHERE id = %d",
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
	    
	    $sql = sprintf("DELETE FROM community WHERE id IN (%s)", $id_list);
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

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_Community::getFields());
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
			SearchFields_Community::ID => new DevblocksSearchField(SearchFields_Community::ID, 'c', 'id'),
			SearchFields_Community::NAME => new DevblocksSearchField(SearchFields_Community::NAME, 'c', 'name'),
		);
	}
};	

class DAO_WorkerWorkspaceList extends DevblocksORMHelper {
	const ID = 'id';
	const WORKER_ID = 'worker_id';
	const WORKSPACE = 'workspace';
	const LIST_VIEW = 'list_view';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($fields))
			return NULL;
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO worker_workspace_list (id, worker_id, workspace, list_view) ".
			"VALUES (%d, 0, '', '')",
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
		
		$sql = "SELECT id, worker_id, workspace, list_view ".
			"FROM worker_workspace_list ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : " ")
			;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_WorkerWorkspaceList();
			$object->id = intval($rs->fields['id']);
			$object->worker_id = intval($rs->fields['worker_id']);
			$object->workspace = $rs->fields['workspace'];
			
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
		
		$db->Execute(sprintf("DELETE FROM worker_workspace_list WHERE id IN (%s)", $ids_list)) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
};

class DAO_WorkerPref extends DevblocksORMHelper {
    const SETTING_TEAM_MOVE_COUNTS = 'team_move_counts';
    
	static function set($worker_id, $key, $value) {
		$db = DevblocksPlatform::getDatabaseService();
		$result = $db->Replace(
		    'worker_pref',
		    array(
		        'worker_id'=>$worker_id,
		        'setting'=>$key,
		        'value'=>$db->qstr($value) // BlobEncode/UpdateBlob?
		    ),
		    array('worker_id','setting'),
		    true
		);
	}
	
	static function get($worker_id, $key, $default=null) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT value FROM worker_pref WHERE setting = %s AND worker_id = %d",
			$db->qstr($key),
			$worker_id
		);
		$value = $db->GetOne($sql);
		
		if(false == $value && !is_null($default)) {
		    return $default;
		}
		
		return $value;
	}
	
	// [JAS]: [TODO] Cache
	static function getAll() {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT worker_id, setting, value FROM worker_pref ");
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		$objects = array();
		
		while(!$rs->EOF) {
		    $object = new Model_WorkerPreference();
		    $object->setting = $rs->fields['setting'];
		    $object->value = $rs->fields['value'];
		    $object->worker_id = $rs->fields['worker_id'];
		    
		    if(!isset($objects[$object->worker_id]))
		    	$objects[$object->worker_id] = array();
		    
		    $objects[$object->worker_id][$object->setting] = $object;
		    $rs->MoveNext();
		}
		
		return $objects;
	}
	
	// Clear any view's move counts for all workers involving the buckets specified 
	static function clearMoveCounts($category_ids) {
		if(!is_array($category_ids)) $category_ids = array($category_ids);
		
		$worker_prefs = self::getAll();
		foreach($worker_prefs AS $worker_pref) {
			$move_counts_const = 'team_move_counts';
			// Make sure this worker pref is a move count
			if(substr($worker_pref->setting, 0, strlen($move_counts_const)) == $move_counts_const) {
				$moveCounts = unserialize($worker_pref->value);
				foreach($category_ids as $id) {
					if(isset($moveCounts['c'.$id])) {
						unset($moveCounts['c'.$id]);
					}
				}
				self::set($worker_pref->worker_id, $worker_pref->setting, serialize($moveCounts));
			}
		}
		
	}
	
	// [TODO] Cache as static/singleton or load up in a page scope object?
	static function getSettings($worker_id=0) {
		$db = DevblocksPlatform::getDatabaseService();

		$workers = array();
		
		$sql = "SELECT worker_id, setting, value ".
		    "FROM worker_pref ".
		    (!empty($worker_id) ? sprintf("WHERE worker_id = %d",$worker_id) : "")
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
		    $worker_id = intval($rs->fields['worker_id']);
		    
		    if(!isset($workers[$worker_id]))
		        $workers[$worker_id] = array();
		    
		    $worker =& $workers[$worker_id];
		        
			$worker[$rs->Fields('setting')] = $rs->Fields('value');
			$rs->MoveNext();
		}
		
		return $workers;
	}
};

class DAO_TeamRoutingRule extends DevblocksORMHelper {
    const ID = 'id';
    const TEAM_ID = 'team_id';
    const HEADER = 'header';
    const PATTERN = 'pattern';
    const POS = 'pos';
    const DO_STATUS = 'do_status';
    const DO_SPAM = 'do_spam';
    const DO_MOVE = 'do_move';
//    const PARAMS = 'params'; // blob
    
	public static function create($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		self::findDupes($fields);
		
		$sql = sprintf("INSERT INTO team_routing_rule (id,created,team_id,header,pattern,pos,do_spam,do_status,do_move) ".
		    "VALUES (%d,%d,0,'','',0,'','','')",
		    $id,
		    time()
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($id, $fields) {
	    self::findDupes($fields);
	    
//	    if($fields[self::PARAMS]) {
//	        $params = $fields[self::PARAMS];
//	        unset($fields[self::PARAMS]);
	        // [TODO] DO our own DB call here for updateBlob (HACK until new patch system)
//	    }
	    
        self::_update($id, 'team_routing_rule', $fields);
	}
	
	private static function findDupes($fields) {
	    $db = DevblocksPlatform::getDatabaseService();
	    
	    // Check for dupes
	    // [TODO] This is stupid
		if(isset($fields[self::TEAM_ID]) && isset($fields[self::PATTERN]) && isset($fields[self::HEADER])) {
		    $sql = sprintf("DELETE FROM team_routing_rule ".
		        "WHERE team_id = %d ".
		        "AND pattern = %s ".
		        "AND header = %s ",
		        intval($fields[self::TEAM_ID]),
		        $db->qstr($fields[self::PATTERN]),
		        $db->qstr($fields[self::HEADER])
		    );
		    $db->Execute($sql);
		    
		    return true;
		}
		
		return false;
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
		
		$sql = sprintf("SELECT id, team_id, header, pattern, pos, do_spam, do_status, do_move ".
		    "FROM team_routing_rule ".
		    "WHERE team_id = %d ".
		    "ORDER BY pos DESC",
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
		
		$sql = "SELECT id, team_id, header, pattern, pos, do_spam, do_status, do_move ".
		    "FROM team_routing_rule ".
		    (!empty($ids) ? sprintf("WHERE id IN (%s) ", implode(',', $ids)) : " ").
		    "ORDER BY pos DESC"
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
		
		while(!$rs->EOF) {
		    $object = new Model_TeamRoutingRule();
		    $object->id = intval($rs->fields['id']);
		    $object->team_id = intval($rs->fields['team_id']);
		    $object->header = $rs->fields['header'];
		    $object->pattern = $rs->fields['pattern'];
		    $object->pos = intval($rs->fields['pos']);
		    $object->do_spam = $rs->fields['do_spam'];
            $object->do_status = $rs->fields['do_status'];
            $object->do_move = $rs->fields['do_move'];
		    		    
//		    $params = $rs->fields['params'];
//		    
//		    if(!empty($params)) {
//		        @$object->params = unserialize($params);
//		    }
		    
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
	    
	    $sql = sprintf("DELETE FROM team_routing_rule WHERE id IN (%s)", $id_list);
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

        list($tables,$wheres) = parent::_parseSearchParams($params, SearchFields_TeamRoutingRule::getFields());
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
		
		$sql = sprintf("DELETE FROM fnr_topic WHERE id IN (%s)", $ids_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$sql = sprintf("DELETE FROM fnr_external_resource WHERE topic_id IN (%s)", $ids_string);
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
		
		$sql = sprintf("DELETE FROM %s WHERE id IN (%s)",
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

class DAO_MailTemplateReply extends DevblocksORMHelper {
	const _TABLE = 'mail_template_reply';
	
	const ID = 'id';
	const TITLE = 'title';
	const DESCRIPTION = 'description';
	const FOLDER = 'folder';
	const OWNER_ID = 'owner_id';
	const CONTENT = 'content';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO %s (id,title,description,folder,owner_id,content) ".
			"VALUES (%d,'','','',0,'')",
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
	public static function getFolders() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$folders = array();
		
		$sql = sprintf("SELECT DISTINCT folder FROM %s ORDER BY folder",
			self::_TABLE
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

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
		
		$sql = sprintf("DELETE FROM %s WHERE id IN (%s)",
			self::_TABLE,
			implode(',', $ids)
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $where
	 * @return Model_MailTemplateReply[]
	 */
	public function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,title,description,folder,owner_id,content ".
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
	 * @return Model_MailTemplateReply
	 */
	public static function get($id) {
		$objects = self::getWhere(sprintf("id = %d", $id));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	public static function _createObjectsFromResultSet(ADORecordSet $rs) {
		$objects = array();
		while(!$rs->EOF) {
			$object = new Model_MailTemplateReply();
			$object->id = intval($rs->fields['id']);
			$object->title = $rs->fields['title'];
			$object->description = $rs->fields['description'];
			$object->folder = $rs->fields['folder'];
			$object->owner_id = intval($rs->fields['owner_id']);
			$object->content = $rs->fields['content'];
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
};

?>