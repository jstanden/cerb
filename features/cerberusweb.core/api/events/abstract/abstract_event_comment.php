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

abstract class AbstractEvent_Comment extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 *
	 * @param integer $comment_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $comment_id=null) {
		
		if(empty($comment_id)) {
			// Pull the latest record
			list($results) = DAO_Comment::search(
				array(),
				array(
				),
				10,
				0,
				SearchFields_Comment::ID,
				true,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$comment_id = $result[SearchFields_Comment::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'comment_id' => $comment_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Comment
		 */
		
		@$comment_id = $event_model->params['comment_id'];
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_COMMENT, $comment_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'comment_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);

		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function renderSimulatorTarget($trigger, $event_model) {
		$context = CerberusContexts::CONTEXT_COMMENT;
		$context_id = $event_model->params['comment_id'];
		DevblocksEventHelper::renderSimulatorTarget($context, $context_id, $trigger, $event_model);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			'comment_id' => array(
				'label' => 'Comment',
				'context' => CerberusContexts::CONTEXT_COMMENT,
			),
			'comment_owner_context' => array(
				'label' => 'Comment author',
				'is_polymorphic' => true,
				'context' => 'comment_owner_context',
				'context_id' => 'comment_owner_context_id',
			),
			'comment_context' => array(
				'label' => 'Comment record',
				'is_polymorphic' => true,
				'context' => 'comment_context',
				'context_id' => 'comment_context_id',
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		asort($vals_to_ctx);
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();

		$labels['comment_context'] = 'Comment record type';
		$labels['comment_owner_context'] = 'Comment author type';
		
		$types = array(
			'comment_created' => Model_CustomField::TYPE_DATE,
			'comment_comment' => Model_CustomField::TYPE_MULTI_LINE,
		);

		$types['comment_context'] = null;
		$types['comment_owner_context'] = null;
		$types['comment_record_label'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['comment_author_label'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);

		return $conditions;
	}
	
	function renderConditionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			case 'comment_context':
			case 'comment_owner_context':
				$context_exts = Extension_DevblocksContext::getAll(false);
				$options = array();
				
				foreach($context_exts as $context_id => $context_mft) {
					$options[$context_id] = $context_mft->name;
				}
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_list.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			case 'comment_context':
			case 'comment_owner_context':
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
				
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions =
			array(
				'create_comment' => array('label' =>'Create a comment'),
				'create_notification' => array('label' =>'Create a notification'),
				'create_task' => array('label' =>'Create a task'),
				'create_ticket' => array('label' =>'Create a ticket'),
				'send_email' => array('label' => 'Send email'),
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
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment($trigger);
				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification($trigger);
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask($trigger);
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket($trigger);
				break;
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;

			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token, $matches)) {
					$field_id = $matches[2];
					$custom_field = DAO_CustomField::get($field_id);
					DevblocksEventHelper::renderActionSetCustomField($custom_field, $trigger);
				}
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$comment_id = $dict->comment_id;

		if(empty($comment_id))
			return;
		
		switch($token) {
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'comment_id');
				break;
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'comment_id');
				break;
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'comment_id');
				break;
			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict);
				break;
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$comment_id = $dict->comment_id;

		if(empty($comment_id))
			return;
		
		switch($token) {
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'comment_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'comment_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'comment_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict);
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;
				
			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
};