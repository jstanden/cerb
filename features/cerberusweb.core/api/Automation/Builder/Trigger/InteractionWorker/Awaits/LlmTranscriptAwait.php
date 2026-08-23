<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class LlmTranscriptAwait extends AbstractAwait {
	// The session row for this render. Loaded once and reused: the poll's caller already fetched it for
	// its ownership check, and _prepare() would otherwise fetch the identical row a second time — each
	// one a SELECT with three LEFT JOINs onto llm_agent_session_property, once per second per viewer.
	private ?\Model_LlmAgentSession $_session = null;
	private bool $_session_loaded = false;

	/** Hand over an already-loaded session so this render doesn't re-query it. */
	function setSession(?\Model_LlmAgentSession $session) : void {
		$this->_session = $session;
		$this->_session_loaded = true;
	}

	private function _getSession(string $session_id) : ?\Model_LlmAgentSession {
		if(!$this->_session_loaded) {
			$this->_session = \DAO_LlmAgentSession::get($session_id);
			$this->_session_loaded = true;
		}

		// Guard the memo: a caller could hand us one session and then prepare a different transcript.
		if($this->_session && strval($this->_session->uuid ?? '') !== $session_id)
			return \DAO_LlmAgentSession::get($session_id);

		return $this->_session;
	}

	/**
	 * Is a composer being rendered alongside this transcript? If so the interaction is parked waiting for the
	 * reader, which means no turn can be running no matter what the tip of the transcript looks like.
	 *
	 * Cheap and exact: a live turn suspends on `await:queue:`, whose `__return` carries no form at all, and an
	 * `on_tool:` render replaces the composer -- so the element is present only in the states where the agent
	 * has already handed control back.
	 */
	private function _hasComposer(Model_AutomationContinuation $continuation) : bool {
		$elements = $continuation->state_data['dict']['__return']['form']['elements'] ?? [];

		if(!is_array($elements))
			return false;

		foreach(array_keys($elements) as $element_key) {
			if(str_starts_with(strval($element_key), 'agentPrompt/'))
				return true;
		}

		return false;
	}

	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return match ($action) {
			'echoTurn' => $this->_promptAction_echoTurn($continuation),
			'pollTurn' => $this->_promptAction_pollTurn($continuation),
			default => false,
		};
	}

	function validate(_DevblocksValidationService $validation) {
		$validation->addField('view', 'view:')
			->string()
			->setPossibleValues(['toggle', 'markdown', 'text'])
		;

		$validation->addField('layout', 'layout:')
			->string()
			->setPossibleValues(['conversation', 'interleaved'])
		;

		foreach(['thinking', 'tools'] as $field) {
			$validation->addField($field, $field . ':')
				->string()
				->setPossibleValues(['raw', 'summary', 'hide'])
			;
		}

		$validation->addField('expand', 'expand:')
			->string()
			->setPossibleValues(['latest', 'all', 'none'])
		;

		$validation->addField('tokens', 'tokens:')
			->boolean()
		;

		// `agent:` -- who the agent turns are ATTRIBUTED to on screen. `stringOrArray()` because the two
		// authored forms arrive as different PHP types, and `string()` would reject the object outright:
		//
		//   agent: '@cerb'          a reference, resolved to an AI worker (name + avatar come from the record)
		//   agent: {name:, icon:, color:}   a literal identity, for a chat with no worker behind it
		//
		// RENDERING ONLY, either way. Nothing is written to the session or its messages, so this can't drift
		// from a persisted identity and an edit takes effect on the next paint. `llm.agent:inputs:agent:` is
		// still what gives a chat a real identity -- attribution, memory, credentials.
		$validation->addField('agent', 'agent:')
			->stringOrArray()
		;
	}
	
	/**
	 * `agent:` -> the identity to paint on agent turns: `{name, icon, color, image, seed}`, or `[]` for none.
	 *
	 * Two authored forms, told apart by TYPE rather than by a discriminator key, because that is what an
	 * author would write without being taught a schema:
	 *
	 *   agent: '@cerb'                  a reference -- name and avatar come from the AI worker record
	 *   agent: {name:, icon:, color:}   a literal -- for a chat with no worker behind it
	 *
	 * The reference form goes through `LlmAgentNode::resolveAgentWorker()`, the SAME resolver
	 * `llm.agent:inputs:agent:` uses, so the two accept identical forms (`@mention`, a bare handle, a worker
	 * id, `cerb:worker:<id|mention>`) and a human worker is refused in both. There is deliberately no
	 * `cerb:agent:` scheme: an agent IS a worker (`worker.is_ai`), and a second URI naming the same thing
	 * would resolve nowhere else in the system.
	 *
	 * An unresolvable reference degrades to the model-icon default rather than throwing -- a display override
	 * must never be able to break a transcript render -- but it LOGS, because a silent fallback here is
	 * indistinguishable from a typo in a handle.
	 *
	 * @return array{name?:string, icon?:string, color?:string, image?:string, seed?:string}
	 */
	/**
	 * The CerbUI tag palette (`cerb-ui/_palette.scss` `$cerb-tag-hues`). Kept in sync by hand -- six names that
	 * have not moved in the palette's life, against a build step to extract them.
	 */
	private const TAG_HUES = ['red', 'blue', 'green', 'gray', 'orange', 'purple'];

	/**
	 * A hue NAME resolves to its theme token; anything else passes through as a raw CSS color.
	 *
	 * The six names are also CSS keywords, and we deliberately shadow them: an author who writes `blue` in a
	 * Cerb UI means Cerb's blue -- the designed, theme-invariant one that every pill, meter, and tag already
	 * uses -- not the browser's `#0000ff`. Anyone who genuinely wants the keyword can write `#00f`.
	 */
	private static function _resolveColor(string $color) : string {
		if('' === ($color = trim($color)))
			return '';

		$hue = DevblocksPlatform::strLower($color);

		return in_array($hue, self::TAG_HUES, true)
			? sprintf('var(--cerb-color-tag-%s)', $hue)
			: $color;
	}

	/**
	 * Where a resolved `agent:` identity is remembered for the renders that have no config to read it from.
	 *
	 * Lives in the continuation's DICT, keyed by session id, because the dict is the one thing that survives
	 * every yield: it is reloaded into the automation's dictionary on resume and re-emitted verbatim on the
	 * next `getDictionary()`. Keyed by session (not by element) because identity belongs to the CHAT -- every
	 * transcript element showing the same session should show the same agent, including ones this component
	 * never sees authored.
	 */
	private const STATE_KEY = '__llm_transcript_agent';

	private function _storedIdentity(Model_AutomationContinuation $continuation, string $session_id) : array {
		$stored = $continuation->state_data['dict'][self::STATE_KEY][$session_id] ?? null;

		return is_array($stored) ? $stored : [];
	}

	/**
	 * Stamp the resolved identity onto the continuation, once, the first time a transcript renders WITH its
	 * authored `agent:`.
	 *
	 * This is what makes every other render inherit it. `LlmAgentNode::_startUiCommand()` synthesizes its own
	 * `llmTranscript/` element for the between-tool-calls repaint carrying only `session_id`/`thinking`/`tools`,
	 * and the async-turn poll builds its `$data` by hand -- neither has the author's block, and both used to
	 * silently fall back to the model's mark mid-turn. They read this instead.
	 *
	 * Write-once and diff-guarded: an unchanged identity costs no query, and an author who edits `agent:`
	 * re-stamps on the next render rather than being frozen.
	 */
	private function _persistIdentity(Model_AutomationContinuation $continuation, string $session_id, array $identity) : void {
		if(!$identity || '' === $session_id)
			return;

		if($this->_storedIdentity($continuation, $session_id) === $identity)
			return;

		$continuation->state_data['dict'][self::STATE_KEY][$session_id] = $identity;

		\DAO_AutomationContinuation::update($continuation->token, [
			\DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
		]);
	}

	private function _agentIdentity() : array {
		$agent = $this->_data['agent'] ?? null;

		// The literal form. `name` alone is enough; icon and color are optional accents.
		//
		// This branch is IDEMPOTENT -- feed it this method's own output and you get that output back. That is
		// what makes a STAMPED identity (see _persistIdentity) interchangeable with an authored one: the two
		// shapes are the same, so nothing downstream has to know which it got. `image` and `seed` are outputs
		// of the reference form; an author writes name/icon/color and lets the seed derive.
		if(is_array($agent)) {
			$name = trim(strval($agent['name'] ?? ''));
			$icon = trim(strval($agent['icon'] ?? ''));
			$color = self::_resolveColor(strval($agent['color'] ?? ''));
			$image = trim(strval($agent['image'] ?? ''));

			if('' === $name && '' === $icon)
				return [];

			return array_filter([
				'name' => $name,
				'icon' => $icon,
				'color' => $color,
				'image' => $image,
				// Seeded off the NAME so the hashed fallback color is stable for one identity across
				// sessions -- an agent that looks different in every chat reads as a different agent.
				'seed' => trim(strval($agent['seed'] ?? '')) ?: ('agent:' . DevblocksPlatform::strLower($name ?: $icon)),
			], fn($v) => '' !== $v);
		}

		if('' === ($ref = trim(strval($agent ?? ''))))
			return [];

		if(!($worker = \Cerb\AutomationBuilder\Node\LlmAgentNode::resolveAgentWorker($ref))) {
			DevblocksPlatform::services()->log()->info(
				sprintf("llmTranscript `agent: %s` didn't resolve to an AI worker; falling back to the model's mark.", $ref)
			);

			return [];
		}

		return array_filter([
			'name' => $worker->getName(),
			'image' => $worker->getImageUrl(),
			'seed' => 'worker:' . $worker->id,
		], fn($v) => '' !== $v);
	}

	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();

		$label = $this->_data['label'] ?? null;

		// Nothing renderable (no session_id, or no visible messages) → no wrapper, no empty transcript.
		if(!$this->_prepare($continuation)) {
			if($this->_isBuilderPreview())
				$this->_renderBuilderPreview($label);
			return;
		}

		$tpl->assign('label', $label);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/llm_transcript.tpl');
	}

	/**
	 * Build the transcript's view state and push it onto the template. Shared by the full `render()` and by the
	 * `echoTurn` action, which re-renders only the turns region — one code path so the optimistic turn is
	 * byte-identical to the real one.
	 *
	 * `$pending` (`{text, images}`) appends a synthetic user turn that isn't in the session yet: the message the
	 * reader just submitted. It's rendered, never stored — the queue payload still carries the real one (see
	 * LlmAgentNode::_startLLMAsync), so an abandoned turn leaves no orphaned message.
	 *
	 * Returns false when there's nothing to draw at all.
	 */
	private function _prepare(Model_AutomationContinuation $continuation, ?array $pending = null, bool $may_persist = true) : bool {
		$tpl = DevblocksPlatform::services()->template();
		$llm = DevblocksPlatform::services()->llm();
		$session = \ChPortalHelper::getSession();

		$transcript_id = $this->_data['session_id'] ?? null;
		$limit = 250;

		// How much of the agent's work the reader sees. Tool/thinking labels are NOT here — they live on the
		// session's `tools:` block (authored on `llm.agent:inputs:tools:`), so the same phrasing renders here,
		// in Setup→Developers, and retroactively for any past session.
		//
		// Default-deny: a tool's raw params/results are whatever the agent fetched, which routinely includes
		// records this worker can't see. Summarize unless the author explicitly opts into `raw`.
		// A worker reading a conversation wants the formatted answer, not its markdown source — and no switcher
		// unless the author asks for one.
		$view = $this->_data['view'] ?? 'markdown';
		$thinking = $this->_data['thinking'] ?? 'summary';
		$tools = $this->_data['tools'] ?? 'summary';

		// 'conversation' (default) reads like a chat answer — the agent's text pools as one message with its
		// work in a sub-thread below; 'interleaved' reads like a step log — each preamble sits with the tool
		// it prompted (bodies and bubbles in author order). Purely presentational; the messages are unchanged.
		$layout = $this->_data['layout'] ?? 'interleaved';

		// With `raw`, only the newest agent turn's payloads are open — older work stays folded behind its
		// summary so a long conversation stays readable.
		$expand = $this->_data['expand'] ?? 'latest';

		// Per-turn In/Out/Cached chips. Off unless asked for: token counts are cost/infrastructure detail, and
		// most interactions are a conversation rather than a debugging view.
		$show_tokens = boolval($this->_data['tokens'] ?? false);

		// No session_id → nothing to draw (no wrapper, no empty transcript).
		if(!$transcript_id)
			return false;

		$transcript = $this->_getSession($transcript_id);
		$tpl->assign('transcript', $transcript);

		// A freshly-minted session_id (e.g. `{{uuid()}}`) has no row yet, and an unprimed session has no
		// provider — either way there are no messages to render. Degrade to an empty transcript instead
		// of fataling on getProvider(null).
		$raw_messages = [];
		$llm_provider = null;

		if($transcript && $transcript->provider) {
			$llm_provider = $llm->getProvider($transcript->provider, [], validate: false);

			// Render the ACTIVE PATH (head → nearest summary), which is what the model is actually being sent —
			// not every row in the session. After a compaction the folded turns are still in the table (WORM,
			// for audit/fork/rewind), and showing them here would put turns on screen that the agent can no
			// longer see. Setup→Developers deliberately does the opposite: it exists to show the whole tree.
			// No head (legacy/unmigrated) → the seq-linear read, same fallback DatabaseHistory makes.
			if($llm_provider) {
				$head = strval($transcript->head_uuid);

				$raw_messages = $head
					? \DAO_LlmAgentMessage::getActivePath($transcript_id, $head, last_n: $limit)
					: \DAO_LlmAgentMessage::getMessagesBySession($transcript_id, $limit);

				if(!$raw_messages)
					$raw_messages = [];
			}
		}

		// Display labels come from the session's persisted `tools:` block, not this component.
		$tool_map = $transcript ? $transcript->getToolMap() : [];

		// Group messages into turns for display (mirrors the Setup→Developers transcript viewer): each
		// user message opens its own panel; a run of agent/tool messages collapses into a single agent
		// panel. Tool-result messages are collected by id so they render inline under their tool call.
		$tool_results = [];
		$turns = [];
		$has_agent_turn = false;

		// A tool's elapsed time is the gap between the message carrying its CALL and the one carrying its
		// RESULT. Both are insert times, so this is the round trip as the session experienced it.
		$tool_call_at = [];
		$tool_result_at = [];

		foreach($raw_messages as $model) {
			$neutral = $llm_provider->convertToGenericMessage($model->data, $model->uuid);

			$model_at = ($model->created_at * 1000000) + $model->created_at_usec;

			foreach($neutral->getToolResults() as $tool_id => $tool_result) {
				$tool_results[$tool_id] = $tool_result;
				$tool_result_at[$tool_id] = $model_at;
			}

			foreach($neutral->getToolCalls() as $tool_call)
				$tool_call_at[$tool_call->getId()] = $model_at;

			if('tool' === $neutral->getRole())
				continue;

			// The boundary node (role=user, kind=summary) is a real user turn — SHOWN — but flagged as a
			// compaction checkpoint so the viewer marks where earlier turns were folded. It's the terminal
			// ancestor for future transcripts/compactions; its assistant answer follows as a normal agent turn.
			if('summary' === $model->kind) {
				$turns[] = ['role' => 'user', 'is_checkpoint' => true, 'ts_first' => $model->created_at, 'seq_first' => $model->seq, 'seq_last' => $model->seq, 'messages' => [$neutral]];
				continue;
			}

			$group_role = ('user' === $neutral->getRole()) ? 'user' : 'agent';
			$last = count($turns) - 1;

			if('agent' === $group_role)
				$has_agent_turn = true;

			// A user message always opens a new panel; agent messages accrete into the open one.
			if($last < 0 || 'user' === $group_role || 'agent' !== $turns[$last]['role']) {
				// `seq_first` is the turn's STABLE identity: the message that opened it, fixed for the turn's
				// lifetime. It's what the live poll addresses, because `seq_last` moves — an agent turn accretes
				// messages through the tool loop, so keying the DOM on the newest one means a growing turn stops
				// matching the node already on screen and gets appended again as a duplicate.
				$turns[] = ['role' => $group_role, 'ts_first' => $model->created_at, 'seq_first' => $model->seq, 'usage' => \DAO_LlmAgentMessage::USAGE_ZERO, 'messages' => [$neutral]];
				$idx = count($turns) - 1;
			} else {
				$turns[$last]['messages'][] = $neutral;
				$idx = $last;
			}

			// The newest message in this turn — the fork point "rewind from here" wants, and what the
			// Setup→Developers viewer means by `data-seq`. Deliberately NOT the patch key.
			$turns[$idx]['seq_last'] = $model->seq;

			// This turn is still being written. Drives the in-message typing indicator, so the "still going"
			// signal sits where the text is actually appearing rather than only in a chip below the whole
			// conversation. Rides the turn (not the message) because the template renders the cursor once,
			// after the messages, as the last thing in the flow.
			if($model->is_streaming)
				$turns[$idx]['is_streaming'] = true;

			// Provider-reported usage rides assistant turns only. FOLD it (output sums; the prompt side takes
			// the turn's final round-trip) so a multi-tool turn shows its real context instead of the sum of
			// every re-sent, mostly-cached prompt — which would N-count the shared history.
			$turns[$idx]['usage'] = \DAO_LlmAgentMessage::foldTurnUsage($turns[$idx]['usage'], $model->usage);

			// Why the LAST reporting round-trip in this turn stopped. Last-non-empty-wins: an agent turn is several
			// round-trips (the tool loop) and it's the final one that says how the turn ended.
			if('' !== $model->finish_reason)
				$turns[$idx]['finish_reason'] = $model->finish_reason;
		}

		// Milliseconds per tool call, for the transcript's summary line. Only where BOTH ends are known — a call
		// still in flight has no result yet, and its bubble says "Working" rather than a made-up number.
		$tool_durations = [];

		foreach($tool_result_at as $tool_id => $ended_at) {
			if(!array_key_exists($tool_id, $tool_call_at))
				continue;

			$tool_durations[$tool_id] = intdiv(max(0, $ended_at - $tool_call_at[$tool_id]), 1000);
		}

		// The reader's just-submitted message, which isn't in the session yet (it rides the queue payload until a
		// worker runs the turn). Appended here so it flows through the SAME ts_label / usage / truncation passes as
		// a stored turn — the point is that it's indistinguishable from the real one when the sync replaces it.
		// Its timestamp is "now"; the real render restamps it with the server's `created_at`.
		if($pending) {
			$turns[] = [
				'role' => 'user',
				'ts_first' => time(),
				'usage' => \DAO_LlmAgentMessage::USAGE_ZERO,
				// Flagged as a PLACEHOLDER because it has no `seq_first` — it isn't stored yet, so there's no seq to
				// give it. The live poll addresses turns by that key, so once the worker does store this message the
				// poll can't match this node and appends the real turn as a SECOND copy of what the reader just sent.
				// The flag is how the client recognizes its own placeholder and lets the real turn supersede it.
				'is_pending' => true,
				'messages' => [$this->_pendingMessage($pending, $llm_provider)],
			];
		}

		// Absolute, not relative: a worker can sit on their turn for a while, and "2 hours ago" would quietly go
		// stale in front of them. Today → just the time; older → the full date + time. formatTime() renders in
		// the reader's timezone.
		$date_service = DevblocksPlatform::services()->date();
		$today = $date_service->formatTime('Y-m-d', time());

		foreach($turns as &$turn) {
			if(!($ts = $turn['ts_first'] ?? 0))
				continue;

			$turn['ts_label'] = ($today === $date_service->formatTime('Y-m-d', $ts))
				? $date_service->formatTime('g:i a', $ts)
				: $date_service->formatTime(DevblocksPlatform::getDateTimeFormat(), $ts)
				;
		}
		unset($turn);

		// Add the `prompt` total + cache `coverage` the chips quote. Same definitions as Setup→Developers —
		// one session must not report two different token counts depending on where it's read.
		foreach($turns as $i => $turn) {
			$turns[$i]['usage'] = \DAO_LlmAgentMessage::deriveUsage($turn['usage'] ?? []);
			// The turn ended abnormally — its content is cut off (`length`) or was withheld (`filter`). Flagged so a
			// worker isn't left guessing why an answer stops mid-sentence. Same rule as Setup→Developers.
			$turns[$i]['is_truncated'] = in_array($turn['finish_reason'] ?? '', ['length', 'filter'], true);
		}

		// Nothing to show yet (new/empty session, or no visible messages).
		//
		// At DESIGN time that means paint the sample instead (render() handles it off a false return).
		//
		// At RUNTIME we still emit the element — EMPTY and hidden. It used to be omitted entirely, which left the
		// very first submit of a conversation with no echo target at all: the composer hides, and the one moment
		// a reader most needs to see something happen is the one moment nothing could. The shell is inert (hidden,
		// no turns, no Stop, no disclaimer) until the echo fills it and reveals it.
		if(!$turns && $this->_isBuilderPreview())
			return false;

		$tpl->assign('is_empty', !$turns);

		// In-progress ⟺ there is NO finished assistant answer after the last user turn — i.e. the newest message
		// isn't a completed agent text reply. That's the whole window where the composer is gone and the reader
		// needs a brake: the agent is thinking (last = user), mid tool call (last = tool_use), or between a tool
		// result and its follow-up turn (last = tool_result). Only a pure assistant text turn (role=assistant,
		// kind=text) is "finished". The async flow surfaces every one of these — an `on_tool:` render captures the
		// transcript before `returnTool` appends the result, and the panel keeps the render up under `await:queue`.
		//
		// A STREAMING row is role=assistant + kind=text and would otherwise read as "finished" the instant it's
		// created — hiding the Stop button and stopping the poll at exactly the moment a long turn needs both.
		// It is the opposite of finished: it's the only state where we KNOW work is happening right now.
		//
		// The tip alone can't close the window, though: an honored Stop yields mid-tool-loop, so the newest
		// message stays a tool_result -- "not finished" forever -- while control is already back with the reader.
		// A COMPOSER in the same form is the positive proof that no turn is running (see _hasComposer), and it
		// is also what makes "exactly one brake" structural rather than a client-side race: the agentPrompt's
		// own Stop covers the submit window, ours covers the renders where the composer is gone.
		$last_message = $raw_messages ? $raw_messages[array_key_last($raw_messages)] : null;
		$is_finished = $last_message
			&& 'assistant' === $last_message->role
			&& 'text' === $last_message->kind
			&& !$last_message->is_streaming;
		$is_in_progress = $last_message && !$is_finished && !$this->_hasComposer($continuation);
		$tpl->assign('is_in_progress', $is_in_progress);
		$tpl->assign('session_id', $transcript_id);

		// The turn currently being written, if any. Drives the live poll: the client patches THIS turn in place
		// rather than re-rendering the transcript, and stops polling the moment it goes away.
		$tpl->assign('streaming_seq', ($last_message && $last_message->is_streaming) ? $last_message->seq : 0);

		// Stop was already clicked (the flag is set but the node hasn't hit its yield boundary yet). The transcript
		// re-renders often (every on_tool / kept across await:queue), so a purely client-side "Stopping…" state gets
		// wiped by the next render — read the pending interrupt here so every render shows it, making the click's
		// effect obvious until the agent actually stops. Read-only (the node consumes/clears the flag at yield).
		$interrupt_pending = $is_in_progress && $transcript_id
			&& DevblocksPlatform::services()->cache()->load(\Cerb\AutomationBuilder\Node\LlmAgentNode::interruptCacheKey($transcript_id), true);
		$tpl->assign('interrupt_pending', $interrupt_pending);

		// Sanitize links to the `#cerb-external-link` + click-to-confirm popup (matches email display / sheets).
		// NOT URIFilter_Extract — that rewrites links to dead `#uri-<code>` anchors whose map we don't render.
		$tpl->assign('filter_links', new \Cerb_HTMLPurifier_URIFilter_Email(true));
		$tpl->assign('turns', $turns);
		$tpl->assign('tool_results', $tool_results);
		$tpl->assign('tool_durations', $tool_durations);
		$tpl->assign('has_agent_turn', $has_agent_turn);
		$tpl->assign('tool_map', $tool_map);
		$tpl->assign('view', $view);
		$tpl->assign('layout', $layout);
		$tpl->assign('thinking', $thinking);
		$tpl->assign('tools', $tools);
		$tpl->assign('expand', $expand);
		$tpl->assign('show_tokens', $show_tokens);

		// Identity for the conversation layout: who started the session (not who's reading it) and the agent's
		// branded glyph. That comes from the session's OWN stamped `display:` block (falling back to the
		// provider's mark), so a transcript keeps showing the vendor it actually ran as even after the
		// `agent_model` record behind it is renamed, re-pointed, or deleted.
		//
		// The session row may not exist yet — the echo of a FIRST message races the submit that primes it — so
		// fall back to the active worker, who is by definition the one who just sent the pending turn. (Reaching
		// here with no row is only possible via that echo; a stored-message render always has one.)
		$tpl->assign('llm_session_user', $transcript ? $transcript->getUser() : \CerberusApplication::getActiveWorker());
		$tpl->assign('agent_provider_icon', $transcript ? $transcript->getDisplayIcon() : '');
		$tpl->assign('agent_provider_color', $transcript ? $transcript->getDisplayIconColor() : '');

		// `agent:` overrides the generic "Agent" byline and its avatar. Render-only and unstored, so an
		// existing transcript relabels on its next paint rather than carrying a stale name forward.
		// Every key, always -- the template service is a singleton reused across renders, so assigning only
		// the keys this transcript has would leak the previous one's identity into a transcript with none.
		if(($agent_identity = $this->_agentIdentity())) {
			// Authored here: remember it for the renders that have none (the uiCommand repaint, the async poll).
			// Never from the poll -- `_promptAction_pollTurn` is read-only by design, and writing there would
			// race the interaction's own gate POST.
			if($may_persist)
				$this->_persistIdentity($continuation, strval($transcript_id), $agent_identity);

		} else {
			$agent_identity = $this->_storedIdentity($continuation, strval($transcript_id));
		}

		$agent_identity += ['name' => '', 'icon' => '', 'color' => '', 'image' => '', 'seed' => ''];

		foreach($agent_identity as $key => $value)
			$tpl->assign('agent_' . $key, $value);

		$tpl->assign('continuation_token', $continuation->token);
		$tpl->assign('session', $session);
		$tpl->assign('var', $this->_key);

		return true;
	}

	/**
	 * The neutral message for a pending (unstored) user turn. Routed through the session provider's
	 * convertToGenericMessage() when there is one, so posted `images:` resolve to display URLs exactly as a
	 * stored message's would. A brand-new session may not be primed yet — the echo races the submit that primes
	 * it — so fall back to text-only rather than failing to draw anything.
	 */
	private function _pendingMessage(array $pending, ?\Extension_DevblocksLlmProvider $llm_provider) : \DevblocksLlmChatResponse {
		$text = strval($pending['text'] ?? '');
		$images = is_array($pending['images'] ?? null) ? $pending['images'] : [];

		if($llm_provider)
			return $llm_provider->convertToGenericMessage(['role' => 'user', 'content' => $text, 'images' => $images]);

		$message = new \DevblocksLlmChatResponse('user');

		if('' !== $text)
			$message->pushMessage($text);

		return $message;
	}

	/**
	 * `echoTurn` — re-render the turns region with the reader's just-submitted message appended, so a submitted
	 * prompt appears immediately instead of after the agent's reply.
	 *
	 * Why this exists: an async turn suspends on `await:queue:`, whose response is an invisible poll marker — the
	 * panel deliberately leaves the transcript untouched for the whole turn (panel.tpl). So without this the
	 * message the worker just sent is nowhere on screen until the agent answers.
	 *
	 * Renders the WHOLE turns region, not just the new turn: it costs the same query, and it re-syncs a transcript
	 * that drifted for any other reason. Nothing is persisted here — the real message rides the queue payload.
	 */
	private function _promptAction_echoTurn(Model_AutomationContinuation $continuation) : bool {
		$text = DevblocksPlatform::importGPC($_POST['text'] ?? null, 'string', '');
		$images = DevblocksPlatform::importGPC($_POST['images'] ?? null, 'array', []);

		// An empty submit has nothing to echo (the composer blocks it too).
		if('' === trim($text) && !$images)
			return false;

		$pending = [
			'text' => $text,
			// Only the uri shape the neutral converter understands; a bad entry drops rather than rendering a
			// broken <img>. The URLs these become are ACL-gated at fetch, same as a stored message's.
			'images' => array_values(array_filter(array_map(
				fn($uri) => is_string($uri) ? ['uri' => $uri] : null,
				$images
			))),
		];

		if(!$this->_prepare($continuation, $pending, may_persist: false))
			return false;

		DevblocksPlatform::services()->template()
			->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/_transcript_turns.tpl');

		return true;
	}

	/**
	 * `pollTurn` — while a streamed turn is being written, hand back JUST that turn's markup so the client can
	 * swap one node instead of redrawing the transcript.
	 *
	 * This is what turns a long turn from a blank spinner into something a reader can watch. The provider
	 * persists the partial as it arrives (`llm_agent_message.is_streaming`), and this reads it — a plain SELECT
	 * on a different request than the one doing the work, which is the only reason the two can overlap at all.
	 *
	 * Returns JSON rather than markup because the client needs the CONTROL fields too: `in_progress` says
	 * whether to keep polling, and `seq` says which node to replace.
	 *
	 * ⚠️ Read-only, like every `invokePrompt` action. That's precisely what makes it safe to fire concurrently
	 * with the interaction's own gate POST — adding a write here would reintroduce the race that design avoids.
	 */
	private function _promptAction_pollTurn(Model_AutomationContinuation $continuation) : bool {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		// CHEAP PATH FIRST. _prepare() walks the whole active path — a recursive CTE materializing full
		// `data_json` for up to 250 messages, then a json_decode and a convertToGenericMessage() per row —
		// and then throws all but the newest turn away. At one tick per second on a long session that is
		// the single most expensive thing in the poll, and during a thinking phase (or ANY turn on a
		// non-streaming provider) it renders markup byte-identical to the last tick, which the client
		// discards. So answer "nothing moved" from the session head alone, before any of that runs.
		if(($state = $this->_pollState($continuation))) {
			if('' !== ($sent = strval($this->_data['fingerprint'] ?? '')) && $sent === $state['fingerprint']) {
				echo json_encode($state + ['unchanged' => true]);
				return true;
			}
		}

		if(!$this->_prepare($continuation, null, may_persist: false)) {
			echo json_encode(['in_progress' => false, 'working' => false, 'streaming' => false, 'seq' => 0, 'html' => '']);
			return true;
		}

		$tpl = DevblocksPlatform::services()->template();

		$turns = $tpl->getTemplateVars('turns') ?: [];
		$in_progress = boolval($tpl->getTemplateVars('is_in_progress'));
		$streaming_seq = intval($tpl->getTemplateVars('streaming_seq'));

		// Render ONLY the newest turn. The rest of the transcript is immutable history the client already has,
		// and re-sending it every second would defeat the point of patching a single node.
		$last = $turns ? [array_key_last($turns) => $turns[array_key_last($turns)]] : [];
		$tpl->assign('turns', $last);

		ob_start();
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/_transcript_turns.tpl');
		$html = ob_get_clean();

		echo json_encode([
			// "No finished assistant answer after the last user turn." Broad on purpose — it also covers the gap
			// while a tool runs — but it is NOT a claim that work is happening RIGHT NOW: after a Stop is honored
			// mid-tool-loop the newest message is a tool_result, so this stays true forever with nothing running.
			'in_progress' => $in_progress,
			// Work is genuinely underway: a turn is being written, or one is queued and about to be. This is what
			// the elapsed clock keys off — a clock that keeps counting after a Stop is worse than no clock,
			// because it asserts something false.
			'working' => $streaming_seq > 0 || 'queue' === strval($continuation->state_await ?? ''),
			// Non-zero only while a turn is actively being written. The client uses the transition to zero as
			// its stop signal, so it never has to guess when a turn ended.
			'streaming' => $streaming_seq > 0,
			// The turn's STABLE key (its first message), not `seq_last` — see the grouping loop. Keying on the
			// newest message made a growing turn miss the node already on screen and append a duplicate.
			'seq' => $last ? intval(reset($last)['seq_first'] ?? 0) : 0,
			'html' => $html,
			// Lets the NEXT tick be answered from the session head alone when nothing has moved.
			'fingerprint' => $state['fingerprint'] ?? '',
			// Whether this session's provider streams at all. A non-streaming provider can never change
			// this transcript mid-turn, so the client uses it to pick a slow cadence on the FIRST tick
			// rather than discovering it by polling a second at a time for the length of the turn.
			'can_stream' => $state['can_stream'] ?? false,
		]);

		return true;
	}

	/**
	 * The poll's control fields, derived from the session head ALONE — one indexed row, no active-path
	 * walk. Everything here is a property of the newest message plus the continuation, which is exactly
	 * what `_prepare()` would compute from the tail of the path it just spent the CTE building.
	 *
	 * The fingerprint has to move whenever the newest turn's RENDERING could: a new message (head_uuid),
	 * a streamed row growing in place (its content length), or that row finalizing (is_streaming).
	 *
	 * @return ?array{fingerprint:string,in_progress:bool,working:bool,streaming:bool,can_stream:bool}
	 */
	private function _pollState(Model_AutomationContinuation $continuation) : ?array {
		if('' === ($session_id = strval($this->_data['session_id'] ?? '')))
			return null;

		if(!($session = $this->_getSession($session_id)))
			return null;

		// A legacy/unmigrated session has no head, and _prepare() answers it from a seq-linear read whose
		// state this can't reproduce from one row. Decline rather than short-circuit on a fingerprint that
		// would be stable while saying the wrong thing.
		if('' === ($head_uuid = strval($session->head_uuid ?? '')))
			return null;

		if(!($head = \DAO_LlmAgentMessage::get($head_uuid)))
			return null;

		$is_streaming = boolval($head->is_streaming);

		// Mirrors _prepare(): finished means the tip is a completed assistant text reply. A STREAMING row
		// is role=assistant and classifies as kind=text, so without the flag it would read as finished the
		// instant it was created — killing the Stop button and the poll exactly when both are needed.
		$is_finished = 'assistant' === $head->role
			&& 'text' === $head->kind
			&& !$is_streaming;

		$can_stream = false;

		if($session->provider) {
			try {
				$provider = DevblocksPlatform::services()->llm()->getProvider($session->provider, [], false);

				$can_stream = $provider instanceof \Cerb\LLM\Providers\Interfaces\ChatStreaming
					&& $provider->isStreamingEnabled();

			} catch(\Throwable $e) {
				// An unknown/misconfigured provider just means "assume it can't stream" — the client falls
				// back to the slow cadence, which is the safe direction to be wrong in.
			}
		}

		return [
			'fingerprint' => implode(':', [
				$head_uuid,
				$is_streaming ? 1 : 0,
				strlen(json_encode($head->data)),
			]),
			// Mirrors _prepare() on BOTH counts, or the cheap path would keep a poll alive that the full render
			// has already ended -- the fingerprint only moves when a MESSAGE does, and the yield that brings the
			// composer back writes nothing.
			'in_progress' => !$is_finished && !$this->_hasComposer($continuation),
			// Work is genuinely underway: a turn is being written, or one is queued and about to be.
			'working' => $is_streaming || 'queue' === strval($continuation->state_await ?? ''),
			'streaming' => $is_streaming,
			'can_stream' => $can_stream,
		];
	}

	// Design-time sample shown when there's no live session to render. Painted to look like a real
	// styled transcript: the user turn is the current worker; the agent turn a representative model.
	private function _renderBuilderPreview($label) : void {
		$tpl = DevblocksPlatform::services()->template();
		$llm = DevblocksPlatform::services()->llm();

		// No model is configured at design time (the provider is only known at runtime off the
		// persisted session), so paint a representative default — the first agentPrompt preset
		// (Anthropic · Claude Fable 5).
		$preset = \AutomationTrigger_InteractionWorker::getAgentModelPresets()[0] ?? [];
		$provider = $preset['provider'] ?? 'anthropic';

		$tpl->assign('label', $label);
		$tpl->assign('llm_session_user', \CerberusApplication::getActiveWorker());
		$tpl->assign('agent_model_label', ($this->_agentIdentity()['name'] ?? '') ?: ($preset['label'] ?? 'Agent'));
		$tpl->assign('agent_provider_icon', $llm->getProviderIcon($provider));
		$tpl->assign('agent_provider_color', $llm->getProviderIconColor($provider));

		// Reflect the author's chosen display modes so the sample matches their config.
		$tpl->assign('view', $this->_data['view'] ?? 'markdown');
		$tpl->assign('layout', $this->_data['layout'] ?? 'interleaved');
		$tpl->assign('thinking', $this->_data['thinking'] ?? 'summary');
		$tpl->assign('tools', $this->_data['tools'] ?? 'summary');
		$tpl->assign('expand', $this->_data['expand'] ?? 'latest');

		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/_simulated_transcript.tpl');
	}

	/*
	private function _promptAction_rating(string $prompt_key, Model_AutomationContinuation $continuation) {
		$message_uuid = DevblocksPlatform::importGPC($_POST['message_uuid'] ?? null);
		$rating = DevblocksPlatform::importGPC($_POST['rating'] ?? null);
		
		if(!$message_uuid || !($message = \DAO_LlmAgentMessage::get($message_uuid)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		\DAO_LlmAgentMessage::setRating($message->uuid, $rating);
	}
	*/
}