<table cellpadding="2" cellspacing="1" width="100%">
	{foreach from=$history_tickets item=history name=histories}
	<tr>
		<td align="right" width="0%" nowrap="nowrap">
		{$history.t_mask}
		</td>
		<td width="100%">
		<a href="index.php?c=core.module.dashboard&a=viewticket&id={$history.t_id}">{$history.t_subject}</a>
		</td>
		<td width="0%" nowrap="nowrap">
		{if $history.t_status=='O'}
			{$translate->say('status.open')|lower}
		{elseif $result.t_status=='W'}
			{$translate->say('status.waiting')|lower}
		{elseif $result.t_status=='C'}
			{$translate->say('status.closed')|lower}
		{elseif $result.t_status=='D'}
			{$translate->say('status.deleted')|lower}
		{/if}
		</td>
		<td width="0%" nowrap="nowrap">
		{$history.t_created_date|date_format}
		</td>
	</tr>
	{/foreach}
</table>

