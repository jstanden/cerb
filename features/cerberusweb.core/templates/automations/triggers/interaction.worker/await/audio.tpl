{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-audio" id="{$element_id}">
	<h6>{$label}</h6>
	<div style="margin-left:10px;">
		<audio {if $controls}controls="controls" {/if}{if $autoplay}autoplay="autoplay" {/if}{if $loop}loop="loop" {/if}></audio>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');
	var $audio = $prompt.find('audio');

	var formData = new FormData();
	formData.set('c', 'profiles');
	formData.set('a', 'invoke');
	formData.set('module', 'automation');
	formData.set('action', 'invokePrompt');
	formData.set('prompt_key', 'audio/{$var}');
	formData.set('prompt_action', 'play');
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

				let soundUrl = window.URL.createObjectURL(xhr.response);
				let audio = $audio.get(0);
				audio.src = soundUrl;
			}
		}
	};
	xhr.send(formData);
});
</script>