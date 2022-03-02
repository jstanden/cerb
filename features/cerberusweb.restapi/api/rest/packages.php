<?php
class ChRest_Packages extends Extension_RestController { //implements IExtensionRestController
	function getAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			case 'import':
				$this->postImport();
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	private function postImport() {
		$worker = CerberusApplication::getActiveWorker();
		
		// Must be an admin to import packages
		if(!$worker->is_superuser)
			$this->error(self::ERRNO_ACL);
		
		$json_string = DevblocksPlatform::importGPC($_POST['package_json'] ?? null, 'string','');
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array',[]);
		
		$records_created = [];
		
		try {
			CerberusApplication::packages()->import($json_string, $prompts, $records_created);
			
		} catch(Exception_DevblocksValidationError $e) {
			$this->error(self::ERRNO_PARAM_INVALID, $e->getMessage());
			
		} catch(Exception $e) {
			$this->error(self::ERRNO_PARAM_INVALID);
		}
		
		$container = [
			'records_created' => $records_created,
		];
		
		$this->success($container);
	}
};