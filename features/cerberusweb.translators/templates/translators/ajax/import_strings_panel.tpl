<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFindStringsEntry" enctype="multipart/form-data">
<input type="hidden" name="c" value="translators">
<input type="hidden" name="a" value="saveImportStringsPanel">

<b>Language File:</b> (.xml; TMX)<br>
<input type="file" name="import_file" size="45"><br>
<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>

<script type="text/javascript" language="JavaScript1.2">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('dialogopen', function(event,ui) {
		$popup.dialog('option','title',"{$translate->_('common.import')|capitalize|escape:'quotes'}");
	} );
</script>

