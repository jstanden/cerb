<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class DAO_Ticket extends Cerb_ORMHelper {
	const BUCKET_ID = 'bucket_id';
	const CLOSED_AT = 'closed_at';
	const CREATED_DATE = 'created_date';
	const ELAPSED_RESOLUTION_FIRST = 'elapsed_resolution_first';
	const ELAPSED_RESPONSE_FIRST = 'elapsed_response_first';
	const FIRST_MESSAGE_ID = 'first_message_id';
	const FIRST_OUTGOING_MESSAGE_ID = 'first_outgoing_message_id';
	const FIRST_WROTE_ID = 'first_wrote_address_id';
	const GROUP_ID = 'group_id';
	const ID = 'id';
	const IMPORTANCE = 'importance';
	const INTERESTING_WORDS = 'interesting_words';
	const LAST_MESSAGE_ID = 'last_message_id';
	const LAST_WROTE_ID = 'last_wrote_address_id';
	const MASK = 'mask';
	const NUM_MESSAGES = 'num_messages';
	const NUM_MESSAGES_IN = 'num_messages_in';
	const NUM_MESSAGES_OUT = 'num_messages_out';
	const ORG_ID = 'org_id';
	const OWNER_ID = 'owner_id';
	const REOPEN_AT = 'reopen_at';
	const SPAM_SCORE = 'spam_score';
	const SPAM_TRAINING = 'spam_training';
	const STATUS_ID = 'status_id';
	const SUBJECT = 'subject';
	const UPDATED_DATE = 'updated_date';
	
	const _PARTICIPANTS = '_participants';
	const _PARTICIPANT_IDS = '_participant_ids';
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::BUCKET_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_BUCKET))
			;
		$validation
			->addField(self::CLOSED_AT)
			->timestamp()
			;
		$validation
			->addField(self::CREATED_DATE)
			->timestamp()
			;
		$validation
			->addField(self::ELAPSED_RESOLUTION_FIRST)
			->uint(4)
			->setEditable(false)
			;
		$validation
			->addField(self::ELAPSED_RESPONSE_FIRST)
			->uint(4)
			->setEditable(false)
			;
		$validation
			->addField(self::FIRST_MESSAGE_ID)
			->id()
			->setEditable(false)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_MESSAGE))
			;
		$validation
			->addField(self::FIRST_OUTGOING_MESSAGE_ID)
			->id()
			->setEditable(false)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_MESSAGE))
			;
		$validation
			->addField(self::FIRST_WROTE_ID)
			->id()
			->setEditable(false)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ADDRESS))
			;
		$validation
			->addField(self::GROUP_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_GROUP))
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IMPORTANCE)
			->number()
			->setMin(0)
			->setMax(100)
			;
		$validation
			->addField(self::INTERESTING_WORDS)
			->string()
			->setMaxLength(255)
			->setEditable(false)
			;
		$validation
			->addField(self::LAST_MESSAGE_ID)
			->id()
			->setEditable(false)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_MESSAGE))
			;
		$validation
			->addField(self::LAST_WROTE_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ADDRESS))
			;
		$validation
			->addField(self::MASK)
			->string()
			->setMaxLength(255)
			->setUnique('DAO_Ticket')
			;
		$validation
			->addField(self::NUM_MESSAGES)
			->uint(4)
			->setEditable(false)
			;
		$validation
			->addField(self::NUM_MESSAGES_IN)
			->uint(4)
			->setEditable(false)
			;
		$validation
			->addField(self::NUM_MESSAGES_OUT)
			->uint(4)
			->setEditable(false)
			;
		$validation
			->addField(self::ORG_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_ORG, true))
			;
		$validation
			->addField(self::OWNER_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER, true))
			;
		$validation
			->addField(self::REOPEN_AT)
			->timestamp()
			;
		$validation
			->addField(self::STATUS_ID)
			->number()
			->setMin(0)
			->setMax(3)
			;
		$validation
			->addField(self::SPAM_SCORE)
			->float()
			->setMin(0.0)
			->setMax(1.0)
			;
		$validation
			->addField(self::SPAM_TRAINING)
			->string()
			->setMaxLength(1)
			->setPossibleValues(['','N','S'])
			;
		$validation
			->addField(self::SUBJECT)
			->string($validation::STRING_UTF8MB4)
			->setMaxLength(255)
			->setRequired(true)
			;
		$validation
			->addField(self::UPDATED_DATE)
			->timestamp()
			;
		// text
		$validation
			->addField(self::_PARTICIPANTS)
			->string()
			->setMaxLength(65535)
			// [TODO] Formatter -> RFC emails
			->addValidator($validation->validators()->emails(true))
			;
		// text
		$validation
			->addField(self::_PARTICIPANT_IDS)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
		
		return $validation->getFields();
	}
	
	static function authorizeByParticipantsAndMessages(array $participant_ids, array $message_ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($participant_ids) || empty($message_ids))
			return false;
		
		$result = $db->GetOneReader(sprintf("SELECT COUNT(t.id) from message m inner join ticket t on (t.id=m.ticket_id) inner join requester r on (r.ticket_id=t.id) where r.address_id in (%s) and m.id in (%s)",
			implode(',', DevblocksPlatform::sanitizeArray($participant_ids, 'int')),
			implode(',', DevblocksPlatform::sanitizeArray($message_ids, 'int'))
		));
		
		return !empty($result);
	}
	
	public static function getStatusTextFromId($status_id) {
		$statuses = [
			0 => 'open',
			1 => 'waiting',
			2 => 'closed',
			3 => 'deleted',
		];
		
		return $statuses[$status_id] ?? null;
	}
	
	public static function getStatusIdFromText($status) {
		$statuses = [
			'open' => 0,
			'waiting' => 1,
			'closed' => 2,
			'deleted' => 3,
		];
		
		return $statuses[DevblocksPlatform::strLower($status)] ?? null;
	}
	
	/**
	 *
	 * @param string $mask
	 * @return integer
	 */
	static function getTicketIdByMask($mask) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT t.id FROM ticket t WHERE t.mask = %s",
			$db->qstr($mask)
		);
		$ticket_id = $db->GetOneReader($sql);

		// If we found a hit on a ticket record, return the ID
		if(!empty($ticket_id)) {
			return intval($ticket_id);
			
		// Check if this mask was previously forwarded elsewhere
		} else {
			$sql = sprintf("SELECT new_ticket_id FROM ticket_mask_forward WHERE old_mask = %s",
				$db->qstr($mask)
			);
			$ticket_id = $db->GetOneReader($sql);
			
			if(!empty($ticket_id))
				return intval($ticket_id);
		}

		// No match
		return null;
	}
	
	static function getMergeParentByMask($old_mask) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT new_mask from ticket_mask_forward WHERE old_mask = %s",
			$db->qstr($old_mask)
		);
		
		$new_mask = $db->GetOneReader($sql);
		
		if(empty($new_mask))
			return null;
		
		return $new_mask;
	}
	
	/**
	 *
	 * @param string $mask
	 * @return Model_Ticket
	 */
	static function getTicketByMask($mask) {
		if(null != ($id = self::getTicketIdByMask($mask))) {
			return self::get($id);
		}
		
		return NULL;
	}
	
	/**
	 *
	 * @param string $message_id
	 * @return integer
	 */
	static function getTicketByMessageId($message_id) {
		if(false == ($message = DAO_Message::get($message_id)))
			return null;
		
		if(false == ($ticket = DAO_Ticket::get($message->ticket_id)))
			return null;
		
		return $ticket;
	}
	
	/**
	 *
	 * @param string $message_id
	 * @return array
	 */
	static function getTicketByMessageIdHeader($raw_message_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT ticket_id, id as message_id ".
			"FROM message ".
			"WHERE hash_header_message_id = %s",
			$db->qstr(sha1($raw_message_id))
		);
		
		if(false == ($row = $db->GetRowReader($sql)) || empty($row))
			return false;
		
		$ticket_id = intval($row['ticket_id']);
		$message_id = intval($row['message_id']);
			
		return array(
			'ticket_id' => $ticket_id,
			'message_id' => $message_id
		);
	}
	
	static function getParticipants($ticket_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT %s AS context, address_id AS context_id, count(id) AS hits FROM message WHERE is_outgoing = 0 AND ticket_id = %d GROUP BY address_id ". 
			"UNION ".
			"SELECT %s AS context, worker_id AS context_id, count(id) FROM message WHERE is_outgoing = 1 AND worker_id > 0 AND ticket_id = %d GROUP BY worker_id",
			$db->qstr(CerberusContexts::CONTEXT_ADDRESS),
			$ticket_id,
			$db->qstr(CerberusContexts::CONTEXT_WORKER),
			$ticket_id
		);
		$results = $db->GetArrayReader($sql);
		
		return $results;
	}
	
	static function getParticipantsByTickets($ticket_ids, $with_workers=true) {
		$db = DevblocksPlatform::services()->database();
		
		$results = [];
		$where_ticket_ids = implode(',', $ticket_ids) ?: '-1';
		
		$sql = sprintf("SELECT %s AS context, address_id AS context_id, ticket_id FROM requester WHERE ticket_id IN (%s) ",
			$db->qstr(CerberusContexts::CONTEXT_ADDRESS),
			$where_ticket_ids
		);
		
		$rows_addresses = $db->GetArrayReader($sql);
		$models_addresses = DAO_Address::getIds(array_column($rows_addresses, 'context_id'));
		
		$dicts_addresses = DevblocksDictionaryDelegate::getDictionariesFromModels($models_addresses, CerberusContexts::CONTEXT_ADDRESS, ['contact_']);
		
		$rows_workers = [];
		$dicts_workers = [];
		
		if($with_workers) {
			$sql = sprintf("SELECT %s AS context, worker_id AS context_id, ticket_id FROM message WHERE is_outgoing = 1 AND worker_id > 0 AND ticket_id IN (%s) ",
			$db->qstr(CerberusContexts::CONTEXT_WORKER),
				$where_ticket_ids
			);
			
			$rows_workers = $db->GetArrayReader($sql);
			$models_workers = DAO_Worker::getIds(array_column($rows_workers, 'context_id'));
			$dicts_workers = DevblocksDictionaryDelegate::getDictionariesFromModels($models_workers, CerberusContexts::CONTEXT_WORKER);
		}
		
		foreach(array_merge($rows_addresses, $rows_workers) as $row) {
			$context = $row['context'];
			$ticket_id = $row['ticket_id'];
			
			if(!array_key_exists($ticket_id, $results))
				$results[$ticket_id] = [];
			
			if($context == CerberusContexts::CONTEXT_ADDRESS) {
				$results[$ticket_id][] = $dicts_addresses[$row['context_id']];
			} else if($context == CerberusContexts::CONTEXT_WORKER) {
				$results[$ticket_id][] = $dicts_workers[$row['context_id']];
			}
		}
		
		return $results;
	}
	
	static function countsByContactId($contact_id) {
		$db = DevblocksPlatform::services()->database();
		
		$counts = array(
			'total' => 0,
			'open' => 0,
			'waiting' => 0,
			'closed' => 0,
		);
		
		$sql = sprintf("SELECT COUNT(ticket.id) AS count, ticket.status_id ".
				"FROM ticket ".
				"INNER JOIN requester ON (requester.ticket_id=ticket.id) ".
				"INNER JOIN address ON (requester.address_id=address.id) ".
				"WHERE address.contact_id = %d AND ticket.status_id != 3 ".
				"GROUP BY ticket.status_id",
			$contact_id
		);
		$results = $db->GetArrayReader($sql);
		
		if(is_array($results))
		foreach($results as $result) {
			switch($result['status_id']) {
				case Model_Ticket::STATUS_OPEN:
					$counts['open'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_WAITING:
					$counts['waiting'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_CLOSED: 
					$counts['closed'] += $result['count'];
					break;
			}
			
			$counts['total'] += $result['count'];
		}
		
		return $counts;
	}
	
	static function countsByBucketId($bucket_id) {
		$db = DevblocksPlatform::services()->database();
		
		$counts = array(
			'total' => 0,
			'open' => 0,
			'waiting' => 0,
			'closed' => 0,
		);
		
		$sql = sprintf("SELECT COUNT(ticket.id) AS count, ticket.status_id ".
				"FROM ticket ".
				"WHERE ticket.bucket_id = %d AND ticket.status_id != 3 ".
				"GROUP BY ticket.status_id",
			$bucket_id
		);
		$results = $db->GetArrayReader($sql);
		
		if(is_array($results))
		foreach($results as $result) {
			switch($result['status_id']) {
				case Model_Ticket::STATUS_OPEN:
					$counts['open'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_WAITING:
					$counts['waiting'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_CLOSED: 
					$counts['closed'] += $result['count'];
					break;
			}
			
			$counts['total'] += $result['count'];
		}
		
		return $counts;
	}
	
	static function countsByGroupId($group_id) {
		$db = DevblocksPlatform::services()->database();
		
		$counts = array(
			'total' => 0,
			'open' => 0,
			'waiting' => 0,
			'closed' => 0,
		);
		
		$sql = sprintf("SELECT COUNT(ticket.id) AS count, ticket.status_id ".
				"FROM ticket ".
				"WHERE ticket.group_id = %d AND ticket.status_id != 3 " .
				"GROUP BY ticket.status_id",
			$group_id
		);
		$results = $db->GetArrayReader($sql);
		
		if(is_array($results))
		foreach($results as $result) {
			switch($result['status_id']) {
				case Model_Ticket::STATUS_OPEN:
					$counts['open'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_WAITING:
					$counts['waiting'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_CLOSED: 
					$counts['closed'] += $result['count'];
					break;
			}
			
			$counts['total'] += $result['count'];
		}
		
		return $counts;
	}
	
	static function countsByOwnerId($worker_id) {
		$db = DevblocksPlatform::services()->database();
		
		$counts = array(
			'total' => 0,
			'open' => 0,
			'waiting' => 0,
			'closed' => 0,
		);
		
		$sql = sprintf("SELECT COUNT(ticket.id) AS count, ticket.status_id ".
				"FROM ticket ".
				"WHERE ticket.owner_id = %d AND ticket.status_id != 3 ".
				"GROUP BY ticket.status_id",
			$worker_id
		);
		$results = $db->GetArrayReader($sql);
		
		if(is_array($results))
		foreach($results as $result) {
			switch($result['status_id']) {
				case Model_Ticket::STATUS_OPEN:
					$counts['open'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_WAITING:
					$counts['waiting'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_CLOSED: 
					$counts['closed'] += $result['count'];
					break;
			}
			
			$counts['total'] += $result['count'];
		}
		
		return $counts;
	}
	
	static function countsByAddressId($address_id) {
		$db = DevblocksPlatform::services()->database();
		
		$counts = array(
			'total' => 0,
			'open' => 0,
			'waiting' => 0,
			'closed' => 0,
		);
		
		$sql = sprintf("SELECT COUNT(ticket.id) AS count, ticket.status_id ".
				"FROM ticket ".
				"INNER JOIN requester ON (requester.ticket_id=ticket.id) ".
				"WHERE requester.address_id = %d AND ticket.status_id != 3 ".
				"GROUP BY ticket.status_id",
			$address_id
		);
		$results = $db->GetArrayReader($sql);
		
		if(is_array($results))
		foreach($results as $result) {
			switch($result['status_id']) {
				case Model_Ticket::STATUS_OPEN:
					$counts['open'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_WAITING:
					$counts['waiting'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_CLOSED: 
					$counts['closed'] += $result['count'];
					break;
			}
			
			$counts['total'] += $result['count'];
		}
		
		return $counts;
	}
	
	static function countsByOrgId($org_id) {
		$db = DevblocksPlatform::services()->database();
		
		$counts = array(
			'total' => 0,
			'open' => 0,
			'waiting' => 0,
			'closed' => 0,
		);
		
		$sql = sprintf("SELECT COUNT(id) AS count, status_id ".
				"FROM ticket ".
				"WHERE org_id = %d AND status_id != 3 ".
				"GROUP BY status_id",
			$org_id
		);
		$results = $db->GetArrayReader($sql);
		
		if(is_array($results))
		foreach($results as $result) {
			switch($result['status_id']) {
				case Model_Ticket::STATUS_OPEN:
					$counts['open'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_WAITING:
					$counts['waiting'] += $result['count'];
					break;
					
				case Model_Ticket::STATUS_CLOSED: 
					$counts['closed'] += $result['count'];
					break;
			}
			
			$counts['total'] += $result['count'];
		}
		
		return $counts;
	}
	
	/**
	 * creates a new ticket object in the database
	 *
	 * @param array $fields
	 * @return integer
	 *
	 */
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		if(!isset($fields[self::MASK]))
			$fields[self::MASK] = CerberusApplication::generateTicketMask();
		
		if(!isset($fields[self::IMPORTANCE]))
			$fields[self::IMPORTANCE] = 50;
		
		$sql = sprintf("INSERT INTO ticket (created_date, updated_date) ".
			"VALUES (%d,%d)",
			time(),
			time()
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_TICKET, $id);
		
		self::update($id, $fields, false);
		
		return $id;
	}

	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		// Fix missing owners
		$sql = "UPDATE ticket SET owner_id = 0 WHERE owner_id != 0 AND owner_id NOT IN (SELECT id FROM worker)";
		$db->ExecuteMaster($sql);
		
		$db->ExecuteMaster("DELETE FROM ticket_mask_forward WHERE new_ticket_id NOT IN (SELECT id FROM ticket)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' ticket_mask_forward records.');

		$db->ExecuteMaster("DELETE FROM requester WHERE ticket_id NOT IN (SELECT id FROM ticket)");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' requester records.');
		
		// Recover any tickets assigned to a NULL bucket
		$db->ExecuteMaster("UPDATE ticket SET bucket_id = 0 WHERE bucket_id != 0 AND bucket_id NOT IN (SELECT id FROM bucket)");
		$logger->info('[Maint] Fixed ' . $db->Affected_Rows() . ' tickets in missing buckets.');
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_TICKET,
					'context_table' => 'ticket',
					'context_key' => 'id',
				)
			)
		);
	}
	
	static function rebuild($id) {
		if(null == ($ticket = DAO_Ticket::get($id)))
			return FALSE;

		$db = DevblocksPlatform::services()->database();
		
		$messages = $ticket->getMessages();
		$first_message = reset($messages);
		$last_message = end($messages);
		$num_messages = count($messages);
		$num_messages_in = 0;
		$num_messages_out = 0;
		
		$fields = [
			DAO_Ticket::FIRST_MESSAGE_ID => 0,
			DAO_Ticket::FIRST_WROTE_ID => 0,
			DAO_Ticket::LAST_MESSAGE_ID => 0,
			DAO_Ticket::LAST_WROTE_ID => 0,
			DAO_Ticket::FIRST_OUTGOING_MESSAGE_ID => 0,
			DAO_Ticket::ELAPSED_RESPONSE_FIRST => 0,
			DAO_Ticket::ELAPSED_RESOLUTION_FIRST => 0,
		];
		
		// Reindex the first message
		if($first_message) {
			$fields[DAO_Ticket::FIRST_MESSAGE_ID] = $first_message->id;
			$fields[DAO_Ticket::FIRST_WROTE_ID] = $first_message->address_id;
		}
		
		// Reindex the last message
		if($last_message) {
			$fields[DAO_Ticket::LAST_MESSAGE_ID] = $last_message->id;
			$fields[DAO_Ticket::LAST_WROTE_ID] = $last_message->address_id;
		}
		
		// Reindex the first outgoing message
		if(is_array($messages))
		foreach($messages as $message_id => $message) { /* @var $message Model_Message */
			if($message->is_outgoing) $num_messages_out++; else $num_messages_in++;
			
			if($message->is_outgoing && $message->worker_id && empty($fields[DAO_Ticket::FIRST_OUTGOING_MESSAGE_ID])) {
				$fields[DAO_Ticket::FIRST_OUTGOING_MESSAGE_ID] = $message_id;
				$fields[DAO_Ticket::ELAPSED_RESPONSE_FIRST] = max($message->created_date - $ticket->created_date, 0);
			}
		}

		// Reindex the earliest close date from activity log
		$sql = sprintf("SELECT MIN(created) FROM context_activity_log WHERE activity_point = 'ticket.status.closed' AND target_context = 'cerberusweb.contexts.ticket' AND target_context_id = %d", $id);
		$closed_at = intval($db->GetOneMaster($sql));
		$fields[DAO_Ticket::CLOSED_AT] = $closed_at;
		
		if(!empty($closed_at))
			$fields[DAO_Ticket::ELAPSED_RESOLUTION_FIRST] = max($closed_at - $ticket->created_date, 0);
		
		// Reindex message count
		$fields[DAO_Ticket::NUM_MESSAGES] = $num_messages;
		$fields[DAO_Ticket::NUM_MESSAGES_IN] = $num_messages_in;
		$fields[DAO_Ticket::NUM_MESSAGES_OUT] = $num_messages_out;
		
		// Update
		if(!empty($fields)) {
			DAO_Ticket::update($id, $fields, false);
		}
		
		return TRUE;
	}
	
	/**
	 *
	 * @param integer $id
	 * @return Model_Ticket
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$tickets = self::getIds(array($id));
		
		if(isset($tickets[$id]))
			return $tickets[$id];
			
		return NULL;
	}
	
	static function getWhere($where=null, $sortBy='updated_date', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id , mask, subject, status_id, group_id, bucket_id, org_id, owner_id, importance, first_message_id, first_outgoing_message_id, last_message_id, ".
			"first_wrote_address_id, last_wrote_address_id, created_date, updated_date, closed_at, reopen_at, spam_training, ".
			"spam_score, interesting_words, num_messages, num_messages_in, num_messages_out, elapsed_response_first, elapsed_resolution_first ".
			"FROM ticket ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->QueryReader($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 *
	 * @param mysqli_result|false $rs
	 */
	static private function _createObjectsFromResultSet($rs=null) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Ticket();
			$object->id = intval($row['id']);
			$object->mask = $row['mask'];
			$object->subject = $row['subject'];
			$object->first_message_id = intval($row['first_message_id']);
			$object->first_outgoing_message_id = intval($row['first_outgoing_message_id']);
			$object->last_message_id = intval($row['last_message_id']);
			$object->group_id = intval($row['group_id']);
			$object->bucket_id = intval($row['bucket_id']);
			$object->org_id = intval($row['org_id']);
			$object->owner_id = intval($row['owner_id']);
			$object->importance = intval($row['importance']);
			$object->status_id = intval($row['status_id']);
			$object->last_wrote_address_id = intval($row['last_wrote_address_id']);
			$object->first_wrote_address_id = intval($row['first_wrote_address_id']);
			$object->created_date = intval($row['created_date']);
			$object->updated_date = intval($row['updated_date']);
			$object->closed_at = intval($row['closed_at']);
			$object->reopen_at = intval($row['reopen_at']);
			$object->spam_score = floatval($row['spam_score']);
			$object->spam_training = $row['spam_training'];
			$object->interesting_words = $row['interesting_words'];
			$object->num_messages = intval($row['num_messages']);
			$object->num_messages_in = intval($row['num_messages_in']);
			$object->num_messages_out = intval($row['num_messages_out']);
			$object->elapsed_response_first = $row['elapsed_response_first'];
			$object->elapsed_resolution_first = $row['elapsed_resolution_first'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('ticket', $fields, $where);
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(empty($fields))
			return;
		
		if(!is_array($ids))
			$ids = [$ids];
		
		// Truncate the subject if necessary
		if(array_key_exists(self::SUBJECT, $fields))
			if(strlen($fields[self::SUBJECT]) > 255)
				$fields[self::SUBJECT] = mb_substr($fields[self::SUBJECT], 0, 255);
		
		// Only a bucket ID?
		if(array_key_exists(self::BUCKET_ID, $fields) && !array_key_exists(self::GROUP_ID, $fields)) {
			if(false != ($bucket = DAO_Bucket::get($fields[self::BUCKET_ID]))) {
				$fields[self::GROUP_ID] = $bucket->group_id;
			} else {
				unset($fields[self::BUCKET_ID]); 
			}
		}
		
		$context = CerberusContexts::CONTEXT_TICKET;
		self::_updateAbstract($context, $ids, $fields);
		
		if(array_key_exists(self::_PARTICIPANTS, $fields)) {
			$participant_emails = CerberusMail::parseRfcAddresses($fields[self::_PARTICIPANTS]);
			$participant_models = DAO_Address::lookupAddresses(array_keys($participant_emails), true);
			unset($fields[self::_PARTICIPANTS]);
			
			if($participant_models)
			foreach($ids as $id) {
				DAO_Ticket::addParticipantIds($id, array_keys($participant_models));
			}
		}
		
		if(array_key_exists(self::_PARTICIPANT_IDS, $fields)) {
			$add_participant_ids = $remove_participant_ids = [];
			
			$participant_ids = DevblocksPlatform::parseCsvString($fields[self::_PARTICIPANT_IDS]);
			
			if(is_array($participant_ids))
			foreach($participant_ids as $id) {
				if($id < 0) {
					$remove_participant_ids[abs($id)] = true;
				} else {
					$add_participant_ids[$id] = true;
				}
			}
			
			unset($fields[self::_PARTICIPANT_IDS]);
			
			$add_participant_ids = array_keys($add_participant_ids);
			$remove_participant_ids = array_keys($remove_participant_ids);
			
			foreach($ids as $id) {
				if($add_participant_ids)
					DAO_Ticket::addParticipantIds($id, $add_participant_ids);
				if($remove_participant_ids)
					DAO_Ticket::removeParticipantIds($id, $remove_participant_ids);
			}
		}
		
		// If we were given a group but not a bucket, use the default bucket
		if(isset($fields[self::GROUP_ID]) && (!isset($fields[self::BUCKET_ID]) || !$fields[self::BUCKET_ID])) {
			if(false !== ($dest_group = DAO_Group::get($fields[self::GROUP_ID]))) {
				if(false != ($dest_bucket = $dest_group->getDefaultBucket()))
					$fields[self::BUCKET_ID] = $dest_bucket->id;
			}
		}
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_TICKET, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'ticket', $fields);
			
			if($check_deltas) {
				// Trigger local events
				self::processUpdateEvents($batch_ids, $fields);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.ticket.update',
						array(
							'ids' => $batch_ids,
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_TICKET, $batch_ids);
			}
		}
	}
	
	public static function updateWithMessageProperties(array &$properties, Model_Ticket &$ticket, array $change_fields=[], $unset=true) {
		// Automatically add new 'To:' recipients?
		if(!array_key_exists('is_forward', $properties) && array_key_exists('to', $properties)) {
			try {
				if(false != ($to_addys = CerberusMail::parseRfcAddresses($properties['to']))) {
					foreach(array_keys($to_addys) as $to_addy)
						DAO_Ticket::createRequester($to_addy, $ticket->id);
				}
			} catch(Exception $e) {}
		}
		
		if(array_key_exists('owner_id', $properties)) {
			@$owner_id = DevblocksPlatform::importVar($properties['owner_id'], 'int', 0);
			
			if(!$owner_id || null != (DAO_Worker::get($owner_id))) {
				$ticket->owner_id = $owner_id;
				$change_fields[DAO_Ticket::OWNER_ID] = $owner_id;
			}
			
			if($unset)
				unset($properties['owner_id']);
		}
		
		if(array_key_exists('status_id', $properties) && !is_null($properties['status_id'])) {
			@$status_id = DevblocksPlatform::importVar($properties['status_id'], 'int', 0);
			@$reopen_at = DevblocksPlatform::importVar($properties['ticket_reopen'], 'string', '');
			
			// Handle reopen date
			if($reopen_at) {
				if(is_numeric($reopen_at) && false != (@$reopen_at = strtotime('now', $reopen_at))) {
				} else if(false !== (@$reopen_at = strtotime($reopen_at))) {
				}
			}
			
			switch($status_id) {
				case Model_Ticket::STATUS_OPEN:
					$change_fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_OPEN;
					$change_fields[DAO_Ticket::REOPEN_AT] = 0;
					break;
				case Model_Ticket::STATUS_CLOSED:
					$change_fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_CLOSED;
					$change_fields[DAO_Ticket::REOPEN_AT] = intval($reopen_at);
					break;
				case Model_Ticket::STATUS_WAITING:
					$change_fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_WAITING;
					$change_fields[DAO_Ticket::REOPEN_AT] = intval($reopen_at);
					break;
			}
			
			if($unset) {
				unset($properties['status_id']);
				unset($properties['ticket_reopen']);
			}
		}
		
		// Move
		if(array_key_exists('group_id', $properties) || array_key_exists('bucket_id', $properties)) {
			@$move_to_group_id = intval($properties['group_id']);
			@$move_to_bucket_id = intval($properties['bucket_id']);
			
			if(!$move_to_group_id || false == ($move_to_group = DAO_Group::get($move_to_group_id)))
				$move_to_group = DAO_Group::getDefaultGroup();
			
			$change_fields[DAO_Ticket::GROUP_ID] = $move_to_group->id;
			
			// Validate the given bucket id
			
			if(!$move_to_bucket_id
				|| false == ($move_to_bucket = DAO_Bucket::get($move_to_bucket_id))
				|| $move_to_bucket->group_id != $move_to_group->id) {
				
				$move_to_bucket = $move_to_group->getDefaultBucket();
			}
			
			// Move to the new bucket if it is an inbox, or it belongs to the group
			if($move_to_bucket) {
				$change_fields[DAO_Ticket::BUCKET_ID] = $move_to_bucket->id;
			}
			
			if($unset) {
				unset($properties['group_id']);
				unset($properties['bucket_id']);
			}
		}
		
		if($change_fields) {
			DAO_Ticket::update($ticket->id, $change_fields);
		}
		
		// Custom fields
		@$custom_fields = isset($properties['custom_fields']) ? $properties['custom_fields'] : [];
		
		if($custom_fields && is_array($custom_fields)) {
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TICKET, $ticket->id, $custom_fields, true, true, false);
		}
		
		if($unset)
			unset($properties['custom_fields']);
		
		if(array_key_exists('watcher_ids', $properties)) {
			@$watcher_ids = DevblocksPlatform::importVar($properties['watcher_ids'], 'array', []);
			$watcher_ids = DevblocksPlatform::sanitizeArray($watcher_ids, 'int');
			
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id, $watcher_ids);
			
			if($unset)
				unset($properties['watcher_ids']);
		}
		
		return true;
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_TICKET;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;

		@$group_id = $fields[self::GROUP_ID];
		@$bucket_id = $fields[self::BUCKET_ID];
		
		if(!$id && !$group_id) {
			$error = "A 'group_id' is required.";
			return false;
		}
		
		if($group_id) {
			if(false == DAO_Group::get($group_id)) {
				$error = "Invalid 'group_id' value.";
				return false;
			}
			
			// Find the default bucket for this group
			if(!$bucket_id) {
				if(false != ($default_bucket = DAO_Bucket::getDefaultForGroup($fields[self::GROUP_ID]))) {
					$bucket_id = $default_bucket->id;
					$fields[self::BUCKET_ID] = $bucket_id;
				}
			}
			
		// If we have only a bucket_id and no group_id then figure it out
		} else if (!$group_id && $bucket_id) {
			
			if(false == ($bucket = DAO_Bucket::get($bucket_id))) {
				$error = "Invalid 'bucket_id' value.";
				return false;
			}
			
			if(!$group_id) {
				$bucket_id = $bucket->group_id;
				$fields[self::GROUP_ID] = $bucket_id;
			}
		}
		
		return true;
	}
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = [];
		$custom_fields = [];
		
		// Actions
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'importance':
					$v = DevblocksPlatform::intClamp($v, 0, 100);
					$change_fields[DAO_Ticket::IMPORTANCE] = $v;
					break;
					
				case 'move':
					if(!isset($v['group_id']) || !isset($v['bucket_id']))
						break;
						
					$change_fields[DAO_Ticket::GROUP_ID] = intval($v['group_id']);
					$change_fields[DAO_Ticket::BUCKET_ID] = intval($v['bucket_id']);
					break;
					
				case 'org':
					$change_fields[DAO_Ticket::ORG_ID] = intval($v);
					break;
					
				case 'owner':
					$change_fields[DAO_Ticket::OWNER_ID] = intval($v);
					break;
					
				case 'spam':
					if(!empty($v)) {
						foreach($ids as $batch_id)
							CerberusBayes::markTicketAsSpam($batch_id);
					} else {
						foreach($ids as $batch_id)
							CerberusBayes::markTicketAsNotSpam($batch_id);
					}
					break;
					
				case 'status':
					if(!isset($v['status_id']))
						break;
					
					$change_fields[DAO_Ticket::STATUS_ID] = intval($v['status_id']);
					
					if(isset($v['reopen_at'])) {
						@$date = strtotime($v['reopen_at']);
						$change_fields[DAO_Ticket::REOPEN_AT] = intval($date);
					}
					break;
					
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}
		
		CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_TICKET, $ids);
		
		// Fields
		if(!empty($change_fields) || !empty($custom_fields)) {
			if(!array_key_exists('skip_updated', $do))
				$change_fields[DAO_Ticket::UPDATED_DATE] = time();
			
			DAO_Ticket::update($ids, $change_fields, false);
			DAO_Ticket::processUpdateEvents($ids, $change_fields);
		}
		
		// Custom Fields
		C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_TICKET, $custom_fields, $ids);
		
		// Watchers
		if(isset($do['watchers']))
			C4_AbstractView::_doBulkChangeWatchers(CerberusContexts::CONTEXT_TICKET, $do['watchers'], $ids);
		
		// Scheduled behavior
		if(isset($do['behavior']))
			C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_TICKET, $do['behavior'], $ids);
		
		if(array_key_exists('broadcast', $do)) {
			try {
				$broadcast_params = $do['broadcast'];
				
				if(
					!isset($broadcast_params['worker_id']) || empty($broadcast_params['worker_id'])
					|| !isset($broadcast_params['message']) || empty($broadcast_params['message'])
					)
					throw new Exception("Missing parameters for broadcast.");
					
				$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_TICKET, $ids);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_TICKET, array('custom_'));
				
				$is_queued = (isset($broadcast_params['is_queued']) && $broadcast_params['is_queued']) ? true : false;
				
				$broadcast_properties = [
					'worker_id' => $broadcast_params['worker_id'],
					'content' => $broadcast_params['message'],
					'content_format' => $broadcast_params['format'],
					'html_template_id' => @$broadcast_params['html_template_id'] ?: 0,
					'file_ids' => @$broadcast_params['file_ids'] ?: [],
				];
				
				if(is_array($dicts))
				foreach($dicts as $ticket_id => $dict) {
					$message_properties = $broadcast_properties;
					$message_properties['group_id'] = $dict->get('group_id', 0);
					
					$params_json = array(
						'in_reply_message_id' => $dict->latest_message_id,
						'is_broadcast' => 1,
						'group_id' => $dict->group_id,
						'bucket_id' => $dict->bucket_id,
						'to' => $dict->requester_emails,
						'subject' => $dict->subject,
						'content' => $tpl_builder->build($message_properties['content'], $dict),
						'worker_id' => $message_properties['worker_id'],
					);
					
					if(isset($message_properties['content_format']))
						$params_json['format'] = $message_properties['content_format'];
					
					if(isset($message_properties['html_template_id']))
						$params_json['html_template_id'] = intval($message_properties['html_template_id']);
					
					$fields = array(
						DAO_MailQueue::TYPE => Model_MailQueue::TYPE_TICKET_REPLY,
						DAO_MailQueue::TICKET_ID => $ticket_id,
						DAO_MailQueue::WORKER_ID => $message_properties['worker_id'],
						DAO_MailQueue::UPDATED => time(),
						DAO_MailQueue::HINT_TO => $dict->initial_message_sender_address,
						DAO_MailQueue::NAME => $dict->subject,
					);
					
					if($is_queued)
						$fields[DAO_MailQueue::IS_QUEUED] = 1;

					if(isset($message_properties['file_ids']))
						$params_json['file_ids'] = $message_properties['file_ids'];
					
					if(!empty($params_json))
						$fields[DAO_MailQueue::PARAMS_JSON] = json_encode($params_json);
					
					DAO_MailQueue::create($fields);
				}
			} catch (Exception $e) {
				
			}
		}
		
		DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_TICKET, $ids);

		$update->markCompleted();
		return true;
	}
	
	static function processUpdateEvents($ids, $change_fields) {

		// We only care about these fields, so abort if they aren't referenced

		$observed_fields = array(
			DAO_Ticket::OWNER_ID,
			DAO_Ticket::GROUP_ID,
			DAO_Ticket::BUCKET_ID,
			DAO_Ticket::STATUS_ID,
		);
		
		$used_fields = array_intersect($observed_fields, array_keys($change_fields));
		
		if(empty($used_fields))
			return;
		
		// Load records only if they're needed
		
		if(false == ($before_models = CerberusContexts::getCheckpoints(CerberusContexts::CONTEXT_TICKET, $ids)))
			return;
		
		if(false == ($models = DAO_Ticket::getIds($ids)))
			return;
		
		foreach($models as $id => $model) {
			if(!isset($before_models[$id]))
				continue;
			
			$before_model = (object) $before_models[$id];
			
			/*
			 * Owner changed
			 */
			
			@$owner_id = $change_fields[DAO_Ticket::OWNER_ID];
			
			if($owner_id == $before_model->owner_id)
				unset($change_fields[DAO_Ticket::OWNER_ID]);
			
			if(isset($change_fields[DAO_Ticket::OWNER_ID])) {
				
				// Mail assigned in group
				
				Event_MailAssignedInGroup::trigger($model->id, $model->group_id);
				
				// Log activity (ticket.unassigned)
				
				if(empty($model->owner_id)) {
					$activity_point = 'ticket.owner.unassigned';
					
					$entry = array(
						// {{actor}} unassigned ticket {{target}}
						'message' => 'activities.ticket.unassigned',
						'variables' => array(
							'target' => sprintf("[%s] %s", $model->mask, $model->subject),
							),
						'urls' => array(
							'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_TICKET, $model->id, $model->mask),
							)
					);
					CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_TICKET, $model->id, $entry);
				}
				
				// Log activity (ticket.assigned)
				
				elseif($model->owner_id) {
					$activity_point = 'ticket.owner.assigned';
					
					if(false != ($target_worker = DAO_Worker::get($model->owner_id)) && ($target_worker instanceof Model_Worker)) {
						$entry = array(
							//{{actor}} assigned ticket {{target}} to worker {{worker}}
							'message' => 'activities.ticket.assigned',
							'variables' => array(
								'target' => sprintf("[%s] %s", $model->mask, $model->subject),
								'worker' => $target_worker->getName(),
								),
							'urls' => array(
								'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_TICKET, $model->id, $model->mask),
								'worker' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_WORKER, $model->owner_id, DevblocksPlatform::strToPermalink($target_worker->getName())),
								)
						);
						CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_TICKET, $model->id, $entry);
					}
				}
			}
			
			/*
			 * Ticket moved
			 */
			
			@$group_id = $change_fields[DAO_Ticket::GROUP_ID];
			@$bucket_id = $change_fields[DAO_Ticket::BUCKET_ID];
			
			if($group_id == $before_model->group_id)
				unset($change_fields[DAO_Ticket::GROUP_ID]);
			
			if($bucket_id == $before_model->bucket_id)
				unset($change_fields[DAO_Ticket::BUCKET_ID]);
			
			if(isset($change_fields[DAO_Ticket::GROUP_ID]) || isset($change_fields[DAO_Ticket::BUCKET_ID])) {
				// Bots

				Event_MailMovedToGroup::trigger($model->id, $model->group_id);

				// Activity log
				
				@$to_group = DAO_Group::get($model->group_id);
				@$to_bucket = DAO_Bucket::get($model->bucket_id);
				
				if($to_group && $to_bucket) {
					$entry = [
						//{{actor}} moved ticket {{target}} to {{group}} {{bucket}}
						'message' => 'activities.ticket.moved',
						'variables' => [
							'target' => sprintf("[%s] %s", $model->mask, $model->subject),
							'group' => $to_group->name,
							'bucket' => $to_bucket->name,
							],
						'urls' => [
							'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_TICKET, $model->id, $model->mask),
							'group' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_GROUP, $to_group->id, $to_group->name),
							]
					];
					CerberusContexts::logActivity('ticket.moved', CerberusContexts::CONTEXT_TICKET, $model->id, $entry);
				}
			}
			
			/*
			 * Ticket status change
			 */

			@$status_id = $change_fields[DAO_Ticket::STATUS_ID];
			
			if($status_id == $before_model->status_id)
				unset($change_fields[DAO_Ticket::STATUS_ID]);
			
			if(isset($change_fields[DAO_Ticket::STATUS_ID])) {

				/*
				 * If closing for the first time, record the date and elapsed time
				 */

				if(isset($change_fields[DAO_Ticket::STATUS_ID]) && $status_id == Model_Ticket::STATUS_CLOSED) {
					if(empty($model->closed_at)) {
						DAO_Ticket::update(
							$model->id,
							array(
								DAO_Ticket::CLOSED_AT => time(),
								DAO_Ticket::ELAPSED_RESOLUTION_FIRST => (time()-intval($model->created_date)),
							),
							false
						);
					}
				}
				
				/*
				 * Log activity
				 */
				
				$status_to = null;
				$activity_point = null;
				
				if($model->status_id == Model_Ticket::STATUS_DELETED) {
					$status_to = 'deleted';
					$activity_point = 'ticket.status.deleted';
					
				} else if($model->status_id == Model_Ticket::STATUS_CLOSED) {
					$status_to = 'closed';
					$activity_point = 'ticket.status.closed';
					
					Event_MailClosedInGroup::trigger($model->id, $model->group_id);
					
				} else if($model->status_id == Model_Ticket::STATUS_WAITING) {
					$status_to = 'waiting';
					$activity_point = 'ticket.status.waiting';
					
				} else {
					$status_to = 'open';
					$activity_point = 'ticket.status.open';
					
				}
				
				if(!empty($status_to) && !empty($activity_point)) {
					/*
					 * Log activity (ticket.status.*)
					 */
					$entry = array(
						//{{actor}} changed ticket {{target}} to status {{status}}
						'message' => 'activities.ticket.status',
						'variables' => array(
							'target' => sprintf("[%s] %s", $model->mask, $model->subject),
							'status' => $status_to,
							),
						'urls' => array(
							'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_TICKET, $model->id, $model->mask),
							)
					);
					CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_TICKET, $model->id, $entry);
				}
			}
			
		} // end $model loop
		
	}
	
	static function updateMessageCount($id) {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("UPDATE ticket ".
			"SET ".
			"num_messages = (SELECT count(id) FROM message WHERE message.ticket_id = ticket.id), ".
			"num_messages_in = (SELECT count(id) FROM message WHERE message.ticket_id = ticket.id AND message.is_outgoing=0), ".
			"num_messages_out = (SELECT count(id) FROM message WHERE message.ticket_id = ticket.id AND message.is_outgoing=1) ".
			"WHERE ticket.id = %d",
			$id
		));
	}
	
	/**
	 *
	 * @param integer $ticket_id
	 * @return Model_Address[]
	 */
	static function getRequestersByTicket($ticket_id) {
		$db = DevblocksPlatform::services()->database();
		
		$ids = [];
		
		$sql = sprintf("SELECT a.id ".
			"FROM address a ".
			"INNER JOIN requester r ON (r.ticket_id = %d AND a.id=r.address_id) ".
			"ORDER BY a.email ASC ",
			$ticket_id
		);
		$results = $db->GetArrayReader($sql);

		if(is_array($results))
		foreach($results as $result) {
			$ids[] = $result['id'];
		}
		
		return DAO_Address::getIds($ids);
	}
	
	static function findMissingRequestersInHeaders($headers, $current_requesters=[]) {
		$results = [];
		$addys = [];

		@$from = CerberusMail::parseRfcAddresses($headers['from']);
		if(!empty($from))
			$addys = array_merge($addys, !is_array($from) ? array($from) : $from);
		
		@$to = CerberusMail::parseRfcAddresses($headers['to']);
		if(!empty($to))
			$addys = array_merge($addys, !is_array($to) ? array($to) : $to);
		
		@$cc = CerberusMail::parseRfcAddresses($headers['cc']);
		if(!empty($cc))
			$addys = array_merge($addys, !is_array($cc) ? array($cc) : $cc);

		$exclude_list = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::PARSER_AUTO_REQ_EXCLUDE, CerberusSettingsDefaults::PARSER_AUTO_REQ_EXCLUDE);
		@$excludes = DevblocksPlatform::parseCrlfString($exclude_list);
		
		if(is_array($addys))
		foreach($addys as $addy_data) {
			$addy_data['email'] = DevblocksPlatform::strLower($addy_data['email']);
			
			try {
				// Filter out our own addresses
				if(DAO_Address::isLocalAddress($addy_data['email']))
					continue;
				
				// Filter explicit excludes
				if(is_array($excludes) && !empty($excludes))
				foreach($excludes as $excl_pattern) {
					if(@preg_match(DevblocksPlatform::parseStringAsRegExp($excl_pattern), $addy_data['email'])) {
						throw new Exception();
					}
				}
				
				// If we aren't given a personal name, attempt to look them up
				if(empty($addy_data['personal'])) {
					if(null != ($addy_lookup = DAO_Address::lookupAddress($addy_data['email']))) {
						$addy_fullname = $addy_lookup->getName();
						if(!empty($addy_fullname)) {
							$addy_data['personal'] = $addy_fullname;
							$addy_data['full_email'] = CerberusMail::writeRfcAddress($addy_data['email'], $addy_fullname);
						}
					}
				}
				
				$results[$addy_data['email']] = $addy_data;
				
			} catch(Exception $e) {
			}
		}

		// Filter existing requesters
		if(is_array($current_requesters))
		foreach($current_requesters as $current_requester)
			unset($results[$current_requester->email]);
		
		return $results;
	}
	
	static function createRequester($raw_email, $ticket_id) {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		$replyto_addresses = DAO_Address::getLocalAddresses();

		if(null == ($address = CerberusApplication::hashLookupAddress($raw_email, true))) {
			$logger->warn(sprintf("[Parser] %s is a malformed requester e-mail address.", $raw_email));
			return false;
		}
		
		// Don't add a requester if the sender is a helpdesk address
		if(isset($replyto_addresses[$address->id])) {
			$logger->info(sprintf("[Parser] Not adding %s as a requester because it's a helpdesk-controlled address. ", $address->email));
			return false;
		}
		
		// Filter out any excluded requesters
		$exclude_list = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::PARSER_AUTO_REQ_EXCLUDE, CerberusSettingsDefaults::PARSER_AUTO_REQ_EXCLUDE);
		if(!empty($exclude_list)) {
			@$excludes = DevblocksPlatform::parseCrlfString($exclude_list);
			
			if(is_array($excludes) && !empty($excludes))
			foreach($excludes as $excl_pattern) {
				if(@preg_match(DevblocksPlatform::parseStringAsRegExp($excl_pattern), $address->email)) {
					$logger->info(sprintf("[Parser] Not adding (%s) as a requester because they match (%s) on the exclude list. ", $address->email, $excl_pattern));
					return false;
				}
			}
		}
		
		$db->ExecuteMaster(sprintf("REPLACE INTO requester (address_id, ticket_id) ".
			"VALUES (%d, %d)",
			$address->id,
			$ticket_id
		));
		
		return true;
	}
	
	static function deleteRequester($id, $address_id) {
		if(empty($id) || empty($address_id))
			return;
			
		$db = DevblocksPlatform::services()->database();

		$sql = sprintf("DELETE FROM requester WHERE ticket_id = %d AND address_id = %d",
			$id,
			$address_id
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
	}
	
	static function addParticipantIds($ticket_id, $address_ids) {
		$db = DevblocksPlatform::services()->database();
		
		$replyto_addresses = DAO_Address::getLocalAddresses();
		$exclude_list = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::PARSER_AUTO_REQ_EXCLUDE, CerberusSettingsDefaults::PARSER_AUTO_REQ_EXCLUDE);
		$addresses = DAO_Address::getIds($address_ids);
		
		if(false == ($ticket = DAO_Ticket::get($ticket_id)))
			return false;

		// Filter out any excluded requesters
		if(!empty($exclude_list)) {
			@$excludes = DevblocksPlatform::parseCrlfString($exclude_list);
			
			$addresses = array_filter($addresses, function($address) use ($excludes) {
				if(is_array($excludes) && !empty($excludes))
				foreach($excludes as $excl_pattern) {
					if(@preg_match(DevblocksPlatform::parseStringAsRegExp($excl_pattern), $address->email)) {
						return false;
					}
				}
				return true;
			});
		}
		
		// Don't add a requester if the sender is a helpdesk address
		$requesters_add = array_diff(array_keys($addresses), array_keys($replyto_addresses));

		$values = [];
		
		if(is_array($requesters_add))
		foreach($requesters_add as $requester_id) {
			$values[] = sprintf("(%d, %d)", $requester_id, $ticket_id);
			
			/*
			 * Log activity (ticket.participant.added)
			 */
			$entry = array(
				//{{actor}} added {{participant}} to ticket {{target}}
				'message' => 'activities.ticket.participant.added',
				'variables' => array(
					'participant' => sprintf("%s", $addresses[$requester_id]->email),
					'target' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
					),
				'urls' => array(
					'participant' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_ADDRESS, $requester_id),
					'target' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $ticket->mask),
					)
			);
			CerberusContexts::logActivity('ticket.participant.added', CerberusContexts::CONTEXT_TICKET, $ticket->id, $entry);
		}
		
		if(!empty($values)) {
			$db->ExecuteMaster(sprintf("REPLACE INTO requester (address_id, ticket_id) ".
				"VALUES %s",
				implode(',', $values)
			));
		}
			
		return true;
	}
	
	static function removeParticipantIds($ticket_id, $address_ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ticket_id) || !is_array($address_ids))
			return false;
		
		if(false == ($ticket = DAO_Ticket::get($ticket_id)))
			return false;
		
		$address_ids = DevblocksPlatform::sanitizeArray($address_ids, 'int');
		
		// Keep only the participants we're removing
		$participants = $ticket->getRequesters();
		$participants = array_intersect_key($participants, array_flip($address_ids));
		
		if(empty($participants))
			return false;

		$sql = sprintf("DELETE FROM requester WHERE ticket_id = %d AND address_id IN (%s)",
			$ticket_id,
			implode(',', array_keys($participants))
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		foreach($participants as $participant) {
			/*
			 * Log activity (ticket.participant.added)
			 */
			$entry = array(
				//{{actor}} removed {{participant}} from ticket {{target}}
				'message' => 'activities.ticket.participant.removed',
				'variables' => array(
					'participant' => sprintf("%s", $participant->email),
					'target' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
					),
				'urls' => array(
					'participant' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_ADDRESS, $participant->id),
					'target' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $ticket->mask),
					)
			);
			CerberusContexts::logActivity('ticket.participant.removed', CerberusContexts::CONTEXT_TICKET, $ticket->id, $entry);
		}
		
		return true;
	}
	
	public static function random() {
		return self::_getRandom('ticket');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Ticket::getFields();
		
		if(is_string($sortBy))
		switch($sortBy) {
			case SearchFields_Ticket::TICKET_IMPORTANCE:
				$sortBy = array(
					SearchFields_Ticket::TICKET_IMPORTANCE,
					SearchFields_Ticket::TICKET_UPDATED_DATE,
				);
				
				$sortAsc = array(
					$sortAsc,
					!$sortAsc,
				);
				break;
				
			case SearchFields_Ticket::BUCKET_RESPONSIBILITY:
				$sortBy = array(
					SearchFields_Ticket::BUCKET_RESPONSIBILITY,
					SearchFields_Ticket::TICKET_IMPORTANCE,
					SearchFields_Ticket::TICKET_UPDATED_DATE,
				);
				
				$sortAsc = array(
					$sortAsc,
					$sortAsc,
					!$sortAsc,
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				$sortBy = array(
					SearchFields_Ticket::TICKET_STATUS_ID,
				);
				
				$sortAsc = array(
					$sortAsc,
				);
				break;
		}
		
		list($tables, $wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Ticket', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.mask as %s, ".
			"t.subject as %s, ".
			"t.status_id as %s, ".
			"t.first_wrote_address_id as %s, ".
			"t.last_wrote_address_id as %s, ".
			"t.first_message_id as %s, ".
			"t.first_outgoing_message_id as %s, ".
			"t.last_message_id as %s, ".
			"t.created_date as %s, ".
			"t.updated_date as %s, ".
			"t.closed_at as %s, ".
			"t.reopen_at as %s, ".
			"t.spam_training as %s, ".
			"t.spam_score as %s, ".
			"t.num_messages as %s, ".
			"t.num_messages_in as %s, ".
			"t.num_messages_out as %s, ".
			"t.elapsed_response_first as %s, ".
			"t.elapsed_resolution_first as %s, ".
			"t.owner_id as %s, ".
			"t.importance as %s, ".
			"t.group_id as %s, ".
			"t.bucket_id as %s, ".
			"t.org_id as %s ",
				SearchFields_Ticket::TICKET_ID,
				SearchFields_Ticket::TICKET_MASK,
				SearchFields_Ticket::TICKET_SUBJECT,
				SearchFields_Ticket::TICKET_STATUS_ID,
				SearchFields_Ticket::TICKET_FIRST_WROTE_ID,
				SearchFields_Ticket::TICKET_LAST_WROTE_ID,
				SearchFields_Ticket::TICKET_FIRST_MESSAGE_ID,
				SearchFields_Ticket::TICKET_FIRST_OUTGOING_MESSAGE_ID,
				SearchFields_Ticket::TICKET_LAST_MESSAGE_ID,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				SearchFields_Ticket::TICKET_CLOSED_AT,
				SearchFields_Ticket::TICKET_REOPEN_AT,
				SearchFields_Ticket::TICKET_SPAM_TRAINING,
				SearchFields_Ticket::TICKET_SPAM_SCORE,
				SearchFields_Ticket::TICKET_NUM_MESSAGES,
				SearchFields_Ticket::TICKET_NUM_MESSAGES_IN,
				SearchFields_Ticket::TICKET_NUM_MESSAGES_OUT,
				SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST,
				SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST,
				SearchFields_Ticket::TICKET_OWNER_ID,
				SearchFields_Ticket::TICKET_IMPORTANCE,
				SearchFields_Ticket::TICKET_GROUP_ID,
				SearchFields_Ticket::TICKET_BUCKET_ID,
				SearchFields_Ticket::TICKET_ORG_ID
		);

		$join_sql =
			"FROM ticket t ".
			// Dynamic table joins
			(isset($tables['msg']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
			'';
		
		if(isset($tables['wtb'])) {
			if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
				$select_sql .= ", wtb.responsibility_level as wtb_responsibility ";
				$join_sql .= sprintf("INNER JOIN worker_to_bucket wtb ON (wtb.bucket_id=t.bucket_id AND wtb.worker_id=%d AND wtb.responsibility_level > 0) ", $active_worker->id);
			}
		}
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Ticket');

		$result = array(
			'primary_table' => 't',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	static function autocomplete($term, $as='models') {
		$db = DevblocksPlatform::services()->database();
		$objects = [];
		
		$results = $db->GetArrayReader(sprintf("SELECT id ".
			"FROM ticket ".
			"WHERE ticket.status_id != 3 ".
			"AND (".
			"mask LIKE %s ".
			"OR subject LIKE %s ".
			") ".
			"ORDER BY id DESC ".
			"LIMIT 25 ",
			$db->qstr($term.'%'),
			$db->qstr($term.'%')
		));
		
		if(is_array($results))
		foreach($results as $row) {
			$objects[$row['id']] = null;
		}
		
		switch($as) {
			case 'ids':
				return array_keys($objects);
				
			default:
				return DAO_Ticket::getIds(array_keys($objects));
		}
	}
	
	static function mergeIds($from_ids, $to_id) {
		$db = DevblocksPlatform::services()->database();

		$context = CerberusContexts::CONTEXT_TICKET;
		
		if(empty($from_ids) || empty($to_id))
			return false;
			
		if(!is_numeric($to_id) || !is_array($from_ids))
			return false;
		
		self::_mergeIds($context, $from_ids, $to_id);
		
		// Messages
		$sql = sprintf("UPDATE message SET ticket_id = %d WHERE ticket_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		);
		$db->ExecuteMaster($sql);
		
		// Mail queue
		$sql = sprintf("UPDATE mail_queue SET ticket_id = %d WHERE ticket_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		);
		$db->ExecuteMaster($sql);
		
		// Requesters
		$sql = sprintf("INSERT IGNORE INTO requester (address_id, ticket_id) ".
			"SELECT address_id, %d FROM requester WHERE ticket_id IN (%s)",
			$to_id,
			implode(',', $from_ids)
		);
		$db->ExecuteMaster($sql);
		
		$sql = sprintf("DELETE FROM requester WHERE ticket_id IN (%s)",
			implode(',', $from_ids)
		);
		$db->ExecuteMaster($sql);
		
		DAO_Ticket::rebuild($to_id);
		
		$dest_ticket = DAO_Ticket::get($to_id);
		$merged_tickets = DAO_Ticket::getIds($from_ids);
		
		// Set up forwarders for the old masks to their new mask
		if(is_array($merged_tickets))
		foreach($merged_tickets as $ticket_id => $ticket) {
			// Clear old ticket meta
			$fields = [
				DAO_Ticket::MASK => !DevblocksPlatform::strEndsWith($ticket->mask, '-MERGED') ? ($ticket->mask . '-MERGED') : $ticket->mask,
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
				DAO_Ticket::REOPEN_AT => 0,
				DAO_Ticket::FIRST_MESSAGE_ID => 0,
				DAO_Ticket::FIRST_OUTGOING_MESSAGE_ID => 0,
				DAO_Ticket::LAST_MESSAGE_ID => 0,
				DAO_Ticket::NUM_MESSAGES => 0,
				DAO_Ticket::NUM_MESSAGES_IN => 0,
				DAO_Ticket::NUM_MESSAGES_OUT => 0,
				DAO_Ticket::ELAPSED_RESPONSE_FIRST => 0,
				DAO_Ticket::ELAPSED_RESOLUTION_FIRST => 0,
			];
			DAO_Ticket::update($ticket_id, $fields, false);
			
			// Forward the old mask to the new mask
			$sql = sprintf("INSERT IGNORE INTO ticket_mask_forward (old_mask, new_mask, new_ticket_id) VALUES (%s, %s, %d)",
				$db->qstr($ticket->mask),
				$db->qstr($dest_ticket->mask),
				$dest_ticket->id
			);
			$db->ExecuteMaster($sql);
			
			// If the old mask was a new_mask in a past life, change to its new destination
			$sql = sprintf("UPDATE ticket_mask_forward SET new_mask = %s, new_ticket_id = %d WHERE new_mask = %s",
				$db->qstr($dest_ticket->mask),
				$dest_ticket->id,
				$db->qstr($ticket->mask)
			);
			$db->ExecuteMaster($sql);
		}
		
		return true;
	}
	
	static function split(Model_Message $orig_message, &$error=null) {
		if(null == ($orig_headers = $orig_message->getHeaders())) {
			$error = "The given message lacked message headers.";
			return false;
		}
		
		if(null == ($orig_ticket = DAO_Ticket::get($orig_message->ticket_id))) {
			$error = "The given message has an invalid parent ticket.";
			return false;
		}
		
		if(null == ($messages = DAO_Message::getMessagesByTicket($orig_message->ticket_id))) {
			$error = "There are no messages on the parent ticket.";
			return false;
		}
		
		if(count($messages) < 2) {
			$error = "There must be at least two messages on the parent ticket.";
			return false;
		}
		
		// Create a new ticket
		$new_ticket_mask = CerberusApplication::generateTicketMask();
		
		$fields = [
			DAO_Ticket::CREATED_DATE => $orig_message->created_date,
			DAO_Ticket::UPDATED_DATE => $orig_message->created_date,
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
			DAO_Ticket::MASK => $new_ticket_mask,
			DAO_Ticket::SUBJECT => (is_array($orig_headers) && isset($orig_headers['subject']) ? $orig_headers['subject'] : $orig_ticket->subject),
			DAO_Ticket::GROUP_ID => $orig_ticket->group_id,
			DAO_Ticket::BUCKET_ID => $orig_ticket->bucket_id,
			DAO_Ticket::ORG_ID => $orig_ticket->org_id,
			DAO_Ticket::IMPORTANCE => $orig_ticket->importance,
		];
		
		$new_ticket_id = DAO_Ticket::create($fields);
		
		if(null == ($new_ticket = DAO_Ticket::get($new_ticket_id))) {
			$error = "Failed to create a new ticket for the given message.";
			return false;
		}
		
		// Copy all the original tickets requesters
		$orig_requesters = DAO_Ticket::getRequestersByTicket($orig_ticket->id);
		foreach($orig_requesters as $orig_req_addy) {
			DAO_Ticket::createRequester($orig_req_addy->email, $new_ticket_id);
		}
		
		// Pull the message off the ticket (reparent)
		unset($messages[$orig_message->id]);
		
		DAO_Message::update($orig_message->id, [
			DAO_Message::TICKET_ID => $new_ticket_id
		]);
		
		DAO_Ticket::rebuild($new_ticket_id);
		DAO_Ticket::rebuild($orig_ticket->id);
		
		/*
		 * Log activity (Ticket Split)
		 */
		
		$entry = [
			//{{actor}} split from ticket {{target}} into ticket {{source}}
			'message' => 'activities.ticket.split',
			'variables' => [
				'target' => sprintf("[%s] %s", $orig_ticket->mask, $orig_ticket->subject),
				'source' => sprintf("[%s] %s", $new_ticket->mask, $new_ticket->subject),
				],
			'urls' => [
				'target' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $orig_ticket->mask),
				'source' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $new_ticket->mask),
				]
		];
		CerberusContexts::logActivity('ticket.split', CerberusContexts::CONTEXT_TICKET, $orig_ticket->id, $entry);
		
		/*
		 * Log activity (Ticket Split From)
		 */
		
		$entry = [
			//{{actor}} split into ticket {{target}} from ticket {{source}}
			'message' => 'activities.ticket.split.from',
			'variables' => [
				'target' => sprintf("[%s] %s", $new_ticket->mask, $new_ticket->subject),
				'source' => sprintf("[%s] %s", $orig_ticket->mask, $orig_ticket->subject),
				],
			'urls' => [
				'target' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $new_ticket->mask),
				'source' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $orig_ticket->mask),
				]
		];
		CerberusContexts::logActivity('ticket.split', CerberusContexts::CONTEXT_TICKET, $new_ticket->id, $entry);
		
		return [
			'id' => $new_ticket_id,
			'mask' => $new_ticket_mask,
		];
	}
	
	/**
	 *
	 * @param array $ids
	 */
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("UPDATE ticket SET status_id = %d WHERE id IN (%s)", Model_Ticket::STATUS_DELETED, $ids_list));

		return true;
	}
	
	/**
	 * @param $columns
	 * @param $params
	 * @param int $limit
	 * @param int $page
	 * @param null $sortBy
	 * @param null $sortAsc
	 * @param bool $withCounts
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$fulltext_params = [];
		
		foreach($params as $param_key => $param) {
			if(!($param instanceof DevblocksSearchCriteria))
				continue;
			
			if($param->field == SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT) {
				$fulltext_params[$param_key] = $param;
				unset($params[$param_key]);
			}
		}
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);
		
		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		if(!empty($fulltext_params)) {
			foreach($fulltext_params as $param) {
				$where_sql .= 'AND ' . SearchFields_Ticket::getWhereSQL($param) . ' ';
			}
		}
		
		return self::_searchWithTimeout(
			SearchFields_Ticket::TICKET_ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
};

class SearchFields_Ticket extends DevblocksSearchFields {
	// Ticket
	const TICKET_ID = 't_id';
	const TICKET_MASK = 't_mask';
	const TICKET_STATUS_ID = 't_status_id';
	const TICKET_SUBJECT = 't_subject';
	const TICKET_FIRST_MESSAGE_ID = 't_first_message_id';
	const TICKET_FIRST_OUTGOING_MESSAGE_ID = 't_first_outgoing_message_id';
	const TICKET_LAST_MESSAGE_ID = 't_last_message_id';
	const TICKET_FIRST_WROTE_ID = 't_first_wrote_address_id';
	const TICKET_LAST_WROTE_ID = 't_last_wrote_address_id';
	const TICKET_CREATED_DATE = 't_created_date';
	const TICKET_UPDATED_DATE = 't_updated_date';
	const TICKET_CLOSED_AT = 't_closed_at';
	const TICKET_REOPEN_AT = 't_reopen_at';
	const TICKET_SPAM_SCORE = 't_spam_score';
	const TICKET_SPAM_TRAINING = 't_spam_training';
	const TICKET_INTERESTING_WORDS = 't_interesting_words';
	const TICKET_NUM_MESSAGES = 't_num_messages';
	const TICKET_NUM_MESSAGES_IN = 't_num_messages_in';
	const TICKET_NUM_MESSAGES_OUT = 't_num_messages_out';
	const TICKET_ELAPSED_RESPONSE_FIRST = 't_elapsed_response_first';
	const TICKET_ELAPSED_RESOLUTION_FIRST = 't_elapsed_resolution_first';
	const TICKET_GROUP_ID = 't_group_id';
	const TICKET_BUCKET_ID = 't_bucket_id';
	const TICKET_ORG_ID = 't_org_id';
	const TICKET_OWNER_ID = 't_owner_id';
	const TICKET_IMPORTANCE = 't_importance';
	
	// Responsibilities
	const BUCKET_RESPONSIBILITY = 'wtb_responsibility';
	
	// Sender
	const SENDER_ADDRESS = 'a1_address';
	
	// Requester
	const REQUESTER_ID = 'r_id';
	const REQUESTER_ADDRESS = 'ra_email';
	
	// Fulltexts
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	const FULLTEXT_MESSAGE_CONTENT = 'ftmc_content';
	
	// Virtuals
	const VIRTUAL_BUCKET_SEARCH = '*_bucket_search';
	const VIRTUAL_COMMENTS_SEARCH = '*_comments_search';
	const VIRTUAL_COMMENTS_FIRST_SEARCH = '*_comments_first_search';
	const VIRTUAL_COMMENTS_LAST_SEARCH = '*_comments_last_search';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_GROUP_SEARCH = '*_group_search';
	const VIRTUAL_GROUPS_OF_WORKER = '*_groups_of_worker';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_MESSAGE_FIRST_SEARCH = '*_message_first_search';
	const VIRTUAL_MESSAGE_FIRST_OUTGOING_SEARCH = '*_message_first_outgoing_search';
	const VIRTUAL_MESSAGE_LAST_SEARCH = '*_message_last_search';
	const VIRTUAL_MESSAGES_SEARCH = '*_messages_search';
	const VIRTUAL_ORG_SEARCH = '*_org_search';
	const VIRTUAL_OWNER_SEARCH = '*_owner_search';
	const VIRTUAL_PARTICIPANT_ID = '*_participant_id';
	const VIRTUAL_PARTICIPANT_SEARCH = '*_participant_search';
	const VIRTUAL_STATUS = '*_status';
	const VIRTUAL_WATCHERS = '*_workers';
	const VIRTUAL_WATCHERS_COUNT = '*_workers_count';
	const VIRTUAL_WORKER_COMMENTED = '*_worker_commented';
	const VIRTUAL_WORKER_REPLIED = '*_worker_replied';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 't.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_TICKET => new DevblocksSearchFieldContextKeys('t.id', self::TICKET_ID),
			CerberusContexts::CONTEXT_ORG => new DevblocksSearchFieldContextKeys('t.org_id', self::TICKET_ORG_ID),
			CerberusContexts::CONTEXT_WORKER => new DevblocksSearchFieldContextKeys('t.owner_id', self::TICKET_OWNER_ID),
			CerberusContexts::CONTEXT_GROUP => new DevblocksSearchFieldContextKeys('t.group_id', self::TICKET_GROUP_ID),
			CerberusContexts::CONTEXT_BUCKET => new DevblocksSearchFieldContextKeys('t.bucket_id', self::TICKET_BUCKET_ID),
			CerberusContexts::CONTEXT_ADDRESS => new DevblocksSearchFieldContextKeys('t.first_wrote_address_id', self::TICKET_FIRST_WROTE_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::BUCKET_RESPONSIBILITY:
				$level = DevblocksPlatform::intClamp($param->value, 0, 100);
				$oper = DevblocksSearchCriteria::sanitizeOperator($param->operator);
				
				return sprintf("wtb.responsibility_level %s %d",
					$oper,
					$level
				);
			
			case self::FULLTEXT_MESSAGE_CONTENT:
				if(false == ($search = Extension_DevblocksSearchSchema::get(Search_MessageContent::ID)))
					return null;
				
				$query = $search->getQueryFromParam($param);
				$join_key = self::getPrimaryKey();
				
				if(DevblocksPlatform::strStartsWith($query, '!')) {
					$not = true;
					$query = ltrim($query, '!');
				} else {
					$not = false;
				}
				
				return $search->generateSql(
					$query,
					[],
					function($sql) use ($join_key, $not) {
						//return sprintf('%sEXISTS (SELECT message.ticket_id FROM message WHERE ticket_id=%s AND EXISTS (%s))',
						return sprintf('%s %sIN (SELECT message.ticket_id FROM message WHERE ticket_id=%s AND id IN (%s))',
							$join_key,
							$not ? 'NOT ' : '',
							$join_key,
							$sql
						);
					},
					function($id_key) use ($join_key) {
						return [
							sprintf('%s = message.id',
								Cerb_ORMHelper::escape($id_key)
							)
						];
					},
					function(array $ids) use ($join_key, $not) {
						return sprintf('%s %sIN (SELECT ticket_id FROM message WHERE ticket_id=%s AND id IN (%s))',
							$join_key,
							$not ? 'NOT ' : '',
							$join_key,
							implode(', ', $ids)
						);
					}
				);
				
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_TICKET, self::getPrimaryKey());
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_TICKET, self::getPrimaryKey());
				
			case self::VIRTUAL_BUCKET_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_BUCKET, 't.bucket_id');
				
			case self::VIRTUAL_COMMENTS_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_COMMENT, sprintf('SELECT context_id FROM comment WHERE context = %s AND id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET), '%s'), 't.id');
			
			case self::VIRTUAL_COMMENTS_FIRST_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_COMMENT, sprintf('SELECT context_id FROM comment WHERE context = %s AND id = (SELECT MIN(id) FROM comment WHERE context = %s AND context_id = %s) AND id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET), Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET), 't.id', '%s'), 't.id');
			
			case self::VIRTUAL_COMMENTS_LAST_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_COMMENT, sprintf('SELECT context_id FROM comment WHERE context = %s AND id = (SELECT MAX(id) FROM comment WHERE context = %s AND context_id = %s) AND id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET), Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET), 't.id', '%s'), 't.id');
			
			case self::VIRTUAL_GROUP_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_GROUP, 't.group_id');
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET)), 't.id');
				
			case self::VIRTUAL_ORG_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_ORG, 't.org_id');
				
			case self::VIRTUAL_OWNER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKER, 't.owner_id');
				
			case self::VIRTUAL_MESSAGE_FIRST_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_MESSAGE, 'SELECT id FROM message WHERE id IN (%s)', 't.first_message_id');
				
			case self::VIRTUAL_MESSAGE_FIRST_OUTGOING_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_MESSAGE, 'SELECT id FROM message WHERE id IN (%s)', 't.first_outgoing_message_id');
				
			case self::VIRTUAL_MESSAGE_LAST_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_MESSAGE, 'SELECT id FROM message WHERE id IN (%s)', 't.last_message_id');
				
			case self::VIRTUAL_MESSAGES_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_MESSAGE, 'SELECT ticket_id FROM message WHERE id IN (%s)', 't.id');
				
			case self::VIRTUAL_PARTICIPANT_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_ADDRESS, 'SELECT ticket_id FROM requester WHERE address_id IN (%s)', 't.id');
				
			// [TODO]
			// [TODO] IN, NOT
			case self::VIRTUAL_PARTICIPANT_ID:
				$participant_ids = is_array($param->value) ? $param->value : array($param->value);
				$participant_ids = DevblocksPlatform::sanitizeArray($participant_ids, 'int');
				
				$participant_ids_string = implode(',', $participant_ids);
				
				if(empty($participant_ids_string))
					$participant_ids_string = '-1';
				
				return sprintf("t.id IN (SELECT r.ticket_id FROM requester r WHERE r.address_id IN (%s))",
					$participant_ids_string
				);
			
			// [TODO] Array
			case self::VIRTUAL_GROUPS_OF_WORKER:
				if(null == ($member = DAO_Worker::get($param->value)))
					return '0';
					
				$all_groups = DAO_Group::getAll();
				$roster = $member->getMemberships();
				
				if(empty($roster))
					$roster = array(0 => 0);
				
				$restricted_groups = array_diff(array_keys($all_groups), array_keys($roster));
				
				// If the worker is in every group, ignore this filter entirely
				if(empty($restricted_groups))
					return '1';
				
				// [TODO] If the worker is in most of the groups, possibly try a NOT IN instead
				
				return sprintf("t.group_id IN (%s)", implode(',', array_keys($roster)));
			
			case self::VIRTUAL_STATUS:
				$values = $param->value;
				if(!is_array($values))
					$values = array($values);
				
				$statuses = [];
				
				switch($param->operator) {
					default:
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_IN_OR_NULL:
						$oper = '';
						break;
					case DevblocksSearchCriteria::OPER_NIN:
					case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
						$oper = 'NOT ';
						break;
				}
				
				foreach($values as $value) {
					switch($value) {
						case 'open':
							$statuses[] = Model_Ticket::STATUS_OPEN;
							break;
						case 'waiting':
							$statuses[] = Model_Ticket::STATUS_WAITING;
							break;
						case 'closed':
							$statuses[] = Model_Ticket::STATUS_CLOSED;
							break;
						case 'deleted':
							$statuses[] = Model_Ticket::STATUS_DELETED;
							break;
					}
				}
				
				if(empty($statuses))
					break;
				
				return sprintf('t.status_id %sIN (%s) ', $oper, implode(', ', $statuses));
				
			case self::REQUESTER_ID:
				$where_sql = $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				
				return sprintf("%s IN (SELECT DISTINCT r.ticket_id FROM requester r WHERE %s)",
					self::getPrimaryKey(),
					$where_sql
				);
			
			case self::REQUESTER_ADDRESS:
				$where_sql = $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				
				return sprintf("%s IN (SELECT DISTINCT r.ticket_id FROM requester r INNER JOIN address ra ON (ra.id=r.address_id) WHERE %s)",
					self::getPrimaryKey(),
					$where_sql
				);
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_TICKET, self::getPrimaryKey());
				
			case self::VIRTUAL_WATCHERS_COUNT:
				return self::_getWhereSQLFromWatchersCountField($param, CerberusContexts::CONTEXT_TICKET, self::getPrimaryKey());
			
			case self::VIRTUAL_WORKER_COMMENTED:
				$ids = is_array($param->value) ? $param->value : array($param->value);
				$ids = DevblocksPlatform::sanitizeArray($ids, 'integer');
				
				return sprintf("%s IN (SELECT DISTINCT context_id FROM comment WHERE context = %s AND context_id = %s AND owner_context = %s AND owner_context_id IN (%s))",
					self::getPrimaryKey(),
					Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET),
					self::getPrimaryKey(),
					Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_WORKER),
					implode(',', $ids)
				);
				
			case self::VIRTUAL_WORKER_REPLIED:
				$ids = is_array($param->value) ? $param->value : array($param->value);
				$ids = DevblocksPlatform::sanitizeArray($ids, 'integer');
				
				return sprintf("%s IN (SELECT DISTINCT ticket_id FROM message WHERE ticket_id = %s AND worker_id IN (%s))",
					self::getPrimaryKey(),
					self::getPrimaryKey(),
					implode(',', $ids)
				);
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
		
		return '0';
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'bucket':
				$key = 'bucket.id';
				break;
				
			case 'group':
				$key = 'group.id';
				break;
				
			case 'org':
				$key = 'org.id';
				break;
				
			case 'owner':
				$key = 'owner.id';
				break;
				
			case 'status':
			case 'status.id':
				$search_key = SearchFields_Ticket::TICKET_STATUS_ID;
				$search_field = $search_fields[$search_key];
				
				return [
					'label' => DevblocksPlatform::translateCapitalized('common.status'),
					'key_query' => $key,
					'key_select' => $search_key,
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'sql_select' => sprintf("%s.%s",
						Cerb_ORMHelper::escape($search_field->db_table),
						Cerb_ORMHelper::escape($search_field->db_column)
					),
					'get_value_as_filter_callback' => function($value) {
						$statuses = [0 => 'o', 1 => 'w', 2 => 'c', 3 => 'd'];
						return @$statuses[$value];
					}
				];
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Ticket::TICKET_ID:
				$models = DAO_Ticket::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_TICKET);
				return array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				
			case SearchFields_Ticket::TICKET_BUCKET_ID:
				$records = DAO_Bucket::getIds($values);
				return array_column($records, 'name', 'id');
				
			case SearchFields_Ticket::TICKET_GROUP_ID:
				$records = DAO_Group::getIds($values);
				return array_column($records, 'name', 'id');
				
			case SearchFields_Ticket::TICKET_ORG_ID:
				$records = DAO_ContactOrg::getIds($values);
				$label_map = array_column($records, 'name', 'id');
				if(in_array(0, $values))
					$label_map[0] = sprintf('(%s)', DevblocksPlatform::translate('common.none'));
				return $label_map;
				
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$label_map = DAO_Worker::getNames(false);
				$label_map[0] = DevblocksPlatform::translate('common.nobody');
				return array_intersect_key($label_map, array_flip($values));
				
			case SearchFields_Ticket::TICKET_STATUS_ID:
				return [
					0 => DevblocksPlatform::translateCapitalized('status.open'),
					1 => DevblocksPlatform::translateCapitalized('status.waiting.abbr'),
					2 => DevblocksPlatform::translateCapitalized('status.closed'),
					3 => DevblocksPlatform::translateCapitalized('status.deleted'),
				];
				
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				return [
					'' => DevblocksPlatform::translateLower('common.unknown'),
					'N' => DevblocksPlatform::translateLower('common.notspam'),
					'S' => DevblocksPlatform::translateLower('common.spam'),
				];
		}
		
		return parent::getLabelsForKeyValues($key, $values);
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			SearchFields_Ticket::TICKET_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_ID, 't', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_MASK => new DevblocksSearchField(SearchFields_Ticket::TICKET_MASK, 't', 'mask', $translate->_('ticket.mask'), Model_CustomField::TYPE_SINGLE_LINE, true),
			SearchFields_Ticket::TICKET_SUBJECT => new DevblocksSearchField(SearchFields_Ticket::TICKET_SUBJECT, 't', 'subject', $translate->_('ticket.subject'), Model_CustomField::TYPE_SINGLE_LINE, true),
			
			SearchFields_Ticket::TICKET_FIRST_MESSAGE_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_FIRST_MESSAGE_ID, 't', 'first_message_id', null, null, true),
			SearchFields_Ticket::TICKET_FIRST_OUTGOING_MESSAGE_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_FIRST_OUTGOING_MESSAGE_ID, 't', 'first_outgoing_message_id', null, null, true),
			SearchFields_Ticket::TICKET_LAST_MESSAGE_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_LAST_MESSAGE_ID, 't', 'last_message_id', null, null, true),
			
			SearchFields_Ticket::TICKET_FIRST_WROTE_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_FIRST_WROTE_ID, 't', 'first_wrote_address_id', $translate->_('ticket.first_wrote'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_LAST_WROTE_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_LAST_WROTE_ID, 't', 'last_wrote_address_id', $translate->_('ticket.last_wrote'), Model_CustomField::TYPE_NUMBER, true),
			
			SearchFields_Ticket::REQUESTER_ADDRESS => new DevblocksSearchField(SearchFields_Ticket::REQUESTER_ADDRESS, 'ra', 'email',$translate->_('common.participant'), Model_CustomField::TYPE_SINGLE_LINE, false),
			
			SearchFields_Ticket::TICKET_ORG_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_ORG_ID, 't','org_id',$translate->_('common.organization'), null, true),
			SearchFields_Ticket::TICKET_OWNER_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_OWNER_ID,'t','owner_id',$translate->_('common.owner'), Model_CustomField::TYPE_WORKER, true),
			SearchFields_Ticket::TICKET_IMPORTANCE => new DevblocksSearchField(SearchFields_Ticket::TICKET_IMPORTANCE,'t','importance',$translate->_('common.importance'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_GROUP_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_GROUP_ID,'t','group_id',$translate->_('common.group'), null, true),
			SearchFields_Ticket::TICKET_BUCKET_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_BUCKET_ID, 't', 'bucket_id',$translate->_('common.bucket'), null, true),
			SearchFields_Ticket::TICKET_CREATED_DATE => new DevblocksSearchField(SearchFields_Ticket::TICKET_CREATED_DATE, 't', 'created_date',$translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Ticket::TICKET_UPDATED_DATE => new DevblocksSearchField(SearchFields_Ticket::TICKET_UPDATED_DATE, 't', 'updated_date',$translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Ticket::TICKET_CLOSED_AT => new DevblocksSearchField(SearchFields_Ticket::TICKET_CLOSED_AT, 't', 'closed_at',$translate->_('ticket.closed_at'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Ticket::TICKET_STATUS_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_STATUS_ID, 't', 'status_id',$translate->_('common.status'), Model_CustomField::TYPE_NUMBER, true),

			SearchFields_Ticket::TICKET_NUM_MESSAGES => new DevblocksSearchField(SearchFields_Ticket::TICKET_NUM_MESSAGES, 't', 'num_messages',$translate->_('ticket.num_messages'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_NUM_MESSAGES_IN => new DevblocksSearchField(SearchFields_Ticket::TICKET_NUM_MESSAGES_IN, 't', 'num_messages_in',$translate->_('ticket.num_messages_in'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_NUM_MESSAGES_OUT => new DevblocksSearchField(SearchFields_Ticket::TICKET_NUM_MESSAGES_OUT, 't', 'num_messages_out',$translate->_('ticket.num_messages_out'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST => new DevblocksSearchField(SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST, 't', 'elapsed_response_first',$translate->_('ticket.elapsed_response_first'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST => new DevblocksSearchField(SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST, 't', 'elapsed_resolution_first',$translate->_('ticket.elapsed_resolution_first'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_SPAM_TRAINING => new DevblocksSearchField(SearchFields_Ticket::TICKET_SPAM_TRAINING, 't', 'spam_training',$translate->_('ticket.spam_training'), null, true),
			SearchFields_Ticket::TICKET_SPAM_SCORE => new DevblocksSearchField(SearchFields_Ticket::TICKET_SPAM_SCORE, 't', 'spam_score',$translate->_('ticket.spam_score'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_INTERESTING_WORDS => new DevblocksSearchField(SearchFields_Ticket::TICKET_INTERESTING_WORDS, 't', 'interesting_words',$translate->_('ticket.interesting_words'), null, true),
			SearchFields_Ticket::TICKET_REOPEN_AT => new DevblocksSearchField(SearchFields_Ticket::TICKET_REOPEN_AT, 't', 'reopen_at',$translate->_('common.reopen_at'), Model_CustomField::TYPE_DATE, true),
			
			SearchFields_Ticket::BUCKET_RESPONSIBILITY => new DevblocksSearchField(SearchFields_Ticket::BUCKET_RESPONSIBILITY, 'wtb', 'responsibility_level', mb_convert_case($translate->_('common.responsibility'), MB_CASE_TITLE), null, true),
			
			SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchField(SearchFields_Ticket::REQUESTER_ID, 'r', 'address_id', $translate->_('common.participant'), null, false),
			
			SearchFields_Ticket::SENDER_ADDRESS => new DevblocksSearchField(SearchFields_Ticket::SENDER_ADDRESS, 'a1', 'email', null, null, true),

			SearchFields_Ticket::VIRTUAL_BUCKET_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_BUCKET_SEARCH, '*', 'bucket_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_COMMENTS_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_COMMENTS_SEARCH, '*', 'comments_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_COMMENTS_FIRST_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_COMMENTS_FIRST_SEARCH, '*', 'comments_first_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_COMMENTS_LAST_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_COMMENTS_LAST_SEARCH, '*', 'comments_last_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			SearchFields_Ticket::VIRTUAL_GROUP_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_GROUP_SEARCH, '*', 'group_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER, '*', 'groups_of_worker', $translate->_('ticket.groups_of_worker'), null, false),
			SearchFields_Ticket::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_SEARCH, '*', 'message_first_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_OUTGOING_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_OUTGOING_SEARCH, '*', 'message_first_outgoing_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_MESSAGE_LAST_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_MESSAGE_LAST_SEARCH, '*', 'message_last_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_MESSAGES_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_MESSAGES_SEARCH, '*', 'messages_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_ORG_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_ORG_SEARCH, '*', 'org_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_OWNER_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_OWNER_SEARCH, '*', 'owner_search', null, null, false),
			SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID, '*', 'participant_id', null, null, false), // participant ID
			SearchFields_Ticket::VIRTUAL_PARTICIPANT_SEARCH => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_PARTICIPANT_SEARCH, '*', 'participant_search', $translate->_('common.participants'), null, false),
			SearchFields_Ticket::VIRTUAL_STATUS => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_STATUS, '*', 'status', $translate->_('common.status'), null, false),
			SearchFields_Ticket::VIRTUAL_WATCHERS => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
			SearchFields_Ticket::VIRTUAL_WATCHERS_COUNT => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_WATCHERS_COUNT, '*', 'workers_count', null, null, false),
			SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED, '*', 'worker_commented', null, null, false),
			SearchFields_Ticket::VIRTUAL_WORKER_REPLIED => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_WORKER_REPLIED, '*', 'worker_replied', null, null, false),
			
			SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
			SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT => new DevblocksSearchField(self::FULLTEXT_MESSAGE_CONTENT, 'ftmc', 'content', $translate->_('message.content'), 'FT', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		$columns[self::FULLTEXT_MESSAGE_CONTENT]->ft_schema = Search_MessageContent::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class Model_Ticket {
	const STATUS_OPEN = 0;
	const STATUS_WAITING = 1;
	const STATUS_CLOSED = 2;
	const STATUS_DELETED = 3;
	
	public $id;
	public $mask;
	public $subject;
	public $status_id = 0;
	public $group_id;
	public $bucket_id;
	public $org_id;
	public $owner_id = 0;
	public $importance = 0;
	public $first_message_id;
	public $first_outgoing_message_id;
	public $last_message_id;
	public $first_wrote_address_id;
	public $last_wrote_address_id;
	public $created_date;
	public $updated_date;
	public $closed_at;
	public $reopen_at;
	public $spam_score;
	public $spam_training;
	public $interesting_words;
	public $num_messages;
	public $num_messages_in;
	public $num_messages_out;
	public $elapsed_response_first;
	public $elapsed_resolution_first;
	
	private $_org = null;
	private $_owner = null;
	private $_group = null;
	private $_bucket = null;

	function getStatusText() {
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($this->status_id) {
			case Model_Ticket::STATUS_WAITING:
				return $translate->_('status.waiting.abbr');
				break;
			case Model_Ticket::STATUS_CLOSED:
				return $translate->_('status.closed');
				break;
			case Model_Ticket::STATUS_DELETED:
				return $translate->_('status.deleted');
				break;
			default:
			case Model_Ticket::STATUS_OPEN:
				return $translate->_('status.open');
				break;
		}
	}
	
	function getMessages() {
		return DAO_Message::getMessagesByTicket($this->id);
	}
	
	function getTimeline($is_ascending=true, $target_context=null, $target_context_id=null, &$start_at=0) {
		$timeline = $this->getMessages();
		
		if(false != ($comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $this->id)))
			$timeline = array_merge($timeline, $comments);
		
		$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d",
			Cerb_ORMHelper::escape(DAO_MailQueue::TICKET_ID),
			$this->id
		));
		
		if($drafts) {
			$timeline = array_merge($timeline, $drafts);
		}
		
		usort($timeline, function($a, $b) use ($is_ascending) {
			if($a instanceof Model_Message) {
				$a_time = intval($a->created_date);
			} else if($a instanceof Model_Comment) {
				$a_time = intval($a->created);
			} else if($a instanceof Model_MailQueue) {
				$a_time = intval($a->updated);
			} else {
				$a_time = 0;
			}
			
			if($b instanceof Model_Message) {
				$b_time = intval($b->created_date);
			} else if($b instanceof Model_Comment) {
				$b_time = intval($b->created);
			} else if($b instanceof Model_MailQueue) {
				$b_time = intval($b->updated);
			} else {
				$b_time = 0;
			}
			
			if($a_time > $b_time) {
				return ($is_ascending) ? 1 : -1;
			} else if ($a_time < $b_time) {
				return ($is_ascending) ? -1 : 1;
			} else {
				return 0;
			}
		});
		
		if($target_context && $target_context_id) {
			$target_classes = [
				CerberusContexts::CONTEXT_COMMENT => 'Model_Comment',
				CerberusContexts::CONTEXT_DRAFT => 'Model_MailQueue',
				CerberusContexts::CONTEXT_MESSAGE => 'Model_Message',
			];
			
			if(false != (@$target_class = $target_classes[$target_context])) {
				foreach($timeline as $object_idx => $object) {
					if($object instanceof $target_class && $object->id == $target_context_id) {
						$start_at = $object_idx;
						break;
					}
				}
			}
		}
		
		return $timeline;
	}
	
	function getFirstMessage() {
		return DAO_Message::get($this->first_message_id);
	}
	
	function getFirstOutgoingMessage() {
		return DAO_Message::get($this->first_outgoing_message_id);
	}
	
	function getLastMessage() {
		return DAO_Message::get($this->last_message_id);
	}

	function getRequesters() {
		return DAO_Ticket::getRequestersByTicket($this->id);
	}
	
	function getParticipants() {
		$results = DAO_Ticket::getParticipants($this->id);
		$participants = [];
		
		foreach($results as $row) {
			if(!isset($participants[$row['context']]))
				$participants[$row['context']] = [];

			$participants[$row['context']][$row['context_id']] = $row['hits'];
		}
		
		return $participants;
	}
	
	// Lazy load
	
	/**
	 * @return Model_ContactOrg
	 */
	function getOrg() {
		if(empty($this->org_id))
			return null;
		
		if(is_null($this->_org) || $this->_org->id != $this->org_id) {
			$this->_org = DAO_ContactOrg::get($this->org_id);
		}
		
		return $this->_org;
	}
	
	/**
	 * @return Model_Worker
	 */
	function getOwner() {
		if(empty($this->owner_id))
			return null;
		
		if(is_null($this->_owner) || $this->_owner->id != $this->owner_id) {
			$this->_owner = DAO_Worker::get($this->owner_id);
		}
		
		return $this->_owner;
	}
	
	/**
	 * @return Model_Group
	 */
	function getGroup() {
		if(empty($this->group_id))
			return null;
		
		if(is_null($this->_group) || $this->_group->id != $this->group_id) {
			$this->_group = DAO_Group::get($this->group_id);
		}
		
		return $this->_group;
	}
	
	/**
	 * @return Model_Bucket
	 */
	function getBucket() {
		if(empty($this->bucket_id))
			return null;
		
		if(is_null($this->_bucket) || $this->_bucket->id != $this->bucket_id) {
			$this->_bucket = DAO_Bucket::get($this->bucket_id);
		}
		
		return $this->_bucket;
	}
};

class View_Ticket extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'tickets_workspace';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Tickets';
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Ticket::TICKET_IMPORTANCE,
			SearchFields_Ticket::TICKET_LAST_WROTE_ID,
			SearchFields_Ticket::TICKET_GROUP_ID,
			SearchFields_Ticket::TICKET_BUCKET_ID,
			SearchFields_Ticket::TICKET_OWNER_ID,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT,
			SearchFields_Ticket::REQUESTER_ADDRESS,
			SearchFields_Ticket::REQUESTER_ID,
			SearchFields_Ticket::TICKET_INTERESTING_WORDS,
			SearchFields_Ticket::VIRTUAL_COMMENTS_SEARCH,
			SearchFields_Ticket::VIRTUAL_COMMENTS_FIRST_SEARCH,
			SearchFields_Ticket::VIRTUAL_COMMENTS_LAST_SEARCH,
			SearchFields_Ticket::VIRTUAL_CONTEXT_LINK,
			SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,
			SearchFields_Ticket::VIRTUAL_HAS_FIELDSET,
			SearchFields_Ticket::VIRTUAL_BUCKET_SEARCH,
			SearchFields_Ticket::VIRTUAL_GROUP_SEARCH,
			SearchFields_Ticket::VIRTUAL_OWNER_SEARCH,
			SearchFields_Ticket::VIRTUAL_ORG_SEARCH,
			SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_SEARCH,
			SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_OUTGOING_SEARCH,
			SearchFields_Ticket::VIRTUAL_MESSAGE_LAST_SEARCH,
			SearchFields_Ticket::VIRTUAL_MESSAGES_SEARCH,
			SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID,
			SearchFields_Ticket::TICKET_STATUS_ID,
			SearchFields_Ticket::VIRTUAL_WATCHERS,
			SearchFields_Ticket::VIRTUAL_WATCHERS_COUNT,
			SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED,
			SearchFields_Ticket::VIRTUAL_WORKER_REPLIED,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Ticket::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Ticket');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Ticket', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Ticket', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Ticket::TICKET_FIRST_WROTE_ID:
				case SearchFields_Ticket::TICKET_LAST_WROTE_ID:
				case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				case SearchFields_Ticket::TICKET_SUBJECT:
				case SearchFields_Ticket::TICKET_GROUP_ID:
				case SearchFields_Ticket::TICKET_BUCKET_ID:
				case SearchFields_Ticket::TICKET_ORG_ID:
				case SearchFields_Ticket::TICKET_OWNER_ID:
					$pass = true;
					break;

				// Virtuals
				case SearchFields_Ticket::VIRTUAL_STATUS:
					$pass = true;
					break;
					
				case SearchFields_Ticket::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Ticket::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Ticket::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_'))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_TICKET;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Ticket::TICKET_SUBJECT:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_Ticket::TICKET_FIRST_WROTE_ID:
			case SearchFields_Ticket::TICKET_LAST_WROTE_ID:
				$label_map = function($ids) {
					$rows = DAO_Address::getIds($ids);
					return array_column(DevblocksPlatform::objectsToArrays($rows), 'email', 'id');
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'value[]');
				break;
				
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_Ticket::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'worker_id[]');
				break;
				
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$label_map = function(array $values) use ($column) {
					return SearchFields_Ticket::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'options[]');
				break;
				
			case SearchFields_Ticket::TICKET_ORG_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_Ticket::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in', 'context_id[]');
				break;
				
			case SearchFields_Ticket::TICKET_GROUP_ID:
				$counts = $this->_getSubtotalCountForBucketsByGroup();
				break;
				
			case SearchFields_Ticket::TICKET_BUCKET_ID:
				$counts = $this->_getSubtotalCountForBuckets();
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				$counts = $this->_getSubtotalCountForStatus();
				break;
				
			case SearchFields_Ticket::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_Ticket::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
			
			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	private function _getSubtotalDataForBuckets() {
		$db = DevblocksPlatform::services()->database();
		
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		// Still fan out buckets if we're only filtering to one of them
		if($this->hasParam(SearchFields_Ticket::TICKET_BUCKET_ID, $params, false)) {
			$results = $this->findParam(SearchFields_Ticket::TICKET_BUCKET_ID, $params, false);
			
			if(is_array($results))
			foreach(array_keys($results) as $k) {
				if($results[$k]->operator == DevblocksSearchCriteria::OPER_IN && 1 == count($results[$k]->value))
					unset($params[$k]);
			}
		}
		
		if(!method_exists('DAO_Ticket','getSearchQueryComponents'))
			return [];
		
		$query_parts = call_user_func_array(
			array('DAO_Ticket','getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		$sql = sprintf(
				"SELECT t.group_id, t.bucket_id, count(*) as hits "
			).
			$join_sql.
			$where_sql.
			"GROUP BY group_id, bucket_id ".
			"ORDER BY hits DESC "
		;
		
		try {
			return $db->GetArrayReader($sql, 15000);
			
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			return false;
		}
	}
	
	private function _getSubtotalCountForBuckets() {
		$counts = [];
		
		if(false === ($results = $this->_getSubtotalDataForBuckets()))
			return false;
		
		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		
		if(is_array($results))
		foreach($results as $result) {
			$group_id = $result['group_id'];
			$bucket_id = $result['bucket_id'];
			$hits = $result['hits'];

			if(!$group_id || !array_key_exists($group_id, $groups))
				continue;
			
			if(!$bucket_id || !array_key_exists($bucket_id, $buckets))
				continue;
			
			if(!isset($counts[$bucket_id])) {
				$label = sprintf("%s (%s)", $buckets[$bucket_id]->name, $groups[$group_id]->name);
				
				$counts[$bucket_id] = array(
					'hits' => $hits,
					'label' => $label,
					'filter' =>
						array(
							'field' => SearchFields_Ticket::TICKET_BUCKET_ID,
							'oper' => DevblocksSearchCriteria::OPER_IN,
							'values' => array('options[]' => $result['bucket_id']),
						),
					'children' => []
				);
			}
		}
		
		return $counts;
	}
	
	private function _getSubtotalCountForBucketsByGroup() {
		$counts = [];
		$results = $this->_getSubtotalDataForBuckets();
		
		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		
		if(is_array($results))
		foreach($results as $result) {
			$group_id = $result['group_id'];
			$bucket_id = $result['bucket_id'];
			$hits = $result['hits'];
			
			if(!$group_id || !array_key_exists($group_id, $groups))
				continue;

			if(!isset($counts[$group_id])) {
				$label = $groups[$group_id]->name;
				
				$counts[$group_id] = array(
					'hits' => 0,
					'label' => $label,
					'filter' =>
						array(
							'field' => SearchFields_Ticket::TICKET_GROUP_ID,
							'oper' => DevblocksSearchCriteria::OPER_IN,
							'values' => array('options[]' => $result['group_id']),
						),
					'children' => []
				);
			}
				
			@$label = $buckets[$bucket_id]->name;
				
			$child = array(
				'hits' => $hits,
				'label' => $label,
				'filter' =>
					array(
						'field' => SearchFields_Ticket::TICKET_BUCKET_ID,
						'oper' => DevblocksSearchCriteria::OPER_IN,
						'values' => array('options[]' => $result['bucket_id']),
					),
			);
			
			$counts[$group_id]['hits'] += $hits;
			$counts[$group_id]['children'][$bucket_id] = $child;
		}
		
		// Sort groups alphabetically
		uasort($counts, array($this, '_sortByLabel'));
		
		// Sort buckets by group preference
		foreach(array_keys($counts) as $group_id) {
			uksort($counts[$group_id]['children'], array($this, '_sortByBucketOrder'));
		}
		
		return $counts;
	}
	
	protected function _getSubtotalDataForStatus($dao_class, $field_key) {
		$db = DevblocksPlatform::services()->database();
		
		$columns = $this->view_columns;
		$params = $this->getParams();

		// We want counts for all statuses even though we're filtering
		$results = $this->findParam(SearchFields_Ticket::VIRTUAL_STATUS, $params, false);
		$param = array_shift($results);
		
		if(
			$param instanceof DevblocksSearchCriteria
			&& is_array($param->value)
			&& count($param->value) < 2
			)
			$this->removeParamByField($param->field, $params);
		
		if(!method_exists($dao_class,'getSearchQueryComponents'))
			return [];
		
		$query_parts = call_user_func_array(
			array($dao_class,'getSearchQueryComponents'),
			array(
				$columns,
				$params,
				$this->renderSortBy,
				$this->renderSortAsc
			)
		);
		
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		$sql = "SELECT COUNT(t.id) AS hits, t.status_id ".
			$join_sql.
			$where_sql.
			' GROUP BY t.status_id'
		;
		
		try {
			return $db->GetArrayReader($sql, 15000);
			
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			return false;
		}
	}
	
	protected function _getSubtotalCountForStatus() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = [];
		
		if(false === ($results = $this->_getSubtotalDataForStatus('DAO_Ticket', SearchFields_Ticket::VIRTUAL_STATUS)))
			return false;

		$oper = DevblocksSearchCriteria::OPER_IN;
		$values = [];
		
		if(is_array($results))
		foreach($results as $result) {
			if(empty($result['hits']))
				continue;
			
			switch($result['status_id']) {
				case Model_Ticket::STATUS_OPEN:
					$label = $translate->_('status.open');
					$values = array('options[]' => 'open');
					break;
				case Model_Ticket::STATUS_WAITING:
					$label = $translate->_('status.waiting');
					$values = array('options[]' => 'waiting');
					break;
				case Model_Ticket::STATUS_CLOSED:
					$label = $translate->_('status.closed');
					$values = array('options[]' => 'closed');
					break;
				case Model_Ticket::STATUS_DELETED:
					$label = $translate->_('status.deleted');
					$values = array('options[]' => 'deleted');
					break;
				default:
					$label = '';
					break;
			}
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $result['hits'],
					'label' => $label,
					'filter' =>
						array(
							'field' => SearchFields_Ticket::VIRTUAL_STATUS,
							'oper' => $oper,
							'values' => $values,
						),
					'children' => []
				);
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Ticket::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT),
				),
			'bucket' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'group' => 1501,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_BUCKET_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_BUCKET, 'q' => ''],
					]
				),
			'bucket.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_BUCKET,
					],
					'options' => array('param_key' => SearchFields_Ticket::TICKET_BUCKET_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BUCKET, 'q' => ''],
					]
				),
			'comments' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => [],
					'examples' => array(
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_COMMENT],
					)
				),
			'comments.first' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => [],
					'examples' => array(
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_COMMENT],
					)
				),
			'comments.last' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => [],
					'examples' => array(
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_COMMENT],
					)
				),
			'closed' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_CLOSED_AT),
				),
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'score' => 1400,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_CREATED_DATE),
				),
			'group' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'score' => 1502,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_GROUP_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_GROUP, 'q' => ''],
					]
				),
			'group.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_GROUP,
					],
					'options' => array('param_key' => SearchFields_Ticket::TICKET_GROUP_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_GROUP, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_TICKET],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_TICKET,
					],
					'options' => array('param_key' => SearchFields_Ticket::TICKET_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_TICKET, 'q' => ''],
					]
				),
			'importance' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_IMPORTANCE),
				),
			'inGroupsOf' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER),
				),
			'mask' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'score' => 1500,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_MASK, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'examples' => array(
						'ABC',
						'("XYZ-12345-678")',
					),
				),
			'messages' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => [],
					'examples' => array(
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_MESSAGE],
					)
				),
			'messages.count' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_NUM_MESSAGES),
				),
			'messages.count.in' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_NUM_MESSAGES_IN),
				),
			'messages.count.out' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_NUM_MESSAGES_OUT),
				),
			'messages.first' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_OUTGOING_SEARCH],
					'examples' => array(
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_MESSAGE],
					)
				),
			'messages.firstOutgoing' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => [],
					'examples' => array(
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_MESSAGE],
					)
				),
			'messages.last' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => [],
					'examples' => array(
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_MESSAGE],
					)
				),
			'org' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'score' => 1500,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_ORG_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ORG, 'q' => ''],
					]
				),
			'org.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'type_options' => [
						'context' => CerberusContexts::CONTEXT_ORG,
					],
					'options' => array('param_key' => SearchFields_Ticket::TICKET_ORG_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ORG, 'q' => ''],
					]
				),
			'owner' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'score' => 1500,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_OWNER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'owner.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_OWNER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'participant' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::REQUESTER_ADDRESS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					],
				),
			'participant.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ADDRESS, 'q' => ''],
					]
				),
			'reopen' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_REOPEN_AT),
				),
			'resolution.first' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER_SECONDS,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST),
				),
			'response.first' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER_SECONDS,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST),
				),
			'responsibility' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::BUCKET_RESPONSIBILITY),
				),
			'spam.score' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_SPAM_SCORE),
				),
			'spam.training' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_SPAM_TRAINING),
					'examples' => array(
						'"not spam"',
						'spam',
						'untrained',
					)
				),
			'status.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_STATUS_ID),
				),
			'status' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'score' => 1505,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_STATUS),
					'examples' => array(
						'open',
						'waiting',
						'closed',
						'deleted',
						'[o,w]',
						'![d]',
					),
				),
			'subject' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_SUBJECT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'score' => 1400,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_UPDATED_DATE),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
			'watchers.count' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_WATCHERS_COUNT),
				),
			'worker.commented' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED),
				),
			'worker.replied' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_WORKER_REPLIED),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Ticket::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_TICKET, $fields, null);
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_ORG, $fields, 'org');
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_MessageContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['text']['examples'] = $ft_examples;
		}
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'bucket':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_BUCKET_SEARCH);
				
			case 'comments':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_COMMENTS_SEARCH);
				
			case 'comments.first':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_COMMENTS_FIRST_SEARCH);
				
			case 'comments.last':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_COMMENTS_LAST_SEARCH);
				
			case 'group':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_GROUP_SEARCH);
				
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_HAS_FIELDSET);
				
			case 'inGroupsOf':
			case 'inGroupsOfWorker':
				$field_key = SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER;
				$oper = null;
				$v = null;
				
				CerbQuickSearchLexer::getOperStringFromTokens($tokens, $oper, $v);
				
				$worker_id = 0;
				
				switch(DevblocksPlatform::strLower($v)) {
					case 'current':
						$worker_id = '{{current_worker_id}}';
						break;
						
					case 'me':
					case 'mine':
					case 'my':
						if(false != ($active_worker = CerberusApplication::getActiveWorker()))
							$worker_id = $active_worker->id;
						break;
						
					default:
						if(is_numeric($v)) {
							$worker_id = intval($v);
							
						} else {
							if(false != ($matches = DAO_Worker::getByString($v)) && !empty($matches))
								$worker_id = key($matches);
						}
						break;
				}
				
				if($worker_id) {
					return new DevblocksSearchCriteria(
						$field_key,
						$oper,
						$worker_id
					);
				}
				break;

			case 'messages':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_MESSAGES_SEARCH);
				
			case 'messages.first':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_SEARCH);
				
			case 'messages.firstOutgoing':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_OUTGOING_SEARCH);
				
			case 'messages.last':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_MESSAGE_LAST_SEARCH);
				
			case 'org':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_ORG_SEARCH);
				
			case 'owner':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_OWNER_SEARCH);
				
			case 'participant':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Ticket::VIRTUAL_PARTICIPANT_SEARCH);
				
			// Alias
			case 'recipient':
				$field = 'participant';
				break;
			
			case 'resolution.first':
				$tokens = CerbQuickSearchLexer::getHumanTimeTokensAsNumbers($tokens);
				
				$field_key = SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST;
				return DevblocksSearchCriteria::getNumberParamFromTokens($field_key, $tokens);
				
			case 'response.first':
				$tokens = CerbQuickSearchLexer::getHumanTimeTokensAsNumbers($tokens);
				
				$field_key = SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST;
				return DevblocksSearchCriteria::getNumberParamFromTokens($field_key, $tokens);
				
			case 'spam.training':
				$field_key = SearchFields_Ticket::TICKET_SPAM_TRAINING;
				$oper = null;
				$states = [];
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $states);
				
				$values = [];
				
				// Normalize status labels
				foreach($states as $status) {
					switch(substr(DevblocksPlatform::strLower($status), 0, 1)) {
						case 's':
							$values['S'] = true;
							break;
						case 'n':
							$values['N'] = true;
							break;
						case 'u':
							$values[''] = true;
							break;
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
			
			case 'status':
				$field_key = SearchFields_Ticket::VIRTUAL_STATUS;
				$oper = null;
				$value = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
				
				$values = [];
				
				// Normalize status labels
				foreach($value as $status) {
					switch(substr(DevblocksPlatform::strLower($status), 0, 1)) {
						case 'o':
							$values['open'] = true;
							break;
						case 'w':
							$values['waiting'] = true;
							break;
						case 'c':
							$values['closed'] = true;
							break;
						case 'd':
							$values['deleted'] = true;
							break;
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Ticket::VIRTUAL_WATCHERS, $tokens);
				
			case 'watchers.count':
				return DevblocksSearchCriteria::getNumberParamFromTokens(SearchFields_Ticket::VIRTUAL_WATCHERS_COUNT, $tokens);
				
			case 'worker.commented':
				$search_fields = SearchFields_Ticket::getFields();
				return DevblocksSearchCriteria::getWorkerParamFromTokens(SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED, $tokens, $search_fields[SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED]);
				
			case 'worker.replied':
				$search_fields = SearchFields_Ticket::getFields();
				return DevblocksSearchCriteria::getWorkerParamFromTokens(SearchFields_Ticket::VIRTUAL_WORKER_REPLIED, $tokens, $search_fields[SearchFields_Ticket::VIRTUAL_WORKER_REPLIED]);
				
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				break;
		}
		
		$search_fields = $this->getQuickSearchFields();
		return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
	}
	
	private function _sortByLabel($a, $b) {
		return strcmp($a['label'], $b['label']);
	}
	
	private function _sortByBucketOrder($a, $b) {
		$buckets = DAO_Bucket::getAll();
		
		if($buckets[$a]->is_default)
			return -1;
		
		if($buckets[$b]->is_default)
			return 1;
		
		return strcasecmp($buckets[$a]->name, $buckets[$b]->name);
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$results = $this->getData();
		$tpl->assign('results', $results);
		
		$this->_checkFulltextMarquee();
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);

		$sender_addresses = DAO_Address::getLocalAddresses();
		$tpl->assign('sender_addresses', $sender_addresses);
		
		$custom_fields =
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET) +
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS) +
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG)
			;
		$tpl->assign('custom_fields', $custom_fields);
		
		// Undo?
		$last_action = View_Ticket::getLastAction($this->id);
		$tpl->assign('last_action', $last_action);
		if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
			$tpl->assign('last_action_count', count($last_action->ticket_ids));
		}

		$tpl->assign('timestamp_now', time());
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::tickets/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
		$tpl->clearAssign('last_action');
		$tpl->clearAssign('last_action_count');
		$tpl->clearAssign('results');
		$tpl->clearAssign('group_buckets');
		$tpl->clearAssign('timestamp_now');
	}
	
	function renderCustomizeOptions() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->display('devblocks:cerberusweb.core::internal/views/options/ticket.tpl');
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_Ticket::VIRTUAL_BUCKET_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.bucket')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_COMMENTS_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.comments')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_COMMENTS_FIRST_SEARCH:
				echo sprintf("First %s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateLower('common.comment')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_COMMENTS_LAST_SEARCH:
				echo sprintf("Last %s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateLower('common.comment')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Ticket::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Ticket::VIRTUAL_GROUP_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.group')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_SEARCH:
				echo sprintf("First message matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_MESSAGE_FIRST_OUTGOING_SEARCH:
				echo sprintf("First outgoing message matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_MESSAGE_LAST_SEARCH:
				echo sprintf("Latest message matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_MESSAGES_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.messages')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_ORG_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.organization')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_OWNER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.owner')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_PARTICIPANT_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.participant')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS_COUNT:
				$this->_renderVirtualWatchersCount($param);
				break;
				
			case SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED:
				$strings_or = [];
				$workers = DAO_Worker::getAll();
				
				if(is_array($param->value)) {
					foreach($param->value as $param_value) {
						if(isset($workers[$param_value]))
						$strings_or[] = sprintf("<b>%s</b>",
							DevblocksPlatform::strEscapeHtml($workers[$param_value]->getName())
						);
					}
				} else {
					if(isset($workers[$param->value]))
					$strings_or[] = sprintf("<b>%s</b>",
						DevblocksPlatform::strEscapeHtml($workers[$param->value]->getName())
					);
				}
				
				echo sprintf("Comment by %s",
					implode(' or ', $strings_or)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_WORKER_REPLIED:
				$strings_or = [];
				$workers = DAO_Worker::getAll();
				
				if(is_array($param->value)) {
					foreach($param->value as $param_value) {
						if(isset($workers[$param_value]))
						$strings_or[] = sprintf("<b>%s</b>",
							DevblocksPlatform::strEscapeHtml($workers[$param_value]->getName())
						);
					}
				} else {
					if(isset($workers[$param->value]))
					$strings_or[] = sprintf("<b>%s</b>",
						DevblocksPlatform::strEscapeHtml($workers[$param->value]->getName())
					);
				}
				
				echo sprintf("Reply by %s",
					implode(' or ', $strings_or)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER:
				$worker_name = $param->value;
				
				if(is_numeric($worker_name)) {
					if(null == ($worker = DAO_Worker::get($worker_name)))
						break;
					
					$worker_name = $worker->getName();
				}
					
				echo sprintf("In <b>%s</b>'s groups", DevblocksPlatform::strEscapeHtml($worker_name));
				break;
				
			case SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID:
				$sep = ' or ';
				$strings = [];
				
				$ids = is_array($param->value) ? $param->value : [$param->value];
				$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
				
				$addresses = DAO_Address::getIds($ids);
				
				foreach($addresses as $address) {
					$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($address->getNameWithEmail()) . '</b>';
				}
				
				$list_of_strings = implode($sep, $strings);
				
				if(count($strings) > 2) {
					$list_of_strings = sprintf("any of <abbr style='font-weight:bold;' title='%s'>(%d people)</abbr>",
						strip_tags($list_of_strings),
						count($strings)
					);
				}
				
				echo sprintf("Participant is %s", $list_of_strings);
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				if(!is_array($param->value))
					$param->value = array($param->value);
					
				$strings = [];
				
				foreach($param->value as $value) {
					switch($value) {
						case 'open':
							$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($translate->_('status.open')) . '</b>';
							break;
						case 'waiting':
							$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($translate->_('status.waiting')) . '</b>';
							break;
						case 'closed':
							$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($translate->_('status.closed')) . '</b>';
							break;
						case 'deleted':
							$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($translate->_('status.deleted')) . '</b>';
							break;
					}
				}
				
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_IN:
						$oper = 'is';
						break;
					case DevblocksSearchCriteria::OPER_IN_OR_NULL:
						$oper = 'is blank or';
						break;
					case DevblocksSearchCriteria::OPER_NIN:
						$oper = 'is not';
						break;
					case DevblocksSearchCriteria::OPER_NIN_OR_NULL:
						$oper = 'is blank or not';
						break;
				}
				echo sprintf("Status %s %s", $oper, implode(' or ', $strings));
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;
				
			case SearchFields_Ticket::TICKET_BUCKET_ID:
			case SearchFields_Ticket::TICKET_GROUP_ID:
			case SearchFields_Ticket::TICKET_ORG_ID:
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$label_map = SearchFields_Ticket::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;

			case SearchFields_Ticket::TICKET_FIRST_WROTE_ID:
			case SearchFields_Ticket::TICKET_LAST_WROTE_ID:
				$label_map = function($ids) {
					return array_column(DevblocksPlatform::objectsToArrays(DAO_Address::getIds($ids)), 'email', 'id');
				};
				
				self::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST:
			case SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST:
				$sep = ' or ';
				$values = is_array($values) ? $values : array($values);
				
				foreach($values as &$value) {
					$value = DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strSecsToString($value, 2));
				}
				
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_BETWEEN:
						$sep = ' and ';
						echo implode($sep, $values);
						break;
						
					default:
						echo implode($sep, $values);
						break;
				}
				
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Ticket::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			case SearchFields_Ticket::BUCKET_RESPONSIBILITY:
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_IMPORTANCE:
			case SearchFields_Ticket::TICKET_NUM_MESSAGES:
			case SearchFields_Ticket::TICKET_NUM_MESSAGES_IN:
			case SearchFields_Ticket::TICKET_NUM_MESSAGES_OUT:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST:
			case SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST:
				$now = time();
				$then = intval(@strtotime($value, $now));
				$value = $then - $now;
				
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Ticket::TICKET_FIRST_WROTE_ID:
			case SearchFields_Ticket::TICKET_LAST_WROTE_ID:
				if($oper == DevblocksSearchCriteria::OPER_LIKE && is_string($value)) {
					$oper = DevblocksSearchCriteria::OPER_IN;
					$value = DAO_Address::autocomplete($value, 'ids');
				}
				
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Ticket::TICKET_CLOSED_AT:
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_REOPEN_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				@$score = DevblocksPlatform::importGPC($_POST['score'],'integer',null);
				if(!is_null($score) && is_numeric($score)) {
					$criteria = new DevblocksSearchCriteria($field,$oper,$score/100);
				}
				break;

			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
			case SearchFields_Ticket::VIRTUAL_STATUS:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
			
			case SearchFields_Ticket::TICKET_ORG_ID:
				@$context_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['context_id'],'array',[]), 'integer', array('unique'));
				$criteria = new DevblocksSearchCriteria($field,$oper,$context_ids);
				break;

			case SearchFields_Ticket::TICKET_GROUP_ID:
				@$group_ids = DevblocksPlatform::importGPC($_POST['options'],'array');

				// Groups
				if(!empty($group_ids)) {
					$this->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_GROUP_ID,$oper,$group_ids));
				} else {
					$this->removeParam(SearchFields_Ticket::TICKET_GROUP_ID);
				}
				break;
				
			case SearchFields_Ticket::TICKET_BUCKET_ID:
				@$bucket_ids = DevblocksPlatform::importGPC($_POST['options'],'array');

				// Buckets
				if(!empty($bucket_ids)) {
					$this->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_BUCKET_ID,$oper,$bucket_ids));
				} else { // clear if no buckets provided
					$this->removeParam(SearchFields_Ticket::TICKET_BUCKET_ID);
				}
				break;
				
			case SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT:
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_POST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$criteria = $this->_doSetCriteriaWorker($field, $oper);
				break;
				
			case SearchFields_Ticket::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER:
				@$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'],'string','');
				$criteria = new DevblocksSearchCriteria($field, '=', $worker_id);
				break;

			case SearchFields_Ticket::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
			case SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED:
			case SearchFields_Ticket::VIRTUAL_WORKER_REPLIED:
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field, $oper, $worker_ids);
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

	static public function setLastAction($view_id, Model_TicketViewLastAction $last_action=null) {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$view_last_actions = $visit->get(CerberusVisit::KEY_VIEW_LAST_ACTION,[]);
		
		if(!is_null($last_action) && !empty($last_action->ticket_ids)) {
			$view_last_actions[$view_id] = $last_action;
		} else {
			if(isset($view_last_actions[$view_id])) {
				unset($view_last_actions[$view_id]);
			}
		}
		
		$visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION,$view_last_actions);
	}

	/**
	 * @param string $view_id
	 * @return Model_TicketViewLastAction
	 */
	static public function getLastAction($view_id) {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$view_last_actions = $visit->get(CerberusVisit::KEY_VIEW_LAST_ACTION,[]);
		return (isset($view_last_actions[$view_id]) ? $view_last_actions[$view_id] : null);
	}

	static public function clearLastActions() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION,[]);
	}
};

class Context_Ticket extends Extension_DevblocksContext implements IDevblocksContextPeek, IDevblocksContextProfile, IDevblocksContextImport, IDevblocksContextMerge, IDevblocksContextAutocomplete, IDevblocksContextBroadcast {
	const ID = 'cerberusweb.contexts.ticket';
	const URI = 'ticket';
	
	static function isReadableByActor($models, $actor) {
		$group_rosters = DAO_Group::getRosters();
		
		// Only admins and group members can see, unless public
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, CerberusContexts::CONTEXT_TICKET)))
			return CerberusContexts::denyEverything($models);
		
		$results = array_fill_keys(array_keys($dicts), false);
			
		switch($actor->_context) {
			case CerberusContexts::CONTEXT_GROUP:
				foreach($dicts as $context_id => $dict) {
					// Anybody can read public group messages
					if(!$dict->group_is_private) {
						$results[$context_id] = true;
						continue;
					}
					
					// A group can edit its own messages
					if($dict->group_id == $actor->id) {
						$results[$context_id] = true;
						continue;
					}
				}
				break;
			
			// A worker can edit if they're a manager of the group
			case CerberusContexts::CONTEXT_WORKER:
				foreach($dicts as $context_id => $dict) {
					// Anybody can read public group messages
					if(!$dict->group_is_private) {
						$results[$context_id] = true;
						continue;
					}
					
					// A group member can read messages
					if(array_key_exists($dict->group_id, $group_rosters)
						&& array_key_exists($actor->id, $group_rosters[$dict->group_id])) {
						$results[$context_id] = true;
					}
				}
				break;
		}
		
		if(is_array($models)) {
			return $results;
		} else {
			return array_shift($results);
		}
	}
	
	static function isWriteableByActor($models, $actor) {
		$group_rosters = DAO_Group::getRosters();
		
		// Only admins and group members can edit
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		if(false == ($dicts = CerberusContexts::polymorphModelsToDictionaries($models, CerberusContexts::CONTEXT_TICKET)))
			return CerberusContexts::denyEverything($models);
		
		$results = array_fill_keys(array_keys($dicts), false);
		
		switch($actor->_context) {
			// A group can manage itself
			case CerberusContexts::CONTEXT_GROUP:
				foreach($dicts as $context_id => $dict) {
					if($dict->group_id == $actor->id) {
						$results[$context_id] = true;
					}
				}
				break;
			
			case CerberusContexts::CONTEXT_WORKER:
				foreach($dicts as $context_id => $dict) {
					if(array_key_exists($dict->group_id, $group_rosters)
						&& array_key_exists($actor->id, $group_rosters[$dict->group_id])) {
						$results[$context_id] = true;
					}
				}
				break;
		}
		
		if(is_array($models)) {
			return $results;
		} else {
			return array_shift($results);
		}
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Ticket::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=ticket&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		
		/* @var $model Model_Ticket */
		if(is_null($model))
			$model = new Model_Ticket();
		
		$properties = [];
		
		$properties['label'] = [
			'label' => mb_ucfirst($translate->_('message.header.subject')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		];
		
		$properties['status'] = [
			'label' => mb_ucfirst($translate->_('common.status')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->getStatusText(),
		];
		
		$properties['mask'] = [
			'label' => mb_ucfirst($translate->_('ticket.mask')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->mask,
		];
		
		$properties['reopen'] = [
			'label' => mb_ucfirst($translate->_('common.reopen_at')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->reopen_at,
		];
		
		$properties['owner'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
		);
		
		$properties['group_id'] = array(
			'label' => mb_ucfirst($translate->_('common.group')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->group_id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_GROUP,
			),
		);
		
		$properties['bucket_id'] = array(
			'label' => mb_ucfirst($translate->_('common.bucket')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->bucket_id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_BUCKET,
			),
		);

		$properties['org'] = array(
			'label' => mb_ucfirst($translate->_('common.organization')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->org_id,
			'params' => array(
				'context' => CerberusContexts::CONTEXT_ORG,
			),
		);
		
		$properties['importance'] = [
			'label' => mb_ucfirst($translate->_('common.importance')),
			'type' => 'slider',
			'value' => $model->importance,
			'params' => [
				'min' => 0,
				'mid' => 50,
				'max' => 100,
			],
		];
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_date,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_date,
		);
		
		$properties['closed'] = array(
			'label' => mb_ucfirst($translate->_('ticket.closed_at')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->closed_at,
		);
		
		$properties['elapsed_response_first'] = array(
			'label' => mb_ucfirst($translate->_('ticket.elapsed_response_first')),
			'type' => 'time_secs',
			'value' => $model->elapsed_response_first,
		);
		
		$properties['elapsed_resolution_first'] = array(
			'label' => mb_ucfirst($translate->_('ticket.elapsed_resolution_first')),
			'type' => 'time_secs',
			'value' => $model->elapsed_resolution_first,
		);
		
		$properties['spam_score'] = array(
			'label' => mb_ucfirst($translate->_('ticket.spam_score')),
			'type' => 'percent',
			'value' => $model->spam_score,
		);
		
		$properties['num_messages'] = array(
			'label' => mb_ucfirst($translate->_('ticket.num_messages')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->num_messages,
		);
		
		$properties['num_messages_in'] = array(
			'label' => mb_ucfirst($translate->_('ticket.num_messages_in')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->num_messages_in,
		);
		
		$properties['num_messages_out'] = array(
			'label' => mb_ucfirst($translate->_('ticket.num_messages_out')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->num_messages_out,
		);
		
		$properties['id'] = array(
			'label' => mb_ucfirst($translate->_('common.id')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
	
		return $properties;
	}
	
	function getMeta($context_id) {
		if(is_numeric($context_id)) {
			if(false == ($ticket = DAO_Ticket::get($context_id)))
				return false;
			
		} else {
			if(false == ($ticket = DAO_Ticket::getTicketByMask($context_id)))
				return false;
		}

		$friendly = DevblocksPlatform::strToPermalink($ticket->mask);
		
		if(!empty($friendly)) {
			$url_writer = DevblocksPlatform::services()->url();
			$url = $url_writer->writeNoProxy('c=profiles&type=ticket&mask='.$ticket->mask, true);
			
		} else {
			$url = $this->profileGetUrl($context_id);
		}
		
		return array(
			'id' => $ticket->id,
			'name' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
			'permalink' => $url,
			'owner_id' => $ticket->owner_id,
			'updated' => $ticket->updated_date,
		);
	}

	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		
		if(!is_array($labels))
			return [];
		
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
					case 'initial_message_sender__label':
						$label = 'First wrote';
						break;
						
					case 'latest_message_sender__label':
						$label = 'Last wrote';
						break;
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() {
		/*
		return array(
			'initial_message_sender__label',
			'latest_message_sender__label',
			'spam_score',
			'num_messages',
		);
		*/
		return array(
			'status',
			'group__label',
			'bucket__label',
			'owner__label',
			'importance',
			'updated',
			'org__label',
		);
	}
	
	function autocomplete($term, $query=null) {
		$results = DAO_Ticket::autocomplete($term);
		$list = [];

		// [TODO] Include more meta? (group/bucket/sender/org)
		
		if(is_array($results))
		foreach($results as $ticket_id => $ticket) {
			$entry = new stdClass();
			$entry->label = sprintf("[#%s] %s", $ticket->mask, $ticket->subject);
			$entry->value = sprintf("%d", $ticket_id);
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a mask?
		if(false != ($id = DAO_Ticket::getTicketIdByMask($alias)))
			return $id;
		
		return null;
	}
	
	function getContext($ticket, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Ticket:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		
		// Polymorph
		if(is_numeric($ticket)) {
			$ticket = DAO_Ticket::get($ticket);
		} elseif($ticket instanceof Model_Ticket) {
			// It's what we want
		} elseif(is_array($ticket)) {
			// [TODO] Cfields?
			$ticket = Cerb_ORMHelper::recastArrayToModel($ticket, 'Model_Ticket');
		} else {
			$ticket = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'closed' => $prefix.$translate->_('ticket.closed_at'),
			'created' => $prefix.$translate->_('common.created'),
			'elapsed_response_first' => $prefix.$translate->_('ticket.elapsed_response_first'),
			'elapsed_resolution_first' => $prefix.$translate->_('ticket.elapsed_resolution_first'),
			'id' => $prefix.$translate->_('common.id'),
			'importance' => $prefix.$translate->_('common.importance'),
			'mask' => $prefix.$translate->_('ticket.mask'),
			'num_messages' => $prefix.$translate->_('ticket.num_messages'),
			'num_messages_in' => $prefix.$translate->_('ticket.num_messages_in'),
			'num_messages_out' => $prefix.$translate->_('ticket.num_messages_out'),
			'reopen_date' => $prefix.$translate->_('common.reopen_at'),
			'spam_score' => $prefix.$translate->_('ticket.spam_score'),
			'spam_training' => $prefix.$translate->_('ticket.spam_training'),
			'status' => $prefix.$translate->_('common.status'),
			'subject' => $prefix.$translate->_('ticket.subject'),
			'updated' => $prefix.$translate->_('common.updated'),
			'url' => $prefix.$translate->_('common.url'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'closed' => Model_CustomField::TYPE_DATE,
			'created' => Model_CustomField::TYPE_DATE,
			'elapsed_response_first' => 'time_secs',
			'elapsed_resolution_first' => 'time_secs',
			'id' => 'id',
			'importance' => Model_CustomField::TYPE_NUMBER,
			'mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'num_messages' => Model_CustomField::TYPE_NUMBER,
			'num_messages_in' => Model_CustomField::TYPE_NUMBER,
			'num_messages_out' => Model_CustomField::TYPE_NUMBER,
			'reopen_date' => Model_CustomField::TYPE_DATE,
			'spam_score' => 'percent',
			'spam_training' => Model_CustomField::TYPE_SINGLE_LINE,
			'status' => Model_CustomField::TYPE_SINGLE_LINE,
			'subject' => Model_CustomField::TYPE_SINGLE_LINE, // [TODO] tag as _label
			'updated' => Model_CustomField::TYPE_DATE,
			'url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_Ticket::ID;
		$token_values['_type'] = Context_Ticket::URI;
		
		$token_values['_types'] = $token_types;
		
		// Ticket token values
		if(null != $ticket) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = sprintf("[#%s] %s", $ticket->mask, $ticket->subject);
			$token_values['closed_at'] = $ticket->closed_at;
			$token_values['closed'] = $ticket->closed_at;
			$token_values['created'] = $ticket->created_date;
			$token_values['elapsed_response_first'] = $ticket->elapsed_response_first;
			$token_values['elapsed_resolution_first'] = $ticket->elapsed_resolution_first;
			$token_values['id'] = $ticket->id;
			$token_values['importance'] = $ticket->importance;
			$token_values['mask'] = $ticket->mask;
			$token_values['num_messages'] = $ticket->num_messages;
			$token_values['num_messages_in'] = $ticket->num_messages_in;
			$token_values['num_messages_out'] = $ticket->num_messages_out;
			$token_values['org_id'] = $ticket->org_id;
			$token_values['reopen_date'] = $ticket->reopen_at;
			$token_values['spam_score'] = $ticket->spam_score;
			$token_values['spam_training'] = $ticket->spam_training;
			$token_values['status_id'] = $ticket->status_id;
			$token_values['subject'] = $ticket->subject;
			$token_values['updated'] = $ticket->updated_date;
			
			// Status
			
			switch($ticket->status_id) {
				case Model_Ticket::STATUS_WAITING:
					$token_values['status'] = 'waiting';
					break;
				case Model_Ticket::STATUS_CLOSED:
					$token_values['status'] = 'closed';
					break;
				case Model_Ticket::STATUS_DELETED:
					$token_values['status'] = 'deleted';
					break;
				default:
				case Model_Ticket::STATUS_OPEN:
					$token_values['status'] = 'open';
					break;
			}
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($ticket, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['url'] = $url_writer->writeNoProxy('c=profiles&type=ticket&id='.$ticket->mask,true);
			$token_values['record_url'] = $token_values['url'];

			// Group
			$token_values['group_id'] = $ticket->group_id;

			// Bucket
			$token_values['bucket_id'] = $ticket->bucket_id;
			
			// First message
			$token_values['initial_message_id'] = $ticket->first_message_id;
			
			// First response
			$token_values['initial_response_message_id'] = $ticket->first_outgoing_message_id;
			
			// Last message
			$token_values['latest_message_id'] = $ticket->last_message_id;
			
			// Owner
			$token_values['owner_id'] = $ticket->owner_id;
			
			// Org
			$token_values['org_id'] = $ticket->org_id;
		}
		
		// Group
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'group_',
			$prefix.'Group:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Bucket
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BUCKET, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'bucket_',
			$prefix.'Bucket:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// First message
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, null, $merge_token_labels, $merge_token_values, '', true);
		
		CerberusContexts::merge(
			'initial_message_',
			$prefix.'Initial:Message:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// First response
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, null, $merge_token_labels, $merge_token_values, '', true);
		
		CerberusContexts::merge(
			'initial_response_message_',
			$prefix.'Initial:Response:Message:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Last message
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, null, $merge_token_labels, $merge_token_values, '', true);
		
		CerberusContexts::merge(
			'latest_message_',
			$prefix.'Latest:Message:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Owner
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, '', true);

			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$merge_token_labels,
				$merge_token_values,
				array(
					"#^address_org_#",
				)
			);
		
			CerberusContexts::merge(
				'owner_',
				$prefix.'Owner:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		
		// Org
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, null, $merge_token_labels, $merge_token_values, '', true);
		
			CerberusContexts::merge(
				'org_',
				$prefix.'Org:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
			
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		$map = parent::getKeyToDaoFieldMap();
		
		$map = array_merge($map, [
			'bucket_id' => DAO_Ticket::BUCKET_ID,
			'closed' => DAO_Ticket::CLOSED_AT,
			'created' => DAO_Ticket::CREATED_DATE,
			'elapsed_response_first' => DAO_Ticket::ELAPSED_RESPONSE_FIRST,
			'elapsed_resolution_first' => DAO_Ticket::ELAPSED_RESOLUTION_FIRST,
			'group_id' => DAO_Ticket::GROUP_ID,
			'id' => DAO_Ticket::ID,
			'importance' => DAO_Ticket::IMPORTANCE,
			'mask' => DAO_Ticket::MASK,
			'org_id' => DAO_Ticket::ORG_ID,
			'owner_id' => DAO_Ticket::OWNER_ID,
			'reopen_date' => DAO_Ticket::REOPEN_AT,
			'spam_score' => DAO_Ticket::SPAM_SCORE,
			'spam_training' => DAO_Ticket::SPAM_TRAINING,
			'status_id' => DAO_Ticket::STATUS_ID,
			'subject' => DAO_Ticket::SUBJECT,
			'updated' => DAO_Ticket::UPDATED_DATE,
		]);
		
		return $map;
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['group'] = [
			'key' => 'group',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'The [group](/docs/records/types/group/) of the ticket; alternative to `group_id`',
			'type' => 'string',
		];
		
		$keys['org'] = [
			'key' => 'org',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'The exact name of the [organization](/docs/records/types/org/) linked to this ticket; alternative to `org_id`',
			'type' => 'string',
		];
		
		$keys['participants'] = [
			'key' => 'participants',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'A comma-separated list of email addresses to add as participants',
			'type' => 'string',
		];
		
		$keys['participant_ids'] = [
			'key' => 'participant_ids',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'A comma-separated list of email addresses IDs to add or remove as participants. Prefix an ID with `-` to remove',
			'type' => 'string',
		];
		
		$keys['status'] = [
			'key' => 'status',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => '`o` (open), `w` (waiting), `c` (closed), `d` (deleted); alternative to `status_id`',
			'type' => 'string',
		];
		
		if(array_key_exists('elapsed_response_first', $keys))
			$keys['elapsed_response_first']['notes'] = "The number of seconds between the creation of this ticket and its first worker response";
		
		if(array_key_exists('elapsed_resolution_first', $keys))
			$keys['elapsed_resolution_first']['notes'] = "The number of seconds between the creation of this ticket and its first resolution";
		
		$keys['bucket_id']['notes'] = "The ID of the [bucket](/docs/records/types/bucket/) containing this ticket";
		$keys['closed']['notes'] = "The date/time this ticket was first set to status `closed`";
		$keys['group_id']['notes'] = "The ID of the [group](/docs/records/types/group/) containing this ticket";
		$keys['importance']['notes'] = "A number from `0` (least) to `100` (most)";
		$keys['mask']['notes'] = "The randomized reference number for this ticket; auto-generated if blank";
		$keys['org_id']['notes'] = "The ID of the [organization](/docs/records/types/org/) linked to this ticket; alternative to `org`";
		$keys['owner_id']['notes'] = "The ID of the [worker](/docs/records/types/worker/) responsible for this ticket";
		$keys['reopen_date']['notes'] = "If status `waiting`, the date/time to automatically change the status back to `open`";
		$keys['spam_score']['notes'] = "`0.0001` (not spam) to `0.9999` (spam); automatically generated";
		$keys['spam_training']['notes'] = "`S` (spam), `N` (not spam); blank for non-trained";
		$keys['status_id']['notes'] = "`0` (open), `1` (waiting), `2` (closed), `3` (deleted); alternative to `status`";
		$keys['subject']['notes'] = "The subject of the ticket";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			case 'group':
				if(false == ($group_id = DAO_Group::getByName($value))) {
					$error = sprintf("Failed to lookup group: %s", $value);
					return false;
				}
				
				$out_fields[DAO_Ticket::GROUP_ID] = $group_id;
				break;
				
			case 'org':
				if(false == ($org_id = DAO_ContactOrg::lookup($value, true))) {
					$error = sprintf("Failed to lookup org: %s", $value);
					return false;
				}
				
				$out_fields[DAO_Ticket::ORG_ID] = $org_id;
				break;
			
			case 'participants':
				$out_fields[DAO_Ticket::_PARTICIPANTS] = $value;
				break;
				
			case 'participant_ids':
				$out_fields[DAO_Ticket::_PARTICIPANT_IDS] = $value;
				break;
				
			case 'status':
				$statuses_to_ids = [
					'o' => 0,
					'w' => 1,
					'c' => 2,
					'd' => 3,
				];
				
				$status_label = DevblocksPlatform::strLower(mb_substr($value,0,1));
				@$status_id = $statuses_to_ids[$status_label];
				
				if(is_null($status_id)) {
					$error = 'Status must be: open, waiting, closed, or deleted.';
					return false;
				}
				
				$out_fields[DAO_Ticket::STATUS_ID] = $status_id;
				break;
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['_messages'] = [
			'label' => 'Messages',
			'type' => 'Records',
		];
		
		$lazy_keys['participants'] = [
			'label' => 'Participants',
			'type' => 'Records',
		];

		$lazy_keys['requester_emails'] = [
			'label' => 'Requester emails (comma-separated)',
			'type' => 'Text',
		];
		
		$lazy_keys['requesters'] = [
			'label' => 'Requesters',
			'type' => 'HashMap',
		];
		
		$lazy_keys['signature'] = [
			'label' => 'Signature',
			'type' => 'Text',
		];
		
		$lazy_keys['latest_incoming_activity'] = [
			'label' => 'Latest Incoming Activity',
			'type' => 'Date',
		];
		
		$lazy_keys['latest_outgoing_activity'] = [
			'label' => 'Latest Outgoing Activity',
			'type' => 'Date',
		];
		
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_TICKET;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'participants':
				$values['participants'] = [];
				$reqs = DAO_Ticket::getRequestersByTicket($context_id);
				
				if(is_array($reqs)) {
					$models = DAO_Address::getIds(array_keys($reqs));
					$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_ADDRESS, ['contact_']);
					$values['participants'] = array_values($dicts);
				}
				break;

			case 'requester_emails':
				if(!isset($dictionary['requesters'])) {
					$result = $this->lazyLoadContextValues('requesters', $dictionary);
					$emails = [];
					
					if(isset($result['requesters'])) {
						$values['requesters'] = $result['requesters'];
						
						
						if(is_array($result['requesters']))
						foreach($result['requesters'] as $req) {
							$emails[] = $req['email'];
						}
						
						$values['requester_emails'] = implode(', ', $emails);
					}
				}
				break;
				
			case 'requesters':
				$values['requesters'] = [];
				$reqs = DAO_Ticket::getRequestersByTicket($context_id);
				if(is_array($reqs))
				foreach($reqs as $req) { /* @var $req Model_Address */
					$values['requesters'][$req->id] = array(
						'id' => $req->id,
						'email' => $req->email,
						'name' => $req->getName(),
						'contact_id' => $req->contact_id,
						'org_id' => $req->contact_org_id,
					);
				}
				break;
				
			case 'signature':
				if(false == ($active_worker = CerberusApplication::getActiveWorker()))
					break;
				
				if(!isset($dictionary['group_id']) || false == ($group = DAO_Group::get($dictionary['group_id'])))
					break;
				
				$values['signature'] = $group->getReplySignature(intval($dictionary['bucket_id']), $active_worker, false);
				break;
				
			case 'signature_html':
				if(false == ($active_worker = CerberusApplication::getActiveWorker()))
					break;
				
				if(!isset($dictionary['group_id']) || false == ($group = DAO_Group::get($dictionary['group_id'])))
					break;
				
				$values['signature_html'] = $group->getReplySignature(intval($dictionary['bucket_id']), $active_worker, true);
				break;
				
			case '_messages':
				$values['_messages'] = DAO_Message::getMessagesByTicket($context_id);
				break;
				
			case 'latest_incoming_activity':
			case 'latest_outgoing_activity':
				// We have some hints about the latest message
				// It'll either be incoming or outgoing
				@$latest_created = $dictionary['latest_message_created'];
				@$latest_is_outgoing = !empty($dictionary['latest_message_is_outgoing']);
				
				switch($token) {
					case 'latest_incoming_activity':
						// Can we just use the info we have already?
						if(!$latest_is_outgoing && !empty($latest_created)) {
							// Yes, cache it.
							$values[$token] = $latest_created;
							
						} else {
							if(!isset($dictionary['_messages'])) {
								$result = $this->lazyLoadContextValues('_messages', $dictionary);
								if(isset($result['_messages'])) {
									$messages = $result['_messages'];
									$values['_messages'] = $messages;
								}
								
							} else {
								$messages = $dictionary['_messages'];
							}
							
							$value = null;
							if(is_array($messages))
							foreach($messages as $message) { /* @var $message Model_Message */
								if(empty($message->is_outgoing))
									$value = $message->created_date;
							}
							$values[$token] = intval($value);
						}
						break;
						
					case 'latest_outgoing_activity':
						// Can we just use the info we have already?
						if($latest_is_outgoing && !empty($latest_created)) {
							// Yes, cache it.
							$values[$token] = $latest_created;
							
						} else {
							if(!isset($dictionary['_messages'])) {
								$result = $this->lazyLoadContextValues('_messages', $dictionary);
								if(isset($result['_messages'])) {
									$messages = $result['_messages'];
									$values['_messages'] = $messages;
								}
								
							} else {
								$messages = $dictionary['_messages'];
							}
							
							$value = null;
							if(is_array($messages))
							foreach($messages as $message) { /* @var $message Model_Message */
								if(!empty($message->is_outgoing))
									$value = $message->created_date;
							}
							$values[$token] = intval($value);
						}
						break;
				}
				break;
				
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
				$values = array_merge($values, $defaults);
				break;
		}
		
		return $values;
	}
	
	// Overload the search view for this context
	function getSearchView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($view = parent::getSearchView($view_id))) {
			if($active_worker) {
				$view->addParamsDefault(array(
					SearchFields_Ticket::VIRTUAL_STATUS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS,'in',array('open', 'waiting')),
					SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,'=',$active_worker->id),
				), true);
			}
		}
		
		return $view;
	}
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->options = [];

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tickets';
		$view->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_WROTE_ID,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_GROUP_ID,
			SearchFields_Ticket::TICKET_BUCKET_ID,
			SearchFields_Ticket::TICKET_OWNER_ID,
		);
		
		$params = array(
			SearchFields_Ticket::VIRTUAL_STATUS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS,'in',array('open','waiting')),
		);
		
		if($active_worker)
			$params[SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER] = new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,'=',$active_worker->id);
		
		$view->addParams($params, true);
		$view->addParamsDefault($params, true);
		
		$view->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tickets';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		if(empty($context_id)) {
			$this->_renderPeekComposePopup($view_id, $edit);
		} else {
			$this->_renderPeekTicketPopup($context_id, $view_id);
		}
	}
	
	function _renderPeekComposePopup($view_id, $edit=null) {
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.ticket.create'))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$is_new_draft = false;
		
		if(!$draft_id) {
			$draft_id = DAO_MailQueue::create([
				DAO_MailQueue::TYPE => Model_MailQueue::TYPE_COMPOSE,
				DAO_MailQueue::WORKER_ID => $active_worker->id,
				DAO_MailQueue::IS_QUEUED => 0,
				DAO_MailQueue::QUEUE_DELIVERY_DATE => 0,
			]);
			$is_new_draft = true;
		}
		
		if(false == ($draft = DAO_MailQueue::get($draft_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Draft::isWriteableByActor($draft, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if($draft->worker_id != $active_worker->id)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Buckets
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$signature_pos = DAO_WorkerPref::get($active_worker->id, 'mail_signature_pos', 2);
		
		$defaults = [
			'group_id' => DAO_WorkerPref::get($active_worker->id, 'compose.group_id', 0),
			'bucket_id' => DAO_WorkerPref::get($active_worker->id, 'compose.bucket_id', 0),
			'status' => DAO_WorkerPref::get($active_worker->id, 'compose.status', 'waiting'),
		];
		
		// Preferences
		if($is_new_draft) {
			if ($bucket_id && false != ($bucket = DAO_Bucket::get($bucket_id))) {
				$defaults['group_id'] = $bucket->group_id;
				$defaults['bucket_id'] = $bucket->id;
				
			} else {
				// Default group/bucket based on worklist
				if (false != ($view = C4_AbstractViewLoader::getView($view_id)) && $view instanceof View_Ticket) {
					$params = $view->getParams();
					
					if (false != ($filter_bucket = $view->findParam(SearchFields_Ticket::TICKET_BUCKET_ID, $params, false))) {
						$filter_bucket = array_shift($filter_bucket);
						
						if (!is_array($filter_bucket->value) || 1 == count($filter_bucket->value)) {
							$bucket_id = is_array($filter_bucket->value) ? current($filter_bucket->value) : $filter_bucket->value;
							
							if (isset($buckets[$bucket_id])) {
								$group_id = $buckets[$bucket_id]->group_id;
								$defaults['group_id'] = $group_id;
								$defaults['bucket_id'] = $bucket_id;
							}
						}
						
					} else if (false != ($filter_group = $view->findParam(SearchFields_Ticket::TICKET_GROUP_ID, $params, false))) {
						$filter_group = array_shift($filter_group);
						
						if (!is_array($filter_group->value) || 1 == count($filter_group->value)) {
							$group_id = is_array($filter_group->value) ? current($filter_group->value) : $filter_group->value;
							
							if (isset($groups[$group_id])) {
								$defaults['group_id'] = $group_id;
								$defaults['bucket_id'] = intval(@$groups[$group_id]->getDefaultBucket()->id);
							}
						}
					}
				}
			}
			
			if (!empty($edit)) {
				$tokens = explode(' ', trim($edit));
				
				foreach ($tokens as $token) {
					list($k, $v) = array_pad(explode(':', $token), 2, null);
					
					if ($v)
						switch ($k) {
							case 'to':
								$to = $v;
								break;
							
							case 'org.id':
								if (false != ($org = DAO_ContactOrg::get($v)))
									$draft->params['org_name'] = $org->name;
								break;
						}
				}
			}
			
			if(1 == $signature_pos) {
				$draft->params['content'] = "\n\n\n#signature\n#cut\n";
			} else if(in_array($signature_pos, [2,3])) {
				$draft->params['content'] = "\n\n\n#signature\n";
			}
			
			// If we still don't have a default group, use the first group
			if(empty($defaults['group_id']) && count($groups)) {
				$default_group = current($groups);
				$defaults['group_id'] = $default_group->id;
				$defaults['bucket_id'] = $default_group->getDefaultBucket()->id ?? 0;
			}
			
			$draft->params['to'] = $to;
			$draft->params['group_id'] = $defaults['group_id'];
			$draft->params['bucket_id'] = $defaults['bucket_id'];
			$draft->params['status_id'] = DAO_Ticket::getStatusIdFromText($defaults['status']);
			
		} else {
			// If the draft doesn't have a group, bucket, or status, default them
			if(!array_key_exists('group_id', $draft->params)) {
				$default_group = current($groups);
				$draft->params['group_id'] = $defaults['group_id'] ?: $default_group->id;
				$draft->params['bucket_id'] = $defaults['bucket_id'] ?: ($default_group->getDefaultBucket()->id ?? 0);
				
			} else if(!array_key_exists('bucket_id', $draft->params)) {
				$current_group = $groups[$draft->params['group_id']] ?? null;
				
				if($current_group)
					$draft->params['bucket_id'] = $current_group->getDefaultBucket()->id ?? 0;
			}
			
			if(!array_key_exists('status_id', $draft->params))
				$draft->params['status_id'] = DAO_Ticket::getStatusIdFromText($defaults['status']);
		}
		
		// Changing the draft through an automation
		AutomationTrigger_MailDraft::trigger($draft, !$is_new_draft);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_fieldsets_available = DAO_CustomFieldset::getUsableByActorByContext($active_worker, CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('custom_fieldsets_available', $custom_fieldsets_available);
		
		// Expanded custom fieldsets (including draft fields)
		
		$custom_field_values = [];
		
		if(array_key_exists('custom_fields', $draft->params))
			$draft->beforeEditingCustomFields($custom_field_values);
		
		$custom_fieldsets_linked = DAO_CustomFieldset::getByFieldIds(array_keys(array_filter($custom_field_values, fn($v) => !is_null($v))));
		$tpl->assign('custom_fieldsets_linked', $custom_fieldsets_linked);
		
		$tpl->assign('custom_field_values', $custom_field_values);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		// Random popup ID
		$random = uniqid();
		$tpl->assign('popup_uniqid', $random);
		
		 // UI bot behaviors

		 if(null != $active_worker && class_exists('Event_MailBeforeUiComposeByWorker')) {
			 $actions = [];
			
			 $macros = DAO_TriggerEvent::getReadableByActor(
				 $active_worker,
				 Event_MailBeforeUiComposeByWorker::ID,
				 false
			 );
			
			 $scope = [
				 'form_id' => 'frmComposePeek' . $random,
			 ];
			 
			 if (is_array($macros))
				 foreach ($macros as $macro)
					 Event_MailBeforeUiComposeByWorker::trigger($macro->id, $scope, $active_worker->id, $actions);
			
			 if (isset($actions['jquery_scripts']) && is_array($actions['jquery_scripts'])) {
				 $tpl->assign('jquery_scripts', $actions['jquery_scripts']);
			 }
		 }
		
		// Compose toolbar
		
	 $toolbar_keyboard_shortcuts = [];
		 
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.mail.compose.formatting',
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id
		]);
		
		$toolbar_compose_formatting_kata = <<< EOD
interaction/link:
  icon: link
  tooltip: Insert Link
  uri: cerb.editor.toolbar.markdownLink
  keyboard: ctrl+k

interaction/image:
  icon: picture
  tooltip: Insert Image
  uri: cerb.editor.toolbar.markdownImage
  keyboard: ctrl+m

menu/formatting:
  icon: more
  #hover@bool: yes
  items:
    interaction/bold:
      label: Bold
      icon: bold
      uri: cerb.editor.toolbar.wrapSelection
      keyboard: ctrl+b
      inputs:
        start_with: **
    interaction/italics:
      label: Italics
      icon: italic
      uri: cerb.editor.toolbar.wrapSelection
      keyboard: ctrl+i
      inputs:
        start_with: _
    interaction/list:
      label: Unordered List
      icon: list
      uri: cerb.editor.toolbar.indentSelection
      inputs:
        prefix: * 
    interaction/quote:
      label: Quote
      icon: quote
      uri: cerb.editor.toolbar.indentSelection
      keyboard: ctrl+q
      inputs:
        prefix: > 
    interaction/variable:
      label: Variable
      icon: edit
      uri: cerb.editor.toolbar.wrapSelection
      inputs:
        start_with: `
    interaction/codeBlock:
      label: Code Block
      icon: embed
      uri: cerb.editor.toolbar.wrapSelection
      keyboard: ctrl+o
      inputs:
        start_with@text:
          ~~~
          
        end_with@text:
          ~~~
          
    interaction/table:
      label: Table
      icon: table
      uri: cerb.editor.toolbar.markdownTable
EOD;
		
		if(false != ($toolbar_compose_formatting_kata = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_compose_formatting_kata, $toolbar_dict))) {
			DevblocksPlatform::services()->ui()->toolbar()->extractKeyboardShortcuts($toolbar_compose_formatting_kata, $toolbar_keyboard_shortcuts);
			$tpl->assign('toolbar_formatting', $toolbar_compose_formatting_kata);
		}
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.mail.compose',
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id
		]);
		
		if(false != ($toolbar_compose_custom = DAO_Toolbar::getKataByName('mail.compose', $toolbar_dict))) {
			DevblocksPlatform::services()->ui()->toolbar()->extractKeyboardShortcuts($toolbar_compose_custom, $toolbar_keyboard_shortcuts);
			$tpl->assign('toolbar_custom', $toolbar_compose_custom);
		}
		
		$tpl->assign('draft', $draft);
		$tpl->assign('toolbar_keyboard_shortcuts', $toolbar_keyboard_shortcuts);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::mail/section/compose/peek.tpl');
	}
	
	function _renderPeekTicketPopup($context_id, $view_id) {
		@$edit_mode = DevblocksPlatform::importGPC($_REQUEST['edit'],'string',null);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_TICKET;
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('edit_mode', $edit_mode);

		// Template
		
		if(false == ($model = DAO_Ticket::get($context_id))) {
			$tpl->assign('error_message', 'The requested record does not exist.');
			$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
			return false;
		}
		
		if(!$context_id || $edit_mode) {
			$field_overrides = [];
			
			if($model) {
				if(!Context_Ticket::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			if($model && $edit_mode) {
				$tokens = explode(' ', trim($edit_mode));
				
				foreach($tokens as $token) {
					@list($k,$v) = explode(':', $token);
					
					if($v)
					switch($k) {
						case 'status':
							$statuses = [
								'o' => 0,
								'w' => 1,
								'c' => 2,
								'd' => 3,
							];
							
							$status_code = substr(DevblocksPlatform::strLower($v),0,1);
							
							if(array_key_exists($status_code, $statuses))
								$model->status_id = $statuses[$status_code];
							
							$tpl->assign('focus_submit', true);
							break;
							
						case 'spam':
							$options = [
								'n' => CerberusTicketSpamTraining::NOT_SPAM,
								'y' => CerberusTicketSpamTraining::SPAM,
							];
							
							if(null !== ($option = $options[substr(DevblocksPlatform::strLower($v),0,1)]))
								$field_overrides['spam_training'] = $option;
							break;
					}
				}
			}
			
			// Props
			$workers = DAO_Worker::getAllActive();
			$tpl->assign('workers', $workers);
			
			$groups = DAO_Group::getAll();
			$tpl->assign('groups', $groups);
			
			$buckets = DAO_Bucket::getAll();
			$tpl->assign('buckets', $buckets);
			
			$requesters = DAO_Ticket::getRequestersByTicket($context_id);
			$tpl->assign('requesters', $requesters);
			
			// Watchers
			$object_watchers = DAO_ContextLink::getContextLinks($context, array($model->id), CerberusContexts::CONTEXT_WORKER);
			$tpl->assign('object_watchers', $object_watchers);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = @DAO_CustomFieldValue::getValuesByContextIds($context, $model->id)[$model->id] ?: [];
			$tpl->assign('custom_field_values', $custom_field_values);
			
			$tpl->assign('ticket', $model);
			$tpl->assign('field_overrides', $field_overrides);
			$tpl->display('devblocks:cerberusweb.core::tickets/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
	
	function importValidateSync($sync_fields) {
		if(!in_array('_id', $sync_fields) && !in_array('_mask', $sync_fields)) {
			return "ERROR: Either the 'ID' or 'Mask' field must be matched.";
		}
		
		return true;
	}
	
	function mergeGetKeys() {
		$keys = [
			'closed_at',
			'importance',
			'org__label',
			'owner__label',
			'reopen_date',
			'status',
			'subject',
			'updated',
		];
		
		return $keys;
	}
	
	function importGetKeys() {
		$keys = array(
			'_id' => array(
				'label' => 'ID',
				'type' => Model_CustomField::TYPE_NUMBER,
				'param' => SearchFields_Ticket::TICKET_ID,
			),
			'_mask' => array(
				'label' => 'Mask',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Ticket::TICKET_MASK,
			),
			'org_id' => array(
				'label' => 'Organization',
				'type' => 'ctx_' . CerberusContexts::CONTEXT_ORG,
				'param' => SearchFields_Ticket::TICKET_ORG_ID,
			),
			'_status' => array(
				'label' => 'Status',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => null,
			),
			'reopen_at' => array(
				'label' => 'Reopen At',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Ticket::TICKET_REOPEN_AT,
			),
			'owner_id' => array(
				'label' => 'Owner',
				'type' => 'ctx_' . CerberusContexts::CONTEXT_WORKER,
				'param' => SearchFields_Ticket::TICKET_OWNER_ID,
			),
			'importance' => array(
				'label' => 'Importance',
				'type' => Model_CustomField::TYPE_NUMBER,
				'param' => SearchFields_Ticket::TICKET_IMPORTANCE,
			),
			'_watchers' => array(
				'label' => 'Watchers',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => null,
			),
			'updated_date' => array(
				'label' => 'Updated Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Ticket::TICKET_UPDATED_DATE,
			),
		);
	
		$fields = SearchFields_Ticket::getFields();
		self::_getImportCustomFields($fields, $keys);
	
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		// This import is only capable of updating existing rows by mask
		if(!isset($meta['object_id']) || empty($meta['object_id']))
			return;
		
		// Convert virtual fields to fields
		if(isset($meta['virtual_fields'])) {
			
			// Status
			
			if(isset($meta['virtual_fields']['_status'])) {
				switch(DevblocksPlatform::strLower($meta['virtual_fields']['_status'])) {
					case 'open':
						$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_OPEN;
						break;
						
					case 'waiting':
						$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_WAITING;
						break;
						
					case 'closed':
						$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_CLOSED;
						break;
						
					case 'deleted':
						$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_DELETED;
						break;
				}
			}
			
			// Watchers
			
			if(isset($meta['virtual_fields']['_watchers'])) {
				@list($watchers_add, $watchers_del) = self::getWatcherDeltasFromString($meta['virtual_fields']['_watchers']);
				
				if(!empty($watchers_del))
					CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TICKET, $meta['object_id'], array_keys($watchers_del));
				
				if(!empty($watchers_add))
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $meta['object_id'], array_keys($watchers_add));
			}
		}
		
		if(!isset($fields[DAO_Ticket::UPDATED_DATE]))
			$fields[DAO_Ticket::UPDATED_DATE] = time();
		
		if(!empty($fields)) {
			DAO_Ticket::update($meta['object_id'], $fields);
		}
		
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
	}
	
	private function getWatcherDeltasFromString($string) {
		$workers = DAO_Worker::getAllActive();
		$patterns = DevblocksPlatform::parseCsvString($string);
		
		$add_watchers = [];
		$remove_watchers = [];
		
		foreach($patterns as $pattern) {
			$is_add = true;
			
			switch(substr($pattern,0,1)) {
				case '+':
				case '-':
					$is_add = substr($pattern,0,1) == '-' ? false : true;
					$pattern = ltrim($pattern,'-+');
					break;
			}
			
			foreach($workers as $worker_id => $worker) {
				$worker_name = $worker->getName();
			
				if(false !== stristr('all', $pattern) || false !== stristr($worker_name, $pattern)) {
					if($is_add) {
						$add_watchers[$worker_id] = $worker;
					} else {
						$remove_watchers[$worker_id] = $worker;
					}
				}
			}
		}

		return array($add_watchers, $remove_watchers);
	}
	
	function broadcastPlaceholdersGet() {
		$token_values = $this->_broadcastPlaceholdersGet(CerberusContexts::CONTEXT_TICKET, false);
		return $token_values;
	}
	
	function broadcastRecipientFieldsGet() {
		return [];
	}
	
	function broadcastRecipientFieldsToEmails(array $fields, DevblocksDictionaryDelegate $dict) {
		return [];
	}
};

class Model_TicketViewLastAction {
	// [TODO] Recycle the bulk update constants for these actions?
	const ACTION_NOT_SPAM = 'not_spam';
	const ACTION_SPAM = 'spam';
	const ACTION_CLOSE = 'close';
	const ACTION_DELETE = 'delete';
	const ACTION_MOVE = 'move';
	const ACTION_WAITING = 'waiting';
	const ACTION_NOT_WAITING = 'not_waiting';

	public $ticket_ids = []; // key = ticket id, value=old value
	public $action = ''; // spam/closed/move, etc.
	public $action_params = []; // DAO Actions Taken
};

class CerberusTicketSpamTraining { // [TODO] Append 'Enum' to class name?
	const BLANK = '';
	const NOT_SPAM = 'N';
	const SPAM = 'S';
};
