<?php
namespace Cerb\LLM\MemoryStore;

use Cerb\LLM\Providers\Interfaces\ChatStreaming;
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

	// The row a streamed turn is currently accumulating into, or null when no stream is open. Its presence is
	// what makes appendMessage() finalize instead of insert.
	private ?string $_streaming_uuid = null;

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
		// A streamed turn already has a row — the one that's been accumulating deltas — so the provider's
		// closing append CLOSES it rather than inserting a second copy of the same turn.
		//
		// Routing it here rather than teaching the provider about streaming is what keeps `chatCompletion()`
		// identical on both paths: it appends its assistant turn exactly as it always has, and the store
		// decides whether that means INSERT or finalize. A provider branching on transport would be the seam
		// where the two paths start to drift.
		if(null !== $this->_streaming_uuid) {
			$uuid = $this->_streaming_uuid;
			$this->_streaming_uuid = null;

			DAO_LlmAgentMessage::finalizeStreaming($uuid, $message, $usage, $finish_reason);

			return DAO_LlmAgentMessage::get($uuid);
		}

		$parent = $this->_head() ?: null;

		$model = DAO_LlmAgentMessage::create($this->getSessionId(), $message, $parent, $kind, $usage, $finish_reason);

		if($model) {
			DAO_LlmAgentSession::setHead($this->getSessionId(), $model->uuid);
			$this->_head_uuid = $model->uuid;
		}

		return $model;
	}

	function beginStreamingMessage() : ?string {
		$parent = $this->_head() ?: null;

		// Opened EMPTY on purpose — there is no content yet, and waiting for the first delta would give up the
		// thing this buys us: a stable uuid the client can address from the very first render.
		$model = DAO_LlmAgentMessage::create(
			$this->getSessionId(),
			['role' => 'assistant', 'content' => []],
			$parent,
			// Classifying an empty message would always say 'text'; finalizeStreaming() re-classifies once the
			// turn's actual shape (text vs tool_use) is known.
			'text',
			null,
			null,
			is_streaming: true
		);

		if(!$model)
			return null;

		DAO_LlmAgentSession::setHead($this->getSessionId(), $model->uuid);
		$this->_head_uuid = $model->uuid;
		$this->_streaming_uuid = $model->uuid;

		return $model->uuid;
	}

	function getOpenStreamingMessage() : ?string {
		return $this->_streaming_uuid;
	}

	function updateStreamingMessage(string $uuid, array $message, ?array $usage=null) : void {
		DAO_LlmAgentMessage::updateStreaming($uuid, $message, $usage);
	}

	function finalizeStreamingMessage(string $uuid, array $message, ?array $usage=null, ?string $finish_reason=null) : void {
		if($uuid === $this->_streaming_uuid)
			$this->_streaming_uuid = null;

		DAO_LlmAgentMessage::finalizeStreaming($uuid, $message, $usage, $finish_reason);
	}

	function discardStreamingMessage(string $uuid) : void {
		if($uuid === $this->_streaming_uuid)
			$this->_streaming_uuid = null;

		if(!($model = DAO_LlmAgentMessage::get($uuid)))
			return;

		DAO_LlmAgentMessage::deleteStreaming($uuid);

		// Hand the cursor back to the discarded row's parent, or clear it entirely if it had none. Leaving the
		// head pointing at a deleted row would strand the session: getActivePath() anchors on the head, so it
		// would return NOTHING and the whole conversation would read as empty.
		DAO_LlmAgentSession::setHead($this->getSessionId(), $model->parent_uuid ?: null);
		$this->_head_uuid = strval($model->parent_uuid);
	}

	function resolveDanglingStream() : void {
		if('' === ($head = $this->_head()))
			return;

		if(!($model = DAO_LlmAgentMessage::get($head)) || !$model->is_streaming)
			return;

		$data = $model->data;

		// Sanitize with the SESSION's provider — this process never saw the stream, so the only thing that can
		// judge these blocks is the structural rules for the grammar they're written in. Skipping this would
		// leave an unsignatured thinking block or an argument-less tool call in the history, and the next
		// request would be rejected outright: one lost turn becomes a permanently stranded session.
		if(($session = DAO_LlmAgentSession::get($this->getSessionId())) && $session->provider) {
			$provider = DevblocksPlatform::services()->llm()->getProvider($session->provider, [], false);

			if($provider instanceof ChatStreaming)
				$data = $provider->sanitizePartialContent($data);
		}

		// Nothing survived — drop it rather than leave an empty assistant head for the next turn to trip over.
		if(!Extension_DevblocksLlmProvider::hasReplayableContent($data)) {
			$this->discardStreamingMessage($head);
			return;
		}

		// Real content we already paid for: keep it, and record WHY it stops there so a reader (and the next
		// turn's tool handling) knows this turn was cut short rather than finished.
		DAO_LlmAgentMessage::finalizeStreaming($head, $data, $model->usage, Extension_DevblocksLlmProvider::FINISH_REASON_INTERRUPTED);
	}
}
