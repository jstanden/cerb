<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveAddListPanel">
<H1>Add Worklist</H1>
A worklist is a custom ticket list driven by your own columns and search criteria.  You can have multiple 
worklists on a "workspace", which is simply the name given to them collectively.<br>
<br>

<b>List Title:</b><br>
<input type="text" name="list_title" value="" size="" style="width:98%;"><br>
<br>

<b>Add List to Workspace:</b><br>
{if !empty($workspaces)}
Existing: <select name="workspace">
	{foreach from=$workspaces item=workspace}
	<option value="{$workspace|escape}">{$workspace}</option>
	{/foreach}
</select><br>
-or-<br>
{/if}
New: <input type="text" name="new_workspace" size="35" maxlength="32" value=""><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>

</form>
