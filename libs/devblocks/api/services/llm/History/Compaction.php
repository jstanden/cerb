<?php
namespace Cerb\LLM\History;

use DevblocksPlatform;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use Model_LlmAgentMessage;

/**
 * The one context-management strategy (it subsumed the earlier sliding-window and token-budget
 * approaches — those were just this with summarization disabled and a cruder budget unit).
 *
 * `thresholdTokens` (context_ratio × window) decides ONLY WHEN to compact — it is not a ceiling on the
 * post-compact context. `keepTailTokens` (tail_ratio × window) is the verbatim tail, INDEPENDENT of the
 * threshold, and effectively defines the post-compact size (tail + one boundary node).
 *
 * When over budget, mint a single boundary node as the new branch root and re-append the last whole turns
 * verbatim after it: `summarize=true` → the node is an LLM summary of the ENTIRE window (the tail is a
 * redundant verbatim copy — intentional few-shot/continuity steering); `summarize=false` → the node is an
 * empty marker (that IS "truncate" in a WORM append-only tree — we never DROP nodes, only bound the window).
 * If the tail already covers the whole window there's nothing older to fold, so it's a no-op (also the
 * guard against a tail_ratio ≥ context_ratio misconfig thrashing). Kept as duplicated nodes so the fold
 * stays append-only (no re-parenting).
 *
 * A model/provider SWITCH is the same primitive with keep_tail=0 (a pure handoff, no tail), driven
 * directly by `_DevblocksLlmService::switchSessionProvider` rather than through here.
 */
class Compaction {
	// The synthetic user instruction that opens a boundary exchange (the terminal-ancestor node). Its
	// content is what a resuming agent sees the "user" asking for, framing the assistant's summary answer.
	const SUMMARY_PROMPT = 'Summarize our conversation so far — the goals, key decisions, established facts, and any open threads — so we can continue with that context.';
	const TRUNCATE_PROMPT = 'The earlier part of this conversation has been omitted for brevity. Continue from the recent messages below.';
	const TRUNCATE_ACK = 'Understood — I\'ll continue with the recent context.';

	public function __construct(
		private int $thresholdTokens = 100000,
		private int $keepTailTokens = 0,
		private bool $summarize = true
	) {}

	// The verbatim tail = tail_ratio worth of recent WHOLE turns, INDEPENDENT of the threshold. context_ratio
	// only decides WHEN to compact; it is not a ceiling on the post-compact context. When the window is
	// smaller than the tail budget the tail is simply the whole window (→ nothing older to fold; see below).
	private function _tail(array $window) : array {
		return ($this->keepTailTokens > 0) ? $this->_tailWithinBudget($window, $this->keepTailTokens) : [];
	}

	// The span older than the tail — what a compaction would actually fold away. Empty ⇒ the tail already
	// covers the whole window, so compacting is pointless (and this is the guard against a tail_ratio ≥
	// context_ratio misconfig thrashing: we just do nothing).
	private function _olderThanTail(array $window, array $tail) : array {
		$tail_uuids = array_flip(array_map(fn($m) => $m->uuid, $tail));
		return array_values(array_filter($window, fn($m) => !isset($tail_uuids[$m->uuid])));
	}

	function selectMessages(Extension_DevblocksLlmMemoryStore $memory, ?Extension_DevblocksLlmProvider $provider=null) : array {
		$active = $memory->getMessageModels();

		if(!$active)
			return [];

		// Under budget: send the active window verbatim.
		if($this->_sumTokenEst($active) <= $this->thresholdTokens)
			return $this->_toNativeMessages($active);

		$tail = $this->_tail($active);

		// Nothing older than the tail → the tail already covers the whole window; no point compacting.
		if(!$this->_olderThanTail($active, $tail))
			return $this->_toNativeMessages($active);

		// Can't summarize without a provider → don't silently drop content; send verbatim.
		if($this->summarize && !$provider)
			return $this->_toNativeMessages($active);

		// WORM: we never DROP nodes. Mint a boundary EXCHANGE — a synthetic user instruction (the terminal
		// ancestor, kind=summary, where the active-path walk stops) followed by the assistant's answer (the
		// summary, or a brief ack when summarize is off). A single user boundary node isn't resumable: the
		// next real user turn would be two consecutive user messages, which the Anthropic API rejects (400).
		// The pair keeps roles alternating and reads coherently to a resuming agent.
		// Summarize through the session's OWN WARM PREFIX (same system prompt, same tools, same native
		// messages + one instruction turn, persisted nowhere). This fires at ~90% of the context window — the
		// moment the conversation is longest — so flattening it into a fresh cold prompt would re-read the
		// entire thing at full input price, exactly when that costs the most. Falls back to the flatten path
		// (which is the RIGHT shape for a provider switch, where the cache is cold anyway) if this can't run.
		$answer = '';
		// The summarize call is a REAL billed round-trip — usually the most expensive single moment in a
		// session, since it reads the whole window. Capture its usage and persist it on the summary turn, or
		// the one call you most want to see costs nothing in the transcript and can only be found in the
		// provider's own console. It also gives the summary an exact `token_est` (from real output tokens
		// rather than bytes÷4), which is what future window math measures against.
		$summary_usage = null;

		if($this->summarize) {
			$llm = DevblocksPlatform::services()->llm();

			$answer = $llm->summarizeSessionWindow($provider, $memory->getSessionId(), $this->_toNativeMessages($active), '', $summary_usage);

			if('' === $answer) {
				$answer = $llm->summarizeMessages($provider, $active);
				$summary_usage = null;   // the cold fallback reports none
			}
		}

		if($this->summarize && '' === trim($answer))
			$answer = '(No summary was produced.)';

		$memory->appendMessage(['role' => 'user', 'content' => $this->summarize ? self::SUMMARY_PROMPT : self::TRUNCATE_PROMPT], 'summary');
		$memory->appendMessage(['role' => 'assistant', 'content' => $this->summarize ? $answer : self::TRUNCATE_ACK], null, $summary_usage);

		// Re-append the verbatim tail after the exchange, dropping any leading non-user turn so the assistant
		// answer isn't immediately followed by another assistant message (alternation).
		//
		// `finish_reason` rides along so a truncated turn keeps its marker across a compaction; `usage` deliberately
		// does NOT, because the transcript sums usage cumulatively and a duplicated row would double-count. A finish
		// reason is a per-message FACT, not an additive quantity, so copying it is correct where copying usage isn't.
		foreach($this->_userLed($tail) as $model)
			$memory->appendMessage($this->_stripInternalKeys($model->data), finish_reason: $model->finish_reason);

		// The new bounded active path: [user(instruction), assistant(summary)] (+ verbatim tail).
		return $this->_toNativeMessages($memory->getMessageModels());
	}

	/**
	 * Trim the front of a re-appended tail until it is BOTH alternation-safe and pairing-safe. The two
	 * conditions have to be enforced together, because satisfying either one can violate the other:
	 *
	 *   - Alternation: after the boundary exchange (which ends on the assistant summary) the next message must
	 *     be a `user` turn, or the provider rejects the pairing.
	 *   - Pairing: a `tool_result` whose `tool_use` was folded away is orphaned, and Anthropic 400s with
	 *     *"Each `tool_result` block must have a corresponding `tool_use` block in the previous message."*
	 *
	 * An Anthropic `tool_result` message HAS `role: user`, so an alternation-only check passes it happily —
	 * and dropping a leading `assistant`/`tool_use` for alternation is exactly what strands the results that
	 * followed it. `_tailWithinBudget()` already drops leading orphans, but that runs BEFORE this trim, so its
	 * work was being undone. Observed live: a `soft` compaction re-appended a tail starting
	 * `tool_result, tool_result, tool_use`, and the next real turn died on `messages.2.content.0` — terminal
	 * (a 400 is futile to retry), so the interaction surfaced "a queued agent turn failed".
	 *
	 * One loop, both conditions: dropping for either reason re-checks the new leader, so it converges.
	 *
	 * @param Model_LlmAgentMessage[] $models
	 * @return Model_LlmAgentMessage[]
	 */
	private function _userLed(array $models) : array {
		while($models) {
			$model = $models[0];

			// Not a user turn → breaks alternation after the summary.
			if('user' !== ($model->role ?? '')) {
				array_shift($models);
				continue;
			}

			// A user-role tool_result whose tool_use is no longer in front of it → orphaned.
			if('tool_result' === ($model->kind ?? '')) {
				array_shift($models);
				continue;
			}

			break;
		}

		return $models;
	}

	function estimateContextTokens(Extension_DevblocksLlmMemoryStore $memory, ?Extension_DevblocksLlmProvider $provider=null) : int {
		return $this->_sumTokenEst($memory->getMessageModels());
	}

	/**
	 * No-LLM structural preview: whether the active window is over budget and, if so, the whole span
	 * that would be summarized (everything back to the current root) vs. the verbatim tail kept after it.
	 * Powers the dev-page Compact preview without a live summarization call.
	 */
	public function plan(Extension_DevblocksLlmMemoryStore $memory) : array {
		$window = $memory->getMessageModels();
		$tokens = $this->_sumTokenEst($window);
		$mode = $this->summarize ? 'summarize' : 'truncate';

		$base = [
			'compacted' => false,
			'mode' => $mode,
			'tokens' => $tokens,
			'max_tokens' => $this->thresholdTokens,
			'keep_tail_tokens' => $this->keepTailTokens,
			'window' => $window,
		];

		if($tokens <= $this->thresholdTokens)
			return $base + ['reason' => 'under_budget'];

		$tail = $this->_tail($window);
		$older = $this->_olderThanTail($window, $tail);

		// Tail already covers the whole window → nothing to fold; no-op (also the anti-thrash guard).
		if(!$older)
			return $base + ['reason' => 'tail_covers_all'];

		// `older` = what only the boundary node (summary or empty marker) will represent; `tail` = the
		// recent whole turns kept verbatim after it. (In summarize mode the summary covers the whole
		// window; the tail is a redundant verbatim copy — intentional steering.)
		return array_merge($base, ['compacted' => true, 'older' => $older, 'tail' => $tail]);
	}

	/**
	 * Keep the most-recent messages whose cumulative token estimate fits $budget,
	 * always keeping at least the last message, and never leading with an orphaned
	 * tool result (whose tool call was trimmed away). Returns chronological models.
	 *
	 * @param Model_LlmAgentMessage[] $models Chronological.
	 * @return Model_LlmAgentMessage[]
	 */
	private function _tailWithinBudget(array $models, int $budget) : array {
		$kept = [];
		$sum = 0;

		foreach(array_reverse($models) as $model) {
			$cost = max(1, $model->token_est);

			if($sum + $cost > $budget && $kept)
				break;

			$sum += $cost;
			$kept[] = $model;
		}

		return $this->_dropLeadingOrphanToolResults(array_reverse($kept));
	}

	/**
	 * A window must not begin with a tool_result whose tool_use was trimmed off the
	 * front — providers reject the dangling pair. Drop any such leading messages.
	 *
	 * @param Model_LlmAgentMessage[] $models
	 * @return Model_LlmAgentMessage[]
	 */
	private function _dropLeadingOrphanToolResults(array $models) : array {
		while($models && 'tool_result' === ($models[0]->kind ?? '')) {
			array_shift($models);
		}

		return $models;
	}

	/**
	 * Strip internal bookkeeping keys (e.g. the compaction boundary marker) from a
	 * native message so only provider-spec fields reach the API.
	 */
	private function _stripInternalKeys(array $message) : array {
		foreach(array_keys($message) as $key) {
			if(str_starts_with($key, 'cerb_'))
				unset($message[$key]);
		}

		return $message;
	}

	/**
	 * @param Model_LlmAgentMessage[] $models
	 */
	private function _toNativeMessages(array $models) : array {
		return array_values(array_map(fn($m) => $this->_stripInternalKeys($m->data), $models));
	}

	/**
	 * Sum the per-message token estimates for a set of models (each at least 1).
	 *
	 * @param Model_LlmAgentMessage[] $models
	 */
	private function _sumTokenEst(array $models) : int {
		$total = 0;

		foreach($models as $model)
			$total += max(1, $model->token_est);

		return $total;
	}
}
