<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class DecryptPgpAction extends AbstractAction {
	const ID = 'decrypt.pgp';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		$gpg = DevblocksPlatform::services()->gpg();
		
		$params = $automation->getParams($this->node, $dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
		try {
			// Params validation
			
			$validation->addField('inputs', 'inputs:')
				->array()
			;
			
			$validation->addField('output', 'output:')
				->string()
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Validate input
			
			$validation->reset();
			
			$validation->addField('message', 'inputs:message:')
				->string()
				->setMaxLength(16_777_216)
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			$policy_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);
			
			if(!$policy->isCommandAllowed(self::ID, $policy_dict)) {
				$error = sprintf(
					"The automation policy does not allow this command (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$decrypted_text = $gpg->decrypt($inputs['message']);
			
			if(false === $decrypted_text)
				throw new Exception_DevblocksAutomationError("Failed to decrypt message.");
			
			if($output) {
				$dict->set($output, $decrypted_text);
			}
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				$dict->set($output, [
					'error' => $error,
				]);
				
				return $event_error->getId();
			}
			
			return false;
		}
		
		if(null != ($event_success = $this->node->getChildBySuffix(':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
}