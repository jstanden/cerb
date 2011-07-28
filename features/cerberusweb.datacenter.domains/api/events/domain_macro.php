<?php
class Event_DomainMacro extends AbstractEvent_Domain {
	const ID = 'event.macro.domain';
	
	function __construct() {
		$this->_event_id = self::ID;
	}
	
	static function trigger($trigger_id, $domain_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'domain_id' => $domain_id,
                	'_whisper' => array(
                		'_trigger_id' => array($trigger_id),
                	),
                )
            )
		);
	}
};