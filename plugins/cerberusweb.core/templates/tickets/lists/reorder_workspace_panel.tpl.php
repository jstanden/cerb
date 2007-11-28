<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doReorderWorkspace">
<input type="hidden" name="workspace" value="{$workspace}">
<H1>Arrange: {$workspace}</H1>
<br>

<b>Order Worklists:</b> (1 is first, 99 is last)<br>
{foreach from=$worklists item=worklist name=worklists key=worklist_id}
{assign var=worklist_view value=$worklist->list_view}
<input type="hidden" name="ids[]" value="{$worklist->id}">
<input type="text" name="pos[]" size="2" maxlength="2" value="{$worklist->list_pos}"> {$worklist_view->title}<br>
{/foreach}

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>

</form>
