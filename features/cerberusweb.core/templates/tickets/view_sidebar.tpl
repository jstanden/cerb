<div class="block">
<h1>Subtotals</h1>
{if 'status'==$view->renderSubtotals}<b>status</b>{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=status&view_id={$view_id|escape}');">status</a>{/if}
 | {if 'group'==$view->renderSubtotals}<b>group</b>{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=group&view_id={$view_id|escape}');">group</a>{/if}
 | {if 'worker'==$view->renderSubtotals}<b>worker</b>{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=worker&view_id={$view_id|escape}');">worker</a>{/if}

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
</div>
<br>
