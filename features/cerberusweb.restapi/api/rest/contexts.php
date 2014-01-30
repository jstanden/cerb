<?php
class ChRest_Contexts extends Extension_RestController {
	function getAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			case 'list':
				$this->getList();
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
	
	private function getList() {
		$results = array();
		
		$contexts = Extension_DevblocksContext::getAll();
		
		foreach($contexts as $context) {
			$results[$context->id] = array(
				'id' => $context->id,
				'name' => $context->name,
				'plugin_id' => $context->id,
			);
		}
		
		$this->success(array('results' => $results));
	}
};