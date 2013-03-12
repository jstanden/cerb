{assign var=journal_address value=$entry->getAddress()}
<div id="entry{$entry->id}">
	<div class="block{if $entry->isinternal} state2{elseif $entry->ispublic} state{/if}" style="overflow:auto;">
		<span class="tag" style="color:rgb(71,133,210);">
			{if $entry->ispublic}{$translate->_('common.public')} {/if}{if $entry->isinternal}{$translate->_('common.internal')} {/if}
			{$translate->_('common.journal_entry')|lower}
		</span>
		
		<b style="font-size:1.3em;">
			{if empty($journal_address)}
				(system)
			{else} 
				<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$journal_address->id}', null, false, '500');" title="{$journal_address->email}">{if empty($journal_address->first_name) && empty($journal_address->last_name)}&lt;{$journal_address->email}&gt;{else}{$journal_address->getName()}{/if}</a>
			{/if}
		</b>
				
		&nbsp;
		
		{if !$readonly && ($active_worker->is_superuser || $journal_address->email==$active_worker->email)}
			<a href="javascript:;" onclick="if(confirm('Are you sure you want to permanently delete this journal entry?')) { genericAjaxGet('', 'c=internal&a=journalDelete&id={$entry->id}', function(o) { $('#entry{$entry->id}').remove(); } ); } ">{$translate->_('common.delete')|lower}</a>
		{/if}
		
		{*
		{$extensions = DevblocksPlatform::getExtensions('cerberusweb.comment.badge', true)}
		{foreach from=$extensions item=extension}
			{$extension->render($comment)}
		{/foreach}
		*}
		<br>
		
		{if isset($entry->created)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$entry->created|devblocks_date} (<abbr title="{$entry->created|devblocks_date}">{$entry->created|devblocks_prettytime}</abbr>)<br>{/if}
		<table border="0" cellpadding="1" cellspacing="0">
			<tr>
				<td><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/traffic_light_{$entry->state}.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" alt="" /></td>
				<td>&nbsp;</td>
				<td><pre class="emailbody" style="padding-top:10px;">{$entry->journal|trim|escape|devblocks_hyperlinks nofilter}</pre></td>
			</tr>
		</table>
		<br>
		
		{* Attachments *}
		{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context="{CerberusContexts::CONTEXT_JOURNAL}" context_id=$entry->id}
	</div>
	<br>
</div>

