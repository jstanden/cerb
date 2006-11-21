<table cellpadding="0" cellspacing="0" border="0" class="tableGreen" width="200" class="tableBg">
	<tr>
		<td class="tableThGreen" nowrap="nowrap"> <img src="images/folder_network.gif"> Mailbox Loads</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="3" cellspacing="1" border="0" width="200" class="tableBg">
				{foreach from=$mailboxes item=mailbox}
				<tr>
					<td class="tableRowBg"><a href="index.php?c=core.module.dashboard&a=clickmailbox&id={$mailbox->id}"><b>{$mailbox->name}</b></a> (10)</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
</table>
