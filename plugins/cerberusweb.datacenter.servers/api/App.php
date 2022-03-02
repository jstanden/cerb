<?php
class VaAction_CreateServer extends Extension_DevblocksEventAction {
	static function getMeta() {
		return [
			'notes' => 'Use [Record create](/docs/bots/events/actions/core.bot.action.record.create/) instead.',
			'deprecated' => true,
			'params' => [
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_SERVER, $tpl);
		
		// Template
		$tpl->display('devblocks:cerberusweb.datacenter.servers::events/action_create_server.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$name = $tpl_builder->build($params['name'] ?? '', $dict);
		
		$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'] ?? null,'array',[]);
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		if(empty($name))
			return "[ERROR] Name is required.";
		
		// Check dupes
		if(false != ($server = DAO_Server::getByName($name))) {
			return sprintf("[ERROR] Name must be unique. A server named '%s' already exists.", $name);
		}
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		$out = sprintf(">>> Creating server: %s\n", $name);
		
		// Custom fields
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetCustomFields($params, $dict);
		
		$out .= "\n";
		
		// Comment content
		if(!empty($comment)) {
			$out .= sprintf(">>> Writing comment on server\n\n".
				"%s\n\n",
				$comment
			);
			
			if(!empty($notify_worker_ids) && is_array($notify_worker_ids)) {
				$out .= ">>> Notifying\n";
				foreach($notify_worker_ids as $worker_id) {
					if(null != ($worker = DAO_Worker::get($worker_id))) {
						$out .= ' * ' . $worker->getName() . "\n";
					}
				}
				$out .= "\n";
			}
		}
		
		// Set object variable
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetVariable($params, $dict);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$name = $tpl_builder->build($params['name'] ?? '', $dict);

		$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'] ?? null,'array',[]);
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		if(empty($name))
			return;
		
		// Dupe check
		if(false != ($server = DAO_Server::getByName($name))) {
			return;
		}
		
		$fields = array(
			DAO_Server::NAME => $name,
		);
			
		if(false == ($server_id = DAO_Server::create($fields)))
			return;
		
		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_SERVER, $server_id, $params, $dict);
		
		// Comment content
		if(!empty($comment)) {
			$fields = array(
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_BOT,
				DAO_Comment::OWNER_CONTEXT_ID => $trigger->bot_id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_SERVER,
				DAO_Comment::CONTEXT_ID => $server_id,
				DAO_Comment::CREATED => time(),
			);
			DAO_Comment::create($fields, $notify_worker_ids);
		}

		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_SERVER, $server_id, $params, $dict);
	}
	
};

class EventListener_Datacenter extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				DAO_Server::maint();
				break;
		}
	}
};