<?php
class ChRest_Comments extends Extension_RestController implements IExtensionRestController {
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
			case 'create':
				$this->postCreate();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$id = array_shift($stack);
		$container = $this->search(array(
			array('id', '=', $id),			
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id])) {
			DAO_Comment::delete($id);
			$this->success(array('message' => sprintf("Comment '%d' was removed", $id)));
		} else {
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid comment id '%d'", $id));
		}
	}
	
	private function getId($id) {
		$worker = $this->getActiveWorker();

		$id = array_shift($stack);

		if(null == ($comment = DAO_Comment::get($id)))
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid comment id %d", $id));

		DAO_Comment::delete($id);

		$result = array('id' => $id);
		$this->success($result);
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'context' => DAO_Comment::CONTEXT,
				'context_id' => DAO_Comment::CONTEXT_ID,
				'address_id' => DAO_Comment::ADDRESS_ID,
				'comment' => DAO_Comment::COMMENT,
				'created' => DAO_Comment::CREATED,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_Comment::ID,
				'context' => SearchFields_Comment::CONTEXT,
				'context_id' => SearchFields_Comment::CONTEXT_ID,
				'address_id' => SearchFields_Comment::ADDRESS_ID,
				'comment' => SearchFields_Comment::COMMENT,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}	
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_COMMENT, $id, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='email', $sortAsc=1, $page=1, $limit=10) {
		$worker = $this->getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		list($results, $total) = DAO_Comment::search(
			array($sortBy),
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
	
	function postCreate() {
		$worker = $this->getActiveWorker();
		
		$postfields = array(
			'context' => 'string',
			'context_id' => 'integer',
			'address' => 'string',
			'address_id' => 'integer',
			'created' => 'integer',
			'comment' => 'string',
		);

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
			
			switch($postfield) {
				case 'address':
					if(null != ($lookup = DAO_Address::lookupAddress($value, true))) {
						unset($postfields['address']);
						$postfield = 'address_id';
						$value = $lookup->id;
					}
					break;
				case 'context':
					switch($_POST[$postfield]) {
						case CerberusContexts::CONTEXT_TICKET:
							if(!$worker->hasPriv('core.display.actions.comment')) {
								$this->error(self::ERRNO_ACL);
							}
							break;
					break;
				}
			}
			
			if(null == ($field = self::translateToken($postfield, 'dao'))) {
				$this->error(self::ERRNO_CUSTOM, sprintf("'%s' is not a valid field.", $postfield));
			}

			// Sanitize
			$value = DevblocksPlatform::importVar($value, $type);
			
			// Overrides
			switch($field) {
			}
			
			$fields[$field] = $value;
		}
		
		// Defaults
		if(!isset($fields[DAO_Comment::CREATED]))
			$fields[DAO_Comment::CREATED] = time();
		
		// Check required fields
		$reqfields = array(DAO_Comment::CONTEXT, DAO_Comment::CONTEXT_ID, DAO_Comment::ADDRESS_ID, DAO_Comment::COMMENT, DAO_Comment::CREATED);
		$this->_handleRequiredFields($reqfields, $fields);
		
		// Create
		if(false != ($id = DAO_Comment::create($fields))) {
			$this->getId($id);
		}
	}
	
	function postSearch() {
		$worker = $this->getActiveWorker();

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
};