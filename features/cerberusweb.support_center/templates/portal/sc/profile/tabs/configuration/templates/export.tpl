<form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="genericAjaxPopupClose('peek');">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="portals">
<input type="hidden" name="action" value="saveExportTemplatesPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>Filename:</b> (.xml)<br>
<input type="text" name="filename" size="45" value="cerb_portal_templates_{$smarty.const.APP_BUILD}.xml"><br>
<br>

<b>Author:</b><br>
<input type="text" name="author" size="45" value=""><br>
<br>

<b>Author E-mail:</b><br>
<input type="text" name="email" size="45" value=""><br>
<br>

<button type="submit"><span class="glyphicons glyphicons-file-export"></span></a> {'common.export'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.export'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
	});
});
</script>