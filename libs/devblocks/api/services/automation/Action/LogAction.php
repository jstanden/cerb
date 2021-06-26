<?php
namespace Cerb\AutomationBuilder\Action;

use DAO_AutomationLog;
use DevblocksDictionaryDelegate;
use Exception_DevblocksAutomationError;
use Model_Automation;

class LogAction extends AbstractAction {
	const ID = 'log';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$params = $this->node->getParams($dict);
		
		/*
		$log_levels = [
			'emergency' => 0,		// Emergency: system is unusable
			'alert' => 1,		// Alert: action must be taken immediately
			'critical' => 2,		// Critical: critical conditions
			'error' => 3,			// Error: error conditions
			'warning' => 4,		// Warning: warning conditions
			'notice' => 5,		// Notice: normal but significant condition
			'info' => 6,		// Informational: informational messages
			'debug' => 7,		// Debug: debug messages
		];
		
		$level_to_label = [
			0 => 'emergency',
			1 => 'alert',
			2 => 'critical',
			3 => 'error',
			4 => 'warning',
			5 => 'notice',
			6 => 'info',
			7 => 'debug',
		];
		*/
		
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