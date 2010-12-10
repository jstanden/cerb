<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewDoCopy">
<input type="hidden" name="view_id" value="{$view_id}">

<H2>Copy Worklist</H2>

You can copy this worklist into your own workspaces, allowing you to put your favorite information in a single place.<br>
<br>

<b>Worklist Name:</b><br>
<input type="text" name="list_title" value="{$view->name}" size="45"><br>
<br>

<b>Add List to Workspace:</b><br>
{if !empty($workspaces)}
Existing: <select name="workspace">
	{foreach from=$workspaces item=workspace}
	<option value="{$workspace}">{$workspace}</option>
	{/foreach}
</select><br>
-or-<br>
{/if}
New: <input type="text" name="new_workspace" size="32" maxlength="32" value=""><br>
<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');$('#{$view_id}_tips').html('');" style=""><span class="cerb-sprite sprite-delete"></span> Do nothing</button><br>
</form>
