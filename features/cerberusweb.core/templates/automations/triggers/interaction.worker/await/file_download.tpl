{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-file-download" id="{$element_id}">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		<button type="button" data-cerb-file>
			<span class="glyphicons glyphicons-download-alt"></span>
			{$filename}
		</button>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');

	$prompt.find('[data-cerb-file]').on('click', function(e) {
		e.stopPropagation();

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation');
		formData.set('action', 'invokePrompt');
		formData.set('prompt_key', 'fileDownload/{$var}');
		formData.set('prompt_action', 'download');
		formData.set('continuation_token', '{$continuation_token}');
		
		var xhr = new XMLHttpRequest();
		xhr.open('POST', '{devblocks_url}{/devblocks_url}');
		xhr.setRequestHeader('X-CSRF-Token', $('meta[name="_csrf_token"]').attr('content'));
		xhr.responseType = 'blob';
		xhr.onreadystatechange = function() {
			if(xhr.readyState === 4) {
				if(xhr.status === 200) {
					if(!(xhr.response instanceof Blob))
						return;
					
					var a = document.createElement('a');
					a.style.display = 'none';
					document.body.appendChild(a);
					a.href = window.URL.createObjectURL(xhr.response);
					a.download = '{$filename}';
					a.click();
					a.remove();
				}
			}
		};
		xhr.send(formData);
	});
});
</script>