<table cellspacing="0" cellpadding="2" border="0" style="padding-top:5px;">
{$editable_params = $view->getEditableParams()}
{if isset($editable_params.t_team_id) || isset($editable_params.t_category_id)}
	<tr>
		<td style="padding-right:20px;" nowrap="nowrap" valign="top">
			&laquo; <a href="javascript:;" onclick="ajax.viewRemoveFilter('{$view_id}', ['t_team_id','t_category_id']);"> any group</a>
		</td>
	</tr>
{/if}
{foreach from=$counts item=category key=category_id}
	<tr>
		<td nowrap="nowrap" valign="middle">
			<a href="javascript:;" onclick="ajax.viewAddFilter('{$view_id}', 't_team_id', 'in', { 'team_id[]':'{$category_id}' } );" style="font-weight:bold;">{$category.label}</a> 
		</td>
		<td align="right" style="padding-left:10px;">
			<div class="badge">{$category.hits}</div>
		</td>
	</tr>
	
	{if isset($category.children) && !empty($category.children)}
	{foreach from=$category.children item=subcategory key=subcategory_id}
	<tr>
		<td nowrap="nowrap" valign="middle" style="padding-left:10px;">
			<a href="javascript:;" onclick="ajax.viewAddFilter('{$view_id}', 't_team_id', 'in', { 'team_id[]':'{$category_id}', 'bucket_id[]':'{$subcategory_id}' } );">{$subcategory.label}</a> 
		</td>
		<td nowrap="nowrap" align="right" style="padding-left:10px;">
			<div class="badge badge-lightgray">{$subcategory.hits}</div>
		</td>
	</tr>
	{/foreach}
	{/if}
{/foreach}
</table>

