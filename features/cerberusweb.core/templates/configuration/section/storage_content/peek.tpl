<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmStorageSchemaPeek">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="storage_content">
<input type="hidden" name="action" value="saveStorageSchemaPeek">
<input type="hidden" name="ext_id" value="{$schema->manifest->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{$schema->renderConfig()}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $frm = $('#frmStorageSchemaPeek');
	let $popup = genericAjaxPopupFind($frm);

    Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{$schema->manifest->name|escape:'javascript' nofilter}");
		
		$popup.find('button.submit').click(function() {
            let $output = $('[data-cerb-storage-schema="{$schema->manifest->id}"]');
			genericAjaxPost('frmStorageSchemaPeek',$output,null,function() {
				genericAjaxPopupClose($popup);
			});
		})
	});
});
</script>