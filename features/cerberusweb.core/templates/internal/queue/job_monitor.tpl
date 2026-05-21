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
    <button data-cerb-button="pause-resume" title="{if $mode == 'process_paused'}Resume{else}Pause{/if}"><span class="glyphicons {if $mode == 'process_paused'}glyphicons-play{else}glyphicons-pause{/if}"></span> <span data-cerb-button-label>{if $mode == 'process_paused'}Resume{else}Pause{/if}</span></button>
    <button data-cerb-button="cancel" title="Cancel" class="{if $mode != 'process_paused'}cerb-hidden{/if}"><span class="glyphicons glyphicons-circle-remove"></span> Cancel</button>
    {/if}
    <button data-cerb-button="refresh"><span class="glyphicons glyphicons-refresh"></span> Refresh</button>
</div>

<div data-cerb-worker-cards class="cerb-worker-cards cerb-hidden">
    {section name=slot start=1 loop=$max_concurrency+1}
    {$palette_idx = ($smarty.section.slot.index - 1) % 10 + 1}
    <div data-cerb-worker-card="{$smarty.section.slot.index}" class="cerb-worker-card cerb-worker-card--slot-{$palette_idx} cerb-hidden">
        <div class="cerb-worker-card--header">
            <span class="cerb-worker-card--dot"></span>
            <span class="cerb-worker-card--label">w-{if $smarty.section.slot.index < 10}0{/if}{$smarty.section.slot.index}</span>
            <span class="cerb-worker-card--badge" data-cerb-worker-card-badge>IDLE</span>
        </div>
        <div class="cerb-worker-card--stats"><span data-cerb-worker-card-done>0</span> done · <span data-cerb-worker-card-rate>0.0</span>/s</div>
        <div class="cerb-worker-card--sparkline" data-cerb-worker-card-sparkline></div>
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

    // Slot model: each slot 1..MAX_CONCURRENCY has a card in the DOM, hidden
    // until first activated. `running` flips while a worker AJAX is in flight;
    // doneTotal + activeMs accumulate across batches so we can compute a rate.
    // `batches` is a sliding window of recent batch sizes for the sparkline.
    // Slot color is owned by CSS — the template assigns .cerb-worker-card--slot-N
    // and the theme defines per-mode palette tokens.
    const SPARKLINE_BARS = 16;

    const slotState = {};
    for(let i = 1; i <= MAX_CONCURRENCY; i++) {
        slotState[i] = {
            everActive: false,
            running: false,
            throttled: false,
            doneTotal: 0,
            activeMs: 0,
            startedAt: null,
            batches: []
        };
    }
    const slotsActive = new Set();

    // Terminal = DONE (2) or CANCELED (3). Either way, no more workers, no controls.
    let isTerminal = ({$queue_job->status_id} === 2 || {$queue_job->status_id} === 3);
    let isPaused = (MODE === 'process_paused');
    let isHidden = (document.visibilityState === 'hidden');
    let finalizePollDelayMs = FINALIZE_POLL_START_MS;
    // Updated from every worker response that includes a `remaining` field.
    // Starts at Infinity so the ramp can spawn freely until we have real data.
    let lastKnownRemaining = Infinity;
    const canProcess = (MODE !== 'view');

    const $button_refresh = $widget.find('button[data-cerb-button=refresh]');
    const $button_refresh_icon = $button_refresh.find('span.glyphicons-refresh');
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
        if(processed > 0) {
            s.doneTotal += processed;
            s.batches.push(processed);
            if(s.batches.length > SPARKLINE_BARS)
                s.batches = s.batches.slice(-SPARKLINE_BARS);
        }
        s.running = false;
        s.throttled = !!throttled;
        slotsActive.delete(slot);
    };

    const $worker_cards_container = $widget.find('[data-cerb-worker-cards]');

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

            $card.removeClass('cerb-hidden cerb-worker-card--running cerb-worker-card--throttled')
                 .toggleClass('cerb-worker-card--running', s.running)
                 .toggleClass('cerb-worker-card--throttled', !s.running && s.throttled);

            const badgeText = s.running ? 'RUNNING' : (s.throttled ? 'THROTTLED' : 'IDLE');
            $card.find('[data-cerb-worker-card-badge]').text(badgeText);
            $card.find('[data-cerb-worker-card-done]').text(s.doneTotal.toLocaleString());

            const rate = s.activeMs > 0 ? (s.doneTotal / (s.activeMs / 1000)) : 0;
            $card.find('[data-cerb-worker-card-rate]').text(rate.toFixed(1));

            // Sparkline: simple flex row of bars whose heights map to each batch's
            // processed count, normalized against this slot's recent max.
            const $sparkline = $card.find('[data-cerb-worker-card-sparkline]');
            const maxBatch = Math.max.apply(null, s.batches.length ? s.batches : [1]);
            const bars = s.batches.map(function(b) {
                const pct = Math.max(8, Math.round((b / maxBatch) * 100));
                return '<span style="height:' + pct + '%"></span>';
            }).join('');
            $sparkline.html(bars);
        }
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

                if(json.hasOwnProperty('progress_html'))
                    $progress_bar.html(json.progress_html);

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

    const funcSpawnSingleWorker = function() {
        if(!isCurrent()) return;
        if(isTerminal || isPaused || !canProcess) return;
        if(slotsActive.size >= (isHidden ? 1 : MAX_CONCURRENCY)) return;

        const slot = funcAcquireSlot();
        if(slot === null) return;
        funcRenderWorkerCards();

        genericAjaxPost(funcBuildFormData('worker'), null, null, function(json) {
            const processed = (typeof json === 'object' && typeof json.processed === 'number') ? json.processed : 0;
            const remaining = (typeof json === 'object' && typeof json.remaining === 'number') ? json.remaining : 0;
            const gotSlot = (typeof json === 'object' && json.slot === true);
            const wasThrottled = (typeof json === 'object' && json.slot === false && remaining > 0);

            // Update remaining BEFORE rendering so the ramp scheduler sees fresh data.
            if(typeof json === 'object' && typeof json.remaining === 'number')
                lastKnownRemaining = remaining;

            funcReleaseSlot(slot, processed, wasThrottled);
            funcRenderWorkerCards();

            if(!isCurrent()) return;
            if(isTerminal || isPaused || !canProcess) return;

            if(remaining === 0) {
                // No messages left — the job is finalizing (or already terminal);
                // drop into the backoff poll loop that watches status.
                funcPollWhileFinalizing();
                return;
            }

            funcRefreshProgress();

            // Pool maintenance: spawn one replacement so the existing pool size is
            // preserved. The ramp scheduler is what grows it further — this just
            // keeps work flowing at the current level.
            const cap = isHidden ? 1 : MAX_CONCURRENCY;
            const target = Math.min(cap, lastKnownRemaining);
            if(slotsActive.size < target) {
                const retryDelay = (gotSlot ? 0 : (isHidden ? HIDDEN_SPAWN_DELAY_MS : RETRY_DELAY_MS));
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
            error: function() {
                funcReleaseSlot(slot, 0, false);
                funcRenderWorkerCards();
            }
        });
    };

    // Time-based ramp scheduler: spawns workers on a fixed interval up to the
    // cap. Independent of worker callbacks — workers can run for 10s and the
    // ramp still animates the cards into view every RAMP_INTERVAL_MS.
    const funcRamp = function() {
        $widget.data('queueJobMonitorRampTimer', null);

        if(!isCurrent() || isTerminal || isPaused || !canProcess) return;

        const cap = isHidden ? 1 : MAX_CONCURRENCY;
        const target = Math.min(cap, lastKnownRemaining);

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
                .find('span.glyphicons')
                .toggleClass('glyphicons-pause', !isPaused)
                .toggleClass('glyphicons-play', isPaused);
            $button_cancel.toggleClass('cerb-hidden', !isPaused);
            if(!isPaused) {
                funcSpawnSingleWorker();
                funcStartRamp();
            }
        });
    };

    const funcCancel = function() {
        if(!isCurrent()) return;

        confirmPopup(
            'Cancel queue job',
            'Cancel this queue job? Remaining work will be discarded.',
            function() {
                if(!isCurrent()) return;

                genericAjaxPost(funcBuildFormData('cancel'), null, null, function(json) {
                    if(!isCurrent()) return;
                    if(typeof json !== 'object' || !json.hasOwnProperty('status_id')) return;
                    funcMarkTerminated();
                });
            }
        );
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

    if(MODE === 'process' && !isTerminal) {
        funcSpawnSingleWorker();
        funcStartRamp();
    }
});
</script>
