<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class Event_MailDuringUiReplyByWorker extends AbstractEvent_Message {
	const ID = 'event.mail.reply.during.ui.worker';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function trigger($trigger_id, $message_id, &$actions, $variables=array()) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'message_id' => $message_id,
					'_caller_actions' => &$actions,
					'_variables' => $variables,
					'_whisper' => array(
						'_trigger_id' => array($trigger_id),
					),
				)
			)
		);
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
				$tpl->display('devblocks:cerberusweb.core::events/ui/reply/action_exec_jquery.tpl');
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$ticket_id = $dict->ticket_id;
		@$message_id = $dict->ticket_latest_message_id;

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'exec_jquery':
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$message_id = $dict->id;
		@$ticket_id = $dict->ticket_id;

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'exec_jquery':
				// Return the parsed script to the caller
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$dict->_caller_actions['jquery_scripts'][] = $tpl_builder->build($params['jquery_script'], $dict);
				break;
		}
	}
};