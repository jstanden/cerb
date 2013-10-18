<?php
class ChRest_Tickets extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(empty($stack) && is_numeric($action)) {
			$this->getId(intval($action));
			
		} else {
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		
		// Updating a single ticket ID?
		if(is_numeric($action)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
				case 'requester':
					$this->putRequester(intval($id));
					break;
					
				default:
					$this->putId(intval($id));
					break;
			}
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);

		if(is_numeric($action) && !empty($stack)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
				case 'comment':
					$this->postComment($id);
					break;
			}
			
		} else {
			switch($action) {
				case 'compose':
					$this->postCompose();
					break;
					
				case 'search':
					$this->postSearch();
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		@$action = array_shift($stack);

		// Delete a single ID?
		if(is_numeric($action)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
				case 'requester':
					$this->deleteRequester(intval($id));
					break;
				default:
					$this->deleteId(intval($id));
					break;
			}
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $id, $labels, $values, null, true);

		unset($values['initial_message_content']);
		unset($values['latest_message_content']);
		
		return $values;
	}
	
	private function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// Internally search (checks ACL via groups)
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket id '%d'", $id));
	}
	
	private function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		$workers = DAO_Worker::getAll();
		
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$is_waiting = DevblocksPlatform::importGPC($_REQUEST['is_waiting'],'string','');
		@$is_closed = DevblocksPlatform::importGPC($_REQUEST['is_closed'],'string','');
		@$is_deleted = DevblocksPlatform::importGPC($_REQUEST['is_deleted'],'string','');
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to modify tickets in this group.');
		
		$fields = array(
//			DAO_Ticket::UPDATED_DATE => time(),
		);
		
		$custom_fields = array(); // [TODO]
		
		if(0 != strlen($subject))
			$fields[DAO_Ticket::SUBJECT] = $subject;
			
		if(0 != strlen($is_waiting))
			$fields[DAO_Ticket::IS_WAITING] = !empty($is_waiting) ? 1 : 0;
		
		// Close
		if(0 != strlen($is_closed)) {
			// ACL
			if(!$worker->hasPriv('core.ticket.actions.close'))
				$this->error(self::ERRNO_ACL, 'Access denied to close tickets.');
			
			$fields[DAO_Ticket::IS_CLOSED] = !empty($is_closed) ? 1 : 0;
		}
			
		// Delete
		if(0 != strlen($is_deleted)) {
			// ACL
			if(!$worker->hasPriv('core.ticket.actions.delete'))
				$this->error(self::ERRNO_ACL, 'Access denied to delete tickets.');

			$fields[DAO_Ticket::IS_DELETED] = !empty($is_deleted) ? 1 : 0;
		}
			
		// Assign
		// [TODO] Redo w/ owners + contexts
			// ACL
//			if(!$worker->hasPriv('core.ticket.actions.assign'))
//				$this->error(self::ERRNO_ACL, 'Access denied to assign tickets.');
		
		if(!empty($fields))
			DAO_Ticket::update($id, $fields);
			
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TICKET, $id, $customfields, true, true, true);

		// Update
		DAO_Ticket::update($id, $fields);
		$this->getId($id);
	}
	
	public function putRequester($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to modify tickets in this group.');

		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
			
		if(!empty($email))
			DAO_Ticket::createRequester($email, $id);
		
		$this->getId($id);
	}
	
	private function deleteId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.ticket.actions.delete'))
			$this->error(self::ERRNO_ACL, 'Access denied to delete tickets.');

		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to delete tickets in this group.');
			
		DAO_Ticket::update($ticket->id, array(
			DAO_Ticket::IS_CLOSED => 1,
			DAO_Ticket::IS_DELETED => 1,
		));
		
		$result = array('id'=> $id);
		
		$this->success($result);
	}
	
	public function deleteRequester($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to modify tickets in this group.');

		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		if(!empty($email)) {
			if(null != ($email = DAO_Address::lookupAddress($email, false))) {
				DAO_Ticket::deleteRequester($id, $email->id);
			} else {
				$this->error(self::ERRNO_CUSTOM, $email . ' is not a valid requester on ticket ' . $id);
			}
		}
		
		$this->getId($id);
	}
	
	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'id' => DAO_Ticket::ID,
				'is_closed' => DAO_Ticket::IS_CLOSED,
				'is_deleted' => DAO_Ticket::IS_DELETED,
				'mask' => DAO_Ticket::MASK,
				'subject' => DAO_Ticket::SUBJECT,
			);
		} else {
			$tokens = array(
				'content' => SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT,
				'created' => SearchFields_Ticket::TICKET_CREATED_DATE,
				'first_wrote' => SearchFields_Ticket::TICKET_FIRST_WROTE,
				'id' => SearchFields_Ticket::TICKET_ID,
				'is_closed' => SearchFields_Ticket::TICKET_CLOSED,
				'is_deleted' => SearchFields_Ticket::TICKET_DELETED,
				'last_wrote' => SearchFields_Ticket::TICKET_LAST_WROTE,
				'mask' => SearchFields_Ticket::TICKET_MASK,
				'requester' => SearchFields_Ticket::REQUESTER_ADDRESS,
				'subject' => SearchFields_Ticket::TICKET_SUBJECT,
				'updated' => SearchFields_Ticket::TICKET_UPDATED_DATE,
				'group' => SearchFields_Ticket::TICKET_GROUP_ID,
				'group_id' => SearchFields_Ticket::TICKET_GROUP_ID,
				'bucket_id' => SearchFields_Ticket::TICKET_BUCKET_ID,
				'org_id' => SearchFields_Ticket::TICKET_ORG_ID,
				'org_name' => SearchFields_Ticket::ORG_NAME
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function search($filters=array(), $sortToken='updated', $sortAsc=0, $page=1, $limit=10) {
		$worker = CerberusApplication::getActiveWorker();

		$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_TICKET);
		$params = $this->_handleSearchBuildParams($filters);
		$params = array_merge($params, $custom_field_params);
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$memberships = $worker->getMemberships();
			$params['tmp_worker_memberships'] = new DevblocksSearchCriteria(
				SearchFields_Ticket::TICKET_GROUP_ID,
				'in',
				(!empty($memberships) ? array_keys($memberships) : array(0))
			);
		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		
		list($results, $total) = DAO_Ticket::search(
			!empty($sortBy) ? array($sortBy) : array(),
			$params,
			$limit,
			max(0,$page-1),
			$sortBy,
			$sortAsc,
			true
		);
		
		$objects = array();
		
		foreach($results as $id => $result) {
			$values = $this->getContext($id);
			$objects[$id] = $values;
		}
		
		$container = array(
			'total' => $total,
			'count' => count($objects),
			'page' => $page,
			'results' => $objects,
		);
		
		return $container;
	}
	
	private function postSearch() {
		$worker = CerberusApplication::getActiveWorker();

		// ACL
// 		if(!$worker->hasPriv('core.mail.search'))
// 			$this->error(self::ERRNO_ACL, 'Access denied to search tickets.');

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	private function _handlePostCompose() {
		$worker = CerberusApplication::getActiveWorker();
		
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
		@$cc = DevblocksPlatform::importGPC($_REQUEST['cc'],'string','');
		@$bcc = DevblocksPlatform::importGPC($_REQUEST['bcc'],'string','');
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		@$file_ids = DevblocksPlatform::importGPC($_REQUEST['file_id'],'array',array());
		
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$reopen_at = DevblocksPlatform::importGPC($_REQUEST['reopen_at'],'integer',0);
		
		$properties = array();
		
		if(empty($group_id))
			$this->error(self::ERRNO_CUSTOM, "The 'group_id' parameter is required");
		
		if(empty($to))
			$this->error(self::ERRNO_CUSTOM, "The 'to' parameter is required");
		
		if(empty($subject))
			$this->error(self::ERRNO_CUSTOM, "The 'subject' parameter is required");
		
		if(empty($content))
			$this->error(self::ERRNO_CUSTOM, "The 'content' parameter is required");

		if(!empty($file_ids))
			$file_ids = DevblocksPlatform::sanitizeArray($file_ids, 'integer', array('nonzero','unique'));
		
		$properties = array(
			'group_id' => $group_id,
			'bucket_id' => $bucket_id,
			'org_id' => $org_id,
			'to' => $to,
			'subject' => $subject,
			'content' => $content,
			'worker_id' => $worker->id,
		);

		if(!empty($cc))
			$properties['cc'] = $cc;
		
		if(!empty($bcc))
			$properties['bcc'] = $bcc;
		
		if(!empty($status) && in_array($status, array(0,1,2)))
			$properties['closed'] = $status;
		
		if(!empty($reopen_at))
			$properties['reopen_at'] = $reopen_at;
		
		if(!empty($file_ids)) {
			$properties['link_forward_files'] = true;
			$properties['forward_files'] = $file_ids;
		}
		
		if(false == ($ticket_id = CerberusMail::compose($properties)))
			$this->error(self::ERRNO_CUSTOM, "Failed to create a new message.");
		
		// Handle custom fields
		$custom_fields = $this->_handleCustomFields($_POST);
		
		if(is_array($custom_fields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TICKET, $ticket_id, $custom_fields, true, true, true);
		
		return $ticket_id;
	}
	
	private function postCompose() {
		$worker = CerberusApplication::getActiveWorker();

		// ACL
		if(!$worker->hasPriv('core.mail.send'))
			$this->error(self::ERRNO_ACL, 'Access denied to compose mail.');
		
		$ticket_id = $this->_handlePostCompose();
		$this->getId($ticket_id);
	}
	
	private function postComment($id) {
		$worker = CerberusApplication::getActiveWorker();

		@$comment = DevblocksPlatform::importGPC($_POST['comment'],'string','');
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid ticket ID %d", $id));
			
		// Check group memberships
		$memberships = $worker->getMemberships();
		if(!$worker->is_superuser && !isset($memberships[$ticket->group_id]))
			$this->error(self::ERRNO_ACL, 'Access denied to delete tickets in this group.');
		
		// Worker address exists
		if(null === ($address = CerberusApplication::hashLookupAddress($worker->email,true)))
			$this->error(self::ERRNO_CUSTOM, 'Your worker does not have a valid e-mail address.');
		
		// Required fields
		if(empty($comment))
			$this->error(self::ERRNO_CUSTOM, "The 'comment' field is required.");
			
		$fields = array(
			DAO_Comment::CREATED => time(),
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
			DAO_Comment::CONTEXT_ID => $ticket->id,
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
			DAO_Comment::COMMENT => $comment,
		);
		$comment_id = DAO_Comment::create($fields);

		$this->success(array(
			'ticket_id' => $ticket->id,
			'comment_id' => $comment_id,
		));
	}
	
};