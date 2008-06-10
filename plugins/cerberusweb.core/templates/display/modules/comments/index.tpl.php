<h2>Add Comment</h2>
<form action="{devblocks_url}{/devblocks_url}" method="post" id="displayAddCommentForm">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveComment">
<input type="hidden" name="ticket_id" value="{$ticket_id}">

<textarea name="comment" rows="5" cols="60" style="width:98%;"></textarea><br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>
<br>

{if !empty($comments)}
	{foreach from=$comments item=comment key=comment_id}
		{include file="$path/display/modules/conversation/comment.tpl.php"}
	{/foreach}
{/if}

<br>