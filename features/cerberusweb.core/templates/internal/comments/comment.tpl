{$owner_meta = $comment->getOwnerMeta()}
<div id="comment{$comment->id}">
	<div class="block" style="overflow:auto;">
		<span class="tag" style="color:rgb(71,133,210);">{$translate->_('common.comment')|lower}</span>
		
		<b style="font-size:1.3em;">
			{if empty($owner_meta)}
				(system)
			{else}
				{if $owner_meta.context_ext instanceof IDevblocksContextPeek} 
				<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$comment->owner_context}&context_id={$comment->owner_context_id}', null, false, '500');">{$owner_meta.name}</a>
				{elseif !empty($owner_meta.permalink)} 
				<a href="{$owner_meta.permalink}" target="_blank">{$owner_meta.name}</a>
				{else}
				{$owner_meta.name}
				{/if}
			{/if}
		</b>
		
		({$owner_meta.context_ext->manifest->name|lower})
		
		<div class="toolbar" style="display:none;float:right;margin-right:20px;">
			{if $comment->context == CerberusContexts::CONTEXT_TICKET}
				<a href="{devblocks_url}c=profiles&type=ticket&mask={$ticket->mask}&focus=comment&focus_id={$comment->id}{/devblocks_url}">{'common.permalink'|devblocks_translate|lower}</a>
			{/if}
			
			{if !$readonly && ($active_worker->is_superuser || ($comment->owner_context == CerberusContexts::CONTEXT_WORKER && $comment->owner_context_id == $active_worker->id))}
				 <a href="javascript:;" style="margin-left:10px;" onclick="if(confirm('Are you sure you want to permanently delete this comment?')) { genericAjaxGet('', 'c=internal&a=commentDelete&id={$comment->id}', function(o) { $('#comment{$comment->id}').remove(); } ); } ">{$translate->_('common.delete')|lower}</a>
			{/if}
		</div>
		
		
		{$extensions = DevblocksPlatform::getExtensions('cerberusweb.comment.badge', true)}
		{foreach from=$extensions item=extension}
			{$extension->render($comment)}
		{/foreach}
		<br>
		
		{if isset($comment->created)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$comment->created|devblocks_date} (<abbr title="{$comment->created|devblocks_date}">{$comment->created|devblocks_prettytime}</abbr>)<br>{/if}
		
		<pre class="emailbody" style="padding-top:10px;">{$comment->comment|trim|escape|devblocks_hyperlinks nofilter}</pre>
		<br clear="all">
		
		{* Attachments *}
		{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id}
	</div>
	<br>
</div>

<script type="text/javascript">
$('#comment{$comment->id}').hover(
	function() {
		$(this).find('div.toolbar').show();
	},
	function() {
		$(this).find('div.toolbar').hide();
	}
);
</script>