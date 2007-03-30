<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"><img src="{devblocks_url}images/folder_network.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>{$translate->_('dashboard.mailbox_loads')|capitalize}</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="ajax.mailboxPanel.hide();"></form></td>
	</tr>
</table>
<div style="height:300px;overflow:auto;background-color:rgb(247, 247, 255);border:1px solid rgb(230,230,230);margin:2px;padding:3px;">
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
		{foreach from=$mailboxes item=mailbox}
			{if $total_count}
				{math assign=percent equation="(x/y)*50" x=$mailbox->count y=$total_count format="%0.0f"}
			{/if}
		<tr>
			<td class="tableCellBg" width="100%" style="padding:2px;"><a href="{devblocks_url}c=tickets&a=mailbox&id={$mailbox->id}{/devblocks_url}"><b>{$mailbox->name}</b></a> ({$mailbox->count})</td>
			<td class="tableCellBgIndent" width="0%" nowrap="nowrap" style="width:51px;"><img src="{devblocks_url}images/cerb_graph.gif{/devblocks_url}" width="{$percent}" height="15"><img src="{devblocks_url}images/cer_graph_cap.gif{/devblocks_url}" height="15" width="1"></td>
		</tr>
		{foreachelse}
		<tr>
			<td class="tableCellBg">{$translate->_('dashboard.no_mailboxes')}</td>
		</tr>
		{/foreach}
	</table>
</div>