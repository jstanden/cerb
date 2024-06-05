{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-file-upload" id="{$element_id}">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
		<ul class="bubbles chooser-container">
			{if $value}
				{$file = DAO_Attachment::get($value)}
				{if !empty($file)}
					<li>
						<input type="hidden" name="prompts[{$var}]" value="{$file->id}">
						<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$file->id}">
							{$file->name} ({$file->storage_size|devblocks_prettybytes})
						</a>
						<a>
							<span class="glyphicons glyphicons-circle-remove"></span>
						</a>
					</li>
				{/if}
			{/if}
		</ul>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $prompt = $('#{$element_id}');
	let $container = $prompt.find('.chooser-container');

	$container.find('.glyphicons-circle-remove').parent().on('click', function(e) {
		e.stopPropagation();
		$(this).closest('li').remove();
	});

	$prompt.find('button.chooser_file').each(function() {
		ajax.chooserFile(this, 'prompts[{$var}]', { single: true });
	});

	$prompt.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;
});
</script>