<?php
class ChRest_Attachments extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		// Looking up a single ID?
		if(empty($stack) && is_numeric($action)) {
			$this->getId(intval($action));
			
		} elseif(is_numeric($action)) { // ID actions
			switch(@array_shift($stack)) {
				case 'download':
					$this->getIdDownload(intval($action));
					break;
			}
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			case 'search':
				$this->postSearch();
				break;
				
			case 'upload':
				$this->postUpload();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		// not implemented for parity with web interface
//		$id = array_shift($stack);
//
//		if(null == ($attachment = DAO_Attachment::get($id)))
//			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid attachment ID %d", $id));
//		DAO_Attachment::delete($id);
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	private function _findAttachmentIdInContainer($id, $container) {
		foreach($container['results'] as $result) {
			if(is_array($result) && $result['id'] == $id) {
				// Deprecated. For backwards compatibility in 8.0.6
				$result['file_id'] = $id;
				return $result;
			}
		}
		
		return null;
	}
	
	private function getId($id) {
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == (Context_Attachment::isReadableByActor($id, $active_worker)))
			$this->error(self::ERRNO_ACL, "Permission denied.");
		
		if(is_array($container) && isset($container['results'])) {
			if(false != ($result = $this->_findAttachmentIdInContainer($id, $container))) {
				$this->success($result);
			}
		}

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid attachment id '%d'", $id));
	}

	private function getIdDownload($id) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(
			null == (@$result = $this->_findAttachmentIdInContainer($id, $container))
			|| null == ($file = DAO_Attachment::get($result['id']))
		)
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid attachment id '%d'", $id));
			
		if(false === ($fp = DevblocksPlatform::getTempFile()))
			$this->error(self::ERRNO_CUSTOM, "Could not open a temporary file.");
		
		if(false == (Context_Attachment::isDownloadableByActor($file, $active_worker)))
			$this->error(self::ERRNO_ACL, "Permission denied.");
		
		if(false === $file->getFileContents($fp))
			$this->error(self::ERRNO_CUSTOM, "Error reading resource.");
			
		$file_stats = fstat($fp);

		// Set headers
		header("Expires: Mon, 26 Nov 1970 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Accept-Ranges: bytes");
//		header("Keep-Alive: timeout=5, max=100");
//		header("Connection: Keep-Alive");
		header("Content-Type: " . $file->mime_type);
		header("Content-disposition: attachment; filename=" . $file->name);
		header("Content-Length: " . $file_stats['size']);
		
		fpassthru($fp);
		fclose($fp);
		exit;
	}
	
	function translateToken($token, $type='dao') {
		if('custom_' == substr($token, 0, 7) && in_array($type, array('search', 'subtotal'))) {
			return 'cf_' . intval(substr($token, 7));
		}
		
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
//				'example' => DAO_Example::PROPERTY,
			);
			
		} elseif ('subtotal'==$type) {
			$tokens = array(
				'name' => SearchFields_Attachment::NAME,
				'mime_type' => SearchFields_Attachment::MIME_TYPE,
				'storage_extension' => SearchFields_Attachment::STORAGE_EXTENSION,
			);
			
		} else {
			$tokens = array(
				'name' => SearchFields_Attachment::NAME,
				'id' => SearchFields_Attachment::ID,
				'mime_type' => SearchFields_Attachment::MIME_TYPE,
				'sha1_hash' => SearchFields_Attachment::STORAGE_SHA1HASH,
				'size' => SearchFields_Attachment::STORAGE_SIZE,
				'storage_extension' => SearchFields_Attachment::STORAGE_EXTENSION,
				'storage_key' => SearchFields_Attachment::STORAGE_KEY,
				'storage_profile_id' => SearchFields_Attachment::STORAGE_PROFILE_ID,
				'updated' => SearchFields_Attachment::UPDATED,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($model) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_ATTACHMENT, $model, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='email', $sortAsc=1, $page=1, $limit=10, $options=array()) {
		@$query = DevblocksPlatform::importVar($options['query'], 'string', null);
		@$show_results = DevblocksPlatform::importVar($options['show_results'], 'boolean', true);
		@$subtotals = DevblocksPlatform::importVar($options['subtotals'], 'array', array());
		
		//$worker = CerberusApplication::getActiveWorker();

		$params = array();
		
		// [TODO] Fix
		
		// (ACL) Add worker group privs
//		if(!$worker->is_superuser) {
//			$memberships = $worker->getMemberships();
//			$params['tmp_worker_memberships'] = new DevblocksSearchCriteria(
//				SearchFields_Attachment::TICKET_GROUP_ID,
//				'in',
//				(!empty($memberships) ? array_keys($memberships) : array(0))
//			);
//		}
		
		// Sort
		$sortBy = $this->translateToken($sortToken, 'search');
		$sortAsc = !empty($sortAsc) ? true : false;
		
		// Search
		
		$view = $this->_getSearchView(
			CerberusContexts::CONTEXT_ATTACHMENT,
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
			
			$new_params = $this->_handleSearchBuildParams($filters);
			$params = array_merge($params, $new_params);
			
			$view->addParams($params, true);
		}
		
		if($show_results)
			list($results, $total) = $view->getData();
		
		// Get subtotal data, if provided
		if(!empty($subtotals))
			$subtotal_data = $this->_handleSearchSubtotals($view, $subtotals);
		
		if($show_results) {
			$objects = array();

			$models = DAO_Attachment::getIds(array_keys($results));
			
			unset($results);

			if(is_array($models))
			foreach($models as $id => $model) {
				CerberusContexts::getContext(CerberusContexts::CONTEXT_ATTACHMENT, $model, $labels, $values, null, true);
				
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
		
		// Search
		list($results, $total) = DAO_Attachment::search(
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
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
// 		if(!$worker->hasPriv('core.mail.search'))
// 			$this->error(self::ERRNO_ACL, 'Access denied to search tickets.');

		$container = $this->_handlePostSearch();
		
		$this->success($container);
	}
	
	private function _handlePostUpload() {
		$file_name = DevblocksPlatform::importGPC($_REQUEST['file_name'] ?? null, 'string','');
		$mime_type = DevblocksPlatform::importGPC($_REQUEST['mime_type'] ?? null, 'string','application/octet-stream');
		$encoding = DevblocksPlatform::importGPC($_REQUEST['encoding'] ?? null, 'string','');
		$content = DevblocksPlatform::importGPC($_REQUEST['content'] ?? null, 'string','');
		
		if(empty($file_name))
			$this->error(self::ERRNO_CUSTOM, "The 'file_name' parameter is required.");
		
		if(empty($mime_type))
			$this->error(self::ERRNO_CUSTOM, "The 'mime_type' parameter is required.");
		
		if(empty($content))
			$this->error(self::ERRNO_CUSTOM, "The 'content' parameter is required.");
		
		switch($encoding) {
			case 'base64':
				$content = base64_decode($content);
				break;
			
			case 'text':
			default:
				break;
		}

		// Detect duplicate file uploads via API
		
		$sha1_hash = sha1($content, false);
		
		if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, strlen($content), $mime_type))) {
			$fields = array(
				DAO_Attachment::NAME => $file_name,
				DAO_Attachment::MIME_TYPE => $mime_type,
				DAO_Attachment::UPDATED => time(),
				DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
			);
			
			$file_id = DAO_Attachment::create($fields);
			
			Storage_Attachments::put($file_id, $content);
		}

		if(empty($file_id))
			return array();
		
		$this->getId($file_id);
	}
	
	function postUpload() {
		$worker = CerberusApplication::getActiveWorker();
		
		// ACL
// 		if(!$worker->hasPriv('core.example'))
// 			$this->error(self::ERRNO_ACL, 'Access denied to upload attachments.');

		$container = $this->_handlePostUpload();
		
		$this->success($container);
	}
};