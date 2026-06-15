<form action="#" method="post" id="frmWorkspacePageExport">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div>
	<textarea style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false">{$json}</textarea>
</div>

<div style="padding:5px;">
	<button class="submit" type="button"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.close'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmWorkspacePageExport');
	const dlg = CerbUI.Dialog.from($frm[0]);

	Devblocks.formDisableSubmit($frm);

	if(dlg)
		dlg.setTitle("Export Page: " + {$page->name|json_encode nofilter});

	$frm.find('button.submit').click(function() {
		if(dlg) dlg.close();
	});
});
</script>


