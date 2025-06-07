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
	
	function appendMessage(array $message) : bool {
		return true;
	}
}
