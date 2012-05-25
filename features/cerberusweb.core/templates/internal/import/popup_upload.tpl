<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" target="iframe_file_post" id="frmImportPopup">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="parseImportFile">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="view_id" value="{$view_id}">

<fieldset>
	<legend>Upload File</legend>
	<b>{'crm.opp.import.upload_csv'|devblocks_translate}:</b> {'crm.opp.import.upload_csv.tip'|devblocks_translate}<br>
	<input type="file" name="csv_file">
</fieldset>

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.upload')|capitalize}</button>
</form>

<iframe id="iframe_file_post" name="iframe_file_post" style="visibility:hidden;display:none;width:0px;height:0px;background-color:#ffffff;"></iframe>
<br>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#frmImportPopup');
	$frm = $popup.find('FORM#frmImportPopup');
	
	$frm.submit(function(event) {
		$frm = $(this);
		$iframe = $frm.siblings('IFRAME[name=iframe_file_post]');
		$iframe.one('load', function(event) {
			//data = $(this).contents().find('body').text();
			//$json = $.parseJSON(data);
			
			//console.log($json);
			
			// [TODO] Error check
			
			//$labels = [];
			//$values = [];

			//$labels.push($json.name + ' (' + $json.size + ' bytes)'); 
			//$values.push($json.id);
		
			// Trigger event
// 			event = jQuery.Event('chooser_save');
// 			event.labels = $labels;
// 			event.values = $values;
// 			$popup.trigger(event);
			
			genericAjaxPopup('{$layer}', 'c=internal&a=showImportMappingPopup&context={$context}&view_id={$view_id}', 'reuse', false, '550');
			//genericAjaxPopupDestroy('{$layer}');
		});
	});
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title',"{'common.import'|devblocks_translate|capitalize}");
	});
	
	$popup.one('diagogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
</script>