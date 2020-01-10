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
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(is_numeric($action)) {
			$this->putId(intval($action));
			
		} else { // actions
			switch($action) {
				case 'members':
					$this->putMembers();
					break;
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
		$worker = CerberusApplication::getActiveWorker();
		
		if(!Context_Group::isReadableByActor($id, $worker))
			$this->error(self::ERRNO_CUSTOM, sprintf("Permission denied for group id '%d'", $id));
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id])) {
			$this->success($container['results'][$id]);
		}
		
		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid group id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'created' => DAO_Group::CREATED,
				'name' => DAO_Group::NAME,
				'is_private' => DAO_Group::IS_PRIVATE,
				'updated' => DAO_Group::UPDATED,
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
				'created' => SearchFields_Group::CREATED,
				'id' => SearchFields_Group::ID,
				'is_private' => SearchFields_Group::IS_PRIVATE,
				'name' => SearchFields_Group::NAME,
				'updated' => SearchFields_Group::UPDATED,
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
			CerberusContexts::CONTEXT_GROUP,
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
			
			$custom_field_params = $this->_handleSearchBuildParamsCustomFields($filters, CerberusContexts::CONTEXT_GROUP);
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params, $custom_field_params);
			
			$view->addParams($params, true);
		}
		
		// (ACL) Add worker group privs
		if(!$worker->is_superuser) {
			$memberships = $worker->getMemberships();
			$view->addParam(
				new DevblocksSearchCriteria(
					SearchFields_Group::ID,
					'in',
					(!empty($memberships) ? array_keys($memberships) : array(0))
				),
				SearchFields_Group::ID
			);
		}
		
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
	
	function putId($id) {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
		
		// Validate the ID
		if(null == DAO_Group::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid group ID '%d'", $id));
		
		$putfields = array(
			'is_private' => 'bit',
			'name' => 'string',
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
		
		// Handle custom fields
		$customfields = $this->_handleCustomFields($_POST);
		if(is_array($customfields))
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_GROUP, $id, $customfields, true, true, true);
		
		// Update
		DAO_Group::update($id, $fields);
		
		$this->getId($id);
	}
	
	function putMembers() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$json = DevblocksPlatform::importGPC($_POST['json'],'string','');
		$groups = DAO_Group::getAll();
		$workers = DAO_Worker::getAll();
		
		if(false == ($json = json_decode($json, true)) || !is_array($json))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid JSON content in 'json' field."));
		
		if(!isset($json['groups']))
			$this->error(self::ERRNO_CUSTOM, sprintf("The JSON should have a 'groups' key at the top level."));
		
		$given_groups = $json['groups'];
		$invalid_ids = array_keys(array_diff_key($given_groups, $groups));
		
		if($invalid_ids)
			$this->error(self::ERRNO_CUSTOM, sprintf("The JSON specifies invalid group IDs: %s", implode(',', $invalid_ids)));
		
		// Check group permissions
		if(in_array(false, Context_Group::isWriteableByActor(array_keys($given_groups), $active_worker)))
			$this->error(self::ERRNO_CUSTOM, 'You must be an admin, or a manager of all of these groups.');
		
		foreach($given_groups as $group_id => $group_data) {
			if(!is_array($group_data) || !isset($group_data['workers']))
				$this->error(self::ERRNO_CUSTOM, sprintf("The JSON specifies invalid data for group %s", $group_id));
			
			$members = $group_data['workers'];
			
			$invalid_ids = array_keys(array_diff_key($members, $workers));
			
			if($invalid_ids)
				$this->error(self::ERRNO_CUSTOM, sprintf("The JSON specifies invalid workers for group %d: %s", $group_id, implode(',', $invalid_ids)));
			
			foreach($members as $worker_id => $action) {
				switch(DevblocksPlatform::strLower($action)) {
					case 'manager':
						DAO_Group::setGroupMember($group_id, $worker_id, true);
						break;
					case 'member':
						DAO_Group::setGroupMember($group_id, $worker_id, false);
						break;
					case 'remove':
						DAO_Group::unsetGroupMember($group_id, $worker_id);
						break;
				}
				
				DAO_WorkerRole::clearWorkerCache($worker_id);
			}
		}
		
		DAO_Group::clearCache();
		
		// [TODO] Return the members/managers for the specified groups
		
		$container = [];
		
		$this->success($container);
	}
	
	function postCreate() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'is_private' => 'bit',
			'name' => 'string',
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
		
		// Check required fields
		$reqfields = array(
			'name',
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_Group::create($fields))) {
			$bucket_fields = array(
				DAO_Bucket::NAME => 'Inbox',
				DAO_Bucket::GROUP_ID => $id,
				DAO_Bucket::IS_DEFAULT => 1,
				DAO_Bucket::UPDATED_AT => time(),
			);
			$bucket_id = DAO_Bucket::create($bucket_fields);
			
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_GROUP, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}
};