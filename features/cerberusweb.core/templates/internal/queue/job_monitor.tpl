{* Shared queue-job monitor body. Used by both the card and profile-tab
   versions of the widget — the caller assigns `widget_type` of "card" or
   "profile" and the differences in DOM lookup, refresh ancestor, and AJAX
   parameter naming are resolved at Smarty compile time. (Named widget_type
   rather than context to avoid clashing with the heavily-overloaded $context
   used elsewhere — e.g. by the attachments list include below.) *}

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
    <span data-cerb-worker-count class="cerb-hidden" style="margin-left:0.75em;opacity:0.6;"></span>
</div>
{/if}

{if $queue_job->isDone() && $attachments}
<div data-cerb-attachments style="margin-top:0.75em;">
    <h2 style="margin:0 0 0.25em 0;">{'common.attachments'|devblocks_translate|capitalize}</h2>
    {include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_QUEUE_JOB}" context_id=$queue_job->id attachments=$attachments}
</div>
{/if}

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
    $widget.data('queueJobMonitorRefreshTimer', null);
    $widget.data('queueJobMonitorRetryTimer', null);
    $widget.data('queueJobMonitorFinalizeTimer', null);

    // Detach the prior generation's document-level visibility listener — $widget
    // persists across re-renders so the document handler isn't swept automatically.
    const prevVisListener = $widget.data('queueJobMonitorVisListener');
    if(prevVisListener) document.removeEventListener('visibilitychange', prevVisListener);

    const isCurrent = function() {
        return $widget.data('queueJobMonitorGeneration') === generation;
    };

    const MODE = '{$mode}';
    const POOL_SIZE = 2;
    const RETRY_DELAY_MS = 1500;
    const HIDDEN_SPAWN_DELAY_MS = 5000;
    const REFRESH_DEBOUNCE_MS = 100;
    const FINALIZE_POLL_START_MS = 500;
    const FINALIZE_POLL_MAX_MS = 5000;

    let activeWorkers = 0;
    // Terminal = DONE (2) or CANCELED (3). Either way, no more workers, no controls.
    let isTerminal = ({$queue_job->status_id} === 2 || {$queue_job->status_id} === 3);
    let isPaused = (MODE === 'process_paused');
    let isHidden = (document.visibilityState === 'hidden');
    let finalizePollDelayMs = FINALIZE_POLL_START_MS;
    const canProcess = (MODE !== 'view');

    const $button_refresh = $widget.find('button[data-cerb-button=refresh]');
    const $button_refresh_icon = $button_refresh.find('span.glyphicons-refresh');
    const $button_pause = $widget.find('button[data-cerb-button=pause-resume]');
    const $button_cancel = $widget.find('button[data-cerb-button=cancel]');
    const $progress_bar = $widget.find('div[data-cerb-progress-bar]');
    const $worker_count = $widget.find('span[data-cerb-worker-count]');

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

    const funcUpdateWorkerCount = function() {
        if(activeWorkers > 0) {
            $worker_count
                .html('<span class="glyphicons glyphicons-cogwheel"></span> ' + activeWorkers + ' / ' + POOL_SIZE)
                .removeClass('cerb-hidden');
        } else {
            $worker_count.addClass('cerb-hidden');
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

        activeWorkers++;
        funcUpdateWorkerCount();

        genericAjaxPost(funcBuildFormData('worker'), null, null, function(json) {
            if(!isCurrent()) return;
            activeWorkers--;
            funcUpdateWorkerCount();
            if(isTerminal || isPaused || !canProcess) return;

            const didWork = typeof json === 'object' && json.slot === true && json.processed > 0;
            const remaining = (typeof json === 'object' && typeof json.remaining === 'number') ? json.remaining : 0;
            const retryDelay = isHidden ? HIDDEN_SPAWN_DELAY_MS : RETRY_DELAY_MS;

            if(remaining === 0) {
                // No messages left — either we just emptied the queue or someone
                // else did. The job is finalizing (or already terminal); drop into
                // the backoff poll loop that watches status.
                funcPollWhileFinalizing();
                return;
            }

            funcRefreshProgress();

            if(!didWork) {
                // Slot denied or another worker grabbed the messages — retry shortly.
                const t = setTimeout(funcSpawnSingleWorker, retryDelay);
                $widget.data('queueJobMonitorRetryTimer', t);
                return;
            }

            if(isHidden) {
                // Keep one worker moving but at a calmer cadence so the background
                // tab doesn't hammer the server.
                const t = setTimeout(funcSpawnSingleWorker, retryDelay);
                $widget.data('queueJobMonitorRetryTimer', t);
            } else {
                const targetWorkers = Math.min(POOL_SIZE, remaining);
                while(activeWorkers < targetWorkers)
                    funcSpawnSingleWorker();
            }
        }, {
            error: function() {
                if(!isCurrent()) return;
                activeWorkers--;
                funcUpdateWorkerCount();
            }
        });
    };

    const funcOnVisibilityChange = function() {
        if(!isCurrent()) return;
        const wasHidden = isHidden;
        isHidden = (document.visibilityState === 'hidden');

        // Came back to a still-running job — kick off a worker if we coasted to
        // zero. An in-flight worker (if any) will fan out further on its callback.
        if(wasHidden && !isHidden && !isTerminal && !isPaused && canProcess && activeWorkers === 0)
            funcSpawnSingleWorker();
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
            if(!isPaused) funcSpawnSingleWorker();
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

    if(MODE === 'process' && !isTerminal) funcSpawnSingleWorker();
});
</script>
