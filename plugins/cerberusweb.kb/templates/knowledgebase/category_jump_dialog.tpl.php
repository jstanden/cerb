<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="100%" nowrap="nowrap"><img src="{devblocks_url}images/cerb_fnr_header.jpg{/devblocks_url}"></td>
	</tr>
</table>
<b>Jump to category:</b><br>
<div style="height:250px;overflow:auto;background-color:rgb(255,255,255);border:1px solid rgb(180,180,180);margin:2px;padding:3px;">
	{foreach from=$sorted key=si item=level name=sorted}
		{assign var=category value=$tree.$si}
		{if $level==1 && !$smarty.foreach.sorted.first}<img src="{devblocks_url}images/spacer.gif{/devblocks_url}" align="absmiddle" height="10" width="1"><br>{/if}
		<img src="{devblocks_url}images/spacer.gif{/devblocks_url}" align="absmiddle" width="{math equation="(x-1)*14" x=$level}" height="1">
		<img src="{devblocks_url}images/folder.gif{/devblocks_url}" align="absmiddle"> <a href="{devblocks_url}c=kb&id={$category->id}{/devblocks_url}">{$category->name}</a> ({$category->hits})<br>
	{/foreach}
</div>
<input type="button" value="{$translate->_('common.cancel')|capitalize}" onclick="kbAjax.categoryPanel.hide();">
<br>
