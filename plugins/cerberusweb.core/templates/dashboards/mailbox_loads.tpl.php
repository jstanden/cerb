<table cellpadding="0" cellspacing="0" border="0" class="tableGreen" width="220" class="tableBg">
	<tr>
		<td class="tableThGreen" nowrap="nowrap"> <img src="{devblocks_url}images/folder_network.gif{/devblocks_url}"> {$translate->say('dashboard.mailbox_loads')|capitalize}</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="0" cellspacing="1" border="0" width="100%" class="tableBg">
				{foreach from=$mailboxes item=mailbox}
					{if $total_count}
						{math assign=percent equation="(x/y)*50" x=$mailbox->count y=$total_count format="%0.0f"}
					{/if}
				<tr>
					<td class="tableCellBg" width="100%" style="padding:2px;"><a href="{devblocks_url}c=dashboard&a=mailbox&id={$mailbox->id}{/devblocks_url}"><b>{$mailbox->name}</b></a> ({$mailbox->count})</td>
					<td class="tableCellBgIndent" width="0%" nowrap="nowrap" style="width:51px;"><img src="{devblocks_url}images/cerb_graph.gif{/devblocks_url}" width="{$percent}" height="15"><img src="{devblocks_url}images/cer_graph_cap.gif{/devblocks_url}" height="15" width="1"></td>
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
