<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" target="iframe_file_post" id="chooserFileUploadForm">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="chooserOpenFileUpload">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{if $single}{'common.upload.file'|devblocks_translate|capitalize}{else}{'common.upload.files'|devblocks_translate|capitalize}{/if}</legend>
	<input type="file" name="file_data[]" {if !$single}multiple="multiple"{/if}>
</fieldset>

{if !$single}
<fieldset class="peek">
	<legend>Include files from these bundles</legend>
	<button type="button" class="chooser-file-bundle"><span class="glyphicons glyphicons-search"></span></button>
</fieldset>
{/if}

<button type="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);vertical-align:middle;"></span> {'common.ok'|devblocks_translate|upper}</button>
</form>

<iframe name="iframe_file_post" sandbox="allow-same-origin allow-scripts" style="visibility:hidden;display:none;width:0px;height:0px;background-color:#ffffff;"></iframe>
<br>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#chooserFileUploadForm');
	var $frm = $popup.find('FORM#chooserFileUploadForm');
	
	$popup.find('UL.buffer').sortable({ placeholder: 'ui-state-highlight' });
	
	// Bundle chooser
	
	$popup.find('button.chooser-file-bundle').each(function() {
		ajax.chooser(this,'{CerberusContexts::CONTEXT_FILE_BUNDLE}','bundle_ids', { autocomplete:true });
	});
	
	// Form
	
	$frm.submit(function(event) {
		var $frm = $(this);
		var $iframe = $frm.parent().find('IFRAME[name=iframe_file_post]');
		$iframe.one('load', function(event) {
			var data = $(this).contents().find('body').text();
			var $json = $.parseJSON(data);
			
			var $labels = [];
			var $values = [];
			
			if(typeof $json == 'object')
			for(file_idx in $json) {
				$labels.push($json[file_idx].name + ' (' + $json[file_idx].size + ' bytes)'); 
				$values.push($json[file_idx].id);
			}
		
			// Trigger event
			var event = jQuery.Event('chooser_save');
			event.response = $json;
			event.labels = $labels;
			event.values = $values;
			$popup.trigger(event);

			genericAjaxPopupDestroy('{$layer}');
		});
	});
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$popup.dialog('option','title','File Chooser');
		
		// We have to use a timeout here since markitup steals focus
		setTimeout(function() {
			$popup.find('input').focus();
		}, 100);
	});
	
	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
});
</script>