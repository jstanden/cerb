<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="frmImportPopup">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worklists">
<input type="hidden" name="action" value="parseImportFile">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{{'common.upload.file'|devblocks_translate|capitalize}} (CSV or JSONL)</legend>

	<div class="file-drop-zone" style="margin-top:5px;padding:20px;border:2px dashed var(--cerb-color-background-contrast-150);border-radius:8px;text-align:center;transition:border-color 0.2s, background-color 0.2s;">
		<input type="file" name="import_file" id="importFileInput" style="position:absolute;left:-9999px;">
		<label for="importFileInput" class="file-drop-message" style="display:block;cursor:pointer;">
			<span class="cerb-icons cerb-icon-file-import" style="font-size:24px;color:var(--cerb-color-background-contrast-150);"></span>
			<div style="margin-top:8px;color:var(--cerb-color-background-contrast-180);">
				{'common.upload.file.drag_and_drop'|devblocks_translate} <span style="color:var(--cerb-color-link);text-decoration:underline;">{'common.upload.file.browse'|devblocks_translate|lower}</span>
			</div>
		</label>
		<div class="file-selected" style="display:none;">
			<span class="cerb-icons cerb-icon-file" style="font-size:24px;color:var(--cerb-color-link);"></span>
			<div class="file-name" style="margin-top:8px;font-weight:bold;"></div>
			<div style="margin-top:5px;"><span class="file-remove" style="color:var(--cerb-color-background-contrast-150);cursor:pointer;text-decoration:underline;">{'common.remove'|devblocks_translate|lower}</span></div>
		</div>
	</div>
</fieldset>

<button type="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.upload'|devblocks_translate|capitalize}</button>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFind('#frmImportPopup');
	let $frm = $popup.find('FORM#frmImportPopup');

	$frm.on('submit', async function(e) {
		e.preventDefault();

		let formData = new FormData(this);

		try {
			let response = await fetch('{devblocks_url}ajax.php{/devblocks_url}', {
				method: 'POST',
				body: formData
			});

			if(!response.ok) {
				Devblocks.createAlertError("The file upload failed (HTTP " + response.status + "). Please try again.");
				return;
			}

			let text = await response.text();
			let import_token = '';

			// Check for JSON error response
			if(text && text.charAt(0) === '\x7B') {
				try {
					let json = JSON.parse(text);

					if(json.status === false || json.error) {
						Devblocks.createAlertError(json.error || "An unexpected error occurred.");
						return;
					}

					if(!json.hasOwnProperty('import_token')) {
						Devblocks.createAlertError(json.error || "An unexpected error occurred.");
						return;
					}

					import_token = json.import_token;

				} catch(e) { }

			} else {
				Devblocks.createAlertError("An unexpected error occurred.");
				return;
			}

			genericAjaxPopup('{$layer}', 'c=internal&a=invoke&module=worklists&action=renderImportMappingPopup&context={$context}&view_id={$view_id}&import_token=' + encodeURIComponent(import_token), null, false, '80%');

		} catch(e) {
			Devblocks.createAlertError("The file upload failed. Please check the file type (CSV/JSONL) and try again.");
		}
	});

	$popup.one('popup_open',function(event) {
		event.stopPropagation();
		$(this).dialog('option','title',"{'common.import'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// File drop zone handling
		let $dropZone = $frm.find('.file-drop-zone');
		let $fileInput = $dropZone.find('input[type=file]');
		let $dropMessage = $dropZone.find('.file-drop-message');
		let $fileSelected = $dropZone.find('.file-selected');
		let $fileName = $dropZone.find('.file-name');
		let $fileRemove = $dropZone.find('.file-remove');

		function showSelectedFile(name) {
			$fileName.text(name);
			$dropMessage.hide();
			$fileSelected.show();
			$dropZone.css('border-color', 'var(--cerb-color-link-text)');
		}

		function clearSelectedFile() {
			$fileInput.val('');
			$fileSelected.hide();
			$dropMessage.show();
			$dropZone.css('border-color', 'var(--cerb-color-background-contrast-150)');
		}

		$fileInput.on('change', function() {
			if(this.files && this.files.length > 0) {
				showSelectedFile(this.files[0].name);
			} else {
				clearSelectedFile();
			}
		});

		$fileRemove.on('click', function(e) {
			e.stopPropagation();
			clearSelectedFile();
		});

		$dropZone.on('dragover dragenter', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).css({
				'border-color': 'var(--cerb-color-link-text)',
				'background-color': 'color-mix(in srgb, var(--cerb-color-link-text) 10%, transparent)'
			});
		});

		$dropZone.on('dragleave dragend', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).css({
				'border-color': $fileSelected.is(':visible') ? 'var(--cerb-color-link-text)' : 'var(--cerb-color-background-contrast-150)',
				'background-color': ''
			});
		});

		$dropZone.on('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).css('background-color', '');

			let files = e.originalEvent.dataTransfer.files;
			if(files && files.length > 0) {
				$fileInput[0].files = files;
				showSelectedFile(files[0].name);
			}
		});
	});
	
	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
});
</script>