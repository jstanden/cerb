<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class Event_WebhookReceived extends AbstractEvent_Webhook {
	const ID = 'event.webhook.received';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function trigger($trigger_id, $http_request, $variables=array()) {
		if(false == ($behavior = DAO_TriggerEvent::get($trigger_id)))
			return;
		
		// If the behavior is disabled, ignore it
		if($behavior->is_disabled)
			return;
		
		// Look up the trigger's owning bot
		if(false == ($bot = $behavior->getBot()))
			return;
		
		// If the behavior is disabled, ignore it
		if($bot->is_disabled)
			return;
		
		$events = DevblocksPlatform::services()->event();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'bot_id' => $bot->id,
					'http_request' => $http_request,
					'_variables' => $variables,
					'_whisper' => array(
						'_trigger_id' => array($trigger_id),
					),
				)
			)
		);
	}
};