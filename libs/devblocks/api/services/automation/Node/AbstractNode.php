<?php
namespace Cerb\AutomationBuilder\Node;

use CerbAutomationAstNode;
use DevblocksDictionaryDelegate;
use Model_Automation;

abstract class AbstractNode {
	protected CerbAutomationAstNode $node;
	
	function __construct(CerbAutomationAstNode $node) {
		$this->node = $node;
	}
	
	/**
	 * @param Model_Automation $automation
	 * @param DevblocksDictionaryDelegate $dict
	 * @param array $node_memory
	 * @param string|null $error
	 */
	abstract function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false;
}