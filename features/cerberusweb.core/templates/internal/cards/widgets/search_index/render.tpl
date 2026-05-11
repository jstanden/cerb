{$is_writeable = CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_SEARCH_INDEX, $dict->id, $active_worker)}

<div>
    <table cellpadding="2" cellspacing="0" style="margin-bottom:0.5em;">
        <tr>
            <th style="text-align:right;padding-right:0.5em;">Type:</th>
            <td>{$stats.type_label|default:''}</td>
        </tr>
        <tr>
            <th style="text-align:right;padding-right:0.5em;">Records:</th>
            <td>{$stats.record_count|number_format}</td>
        </tr>
        <tr>
            <th style="text-align:right;padding-right:0.5em;">Indexed:</th>
            <td>{$stats.indexed_count|number_format}</td>
        </tr>
        <tr>
            <th style="text-align:right;padding-right:0.5em;">Last indexed:</th>
            <td>{if $stats.last_indexed_at}{$stats.last_indexed_at|devblocks_prettytime}{else}Never{/if}</td>
        </tr>
    </table>

    {if $is_writeable && !$queue_job}
    <div class="cerb-code-editor-toolbar" style="margin-bottom:0.5em;">
        <button type="button" data-cerb-button-reindex><span class="glyphicons glyphicons-repeat"></span> Re-index</button>
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
    $widget.find('[data-cerb-button-reindex]').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $spinner = Devblocks.getSpinner().css('max-width','16px');
        const $status = $('<b/>').text(' Re-indexing...');
        $spinner.insertAfter($btn);
        $status.insertAfter($spinner);
        $btn.hide();

        const formData = new FormData();
        formData.set('c','profiles');
        formData.set('a','invoke');
        formData.set('module','card_widget');
        formData.set('action','invokeWidget');
        formData.set('widget_id','{$widget->id}');
        formData.set('invoke_action','reindex');
        formData.set('index_id','{$dict->id}');

        genericAjaxPost(formData, null, null, function(json) {
            if(typeof json !== 'object' || !json.job_id) {
                $spinner.remove(); $status.remove(); $btn.show();
                return;
            }

            const $trigger = $('<a/>')
                .attr('data-context','cerb.contexts.queue.job')
                .attr('data-context-id', String(json.job_id))
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
        });
    });
    {/if}
});
</script>
