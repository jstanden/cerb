<?php
namespace Cerb\AutomationBuilder\Action;

use DAO_AutomationLog;
use DevblocksDictionaryDelegate;
use Exception_DevblocksAutomationError;
use Model_Automation;

class LogAction extends AbstractAction {
	const ID = 'log';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$params = $automation->getParams($this->node, $dict);
		
		try {
			$log_message = $params[''] ?? '';
			
			// Ignore the action on an empty error message
			if(empty($log_message))
				return $this->node->getParent()->getId();
			
			$node_type = $this->node->getNameType();
			
			if('log.alert' == $node_type) {
				$log_level = 1;
			} else if('log.error' == $node_type) {
				$log_level = 3;
			} elseif ('log.warn' == $node_type) {
				$log_level = 4;
			} else {
				$log_level = 7;
			}
			
			$fields = [
				DAO_AutomationLog::LOG_MESSAGE => $log_message,
				DAO_AutomationLog::LOG_LEVEL => $log_level,
				DAO_AutomationLog::CREATED_AT => time(),
				DAO_AutomationLog::AUTOMATION_NAME => $automation->name ?? '',
				DAO_AutomationLog::AUTOMATION_NODE => $this->node->getId(),
			];
			
			if(false == DAO_AutomationLog::validate($fields, $error))
				throw new Exception_DevblocksAutomationError($error);
			
			// Write to database
			DAO_AutomationLog::create($fields);
			
		} catch(Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			return false;
		}
		
		return $this->node->getParent()->getId();
	}
}