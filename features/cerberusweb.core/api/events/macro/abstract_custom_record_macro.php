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

class Event_AbstractCustomRecordMacro extends AbstractEvent_AbstractCustomRecord {
	const ID = 'event.macro.abstract_custom_record';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = static::ID;
	}
	
	static function trigger($trigger_id, $context_id, $variables=[]) {
		$events = DevblocksPlatform::services()->event();
		return $events->trigger(
			new Model_DevblocksEvent(
				static::ID,
				[
					'context_id' => $context_id,
					'_variables' => $variables,
					'_whisper' => [
						'_trigger_id' => [$trigger_id],
					],
				]
			)
		);
	}
	
	function renderEventParams(Model_TriggerEvent $trigger=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('trigger', $trigger);
		$tpl->display('devblocks:cerberusweb.core::events/record/params_macro_default.tpl');
	}
};