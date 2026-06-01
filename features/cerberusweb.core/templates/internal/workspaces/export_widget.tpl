<form action="#" method="post" id="frmWorkspaceWidgetExport">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>JSON</legend>
	<textarea data-editor-mode="ace/mode/json" data-editor-readonly="true">{$export_json}</textarea>
</fieldset>

<fieldset class="peek">
	<legend>{{'common.workflow'|devblocks_translate|capitalize}}</legend>
	<textarea data-editor-mode="ace/mode/cerb_kata" data-editor-readonly="true">{$export_workflow}</textarea>
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

		$popup.find('textarea[data-editor-mode]').cerbCodeEditor();

		$frm.find('button.submit').click(function(e) {
			e.stopPropagation();
			let $popup = genericAjaxPopupFind($(this));
			$popup.dialog('close');
		});
	});
});
</script>