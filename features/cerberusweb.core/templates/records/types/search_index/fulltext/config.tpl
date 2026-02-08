{$fieldset_uid = uniqid('fieldset')}
<fieldset id="{$fieldset_uid}" class="peek" style="margin-top:1em;">
    <legend>Fulltext</legend>

    <div style="margin-top:0.5em;">
        <b>Index records matching this query:</b> (blank for all)
        <div>
            <textarea name="params[record_query]" class="cerb-query-trigger" data-context="{$model->record_type}" style="width:100%;height:3em;">{$model->extension_params.record_query}</textarea>
        </div>
    </div>

    <div style="margin-top:0.5em;">
        <b>Using this record content template:</b>
        <div>
            <textarea name="params[content]" placeholder="e.g. {literal}{{content}}{/literal}" class="cerb-template-trigger" data-context="{$model->record_type}" style="width:100%;height:8em;">{$model->extension_params.content}</textarea>
        </div>
    </div>
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $fieldset = $('#{$fieldset_uid}');
    let $extension_params = $fieldset.closest('.search-index-params');
    let $template = $fieldset.find('.cerb-template-trigger');
    let $query = $fieldset.find('.cerb-query-trigger');

    // Query editor
    $query.cerbQueryTrigger();

    // Template editor
    $fieldset.find('.cerb-template-trigger').cerbTemplateTrigger();

    // On record type change
    $extension_params.on('cerb-search-index-params-change-context', function(e, context) {
        e.stopPropagation();

        // Update code editor context
        $query.attr('data-context', context);

        // Update template editor context
        $template.attr('data-context', context);
    });
});
</script>
