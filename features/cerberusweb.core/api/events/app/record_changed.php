<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class Event_RecordChanged extends AbstractEvent_Record {
	const ID = 'event.record.changed';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function trigger($context, $new_model, $old_model, $variables=array()) {
		$events = DevblocksPlatform::getEventService();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'context' => $context,
					'new_model' => $new_model,
					'old_model' => $old_model,
					'_variables' => $variables,
					'_whisper' => array(
						'event_params' => array(
							'context' => $context,
						),
					),
				)
			)
		);
	}
};