{$element_id = uniqid('response_')}
{* An empty transcript renders HIDDEN rather than not at all, so the optimistic echo has a target on the very
   first submit of a conversation (see `is_empty` in LlmTranscriptAwait). It reveals itself when filled. *}
<div class="cerb-form-builder-prompt cerb-form-builder-response-llm-transcript" id="{$element_id}"{if $is_empty} style="display:none;"{/if}>
    {if $label}<h6>{$label}</h6>{/if}

    {* Bare markup — CerbUI.AgentTranscript.enhance() below builds the chrome.
       The echo attrs let the ONE form-level submitted-handler address whichever transcripts are live at fire
       time, without every render binding another handler to the persistent form. *}
    <div class="cerb-ui-agent-transcript" data-cerb-agent-transcript
        data-cerb-transcript-echo-key="llmTranscript/{$var}"
        data-cerb-transcript-echo-token="{$continuation_token}">
        {include file="devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/_transcript_turns.tpl"}
    </div>

    {* While the agent is mid-action (a tool call awaiting its result) the composer is gone — offer a brake here so
       a runaway agent can be stopped without closing the tab. It flags the running llm.agent node (interruptAgent),
       which yields at its next tree-safe boundary. Live only (a real continuation + session). *}
    {* Emitted whenever there's a live continuation, not only when the server already knows a turn is running.
       A turn usually STARTS after this render (submit → `await:queue:` → no re-render until it ends), so the
       row has to exist for the poll to reveal. Hidden until then so an idle transcript looks idle. *}
    {if $continuation_token && !($is_automation_simulated|default:false)}
        <div data-cerb-transcript-stop class="cerb-u-flex cerb-u-items-center cerb-u-gap-2" style="margin-top:0.75em;{if !$is_in_progress}display:none;{/if}">
            {* The Stop button is server-rendered only when the server knows a turn is live. In the submit
               window the brake lives on the agentPrompt's own busy block instead, so there's always one. *}
            {if $is_in_progress}
            {if $interrupt_pending}
                {* Stop already requested — persist the feedback across the frequent re-renders until the agent yields. *}
                <button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-transcript-stop-btn disabled title="Stopping the agent at the next safe point…">
                    <span class="cerb-icons cerb-icon-square"></span> Stopping…
                </button>
            {else}
                <button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-transcript-stop-btn title="Stop the agent">
                    <span class="cerb-icons cerb-icon-square"></span> Stop
                </button>
            {/if}
            {/if}

            {* Liveness, beside the brake it belongs with. Deliberately an ELAPSED CLOCK rather than a token
               count: a turn can spend minutes in extended thinking, whose blocks stream with empty text
               unless the author asked for `display: summarized`, so anything derived from content would sit
               at zero and read as hung. The clock is always true, and the answer streams in above as soon as
               there is any. Revealed by the poll, so it can't claim work is happening when nothing is. *}
            <div data-cerb-transcript-activity class="cerb-ui-chip" title="The agent is still working" style="display:none;">
                <div class="cerb-ui-chip--head"><span class="cerb-icons cerb-icon-stopwatch"></span></div>
                <div class="cerb-ui-chip--value" data-cerb-transcript-activity-elapsed>0s</div>
            </div>
        </div>
    {/if}

    {if $has_agent_turn}
        <div data-cerb-dom="transcript-disclaimer" class="cerb-u-text-muted" style="margin-top:0.75em;font-size:0.85em;">
            (This conversation contains machine-generated answers and may have inaccuracies.)
        </div>
    {/if}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    const $prompt = $('#{$element_id}');

    // Stop the running agent (shown only while in-progress). Raises the out-of-band interrupt flag; the node
    // yields at its next tree-safe boundary and control returns here. Disable on click so a double-tap doesn't
    // spam the endpoint — the next render (the yield) restores the composer.
    $prompt.find('[data-cerb-transcript-stop-btn]').on('click', function() {
        const $b = $(this);
        if($b.prop('disabled')) return;
        // Immediate, VISIBLE feedback (before the next render lands): swap the label to "Stopping…" and disable.
        // The next transcript render (which reads the pending flag server-side) keeps this state until the yield.
        $b.prop('disabled', true).attr('title', 'Stopping the agent at the next safe point…')
            .html('<span class="cerb-icons cerb-icon-square"></span> Stopping…');

        const fd = new FormData();
        fd.set('c', 'profiles');
        fd.set('a', 'invoke');
        fd.set('module', 'automation');
        fd.set('action', 'interruptAgent');
        fd.set('continuation_token', '{$continuation_token|escape:'javascript'}');
        fd.set('session_id', '{$session_id|escape:'javascript'}');
        genericAjaxPost(fd, null, null, function() { });
    });

    // Echo a just-submitted prompt into the transcript right away. A sibling agentPrompt announces what it
    // posted; we re-render the turns region with that message appended (server-rendered from the same partial
    // as the real turns, so the sync that replaces it doesn't visibly reflow).
    //
    // Bound ONCE per form, not once per render: the form outlives every transcript render (`$data.html()`
    // replaces us, never it), so re-binding here would stack a handler — and therefore an extra POST — per
    // render. Instead the single handler re-discovers whichever transcripts are live when it fires.
    const $form = $prompt.closest('form.cerb-form-builder');

    if($form.length && !$form.data('cerbTranscriptEchoBound')) {
        $form.data('cerbTranscriptEchoBound', true);

        $form.on('cerb-agentprompt-submitted', function(e, data) {
            data = data || { };

            $form.find('[data-cerb-transcript-echo-key]').each(function() {
                const container = this;
                const inst = (window.CerbUI && CerbUI.AgentTranscript) ? CerbUI.AgentTranscript.from(container) : null;

                if(!inst)
                    return;

                // No continuation, nothing to echo against. The simulator's form-state preview renders a
                // WORKING composer (`is_automation_form_fill` deliberately opts out of the inert render) over
                // an ephemeral continuation whose token is '', so this handler fires there too -- and
                // `invokePrompt` answers an unknown token with a 404.
                const token = container.getAttribute('data-cerb-transcript-echo-token') || '';

                if(!token)
                    return;

                const fd = new FormData();
                fd.set('c', 'profiles');
                fd.set('a', 'invoke');
                fd.set('module', 'automation');
                fd.set('action', 'invokePrompt');
                fd.set('prompt_key', container.getAttribute('data-cerb-transcript-echo-key') || '');
                fd.set('prompt_action', 'echoTurn');
                fd.set('continuation_token', token);
                fd.set('text', data.text || '');
                (data.images || []).forEach(function(uri) { if(uri) fd.append('images[]', uri); });

                genericAjaxPost(fd, null, null, function(html) {
                    // The container we posted for: a real render replaces this node wholesale, so a response
                    // that lands late finds it detached and drops. Without this a slow echo would REVERT the
                    // transcript to a state predating the agent's reply.
                    if(!html || !document.contains(container))
                        return;

                    CerbUI.AgentTranscript.from(container).setTurns(html);

                    // The scroll box is the OUTER prompt div, resolved from the container at fire time — this
                    // handler outlives the render that bound it, so it can't close over that render's element.
                    const box = container.closest('.cerb-form-builder-response-llm-transcript');

                    // A first-message transcript rendered hidden and empty (nothing to show, but something had
                    // to exist for this response to land in). It has turns now.
                    if(box)
                        box.style.display = '';

                    // Land on the new turn, deferred a frame so it measures the rebuilt (and now visible) turns.
                    requestAnimationFrame(function() {
                        if(box && box.scrollHeight > box.clientHeight)
                            box.scrollTop = box.scrollHeight;
                    });
                });
            });
        });
    }

    {* Watch a turn being written. The provider persists its partial answer as it streams, so this reads that
       row and swaps ONE turn per tick — the transcript fills in instead of sitting frozen for minutes.

       THE POLL MUST BE STARTABLE ON SUBMIT, not only when a render happens to catch a turn already running.
       The first version only emitted this block under `$is_in_progress`, which sounds right and is wrong in
       the most common case: when you submit, the transcript ON SCREEN is the one rendered BEFORE the submit,
       back when the conversation was idle — and during `await:queue:` the panel deliberately never re-renders.
       So the whole first stretch of a turn (often the longest, all thinking) had no poll at all, and updates
       only appeared once an `on_tool:` render incidentally produced a transcript that WAS in progress.

       So: the machinery is emitted whenever there's a live continuation, and started from two places —
       here if the server already knows a turn is running (resuming a parked interaction), and from the
       form-level submit handler below for the ordinary case. `startPoll` is idempotent. *}
    {if $continuation_token && !($is_automation_simulated|default:false)}
    (function() {
        const container = $prompt.find('[data-cerb-transcript-echo-key]')[0];

        if(!container || !(window.CerbUI && CerbUI.AgentTranscript))
            return;

        const activity = $prompt.find('[data-cerb-transcript-activity]')[0];
        const stopRow = $prompt.find('[data-cerb-transcript-stop]')[0];

        // Fast while a turn is genuinely being WRITTEN -- that's the only time this transcript can change
        // between ticks. Slow otherwise: the gap while a tool runs, and every turn on a provider that
        // doesn't stream at all, where the markup is byte-identical for the whole turn.
        const POLL_STREAMING_MS = 1000;
        const POLL_IDLE_MS = 5000;
        // A backgrounded tab is not being read; keep watching, but stop paying for it every second.
        const POLL_HIDDEN_MS = 15000;
        // Give up after this many consecutive ticks with no work and no change.
        const MAX_IDLE_TICKS = 30;

        let startedAt = 0;
        let inflight = false;
        let failures = 0;
        let timer = null;
        let lastHtml = '';
        let idle = 0;
        // Opaque server token for "what I already have". Lets the server answer from the session head
        // instead of walking the whole active path to rebuild markup we'd only throw away.
        let fingerprint = '';
        // Until the first response says otherwise, assume the provider streams: being wrong that way costs
        // a few fast ticks, while the reverse would make a genuinely live turn look frozen.
        let canStream = true;
        // Owned by the poll's lifetime, not the render's -- both are torn down in stop(). A document-level
        // listener left behind would stack one per render, and the elapsed ticker would outlive its clock.
        let ticker = null;
        let onVisible = null;

        const stop = function() {
            if(timer) { clearTimeout(timer); timer = null; }
            if(ticker) { clearInterval(ticker); ticker = null; }

            if(onVisible) {
                document.removeEventListener('visibilitychange', onVisible);
                onVisible = null;
            }

            container._cerbTranscriptPolling = false;
            if(activity) activity.style.display = 'none';

            // The brake goes with the clock: this row is revealed only by startPoll(), so leaving it up outlives
            // the turn it could stop. A Stop button under a finished turn does nothing when clicked, and the next
            // submit would put the agentPrompt's own brake beside it -- two buttons for one interrupt.
            if(stopRow) stopRow.style.display = 'none';
        };

        // Self-rescheduling rather than a fixed interval, so the cadence can follow what's actually
        // happening. `inflight` still guards overlap; this decides how soon we ask again.
        const schedule = function(delay) {
            if(timer) clearTimeout(timer);
            timer = setTimeout(poll, delay);
        };

        const nextDelay = function(json) {
            if(document.visibilityState === 'hidden')
                return POLL_HIDDEN_MS;

            // Content is arriving right now -- this is the only case that earns a 1s cadence.
            if(json && json.streaming)
                return POLL_STREAMING_MS;

            // The provider can't stream, so nothing will change mid-turn no matter how often we ask.
            if(!canStream)
                return POLL_IDLE_MS;

            // Working but not streaming: a tool is running, or a turn is queued and about to start.
            return (json && json.working) ? POLL_STREAMING_MS : POLL_IDLE_MS;
        };

        // Elapsed time is the honest liveness signal. During an extended-thinking phase there is genuinely
        // nothing to show — thinking blocks stream with EMPTY text unless the author opted into
        // `display: summarized` — so a token counter would sit at zero and read as "stuck". A ticking clock
        // says what's actually true: still working, this long so far.
        const tick = function() {
            if(!activity) return;
            const s = Math.floor((Date.now() - startedAt) / 1000);
            const label = activity.querySelector('[data-cerb-transcript-activity-elapsed]');
            if(label) label.textContent = (s < 60) ? (s + 's') : (Math.floor(s / 60) + 'm ' + (s % 60) + 's');
        };

        const poll = function() {
            // The render that owned us has been replaced — a newer transcript is on screen with its own poll.
            if(!document.contains(container))
                return stop();

            tick();

            // Overlap guard. It MUST reschedule rather than just bail: this loop is a chain of setTimeouts,
            // so any path that neither stops nor schedules kills it silently and forever. (Under the old
            // fixed setInterval a bare `return` was harmless, which is exactly why it reads as safe.)
            // Reachable via the visibility handler's schedule(0) landing on an in-flight request.
            if(inflight)
                return schedule(POLL_STREAMING_MS);

            inflight = true;

            // A dedicated profileAction, NOT invokePrompt: a turn only streams while the continuation is
            // parked on `await:queue:`, whose `__return` carries no form — so invokePrompt's
            // element-must-be-in-the-current-form check 404s every time. Same reason interruptAgent has its
            // own action. Display options ride along because they lived on that unreachable form element.
            const fd = new FormData();
            fd.set('c', 'profiles');
            fd.set('a', 'invoke');
            fd.set('module', 'automation');
            fd.set('action', 'pollAgentTurn');
            fd.set('continuation_token', container.getAttribute('data-cerb-transcript-echo-token') || '');
            fd.set('session_id', '{$session_id|escape:'javascript'}');
            fd.set('view', '{$view|escape:'javascript'}');
            fd.set('layout', '{$layout|default:'interleaved'|escape:'javascript'}');
            fd.set('thinking', '{$thinking|default:'summary'|escape:'javascript'}');
            fd.set('tools', '{$tools|default:'summary'|escape:'javascript'}');
            fd.set('expand', '{$expand|default:'latest'|escape:'javascript'}');
            fd.set('tokens', '{if $show_tokens}1{else}0{/if}');
            fd.set('fingerprint', fingerprint);

            genericAjaxPost(fd, null, null, function(json) {
                inflight = false;
                failures = 0;

                if(!document.contains(container))
                    return stop();

                // An auth/not-found failure answers HTTP 200 with an `error` key, so the transport-level
                // `fail` handler below never sees it. Without this a dead or expired continuation is polled
                // for as long as the tab stays open.
                if(json && json.error)
                    return stop();

                if(json && typeof json.can_stream === 'boolean')
                    canStream = json.can_stream;

                if(json && typeof json.fingerprint === 'string')
                    fingerprint = json.fingerprint;

                // Only touch the DOM when the markup actually changed. A turn spends long stretches producing
                // content the transcript can't show (thinking blocks stream with EMPTY text under
                // `display: omitted`), and rebuilding an identical turn every second would churn its bubbles
                // and wreck text selection for a reader who is mid-sentence. `unchanged` is the server having
                // reached the same conclusion before doing the work to render anything.
                let changed = false;

                if(json && !json.unchanged && json.seq && json.html && json.html !== lastHtml) {
                    lastHtml = json.html;
                    changed = true;

                    // NO auto-scroll while streaming, deliberately. An "only follow if they're already at the
                    // bottom" rule isn't enough here: updateTurn REPLACES the turn node, which destroys the
                    // element the browser was scroll-anchored to, so a turn that grows — a new tool bubble,
                    // say — shifts content under the reader on its own. Forcing the tail on top of that moved
                    // text out from under someone mid-sentence. Letting it grow below the viewport is
                    // predictable and never fights a reader; the cost is that following along is manual.
                    CerbUI.AgentTranscript.from(container).updateTurn(json.seq, json.html);
                }

                // The clock asserts that work is happening, so it follows `working` (a turn being written or
                // queued) — NOT `in_progress`, which stays true after a Stop is honored mid-tool-loop because
                // the newest message is a tool_result. A clock still counting then is a lie.
                if(activity)
                    activity.style.display = (json && json.working) ? '' : 'none';

                // Keep watching across the gap while a tool runs (nothing is streaming, but the next turn is
                // coming and no render will happen to restart us). Bounded, though: after a Stop the
                // interaction can sit indefinitely with `in_progress` true and nothing ever arriving, and
                // polling forever for a view nobody is waiting on is just noise. A real render detaches the
                // container and ends it sooner anyway.
                //
                // Idle means NOTHING MOVED -- no work reported and the markup unchanged. The old test counted
                // a tick as busy whenever `html` was merely PRESENT, and a session with any turn in it always
                // renders something, so `idle` never incremented and the cutoff below could never fire.
                idle = (json && (json.working || changed)) ? 0 : (idle + 1);

                // The turn is done. The interaction's own gate poll owns what happens next — this only ever
                // watches; it never advances the interaction.
                if(json && !json.in_progress)
                    return stop();

                if(idle >= MAX_IDLE_TICKS)
                    return stop();

                schedule(nextDelay(json));

            }, {
                // A failed WATCH must stay silent. This runs alongside the interaction's own gate poll and the
                // queue sidecars, all sharing this endpoint, and the default handler calls clearAlerts() —
                // a blip here would wipe unrelated banners and alarm the reader about a request that only
                // affects a cosmetic refresh. Give up after a few in a row rather than hammering a dead
                // endpoint; the gate poll is what actually advances the interaction, and it reports for itself.
                fail: function() {
                    inflight = false;

                    if(++failures >= 5)
                        return stop();

                    // Back off while it's failing rather than retrying at the streaming cadence.
                    schedule(POLL_IDLE_MS);
                }
            });
        };

        // Idempotent, and flagged on the CONTAINER rather than in this closure — the submit handler below
        // lives on the persistent form and re-discovers whichever transcript is live at fire time, so the
        // guard has to be readable from outside here. The container dies with each render, which resets it.
        const startPoll = function() {
            // Per-turn state, reset BEFORE the idempotence guard: a poll can still be alive from the previous
            // turn (it gives up on its own schedule, not the turn's), and the clock is the one thing that must
            // not survive that. Counting a new turn from the old turn's start reads as minutes of work seconds
            // in -- the same lie the clock follows `working` to avoid.
            startedAt = Date.now();
            idle = 0;
            lastHtml = '';
            fingerprint = '';
            failures = 0;

            if(stopRow) stopRow.style.display = '';
            if(activity) { activity.style.display = ''; tick(); }

            if(container._cerbTranscriptPolling) return;
            container._cerbTranscriptPolling = true;

            // No immediate tick: a submit fires the optimistic echo at the same moment, and that does a FULL
            // setTurns() replace. Polling in the same beat would race it — we'd patch a turn the echo is
            // about to wipe. One second late costs nothing and removes the race entirely.
            schedule(POLL_STREAMING_MS);

            // The CLOCK must keep moving at a steady rate even though the REQUESTS back off -- at a 5s
            // cadence the elapsed readout would otherwise jump five seconds at a time.
            ticker = setInterval(tick, 1000);

            // Coming back to the tab should feel live again immediately, not whenever the slow timer expires.
            onVisible = function() {
                if('visible' === document.visibilityState && document.contains(container))
                    schedule(0);
            };

            document.addEventListener('visibilitychange', onVisible);
        };

        container._cerbTranscriptStartPoll = startPoll;

        {* The server already knows a turn is running: a re-render that landed mid-turn, or a parked
           interaction being resumed. Start straight away rather than waiting for a submit that won't come. *}
        {if $is_in_progress}startPoll();{/if}
    })();

    {* The ordinary path: a turn begins when the reader submits, and nothing re-renders after that until it
       ends. The agentPrompt already announces the submit for the optimistic echo — reuse that signal to
       start watching. Bound ONCE per form for the same reason the echo handler is: the form outlives every
       render, so binding per render would stack a handler (and an extra poll) for each one. *}
    if($form.length && !$form.data('cerbTranscriptPollBound')) {
        $form.data('cerbTranscriptPollBound', true);

        $form.on('cerb-agentprompt-submitted', function() {
            $form.find('[data-cerb-transcript-echo-key]').each(function() {
                if('function' === typeof this._cerbTranscriptStartPoll)
                    this._cerbTranscriptStartPoll();
            });
        });
    }
    {/if}

    if(window.CerbUI && CerbUI.AgentTranscript)
        CerbUI.AgentTranscript.enhance($prompt[0], undefined, {
            controls: {if 'toggle' == $view}true{else}false{/if},
            view: '{$view|escape:'javascript'}',
            layout: '{$layout|default:'interleaved'|escape:'javascript'}',
            thinking: '{$thinking|escape:'javascript'}',
            tools: '{$tools|escape:'javascript'}',
            expand: '{$expand|escape:'javascript'}'
        });

    // Land at the BOTTOM, not at the start of the newest turn. An agent running tools re-renders this on every
    // `on_tool:` call, and each new tool row appends below the fold — pinning to the top of the turn leaves the
    // view frozen while the most is happening, so it reads as stalled. (Scroll back up manually to re-read a
    // long reply; a Slack-style "jump to first unread" banner is the eventual answer.)
    //
    // Deferred a frame: enhance() rebuilds every turn (and defers its JSON editors), so measuring any sooner
    // measures a layout that no longer exists. The frame also puts us after panel.tpl's focus-first-focusable,
    // which scrolls its target into view — on an `on_tool:` re-render there's no prompt to focus, so that
    // target is a per-turn copy button near the top and we must have the last word.
    const pinToBottom = function() {
        const el = $prompt.get(0);

        if(!el)
            return;

        // The transcript container is its own scroll box (max-height:75vh; overflow:auto). Scroll ONLY inside
        // it, and only when its content actually overflows — never nudge the browser page. Inside an AgentPane
        // that cap is dropped, so this no-ops and the pane's own bottom-pin does the work.
        if(el.scrollHeight <= el.clientHeight)
            return;

        el.scrollTop = el.scrollHeight;
    };

    // Twice: once after layout, and again shortly after to catch late height changes (the deferred JSON
    // editors), which would otherwise leave us short of the bottom. Same reason CerbUI.AgentPane does it.
    requestAnimationFrame(pinToBottom);
    setTimeout(pinToBottom, 150);
});
</script>