{$owner_meta = $comment->getOwnerMeta()}
{$target_context = $comment->getTargetContext(false)}
{$is_writeable = Context_Comment::isWriteableByActor($comment, $active_worker)}

<div class="block" style="overflow:auto;margin-bottom:10px;">
	<span class="tag" style="background-color:rgb(71,133,210);color:white;margin-right:5px;">{'common.comment'|devblocks_translate|lower}</span>
	
	<b style="font-size:1.3em;">
		{if empty($owner_meta)}
			(system)
		{else}
			{if $owner_meta.context_ext instanceof IDevblocksContextPeek} 
			<a href="javascript:;" class="cerb-peek-trigger" data-context="{$comment->owner_context}" data-context-id="{$comment->owner_context_id}">{$owner_meta.name}</a>
			{elseif !empty($owner_meta.permalink)} 
			<a href="{$owner_meta.permalink}" target="_blank" rel="noopener">{$owner_meta.name}</a>
			{else}
			{$owner_meta.name}
			{/if}
		{/if}
	</b>
	
	{if $comment->owner_context == CerberusContexts::CONTEXT_WORKER}
		{$actor = $comment->getActorDictionary()}
		 &nbsp;
		{$actor->title}
	{else}
		({$owner_meta.context_ext->manifest->name|lower})
	{/if}

	{if !$embed}
	<div class="toolbar" style="display:none;float:right;margin-right:20px;">
		{if $is_writeable}<button type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="{$comment->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel" title="{'common.edit'|devblocks_translate|lower}"></span></button>{/if}

		{$permalink_url = "{devblocks_url full=true}c=profiles&type={$target_context->params.alias}&id={$comment->context_id}{/devblocks_url}/#comment{$comment->id}"}
		<button type="button" onclick="genericAjaxPopup('permalink', 'c=internal&a=showPermalinkPopup&url={$permalink_url|escape:'url'}');" title="{'common.permalink'|devblocks_translate|lower}"><span class="glyphicons glyphicons-link"></span></button>
	</div>
	{/if}
	
	{if isset($owner_meta.context_ext->manifest->params.alias)}
	<div style="float:left;margin:0px 5px 5px 0px;">
		<img src="{devblocks_url}c=avatars&context={$owner_meta.context_ext->manifest->params.alias}&context_id={$owner_meta.id}{/devblocks_url}?v={$owner_meta.updated}" style="height:64px;width:64px;border-radius:64px;">
	</div>
	{/if}
	
	<br>
	
	{if isset($comment->created)}<b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$comment->created|devblocks_date} ({$comment->created|devblocks_prettytime})<br>{/if}

	{if $comment->is_markdown}
		<div class="commentBodyHtml">{$comment->getContent() nofilter}</div>
	{else}
		<pre class="emailbody" style="padding-top:10px;">{$comment->getContent()|trim|escape|devblocks_hyperlinks nofilter}</pre>
	{/if}
	<br clear="all">
	
	{* Attachments *}
	{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id attachments=[]}
</div>

{if !$embed}
<script type="text/javascript">
$(function() {
	$('#comment{$comment->id}')
		.hover(
			function() {
				$(this).find('div.toolbar').show();
			},
			function() {
				$(this).find('div.toolbar').hide();
			}
		)
		.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
				.on('cerb-peek-saved', function(e) {
					if(e.id && e.comment_html)
						$('#comment' + e.id).html(e.comment_html);
				})
				.on('cerb-peek-deleted', function(e) {
					$('#comment' + e.id).remove();
				})
		;
});
</script>
{/if}