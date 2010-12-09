<?php
class ChRest_KbArticles extends Extension_RestController implements IExtensionRestController {
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
	
	private function getId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('plugin.cerberusweb.kb'))
			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid article id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'content' => DAO_KbArticle::CONTENT,
				'id' => DAO_KbArticle::ID,
				'format' => DAO_KbArticle::FORMAT,
				'title' => DAO_KbArticle::TITLE,
				'updated' => DAO_KbArticle::UPDATED,
				'views' => DAO_KbArticle::VIEWS,
			);
		} else {
			$tokens = array(
				'category_id' => SearchFields_KbArticle::CATEGORY_ID,
				'content' => SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT,
				'id' => SearchFields_KbArticle::ID,
				'format' => SearchFields_KbArticle::FORMAT,
				'title' => SearchFields_KbArticle::TITLE,
				'updated' => SearchFields_KbArticle::UPDATED,
				'views' => SearchFields_KbArticle::VIEWS,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_KB_ARTICLE, $id, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_KbArticle::search(
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
		if(!$worker->hasPriv('plugin.cerberusweb.kb'))
			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = $this->getActiveWorker();
		
		// Validate the ID
		if(null == ($article = DAO_KbArticle::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid article ID '%d'", $id));
			
		// ACL
		if(!($worker->hasPriv('core.kb.articles.modify')))
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'content' => 'string',
			'format' => 'integer',
			'title' => 'string',
			'updated' => 'timestamp',
			'views' => 'integer',
		);

		$fields = array();

		foreach($putfields as $putfield => $type) {
			if(!isset($_POST[$putfield]))
				continue;
			
			@$value = DevblocksPlatform::importGPC($_POST[$putfield], 'string', '');
			
			if(null == ($field = self::translateToken($putfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $putfield));
			}
			
			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
						
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_KbArticle::UPDATED]))
			$fields[DAO_KbArticle::UPDATED] = time();
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_KB_ARTICLE, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_KbArticle::update($id, $fields);
		
		// Handle delta categories
		if(isset($_POST['category_id'])) {
			$category_ids = !is_array($_POST['category_id']) ? array($_POST['category_id']) : $_POST['category_id'];
			DAO_KbArticle::setCategories($id, $category_ids, false);
		}
		
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.kb.articles.modify'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'content' => 'string',
			'format' => 'integer',
			'title' => 'string',
			'updated' => 'timestamp',
			'views' => 'integer',
		);

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
				
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
			}

			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
			$fields[$field] = $value;
		}

		// Defaults
		if(!isset($fields[DAO_KbArticle::UPDATED]))
			$fields[DAO_KbArticle::UPDATED] = time();
		if(!isset($fields[DAO_KbArticle::FORMAT]))
			$fields[DAO_KbArticle::FORMAT] = Model_KbArticle::FORMAT_HTML;
			
		// Check required fields
		$reqfields = array(
			DAO_KbArticle::TITLE, 
			DAO_KbArticle::CONTENT, 
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_KbArticle::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_KB_ARTICLE, $id, $customfields, true, true, true);
			
			// Handle delta categories
			if(isset($_POST['category_id'])) {
				$category_ids = !is_array($_POST['category_id']) ? array($_POST['category_id']) : $_POST['category_id'];
				DAO_KbArticle::setCategories($id, $category_ids, false);
			}
				
			$this->getId($id);
		}
	}	
};