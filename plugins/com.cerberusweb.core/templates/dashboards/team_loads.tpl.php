<table cellpadding="0" cellspacing="0" border="0" class="tableOrange" width="200" class="tableBg">
	<tr>
		<td class="tableThOrange" nowrap="nowrap"> <img src="images/businessmen.gif"> Team Loads</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="3" cellspacing="1" border="0" width="200" class="tableBg">
				{foreach from=$teams item=team}
				<tr>
					<td class="tableRowBg"><a href="index.php?c=core.module.dashboard&a=clickteam&id={$team->id}"><b>{$team->name}</b></a> (xx)</td>
				</tr>
				{foreachelse}
				<tr>
					<td class="tableRowBg">No visible teams.</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
</table>
