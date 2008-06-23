{assign var=comment_address value=$comment->getAddress()}
<div id="displayComment{$comment->id}">
	<div class="ticket_comment">
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_plain.png{/devblocks_url}" align="top">
		<b style="color:rgb(71,133,210);">[comment] {$comment_address->email|escape:"htmlall"|nl2br}</b> &nbsp;  
		{*<a href="">edit</a>*}
		{if $active_worker->is_superuser || $comment_address->email==$active_worker->email}
			<a href="javascript:;" onclick="if(confirm('Are you sure you want to delete this comment?')){literal}{{/literal}document.location='{devblocks_url}c=display&a=deleteComment{/devblocks_url}?comment_id={$comment->id}&ticket_id={$comment->ticket_id}';{literal}}{/literal}">delete</a>
		{/if}	
		
		<br>
		{if isset($comment->created)}<b>Date:</b> {$comment->created|devblocks_date}<br>{/if}
		<br>
		{$comment->comment|trim|escape:"htmlall"|makehrefs|nl2br} <br>
	</div>
	<br>
</div>

