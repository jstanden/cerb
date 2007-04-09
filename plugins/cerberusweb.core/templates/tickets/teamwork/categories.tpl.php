{if !empty($categories)}
<table cellpadding="0" cellspacing="0" border="0" class="tableOrange" width="220" class="tableBg">
	<tr>
		<td class="tableThOrange" nowrap="nowrap"> <img src="{devblocks_url}images/bookmark.gif{/devblocks_url}"> {$translate->_('teamwork.categories')|capitalize}</td>
	</tr>
	<tr>
		<td class="tableCellBg" width="100%" style="padding:2px;">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
				{foreach from=$categories item=category}
					{if $team_total_count}
						{math assign=percent equation="(x/y)*50" x=$team->count y=$team_total_count format="%0.0f"}
					{/if}
				<tr>
					<td class="tableCellBg" width="100%"><label><input type="checkbox" name="" value="{$category->id}"> {$category->name} (0)</label></td>
					<td class="tableCellBgIndent" width="0%" nowrap="nowrap" style="width:51px;"><img src="{devblocks_url}images/cerb_graph.gif{/devblocks_url}" width="{$percent}" height="15"><img src="{devblocks_url}images/cer_graph_cap.gif{/devblocks_url}" height="15" width="1"></td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="2" class="tableCellBg">{$translate->_('dashboard.no_categories')}</td>
				</tr>
				{/foreach}
			</table>
			<div align="right">
				<input type="button" value="{$translate->_('common.filter')|capitalize}" onclick="">
			</div>
		</td>
	</tr>
</table>
{/if}