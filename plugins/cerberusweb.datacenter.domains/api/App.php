<?php
class VaAction_CreateDomain extends Extension_DevblocksEventAction {
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
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_DOMAIN, $tpl);
		
		// Template
		$tpl->display('devblocks:cerberusweb.datacenter.domains::events/action_create_domain.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$out = null;
		
		$name = $tpl_builder->build($params['name'] ?? '', $dict);
		
		$server_id = DevblocksPlatform::importVar($params['server_id'] ?? null,'string','');
		$email_ids = DevblocksPlatform::importVar($params['email_ids'] ?? null,'array',[]);
		
		$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'] ?? null,'array',[]);
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		if(empty($name))
			return "[ERROR] Name is required.";
		
		// Check dupes
		if(false != (DAO_Domain::getByName($name))) {
			return sprintf("[ERROR] Name must be unique. A domain named '%s' already exists.", $name);
		}
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		$out = sprintf(">>> Creating domain: %s\n", $name);
		
		// Server
		
		if(!is_numeric($server_id) && isset($dict->$server_id)) {
			if(is_array($dict->$server_id)) {
				$server_id = key($dict->$server_id);
			} else {
				$server_id = $dict->$server_id;
			}
		}
		
		$server_id = intval($server_id);
		
		if($server = DAO_Server::get($server_id)) {
			$out .= sprintf("Server: %s\n", $server->name);
		}
		
		// Contacts
		
		if(is_array($email_ids))
		foreach($email_ids as $idx => $email_id) {
			if(!is_numeric($email_id) && isset($dict->$email_id)) {
				if(is_array($dict->$email_id)) {
					$email_ids = array_merge($email_ids, array_keys($dict->$email_id));
				} else {
					$email_ids[] = $dict->$email_id;
				}
				unset($email_ids[$idx]);
			}
		}
		
		$email_ids = DevblocksPlatform::sanitizeArray($email_ids, 'int');
		
		if(!empty($email_ids)) {
			$out .= "Contacts:\n";
			
			$models = DAO_Address::getIds($email_ids);
			
			if(is_array($models))
			foreach($models as $model) {
				$out .= " * " . $model->email . "\n";
			}
		}
		
		// Custom fields

		$out .= DevblocksEventHelper::simulateActionCreateRecordSetCustomFields($params, $dict);
		
		$out .= "\n";
		
		// Comment content
		if(!empty($comment)) {
			$out .= sprintf(">>> Writing comment on domain\n\n".
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
		
		$server_id = DevblocksPlatform::importVar($params['server_id'] ?? null,'string','');
		$email_ids = DevblocksPlatform::importVar($params['email_ids'] ?? null,'array',[]);
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		if(empty($name))
			return;
		
		// Dupe check
		if((DAO_Domain::getByName($name))) {
			return;
		}
		
		// Server
		
		if(!is_numeric($server_id) && isset($dict->$server_id)) {
			if(is_array($dict->$server_id)) {
				$server_id = key($dict->$server_id);
			} else {
				$server_id = $dict->$server_id;
			}
		}
		
		$server_id = intval($server_id);
		
		// Contacts
		
		if(is_array($email_ids))
		foreach($email_ids as $idx => $email_id) {
			if(!is_numeric($email_id) && isset($dict->$email_id)) {
				if(is_array($dict->$email_id)) {
					$email_ids = array_merge($email_ids, array_keys($dict->$email_id));
				} else {
					$email_ids[] = $dict->$email_id;
				}
				unset($email_ids[$idx]);
			}
		}
		
		$email_ids = DevblocksPlatform::sanitizeArray($email_ids, 'int');
		
		$fields = array(
			DAO_Domain::NAME => $name,
			DAO_Domain::SERVER_ID => $server_id,
		);
			
		if(!($domain_id = DAO_Domain::create($fields)))
			return;
		
		// Contact links
		
		if(is_array($email_ids))
		foreach($email_ids as $email_id)
			DAO_ContextLink::setLink(CerberusContexts::CONTEXT_DOMAIN, $domain_id, CerberusContexts::CONTEXT_ADDRESS, $email_id);
		
		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_DOMAIN, $domain_id, $params, $dict);
		
		// Comment content
		if(!empty($comment)) {
			$fields = [
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_BOT,
				DAO_Comment::OWNER_CONTEXT_ID => $trigger->bot_id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_DOMAIN,
				DAO_Comment::CONTEXT_ID => $domain_id,
				DAO_Comment::CREATED => time(),
			];
			$comment_id = DAO_Comment::create($fields);
			DAO_Comment::onUpdateByActor($trigger->getBot(), $fields, $comment_id);
		}
		
		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_DOMAIN, $domain_id, $params, $dict);
	}
	
};

class EventListener_DatacenterDomains extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				DAO_Domain::maint();
				break;
		}
	}
};