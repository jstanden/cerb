<?php
class ChRest_Tickets extends Extension_RestController implements IExtensionRestController {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
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
			$this->putId(intval($action));
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			case 'search':
				$this->postSearch();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		@$action = array_shift($stack);
		
		// Delete a single ID?
		if(is_numeric($action)) {
			$this->deleteId(intval($action));
			
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
		$worker = $this->getActiveWorker();
		
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
		$worker = $this->getActiveWorker();
		$workers = DAO_Worker::getAll();
		
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$is_waiting = DevblocksPlatform::importGPC($_REQUEST['is_waiting'],'string','');
		@$is_closed = DevblocksPlatform::importGPC($_REQUEST['is_closed'],'string','');
		@$is_deleted = DevblocksPlatform::importGPC($_REQUEST['is_deleted'],'string','');
		@$next_worker_id = DevblocksPlatform::importGPC($_REQUEST['next_worker_id'],'string','');
		
		// [TODO] Check group memberships

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
		if(0 != strlen($next_worker_id)) {
			// ACL
			if(!$worker->hasPriv('core.ticket.actions.assign'))
				$this->error(self::ERRNO_ACL, 'Access denied to assign tickets.');

			$next_worker_id = intval($next_worker_id);
			
			// if valid
			if(0==$next_worker_id || isset($workers[$next_worker_id]))
				$fields[DAO_Ticket::NEXT_WORKER_ID] = intval($next_worker_id);
		}
		
		if(!empty($fields))
			DAO_Ticket::updateTicket(intval($id), $fields);
			
		// [TODO] Set custom fields
		
		$result = array('id'=> $id);
		
		$this->success($result);
	}
	
	private function deleteId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.ticket.actions.delete'))
			$this->error(self::ERRNO_ACL, 'Access denied to delete tickets.');

		// [TODO] Check group memberships
		
		DAO_Ticket::updateTicket(intval($id), array(
			DAO_Ticket::IS_CLOSED => 1,
			DAO_Ticket::IS_DELETED => 1,
		));
		
		$result = array('id'=> $id);
		
		$this->success($result);
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
				'id' => SearchFields_Ticket::TICKET_ID,
				'is_closed' => SearchFields_Ticket::TICKET_CLOSED,
				'is_deleted' => SearchFields_Ticket::TICKET_DELETED,
				'mask' => SearchFields_Ticket::TICKET_MASK,
				'subject' => SearchFields_Ticket::TICKET_SUBJECT,
				'updated' => SearchFields_Ticket::TICKET_UPDATED_DATE,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function search($filters=array(), $sortToken='updated', $sortAsc=0, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$memberships = $worker->getMemberships();
			$params['tmp_worker_memberships'] = new DevblocksSearchCriteria(
				SearchFields_Ticket::TICKET_TEAM_ID,
				'in',
				(!empty($memberships) ? array_keys($memberships) : array(0))
			);
		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		
		list($results, $total) = DAO_Ticket::search(
			array(),
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
		$worker = $this->getActiveWorker();

		// ACL
		if(!$worker->hasPriv('core.mail.search'))
			$this->error(self::ERRNO_ACL, 'Access denied to search tickets.');

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
};