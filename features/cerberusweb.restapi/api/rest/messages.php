<?php
class ChRest_Messages extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
				default:
					$this->error(self::ERRNO_NOT_IMPLEMENTED);
					break;
			}
		}
	}
	
	function putAction($stack) {
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
		$worker = $this->getActiveWorker();
		if(!$worker->hasPriv('core.display.message.actions.delete'))
			$this->error(self::ERRNO_ACL);

		$id = array_shift($stack);

		if(null == ($message = DAO_Message::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid message ID %d", $id));

		DAO_Message::delete($id);
		$result = array('id' => $id);
		$this->success($result);		
	}
	
	private function getId($id) {
		$worker = $this->getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid message id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
//				'example' => DAO_Example::PROPERTY,
			);
		} else {
			$tokens = array(
				'created' => SearchFields_Message::CREATED_DATE,
				'content' => SearchFields_Message::MESSAGE_CONTENT,
				'id' => SearchFields_Message::ID,
				'sender_id' => SearchFields_Message::ADDRESS_ID,
				'ticket_id' => SearchFields_Message::TICKET_ID,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $id, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$memberships = $worker->getMemberships();
			$params['tmp_worker_memberships'] = new DevblocksSearchCriteria(
				SearchFields_Message::TICKET_GROUP_ID,
				'in',
				(!empty($memberships) ? array_keys($memberships) : array(0))
			);
		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_Message::search(
//			array(),
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
	
	function postSearch() {
		$worker = $this->getActiveWorker();
		
		// ACL
// 		if(!$worker->hasPriv('core.mail.search'))
// 			$this->error(self::ERRNO_ACL, 'Access denied to search tickets.');
		
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};