{* Shared queue-job monitor body. Used by both the card and profile-tab
   versions of the widget — the caller assigns `widget_type` of "card" or
   "profile" and the differences in DOM lookup, refresh ancestor, and AJAX
   parameter naming are resolved at Smarty compile time. (Named widget_type
   rather than context to avoid clashing with the heavily-overloaded $context
   used elsewhere — e.g. by the attachments list include below.) *}

<div class="cerb-queue-monitor">

<div data-cerb-progress-bar>
    {include file="devblocks:cerberusweb.core::internal/queue/progress_bar.tpl"}
</div>

{if !$queue_job->isTerminal()}
<div data-cerb-buttons style="margin-top:0.5em;">
    {if $mode != 'view'}
    <button data-cerb-button="pause-resume" title="{if $mode == 'process_paused'}Resume{else}Pause{/if}"><span class="cerb-icons {if $mode == 'process_paused'}cerb-icon-play{else}cerb-icon-pause{/if}"></span> <span data-cerb-button-label>{if $mode == 'process_paused'}Resume{else}Pause{/if}</span></button>
    <button data-cerb-button="cancel" title="Cancel" class="{if $mode != 'process_paused'}cerb-hidden{/if}"><span class="cerb-icons cerb-icon-circle-remove"></span> Cancel</button>
    {/if}
    <button data-cerb-button="refresh"><span class="cerb-icons cerb-icon-refresh"></span> Refresh</button>

    {* Why the worker tiles are throttled. The queue_slot_N pool is shared with LLM agent turns and the
       cron, so this job can be stalled by work that isn't its own -- the dimmed tiles alone can't say
       that. Populated from the throttled worker response; hidden whenever nothing is throttled. *}
    <div data-cerb-slot-chip class="cerb-hidden" style="vertical-align:middle;margin-left:0.5em;"></div>
</div>

<div data-cerb-worker-cards class="cerb-worker-cards cerb-hidden">
    {section name=slot start=1 loop=$max_concurrency+1}
    <div data-cerb-worker-card="{$smarty.section.slot.index}" class="cerb-ui-tile cerb-ui-tile--block cerb-worker-tile cerb-u-opacity-50 cerb-hidden">
        <span class="cerb-ui-tile--icon" data-cerb-worker-card-icon><span class="cerb-icons cerb-icon-stopwatch"></span></span>
        <div class="cerb-ui-tile--text">
            <div class="cerb-ui-tile--kind">w{if $smarty.section.slot.index < 10}0{/if}{$smarty.section.slot.index}</div>
            <div class="cerb-ui-tile--body"><span data-cerb-worker-card-done>0</span> done · <span data-cerb-worker-card-rate>0.0</span>/s</div>
        </div>
    </div>
    {/section}
</div>
{/if}

{if $queue_job->isDone() && $attachments}
<div data-cerb-attachments style="margin-top:0.75em;">
    <h2 style="margin:0 0 0.25em 0;">{'common.output'|devblocks_translate|capitalize}</h2>
    {include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_QUEUE_JOB}" context_id=$queue_job->id attachments=$attachments}
</div>
{/if}

<div data-cerb-job-log-container>
    {include file="devblocks:cerberusweb.core::internal/queue/job_log.tpl" logs=$logs}
</div>

</div>{* /.cerb-queue-monitor *}

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
    {if $widget_type == 'card'}
    const $widget = $('#cardWidget' + '{$widget->getUniqueId($queue_job->id)}');
    {else}
    const $widget = $('#profileWidget' + '{$widget->id}');
    {/if}
    if(!$widget.length) return;

    {if $widget_type == 'card'}
    const $ancestor = genericAjaxPopupFind($widget);
    {else}
    const $ancestor = $widget.closest('.cerb-profile-layout');
    {/if}

    // Bind any peek triggers in this render (e.g. attachment downloads when done)
    $widget.find('.cerb-peek-trigger').cerbPeekTrigger();

    // Generation guard: when the framework refreshes the widget it does
    // $widget.html(newHtml), which replaces children but leaves $widget itself
    // (and its .data()) intact. Every closure in this render captures `generation`;
    // any callback that fires after a subsequent render bails via isCurrent().
    const generation = (Number($widget.data('queueJobMonitorGeneration')) || 0) + 1;
    $widget.data('queueJobMonitorGeneration', generation);

    // Cancel any pending timers from a prior generation
    const prevRefreshTimer = $widget.data('queueJobMonitorRefreshTimer');
    if(prevRefreshTimer) clearTimeout(prevRefreshTimer);
    const prevRetryTimer = $widget.data('queueJobMonitorRetryTimer');
    if(prevRetryTimer) clearTimeout(prevRetryTimer);
    const prevFinalizeTimer = $widget.data('queueJobMonitorFinalizeTimer');
    if(prevFinalizeTimer) clearTimeout(prevFinalizeTimer);
    const prevRampTimer = $widget.data('queueJobMonitorRampTimer');
    if(prevRampTimer) clearTimeout(prevRampTimer);
    $widget.data('queueJobMonitorRefreshTimer', null);
    $widget.data('queueJobMonitorRetryTimer', null);
    $widget.data('queueJobMonitorFinalizeTimer', null);
    $widget.data('queueJobMonitorRampTimer', null);

    // Detach the prior generation's document-level visibility listener — $widget
    // persists across re-renders so the document handler isn't swept automatically.
    const prevVisListener = $widget.data('queueJobMonitorVisListener');
    if(prevVisListener) document.removeEventListener('visibilitychange', prevVisListener);

    const isCurrent = function() {
        return $widget.data('queueJobMonitorGeneration') === generation;
    };

    const MODE = '{$mode}';
    const MAX_CONCURRENCY = {$max_concurrency};
    const RETRY_DELAY_MS = 1500;
    const HIDDEN_SPAWN_DELAY_MS = 5000;
    const REFRESH_DEBOUNCE_MS = 100;
    const FINALIZE_POLL_START_MS = 500;
    const FINALIZE_POLL_MAX_MS = 5000;
    // Time-based ramp: spawn the next worker every N ms regardless of whether
    // the prior worker has returned. Decouples ramp visibility from worker
    // batch duration — a 10s worker doesn't block the ramp from animating.
    const RAMP_INTERVAL_MS = 2000;
    // Losing the concurrency slot is NOT the same as losing a claim race: the pool is full, so no
    // amount of spawning can win one, and a throttled response returns in milliseconds. Backing off
    // on RETRY_DELAY_MS per worker plus a re-armed ramp is a hot loop against a shared resource --
    // it got much easier to hit once the pool became license-derived and shared with agent turns.
    const THROTTLE_BACKOFF_START_MS = 2000;
    const THROTTLE_BACKOFF_MAX_MS = 20000;

    // Semantic colors for the progress Distbar, in segment order: done, error, inflight,
    // retrying, available — matched 1:1 to the <span> order in progress_bar.tpl.
    const DISTBAR_PALETTE = [
        'var(--cerb-color-progress-done)',
        'var(--cerb-color-progress-failed)',
        'var(--cerb-color-progress-inflight)',
        'var(--cerb-color-progress-scheduled)',
        'var(--cerb-color-progress-available)',
    ];

    // Slot model: each slot 1..MAX_CONCURRENCY has a tile in the DOM, hidden
    // until first activated. `running` flips while a worker AJAX is in flight;
    // doneTotal + activeMs accumulate across batches so we can compute a rate.
    // Tile icon color is owned by a rainbow CerbUI.colorScale keyed by worker number.

    const slotState = {};
    for(let i = 1; i <= MAX_CONCURRENCY; i++) {
        slotState[i] = {
            everActive: false,
            running: false,
            throttled: false,
            doneTotal: 0,
            activeMs: 0,
            startedAt: null
        };
    }
    const slotsActive = new Set();

    // Terminal = DONE (2) or CANCELED (3). Either way, no more workers, no controls.
    let isTerminal = ({$queue_job->status_id} === 2 || {$queue_job->status_id} === 3);
    let isPaused = (MODE === 'process_paused');
    let isHidden = (document.visibilityState === 'hidden');
    let finalizePollDelayMs = FINALIZE_POLL_START_MS;
    // Updated from every worker response that includes a `ready` count (messages
    // claimable right now). Starts at Infinity so the ramp can spawn freely until
    // we have real data. Retry-deferred (`scheduled`) messages are deliberately
    // excluded so the pool backs off instead of spinning during a backoff window.
    let lastKnownReady = Infinity;
    // Last pool occupancy seen from a throttled worker response. Kept across cycles: only the
    // throttled path reports it, so clearing on every other response would strobe the chip.
    let lastSlotUsage = { used: 0, total: 0 };
    // Widget-level, NOT per-slot: an individual slot flips throttled -> running -> throttled every
    // cycle, so driving UI off slotState[i] strobes. This holds until a worker actually gets a slot.
    let isThrottled = false;
    let throttleBackoffMs = THROTTLE_BACKOFF_START_MS;
    const canProcess = (MODE !== 'view');

    const $button_refresh = $widget.find('button[data-cerb-button=refresh]');
    const $button_refresh_icon = $button_refresh.find('span.cerb-icon-refresh');
    const $button_pause = $widget.find('button[data-cerb-button=pause-resume]');
    const $button_cancel = $widget.find('button[data-cerb-button=cancel]');
    const $progress_bar = $widget.find('div[data-cerb-progress-bar]');

    const funcBuildFormData = function(verb) {
        const fd = new FormData();
        fd.set('c', 'profiles');
        {if $widget_type == 'card'}
        fd.set('a', 'invoke');
        fd.set('module', 'card_widget');
        fd.set('action', 'invokeWidget');
        fd.set('invoke_action', verb);
        {else}
        fd.set('a', 'invokeWidget');
        fd.set('action', verb);
        {/if}
        fd.set('widget_id', '{$widget->id}');
        fd.set('card_context_id', '{$queue_job->id}');
        return fd;
    };

    // The `worker` verb moved off the widget extensions onto /queue/drainJob, so it needs no invoke
    // plumbing and no card/profile fork -- just the two ids and which widget kind to load them from.
    // The other verbs (refresh, pause, resume, cancel) stay on ajax.php above.
    const funcBuildDrainFormData = function() {
        const fd = new FormData();
        fd.set('widget_id', '{$widget->id}');
        fd.set('widget_type', '{$widget_type}');
        fd.set('card_context_id', '{$queue_job->id}');
        return fd;
    };

    const funcAcquireSlot = function() {
        for(let i = 1; i <= MAX_CONCURRENCY; i++) {
            if(!slotsActive.has(i)) {
                const s = slotState[i];
                slotsActive.add(i);
                s.everActive = true;
                s.running = true;
                s.throttled = false;
                s.startedAt = Date.now();
                return i;
            }
        }
        return null;
    };

    const funcReleaseSlot = function(slot, processed, throttled) {
        const s = slotState[slot];
        if(s.startedAt) {
            s.activeMs += Date.now() - s.startedAt;
            s.startedAt = null;
        }
        if(processed > 0)
            s.doneTotal += processed;
        s.running = false;
        s.throttled = !!throttled;
        slotsActive.delete(slot);
    };

    const $worker_cards_container = $widget.find('[data-cerb-worker-cards]');

    // Color each worker tile's icon by its number from a rainbow ordinal scale (w01, w02, …)
    const workerScale = (window.CerbUI && CerbUI.colorScale) ? CerbUI.colorScale('rainbow') : null;
    if(workerScale) {
        for(let i = 1; i <= MAX_CONCURRENCY; i++) {
            const ico = $widget.find('[data-cerb-worker-card="' + i + '"] .cerb-ui-tile--icon')[0];
            if(ico) ico.style.backgroundColor = workerScale.color('w' + i);
        }
    }

    const funcRenderWorkerCards = function() {
        const anyActive = Object.values(slotState).some(s => s.everActive);
        $worker_cards_container.toggleClass('cerb-hidden', !anyActive);

        for(let i = 1; i <= MAX_CONCURRENCY; i++) {
            const s = slotState[i];
            const $card = $widget.find('[data-cerb-worker-card="' + i + '"]');

            if(!s.everActive) {
                $card.addClass('cerb-hidden');
                continue;
            }

            const isActive = s.running || s.throttled;
            $card.removeClass('cerb-hidden')
                 .toggleClass('cerb-worker-tile--throttled', !s.running && s.throttled)
                 .toggleClass('cerb-u-opacity-50', !isActive);   // dim when idle; full when active

            // Icon: spinner (rotating) while a batch is in flight, stopwatch when idle/throttled. A
            // THROTTLED tile pulses -- it's waiting on a slot, which is live state, not the same as an
            // idle tile that simply has no work.
            const iconClass = s.running
                ? 'cerb-icon-spinner cerb-u-anim-spin'
                : (s.throttled ? 'cerb-icon-stopwatch cerb-u-anim-pulse' : 'cerb-icon-stopwatch');
            $card.find('[data-cerb-worker-card-icon] > .cerb-icons')
                 .attr('class', 'cerb-icons ' + iconClass);

            $card.find('[data-cerb-worker-card-done]').text(s.doneTotal.toLocaleString());

            const rate = s.activeMs > 0 ? (s.doneTotal / (s.activeMs / 1000)) : 0;
            $card.find('[data-cerb-worker-card-rate]').text(rate.toFixed(1));
        }
    };

    {include file="devblocks:cerberusweb.core::internal/queue/_slot_chip.tpl"}

    // Shown while the widget is in its throttled state AND the pool we last read was saturated.
    // Keyed off `isThrottled` rather than slotState[] on purpose: a single slot flips
    // throttled -> running -> throttled every cycle, which made the chip strobe.
    //
    // Content comes from the shared partial above; this host only decides whether the element is
    // visible. Passing a zeroed pool is how we clear it -- the partial renders nothing unless the
    // pool is saturated, and reports back whether it drew anything.
    const funcRenderSlotChip = function() {
        const $chip = $widget.find('[data-cerb-slot-chip]');

        if(!$chip.length)
            return;

        const shown = isThrottled
            ? renderSlotChip($chip[0], lastSlotUsage.used, lastSlotUsage.total, 0)
            : renderSlotChip($chip[0], 0, 0, 0);

        $chip.toggleClass('cerb-hidden', !shown);
    };

    const funcMarkTerminated = function() {
        if(isTerminal) return;
        isTerminal = true;

        // Re-render the whole widget so the post-finalization $attachments block
        // (download link for export jobs) or canceled state is reflected.
        $widget.find('div[data-cerb-buttons]').hide();
        if($ancestor && $ancestor.length)
            $ancestor.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
    };

    // (Re)build the progress Distbar from the freshly-rendered markup — it draws its own
    // legend. Must run after each $progress_bar.html() since Distbar doesn't auto-init and
    // the prior instance (and its generated legend) is discarded with the replaced DOM.
    const funcRenderDistbar = function() {
        if(!(window.CerbUI && CerbUI.Distbar)) return;
        const el = $progress_bar.find('.cerb-ui-distbar')[0];
        if(el) new CerbUI.Distbar(el, { legend: true, hideZeros: true, palette: DISTBAR_PALETTE });
    };

    const funcRefreshProgress = function() {
        if(!isCurrent()) return;

        const prev = $widget.data('queueJobMonitorRefreshTimer');
        if(prev) clearTimeout(prev);

        const t = setTimeout(function() {
            if(!isCurrent()) return;

            genericAjaxPost(funcBuildFormData('refresh'), null, null, function(json) {
                if(!isCurrent()) return;
                if(typeof json !== 'object') return;

                // Terminal status — DONE (2) or CANCELED (3). Skip the partial paint
                // since we're about to re-render the whole widget.
                if(json.hasOwnProperty('job_status') && (json.job_status === 2 || json.job_status === 3)) {
                    funcMarkTerminated();
                    return;
                }

                if(json.hasOwnProperty('progress_html')) {
                    $progress_bar.html(json.progress_html);
                    funcRenderDistbar();
                }

                if(json.hasOwnProperty('log_html'))
                    $widget.find('[data-cerb-job-log-container]').html(json.log_html);
            });
        }, REFRESH_DEBOUNCE_MS);

        $widget.data('queueJobMonitorRefreshTimer', t);
    };

    // Backoff poll while the job is waiting for its completion hook to finish.
    // No worker requests fire here — only progress refreshes — because there's no
    // message work left to do; we're just watching for status to flip to terminal.
    const funcPollWhileFinalizing = function() {
        if(!isCurrent() || isTerminal) return;

        const prev = $widget.data('queueJobMonitorFinalizeTimer');
        if(prev) clearTimeout(prev);

        funcRefreshProgress();

        const t = setTimeout(function() {
            finalizePollDelayMs = Math.min(finalizePollDelayMs * 2, FINALIZE_POLL_MAX_MS);
            funcPollWhileFinalizing();
        }, finalizePollDelayMs);

        $widget.data('queueJobMonitorFinalizeTimer', t);
    };

    // A single deferred probe. One timer slot for the whole widget: without this, every worker in
    // the pool schedules its own and the "back off" multiplies by MAX_CONCURRENCY.
    const funcScheduleProbe = function(delay) {
        const prev = $widget.data('queueJobMonitorRetryTimer');
        if(prev) clearTimeout(prev);
        const t = setTimeout(funcSpawnSingleWorker, delay);
        $widget.data('queueJobMonitorRetryTimer', t);
    };

    // ONE handler for BOTH refusal layers: the app answers 529 when the concurrency pool is
    // full, nginx answers 529 when the background FPM pool has no child free. Same meaning, same
    // payload shape, so the same handling -- and keeping it in one place is what stops the two
    // from drifting apart. `json` may be missing keys (nginx's body carries no counters), in which
    // case the last known readings stand rather than being zeroed.
    const funcHandleThrottled = function(slot, json, retryAfterSecs) {
        const isObj = (typeof json === 'object' && json !== null);

        // Pace on `ready` only -- deferred retries must not keep the pool hot.
        if(isObj && typeof json.ready === 'number')
            lastKnownReady = json.ready;

        // Only a throttled response carries occupancy; keep the last reading otherwise.
        if(isObj && typeof json.slots_total === 'number') {
            lastSlotUsage = {
                used: (typeof json.slots_used === 'number') ? json.slots_used : 0,
                total: json.slots_total
            };
        }

        funcReleaseSlot(slot, 0, true);
        funcRenderWorkerCards();

        if(!isCurrent()) return;
        if(isTerminal || isPaused || !canProcess) return;

        // Spawning again can't help -- every slot is held, by this job's own workers or by agent
        // turns and the cron sharing the pool. Take a single probe on an exponential backoff and
        // let the ramp stand down until it lands.
        isThrottled = true;
        funcRenderSlotChip();
        funcRefreshProgress();

        // Retry-After is a MINIMUM, not an instruction: honour it, but never let it SHORTEN our
        // own backoff, which knows how long we have been waiting where a flat header cannot.
        const delay = Math.max(throttleBackoffMs, (retryAfterSecs > 0 ? retryAfterSecs * 1000 : 0));
        throttleBackoffMs = Math.min(throttleBackoffMs * 2, THROTTLE_BACKOFF_MAX_MS);
        funcScheduleProbe(delay);
    };

    const funcSpawnSingleWorker = function() {
        if(!isCurrent()) return;
        if(isTerminal || isPaused || !canProcess) return;
        if(slotsActive.size >= (isHidden ? 1 : MAX_CONCURRENCY)) return;

        const slot = funcAcquireSlot();
        if(slot === null) return;
        funcRenderWorkerCards();

        genericAjaxPost(funcBuildDrainFormData(), null, null, function(json) {
            const isObj = (typeof json === 'object' && json !== null);
            const processed = (isObj && typeof json.processed === 'number') ? json.processed : 0;
            const ready = (isObj && typeof json.ready === 'number') ? json.ready : 0;
            const scheduled = (isObj && typeof json.scheduled === 'number') ? json.scheduled : 0;
            const inflight = (isObj && typeof json.inflight === 'number') ? json.inflight : 0;
            const nextAvailableAt = (isObj && typeof json.next_available_at === 'number') ? json.next_available_at : 0;
            const gotSlot = (isObj && json.slot === true);
            // Throttled (no concurrency slot) but there's still work to come back for.
            const wasThrottled = (isObj && json.slot === false && (ready > 0 || scheduled > 0 || inflight > 0));

            // DEFENSIVE. The app answers 529 for a throttled drain, so a 200-shaped refusal no
            // longer reaches here -- but anything that rewrites the status (a proxy, a future
            // caller) would otherwise silently lose the backoff, which is the failure this whole
            // branch exists to prevent. Costs nothing to keep, and routes to the same handler.
            if(wasThrottled) {
                funcHandleThrottled(slot, json, 0);
                return;
            }

            // Update pacing data BEFORE rendering so the ramp scheduler sees fresh
            // data. We pace on `ready` only — deferred retries must not keep the pool hot.
            if(isObj && typeof json.ready === 'number')
                lastKnownReady = ready;

            // Only the throttled response carries occupancy; keep the last reading otherwise.
            if(isObj && typeof json.slots_total === 'number') {
                lastSlotUsage = {
                    used: (typeof json.slots_used === 'number') ? json.slots_used : 0,
                    total: json.slots_total
                };
            }

            funcReleaseSlot(slot, processed, wasThrottled);
            funcRenderWorkerCards();

            if(!isCurrent()) return;
            if(isTerminal || isPaused || !canProcess) return;

            // Got a slot (or there's nothing left to do): the pool is reachable again.
            isThrottled = false;
            throttleBackoffMs = THROTTLE_BACKOFF_START_MS;
            funcRenderSlotChip();

            // Nothing claimable right now.
            if(ready === 0) {
                if(scheduled > 0 || inflight > 0) {
                    // Failed messages are waiting out their retry backoff, or other
                    // workers are still draining inflight work. Refresh the bar and
                    // schedule a SINGLE probe instead of busy-looping the endpoint.
                    funcRefreshProgress();

                    let delay;
                    if(scheduled > 0 && nextAvailableAt > 0) {
                        // Resume right when the soonest retry window opens.
                        delay = Math.min(Math.max(nextAvailableAt * 1000 - Date.now(), RETRY_DELAY_MS), FINALIZE_POLL_MAX_MS);
                    } else {
                        // Inflight draining elsewhere — exponential backoff.
                        finalizePollDelayMs = Math.min(finalizePollDelayMs * 2, FINALIZE_POLL_MAX_MS);
                        delay = finalizePollDelayMs;
                    }

                    const prevTimer = $widget.data('queueJobMonitorRetryTimer');
                    if(prevTimer) clearTimeout(prevTimer);
                    const t = setTimeout(funcSpawnSingleWorker, delay);
                    $widget.data('queueJobMonitorRetryTimer', t);
                    return;
                }

                // No ready, scheduled, or inflight work — the job is finalizing (or
                // already terminal); drop into the backoff poll that watches status.
                funcPollWhileFinalizing();
                return;
            }

            // We have claimable work — reset the backoff and keep the pool flowing.
            finalizePollDelayMs = FINALIZE_POLL_START_MS;
            funcRefreshProgress();

            // Pool maintenance: spawn one replacement so the existing pool size is
            // preserved. The ramp scheduler is what grows it further — this just
            // keeps work flowing at the current level.
            const cap = isHidden ? 1 : MAX_CONCURRENCY;
            const target = Math.min(cap, lastKnownReady);
            if(slotsActive.size < target) {
                // Zero delay only when we actually drained work on a real slot;
                // otherwise throttle so a no-op response can't busy-loop.
                const retryDelay = ((gotSlot && processed > 0) ? 0 : (isHidden ? HIDDEN_SPAWN_DELAY_MS : RETRY_DELAY_MS));
                if(retryDelay > 0) {
                    const t = setTimeout(funcSpawnSingleWorker, retryDelay);
                    $widget.data('queueJobMonitorRetryTimer', t);
                } else {
                    funcSpawnSingleWorker();
                }
            }

            // Re-arm the ramp in case it had stopped (e.g. we were at cap; now
            // a worker has finished and we may have room).
            funcStartRamp();
        }, {
            // `?_log=` is for the FPM access log only -- every worker in the pool posts the same
            // path with an identical body, so the job id is what tells one job's drains from
            // another's when reading durations. Never read server-side: `_log` must stay a
            // decoration that dispatch does not depend on.
            path: 'queue/drainJob?_log={$queue_job->id}',
            // `fail` REPLACES the default failure UI (`error` would run it first and clear the
            // user's alerts). Two layers can refuse a drain and the client must treat them the
            // same: the app answers 200 + `slot:false` when the concurrency pool is full, and
            // nginx answers 529 when the background FPM pool has no child free. Only the status
            // differs -- both mean "no capacity, come back later", so a 529 is routed into the
            // exact same single-probe backoff rather than looking like an error.
            fail: function(err) {
                // 529 from EITHER layer. jQuery parses the body into responseJSON because both
                // emitters send `Content-Type: application/json`, so the app's counters survive
                // the move from the success path to here.
                if(529 === err.status)
                    return funcHandleThrottled(slot, err.responseJSON, parseInt(err.getResponseHeader('Retry-After'), 10));

                funcReleaseSlot(slot, 0, false);
                funcRenderWorkerCards();
                Devblocks.ajaxFail(err);
            }
        });
    };

    // Time-based ramp scheduler: spawns workers on a fixed interval up to the
    // cap. Independent of worker callbacks — workers can run for 10s and the
    // ramp still animates the cards into view every RAMP_INTERVAL_MS.
    const funcRamp = function() {
        $widget.data('queueJobMonitorRampTimer', null);

        if(!isCurrent() || isTerminal || isPaused || !canProcess) return;

        // Every slot is held. Growing the pool can only produce more no-op requests; the throttle
        // backoff owns the retry, and it re-arms the ramp once a worker gets in.
        if(isThrottled) return;

        const cap = isHidden ? 1 : MAX_CONCURRENCY;
        const target = Math.min(cap, lastKnownReady);

        if(slotsActive.size < target) {
            funcSpawnSingleWorker();
            // Keep stepping until we hit the cap or run out of work
            if(slotsActive.size < target) {
                const t = setTimeout(funcRamp, RAMP_INTERVAL_MS);
                $widget.data('queueJobMonitorRampTimer', t);
            }
        }
    };

    const funcStartRamp = function() {
        if(!isCurrent() || isTerminal || isPaused || !canProcess) return;
        if(isThrottled) return;
        if($widget.data('queueJobMonitorRampTimer')) return;
        const t = setTimeout(funcRamp, RAMP_INTERVAL_MS);
        $widget.data('queueJobMonitorRampTimer', t);
    };

    const funcOnVisibilityChange = function() {
        if(!isCurrent()) return;
        const wasHidden = isHidden;
        isHidden = (document.visibilityState === 'hidden');

        // Came back to a still-running job — kick off a worker if we coasted to
        // zero, and re-arm the ramp so the pool can grow back to its non-hidden cap.
        if(wasHidden && !isHidden && !isTerminal && !isPaused && canProcess) {
            if(slotsActive.size === 0)
                funcSpawnSingleWorker();
            funcStartRamp();
        }
    };

    document.addEventListener('visibilitychange', funcOnVisibilityChange);
    $widget.data('queueJobMonitorVisListener', funcOnVisibilityChange);

    const funcTogglePause = function() {
        if(!isCurrent()) return;

        genericAjaxPost(funcBuildFormData(isPaused ? 'resume' : 'pause'), null, null, function(json) {
            if(!isCurrent()) return;
            if(typeof json !== 'object' || !json.hasOwnProperty('status_id')) return;

            isPaused = (json.status_id === 1);
            const label = isPaused ? 'Resume' : 'Pause';
            $button_pause
                .attr('title', label)
                .find('span[data-cerb-button-label]').text(label).end()
                .find('span.cerb-icons')
                .toggleClass('cerb-icon-pause', !isPaused)
                .toggleClass('cerb-icon-play', isPaused);
            $button_cancel.toggleClass('cerb-hidden', !isPaused);
            if(!isPaused) {
                funcSpawnSingleWorker();
                funcStartRamp();
            }
        });
    };

    const funcCancel = function() {
        if(!isCurrent()) return;

        CerbUI.Confirm.open({
            title: 'Cancel queue job',
            body: 'Cancel this queue job? Remaining work will be discarded.',
            onConfirm: function() {
                if(!isCurrent()) return;

                genericAjaxPost(funcBuildFormData('cancel'), null, null, function(json) {
                    if(!isCurrent()) return;
                    if(typeof json !== 'object' || !json.hasOwnProperty('status_id')) return;
                    funcMarkTerminated();
                });
            }
        });
    };

    const funcOnRefreshClick = function() {
        // Visible click feedback — without it a fast response can hide the fact
        // that anything happened. The button is disabled for the spin duration
        // so the click can't be spammed; the AJAX itself stays async underneath.
        $button_refresh.prop('disabled', true);
        $button_refresh_icon.css({ transition: 'transform 0.5s ease', transform: 'rotate(360deg)' });
        setTimeout(function() {
            $button_refresh_icon.css({ transition: 'none', transform: '' });
            $button_refresh.prop('disabled', false);
        }, 500);

        funcRefreshProgress();
    };

    $button_refresh.on('click', funcOnRefreshClick);
    $button_pause.on('click', funcTogglePause);
    $button_cancel.on('click', funcCancel);

    // Draw the progress bar on first render (every mode, incl. view/terminal).
    funcRenderDistbar();

    if(MODE === 'process' && !isTerminal) {
        funcSpawnSingleWorker();
        funcStartRamp();
    }
});
</script>
