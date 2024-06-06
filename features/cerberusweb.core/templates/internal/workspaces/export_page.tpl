<form action="#" method="post" id="frmWorkspacePageExport">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div>
	<textarea style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false">{$json}</textarea>
</div>

<div style="padding:5px;">
	<button class="submit" type="button"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmWorkspacePageExport');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		let $this = $(this);

		let title = "Export Page: " + {$page->name|json_encode nofilter};
		$this.dialog('option','title',title);

		$frm.find('button.submit').click(function() {
			let $popup = genericAjaxPopupFind($(this));
			$popup.dialog('close');
		});
	});
});
</script>


