<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" target="iframe_file_post" id="frmImportPopup">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worklists">
<input type="hidden" name="action" value="parseImportFile">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Upload File</legend>
	<b>{'common.import.upload_csv'|devblocks_translate}:</b> {'common.import.upload_csv.tip'|devblocks_translate}<br>
	<input type="file" name="csv_file">
</fieldset>

<button type="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.upload'|devblocks_translate|capitalize}</button>
</form>

<iframe id="iframe_file_post" name="iframe_file_post" sandbox="allow-same-origin allow-scripts" style="visibility:hidden;display:none;width:0px;height:0px;background-color:#ffffff;"></iframe>
<br>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#frmImportPopup');
	var $frm = $popup.find('FORM#frmImportPopup');
	
	$frm.on('submit', function() {
		var $frm = $(this);
		var $iframe = $frm.siblings('IFRAME[name=iframe_file_post]');
		$iframe.one('load', function() {
			genericAjaxPopup('{$layer}', 'c=internal&a=invoke&module=worklists&action=renderImportMappingPopup&context={$context}&view_id={$view_id}', 'reuse', false, '550');
		});
	});
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title',"{'common.import'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
	});
	
	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
});
</script>