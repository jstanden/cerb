<?php
class EventListener_LegacyBehaviors extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'context.delete':
				$this->_handleContextDelete($event);
				break;
				
			case 'cron.heartbeat':
				$this->_handleCronHeartbeat();
				break;
				
			case 'cron.maint':
				$this->_handleCronMaint();
				break;
		}
	}
	
	private function _handleContextDelete(Model_DevblocksEvent $event) : void {
		$context = $event->params['context'] ?? null;
		$context_ids = $event->params['context_ids'] ?? null;
		
		if(empty($context))
			return;
		
		if(!is_array($context_ids) || empty($context_ids))
			return;
		
		DAO_ContextScheduledBehavior::deleteByContext($context, $context_ids);
		DAO_Bot::deleteByOwner($context, $context_ids);
	}
	
	private function _handleCronHeartbeat() : void {
		DAO_BotDatastore::maint();
		DAO_BotInteractionProactive::maint();
	}
	
	private function _handleCronMaint() : void {
		DAO_BotSession::maint();
	}
};