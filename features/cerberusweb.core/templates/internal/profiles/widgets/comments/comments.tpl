{$height = $widget->extension_params.height|default:0|round}
<div id="widget{$widget->id}Comments" style="{if $height}max-height:{$height}px;overflow:auto;{/if}">
	<div style="margin-bottom:10px;">
		{if $active_worker->hasPriv("contexts.{$context}.comment")}
		<button type="button" class="cerb-button-add" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$context} context.id:{$context_id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>
		{/if}
	</div>

	<div class="cerb-comments">
		{foreach from=$comments item=comment}
		<div id="comment{$comment->id}" class="cerb-comment">
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

	// Make sure we only ever listen once
	$parent
		.off('cerb_profile_comment_created.widget{$widget->id}')
		.on('cerb_profile_comment_created.widget{$widget->id}', function(e) {
			if(e.comment_id && e.comment_html) {
				$('<div id="comment' + e.comment_id + '"/>')
					.addClass('cerb-comment')
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
				$('<div id="comment' + e.id + '"/>')
					.addClass('cerb-comment')
					.html(e.comment_html)
					.prependTo($container)
				;
			}
		})
	;

	// Scroll to and highlight comments in permalinks

	var anchor = window.location.hash.substr(1);

	if('comment' == anchor.substr(0,7)) {
		var $anchor = $('#' + anchor);

		if($anchor.length > 0) {
			var offset = $anchor.offset();
			window.scrollTo(offset.left, offset.top);

			$anchor.find('> div.block').effect('highlight', { }, 1000);
		}
	}
});
</script>