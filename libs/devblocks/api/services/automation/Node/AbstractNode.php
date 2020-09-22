<?php
namespace Cerb\AutomationBuilder\Node;

use CerbAutomationAstNode;
use DevblocksDictionaryDelegate;

abstract class AbstractNode {
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
	 * @param array $environment
	 * @param string|null $error
	 * @return string|false
	 */
	abstract function activate(DevblocksDictionaryDelegate $dict, array &$node_memory, array $environment, string &$error=null);
}