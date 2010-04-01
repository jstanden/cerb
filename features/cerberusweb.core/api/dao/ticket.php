<?php
class DAO_Ticket extends C4_ORMHelper {
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
	const LAST_ACTION_CODE = 'last_action_code';
	const LAST_WORKER_ID = 'last_worker_id';
	const NEXT_WORKER_ID = 'next_worker_id';
	
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
			'next_worker_id' => $translate->_('ticket.next_worker'),
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
		$ticket_id = $db->GetOne($sql); 

		// If we found a hit on a ticket record, return the ID
		if(!empty($ticket_id)) {
			return $ticket_id;
			
		// Check if this mask was previously forwarded elsewhere
		} else {
			$sql = sprintf("SELECT new_ticket_id FROM ticket_mask_forward WHERE old_mask = %s",
				$db->qstr($mask)
			);
			$ticket_id = $db->GetOne($sql);
			
			if(!empty($ticket_id))
				return $ticket_id;
		}

		// No match
		return null;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $mask
	 * return Model_Ticket
	 */
	static function getTicketByMask($mask) {
		if(null != ($id = self::getTicketIdByMask($mask))) {
			return self::getTicket($id);
		}
		
		return NULL;
	}
	
	static function getTicketByMessageId($message_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT t.id AS ticket_id, mh.message_id AS message_id ".
			"FROM message_header mh ".
			"INNER JOIN message m ON (m.id=mh.message_id) ".
			"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
			"WHERE mh.header_name = 'message-id' AND mh.header_value = %s",
			$db->qstr($message_id)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		if($row = mysql_fetch_assoc($rs)) {
			$ticket_id = intval($row['ticket_id']);
			$message_id = intval($row['message_id']);
			
			mysql_free_result($rs);
			
			return array(
				'ticket_id' => $ticket_id,
				'message_id' => $message_id
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
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		self::updateTicket($newId, $fields);
		
		// send new ticket auto-response
//		DAO_Mail::sendAutoresponse($id, 'new');
		
		return $newId;
	}

	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK ticket_mask_forward FROM ticket_mask_forward LEFT JOIN ticket ON ticket_mask_forward.new_ticket_id=ticket.id WHERE ticket.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' ticket_mask_forward records.');

		$sql = "DELETE QUICK ticket_comment FROM ticket_comment LEFT JOIN ticket ON ticket_comment.ticket_id=ticket.id WHERE ticket.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' ticket_comment records.');
		
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
		
		// ===========================================================================
		// Ophaned ticket custom fields
		$db->Execute("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN ticket ON (ticket.id=custom_field_stringvalue.source_id) WHERE custom_field_stringvalue.source_extension = 'cerberusweb.fields.source.ticket' AND ticket.id IS NULL");
		$db->Execute("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN ticket ON (ticket.id=custom_field_numbervalue.source_id) WHERE custom_field_numbervalue.source_extension = 'cerberusweb.fields.source.ticket' AND ticket.id IS NULL");
		$db->Execute("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN ticket ON (ticket.id=custom_field_clobvalue.source_id) WHERE custom_field_clobvalue.source_extension = 'cerberusweb.fields.source.ticket' AND ticket.id IS NULL");
	}
	
	static function merge($ids=array()) {
		if(!is_array($ids) || empty($ids) || count($ids) < 2) {
			return false;
		}
		
		$db = DevblocksPlatform::getDatabaseService();
			
		list($merged_tickets, $null) = self::search(
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
			$db->Execute($sql);
			
			// Requesters (merge)
			$sql = sprintf("INSERT IGNORE INTO requester (address_id,ticket_id) ".
				"SELECT address_id, %d FROM requester WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			$sql = sprintf("DELETE FROM requester WHERE ticket_id IN (%s)",
				implode(',', $merge_ticket_ids)
			);

			// Tasks
			$sql = sprintf("UPDATE task SET source_id = %d WHERE source_extension = %s AND source_id IN (%s)",
				$oldest_id,
				$db->qstr('cerberusweb.tasks.ticket'),
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);

			// Comments
			$sql = sprintf("UPDATE ticket_comment SET ticket_id = %d WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			DAO_Ticket::updateTicket($merge_ticket_ids, array(
				DAO_Ticket::IS_CLOSED => 1,
				DAO_Ticket::IS_DELETED => 1,
			));

			// Sort merge tickets by updated date ascending to find the latest touched
			$tickets = $merged_tickets;
			array_unshift($tickets, $oldest_ticket);
			uasort($tickets, create_function('$a, $b', "return strcmp(\$a[SearchFields_Ticket::TICKET_UPDATED_DATE],\$b[SearchFields_Ticket::TICKET_UPDATED_DATE]);\n"));
			$most_recent_updated_ticket = end($tickets);

			// Set our destination ticket to the latest touched details
			DAO_Ticket::updateTicket($oldest_id,array(
				DAO_Ticket::LAST_ACTION_CODE => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_LAST_ACTION_CODE], 
				DAO_Ticket::LAST_WROTE_ID => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_LAST_WROTE_ID], 
				DAO_Ticket::LAST_WORKER_ID => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_LAST_WORKER_ID], 
				DAO_Ticket::UPDATED_DATE => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_UPDATED_DATE]
			));			

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
				$db->Execute($sql);
				
				// If the old mask was a new_mask in a past life, change to its new destination
				$sql = sprintf("UPDATE ticket_mask_forward SET new_mask = %s, new_ticket_id = %d WHERE new_mask = %s",
					$db->qstr($new_mask),
					$oldest_id,
					$db->qstr($ticket[SearchFields_Ticket::TICKET_MASK])
				);
				$db->Execute($sql);
			}
			
			/*
			 * Notify anything that wants to know when tickets merge.
			 */
		    $eventMgr = DevblocksPlatform::getEventService();
		    $eventMgr->trigger(
		        new Model_DevblocksEvent(
		            'ticket.merge',
	                array(
	                    'new_ticket_id' => $oldest_id,
	                    'old_ticket_ids' => $merge_ticket_ids,
	                )
	            )
		    );
			
			return $oldest_id;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Ticket
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
	 * @return Model_Ticket[]
	 */
	static function getTickets($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$tickets = array();
		if(empty($ids)) return array();
		
		$sql = "SELECT t.id , t.mask, t.subject, t.is_waiting, t.is_closed, t.is_deleted, t.team_id, t.category_id, t.first_message_id, ".
			"t.first_wrote_address_id, t.last_wrote_address_id, t.created_date, t.updated_date, t.due_date, t.unlock_date, t.spam_training, ". 
			"t.spam_score, t.interesting_words, t.last_worker_id, t.next_worker_id ".
			"FROM ticket t ".
			(!empty($ids) ? sprintf("WHERE t.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY t.updated_date DESC"
		;
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		while($row = mysql_fetch_assoc($rs)) {
			$ticket = new Model_Ticket();
			$ticket->id = intval($row['id']);
			$ticket->mask = $row['mask'];
			$ticket->subject = $row['subject'];
			$ticket->first_message_id = intval($row['first_message_id']);
			$ticket->team_id = intval($row['team_id']);
			$ticket->category_id = intval($row['category_id']);
			$ticket->is_waiting = intval($row['is_waiting']);
			$ticket->is_closed = intval($row['is_closed']);
			$ticket->is_deleted = intval($row['is_deleted']);
			$ticket->last_wrote_address_id = intval($row['last_wrote_address_id']);
			$ticket->first_wrote_address_id = intval($row['first_wrote_address_id']);
			$ticket->created_date = intval($row['created_date']);
			$ticket->updated_date = intval($row['updated_date']);
			$ticket->due_date = intval($row['due_date']);
			$ticket->unlock_date = intval($row['unlock_date']);
			$ticket->spam_score = floatval($row['spam_score']);
			$ticket->spam_training = $row['spam_training'];
			$ticket->interesting_words = $row['interesting_words'];
			$ticket->last_worker_id = intval($row['last_worker_id']);
			$ticket->next_worker_id = intval($row['next_worker_id']);
			$tickets[$ticket->id] = $ticket;
		}
		
		mysql_free_result($rs);
		
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
	
	static function getRequestersByTicket($ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$addresses = array();
		
		$sql = sprintf("SELECT a.id , a.email ".
			"FROM address a ".
			"INNER JOIN requester r ON (r.ticket_id = %d AND a.id=r.address_id) ".
			"ORDER BY a.email ASC ",
			$ticket_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		 
		while($row = mysql_fetch_assoc($rs)) {
			$address = new Model_Address();
			$address->id = intval($row['id']);
			$address->email = $row['email'];
			$addresses[$address->id] = $address;
		}
		
		mysql_free_result($rs);

		return $addresses;
	}
	
	static function isTicketRequester($email, $ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT a.id ".
			"FROM address a ".
			"INNER JOIN requester r ON (r.ticket_id = %d AND a.id=r.address_id) ".
			"WHERE a.email = %s ".
			"ORDER BY a.email ASC ",
			$ticket_id,
			$db->qstr($email)
		);
		$result = $db->GetOne($sql);
		return !empty($result);
	}
	
	static function createRequester($address_id,$ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("REPLACE INTO requester (address_id, ticket_id) ".
			"VALUES (%d, %d)",
			$address_id,
			$ticket_id
		));
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
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
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
				
				(isset($tables['msg']) || isset($tables['mc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
				(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " "). // [TODO] Choose between first message and all?
				(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
				(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
				
				(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
		        "GROUP BY domain HAVING count(*) > 1 ".
		        "ORDER BY hits DESC ";
			
		    $rs = $db->SelectLimit($sql, $limit, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		    
			$domains = array(); // [TODO] Temporary
			while($row = mysql_fetch_assoc($rs)) {
		        $hash = md5('domain'.$row['domain']);
		        $domains[] = $row['domain']; // [TODO] Temporary
		        $tops[$hash] = array('domain',$row['domain'],$row['hits']);
		    }
		    
		    mysql_free_result($rs);
		    
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
				
				(isset($tables['msg']) || isset($tables['mc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
				(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " "). // [TODO] Choose between first message and all?
				(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
				(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
				
				(!empty($sender_wheres) ? sprintf("WHERE %s ",implode(' AND ',$sender_wheres)) : "").
		        "GROUP BY a1.email HAVING count(*) > 1 ".
		        "ORDER BY hits DESC ";
	
		    $rs = $db->SelectLimit($sql, $limit*2, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		    
		    while($row = mysql_fetch_assoc($rs)) {
		        $hash = md5('sender'.$row['email']);
		        $senders[$hash] = array('sender',$row['email'],$row['hits']);
		    }
		    
		    mysql_free_result($rs);
		    
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
				
				(isset($tables['msg']) || isset($tables['mc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
				(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " "). // [TODO] Choose between first message and all?
				(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
				(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
				
				(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
		        "GROUP BY substring(t.subject from 1 for 8) ".
		        "ORDER BY hits DESC ";
			
		    $rs = $db->SelectLimit($sql, $limit, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		    
			$prefixes = array(); // [TODO] Temporary

			while($row = mysql_fetch_assoc($rs)) {
		        $prefixes[] = $row['prefix'];
		    }
		    
		    mysql_free_result($rs);

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
					
					(isset($tables['msg']) || isset($tables['mc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
					(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " "). // [TODO] Choose between first message and all?
					(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
					(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
					
					(!empty($prefix_wheres) ? sprintf("WHERE %s ",implode(' AND ',$prefix_wheres)) : "").
			        "GROUP BY t.id, t.subject ";
		
				// [TODO] $limit here is completely arbitrary
			    $rs = $db->SelectLimit($sql, 2500, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
			    
			    $lines = array();
			    $subjects = array();
			    $patterns = array();
			    $subpatterns = array();
			    
			    while($row = mysql_fetch_assoc($rs)) {
			    	$lines[] = $row['subject'];
			    }
			    
			    $patterns = self::findPatterns($lines, 8);
			    $num_rows = mysql_num_rows($rs);
				mysql_free_result($rs);
			    
			    if(!empty($patterns)) {
			    	@$pattern = array_shift($patterns);
			        $tophash = md5('subject'.$pattern.'*');
			        $tops[$tophash] = array('subject',$pattern.'*',$num_rows);

			        if(!empty($patterns)) // thread subpatterns
			    	foreach($patterns as $hits => $pattern) {
				        $hash = md5('subject'.$pattern.'*');
				        $tops[$tophash][3][$hash] = array('subject',$pattern.'*',0);
				    }
			    }
			    
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
				
				(isset($tables['msg']) || isset($tables['mc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
				(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " "). // [TODO] Choose between first message and all?
				(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
				(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
				
				(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
		        "GROUP BY mh.header_value HAVING mh.header_value <> '' ".
		        "ORDER BY hits DESC ";
		    $rs = $db->SelectLimit($sql, 25, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		    
		    while($row = mysql_fetch_assoc($rs)) {
		        $hash = md5('header'.$row['header_value']);
		        $tops[$hash] = array('header',$row['header_value'],$row['hits'],array(),$mode_param);
		    }
		    
		    mysql_free_result($rs);
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
		$fields = SearchFields_Ticket::getFields();
		
		$total = -1;

		// Sanitize
		if(!isset($fields[$sortBy])) {
			$sortBy=null;
		}
		
        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
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
			"t.last_action_code as %s, ".
			"t.last_worker_id as %s, ".
			"t.next_worker_id as %s, ".
			"t.team_id as %s, ".
			"t.category_id as %s ",
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
			    SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			    SearchFields_Ticket::TICKET_LAST_WORKER_ID,
			    SearchFields_Ticket::TICKET_NEXT_WORKER_ID,
			    SearchFields_Ticket::TICKET_TEAM_ID,
			    SearchFields_Ticket::TICKET_CATEGORY_ID
			);

		$join_sql = 
			"FROM ticket t ".
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
			"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) ".
			// [JAS]: Dynamic table joins
			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id) " : " ").
			(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
			(isset($tables['msg']) || isset($tables['ftmc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
			(isset($tables['ftmc']) ? "INNER JOIN fulltext_message_content ftmc ON (ftmc.id=msg.id) " : " ").
			(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " ") // [TODO] Choose between first message and all?
			;
			
		// Org joins
		if(isset($tables['o'])) {
			$select_sql .= ", o.name as o_name ";
			$join_sql .= "LEFT JOIN contact_org o ON (a1.contact_org_id=o.id) ";
		}
			
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			't.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");

		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY t.id ' : '').
			$sort_sql;

		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($row[SearchFields_Ticket::TICKET_ID]);
			$results[$ticket_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				"SELECT COUNT(DISTINCT t.id) ".
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
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
	const TICKET_LAST_ACTION_CODE = 't_last_action_code';
	const TICKET_LAST_WORKER_ID = 't_last_worker_id';
	const TICKET_NEXT_WORKER_ID = 't_next_worker_id';
	const TICKET_TEAM_ID = 't_team_id';
	const TICKET_CATEGORY_ID = 't_category_id';
	
	const TICKET_MESSAGE_HEADER = 'mh_header_name';
    const TICKET_MESSAGE_HEADER_VALUE = 'mh_header_value';	

	// Sender
	const SENDER_ADDRESS = 'a1_address';
	
	// Requester
	const REQUESTER_ID = 'ra_id';
	const REQUESTER_ADDRESS = 'ra_email';
	
	// Sender Org
	const ORG_NAME = 'o_name';

	// Message Content
	const FULLTEXT_MESSAGE_CONTENT = 'ftmc_content';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 't', 'id', $translate->_('ticket.id')),
			self::TICKET_MASK => new DevblocksSearchField(self::TICKET_MASK, 't', 'mask', $translate->_('ticket.mask')),
			self::TICKET_SUBJECT => new DevblocksSearchField(self::TICKET_SUBJECT, 't', 'subject', $translate->_('ticket.subject')),
			
			self::TICKET_FIRST_MESSAGE_ID => new DevblocksSearchField(self::TICKET_FIRST_MESSAGE_ID, 't', 'first_message_id'),
			
			self::TICKET_FIRST_WROTE_ID => new DevblocksSearchField(self::TICKET_FIRST_WROTE_ID, 't', 'first_wrote_address_id'),
			self::TICKET_FIRST_WROTE => new DevblocksSearchField(self::TICKET_FIRST_WROTE, 'a1', 'email',$translate->_('ticket.first_wrote')),
			self::TICKET_LAST_WROTE_ID => new DevblocksSearchField(self::TICKET_LAST_WROTE_ID, 't', 'last_wrote_address_id'),
			self::TICKET_LAST_WROTE => new DevblocksSearchField(self::TICKET_LAST_WROTE, 'a2', 'email',$translate->_('ticket.last_wrote')),

			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'o', 'name', $translate->_('contact_org.name')),
			self::REQUESTER_ADDRESS => new DevblocksSearchField(self::REQUESTER_ADDRESS, 'ra', 'email',$translate->_('ticket.requester')),
			
			self::TICKET_TEAM_ID => new DevblocksSearchField(self::TICKET_TEAM_ID,'t','team_id',$translate->_('common.group')),
			self::TICKET_CATEGORY_ID => new DevblocksSearchField(self::TICKET_CATEGORY_ID, 't', 'category_id',$translate->_('common.bucket')),
			self::TICKET_CREATED_DATE => new DevblocksSearchField(self::TICKET_CREATED_DATE, 't', 'created_date',$translate->_('ticket.created')),
			self::TICKET_UPDATED_DATE => new DevblocksSearchField(self::TICKET_UPDATED_DATE, 't', 'updated_date',$translate->_('ticket.updated')),
			self::TICKET_WAITING => new DevblocksSearchField(self::TICKET_WAITING, 't', 'is_waiting',$translate->_('status.waiting')),
			self::TICKET_CLOSED => new DevblocksSearchField(self::TICKET_CLOSED, 't', 'is_closed',$translate->_('status.closed')),
			self::TICKET_DELETED => new DevblocksSearchField(self::TICKET_DELETED, 't', 'is_deleted',$translate->_('status.deleted')),

			self::TICKET_LAST_ACTION_CODE => new DevblocksSearchField(self::TICKET_LAST_ACTION_CODE, 't', 'last_action_code',$translate->_('ticket.last_action')),
			self::TICKET_LAST_WORKER_ID => new DevblocksSearchField(self::TICKET_LAST_WORKER_ID, 't', 'last_worker_id',$translate->_('ticket.last_worker')),
			self::TICKET_NEXT_WORKER_ID => new DevblocksSearchField(self::TICKET_NEXT_WORKER_ID, 't', 'next_worker_id',$translate->_('ticket.next_worker')),
			self::TICKET_SPAM_TRAINING => new DevblocksSearchField(self::TICKET_SPAM_TRAINING, 't', 'spam_training',$translate->_('ticket.spam_training')),
			self::TICKET_SPAM_SCORE => new DevblocksSearchField(self::TICKET_SPAM_SCORE, 't', 'spam_score',$translate->_('ticket.spam_score')),
			self::TICKET_FIRST_WROTE_SPAM => new DevblocksSearchField(self::TICKET_FIRST_WROTE_SPAM, 'a1', 'num_spam',$translate->_('address.num_spam')),
			self::TICKET_FIRST_WROTE_NONSPAM => new DevblocksSearchField(self::TICKET_FIRST_WROTE_NONSPAM, 'a1', 'num_nonspam',$translate->_('address.num_nonspam')),
			self::TICKET_INTERESTING_WORDS => new DevblocksSearchField(self::TICKET_INTERESTING_WORDS, 't', 'interesting_words',$translate->_('ticket.interesting_words')),
			self::TICKET_DUE_DATE => new DevblocksSearchField(self::TICKET_DUE_DATE, 't', 'due_date',$translate->_('ticket.due')),
			self::TICKET_UNLOCK_DATE => new DevblocksSearchField(self::TICKET_UNLOCK_DATE, 't', 'unlock_date', $translate->_('ticket.unlock_date')),
			self::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchField(self::TICKET_FIRST_CONTACT_ORG_ID, 'a1', 'contact_org_id'),
			
			self::REQUESTER_ID => new DevblocksSearchField(self::REQUESTER_ID, 'ra', 'id'),
			
			self::SENDER_ADDRESS => new DevblocksSearchField(self::SENDER_ADDRESS, 'a1', 'email'),
			
			self::TICKET_MESSAGE_HEADER => new DevblocksSearchField(self::TICKET_MESSAGE_HEADER, 'mh', 'header_name'),
			self::TICKET_MESSAGE_HEADER_VALUE => new DevblocksSearchField(self::TICKET_MESSAGE_HEADER_VALUE, 'mh', 'header_value'),
		);

		$tables = DevblocksPlatform::getDatabaseTables();
		if(isset($tables['fulltext_message_content'])) {
			$columns[self::FULLTEXT_MESSAGE_CONTENT] = new DevblocksSearchField(self::FULLTEXT_MESSAGE_CONTENT, 'ftmc', 'content', $translate->_('message.content'));
		}
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);

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

class Model_Ticket {
	public $id;
	public $mask;
	public $subject;
	public $is_waiting = 0;
	public $is_closed = 0;
	public $is_deleted = 0;
	public $team_id;
	public $category_id;
	public $first_message_id;
	public $first_wrote_address_id;
	public $last_wrote_address_id;
	public $created_date;
	public $updated_date;
	public $due_date;
	public $unlock_date;
	public $spam_score;
	public $spam_training;
	public $interesting_words;
	public $last_action_code;
	public $last_worker_id;
	public $next_worker_id;

	function Model_Ticket() {}

	function getMessages() {
		$messages = DAO_Message::getMessagesByTicket($this->id);
		return $messages;
	}

	function getRequesters() {
		$requesters = DAO_Ticket::getRequestersByTicket($this->id);
		return $requesters;
	}
};

class View_Ticket extends C4_AbstractView {
	const DEFAULT_ID = 'tickets_workspace';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Tickets';
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_SPAM_SCORE,
		);
	}

	function getData() {
		$objects = DAO_Ticket::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$view_path = APP_PATH . '/features/cerberusweb.core/templates/tickets/';
		$tpl->assign('view_path',$view_path);
		$tpl->assign('view', $this);

		$visit = CerberusApplication::getVisit();

		$results = self::getData();
		$tpl->assign('results', $results);
		
		@$ids = array_keys($results[0]);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);

		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Undo?
		$last_action = View_Ticket::getLastAction($this->id);
		$tpl->assign('last_action', $last_action);
		if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
			$tpl->assign('last_action_count', count($last_action->ticket_ids));
		}

		$tpl->assign('timestamp_now', time());
		
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . $view_path . 'ticket_view.tpl');
	}

	function doResetCriteria() {
		$active_worker = CerberusApplication::getActiveWorker(); /* @var $active_worker Model_Worker */
		$active_worker_memberships = $active_worker->getMemberships();
		
		$this->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
			SearchFields_Ticket::TICKET_TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'in',array_keys($active_worker_memberships)), // censor
		);
		$this->renderPage = 0;
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		$tpl_path = APP_PATH . '/features/cerberusweb.core/templates/';

		switch($field) {
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__string.tpl');
				break;

			case SearchFields_Ticket::TICKET_FIRST_WROTE_SPAM:
			case SearchFields_Ticket::TICKET_FIRST_WROTE_NONSPAM:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__number.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_WAITING:
			case SearchFields_Ticket::TICKET_DELETED:
			case SearchFields_Ticket::TICKET_CLOSED:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__bool.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_DUE_DATE:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__date.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_spam_training.tpl');
				break;
				
			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_spam_score.tpl');
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_last_action.tpl');
				break;

			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__worker.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_TEAM_ID:
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);

				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);

				$tpl->display('file:' . $tpl_path . 'tickets/search/criteria/ticket_team.tpl');
				break;

			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				$tpl->display('file:' . $tpl_path . 'internal/views/criteria/__fulltext.tpl');
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
			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
					$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
					continue;
					else
					$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TICKET_TEAM_ID:
				$teams = DAO_Group::getAll();
				$strings = array();

				foreach($values as $val) {
					if(!isset($teams[$val]))
					continue;

					$strings[] = $teams[$val]->name;
				}
				echo implode(", ", $strings);
				break;
					
			case SearchFields_Ticket::TICKET_CATEGORY_ID:
				$buckets = DAO_Bucket::getAll();
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "Inbox";
					} elseif(!isset($buckets[$val])) {
						continue;
					} else {
						$strings[] = $buckets[$val]->name;
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				$strings = array();

				foreach($values as $val) {
					switch($val) {
						case 'O':
							$strings[] = "New Ticket";
							break;
						case 'R':
							$strings[] = "Customer Reply";
							break;
						case 'W':
							$strings[] = "Worker Reply";
							break;
					}
				}
				echo implode(", ", $strings);
				break;

			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$strings = array();

				foreach($values as $val) {
					switch($val) {
						case 'S':
							$strings[] = "Spam";
							break;
						case 'N':
							$strings[] = "Not Spam";
							break;
						default:
							$strings[] = "Not Trained";
							break;
					}
				}
				echo implode(", ", $strings);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Ticket::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Ticket::TICKET_CATEGORY_ID]);
		unset($fields[SearchFields_Ticket::TICKET_UNLOCK_DATE]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Ticket::REQUESTER_ID]);
		unset($fields[SearchFields_Ticket::REQUESTER_ADDRESS]);
		unset($fields[SearchFields_Ticket::TICKET_UNLOCK_DATE]);
		unset($fields[SearchFields_Ticket::TICKET_INTERESTING_WORDS]);
		return $fields;
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;

			case SearchFields_Ticket::TICKET_WAITING:
			case SearchFields_Ticket::TICKET_DELETED:
			case SearchFields_Ticket::TICKET_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Ticket::TICKET_FIRST_WROTE_SPAM:
			case SearchFields_Ticket::TICKET_FIRST_WROTE_NONSPAM:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_DUE_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from) || (!is_numeric($from) && @false === strtotime(str_replace('.','-',$from))))
					$from = 0;
					
				if(empty($to) || (!is_numeric($to) && @false === strtotime(str_replace('.','-',$to))))
					$to = 'now';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;

			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				@$score = DevblocksPlatform::importGPC($_REQUEST['score'],'integer',null);
				if(!is_null($score) && is_numeric($score)) {
					$criteria = new DevblocksSearchCriteria($field,$oper,intval($score)/100);
				}
				break;

			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				@$last_action_code = DevblocksPlatform::importGPC($_REQUEST['last_action'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$last_action_code);
				break;

			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
				

			case SearchFields_Ticket::TICKET_TEAM_ID:
				@$team_ids = DevblocksPlatform::importGPC($_REQUEST['team_id'],'array');
				@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'array');

				if(!empty($team_ids))
				$this->params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,$oper,$team_ids);
				if(!empty($bucket_ids))
				$this->params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,$oper,$bucket_ids);

				break;
				
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

	/**
	 * @param array
	 * @param array
	 * @return boolean
	 * [TODO] Find a better home for this?
	 */
	function doBulkUpdate($filter, $filter_param, $data, $do, $ticket_ids=array()) {
		@set_time_limit(600);
	  
		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ticket_ids))
			return;
		
		$rule = new Model_GroupInboxFilter();
		$rule->actions = $do;
	  
		$params = $this->params;

		if(empty($filter)) {
			$data[] = '*'; // All, just to permit a loop in foreach($data ...)
		}

		switch($filter) {
			default:
			case 'subject':
			case 'sender':
			case 'header':
				if(is_array($data))
				foreach($data as $v) {
					$new_params = array();
					$do_header = null;
		    
					switch($filter) {
						case 'subject':
							$new_params = array(
								new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SUBJECT,DevblocksSearchCriteria::OPER_LIKE,$v)
							);
							$do_header = 'subject';
							$ticket_ids = array();
							break;
						case 'sender':
							$new_params = array(
								new DevblocksSearchCriteria(SearchFields_Ticket::SENDER_ADDRESS,DevblocksSearchCriteria::OPER_LIKE,$v)
							);
							$do_header = 'from';
							$ticket_ids = array();
							break;
						case 'header':
							$new_params = array(
								// [TODO] It will eventually come up that we need multiple header matches (which need to be pair grouped as OR)
								new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_HEADER,DevblocksSearchCriteria::OPER_EQ,$filter_param),
								new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_HEADER_VALUE,DevblocksSearchCriteria::OPER_EQ,$v)
							);
							$ticket_ids = array();
							break;
					}

					$new_params = array_merge($new_params, $params);
					$pg = 0;

					if(empty($ticket_ids)) {
						do {
							list($tickets,$null) = DAO_Ticket::search(
								array(),
								$new_params,
								100,
								$pg++,
								SearchFields_Ticket::TICKET_ID,
								true,
								false
							);
							 
							$ticket_ids = array_merge($ticket_ids, array_keys($tickets));
							 
						} while(!empty($tickets));
					}
			   
					$batch_total = count($ticket_ids);
					for($x=0;$x<=$batch_total;$x+=200) {
						$batch_ids = array_slice($ticket_ids,$x,200);
						$rule->run($batch_ids);
						unset($batch_ids);
					}
				}

				break;
		}

		unset($ticket_ids);
	}

	static function createSearchView() {
		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();
		$translate = DevblocksPlatform::getTranslationService();
		
		$view = new View_Ticket();
		$view->id = CerberusApplication::VIEW_SEARCH;
		$view->name = $translate->_('common.search_results');
		$view->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_SPAM_SCORE,
		);
		$view->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,DevblocksSearchCriteria::OPER_EQ,0),
			SearchFields_Ticket::TICKET_TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'in',array_keys($memberships)), // censor
		);
		$view->renderLimit = 100;
		$view->renderPage = 0;
		$view->renderSortBy = null; // SearchFields_Ticket::TICKET_UPDATED_DATE
		$view->renderSortAsc = 0;

		return $view;
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