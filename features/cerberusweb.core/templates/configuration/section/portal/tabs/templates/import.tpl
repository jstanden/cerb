<form action="{devblocks_url}{/devblocks_url}" method="post" enctype="multipart/form-data">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="portal">
<input type="hidden" name="action" value="saveImportTemplatesPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="portal" value="{$portal}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>Import File:</b> (.xml)<br>
<input type="file" name="import_file" size="45"><br>
<br>

<button type="button" onclick="genericAjaxPopupClose('peek');this.form.submit();"><span class="glyphicons glyphicons-file-import"></span></a> {'common.import'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'common.import'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
	});
});
</script>