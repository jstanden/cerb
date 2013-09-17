<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFindStringsEntry" enctype="multipart/form-data">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="translations">
<input type="hidden" name="action" value="saveImportStringsPanel">

<b>Language File:</b> (.xml; TMX)<br>
<input type="file" name="import_file" size="45"><br>
<br>

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'common.import'|devblocks_translate|capitalize}");
	} );
</script>

