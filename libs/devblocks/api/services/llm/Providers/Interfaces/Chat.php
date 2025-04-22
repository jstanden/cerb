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
}