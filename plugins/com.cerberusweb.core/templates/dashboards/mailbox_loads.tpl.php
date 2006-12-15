<table cellpadding="0" cellspacing="0" border="0" class="tableGreen" width="250" class="tableBg">
	<tr>
		<td class="tableThGreen" nowrap="nowrap"> <img src="images/folder_network.gif"> {$translate->say('dashboard.mailbox_loads')|capitalize}</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="0" cellspacing="1" border="0" width="100%" class="tableBg">
				{foreach from=$mailboxes item=mailbox}
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;"><a href="index.php?c=core.module.dashboard&a=clickmailbox&id={$mailbox->id}"><b>{$mailbox->name}</b></a> (xx)</td>
					<td class="tableCellBgIndent" width="0%" nowrap="nowrap" style="width:50px;"><img src="images/cerb_graph.gif" width="30" height="15"><img src="images/cer_graph_cap.gif" height="15" width="1"></td>
				</tr>
				{foreachelse}
				<tr>
					<td class="tableCellBg">{$translate->say('dashboard.no_mailboxes')}</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
</table>
