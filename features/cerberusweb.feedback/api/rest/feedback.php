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

class ChRest_Feedback extends Extension_RestController implements IExtensionRestController {
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
		
		switch($action) {
			case 'create':
				$this->postCreate();
				break;
			case 'search':
				$this->postSearch();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$worker = CerberusApplication::getActiveWorker();

		if(!$worker->hasPriv('contexts.cerberusweb.contexts.feedback.delete'))
			$this->error(self::ERRNO_ACL);

		$id = array_shift($stack);
		
		if(null == ($feedbackEntry = DAO_FeedbackEntry::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid feedback id %d", $id));

		DAO_FeedbackEntry::delete($id);

		$result = array('id' => $id);
		$this->success($result);
	}

	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'author_id' => DAO_FeedbackEntry::QUOTE_ADDRESS_ID,
				'created' => DAO_FeedbackEntry::LOG_DATE,
				'quote_mood_id' => DAO_FeedbackEntry::QUOTE_MOOD,
				'quote_text' => DAO_FeedbackEntry::QUOTE_TEXT,
				'url' => DAO_FeedbackEntry::SOURCE_URL,
				'worker_id' => DAO_FeedbackEntry::WORKER_ID,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_FeedbackEntry::VIRTUAL_HAS_FIELDSET,
				'watchers' => SearchFields_FeedbackEntry::VIRTUAL_WATCHERS,
					
				'author_address' => SearchFields_FeedbackEntry::ADDRESS_EMAIL,
				'quote_mood' => SearchFields_FeedbackEntry::QUOTE_MOOD,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_FEEDBACK);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'author_address' => SearchFields_FeedbackEntry::ADDRESS_EMAIL,
				'author_id' => SearchFields_FeedbackEntry::QUOTE_ADDRESS_ID,
				'created' => SearchFields_FeedbackEntry::LOG_DATE,
				'id' => SearchFields_FeedbackEntry::ID,
				'quote_mood_id' => SearchFields_FeedbackEntry::QUOTE_MOOD,
				'quote_text' => SearchFields_FeedbackEntry::QUOTE_TEXT,
				'worker_id' => SearchFields_FeedbackEntry::WORKER_ID,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_FEEDBACK, $model, $labels, $values, null, true);

		unset($values['quote_mood_id']);

		return $values;
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid feedback id '%d'", $id));
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$worker = CerberusApplication::getActiveWorker();

		foreach($filters as $k => $filter) {
			switch($k) {
				case 'quote_mood':
					switch(DevblocksPlatform::strLower($filter[2])) {
						case 'praise':
							$filter[0] = 'quote_mood_id';
							$filter[2] = 1;
							break;
						case 'neutral':
							$filter[0] = 'quote_mood_id';
							$filter[2] = 0;
							break;
						case 'criticism':
							$filter[0] = 'quote_mood_id';
							$filter[2] = 2;
							break;
					}
					$filters[$k] = $filter;
					break;
			}
		}
		
		$params = array();
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_FEEDBACK,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_FEEDBACK);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_FEEDBACK, array_keys($results));
			
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
		if(null == ($feedback = DAO_FeedbackEntry::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid feedback ID '%d'", $id));
			
		// ACL
		if(!($worker->hasPriv('contexts.cerberusweb.contexts.feedback.update') || $feedback->worker_id == $worker->id))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'author_address' => 'string',
			'created' => 'timestamp',
			'quote_mood' => 'string',
			'quote_text' => 'string',
			'url' => 'string',
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
				case 'author_address':
					if(null != ($lookup = DAO_Address::lookupAddress($value, true))) {
						unset($putfields['author_address']);
						$putfield = 'author_id';
						$value = $lookup->id;
					}
					break;
					
				case 'quote_mood':
					switch(DevblocksPlatform::strLower($value)) {
						case 'praise':
							unset($putfields['quote_mood_id']);
							$putfield = 'quote_mood_id';
							$value = 1;
							break;
						case 'neutral':
							unset($putfields['quote_mood_id']);
							$putfield = 'quote_mood_id';
							$value = 0;
							break;
						case 'criticism':
							unset($putfields['quote_mood_id']);
							$putfield = 'quote_mood_id';
							$value = 2;
							break;
					}
					break;
			}
			
			if(null == ($field = self::translateToken($putfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $putfield));
			}

			$fields[$field] = $value;
		}
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_FEEDBACK, $id, $customfields, true, true, true);
		
		// Update
		DAO_FeedbackEntry::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.feedback.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'author_address' => 'string',
			'author_id' => 'integer',
			'created' => 'timestamp',
			'quote_mood' => 'string',
			'quote_text' => 'string',
			'url' => 'string',
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
				case 'author_address':
					if(null != ($lookup = DAO_Address::lookupAddress($value, true))) {
						unset($postfields['author_address']);
						$postfield = 'author_id';
						$value = $lookup->id;
					}
					break;
					
				case 'quote_mood':
					switch(DevblocksPlatform::strLower($value)) {
						case 'praise':
							unset($postfields['quote_mood_id']);
							$postfield = 'quote_mood_id';
							$value = 1;
							break;
						case 'neutral':
							unset($postfields['quote_mood_id']);
							$postfield = 'quote_mood_id';
							$value = 0;
							break;
						case 'criticism':
							unset($postfields['quote_mood_id']);
							$postfield = 'quote_mood_id';
							$value = 2;
							break;
					}
					break;
			}
			
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
			}
			
			$fields[$field] = $value;
		}

		// Defaults
		if(!isset($fields[DAO_FeedbackEntry::LOG_DATE]))
			$fields[DAO_FeedbackEntry::LOG_DATE] = time();
		
		// Check required fields
		$reqfields = array(
			DAO_FeedbackEntry::QUOTE_TEXT,
			DAO_FeedbackEntry::QUOTE_ADDRESS_ID,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_FeedbackEntry::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_FEEDBACK, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}
};