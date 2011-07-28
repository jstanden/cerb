<?php
class Event_AddressMacro extends AbstractEvent_Address {
	const ID = 'event.macro.address';
	
	function __construct() {
		$this->_event_id = self::ID;
	}
	
	static function trigger($trigger_id, $address_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'address_id' => $address_id,
                	'_whisper' => array(
                		'_trigger_id' => array($trigger_id),
                	),
                )
            )
		);
	}
};