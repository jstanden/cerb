{if isset($message_notes.$message_id) && is_array($message_notes.$message_id)}
	{foreach from=$message_notes.$message_id item=note name=notes key=note_id}
		{assign var=comment_address value=$note->getAddress()}
		<div class="message_note" style="margin:10px;margin-left:20px;">
			<span class="tag" style="color:rgb(238,88,31);">{$translate->_('display.ui.sticky_note')|lower}</span>
			<b><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$comment_address->id}', this, false, '500');" title="{$comment_address->email}">{if empty($comment_address->first_name) && empty($comment_address->last_name)}&lt;{$comment_address->email}&gt;{else}{$comment_address->getName()}{/if}</a></b>&nbsp;
			<a href="javascript:;" onclick="if(confirm('Are you sure you want to permanently delete this note?')) { genericAjaxGet('','c=internal&a=commentDelete&id={$note->id}');$(this).closest('div.message_note').remove(); } ">{$translate->_('common.delete')|lower}</a><br>
			<b>{$translate->_('message.header.date')|capitalize}:</b> {$note->created|devblocks_date} (<abbr title="{$note->created|devblocks_date}">{$note->created|devblocks_prettytime}</abbr>)<br>
			{if !empty($note->comment)}<pre class="emailbody" style="padding-top:10px;">{$note->comment|escape|devblocks_hyperlinks nofilter}</pre>{/if}
		</div>
	{/foreach}
{/if}
