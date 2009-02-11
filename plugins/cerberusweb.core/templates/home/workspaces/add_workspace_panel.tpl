<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="home">
<input type="hidden" name="a" value="doAddWorkspace">
<H1>{$translate->_('dashboard.add_view')|capitalize}</H1>
<br>

<b>Worklist Name:</b><br>
<input type="text" name="name" value="" size="35" style="width:100%;"><br>
<br>

<b>Worklist Type:</b><br>
<select name="source">
	{foreach from=$sources item=mft key=mft_id}
	<option value="{$mft_id}">{$mft->name}</option>
	{/foreach}
</select><br>
<br>

<b>Add Worklist to Workspace:</b><br>
{if !empty($workspaces)}
Existing: <select name="workspace">
	{foreach from=$workspaces item=workspace}
	<option value="{$workspace|escape}">{$workspace}</option>
	{/foreach}
</select><br>
-or-<br>
{/if}
New: <input type="text" name="new_workspace" size="32" maxlength="32" value=""><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
</form>
