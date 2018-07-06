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
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	private function getQuery() {
		$worker = CerberusApplication::getActiveWorker();
		$data = DevblocksPlatform::services()->data();
		
		@$query = DevblocksPlatform::importGPC($_REQUEST['q'],'string','');
		
		if(empty($query))
			$this->error(self::ERRNO_CUSTOM, "The 'q' query parameter is required.");
		
		$results = $data->executeQuery($query);
		
		$this->success($results);
	}
};