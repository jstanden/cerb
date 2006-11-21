<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%" class="tableBg">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">My Tickets</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="" class="tableThLink">refresh</a><span style="font-size:12px"> | </span>
			<a href="index.php?c=core.module.dashboard&a=searchview&id={$id}" class="tableThLink">search</a><span style="font-size:12px"> | </span>
			<a href="javascript:;" onclick="getCustomize({$id});" class="tableThLink">customize</a>
		</td>
	</tr>
</table>
<div id="customize{$id}"></div>
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="tableRowBg">
	<tr>
		<td align="center" class="tableThBg">all</td>
		<td class="tableThBg">ID</td>
		<td class="tableThBg">Status</td>
		<td class="tableThBg">Last Wrote</td>
	</tr>

	{foreach from=$tickets item=ticket key=idx}
	<tr>
		<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value=""></td>
		<td colspan="3"><a href="index.php?c=core.module.dashboard&a=viewticket&id={$ticket->id}" class="normalLink"><b>{$ticket->subject}</b></a></td>
	</tr>
	<tr>
		<td>{$ticket->mask}</td>
		<td>{$ticket->status}</td>
		<td>{$ticket->last_wrote}</td>
	</tr>
	<tr>
		<td class="tableBg" colspan="4"></td>
	</tr>
	{/foreach}

	<tr>
		<td class="tableBg" colspan="4">
			<select name="">
				<option value="">-- perform action --
			</select>
		</td>
	</tr>
	<tr>
		<td class="tableBg" colspan="4" align="right">
				(Showing x-y of {$total})
		</td>
	</tr>
	
</table>

