<?php
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
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}

	function translateToken($token, $type='dao') {
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
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_FEEDBACK, $id, $labels, $values, null, true);

//		unset($values['initial_message_content']);

		return $values;
	}
	
	function getId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('plugin.cerberusweb.feedback'))
			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid feedback id '%d'", $id));
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_FeedbackEntry::search(
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
	
	function postSearch() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('plugin.cerberusweb.feedback'))
			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}	
	
	function putId($id) {
		$worker = $this->getActiveWorker();
		
		// Validate the ID
		if(null == ($feedback = DAO_FeedbackEntry::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid feedback ID '%d'", $id));
			
		// ACL
		if(!($worker->hasPriv('feedback.actions.update_all') || $feedback->worker_id == $worker->id))
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
						unset($putfields['author_id']);
						$putfield = 'author_id';
						$value = $lookup->id;
					}
					break;
					
				case 'quote_mood':
					switch(strtolower($value)) {
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
						
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
			$fields[$field] = $value;
		}
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_FEEDBACK, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_FeedbackEntry::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('feedback.actions.create'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'author_address' => 'string',
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
						unset($postfields['author_id']);
						$postfield = 'author_id';
						$value = $lookup->id;
					}
					break;
					
				case 'quote_mood':
					switch(strtolower($value)) {
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
			
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
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