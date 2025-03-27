<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class LlmTranscriptAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function validate(_DevblocksValidationService $validation) {
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$llm = DevblocksPlatform::services()->llm();
		$session = \ChPortalHelper::getSession();
		
		$label = $this->_data['label'] ?? null;
		$transcript_id = $this->_data['session_id'] ?? null;
		$limit = 250; // [TODO] Configurable
		
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
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/llm_transcript.tpl');
	}
}