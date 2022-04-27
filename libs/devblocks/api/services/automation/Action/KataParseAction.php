<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class KataParseAction extends AbstractAction {
	const ID = 'kata.parse';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$kata = DevblocksPlatform::services()->kata();
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $this->node->getParams($dict);
		
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
			
			$validation->reset();
			
			// Inputs validation
			
			$validation->addField('kata', 'inputs:kata:')
				->stringOrArray()
				->setMaxLength(1_024_000)
				->setRequired(true)
				->setNotEmpty(false)
			;
			
			$validation->addField('dict', 'inputs:dict:')
				->array()
			;
			
			$validation->addField('schema', 'inputs:schema:')
				->array()
			;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);
			
			$kata_string = $inputs['kata'] ?? null;
			$kata_dict = $inputs['dict'] ?? [];
			$kata_schema = $inputs['schema'] ?? null;
			
			$error = null;
			
			$kata_dict = DevblocksDictionaryDelegate::instance($kata_dict);
			
			// Polymorph kata tree (string)
			if(is_array($kata_string))
				$kata_string = $kata->emit($kata_string);
			
			if(is_array($kata_schema)) {
				$kata_schema = ['schema' => $kata_schema];
				$kata_schema = $kata->emit($kata_schema);
			}
			
			// Validate schema (if provided)
			if($kata_schema)
				$kata->validate($kata_string, $kata_schema, $error, $kata_dict);
			
			if(!$error)
				$tree = $kata->parse($kata_string, $error);
			
			if(!$error)
				$tree = $kata->formatTree($tree, $kata_dict, $error);
			
			if($error) {
				$error = sprintf("%s at %s:inputs:kata:", $error, $this->node->getId());
				
				if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
					$error_dict = [];
					
					$error_dict['error'] = $error;
					
					if($output) {
						$dict->set($output, $error_dict);
					}
					
					return $event_error->getId();
					
				} else {
					return false;
				}
				
			} else {
				if ($output) {
					$dict->set($output, $tree);
				}
				
			}
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				if ($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}
				
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