<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class CallsEventListener extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				DAO_CallEntry::maint();
				break;
		}
	}
};

class WgmCalls_EventActionPost extends Extension_DevblocksEventAction {
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
		$tpl->assign('workers', DAO_Worker::getAll());
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_CALL, $tpl);
		
		// Template
		$tpl->display('devblocks:cerberusweb.calls::calls/events/action_create_call.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
				
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$subject = $tpl_builder->build($params['subject'], $dict);
		$phone = $tpl_builder->build($params['phone'], $dict);
		$is_outgoing = $params['is_outgoing'];
		$is_closed = $params['is_closed'];
		$created = intval(@strtotime($tpl_builder->build($params['created'], $dict)));
		$comment = $tpl_builder->build($params['comment'], $dict);

		if(empty($created))
			$created = time();
		
		$out = sprintf(">>> Creating call\n".
			"Subject: %s\n".
			"Phone #: %s\n".
			"Type: %s\n".
			"Status: %s\n".
			"Created: %s (%s)\n".
			"",
			$subject,
			$phone,
			($is_outgoing ? 'Outgoing' : 'Incoming'),
			($is_closed ? 'Closed' : 'Open'),
			(!empty($created) ? date("Y-m-d h:ia", $created) : 'none'),
			$params['created']
		);
		
		// Custom fields
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetCustomFields($params, $dict);
		
		$out .= "\n";
		
		// Watchers
		if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
			$out .= ">>> Adding watchers to call:\n";
			foreach($watcher_worker_ids as $worker_id) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$out .= ' * ' . $worker->getName() . "\n";
				}
			}
			$out .= "\n";
		}
		
		// Comment content
		if(!empty($comment)) {
			$out .= sprintf(">>> Writing comment on call\n\n".
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
		
		// Connection
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetLinks($params, $dict);
		

		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		// Set object variable
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetVariable($params, $dict);
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
				
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$subject = $tpl_builder->build($params['subject'], $dict);
		$phone = $tpl_builder->build($params['phone'], $dict);
		$is_outgoing = intval($params['is_outgoing']);
		$is_closed = intval($params['is_closed']);
		$created = intval(@strtotime($tpl_builder->build($params['created'], $dict)));
		$comment = $tpl_builder->build($params['comment'], $dict);

		if(empty($created))
			$created = time();
		
		$trigger = $dict->__trigger;
		
		$fields = array(
			DAO_CallEntry::SUBJECT => $subject,
			DAO_CallEntry::PHONE => $phone,
			DAO_CallEntry::CREATED_DATE => $created,
			DAO_CallEntry::UPDATED_DATE => time(),
			DAO_CallEntry::IS_CLOSED => $is_closed ? 1 : 0,
			DAO_CallEntry::IS_OUTGOING => $is_outgoing ? 1 : 0,
		);
		
		if(false == ($call_id = DAO_CallEntry::create($fields)))
			return false;
		
		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_CALL, $call_id, $params, $dict);
		
		// Watchers
		if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_CALL, $call_id, $watcher_worker_ids);
		}
		
		// Comment content
		if(!empty($comment)) {
			$fields = array(
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_BOT,
				DAO_Comment::OWNER_CONTEXT_ID => $trigger->bot_id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_CALL,
				DAO_Comment::CONTEXT_ID => $call_id,
				DAO_Comment::CREATED => time(),
			);
			DAO_Comment::create($fields, $notify_worker_ids);
		}
		
		// Links
		DevblocksEventHelper::runActionCreateRecordSetLinks(CerberusContexts::CONTEXT_CALL, $call_id, $params, $dict);
		
		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_CALL, $call_id, $params, $dict);

		return $call_id;
	}
};