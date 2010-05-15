<?php
class ChRest_Orgs extends Extension_RestController implements IExtensionRestController {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
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
	
	function getId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook'))
			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid org id '%d'", $id));		
	}
	
	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'name' => DAO_ContactOrg::NAME,
				'street' => DAO_ContactOrg::STREET,
				'city' => DAO_ContactOrg::CITY,
				'province' => DAO_ContactOrg::PROVINCE,
				'postal' => DAO_ContactOrg::POSTAL,
				'country' => DAO_ContactOrg::COUNTRY,
				'phone' => DAO_ContactOrg::PHONE,
				'website' => DAO_ContactOrg::WEBSITE,
				'created' => DAO_ContactOrg::CREATED,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_ContactOrg::ID,
				'name' => SearchFields_ContactOrg::NAME,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, $id, $labels, $values, null, true);

//		unset($values['initial_message_content']);

		return $values;
	}
	
	function search($filters=array(), $sortToken='name', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_ContactOrg::search(
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
		if(!$worker->hasPriv('core.addybook'))
			$this->error(self::ERRNO_ACL);

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	function putId($id) {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook.org.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		// Validate the ID
		if(null == DAO_ContactOrg::get($id))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid org ID '%d'", $id));
			
		$putfields = array(
			'name' => 'string',
			'street' => 'string',
			'city' => 'string',
			'province' => 'string',
			'postal' => 'string',
			'country' => 'string',
			'phone' => 'string',
			'website' => 'string',
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
			$value = $this->_handleSanitizeValue($value, $type);
						
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
			DAO_CustomFieldValue::formatAndSetFieldValues(ChCustomFieldSource_Org::ID, $id, $customfields, true, true, true);
		
		// Check required fields
//		$reqfields = array(DAO_Address::EMAIL);
//		$this->_handleRequiredFields($reqfields, $fields);

		// Update
		DAO_ContactOrg::update($id, $fields);
		$this->getId($id);
	}
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		// ACL
		if(!$worker->hasPriv('core.addybook.org.actions.update'))
			$this->error(self::ERRNO_ACL);
		
		$postfields = array(
			'name' => 'string',
			'street' => 'string',
			'city' => 'string',
			'province' => 'string',
			'postal' => 'string',
			'country' => 'string',
			'phone' => 'string',
			'website' => 'string',
			'created' => 'integer',
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
			$value = $this->_handleSanitizeValue($value, $type);
			
//			switch($field) {
//				case DAO_Worker::PASSWORD:
//					$value = md5($value);
//					break;
//			}
			
			$fields[$field] = $value;
		}
		
		if(!isset($fields[DAO_ContactOrg::CREATED]))
			$fields[DAO_ContactOrg::CREATED] = time();
		
		// Check required fields
		$reqfields = array(
			DAO_ContactOrg::NAME, 
		);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_ContactOrg::create($fields))) {
			// Handle custom fields
			$customfields = $this->_handleCustomFields($_POST);
			if(is_array($customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(ChCustomFieldSource_Org::ID, $id, $customfields, true, true, true);
			
			$this->getId($id);
		}
	}		
};