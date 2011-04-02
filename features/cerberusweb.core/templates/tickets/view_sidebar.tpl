<fieldset>
	<legend>{'common.subtotals'|devblocks_translate|capitalize}</legend>
{* [TODO] HACK!! *}
{if $view->id == 'mail_workflow'}
	 {if 'group'==$view->renderSubtotals}<b>group</b>{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=group&view_id={$view_id}');">group</a>{/if}
	 | {if 'worker'==$view->renderSubtotals}<b>{'common.watcher'|devblocks_translate|lower}</b>{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=worker&view_id={$view_id}');">{'common.watcher'|devblocks_translate|lower}</a>{/if}
{else}
	{if 'status'==$view->renderSubtotals}<b>status</b>{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=status&view_id={$view_id}');">status</a>{/if}
	 | {if 'group'==$view->renderSubtotals}<b>group</b>{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=group&view_id={$view_id}');">group</a>{/if}
	 | {if 'worker'==$view->renderSubtotals}<b>{'common.watcher'|devblocks_translate|lower}</b>{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=worker&view_id={$view_id}');">{'common.watcher'|devblocks_translate|lower}</a>{/if}
{/if}

{if empty($view->renderSubtotalsClickable)}
	{include file="devblocks:cerberusweb.core::internal/views/view_subtotal_sidebar.tpl"}
{else}
	{if 'status'==$view->renderSubtotals}
		{include file="devblocks:cerberusweb.core::tickets/view/sidebar/clickable_subtotal_status.tpl"}
	{elseif 'group'==$view->renderSubtotals}
		{include file="devblocks:cerberusweb.core::tickets/view/sidebar/clickable_subtotal_group.tpl"}
	{elseif 'worker'==$view->renderSubtotals}
		{include file="devblocks:cerberusweb.core::tickets/view/sidebar/clickable_subtotal_worker.tpl"}
	{else}
		{include file="devblocks:cerberusweb.core::internal/views/view_subtotal_sidebar.tpl"}
	{/if}
{/if}
</fieldset>
