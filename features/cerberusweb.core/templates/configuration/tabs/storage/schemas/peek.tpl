<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmStorageSchemaPeek" name="frmStorageSchemaPeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveStorageSchemaPeek">
<input type="hidden" name="ext_id" value="{$schema->manifest->id}">

{$schema->renderConfig()}

<button type="button" onclick="genericAjaxPost('frmStorageSchemaPeek','schema_{$schema->manifest->id|md5}');genericAjaxPopupClose('peek');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$schema->manifest->name|escape}");
	} );
</script>
