{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-file-upload" id="{$element_id}">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		{$uniq_id = uniqid('file_')}

		<label for="{$uniq_id}" class="cerb-button-upload">
			<input id="{$uniq_id}" type="file" style="display:none;" {if $accept}accept="{$accept}"{/if}>
			<button type="button"><span class="cerb-icons cerb-icon-paperclip"></span></button>
		</label>

		<ul data-cerb-uploads-summary class="bubbles chooser-container">
			{if $value}
				{$resource = DAO_AutomationResource::getByToken($value)}
				{if $resource}
				<li>
					<input type="hidden" name="prompts[{$var}]" value="{$resource->token}">
					{$resource->name} ({$resource->storage_size|devblocks_prettybytes})
					<span class="cerb-icons cerb-icon-circle-remove"></span>
				</li>
				{/if}
			{/if}
		</ul>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $prompt = $('#{$element_id}');
	let $input = $prompt.find('input[type=file]');
	let $summary = $prompt.find('ul[data-cerb-uploads-summary]');
	let $button = $prompt.find('.cerb-button-upload button');

	$button.on('click', function(e) {
		e.stopPropagation();
		$(this).parent().click();
	});

	// bind all summary remove clicks
	$summary.on('click', function(e) {
		e.stopPropagation();
		let $target = $(e.target);

		if(!$target.is('.cerb-icon-circle-remove'))
			return true;

		$target.parent().remove();
	});

	let errorFile = function($item, file, error) {
		$item.text('Error: ' + file.name + ' (' + error + ')');
		$item.css('color', 'red');
	}

	// Only the UPLOAD needs the continuation; the picker and the remove-chip handlers are pure client work and
	// stay live in a preview, so this guard sits here rather than around the whole script.
	const continuationToken = '{$continuation_token}';

	let uploadFile = function(file) {
		// A preview render (builder palette, simulator form-fill) has an ephemeral continuation whose token is
		// '', so there's nothing to upload INTO. Say so instead of posting: `invokePrompt` 404s on an unknown
		// token, and genericAjaxPost's default failure handler would drop a real error banner on the editor
		// (and clear any unrelated alerts with it).
		if(!continuationToken) {
			let $preview_item = $('<li/>');
			$preview_item.text(file.name + ' (uploads are disabled in this preview)');
			$summary.append($preview_item);
			return;
		}

		let $spinner = Devblocks.getSpinner().css('max-width', '16px').show();

		let $item = $('<li/>');
		$item.text("Uploading " + file.name + "...");
		$item.prepend($spinner);
		$summary.append($item);

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation');
		formData.set('action', 'invokePrompt');
		formData.set('prompt_key', 'fileUpload/{$var}');
		formData.set('prompt_action', 'uploadFile');
		formData.set('continuation_token', continuationToken);
		formData.set('file', file);

		genericAjaxPost(formData, null, null, function(json) {
			$item.text('');

			if(typeof json != 'object') {
				errorFile($item, file, 'An unexpected error occurred');
				return;
			}

			if(json.hasOwnProperty('error')) {
				errorFile($item, file, json.error);
				return;
			}

			$item.text(json.name);

			let $hidden = $('<input/>');
			$hidden.attr('type', 'hidden');
			$hidden.attr('name', 'prompts[{$var}]{if $is_multiple}[]{/if}');
			$hidden.attr('value', json.token);
			$item.append($hidden);

			$item.append($('<span class="cerb-icons cerb-icon-circle-remove"></span>'));
		});
	}

	$input.on('change', function(e) {
		e.stopPropagation();

		let files = e.target.files || e.dataTransfer.files || [];

		for (let i = 0; i < files.length; i++) {
			uploadFile(files[i]);
		}

		$input.val('');
	});
});
</script>