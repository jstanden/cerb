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
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
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
	}
	
	private function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
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
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_Group::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_Group::VIRTUAL_CONTEXT_LINK,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_GROUP);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
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
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $model, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$worker = CerberusApplication::getActiveWorker();

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
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_GROUP,
			$params,
			$limit,
			$page,
			$sortBy,
			$sortAsc
		);
		
		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleSearchSubtotals($view, $subtotals);
		
		if($show_results) {
			$objects = array();
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_GROUP, array_keys($results));
			
			unset($results);
			
			foreach($models as $id => $model) {
				$values = $this->getContext($model);
				$objects[$id] = $values;
			}
		}
		
		$container = array();
		
		if($show_results) {
			$container['results'] = $objects;
			$container['total'] = $total;
			$container['count'] = count($objects);
			$container['page'] = $page;
		}
		
		if(!empty($subtotals)) {
			$container['subtotals'] = $subtotal_data;
		}
		
		return $container;
	}
	
	function postSearch() {
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};