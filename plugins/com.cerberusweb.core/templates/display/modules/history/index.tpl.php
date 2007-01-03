<table cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td style="border-bottom:1px solid rgb(200,200,200);border-right:1px solid rgb(200,200,200);" align="center"><b>{$translate->say('ticket.created')}</b></td>
		<td style="border-bottom:1px solid rgb(200,200,200);"><b>{$translate->say('ticket.subject')}</b></td>
		<td style="border-bottom:1px solid rgb(200,200,200);border-left:1px solid rgb(200,200,200);" align="center"><b>{$translate->say('ticket.mask')}</b></td>
	</tr>

	{foreach from=$history_tickets item=history name=histories}
	<tr>
		<td width="0%" nowrap="nowrap" style="border-right:1px solid rgb(220,220,220);" valign="top" align="right">
		{$history.t_created_date|date_format}
		</td>
		<td width="100%" valign="top">
			<a href="index.php?c=core.module.dashboard&a=viewticket&id={$history.t_id}">{$history.t_subject}</a>
			<i>({if $history.t_status=='O'}
				{$translate->say('status.open')|lower}
			{elseif $history.t_status=='W'}
				{$translate->say('status.waiting')|lower}
			{elseif $history.t_status=='C'}
				{$translate->say('status.closed')|lower}
			{elseif $history.t_status=='D'}
				{$translate->say('status.deleted')|lower}
			{/if})</i>
		</td>
		<td width="0%" nowrap="nowrap" align="right" valign="top" style="border-left:1px solid rgb(200,200,200);">
			{$history.t_mask}
		</td>
	</tr>
	{/foreach}
</table>

