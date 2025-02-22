<?php
namespace Cerb\LLM\MemoryStore;

use DAO_LlmAgentMessage;
use Extension_DevblocksLlmMemoryStore;

class DatabaseHistory extends Extension_DevblocksLlmMemoryStore {
	function getMessages(int $limit=10) : array {
		$memory_messages = \DAO_LlmAgentMessage::getMessagesBySession($this->getSessionId(), last_n: $limit);
		return array_map(fn($message) => $message->data, $memory_messages);
	}
	
	function appendMessage(array $message) : bool {
		DAO_LlmAgentMessage::create(
			$this->getSessionId(),
			$message
		);
		
		return true;
	}
}
