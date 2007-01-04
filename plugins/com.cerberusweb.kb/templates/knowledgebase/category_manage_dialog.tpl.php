<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="100%" nowrap="nowrap"><img src="images/cerb_fnr_header.jpg"></td>
	</tr>
</table>
<img src="images/folder_add.gif" align="absmiddle"> <a href="#">add category</a><br>
<div style="height:200px;overflow:auto;background-color:rgb(255,255,255);border:1px solid rgb(180,180,180);margin:2px;padding:3px;">
	{foreach from=$node->children item=category name=categories}
		<img src="images/folder.gif" align="absmiddle"> <a href="#">{$category->name}</a><br>
	{/foreach}
</div>
<input type="button" value="{$translate->say('common.save_changes')|capitalize}">
<input type="button" value="{$translate->say('common.close')|capitalize}" onclick="kbAjax.categoryPanel.hide();">
<br>
