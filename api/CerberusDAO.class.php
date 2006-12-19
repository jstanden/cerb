<?php

class CerberusAgentDAO {
	private function CerberusAgentDAO() {}
	
	static function createAgent($login, $password, $admin=0) {
		if(empty($login) || empty($password))
			return null;
			
		$um_db = UserMeetDatabase::getInstance();
		$id = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO login (id, login, password, admin) ".
			"VALUES (%d, %s, %s, %d)",
			$id,
			$um_db->qstr($login),
			$um_db->qstr(md5($password)),
			$admin
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
	static function getAgents($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		
		$um_db = UserMeetDatabase::getInstance();
		$agents = array();
		
		$sql = "SELECT a.id, a.login, a.password, a.admin ".
			"FROM login a ".
			((!empty($ids) ? sprintf("WHERE a.id IN (%s)",implode(',',$ids)) : " ").
			"ORDER BY a.login "
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$agent = new CerberusAgent();
			$agent->id = intval($rs->fields['id']);
			$agent->login = $rs->fields['login'];
			$agent->admin = intval($rs->fields['admin']);
			$agents[$agent->id] = $agent;
			$rs->MoveNext();
		}
		
		return $agents;		
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
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT a.id FROM login a WHERE a.login = %s",
			$um_db->qstr($login)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			return intval($rs->fields['id']);
		}
		
		return null;		
	}
	
	static function updateAgent($id, $fields) {
		$um_db = UserMeetDatabase::getInstance();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE login SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	static function deleteAgent($id) {
		if(empty($id)) return;
		
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM login WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM worker_to_team WHERE agent_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM favorite_tag_to_worker WHERE agent_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	static function setAgentTeams($agent_id, $team_ids) {
		if(!is_array($team_ids)) $team_ids = array($team_ids);
		if(empty($agent_id)) return;
		$um_db = UserMeetDatabase::getInstance();

		$sql = sprintf("DELETE FROM worker_to_team WHERE agent_id = %d",
			$agent_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		foreach($team_ids as $team_id) {
			$sql = sprintf("INSERT INTO worker_to_team (agent_id, team_id) ".
				"VALUES (%d,%d)",
				$agent_id,
				$team_id
			);
			$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
	}
	
	static function getAgentTeams($agent_id) {
		if(empty($agent_id)) return;
		$um_db = UserMeetDatabase::getInstance();
		$ids = array();
		
		$sql = sprintf("SELECT wt.team_id FROM worker_to_team wt WHERE wt.agent_id = %d",
			$agent_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['team_id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
		
		return CerberusWorkflowDAO::getTeams($ids);
	}
	
	static function getFavoriteTags($agent_id) {
		$um_db = UserMeetDatabase::getInstance();
		if(empty($agent_id)) return null;
		
		$ids = array();
		
		$sql = sprintf("SELECT tag_id FROM favorite_tag_to_worker WHERE agent_id = %d",
			$agent_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['tag_id']);
			$rs->MoveNext();
		}

		if(empty($ids))
			return array();
		
		return CerberusWorkflowDAO::getTags($ids);
	}
	
	static function setFavoriteTags($agent_id, $tag_string) {
		$um_db = UserMeetDatabase::getInstance();
		if(empty($agent_id)) return null;
		
		$sql = sprintf("DELETE FROM favorite_tag_to_worker WHERE agent_id = %d",
			$agent_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
			$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */	
		}
		
	}
	
	static function getFavoriteWorkers($agent_id) {
		$um_db = UserMeetDatabase::getInstance();
		if(empty($agent_id)) return null;
		
		$ids = array();
		
		$sql = sprintf("SELECT worker_id FROM favorite_worker_to_worker WHERE agent_id = %d",
			$agent_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['worker_id']);
			$rs->MoveNext();
		}

		if(empty($ids))
			return array();
		
		return CerberusAgentDAO::getAgents($ids);
	}
	
	static function setFavoriteWorkers($agent_id, $worker_string) {
		$um_db = UserMeetDatabase::getInstance();
		if(empty($agent_id)) return null;
		
		$sql = sprintf("DELETE FROM favorite_worker_to_worker WHERE agent_id = %d",
			$agent_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
			$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */	
		}
		
	}
	
}

class CerberusContactDAO {
	private function CerberusContactDAO() {}
	
	static function lookupAddress($email,$create_if_null=false) {
		$um_db = UserMeetDatabase::getInstance();
		$id = null;
		
		$sql = sprintf("SELECT id FROM address WHERE email = %s",
			$um_db->qstr(trim(strtolower($email)))
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$id = $rs->fields['id'];
		} elseif($create_if_null) {
			$id = CerberusContactDAO::createAddress($email);
		}
		
		return $id;
	}
	
	static function getAddresses($ids=array()) {
		$um_db = UserMeetDatabase::getInstance();
		if(!is_array($ids)) $ids = array($ids);
		$addresses = array();
		
		$sql = sprintf("SELECT a.id, a.email, a.personal, a.bitflags ".
			"FROM address a ".
			((!empty($ids)) ? "WHERE a.id IN (%s) " : " ").
			"ORDER BY a.email ",
			implode(',', $ids)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
	
	static function getAddress($id) {
		if(empty($id)) return null;
		
		$addresses = CerberusContactDAO::getAddresses(array($id));
		
		if(isset($addresses[$id]))
			return $addresses[$id];
			
		return null;		
	}

	static function getMailboxIdByAddress($email) {
		$um_db = UserMeetDatabase::getInstance();
		$id = CerberusContactDAO::lookupAddress($email,false);
		$mailbox_id = null;
		
		if(empty($id))
			return null;
		
		$sql = sprintf("SELECT am.mailbox_id FROM address_to_mailbox am WHERE am.address_id = %d",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$mailbox_id = intval($rs->fields['mailbox_id']);
		}
		
		return $mailbox_id;
	}
	
	/**
	 * creates an address entry in the database if it doesn't exist already
	 *
	 * @param string $email
	 * @param string $personal
	 * @return integer
	 * @throws exception on invalid address
	 */
	static function createAddress($email,$personal='') {
		$um_db = UserMeetDatabase::getInstance();
		
		if(null != ($id = CerberusContactDAO::lookupAddress($email,false)))
			return $id;

		$id = $um_db->GenID('address_seq');
		
		require_once(UM_PATH . '/libs/pear/Mail/RFC822.php');
		if (false === Mail_RFC822::isValidInetAddress($email)) {
//			throw new Exception($email . UserMeetTranslationManager::say('ticket.requester.invalid'));
			return null;
		}
		
		$sql = sprintf("INSERT INTO address (id,email,personal) VALUES (%d,%s,%s)",
			$id,
			$um_db->qstr(trim(strtolower($email))),
			$um_db->qstr($personal)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
}

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class CerberusTicketDAO {
	
	private function CerberusTicketDAO() {}
	
	/**
	 * Enter description here...
	 *
	 * @param string $mask
	 * @return CerberusTicket
	 */
	static function getTicketByMask($mask) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT t.id FROM ticket t WHERE t.mask = %s",
			$um_db->qstr($mask)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$ticket_id = intval($rs->fields['id']);
			return CerberusTicketDAO::getTicket($ticket_id);
		}
		
		return null;
	}
	
	static function getTicketByMessageId($message_id) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT t.id ".
			"FROM ticket t ".
			"INNER JOIN message m ON (t.id=m.ticket_id) ".
			"WHERE m.message_id = %s",
			$um_db->qstr($message_id)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('attachment_seq');
		
		$sql = sprintf("INSERT INTO attachment (id, message_id, display_name, filepath)".
			"VALUES (%d,%d,%s,%s)",
			$newId,
			$message_id,
			$um_db->qstr($display_name),
			$um_db->qstr($filepath)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT a.id, a.message_id, a.display_name, a.filepath ".
			"FROM attachment a WHERE a.message_id = %d",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
	 */
	static function createTicket($mask, $subject, $status, $mailbox_id, $last_wrote, $created_date) {
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('ticket_seq');
		
		$sql = sprintf("INSERT INTO ticket (id, mask, subject, status, mailbox_id, last_wrote, first_wrote, created_date, updated_date, priority) ".
			"VALUES (%d,%s,%s,%s,%d,%s,%s,%d,%d,0)",
			$newId,
			$um_db->qstr($mask),
			$um_db->qstr($subject),
			$um_db->qstr($status),
			$mailbox_id,
			$um_db->qstr($last_wrote),
			$um_db->qstr($last_wrote),
			$created_date,
			gmmktime()
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}

	static function createMessage($ticket_id,$type,$created_date,$address_id,$headers,$content) {
		$um_db = UserMeetDatabase::getInstance();
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
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
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
	 */
	static function searchTickets($params,$limit=10,$page=0,$sortBy=null,$sortAsc=null) {
		$um_db = UserMeetDatabase::getInstance();
		
		$tickets = array();
		$start = min($page * $limit,1);
		
//		print_r($params);
		$wheres = array();
		
		// [JAS]: Search Builder
		if(is_array($params))
		foreach($params as $param) { /* @var $param CerberusSearchCriteria */
			if(!is_a($param,'CerberusSearchCriteria')) continue;
			$where = "";
			
			// [JAS]: Filter allowed columns (ignore invalid/deprecated)
			switch($param->field) {
				case "t.id";
				case "t.mask";
				case "t.status";
				case "t.subject";
				case "t.priority";
				case "t.mailbox_id";
				case "t.last_wrote";
				case "t.first_wrote";
					break;
				default:
					continue;
					break;
			}
			
			// [JAS]: Operators
			switch($param->operator) {
				case "=":
					$where = sprintf("%s = %s",
						$param->field,
						$um_db->qstr($param->value)
					);
					break;
					
				case "!=":
					$where = sprintf("%s != %s",
						$param->field,
						$um_db->qstr($param->value)
					);
					break;
				
				case "in":
					if(!is_array($param->value)) break;
					$where = sprintf("%s IN ('%s')",
						$param->field,
						implode("','",$param->value)
					);
					break;
					
				case "like":
//					if(!is_array($param->value)) break;
					$where = sprintf("%s LIKE %s",
						$param->field,
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
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.priority, t.mailbox_id, t.bitflags, t.first_wrote, t.last_wrote, t.created_date, ".
			"t.updated_date ". //m.name
			"FROM ticket t ".
//			"INNER JOIN mailbox m ON (t.mailbox_id=m.id) ".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		);
//		echo $sql;
		$rs = $um_db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$ticket = new CerberusTicket();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->bitflags = intval($rs->fields['bitflags']);
			$ticket->status = $rs->fields['status'];
			$ticket->priority = intval($rs->fields['priority']);
			$ticket->mailbox_id = intval($rs->fields['mailbox_id']);
			$ticket->last_wrote = $rs->fields['last_wrote'];
			$ticket->first_wrote = $rs->fields['first_wrote'];
			$ticket->created_date = intval($rs->fields['created_date']);
			$ticket->updated_date = intval($rs->fields['updated_date']);
			$tickets[$ticket->id] = $ticket;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$rs = $um_db->Execute($sql);
		$total = $rs->RecordCount();
		
		return array($tickets,$total);
	}
	
	// [JAS]: [TODO] Replace this inner function with a ticket ID search using searchTicket()?  Removes redundancy
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return CerberusTicket
	 */
	static function getTicket($id) {
		$um_db = UserMeetDatabase::getInstance();
		
		$ticket = null;
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.priority, t.mailbox_id, t.bitflags, t.first_wrote, t.last_wrote, t.created_date, t.updated_date ".
			"FROM ticket t ".
			"WHERE t.id = %d",
			$id
		);
		$rs = $um_db->SelectLimit($sql,2,0) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$ticket = new CerberusTicket();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->bitflags = intval($rs->fields['bitflags']);
			$ticket->status = $rs->fields['status'];
			$ticket->priority = intval($rs->fields['priority']);
			$ticket->mailbox_id = intval($rs->fields['mailbox_id']);
			$ticket->last_wrote = $rs->fields['last_wrote'];
			$ticket->first_wrote = $rs->fields['first_wrote'];
			$ticket->created_date = intval($rs->fields['created_date']);
			$ticket->updated_date = intval($rs->fields['updated_date']);
		}
		
		return $ticket;
	}
	
	static function updateTicket($id,$fields) {
		$um_db = UserMeetDatabase::getInstance();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$um_db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE ticket SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	static function tagTicket($ticket_id, $tag_string) {
		$um_db = UserMeetDatabase::getInstance();
		$tags = CerberusApplication::parseCsvString($tag_string);
		
		if(is_array($tags))
		foreach($tags as $tagName) {
			$tag = CerberusWorkflowDAO::lookupTag($tagName, true);
			$um_db->Replace('tag_to_ticket', array('ticket_id'=>$ticket_id,'tag_id'=>$tag->id), array('ticket_id','tag_id'));
		}
	}
	
	static function untagTicket($ticket_id, $tag_id) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM tag_to_ticket WHERE tag_id = %d AND ticket_id = %d",
			$tag_id,
			$ticket_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function flagTicket($ticket_id, $agent_id) {
		if(empty($ticket_id) || empty($agent_id))
			return null;
		
		$um_db = UserMeetDatabase::getInstance();
		$um_db->Replace('assign_to_ticket', array('ticket_id'=>$ticket_id,'agent_id'=>$agent_id,'is_flag'=>1), array('ticket_id','agent_id'));
	}
	
	static function unflagTicket($ticket_id, $agent_id) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM assign_to_ticket WHERE agent_id = %d AND ticket_id = %d AND is_flag = 1",
			$agent_id,
			$ticket_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function suggestTicket($ticket_id, $agent_id) {
		if(empty($ticket_id) || empty($agent_id))
			return null;
		
		$um_db = UserMeetDatabase::getInstance();
		$um_db->Replace('assign_to_ticket', array('ticket_id'=>$ticket_id,'agent_id'=>$agent_id,'is_flag'=>0), array('ticket_id','agent_id'));
	}
	
	static function unsuggestTicket($ticket_id, $agent_id) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM assign_to_ticket WHERE agent_id = %d AND ticket_id = %d AND is_flag = 0",
			$agent_id,
			$ticket_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function getMessagesByTicket($ticket_id) {
		$um_db = UserMeetDatabase::getInstance();
		$messages = array();
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.message_type, m.created_date, m.address_id, m.message_id, m.headers ".
			"FROM message m ".
			"WHERE m.ticket_id = %d ".
			"ORDER BY m.created_date ASC ",
			$ticket_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
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
		$um_db = UserMeetDatabase::getInstance();
		$message = null;
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.message_type, m.created_date, m.address_id, m.message_id, m.headers ".
			"FROM message m ".
			"WHERE m.id = %d ".
			"ORDER BY m.created_date ASC ",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
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
		$um_db = UserMeetDatabase::getInstance();
		$addresses = array();
		
		$sql = sprintf("SELECT a.id , a.email, a.personal ".
			"FROM address a ".
			"INNER JOIN requester r ON (r.ticket_id = %d AND a.id=r.address_id) ".
			"ORDER BY a.personal, a.email ASC ",
			$ticket_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
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
		$um_db = UserMeetDatabase::getInstance();
		$content = '';
		
		$sql = sprintf("SELECT m.id, m.content ".
			"FROM message m ".
			"WHERE m.id = %d ",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$content = $rs->fields['content'];
		}
		
		return $content;
	}
	
	static function createRequester($address_id,$ticket_id) {
		$um_db = UserMeetDatabase::getInstance();
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
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO dashboard (id, name, agent_id) ".
			"VALUES (%d, %s, %d)",
			$newId,
			$um_db->qstr($name),
			$agent_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	// [JAS]: Convert this over to pulling by a list of IDs?
	static function getDashboards($agent_id=0) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT id, name ".
			"FROM dashboard "
//			(($agent_id) ? sprintf("WHERE agent_id = %d ",$agent_id) : " ")
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
		$um_db = UserMeetDatabase::getInstance();
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
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	static private function _updateView($id,$fields) {
		$um_db = UserMeetDatabase::getInstance();
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
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function deleteView($id) {
		if(empty($id)) return;
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM dashboard_view WHERE id = %d",
			$id
		);
		
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $dashboard_id
	 * @return CerberusDashboardView[]
	 */
	static function getViews($dashboard_id=0) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT v.id, v.name, v.dashboard_id, v.type, v.agent_id, v.view_columns, v.num_rows, v.sort_by, v.sort_asc, v.page, v.params ".
			"FROM dashboard_view v ".
			"WHERE v.dashboard_id > 0 "
//			(!empty($dashboard_id) ? sprintf("WHERE v.dashboard_id = %d ", $dashboard_id) : " ")
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
				't.mask',
				't.status',
				't.priority',
				't.last_wrote',
				't.created_date'
				);
			$view->params = array();
			$view->renderLimit = 100;
			$view->renderPage = 0;
			$view->renderSortBy = 't.created_date';
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
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT v.id, v.name, v.dashboard_id, v.type, v.agent_id, v.view_columns, v.num_rows, v.sort_by, v.sort_asc, v.page, v.params ".
			"FROM dashboard_view v ".
			"WHERE v.id = %d ",
			$view_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */

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
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('generic_seq');
		
		$sCriteria = serialize($criteria); // Flatten criterion array into a string
		
		$sql = sprintf("INSERT INTO mail_rule (id, criteria, sequence, strictness) ".
			"VALUES (%d, %s, %s, %s)",
			$newId,
			$um_db->qstr($sCriteria),
			$um_db->qstr($sequence),
			$um_db->qstr($strictness)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg());
	}
	
	/**
	 * deletes a mail rule from the database
	 *
	 * @param integer $id
	 */
	static function deleteMailRule ($id) {
		if(empty($id)) return;
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM mail_rule WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg());
	}
	
	/**
	 * returns the mail rule with the given id
	 *
	 * @param integer $id
	 * @return CerberusMailRule
	 */
	static function getMailRule ($id) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT m.id, m.criteria, m.sequence, m.strictness ".
			"FROM mail_rule m ".
			"WHERE m.id = %d",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg());
		
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
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT m.id, m.criteria, m.sequence, m.strictness ".
			"FROM mail_rule m"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg());
		
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
		$um_db = UserMeetDatabase::getInstance();
		
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
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg());
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
	 * @param integer $agent_id
	 * @return CerberusDashboardView[]
	 */
	static function getSavedSearches($agent_id) {
		$um_db = UserMeetDatabase::getInstance();
		$searches = array();
		
		$sql = sprintf("SELECT v.id, v.name, v.dashboard_id, v.type, v.agent_id, v.view_columns, v.num_rows, v.sort_by, v.sort_asc, v.page, v.params ".
			"FROM dashboard_view v ".
			"WHERE v.type = 'S' "
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */

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
		
		$um_db = UserMeetDatabase::getInstance();
		$tag = null;

		$sql = sprintf("SELECT t.id FROM tag t WHERE t.name = %s",
			$um_db->qstr($tag_name)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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

		$um_db = UserMeetDatabase::getInstance();
		$tags = array();

		$sql = "SELECT t.id, t.name ".
			"FROM tag t ".
			((!empty($ids) ? sprintf("WHERE t.id IN (%s)",implode(',', $ids)) : " ").
			"ORDER BY t.name"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
		$um_db = UserMeetDatabase::getInstance();
		$ids = array();
		$tags = array();
		
		$sql = sprintf("SELECT tt.tag_id ".
			"FROM tag_to_ticket tt ".
			"INNER JOIN tag t ON (tt.tag_id=t.id) ".
			"WHERE tt.ticket_id = %d ".
			"ORDER BY t.name",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
		$um_db = UserMeetDatabase::getInstance();
		$ids = array();
		$workers = array();
		
		$sql = sprintf("SELECT at.agent_id ".
			"FROM assign_to_ticket at ".
			"WHERE at.ticket_id = %d ".
			"AND at.is_flag = %d",
			$ticket_id,
			($is_flag) ? 1 : 0
		);
		$rs= $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
	
	static function searchTags($query,$limit=10) {
		$um_db = UserMeetDatabase::getInstance();
		if(empty($query)) return null;
		
		$sql = sprintf("SELECT t.id FROM tag t WHERE t.name LIKE '%s%%' LIMIT 0,%d",
			$query,
			$limit
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
		$um_db = UserMeetDatabase::getInstance();
		if(empty($name)) return null;
		
		$id = $um_db->GenID('tag_seq');
		
		$sql = sprintf("INSERT INTO tag (id, name) ".
			"VALUES (%d, %s)",
			$id,
			$um_db->qstr(strtolower($name))
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
	static function deleteTag($id) {
		$um_db = UserMeetDatabase::getInstance();
		if(empty($id)) return;
		
		$sql = sprintf("DELETE FROM tag WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM favorite_tag_to_worker WHERE tag_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM tag_to_ticket WHERE tag_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
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
		$um_db = UserMeetDatabase::getInstance();

		$teams = array();
		
		$sql = sprintf("SELECT t.id , t.name ".
			"FROM team t ".
			((!empty($ids)) ? sprintf("WHERE t.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY t.name ASC"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
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
		
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO team (id, name) VALUES (%d,%s)",
			$newId,
			$um_db->qstr($name)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}

	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @param array $fields
	 */
	static function updateTeam($id, $fields) {
		$um_db = UserMeetDatabase::getInstance();
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
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 */
	static function deleteTeam($id) {
		if(empty($id)) return;
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM team WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM mailbox_to_team WHERE team_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM worker_to_team WHERE team_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function setTeamMailboxes($team_id, $mailbox_ids) {
		if(!is_array($mailbox_ids)) $mailbox_ids = array($mailbox_ids);
		if(empty($team_id)) return;
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM mailbox_to_team WHERE team_id = %d",
			$team_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		foreach($mailbox_ids as $mailbox_id) {
			$sql = sprintf("INSERT INTO mailbox_to_team (mailbox_id, team_id, is_routed) ".
				"VALUES (%d,%d,%d)",
				$mailbox_id,
				$team_id,
				1
			);
			$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
	}
	
	static function getTeamMailboxes($team_id) {
		if(empty($team_id)) return;
		$um_db = UserMeetDatabase::getInstance();
		$ids = array();
		
		$sql = sprintf("SELECT mt.mailbox_id FROM mailbox_to_team mt WHERE mt.team_id = %d",
			$team_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['mailbox_id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
		
		return CerberusMailDAO::getMailboxes($ids);
	}
	
	static function setTeamWorkers($team_id, $agent_ids) {
		if(!is_array($agent_ids)) $agent_ids = array($agent_ids);
		if(empty($team_id)) return;
		$um_db = UserMeetDatabase::getInstance();

		$sql = sprintf("DELETE FROM worker_to_team WHERE team_id = %d",
			$team_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		foreach($agent_ids as $agent_id) {
			$sql = sprintf("INSERT INTO worker_to_team (agent_id, team_id) ".
				"VALUES (%d,%d)",
				$agent_id,
				$team_id
			);
			$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
	}
	
	static function getTeamWorkers($team_id) {
		if(empty($team_id)) return;
		$um_db = UserMeetDatabase::getInstance();
		$ids = array();
		
		$sql = sprintf("SELECT wt.agent_id FROM worker_to_team wt WHERE wt.team_id = %d",
			$team_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
	
	/**
	 * Returns a list of all known mailboxes, sorted by name
	 *
	 * @return CerberusMailbox[]
	 */
	static function getMailboxes($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$um_db = UserMeetDatabase::getInstance();

		$mailboxes = array();
		
		$sql = sprintf("SELECT m.id , m.name, m.reply_address_id, m.display_name ".
			"FROM mailbox m ".
			((!empty($ids)) ? sprintf("WHERE m.id IN (%s) ",implode(',', $ids)) : " ").
			"ORDER BY m.name ASC"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$mailbox = new CerberusMailbox();
			$mailbox->id = intval($rs->fields['id']);
			$mailbox->name = $rs->fields['name'];
			$mailbox->reply_address_id = $rs->fields['reply_address_id'];
			$mailbox->display_name = $$rs->fields['display_name'];
			$mailboxes[$mailbox->id] = $mailbox;
			$rs->MoveNext();
		}
		
		return $mailboxes;
	}
	
	static function getMailboxListWithCounts() {
		$um_db = UserMeetDatabase::getInstance();
		$mailboxes = CerberusMailDAO::getMailboxes(); /* @var $mailboxes CerberusMailbox[] */
		
		foreach ($mailboxes as $mailbox) {
			$sql = sprintf("SELECT COUNT(t.id) as ticket_count ".
				"FROM ticket t ".
				"WHERE t.mailbox_id = %d AND t.status = 'O'",
				$mailbox->id
			);
			
			$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
			
			while(!$rs->EOF) {
				$mailbox->count = intval($rs->fields['ticket_count']);
				$rs->MoveNext();
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
	static function createMailbox($name, $reply_address_id, $display_name = '') {
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO mailbox (id, name, reply_address_id, display_name) VALUES (%d,%s,%d,%s)",
			$newId,
			$um_db->qstr($name),
			$reply_address_id,
			$um_db->qstr($display_name)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	static function updateMailbox($id, $fields) {
		$um_db = UserMeetDatabase::getInstance();
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
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	static function getMailbox($id) {
		$mailboxes = CerberusMailDAO::getMailboxes(array($id));
		
		if(isset($mailboxes[$id]))
			return $mailboxes[$id];
			
		return null;
	}
	
	static function deleteMailbox($id) {
		if(empty($id)) return;
		
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM mailbox WHERE id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$sql = sprintf("DELETE FROM mailbox_to_team WHERE mailbox_id = %d",
			$id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
	}
	
	
	static function setMailboxTeams($mailbox_id, $team_ids) {
		if(!is_array($team_ids)) $team_ids = array($team_ids);
		if(empty($mailbox_id)) return;
		$um_db = UserMeetDatabase::getInstance();

		$sql = sprintf("DELETE FROM mailbox_to_team WHERE mailbox_id = %d",
			$mailbox_id
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		foreach($team_ids as $team_id) {
			$sql = sprintf("INSERT INTO mailbox_to_team (mailbox_id, team_id, is_routed) ".
				"VALUES (%d,%d,%d)",
				$mailbox_id,
				$team_id,
				1
			);
			$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		}
	}
	
	static function getMailboxTeams($mailbox_id) {
		if(empty($mailbox_id)) return;
		$um_db = UserMeetDatabase::getInstance();
		$ids = array();
		
		$sql = sprintf("SELECT mt.team_id FROM mailbox_to_team mt WHERE mt.mailbox_id = %d",
			$mailbox_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		while(!$rs->EOF) {
			$ids[] = intval($rs->fields['team_id']);
			$rs->MoveNext();
		}
		
		if(empty($ids))
			return array();
			
		return CerberusWorkflowDAO::getTeams($ids);
	}
		
	// Pop3 Accounts
	
	static function createPop3Account($nickname,$host,$username,$password) {
		if(empty($nickname) || empty($host) || empty($username) || empty($password)) 
			return null;
			
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO pop3_account (id, nickname, host, username, password) ".
			"VALUES (%d,%s,%s,%s,%s)",
			$newId,
			$um_db->qstr($nickname),
			$um_db->qstr($host),
			$um_db->qstr($username),
			$um_db->qstr($password)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	static function getPop3Accounts($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$um_db = UserMeetDatabase::getInstance();
		$pop3accounts = array();
		
		$sql = "SELECT id, nickname, host, username, password ".
			"FROM pop3_account ".
			((!empty($ids) ? sprintf("WHERE id IN (%s)", implode(',', $ids)) : " ").
			"ORDER BY nickname "
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
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
		$um_db = UserMeetDatabase::getInstance();
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
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	static function deletePop3Account($id) {
		if(empty($id))
			return;
			
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("DELETE FROM pop3_account WHERE id = %d",
			$id			
		);
		
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
};
?>