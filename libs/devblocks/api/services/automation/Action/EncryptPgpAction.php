<?php
namespace Cerb\AutomationBuilder\Action;

use CerberusContexts;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class EncryptPgpAction extends AbstractAction {
	const ID = 'encrypt.pgp';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		$gpg = DevblocksPlatform::services()->gpg();
		
		$params = $this->node->getParams($dict);
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
			
			$validation->addField('public_keys', 'inputs:public_keys:')
				->array()
				->setRequired(true)
			;
			
			// [TODO] Signing key
			
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
			
			$public_key_fingerprints = [];
			
			foreach($inputs['public_keys'] as $key_type => $key_value) {
				$key_type = DevblocksPlatform::services()->string()->strBefore($key_type, '/');
				
				if('uri' == $key_type) {
					if(false == ($result = DevblocksPlatform::services()->ui()->parseURI($key_value)))
						continue;
					
					if($result['context'] != CerberusContexts::CONTEXT_GPG_PUBLIC_KEY)
						continue;
					
					$public_key_fingerprints[] = $result['context_id'];
					
				} elseif('fingerprint' == $key_type) {
					$public_key_fingerprints[] = $key_value;
					
				} elseif('ids' == $key_type && is_array($key_value)) {
					$public_key_fingerprints = array_merge($public_key_fingerprints, $key_value);
					
				} elseif('id' == $key_type) {
					$public_key_fingerprints[] = $key_value;
				}
			}
			
			$encrypted_text = $gpg->encrypt($inputs['message'], $public_key_fingerprints);
			
			if(false === $encrypted_text)
				throw new Exception_DevblocksAutomationError("Failed to encrypt message.");
			
			if($output) {
				$dict->set($output, $encrypted_text);
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