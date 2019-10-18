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

abstract class AbstractEvent_JiraIssue extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 *
	 * @param integer $context_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context_id=null, $comment_id=null) {
		// If this is the 'new comment on jira issue' event, simulate a comment
		if(empty($comment_id) && get_class($this) == 'Event_JiraIssueCommented') {
			// [TODO] This should get a comment where the context_id is related
			$comment_id = DAO_JiraIssue::randomComment();
		}
		
		if(empty($context_id)) {
			// If we have a comment, use its context_id
			if(!empty($comment_id)) {
				if(false !== ($comment = DAO_JiraIssue::getComment($comment_id))) {
					if(false !== ($issue = DAO_JiraIssue::get($comment['issue_id'])))
						$context_id = $issue->id;
				}
			}
			
			// Otherwise, pick a random issue
			if(empty($context_id)) {
				list($results) = DAO_JiraIssue::search(
					array(),
					array(),
					25,
					0,
					SearchFields_JiraIssue::ID,
					false,
					false
				);
				
				shuffle($results);
				
				$result = array_shift($results);
				
				$context_id = $result[SearchFields_JiraIssue::ID];
			}
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'context_id' => $context_id,
				'comment_id' => $comment_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		$labels = array();
		$values = array();
		
		/**
		 * Behavior
		 */
		
		$merge_labels = $merge_values = [];
		
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
		
		// We can accept a model object or a context_id
		@$model = $event_model->params['context_model'] ?: $event_model->params['context_id'];
		
		/**
		 * Issue
		 */
		
		$merge_labels = $merge_values = [];
		
		CerberusContexts::getContext(Context_JiraIssue::ID, $model, $merge_labels, $merge_values, null, true);
		
			// Merge
			CerberusContexts::merge(
				'issue_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		
		/**
		 * Comment
		 */
		
		@$comment_id = $event_model->params['comment_id'];
		
		if(get_class($this) == 'Event_JiraIssueCommented') {
			$merge_token_labels = array(
				'author' => 'Comment author',
				'body' => 'Comment body',
				'created' => 'Comment created',
			);
			
			$merge_token_values = array(
				//'_context' => null,
				'_labels' => $merge_token_labels,
				'_types' => array(
					'id' => Model_CustomField::TYPE_NUMBER,
					'author' => Model_CustomField::TYPE_SINGLE_LINE,
					'body' => Model_CustomField::TYPE_MULTI_LINE,
					'created' => Model_CustomField::TYPE_DATE,
				),
			);
			
			if(false !== ($comment = DAO_JiraIssue::getComment($comment_id))) {
				$merge_token_values['id'] = $comment_id;
				$merge_token_values['author'] = $comment['jira_author'];
				$merge_token_values['body'] = $comment['body'];
				$merge_token_values['created'] = $comment['created'];
			}
			
			CerberusContexts::merge(
				'issue_comment_',
				'',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);
		}
			
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function renderSimulatorTarget($trigger, $event_model) {
		$context = Context_JiraIssue::ID;
		$context_id = $event_model->params['context_id'];
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
			'issue_id' => array(
				'label' => 'Issue',
				'context' => Context_JiraIssue::ID,
			),
			'issue_project_id' => array(
				'label' => 'Issue project',
				'context' => Context_JiraProject::ID,
			),
			'issue_project_watchers' => array(
				'label' => 'Issue project watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'issue_watchers' => array(
				'label' => 'Issue watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
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
		
		$labels['issue_link'] = 'Jira issue is linked';
		$labels['issue_project_link'] = 'Jira issue project is linked';
		
		$labels['issue_project_watcher_count'] = 'Jira issue project watcher count';
		$labels['issue_watcher_count'] = 'Jira issue watcher count';
		
		$types['issue_link'] = null;
		$types['issue_project_link'] = null;
		
		$types['issue_project_watcher_count'] = null;
		$types['issue_watcher_count'] = null;

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
			case 'issue_link':
			case 'issue_project_link':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/condition_link.tpl');
				break;
				
			case 'issue_watcher_count':
			case 'issue_project_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			case 'issue_link':
			case 'issue_project_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;

				switch($as_token) {
					case 'issue_link':
						$from_context = Context_JiraIssue::ID;
						@$from_context_id = $dict->issue_id;
						break;
					case 'issue_project_link':
						$from_context = Context_JiraProject::ID;
						@$from_context_id = $dict->issue_project_id;
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
				
			case 'issue_watcher_count':
			case 'issue_project_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');

				switch($as_token) {
					case 'issue_project_watcher_count':
						$value = count($dict->issue_project_watchers);
						break;
					default:
						$value = count($dict->issue_watchers);
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
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			[]
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels($trigger))
			;
			
		return $actions;
	}
	
	function getActionDefaultOn() {
		return 'issue_id';
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			default:
				$matches = [];
				
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token, $matches)) {
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
		@$issue_id = $dict->issue_id;

		if(empty($issue_id))
			return;
		
		switch($token) {
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$issue_id = $dict->issue_id;

		if(empty($issue_id))
			return;
		
		switch($token) {
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
};