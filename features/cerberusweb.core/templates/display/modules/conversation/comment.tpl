{assign var=comment_address value=$comment->getAddress()}
<div id="comment{$comment->id}">
	<div class="block">
		<h3 style="display:inline;"><span style="background-color:rgb(232,242,254);color:rgb(71,133,210);">{$translate->_('common.comment')|lower}</span> <a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&address_id={$comment_address->id}', this, false, '500');" title="{$comment_address->email|escape}">{if empty($comment_address->first_name) && empty($comment_address->last_name)}&lt;{$comment_address->email|escape}&gt;{else}{$comment_address->getName()}{/if}</a></h3> &nbsp;  
		{if $active_worker->is_superuser || $comment_address->email==$active_worker->email}
			<a href="javascript:;" onclick="if(confirm('Are you sure you want to permanently delete this comment?')) { genericAjaxGet('', 'c=display&a=deleteComment&comment_id={$comment->id}&ticket_id={$comment->ticket_id}', function(o) { $('#comment{$comment->id}').remove(); } ); } ">{$translate->_('common.delete')|lower}</a>
		{/if}	
		
		<br>
		{if isset($comment->created)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$comment->created|devblocks_date}<br>{/if}
		<pre class="emailbody" style="padding-top:10px;">{$comment->comment|trim|escape|devblocks_hyperlinks}</pre>
	</div>
	<br>
</div>

