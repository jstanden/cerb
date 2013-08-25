<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class Event_NotificationReceivedByWorker extends Extension_DevblocksEvent {
	const ID = 'event.notification.received.worker';
	
	static function trigger($notification_id, $worker_id) {
		$events = DevblocksPlatform::getEventService();
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
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		@$notification_id = $event_model->params['notification_id'];
		
		$labels = array();
		$values = array();
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
		asort($vals_to_ctx);
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		// [TODO] Move this into snippets somehow
		$types = array(
			'created|date' => Model_CustomField::TYPE_DATE,
			'message' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_read' => Model_CustomField::TYPE_CHECKBOX,
			'url' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_title' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'assignee_address_num_spam' => Model_CustomField::TYPE_NUMBER,
			'assignee_address_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'assignee_address_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_created' => Model_CustomField::TYPE_DATE,
			'assignee_address_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'assignee_address_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $trigger, $params=array(), $seq=null) {
		$conditions = $this->getConditions($trigger);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		//$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_bool.tpl');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions = array(
			'send_email_owner' => array('label' => 'Send email to notified worker'),
			'create_task' => array('label' =>'Create a task'),
			'mark_read' => array('label' =>'Mark read'),
		);
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
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
				
			case 'send_email_owner':
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				if(false == ($va = $trigger->getVirtualAttendant()))
					break;
				
				// [TODO] Refactor this to a simple email 'To:' input box
				$addresses = DAO_AddressToWorker::getByWorker($va->owner_context_id);
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
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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