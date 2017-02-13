{$attachments = DAO_Attachment::getByContextIds($context, $context_id)}

{if $attachments}
<b>{'common.attachments'|devblocks_translate|capitalize}:</b>
<ul class="bubbles" style="display:block;margin:5px 0px 15px 10px;">
	{foreach from=$attachments item=attachment}
	<li>
		<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$attachment->id}" data-profile-url="{devblocks_url}c=files&id={$attachment->id}&name={$attachment->name}{/devblocks_url}">
			<b>{$attachment->name}</b>
			({$attachment->storage_size|devblocks_prettybytes} 
			- 
			{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{'display.convo.unknown_format'|devblocks_translate|capitalize}{/if})
		</a>
	</li>
	{/foreach}
</ul>
{/if}