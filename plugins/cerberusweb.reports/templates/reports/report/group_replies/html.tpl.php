{if $invalidDate}<font color="red"><b>Invalid Date specified.  Please try again.</b></font>{/if}
<br>


{if !empty($group_counts)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$groups item=group key=group_id}
		{if !empty($group_counts.$group_id)}
		
		<tr>
			<td style="padding-right:20px;">{$groups.$group_id->name}</td>
			<td align="right">{$group_counts.$group_id.hits}</td>
			<td></td>
		</tr>
		
		{/if}
	{/foreach}
	</table>
{/if}


