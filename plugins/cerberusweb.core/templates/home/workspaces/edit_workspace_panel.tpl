<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="home">
<input type="hidden" name="a" value="doEditWorkspace">
<input type="hidden" name="workspace" value="{$workspace}">
<h1 style="color:rgb(0,150,0);">{$workspace}</h1>
<br>

<b>Rename Workspace:</b><br>
<input type="text" name="rename_workspace" value="" size="35" style="width:100%;"><br>
<br>

<div style="height:300px;overflow:auto;margin:2px;padding:3px;">
<table width="100%">
	<tr>
		<td align="center"><b>{$translate->_('common.order')|capitalize}</b></td>
		<td><b>Worklist</b></td>
		<td align="center"><b>{$translate->_('common.remove')|capitalize}</b></td>
	</tr>
	
	{foreach from=$worklists item=worklist name=worklists key=worklist_id}
	{assign var=worklist_view value=$worklist->list_view}
	<tr>
		<td align="center">
			<input type="hidden" name="ids[]" value="{$worklist->id}">
			<input type="text" name="pos[]" size="2" maxlength="2" value="{counter name=worklistPos}">
		</td>
		<td>
			<input type="text" name="names[]" value="{$worklist_view->title|escape}" style="width:100%;">
		</td>
		<td align="center">
			<input type="checkbox" name="deletes[]" value="{$worklist->id}">
		</td>
	</tr>
	{/foreach}
</table>
</div>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>
