{$uniqid = uniqid('automationExport')}

<div id="{$uniqid}">
    <textarea data-editor-mode="ace/mode/json" data-editor-readonly="true">{$export_json}</textarea>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    const $div = $('#{$uniqid}');
    const $popup = genericAjaxPopupFind($div);

    $popup.one('popup_open', function() {
        $popup.dialog('option', 'title', '{'common.export'|devblocks_translate|capitalize}');
        
        const $textarea = $popup.find('textarea');
        const $editor = $textarea.cerbCodeEditor().nextAll('pre.ace_editor');
        const editor = ace.edit($editor.attr('id'));
        
        setTimeout(function() {
            if(editor) {
                editor.selectAll();
                editor.focus();
            }
        }, 50);
    });
});
</script>