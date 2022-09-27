<?php
class ChRest_Data extends Extension_RestController { //implements IExtensionRestController
	function getAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			case 'query':
				$this->getQuery();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			case 'query':
				$this->postQuery();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	private function getQuery() {
		$data = DevblocksPlatform::services()->data();
		
		$query = DevblocksPlatform::importGPC($_REQUEST['q'] ?? null, 'string','');
		$error = null;
		
		if(empty($query))
			$this->error(self::ERRNO_CUSTOM, "The 'q' query parameter is required.");
		
		if(false === ($results = $data->executeQuery($query, [], $error))) {
			$this->error(self::ERRNO_CUSTOM, $error);
		}
		
		$this->success($results);
	}
	
	private function postQuery() {
		$worker = CerberusApplication::getActiveWorker();
		$data = DevblocksPlatform::services()->data();
		
		@$query = DevblocksPlatform::getHttpBody();
		$error = null;
		
		if(empty($query))
			$this->error(self::ERRNO_CUSTOM, "A query is required in the HTTP request body.");
		
		if(false === ($results = $data->executeQuery($query, [], $error))) {
			$this->error(self::ERRNO_CUSTOM, $error);
		}
		
		$this->success($results);
	}
};