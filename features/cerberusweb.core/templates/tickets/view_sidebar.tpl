<div class="block">
<h1>Subtotals</h1>
{if 'status'==$category}<b>status</b>{else}<a href="javascript:;" title="status" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=status&view_id={$view_id|escape}');">status</a>{/if}
 | {if 'group'==$category}<b>group</b>{else}<a href="javascript:;" title="group" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=group&view_id={$view_id|escape}');">group</a>{/if}
 | {if 'worker'==$category}<b>worker</b>{else}<a href="javascript:;" title="worker" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category=worker&view_id={$view_id|escape}');">worker</a>{/if}

<table cellspacing="0" cellpadding="2" border="0" width="220" style="padding-top:5px;">
{foreach from=$counts item=category}
	<tr>
		<td style="padding-right:20px;" nowrap="nowrap" valign="top">
			<span style="font-weight:bold;">{$category.label}</span> <div class="badge">{$category.hits}</div>
			{if isset($category.children) && !empty($category.children)}
			{foreach from=$category.children item=subcategory}
			<div style="display:block;padding-left:10px;padding-bottom:2px;padding-top:2px;">
				<span>{$subcategory.label}</span> <div class="badge badge-lightgray">{$subcategory.hits}</div><br>
			</div>
			{/foreach}
			{/if}
		</td>
	</tr>
{/foreach}
</table>
</div>
<br>
