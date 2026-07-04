<form action="#" method="post" id="frmCardWidgetExport">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">JSON</div>
	</div>
	<textarea id="cardWidgetExportJson" data-editor-lines="20" spellcheck="false">{$export_json}</textarea>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.workflow'|devblocks_translate|capitalize}</div>
	</div>
	<textarea id="cardWidgetExportWorkflow" data-editor-lines="20" spellcheck="false">{$export_workflow}</textarea>
</div>

<div class="buttons" style="margin-top:10px;">
	<button class="cerb-ui-button submit" type="button"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmCardWidgetExport');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		let $this = $(this);

		let title = "Export Widget: " + {$widget->name|json_encode nofilter};
		$this.dialog('option','title', title);

		new CerbUI.JsonEditor($popup.find('#cardWidgetExportJson')[0], { readOnly: true });
		new CerbUI.KataEditor($popup.find('#cardWidgetExportWorkflow')[0], { readOnly: true });

		$frm.find('button.submit').click(function(e) {
			e.stopPropagation();
			let $popup = genericAjaxPopupFind($(this));
			$popup.dialog('close');
		});
	});
});
</script>