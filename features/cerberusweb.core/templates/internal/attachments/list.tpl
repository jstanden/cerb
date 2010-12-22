{$a_map = DAO_AttachmentLink::getLinksAndAttachments($context, $context_id)}
{$links = $a_map.links}
{$attachments = $a_map.attachments}

{if !empty($links) && !empty($attachments)}
<div>
<b>{$translate->_('display.convo.attachments_label')|capitalize}</b><br>
<ul style="margin-top:0px;margin-bottom:5px;">
	{foreach from=$links item=link name=links}
	{$attachment = $attachments.{$link->attachment_id}}
	{if !empty($attachment)}
		<li>
			<a href="{devblocks_url}c=files&p={$link->guid}&name={$attachment->display_name|escape:'url'}{/devblocks_url}" target="_blank" style="font-weight:bold;color:rgb(50,120,50);">{$attachment->display_name}</a>
			(  
			{$attachment->storage_size|devblocks_prettybytes} 
			- 
			{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{$translate->_('display.convo.unknown_format')|capitalize}{/if}
			 )
		</li>
	{/if}
	{/foreach}
</ul>
</div>
{/if}
