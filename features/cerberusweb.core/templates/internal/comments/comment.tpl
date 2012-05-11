{assign var=comment_address value=$comment->getAddress()}
<div id="comment{$comment->id}">
	<div class="block" style="overflow:auto;">
		<span class="tag tag-blue">{$translate->_('common.comment')|lower}</span>
		
		<h3 style="display:inline;">
			{if empty($comment_address)}
				(system)
			{else} 
				<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$comment_address->id}', null, false, '500');" title="{$comment_address->email}">{if empty($comment_address->first_name) && empty($comment_address->last_name)}&lt;{$comment_address->email}&gt;{else}{$comment_address->getName()}{/if}</a>
			{/if}
		</h3>
		
		&nbsp;
		
		{if !$readonly && ($active_worker->is_superuser || $comment_address->email==$active_worker->email)}
			<a href="javascript:;" onclick="if(confirm('Are you sure you want to permanently delete this comment?')) { genericAjaxGet('', 'c=internal&a=commentDelete&id={$comment->id}', function(o) { $('#comment{$comment->id}').remove(); } ); } ">{$translate->_('common.delete')|lower}</a>
		{/if}
		
		{$extensions = DevblocksPlatform::getExtensions('cerberusweb.comment.badge', true)}
		{foreach from=$extensions item=extension}
			{$extension->render($comment)}
		{/foreach}
		<br>
		
		{if isset($comment->created)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$comment->created|devblocks_date} (<abbr title="{$comment->created|devblocks_date}">{$comment->created|devblocks_prettytime}</abbr>)<br>{/if}
		<pre class="emailbody" style="padding-top:10px;">{$comment->comment|trim|escape|devblocks_hyperlinks nofilter}</pre>
		<br>
		
		{* Attachments *}
		{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id}
	</div>
	<br>
</div>

