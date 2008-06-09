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
<h2 style="color:rgb(80,80,80);">Comments (Newest to Oldest)</h2>

{foreach from=$comments item=comment key=comment_id}
	{assign var=comment_address_id value=$comment->address_id}
	{if isset($comment_addresses.$comment_address_id)}
		{assign var=comment_address value=$comment_addresses.$comment_address_id}
		<div id="displayComment{$comment_id}">
			<div class="note">
			<h2 style="display:inline;">{$comment_address->email} wrote:</h2>
			&nbsp; <i>{$comment->created|date_format:'%b %e, %Y'} GMT</i>
			
			{* [TODO] Allow workers to edit their own comments *}
			{if $active_worker->is_superuser} {* || $active_worker->id==$comment_worker_id*}
				&nbsp; <a href="javascript:;" onclick="if(confirm('Are you sure you want to delete this comment?')){literal}{{/literal}clearDiv('displayComment{$comment_id}');genericAjaxGet('','c=display&a=deleteComment&comment_id={$comment_id}&ticket_id={$ticket_id}');{literal}}{/literal}">delete comment</a>
			{/if}
			<br>
			<blockquote style="margin:10px;">{$comment->comment|escape|nl2br}</blockquote>
			</div>
		</div>
	{/if}
{/foreach}
{/if}

<br>