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

abstract class AbstractEvent_ApiRequest extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 *
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger) {
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'virtual_attendant_id' => $trigger->virtual_attendant_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		// Load the VA of the current macro
		
		/**
		 * Virtual Attendant
		 */
		
		@$virtual_attendant_id = $event_model->params['virtual_attendant_id'];
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $virtual_attendant_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'va_',
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
		$context = CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT;
		$context_id = $event_model->params['virtual_attendant_id'];
		DevblocksEventHelper::renderSimulatorTarget($context, $context_id, $trigger, $event_model);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			'va_id' => array(
				'label' => 'Virtual Attendant',
				'context' => CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT,
			),
			'va_watchers' => array(
				'label' => 'Virtual Attendant Watchers',
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
		$types = $this->getTypes();
		
		$labels['va_link'] = 'Virtual attendant is linked';
		$labels['va_watcher_count'] = 'Virtual attendant watcher count';
		
		$types['va_link'] = null;
		$types['va_watcher_count'] = null;
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);

		return $conditions;
	}
	
	function renderConditionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			case 'va_link':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/condition_link.tpl');
				break;
				
			case 'va_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
			
			default:
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			case 'va_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;

				switch($token) {
					case 'email_link':
						$from_context = CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT;
						@$from_context_id = $dict->va_id;
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
					
				} else {
					$pass = false;
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'va_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');

				switch($token) {
					default:
						$value = count($dict->va_watchers);
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
				'add_watchers' => array('label' =>'Add watchers'),
				'create_comment' => array('label' =>'Create a comment'),
				'create_notification' => array('label' =>'Create a notification'),
				'create_task' => array('label' =>'Create a task'),
				'create_ticket' => array('label' =>'Create a ticket'),
				'send_email' => array('label' => 'Send email'),
				'set_links' => array('label' => 'Set links'),
				'add_api_response' => array('label' => '(Add to API response message)'),
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
			case 'add_api_response':
				$tpl->display('devblocks:cerberusweb.core::events/model/api/action_add_api_response.tpl');
				break;
				
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
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;

			case 'set_links':
				DevblocksEventHelper::renderActionSetLinks($trigger);
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
		@$virtual_attendant_id = $dict->va_id;

		if(empty($virtual_attendant_id))
			return;
		
		switch($token) {
			case 'add_api_response':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();

				@$value = $params['value'];

				@$out = $tpl_builder->build($value, $dict);
				
				$dict->_response .= $out;
				
				return sprintf(">>> Adding to API response message:\n%s\n",
					$out
				);
				break;
			case 'add_watchers':
				return DevblocksEventHelper::simulateActionAddWatchers($params, $dict, 'va_id');
				break;
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'va_id');
				break;
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'va_id');
				break;
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'va_id');
				break;
			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict);
				break;
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
			case 'set_links':
				return DevblocksEventHelper::simulateActionSetLinks($trigger, $params, $dict);
				break;
			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$virtual_attendant_id = $dict->va_id;

		if(empty($virtual_attendant_id))
			return;
		
		switch($token) {
			case 'add_api_response':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();

				@$value = $params['value'];

				@$out = $tpl_builder->build($value, $dict);
				
				$response = rtrim($dict->_response);
				
				if(false !== $out)
					$dict->_response =  (!empty($response) ? $response . PHP_EOL : '') . $out;
				break;
			
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $dict, 'va_id');
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'va_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'va_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'va_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict);
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;
				
			case 'set_links':
				DevblocksEventHelper::runActionSetLinks($trigger, $params, $dict);
				break;
				
			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
};