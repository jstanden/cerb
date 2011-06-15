<?php
class ChRest_Groups extends Extension_RestController implements IExtensionRestController {
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
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
//		$worker = $this->getActiveWorker();
//
//		$id = array_shift($stack);
//
//		if(null == ($group = DAO_Group::get($id)))
//			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid group ID %d", $id));
//
//		DAO_Group::delete($id);
//		$result = array('id' => $id);
//		$this->success($result);		
	}
	
	private function getId($id) {
		$worker = $this->getActiveWorker();
		$memberships = $worker->getMemberships();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id])) {
			if(!in_array($id, array_keys($memberships))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("Permission denied for group id '%d'", $id));
			} else {
				$this->success($container['results'][$id]);	
			}
		}
		
		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid group id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
//				'example' => DAO_Example::PROPERTY,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_Group::ID,
				'name' => SearchFields_Group::NAME,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $id, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$memberships = $worker->getMemberships();
			$params['tmp_worker_memberships'] = new DevblocksSearchCriteria(
				SearchFields_Group::ID,
				'in',
				(!empty($memberships) ? array_keys($memberships) : array(0))
			);
		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_Group::search(
			array($sortBy),
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
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};