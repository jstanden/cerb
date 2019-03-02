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

class Event_NotificationReceivedByWorker extends Extension_DevblocksEvent {
	const ID = 'event.notification.received.worker';
	
	static function trigger($notification_id, $worker_id) {
		$events = DevblocksPlatform::services()->event();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'notification_id' => $notification_id,
					'_whisper' => array(
						CerberusContexts::CONTEXT_WORKER => array($worker_id),
					),
				)
			)
		);
	}
	
	/**
	 *
	 * @param integer $notification_id
	 * @param integer $worker_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $notification_id=null, $worker_id=null) {
		if(empty($worker_id) || null == ($worker = DAO_Worker::get($worker_id)))
			$worker = CerberusApplication::getActiveWorker();
		
		if(empty($notification_id)) {
			// Pull the latest ticket
			list($results) = DAO_Notification::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Notification::WORKER_ID,'=',$worker->id),
				),
				10,
				0,
				SearchFields_Notification::CREATED_DATE,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$notification_id = $result[SearchFields_Notification::ID];
			$worker_id = $worker->id;
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'notification_id' => $notification_id,
				'worker_id' => $worker_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		@$notification_id = $event_model->params['notification_id'];
		
		$labels = array();
		$values = array();
		
		/**
		 * Behavior
		 */
		
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BEHAVIOR, $trigger, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'behavior_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_NOTIFICATION, $notification_id, $labels, $values, null, true);
		
		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function renderSimulatorTarget($trigger, $event_model) {
		$context = CerberusContexts::CONTEXT_NOTIFICATION;
		$context_id = $event_model->params['notification_id'];
		DevblocksEventHelper::renderSimulatorTarget($context, $context_id, $trigger, $event_model);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			'behavior_id' => array(
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'behavior_bot_id' => array(
				'label' => 'Bot',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'id' => array(
				'label' => 'Notification',
				'context' => CerberusContexts::CONTEXT_NOTIFICATION,
			),
			'assignee_id' => array(
				'label' => 'Assignee',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'assignee_address_id' => array(
				'label' => 'Assignee email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'assignee_id' => array(
				'label' => 'Assignee org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		DevblocksPlatform::sortObjects($vals_to_ctx, '[label]');
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions = array(
			'send_email' => array('label' => 'Send email'),
			'send_email_owner' => array('label' => 'Send email to notified worker'),
			'create_task' => array('label' =>'Create task'),
			'mark_read' => array('label' =>'Mark read'),
		);
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask($trigger);
				break;
				
			case 'mark_read':
				echo "The notification will be marked as read.";
				break;
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;
				
			case 'send_email_owner':
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				if(false == ($va = $trigger->getBot()))
					break;
				
				$addresses = DAO_Address::getByWorkerId($va->owner_context_id);
				$tpl->assign('addresses', $addresses);
				
				$tpl->display('devblocks:cerberusweb.core::events/notification_received_by_owner/action_send_email_owner.tpl');
				break;
		}
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$notification_id = $dict->id;

		if(empty($notification_id))
			return;
		
		switch($token) {
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
			
			case 'send_email_owner':
				$to = array();
				
				if(isset($params['to'])) {
					$to = $params['to'];
					
				} else {
					// Default to worker email address
					@$to = array($dict->assignee_address_address);
				}
				
				if(
					empty($to)
					|| !isset($params['subject'])
					|| !isset($params['content'])
				)
					break;
				
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$subject = strtr($tpl_builder->build($params['subject'], $dict), "\r\n", ' '); // no CRLF
				$content = $tpl_builder->build($params['content'], $dict);

				$out = sprintf(">>> Sending email to notified worker\nTo: %s\nSubject: %s\n\n%s",
					implode('; ', $to),
					$subject,
					$content
				);
				
				return $out;
				break;
				
			case 'mark_read':
				return ">>> Marking notification as read\n";
				break;
				
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'id');
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$notification_id = $dict->id;

		if(empty($notification_id))
			return;
		
		switch($token) {
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'id');
				break;
			
			case 'mark_read':
				DAO_Notification::update($notification_id, array(
					DAO_Notification::IS_READ => 1,
				));
				$dict->is_read = 1;
				break;
			
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;
				
			case 'send_email_owner':
				$to = array();
				
				if(isset($params['to'])) {
					$to = $params['to'];
					
				} else {
					// Default to worker email address
					@$to = array($dict->assignee_address_address);
				}
				
				if(
					empty($to)
					|| !isset($params['subject'])
					|| !isset($params['content'])
				)
					break;
				
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$subject = strtr($tpl_builder->build($params['subject'], $dict), "\r\n", ' '); // no CRLF
				$content = $tpl_builder->build($params['content'], $dict);

				if(is_array($to))
				foreach($to as $to_addy) {
					CerberusMail::quickSend(
						$to_addy,
						$subject,
						$content
					);
				}
				break;
		}
	}
	
};