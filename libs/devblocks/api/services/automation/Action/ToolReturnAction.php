<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use Model_Automation;

class ToolReturnAction extends AbstractAction {
	const ID = 'tool.return';
	
	/**
	 * @param Model_Automation $automation
	 * @param DevblocksDictionaryDelegate $dict
	 * @param array $node_memory
	 * @param string|null $error
	 * @return string|false
	 */
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, ?string &$error=null) : string|false {
		$return_values = $automation->getParams($this->node, $dict);
		
		$tool_node = $this->node->getAncestorByType('on_tool');
		
		// This command can only run inside an `on_tool:` branch
		if(!$tool_node) {
			$error = "The `tool.return:` command must occur within `on_tool:`";
			return false;
		}
		
		if(!($command_node = $tool_node->getParent()))
			return false;
		
		if(array_key_exists('content', $return_values))
			$dict->setKeyPath('__tool.content', $return_values['content']);
		
		return $command_node->getId();
	}
}