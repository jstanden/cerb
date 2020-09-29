<?php
namespace Cerb\AutomationBuilder\Action;

class SetAction extends AbstractAction {
	const ID = 'set';
	
	function activate(\DevblocksDictionaryDelegate $dict, array &$node_memory, \CerbAutomationPolicy $policy, string &$error=null) {
		$params = $this->node->getParams($dict);
		
		foreach($params as $k => $v) {
			$dict->set($k, $v);
		}
		
		return $this->node->getParent()->getId();
	}
}