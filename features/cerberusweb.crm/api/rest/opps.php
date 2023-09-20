<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class ChRest_Opps extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->getId(intval($action));
			
		} else { // actions
			switch($action) {
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
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
		
		if(is_numeric($action) && !empty($stack)) {
			$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
				case 'note':
					$this->postNote($id);
					break;
			}

		} else {
			switch($action) {
				case 'create':
					$this->postCreate();
					break;
				case 'search':
					$this->postSearch();
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, $model, $labels, $values, null, true);

//		unset($dict->initial_message_content);
		
		return $values;
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}

		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'amount' => DAO_CrmOpportunity::AMOUNT,
				'created' => DAO_CrmOpportunity::CREATED_DATE,
				'status_id' => DAO_CrmOpportunity::STATUS_ID,
				'title' => DAO_CrmOpportunity::NAME,
				'updated' => DAO_CrmOpportunity::UPDATED_DATE,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_CrmOpportunity::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_CrmOpportunity::VIRTUAL_CONTEXT_LINK,
				'watchers' => SearchFields_CrmOpportunity::VIRTUAL_WATCHERS,
					
				'status_id' => SearchFields_CrmOpportunity::STATUS_ID,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'amount' => SearchFields_CrmOpportunity::CURRENCY_AMOUNT,
				'currency_id' => SearchFields_CrmOpportunity::CURRENCY_ID,
				'created' => SearchFields_CrmOpportunity::CREATED_DATE,
				'id' => SearchFields_CrmOpportunity::ID,
				'status_id' => SearchFields_CrmOpportunity::STATUS_ID,
				'title' => SearchFields_CrmOpportunity::NAME,
				'updated' => SearchFields_CrmOpportunity::UPDATED_DATE,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid opportunity id '%d'", $id));
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
			CerberusContexts::CONTEXT_OPPORTUNITY,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_OPPORTUNITY);
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params, $custom_field_params);
			
			$view->addParams($params, true);
		}
		
		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleSearchSubtotals($view, $subtotals);
		
		if($show_results) {
			$objects = array();
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_OPPORTUNITY, array_keys($results));
			
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
		
		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// Validate the ID
		if(null == ($opp = DAO_CrmOpportunity::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid opportunity ID '%d'", $id));
			
		// ACL
		if(!($worker->hasPriv('contexts.cerberusweb.contexts.opportunity.update') || $opp->worker_id==$worker->id))
			$this->error(self::ERRNO_ACL);
		
		$putfields = array(
			'amount' => 'float',
			'created' => 'timestamp',
			'currency_id' => 'integer',
			'status_id' => 'integer',
			'title' => 'string',
			'updated' => 'timestamp',
		);

		$fields = array();

		foreach($putfields as $putfield => $type) {
			if(!isset($_POST[$putfield]))
				continue;
			
			@$value = DevblocksPlatform::importGPC($_POST[$putfield], 'string', '');
			
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);

			// Pre-filter
			switch($putfield) {
			}
			
			if(null == ($field = self::translateToken($putfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $putfield));
			}
			
			// Post-filter
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_CrmOpportunity::UPDATED_DATE]))
			$fields[DAO_CrmOpportunity::UPDATED_DATE] = time();
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_OPPORTUNITY, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_CrmOpportunity::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.opportunity.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'amount' => 'float',
			'created' => 'timestamp',
			'status_id' => 'integer',
			'title' => 'string',
			'updated' => 'timestamp',
		);

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
				
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			// Pre-filter
			switch($postfield) {
			}
			
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
			}
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_CrmOpportunity::CREATED_DATE]))
			$fields[DAO_CrmOpportunity::CREATED_DATE] = time();
		if(!isset($fields[DAO_CrmOpportunity::UPDATED_DATE]))
			$fields[DAO_CrmOpportunity::UPDATED_DATE] = time();
		
		// Check required fields
		$reqfields = array(
			DAO_CrmOpportunity::NAME,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_CrmOpportunity::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_OPPORTUNITY, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}
	
	private function postNote($id) {
		$worker = CerberusApplication::getActiveWorker();

		$note = DevblocksPlatform::importGPC($_POST['note'] ?? null, 'string','');
		
		if(null == ($opp = DAO_CrmOpportunity::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid opp ID %d", $id));

		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.opportunity.update'))
			$this->error(self::ERRNO_ACL);
		
		// Required fields
		if(empty($note))
			$this->error(self::ERRNO_CUSTOM, "The 'note' field is required.");
			
		// Post
		$fields = [
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_OPPORTUNITY,
			DAO_Comment::CONTEXT_ID => $opp->id,
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $note,
		];
		$note_id = DAO_Comment::create($fields);
		DAO_Comment::onUpdateByActor($worker, $fields, $note_id);
			
		$this->success(array(
			'opp_id' => $opp->id,
			'note_id' => $note_id,
		));
	}
};