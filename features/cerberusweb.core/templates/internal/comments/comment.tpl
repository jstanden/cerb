{assign var=comment_address value=$comment->getAddress()}
<div id="comment{$comment->id}">
	<div class="block" style="overflow:auto;">
		<h3 style="display:inline;"><span style="background-color:rgb(232,242,254);color:rgb(71,133,210);">{$translate->_('common.comment')|lower}</span> <a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&address_id={$comment_address->id}', null, false, '500');" title="{$comment_address->email}">{if empty($comment_address->first_name) && empty($comment_address->last_name)}&lt;{$comment_address->email}&gt;{else}{$comment_address->getName()}{/if}</a></h3> &nbsp;
		{if !$readonly && ($active_worker->is_superuser || $comment_address->email==$active_worker->email)}
			<a href="javascript:;" onclick="if(confirm('Are you sure you want to permanently delete this comment?')) { genericAjaxGet('', 'c=internal&a=commentDelete&id={$comment->id}', function(o) { $('#comment{$comment->id}').remove(); } ); } ">{$translate->_('common.delete')|lower}</a>
		{/if}
		
		{$extensions = DevblocksPlatform::getExtensions('cerberusweb.comment.badge', true)}
		{foreach from=$extensions item=extension}
			{$extension->render($comment)}
		{/foreach}
		<br>
		
		{if isset($comment->created)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$comment->created|devblocks_date}<br>{/if}
		<pre class="emailbody" style="padding-top:10px;">{$comment->comment|trim|escape|devblocks_hyperlinks nofilter}</pre>
		<br>
		
		{* Attachments *}
		{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_COMMENT}" context_id=$comment->id}
	</div>
	<br>
</div>

