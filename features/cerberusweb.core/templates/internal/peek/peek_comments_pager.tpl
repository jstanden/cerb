{$cerb_comments_divid = uniqid()} 
<div class="cerb-comments" id="{$cerb_comments_divid}">
	{foreach from=$comments item=comment name=comments}
		<div class="cerb-comment" style="{if !$smarty.foreach.comments.last}display:none;{/if}">
		{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$comment}
		</div>
	{/foreach}
	
	{if count($comments) > 1}
		<div style="float:right;" class="cerb-pg-set">
			<a href="javascript:;" class="cerb-pg-prev">&lt;Prev</a>
			<span class="cerb-pg-status"></span>
			<a href="javascript:;" class="cerb-pg-next">Next&gt;</a>
		</div>
		
		<br clear="all" style="clear:all;">
	{/if}
</div>

{if count($comments) > 1}
<script type="text/javascript">
$(function() {
	var $comments_container = $('#{$cerb_comments_divid}');
	var $comments_prev = $comments_container.find('a.cerb-pg-prev');
	var $comments_next = $comments_container.find('a.cerb-pg-next');
	var $comments_status = $comments_container.find('span.cerb-pg-status');
	var $comments = $comments_container.find('div.cerb-comment');
	$comments.hide().filter(':last').show();
	
	$comments_container.on('redraw', function() {
		var $comment_selected = $comments.filter(':visible');
		var comment_index = $comments.index($comment_selected);
		var comment_pos = comment_index + 1;
		$comments_status.html('(' + (comment_pos) + ' of ' + $comments.length + ')');

		if(comment_pos == 1) {
			$comments_prev.hide();
		} else {
			$comments_prev.show();
		}
		
		if(comment_pos == $comments.length) {
			$comments_next.hide();
		} else {
			$comments_next.show();
		}
	});
	
	$comments_prev.click(function() {
		var $comment_selected = $comments.filter(':visible');
		$comment_selected.hide().prev().show();
		$comments.trigger('redraw');
	});
	
	$comments_next.click(function() {
		var $comment_selected = $comments.filter(':visible');
		$comment_selected.hide().next().show();
		$comments.trigger('redraw');
	});
	
	$comments.trigger('redraw');
});
</script>
{/if}