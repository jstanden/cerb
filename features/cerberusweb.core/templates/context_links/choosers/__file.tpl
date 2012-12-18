<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" target="iframe_file_post" id="chooserFileUploadForm">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="chooserOpenFileUpload">

<fieldset>
	<legend>Upload File</legend>
	<input type="file" name="file_data">
</fieldset>

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.upload')|capitalize}</button>
</form>

<iframe name="iframe_file_post" style="visibility:hidden;display:none;width:0px;height:0px;background-color:#ffffff;"></iframe>
<br>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#chooserFileUploadForm');
	$frm = $popup.find('FORM#chooserFileUploadForm');
	
	$popup.find('UL.buffer').sortable({ placeholder: 'ui-state-highlight' });
	
	$frm.submit(function(event) {
		$frm = $(this);
		$iframe = $frm.parent().find('IFRAME[name=iframe_file_post]');
		$iframe.load(function(event) {
			data = $(this).contents().find('body').html();
			$json = $.parseJSON(data);
			
			// [TODO] Error check
			
			$labels = [];
			$values = [];

			$labels.push($json.name + ' (' + $json.size + ' bytes)'); 
			$values.push($json.id);
		
			// Trigger event
			event = jQuery.Event('chooser_save');
			event.labels = $labels;
			event.values = $values;
			$popup.trigger(event);
			
			genericAjaxPopupDestroy('{$layer}');
		});
	});
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title','File Chooser');
	});
	
	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
</script>