<?php
namespace Cerb\LLM\Providers\Interfaces;

use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use Extension_DevblocksLlmMemoryStore;

interface Chat {
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse;
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory) : void;
	
	/**
	 * @param array $message
	 * @param ?string $message_uuid
	 * @return DevblocksLlmChatResponse
	 */
	function convertToGenericMessage(array $message, ?string $message_uuid=null) : DevblocksLlmChatResponse;

	/**
	 * Reverse of convertToGenericMessage(): render a neutral message back into this
	 * provider's native wire format for cross-provider replay. Returns a LIST of native
	 * messages (a neutral tool-result can fan out to several). Satisfied by the default
	 * on Extension_DevblocksLlmProvider unless a provider overrides it.
	 *
	 * @return array List of provider-native message arrays.
	 */
	function toNativeMessage(DevblocksLlmChatResponse $message) : array;

	// The known chat model ids (KATA `model:` value autocompletion; not authoritative — a model is only
	// usable if auth is set up).
	function getChatModels() : array;

	// KATA autocomplete contribution for this provider's chat params block, consumed by
	// _DevblocksLlmService::getKataProviderAutocomplete(): ['keys' => block keys, 'values' => sub-path values].
	function getChatKataAutocomplete() : array;
}