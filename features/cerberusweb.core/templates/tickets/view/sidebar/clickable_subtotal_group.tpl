<table cellspacing="0" cellpadding="2" border="0" width="220" style="padding-top:5px;">
{foreach from=$counts item=category key=category_id}
	<tr>
		<td style="padding-right:20px;" nowrap="nowrap" valign="top">
			<a href="javascript:;" onclick="ajax.viewAddFilter('{$view_id}', 't_team_id', 'in', { 'team_id[]':'{$category_id}' } );" style="font-weight:bold;">{$category.label}</a> <div class="badge">{$category.hits}</div>
			{if isset($category.children) && !empty($category.children)}
			{foreach from=$category.children item=subcategory key=subcategory_id}
			<div style="display:block;padding-left:10px;padding-bottom:2px;padding-top:2px;">
				<a href="javascript:;" onclick="ajax.viewAddFilter('{$view_id}', 't_team_id', 'in', { 'team_id[]':'{$category_id}', 'bucket_id[]':'{$subcategory_id}' } );">{$subcategory.label}</a> <div class="badge badge-lightgray">{$subcategory.hits}</div><br>
			</div>
			{/foreach}
			{/if}
		</td>
	</tr>
{/foreach}
</table>

<script type="text/javascript">
	
</script>
