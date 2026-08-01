<?php
namespace Cerb\LLM\MemoryStore;

use DAO_LlmAgentMessage;
use DAO_LlmAgentSession;
use DevblocksPlatform;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use Model_LlmAgentMessage;

class DatabaseHistory extends Extension_DevblocksLlmMemoryStore {
	// The active-branch leaf (single implicit cursor), lazy-loaded from the session then kept in sync
	// as we append so multiple appends within a turn don't re-read. Empty '' = no head yet.
	private ?string $_head_uuid = null;

	private function _head() : string {
		if(null === $this->_head_uuid) {
			$session = DAO_LlmAgentSession::get($this->getSessionId());
			$this->_head_uuid = $session ? strval($session->head_uuid) : '';
		}
		return $this->_head_uuid;
	}

	function getMessages(int $limit=10) : array {
		return array_map(fn($m) => $m->data, $this->getMessageModels($limit));
	}

	function getMessageModels(int $limit=0) : array {
		$head = $this->_head();

		// Legacy/unmigrated session with no cursor: fall back to the seq-linear read.
		if('' === $head)
			return DAO_LlmAgentMessage::getMessagesBySession($this->getSessionId(), last_n: $limit);

		return DAO_LlmAgentMessage::getActivePath($this->getSessionId(), $head, last_n: $limit);
	}

	function appendMessage(array $message, ?string $kind=null, ?array $usage=null, ?string $finish_reason=null) : ?Model_LlmAgentMessage {
		$parent = $this->_head() ?: null;

		$model = DAO_LlmAgentMessage::create($this->getSessionId(), $message, $parent, $kind, $usage, $finish_reason);

		if($model) {
			DAO_LlmAgentSession::setHead($this->getSessionId(), $model->uuid);
			$this->_head_uuid = $model->uuid;
		}

		return $model;
	}
			$this->getSessionId(),
			$message
		);
		
		return true;
	}
}
