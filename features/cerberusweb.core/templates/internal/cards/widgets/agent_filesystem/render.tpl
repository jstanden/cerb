{$is_writeable = CerberusContexts::isWriteableByActor('cerb.contexts.agent.filesystem', $dict->id, $active_worker)}

<div>
    <table cellpadding="2" cellspacing="0" style="margin-bottom:0.5em;">
        <tr>
            <th style="text-align:right;padding-right:0.5em;">Files:</th>
            <td>{$stats.file_count|number_format}</td>
        </tr>
        <tr>
            <th style="text-align:right;padding-right:0.5em;">Size:</th>
            <td>{$stats.total_bytes|devblocks_prettybytes}</td>
        </tr>
    </table>

    {if $is_writeable && !$queue_job}
    <div class="cerb-code-editor-toolbar" style="margin-bottom:0.5em;">
        <button type="button" data-cerb-button-import><span class="cerb-icons cerb-icon-download"></span> Import ZIP</button>
    </div>
    {/if}

    {if $rows}
        {include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
    {/if}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    const $widget = $('#cardWidget{$widget->getUniqueId($dict->id)}');
    if(!$widget.length) return;

    const $popup = genericAjaxPopupFind($widget);

    // job_id => status_id snapshot at render time. Used to skip a refresh when
    // the user opens and closes the card for an already-Done job.
    const jobStatuses = {$job_statuses|json_encode nofilter};
    const STATUS_DONE = 2;

    const funcRefreshWidget = function() {
        $popup.triggerHandler($.Event('cerb-widget-refresh', { widget_id: {$widget->id} }));
    };

    // Open the queue job monitor for a job id, refreshing this widget when it closes.
    const funcMonitorJob = function(jobId) {
        const $trigger = $('<a/>')
            .attr('data-context','cerb.contexts.queue.job')
            .attr('data-context-id', String(jobId))
            .css('display','none')
            .appendTo('body');

        $trigger
            .cerbPeekTrigger({ width: '600' })
            .on('cerb-peek-closed', function(ev) {
                ev.stopPropagation();
                $trigger.remove();
                funcRefreshWidget();
            })
            .trigger('click');
    };

    // Sheet rows: ui/sheets/render.tpl already attached cerbPeekTrigger.
    // We only add a refresh-on-close listener, gated on prior status.
    $widget.find('.cerb-sheet .cerb-peek-trigger[data-context="cerb.contexts.queue.job"]')
        .on('cerb-peek-closed', function(e) {
            e.stopPropagation();
            const jobId = parseInt($(this).attr('data-context-id'), 10);
            if(jobStatuses[jobId] === STATUS_DONE) return;
            funcRefreshWidget();
        });

    {if $is_writeable && !$queue_job}
    $widget.find('[data-cerb-button-import]').on('click', function(e) {
        e.preventDefault();

        genericAjaxPopup(
            'agentFilesystemImport',
            'c=profiles&a=invoke&module=agent_filesystem&action=renderImportPopup&filesystem_id={$dict->id}&widget_uid={$widget->getUniqueId($dict->id)}',
            null,
            false,
            '50%'
        );
    });

    // The upload popup hands the queued job back here so the monitor opens over the card.
    $widget.on('cerb-agent-filesystem-import-started', function(e, jobId) {
        e.stopPropagation();
        funcMonitorJob(jobId);
    });
    {/if}
});
</script>
