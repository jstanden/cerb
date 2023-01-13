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

/**
 * Class Event_WebhookReceived
 * @deprecated
 */
class Event_WebhookReceived extends AbstractEvent_Webhook {
	const ID = 'event.webhook.received';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function trigger($trigger_id, $http_request, $variables=[]) : ?Model_DevblocksEvent {
		if(!($behavior = DAO_TriggerEvent::get($trigger_id)))
			return null;
		
		// If the behavior is disabled, ignore it
		if($behavior->is_disabled)
			return null;
		
		// Look up the trigger's owning bot
		if(!($bot = $behavior->getBot()))
			return null;
		
		// If the behavior is disabled, ignore it
		if($bot->is_disabled)
			return null;
		
		$events = DevblocksPlatform::services()->event();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				[
					'bot_id' => $bot->id,
					'http_request' => $http_request,
					'_variables' => $variables,
					'_whisper' => [
						'_trigger_id' => [$trigger_id],
					],
				]
			)
		);
	}
};