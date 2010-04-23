<?php
class ChRest_Notifications extends Extension_RestController implements IExtensionRestController {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single notification ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
				case 'list':
					$this->getList();
					break;
				default:
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
//				'is_banned' => DAO_WorkerEvent::IS_BANNED,
//				'is_registered' => DAO_WorkerEvent::IS_REGISTERED,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_WorkerEvent::ID,
				'is_read' => SearchFields_WorkerEvent::IS_READ,
				'worker_id' => SearchFields_WorkerEvent::WORKER_ID,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_NOTIFICATION, $id, $labels, $values, null, true);

//		unset($values['latest_message_content']);

		return $values;
	} 
	
	private function getId($id) {
		$worker = $this->getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid notification id '%d'", $id));
	}
	
	private function getList() {
		$worker = $this->getActiveWorker();

		@$page = DevblocksPlatform::importGPC($_REQUEST['page'],'integer',1);
		@$unread = DevblocksPlatform::importGPC($_REQUEST['unread'],'string','');
		
		$filters = array(
			array('worker_id', '=', $worker->id),
		);
		
		if(0 != strlen($unread)) {
			$filters[] = array('is_read', '=', ($unread ? 0 : 1));
		}
		
		$container = $this->search(
			$filters,
			null,
			null,
			$page,
			10
		);
		
		$this->success($container);
	}

	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$params['tmp_worker_id'] = new DevblocksSearchCriteria(
				SearchFields_WorkerEvent::WORKER_ID,
				'=',
				$worker->id
			);
		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_WorkerEvent::search(
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
//		if(!$worker->hasPriv('core.addybook'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}	
};
