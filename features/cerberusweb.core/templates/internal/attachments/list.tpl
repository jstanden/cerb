{$a_map = DAO_AttachmentLink::getLinksAndAttachments($context, $context_id)}
{$links = $a_map.links}
{$attachments = $a_map.attachments}
{$uniq_id = uniqid()}

{if !empty($links) && !empty($attachments)}
<div id="attachments{$uniq_id}">
<b>{'common.attachments'|devblocks_translate|capitalize}:</b><br>
<ul style="margin-top:0px;margin-bottom:5px;">
	{foreach from=$links item=link name=links}
	{$attachment = $attachments.{$link->attachment_id}}
	{if !empty($attachment)}
		<li>
			<a href="{devblocks_url}c=files&p={$link->guid}&name={$attachment->display_name|escape:'url'}{/devblocks_url}" target="_blank" style="font-weight:bold;color:rgb(50,120,50);">{$attachment->display_name}</a>
			(  
			{$attachment->storage_size|devblocks_prettybytes} 
			- 
			{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{'display.convo.unknown_format'|devblocks_translate|capitalize}{/if}
			 )
			 <span style="margin-left:10px;" class="download"><a href="{devblocks_url}c=files&p={$link->guid}&name={$attachment->display_name|escape:'url'}{/devblocks_url}?download=">download</a></span>
		</li>
	{/if}
	{/foreach}
</ul>
</div>

<script type="text/javascript">
$('#attachments{$uniq_id} ul li').hover(
	function() {
		$(this).find('span.download').show();
	},
	function() {
		$(this).find('span.download').hide();
	}
);
</script>
{/if}
