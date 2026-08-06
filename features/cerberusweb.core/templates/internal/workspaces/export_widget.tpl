<form action="#" method="post" id="frmWorkspaceWidgetExport">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>JSON</legend>
	<textarea id="workspaceWidgetExportJson" data-editor-lines="20" spellcheck="false">{$export_json}</textarea>
</fieldset>

<fieldset class="peek">
	<legend>{{'common.workflow'|devblocks_translate|capitalize}}</legend>
	<textarea id="workspaceWidgetExportWorkflow" data-editor-lines="20" spellcheck="false">{$export_workflow}</textarea>
</fieldset>

<div style="padding:5px;">
	<button class="submit" type="button"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmWorkspaceWidgetExport');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		let $this = $(this);

		let title = "Export Widget: " + {$widget->label|json_encode nofilter};
		$this.dialog('option','title', title);

		new CerbUI.JsonEditor($popup.find('#workspaceWidgetExportJson')[0], { readOnly: true });
		new CerbUI.KataEditor($popup.find('#workspaceWidgetExportWorkflow')[0], { readOnly: true });

		$frm.find('button.submit').click(function(e) {
			e.stopPropagation();
			let $popup = genericAjaxPopupFind($(this));
			$popup.dialog('close');
		});
	});
});
</script>