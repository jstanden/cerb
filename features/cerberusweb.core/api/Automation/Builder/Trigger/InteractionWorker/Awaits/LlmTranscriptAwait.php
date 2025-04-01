<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class LlmTranscriptAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		/*
		return match ($action) {
			'rating' => $this->_promptAction_rating($prompt_key, $continuation),
			default => false,
		};
		*/
		return false;
	}

	function validate(_DevblocksValidationService $validation) {
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		$llm = DevblocksPlatform::services()->llm();
		$session = \ChPortalHelper::getSession();
		
		$label = $this->_data['label'] ?? null;
		$transcript_id = $this->_data['session_id'] ?? null;
		$limit = 250;
		
		$transcript = \DAO_LlmAgentSession::get($transcript_id);
		$tpl->assign('transcript', $transcript);
		
		if(!($llm_provider = $llm->getProvider($transcript->provider, [], validate: false)))
			return;
		
		if(!($messages = \DAO_LlmAgentMessage::getMessagesBySession($transcript_id, $limit)))
			$messages = [];
		
		// Convert the messages into a neutral format using providers
		$messages = array_map(fn($message) => $llm_provider->convertToGenericMessage($message->data, $message->uuid), $messages);
		$tpl->assign('transcript_messages', $messages);
		
		$tpl->assign('continuation_token', $continuation->token);
		$tpl->assign('session', $session);
		$tpl->assign('label', $label);
		$tpl->assign('var', $this->_key);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/llm_transcript.tpl');
	}
	
	/*
	private function _promptAction_rating(string $prompt_key, Model_AutomationContinuation $continuation) {
		$message_uuid = DevblocksPlatform::importGPC($_POST['message_uuid'] ?? null);
		$rating = DevblocksPlatform::importGPC($_POST['rating'] ?? null);
		
		if(!$message_uuid || !($message = \DAO_LlmAgentMessage::get($message_uuid)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		\DAO_LlmAgentMessage::setRating($message->uuid, $rating);
	}
	*/
}