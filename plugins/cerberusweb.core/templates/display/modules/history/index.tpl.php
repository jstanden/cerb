<table cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td style="border-bottom:1px solid rgb(200,200,200);border-right:1px solid rgb(200,200,200);" align="center"><b>{$translate->_('ticket.created')}</b></td>
		<td style="border-bottom:1px solid rgb(200,200,200);"><b>{$translate->_('ticket.subject')}</b></td>
		<td style="border-bottom:1px solid rgb(200,200,200);border-left:1px solid rgb(200,200,200);" align="center"><b>{$translate->_('ticket.mask')}</b></td>
	</tr>

	{foreach from=$history_tickets item=history name=histories}
	<tr>
		<td width="0%" nowrap="nowrap" style="border-right:1px solid rgb(220,220,220);" valign="top" align="right">
		{$history.t_created_date|date_format}
		</td>
		<td width="100%" valign="top">
			<a href="{devblocks_url}c=display&id={$history.t_mask}{/devblocks_url}">{$history.t_subject}</a>
			<i>({if $history.t_status=='O'}
				{$translate->_('status.open')|lower}
			{elseif $history.t_status=='W'}
				{$translate->_('status.waiting')|lower}
			{elseif $history.t_status=='C'}
				{$translate->_('status.closed')|lower}
			{elseif $history.t_status=='D'}
				{$translate->_('status.deleted')|lower}
			{/if})</i>
		</td>
		<td width="0%" nowrap="nowrap" align="right" valign="top" style="border-left:1px solid rgb(200,200,200);">
			{$history.t_mask}
		</td>
	</tr>
	{/foreach}
</table>

