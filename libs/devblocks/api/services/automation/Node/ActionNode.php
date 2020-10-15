<?php
namespace Cerb\AutomationBuilder\Node;

use Cerb\AutomationBuilder\Action\AbstractAction;
use CerbAutomationPolicy;
use DevblocksDictionaryDelegate;

class ActionNode extends AbstractNode {
	static function getActionClasses() {
		return [
			'await' => '\Cerb\AutomationBuilder\Action\AwaitAction',
			'data.query' => '\Cerb\AutomationBuilder\Action\DataQueryAction',
			'email.parse' => '\Cerb\AutomationBuilder\Action\EmailParseAction',
			'email.send' => '\Cerb\AutomationBuilder\Action\EmailSendAction',
			'error' => '\Cerb\AutomationBuilder\Action\ErrorAction',
			'function' => '\Cerb\AutomationBuilder\Action\FunctionAction',
			'http.request' => '\Cerb\AutomationBuilder\Action\HttpRequestAction',
			'log' => '\Cerb\AutomationBuilder\Action\LogAction',
			'record.create' => '\Cerb\AutomationBuilder\Action\RecordCreateAction',
			'record.delete' => '\Cerb\AutomationBuilder\Action\RecordDeleteAction',
			'record.get' => '\Cerb\AutomationBuilder\Action\RecordGetAction',
			'record.update' => '\Cerb\AutomationBuilder\Action\RecordUpdateAction',
			'record.upsert' => '\Cerb\AutomationBuilder\Action\RecordUpsertAction',
			'return' => '\Cerb\AutomationBuilder\Action\ReturnAction',
			'set' => '\Cerb\AutomationBuilder\Action\SetAction',
			'storage.delete' => '\Cerb\AutomationBuilder\Action\StorageDeleteAction',
			'storage.get' => '\Cerb\AutomationBuilder\Action\StorageGetAction',
			'storage.set' => '\Cerb\AutomationBuilder\Action\StorageSetAction',
			'var.push' => '\Cerb\AutomationBuilder\Action\VarPushAction',
			'var.set' => '\Cerb\AutomationBuilder\Action\VarSetAction',
			'var.unset' => '\Cerb\AutomationBuilder\Action\VarUnsetAction',
		];
	}
	
	public function activate(DevblocksDictionaryDelegate $dict, array &$node_memory, array $environment, string &$error = null) {
		@$policy = $environment['policy']; /* @var $policy CerbAutomationPolicy */
		
		$id_parts = explode(':', $this->node->getId());
		$action = end($id_parts);
		
		$error = null;
		
		@list($action_type,) = explode('/', $action);
		
		// Deactivating node
		if(array_key_exists('activated', $node_memory)) {
			unset($node_memory['activated']);
			return $this->node->getParent()->getId();
		}
		
		$node_memory['activated'] = true;
		
		$is_simulator = $dict->get('__simulate', false);
		
		if($is_simulator && null != ($event_simulate = $this->node->getChildBySuffix(':on_simulate'))) {
			return $event_simulate->getId();
		}
		
		$action_classes = self::getActionClasses();
		
		if(array_key_exists($action_type, $action_classes)) {
			$action_class = new $action_classes[$action_type]($this->node); /* @var $action_class AbstractAction */
			
			if(false !== ($return_state = $action_class->activate($dict, $node_memory, $policy, $error))) {
				// Otherwise parent
				if($return_state) {
					return $return_state;
				} else {
					return $this->node->getParent()->getId();
				}
			} else {
				return false;
			}
			
		} else {
			$error = sprintf("Unknown action `%s`", $action_type);
			return false;
		}
	}
}