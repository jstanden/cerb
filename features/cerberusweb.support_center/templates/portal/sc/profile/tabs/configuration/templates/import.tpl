<form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="portals">
<input type="hidden" name="action" value="saveImportTemplatesPeek">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>Portal:</b><br>
{$portal->name}<br>
<br>

<b>Import File:</b> (.xml)<br>
<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
<br>
<br>

<button type="button" class="submit"><span class="glyphicons glyphicons-file-import"></span></a> {'common.import'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('import');
	var $frm = $popup.find('form');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.import'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$popup.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_id', { single: true });
		});
		
		$popup.find('button.submit').on('click', function(e) {
			genericAjaxPost($frm, '', null, function(json) {
				genericAjaxGet('view{$view_id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
				genericAjaxPopupClose('import');
			});
		});
	});
});
</script>