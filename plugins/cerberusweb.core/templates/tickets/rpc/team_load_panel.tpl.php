<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"><img src="{devblocks_url}images/businessmen.gif{/devblocks_url}" align="absmiddle">&nbsp;</td>
		<td align="left" width="100%"><h1>{$translate->_('teamwork.my_team_loads')|capitalize}</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="genericPanel.hide();"></form></td>
	</tr>
</table>
<div style="height:300px;overflow:auto;background-color:rgb(247, 247, 255);border:1px solid rgb(230,230,230);margin:2px;padding:3px;">
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
		{assign var=team_totals value=$team_counts.0}
		{foreach from=$teams item=team key=team_id}
   			{assign var=team_count value=$team_counts.$team_id}
		
			{if $team_totals}
				{math assign=percent equation="(x/y)*50" x=$team_count.tickets y=$team_totals.tickets format="%0.0f"}
			{/if}
		<tr>
			<td class="tableCellBg" width="100%" style="padding:2px;"><a href="{devblocks_url}c=tickets&a=dashboards&t=team&id={$team->id}{/devblocks_url}"><b>{$team->name}</b></a> ({$team_count.tickets})</td>
			<td class="tableCellBgIndent" width="0%" nowrap="nowrap" style="width:51px;"><img src="{devblocks_url}images/cerb_graph.gif{/devblocks_url}" width="{$percent}" height="15"><img src="{devblocks_url}images/cer_graph_cap.gif{/devblocks_url}" height="15" width="1"></td>
		</tr>
		{foreachelse}
		<tr>
			<td colspan="2" class="tableCellBg">{$translate->_('dashboard.no_teams')}</td>
		</tr>
		{/foreach}
	</table>
</div>