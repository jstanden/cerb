{$div_uid = uniqid('fieldset')}
<div id="{$div_uid}">
    <fieldset class="peek" style="margin-top:1em;">
        <legend>Indexing</legend>

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

    <fieldset class="peek" style="margin-top:1em;">
        <legend>Queries</legend>

        <div style="margin-top:0.5em;">
            <div>
                <label><input type="checkbox" name="params[wildcards_disable]" value="1" {if $model->extension_params.wildcards_disable}checked="checked"{/if}> Disable wildcards (*)</label>
            </div>
        </div>
    </fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $div = $('#{$div_uid}');
    let $extension_params = $div.closest('.search-index-params');
    let $template = $div.find('.cerb-template-trigger');
    let $query = $div.find('.cerb-query-trigger');

    // Query editor
    $query.cerbQueryTrigger();

    // Template editor
    $div.find('.cerb-template-trigger').cerbTemplateTrigger();

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
