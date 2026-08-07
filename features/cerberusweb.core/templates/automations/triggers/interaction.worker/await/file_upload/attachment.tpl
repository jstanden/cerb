{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-file-upload" id="{$element_id}">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		<div class="cerb-ui-file-upload" data-name="prompts[{$var}]">
			{if $value}
				{$file = DAO_Attachment::get($value)}
				{if !empty($file)}
					<li data-file-id="{$file->id}" data-file-name="{$file->name}" data-file-size="{$file->storage_size}"></li>
				{/if}
			{/if}
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $prompt = $('#{$element_id}');

	if(window.CerbUI && CerbUI.FileUpload)
		$prompt.find('.cerb-ui-file-upload').each(function() {
			new CerbUI.FileUpload(this, { name: 'prompts[{$var}]', multiple: false });
		});
});
</script>
