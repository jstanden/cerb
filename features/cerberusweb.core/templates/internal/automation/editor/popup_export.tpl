{$uniqid = uniqid('automationExport')}

<div id="{$uniqid}">
    <fieldset class="peek">
        <legend>{{'common.package'|devblocks_translate|capitalize}}</legend>
        <textarea id="{$uniqid}Json" data-editor-lines="20" spellcheck="false">{$export_json}</textarea>
    </fieldset>

    <fieldset class="peek">
        <legend>{{'common.workflow'|devblocks_translate|capitalize}}</legend>
        <textarea id="{$uniqid}Workflow" data-editor-lines="20" spellcheck="false">{$export_workflow}</textarea>
    </fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    const $div = $('#{$uniqid}');
    const $popup = genericAjaxPopupFind($div);

    $popup.one('popup_open', function() {
        $popup.dialog('option', 'title', '{'common.export'|devblocks_translate|capitalize}: {{'common.automation'|devblocks_translate}|capitalize}');
        
        new CerbUI.JsonEditor($popup.find('#{$uniqid}Json')[0], { readOnly: true });
        new CerbUI.KataEditor($popup.find('#{$uniqid}Workflow')[0], { readOnly: true });
    });
});
</script>