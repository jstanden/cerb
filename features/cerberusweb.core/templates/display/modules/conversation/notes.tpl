{if isset($message_notes.$message_id) && is_array($message_notes.$message_id)}
	{foreach from=$message_notes.$message_id item=note name=notes key=note_id}
		{assign var=comment_address value=$note->getAddress()}
		<div class="message_note" style="margin:10px;margin-left:20px;">
			<h3 style="display:inline;"><span style="color:rgb(222,73,0);background-color:rgb(255,235,104);">{$translate->_('display.ui.sticky_note')|lower}</span> <a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&address_id={$comment_address->id}', this, false, '500');" title="{$comment_address->email|escape}">{if empty($comment_address->first_name) && empty($comment_address->last_name)}&lt;{$comment_address->email|escape}&gt;{else}{$comment_address->getName()}{/if}</a></h3>&nbsp;
			<a href="javascript:;" onclick="genericAjaxGet('','c=internal&a=commentDelete&id={$note->id|escape}');$(this).closest('div.message_note').remove();">{$translate->_('common.delete')|lower}</a><br>
			<b>{$translate->_('message.header.date')|capitalize}:</b> {$note->created|devblocks_date}<br>
			{if !empty($note->comment)}<pre class="emailbody" style="padding-top:10px;">{$note->comment|escape|devblocks_hyperlinks}</pre>{/if}
		</div>
	{/foreach}
{/if}
