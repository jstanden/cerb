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
		@$action = array_shift($stack);
		
		switch($action) {
			case 'link':
				$this->postLink();
				break;
				
			case 'unlink':
				$this->postUnlink();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	private function _verifyContextString($string) {
		@list($context, $context_id) = explode(':', $string, 2);
		return $this->_verifyContext($context, $context_id);
	}
	
	private function _verifyContext($context, $context_id) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($context_mft = Extension_DevblocksContext::get($context)))
			return false;
		
		if(false === $context_mft->authorize($context_id, $active_worker))
			return false;
		
		if(false == (@$meta = $context_mft->getMeta($context_id)))
			return false;
		
		return array(
			'context' => $context,
			'context_id' => intval($context_id),
			'meta' => $meta,
		);
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
	
	private function postLink() {
		@$on = DevblocksPlatform::importGPC($_POST['on'], 'string', '');
		@$targets = DevblocksPlatform::importGPC($_POST['targets'], 'string', '');
		
		// Verify the 'on' context and accessibility by active worker
		if(false == ($result_on = $this->_verifyContextString($on)))
			$this->error(self::ERRNO_CUSTOM, sprintf("The 'on' value of '%s' is not valid.", $on));
		
		// Verify a well-formed JSON array for 'targets', and that each exists
		@$targets = json_decode($targets, true);
		
		if(!is_array($targets))
			$this->error(self::ERRNO_CUSTOM, "The 'targets' parameter should be a JSON formatted array of context:id pairs.");
		
		$result_targets = array();
		
		foreach($targets as $target) {
			if(false == ($result_target = $this->_verifyContextString($target)))
				$this->error(self::ERRNO_CUSTOM, sprintf("The 'targets' value of '%s' is not valid.", $target));
			
			$result_targets[] = $result_target;
		}
		
		foreach($result_targets as $result_target) {
			// [TODO] If false, return the list of errors
			DAO_ContextLink::setLink($result_on['context'], $result_on['context_id'], $result_target['context'], $result_target['context_id'], $result_on['meta'], $result_target['meta']);
		}
		
		$this->success(array(
			'on' => $result_on,
			'targets' => $result_targets,
		));
	}
	
	private function postUnlink() {
		@$on = DevblocksPlatform::importGPC($_POST['on'], 'string', '');
		@$targets = DevblocksPlatform::importGPC($_POST['targets'], 'string', '');
		
		// Verify the 'on' context and accessibility by active worker
		if(false == ($result_on = $this->_verifyContextString($on)))
			$this->error(self::ERRNO_CUSTOM, sprintf("The 'on' value of '%s' is not valid.", $on));
		
		// Verify a well-formed JSON array for 'targets', and that each exists
		@$targets = json_decode($targets, true);
		
		if(!is_array($targets))
			$this->error(self::ERRNO_CUSTOM, "The 'targets' parameter should be a JSON formatted array of context:id pairs.");
		
		$result_targets = array();
		
		foreach($targets as $target) {
			if(false == ($result_target = $this->_verifyContextString($target)))
				$this->error(self::ERRNO_CUSTOM, sprintf("The 'targets' value of '%s' is not valid.", $target));
			
			$result_targets[] = $result_target;
		}
		
		foreach($result_targets as $result_target) {
			// [TODO] If false, return the list of errors
			DAO_ContextLink::deleteLink($result_on['context'], $result_on['context_id'], $result_target['context'], $result_target['context_id'], $result_on['meta'], $result_target['meta']);
		}
		
		$this->success(array(
			'on' => $result_on,
			'targets' => $result_targets,
		));
	}
};