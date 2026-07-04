{$config_uniqid = uniqid('widgetConfig_')}
<div id="cardWidgetConfig{$config_uniqid}" class="cerb-u-mt-3">
    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">Display this attachment:</div>
        </div>

        <div class="cerb-ui-form">
            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label"><a class="cerb-chooser" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-single="true">ID</a></label>
                <input type="text" name="params[attachment_id]" value="{$widget->extension_params.attachment_id}" class="placeholders" autocomplete="off" spellcheck="false">
            </div>
        </div>
    </div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    var $config = $('#cardWidgetConfig{$config_uniqid}');
    var $input_attachment_id = $config.find('input[name="params[attachment_id]"]');

    if(window.CerbUI && CerbUI.RecordChooser) CerbUI.RecordChooser.pickerLink($config.find('.cerb-chooser')[0], { input: $input_attachment_id });
});
</script>