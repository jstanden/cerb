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

<script type="text/javascript">
$(function() {
    let $frm = $('#frmStorageSchemaPeek');
	var $popup = genericAjaxPopupFind($frm);

    Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{$schema->manifest->name|escape:'javascript' nofilter}");
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('frmStorageSchemaPeek','schema_{$schema->manifest->id|md5}',null,function() {
				genericAjaxPopupClose($popup);
			});
		})
	});
});
</script>