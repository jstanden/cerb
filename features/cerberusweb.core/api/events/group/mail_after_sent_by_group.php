<?php
class Event_MailAfterSentByGroup extends AbstractEvent_Message {
	const ID = 'event.mail.after.sent.group';
	
	function __construct() {
		$this->_event_id = self::ID;
	}
	
	static function trigger($message_id, $group_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'message_id' => $message_id,
                    'group_id' => $group_id,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_GROUP => array($group_id),
                	),
                )
            )
		);
	}
};