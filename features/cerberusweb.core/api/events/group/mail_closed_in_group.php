<?php
class Event_MailClosedInGroup extends AbstractEvent_Ticket {
	const ID = 'event.mail.closed.group';
	
	function __construct() {
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