<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
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

class Event_MailClosedInGroup extends AbstractEvent_Ticket {
	const ID = 'event.mail.closed.group';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function trigger($ticket_id, $group_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'ticket_id' => $ticket_id,
					'_whisper' => array(
						CerberusContexts::CONTEXT_GROUP => array($group_id),
					),
				)
			)
		);
	}
};