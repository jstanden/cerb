<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class DAO_Ticket extends Cerb_ORMHelper {
	const ID = 'id';
	const MASK = 'mask';
	const SUBJECT = 'subject';
	const STATUS_ID = 'status_id';
	const GROUP_ID = 'group_id';
	const BUCKET_ID = 'bucket_id';
	const ORG_ID = 'org_id';
	const OWNER_ID = 'owner_id';
	const IMPORTANCE = 'importance';
	const FIRST_MESSAGE_ID = 'first_message_id';
	const FIRST_OUTGOING_MESSAGE_ID = 'first_outgoing_message_id';
	const LAST_MESSAGE_ID = 'last_message_id';
	const LAST_WROTE_ID = 'last_wrote_address_id';
	const FIRST_WROTE_ID = 'first_wrote_address_id';
	const CREATED_DATE = 'created_date';
	const UPDATED_DATE = 'updated_date';
	const CLOSED_AT = 'closed_at';
	const REOPEN_AT = 'reopen_at';
	const SPAM_TRAINING = 'spam_training';
	const SPAM_SCORE = 'spam_score';
	const INTERESTING_WORDS = 'interesting_words';
	const NUM_MESSAGES = 'num_messages';
	const ELAPSED_RESPONSE_FIRST = 'elapsed_response_first';
	const ELAPSED_RESOLUTION_FIRST = 'elapsed_resolution_first';
	
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
		$ticket_id = $db->GetOneSlave($sql);

		// If we found a hit on a ticket record, return the ID
		if(!empty($ticket_id)) {
			return $ticket_id;
			
		// Check if this mask was previously forwarded elsewhere
		} else {
			$sql = sprintf("SELECT new_ticket_id FROM ticket_mask_forward WHERE old_mask = %s",
				$db->qstr($mask)
			);
			$ticket_id = $db->GetOneSlave($sql);
			
			if(!empty($ticket_id))
				return $ticket_id;
		}

		// No match
		return null;
	}
	
	static function getMergeParentByMask($old_mask) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT new_mask from ticket_mask_forward WHERE old_mask = %s",
			$db->qstr($old_mask)
		);
		
		$new_mask = $db->GetOneSlave($sql);
		
		if(empty($new_mask))
			return null;
		
		return $new_mask;
	}
	
	/**
	 * Enter description here...
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
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT ticket_id, id as message_id ".
			"FROM message ".
			"WHERE hash_header_message_id = %s",
			$db->qstr(sha1($raw_message_id))
		);
		
		if(false == ($row = $db->GetRowSlave($sql)) || empty($row))
			return false;
		
		$ticket_id = intval($row['ticket_id']);
		$message_id = intval($row['message_id']);
			
		return array(
			'ticket_id' => $ticket_id,
			'message_id' => $message_id
		);
	}
	
	static function getViewForRequesterHistory($view_id, $ticket, $scope=null) {
		$translate = DevblocksPlatform::getTranslationService();
		
		// Defaults
		$defaults = C4_AbstractViewModel::loadFromClass('View_Ticket');
		$defaults->id = $view_id;
		$defaults->name = $translate->_('addy_book.history.view.title');
		
		// View
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		
		// Sanitize scope options
		if('org'==$scope) {
			if(empty($ticket->org_id))
				$scope = '';
		}
		
		if('domain'==$scope) {
			$contact = DAO_Address::get($ticket->first_wrote_address_id);
			
			$email_parts = explode('@', $contact->email);
			if(!is_array($email_parts) || 2 != count($email_parts))
				$scope = '';
		}

		switch($scope) {
			case 'org':
				$view->addParamsRequired(array(
					SearchFields_Ticket::VIRTUAL_ORG_ID => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_ORG_ID,'=',$ticket->org_id),
					SearchFields_Ticket::TICKET_STATUS_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_STATUS_ID,'!=',Model_Ticket::STATUS_DELETED),
				), true);
				$view->name = ucwords($translate->_('common.organization'));
				break;
				
			case 'domain':
				$view->addParamsRequired(array(
					SearchFields_Ticket::REQUESTER_ADDRESS => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,'like','*@'.$email_parts[1]),
					SearchFields_Ticket::TICKET_STATUS_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_STATUS_ID,'!=',Model_Ticket::STATUS_DELETED),
				), true);
				$view->name = ucwords($translate->_('common.email')) . ": *@" . $email_parts[1];
				break;
				
			default:
			case 'email':
				$scope = 'email';
				$requesters = $ticket->getRequesters();
				
				$view->addParamsRequired(array(
					SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array_keys($requesters)),
					SearchFields_Ticket::TICKET_STATUS_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_STATUS_ID,'!=',Model_Ticket::STATUS_DELETED),
				), true);
				$view->name = sprintf("History: %d recipient(s)", count($requesters));
				break;
		}
		
		$view->renderPage = 0;
		
		return $view;
	}
	
	static function getParticipants($ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT %s AS context, address_id AS context_id, count(id) AS hits FROM message WHERE is_outgoing = 0 AND ticket_id = %d GROUP BY address_id ". 
			"UNION ".
			"SELECT %s AS context, worker_id AS context_id, count(id) FROM message WHERE is_outgoing = 1 AND worker_id > 0 AND ticket_id = %d GROUP BY worker_id",
			$db->qstr(CerberusContexts::CONTEXT_ADDRESS),
			$ticket_id,
			$db->qstr(CerberusContexts::CONTEXT_WORKER),
			$ticket_id
		);
		$results = $db->GetArraySlave($sql);
		
		return $results;
	}
	
	static function countsByContactId($contact_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$results = $db->GetArraySlave($sql);
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$results = $db->GetArraySlave($sql);
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$results = $db->GetArraySlave($sql);
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$results = $db->GetArraySlave($sql);
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$results = $db->GetArraySlave($sql);
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
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
		$results = $db->GetArraySlave($sql);
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO ticket (created_date, updated_date) ".
			"VALUES (%d,%d)",
			time(),
			time()
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		$id = $db->LastInsertId();
		
		self::update($id, $fields, false);
		
		return $id;
	}

	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
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
		$eventMgr = DevblocksPlatform::getEventService();
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
	
	static function merge($ids=array()) {
		if(!is_array($ids) || empty($ids) || count($ids) < 2) {
			return false;
		}
		
		$db = DevblocksPlatform::getDatabaseService();
			
		list($merged_tickets, $null) = DAO_Ticket::search(
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
		if(is_array($merged_tickets)) {
			list($oldest_id, $oldest_ticket) = each($merged_tickets);
			unset($merged_tickets[$oldest_id]);
			
			$merge_ticket_ids = array_keys($merged_tickets);
			
			if(empty($oldest_id) || empty($merge_ticket_ids))
				return null;
			
			// Messages
			$sql = sprintf("UPDATE message SET ticket_id = %d WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->ExecuteMaster($sql);
			
			// Mail queue
			$sql = sprintf("UPDATE mail_queue SET ticket_id = %d WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->ExecuteMaster($sql);
			
			// Requesters (merge)
			$sql = sprintf("INSERT IGNORE INTO requester (address_id, ticket_id) ".
				"SELECT address_id, %d FROM requester WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->ExecuteMaster($sql);
			
			$sql = sprintf("DELETE FROM requester WHERE ticket_id IN (%s)",
				implode(',', $merge_ticket_ids)
			);
			$db->ExecuteMaster($sql);

			// Context Links
			
			$db->ExecuteMaster(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
				"SELECT 'cerberusweb.contexts.ticket', %d, to_context, to_context_id ".
				"FROM context_link WHERE from_context = 'cerberusweb.contexts.ticket' AND from_context_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			));
			
			$db->ExecuteMaster(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
				"SELECT from_context, from_context_id, 'cerberusweb.contexts.ticket', %d ".
				"FROM context_link WHERE to_context = 'cerberusweb.contexts.ticket' AND to_context_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			));
			
			$db->ExecuteMaster(sprintf("DELETE FROM context_link ".
				"WHERE (from_context = 'cerberusweb.contexts.ticket' AND from_context_id IN (%s)) ".
				"OR (to_context = 'cerberusweb.contexts.ticket' AND to_context_id IN (%s))",
				implode(',', $merge_ticket_ids),
				implode(',', $merge_ticket_ids)
			));
			
			$db->ExecuteMaster(sprintf("DELETE FROM context_link WHERE from_context=to_context AND from_context_id=to_context_id ".
				"AND from_context = 'cerberusweb.contexts.ticket' AND from_context_id = %d",
				$oldest_id
			));
			
			// Activity log
			
			$db->ExecuteMaster(sprintf("UPDATE IGNORE context_activity_log ".
				"SET target_context_id = %d ".
				"WHERE target_context = 'cerberusweb.contexts.ticket' AND target_context_id IN (%s) ",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			));
			
			$db->ExecuteMaster(sprintf("DELETE FROM context_activity_log ".
				"WHERE target_context = 'cerberusweb.contexts.ticket' AND target_context_id IN (%s) ",
				implode(',', $merge_ticket_ids)
			));
			
			// Notifications
			
			$sql = sprintf("UPDATE notification SET context_id = %d WHERE context = %s AND context_id IN (%s)",
				$oldest_id,
				$db->qstr(CerberusContexts::CONTEXT_TICKET),
				implode(',', $merge_ticket_ids)
			);
			$db->ExecuteMaster($sql);
			
			// Comments
			
			$sql = sprintf("UPDATE comment SET context_id = %d WHERE context = %s AND context_id IN (%s)",
				$oldest_id,
				$db->qstr(CerberusContexts::CONTEXT_TICKET),
				implode(',', $merge_ticket_ids)
			);
			$db->ExecuteMaster($sql);
			
			// Clear old ticket meta
			$fields = array(
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
				DAO_Ticket::REOPEN_AT => 0,
				DAO_Ticket::FIRST_MESSAGE_ID => 0,
				DAO_Ticket::FIRST_OUTGOING_MESSAGE_ID => 0,
				DAO_Ticket::LAST_MESSAGE_ID => 0,
				DAO_Ticket::NUM_MESSAGES => 0,
				DAO_Ticket::ELAPSED_RESPONSE_FIRST => 0,
				DAO_Ticket::ELAPSED_RESOLUTION_FIRST => 0,
			);
			DAO_Ticket::update($merge_ticket_ids, $fields, false);

			// Sort merge tickets by updated date ascending to find the latest touched
			$tickets = $merged_tickets;
			array_unshift($tickets, $oldest_ticket);
			DevblocksPlatform::sortObjects($tickets, '[' . SearchFields_Ticket::TICKET_UPDATED_DATE . ']');
			$most_recent_updated_ticket = end($tickets);

			// Default our status bits to the most recently updated
			$merge_dst_status_id = $most_recent_updated_ticket[SearchFields_Ticket::TICKET_STATUS_ID];
			
			reset($tickets);
			
			// If any ticket in the list is status open, our destination should be open
			foreach($tickets as $merged_ticket) {
				if($merged_ticket[SearchFields_Ticket::TICKET_STATUS_ID] == Model_Ticket::STATUS_OPEN) {
					$merge_dst_status_id = Model_Ticket::STATUS_OPEN;
					break;
				}
			}
			
			// Set our destination ticket to the latest touched details
			$fields = array(
				DAO_Ticket::UPDATED_DATE => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_UPDATED_DATE],
				DAO_Ticket::STATUS_ID => $merge_dst_status_id,
			);
			DAO_Ticket::update($oldest_id, $fields, false);
			
			DAO_Ticket::rebuild($oldest_id);
			
			// Set up forwarders for the old masks to their new mask
			$new_mask = $oldest_ticket[SearchFields_Ticket::TICKET_MASK];
			if(is_array($merged_tickets))
			foreach($merged_tickets as $ticket) {
				// Forward the old mask to the new mask
				$sql = sprintf("INSERT IGNORE INTO ticket_mask_forward (old_mask, new_mask, new_ticket_id) VALUES (%s, %s, %d)",
					$db->qstr($ticket[SearchFields_Ticket::TICKET_MASK]),
					$db->qstr($new_mask),
					$oldest_id
				);
				$db->ExecuteMaster($sql);
				
				// If the old mask was a new_mask in a past life, change to its new destination
				$sql = sprintf("UPDATE ticket_mask_forward SET new_mask = %s, new_ticket_id = %d WHERE new_mask = %s",
					$db->qstr($new_mask),
					$oldest_id,
					$db->qstr($ticket[SearchFields_Ticket::TICKET_MASK])
				);
				$db->ExecuteMaster($sql);
			}
			
			if(is_array($merged_tickets))
			foreach($merged_tickets as $ticket) {
				/*
				 * Log activity (ticket.merge)
				 */
				$entry = array(
					//{{actor}} merged ticket {{source}} with ticket {{target}}
					'message' => 'activities.ticket.merge',
					'variables' => array(
						'source' => sprintf("[%s] %s", $ticket[SearchFields_Ticket::TICKET_MASK], $ticket[SearchFields_Ticket::TICKET_SUBJECT]),
						'target' => sprintf("[%s] %s", $oldest_ticket[SearchFields_Ticket::TICKET_MASK], $oldest_ticket[SearchFields_Ticket::TICKET_SUBJECT]),
						),
					'urls' => array(
						'source' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $ticket[SearchFields_Ticket::TICKET_MASK]),
						'target' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $oldest_ticket[SearchFields_Ticket::TICKET_MASK]),
						)
				);
				CerberusContexts::logActivity('ticket.merge', CerberusContexts::CONTEXT_TICKET, $oldest_id, $entry);
			}
			
			/*
			 * Notify anything that wants to know when tickets merge.
			 */
			$eventMgr = DevblocksPlatform::getEventService();
			$eventMgr->trigger(
				new Model_DevblocksEvent(
					'ticket.action.merge',
					array(
						'new_ticket_id' => $oldest_id,
						'old_ticket_ids' => $merge_ticket_ids,
					)
				)
			);
			
			return $oldest_id;
		}
	}
	
	static function rebuild($id) {
		if(null == ($ticket = DAO_Ticket::get($id)))
			return FALSE;

		$db = DevblocksPlatform::getDatabaseService();
		
		$messages = $ticket->getMessages();
		$first_message = reset($messages);
		$last_message = end($messages);
		
		// If no messages, delete the ticket
		if(empty($first_message) && empty($last_message)) {
			$fields = array(
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
			);
			DAO_Ticket::update($id, $fields, false);
			
			return FALSE;
		}
		
		$fields = array();
		
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
			if($message->is_outgoing && !empty($worker->id) && !isset($fields[DAO_Ticket::FIRST_OUTGOING_MESSAGE_ID])) {
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
		
		// Update
		if(!empty($fields)) {
			DAO_Ticket::update($id, $fields, false);
		}
		
		// Reindex message count
		DAO_Ticket::updateMessageCount($id);
		
		return TRUE;
	}
	
	/**
	 * Enter description here...
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
		$db = DevblocksPlatform::getDatabaseService();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id , mask, subject, status_id, group_id, bucket_id, org_id, owner_id, importance, first_message_id, first_outgoing_message_id, last_message_id, ".
			"first_wrote_address_id, last_wrote_address_id, created_date, updated_date, closed_at, reopen_at, spam_training, ".
			"spam_score, interesting_words, num_messages, elapsed_response_first, elapsed_resolution_first ".
			"FROM ticket ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param resource $rs
	 */
	static private function _createObjectsFromResultSet($rs=null) {
		$objects = array();
		
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
			$object->num_messages = $row['num_messages'];
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
			$ids = array($ids);
		
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
				self::_processUpdateEvents($batch_ids, $fields);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
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
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = array();
		$custom_fields = array();
		$worker_dict = null;
		
		// If merging, do that first, then run subsequent actions on the lone destination ticket
		if(isset($do['merge'])) {
			if(null != ($merged_into_id = DAO_Ticket::merge($ids))) {
				$ids = array($merged_into_id);
			}
		}
		
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
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}
		
		// Fields
		if(!empty($change_fields) || !empty($custom_fields)) {
			$change_fields[DAO_Ticket::UPDATED_DATE] = time();
			DAO_Ticket::update($ids, $change_fields);
		}
		
		// Custom Fields
		C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_TICKET, $custom_fields, $ids);
		
		// Watchers
		if(isset($do['watchers']))
			C4_AbstractView::_doBulkChangeWatchers(CerberusContexts::CONTEXT_TICKET, $do['watchers'], $ids);
		
		// Scheduled behavior
		if(isset($do['behavior']))
			C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_TICKET, $do['behavior'], $ids);
		
		if(isset($do['broadcast'])) {
			try {
				$broadcast_params = $do['broadcast'];
				
				if(
					!isset($broadcast_params['worker_id']) || empty($broadcast_params['worker_id'])
					|| !isset($broadcast_params['message']) || empty($broadcast_params['message'])
					)
					throw new Exception("Missing parameters for broadcast.");
					
				$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_TICKET, $ids);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_TICKET, array('custom_'));
				
				// $tpl_builder->tokenize($broadcast_params['message']
				
				$is_queued = (isset($broadcast_params['is_queued']) && $broadcast_params['is_queued']) ? true : false;
				
				if(is_array($dicts))
				foreach($dicts as $ticket_id => $dict) {
					$body = $tpl_builder->build($broadcast_params['message'], $dict);
					
					$params_json = array(
						'in_reply_message_id' => $dict->latest_message_id,
						'is_broadcast' => 1,
					);
					
					if(isset($broadcast_params['format']))
						$params_json['format'] = $broadcast_params['format'];
					
					if(isset($broadcast_params['html_template_id']))
						$params_json['html_template_id'] = intval($broadcast_params['html_template_id']);
					
					$fields = array(
						DAO_MailQueue::TYPE => Model_MailQueue::TYPE_TICKET_REPLY,
						DAO_MailQueue::TICKET_ID => $ticket_id,
						DAO_MailQueue::WORKER_ID => $broadcast_params['worker_id'],
						DAO_MailQueue::UPDATED => time(),
						DAO_MailQueue::HINT_TO => $dict->initial_message_sender_address,
						DAO_MailQueue::SUBJECT => $dict->subject,
						DAO_MailQueue::BODY => $body,
					);
					
					if($is_queued)
						$fields[DAO_MailQueue::IS_QUEUED] = 1;

					if(isset($broadcast_params['file_ids']))
						$params_json['file_ids'] = $broadcast_params['file_ids'];
					
					if(!empty($params_json))
						$fields[DAO_MailQueue::PARAMS_JSON] = json_encode($params_json);
					
					$draft_id = DAO_MailQueue::create($fields);
				}
			} catch (Exception $e) {
				
			}
		}

		$update->markCompleted();
		return true;
	}
	
	static function _processUpdateEvents($ids, $change_fields) {

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
				// VAs

				Event_MailMovedToGroup::trigger($model->id, $model->group_id);

				// Activity log
				
				@$to_group = DAO_Group::get($model->group_id);
				@$to_bucket = DAO_Bucket::get($model->bucket_id);
				
				if(empty($to_group))
					$to_group = DAO_Group::get($model->group_id);
				
				$entry = array(
					//{{actor}} moved ticket {{target}} to {{group}} {{bucket}}
					'message' => 'activities.ticket.moved',
					'variables' => array(
						'target' => sprintf("[%s] %s", $model->mask, $model->subject),
						'group' => $to_group->name,
						'bucket' => $to_bucket->name,
						),
					'urls' => array(
						'target' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_TICKET, $model->id, $model->mask),
						'group' => sprintf("ctx://%s:%d/%s", CerberusContexts::CONTEXT_GROUP, $to_group->id, $to_group->name),
						)
				);
				CerberusContexts::logActivity('ticket.moved', CerberusContexts::CONTEXT_TICKET, $model->id, $entry);
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
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->ExecuteMaster(sprintf("UPDATE ticket ".
			"SET num_messages = (SELECT count(id) FROM message WHERE message.ticket_id = ticket.id) ".
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
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids = array();
		
		$sql = sprintf("SELECT a.id ".
			"FROM address a ".
			"INNER JOIN requester r ON (r.ticket_id = %d AND a.id=r.address_id) ".
			"ORDER BY a.email ASC ",
			$ticket_id
		);
		$results = $db->GetArray($sql);

		if(is_array($results))
		foreach($results as $result) {
			$ids[] = $result['id'];
		}
		
		return DAO_Address::getIds($ids);
	}
	
	static function findMissingRequestersInHeaders($headers, $current_requesters=array()) {
		$results = array();
		$addys = array();

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
		
		foreach($addys as $addy => $addy_data) {
			try {
				// Filter out our own addresses
				if(DAO_AddressOutgoing::isLocalAddress($addy))
					continue;
				
				// Filter explicit excludes
				if(is_array($excludes) && !empty($excludes))
				foreach($excludes as $excl_pattern) {
					if(@preg_match(DevblocksPlatform::parseStringAsRegExp($excl_pattern), $addy)) {
						throw new Exception();
					}
				}
				
				// If we aren't given a personal name, attempt to look them up
				if(empty($addy_data['personal'])) {
					if(null != ($addy_lookup = DAO_Address::lookupAddress($addy))) {
						$addy_fullname = $addy_lookup->getName();
						if(!empty($addy_fullname)) {
							$addy_data['personal'] = $addy_fullname;
							$addy_data['full_email'] = imap_rfc822_write_address($addy_data['mailbox'], $addy_data['host'], $addy_fullname);
						}
					}
				}
				
				$results[$addy] = $addy_data;
				
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
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$replyto_addresses = DAO_AddressOutgoing::getAll();

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
			
		$db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("DELETE FROM requester WHERE ticket_id = %d AND address_id = %d",
			$id,
			$address_id
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
	}
	
	static function addParticipantIds($ticket_id, $address_ids) {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$replyto_addresses = DAO_AddressOutgoing::getAll();
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
			});
		}
		
		// Don't add a requester if the sender is a helpdesk address
		$requesters_add = array_diff(array_keys($addresses), array_keys($replyto_addresses));

		$values = array();
		
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
		$db = DevblocksPlatform::getDatabaseService();
		
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
			"a1.email as %s, ".
			"a2.email as %s, ".
			"t.created_date as %s, ".
			"t.updated_date as %s, ".
			"t.closed_at as %s, ".
			"t.reopen_at as %s, ".
			"t.spam_training as %s, ".
			"t.spam_score as %s, ".
			"t.num_messages as %s, ".
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
				SearchFields_Ticket::TICKET_FIRST_WROTE,
				SearchFields_Ticket::TICKET_LAST_WROTE,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				SearchFields_Ticket::TICKET_CLOSED_AT,
				SearchFields_Ticket::TICKET_REOPEN_AT,
				SearchFields_Ticket::TICKET_SPAM_TRAINING,
				SearchFields_Ticket::TICKET_SPAM_SCORE,
				SearchFields_Ticket::TICKET_NUM_MESSAGES,
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
			"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
			"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) ".
			// Dynamic table joins
			(isset($tables['msg']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.ticket' AND context_link.to_context_id = t.id) " : " ")
			;
		
		if(isset($tables['wtb'])) {
			if(false != ($active_worker = CerberusApplication::getActiveWorker())) {
				$select_sql .= ", wtb.responsibility_level as wtb_responsibility ";
				$join_sql .= sprintf("INNER JOIN worker_to_bucket wtb ON (wtb.bucket_id=t.bucket_id AND wtb.worker_id=%d) ", $active_worker->id);
			}
		}
			
		// Org joins
		if(isset($tables['o'])) {
			$select_sql .= ", o.name as o_name ";
			$join_sql .= "LEFT JOIN contact_org o ON (t.org_id=o.id) ";
		}
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Ticket');

		// Translate virtual fields
		
		$args = array(
			'select_sql' => &$select_sql,
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
		
		array_walk_recursive(
			$params,
			array('DAO_Ticket', '_translateVirtualParameters'),
			$args
		);
		
		$result = array(
			'primary_table' => 't',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
		
		/* @var $param DevblocksSearchCriteria */
		
		$from_context = 'cerberusweb.contexts.ticket';
		$from_index = 't.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_Ticket::VIRTUAL_CONTEXT_LINK:
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
				
			case SearchFields_Ticket::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		}
	}
	
	// [TODO] Utilize Sphinx when it exists?
	static function autocomplete($term) {
		$db = DevblocksPlatform::getDatabaseService();
		$objects = array();
		
		$results = $db->GetArraySlave(sprintf("SELECT id ".
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
		
		$objects = DAO_Ticket::getIds(array_keys($objects));
		
		return $objects;
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
			($has_multiple_values ? 'GROUP BY t.id ' : '').
			$sort_sql;
		
		if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
			return false;
		
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$id = intval($row[SearchFields_Ticket::TICKET_ID]);
			$results[$id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					(($has_multiple_values) ? "SELECT COUNT(DISTINCT t.id) " : "SELECT COUNT(t.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results, $total);
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
	const TICKET_FIRST_WROTE = 't_first_wrote';
	const TICKET_LAST_WROTE_ID = 't_last_wrote_address_id';
	const TICKET_LAST_WROTE = 't_last_wrote';
	const TICKET_CREATED_DATE = 't_created_date';
	const TICKET_UPDATED_DATE = 't_updated_date';
	const TICKET_CLOSED_AT = 't_closed_at';
	const TICKET_REOPEN_AT = 't_reopen_at';
	const TICKET_SPAM_SCORE = 't_spam_score';
	const TICKET_SPAM_TRAINING = 't_spam_training';
	const TICKET_INTERESTING_WORDS = 't_interesting_words';
	const TICKET_NUM_MESSAGES = 't_num_messages';
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
	
	// Sender Org
	const ORG_NAME = 'o_name';

	// Fulltexts
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	const FULLTEXT_MESSAGE_CONTENT = 'ftmc_content';
	const FULLTEXT_NOTE_CONTENT = 'ftnc_content';
	
	// Context Links
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	// Virtuals
	const VIRTUAL_ATTACHMENT_NAME = '*_attachment_name';
	const VIRTUAL_CONTACT_ID = '*_contact_id';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_GROUPS_OF_WORKER = '*_groups_of_worker';
	const VIRTUAL_HAS_ATTACHMENTS = '*_has_attachments';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_ORG_ID = '*_org_id';
	const VIRTUAL_PARTICIPANT_ID = '*_participant_id';
	const VIRTUAL_STATUS = '*_status';
	const VIRTUAL_WATCHERS = '*_workers';
	const VIRTUAL_WORKER_COMMENTED = '*_worker_commented';
	const VIRTUAL_WORKER_REPLIED = '*_worker_replied';
	
	const VIRTUAL_RECOMMENDATIONS = '*_recommendations';
	
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
			case self::VIRTUAL_HAS_ATTACHMENTS:
				return sprintf("%s %sIN (".
					"SELECT message.ticket_id ".
					"FROM attachment_link ".
					"INNER JOIN message ON (attachment_link.context_id = message.id) ".
					"WHERE context='cerberusweb.contexts.message' AND message.ticket_id = %s ".
					")",
					self::getPrimaryKey(),
					!empty($param->value) ? '' : 'NOT ',
					self::getPrimaryKey()
				);
				break;
				
			case self::VIRTUAL_ATTACHMENT_NAME:
				$where = '0';
				$values = array();
				$not = false;
				
				if(is_array($param->value))
				foreach($param->value as $value)
					$values[] = Cerb_ORMHelper::qstr($value);
				
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_NIN:
						$not = ($param->operator == DevblocksSearchCriteria::OPER_NIN);
						$wheres = sprintf('attachment.display_name IN (%s)',
							implode(',', $values)
						);
						break;
						
					case DevblocksSearchCriteria::OPER_EQ:
					case DevblocksSearchCriteria::OPER_NEQ:
						$not = ($param->operator == DevblocksSearchCriteria::OPER_NEQ);
						$wheres = sprintf('attachment.display_name = %s',
							Cerb_ORMHelper::qstr($param->value)
						);
						break;
						
					case DevblocksSearchCriteria::OPER_LIKE:
					case DevblocksSearchCriteria::OPER_NOT_LIKE:
						$not = ($param->operator == DevblocksSearchCriteria::OPER_NOT_LIKE);
						$wheres = sprintf('attachment.display_name LIKE %s',
							Cerb_ORMHelper::qstr(str_replace('*','%',$param->value))
						);
						break;
				}
				
				return sprintf("%s %sIN (".
					"SELECT message.ticket_id ".
					"FROM attachment_link ".
					"INNER JOIN attachment ON (attachment.id=attachment_link.attachment_id) ".
					"INNER JOIN message ON (attachment_link.context_id = message.id) ".
					"WHERE context='cerberusweb.contexts.message' AND message.ticket_id = %s ".
					"AND %s".
					")",
					self::getPrimaryKey(),
					!empty($param->value) ? '' : 'NOT ',
					self::getPrimaryKey(),
					$wheres
				);
				break;
			
			case self::FULLTEXT_MESSAGE_CONTENT:
				if(false == ($search = Extension_DevblocksSearchSchema::get(Search_MessageContent::ID)))
					return null;
				
				$query = $search->getQueryFromParam($param);
				
				if(false === ($ids = $search->query($query, array()))) {
					return '0';
					
				} elseif(is_array($ids)) {
					if(empty($ids))
						$ids = array(-1);
					
					$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
					
					return sprintf('%s IN (SELECT ticket_id FROM message WHERE ticket_id=%s AND id IN (%s))',
						self::getPrimaryKey(),
						self::getPrimaryKey(),
						implode(', ', $ids)
					);
					
				} elseif(is_string($ids)) {
					return sprintf("%s IN (SELECT message.ticket_id FROM %s INNER JOIN message ON (message.id=%s.id) WHERE message.ticket_id=%s)",
						self::getPrimaryKey(),
						$ids,
						$ids,
						self::getPrimaryKey()
					);
				}
				
				return 0;
				break;
				
			case self::FULLTEXT_NOTE_CONTENT:
				$search = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID);
				$query = $search->getQueryFromParam($param);
				
				if(false === ($ids = $search->query($query, array('context_crc32' => sprintf("%u", crc32(CerberusContexts::CONTEXT_MESSAGE)))))) {
					return '0';
				
				} elseif(is_array($ids)) {
					$from_ids = DAO_Comment::getContextIdsByContextAndIds(CerberusContexts::CONTEXT_MESSAGE, $ids);
					
					return sprintf('%s IN (SELECT ticket_id FROM message WHERE id IN (%s) AND ticket_id = %s)',
						self::getPrimaryKey(),
						implode(', ', (!empty($from_ids) ? $from_ids : array(-1))),
						self::getPrimaryKey()
					);
					
				} elseif(is_string($ids)) {
					return sprintf("%s IN (SELECT ticket_id FROM comment INNER JOIN %s ON (%s.id=comment.id) INNER JOIN message ON (message.id=comment.context_id))",
						self::getPrimaryKey(),
						$ids,
						$ids
					);
				}
				break;
				
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_TICKET, self::getPrimaryKey());
				break;
				
			// [TODO] This doesn't need to be virtual now
			// [TODO] IN, NOT
			case self::VIRTUAL_ORG_ID:
				$org_id = $param->value;
				
				return sprintf("t.org_id = %d",
					$org_id
				);
				break;
				
			// [TODO]
			// [TODO] IN, NOT
			case self::VIRTUAL_CONTACT_ID:
				$contact_ids = is_array($param->value) ? $param->value : array($param->value);
				$contact_ids = DevblocksPlatform::sanitizeArray($contact_ids, 'int');
				
				$contact_ids_string = implode(',', $contact_ids);
				
				if(empty($contact_ids_string))
					$contact_ids_string = '-1';
				
				return sprintf("(t.id IN (SELECT DISTINCT r.ticket_id FROM requester r INNER JOIN address a ON (a.id=r.address_id) WHERE a.contact_id IN (%s)))",
					$contact_ids_string
				);
				
				break;
				
			// [TODO]
			// [TODO] IN, NOT
			case self::VIRTUAL_PARTICIPANT_ID:
				$participant_ids = is_array($param->value) ? $param->value : array($param->value);
				$participant_ids = DevblocksPlatform::sanitizeArray($participant_ids, 'int');
				
				$participant_ids_string = implode(',', $participant_ids);
				
				if(empty($participant_ids_string))
					$participant_ids_string = '-1';
				
				return sprintf("(t.first_wrote_address_id IN (%s) OR t.id IN (SELECT DISTINCT r.ticket_id FROM requester r WHERE r.address_id IN (%s)))",
					$participant_ids_string,
					$participant_ids_string
				);
				break;
			
			// [TODO] Array
			case self::VIRTUAL_GROUPS_OF_WORKER:
				if(null == ($member = DAO_Worker::get($param->value)))
					break;
					
				$all_groups = DAO_Group::getAll();
				$roster = $member->getMemberships();
				
				if(empty($roster))
					$roster = array(0 => 0);
				
				$restricted_groups = array_diff(array_keys($all_groups), array_keys($roster));
				
				// If the worker is in every group, ignore this filter entirely
				if(empty($restricted_groups))
					break;
				
				// [TODO] If the worker is in most of the groups, possibly try a NOT IN instead
				
				return sprintf("t.group_id IN (%s)", implode(',', array_keys($roster)));
				break;
			
			case self::VIRTUAL_STATUS:
				$values = $param->value;
				if(!is_array($values))
					$values = array($values);
				
				$oper_sql = array();
				$statuses = array();
				
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
				break;
				
			case self::REQUESTER_ID:
				$where_sql = $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				
				return sprintf("%s IN (SELECT DISTINCT r.ticket_id FROM requester r WHERE %s)",
					self::getPrimaryKey(),
					$where_sql
				);
				break;
			
			case self::REQUESTER_ADDRESS:
				$where_sql = $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				
				return sprintf("%s IN (SELECT DISTINCT r.ticket_id FROM requester r INNER JOIN address ra ON (ra.id=r.address_id) WHERE %s)",
					self::getPrimaryKey(),
					$where_sql
				);
				break;
				
			case self::VIRTUAL_RECOMMENDATIONS:
				$ids = is_array($param->value) ? $param->value : array($param->value);
				$ids = DevblocksPlatform::sanitizeArray($ids, 'integer');
		
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_EQ:
						return sprintf("%s IN (SELECT context_id FROM context_recommendation WHERE context = %s AND context_id = %s AND worker_id IN (%s))",
							self::getPrimaryKey(),
							Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET),
							self::getPrimaryKey(),
							implode(',', $ids)
						);
						break;
						
					case DevblocksSearchCriteria::OPER_NIN:
					case DevblocksSearchCriteria::OPER_NEQ:
						return sprintf("%s NOT IN (SELECT context_id FROM context_recommendation WHERE context = %s AND context_id = %s AND worker_id IN (%s))",
							self::getPrimaryKey(),
							Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET),
							self::getPrimaryKey(),
							implode(',', $ids)
						);
						break;
					
					case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
						return sprintf("%s IN (SELECT context_id FROM context_recommendation WHERE context = %s AND context_id = %s)",
							self::getPrimaryKey(),
							Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET),
							self::getPrimaryKey()
						);
						break;
						
					case DevblocksSearchCriteria::OPER_IS_NULL:
						return sprintf("%s NOT IN (SELECT context_id FROM context_recommendation WHERE context = %s AND context_id = %s)",
							self::getPrimaryKey(),
							Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET),
							self::getPrimaryKey()
						);
						break;
				}
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_TICKET, self::getPrimaryKey());
			
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
				break;
				
			case self::VIRTUAL_WORKER_REPLIED:
				$ids = is_array($param->value) ? $param->value : array($param->value);
				$ids = DevblocksPlatform::sanitizeArray($ids, 'integer');
				
				return sprintf("%s IN (SELECT DISTINCT ticket_id FROM message WHERE ticket_id = %s AND worker_id IN (%s))",
					self::getPrimaryKey(),
					self::getPrimaryKey(),
					implode(',', $ids)
				);
				break;
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
		
		return '0';
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
			
			SearchFields_Ticket::TICKET_FIRST_WROTE_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_FIRST_WROTE_ID, 't', 'first_wrote_address_id', null, null, true),
			SearchFields_Ticket::TICKET_FIRST_WROTE => new DevblocksSearchField(SearchFields_Ticket::TICKET_FIRST_WROTE, 'a1', 'email',$translate->_('ticket.first_wrote'), Model_CustomField::TYPE_SINGLE_LINE, true),
				
			SearchFields_Ticket::TICKET_LAST_WROTE_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_LAST_WROTE_ID, 't', 'last_wrote_address_id', null, null, true),
			SearchFields_Ticket::TICKET_LAST_WROTE => new DevblocksSearchField(SearchFields_Ticket::TICKET_LAST_WROTE, 'a2', 'email',$translate->_('ticket.last_wrote'), Model_CustomField::TYPE_SINGLE_LINE, true),
				
			SearchFields_Ticket::ORG_NAME => new DevblocksSearchField(SearchFields_Ticket::ORG_NAME, 'o', 'name', $translate->_('common.organization'), Model_CustomField::TYPE_SINGLE_LINE, true),
			SearchFields_Ticket::REQUESTER_ADDRESS => new DevblocksSearchField(SearchFields_Ticket::REQUESTER_ADDRESS, 'ra', 'email',$translate->_('common.participant'), Model_CustomField::TYPE_SINGLE_LINE, false),
			
			SearchFields_Ticket::TICKET_ORG_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_ORG_ID, 't','org_id',$translate->_('common.id'), null, true),
			SearchFields_Ticket::TICKET_OWNER_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_OWNER_ID,'t','owner_id',$translate->_('common.owner'), Model_CustomField::TYPE_WORKER, true),
			SearchFields_Ticket::TICKET_IMPORTANCE => new DevblocksSearchField(SearchFields_Ticket::TICKET_IMPORTANCE,'t','importance',$translate->_('common.importance'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_GROUP_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_GROUP_ID,'t','group_id',$translate->_('common.group'), null, true),
			SearchFields_Ticket::TICKET_BUCKET_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_BUCKET_ID, 't', 'bucket_id',$translate->_('common.bucket'), null, true),
			SearchFields_Ticket::TICKET_CREATED_DATE => new DevblocksSearchField(SearchFields_Ticket::TICKET_CREATED_DATE, 't', 'created_date',$translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Ticket::TICKET_UPDATED_DATE => new DevblocksSearchField(SearchFields_Ticket::TICKET_UPDATED_DATE, 't', 'updated_date',$translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Ticket::TICKET_CLOSED_AT => new DevblocksSearchField(SearchFields_Ticket::TICKET_CLOSED_AT, 't', 'closed_at',$translate->_('ticket.closed_at'), Model_CustomField::TYPE_DATE, true),
			SearchFields_Ticket::TICKET_STATUS_ID => new DevblocksSearchField(SearchFields_Ticket::TICKET_STATUS_ID, 't', 'status_id',$translate->_('common.status'), Model_CustomField::TYPE_NUMBER, true),

			SearchFields_Ticket::TICKET_NUM_MESSAGES => new DevblocksSearchField(SearchFields_Ticket::TICKET_NUM_MESSAGES, 't', 'num_messages',$translate->_('ticket.num_messages'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST => new DevblocksSearchField(SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST, 't', 'elapsed_response_first',$translate->_('ticket.elapsed_response_first'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST => new DevblocksSearchField(SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST, 't', 'elapsed_resolution_first',$translate->_('ticket.elapsed_resolution_first'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_SPAM_TRAINING => new DevblocksSearchField(SearchFields_Ticket::TICKET_SPAM_TRAINING, 't', 'spam_training',$translate->_('ticket.spam_training'), null, true),
			SearchFields_Ticket::TICKET_SPAM_SCORE => new DevblocksSearchField(SearchFields_Ticket::TICKET_SPAM_SCORE, 't', 'spam_score',$translate->_('ticket.spam_score'), Model_CustomField::TYPE_NUMBER, true),
			SearchFields_Ticket::TICKET_INTERESTING_WORDS => new DevblocksSearchField(SearchFields_Ticket::TICKET_INTERESTING_WORDS, 't', 'interesting_words',$translate->_('ticket.interesting_words'), null, true),
			SearchFields_Ticket::TICKET_REOPEN_AT => new DevblocksSearchField(SearchFields_Ticket::TICKET_REOPEN_AT, 't', 'reopen_at',$translate->_('ticket.reopen_at'), Model_CustomField::TYPE_DATE, true),
			
			SearchFields_Ticket::BUCKET_RESPONSIBILITY => new DevblocksSearchField(SearchFields_Ticket::BUCKET_RESPONSIBILITY, 'wtb', 'responsibility_level', mb_convert_case($translate->_('common.responsibility'), MB_CASE_TITLE), null, true),
				
			SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchField(SearchFields_Ticket::REQUESTER_ID, 'r', 'address_id', $translate->_('common.participant'), null, false),
			
			SearchFields_Ticket::SENDER_ADDRESS => new DevblocksSearchField(SearchFields_Ticket::SENDER_ADDRESS, 'a1', 'email', null, null, true),

			SearchFields_Ticket::CONTEXT_LINK => new DevblocksSearchField(SearchFields_Ticket::CONTEXT_LINK, 'context_link', 'from_context', null, null, false),
			SearchFields_Ticket::CONTEXT_LINK_ID => new DevblocksSearchField(SearchFields_Ticket::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null, null, false),
			
			SearchFields_Ticket::VIRTUAL_ATTACHMENT_NAME => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_ATTACHMENT_NAME, '*', 'attachment_name', $translate->_('message.search.attachment_name'), null, false),
			SearchFields_Ticket::VIRTUAL_CONTACT_ID => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_CONTACT_ID, '*', 'contact_id', null, null, false), // contact ID
			SearchFields_Ticket::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER, '*', 'groups_of_worker', $translate->_('ticket.groups_of_worker'), null, false),
			SearchFields_Ticket::VIRTUAL_HAS_ATTACHMENTS => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_HAS_ATTACHMENTS, '*', 'has_attachments', $translate->_('message.search.has_attachments'), Model_CustomField::TYPE_CHECKBOX, false),
			SearchFields_Ticket::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			SearchFields_Ticket::VIRTUAL_ORG_ID => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_ORG_ID, '*', 'org_id', null, null, false), // org ID
			SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID, '*', 'participant_id', null, null, false), // participant ID
			SearchFields_Ticket::VIRTUAL_RECOMMENDATIONS => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_RECOMMENDATIONS, '*', 'recommendations', $translate->_('common.recommended'), null, false),
			SearchFields_Ticket::VIRTUAL_STATUS => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_STATUS, '*', 'status', $translate->_('common.status'), null, false),
			SearchFields_Ticket::VIRTUAL_WATCHERS => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
			SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED, '*', 'worker_commented', null, null, false),
			SearchFields_Ticket::VIRTUAL_WORKER_REPLIED => new DevblocksSearchField(SearchFields_Ticket::VIRTUAL_WORKER_REPLIED, '*', 'worker_replied', null, null, false),
				
			SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
			SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT => new DevblocksSearchField(self::FULLTEXT_MESSAGE_CONTENT, 'ftmc', 'content', $translate->_('message.content'), 'FT', false),
			SearchFields_Ticket::FULLTEXT_NOTE_CONTENT => new DevblocksSearchField(self::FULLTEXT_NOTE_CONTENT, 'ftnc', 'content', $translate->_('message.note.content'), 'FT', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		$columns[self::FULLTEXT_MESSAGE_CONTENT]->ft_schema = Search_MessageContent::ID;
		$columns[self::FULLTEXT_NOTE_CONTENT]->ft_schema = Search_CommentContent::ID;
		
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
	
	function isReadableByWorker(Model_Worker $worker) {
		if(false == ($group = DAO_Group::get($this->group_id)))
			return false;
		
		return $group->isReadableByWorker($worker);
	}

	function getMessages() {
		$messages = DAO_Message::getMessagesByTicket($this->id);
		return $messages;
	}
	
	function getTimeline($is_ascending=true) {
		$timeline = $this->getMessages();
		
		if(false != ($comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $this->id)))
			$timeline = array_merge($timeline, $comments);
		
		usort($timeline, function($a, $b) use ($is_ascending) {
			if($a instanceof Model_Message) {
				$a_time = intval($a->created_date);
			} else if($a instanceof Model_Comment) {
				$a_time = intval($a->created);
			} else {
				$a_time = 0;
			}
			
			if($b instanceof Model_Message) {
				$b_time = intval($b->created_date);
			} else if($b instanceof Model_Comment) {
				$b_time = intval($b->created);
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
		$requesters = DAO_Ticket::getRequestersByTicket($this->id);
		return $requesters;
	}
	
	function getParticipants() {
		$results = DAO_Ticket::getParticipants($this->id);
		$participants = array();
		
		foreach($results as $row) {
			if(!isset($participants[$row['context']]))
				$participants[$row['context']] = array();

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
			SearchFields_Ticket::TICKET_LAST_WROTE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_GROUP_ID,
			SearchFields_Ticket::TICKET_BUCKET_ID,
			SearchFields_Ticket::TICKET_OWNER_ID,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Ticket::CONTEXT_LINK,
			SearchFields_Ticket::CONTEXT_LINK_ID,
			SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT,
			SearchFields_Ticket::FULLTEXT_NOTE_CONTENT,
			SearchFields_Ticket::REQUESTER_ADDRESS,
			SearchFields_Ticket::REQUESTER_ID,
			SearchFields_Ticket::TICKET_INTERESTING_WORDS,
			SearchFields_Ticket::TICKET_ORG_ID,
			SearchFields_Ticket::VIRTUAL_ATTACHMENT_NAME,
			SearchFields_Ticket::VIRTUAL_CONTACT_ID,
			SearchFields_Ticket::VIRTUAL_CONTEXT_LINK,
			SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,
			SearchFields_Ticket::VIRTUAL_HAS_ATTACHMENTS,
			SearchFields_Ticket::VIRTUAL_HAS_FIELDSET,
			SearchFields_Ticket::VIRTUAL_ORG_ID,
			SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID,
			SearchFields_Ticket::VIRTUAL_RECOMMENDATIONS,
			SearchFields_Ticket::TICKET_STATUS_ID,
			SearchFields_Ticket::VIRTUAL_WATCHERS,
			SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED,
			SearchFields_Ticket::VIRTUAL_WORKER_REPLIED,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Ticket::CONTEXT_LINK,
			SearchFields_Ticket::CONTEXT_LINK_ID,
			SearchFields_Ticket::REQUESTER_ID,
			SearchFields_Ticket::TICKET_ORG_ID,
			SearchFields_Ticket::TICKET_STATUS_ID,
			SearchFields_Ticket::VIRTUAL_CONTACT_ID,
			SearchFields_Ticket::VIRTUAL_ORG_ID,
			SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID,
			SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED,
			SearchFields_Ticket::VIRTUAL_WORKER_REPLIED,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		// [TODO] Only return IDs here
		$objects = DAO_Ticket::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
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
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Ticket::ORG_NAME:
				case SearchFields_Ticket::TICKET_FIRST_WROTE:
				case SearchFields_Ticket::TICKET_LAST_WROTE:
				case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				case SearchFields_Ticket::TICKET_SUBJECT:
				case SearchFields_Ticket::TICKET_GROUP_ID:
				case SearchFields_Ticket::TICKET_BUCKET_ID:
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
		$context = CerberusContexts::CONTEXT_TICKET;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Ticket::ORG_NAME:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::TICKET_SUBJECT:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$label_map = array(
					'' => 'Not trained',
					'S' => 'Spam',
					'N' => 'Not spam',
				);
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'options[]');
				break;
				
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$label_map = array(
					'0' => '(nobody)',
				);
				$workers = DAO_Worker::getAll();
				foreach($workers as $k => $v)
					$label_map[$k] = $v->getName();
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in', 'worker_id[]');
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
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	private function _getSubtotalDataForBuckets() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$fields = $this->getFields();
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		// Don't drill down to buckets (usability)
		if($this->hasParam(SearchFields_Ticket::TICKET_BUCKET_ID, $params, false)
			&& $this->hasParam(SearchFields_Ticket::TICKET_GROUP_ID, $params, false)) {
				$results = $this->findParam(SearchFields_Ticket::TICKET_BUCKET_ID, $params, false);
				
				if(is_array($results))
				foreach(array_keys($results) as $k)
					unset($params[$k]);
		}
		
		if(!method_exists('DAO_Ticket','getSearchQueryComponents'))
			return array();
		
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
		
		$results = $db->GetArraySlave($sql);

		return $results;
	}
	
	private function _getSubtotalCountForBuckets() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = array();
		$results = $this->_getSubtotalDataForBuckets();
		
		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		
		if(is_array($results))
		foreach($results as $result) {
			$group_id = $result['group_id'];
			$bucket_id = $result['bucket_id'];
			$hits = $result['hits'];

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
					'children' => array()
				);
			}
		}
		
		return $counts;
	}
	
	private function _getSubtotalCountForBucketsByGroup() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = array();
		$results = $this->_getSubtotalDataForBuckets();
		
		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		
		if(is_array($results))
		foreach($results as $result) {
			$group_id = $result['group_id'];
			$bucket_id = $result['bucket_id'];
			$hits = $result['hits'];

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
					'children' => array()
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
		foreach($counts as $group_id => $data) {
			uksort($counts[$group_id]['children'], array($this, '_sortByBucketOrder'));
		}
		
		return $counts;
	}
	
	protected function _getSubtotalDataForStatus($dao_class, $field_key) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$fields = $this->getFields();
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
			return array();
		
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
		
		$results = $db->GetArraySlave($sql);
		
		return $results;
	}
	
	protected function _getSubtotalCountForStatus() {
		$workers = DAO_Worker::getAll();
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = array();
		$results = $this->_getSubtotalDataForStatus('DAO_Ticket', SearchFields_Ticket::VIRTUAL_STATUS);

		$oper = DevblocksSearchCriteria::OPER_IN;
		
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
					'children' => array()
				);
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_Ticket::getFields();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$workers = DAO_Worker::getAllActive();
		$worker_names = array('me');
		array_walk($workers, function($worker) use (&$worker_names) {
			$worker_names[] = sprintf('"%s"', $worker->getName());
		});
		
		$group_names = DAO_Group::getNames($active_worker);
		$bucket_names = DAO_Bucket::getNames($active_worker);
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT),
				),
			'attachments.exist' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_HAS_ATTACHMENTS),
				),
			'attachments.name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_ATTACHMENT_NAME),
					'examples' => array(
						'*.html',
						'(*.png OR *.jpg)',
					),
			),
			'bucket' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_BUCKET_ID),
					'examples' => array_slice($bucket_names, 0, 15),
			),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT),
				),
			'contact.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_CONTACT_ID),
				),
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_CREATED_DATE),
				),
			'group' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_GROUP_ID),
					'examples' => array_slice($group_names, 0, 15),
			),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_ID),
				),
			'importance' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_IMPORTANCE),
				),
			'inGroupsOfWorker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER),
					'examples' => array_merge(array('me','current'),array_slice($worker_names, 0, 13)),
				),
			'mask' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_MASK, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
					'examples' => array(
						'ABC',
						'("XYZ-12345-678")',
					),
				),
			'msgs.content' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT),
				),
			'msgs.count' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_NUM_MESSAGES),
				),
			'msgs.notes' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Ticket::FULLTEXT_NOTE_CONTENT),
				),
			'org' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Ticket::ORG_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'org.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_ORG_ID),
				),
			'owner' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_OWNER_ID),
				),
			'participant' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Ticket::REQUESTER_ADDRESS),
				),
			'participant.id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID),
				),
			'recommended' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_RECOMMENDATIONS),
					'examples' => array_slice($worker_names, 0, 15),
				),
			'resolution.first' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST),
				),
			'response.first' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST),
				),
			'sender' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Ticket::TICKET_FIRST_WROTE),
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
			'status' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
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
					'options' => array('param_key' => SearchFields_Ticket::TICKET_UPDATED_DATE),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Ticket::VIRTUAL_WATCHERS),
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
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_TICKET, $fields, null);
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_ORG, $fields, 'org');
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_MessageContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['text']['examples'] = $ft_examples;
			$fields['msgs.content']['examples'] = $ft_examples;
		}
		
		// Engine/schema examples: Comments
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples)) {
			$fields['comments']['examples'] = $ft_examples;
			$fields['msgs.notes']['examples'] = $ft_examples;
		}
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'attachments.name':
				$field_key = SearchFields_Ticket::VIRTUAL_ATTACHMENT_NAME;
				$oper = null;
				$value = null;
				
				$param = DevblocksSearchCriteria::getTextParamFromTokens($field_key, $tokens);
				return $param;
				break;
			
			case 'bucket':
				$field_key = SearchFields_Ticket::TICKET_BUCKET_ID;
				$oper = null;
				$terms = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $terms);
				
				if(!is_array($terms))
					break;
				
				$buckets = DAO_Bucket::getAll();
				$bucket_ids = array();
				
				foreach($terms as $term) {
					if(is_numeric($term) && isset($buckets[$term])) {
						$bucket_ids[intval($term)] = true;
						
					} else {
						foreach($buckets as $bucket_id => $bucket) {
							if(isset($bucket_ids[$bucket_id]))
								continue;
							
							if(false !== stristr($bucket->name, $term)) {
								$bucket_ids[$bucket_id] = true;
							}
						}
					}
				}
				
				if(!empty($bucket_ids)) {
					return new DevblocksSearchCriteria(
						$field_key,
						$oper,
						array_keys($bucket_ids)
					);
				}
				
				return false;
				break;
				
			case 'group':
				$field_key = SearchFields_Ticket::TICKET_GROUP_ID;
				$oper = null;
				$terms = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $terms);
				
				if(!is_array($terms))
					break;
				
				$groups = DAO_Group::getAll();
				$group_ids = array();
				
				foreach($terms as $term) {
					if(is_numeric($term) && isset($groups[$term])) {
						$group_ids[intval($term)] = true;
						
					} else {
						foreach($groups as $group_id => $group) {
							if(isset($group_ids[$group_id]))
								continue;
							
							if(false !== stristr($group->name, $term)) {
								$group_ids[$group_id] = true;
							}
						}
					}
				}
				
				if(!empty($group_ids)) {
					return new DevblocksSearchCriteria(
						$field_key,
						$oper,
						array_keys($group_ids)
					);
				}
				
				return false;
				break;
				
			case 'inGroupsOfWorker':
				$field_key = SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER;
				$oper = null;
				$v = null;
				
				CerbQuickSearchLexer::getOperStringFromTokens($tokens, $oper, $v);
				
				$worker_id = 0;
				
				switch(strtolower($v)) {
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
						if(false != ($matches = DAO_Worker::getByString($v)) && !empty($matches))
							$worker_id = key($matches);
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

			// Alias
			case 'recipient':
				$field = 'participant';
				break;
			
			// [TODO] support 'current'
			case 'recommended':
				$field_key = SearchFields_Ticket::VIRTUAL_RECOMMENDATIONS;
				$oper = null;
				$patterns = array();
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $patterns);
				
				$active_worker = CerberusApplication::getActiveWorker();
				$workers = DAO_Worker::getAllActive();
				
				if(!is_array($patterns))
					break;
				
				if(count($patterns) == 1 && in_array($patterns[0],array('none','noone','nobody','no','false'))) {
					return new DevblocksSearchCriteria(
						$field_key,
						DevblocksSearchCriteria::OPER_IS_NULL,
						null
					);
					
				} elseif(count($patterns) == 1 && in_array($patterns[0],array('true','yes','anybody','any'))) {
					return new DevblocksSearchCriteria(
						$field_key,
						DevblocksSearchCriteria::OPER_IS_NOT_NULL,
						null
					);
					
				} else {
					$worker_ids = array();
					
					foreach($patterns as $pattern) {
						if($active_worker && 0 == strcasecmp($pattern, 'me')) {
							$worker_ids[$active_worker->id] = true;
							continue;
						}
						
						if(is_array($workers))
						foreach($workers as $worker) {
							if(false !== stristr(sprintf("%s %s", $worker->getName(), $worker->at_mention_name), $pattern)) {
								$worker_ids[$worker->id] = true;
								break;
							}
						}
					}
					
					if(empty($worker_ids))
						return null;
					
					return new DevblocksSearchCriteria(
						$field_key,
						$oper,
						array_keys($worker_ids)
					);
				}
				break;
			
			case 'resolution.first':
				$tokens = CerbQuickSearchLexer::getHumanTimeTokensAsNumbers($tokens);
				
				$field_key = SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST;
				return DevblocksSearchCriteria::getNumberParamFromTokens($field_key, $tokens);
				break;
				
			case 'response.first':
				$tokens = CerbQuickSearchLexer::getHumanTimeTokensAsNumbers($tokens);
				
				$field_key = SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST;
				return DevblocksSearchCriteria::getNumberParamFromTokens($field_key, $tokens);
				break;
				
			case 'spam.training':
				$field_key = SearchFields_Ticket::TICKET_SPAM_TRAINING;
				$oper = null;
				$states = array();
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $states);
				
				$values = array();
				
				// Normalize status labels
				foreach($states as $idx => $status) {
					switch(substr(strtolower($status), 0, 1)) {
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
				break;
			
			case 'status':
				$field_key = SearchFields_Ticket::VIRTUAL_STATUS;
				$oper = null;
				$value = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
				
				$values = array();
				
				// Normalize status labels
				foreach($value as $idx => $status) {
					switch(substr(strtolower($status), 0, 1)) {
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
				break;
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Ticket::VIRTUAL_WATCHERS, $tokens);
				break;
				
			case 'worker.commented':
				$search_fields = SearchFields_Ticket::getFields();
				return DevblocksSearchCriteria::getWorkerParamFromTokens(SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED, $tokens, $search_fields[SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED]);
				break;
				
			case 'worker.replied':
				$search_fields = SearchFields_Ticket::getFields();
				return DevblocksSearchCriteria::getWorkerParamFromTokens(SearchFields_Ticket::VIRTUAL_WORKER_REPLIED, $tokens, $search_fields[SearchFields_Ticket::VIRTUAL_WORKER_REPLIED]);
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$visit = CerberusApplication::getVisit();

		$results = self::getData();
		$tpl->assign('results', $results);
		
		@$ids = array_keys($results[0]);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);

		$sender_addresses = DAO_AddressOutgoing::getAll();
		$tpl->assign('sender_addresses', $sender_addresses);
		
		$custom_fields =
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET) +
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
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/views/options/ticket.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
			case SearchFields_Ticket::VIRTUAL_ATTACHMENT_NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;

			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_IMPORTANCE:
			case SearchFields_Ticket::TICKET_NUM_MESSAGES:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;

			case SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST:
			case SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__time_elapsed.tpl');
				break;
					
			case SearchFields_Ticket::VIRTUAL_HAS_ATTACHMENTS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_REOPEN_AT:
			case SearchFields_Ticket::TICKET_CLOSED_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$options = array(
					'N' => 'Not Spam',
					'S' => 'Spam',
					'' => 'Not Trained',
				);
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
				break;
				
			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				$tpl->display('devblocks:cerberusweb.core::tickets/search/criteria/ticket_spam_score.tpl');
				break;

			case SearchFields_Ticket::TICKET_GROUP_ID:
				$groups = DAO_Group::getAll();
				$tpl->assign('options', $groups);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
				break;
				
			case SearchFields_Ticket::TICKET_BUCKET_ID:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);

				$group_buckets = DAO_Bucket::getGroups();
				$tpl->assign('group_buckets', $group_buckets);

				$tpl->display('devblocks:cerberusweb.core::tickets/search/criteria/ticket_bucket.tpl');
				break;

			case SearchFields_Ticket::TICKET_OWNER_ID:
				$tpl->assign('opers', array(
					'in' => 'is',
					'not in' => 'is not',
					DevblocksSearchCriteria::OPER_IN_OR_NULL => 'is nobody or',
				));
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			case SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT:
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
			case SearchFields_Ticket::FULLTEXT_NOTE_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER:
				$tpl->assign('workers', DAO_Worker::getAllActive());
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__worker.tpl');
				break;
				
			case SearchFields_Ticket::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_Ticket::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_TICKET);
				break;
				
			case SearchFields_Ticket::VIRTUAL_RECOMMENDATIONS:
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__recommendations.tpl');
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
			case SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED:
			case SearchFields_Ticket::VIRTUAL_WORKER_REPLIED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				$translate = DevblocksPlatform::getTranslationService();
				
				$options = array(
					'open' => $translate->_('status.open'),
					'waiting' => $translate->_('status.waiting'),
					'closed' => $translate->_('status.closed'),
					'deleted' => $translate->_('status.deleted'),
				);
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
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

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			// [TODO] Handle NOT
			// [TODO] And on messages
			case SearchFields_Ticket::VIRTUAL_ATTACHMENT_NAME:
				$strings_or = array();

				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_EQ:
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_LIKE:
						$oper = 'is';
						break;
					case DevblocksSearchCriteria::OPER_NEQ:
					case DevblocksSearchCriteria::OPER_NIN:
					case DevblocksSearchCriteria::OPER_NOT_LIKE:
						$oper = 'is not';
						break;
					default:
						$oper = $param->operator;
						break;
				}
				
				if(is_array($param->value)) {
					foreach($param->value as $param_value) {
						$strings_or[] = sprintf("<b>%s</b>",
							DevblocksPlatform::strEscapeHtml($param_value)
						);
					}
				} else {
					$strings_or[] = sprintf("<b>%s</b>",
						DevblocksPlatform::strEscapeHtml($param->value)
					);
				}
				
				echo sprintf("Attachment name %s %s",
					DevblocksPlatform::strEscapeHtml($oper),
					implode(' or ', $strings_or)
				);
				break;
				
			case SearchFields_Ticket::VIRTUAL_HAS_ATTACHMENTS:
				if($param->value)
					echo "<b>Has</b> attachments";
				else
					echo "<b>Doesn't</b> have attachments";
				break;
			
			case SearchFields_Ticket::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Ticket::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
				
			case SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED:
				$strings_or = array();
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
				$strings_or = array();
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
				
			// [TODO] Handle long multiple value strings
			case SearchFields_Ticket::VIRTUAL_ORG_ID:
				$sep = ' or ';
				$strings = array();
				
				$ids = is_array($param->value) ? $param->value : array($param->value);
				$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
				
				$orgs = DAO_ContactOrg::getIds($ids);
				
				foreach($orgs as $org) {
					$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($org->name) . '</b>';
				}
				
				echo sprintf("Org is %s", implode($sep, $strings));
				break;
			
			// [TODO] Handle long multiple value strings
			case SearchFields_Ticket::VIRTUAL_CONTACT_ID:
				$sep = ' or ';
				$strings = array();
				
				$ids = is_array($param->value) ? $param->value : array($param->value);
				$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
				
				$contacts = DAO_Contact::getIds($ids);
				
				foreach($contacts as $contact) {
					$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($contact->getName()) . '</b>';
				}
				
				echo sprintf("Contact is %s", implode($sep, $strings));
				break;
			
			// [TODO] Handle long multiple value strings
			case SearchFields_Ticket::VIRTUAL_PARTICIPANT_ID:
				$sep = ' or ';
				$strings = array();
				
				$ids = is_array($param->value) ? $param->value : array($param->value);
				$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
				
				$addresses = DAO_Address::getIds($ids);
				
				foreach($addresses as $address) {
					$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($address->getNameWithEmail()) . '</b>';
				}
				
				echo sprintf("Participant is %s", implode($sep, $strings));
				break;
				
			case SearchFields_Ticket::VIRTUAL_RECOMMENDATIONS:
				switch($param->operator) {
					case DevblocksSearchCriteria::OPER_IS_NULL:
						echo "<b>Not recommended</b> to anybody";
						return;
						break;
						
					case DevblocksSearchCriteria::OPER_IS_NOT_NULL:
						echo "Recommended to <b>anybody</b>";
						return;
						break;
						
					case DevblocksSearchCriteria::OPER_EQ:
					case DevblocksSearchCriteria::OPER_NEQ:
					case DevblocksSearchCriteria::OPER_IN:
					case DevblocksSearchCriteria::OPER_NIN:
						$sep = ' or ';
						$strings = array();
						
						$workers = DAO_Worker::getAll();
						
						$ids = is_array($param->value) ? $param->value : array($param->value);
						
						foreach($ids as $id) {
							if(!is_numeric($id)) {
								$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($id) . '</b>';
								
							} elseif (is_numeric($id) && false != (@$worker = $workers[$id])) {
								$strings[] = '<b>' . DevblocksPlatform::strEscapeHtml($worker->getName()) . '</b>';
							}
						}
						
						if($param->operator == DevblocksSearchCriteria::OPER_NIN) {
							echo sprintf("Not recommended to %s", implode($sep, $strings));
						} else {
							echo sprintf("Recommended to %s", implode($sep, $strings));
						}
						break;
				}
				
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				if(!is_array($param->value))
					$param->value = array($param->value);
					
				$strings = array();
				
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
		$translate = DevblocksPlatform::getTranslationService();
		
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;
				
			case SearchFields_Ticket::TICKET_GROUP_ID:
				$groups = DAO_Group::getAll();
				$strings = array();

				foreach($values as $val) {
					if(!isset($groups[$val]))
						continue;

					$strings[] = DevblocksPlatform::strEscapeHtml($groups[$val]->name);
				}
				echo implode(", ", $strings);
				break;
					
			case SearchFields_Ticket::TICKET_BUCKET_ID:
				$buckets = DAO_Bucket::getAll();
				$strings = array();

				foreach($values as $val) {
					if(!isset($buckets[$val])) {
						continue;
						
					} else {
						if(false != ($group = $buckets[$val]->getGroup()))
							$strings[] = DevblocksPlatform::strEscapeHtml($group->name . ': ' . $buckets[$val]->name);
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$strings = array();
				
				if(!is_array($values))
					$values = array($values);
				
				if(is_array($values))
				foreach($values as $val) {
					switch($val) {
						case 'S':
							$strings[] = DevblocksPlatform::strEscapeHtml("Spam");
							break;
						case 'N':
							$strings[] = DevblocksPlatform::strEscapeHtml("Not Spam");
							break;
						default:
							$strings[] = DevblocksPlatform::strEscapeHtml("Not Trained");
							break;
					}
				}
				echo implode(", ", $strings);
				break;
				
			case SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST:
			case SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST:
				$sep = ' or ';
				$values = is_array($values) ? $values : array($values);
				
				foreach($values as &$value) {
					$value = DevblocksPlatform::strEscapeHtml(DevblocksPlatform::strSecsToString($value));
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
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			case SearchFields_Ticket::VIRTUAL_HAS_ATTACHMENTS:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_IMPORTANCE:
			case SearchFields_Ticket::TICKET_NUM_MESSAGES:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Ticket::TICKET_ELAPSED_RESPONSE_FIRST:
			case SearchFields_Ticket::TICKET_ELAPSED_RESOLUTION_FIRST:
				$now = time();
				@$then = intval(strtotime($value, $now));
				$value = $then - $now;
				
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Ticket::TICKET_CLOSED_AT:
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_REOPEN_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				@$score = DevblocksPlatform::importGPC($_REQUEST['score'],'integer',null);
				if(!is_null($score) && is_numeric($score)) {
					$criteria = new DevblocksSearchCriteria($field,$oper,$score/100);
				}
				break;

			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
			case SearchFields_Ticket::VIRTUAL_STATUS:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;

			case SearchFields_Ticket::TICKET_GROUP_ID:
				@$group_ids = DevblocksPlatform::importGPC($_REQUEST['options'],'array');

				// Groups
				if(!empty($group_ids)) {
					$this->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_GROUP_ID,$oper,$group_ids));
				} else {
					$this->removeParam(SearchFields_Ticket::TICKET_GROUP_ID);
				}
				break;
				
			case SearchFields_Ticket::TICKET_BUCKET_ID:
				@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['options'],'array');

				// Buckets
				if(!empty($bucket_ids)) {
					$this->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_BUCKET_ID,$oper,$bucket_ids));
				} else { // clear if no buckets provided
					$this->removeParam(SearchFields_Ticket::TICKET_BUCKET_ID);
				}
				break;
				
			case SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT:
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
			case SearchFields_Ticket::FULLTEXT_NOTE_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$criteria = $this->_doSetCriteriaWorker($field, $oper);
				break;
				
			case SearchFields_Ticket::VIRTUAL_ATTACHMENT_NAME:
				$criteria = new DevblocksSearchCriteria($field,$oper,explode(' OR ', $value));
				break;
				
			case SearchFields_Ticket::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'string','');
				$criteria = new DevblocksSearchCriteria($field, '=', $worker_id);
				break;

			case SearchFields_Ticket::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Ticket::VIRTUAL_RECOMMENDATIONS:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'string','');
				$oper = DevblocksSearchCriteria::OPER_IN;
				$criteria = new DevblocksSearchCriteria($field, $oper, array($worker_id));
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
			case SearchFields_Ticket::VIRTUAL_WORKER_COMMENTED:
			case SearchFields_Ticket::VIRTUAL_WORKER_REPLIED:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
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
		$view_last_actions = $visit->get(CerberusVisit::KEY_VIEW_LAST_ACTION,array());
	  
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
		$view_last_actions = $visit->get(CerberusVisit::KEY_VIEW_LAST_ACTION,array());
		return (isset($view_last_actions[$view_id]) ? $view_last_actions[$view_id] : null);
	}

	static public function clearLastActions() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION,array());
	}
};

class Context_Ticket extends Extension_DevblocksContext implements IDevblocksContextPeek, IDevblocksContextProfile, IDevblocksContextImport, IDevblocksContextAutocomplete {
	const ID = 'cerberusweb.contexts.ticket';
	
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->is_superuser)
				return TRUE;
				
			if(null == ($ticket = DAO_Ticket::get($context_id)))
				throw new Exception();
			
			return $worker->isGroupMember($ticket->group_id);
				
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	function getRandom() {
		return DAO_Ticket::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=ticket&id='.$context_id, true);
		return $url;
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
			$url_writer = DevblocksPlatform::getUrlService();
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
	
	function autocomplete($term) {
		$results = DAO_Ticket::autocomplete($term);
		$list = array();

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
	
	function getContext($ticket, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Ticket:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$workers = DAO_Worker::getAll();
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
			'reopen_date' => $prefix.$translate->_('ticket.reopen_at'),
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
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_TICKET;
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
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['url'] = $url_writer->writeNoProxy('c=profiles&type=ticket&id='.$ticket->mask,true);

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
		$merge_token_labels = array();
		$merge_token_values = array();
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
		$merge_token_labels = array();
		$merge_token_values = array();
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
		$merge_token_labels = array();
		$merge_token_values = array();
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
		$merge_token_labels = array();
		$merge_token_values = array();
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
		$merge_token_labels = array();
		$merge_token_values = array();
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
		$merge_token_labels = array();
		$merge_token_values = array();
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
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, null, $merge_token_labels, $merge_token_values, '', true);
		
			CerberusContexts::merge(
				'org_',
				$prefix.'Org:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
			
		// Plugin-provided tokens
		// [TODO]
		$token_extension_mfts = DevblocksPlatform::getExtensions('cerberusweb.template.token', false);
		foreach($token_extension_mfts as $mft) { /* @var $mft DevblocksExtensionManifest */
			@$token = $mft->params['token'];
			@$label = $mft->params['label'];
			@$bind = $mft->params['bind'][0];
			
			if(empty($token) || empty($label) || !is_array($bind))
				continue;

			if(!isset($bind['ticket']))
				continue;
				
			if(null != ($ext = $mft->createInstance()) && $ext instanceof ITemplateToken_Ticket) {
				/* @var $ext ITemplateToken_Signature */
				$value = $ext->getTicketTokenValue($worker);
				
				if(!empty($value)) {
					$token_labels[$token] = $label;
					$token_values[$token] = $value;
				}
			}
		}
		
		return true;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_TICKET;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'requester_emails':
				if(!isset($dictionary['requesters'])) {
					$result = $this->lazyLoadContextValues('requesters', $dictionary);
					$emails = array();
					
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
				$values['requesters'] = array();
				$reqs = DAO_Ticket::getRequestersByTicket($context_id);
				if(is_array($reqs))
				foreach($reqs as $req) { /* @var $req Model_Address */
					$values['requesters'][$req->id] = array(
						'email' => $req->email,
						'name' => $req->getName(),
						'contact_id' => $req->contact_id,
						'org_id' => $req->contact_org_id,
					);
				}
				break;
				
			case 'signature':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				
				if(false == ($active_worker = CerberusApplication::getActiveWorker()))
					break;
				
				if(!isset($dictionary['group_id']) || false == ($group = DAO_Group::get($dictionary['group_id'])))
					break;
				
				$values['signature'] = $group->getReplySignature(intval($dictionary['bucket_id']), $active_worker);
				break;
				
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
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
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	// Overload the search view for this context
	function getSearchView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($view = parent::getSearchView($view_id))) {
			$view->addParamsDefault(array(
				SearchFields_Ticket::VIRTUAL_STATUS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS,'in',array('open', 'waiting')),
				SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,'=',$active_worker->id),
			), true);
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
		$defaults->options = array(
			'disable_recommendations' => '1',
		);

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tickets';
		$view->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_WROTE,
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
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tickets';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Ticket::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Ticket::CONTEXT_LINK_ID,'=',$context_id),
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
		
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.mail.send'))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($edit)) {
			$tokens = explode(' ', trim($edit));
			
			foreach($tokens as $token) {
				@list($k,$v) = explode(':', $token);
				
				if($v)
				switch($k) {
					case 'to':
						$to = $v;
						break;
						
					case 'org.id':
						if(false != ($org = DAO_ContactOrg::get($v)))
							$tpl->assign('org', $org->name);
						break;
				}
			}
		}
		
		$tpl->assign('to', $to);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Buckets
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Preferences
		$defaults = array(
			'group_id' => DAO_WorkerPref::get($active_worker->id,'compose.group_id',0),
			'bucket_id' => DAO_WorkerPref::get($active_worker->id,'compose.bucket_id',0),
			'status' => DAO_WorkerPref::get($active_worker->id,'compose.status','waiting'),
		);
		
		// Continue a draft?
		if(!empty($draft_id)) {
			$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d AND %s = %s",
				DAO_MailQueue::ID,
				$draft_id,
				DAO_MailQueue::WORKER_ID,
				$active_worker->id,
				DAO_MailQueue::TYPE,
				Cerb_ORMHelper::qstr(Model_MailQueue::TYPE_COMPOSE)
			));
			
			@$draft = $drafts[$draft_id];
			
			if(!empty($drafts)) {
				$tpl->assign('draft', $draft);
				
				// Overload the defaults of the form
				if(isset($draft->params['group_id']))
					$defaults['group_id'] = $draft->params['group_id'];
				if(isset($draft->params['bucket_id']))
					$defaults['bucket_id'] = $draft->params['bucket_id'];
			}
		}
		
		// If we still don't have a default group, use the first group
		if(empty($defaults['group_id']))
			$defaults['group_id'] = key($groups);
		
		$tpl->assign('defaults', $defaults);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, false);
		$tpl->assign('custom_fields', $custom_fields);

		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::mail/section/compose/peek.tpl');
	}
	
	function _renderPeekTicketPopup($context_id, $view_id) {
		@$msgid = DevblocksPlatform::importGPC($_REQUEST['msgid'],'integer',0);
		@$edit_mode = DevblocksPlatform::importGPC($_REQUEST['edit'],'string',null);
		
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('view_id', $view_id);
		$tpl->assign('edit_mode', $edit_mode);

		$messages = array();
		
		if(null != ($ticket = DAO_Ticket::get($context_id))) {
			/* @var $ticket Model_Ticket */
			$tpl->assign('ticket', $ticket);
			
			$messages = $ticket->getMessages();
		}
		
		if(false == ($group = $ticket->getGroup()))
			return;

		// Permissions
		
		$active_worker = CerberusApplication::getActiveWorker();
		$translate = DevblocksPlatform::getTranslationService();
		
		// Check group membership ACL
		if(!$group->isReadableByWorker($active_worker)) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		// Template
		
		if(empty($context_id) || $edit_mode) {
			// Props
			$workers = DAO_Worker::getAllActive();
			$tpl->assign('workers', $workers);
			
			$groups = DAO_Group::getAll();
			$tpl->assign('groups', $groups);
			
			$buckets = DAO_Bucket::getAll();
			$tpl->assign('buckets', $buckets);
			
			// Watchers
			$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TICKET, array($ticket->id), CerberusContexts::CONTEXT_WORKER);
			$tpl->assign('object_watchers', $object_watchers);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket->id);
			if(isset($custom_field_values[$ticket->id]))
				$tpl->assign('custom_field_values', $custom_field_values[$ticket->id]);
			
			$tpl->display('devblocks:cerberusweb.core::tickets/peek_edit.tpl');
			
		} else {
			// Counts
			$activity_counts = array(
				'participants' => DAO_Address::countByTicketId($context_id),
				'messages' => DAO_Message::countByTicketId($context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			// Links
			$links = array(
				CerberusContexts::CONTEXT_TICKET => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							CerberusContexts::CONTEXT_TICKET,
							$context_id,
							array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Timeline
			$timeline_json = Page_Profiles::getTimelineJson($ticket->getTimeline());
			$tpl->assign('timeline_json', $timeline_json);
			
			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_TICKET)))
				return;
			
			// Dictionary
			$labels = array();
			$values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			// Template
			
			$tpl->display('devblocks:cerberusweb.core::tickets/peek.tpl');
		}
	}
	
	function importValidateSync($sync_fields) {
		if(!in_array('_id', $sync_fields) && !in_array('_mask', $sync_fields)) {
			return "ERROR: Either the 'ID' or 'Mask' field must be matched.";
		}
		
		return true;
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
				switch(strtolower($meta['virtual_fields']['_status'])) {
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
		
		$add_watchers = array();
		$remove_watchers = array();
		
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

	public $ticket_ids = array(); // key = ticket id, value=old value
	public $action = ''; // spam/closed/move, etc.
	public $action_params = array(); // DAO Actions Taken
};

class CerberusTicketSpamTraining { // [TODO] Append 'Enum' to class name?
	const BLANK = '';
	const NOT_SPAM = 'N';
	const SPAM = 'S';
};
