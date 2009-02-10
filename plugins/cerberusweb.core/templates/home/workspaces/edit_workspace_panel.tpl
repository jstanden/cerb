<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="home">
<input type="hidden" name="a" value="doEditWorkspace">
<input type="hidden" name="workspace" value="{$workspace}">
<H1>{$workspace}</H1>
<br>

<b>Rename Workspace:</b><br>
<input type="text" name="rename_workspace" value="" size="35" style="width:100%;"><br>
<br>

<b>Choose the display order of your worklists:</b><br>
{foreach from=$worklists item=worklist name=worklists key=worklist_id}
{assign var=worklist_view value=$worklist->list_view}
<input type="hidden" name="ids[]" value="{$worklist->id}">
<input type="text" name="pos[]" size="2" maxlength="2" value="{counter name=worklistPos}"> {$worklist_view->title}<br>
{/foreach}
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
</form>
