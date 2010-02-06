<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="1%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="98%" nowrap="nowrap"><h1>Groups</h1></td>
		<td align="left" width="1%" nowrap="nowrap" style="padding-right:5px;"><a href="{devblocks_url}c=groups&a=config&id={$group->id}{/devblocks_url}">configuration</a></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formGroupsPeek" name="formGroupsPeek" onsubmit="return false;">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveGroupsPanel">
<input type="hidden" name="group_id" value="{$group->id}">
<input type="hidden" name="view_id" value="{$view_id}">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Name: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$group->name|escape}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Buckets: </td>
		<td width="100%">
			
		</td>
	</tr>
</table>

<button type="button" onclick="genericPanel.dialog('close');genericAjaxPost('formGroupsPeek', 'view{$view_id}')"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="genericPanel.dialog('close');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
<br>
</form>
