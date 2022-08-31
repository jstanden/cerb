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
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$file->id}">
							{$file->name} ({$file->storage_size|devblocks_prettybytes})
						</a>
						<a href="javascript:;" onclick="$(this).parent().remove();">
							<span class="glyphicons glyphicons-circle-remove"></span>
						</a>
					</li>
				{/if}
			{/if}
		</ul>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');

	$prompt.find('button.chooser_file').each(function() {
		ajax.chooserFile(this, 'prompts[{$var}]', { single: true });
	});

	$prompt.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;
});
</script>