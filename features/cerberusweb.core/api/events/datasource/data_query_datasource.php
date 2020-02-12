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

class Event_DataQueryDatasource extends Extension_DevblocksEvent {
	const ID = 'event.data.query.datasource';
	
	static function getByAlias($alias) {
		$datasources = DAO_TriggerEvent::getByEvent(self::ID);
		
		foreach($datasources as $datasource) {
			if(0 == strcasecmp($alias, $datasource->event_params['alias']))
				return $datasource;
		}
		
		return false;
	}
	
	function renderEventParams(Model_TriggerEvent $trigger=null) {
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('trigger', $trigger);
		
		$tpl->display('devblocks:cerberusweb.core::events/datasource/params_data_query_datasource.tpl');
	}
	
	function prepareEventParams(Model_TriggerEvent $behavior=null, &$event_params, &$error) {
		$error = null;
		
		@$alias = DevblocksPlatform::importGPC($event_params['alias']);
		
		if(!$alias) {
			$error = "The alias is required.";
			return false;
		}
		
		if($alias != DevblocksPlatform::strAlphaNum($alias, '_')) {
			$error = "The alias must only contain a-z, 0-9, and underscore.";
			return false;
		}
		
		if(!(false == ($found = self::getByAlias($alias))
			|| $found->id == $behavior->id)) {
				$error = "The alias must be unique.";
				return false;
			}
		
		return true;
	}
	
	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger) {
		$actions = [];
		
		return new Model_DevblocksEvent(
			self::ID,
			[
				'actions' => &$actions,
			]
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		$labels = [];
		$values = [];
		
		$labels['query_format'] = 'Query format';
		$values['query_format'] = @$event_model->params['query_format'];
		
		/**
		 * Behavior
		 */
		
		$merge_labels = [];
		$merge_values = [];
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
		
		if($event_model) {
			// Actions
			$values['_actions'] =& $event_model->params['actions'];
		} else {
			$values['_actions'] = [];
		}
		
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
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
		
		switch($as_token) {
		}
		
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
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
		$actions =
			[
				'return_data' => [
					'label' => 'Return data',
					'notes' => '',
					'params' => [
						'data' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The [data query](/docs/data-queries/) results in the given `format:`',
						],
					],
				],
			]
		;
		
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
		
		switch($token) {
			case 'return_data':
				if(is_null($params)) {
					$params['data'] = "{% set json = {\n".
						"\t\"results\": [\n".
						"\t]\n".
						"} %}\n".
						"{{json|json_encode|json_pretty}}"
					;
				}
				
				$tpl->assign('params', $params);
				$tpl->display('devblocks:cerberusweb.core::events/datasource/action_return_data.tpl');
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$out = null;
		
		switch($token) {
			case 'return_data':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$data = $params['data'] ?: '';
				$output = $tpl_builder->build($data, $dict);
				
				$out = sprintf(">>> Returning data:\n".
					"%s\n",
					$output
				);
				break;
		}
		
		return $out;
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'return_data':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$actions =& $dict->_actions;
				
				if(!is_array($actions))
					$actions = [];
				
				@$data = $params['data'] ?: '';
				$output = $tpl_builder->build($data, $dict);
				
				$actions[] = array(
					'_action' => 'return_data',
					'_trigger_id' => $trigger->id,
					'data' => $output,
				);
				break;
		}
	}
};