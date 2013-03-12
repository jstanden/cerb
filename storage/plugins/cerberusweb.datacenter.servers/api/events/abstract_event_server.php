<?php
abstract class AbstractEvent_Server extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 * 
	 * @param integer $server_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($server_id=null) {
		
		if(empty($server_id)) {
			// Pull the latest record
			list($results) = DAO_Server::search(
				array(),
				array(
					//new DevblocksSearchCriteria(SearchFields_Server::IS_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Server::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$server_id = $result[SearchFields_Server::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'server_id' => $server_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Server
		 */
		
		@$server_id = $event_model->params['server_id']; 
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext('cerberusweb.contexts.datacenter.server', $server_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'server_',
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

	function getValuesContexts($trigger) {
		$vals = array(
			'server_id' => array(
				'label' => 'Server',
				'context' => CerberusContexts::CONTEXT_SERVER,
			),
			'server_watchers' => array(
				'label' => 'Server watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		asort($vals_to_ctx);
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['server_link'] = 'Server is linked';
		$labels['server_watcher_count'] = 'Server watcher count';
		
		$types = array(
			'server_name' => Model_CustomField::TYPE_SINGLE_LINE,
			
			'server_link' => null,
			'server_watcher_count' => null,
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
			case 'server_link':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/condition_link.tpl');
				break;
				
			case 'server_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			case 'server_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;
				
				switch($token) {
					case 'server_link':
						$from_context = CerberusContexts::CONTEXT_SERVER;
						@$from_context_id = $dict->server_id;
						break;
					default:
						$pass = false;
				}
				
				// Get links by context+id

				if(!empty($from_context) && !empty($from_context_id)) {
					@$context_strings = $params['context_objects'];
					$links = DAO_ContextLink::intersect($from_context, $from_context_id, $context_strings);
					
					// OPER: any, !any, all
	
					switch($oper) {
						case 'in':
							$pass = (is_array($links) && !empty($links));
							break;
						case 'all':
							$pass = (is_array($links) && count($links) == count($context_strings));
							break;
						default:
							$pass = false;
							break;
					}
					
					$pass = ($not) ? !$pass : $pass;
					
				} else {
					$pass = false;
				}
				
				break;
				
			case 'server_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				$value = count($dict->server_watchers);
				
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
				'add_watchers' => array('label' =>'Add watchers'),
				'create_comment' => array('label' =>'Create a comment'),
				'create_notification' => array('label' =>'Create a notification'),
				'create_task' => array('label' =>'Create a task'),
				'create_ticket' => array('label' =>'Create a ticket'),
				'schedule_behavior' => array('label' => 'Schedule behavior'),
				'set_links' => array('label' => 'Set links'),
				'unschedule_behavior' => array('label' => 'Unschedule behavior'),
			)
			+ DevblocksEventHelper::getActionCustomFields('cerberusweb.contexts.datacenter.server')
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
				DevblocksEventHelper::renderActionAddWatchers($trigger);
				break;
			
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
				
			case 'schedule_behavior':
				$dates = array();
				$conditions = $this->getConditions($trigger);
				foreach($conditions as $key => $data) {
					if(isset($data['type']) && $data['type'] == Model_CustomField::TYPE_DATE)
						$dates[$key] = $data['label'];
				}
				$tpl->assign('dates', $dates);
			
				DevblocksEventHelper::renderActionScheduleBehavior($trigger);
				break;

			case 'unschedule_behavior':
				DevblocksEventHelper::renderActionUnscheduleBehavior($trigger);
				break;
				
			case 'set_links':
				DevblocksEventHelper::renderActionSetLinks($trigger);
				break;
				
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
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$server_id = $dict->server_id;

		if(empty($server_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				return DevblocksEventHelper::simulateActionAddWatchers($params, $dict, 'server_id');
				break;
			
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'server_id');
				break;
				
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'server_id');
				break;
				
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'server_id');
				break;

			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict, 'server_id');
				break;
				
			case 'schedule_behavior':
				return DevblocksEventHelper::simulateActionScheduleBehavior($params, $dict);
				break;
				
			case 'unschedule_behavior':
				return DevblocksEventHelper::simulateActionUnscheduleBehavior($params, $dict);
				break;
				
			case 'set_links':
				return DevblocksEventHelper::simulateActionSetLinks($trigger, $params, $dict);
				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case 'cerberusweb.contexts.datacenter.server':
							$context = $custom_field->context;
							$context_id = $server_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						return DevblocksEventHelper::simulateActionSetCustomField($custom_field, 'server_custom', $params, $dict, $context, $context_id);
				}
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$server_id = $dict->server_id;

		if(empty($server_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $dict, 'server_id');
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'server_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'server_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'server_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict, 'server_id');
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $dict);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::runActionUnscheduleBehavior($params, $dict);
				break;
				
			case 'set_links':
				DevblocksEventHelper::runActionSetLinks($trigger, $params, $dict);
				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case 'cerberusweb.contexts.datacenter.server':
							$context = $custom_field->context;
							$context_id = $server_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'server_custom', $params, $dict, $context, $context_id);
				}
				break;	
		}
	}
	
};