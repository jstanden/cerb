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
		$session = $this->_getSession();

		$label = $this->_data['label'] ?? null;
		$transcript_id = $this->_data['session_id'] ?? null;
		$limit = 250; // [TODO] Configurable

		// No session_id → render nothing at all (no wrapper, no empty transcript). In the design-time builder
		// preview (no real state) show a labeled placeholder so the field stays visible/selectable.
		if(!$transcript_id) {
			if($this->_isBuilderPreview())
				$this->_renderSimulatedPlaceholder($label, 'conversation', 'Transcript preview');
			return;
		}

		$transcript = \DAO_LlmAgentSession::get($transcript_id);
		$tpl->assign('transcript', $transcript);

		// Tolerate a not-yet-created (`{{uuid()}}`) or unprimed session — no provider means no messages;
		// degrade to an empty transcript instead of fataling on getProvider(null).
		$messages = [];

		if($transcript && $transcript->provider && ($llm_provider = $llm->getProvider($transcript->provider, [], validate: false))) {
			if(!($raw_messages = \DAO_LlmAgentMessage::getMessagesBySession($transcript_id, $limit)))
				$raw_messages = [];

			// Convert the messages into a neutral format using providers
			$messages = array_map(fn($message) => $llm_provider->convertToGenericMessage($message->data, $message->uuid), $raw_messages);
		}

		// Nothing to show yet (new/empty session, or no messages) → render no wrapper/transcript at all. In the
		// design-time builder preview show a placeholder; the simulator form-fill mirrors the runtime (blank).
		if(!$messages) {
			if($this->_isBuilderPreview())
				$this->_renderSimulatedPlaceholder($label, 'conversation', 'Transcript preview');
			return;
		}

		$tpl->assign('transcript_messages', $messages);

		// Display labels come from the session's persisted `tools:` block, not this component.
		$tpl->assign('tool_map', $transcript->getToolMap());

		$tpl->assign('continuation_token', $continuation->token);
		$tpl->assign('session', $session);
		$tpl->assign('label', $label);
		$tpl->assign('var', $this->_key);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/llm_transcript.tpl');
	}
}