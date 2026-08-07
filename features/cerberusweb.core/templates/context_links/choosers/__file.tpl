<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="chooserFileUploadForm">

<fieldset class="peek">
	<legend>{if $single}{'common.upload.file'|devblocks_translate|capitalize}{else}{'common.upload.files'|devblocks_translate|capitalize}{/if}</legend>
	<input type="file" name="file_data[]" {if !$single}multiple="multiple"{/if} autofocus="autofocus">
</fieldset>

{if !$single && $file_bundles_enabled}
<fieldset class="peek">
	<legend>Include files from these bundles</legend>
	<div class="cerb-ui-record-chooser cerb-file-bundle-chooser" data-context="cerberusweb.contexts.file_bundle"></div>
</fieldset>
{/if}

<div class="cerb-uploads"></div>

<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.ok'|devblocks_translate|upper}</button>
</form>

<br>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#chooserFileUploadForm');
	var $frm = $popup.find('FORM#chooserFileUploadForm');
	var $file_input = $frm.find('input[type=file]');
	var $uploads = $frm.find('div.cerb-uploads');
	var $submit = $frm.find('button.submit');

	Devblocks.formDisableSubmit($frm);
	
	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($popup.find('ul.buffer').get(0));
	
	// Bundle chooser
	{if $file_bundles_enabled}
	$popup.find('.cerb-file-bundle-chooser').each(function() {
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser(this, { context: 'cerberusweb.contexts.file_bundle', name: 'bundle_ids', multiple: true, emptyIcon: 'paperclip' });
	});
	{/if}

	var uploadFunc = function(f, labels, values, callback) {
		var xhr = new XMLHttpRequest();
		var file = f;
		var $progress = $('<p/>').appendTo($uploads);
		
		if(xhr.upload) {
			xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=invoke&module=records&action=chooserOpenFileAjaxUpload', true);
			xhr.setRequestHeader('X-File-Name', encodeURIComponent(f.name));
			xhr.setRequestHeader('X-File-Type', f.type);
			xhr.setRequestHeader('X-File-Size', f.size);
			xhr.setRequestHeader('X-CSRF-Token', '{$session.csrf_token}');
			
			xhr.upload.addEventListener('progress', function(e) {
				var percent = parseInt(e.loaded/e.total*100);
				$progress.text('Uploading ' + file.name + ' ...' + percent + '%');
				$progress.css('background', 'linear-gradient(90deg, rgb(40,130,250) ' + percent + '%, rgb(200,200,200) ' + (100-percent) + '%);');
			});
			
			xhr.onreadystatechange = function(e) {
				if(xhr.readyState === 4) {
					var json = {};
					if(xhr.status === 200) {
						$progress
							.text(file.name)
							.addClass('success')
							;
						
						json = JSON.parse(xhr.responseText);
						labels.push(json.name + ' (' + json.size_label + ')');
						values.push(json.id);
						
					} else {
						$progress
							.text('Failed to upload ' + file.name)
							.addClass('failure')
							;
					}
					
					callback(null, json);
				}
			};
			
			xhr.send(f);
		}
	};
	
	{if $file_bundles_enabled}
	var loadBundleFunc = function(bundle_id, labels, values, callback) {
		genericAjaxGet('', 'c=internal&a=invoke&module=records&action=chooserOpenFileLoadBundle&bundle_id=' + encodeURIComponent(bundle_id), function(json) {
			if(!Array.isArray(json)) {
				callback();
				return;
			}

			for(var i = 0; i < json.length; i++) {
				labels.push(json[i].name + ' (' + json[i].size_label + ')');
				values.push(json[i].id);
			}

			callback(null, json);
		});
	};
	{/if}

	// Form
	
	$submit.on('click', function(event) {
		$submit.hide();
		
		var labels = [];
		var values = [];
		var jobs = [];

		// Loop through bundles
		{if $file_bundles_enabled}
		var $bundles = $frm.find('input:hidden[name="bundle_ids[]"]');

		$bundles.each(function(e) {
			var $bundle = $(this);
			var bundle_id = $bundle.val();

			jobs.push(
				CerbUI.utils.apply(loadBundleFunc, bundle_id, labels, values)
			);
		});
		{/if}

		// Upload individual files
		
		var files = $file_input[0].files;
		
		for(var i = 0, f; f = files[i]; i++) {
			jobs.push(
				CerbUI.utils.apply(uploadFunc, f, labels, values)
			);
		}
		
		CerbUI.utils.series(jobs, function(err, json) {
			// Trigger event
			var event = jQuery.Event('chooser_save');
			event.response = json;
			event.labels = labels;
			event.values = values;

			genericAjaxPopupClose('{$layer}', event);
		});
	});
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$popup.dialog('option','title','File Chooser');
	});
});
</script>