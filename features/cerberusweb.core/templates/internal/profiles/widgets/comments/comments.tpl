{$height = $widget->extension_params.height|round}
<div id="widget{$widget->id}Comments" style="{if $height}max-height:{$height}px;overflow:auto;{/if}">
	<div style="margin-bottom:10px;">
		{if $active_worker->hasPriv("contexts.{$context}.comment")}
		<button type="button" class="cerb-button-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$context} context.id:{$context_id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>
		{/if}
	</div>

	<div class="cerb-comments">
		{foreach from=$comments item=comment}
		<div id="comment{$comment->id}">
			{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl"}
		</div>
		{/foreach}
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $comments = $('#widget{$widget->id}Comments');
	var $container = $comments.find('.cerb-comments');
	var $button = $comments.find('button.cerb-button-add');
	
	var $parent = $comments.closest('div.cerb-profile-widget');
	var $refresh = $parent.find('li.cerb-profile-widget-menu--refresh > a');
	
	// Make sure we only ever listen once
	$parent
		.off('cerb_profile_comment_created.widget{$widget->id}')
		.on('cerb_profile_comment_created.widget{$widget->id}', function(e) {
			if(e.comment_id && e.comment_html) {
				var $new_comment = $('<div id="comment' + e.comment_id + '"/>')
					.html(e.comment_html)
					.prependTo($container)
				;
			}
		})
		;
	
	$button
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();

			if(e.id && e.comment_html) {
				var $new_comment = $('<div id="comment' + e.id + '"/>')
					.html(e.comment_html)
					.prependTo($container)
				;
			}
		})
	;
});
</script>