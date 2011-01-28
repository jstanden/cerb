<table cellspacing="0" cellpadding="2" border="0" width="220" style="padding-top:5px;">
{$editable_params = $view->getEditableParams()}
{$filter_workers = '*_workers'}
{if isset($editable_params.$filter_workers)}
	<tr>
		<td style="padding-right:20px;" nowrap="nowrap" valign="top">
			&laquo; <a href="javascript:;" onclick="ajax.viewRemoveFilter('{$view_id}', ['*_workers']);"> any owner</a>
		</td>
	</tr>
{/if}
{foreach from=$counts item=category key=category_id}
	<tr>
		<td style="padding-right:20px;" nowrap="nowrap" valign="top">
			<a href="javascript:;" onclick="ajax.viewAddFilter('{$view_id}', '*_workers', '', { 'worker_id[]':'{$category_id}' } );" style="font-weight:bold;">{$category.label}</a> <div class="badge">{$category.hits}</div>
		</td>
	</tr>
{/foreach}
</table>
