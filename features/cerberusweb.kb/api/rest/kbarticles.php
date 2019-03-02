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
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.kb_article.delete'))
			$this->error(self::ERRNO_ACL);

		$id = array_shift($stack);

		if(null == ($kbarticle = DAO_KbArticle::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid Knowledgebase article ID %d", $id));

		DAO_KbArticle::delete($id);

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
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid article id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
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
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'links' => SearchFields_KbArticle::VIRTUAL_CONTEXT_LINK,
				'fieldsets' => SearchFields_KbArticle::VIRTUAL_HAS_FIELDSET,
				'watchers' => SearchFields_KbArticle::VIRTUAL_WATCHERS,
					
				'topic' => SearchFields_KbArticle::TOP_CATEGORY_ID,
				'format' => SearchFields_KbArticle::FORMAT,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_KB_ARTICLE);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
		
		} else {
			$tokens = array(
				'category_id' => SearchFields_KbArticle::CATEGORY_ID,
				'content' => SearchFields_KbArticle::FULLTEXT_ARTICLE_CONTENT,
				'id' => SearchFields_KbArticle::ID,
				'format' => SearchFields_KbArticle::FORMAT,
				'title' => SearchFields_KbArticle::TITLE,
				'topic_id' => SearchFields_KbArticle::TOP_CATEGORY_ID,
				'updated' => SearchFields_KbArticle::UPDATED,
				'views' => SearchFields_KbArticle::VIEWS,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_KB_ARTICLE, $model, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$params = array();
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_KB_ARTICLE,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_KB_ARTICLE);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_KB_ARTICLE, array_keys($results));
			
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
		if(null == ($article = DAO_KbArticle::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid article ID '%d'", $id));
			
		// ACL
		if(!($worker->hasPriv('contexts.cerberusweb.contexts.kb_article.update')))
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
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_KbArticle::UPDATED]))
			$fields[DAO_KbArticle::UPDATED] = time();
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_KB_ARTICLE, $id, $customfields, true, true, true);
		
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
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('contexts.cerberusweb.contexts.kb_article.create'))
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