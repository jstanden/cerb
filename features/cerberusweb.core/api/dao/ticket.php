<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class DAO_Ticket extends C4_ORMHelper {
	const ID = 'id';
	const MASK = 'mask';
	const SUBJECT = 'subject';
	const IS_WAITING = 'is_waiting';
	const IS_CLOSED = 'is_closed';
	const IS_DELETED = 'is_deleted';
	const GROUP_ID = 'group_id';
	const BUCKET_ID = 'bucket_id';
	const ORG_ID = 'org_id';
	const OWNER_ID = 'owner_id';
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
	const NUM_MESSAGES = 'num_messages';
	
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
			'group_id' => $translate->_('ticket.group'),
			'bucket_id' => $translate->_('ticket.bucket'),
			'owner_id' => $translate->_('common.owner'),
			'updated_date' => $translate->_('ticket.updated'),
			'spam_training' => $translate->_('ticket.spam_training'),
			'spam_score' => $translate->_('ticket.spam_score'),
			'interesting_words' => $translate->_('ticket.interesting_words'),
			'num_messages' => $translate->_('ticket.num_messages'),
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
	 * @return array
	 */
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
	static function create($fields) {
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
		
		// Recover any tickets assigned to a NULL bucket
		$sql = "UPDATE ticket LEFT JOIN bucket ON ticket.bucket_id = bucket.id SET ticket.bucket_id = 0 WHERE ticket.bucket_id > 0 AND bucket.id IS NULL";
		$db->Execute($sql);
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
			
			// Mail queue
			$sql = sprintf("UPDATE mail_queue SET ticket_id = %d WHERE ticket_id IN (%s)",
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
			
			// Activity log
			
			$db->Execute(sprintf("UPDATE IGNORE context_activity_log ".
				"SET target_context_id = %d ".
				"WHERE target_context = 'cerberusweb.contexts.ticket' AND target_context_id IN (%s) ",
				$oldest_id,
				implode(',', $merge_ticket_ids)
			));
			
			$db->Execute(sprintf("DELETE FROM context_activity_log ".
				"WHERE target_context = 'cerberusweb.contexts.ticket' AND target_context_id IN (%s) ",
				implode(',', $merge_ticket_ids)
			));
			
			// Notifications
			
			$sql = sprintf("UPDATE notification SET context_id = %d WHERE context = %s AND context_id IN (%s)",
				$oldest_id,
				$db->qstr(CerberusContexts::CONTEXT_TICKET),
				implode(',', $merge_ticket_ids)
			);
			$db->Execute($sql);
			
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
				DAO_Ticket::NUM_MESSAGES => 0,
			));

			// Sort merge tickets by updated date ascending to find the latest touched
			$tickets = $merged_tickets;
			array_unshift($tickets, $oldest_ticket);
			DevblocksPlatform::sortObjects($tickets, '[' . SearchFields_Ticket::TICKET_UPDATED_DATE . ']');
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

			DAO_Ticket::updateMessageCount($oldest_id);
			
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
						'source' => 'c=display&mask='.$ticket[SearchFields_Ticket::TICKET_MASK],
						'target' => 'c=display&mask='.$oldest_ticket[SearchFields_Ticket::TICKET_MASK],
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
		if(!is_array($ids))
			$ids = array($ids);
		
		return self::getWhere(
			(!empty($ids) ? sprintf("id IN (%s) ",implode(',',$ids)) : " ")
		);
	}
	
	static function getWhere($where=null, $sortBy='updated_date', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id , mask, subject, is_waiting, is_closed, is_deleted, group_id, bucket_id, org_id, owner_id, first_message_id, last_message_id, ".
			"first_wrote_address_id, last_wrote_address_id, created_date, updated_date, due_date, spam_training, ". 
			"spam_score, interesting_words, num_messages ".
			"FROM ticket ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param resource $rs
	 */
	static private function _createObjectsFromResultSet($rs=null) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Ticket();
			$object->id = intval($row['id']);
			$object->mask = $row['mask'];
			$object->subject = $row['subject'];
			$object->first_message_id = intval($row['first_message_id']);
			$object->last_message_id = intval($row['last_message_id']);
			$object->group_id = intval($row['group_id']);
			$object->bucket_id = intval($row['bucket_id']);
			$object->org_id = intval($row['org_id']);
			$object->owner_id = intval($row['owner_id']);
			$object->is_waiting = intval($row['is_waiting']);
			$object->is_closed = intval($row['is_closed']);
			$object->is_deleted = intval($row['is_deleted']);
			$object->last_wrote_address_id = intval($row['last_wrote_address_id']);
			$object->first_wrote_address_id = intval($row['first_wrote_address_id']);
			$object->created_date = intval($row['created_date']);
			$object->updated_date = intval($row['updated_date']);
			$object->due_date = intval($row['due_date']);
			$object->spam_score = floatval($row['spam_score']);
			$object->spam_training = $row['spam_training'];
			$object->interesting_words = $row['interesting_words'];
			$object->num_messages = $row['num_messages'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
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
    	while($batch_ids = array_shift($chunks)) {
	    	$objects = DAO_Ticket::getTickets($batch_ids);
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
	    	
	    	parent::_update($batch_ids, 'ticket', $fields);
	    	
	    	// Local events
	    	self::_processUpdateEvents($object_changes);
	    	
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
	
	static function _processUpdateEvents($objects) {
    	if(is_array($objects))
    	foreach($objects as $object_id => $object) {
    		@$model = $object['model'];
    		@$changes = $object['changes'];
    		
    		if(empty($model) || empty($changes))
    			continue;
    		
    		/*
    		 * Owner changed
    		 */
    		if(isset($changes[DAO_Ticket::OWNER_ID])) {
	    		@$owner_id = $changes[DAO_Ticket::OWNER_ID];
	    		
	    		/*
	    		* Mail assigned in group
	    		*/
	    		Event_MailAssignedInGroup::trigger($object_id, $model[DAO_Ticket::GROUP_ID]);
	    		
				/*
				 * Log activity (ticket.unassigned)
				 */
	    		if(empty($owner_id['to'])) {
					$activity_point = 'ticket.owner.unassigned';
					
					$entry = array(
						// {{actor}} unassigned ticket {{target}}
						'message' => 'activities.ticket.unassigned',
						'variables' => array(
							'target' => sprintf("[%s] %s", $model[DAO_Ticket::MASK], $model[DAO_Ticket::SUBJECT]),
							),
						'urls' => array(
							'target' => 'c=display&mask='.$model[DAO_Ticket::MASK],
							)
					);
					CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_TICKET, $object_id, $entry);
	    		}
	    		
				/*
				 * Log activity (ticket.assigned)
				 */
	    		if(!empty($owner_id['to'])) {
					$activity_point = 'ticket.owner.assigned';
					$target_worker = DAO_Worker::get($changes[DAO_Ticket::OWNER_ID]['to']);

					$entry = array(
						//{{actor}} assigned ticket {{target}} to worker {{worker}}
						'message' => 'activities.ticket.assigned',
						'variables' => array(
							'target' => sprintf("[%s] %s", $model[DAO_Ticket::MASK], $model[DAO_Ticket::SUBJECT]),
							'worker' => (!empty($target_worker) && $target_worker instanceof Model_Worker) ? $target_worker->getName() : '',
							),
						'urls' => array(
							'target' => 'c=display&mask='.$model[DAO_Ticket::MASK],
							)
					);
					CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_TICKET, $object_id, $entry);
	    			
	    		}
    		}
    		
			/*
			 * Ticket moved
			 */
			@$group_id = $changes[DAO_Ticket::GROUP_ID];
			@$bucket_id = $changes[DAO_Ticket::BUCKET_ID];
			
			if(!empty($group_id) || !empty($bucket_id)) {
				Event_MailMovedToGroup::trigger($object_id, $model[DAO_Ticket::GROUP_ID]);
			}
			
			/*
			 * Ticket closed (but not deleted)
			 */
			if(
				isset($changes[DAO_Ticket::IS_WAITING])
				|| isset($changes[DAO_Ticket::IS_CLOSED]) 
				|| isset($changes[DAO_Ticket::IS_DELETED])
			) {
				@$waiting = $changes[DAO_Ticket::IS_WAITING];
				@$closed = $changes[DAO_Ticket::IS_CLOSED];
				@$deleted = $changes[DAO_Ticket::IS_DELETED];

				$status_to = null;
				$activity_point = null;
				
				if(!empty($model[DAO_Ticket::IS_DELETED])) {
					$status_to = 'deleted';
					$activity_point = 'ticket.status.deleted';
					
				} else if(!empty($model[DAO_Ticket::IS_CLOSED])) {
					$status_to = 'closed';
					$activity_point = 'ticket.status.closed';
					
					//$logger = DevblocksPlatform::getConsoleLog();
					//$log_level = $logger->setLogLevel(7);
					Event_MailClosedInGroup::trigger($object_id, $model[DAO_Ticket::GROUP_ID]);
					//$logger->setLogLevel($log_level);
					
				} else if(!empty($model[DAO_Ticket::IS_WAITING])) {
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
							'target' => sprintf("[%s] %s", $model[DAO_Ticket::MASK], $model[DAO_Ticket::SUBJECT]),
							'status' => $status_to,
							),
						'urls' => array(
							'target' => 'c=display&mask='.$model[DAO_Ticket::MASK],
							)
					);
					CerberusContexts::logActivity($activity_point, CerberusContexts::CONTEXT_TICKET, $object_id, $entry);
				}
				
			} //foreach
    	}		
	}
	
	static function updateMessageCount($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("UPDATE ticket ".
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
	
	public static function random() {
		return self::_getRandom('ticket');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Ticket::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
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
			"t.num_messages as %s, ".
			"t.owner_id as %s, ".
			"t.group_id as %s, ".
			"t.bucket_id as %s, ".
			"t.org_id as %s ",
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
			    SearchFields_Ticket::TICKET_NUM_MESSAGES,
			    SearchFields_Ticket::TICKET_OWNER_ID,
			    SearchFields_Ticket::TICKET_GROUP_ID,
			    SearchFields_Ticket::TICKET_BUCKET_ID,
			    SearchFields_Ticket::TICKET_ORG_ID
		);

		$join_sql = 
			"FROM ticket t ".
			"INNER JOIN address a1 ON (t.first_wrote_address_id=a1.id) ".
			"INNER JOIN address a2 ON (t.last_wrote_address_id=a2.id) ".
			// [JAS]: Dynamic table joins
			((isset($tables['r']) || isset($tables['ra'])) ? "INNER JOIN requester r ON (r.ticket_id=t.id) " : " ").
			(isset($tables['ra']) ? "INNER JOIN address ra ON (ra.id=r.address_id) " : " ").
			(isset($tables['msg']) || isset($tables['ftmc']) ? "INNER JOIN message msg ON (msg.ticket_id=t.id) " : " ").
			(isset($tables['ftcc']) ? "INNER JOIN comment ON (comment.context = 'cerberusweb.contexts.ticket' AND comment.context_id = t.id) " : " ").
			(isset($tables['ftcc']) ? "INNER JOIN fulltext_comment_content ftcc ON (ftcc.id=comment.id) " : " ").
			(isset($tables['ftmc']) ? "INNER JOIN fulltext_message_content ftmc ON (ftmc.id=msg.id) " : " ").
			(isset($tables['mh']) ? "INNER JOIN message_header mh ON (mh.message_id=t.first_message_id) " : " "). // [TODO] Choose between first message and all?
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.ticket' AND context_link.to_context_id = t.id) " : " ")
			;
			
		// Org joins
		if(isset($tables['o'])) {
			$select_sql .= ", o.name as o_name ";
			$join_sql .= "LEFT JOIN contact_org o ON (t.org_id=o.id) ";
		}
		
		// Map custom fields to indexes
		
		$cfield_index_map = array(
			CerberusContexts::CONTEXT_TICKET => 't.id',
			CerberusContexts::CONTEXT_ORG => 't.org_id',
		);
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			$cfield_index_map,
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");

		// Translate virtual fields
		
		array_walk_recursive(
			$params,
			array('DAO_Ticket', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'has_multiple_values' => &$has_multiple_values
			)
		);

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
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
		
		$param_key = $param->field;
		settype($param_key, 'string');

		switch($param_key) {
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				$from_context = 'cerberusweb.contexts.ticket';
				$from_index = 't.id';
				
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
				
			case SearchFields_Ticket::VIRTUAL_ASSIGNABLE:
				$assignable_buckets = DAO_Bucket::getAssignableBuckets();
				$assignable_bucket_ids = array_keys($assignable_buckets);
				array_unshift($assignable_bucket_ids, 0);
				if($param->value) { // true
					$args['where_sql'] .= sprintf("AND t.bucket_id IN (%s) ", implode(',', $assignable_bucket_ids));	
				} else { // false
					$args['where_sql'] .= sprintf("AND t.bucket_id NOT IN (%s) ", implode(',', $assignable_bucket_ids));	
				}
				break;
				
			case SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER:
				$member = DAO_Worker::get($param->value);
				$roster = $member->getMemberships();
				if(empty($roster))
					break;
				$args['where_sql'] .= sprintf("AND t.group_id IN (%s) ", implode(',', array_keys($roster)));
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
				
				$args['where_sql'] .= 'AND (' . implode(' OR ', $status_sql) . ') ';
				break;
		}		
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
	const TICKET_NUM_MESSAGES = 't_num_messages';
	const TICKET_GROUP_ID = 't_group_id';
	const TICKET_BUCKET_ID = 't_bucket_id';
	const TICKET_ORG_ID = 't_org_id';
	const TICKET_OWNER_ID = 't_owner_id';
	
	const TICKET_MESSAGE_HEADER = 'mh_header_name';
    const TICKET_MESSAGE_HEADER_VALUE = 'mh_header_value';	

	// Sender
	const SENDER_ADDRESS = 'a1_address';
	
	// Requester
	const REQUESTER_ID = 'r_id';
	const REQUESTER_ADDRESS = 'ra_email';
	
	// Sender Org
	const ORG_NAME = 'o_name';

	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';

	// Message Content
	const FULLTEXT_MESSAGE_CONTENT = 'ftmc_content';
	
	// Context Links
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	// Virtuals
	const VIRTUAL_ASSIGNABLE = '*_assignable';
	const VIRTUAL_GROUPS_OF_WORKER = '*_groups_of_worker';
	const VIRTUAL_STATUS = '*_status';
	const VIRTUAL_WATCHERS = '*_workers';
	
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
			
			self::TICKET_ORG_ID => new DevblocksSearchField(self::TICKET_ORG_ID, 't','org_id',$translate->_('contact_org.id')),
			self::TICKET_OWNER_ID => new DevblocksSearchField(self::TICKET_OWNER_ID,'t','owner_id',$translate->_('common.owner')),
			self::TICKET_GROUP_ID => new DevblocksSearchField(self::TICKET_GROUP_ID,'t','group_id',$translate->_('common.group')),
			self::TICKET_BUCKET_ID => new DevblocksSearchField(self::TICKET_BUCKET_ID, 't', 'bucket_id',$translate->_('common.bucket')),
			self::TICKET_CREATED_DATE => new DevblocksSearchField(self::TICKET_CREATED_DATE, 't', 'created_date',$translate->_('ticket.created')),
			self::TICKET_UPDATED_DATE => new DevblocksSearchField(self::TICKET_UPDATED_DATE, 't', 'updated_date',$translate->_('ticket.updated')),
			self::TICKET_WAITING => new DevblocksSearchField(self::TICKET_WAITING, 't', 'is_waiting',$translate->_('status.waiting')),
			self::TICKET_CLOSED => new DevblocksSearchField(self::TICKET_CLOSED, 't', 'is_closed',$translate->_('status.closed')),
			self::TICKET_DELETED => new DevblocksSearchField(self::TICKET_DELETED, 't', 'is_deleted',$translate->_('status.deleted')),

			self::TICKET_LAST_ACTION_CODE => new DevblocksSearchField(self::TICKET_LAST_ACTION_CODE, 't', 'last_action_code',$translate->_('ticket.last_action')),
			self::TICKET_NUM_MESSAGES => new DevblocksSearchField(self::TICKET_NUM_MESSAGES, 't', 'num_messages',$translate->_('ticket.num_messages')),
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
			self::VIRTUAL_GROUPS_OF_WORKER => new DevblocksSearchField(self::VIRTUAL_GROUPS_OF_WORKER, '*', 'groups_of_worker', $translate->_('ticket.groups_of_worker')),
			self::VIRTUAL_STATUS => new DevblocksSearchField(self::VIRTUAL_STATUS, '*', 'status', $translate->_('ticket.status')),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers')),
		);

		$tables = DevblocksPlatform::getDatabaseTables();
		if(isset($tables['fulltext_comment_content'])) {
			$columns[self::FULLTEXT_COMMENT_CONTENT] = new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'));
		}
		if(isset($tables['fulltext_message_content'])) {
			$columns[self::FULLTEXT_MESSAGE_CONTENT] = new DevblocksSearchField(self::FULLTEXT_MESSAGE_CONTENT, 'ftmc', 'content', $translate->_('message.content'));
		}
		
		// Custom Fields: ticket + org
		$fields = 
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET) + 
			DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG)
		;
		
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
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
	public $group_id;
	public $bucket_id;
	public $org_id;
	public $owner_id = 0;
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
	public $num_messages;
	
	private $_org = null;

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
	
	// Lazy load
	function getOrg() {
		if(empty($this->org_id))
			return null;
		
		if(is_null($this->_org)) {
			$this->_org = DAO_ContactOrg::get($this->org_id);
		}
		
		return $this->_org;
	}
};

class View_Ticket extends C4_AbstractView implements IAbstractView_Subtotals {
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
			SearchFields_Ticket::TICKET_GROUP_ID,
			SearchFields_Ticket::TICKET_BUCKET_ID,
			SearchFields_Ticket::TICKET_SPAM_SCORE,
			SearchFields_Ticket::TICKET_OWNER_ID,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Ticket::CONTEXT_LINK,
			SearchFields_Ticket::CONTEXT_LINK_ID,
			SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT,
			SearchFields_Ticket::REQUESTER_ADDRESS,
			SearchFields_Ticket::REQUESTER_ID,
			SearchFields_Ticket::TICKET_CLOSED,
			SearchFields_Ticket::TICKET_DELETED,
			SearchFields_Ticket::TICKET_INTERESTING_WORDS,
			SearchFields_Ticket::TICKET_ORG_ID,
			SearchFields_Ticket::TICKET_WAITING,
			SearchFields_Ticket::VIRTUAL_ASSIGNABLE,
			SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,
			SearchFields_Ticket::VIRTUAL_STATUS,
			SearchFields_Ticket::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Ticket::CONTEXT_LINK,
			SearchFields_Ticket::CONTEXT_LINK_ID,
			SearchFields_Ticket::REQUESTER_ID,
			SearchFields_Ticket::TICKET_BUCKET_ID,
			SearchFields_Ticket::TICKET_CLOSED,
			SearchFields_Ticket::TICKET_DELETED,
			SearchFields_Ticket::TICKET_ORG_ID,
			SearchFields_Ticket::TICKET_WAITING,
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

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Ticket', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Ticket', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
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
				case SearchFields_Ticket::TICKET_OWNER_ID:
					$pass = true;
					break;

				// Virtuals
				case SearchFields_Ticket::VIRTUAL_STATUS:
					$pass = true;
					break;
					
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Ticket::ORG_NAME:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::TICKET_SUBJECT:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Ticket', $column);
				break;
				
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
				$label_map = array(
					'' => 'Not trained',
					'S' => 'Spam',
					'N' => 'Not spam',
				);
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Ticket', $column, $label_map);
				break;
				
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$label_map = array();
				$workers = DAO_Worker::getAll();
				foreach($workers as $k => $v)
					$label_map[$k] = $v->getName();
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Ticket', $column, $label_map, 'in', 'worker_id[]');
				break;
				
			case SearchFields_Ticket::TICKET_GROUP_ID:
				$counts = $this->_getSubtotalCountForBuckets();
				break;
				
			case SearchFields_Ticket::VIRTUAL_STATUS:
				$counts = $this->_getSubtotalCountForStatus();
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_Ticket', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Ticket', $column, 't.id');
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
		if(isset($params[SearchFields_Ticket::TICKET_BUCKET_ID])) {
			// Allow all inbox search if no group filter
			if(!isset($params[SearchFields_Ticket::TICKET_GROUP_ID])
				&& isset($params[SearchFields_Ticket::TICKET_BUCKET_ID]->value)) {
					// Allow single drill-down
			 } else {
				unset($params[SearchFields_Ticket::TICKET_BUCKET_ID]);
			 }
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
			"GROUP BY group_id, bucket_id "
		;
		
		$results = $db->GetArray($sql);

		return $results;
	}	
	
	private function _getSubtotalCountForBuckets() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = array();
		$results = $this->_getSubtotalDataForBuckets();
		
		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		
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
							'values' => array('group_id[]' => $result['group_id']),
						),
					'children' => array()
				);
			}
				
			@$label = $buckets[$bucket_id]->name;
			if(empty($label))
				$label = mb_convert_case($translate->_('common.inbox'), MB_CASE_TITLE);
				
			$child = array(
				'hits' => $hits,
				'label' => $label,
				'filter' => 
					array(
						'field' => SearchFields_Ticket::TICKET_GROUP_ID,
						'oper' => DevblocksSearchCriteria::OPER_IN,
						'values' => array('group_id[]' => $result['group_id'], 'bucket_id[]' => $result['bucket_id']),
					),
			);
			
			$counts[$group_id]['hits'] += $hits;
			$counts[$group_id]['children'][$bucket_id] = $child;
		}
		
		// Sort groups alphabetically
		uasort($counts, array($this, '_sortByLabel'));
		
		// Sort buckets by group preference
		foreach($counts as $group_id => $data) {
			uksort($counts[$group_id]['children'], array($this, '_sortByBucketPos'));
		}
		
		return $counts;		
	}
	
	protected function _getSubtotalDataForStatus($dao_class, $field_key) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$fields = $this->getFields();
		$columns = $this->view_columns;
		$params = $this->getParams();
		
		if(!isset($params[$field_key])) {
			$new_params = array(
				$field_key => new DevblocksSearchCriteria($field_key, DevblocksSearchCriteria::OPER_TRUE),
			);
			$params = array_merge($new_params, $params);
		}
		
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
		
		$sql = "SELECT COUNT(IF(t.is_closed=0 AND t.is_waiting=0,1,NULL)) AS open_hits, COUNT(IF(t.is_waiting=1 AND t.is_deleted=0,1,NULL)) AS waiting_hits, COUNT(IF(t.is_closed=1 AND t.is_deleted=0,1,NULL)) AS closed_hits, COUNT(IF(t.is_deleted=1,1,NULL)) AS deleted_hits ".
			$join_sql.
			$where_sql 
		;
		
		$results = $db->GetArray($sql);

		return $results;
	}	
	
	protected function _getSubtotalCountForStatus() {
		$workers = DAO_Worker::getAll();
		$translate = DevblocksPlatform::getTranslationService();
		
		$counts = array();
		$results = $this->_getSubtotalDataForStatus('DAO_Ticket', SearchFields_Ticket::VIRTUAL_STATUS);

		$result = array_shift($results);
		$oper = DevblocksSearchCriteria::OPER_IN;
		
		foreach($result as $key => $hits) {
			if(empty($hits))
				continue;
			
			switch($key) {
				case 'open_hits':
					$label = $translate->_('status.open');
					$values = array('value[]' => 'open');
					break;
				case 'waiting_hits':
					$label = $translate->_('status.waiting');
					$values = array('value[]' => 'waiting');
					break;
				case 'closed_hits':
					$label = $translate->_('status.closed');
					$values = array('value[]' => 'closed');
					break;
				case 'deleted_hits':
					$label = $translate->_('status.deleted');
					$values = array('value[]' => 'deleted');
					break;
				default:
					$label = '';
					break;
			}
			
			if(!isset($counts[$label]))
				$counts[$label] = array(
					'hits' => $hits,
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

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);

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
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::tickets/ticket_view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
		$tpl->clearAssign('last_action');
		$tpl->clearAssign('last_action_count');
		$tpl->clearAssign('results');
		$tpl->clearAssign('group_buckets');
		$tpl->clearAssign('timestamp_now');
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
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;

			case SearchFields_Ticket::TICKET_FIRST_WROTE_SPAM:
			case SearchFields_Ticket::TICKET_FIRST_WROTE_NONSPAM:
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_NUM_MESSAGES:
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

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				$options = array(
					'O' => 'New Ticket',
					'R' => 'Customer Reply',
					'W' => 'Worker Reply',
				);
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
				break;

			case SearchFields_Ticket::TICKET_GROUP_ID:
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);

				$group_buckets = DAO_Bucket::getGroups();
				$tpl->assign('group_buckets', $group_buckets);

				$tpl->display('devblocks:cerberusweb.core::tickets/search/criteria/ticket_group.tpl');
				break;

			case SearchFields_Ticket::TICKET_OWNER_ID:
				$tpl->assign('opers', array(
					'in' => 'is',
					'in or null' => 'is blank or',
					'not in' => 'is not',
					'not in and not null' => 'is not blank or',
				));
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			case SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT:
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER:
				$tpl->assign('workers', DAO_Worker::getAllActive());
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__worker.tpl');
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
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
			case SearchFields_Ticket::VIRTUAL_ASSIGNABLE:
				if(empty($param->value)) {
					echo "Tickets <b>are not assignable</b>";
				} else { 
					echo "Tickets <b>are assignable</b>";
				}
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
				
			case SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER:
				if(null == ($worker = DAO_Worker::get($param->value)))
					break;
					
				echo sprintf("In <b>%s</b>'s groups", $worker->getName());
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
			case SearchFields_Ticket::TICKET_WAITING:
			case SearchFields_Ticket::TICKET_DELETED:
			case SearchFields_Ticket::TICKET_CLOSED:
			case SearchFields_Ticket::VIRTUAL_ASSIGNABLE:
				$this->_renderCriteriaParamBoolean($param);
				break;
			
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;
				
			case SearchFields_Ticket::TICKET_GROUP_ID:
				$groups = DAO_Group::getAll();
				$strings = array();

				foreach($values as $val) {
					if(!isset($groups[$val]))
						continue;

					$strings[] = $groups[$val]->name;
				}
				echo implode(", ", $strings);
				break;
					
			case SearchFields_Ticket::TICKET_BUCKET_ID:
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
			case SearchFields_Ticket::TICKET_MASK:
			case SearchFields_Ticket::TICKET_SUBJECT:
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
			case SearchFields_Ticket::TICKET_LAST_WROTE:
			case SearchFields_Ticket::REQUESTER_ADDRESS:
			case SearchFields_Ticket::TICKET_INTERESTING_WORDS:
			case SearchFields_Ticket::ORG_NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
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
			case SearchFields_Ticket::TICKET_ID:
			case SearchFields_Ticket::TICKET_NUM_MESSAGES:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_Ticket::TICKET_CREATED_DATE:
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
			case SearchFields_Ticket::TICKET_DUE_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				@$score = DevblocksPlatform::importGPC($_REQUEST['score'],'integer',null);
				if(!is_null($score) && is_numeric($score)) {
					$criteria = new DevblocksSearchCriteria($field,$oper,intval($score)/100);
				}
				break;

			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
			case SearchFields_Ticket::TICKET_SPAM_TRAINING:
			case SearchFields_Ticket::VIRTUAL_STATUS:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;

			case SearchFields_Ticket::TICKET_GROUP_ID:
				@$group_ids = DevblocksPlatform::importGPC($_REQUEST['group_id'],'array');
				@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'array');

				// Groups
				if(!empty($group_ids)) {
					$this->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_GROUP_ID,$oper,$group_ids));
				}
					
				// Buckets
				if(!empty($bucket_ids)) {
					$this->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_BUCKET_ID,$oper,$bucket_ids));
				} else { // clear if no buckets provided
					$this->removeParam(SearchFields_Ticket::TICKET_BUCKET_ID);
				}

				break;
				
			case SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT:
			case SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Ticket::TICKET_OWNER_ID:
				$criteria = $this->_doSetCriteriaWorker($field, $oper);
				break;
				
			case SearchFields_Ticket::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field, $oper, $worker_ids);
				break;
				
			case SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
				$criteria = new DevblocksSearchCriteria($field, '=', $worker_id);
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
	 */
	function doBulkUpdate($filter, $filter_param, $data, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
		$custom_fields = array();

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		// Make sure we have actions
		if(empty($do))
			return;
		
		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
		
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'move':
					$change_fields[DAO_Ticket::GROUP_ID] = $v['group_id'];
					$change_fields[DAO_Ticket::BUCKET_ID] = $v['bucket_id'];
					break;
				case 'owner':
					$change_fields[DAO_Ticket::OWNER_ID] = $v['worker_id'];
					break;
				case 'org':
					$change_fields[DAO_Ticket::ORG_ID] = intval($v['org_id']);
					break;
				case 'status':
					$change_fields[DAO_Ticket::IS_WAITING] = !empty($v['is_waiting']) ? 1 : 0;
					$change_fields[DAO_Ticket::IS_CLOSED] = !empty($v['is_closed']) ? 1 : 0;
					$change_fields[DAO_Ticket::IS_DELETED] = !empty($v['is_deleted']) ? 1 : 0;
					break;
				case 'reopen':
					@$date = strtotime($v['date']);
					$change_fields[DAO_Ticket::DUE_DATE] = intval($date);
					break;
				case 'broadcast':
					if(isset($v['worker_id'])) {
						CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $v['worker_id'], $worker_labels, $worker_values);
					}
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
			
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
					$ids = array();
					break;
				case 'sender':
					$new_params = array(
						new DevblocksSearchCriteria(SearchFields_Ticket::SENDER_ADDRESS,DevblocksSearchCriteria::OPER_LIKE,$v)
					);
					$do_header = 'from';
					$ids = array();
					break;
				case 'header':
					$new_params = array(
						// [TODO] It will eventually come up that we need multiple header matches (which need to be pair grouped as OR)
						new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_HEADER,DevblocksSearchCriteria::OPER_EQ,$filter_param),
						new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_HEADER_VALUE,DevblocksSearchCriteria::OPER_EQ,$v)
					);
					$ids = array();
					break;
			}

			$new_params = array_merge($new_params, $params);
			$pg = 0;

			if(empty($ids)) {
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
					 
					$ids = array_merge($ids, array_keys($tickets));
					 
				} while(!empty($tickets));
			}
	   
			$batch_total = count($ids);
			for($x=0;$x<=$batch_total;$x+=200) {
				$batch_ids = array_slice($ids,$x,200);
				
				// Fields
				if(!empty($change_fields))
					DAO_Ticket::update($batch_ids, $change_fields);
				
				// Custom Fields
				self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_TICKET, $custom_fields, $batch_ids);
				
				// Spam
				if(isset($do['spam'])) {
					if(!empty($do['spam']['is_spam'])) {
						foreach($batch_ids as $batch_id)
							CerberusBayes::markTicketAsSpam($batch_id);
					} else {
						foreach($batch_ids as $batch_id)
							CerberusBayes::markTicketAsNotSpam($batch_id);
					}
				}
				
				// Scheduled behavior
				if(isset($do['behavior']) && is_array($do['behavior'])) {
					$behavior_id = $do['behavior']['id'];
					@$behavior_when = strtotime($do['behavior']['when']) or time();
					@$behavior_params = isset($do['behavior']['params']) ? $do['behavior']['params'] : array();
					
					if(!empty($batch_ids) && !empty($behavior_id))
					foreach($batch_ids as $batch_id) {
						DAO_ContextScheduledBehavior::create(array(
							DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
							DAO_ContextScheduledBehavior::CONTEXT => CerberusContexts::CONTEXT_TICKET,
							DAO_ContextScheduledBehavior::CONTEXT_ID => $batch_id,
							DAO_ContextScheduledBehavior::RUN_DATE => $behavior_when,
							DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($behavior_params),
						));
					}
				}
				
				// Watchers
				if(isset($do['watchers']) && is_array($do['watchers'])) {
					$watcher_params = $do['watchers'];
					foreach($batch_ids as $batch_id) {
						if(isset($watcher_params['add']) && is_array($watcher_params['add']))
							CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $batch_id, $watcher_params['add']);
						if(isset($watcher_params['remove']) && is_array($watcher_params['remove']))
							CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TICKET, $batch_id, $watcher_params['remove']);
					}
				}
				
				if(isset($do['broadcast'])) {
					$broadcast_params = $do['broadcast'];
					
					if(
						!isset($broadcast_params['worker_id']) || empty($broadcast_params['worker_id'])
						|| !isset($broadcast_params['message']) || empty($broadcast_params['message'])
						)
						break;
						
					list($tickets, $null) = DAO_Ticket::search(
						array(),
						array(
							SearchFields_Ticket::TICKET_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_ID,DevblocksSearchCriteria::OPER_IN,$batch_ids),
						),
						-1,
						0,
						null,
						true,
						false
					);
					$is_queued = (isset($broadcast_params['is_queued']) && $broadcast_params['is_queued']) ? true : false; 
					
					if(is_array($tickets))
					foreach($tickets as $ticket_id => $row) {
						CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $tpl_labels, $tpl_tokens);
						
						// Add the signature to the token_values
						// [TODO] This shouldn't be redundant with ::doBulkUpdateBroadcastTestAction()
						if(in_array('signature', $tpl_builder->tokenize($broadcast_params['message']))) {
							if(isset($tpl_tokens['group_id']) && null != ($sig_group = DAO_Group::get($tpl_tokens['group_id']))) {
								 $sig_template = $sig_group->getReplySignature(@intval($tpl_tokens['bucket_id']));

								 if(isset($worker_values)) {
									 if(false !== ($out = $tpl_builder->build($sig_template, $worker_values))) {
									 	$tpl_tokens['signature'] = $out;
									 }
								 }
							}
						}
						
						$body = $tpl_builder->build($broadcast_params['message'], $tpl_tokens);
						
						$fields = array(
							DAO_MailQueue::TYPE => Model_MailQueue::TYPE_TICKET_REPLY,
							DAO_MailQueue::TICKET_ID => $ticket_id,
							DAO_MailQueue::WORKER_ID => $broadcast_params['worker_id'],
							DAO_MailQueue::UPDATED => time(),
							DAO_MailQueue::HINT_TO => $row[SearchFields_Ticket::TICKET_FIRST_WROTE],
							DAO_MailQueue::SUBJECT => $row[SearchFields_Ticket::TICKET_SUBJECT],
							DAO_MailQueue::BODY => $body,
							DAO_MailQueue::PARAMS_JSON => json_encode(array(
								'in_reply_message_id' => $row[SearchFields_Ticket::TICKET_FIRST_MESSAGE_ID],
							)),
						);
						
						if($is_queued) {
							$fields[DAO_MailQueue::IS_QUEUED] = 1;
						}
						
						$draft_id = DAO_MailQueue::create($fields);
					}
				}				
				
				unset($batch_ids);
			}
		}

		unset($ids);
	}

	static function createSearchView() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$view = new View_Ticket();
		$view->id = CerberusApplication::VIEW_SEARCH;
		$view->name = $translate->_('common.search_results');
		$view->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_GROUP_ID,
			SearchFields_Ticket::TICKET_BUCKET_ID,
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
		
	function getMeta($context_id) {
		$ticket = DAO_Ticket::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		return array(
			'id' => $ticket->id,
			'name' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
			'permalink' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
			'owner_id' => $ticket->owner_id,
		);
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
		} else {
			$ticket = null;
		}
			
		// Token labels
		$token_labels = array(
			'created|date' => $prefix.$translate->_('ticket.created'),
			'id' => $prefix.$translate->_('ticket.id'),
			'mask' => $prefix.$translate->_('ticket.mask'),
			'num_messages' => $prefix.$translate->_('ticket.num_messages'),
			'reopen_date|date' => $prefix.$translate->_('ticket.reopen_date'),
			'spam_score' => $prefix.$translate->_('ticket.spam_score'),
			'spam_training' => $prefix.$translate->_('ticket.spam_training'),
			'status' => $prefix.$translate->_('common.status'),
			'subject' => $prefix.$translate->_('ticket.subject'),
			'updated|date' => $prefix.$translate->_('ticket.updated'),
			'url' => $prefix.$translate->_('common.url'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_TICKET;
		
		// Ticket token values
		if(null != $ticket) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = sprintf("#%s %s", $ticket->mask, $ticket->subject);
			$token_values['created'] = $ticket->created_date;
			$token_values['id'] = $ticket->id;
			$token_values['mask'] = $ticket->mask;
			$token_values['num_messages'] = $ticket->num_messages;
			$token_values['reopen_date'] = $ticket->due_date;
			$token_values['spam_score'] = $ticket->spam_score;
			$token_values['spam_training'] = $ticket->spam_training;
			$token_values['subject'] = $ticket->subject;
			$token_values['updated'] = $ticket->updated_date;
			$token_values['org_id'] = $ticket->org_id;
			
			// Status
			@$is_closed = intval($ticket->is_closed);
			@$is_waiting = intval($ticket->is_waiting);
			@$is_deleted = intval($ticket->is_deleted);
			if($is_deleted) {
				$token_values['status'] = 'deleted';
			} elseif($is_closed) {
				$token_values['status'] = 'closed';
			} elseif($is_waiting) {
				$token_values['status'] = 'waiting';
			} else {
				$token_values['status'] = 'open';
			}
			
			$token_values['custom'] = array();
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['url'] = $url_writer->writeNoProxy('c=display&mask='.$ticket->mask,true);

			// Group
			$token_values['group_id'] = $ticket->group_id;

			// Bucket
			$token_values['bucket_id'] = $ticket->bucket_id;
			
			// First message
			$token_values['initial_message_id'] = $ticket->first_message_id; 
			
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
			'Group:',
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
			'Bucket:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		// First message
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, null, $merge_token_labels, $merge_token_values, 'Message:', true);
		
		CerberusContexts::merge(
			'initial_message_',
			'Ticket:Initial:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		
		);
		
		// Last message
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, null, $merge_token_labels, $merge_token_values, 'Message:', true);
		
		CerberusContexts::merge(
			'latest_message_',
			'Ticket:Latest:',
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
					"#^address_first_name$#",
					"#^address_full_name$#",
					"#^address_last_name$#",
					"#^address_org_#",
				)
			);
		
			CerberusContexts::merge(
				'owner_',
				'Ticket:Owner:',
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
				'Ticket:Org:',
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
			CerberusContexts::getContext($context, $context_id, $labels, $values);
		}
		
		switch($token) {
			case 'requesters':
				$values['requesters'] = array();
				$reqs = DAO_Ticket::getRequestersByTicket($context_id);
				if(is_array($reqs))
				foreach($reqs as $req) { /* @var $req Model_Address */
					$values['requesters'][$req->id] = array(
						'email' => $req->email,
						'first_name' => $req->first_name,
						'last_name' => $req->last_name,
						'full_name' => $req->getName(),
						'org_id' => $req->contact_org_id,
					);
				}
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
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
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
			SearchFields_Ticket::TICKET_GROUP_ID,
			SearchFields_Ticket::TICKET_BUCKET_ID,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
		);
		$view->addParams(array(
			SearchFields_Ticket::VIRTUAL_STATUS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS,'in',array('open','waiting')),
			SearchFields_Ticket::VIRTUAL_WATCHERS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_WATCHERS,'in',array($active_worker->id)),
		), true);
		$view->addParamsRequired(array(
			SearchFields_Ticket::TICKET_GROUP_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_GROUP_ID,'in',array_keys($active_worker->getMemberships())),
		), true);
		$view->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = true;
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
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Ticket::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Ticket::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
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

class CerberusTicketStatus {
	const OPEN = 0;
	const CLOSED = 1;
};

class CerberusTicketSpamTraining { // [TODO] Append 'Enum' to class name?
	const BLANK = '';
	const NOT_SPAM = 'N';
	const SPAM = 'S';
};

class CerberusTicketActionCode {
	const TICKET_OPENED = 'O';
	const TICKET_CUSTOMER_REPLY = 'R';
	const TICKET_WORKER_REPLY = 'W';
};