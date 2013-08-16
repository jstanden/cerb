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

class Event_MailBeforeSentByGroup extends Extension_DevblocksEvent {
	const ID = 'event.mail.sent.group';
	
	static function trigger(&$properties, $message_id=null, $ticket_id=null, $group_id=null) {
		$events = DevblocksPlatform::getEventService();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'properties' => &$properties,
					'message_id' => $message_id,
					'ticket_id' => $ticket_id,
					'group_id' => $group_id,
					'_whisper' => array(
						CerberusContexts::CONTEXT_GROUP => array($group_id),
					),
				)
			)
		);
	}
	
	/**
	 *
	 * @param array $properties
	 * @param Model_Message $message
	 * @param Model_Ticket $ticket
	 * @param Model_Group $group
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($properties=null, $message_id=null, $ticket_id=null, $group_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($message_id)) {
			// Pull the latest ticket
			list($results) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Ticket::TICKET_ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$message_id = $result[SearchFields_Ticket::TICKET_LAST_MESSAGE_ID];
			$ticket_id = $result[SearchFields_Ticket::TICKET_ID];
			$group_id = $result[SearchFields_Ticket::TICKET_GROUP_ID];
		}
		
		$properties = array(
			'to' => 'customer@example.com',
			'cc' => 'boss@example.com',
			'bcc' => 'secret@example.com',
			'subject' => 'This is the subject',
			'ticket_reopen' => "+2 hours",
			'closed' => 2,
			'content' => "This is the message body\r\nOn more than one line.\r\n",
			'worker_id' => $active_worker->id,
		);
		
		$dict->content =& $properties['content'];
		$values['to'] =& $properties['to'];
		$values['cc'] =& $properties['cc'];
		$values['bcc'] =& $properties['bcc'];
		$values['subject'] =& $properties['subject'];
		$values['waiting_until'] =& $properties['ticket_reopen'];
		$values['closed'] =& $properties['closed'];
		$values['worker_id'] =& $properties['worker_id'];
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'properties' => $properties,
				'message_id' => $message_id,
				'ticket_id' => $ticket_id,
				'group_id' => $group_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();
		
		/**
		 * Properties
		 */
		
		@$properties =& $event_model->params['properties'];
		$prefix = 'Sent message ';
		
		$labels['content'] = $prefix.'content';
		$values['content'] =& $properties['content'];
		
		$labels['to'] = $prefix.'to';
		$values['to'] =& $properties['to'];
		
		$labels['cc'] = $prefix.'cc';
		$values['cc'] =& $properties['cc'];
		
		$labels['bcc'] = $prefix.'bcc';
		$values['bcc'] =& $properties['bcc'];

		$labels['subject'] = $prefix.'subject';
		$values['subject'] =& $properties['subject'];
		
		//$labels['waiting_until'] = $prefix.'waiting until';
		$values['waiting_until'] =& $properties['ticket_reopen'];
		
		//$labels['closed'] = $prefix.'is closed';
		$values['closed'] =& $properties['closed'];
		
		//$labels['worker_id'] = $prefix.'worker id';
		$values['worker_id'] =& $properties['worker_id'];
		
		/**
		 * Ticket
		 */

		@$ticket_id = $event_model->params['ticket_id'];
		
		$ticket_labels = array();
		$ticket_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $ticket_labels, $ticket_values, null, true);
		
			// Fill some custom values

			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$ticket_labels,
				$ticket_values,
				array(
					"#^group_#",
					//"#^id$#",
				)
			);
			
			// Merge
			CerberusContexts::merge(
				'ticket_',
				'',
				$ticket_labels,
				$ticket_values,
				$labels,
				$values
			);
			
		/**
		 * Group
		 */
		@$group_id = $event_model->params['group_id'];
		$group_labels = array();
		$group_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $group_id, $group_labels, $group_values, null, true);
				
			// Merge
			CerberusContexts::merge(
				'group_',
				'',
				$group_labels,
				$group_values,
				$labels,
				$values
			);
		
		/**
		 * Worker
		 */
		@$worker_id = $properties['worker_id'];
		$worker_labels = array();
		$worker_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $worker_labels, $worker_values, '', true);
				
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$worker_labels,
				$worker_values,
				array(
					"#^address_org_#",
				)
			);
		
			// Merge
			CerberusContexts::merge(
				'worker_',
				'Worker ',
				$worker_labels,
				$worker_values,
				$labels,
				$values
			);

		/**
		 * Signature
		 */
		$labels['group_sig'] = 'Group signature';
		if(!empty($group_id)) {
			if(null != ($group = DAO_Group::get($group_id))) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$sig_bucket_id = isset($ticket_values['bucket_id']) ? $ticket_values['bucket_id'] : 0;
					$values['group_sig'] = $group->getReplySignature($sig_bucket_id, $worker);
				}
			}
		}
			
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			/*
			'group_id' => array(
				'label' => 'Group',
				'context' => CerberusContexts::CONTEXT_GROUP,
			),
			*/
			'ticket_id' => array(
				'label' => 'Ticket',
				'context' => CerberusContexts::CONTEXT_TICKET,
			),
			'ticket_org_id' => array(
				'label' => 'Ticket org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'ticket_org_watchers' => array(
				'label' => 'Ticket org watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'ticket_owner_id' => array(
				'label' => 'Ticket owner',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'ticket_watchers' => array(
				'label' => 'Ticket watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'worker_id' => array(
				'label' => 'Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'worker_email_id' => array(
				'label' => 'Worker email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		asort($vals_to_ctx);
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['ticket_org_watcher_count'] = 'Ticket org watcher count';
		$labels['ticket_watcher_count'] = 'Ticket watcher count';
		
		$types = array(
			'bcc' => Model_CustomField::TYPE_SINGLE_LINE,
			'cc' => Model_CustomField::TYPE_SINGLE_LINE,
			'content' => Model_CustomField::TYPE_MULTI_LINE,
			'subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'to' => Model_CustomField::TYPE_SINGLE_LINE,
		
			'group_name' => Model_CustomField::TYPE_SINGLE_LINE,
		
			'ticket_owner_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_title' => Model_CustomField::TYPE_SINGLE_LINE,
		
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_spam_score' => null,
			'ticket_spam_training' => null,
			'ticket_status' => null,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
		
			'worker_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_address_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'worker_address_num_spam' => Model_CustomField::TYPE_NUMBER,
			'worker_address_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'worker_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'worker_title' => Model_CustomField::TYPE_SINGLE_LINE,
			
			'ticket_org_watcher_count' => null,
			'ticket_watcher_count' => null,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
			case 'ticket_org_watcher_count':
			case 'ticket_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			case 'ticket_spam_score':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = intval($dict->$token * 100);

				switch($oper) {
					case 'is':
						$pass = intval($value)==intval($params['value']);
						break;
					case 'gt':
						$pass = intval($value) > intval($params['value']);
						break;
					case 'lt':
						$pass = intval($value) < intval($params['value']);
						break;
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'ticket_spam_training':
			case 'ticket_status':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = $dict->$token;
				
				if(!isset($params['values']) || !is_array($params['values'])) {
					$pass = false;
					break;
				}
				
				switch($oper) {
					case 'in':
						$pass = false;
						foreach($params['values'] as $v) {
							if($v == $value) {
								$pass = true;
								break;
							}
						}
						break;
				}
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'ticket_org_watcher_count':
			case 'ticket_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				switch($token) {
					case 'ticket_org_watcher_count':
						$value = count($dict->ticket_org_watchers);
						break;
					case 'ticket_watcher_count':
					default:
						$value = count($dict->ticket_watchers);
						break;
				}
				
				switch($oper) {
					case 'is':
						$pass = intval($value)==intval($params['value']);
						break;
					case 'gt':
						$pass = intval($value) > intval($params['value']);
						break;
					case 'lt':
						$pass = intval($value) < intval($params['value']);
						break;
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions =
			array(
				'append_to_content' => array('label' =>'Append text to message content'),
				'prepend_to_content' => array('label' =>'Prepend text to message content'),
				'replace_content' => array('label' =>'Replace text in message content'),
				'create_notification' => array('label' =>'Create a notification'),
			)
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels())
			;
		
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
			case 'append_to_content':
			case 'prepend_to_content':
				$tpl->display('devblocks:cerberusweb.core::events/mail_before_sent_by_group/action_add_content.tpl');
				break;
				
			case 'replace_content':
				$tpl->display('devblocks:cerberusweb.core::events/mail_before_sent_by_group/action_replace_content.tpl');
				break;

			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification($trigger);
				break;
				
			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token, $matches)) {
					$field_id = $matches[2];
					$custom_field = DAO_CustomField::get($field_id);
					DevblocksEventHelper::renderActionSetCustomField($custom_field);
				}
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$ticket_id = $dict->ticket_id;

		switch($token) {
			case 'append_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $dict);
				$dict->content .= "\r\n" . $content;
				
				$out = sprintf(">>> Appending text to message content\n".
					"Text:\n%s\n".
					"Message:\n%s\n",
					$content,
					$dict->content
				);
				
				return $out;
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $dict);
				$dict->content = $content . "\r\n" . $dict->content;
				
				$out = sprintf(">>> Prepending text to message content\n".
					"Text:\n%s\n".
					"Message:\n%s\n",
					$content,
					$dict->content
				);
				
				return $out;
				break;
				
			case 'replace_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$replace = $tpl_builder->build($params['replace'], $dict);
				$with = $tpl_builder->build($params['with'], $dict);
				
				if(isset($params['is_regexp']) && !empty($params['is_regexp'])) {
					@$value = preg_replace($replace, $with, $dict->content);
				} else {
					$value = str_replace($replace, $with, $dict->content);
				}
				
				$before = $dict->body;
				
				if(!empty($value)) {
					$dict->content = trim($value,"\r\n");
				}
				
				$out = sprintf(">>> Replacing content\n".
					"Before:\n%s\n".
					"After:\n%s\n",
					$before,
					$dict->body
				);
				
				return $out;
				break;
		}

		if(empty($ticket_id))
			return;
		
		switch($token) {
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'ticket_id');
				break;

			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$ticket_id = $dict->ticket_id;

		switch($token) {
			case 'append_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$dict->content .= "\r\n" . $tpl_builder->build($params['content'], $dict);
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$dict->content = $tpl_builder->build($params['content'], $dict) . "\r\n" . $dict->content;
				break;
				
			case 'replace_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$replace = $tpl_builder->build($params['replace'], $dict);
				$with = $tpl_builder->build($params['with'], $dict);
				
				if(isset($params['is_regexp']) && !empty($params['is_regexp'])) {
					@$value = preg_replace($replace, $with, $dict->content);
				} else {
					$value = str_replace($replace, $with, $dict->content);
				}
				
				if(!empty($value)) {
					$dict->content = trim($value,"\r\n");
				}
				break;
		}

		if(empty($ticket_id))
			return;
		
		switch($token) {
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'ticket_id');
				break;

			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
};