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
			
			case 'search':
				$this->postSearch();
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
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container['results'][$id]);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid comment id '%d'", $id));
	}

	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
				'context' => DAO_Comment::CONTEXT,
				'context_id' => DAO_Comment::CONTEXT_ID,
				'owner_context' => DAO_Comment::OWNER_CONTEXT,
				'owner_context_id' => DAO_Comment::OWNER_CONTEXT_ID,
				'comment' => DAO_Comment::COMMENT,
				'created' => DAO_Comment::CREATED,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_Comment::ID,
				'context' => SearchFields_Comment::CONTEXT,
				'context_id' => SearchFields_Comment::CONTEXT_ID,
				'owner_context' => SearchFields_Comment::OWNER_CONTEXT,
				'owner_context_id' => SearchFields_Comment::OWNER_CONTEXT_ID,
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
			!empty($sortBy) ? array($sortBy) : array(),
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
			'owner_context' => 'string',
			'owner_context_id' => 'integer',
			'created' => 'integer',
			'comment' => 'string',
		);

		@$context = DevblocksPlatform::importGPC($_POST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_POST['context'], 'integer', 0);
		@$owner_context = DevblocksPlatform::importGPC($_POST['context'], 'string', '');
		@$owner_context_id = DevblocksPlatform::importGPC($_POST['context'], 'integer', 0);

		$fields = array();
		
		foreach($postfields as $postfield => $type) {
			if(!isset($_POST[$postfield]))
				continue;
				
			@$value = DevblocksPlatform::importGPC($_POST[$postfield], 'string', '');
			
			switch($postfield) {
				case 'context':
					if($worker->is_superuser) {
						// A superuser can do anything
					} else {
						// Otherwise, is this worker allowed to see this record they are commenting on?
						if(null != ($context_ext = Extension_DevblocksContext::get($context))) {
							if(!$context_ext->authorize($context_id, $worker))
								$this->error(self::ERRNO_ACL);
						}
					}
					break;
				
				case 'owner_context':
					if($worker->is_superuser) {
						// A superuser can do anything
						
					} else {
						// A worker cannot comment as the app, a role, or a group
						switch($owner_context) {
							case CerberusContexts::CONTEXT_APPLICATION:
							case CerberusContexts::CONTEXT_GROUP:
							case CerberusContexts::CONTEXT_ROLE:
								$this->error(self::ERRNO_ACL);
								break;
								
							case CerberusContexts::CONTEXT_WORKER:
								// A worker cannot comment as someone else
								if($owner_context_id != $worker->id)
									$this->error(self::ERRNO_ACL);
								break;
						}
					}
					break;
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
		$reqfields = array(DAO_Comment::CONTEXT, DAO_Comment::CONTEXT_ID, DAO_Comment::OWNER_CONTEXT, DAO_Comment::OWNER_CONTEXT_ID, DAO_Comment::COMMENT, DAO_Comment::CREATED);
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