{$config_uniqid = uniqid('widgetConfig_')}
<div id="cardWidgetConfig{$config_uniqid}" style="margin-top:10px;">
    <fieldset class="peek">
        <legend>Display this search index:</legend>

        <b><a class="cerb-chooser" data-context="{CerberusContexts::CONTEXT_SEARCH_INDEX}" data-single="true">ID</a>:</b>

        <div style="margin-left:10px;">
            <input type="text" name="params[search_index_id]" value="{$widget->extension_params.search_index_id}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">
        </div>
    </fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    var $config = $('#cardWidgetConfig{$config_uniqid}');
    var $input_search_index_id = $config.find('input[name="params[search_index_id]"]');

    $config.find('.cerb-chooser').cerbChooserTrigger()
        .on('cerb-chooser-selected', function(e) {
            {literal}$input_search_index_id.val(e.values[0] + '{# ' + e.labels[0] + ' #}');{/literal}
        })
    ;
});
</script>