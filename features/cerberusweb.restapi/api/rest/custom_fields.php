<?php
class ChRest_CustomFields extends Extension_RestController implements IExtensionRestController {
	function getAction($stack) {
		@$context = array_shift($stack);
				
		switch($context) {
			case 'tickets':
			case 'ticket':
				$context = CerberusContexts::CONTEXT_TICKET;
				break;
			case 'addresses':
			case 'address':
				$context = CerberusContexts::CONTEXT_ADDRESS;
				break;
			case 'tasks':
			case 'task':
				$context = CerberusContexts::CONTEXT_TASK;
				break;
			case 'orgs':
			case 'org':
				$context = CerberusContexts::CONTEXT_ORG;
				break;
			case 'feedback':
				$context = CerberusContexts::CONTEXT_FEEDBACK;
				break;
			case 'worker':
				$context = CerberusContexts::CONTEXT_WORKER;
				break;
			default:
				if(null != ($contexts = DevblocksPlatform::getExtensions('devblocks.context'))) {
					if(!isset($contexts[$context])) {
						$this->error(self::ERRNO_CUSTOM, sprintf('%s is not a valid custom field object!', $context));
					}
					
				}
				break;
			
		}
		
		switch($context) {
			case CerberusContexts::CONTEXT_TICKET:
				$fields = array();
				if(null != ($context_fields = DAO_CustomField::getByContext($context)) && !empty($context_fields))
					foreach($context_fields as $field)
						$fields['custom_' . $field->id] = array(
							'id' => $field->id,
							'name' => $field->name,
							'type' => $field->type,
							'context' => $field->context,
							'group_id' => $field->group_id,
							'options' => @$field->params['options'],
						);
				break;
				
			default:
				$fields = array();
				if(null != ($context_fields = DAO_CustomField::getByContext($context)) && !empty($context_fields))
					foreach($context_fields as $field)
						$fields['custom_' . $field->id] = array(
							'id' => $field->id,
							'name' => $field->name,
							'type' => $field->type,
							'context' => $field->context,
							'options' => @$field->params['options'],
						);
				break;
		}
		
		$this->success($fields);
		
	}
	
	function putAction($stack) {
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		// [TODO] Expose the ability to create custom fields
//		@$action = array_shift($stack);
//
//		switch($action) {
//			case 'create':
//				$this->postCreate();
//				break;

//		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		// [TODO] Expose the ability to delete custom fields
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}

	function translateToken($token, $type='dao') {
		// [TODO]
//		$tokens = array();
//
//		if('dao'==$type) {
//			$tokens = array(
//			);
//		} else {
//			$tokens = array(
//			);
//		}
//
//		if(isset($tokens[$token]))
//			return $tokens[$token];
//
		return NULL;
	}
	
	function getContext($id) {
		return NULL;
	}
	
	function search($filters=array(), $sortToken='email', $sortAsc=1, $page=1, $limit=10) {
		return NULL;
	}
	
	function putId($id) {
		// [TODO] Implement the ability to update custom fields
	}
	
	function postCreate() {
		
	}
	
	function postSearch() {
		
	}
};