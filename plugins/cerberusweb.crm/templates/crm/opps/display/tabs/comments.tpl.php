<h2>Add Comment</h2>
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDisplayOppAddComment">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveOppComment">
<input type="hidden" name="opp_id" value="{$opp->id}">

<textarea name="comment" rows="5" cols="60" style="width:98%;"></textarea><br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>
<br>

{if !empty($comments)}
<h2 style="color:rgb(80,80,80);">Comments (Newest to Oldest)</h2>

{foreach from=$comments item=comment key=comment_id}
	{assign var=comment_worker_id value=$comment->worker_id}
	{if !empty($workers.$comment_worker_id)}
		{assign var=comment_worker value=$workers.$comment_worker_id}
		<div id="displayComment{$comment_id}">
		<div class="note">
		<h2 style="display:inline;">{$comment_worker->getName()} wrote:</h2>
		&nbsp; <i>{$comment->created_date|devblocks_date}</i>
		
		{if $active_worker->is_superuser || $active_worker->id==$comment_worker_id}
			&nbsp; <a href="javascript:;" onclick="if(confirm('Are you sure you want to delete this comment?')){literal}{{/literal}clearDiv('displayComment{$comment_id}');genericAjaxGet('','c=crm&a=deleteOppComment&comment_id={$comment->id}&opp_id={$comment->opportunity_id}');{literal}}{/literal}">delete comment</a>
		{/if}
		
		<br>
		<blockquote style="margin:10px;">{$comment->content|escape|nl2br}</blockquote>
		</div>
		</div>
	{/if}
{/foreach}
{/if}

<br>