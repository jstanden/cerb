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

class Event_UiWorklistRenderByWorker extends Extension_DevblocksEvent {
	const ID = 'event.ui.worklist.render.worker';

	protected $_event_id = null; // override
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function triggerForWorker($worker, $context, $view_id, &$actions, &$triggers) {
		if(empty($worker) || !($worker instanceof Model_Worker))
			return;
		
		$macros = DAO_TriggerEvent::getByVirtualAttendantOwners(
			array(
				array(CerberusContexts::CONTEXT_APPLICATION, null, null),
				array(CerberusContexts::CONTEXT_WORKER, $worker->id, null),
			),
			Event_UiWorklistRenderByWorker::ID
		);
		
		if(is_array($macros))
		foreach($macros as $macro) {
			$new_actions = array();;
			Event_UiWorklistRenderByWorker::trigger($macro->id, $context, $view_id, $new_actions);
			
			if(!empty($new_actions)) {
				foreach($new_actions as $idx => $action) {
					if(isset($actions[$idx]))
						$actions[$idx] = array_merge($actions[$idx], $action);
					else
						$actions[$idx] = $action;
				}
				$triggers[] = $macro;
			}
		}
	}
	
	static function trigger($trigger_id, $context, $view_id, &$actions) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'_caller_actions' => &$actions,
					'context' => $context,
					'view_id' => $view_id,
					'_whisper' => array(
						'_trigger_id' => array($trigger_id),
					),
				)
			)
		);
	}
	
	/**
	 *
	 * @param string $context
	 * @param string $view_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($context=null, $view_id=null) {
		// [TODO] Set defaults
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'context' => $context ?: 'cerberusweb.contexts.ticket',
				'view_id' => $view_id ?: 'search_cerberusweb_contexts_ticket',
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		@$context = $event_model->params['context'];
		@$view_id = $event_model->params['view_id'];
		
		$labels = array();
		$values = array();
		
		$labels['context'] = 'Worklist Type';
		$values['context'] = $context;
		
		$labels['view_id'] = 'Worklist ID';
		$values['view_id'] = $view_id;
		
		/**
		 * Caller actions
		 */
		
		if(isset($event_model->params['_caller_actions'])) {
			$values['_caller_actions'] =& $event_model->params['_caller_actions'];
		}
			
		/**
		 * Return
		 */
			
		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getValuesContexts($trigger) {
		$vars = parent::getValuesContexts($trigger);
		asort($vars);
		return $vars;
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		
		$labels['context'] = 'Worklist Type';
		$labels['view_id'] = 'Worklist ID';
		
		$types = array(
			'context' => null,
			'view_id' => Model_CustomField::TYPE_SINGLE_LINE,
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
			case 'context':
				$contexts = Extension_DevblocksContext::getAll(false, array('workspace'));
				$options = array();
				
				foreach($contexts as $context_mft) {
					$options[$context_mft->id] = $context_mft->name;
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
			case 'context':
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
		}
		
		return $pass;
	}
	
	function getActionExtensions() {
		$actions =
			array(
				'exec_jquery' => array('label' =>'Execute jQuery script'),
			)
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
			case 'exec_jquery':
				$default_jquery =
					"var view_id = '{{view_id}}';\n".
					"var \$view = \$('#view' + view_id);\n".
					"var \$worklist = \$view.find('TABLE.worklist');\n".
					"var \$worklist_rows = \$view.find('TABLE.worklistBody');\n".
					"var \$worklist_actions = \$('#' + view_id + '_actions');\n".
					"\n".
					"// Enter your jQuery script here\n".
					"";
				
				$tpl->assign('default_jquery', $default_jquery);
				$tpl->display('devblocks:cerberusweb.core::events/ui/reply/action_exec_jquery.tpl');
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'exec_jquery':
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'exec_jquery':
				// Return the parsed script to the caller
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$dict->_caller_actions['jquery_scripts'][] = $tpl_builder->build($params['jquery_script'], $dict);
				break;
		}
	}
};