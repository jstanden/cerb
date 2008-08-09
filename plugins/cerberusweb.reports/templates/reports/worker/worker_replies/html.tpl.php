{if $invalidDate}<font color="red"><b>Invalid Date specified.  Please try again.</b></font>{/if}
<br>


{if !empty($worker_counts)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$workers item=worker key=worker_id}
		{if !empty($worker_counts.$worker_id)}
		{assign var=counts value=$worker_counts.$worker_id}
		
		<tr>
			<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);"><h2>{$workers.$worker_id->getName()}</h2></td>
		</tr>
		
		{foreach from=$counts item=team_hits key=team_id}
			{if is_numeric($team_id)}
			<tr>
				<td style="padding-right:20px;">{$groups.$team_id->name}</td>
				<td align="right">{$team_hits}</td>
				<td></td>
			</tr>
			{/if}
		{/foreach}
		
		<tr>
			<td></td>
			<td style="border-top:1px solid rgb(200,200,200);" align="right"><b>{$counts.total}</b></td>
			<td style="padding-left:10px;"><b>(avg: {math equation="x/y" x=$counts.total y=$age_dur format="%0.2f"}/day)</b></td>
		</tr>
		
		{/if}
	{/foreach}
	</table>
{/if}


