<?php
namespace Cerb\LLM\MemoryStore;

use Extension_DevblocksLlmMemoryStore;

class NoHistory extends Extension_DevblocksLlmMemoryStore {
	public function __construct() {
		return parent::__construct('');
	}
	
	function getMessages(int $limit=10) : array {
		return [];
	}

	function appendMessage(array $message, ?string $kind=null, ?array $usage=null, ?string $finish_reason=null) : ?\Model_LlmAgentMessage {
		return null;
	}
}
