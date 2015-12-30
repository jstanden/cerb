{$owner_meta = $comment->getOwnerMeta()}
<div id="comment{$comment->id}">
	<div class="block" style="overflow:auto;margin-bottom:10px;">
		<span class="tag" style="background-color:rgb(71,133,210);color:white;margin-right:5px;">{'common.comment'|devblocks_translate|lower}</span>
		
		<b style="font-size:1.3em;">
			{if empty($owner_meta)}
				(system)
			{else}
				{if $owner_meta.context_ext instanceof IDevblocksContextPeek} 
				{* [TODO] Use peek triggers? *}
				<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$comment->owner_context}&context_id={$comment->owner_context_id}', null, false, '50%');">{$owner_meta.name}</a>
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
				<button type="button" onclick="document.location='{devblocks_url}c=profiles&type=ticket&mask={$ticket->mask}&focus=comment&focus_id={$comment->id}{/devblocks_url}';"><span class="glyphicons glyphicons-link" title="{'common.permalink'|devblocks_translate|lower}"></span></button>
			{/if}
			
			{if !$readonly && ($active_worker->is_superuser || ($comment->owner_context == CerberusContexts::CONTEXT_WORKER && $comment->owner_context_id == $active_worker->id))}
				 <button type="button" onclick="if(confirm('Are you sure you want to permanently delete this comment?')) { genericAjaxGet('', 'c=internal&a=commentDelete&id={$comment->id}', function(o) { $('#comment{$comment->id}').remove(); } ); } "><span class="glyphicons glyphicons-circle-remove" title="{'common.delete'|devblocks_translate|lower}"></span></button>
			{/if}
		</div>
		
		{if isset($owner_meta.context_ext->manifest->params.alias)}
		<div style="float:left;margin:0px 5px 5px 0px;">
			<img src="{devblocks_url}c=avatars&context={$owner_meta.context_ext->manifest->params.alias}&context_id={$owner_meta.id}{/devblocks_url}?v={$owner_meta.updated}" style="height:64px;width:64px;border-radius:64px;">
		</div>
		{/if}
		
		<br>
		
		{if isset($comment->created)}<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$comment->created|devblocks_date} ({$comment->created|devblocks_prettytime})<br>{/if}
		
		<pre class="emailbody" style="padding-top:10px;">{$comment->comment|trim|escape|devblocks_hyperlinks nofilter}</pre>
		<br clear="all">
		
		{* Attachments *}
		{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id}
	</div>
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