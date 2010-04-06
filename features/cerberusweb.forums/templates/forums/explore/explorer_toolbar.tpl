<form id="frmForumsExplorerToolbar" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
	<input type="hidden" name="c" value="forums">
	<input type="hidden" name="a" value="">
	<input type="hidden" name="hash" value="{$item->hash|escape}">
	<input type="hidden" name="item_id" value="{$item->pos|escape}">
	<input type="hidden" name="id" value="{$item->params.id|escape}">
	
	<select name="worker_id" onchange="genericAjaxPost('frmForumsExplorerToolbar','','c=forums&a=ajaxExploreAssign');">
		<option value="">-- {$translate->_('common.assign')|lower} --</option>
		{foreach from=$workers item=worker key=worker_id}
			<option value="{$worker_id}" {if $item->params.worker_id==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
		{/foreach}
		<option value="0">- {$translate->_('common.unassign')|capitalize} -</option>
	</select>
	{if !$item->params.is_closed}<button type="button" onclick="$(this).fadeOut();genericAjaxPost('frmForumsExplorerToolbar','','c=forums&a=ajaxExploreClose');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.close')|capitalize}</button>{/if}
	{if $item->params.is_closed}<button type="button" onclick="$(this).fadeOut();genericAjaxPost('frmForumsExplorerToolbar','','c=forums&a=ajaxExploreReopen');"><span class="cerb-sprite sprite-folder_out"></span> {$translate->_('common.reopen')|capitalize}</button>{/if}
</form>
