<?php
class ChRest_MailHtmlTemplates extends Extension_RestController implements IExtensionRestController {
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
			//$id = intval($action);
			$action = array_shift($stack);
			
			switch($action) {
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
		$id = array_shift($stack);
		
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);

		if(null == (DAO_MailHtmlTemplate::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid mail HTML template ID %d", $id));

		DAO_MailHtmlTemplate::delete($id);

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
				'content' => DAO_MailHtmlTemplate::CONTENT,
				'name' => DAO_MailHtmlTemplate::NAME,
				'owner_context' => DAO_MailHtmlTemplate::OWNER_CONTEXT,
				'owner_context_id' => DAO_MailHtmlTemplate::OWNER_CONTEXT_ID,
				'signature' => DAO_MailHtmlTemplate::SIGNATURE,
				'updated_at' => DAO_MailHtmlTemplate::UPDATED_AT,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'fieldsets' => SearchFields_MailHtmlTemplate::VIRTUAL_HAS_FIELDSET,
				'links' => SearchFields_MailHtmlTemplate::VIRTUAL_CONTEXT_LINK,
			);
			
			$tokens_cfields = $this->_handleSearchTokensCustomFields(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE);
			
			if(is_array($tokens_cfields))
				$tokens = array_merge($tokens, $tokens_cfields);
			
		} else {
			$tokens = array(
				'content' => SearchFields_MailHtmlTemplate::CONTENT,
				'id' => SearchFields_MailHtmlTemplate::ID,
				'name' => SearchFields_MailHtmlTemplate::NAME,
				'owner_context' => SearchFields_MailHtmlTemplate::OWNER_CONTEXT,
				'owner_context_id' => SearchFields_MailHtmlTemplate::OWNER_CONTEXT_ID,
				'signature' => SearchFields_MailHtmlTemplate::SIGNATURE,
				'updated_at' => SearchFields_MailHtmlTemplate::UPDATED_AT,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}

	function getContext($model) {
		$labels = $values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $model, $labels, $values, null, true);
		return $values;
	}
	
	function getId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!Context_MailHtmlTemplate::isReadableByActor($id, $worker))
			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid mail HTML template id '%d'", $id));
	}
	
	function search($filters=array(), $sortToken='id', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		$params = [];
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;

		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE);
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
			
			$models = CerberusContexts::getModels(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, array_keys($results));
			
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
		// ACL
//		if(!$worker->hasPriv('core.addybook'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// Validate the ID
		if(null == (DAO_MailHtmlTemplate::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid mail HTML template ID '%d'", $id));
			
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
			
		$putfields = array(
			'content' => 'string',
			'name' => 'string',
			'owner_context' => 'string',
			'owner_context_id' => 'integer',
			'signature' => 'string',
			'updated_at' => 'timestamp',
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
		
		if(!isset($fields[DAO_MailHtmlTemplate::UPDATED_AT]))
			$fields[DAO_MailHtmlTemplate::UPDATED_AT] = time();
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $id, $customfields, true, true, true);
		
		// Update
		DAO_MailHtmlTemplate::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'content' => 'string',
			'name' => 'string',
			'owner_context' => 'string',
			'owner_context_id' => 'integer',
			'signature' => 'string',
			'updated_at' => 'timestamp',
		);

		$fields = array(
			DAO_MailHtmlTemplate::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
			DAO_MailHtmlTemplate::OWNER_CONTEXT_ID => 0,
			DAO_MailHtmlTemplate::UPDATED_AT => time(),
		);
		
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
		
		// Check required fields
		$reqfields = array(
			DAO_MailHtmlTemplate::NAME,
			DAO_MailHtmlTemplate::CONTENT,
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Custom fields
		$this->_handleCustomFields($_POST);
		
		// Create
		if(false != ($id = DAO_MailHtmlTemplate::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}

};