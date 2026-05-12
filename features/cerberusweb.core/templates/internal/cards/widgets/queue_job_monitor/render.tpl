<div data-cerb-progress-bar>
    {include file="devblocks:cerberusweb.core::internal/queue/progress_bar.tpl"}
</div>

{if !$queue_job->isDone()}
<div data-cerb-buttons style="margin-top:0.5em;">
    {if $mode != 'view'}
    <button data-cerb-button="pause-resume" title="{if $mode == 'process_paused'}Resume{else}Pause{/if}"><span class="glyphicons {if $mode == 'process_paused'}glyphicons-play{else}glyphicons-pause{/if}"></span> <span data-cerb-button-label>{if $mode == 'process_paused'}Resume{else}Pause{/if}</span></button>
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
    const $widget = $('#cardWidget' + '{$widget->getUniqueId($queue_job->id)}');
    if(!$widget.length) return;

    const $popup = genericAjaxPopupFind($widget);

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
    $widget.data('queueJobMonitorRefreshTimer', null);
    $widget.data('queueJobMonitorRetryTimer', null);

    const isCurrent = function() {
        return $widget.data('queueJobMonitorGeneration') === generation;
    };

    const MODE = '{$mode}';
    const POOL_SIZE = 2;
    const RETRY_DELAY_MS = 1500;
    const REFRESH_DEBOUNCE_MS = 100;

    let activeWorkers = 0;
    let isJobDone = ({$queue_job->status_id} === 2);
    let isPaused = (MODE === 'process_paused');
    const canProcess = (MODE !== 'view');

    const $button_refresh = $widget.find('button[data-cerb-button=refresh]');
    const $button_pause = $widget.find('button[data-cerb-button=pause-resume]');
    const $progress_bar = $widget.find('div[data-cerb-progress-bar]');
    const $worker_count = $widget.find('span[data-cerb-worker-count]');

    const funcUpdateWorkerCount = function() {
        if(activeWorkers > 0) {
            $worker_count
                .html('<span class="glyphicons glyphicons-cogwheel"></span> ' + activeWorkers + ' / ' + POOL_SIZE)
                .removeClass('cerb-hidden');
        } else {
            $worker_count.addClass('cerb-hidden');
        }
    };

    const funcMarkDone = function() {
        if(isJobDone) return;
        isJobDone = true;

        // In-place update only: hide the controls. The progress bar's final 100% state
        // is already painted by the refresh action's response that called us. We deliberately
        // do NOT trigger cerb-widget-refresh / renderWidget — a partial update for just this
        // widget is enough; no need to re-render the whole card.
        $widget.find('div[data-cerb-buttons]').hide();
    };

    const funcRefreshProgress = function() {
        if(!isCurrent()) return;

        const prev = $widget.data('queueJobMonitorRefreshTimer');
        if(prev) clearTimeout(prev);

        const t = setTimeout(function() {
            if(!isCurrent()) return;

            const formData = new FormData();
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'card_widget');
            formData.set('action', 'invokeWidget');
            formData.set('widget_id', '{$widget->id}');
            formData.set('invoke_action', 'refresh');
            formData.set('card_context_id', '{$queue_job->id}');

            genericAjaxPost(formData, null, null, function(json) {
                if(!isCurrent()) return;
                if(typeof json !== 'object') return;
                if(json.hasOwnProperty('progress_html'))
                    $progress_bar.html(json.progress_html);
                if(json.hasOwnProperty('job_status') && json.job_status === 2)
                    funcMarkDone();
            });
        }, REFRESH_DEBOUNCE_MS);

        $widget.data('queueJobMonitorRefreshTimer', t);
    };

    const funcSpawnSingleWorker = function() {
        if(!isCurrent()) return;
        if(isJobDone || isPaused || !canProcess) return;

        activeWorkers++;
        funcUpdateWorkerCount();

        const formData = new FormData();
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'card_widget');
        formData.set('action', 'invokeWidget');
        formData.set('widget_id', '{$widget->id}');
        formData.set('invoke_action', 'worker');
        formData.set('card_context_id', '{$queue_job->id}');

        genericAjaxPost(formData, null, null, function(json) {
            if(!isCurrent()) return;
            activeWorkers--;
            funcUpdateWorkerCount();
            if(isJobDone || isPaused || !canProcess) return;

            const didWork = typeof json === 'object' && json.slot === true && json.processed > 0;

            if(didWork) {
                funcRefreshProgress();
                funcSpawnSingleWorker();
            } else {
                funcRefreshProgress();
                const t = setTimeout(funcSpawnSingleWorker, RETRY_DELAY_MS);
                $widget.data('queueJobMonitorRetryTimer', t);
            }
        }, {
            error: function() {
                if(!isCurrent()) return;
                activeWorkers--;
                funcUpdateWorkerCount();
            }
        });
    };

    const funcFillPool = function() {
        while(activeWorkers < POOL_SIZE && !isJobDone && !isPaused && canProcess)
            funcSpawnSingleWorker();
    };

    const funcTogglePause = function() {
        if(!isCurrent()) return;

        const formData = new FormData();
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'card_widget');
        formData.set('action', 'invokeWidget');
        formData.set('widget_id', '{$widget->id}');
        formData.set('invoke_action', isPaused ? 'resume' : 'pause');
        formData.set('card_context_id', '{$queue_job->id}');

        genericAjaxPost(formData, null, null, function(json) {
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
            if(!isPaused) funcFillPool();
        });
    };

    $button_refresh.on('click', funcRefreshProgress);
    $button_pause.on('click', funcTogglePause);

    if(MODE === 'process' && !isJobDone) funcFillPool();
});
</script>
