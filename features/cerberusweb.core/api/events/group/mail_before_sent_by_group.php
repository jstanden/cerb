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

class Event_MailBeforeSentByGroup extends AbstractEvent_MailBeforeSent {
	const ID = 'event.mail.sent.group';
	
	static function trigger(&$properties, $message_id=null, $ticket_id=null, $group_id=null) {
		$events = DevblocksPlatform::getEventService();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'properties' => &$properties,
					'message_id' => $message_id,
					'ticket_id' => $ticket_id,
					'group_id' => $group_id,
					'_whisper' => array(
						CerberusContexts::CONTEXT_GROUP => array($group_id),
					),
				)
			)
		);
	}
};