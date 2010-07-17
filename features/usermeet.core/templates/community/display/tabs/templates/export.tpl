<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="saveExportTemplatesPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="portal" value="{$portal}">

<b>Filename:</b> (.xml)<br>
<input type="text" name="filename" size="45" value="cerb5_portal_templates_{$smarty.const.APP_BUILD}.xml"><br>
<br>

<b>Author:</b><br>
<input type="text" name="author" size="45" value=""><br>
<br>

<b>Author E-mail:</b><br>
<input type="text" name="email" size="45" value=""><br>
<br>

<button type="button" onclick="genericAjaxPopupClose('peek');this.form.submit();"><span class="cerb-sprite sprite-export"></span> {'common.export'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'common.export'|devblocks_translate|capitalize|escape:'quotes'}");
	} );
</script>
