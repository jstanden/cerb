<?php
abstract class AbstractEvent_FeedItem extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 * 
	 * @param integer $item_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($item_id=null) {
		
		if(empty($item_id)) {
			// Pull the latest record
			list($results) = DAO_FeedItem::search(
				array(),
				array(
					//new DevblocksSearchCriteria(SearchFields_FeedItem::IS_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_FeedItem::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$item_id = $result[SearchFields_FeedItem::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'item_id' => $item_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Feed Item
		 */
		
		@$item_id = $event_model->params['item_id']; 
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext('cerberusweb.contexts.feed.item', $item_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'item_',
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
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
//		$labels['ticket_has_owner'] = 'Ticket has owner';
		
		$types = array(
			'item_created_date|date' => Model_CustomField::TYPE_DATE,
			'item_guid' => Model_CustomField::TYPE_SINGLE_LINE,
			'item_is_closed' => Model_CustomField::TYPE_CHECKBOX,
			'item_title' => Model_CustomField::TYPE_SINGLE_LINE,
			'item_url' => Model_CustomField::TYPE_URL,
			
			'item_feed_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'item_feed_url' => Model_CustomField::TYPE_URL,
		
//			'ticket_has_owner' => null,
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
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, $values) {
		$pass = true;
		
		switch($token) {
//			case 'ticket_has_owner':
//				$bool = $params['bool'];
//				@$value = $values['ticket_owner_id'];
//				$pass = ($bool == !empty($value));
//				break;
				
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions = 
			array(
				'add_watchers' => array('label' =>'Add watchers'),
				'create_comment' => array('label' =>'Create a comment'),
				'create_notification' => array('label' =>'Create a notification'),
				'create_task' => array('label' =>'Create a task'),
				'create_ticket' => array('label' =>'Create a ticket'),
				'schedule_behavior' => array('label' => 'Schedule behavior'),
// 				'send_email' => array('label' => 'Send email'),
// 				'set_status' => array('label' => 'Set status'),
			)
			+ DevblocksEventHelper::getActionCustomFields('cerberusweb.contexts.feed.item')
			;
			
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels();
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::renderActionAddWatchers();
				break;
			
// 			case 'send_email':
// 				DevblocksEventHelper::renderActionSendEmail();
// 				break;
				
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment();
				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification();
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask();
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket();
				break;
				
			case 'schedule_behavior':
				$dates = array();
				$conditions = $this->getConditions();
				foreach($conditions as $key => $data) {
					if($data['type'] == Model_CustomField::TYPE_DATE)
					$dates[$key] = $data['label'];
				}
				$tpl->assign('dates', $dates);
			
				DevblocksEventHelper::renderActionScheduleBehavior($trigger->owner_context, $trigger->owner_context_id, $this->_event_id);
				break;
				
// 			case 'set_status':
// 				$tpl->display('devblocks:cerberusweb.core::events/model/task/action_set_status.tpl');
// 				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					DevblocksEventHelper::renderActionSetCustomField($custom_field);
				}
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');		
	}
	
	function runActionExtension($token, $trigger, $params, &$values) {
		@$item_id = $values['item_id'];

		if(empty($item_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $values, 'cerberusweb.contexts.feed.item', $item_id);
				break;
			
// 			case 'send_email':
// 				DevblocksEventHelper::runActionSendEmail($params, $values);
// 				break;
				
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, 'cerberusweb.contexts.feed.item', $item_id);
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $values, 'cerberusweb.contexts.feed.item', $item_id);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, 'cerberusweb.contexts.feed.item', $item_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, 'cerberusweb.contexts.feed.item', $item_id);
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $values, 'cerberusweb.contexts.feed.item', $item_id);
				break;
				
// 			case 'set_status':
// 				@$to_status = $params['status'];
// 				@$current_status = $values['task_status'];
				
// 				if($to_status == $current_status)
// 					break;
				
// 				$fields = array();
					
// 				switch($to_status) {
// 					case 'active':
// 						$fields = array(
// 							DAO_FeedItem::IS_COMPLETED => 0,
// 							DAO_FeedItem::COMPLETED_DATE => 0,
// 						);
// 						break;
// 					case 'completed':
// 						$fields = array(
// 							DAO_FeedItem::IS_COMPLETED => 1,
// 							DAO_FeedItem::COMPLETED_DATE => time(),
// 						);
// 						break;
// 				}
				
// 				if(!empty($fields)) {
// 					$values['status'] = $to_status;
// 					DAO_FeedItem::update($item_id, $fields);
// 				}
				
// 				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case 'cerberusweb.contexts.feed.item':
							$context = $custom_field->context;
							$context_id = $item_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'item_custom', $params, $values, $context, $context_id);
				}
				break;	
		}
	}
	
};