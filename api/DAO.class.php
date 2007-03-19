<?php

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
	
	static function addOneToSpamWord($word_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE bayes_words SET spam = spam + 1 WHERE id = %d", $word_id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function addOneToNonSpamWord($word_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE bayes_words SET nonspam = nonspam + 1 WHERE id = %d", $word_id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
};

// [JAS]: [TODO] Should rename this to WorkerDAO for consistency
class CerberusAgentDAO {
	private function CerberusAgentDAO() {}
	
	const ID = 'id';
	const LOGIN = 'login';
	const PASSWORD = 'pass';
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const TITLE = 'title';
	
	static function createAgent($login, $password, $first_name, $last_name, $title) {
		if(empty($login) || empty($password))
			return null;
			
		$um_db = DevblocksPlatform::getDatabaseService();
		$id = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO worker (id, login, pass, first_name, last_name, title) ".
			"VALUES (%d, %s, %s, %s, %s, %s)",
			$id,
			$um_db->qstr($login),
			$um_db->qstr(md5($password)),
			$um_db->qstr($first_name),
			$um_db->qstr($last_name),
			$um_db->qstr($title)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
	static function getAgents($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$workers = array();
		
		$sql = "SELECT a.id, a.first_name, a.last_name, a.login, a.title ".
			"FROM worker a ".
			((!empty($ids) ? sprintf("WHERE a.id IN (%s)",implode(',',$ids)) : " ").
			"ORDER BY a.last_name, a.first_name "
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			// [TODO] CerberusWorker
			$worker = new CerberusWorker();
			$worker->id = intval($rs->fields['id']);
			$worker->first_name = $rs->fields['first_name'];
			$worker->last_name = $rs->fields['last_name'];
			$worker->login = $rs->fields['login'];
			$worker->title = $rs->fields['title'];
			$worker->last_activity_date = intval($rs->fields['last_activity_date']);
			$workers[$worker->id] = $worker;
			$rs->MoveNext();
		}
		
		return $workers;		
	}
	
	static function getAgent($id) {
		if(empty($id)) return null;
		
		$agents = CerberusAgentDAO::getAgents(array($id));
		
		if(isset($agents[$id]))
			return $agents[$id];
			
		return null;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $login
	 * @return integer $id
	 */
	static function lookupAgentLogin($login) {
		if(empty($login)) return null;
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT a.id FROM worker a WHERE a.login = %s",
			$um_db->qstr($login)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			return intval($rs->fields['id']);
		}
		
		return null;		
	}
	
	static function updateAgent($id, $fields) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE worker SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	static function deleteAgent($id) {
		if(empty($id)) return;
		
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM worker WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM worker_to_team WHERE agent_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM favorite_tag_to_worker WHERE agent_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	static function login($login, $password) {
		$db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("SELECT id ".
			"FROM worker ".
			"WHERE login = %s ".
			"AND pass = MD5(%s)",
				$db->qstr($login),
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
		$um_db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("DELETE FROM worker_to_team WHERE agent_id = %d",
			$agent_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		foreach($team_ids as $team_id) {
			$sql = sprintf("INSERT INTO worker_to_team (agent_id, team_id) ".
				"VALUES (%d,%d)",
				$agent_id,
				$team_id
			);
			$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
	}
	
	static function getAgentTeams($agent_id) {
		if(empty($agent_id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();
		$ids = array();
		
		$sql = sprintf("SELECT wt.team_id FROM worker_to_team wt WHERE wt.agent_id = %d",
			$agent_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['team_id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
		
		return CerberusWorkflowDAO::getTeams($ids);
	}
	
	static function getFavoriteTags($agent_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($agent_id)) return null;
		
		$ids = array();
		
		$sql = sprintf("SELECT tag_id FROM favorite_tag_to_worker WHERE agent_id = %d",
			$agent_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['tag_id']);
			$rs->MoveNext();
		}

		if(empty($ids))
			return array();
		
		return CerberusWorkflowDAO::getTags($ids);
	}
	
	static function setFavoriteTags($agent_id, $tag_string) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($agent_id)) return null;
		
		$sql = sprintf("DELETE FROM favorite_tag_to_worker WHERE agent_id = %d",
			$agent_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$tags = CerberusApplication::parseCsvString($tag_string);
		$ids = array();
		
		foreach($tags as $tag_name) {
			$tag = CerberusWorkflowDAO::lookupTag($tag_name, true);
			$ids[$tag->id] = $tag->id;
		}
		
		foreach($ids as $tag_id) {
			$sql = sprintf("INSERT INTO favorite_tag_to_worker (tag_id, agent_id) ".
				"VALUES (%d,%d) ",
					$tag_id,
					$agent_id
			);
			$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */	
		}
		
	}
	
	static function getFavoriteWorkers($agent_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($agent_id)) return null;
		
		$ids = array();
		
		$sql = sprintf("SELECT worker_id FROM favorite_worker_to_worker WHERE agent_id = %d",
			$agent_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['worker_id']);
			$rs->MoveNext();
		}

		if(empty($ids))
			return array();
		
		return CerberusAgentDAO::getAgents($ids);
	}
	
	static function setFavoriteWorkers($agent_id, $worker_string) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($agent_id)) return null;
		
		$sql = sprintf("DELETE FROM favorite_worker_to_worker WHERE agent_id = %d",
			$agent_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$workers = CerberusApplication::parseCsvString($worker_string);
		$ids = array();
		
		foreach($workers as $worker_name) {
			$worker_id = CerberusAgentDAO::lookupAgentLogin($worker_name);
			
			if(null == $worker_id)
				continue;

			$ids[$worker_id] = $worker_id;
		}
		
		foreach($ids as $worker_id) {
			$sql = sprintf("INSERT INTO favorite_worker_to_worker (worker_id, agent_id) ".
				"VALUES (%d,%d) ",
					$worker_id,
					$agent_id
			);
			$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */	
		}
		
	}
	
	static function searchAgents($query, $limit=10) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($query)) return null;
		
		$sql = sprintf("SELECT a.id FROM login a WHERE a.login LIKE '%s%%' LIMIT 0,%d",
			$query,
			$limit
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$ids = array();
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
			
		return CerberusAgentDAO::getAgents($ids);
	}
	
}

class CerberusContactDAO {
	private function CerberusContactDAO() {}
	
	// [JAS]: [TODO] Move this into MailDAO
	static function lookupAddress($email,$create_if_null=false) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$id = null;
		
		$sql = sprintf("SELECT id FROM address WHERE email = %s",
			$um_db->qstr(trim(strtolower($email)))
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$id = $rs->fields['id'];
		} elseif($create_if_null) {
			$id = CerberusContactDAO::createAddress($email);
		}
		
		return $id;
	}
	
	// [JAS]: [TODO] Move this into MailDAO
	static function getAddresses($ids=array()) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(!is_array($ids)) $ids = array($ids);
		$addresses = array();
		
		$sql = sprintf("SELECT a.id, a.email, a.personal, a.bitflags ".
			"FROM address a ".
			((!empty($ids)) ? "WHERE a.id IN (%s) " : " ").
			"ORDER BY a.email ",
			implode(',', $ids)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$address = new CerberusAddress();
			$address->id = intval($rs->fields['id']);
			$address->email = $rs->fields['email'];
			$address->personal = $rs->fields['personal'];
			$address->bitflags = intval($rs->fields['bitflags']);
			$addresses[$address->id] = $address;
			$rs->MoveNext();
		}
		
		return $addresses;
	}
	
	// [JAS]: [TODO] Move this into MailDAO
	static function getAddress($id) {
		if(empty($id)) return null;
		
		$addresses = CerberusContactDAO::getAddresses(array($id));
		
		if(isset($addresses[$id]))
			return $addresses[$id];
			
		return null;		
	}

	// [JAS]: [TODO] Move this into MailDAO
	static function getMailboxIdByAddress($email) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$id = CerberusContactDAO::lookupAddress($email,false);
		$mailbox_id = null;
		
		if(empty($id))
			return null;
		
		$sql = sprintf("SELECT am.mailbox_id FROM address_to_mailbox am WHERE am.address_id = %d",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$mailbox_id = intval($rs->fields['mailbox_id']);
		}
		
		return $mailbox_id;
	}
	
	// [JAS]: [TODO] Move this into MailDAO
	/**
	 * creates an address entry in the database if it doesn't exist already
	 *
	 * @param string $email
	 * @param string $personal
	 * @return integer
	 * @throws exception on invalid address
	 */
	static function createAddress($email,$personal='') {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		if(null != ($id = CerberusContactDAO::lookupAddress($email,false)))
			return $id;

		$id = $um_db->GenID('address_seq');
		
		/*
		 * [JAS]: [TODO] If we're going to call platform libs directly we should just have
		 * the platform provide the functionality.
		 */
		// [TODO] This code fails with anything@localhost
		require_once(DEVBLOCKS_PATH . 'pear/Mail/RFC822.php');
		if (false === Mail_RFC822::isValidInetAddress($email)) {
//			throw new Exception($email . DevblocksTranslationManager::say('ticket.requester.invalid'));
			return null;
		}
		
		$sql = sprintf("INSERT INTO address (id,email,personal,bitflags) VALUES (%d,%s,%s,0)",
			$id,
			$um_db->qstr(trim(strtolower($email))),
			$um_db->qstr($personal)
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
}

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class CerberusTicketDAO {
	const ID = 'id';
	const STATUS = 'status';
	const PRIORITY = 'priority';
	const MAILBOX_ID = 'mailbox_id';
	const SPAM_TRAINING = 'spam_training';
	const SPAM_SCORE = 'spam_score';
	
	private function CerberusTicketDAO() {}
	
	/**
	 * Enter description here...
	 *
	 * @param string $mask
	 * @return CerberusTicket
	 */
	static function getTicketByMask($mask) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT t.id FROM ticket t WHERE t.mask = %s",
			$um_db->qstr($mask)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$ticket_id = intval($rs->fields['id']);
			return CerberusTicketDAO::getTicket($ticket_id);
		}
		
		return null;
	}
	
	static function getTicketByMessageId($message_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT t.id ".
			"FROM ticket t ".
			"INNER JOIN message m ON (t.id=m.ticket_id) ".
			"WHERE m.message_id = %s",
			$um_db->qstr($message_id)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$ticket_id = intval($rs->fields['id']);
			return $ticket_id;
		}
		
		return null;
	}
	
	/**
	 * Adds an attachment link to the database (this is informational only, it does not contain
	 * the actual attachment)
	 *
	 * @param integer $message_id
	 * @param string $display_name
	 * @param string $filepath
	 * @return integer
	 */
	static function createAttachment($message_id, $display_name, $filepath) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$newId = $um_db->GenID('attachment_seq');
		
		$sql = sprintf("INSERT INTO attachment (id, message_id, display_name, filepath)".
			"VALUES (%d,%d,%s,%s)",
			$newId,
			$message_id,
			$um_db->qstr($display_name),
			$um_db->qstr($filepath)
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	/**
	 * returns an array of CerberusAttachments that
	 * correspond to the supplied message id.
	 *
	 * @param integer $id
	 * @return CerberusAttachment[]
	 */
	static function getAttachmentsByMessage($id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT a.id, a.message_id, a.display_name, a.filepath ".
			"FROM attachment a WHERE a.message_id = %d",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$attachments = array();
		while(!$rs->EOF) {
			$attachment = new CerberusAttachment();
			$attachment->id = intval($rs->fields['id']);
			$attachment->message_id = intval($rs->fields['message_id']);
			$attachment->display_name = $rs->fields['display_name'];
			$attachment->filepath = $rs->fields['filepath'];
			$attachments[] = $attachment;
			$rs->MoveNext();
		}

		return $attachments;
	}
	
	/**
	 * creates a new ticket object in the database
	 *
	 * @param string $mask
	 * @param string $subject
	 * @param string $status
	 * @param integer $mailbox_id
	 * @param string $last_wrote
	 * @param integer $created_date
	 * @return integer
	 * 
	 * [TODO]: Change $last_wrote argument to an ID rather than string?
	 */
	static function createTicket($mask, $subject, $status, $mailbox_id, $last_wrote, $created_date) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$newId = $um_db->GenID('ticket_seq');
		
		$last_wrote_id = CerberusContactDAO::lookupAddress($last_wrote, true);
		
		$sql = sprintf("INSERT INTO ticket (id, mask, subject, status, mailbox_id, last_wrote_address_id, first_wrote_address_id, created_date, updated_date, priority) ".
			"VALUES (%d,%s,%s,%s,%d,%d,%d,%d,%d,0)",
			$newId,
			$um_db->qstr($mask),
			$um_db->qstr($subject),
			$um_db->qstr($status),
			$mailbox_id,
			$last_wrote_id,
			$last_wrote_id,
			$created_date,
			gmmktime()
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// send new ticket auto-response
		CerberusMailDAO::sendAutoresponse($id, 'new');
		
		return $newId;
	}

	static function createMessage($ticket_id,$type,$created_date,$address_id,$headers,$content) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$newId = $um_db->GenID('message_seq');
		
		// [JAS]: Flatten an array of headers into a string.
		$sHeaders = serialize($headers);

		$sql = sprintf("INSERT INTO message (id,ticket_id,message_type,created_date,address_id,message_id,headers,content) ".
			"VALUES (%d,%d,%s,%d,%d,%s,%s,%s)",
				$newId,
				$ticket_id,
				$um_db->qstr($type),
				$created_date,
				$address_id,
				((isset($headers['message-id'])) ? $um_db->qstr($headers['message-id']) : "''"),
				$um_db->qstr($sHeaders),
				$um_db->qstr($content)
		);
		
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
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
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$tickets = array();
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.priority, t.mailbox_id, t.bitflags, ".
			"t.first_wrote_address_id, t.last_wrote_address_id, t.created_date, t.updated_date, t.spam_training, t.spam_score ".
			"FROM ticket t ".
			(!empty($ids) ? sprintf("WHERE t.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY t.updated_date DESC",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ticket = new CerberusTicket();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->bitflags = intval($rs->fields['bitflags']);
			$ticket->status = $rs->fields['status'];
			$ticket->priority = intval($rs->fields['priority']);
			$ticket->mailbox_id = intval($rs->fields['mailbox_id']);
			$ticket->last_wrote_address_id = intval($rs->fields['last_wrote_address_id']);
			$ticket->first_wrote_address_id = intval($rs->fields['first_wrote_address_id']);
			$ticket->created_date = intval($rs->fields['created_date']);
			$ticket->updated_date = intval($rs->fields['updated_date']);
			$ticket->spam_score = floatval($rs->fields['spam_score']);
			$ticket->spam_training = $rs->fields['spam_training'];
			$tickets[$ticket->id] = $ticket;
			$rs->MoveNext();
		}
		
		return $tickets;
	}
	
	static function updateTicket($id,$fields) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			switch ($k) {
				case 'status':
					if (0 == strcasecmp($v, 'C')) // if ticket is being closed
						CerberusMailDAO::sendAutoresponse($id, 'closed');
					break;
			}
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE ticket SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	static function tagTicket($ticket_id, $tag_string) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$tags = CerberusApplication::parseCsvString($tag_string);
		
		if(is_array($tags))
		foreach($tags as $tagName) {
			$tag = CerberusWorkflowDAO::lookupTag($tagName, true);
			$um_db->Replace('tag_to_ticket', array('ticket_id'=>$ticket_id,'tag_id'=>$tag->id), array('ticket_id','tag_id'));
		}
	}
	
	static function untagTicket($ticket_id, $tag_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM tag_to_ticket WHERE tag_id = %d AND ticket_id = %d",
			$tag_id,
			$ticket_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function flagTicket($ticket_id, $agent_id) {
		if(empty($ticket_id) || empty($agent_id))
			return null;
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$um_db->Replace('assign_to_ticket', array('ticket_id'=>$ticket_id,'agent_id'=>$agent_id,'is_flag'=>1), array('ticket_id','agent_id'));
	}
	
	static function unflagTicket($ticket_id, $agent_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM assign_to_ticket WHERE agent_id = %d AND ticket_id = %d AND is_flag = 1",
			$agent_id,
			$ticket_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function suggestTicket($ticket_id, $agent_id) {
		if(empty($ticket_id) || empty($agent_id))
			return null;
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$um_db->Replace('assign_to_ticket', array('ticket_id'=>$ticket_id,'agent_id'=>$agent_id,'is_flag'=>0), array('ticket_id','agent_id'));
	}
	
	static function unsuggestTicket($ticket_id, $agent_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM assign_to_ticket WHERE agent_id = %d AND ticket_id = %d AND is_flag = 0",
			$agent_id,
			$ticket_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	/**
	 * @return CerberusMessage[]
	 */
	static function getMessagesByTicket($ticket_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$messages = array();
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.message_type, m.created_date, m.address_id, m.message_id, m.headers ".
			"FROM message m ".
			"WHERE m.ticket_id = %d ".
			"ORDER BY m.created_date ASC ",
			$ticket_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$message = new CerberusMessage();
			$message->id = intval($rs->fields['id']);
			$message->ticket_id = intval($rs->fields['ticket_id']);
			$message->message_type = $rs->fields['message_type'];
			$message->created_date = intval($rs->fields['created_date']);
			$message->address_id = intval($rs->fields['address_id']);
			$message->message_id = $rs->fields['message_id'];
			
			$headers = unserialize($rs->fields['headers']);
			$message->headers = $headers;

			$messages[$message->id] = $message;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$rs = $um_db->Execute($sql);
		$total = $rs->RecordCount();
		
		return array($messages,$total);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id message id
	 * @return CerberusMessage
	 */
	static function getMessage($id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$message = null;
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.message_type, m.created_date, m.address_id, m.message_id, m.headers ".
			"FROM message m ".
			"WHERE m.id = %d ".
			"ORDER BY m.created_date ASC ",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		if(!$rs->EOF) {
			$message = new CerberusMessage();
			$message->id = intval($rs->fields['id']);
			$message->ticket_id = intval($rs->fields['ticket_id']);
			$message->message_type = $rs->fields['message_type'];
			$message->created_date = intval($rs->fields['created_date']);
			$message->address_id = intval($rs->fields['address_id']);
			$message->message_id = $rs->fields['message_id'];
			
			$headers = unserialize($rs->fields['headers']);
			$message->headers = $headers;
		}

		// [JAS]: Count all
//		$rs = $um_db->Execute($sql);
//		$total = $rs->RecordCount();
		
		return $message;
//		return array($messages,$total);
	}
	
	static function getRequestersByTicket($ticket_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$addresses = array();
		
		$sql = sprintf("SELECT a.id , a.email, a.personal ".
			"FROM address a ".
			"INNER JOIN requester r ON (r.ticket_id = %d AND a.id=r.address_id) ".
			"ORDER BY a.email ASC ",
			$ticket_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$address = new CerberusAddress();
			$address->id = intval($rs->fields['id']);
			$address->email = $rs->fields['email'];
			$address->personal = $rs->fields['personal'];
			$addresses[$address->id] = $address;
			$rs->MoveNext();
		}

		// [JAS]: Count all
//		$rs = $um_db->Execute($sql);
//		$total = $rs->RecordCount();
//		return array($addresses,$total);

		return $addresses;
	}
	
	static function getMessageContent($id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$content = '';
		
		$sql = sprintf("SELECT m.id, m.content ".
			"FROM message m ".
			"WHERE m.id = %d ",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$content = $rs->fields['content'];
		}
		
		return $content;
	}
	
	static function createRequester($address_id,$ticket_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$um_db->Replace("requester",array("address_id"=>$address_id,"ticket_id"=>$ticket_id),array("address_id","ticket_id")); 
		return true;
	}
	
};

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class CerberusDashboardDAO {
	private function CerberusDashboardDAO() {}
	
	static function createDashboard($name, $agent_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO dashboard (id, name, agent_id) ".
			"VALUES (%d, %s, %d)",
			$newId,
			$um_db->qstr($name),
			$agent_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	// [JAS]: Convert this over to pulling by a list of IDs?
	static function getDashboards($agent_id=0) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id, name ".
			"FROM dashboard "
//			(($agent_id) ? sprintf("WHERE agent_id = %d ",$agent_id) : " ")
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$dashboards = array();
		
		while(!$rs->EOF) {
			$dashboard = new CerberusDashboard();
			$dashboard->id = intval($rs->fields['id']);
			$dashboard->name = $rs->fields['name'];
			$dashboard->agent_id = intval($rs->fields['agent_id']);
			$dashboards[$dashboard->id] = $dashboard;
			$rs->MoveNext();
		}
		
		return $dashboards;
	}
	
	static function createView($name,$dashboard_id,$num_rows=10,$sort_by=null,$sort_asc=1,$type='D') {
		$um_db = DevblocksPlatform::getDatabaseService();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO dashboard_view (id, name, dashboard_id, type, num_rows, sort_by, sort_asc, page, params) ".
			"VALUES (%d, %s, %d, %s, %d, %s, %s, %d, '')",
			$newId,
			$um_db->qstr($name),
			$dashboard_id,
			$um_db->qstr($type),
			$num_rows,
			$um_db->qstr($sort_by),
			$sort_asc,
			0
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	static private function _updateView($id,$fields) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE dashboard_view SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function deleteView($id) {
		if(empty($id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM dashboard_view WHERE id = %d",
			$id
		);
		
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $dashboard_id
	 * @return CerberusDashboardView[]
	 */
	static function getViews($dashboard_id=0) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT v.id, v.name, v.dashboard_id, v.type, v.agent_id, v.view_columns, v.num_rows, v.sort_by, v.sort_asc, v.page, v.params ".
			"FROM dashboard_view v ".
			"WHERE v.dashboard_id > 0 "
//			(!empty($dashboard_id) ? sprintf("WHERE v.dashboard_id = %d ", $dashboard_id) : " ")
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$views = array();
		
		while(!$rs->EOF) {
			$view = new CerberusDashboardView();
			$view->id = $rs->fields['id'];
			$view->name = $rs->fields['name'];
			$view->dashboard_id = intval($rs->fields['dashboard_id']);
			$view->type = $rs->fields['type'];
			$view->agent_id = intval($rs->fields['agent_id']);
			$view->view_columns = unserialize($rs->fields['view_columns']);
			$view->params = unserialize($rs->fields['params']);
			$view->renderLimit = intval($rs->fields['num_rows']);
			$view->renderSortBy = $rs->fields['sort_by'];
			$view->renderSortAsc = intval($rs->fields['sort_asc']);
			$view->renderPage = intval($rs->fields['page']);
			$views[$view->id] = $view; 
			$rs->MoveNext();
		}
		
		return $views;
	}
	
	/**
	 * Loads or creates a view for a given agent
	 *
	 * @param integer $search_id
	 * @return CerberusDashboardView
	 */
	static function getView($view_id) {
		if(!empty($view_id)) {
			$view = CerberusDashboardDAO::_getView($view_id);
			
		} elseif(!empty($_SESSION['search_view'])) {
			$view = $_SESSION['search_view'];
			
		} else {
			$view = new CerberusDashboardView();
			$view->id = 0;
			$view->name = "Search Results";
			$view->dashboard_id = 0;
			$view->view_columns = array(
				CerberusSearchFields::TICKET_MASK,
				CerberusSearchFields::TICKET_STATUS,
				CerberusSearchFields::TICKET_PRIORITY,
				CerberusSearchFields::MAILBOX_NAME,
				CerberusSearchFields::TICKET_LAST_WROTE,
				CerberusSearchFields::TICKET_CREATED_DATE,
				);
			$view->params = array();
			$view->renderLimit = 100;
			$view->renderPage = 0;
			$view->renderSortBy = CerberusSearchFields::TICKET_CREATED_DATE;
			$view->renderSortAsc = 0;
			
			$_SESSION['search_view'] = $view;
		}
		
		return $view;
	}
	
	static function updateView($view_id,$fields) {
		
		if(!empty($view_id)) { // db-driven view
			CerberusDashboardDAO::_updateView($view_id, $fields);
			
		} elseif(!empty($_SESSION['search_view'])) { // virtual view
			$view =& $_SESSION['search_view']; /* @var $view CerberusDashboardView */
			
			foreach($fields as $key => $value) {
				switch($key) {
					case 'name':
						$view->name = $value;
						break;
					case 'view_columns':
						$view->view_columns = unserialize($value);
						break;
					case 'params':
						$view->params = unserialize($value);
						break;
					case 'num_rows':
						$view->renderLimit = intval($value);
						break;
					case 'page':
						$view->renderPage = intval($value);
						break;
					case 'type':
						$view->type = $value;
						break;
					case 'sort_by':
						$view->renderSortBy = $value;
						break;
					case 'sort_asc':
						$view->renderSortAsc = (boolean) $value;
						break;
				}
			}
		}		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $view_id
	 * @return CerberusDashboardView
	 */
	static private function _getView($view_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT v.id, v.name, v.dashboard_id, v.type, v.agent_id, v.view_columns, v.num_rows, v.sort_by, v.sort_asc, v.page, v.params ".
			"FROM dashboard_view v ".
			"WHERE v.id = %d ",
			$view_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(!$rs->EOF) {
			$view = new CerberusDashboardView();
			$view->id = $rs->fields['id'];
			$view->name = $rs->fields['name'];
			$view->dashboard_id = intval($rs->fields['dashboard_id']);
			$view->type = $rs->fields['type'];
			$view->agent_id = intval($rs->fields['agent_id']);
			$view->view_columns = unserialize($rs->fields['view_columns']);
			$view->params = unserialize($rs->fields['params']);
			$view->renderLimit = intval($rs->fields['num_rows']);
			$view->renderSortBy = $rs->fields['sort_by'];
			$view->renderSortAsc = intval($rs->fields['sort_asc']);
			$view->renderPage = intval($rs->fields['page']);
			$views[$view->id] = $view; 
			return $view;
		}
		
		return null;
	}
	
};

class DAO_DashboardViewAction extends DevblocksORMHelper {
	static $properties = array(
		'table' => 'dashboard_view_action',
		'id_column' => 'id'
	);

	static public $FIELD_ID = 'id';
	static public $FIELD_VIEW_ID = 'dashboard_view_id';
	static public $FIELD_NAME = 'name';
	static public $FIELD_WORKER_ID = 'worker_id';
	static public $FIELD_PARAMS = 'params';
	
	/**
	 * Create a DAO entity.
	 *
	 * @return integer
	 */
	static function create() {
		return parent::_createId(self::$properties);
	}

	/**
	 * Update a DAO entity.
	 *
	 * @param integer $id
	 * @param array $fields
	 */
	static function update($id, $fields) {
		parent::_update($id,self::$properties['table'],$fields);
	}
	
	/**
	 * Get multiple DAO entities.
	 *
	 * @param array $ids
	 * @return Model_DashboardViewAction[]
	 */
	static function getList($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		$objects = array();
		
		$sql = sprintf("SELECT id, dashboard_view_id, name, worker_id, params ".
			"FROM %s ".
			(!empty($ids) ? sprintf("WHERE id IN (%s) ",implode(',',$ids)) : ""),
				self::$properties['table']
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if($rs->NumRows())
		while(!$rs->EOF) {
			$object = new Model_DashboardViewAction();
			$object->id = intval($rs->Fields('id'));
			$object->dashboard_view_id = intval($rs->Fields('dashboard_view_id'));
			$object->name = $rs->Fields('name');
			$object->worker_id = intval($rs->Fields('worker_id'));
			
			$params = $rs->Fields('params');
			$object->params = !empty($params) ? unserialize($params) : array();
			
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	/**
	 * Get a single DAO entity.
	 *
	 * @param integer $id
	 * @return Model_DashboardViewAction
	 */
	static function get($id) {
		if(empty($id)) return NULL;
		
		$results = self::getList(array($id));
		
		if(isset($results[$id])) 
			return $results[$id];
			
		return NULL;
	}
	
	/**
	 * Delete a DAO entity.
	 *
	 * @param integer $id
	 */
	static function delete($id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM %s WHERE %s = %d",
			self::$properties['table'],
			self::$properties['id_column'],
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		// [TODO]: Don't forget to also cascade deletes for foreign keys.
	}
	
};

class CerberusMailRuleDAO {
	private function CerberusMailRuleDAO() {}
	
	/**
	 * creates a new mail rule
	 *
	 * @param CerberusMailRuleCriterion[] $criteria
	 * @param string $sequence
	 * @param string $strictness
	 */
	static function createMailRule ($criteria, $sequence, $strictness) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$newId = $um_db->GenID('generic_seq');
		
		$sCriteria = serialize($criteria); // Flatten criterion array into a string
		
		$sql = sprintf("INSERT INTO mail_rule (id, criteria, sequence, strictness) ".
			"VALUES (%d, %s, %s, %s)",
			$newId,
			$um_db->qstr($sCriteria),
			$um_db->qstr($sequence),
			$um_db->qstr($strictness)
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg());
	}
	
	/**
	 * deletes a mail rule from the database
	 *
	 * @param integer $id
	 */
	static function deleteMailRule ($id) {
		if(empty($id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM mail_rule WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg());
	}
	
	/**
	 * returns the mail rule with the given id
	 *
	 * @param integer $id
	 * @return CerberusMailRule
	 */
	static function getMailRule ($id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT m.id, m.criteria, m.sequence, m.strictness ".
			"FROM mail_rule m ".
			"WHERE m.id = %d",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg());
		
		$mailRule = new CerberusMailRule();
		while(!$rs->EOF) {
			$mailRule->id = intval($rs->fields['id']);
			$mailRule->sequence = $rs->fields['sequence'];
			$mailRule->strictness = $rs->fields['strictness'];
			
			$criteria = unserialize($rs->fields['criteria']);
			$mailRule->criteria = $criteria;

			$mailRules[$mailRule->id] = $mailRule;
			$rs->MoveNext();
		}
		
		return $mailRule;
	}
	
	/**
	 * returns an array of all mail rules
	 *
	 * @return CerberusMailRule[]
	 */
	static function getMailRules () {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT m.id, m.criteria, m.sequence, m.strictness ".
			"FROM mail_rule m"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg());
		
		$mailRules = array();
		
		while(!$rs->EOF) {
			$mailRule = new CerberusMailRule();
			$mailRule->id = intval($rs->fields['id']);
			$mailRule->sequence = $rs->fields['sequence'];
			$mailRule->strictness = $rs->fields['strictness'];
			
			$criteria = unserialize($rs->fields['criteria']);
			$mailRule->criteria = $criteria;

			$mailRules[$mailRule->id] = $mailRule;
			$rs->MoveNext();
		}
		
		return $mailRules;
	}
	
	/**
	 * update changed fields on a mail rule
	 *
	 * @param integer $id
	 * @param associative array $fields
	 */
	static function updateMailRule ($id, $fields) {
		$um_db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE mail_rule SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg());
	}
};

/**
 * Enter description here...
 * 
 * @addtogroup dao
 */
class CerberusSearchDAO {
	// [JAS]: [TODO] Implement Agent ID lookup
	// [JAS]: [TODO] Move to a single getViewsById
	
	/**
	 * Enter description here...
	 *
	 * @param CerberusSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @return array
	 * [TODO]: Fold back into DAO_Ticket
	 */
	static function searchTickets($params,$limit=10,$page=0,$sortBy=null,$sortAsc=null) {
		$um_db = DevblocksPlatform::getDatabaseService();

		$fields = CerberusSearchFields::getFields();
		$start = min($page * $limit,1);
		
		$results = array();
		$tables = array();
		$wheres = array();
		
		// [JAS]: Search Builder
		if(is_array($params))
		foreach($params as $param) { /* @var $param CerberusSearchCriteria */
			if(!is_a($param,'CerberusSearchCriteria')) continue;
			$where = "";
			
			// [JAS]: Filter allowed columns (ignore invalid/deprecated)
			if(!isset($fields[$param->field]))
				continue;

			$db_field_name = $fields[$param->field]->db_table . '.' . $fields[$param->field]->db_column; 
			
			// [JAS]: Indexes for optimization
			$tables[$fields[$param->field]->db_table] = $fields[$param->field]->db_table;
				
			// [JAS]: Operators
			switch($param->operator) {
				case "=":
					$where = sprintf("%s = %s",
						$db_field_name,
						$um_db->qstr($param->value)
					);
					break;
					
				case "!=":
					$where = sprintf("%s != %s",
						$db_field_name,
						$um_db->qstr($param->value)
					);
					break;
				
				case "in":
					if(!is_array($param->value)) break;
					$where = sprintf("%s IN ('%s')",
						$db_field_name,
						implode("','",$param->value)
					);
					break;
					
				case "like":
//					if(!is_array($param->value)) break;
					$where = sprintf("%s LIKE %s",
						$db_field_name,
						$um_db->qstr(str_replace('*','%%',$param->value))
					);
					break;
					
				default:
					break;
			}
			
			if(!empty($where)) $wheres[] = $where;
		}
		
		// [JAS]: 1-based [TODO] clean up + document
		$start = ($page * $limit);
		
		$sql = sprintf("SELECT ".
			"t.id as t_id, ".
			"t.mask as t_mask, ".
			"t.subject as t_subject, ".
			"t.status as t_status, ".
			"t.priority as t_priority, ".
			"t.mailbox_id as t_mailbox_id, ".
			"a1.email as t_first_wrote, ".
			"a2.email as t_last_wrote, ".
			"t.created_date as t_created_date, ".
			"t.updated_date as t_updated_date, ".
			"t.spam_score as t_spam_score, ".
			"m.id as m_id, ".
			"m.name as m_name ".
			"FROM ticket t ".
			"INNER JOIN mailbox m ON (t.mailbox_id=m.id) ".
			"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
			"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) ".
			
			// [JAS]: Dynamic table joins
			(isset($tables['att']) ? "LEFT JOIN assign_to_ticket att ON (att.ticket_id=t.id AND att.is_flag = 1) " : " ").
			(isset($tables['stt']) ? "LEFT JOIN assign_to_ticket stt ON (stt.ticket_id=t.id AND stt.is_flag = 0) " : " ").
			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
			(isset($tables['msg']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			"GROUP BY t.id ".
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		);
		$rs = $um_db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			// [JAS]: [TODO] This needs to change to an intermediary search row object.
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($rs->fields[CerberusSearchFields::TICKET_ID]);
			$results[$ticket_id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$rs = $um_db->Execute($sql);
		$total = $rs->RecordCount();
		
		return array($results,$total);
	}	
	
	/**
	 * Enter description here...
	 *
	 * @param CerberusSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @return array
	 * 
	 * @todo [TODO] This and the ticket search could really share a lot of the operator/field functionality 
	 */
	static function searchResources($params,$limit=10,$page=0,$sortBy=null,$sortAsc=null) {
		$um_db = DevblocksPlatform::getDatabaseService();

		$fields = CerberusResourceSearchFields::getFields();
		$start = min($page * $limit,1);
		
		$results = array();
		$tables = array();
		$wheres = array();
		
		// [JAS]: Search Builder
		if(is_array($params))
		foreach($params as $param) { /* @var $param CerberusSearchCriteria */
			if(!is_a($param,'CerberusSearchCriteria')) continue;
			$where = "";
			
			// [JAS]: Filter allowed columns (ignore invalid/deprecated)
			if(!isset($fields[$param->field]))
				continue;

			$db_field_name = $fields[$param->field]->db_table . '.' . $fields[$param->field]->db_column; 
			
			// [JAS]: Indexes for optimization
			$tables[$fields[$param->field]->db_table] = $fields[$param->field]->db_table;
				
			// [JAS]: Operators
			switch($param->operator) {
				case "=":
					$where = sprintf("%s = %s",
						$db_field_name,
						$um_db->qstr($param->value)
					);
					break;
					
				case "!=":
					$where = sprintf("%s != %s",
						$db_field_name,
						$um_db->qstr($param->value)
					);
					break;
				
				case "in":
					if(!is_array($param->value)) break;
					$where = sprintf("%s IN ('%s')",
						$db_field_name,
						implode("','",$param->value)
					);
					break;
					
				case "like":
//					if(!is_array($param->value)) break;
					$where = sprintf("%s LIKE %s",
						$db_field_name,
						$um_db->qstr(str_replace('*','%%',$param->value))
					);
					break;
					
				default:
					break;
			}
			
			if(!empty($where)) $wheres[] = $where;
		}
		
		// [JAS]: 1-based [TODO] clean up + document
		$start = ($page * $limit);
		
		$sql = sprintf("SELECT ".
			"kb.id as kb_id, ".
			"kb.title as kb_title, ".
			"kb.type as kb_type ".
			"FROM kb ".
			
			// [JAS]: Dynamic table joins
			(isset($tables['kbc']) ? "INNER JOIN kb_content kbc ON (kbc.kb_id=kb.id) " : " ").
			(isset($tables['kbcat']) ? "LEFT JOIN kb_to_category kbtc ON (kbtc.kb_id=kb.id) " : " ").
			(isset($tables['kbcat']) ? "LEFT JOIN kb_category kbcat ON (kbcat.id=kbtc.category_id) " : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			"GROUP BY kb.id ".
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		);
		$rs = $um_db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$result = array();
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[CerberusResourceSearchFields::KB_ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$rs = $um_db->Execute($sql);
		$total = $rs->RecordCount();
		
		return array($results,$total);
	}	
	
	/**
	 * Enter description here...
	 *
	 * @param integer $agent_id
	 * @return CerberusDashboardView[]
	 */
	static function getSavedSearches($agent_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$searches = array();
		
		$sql = sprintf("SELECT v.id, v.name, v.dashboard_id, v.type, v.agent_id, v.view_columns, v.num_rows, v.sort_by, v.sort_asc, v.page, v.params ".
			"FROM dashboard_view v ".
			"WHERE v.type = 'S' "
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */

		while(!$rs->EOF) {
			$view = new CerberusDashboardView();
			$view->id = $rs->fields['id'];
			$view->name = $rs->fields['name'];
			$view->dashboard_id = intval($rs->fields['dashboard_id']);
			$view->type = $rs->fields['type'];
			$view->agent_id = intval($rs->fields['agent_id']);
			$view->columns = unserialize($rs->fields['view_columns']);
			$view->params = unserialize($rs->fields['params']);
			$view->renderLimit = intval($rs->fields['num_rows']);
			$view->renderSortBy = $rs->fields['sort_by'];
			$view->renderSortAsc = intval($rs->fields['sort_asc']);
			$view->renderPage = intval($rs->fields['page']);
			$searches[$view->id] = $view; 
			$rs->MoveNext();
		}
		
		return $searches;
	}
};

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class CerberusWorkflowDAO {
	
	/**
	 * Enter description here...
	 *
	 * @param string $tag_name
	 * @param boolean $create_if_notexist
	 * @return CerberusTag
	 */
	static function lookupTag($tag_name, $create_if_notexist=false) {
		if(empty($tag_name)) return null;
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$tag = null;

		$sql = sprintf("SELECT t.id FROM tag t WHERE t.name = %s",
			$um_db->qstr($tag_name)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$id = intval($rs->fields['id']);
		} elseif($create_if_notexist) {
			$id = CerberusWorkflowDAO::createTag($tag_name);
		}
		
		if(!empty($id)) {
			$tag = CerberusWorkflowDAO::getTag($id);
		}
		
		return $tag;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return CerberusTag[]
	 */
	static function getTags($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);

		$um_db = DevblocksPlatform::getDatabaseService();
		$tags = array();

		$sql = "SELECT t.id, t.name ".
			"FROM tag t ".
			((!empty($ids) ? sprintf("WHERE t.id IN (%s)",implode(',', $ids)) : " ").
			"ORDER BY t.name"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$tag = new CerberusTag();
			$tag->id = intval($rs->fields['id']);
			$tag->name = $rs->fields['name'];
			$tags[$tag->id] = $tag;
			$rs->MoveNext();
		}
		
		return $tags;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return CerberusTag[]
	 */
	static function getTagsByTicket($id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$ids = array();
		$tags = array();
		
		$sql = sprintf("SELECT tt.tag_id ".
			"FROM tag_to_ticket tt ".
			"INNER JOIN tag t ON (tt.tag_id=t.id) ".
			"WHERE tt.ticket_id = %d ".
			"ORDER BY t.name",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['tag_id']);
			$rs->MoveNext();
		}
		
		if(!empty($ids)) {
			$tags = CerberusWorkflowDAO::getTags($ids); 
		}
		
		return $tags;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $ticket_id
	 * @param boolean $is_flag
	 * @return CerberusAgent[]
	 */
	static function getWorkersByTicket($ticket_id, $is_flag) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$ids = array();
		$workers = array();
		
		$sql = sprintf("SELECT at.agent_id ".
			"FROM assign_to_ticket at ".
			"WHERE at.ticket_id = %d ".
			"AND at.is_flag = %d",
			$ticket_id,
			($is_flag) ? 1 : 0
		);
		$rs= $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['agent_id']);
			$rs->MoveNext();
		}

		if(!empty($ids)) {
			$workers = CerberusAgentDAO::getAgents($ids);
		}
		
		return $workers;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return CerberusTag
	 */
	static function getTag($id) {
		$tags = CerberusWorkflowDAO::getTags(array($id));
		
		if(isset($tags[$id]))
			return $tags[$id];
			
		return null;
	}
	
	static function getSuggestedTags($ticket_id,$limit=10) {
		if(empty($ticket_id)) return array();
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$tags = array();
		
		$msgs = CerberusTicketDAO::getMessagesByTicket($ticket_id);
		if(!is_array($msgs[0])) return array();
		
		$msg = array_shift($msgs[0]); /* @var $msg CerberusMessage */
		$content = $msg->getContent();
		
		// [JAS]: [TODO] This could get out of control fast
		$terms = CerberusWorkflowDAO::getTagTerms();

		foreach($terms as $term) {
			if(FALSE === stristr($content,$term->term)) continue;
			$tags[$term->tag_id] = intval($tags[$term->tag_id]) + 1;
		}
		
		arsort($tags);
		$tags = array_slice($tags,0,$limit,true);
		
		unset($terms);
		
		if(empty($tags))
			return array();
		
		return CerberusWorkflowDAO::getTags(array_keys($tags));
	}
	
	static function searchTags($query,$limit=10) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($query)) return null;
		
		$sql = sprintf("SELECT t.id FROM tag t WHERE t.name LIKE '%s%%' LIMIT 0,%d",
			$query,
			$limit
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$ids = array();
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
			
		return CerberusWorkflowDAO::getTags($ids);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $name
	 * @return integer id
	 */
	static function createTag($name) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($name)) return null;
		
		$id = $um_db->GenID('tag_seq');
		
		$sql = sprintf("INSERT INTO tag (id, name) ".
			"VALUES (%d, %s)",
			$id,
			$um_db->qstr(strtolower($name))
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @param array $fields
	 */
	static function updateTag($id, $fields) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE tag SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	static function setTagTerms($id, $terms) {
		if(empty($id)) return null;
		
		$um_db = DevblocksPlatform::getDatabaseService();

		// [JAS]: Clear previous terms
		$um_db->Execute(sprintf("DELETE FROM tag_term WHERE tag_id = %d", $id));
		
		if(is_array($terms))
		foreach($terms as $v) {
			$term = trim($v);
			if(empty($term)) continue;
			$um_db->Replace('tag_term', array('tag_id'=>$id,'term'=>$um_db->qstr($term)),array('tag_id','term'),false);
		}
	}
	
	static function getTagTerms($id=null) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$terms = array();
		
		$sql = "SELECT tag_id, term ".
			"FROM tag_term ".
			((!empty($id)) ? sprintf("WHERE tag_id = %d ",$id) : " "). 
			"ORDER BY term ASC";
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$term = new CerberusTagTerm();
			$term->tag_id = intval($rs->fields['tag_id']);
			$term->term = $rs->fields['term'];
			$terms[] = $term;
			$rs->MoveNext();
		}
		
		return $terms;
	}
	
	static function deleteTag($id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($id)) return;
		
		$sql = sprintf("DELETE FROM tag WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM favorite_tag_to_worker WHERE tag_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM tag_to_ticket WHERE tag_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	// Teams
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return CerberusTeam
	 */
	static function getTeam($id) {
		$teams = CerberusWorkflowDAO::getTeams(array($id));
		
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
		$um_db = DevblocksPlatform::getDatabaseService();

		$teams = array();
		
		$sql = sprintf("SELECT t.id , t.name ".
			"FROM team t ".
			((!empty($ids)) ? sprintf("WHERE t.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY t.name ASC"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$team = new CerberusTeam();
			$team->id = intval($rs->fields['id']);
			$team->name = $rs->fields['name'];
			$teams[$team->id] = $team;
			$rs->MoveNext();
		}
		
		return $teams;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $name
	 * @return integer
	 */
	static function createTeam($name) {
		if(empty($name))
			return;
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO team (id, name) VALUES (%d,%s)",
			$newId,
			$um_db->qstr($name)
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}

	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @param array $fields
	 */
	static function updateTeam($id, $fields) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE team SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 */
	static function deleteTeam($id) {
		if(empty($id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM team WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM mailbox_to_team WHERE team_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM worker_to_team WHERE team_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function setTeamMailboxes($team_id, $mailbox_ids) {
		if(!is_array($mailbox_ids)) $mailbox_ids = array($mailbox_ids);
		if(empty($team_id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM mailbox_to_team WHERE team_id = %d",
			$team_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		foreach($mailbox_ids as $mailbox_id) {
			$sql = sprintf("INSERT INTO mailbox_to_team (mailbox_id, team_id, is_routed) ".
				"VALUES (%d,%d,%d)",
				$mailbox_id,
				$team_id,
				1
			);
			$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
	}
	
	static function getTeamMailboxes($team_id, $with_counts = false) {
		if(empty($team_id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();
		$ids = array();
		
		$sql = sprintf("SELECT mt.mailbox_id FROM mailbox_to_team mt WHERE mt.team_id = %d",
			$team_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['mailbox_id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
		
		return CerberusMailDAO::getMailboxes($ids, $with_counts);
	}
	
	static function setTeamWorkers($team_id, $agent_ids) {
		if(!is_array($agent_ids)) $agent_ids = array($agent_ids);
		if(empty($team_id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("DELETE FROM worker_to_team WHERE team_id = %d",
			$team_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		foreach($agent_ids as $agent_id) {
			$sql = sprintf("INSERT INTO worker_to_team (agent_id, team_id) ".
				"VALUES (%d,%d)",
				$agent_id,
				$team_id
			);
			$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
	}
	
	static function getTeamWorkers($team_id) {
		if(empty($team_id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();
		$ids = array();
		
		$sql = sprintf("SELECT wt.agent_id FROM worker_to_team wt WHERE wt.team_id = %d",
			$team_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['agent_id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
		
		return CerberusAgentDAO::getAgents($ids);
	}
	
	
}

class CerberusMailDAO {
	// Mailboxes
	
	const MAILBOX_ID = 'id';
	const MAILBOX_NAME = 'name';
	const MAILBOX_REPLY_ADDRESS_ID = 'reply_address_id';
	const MAILBOX_DISPLAY_NAME = 'display_name';
	
	/**
	 * Returns a list of all known mailboxes, sorted by name
	 *
	 * @return CerberusMailbox[]
	 */
	static function getMailboxes($ids=array(), $with_counts = false) {
		if(!is_array($ids)) $ids = array($ids);
		$um_db = DevblocksPlatform::getDatabaseService();

		$mailboxes = array();

		$sql = sprintf("SELECT m.id , m.name, m.reply_address_id, m.display_name, m.close_autoresponse, m.new_autoresponse ".
			"FROM mailbox m ".
			((!empty($ids)) ? sprintf("WHERE m.id IN (%s) ",implode(',', $ids)) : " ").
			"ORDER BY m.name ASC"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$mailbox = new CerberusMailbox();
			$mailbox->id = intval($rs->fields['id']);
			$mailbox->name = $rs->fields['name'];
			$mailbox->reply_address_id = $rs->fields['reply_address_id'];
			$mailbox->display_name = $rs->fields['display_name'];
			$mailbox->close_autoresponse = $rs->fields['close_autoresponse'];
			$mailbox->new_autoresponse = $rs->fields['new_autoresponse'];
			$mailboxes[$mailbox->id] = $mailbox;
			$rs->MoveNext();
		}
		
		// add per-mailbox counts of open tickets if requested
		if ($with_counts === true) {
			foreach ($mailboxes as $mailbox) {
				$sql = sprintf("SELECT COUNT(t.id) as ticket_count ".
					"FROM ticket t ".
					"WHERE t.mailbox_id = %d AND t.status = 'O'",
					$mailbox->id
				);
				
				$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
				
				while(!$rs->EOF) {
					$mailbox->count = intval($rs->fields['ticket_count']);
					$rs->MoveNext();
				}			
			}
		}

		return $mailboxes;
	}
	
	/**
	 * creates a new mailbox in the database
	 *
	 * @param string $name
	 * @param integer $reply_address_id
	 * @param string $display_name
	 * @return integer
	 */
	static function createMailbox($name, $reply_address_id, $display_name = '', $close_autoresponse = '', $new_autoresponse = '') {
		$um_db = DevblocksPlatform::getDatabaseService();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO mailbox (id, name, reply_address_id, display_name, close_autoresponse, new_autoresponse) VALUES (%d,%s,%d,%s,%s,%s)",
			$newId,
			$um_db->qstr($name),
			$reply_address_id,
			$um_db->qstr($display_name),
			$um_db->qstr($close_autoresponse),
			$um_db->qstr($new_autoresponse)
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	static function updateMailbox($id, $fields) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE mailbox SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	static function getMailbox($id) {
		if(empty($id)) return null;
		$mailboxes = CerberusMailDAO::getMailboxes(array($id));
		
		if(isset($mailboxes[$id]))
			return $mailboxes[$id];
			
		return null;
	}
	
	static function deleteMailbox($id) {
		if(empty($id)) return;
		
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM mailbox WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM mailbox_to_team WHERE mailbox_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	
	static function setMailboxTeams($mailbox_id, $team_ids) {
		if(!is_array($team_ids)) $team_ids = array($team_ids);
		if(empty($mailbox_id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("DELETE FROM mailbox_to_team WHERE mailbox_id = %d",
			$mailbox_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		foreach($team_ids as $team_id) {
			$sql = sprintf("INSERT INTO mailbox_to_team (mailbox_id, team_id, is_routed) ".
				"VALUES (%d,%d,%d)",
				$mailbox_id,
				$team_id,
				1
			);
			$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
	}
	
	static function getMailboxTeams($mailbox_id) {
		if(empty($mailbox_id)) return;
		$um_db = DevblocksPlatform::getDatabaseService();
		$ids = array();
		
		$sql = sprintf("SELECT mt.team_id FROM mailbox_to_team mt WHERE mt.mailbox_id = %d",
			$mailbox_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['team_id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
			
		return CerberusWorkflowDAO::getTeams($ids);
	}
	
	static function getMailboxRouting() {
		$um_db = DevblocksPlatform::getDatabaseService();
		$routing = array();
		
		$sql = "SELECT am.address_id, am.mailbox_id ".
			"FROM address_to_mailbox am ".
			"INNER JOIN address a ON (a.id=am.address_id) ".
			"ORDER BY a.email ";
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$address_id = intval($rs->fields['address_id']);
			$mailbox_id = intval($rs->fields['mailbox_id']);
			$routing[$address_id] = $mailbox_id;
			$rs->MoveNext();
		}
		
		return $routing;
	}
	
	static function setMailboxRouting($address_id, $mailbox_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		return $um_db->Replace('address_to_mailbox', array('address_id'=>$address_id,'mailbox_id'=>$mailbox_id),array('address_id'));
	}
	
	static function deleteMailboxRouting($address_id) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($address_id)) return;
		
		$sql = sprintf("DELETE FROM address_to_mailbox WHERE address_id = %d",
			$address_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function searchAddresses($query, $limit=10) {
		$um_db = DevblocksPlatform::getDatabaseService();
		if(empty($query)) return null;
		
		$sql = sprintf("SELECT a.id FROM address a WHERE a.email LIKE '%s%%' LIMIT 0,%d",
			$query,
			$limit
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$ids = array();
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
			
		return CerberusContactDAO::getAddresses($ids);
	}
	
	static function sendAutoresponse($ticket_id, $type) {
		$mailMgr = DevblocksPlatform::getMailService();
		$ticket = CerberusTicketDAO::getTicket($ticket_id);  /* @var $ticket CerberusTicket */
		$mailbox = CerberusMailDAO::getMailbox($ticket->mailbox_id);  /* @var $mailbox CerberusMailbox */
		
		$body = '';
		switch ($type) {
			case 'new':
				$body = CerberusMailDAO::getTokenizedText($ticket_id, $mailbox->new_autoresponse);
				break;
			case 'closed':
				$body = CerberusMailDAO::getTokenizedText($ticket_id, $mailbox->close_autoresponse);
				break;
		}
		if (0 == strcmp($body, '')) return 0; // if there's no body, we must not need to send an autoresponse.
		
		$headers = CerberusMailDAO::getHeaders(CerberusMessageType::AUTORESPONSE, $ticket_id);
		
		$mail_result =& $mailMgr->send('mail.webgroupmedia.com', $headers['x-rcpt'], $headers, $body); // DDH: TODO: this needs to pull the servername from a config, not hardcoded.
		if ($mail_result !== true) die("Error message was: " . $mail_result->getMessage());
	}
	
	static function getTokenizedText($ticket_id, $source_text) {
		// TODO: actually implement this function...
		return $source_text;
	}
	
	static function getHeaders($type, $ticket_id = 0) {
		// variable loading
		@$id		= DevblocksPlatform::importGPC($_REQUEST['id']); // message id
		@$to		= DevblocksPlatform::importGPC($_REQUEST['to']);
		@$cc		= DevblocksPlatform::importGPC($_REQUEST['cc']);
		@$bcc		= DevblocksPlatform::importGPC($_REQUEST['bcc']);
		
		// object loading
		if (!empty($id)) {
			$message	= CerberusTicketDAO::getMessage($id);
			if ($ticket_id == 0)
				$ticket_id	= $message->ticket_id;
		} else {
			$messages = CerberusTicketDAO::getMessagesByTicket($ticket_id);
			if ($messages[1] > 0) $message = $messages[0][0];
		}
		$ticket		= CerberusTicketDAO::getTicket($ticket_id);						/* @var $ticket CerberusTicket */
		$mailbox	= CerberusMailDAO::getMailbox($ticket->mailbox_id);				/* @var $mailbox CerberusMailbox */
		$address	= CerberusContactDAO::getAddress($mailbox->reply_address_id);	/* @var $address CerberusAddress */
		$requesters	= CerberusTicketDAO::getRequestersByTicket($ticket_id);			/* @var $requesters CerberusRequester[] */
		
		// requester address parsing - needs to vary based on type
		$sTo = '';
		$sRCPT = '';
		if ($type == CerberusMessageType::EMAIL
		||	$type == CerberusMessageType::AUTORESPONSE ) {
			foreach ($requesters as $requester) {
				if (!empty($sTo)) $sTo .= ', ';
				if (!empty($sRCPT)) $sRCPT .= ', ';
				if (!empty($requester->personal)) $sTo .= $requester->personal . ' ';
				$sTo .= '<' . $requester->email . '>';
				$sRCPT .= $requester->email;
			}
		} else {
			$sTo = $to;
			$sRCPT = $to;
		}

		// header setup: varies based on type of response - BREAK statements intentionally left out!
		$headers = array();
		switch ($type) {
			case CerberusMessageType::FORWARD :
			case CerberusMessageType::EMAIL :
				$headers['cc']			= $cc;
				$headers['bcc']			= $bcc;
			case CerberusMessageType::AUTORESPONSE :
				$headers['to']			= $sTo;
				$headers['x-rcpt']		= $sRCPT;
			case CerberusMessageType::COMMENT :
				// TODO: pull info from mailbox instead of hard-coding it.  (display name cannot be just a personal on a mailbox address...)
				// TODO: differentiate between mailbox from as part of email/forward and agent from as part of comment (may not be necessary, depends on ticket display)
				$headers['from']		= (!empty($mailbox->display_name)) ? '"' . $mailbox->display_name . '" <' . $address->email . '>' : $address->email ;
				$headers['date']		= gmdate(r);
				$headers['message-id']	= CerberusApplication::generateMessageId();
				$headers['subject']		= $ticket->subject;
				$headers['references']	= (!empty($message)) ? $message->headers['message-id'] : '' ;
				$headers['in-reply-to']	= (!empty($message)) ? $message->headers['message-id'] : '' ;
		}
		
		return $headers;
	}
		
	// Pop3 Accounts
	
	// [TODO] Allow custom ports
	static function createPop3Account($nickname,$host,$username,$password) {
		if(empty($nickname) || empty($host) || empty($username) || empty($password)) 
			return null;
			
		$um_db = DevblocksPlatform::getDatabaseService();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO pop3_account (id, nickname, host, username, password) ".
			"VALUES (%d,%s,%s,%s,%s)",
			$newId,
			$um_db->qstr($nickname),
			$um_db->qstr($host),
			$um_db->qstr($username),
			$um_db->qstr($password)
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	static function getPop3Accounts($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$um_db = DevblocksPlatform::getDatabaseService();
		$pop3accounts = array();
		
		$sql = "SELECT id, nickname, host, username, password ".
			"FROM pop3_account ".
			((!empty($ids) ? sprintf("WHERE id IN (%s)", implode(',', $ids)) : " ").
			"ORDER BY nickname "
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$pop3 = new CerberusPop3Account();
			$pop3->id = intval($rs->fields['id']);
			$pop3->nickname = $rs->fields['nickname'];
			$pop3->host = $rs->fields['host'];
			$pop3->username = $rs->fields['username'];
			$pop3->password = $rs->fields['password'];
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
		$accounts = CerberusMailDAO::getPop3Accounts(array($id));
		
		if(isset($accounts[$id]))
			return $accounts[$id];
			
		return null;
	}
	
	static function updatePop3Account($id, $fields) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE pop3_account SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function deletePop3Account($id) {
		if(empty($id))
			return;
			
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM pop3_account WHERE id = %d",
			$id			
		);
		
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
};

class DAO_Kb {
	
	/**
	 * @return integer
	 */
	static function createCategory($name, $parent_id=0) {
		if(empty($name)) return null;
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$id = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO kb_category (id,name,parent_id) ".
			"VALUES (%d,%s,%d)",
			$id,
			$um_db->qstr($name),
			$parent_id
		);
		
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
	static function getCategory($id) {
		$categories = DAO_Kb::getCategories(array($id));
		
		if(isset($categories[$id]))
			return $categories[$id];
			
		return null;
	}
	
	static function getBreadcrumbTrail(&$tree,$id) {
		$trail = array();
		$p = $id;
		do {
			$trail[] =& $tree[$p];
			$p = $tree[$p]->parent_id; 		
		} while($p >= 0);
		$trail = array_reverse($trail,true);
		return $trail;
	}
	
	/*
	 * @return array
	 */
	static private function _getCategoryResourceTotals() {
		$um_db = DevblocksPlatform::getDatabaseService();
		$totals = array();
		
		$sql = sprintf("SELECT kbc.category_id, count(kb.id) as hits ".
			"FROM kb ".
			"INNER JOIN kb_to_category kbc ON (kb.id=kbc.kb_id) ".
			"GROUP BY kbc.category_id"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$id = intval($rs->fields['category_id']);
			$hits = intval($rs->fields['hits']);
			$totals[$id] = $hits;
			$rs->MoveNext();
		}
		
		return $totals;
	}
	
	static function getCategoryTree() {

		// [JAS]: Root node
		$rootNode = new CerberusKbCategory();
		$rootNode->id = 0;
		$rootNode->name = 'Top';
		$rootNode->parent_id = -1;
		
		$tree = DAO_Kb::getCategories();
		$tree[0] = $rootNode;
		
		// [JAS]: Pointer hash
		foreach($tree as $catid => $cat) { /* @var $cat CerberusKbCategory */
			if(isset($tree[$cat->parent_id]) && $cat->parent_id != $catid) {
				$children =& $tree[$cat->parent_id]->children;
				$children[$catid] =& $tree[$catid];
			}
		}

		// [JAS]: Alphabetize children
		foreach($tree as $catid => $cat) { /* @var $cat CerberusKbCategory */
			$func = create_function('$a,$b', 'return strcasecmp($a->name,$b->name);');
			uasort($cat->children, $func);
		}
		
		// [JAS]: Recursively total resources
		$totals = DAO_Kb::_getCategoryResourceTotals();
		foreach($totals as $catid => $hits) {
			$ptrid = $catid;
			do {
				$ptr =& $tree[$ptrid];
				$ptr->hits += $hits;
				$ptrid = $ptr->parent_id;
			} while($ptrid >= 0);
		}
		
		return $tree;
	}
	
	static function buildTreeMap($tree,&$map,$position=0) {
		static $level = 0;
		$node =& $tree[$position];
		
		$level++;
		
		if(is_array($node->children))
		foreach($node->children as $ck => $cv) {
			$map[$ck] = $level;
			DAO_Kb::buildTreeMap($tree,$map,$ck);
		}
		
		$level--;
	}
	
	static function getCategories($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$categories = array();
		
		$sql = "SELECT kc.id, kc.name, kc.parent_id ".
			"FROM kb_category kc ".
			(!empty($ids) ? sprintf("WHERE kc.id IN (%s) ",implode(',', $ids)) : " ").
			"ORDER BY kc.id";
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$category = new CerberusKbCategory();
			$category->id = intval($rs->fields['id']);
			$category->name = $rs->fields['name'];
			$category->parent_id = intval($rs->fields['parent_id']);
			$categories[$category->id] = $category;
			$rs->MoveNext();
		}
		
		return $categories;
	}
	
	static function updateCategory($id, $fields) {
		
	}
	
	static function deleteCategory($id) {
		if(empty($id)) return null;
		$um_db = DevblocksPlatform::getDatabaseService();
		$um_db->Execute(sprintf("DELETE FROM kb_category WHERE id = %d",$id)) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function createResource($title,$type=CerberusKbResourceTypes::ARTICLE) {
		if(empty($title)) return null;
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$id = $um_db->GenID('kb_seq');
		
		$sql = sprintf("INSERT INTO (id,title,type) ".
			"VALUES (%d,%s,%s)",
			$id,
			$um_db->qstr($title),
			$um_db->qstr($type)
		);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
	static function getResource($id) {
		if(empty($id)) return null;
		
		$resources = DAO_Kb::getResources(array($id));
		
		if(isset($resources[$id]))
			return $resources[$id];
			
		return null;
	}
	
	static function getResources($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		
		$um_db = DevblocksPlatform::getDatabaseService();
		$resources = array();
		
		$sql = "SELECT kb.id, kb.title, kb.type ".
			"FROM kb ".
			((!empty($ids)) ? sprintf("WHERE kb.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY kb.title";
		$rs = $um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$resource = new CerberusKbResource();
			$resource->id = intval($rs->fields['id']);
			$resource->title = $rs->fields['title'];
			$resource->type = $rs->fields['type'];
			$resources[$resource->id] = $resource;
			$rs->MoveNext();
		}
			
		return $resources;		
	}
	
	static function updateResource($id, $fields) {
		
	}
	
	static function deleteResource($id) {
		if(empty($id)) return null;
		$um_db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM kb WHERE id = %d",$id);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM kb_content WHERE kb_id = %d",$id);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM kb_to_category WHERE kb_id = %d",$id);
		$um_db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @param string $content
	 */
	static function setResourceContent($id, $content) {
		$um_db = DevblocksPlatform::getDatabaseService();
		$um_db->Replace('kb_content',array('kb_id'=>$id,'content'=>$um_db->qstr($content)),array('kb_id'),false);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return string
	 */
	static function getResourceContent($id) {
		$content = "Content";
		return $content;
	}
	
};

?>
