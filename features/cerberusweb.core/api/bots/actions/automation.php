<?php
class BotAction_Automation extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.automation';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'automations_kata' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The [Events KATA](https://cerb.ai/docs/automations#events) binding possible automation functions',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the automation results',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_automation.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$object_placeholder = ($params['object_placeholder'] ?? null) ?: '_results';
		$error = null;
		
		$event_state = $dict->getDictionary();
		unset($event_state['_labels']);
		unset($event_state['_types']);
		unset($event_state['_current_time']);
		unset($event_state['__trigger']);
		
		$event_dict = DevblocksDictionaryDelegate::instance($event_state);
		
		$handlers = $event_handler->parse($params['automations_kata'] ?? null, $event_dict, $error);
		
		$out = sprintf(">>> Executing an automation with first responder:\n\n%s\n",
			DevblocksPlatform::services()->string()->indentWith(
				DevblocksPlatform::services()->string()->yamlEmit($handlers, false),
				'  '
			)
		);
		
		// Run in simulator
		$run_in_simulator = (bool)($params['run_in_simulator'] ?? null);
		
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
			
			// Set placeholder with object meta
			
			if(!empty($object_placeholder)) {
				$out .= sprintf("\n>>> Saving results to {{%s}}\n%s\n",
					$object_placeholder,
					DevblocksPlatform::services()->string()->yamlEmit($dict->get($object_placeholder), false)
				);
			}
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$object_placeholder = ($params['object_placeholder'] ?? null) ?: '_results';
		$initial_state = [];
		$error = null;
		$handler = null;
		
		$event_state = $dict->getDictionary();
		unset($event_state['_labels']);
		unset($event_state['_types']);
		unset($event_state['_current_time']);
		unset($event_state['__trigger']);
		
		$event_dict = DevblocksDictionaryDelegate::instance($event_state);
		
		$handlers = $event_handler->parse($params['automations_kata'] ?? null, $event_dict, $error);
		
		$result = $event_handler->handleOnce(
			AutomationTrigger_BehaviorAction::ID,
			$handlers,
			$initial_state,
			$error,
			null,
			$handler
		);
		
		if($result instanceof DevblocksDictionaryDelegate) {
			$exit_code = $result->getKeyPath('__exit');
			
			if(in_array($exit_code, ['exit','return'])) {
				$result = [
					'exit' => $exit_code,
					'return' => $result->getKeyPath('__return', [])
				];
				
			} else if('error' == $exit_code) {
				$result = [
					'exit' => 'error',
					'error' => $result->getKeyPath('error', null),
				];
			}
			
		} else {
			$result = [
				'exit' => 'error',
				'error' => $error ?? null,
			];
		}
		
		// Set placeholder with object meta
		
		if($object_placeholder) {
			$dict->set($object_placeholder, $result);
		}
	}
};