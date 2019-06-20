<?php
class BotAction_CreateAttachment extends Extension_DevblocksEventAction {
	const ID = 'core.va.action.create_attachment';
	
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
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_create_attachment.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$out = null;
		
		@$file_name = $tpl_builder->build($params['file_name'], $dict);
		@$file_type = $tpl_builder->build($params['file_type'], $dict);
		@$content = $tpl_builder->build($params['content'], $dict);
		@$object_placeholder = $params['object_placeholder'] ?: '_attachment_meta';
		
		if(empty($file_name))
			return "[ERROR] File name is required.";
		
		if(empty($content))
			return "[ERROR] File content is required.";
		
		// MIME Type
		
		if(empty($file_type))
			$file_type = 'text/plain';

		$out = sprintf(">>> Creating attachment: %s (%s)\n", $file_name, $file_type);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$out .= sprintf("\n>>> Saving metadata to {{%1\$s}}\n".
				" * {{%1\$s.id}}\n".
				" * {{%1\$s.name}}\n".
				" * {{%1\$s.type}}\n".
				" * {{%1\$s.size}}\n".
				" * {{%1\$s.hash}}\n".
				"\n",
				$object_placeholder
			);
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
		
		@$file_name = $tpl_builder->build($params['file_name'], $dict);
		@$file_type = $tpl_builder->build($params['file_type'], $dict);
		@$content = $tpl_builder->build($params['content'], $dict);
		@$content_encoding = $params['content_encoding'];
		@$object_placeholder = $params['object_placeholder'] ?: '_attachment_meta';
		
		// MIME Type
		
		if(empty($file_type))
			$file_type = 'text/plain';
		
		// Encoding
		
		switch($content_encoding) {
			case 'base64':
				$content = base64_decode($content);
				break;
		}
		
		$file_size = strlen($content);

		$sha1_hash = sha1($content, false);
		
		if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $file_name))) {
			$fields = array(
				DAO_Attachment::NAME => $file_name,
				DAO_Attachment::MIME_TYPE => $file_type,
				DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
			);
				
			$file_id = DAO_Attachment::create($fields);
		}

		if(empty($file_id))
			return;
		
		if(false == Storage_Attachments::put($file_id, $content))
			return;
		
		unset($content);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$dict->$object_placeholder = array(
				'id' => $file_id,
				'name' => $file_name,
				'type' => $file_type,
				'size' => $file_size,
				'hash' => $sha1_hash,
			);
		}
		
		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_ATTACHMENT, $file_id, $params, $dict);
	}
};

class BotAction_CreateReminder extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.create_reminder';
	
	static function getMeta() {
		return [
			'notes' => 'Use [Record create](/docs/bots/events/actions/core.bot.action.record.create/) instead.',
			'deprecated' => true,
			'params' => [
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);

		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_REMINDER, $tpl);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_create_reminder.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$out = null;
		
		@$name = $tpl_builder->build($params['name'], $dict);
		@$remind_at = $tpl_builder->build($params['remind_at'], $dict);
		@$object_placeholder = $params['object_placeholder'] ?: '_attachment_meta';
		
		@$worker_ids = DevblocksPlatform::importVar($params['worker_id'],'string','');
		$worker_ids = DevblocksEventHelper::mergeWorkerVars($worker_ids, $dict);
		
		if(empty($name))
			return "[ERROR] 'Name' is required.";
		
		if(empty($remind_at))
			return "[ERROR] 'Remind at' is required.";
		
		$out = sprintf(">>> Creating reminder: %s (%s)\n", $name, $remind_at);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$out .= sprintf("\n>>> Saving metadata to {{%1\$s}}\n".
				" * {{%1\$s.id}}\n".
				" * {{%1\$s.name}}\n".
				" * {{%1\$s.remind_at}}\n".
				" * {{%1\$s.url}}\n".
				" * {{%1\$s.worker_id}}\n".
				"\n",
				$object_placeholder
			);
		}
		
		// Connection
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetLinks($params, $dict);
		
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
		
		@$name = $tpl_builder->build($params['name'], $dict);
		@$remind_at = $tpl_builder->build($params['remind_at'], $dict);
		@$behavior_ids = $params['behavior_ids'] ?: [];
		@$behaviors = $params['behaviors'] ?: [];
		@$object_placeholder = $params['object_placeholder'] ?: '_reminder_meta';
		
		@$worker_ids = DevblocksPlatform::importVar($params['worker_id'],'string','');
		$worker_ids = DevblocksEventHelper::mergeWorkerVars($worker_ids, $dict);
		$worker_id = array_shift($worker_ids) ?: 0;
		
		$reminder_params = ['behaviors' => []];
		
		if(is_array($behavior_ids))
		foreach($behavior_ids as $behavior_id)
			$reminder_params['behaviors'][$behavior_id] = @$behaviors[$behavior_id] ?: [];
		
		$fields = [
			DAO_Reminder::NAME => $name,
			DAO_Reminder::REMIND_AT => @strtotime($remind_at) ?: 0,
			DAO_Reminder::IS_CLOSED => 0,
			DAO_Reminder::PARAMS_JSON => json_encode($reminder_params),
			DAO_Reminder::UPDATED_AT => time(),
			DAO_Reminder::WORKER_ID => $worker_id,
		];
		
		$remind_id = DAO_Reminder::create($fields);

		if(empty($remind_id))
			return;
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$url_writer = DevblocksPlatform::services()->url();
			
			$dict->$object_placeholder = [
				'id' => $remind_id,
				'name' => $name,
				'remind_at' => $remind_at,
				'url' => $url_writer->write('c=profiles&what=reminder&id=' . $remind_id, true) . '-' . DevblocksPlatform::strToPermalink($name),
				'worker_id' => $worker_id,
			];
		}
		
		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_REMINDER, $remind_id, $params, $dict);
		
		// Connection
		DevblocksEventHelper::runActionCreateRecordSetLinks(CerberusContexts::CONTEXT_REMINDER, $remind_id, $params, $dict);

		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_REMINDER, $remind_id, $params, $dict);
	}
};