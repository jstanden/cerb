<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="100%" nowrap="nowrap"><img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/cerb_fnr_header.jpg"></td>
	</tr>
</table>
<b>Jump to category:</b><br>
<div style="height:250px;overflow:auto;background-color:rgb(255,255,255);border:1px solid rgb(180,180,180);margin:2px;padding:3px;">
	{foreach from=$sorted key=si item=level name=sorted}
		{assign var=category value=$tree.$si}
		{if $level==1 && !$smarty.foreach.sorted.first}<img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/spacer.gif" align="absmiddle" height="10" width="1"><br>{/if}
		<img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/spacer.gif" align="absmiddle" width="{math equation="(x-1)*14" x=$level}" height="1">
		<img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/folder.gif" align="absmiddle"> <a href="{$smarty.const.DEVBLOCKS_WEBPATH}index.php?c=core.module.kb&a=click&id={$category->id}">{$category->name}</a> ({$category->hits})<br>
	{/foreach}
</div>
<input type="button" value="{$translate->say('common.cancel')|capitalize}" onclick="kbAjax.categoryPanel.hide();">
<br>
