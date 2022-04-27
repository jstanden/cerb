<?php
namespace Cerb\AutomationBuilder\Node;

use Cerb\AutomationBuilder\Action\AbstractAction;
use CerbAutomationPolicy;
use DevblocksDictionaryDelegate;
use Model_Automation;

class ActionNode extends AbstractNode {
	static function getActionClasses() {
		return [
			'await' => '\Cerb\AutomationBuilder\Action\AwaitAction',
			'data.query' => '\Cerb\AutomationBuilder\Action\DataQueryAction',
			'decrypt.pgp' => '\Cerb\AutomationBuilder\Action\DecryptPgpAction',
			'email.parse' => '\Cerb\AutomationBuilder\Action\EmailParseAction',
			'encrypt.pgp' => '\Cerb\AutomationBuilder\Action\EncryptPgpAction',
			'error' => '\Cerb\AutomationBuilder\Action\ErrorAction',
			'file.read' => '\Cerb\AutomationBuilder\Action\FileReadAction',
			'function' => '\Cerb\AutomationBuilder\Action\FunctionAction',
			'http.request' => '\Cerb\AutomationBuilder\Action\HttpRequestAction',
			'kata.parse' => '\Cerb\AutomationBuilder\Action\KataParseAction',
			'log' => '\Cerb\AutomationBuilder\Action\LogAction',
			'log.alert' => '\Cerb\AutomationBuilder\Action\LogAction',
			'log.error' => '\Cerb\AutomationBuilder\Action\LogAction',
			'log.warn' => '\Cerb\AutomationBuilder\Action\LogAction',
			'metric.increment' => '\Cerb\AutomationBuilder\Action\MetricIncrementAction',
			'queue.pop' => '\Cerb\AutomationBuilder\Action\QueuePopAction',
			'queue.push' => '\Cerb\AutomationBuilder\Action\QueuePushAction',
			'record.create' => '\Cerb\AutomationBuilder\Action\RecordCreateAction',
			'record.delete' => '\Cerb\AutomationBuilder\Action\RecordDeleteAction',
			'record.get' => '\Cerb\AutomationBuilder\Action\RecordGetAction',
			'record.search' => '\Cerb\AutomationBuilder\Action\RecordSearchAction',
			'record.update' => '\Cerb\AutomationBuilder\Action\RecordUpdateAction',
			'record.upsert' => '\Cerb\AutomationBuilder\Action\RecordUpsertAction',
			'return' => '\Cerb\AutomationBuilder\Action\ReturnAction',
			'set' => '\Cerb\AutomationBuilder\Action\SetAction',
			'simulate.error' => '\Cerb\AutomationBuilder\Action\SimulateErrorAction',
			'simulate.success' => '\Cerb\AutomationBuilder\Action\SimulateSuccessAction',
			'storage.delete' => '\Cerb\AutomationBuilder\Action\StorageDeleteAction',
			'storage.get' => '\Cerb\AutomationBuilder\Action\StorageGetAction',
			'storage.set' => '\Cerb\AutomationBuilder\Action\StorageSetAction',
			'var.expand' => '\Cerb\AutomationBuilder\Action\VarExpandAction',
			'var.push' => '\Cerb\AutomationBuilder\Action\VarPushAction',
			'var.set' => '\Cerb\AutomationBuilder\Action\VarSetAction',
			'var.unset' => '\Cerb\AutomationBuilder\Action\VarUnsetAction',
		];
	}
	
	public function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error = null) {
		$error = null;
		
		$action_type = $this->node->getNameType();
		
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
			
			if(false !== ($return_state = $action_class->activate($automation, $dict, $node_memory, $error))) {
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