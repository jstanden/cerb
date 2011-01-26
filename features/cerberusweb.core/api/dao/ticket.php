<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
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
	const LAST_MESSAGE_ID = 'last_message_id';
	const LAST_WROTE_ID = 'last_wrote_address_id';
	const FIRST_WROTE_ID = 'first_wrote_address_id';
	const CREATED_DATE = 'created_date';
	const UPDATED_DATE = 'updated_date';
	const DUE_DATE = 'due_date';
	const SPAM_TRAINING = 'spam_training';
	const SPAM_SCORE = 'spam_score';
	const INTERESTING_WORDS = 'interesting_words';
	const LAST_ACTION_CODE = 'last_action_code';
	
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
			return self::get($id);
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
	 */
	static function createTicket($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO ticket (created_date, updated_date) ".
			"VALUES (%d,%d)",
			time(),
			time()
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId(); 
		
		self::update($id, $fields);
		
		return $id;
	}

	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK ticket_mask_forward FROM ticket_mask_forward LEFT JOIN ticket ON ticket_mask_forward.new_ticket_id=ticket.id WHERE ticket.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' ticket_mask_forward records.');

		$sql = "DELETE QUICK requester FROM requester LEFT JOIN ticket ON requester.ticket_id = ticket.id WHERE ticket.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' requester records.');
		
		// Context Links
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN ticket ON context_link.from_context_id=ticket.id WHERE context_link.from_context = %s AND ticket.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_TICKET)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' ticket context link sources.');
		
		$db->Execute(sprintf("DELETE QUICK context_link FROM context_link LEFT JOIN ticket ON context_link.to_context_id=ticket.id WHERE context_link.to_context = %s AND ticket.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_TICKET)
		));
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' ticket context link targets.');
		
		
		// Recover any tickets assigned to a NULL bucket
		$sql = "UPDATE ticket LEFT JOIN category ON ticket.category_id = category.id SET ticket.category_id = 0 WHERE ticket.category_id > 0 AND category.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Fixed ' . $db->Affected_Rows() . ' tickets in missing buckets.');
		
		// ===========================================================================
		// Ophaned ticket custom fields
		$db->Execute(sprintf("DELETE QUICK custom_field_stringvalue FROM custom_field_stringvalue LEFT JOIN ticket ON (ticket.id=custom_field_stringvalue.context_id) WHERE custom_field_stringvalue.context = %s AND ticket.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_TICKET)
		));
		$db->Execute(sprintf("DELETE QUICK custom_field_numbervalue FROM custom_field_numbervalue LEFT JOIN ticket ON (ticket.id=custom_field_numbervalue.context_id) WHERE custom_field_numbervalue.context = %s AND ticket.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_TICKET)
		));
		$db->Execute(sprintf("DELETE QUICK custom_field_clobvalue FROM custom_field_clobvalue LEFT JOIN ticket ON (ticket.id=custom_field_clobvalue.context_id) WHERE custom_field_clobvalue.context = %s AND ticket.id IS NULL",
			$db->qstr(CerberusContexts::CONTEXT_TICKET)
		));
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
			$sql = sprintf("INSERT IGNORE INTO requester (address_id, ticket_id) ".
				"SELECT address_id, %d FROM requester WHERE ticket_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			$sql = sprintf("DELETE FROM requester WHERE ticket_id IN (%s)",
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);

			// Context Links
			
			$db->Execute(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
				"SELECT 'cerberusweb.contexts.ticket', %d, to_context, to_context_id ".
				"FROM context_link WHERE from_context = 'cerberusweb.contexts.ticket' AND from_context_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			));
			
			$db->Execute(sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
				"SELECT from_context, from_context_id, 'cerberusweb.contexts.ticket', %d ".
				"FROM context_link WHERE to_context = 'cerberusweb.contexts.ticket' AND to_context_id IN (%s)",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			));
			
			$db->Execute(sprintf("DELETE FROM context_link ".
				"WHERE (from_context = 'cerberusweb.contexts.ticket' AND from_context_id IN (%s)) ".
				"OR (to_context = 'cerberusweb.contexts.ticket' AND to_context_id IN (%s))",
				implode(',', $merge_ticket_ids),
				implode(',', $merge_ticket_ids)
			));
			
			// Comments
			$sql = sprintf("UPDATE comment SET context_id = %d WHERE context = %s AND context_id IN (%s)",
				$oldest_id,
				$db->qstr(CerberusContexts::CONTEXT_TICKET),
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
			DAO_Ticket::update($merge_ticket_ids, array(
				DAO_Ticket::IS_CLOSED => 1,
				DAO_Ticket::IS_DELETED => 1,
				DAO_Ticket::DUE_DATE => 0,
			));

			// Sort merge tickets by updated date ascending to find the latest touched
			$tickets = $merged_tickets;
			array_unshift($tickets, $oldest_ticket);
			uasort($tickets, create_function('$a, $b', "return strcmp(\$a[SearchFields_Ticket::TICKET_UPDATED_DATE],\$b[SearchFields_Ticket::TICKET_UPDATED_DATE]);\n"));
			$most_recent_updated_ticket = end($tickets);

			// Set our destination ticket to the latest touched details
			DAO_Ticket::update($oldest_id,array(
				DAO_Ticket::LAST_ACTION_CODE => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_LAST_ACTION_CODE], 
				DAO_Ticket::LAST_MESSAGE_ID => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_LAST_MESSAGE_ID], 
				DAO_Ticket::LAST_WROTE_ID => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_LAST_WROTE_ID], 
				DAO_Ticket::UPDATED_DATE => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_UPDATED_DATE],
				DAO_Ticket::IS_CLOSED => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_CLOSED],
				DAO_Ticket::IS_WAITING => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_WAITING],
				DAO_Ticket::IS_DELETED => $most_recent_updated_ticket[SearchFields_Ticket::TICKET_DELETED],
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

		$messages = $ticket->getMessages();
		$first_message = reset($messages);
		$last_message = end($messages);
		
		// If no messages, delete the ticket
		if(empty($first_message) && empty($last_message)) {
			DAO_Ticket::update($id, array(
				DAO_Ticket::IS_WAITING => 0,
				DAO_Ticket::IS_CLOSED => 1,
				DAO_Ticket::IS_DELETED => 1,
			));
			
			return FALSE;
		}
			
		// Reindex the first message
		if($first_message) {
			DAO_Ticket::update($id, array(
				DAO_Ticket::FIRST_MESSAGE_ID => $first_message->id,
				DAO_Ticket::FIRST_WROTE_ID => $first_message->address_id
			));
		}
		
		// Reindex the last message
		if($last_message) {
			DAO_Ticket::update($id, array(
				DAO_Ticket::LAST_MESSAGE_ID => $last_message->id,
				DAO_Ticket::LAST_WROTE_ID => $last_message->address_id
			));
		}
		
		return TRUE;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Ticket
	 */
	static function get($id) {
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
		
		$sql = "SELECT t.id , t.mask, t.subject, t.is_waiting, t.is_closed, t.is_deleted, t.team_id, t.category_id, t.first_message_id, t.last_message_id, ".
			"t.first_wrote_address_id, t.last_wrote_address_id, t.created_date, t.updated_date, t.due_date, t.spam_training, ". 
			"t.spam_score, t.interesting_words ".
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
			$ticket->last_message_id = intval($row['last_message_id']);
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
			$ticket->spam_score = floatval($row['spam_score']);
			$ticket->spam_training = $row['spam_training'];
			$ticket->interesting_words = $row['interesting_words'];
			$tickets[$ticket->id] = $ticket;
		}
		
		mysql_free_result($rs);
		
		return $tickets;
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('ticket', $fields, $where);
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids))
			$ids = array($ids);
		
		/*
		 * Make a diff for the requested objects in batches
		 */
    	$chunks = array_chunk($ids, 25, true);
    	while($ids = array_shift($chunks)) {
	    	$objects = DAO_Ticket::getTickets($ids);
	    	$object_changes = array();
	    	
	    	foreach($objects as $object_id => $object) {
	    		$pre_fields = get_object_vars($object);
	    		$changes = array();
	    		
	    		foreach($fields as $field_key => $field_val) {
	    			// Make sure the value of the field actually changed
	    			if($pre_fields[$field_key] != $field_val) {
	    				$changes[$field_key] = array('from' => $pre_fields[$field_key], 'to' => $field_val);
	    			}
	    		}
	    		
	    		// If we had changes
	    		if(!empty($changes)) {
	    			$object_changes[$object_id] = array(
	    				'model' => array_merge($pre_fields, $fields),
	    				'changes' => $changes,
	    			);
	    		}
	    	}
	    	
	    	/*
	    	 * Make the changes
	    	 */
	        parent::_update($ids, 'ticket', $fields);
	
	        /*
	         * Trigger an event about the changes
	         */
	    	if(!empty($object_changes)) {
			    $eventMgr = DevblocksPlatform::getEventService();
			    $eventMgr->trigger(
			        new Model_DevblocksEvent(
			            'dao.ticket.update',
		                array(
		                    'objects' => $object_changes,
		                )
		            )
			    );
	    	}
    	}
	}
	
	static function getRequestersByTicket($ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$addresses = array();
		
		$sql = sprintf("SELECT a.id , a.email, a.first_name, a.last_name ".
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
			$address->first_name = $row['first_name'];
			$address->last_name = $row['last_name'];
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
	
	static function createRequester($raw_email, $ticket_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$helpdesk_senders = CerberusApplication::getHelpdeskSenders();

		if(null == ($address = CerberusApplication::hashLookupAddress($raw_email, true))) {
			$logger->warn(sprintf("[Parser] %s is a malformed requester e-mail address.", $raw_email));
			return false;
		}
		
		// Don't add a requester if the sender is a helpdesk address
		if(isset($helpdesk_senders[$address->email])) {
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
		
		$db->Execute(sprintf("REPLACE INTO requester (address_id, ticket_id) ".
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

        $sql = sprintf("DELETE QUICK FROM requester WHERE ticket_id = %d AND address_id = %d",
            $id,
            $address_id
        );
        $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	}
	
	static function analyze($params, $limit=15, $mode="senders", $mode_param=null) { // or "subjects"
		$db = DevblocksPlatform::getDatabaseService();
		
		$tops = array();
		
		if($mode=="senders") {
			$query_parts = DAO_Ticket::getSearchQueryComponents(
				array(),
				$params,
				null,
				null
			);
			
			// Overload
			$join_sql = $query_parts['join'];
			$where_sql = $query_parts['where'];
			
			$senders = array();

			// [JAS]: Most common sender domains in work pile
			$sql = "SELECT COUNT(*) AS hits, SUBSTRING(a1.email FROM POSITION('@' IN a1.email)) AS domain ".
				$join_sql.
				$where_sql.
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
		    
			// [JAS]: Most common senders in work pile
			$sql = "SELECT count(*) AS hits, a1.email ".
				$join_sql.
				$where_sql . 
					sprintf(" AND SUBSTRING(a1.email FROM POSITION('@' IN a1.email)) IN ('%s')",
		        		implode("','", $domains)
		    		).
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
			$query_parts = DAO_Ticket::getSearchQueryComponents(
				array(),
				$params,
				null,
				null
			);
			
			// Overload
			$join_sql = $query_parts['join'];
			$where_sql = $query_parts['where'];
			
			$prefixes = array();
			
			$sql = "SELECT COUNT(*) AS hits, SUBSTRING(t.subject FROM 1 FOR 8) AS prefix ".
				$join_sql.
				$where_sql.
		        "GROUP BY SUBSTRING(t.subject FROM 1 FOR 8) HAVING hits > 1 ".
		        "ORDER BY hits DESC ";
		    $rs = $db->SelectLimit($sql, $limit, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		    
			$prefixes = array(); // [TODO] Temporary

			while($row = mysql_fetch_assoc($rs)) {
		        $prefixes[] = $row['prefix'];
		    }
		    
		    mysql_free_result($rs);

		    foreach($prefixes as $prefix_idx => $prefix) {
				// [JAS]: Most common subjects in work pile
				$sql = "SELECT COUNT(t.id) AS hits, t.subject ".
					$join_sql.
					$where_sql.
						sprintf(" AND t.subject LIKE %s ",
			        		$db->qstr($prefix.'%')
			    		).
			        "GROUP BY t.subject HAVING hits > 1 ".
			        "ORDER BY hits DESC ";
		    	
			    $rs = $db->SelectLimit($sql, 15, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
			    $num_rows = mysql_num_rows($rs);

			    $lines = array();
			    while($row = mysql_fetch_assoc($rs)) {
			    	$lines[$row['subject']] = $row['hits'];
			    }
			    
			    $prefix = self::findLongestCommonPrefix($lines);
			    
			    if(strlen(key($prefix)) < 8)
			    	continue;

		        if(count($lines) > 1) {
        	        $tophash = md5('subject'.key($prefix).'*');
			        $tops[$tophash] = array('subject',key($prefix).'*',current($prefix));
		        	
			        foreach($lines as $line => $hits) {
			        	$hash = md5('subject'.$line);
			        	$tops[$tophash][3][$hash] = array('subject',$line,$hits);
			        }
			        
		        } else {
			        $tophash = md5('subject'.key($prefix));
			        $tops[$tophash] = array('subject',key($prefix),current($prefix));
		        }
		        
				mysql_free_result($rs);
		    }

		} elseif ($mode=="headers") {
			$params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_HEADER,'=',$mode_param);
			
			$query_parts = DAO_Ticket::getSearchQueryComponents(
				array(),
				$params,
				null,
				null
			);
			
			// Overload
			$join_sql = $query_parts['join'];
			$where_sql = $query_parts['where'];
			
			$sql = "SELECT count(t.id) as hits, mh.header_value ".
				$join_sql.
				$where_sql.
				"GROUP BY mh.header_value HAVING mh.header_value != '' ".
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

	private function findLongestCommonPrefix($list) {
		// Find the longest subject line
		$subjects = array_keys($list);
		usort($subjects, array('DAO_Ticket','sortByLen'));
		$longest_item = reset($subjects);
		unset($subjects);
		
		// Find the optimal similar prefix
		$positions = array();
		foreach($list as $subject => $hits) {
			$x = 0;
			while(isset($longest_item[$x]) && isset($subject[$x]) && $longest_item[$x]==$subject[$x]) {
				if(!isset($positions[$x]))
					$positions[$x] = 0;
					
				$positions[$x] += $hits;
				$x++;
			}
		}
		
		// Find the positions with the most hits
		arsort($positions);
		
		// Only keep positions tied for first
		foreach($positions as $k=>$v)
			if($positions[$k] != $positions[0])
				unset($positions[$k]);
		
		// And find the highest position
		krsort($positions);
		reset($positions);

		$results = array(
			substr($longest_item,0,key($positions)+1) => current($positions),
		);
		
		return $results;
	}
	
	// Sort by strlen (longest to shortest)
	private function sortByLen($a,$b) {
		$asize = strlen($a);
		$bsize = strlen($b);
		if($asize==$bsize) return 0;
		return ($asize>$bsize)?-1:1;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Ticket::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
			$sortBy=null;
		
        list($tables, $wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
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
			"t.last_message_id as %s, ".
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
			    SearchFields_Ticket::TICKET_LAST_MESSAGE_ID,
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
			    SearchFields_Ticket::TICKET_TEAM_ID,
			    SearchFields_Ticket::TICKET_CATEGORY_ID
		);

		$join_sql = 
			"FROM ticket t ".
			"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
			"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) ".
			// [JAS]: Dynamic table joins
			((isset($tables['r']) || isset($tables['ra'])) ? "INNER JOIN requester r ON (r.ticket_id=t.id) " : " ").
			(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
			(isset($tables['msg']) || isset($tables['ftmc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
			(isset($tables['ftmc']) ? "INNER JOIN fulltext_message_content ftmc ON (ftmc.id=msg.id) " : " ").
			(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " "). // [TODO] Choose between first message and all?
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.ticket' AND context_link.to_context_id = t.id) " : " ")
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
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");

		// Virtuals
		foreach($params as $param) {
			$param_key = $param->field;
			settype($param_key, 'string');

			switch($param_key) {
				case SearchFields_Ticket::VIRTUAL_WORKERS:
					$has_multiple_values = true;
					if(empty($param->value)) { // empty
						if(DevblocksSearchCriteria::OPER_NIN == $param->operator || DevblocksSearchCriteria::OPER_NEQ == $param->operator) {
							$join_sql .= "LEFT JOIN context_link AS context_owner ON (context_owner.from_context = 'cerberusweb.contexts.ticket' AND context_owner.from_context_id = t.id AND context_owner.to_context = 'cerberusweb.contexts.worker') ";
							$where_sql .= "AND context_owner.to_context_id IS NOT NULL ";
						} else {
							$join_sql .= "LEFT JOIN context_link AS context_owner ON (context_owner.from_context = 'cerberusweb.contexts.ticket' AND context_owner.from_context_id = t.id AND context_owner.to_context = 'cerberusweb.contexts.worker') ";
							$where_sql .= "AND context_owner.to_context_id IS NULL ";
						}
					} else {
						$join_sql .= sprintf("INNER JOIN context_link AS context_owner ON (context_owner.from_context = 'cerberusweb.contexts.ticket' AND context_owner.from_context_id = t.id AND context_owner.to_context = 'cerberusweb.contexts.worker' AND context_owner.to_context_id IN (%s)) ",
							implode(',', $param->value)
						);
					}
					break;
					
				case SearchFields_Ticket::VIRTUAL_ASSIGNABLE:
					$assignable_buckets = DAO_Bucket::getAssignableBuckets();
					$assignable_bucket_ids = array_keys($assignable_buckets);
					array_unshift($assignable_bucket_ids, 0);
					if($param->value) { // true
						$where_sql .= sprintf("AND t.category_id IN (%s) ", implode(',', $assignable_bucket_ids));	
					} else { // false
						$where_sql .= sprintf("AND t.category_id NOT IN (%s) ", implode(',', $assignable_bucket_ids));	
					}
					break;
					
				case SearchFields_Ticket::VIRTUAL_STATUS:
					$values = $param->value;
					if(!is_array($values))
						$values = array($values);
						
					$status_sql = array();
					
					foreach($values as $value) {
						switch($value) {
							case 'open':
								$status_sql[] = "(t.is_waiting = 0 AND t.is_closed = 0)";
								break;
							case 'waiting':
								$status_sql[] = "(t.is_waiting = 1 AND t.is_closed = 0)";
								break;
							case 'closed':
								$status_sql[] = "(t.is_closed = 1 AND t.is_deleted=0)";
								break;
							case 'deleted':
								$status_sql[] = "(t.is_closed = 1 AND t.is_deleted=1)";
								break;
						}
					}
					
					if(empty($status_sql))
						break;
					
					$where_sql .= 'AND (' . implode(' OR ', $status_sql) . ') ';
					break;
			}
		}

		// Fulltext has multiple values
		if(isset($tables['ftmc']))
			$has_multiple_values = true; 		

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

		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_Ticket::TICKET_ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				(($has_multiple_values) ? "SELECT COUNT(DISTINCT t.id) " : "SELECT COUNT(t.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results, $total);
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
	const TICKET_LAST_MESSAGE_ID = 't_last_message_id';
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
	const TICKET_SPAM_SCORE = 't_spam_score';
	const TICKET_SPAM_TRAINING = 't_spam_training';
	const TICKET_INTERESTING_WORDS = 't_interesting_words';
	const TICKET_LAST_ACTION_CODE = 't_last_action_code';
	const TICKET_TEAM_ID = 't_team_id';
	const TICKET_CATEGORY_ID = 't_category_id';
	
	const TICKET_MESSAGE_HEADER = 'mh_header_name';
    const TICKET_MESSAGE_HEADER_VALUE = 'mh_header_value';	

	// Sender
	const SENDER_ADDRESS = 'a1_address';
	
	// Requester
	const REQUESTER_ID = 'r_id';
	const REQUESTER_ADDRESS = 'ra_email';
	
	// Sender Org
	const ORG_NAME = 'o_name';

	// Message Content
	const FULLTEXT_MESSAGE_CONTENT = 'ftmc_content';
	
	// Context Links
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	// Virtuals
	const VIRTUAL_ASSIGNABLE = '*_assignable';
	const VIRTUAL_STATUS = '*_status';
	const VIRTUAL_WORKERS = '*_workers';
	
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
			self::TICKET_LAST_MESSAGE_ID => new DevblocksSearchField(self::TICKET_LAST_MESSAGE_ID, 't', 'last_message_id'),
			
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
			self::TICKET_SPAM_TRAINING => new DevblocksSearchField(self::TICKET_SPAM_TRAINING, 't', 'spam_training',$translate->_('ticket.spam_training')),
			self::TICKET_SPAM_SCORE => new DevblocksSearchField(self::TICKET_SPAM_SCORE, 't', 'spam_score',$translate->_('ticket.spam_score')),
			self::TICKET_FIRST_WROTE_SPAM => new DevblocksSearchField(self::TICKET_FIRST_WROTE_SPAM, 'a1', 'num_spam',$translate->_('address.num_spam')),
			self::TICKET_FIRST_WROTE_NONSPAM => new DevblocksSearchField(self::TICKET_FIRST_WROTE_NONSPAM, 'a1', 'num_nonspam',$translate->_('address.num_nonspam')),
			self::TICKET_INTERESTING_WORDS => new DevblocksSearchField(self::TICKET_INTERESTING_WORDS, 't', 'interesting_words',$translate->_('ticket.interesting_words')),
			self::TICKET_DUE_DATE => new DevblocksSearchField(self::TICKET_DUE_DATE, 't', 'due_date',$translate->_('ticket.due')),
			self::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchField(self::TICKET_FIRST_CONTACT_ORG_ID, 'a1', 'contact_org_id'),
			
			self::REQUESTER_ID => new DevblocksSearchField(self::REQUESTER_ID, 'r', 'address_id', $translate->_('ticket.requester')),
			
			self::SENDER_ADDRESS => new DevblocksSearchField(self::SENDER_ADDRESS, 'a1', 'email'),
			
			self::TICKET_MESSAGE_HEADER => new DevblocksSearchField(self::TICKET_MESSAGE_HEADER, 'mh', 'header_name'),
			self::TICKET_MESSAGE_HEADER_VALUE => new DevblocksSearchField(self::TICKET_MESSAGE_HEADER_VALUE, 'mh', 'header_value'),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
			
			self::VIRTUAL_ASSIGNABLE => new DevblocksSearchField(self::VIRTUAL_ASSIGNABLE, '*', 'assignable', $translate->_('ticket.assignable')),
			self::VIRTUAL_STATUS => new DevblocksSearchField(self::VIRTUAL_STATUS, '*', 'status', $translate->_('ticket.status')),
			self::VIRTUAL_WORKERS => new DevblocksSearchField(self::VIRTUAL_WORKERS, '*', 'workers', $translate->_('common.owners')),
		);

		$tables = DevblocksPlatform::getDatabaseTables();
		if(isset($tables['fulltext_message_content'])) {
			$columns[self::FULLTEXT_MESSAGE_CONTENT] = new DevblocksSearchField(self::FULLTEXT_MESSAGE_CONTENT, 'ftmc', 'content', $translate->_('message.content'));
		}
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);

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
	public $last_message_id;
	public $first_wrote_address_id;
	public $last_wrote_address_id;
	public $created_date;
	public $updated_date;
	public $due_date;
	public $spam_score;
	public $spam_training;
	public $interesting_words;
	public $last_action_code;

	function Model_Ticket() {}

	function getMessages() {
		$messages = DAO_Message::getMessagesByTicket($this->id);
		return $messages;
	}
	
	function getFirstMessage() {
		return DAO_Message::get($this->first_message_id);
	}
	
	function getLastMessage() {
		return DAO_Message::get($this->last_message_id);
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
		$this->addColumnsHidden(array(
			SearchFields_Ticket::REQUESTER_ID,
			SearchFields_Ticket::REQUESTER_ADDRESS,
			SearchFields_Ticket::TICKET_CLOSED,
			SearchFields_Ticket::TICKET_DELETED,
			SearchFields_Ticket::TICKET_WAITING,
			SearchFields_Ticket::TICKET_INTERESTING_WORDS,
			SearchFields_Ticket::CONTEXT_LINK,
			SearchFields_Ticket::CONTEXT_LINK_ID,
			SearchFields_Ticket::VIRTUAL_ASSIGNABLE,
			SearchFields_Ticket::VIRTUAL_STATUS,
			SearchFields_Ticket::VIRTUAL_WORKERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Ticket::REQUESTER_ID,
			SearchFields_Ticket::TICKET_CLOSED,
			SearchFields_Ticket::TICKET_DELETED,
			SearchFields_Ticket::TICKET_WAITING,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::CONTEXT_LINK,
			SearchFields_Ticket::CONTEXT_LINK_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Ticket::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Ticket', $size);
	}
	
	function renderSubtotals() {
		if(!method_exists($this, 'getCounts'))
			return;
			
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $this->id);
		$tpl->assign('view', $this);

		if($this->id == 'mail_workflow') {
			// Save
			$params = $this->getEditableParams();
			
			// Remove the bucket filter before sorting workflow (reduce clicking)
			$this->removeParam(SearchFields_Ticket::TICKET_CATEGORY_ID);
			$counts = $this->getCounts($this->renderSubtotals);
			
			// Restore
			if(count($params) != count($this->getEditableParams()))
				$this->addParams($params, true);
			
		} else {
			$counts = $this->getCounts($this->renderSubtotals);
		}
		
		$tpl->assign('counts', $counts);
		
		$tpl->display('devblocks:cerberusweb.core::tickets/view_sidebar.tpl');
	}
	
	// [TODO] This code really should be in DAO_*
	function getCounts($category=null) {
		$db = DevblocksPlatform::getDatabaseService();
		$translate = DevblocksPlatform::getTranslationService();
		
		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		$workers = DAO_Worker::getAll();
		
		$counts = array();

		switch($category) {
			case 'group':
				$params = $this->getParams();
				
				$query_parts = DAO_Ticket::getSearchQueryComponents(
					$this->view_columns,
					$params,
					$this->renderSortBy,
					$this->renderSortAsc
				);
				
				$join_sql = $query_parts['join'];
				$where_sql = $query_parts['where'];
				
				$sql = 
					"SELECT t.team_id, t.category_id, count(t.id) as hits ".
					$join_sql.
					$where_sql.
					"GROUP BY t.team_id, t.category_id ";
		
				$results = $db->GetArray($sql);
				
				foreach($results as $result) {
					$group_id = $result['team_id'];
					$bucket_id = $result['category_id'];
					$hits = $result['hits'];
		
					if(!isset($groups[$group_id]))
						continue;
					
					// ACL
					if(!isset($counts[$group_id]))
						$counts[$group_id] = array(
							'hits'=>0,
							'label'=>$groups[$group_id]->name,
							'children'=>array()
						);
					
					if(empty($bucket_id))
						$label = 'Inbox';
					else
						$label = $buckets[$bucket_id]->name;
						
					$counts[$group_id]['children'][$bucket_id] = array(
						'hits'=>$hits,
						'label'=>$label
					);
					$counts[$group_id]['hits'] += $hits;
				}
				
				unset($results);
				
				// Sort groups by name
				uasort($counts, array($this, '_sortByLabel'));
				
				// Sort by bucket position
				foreach($counts as $group_id => $group)
					uksort($counts[$group_id]['children'], array($this, '_sortByBucketPos'));				
					
				break;
				
			case 'status':
				$params = $this->getParams();
				
				$query_parts = DAO_Ticket::getSearchQueryComponents(
					$this->view_columns,
					$params,
					$this->renderSortBy,
					$this->renderSortAsc
				);
				
				$join_sql = $query_parts['join'];
				$where_sql = $query_parts['where'];				
				
				$sql = 
					"SELECT COUNT(IF(t.is_closed=0 AND t.is_waiting=0,1,NULL)) AS open_hits, COUNT(IF(t.is_waiting=1 AND t.is_deleted=0,1,NULL)) AS waiting_hits, COUNT(IF(t.is_closed=1 AND t.is_deleted=0,1,NULL)) AS closed_hits, COUNT(IF(t.is_deleted=1,1,NULL)) AS deleted_hits ".
					$join_sql.
					$where_sql.
					"";
		
				$result = $db->GetRow($sql);
				
				if(!empty($result['open_hits']))
					$counts['open'] = array('hits'=> $result['open_hits'], 'label'=>$translate->_('status.open'));

				if(!empty($result['waiting_hits']))
					$counts['waiting'] = array('hits'=> $result['waiting_hits'], 'label'=>$translate->_('status.waiting'));
					
				if(!empty($result['closed_hits']))
					$counts['closed'] = array('hits'=> $result['closed_hits'], 'label'=>$translate->_('status.closed'));
					
				if(!empty($result['deleted_hits']))
					$counts['deleted'] = array('hits'=> $result['deleted_hits'], 'label'=>$translate->_('status.deleted'));

				unset($result);
				
				break;
				
			case 'worker':
				$params = $this->getParams();
				
				if(!isset($params[SearchFields_Ticket::VIRTUAL_WORKERS]))
					$params[SearchFields_Ticket::VIRTUAL_WORKERS] = new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_WORKERS,DevblocksSearchCriteria::OPER_NIN,array());
				
				$query_parts = DAO_Ticket::getSearchQueryComponents(
					$this->view_columns,
					$params,
					$this->renderSortBy,
					$this->renderSortAsc
				);
				
				$join_sql = $query_parts['join'];
				$where_sql = $query_parts['where'];				
				
				$sql = 
					"SELECT context_owner.to_context_id as worker_id, count(t.id) as hits ".
					$join_sql.
					$where_sql.
					"GROUP BY context_owner.to_context_id ";
		
				$results = $db->GetArray($sql);
				
				foreach($results as $result) {
					$worker_id = $result['worker_id'];
					$hits = $result['hits'];
		
					if(!isset($workers[$worker_id]))
						continue;
						
					// ACL
					if(!isset($counts[$worker_id]))
						$counts[$worker_id] = array('hits'=>0, 'label'=>$workers[$worker_id]->getName(), 'children'=>array());
					
					$counts[$worker_id]['hits'] += $hits;
				}
				
				unset($results);
				
				// Sort groups by name
				uasort($counts, array($this, '_sortByLabel'));
				
				break;
		}
		
		return $counts;
	}
	
	private function _sortByLabel($a, $b) {
		return strcmp($a['label'], $b['label']);
	}
	
	private function _sortByBucketPos($a, $b) {
		$buckets = DAO_Bucket::getAll();
		if(0==$a) return -1; // inbox
		if(0==$b) return 1;
		return (intval($buckets[$a]->pos) < intval($buckets[$b]->pos)) ? -1 : 1;
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

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);

		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
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
				$tpl->display('devblocks:cerberusweb.core::tickets/view_contextlinks_chooser.tpl');
				break;
			default:
				$tpl->display('devblocks:cerberusweb.core::tickets/ticket_view.tpl');
				break;
		}
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;

			case SearchFields_Ticket::TICKET_FIRST_WROTE_SPAM:
			case SearchFields_Ticket::TICKET_FIRST_WROTE_NONSPAM:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_WAITING:
			case SearchFields_Ticket::TICKET_DELETED:
			case SearchFields_Ticket::TICKET_CLOSED:
			case SearchFields_Ticket::VIRTUAL_ASSIGNABLE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_DUE_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
					
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$tpl->display('devblocks:cerberusweb.core::tickets/search/criteria/ticket_spam_training.tpl');
				break;
				
			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				$tpl->display('devblocks:cerberusweb.core::tickets/search/criteria/ticket_spam_score.tpl');
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				$tpl->display('devblocks:cerberusweb.core::tickets/search/criteria/ticket_last_action.tpl');
				break;

			case SearchFields_Ticket::TICKET_TEAM_ID:
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);

				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);

				$tpl->display('devblocks:cerberusweb.core::tickets/search/criteria/ticket_team.tpl');
				break;

			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_Ticket::VIRTUAL_WORKERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				$tpl->display('devblocks:cerberusweb.core::tickets/search/criteria/ticket_status.tpl');
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
			case SearchFields_Ticket::VIRTUAL_ASSIGNABLE:
				if(empty($param->value)) {
					echo "Tickets <b>are not assignable</b>";
				} else { 
					echo "Tickets <b>are assignable</b>";
				}
				break;
				
			case SearchFields_Ticket::VIRTUAL_WORKERS:
				if(empty($param->value)) {
					echo "Owners <b>are not assigned</b>";
					
				} elseif(is_array($param->value)) {
					$workers = DAO_Worker::getAll();
					$strings = array();
					
					foreach($param->value as $worker_id) {
						if(isset($workers[$worker_id]))
							$strings[] = '<b>'.$workers[$worker_id]->getName().'</b>';
					}
					
					echo sprintf("Owner is %s", implode(' or ', $strings));
				}
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				if(!is_array($param->value))
					$param->value = array($param->value);
					
				$strings = array();
				
				foreach($param->value as $value) {
					switch($value) {
						case 'open':
							$strings[] = '<b>' . $translate->_('status.open') . '</b>';
							break;
						case 'waiting':
							$strings[] = '<b>' . $translate->_('status.waiting') . '</b>';
							break;
						case 'closed':
							$strings[] = '<b>' . $translate->_('status.closed') . '</b>';
							break;
						case 'deleted':
							$strings[] = '<b>' . $translate->_('status.deleted') . '</b>';
							break;
					}
				}
				
				echo sprintf("Status is %s", implode(' or ', $strings));
				break;
		}
	}	
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
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

	function getFields() {
		return SearchFields_Ticket::getFields();
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
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;

			case SearchFields_Ticket::TICKET_WAITING:
			case SearchFields_Ticket::TICKET_DELETED:
			case SearchFields_Ticket::TICKET_CLOSED:
			case SearchFields_Ticket::VIRTUAL_ASSIGNABLE:
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

			case SearchFields_Ticket::TICKET_TEAM_ID:
				@$team_ids = DevblocksPlatform::importGPC($_REQUEST['team_id'],'array');
				@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'array');

				// Groups
				if(!empty($team_ids)) {
					$this->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,$oper,$team_ids));
				}
					
				// Buckets
				if(!empty($bucket_ids)) {
					$this->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,$oper,$bucket_ids));
				} else { // clear if no buckets provided
					$this->removeParam(SearchFields_Ticket::TICKET_CATEGORY_ID);
				}

				break;
				
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Ticket::VIRTUAL_WORKERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field, 'in', $worker_ids);
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				@$statuses = DevblocksPlatform::importGPC($_REQUEST['value'],'array',array());
				$criteria = new DevblocksSearchCriteria($field, null, $statuses);
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
	  
		$params = $this->getParams();

		if(empty($filter)) {
			$data[] = '*'; // All, just to permit a loop in foreach($data ...)
		}

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

		unset($ticket_ids);
	}

	static function createSearchView() {
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

class Context_Ticket extends Extension_DevblocksContext {
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->is_superuser)
				return TRUE;
				
			if(null == ($ticket = DAO_Ticket::get($context_id)))
				throw new Exception();
			
			return $worker->isTeamMember($ticket->team_id);
				
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
		
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=display&id='.$context_id, true);
    }

	function getContext($ticket, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Ticket:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$workers = DAO_Worker::getAll();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		
		// Polymorph
		if(is_numeric($ticket)) {
			list($results, $null) = DAO_Ticket::search(
				array(),
				array(
					SearchFields_Ticket::TICKET_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_ID,'=',$ticket),
					// [TODO] Enforce worker privs
				),
				1,
				0,
				null,
				null,
				false
			);
			
			if(!empty($results))
				$ticket = array_shift($results);
			else
				$ticket = null;
				
		} elseif(is_array($ticket)) {
			// It's what we want
		} else {
			$ticket = null;
		}
			
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('ticket.id'),
			'mask' => $prefix.$translate->_('ticket.mask'),
			'subject' => $prefix.$translate->_('ticket.subject'),
			'created|date' => $prefix.$translate->_('ticket.created'),
			'updated|date' => $prefix.$translate->_('ticket.updated'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Ticket token values
		if(null != $ticket) {
			$token_values['id'] = $ticket[SearchFields_Ticket::TICKET_ID];
			$token_values['mask'] = $ticket[SearchFields_Ticket::TICKET_MASK];
			$token_values['subject'] = $ticket[SearchFields_Ticket::TICKET_SUBJECT];
			$token_values['created'] = $ticket[SearchFields_Ticket::TICKET_CREATED_DATE];
			$token_values['updated'] = $ticket[SearchFields_Ticket::TICKET_UPDATED_DATE];
			$token_values['custom'] = array();
			
			// Custom fields
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket[SearchFields_Ticket::TICKET_ID]));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $ticket)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $ticket)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}

		// Group
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $ticket[SearchFields_Ticket::TICKET_TEAM_ID], $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'group_',
			'Ticket:Group:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Bucket
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BUCKET, $ticket[SearchFields_Ticket::TICKET_CATEGORY_ID], $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'bucket_',
			'Ticket:Bucket:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// First message
		$first_message_id = $ticket[SearchFields_Ticket::TICKET_FIRST_MESSAGE_ID];
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $first_message_id, $merge_token_labels, $merge_token_values, 'Message:', true);
		
		CerberusContexts::merge(
			'initial_message_',
			'Initial:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		
		);
		
		// Last message
		$last_message_id = $ticket[SearchFields_Ticket::TICKET_LAST_MESSAGE_ID];
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $last_message_id, $merge_token_labels, $merge_token_values, 'Message:', true);
		
		CerberusContexts::merge(
			'latest_message_',
			'Latest:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// Plugin-provided tokens
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
    
	function getChooserView() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tickets';
		$view->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
		);
		$view->addParams(array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
			SearchFields_Ticket::VIRTUAL_WORKERS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_WORKERS,null,array($active_worker->id)),
			SearchFields_Ticket::TICKET_TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'in',array_keys($active_worker->getMemberships())),
		), true);
		$view->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$view->renderSortAsc = false;
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
		$view->name = 'Tickets';
		
		$params = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params = array(
				new DevblocksSearchCriteria(SearchFields_Ticket::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Ticket::CONTEXT_LINK_ID,'=',$context_id),
			);
		}

		if(isset($options['filter_open'])) {
			$params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0);
			$params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0);
		}
		
		$view->addParams($params, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};
