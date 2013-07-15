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
	
	private function getId($id) {
		// ACL
//		$worker = CerberusApplication::getActiveWorker();
//		if(!$worker->hasPriv('core.addybook'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(is_array($container) && isset($container['results']) && isset($container['results'][$id]))
			$this->success($container);

		// Error
		$this->error(self::ERRNO_CUSTOM, sprintf("Invalid attachment id '%d'", $id));
	}

	private function getIdDownload($id) {
		// ACL
//		$worker = CerberusApplication::getActiveWorker();
//		if(!$worker->hasPriv('core.addybook'))
//			$this->error(self::ERRNO_ACL);

		$container = $this->search(array(
			array('id', '=', $id),
		));
		
		if(
			null == (@$result = $container['results'][$id])
			|| null == ($file = DAO_Attachment::get($result['id']))
		)
			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid attachment id '%d'", $id));
			
		if(false === ($fp = DevblocksPlatform::getTempFile()))
			$this->error(self::ERRNO_CUSTOM, "Could not open a temporary file.");
		
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
		header("Content-disposition: attachment; filename=" . $file->display_name);
		header("Content-Length: " . $file_stats['size']);
		
		fpassthru($fp);
		fclose($fp);
		exit;
	}
	
	function translateToken($token, $type='dao') {
		$tokens = array();
		
		if('dao'==$type) {
			$tokens = array(
//				'example' => DAO_Example::PROPERTY,
			);
		} else {
			$tokens = array(
				'id' => SearchFields_AttachmentLink::ID,
				'context' => SearchFields_AttachmentLink::LINK_CONTEXT,
				'context_id' => SearchFields_AttachmentLink::LINK_CONTEXT_ID,
			);
		}
		
		if(isset($tokens[$token]))
			return $tokens[$token];
		
		return NULL;
	}
	
	function getContext($id) {
		$labels = array();
		$values = array();
		$context = CerberusContexts::getContext(CerberusContexts::CONTEXT_ATTACHMENT, $id, $labels, $values, null, true);

		return $values;
	}
	
	function search($filters=array(), $sortToken='email', $sortAsc=1, $page=1, $limit=10) {
		$worker = CerberusApplication::getActiveWorker();

		$params = $this->_handleSearchBuildParams($filters);
		
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
		list($results, $total) = DAO_AttachmentLink::search(
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
		@$file_name = DevblocksPlatform::importGPC($_REQUEST['file_name'],'string','');
		@$mime_type = DevblocksPlatform::importGPC($_REQUEST['mime_type'],'string','application/octet-stream');
		@$encoding = DevblocksPlatform::importGPC($_REQUEST['encoding'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
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
		
		$fields = array(
			DAO_Attachment::DISPLAY_NAME => $file_name,
			DAO_Attachment::MIME_TYPE => $mime_type,
			DAO_Attachment::UPDATED => time(),
		);
		
		$file_id = DAO_Attachment::create($fields);

		Storage_Attachments::put($file_id, $content);
		
		return array(
			'file_id' => $file_id,
		);
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