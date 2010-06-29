<form action="{devblocks_url}{/devblocks_url}" method="post" enctype="multipart/form-data">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="saveImportTemplatesPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="portal" value="{$portal}">

<b>Import File:</b> (.xml)<br>
<input type="file" name="import_file" size="45"><br>
<br>

<button type="button" onclick="genericAjaxPopupClose('peek');this.form.submit();"><span class="cerb-sprite sprite-import"></span> {'common.import'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript" language="JavaScript1.2">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.import'|devblocks_translate|capitalize|escape:'quotes'}");
	} );
</script>
