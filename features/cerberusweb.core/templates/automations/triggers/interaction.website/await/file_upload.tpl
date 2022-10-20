{$element_id = uniqid('el')}
<div id="{$element_id}" class="cerb-interaction-popup--form-elements--prompt cerb-interaction-popup--form-elements-fileUpload">
	<h6>{$label}</h6>
	<input type="file" {if $is_multiple}multiple="multiple"{/if} {if $accept}accept="{$accept}"{/if}>
	<div data-cerb-uploads-summary></div>
</div>

<script type="text/javascript" nonce="{$session->nonce}">
{
	let $prompt = document.querySelector('#{$element_id}');
	let $input = $prompt.querySelector('input[type=file]');
	let $summary = $prompt.querySelector('[data-cerb-uploads-summary]');
	
	let errorFile = function($item, file, error) {
		$item.innerText = 'Error: ' + file.name + ' (' + error + ')';
		$item.style.color = 'red';

		let $remove = document.createElement('div');
		$remove.classList.add('cerb-button-remove');
		$remove.addEventListener('click', function(e) {
			e.stopPropagation();
			$item.remove();
		});
		$item.prepend($remove);
	}
	
	let uploadFile = function(file) {
		let formData = new FormData();
		formData.set('continuation_token', '{$continuation_token}');
		formData.set('prompt_key', 'fileUpload/{$var}');
		formData.set('prompt_action', 'uploadFile');
		formData.set('file', file);

		let $spinner = $$.getSpinner();
		$spinner.style.width = '16px';
		$spinner.style.height = '16px';
		
		let $item = document.createElement('div');
		$item.innerText = "Uploading " + file.name + "...";
		$item.prepend($spinner);
		$summary.appendChild($item);

		$$.interactionInvoke(
			formData,
			function(err, res) {
				if(err) {
					if(res && 413 === res.status) {
						errorFile($item, file, 'The uploaded file is too large');
						return;
					}
					
					errorFile($item, file, 'An unexpected error occurred');
					return;
				}
				
				let json = JSON.parse(res.responseText);
				
				if(typeof json != 'object') {
					errorFile($item, file, 'An unexpected error occurred');
					return;
				}
				
				if(json.hasOwnProperty('error')) {
					errorFile($item, file, json.error);
					return;
				}
				
				$item.innerText = json.name;
				
				let $remove = document.createElement('div');
				$remove.classList.add('cerb-button-remove');
				$remove.addEventListener('click', function(e) {
					e.stopPropagation();
					$item.remove();
				});
				$item.prepend($remove);
				
				let $hidden = document.createElement('input');
				$hidden.setAttribute('type', 'hidden');
				$hidden.setAttribute('name', 'prompts[{$var}]{if $is_multiple}[]{/if}');
				$hidden.setAttribute('value', json.token);
				$item.appendChild($hidden);
			},
			function(e) {
				e.stopPropagation();
				if(e.loaded <= file.size) {
					var percent = Math.round(100 - (e.loaded / file.size * 100));
					$item.innerText = "Uploading " + file.name + "... (" + percent + "%)";
					$item.prepend($spinner);
				}
			}
		);
	}
	
	$input.addEventListener('change', function(e) {
		e.stopPropagation();
		
		let files = e.target.files || e.dataTransfer.files || [];

		{if !$is_multiple}
		$summary.innerHTML = '';
		{/if}
		
		$$.forEach(files, function(index, file) {
			uploadFile(file);
		});
		
		$input.value = '';
	});
}
</script>