<div class="cerb-ui-header">
    <div>
        <div class="cerb-ui-header--title">Export Bots</div>
        <div class="cerb-ui-header--subtitle">Save legacy bot behaviors to a JSON file</div>
    </div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupExportBots">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="export_bots">
<input type="hidden" name="action" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <textarea id="exportBotsJson" data-editor-lines="25" spellcheck="false">{$bots_json}</textarea>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frmSetupExportBots');

    Devblocks.formDisableSubmit($frm);

	new CerbUI.JsonEditor($frm.find('#exportBotsJson')[0], { readOnly: true, minLines: 8 });
});
</script>
