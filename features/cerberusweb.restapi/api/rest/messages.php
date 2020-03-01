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
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.message.delete'))
			$this->error(self::ERRNO_ACL);

		$id = array_shift($stack);

		if(null == ($message = DAO_Message::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid message ID %d", $id));
		
		CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_MESSAGE, $id);
		
		DAO_Message::delete($id);
		$result = array('id' => $id);
		$this->success($result);
	}
	
	private function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid message id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'is_broadcast' => DAO_Message::IS_BROADCAST,
				'is_outgoing' => DAO_Message::IS_OUTGOING,
				'ticket_id' => DAO_Message::TICKET_ID,
				'worker_id' => DAO_Message::WORKER_ID,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'is_broadcast' => SearchFields_Message::IS_BROADCAST,
				'is_outgoing' => SearchFields_Message::IS_OUTGOING,
				'sender_address' => SearchFields_Message::ADDRESS_EMAIL,
				'ticket_group' => SearchFields_Message::TICKET_GROUP_ID,
				'worker' => SearchFields_Message::WORKER_ID,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_MESSAGE);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'created' => SearchFields_Message::CREATED_DATE,
				'content' => SearchFields_Message::MESSAGE_CONTENT,
				'id' => SearchFields_Message::ID,
				'is_broadcast' => SearchFields_Message::IS_BROADCAST,
				'is_outgoing' => SearchFields_Message::IS_OUTGOING,
				'sender_id' => SearchFields_Message::ADDRESS_ID,
				'ticket_id' => SearchFields_Message::TICKET_ID,
				'worker_id' => SearchFields_Message::WORKER_ID,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $model, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$worker = CerberusApplication::getActiveWorker();

		$params = array();
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;

		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_MESSAGE,
			$params,
			$limit,
			$page,
			$sortBy,
			$sortAsc
		);
		
		if(!empty($query) && $view instanceof IAbstractView_QuickSearch)
			$view->addParamsWithQuickSearch($query, true);

		// If we're given explicit filters, merge them in to our quick search
		if(!empty($filters)) {
			if(!empty($query))
				$params = $view->getParams(false);
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_MESSAGE);
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params, $custom_field_params);
			
			$view->addParams($params, true);
		}
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$params['tmp_worker_memberships'] = new DevblocksSearchCriteria(
				SearchFields_Message::VIRTUAL_TICKET_SEARCH,
				DevblocksSearchCriteria::OPER_CUSTOM,
				"inGroupsOf:me"
			);
		}
		
		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleSearchSubtotals($view, $subtotals);
		
		if($show_results) {
			$objects = array();
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_MESSAGE, array_keys($results));
			
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
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
// 		if(!$worker->hasPriv('core.mail.search'))
// 			$this->error(self::ERRNO_ACL, 'Access denied to search tickets.');
		
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};