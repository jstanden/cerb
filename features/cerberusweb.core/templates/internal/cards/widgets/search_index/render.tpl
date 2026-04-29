{$script_uuid = uniqid('script')}
{$is_writeable = CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_SEARCH_INDEX, $dict->id, $active_worker)}

<div>
    <div>
        [[ stats #{$dict->id} ]]
    </div>

    {if $is_writeable}
    <div class="cerb-code-editor-toolbar">
        {if !$job}
        <button type="button" data-cerb-button-reindex><span class="glyphicons glyphicons-repeat"></span> Re-index</button>
        {/if}
    </div>
    {/if}

    {* [TODO] Job status *}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $widget = $('#cardWidget{$widget->getUniqueId($dict->id)}');
    let $toolbar = $widget.find('.cerb-code-editor-toolbar');
    let $button_reindex = $toolbar.find('[data-cerb-button-reindex]');

    {if !$job}
    $button_reindex.on('click', function (e) {
        e.preventDefault();

        let $spinner = Devblocks.getSpinner().css('max-width','16px');
        let $reindex_status = $('<b/>').text(' Re-indexing...');
        $spinner.insertAfter($button_reindex);
        $reindex_status.insertAfter($spinner);
        $button_reindex.hide();

        let formData = new FormData();
        formData.set('c', 'profiles');
        formData.set('a', 'invoke');
        formData.set('module', 'card_widget');
        formData.set('action', 'invokeWidget');
        formData.set('widget_id', '{$widget->id}');
        formData.set('invoke_action', 'reindex');
        formData.set('index_id', '{$dict->id}');

        // [TODO] If there's a reindex job going even on reload, prevent repeat
        // [TODO] We should show a spinner in its place
        // [TODO] Allow aborting a reindex job somewhere (ex. here, setup)
        // [TODO] We want to show some kind of reusable queue progress component
        // [TODO] Reloading the widget would check for in-progress jobs and show them
        // [TODO] We should disable the button while we're working

        genericAjaxPost(formData, null, null, function(json) {
            //$spinner.remove();
            //$button_reindex.fadeIn();
            console.log(json);
        });
    });
    {/if}

    // [TODO] Queue job poll refresh
});
</script>