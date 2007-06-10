<table cellpadding="2" cellspacing="0" width="100%">
	<tr style="background-color:rgb(240,240,240);">
		<td style="border-bottom:1px solid rgb(200,200,200);border-right:1px solid rgb(200,200,200);" align="center"><b>{$translate->_('ticket.created')}</b></td>
		<td style="border-bottom:1px solid rgb(200,200,200);border-right:1px solid rgb(200,200,200);"><b>{$translate->_('ticket.subject')}</b></td>
		<td style="border-bottom:1px solid rgb(200,200,200);" align="center"><b>{$translate->_('ticket.mask')}</b></td>
	</tr>

	{foreach from=$history_tickets item=history name=histories}
	<tr>
		<td width="0%" nowrap="nowrap" style="border-right:1px solid rgb(220,220,220);border-bottom:1px solid rgb(220,220,220);" valign="top" align="right">
		{$history.t_created_date|date_format}
		</td>
		<td width="100%" valign="top" style="border-right:1px solid rgb(220,220,220);border-bottom:1px solid rgb(220,220,220);">
			<a href="{devblocks_url}c=display&id={$history.t_mask}{/devblocks_url}" class="ticketLink"><b>{if $history.t_is_closed}<strike>{$history.t_subject}</strike>{else}{$history.t_subject}{/if}</b></a>
		</td>
		<td width="0%" nowrap="nowrap" align="right" valign="top" style="border-bottom:1px solid rgb(220,220,220);">
			{$history.t_mask}
		</td>
	</tr>
	{/foreach}
</table>

