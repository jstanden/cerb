{$height = $widget->extension_params.height|round}
<div id="widget{$widget->id}Comments" style="{if $height}max-height:{$height}px;overflow:auto;{/if}">
	<div style="margin-bottom:10px;">
		{if $active_worker->hasPriv("contexts.{$context}.comment")}
		<button type="button" class="cerb-button-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$context} context.id:{$context_id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>
		{/if}
	</div>

	{if empty($comments)}
		<div style="color:rgb(120,120,120);text-align:center;font-size:1.2em;">
		(there are no comments on this record)
		</div>
	{else}
		{foreach from=$comments item=comment}
		<div id="comment{$comment->id}">
			{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl"}
		</div>
		{/foreach}
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $comments = $('#widget{$widget->id}Comments');
	var $button = $comments.find('button.cerb-button-add');
	
	var $parent = $comments.closest('div.cerb-profile-widget');
	var $refresh = $parent.find('li.cerb-profile-widget-menu--refresh > a');
	
	$button
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			
			// [TODO] We can insert this into the DOM 
			// [TODO] Just use an event on widget instead?
			$refresh.click();
		})
	;
});
</script>