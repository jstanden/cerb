<?php
namespace Cerb\AutomationBuilder\Action;

use CerbAutomationAstNode;
use DevblocksDictionaryDelegate;

abstract class AbstractAction {
	/**
	 * @var CerbAutomationAstNode
	 */
	protected $node;
	
	function __construct(CerbAutomationAstNode $node) {
		$this->node = $node;
	}
	
	/**
	 * @param DevblocksDictionaryDelegate $dict
	 * @param array $node_memory
	 * @param \CerbAutomationPolicy $policy
	 * @param string|null $error
	 * @return string|false
	 */
	abstract function activate(DevblocksDictionaryDelegate $dict, array &$node_memory, \CerbAutomationPolicy $policy, string &$error=null);
}